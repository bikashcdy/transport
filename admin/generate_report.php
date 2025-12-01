<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header("Location: ../index.php");
    exit;
}

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../db.php';
require_once '../libs/fpdf/fpdf.php';
date_default_timezone_set('Asia/Kathmandu');

// Check database connection
if (!$conn) {
    die("Database connection failed. Please check your db.php configuration.");
}

// Helper function to safely execute queries
function safeQuery($conn, $query, $errorMsg = "Query failed") {
    $result = $conn->query($query);
    if ($result === false) {
        die("$errorMsg: " . $conn->error . "<br>Query: $query");
    }
    return $result;
}

// Helper function to safely get count
function getCount($conn, $query) {
    $result = safeQuery($conn, $query);
    $row = $result->fetch_assoc();
    return $row['total'] ?? 0;
}

// ====================== FETCH ALL DATA ======================

// 1. Executive Summary
$totalBookings = getCount($conn, "SELECT COUNT(*) AS total FROM bookings");
$totalUsers = getCount($conn, "SELECT COUNT(*) AS total FROM users WHERE user_type='user'");
$totalVehicles = getCount($conn, "SELECT COUNT(*) AS total FROM vehicles");

// FIXED: Include both completed AND confirmed bookings for revenue
$revenueResult = safeQuery($conn, "SELECT COALESCE(SUM(price), 0) AS total FROM bookings WHERE status IN ('completed', 'confirmed')");
$totalRevenue = $revenueResult->fetch_assoc()['total'] ?? 0;
$avgBookingValue = $totalBookings > 0 ? $totalRevenue / $totalBookings : 0;

// 2. Booking Status with Revenue
$statusData = [];
$statusQuery = safeQuery($conn, "
    SELECT 
        status,
        COUNT(*) as count,
        COALESCE(SUM(price), 0) as revenue
    FROM bookings
    GROUP BY status
");

while ($row = $statusQuery->fetch_assoc()) {
    $statusData[$row['status']] = [
        'count' => $row['count'],
        'revenue' => $row['revenue']
    ];
}

$pendingBookings = $statusData['pending']['count'] ?? 0;
$confirmedBookings = $statusData['confirmed']['count'] ?? 0;
$completedBookings = $statusData['completed']['count'] ?? 0;
$cancelledBookings = $statusData['cancelled']['count'] ?? 0;

// 3. Monthly Revenue Trend - FIXED: Include confirmed bookings
$monthlyRevenue = [];
$result = safeQuery($conn, "
    SELECT 
        DATE_FORMAT(created_at, '%Y-%m') AS month,
        COALESCE(SUM(CASE WHEN status IN ('completed', 'confirmed') THEN price ELSE 0 END), 0) AS revenue,
        COUNT(*) AS bookings,
        COALESCE(SUM(CASE WHEN status = 'completed' THEN price ELSE 0 END), 0) AS completed_revenue,
        COALESCE(SUM(CASE WHEN status = 'confirmed' THEN price ELSE 0 END), 0) AS confirmed_revenue
    FROM bookings
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
    GROUP BY DATE_FORMAT(created_at, '%Y-%m')
    ORDER BY month DESC
");

while ($row = $result->fetch_assoc()) {
    $monthlyRevenue[] = $row;
}

// 4. Top Performing Vehicles - FIXED: Include confirmed bookings
$topVehicles = [];
$result = safeQuery($conn, "
    SELECT 
        v.id,
        v.vehicle_number,
        v.vehicle_name,
        v.vehicle_type_id,
        v.capacity,
        COUNT(b.id) AS total_bookings,
        COALESCE(SUM(CASE WHEN b.status IN ('completed', 'confirmed') THEN b.price ELSE 0 END), 0) AS revenue
    FROM vehicles v
    LEFT JOIN bookings b ON v.id = b.vehicle_id
    GROUP BY v.id, v.vehicle_number, v.vehicle_name, v.vehicle_type_id, v.capacity
    ORDER BY total_bookings DESC
    LIMIT 10
");

while ($row = $result->fetch_assoc()) {
    $topVehicles[] = $row;
}

// 5. Top Customers - FIXED: Include confirmed bookings
$topUsers = [];
$result = safeQuery($conn, "
    SELECT 
        u.name, 
        u.email,
        COUNT(b.id) AS total_bookings, 
        COALESCE(SUM(CASE WHEN b.status IN ('completed', 'confirmed') THEN b.price ELSE 0 END), 0) AS total_spent,
        MAX(b.created_at) AS last_booking
    FROM users u
    LEFT JOIN bookings b ON u.id = b.user_id
    WHERE u.user_type = 'user'
    GROUP BY u.id, u.name, u.email
    ORDER BY total_bookings DESC
    LIMIT 10
");

while ($row = $result->fetch_assoc()) {
    $topUsers[] = $row;
}

// 6. Recent Bookings
$recentBookings = [];
$result = safeQuery($conn, "
    SELECT 
        b.booking_id,
        b.user_name,
        b.vehicle_id,
        b.trip_start,
        b.trip_end,
        b.price,
        b.status,
        DATEDIFF(b.trip_end, b.trip_start) as duration
    FROM bookings b
    ORDER BY b.created_at DESC
    LIMIT 15
");

while ($row = $result->fetch_assoc()) {
    $recentBookings[] = $row;
}

// 7. Booking Duration Analysis - FIXED: Include confirmed bookings
$durationAnalysis = [];
if ($totalBookings > 0) {
    $result = safeQuery($conn, "
        SELECT 
            CASE 
                WHEN DATEDIFF(trip_end, trip_start) <= 2 THEN '1-2 days'
                WHEN DATEDIFF(trip_end, trip_start) <= 5 THEN '3-5 days'
                WHEN DATEDIFF(trip_end, trip_start) <= 10 THEN '6-10 days'
                ELSE '10+ days'
            END as duration_range,
            COUNT(*) as count,
            AVG(CASE WHEN status IN ('completed', 'confirmed') THEN price ELSE NULL END) as avg_price,
            SUM(CASE WHEN status IN ('completed', 'confirmed') THEN price ELSE 0 END) as total_revenue
        FROM bookings
        WHERE status IN ('completed', 'confirmed')
        GROUP BY duration_range
        ORDER BY 
            CASE duration_range
                WHEN '1-2 days' THEN 1
                WHEN '3-5 days' THEN 2
                WHEN '6-10 days' THEN 3
                ELSE 4
            END
    ");

    while ($row = $result->fetch_assoc()) {
        $durationAnalysis[] = $row;
    }
}

// 8. Day of Week Analysis - FIXED: Include confirmed bookings
$dayAnalysis = [];
$recentBookingsCount = getCount($conn, "SELECT COUNT(*) AS total FROM bookings WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)");

if ($recentBookingsCount > 0) {
    $result = safeQuery($conn, "
        SELECT 
            DAYNAME(created_at) as day_name,
            COUNT(*) as bookings,
            COALESCE(SUM(CASE WHEN status IN ('completed', 'confirmed') THEN price ELSE 0 END), 0) as revenue
        FROM bookings
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        GROUP BY DAYOFWEEK(created_at), DAYNAME(created_at)
        ORDER BY DAYOFWEEK(created_at)
    ");

    while ($row = $result->fetch_assoc()) {
        $dayAnalysis[] = $row;
    }
}

$reportData = json_encode([
    'summary' => [
        'total_bookings' => $totalBookings,
        'total_users' => $totalUsers,
        'total_vehicles' => $totalVehicles,
        'total_revenue' => $totalRevenue,
        'avg_booking_value' => $avgBookingValue
    ],
    'booking_status' => $statusData,
    'monthly_revenue' => $monthlyRevenue,
    'top_vehicles' => $topVehicles,
    'top_customers' => $topUsers,
    'recent_bookings' => $recentBookings,
    'duration_analysis' => $durationAnalysis,
    'day_analysis' => $dayAnalysis,
    'generated_at' => date('Y-m-d H:i:s')
]);

$reportType = 'pdf_report';
$generatedBy = $_SESSION['user_id'];

$stmt = $conn->prepare("INSERT INTO reports (report_type, report_data, generated_by) VALUES (?, ?, ?)");
$stmt->bind_param("ssi", $reportType, $reportData, $generatedBy);
$stmt->execute();
$stmt->close();

// ====================== CREATE ENHANCED PDF ======================
class EnhancedPDF extends FPDF {
    function Header() {
        $this->SetFont('Arial', 'B', 24);
        $this->SetTextColor(41, 128, 185);
        $this->Cell(0, 12, 'BookingNepal', 0, 1, 'C');
        
        $this->SetFont('Arial', 'B', 16);
        $this->SetTextColor(52, 73, 94);
        $this->Cell(0, 8, 'Business Analytics Report', 0, 1, 'C');
        
        $this->SetFont('Arial', '', 10);
        $this->SetTextColor(127, 140, 141);
        $this->Cell(0, 5, 'Generated: ' . date('F d, Y - h:i A'), 0, 1, 'C');
        $this->Ln(3);
        
        $this->SetDrawColor(52, 152, 219);
        $this->SetLineWidth(0.5);
        $this->Line(10, $this->GetY(), 200, $this->GetY());
        $this->Ln(8);
    }

    function Footer() {
        $this->SetY(-15);
        $this->SetFont('Arial', 'I', 8);
        $this->SetTextColor(149, 165, 166);
        $this->Cell(95, 10, 'Confidential Report', 0, 0, 'L');
        $this->Cell(95, 10, 'Page ' . $this->PageNo(), 0, 0, 'R');
    }

    function SectionTitle($title, $r=52, $g=152, $b=219) {
        $this->SetFont('Arial', 'B', 13);
        $this->SetFillColor($r, $g, $b);
        $this->SetTextColor(255, 255, 255);
        $this->SetDrawColor($r, $g, $b);
        $this->Cell(0, 9, '  ' . $title, 1, 1, 'L', true);
        $this->Ln(4);
        $this->SetTextColor(0, 0, 0);
    }

    function MetricCard($label, $value, $x, $y, $color) {
        $this->SetXY($x, $y);
        $this->SetFillColor($color[0], $color[1], $color[2]);
        $this->SetTextColor(255, 255, 255);
        $this->SetFont('Arial', 'B', 9);
        $this->Cell(45, 7, $label, 1, 2, 'C', true);
        $this->SetX($x);
        $this->SetFont('Arial', 'B', 12);
        $this->Cell(45, 9, $value, 1, 0, 'C', true);
    }

    function ColorTable($header, $data, $widths, $alignments = []) {
        // Header
        $this->SetFillColor(52, 152, 219);
        $this->SetTextColor(255, 255, 255);
        $this->SetFont('Arial', 'B', 9);
        $this->SetDrawColor(52, 152, 219);
        
        foreach ($header as $i => $col) {
            $align = $alignments[$i] ?? 'C';
            $this->Cell($widths[$i], 7, $col, 1, 0, $align, true);
        }
        $this->Ln();

        // Data rows
        $this->SetFont('Arial', '', 8);
        $this->SetDrawColor(200, 200, 200);
        $fill = false;
        
        foreach ($data as $row) {
            $this->SetFillColor($fill ? 245 : 255, $fill ? 245 : 255, $fill ? 245 : 255);
            $this->SetTextColor(44, 62, 80);
            
            foreach ($row as $i => $col) {
                $align = $alignments[$i] ?? 'L';
                $this->Cell($widths[$i], 6, $col, 1, 0, $align, true);
            }
            $this->Ln();
            $fill = !$fill;
        }
    }

    function EmptyMessage($message) {
        $this->SetFont('Arial', 'I', 10);
        $this->SetTextColor(150, 150, 150);
        $this->Cell(0, 10, $message, 0, 1, 'C');
        $this->SetTextColor(0, 0, 0);
    }
}

$pdf = new EnhancedPDF();
$pdf->AddPage();
$pdf->SetFont('Arial', '', 10);

// ====================== PAGE 1: EXECUTIVE SUMMARY ======================
$pdf->SectionTitle('Executive Summary', 41, 128, 185);

// Metric Cards
$y = $pdf->GetY();
$pdf->MetricCard('Total Bookings', number_format($totalBookings), 10, $y, [46, 204, 113]);
$pdf->MetricCard('Total Revenue', 'Rs. ' . number_format($totalRevenue), 58, $y, [52, 152, 219]);
$pdf->MetricCard('Total Customers', number_format($totalUsers), 106, $y, [155, 89, 182]);
$pdf->MetricCard('Total Vehicles', number_format($totalVehicles), 154, $y, [231, 76, 60]);

$pdf->Ln(18);

// Average Booking Value with note
$pdf->SetFont('Arial', 'B', 10);
$pdf->Cell(0, 7, 'Average Booking Value: Rs. ' . number_format($avgBookingValue, 2), 0, 1, 'C');
$pdf->SetFont('Arial', 'I', 8);
$pdf->SetTextColor(100, 100, 100);
$pdf->Cell(0, 5, '(Revenue includes both completed and confirmed bookings)', 0, 1, 'C');
$pdf->SetTextColor(0, 0, 0);
$pdf->Ln(8);

// ====================== BOOKING STATUS WITH REVENUE ======================
$pdf->SectionTitle('Booking Status Analysis', 46, 204, 113);

if ($totalBookings > 0) {
    $statusHeader = ['Status', 'Count', 'Percentage', 'Revenue', 'Avg/Booking'];
    $statusTableData = [];

    foreach (['pending', 'confirmed', 'completed', 'cancelled'] as $status) {
        $count = $statusData[$status]['count'] ?? 0;
        $revenue = $statusData[$status]['revenue'] ?? 0;
        $percentage = $totalBookings > 0 ? round(($count / $totalBookings) * 100, 1) : 0;
        $avgPerBooking = $count > 0 ? $revenue / $count : 0;
        
        $statusTableData[] = [
            ucfirst($status),
            number_format($count),
            $percentage . '%',
            'Rs. ' . number_format($revenue),
            'Rs. ' . number_format($avgPerBooking)
        ];
    }

    $pdf->ColorTable($statusHeader, $statusTableData, [35, 25, 25, 45, 45], ['L', 'C', 'C', 'R', 'R']);
} else {
    $pdf->EmptyMessage('No booking status data available');
}
$pdf->Ln(10);

// ====================== MONTHLY REVENUE TREND ======================
if (!empty($monthlyRevenue)) {
    $pdf->SectionTitle('Monthly Revenue Trend (Confirmed + Completed)', 155, 89, 182);

    $revenueHeader = ['Month', 'Bookings', 'Total Revenue', 'Confirmed', 'Completed'];
    $revenueData = [];
    
    foreach ($monthlyRevenue as $data) {
        $monthName = date('M Y', strtotime($data['month'] . '-01'));
        
        $revenueData[] = [
            $monthName,
            number_format($data['bookings']),
            'Rs. ' . number_format($data['revenue']),
            'Rs. ' . number_format($data['confirmed_revenue']),
            'Rs. ' . number_format($data['completed_revenue'])
        ];
    }

    $pdf->ColorTable($revenueHeader, $revenueData, [35, 25, 40, 40, 40], ['L', 'C', 'R', 'R', 'R']);
    $pdf->Ln(10);
}

// ====================== PAGE 2: TOP PERFORMERS ======================
$pdf->AddPage();
$pdf->SectionTitle('Top Performing Vehicles', 231, 76, 60);

if (!empty($topVehicles)) {
    $vehicleHeader = ['#', 'Vehicle', 'Category', 'Bookings', 'Revenue'];
    $vehicleData = [];
    $i = 1;
    
    foreach ($topVehicles as $vehicle) {
        $vehicleName = $vehicle['vehicle_name'] ?? 'Vehicle #' . $vehicle['id'];
        $vehicleCategory = $vehicle['category'] ?? 'N/A';
        
        $vehicleData[] = [
            $i++,
            substr($vehicleName, 0, 30),
            substr($vehicleCategory, 0, 20),
            number_format($vehicle['total_bookings']),
            'Rs. ' . number_format($vehicle['revenue'])
        ];
    }
    
    $pdf->ColorTable($vehicleHeader, $vehicleData, [10, 55, 35, 30, 45], ['C', 'L', 'L', 'C', 'R']);
} else {
    $pdf->EmptyMessage('No vehicle data available');
}

$pdf->Ln(10);

// ====================== TOP CUSTOMERS ======================
$pdf->SectionTitle('Top 10 Customers', 52, 73, 94);

if (!empty($topUsers)) {
    $userHeader = ['#', 'Name', 'Email', 'Bookings', 'Total Spent'];
    $userData = [];
    $i = 1;
    
    foreach ($topUsers as $user) {
        $userData[] = [
            $i++,
            substr($user['name'], 0, 25),
            substr($user['email'], 0, 32),
            number_format($user['total_bookings']),
            'Rs. ' . number_format($user['total_spent'])
        ];
    }
    
    $pdf->ColorTable($userHeader, $userData, [10, 48, 62, 25, 40], ['C', 'L', 'L', 'C', 'R']);
} else {
    $pdf->EmptyMessage('No customer data available');
}

// ====================== PAGE 3: DETAILED ANALYSIS ======================
if (!empty($durationAnalysis) || !empty($dayAnalysis)) {
    $pdf->AddPage();

    // Duration Analysis
    if (!empty($durationAnalysis)) {
        $pdf->SectionTitle('Booking Duration Analysis', 230, 126, 34);
        
        $durationHeader = ['Duration', 'Count', 'Avg Price', 'Total Revenue'];
        $durationData = [];
        
        foreach ($durationAnalysis as $data) {
            $durationData[] = [
                $data['duration_range'],
                number_format($data['count']),
                'Rs. ' . number_format($data['avg_price']),
                'Rs. ' . number_format($data['total_revenue'])
            ];
        }
        
        $pdf->ColorTable($durationHeader, $durationData, [45, 30, 45, 55], ['L', 'C', 'R', 'R']);
        $pdf->Ln(10);
    }

    // Day of Week Analysis
    if (!empty($dayAnalysis)) {
        $pdf->SectionTitle('Weekly Pattern Analysis (Last 30 Days)', 241, 196, 15);
        
        $dayHeader = ['Day', 'Bookings', 'Revenue', 'Avg/Booking'];
        $dayData = [];
        
        foreach ($dayAnalysis as $data) {
            $avgPerDay = $data['bookings'] > 0 ? $data['revenue'] / $data['bookings'] : 0;
            $dayData[] = [
                $data['day_name'],
                number_format($data['bookings']),
                'Rs. ' . number_format($data['revenue']),
                'Rs. ' . number_format($avgPerDay)
            ];
        }
        
        $pdf->ColorTable($dayHeader, $dayData, [45, 30, 50, 50], ['L', 'C', 'R', 'R']);
        $pdf->Ln(10);
    }
}

// ====================== PAGE 4: RECENT BOOKINGS ======================
if (!empty($recentBookings)) {
    $pdf->AddPage();
    $pdf->SectionTitle('Recent Bookings (Last 15)', 52, 73, 94);

    $bookingHeader = ['Booking ID', 'Customer', 'Trip Start', 'Days', 'Price', 'Status'];
    $bookingData = [];
    
    foreach ($recentBookings as $booking) {
        $bookingData[] = [
            substr($booking['booking_id'], 0, 15),
            substr($booking['user_name'], 0, 22),
            date('M d, Y', strtotime($booking['trip_start'])),
            $booking['duration'] . 'd',
            'Rs. ' . number_format($booking['price']),
            ucfirst($booking['status'])
        ];
    }
    
    $pdf->ColorTable($bookingHeader, $bookingData, [32, 42, 30, 15, 35, 25], ['L', 'L', 'C', 'C', 'R', 'C']);
}

// ====================== OUTPUT PDF ======================
$filename = 'BookingNepal_Complete_Report_' . date('Y-m-d_His') . '.pdf';
$pdf->Output('D', $filename);
exit;
?>