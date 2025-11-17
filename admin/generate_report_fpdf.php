<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header("Location: ../index.php");
    exit;
}

require_once '../db.php';
require_once '../libs/fpdf/fpdf.php'; // You'll need to install FPDF

// Set timezone
date_default_timezone_set('Asia/Kathmandu');

// ====================== FETCH DATA ======================
$totalBookings = $conn->query("SELECT COUNT(*) AS total FROM bookings")->fetch_assoc()['total'];
$totalUsers = $conn->query("SELECT COUNT(*) AS total FROM users WHERE user_type='user'")->fetch_assoc()['total'];
$totalVehicles = $conn->query("SELECT COUNT(*) AS total FROM vehicles")->fetch_assoc()['total'];
$totalRevenue = $conn->query("SELECT SUM(price) AS total FROM bookings WHERE status='completed'")->fetch_assoc()['total'] ?? 0;

// Booking Status Summary
$pendingBookings = $conn->query("SELECT COUNT(*) AS total FROM bookings WHERE status='pending'")->fetch_assoc()['total'];
$confirmedBookings = $conn->query("SELECT COUNT(*) AS total FROM bookings WHERE status='confirmed'")->fetch_assoc()['total'];
$completedBookings = $conn->query("SELECT COUNT(*) AS total FROM bookings WHERE status='completed'")->fetch_assoc()['total'];
$cancelledBookings = $conn->query("SELECT COUNT(*) AS total FROM bookings WHERE status='cancelled'")->fetch_assoc()['total'];

// Recent Bookings
$recentBookings = [];
$result = $conn->query("
    SELECT 
        b.booking_id,
        b.user_name,
        b.vehicle_id,
        b.trip_start,
        b.trip_end,
        b.price,
        b.status,
        b.created_at
    FROM bookings b
    ORDER BY b.created_at DESC
    LIMIT 15
");
while ($row = $result->fetch_assoc()) {
    $recentBookings[] = $row;
}

// Top Users
$topUsers = [];
$result = $conn->query("
    SELECT 
        u.name, 
        u.email,
        COUNT(b.id) AS total_bookings, 
        COALESCE(SUM(b.price), 0) AS total_spent
    FROM users u
    LEFT JOIN bookings b ON u.id = b.user_id
    WHERE u.user_type = 'user'
    GROUP BY u.id
    ORDER BY total_bookings DESC
    LIMIT 10
");
while ($row = $result->fetch_assoc()) {
    $topUsers[] = $row;
}

// Monthly Revenue (Last 6 months)
$monthlyRevenue = [];
$result = $conn->query("
    SELECT 
        DATE_FORMAT(created_at, '%Y-%m') AS month,
        SUM(price) AS revenue,
        COUNT(*) AS bookings
    FROM bookings
    WHERE status='completed' AND created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
    GROUP BY DATE_FORMAT(created_at, '%Y-%m')
    ORDER BY month DESC
");
while ($row = $result->fetch_assoc()) {
    $monthlyRevenue[] = $row;
}

// ====================== CREATE PDF ======================
class PDF extends FPDF {
    function Header() {
        $this->SetFont('Arial', 'B', 20);
        $this->SetTextColor(52, 152, 219);
        $this->Cell(0, 10, 'BookingNepal - System Report', 0, 1, 'C');
        $this->SetFont('Arial', '', 10);
        $this->SetTextColor(100, 100, 100);
        $this->Cell(0, 5, 'Generated on: ' . date('F d, Y h:i A'), 0, 1, 'C');
        $this->Ln(5);
        $this->SetDrawColor(52, 152, 219);
        $this->Line(10, $this->GetY(), 200, $this->GetY());
        $this->Ln(5);
    }

    function Footer() {
        $this->SetY(-15);
        $this->SetFont('Arial', 'I', 8);
        $this->SetTextColor(128, 128, 128);
        $this->Cell(0, 10, 'Page ' . $this->PageNo() . ' | BookingNepal Admin Report', 0, 0, 'C');
    }

    function SectionTitle($title, $r=52, $g=152, $b=219) {
        $this->SetFont('Arial', 'B', 14);
        $this->SetFillColor($r, $g, $b);
        $this->SetTextColor(255, 255, 255);
        $this->Cell(0, 10, $title, 0, 1, 'L', true);
        $this->Ln(3);
        $this->SetTextColor(0, 0, 0);
    }

    function ColoredTable($header, $data, $widths) {
        // Header
        $this->SetFillColor(52, 152, 219);
        $this->SetTextColor(255, 255, 255);
        $this->SetFont('Arial', 'B', 9);
        
        foreach ($header as $i => $col) {
            $this->Cell($widths[$i], 8, $col, 1, 0, 'C', true);
        }
        $this->Ln();

        // Data
        $this->SetFillColor(240, 240, 240);
        $this->SetTextColor(0, 0, 0);
        $this->SetFont('Arial', '', 9);
        
        $fill = false;
        foreach ($data as $row) {
            foreach ($row as $i => $col) {
                $this->Cell($widths[$i], 7, $col, 1, 0, $header[$i] == '#' || $header[$i] == 'Bookings' ? 'C' : 'L', $fill);
            }
            $this->Ln();
            $fill = !$fill;
        }
    }
}

$pdf = new PDF();
$pdf->AddPage();
$pdf->SetFont('Arial', '', 11);

// ====================== SUMMARY SECTION ======================
$pdf->SectionTitle('Executive Summary');

// Summary Cards with colored boxes
$summaryData = [
    ['Total Bookings', number_format($totalBookings), [46, 204, 113]],
    ['Total Users', number_format($totalUsers), [52, 152, 219]],
    ['Total Vehicles', number_format($totalVehicles), [155, 89, 182]],
    ['Total Revenue', 'Rs. ' . number_format($totalRevenue), [231, 76, 60]]
];

$x = 10;
$y = $pdf->GetY();
foreach ($summaryData as $data) {
    $pdf->SetXY($x, $y);
    $pdf->SetFillColor($data[2][0], $data[2][1], $data[2][2]);
    $pdf->SetTextColor(255, 255, 255);
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Cell(45, 8, $data[0], 1, 2, 'C', true);
    $pdf->SetX($x);
    $pdf->SetFont('Arial', 'B', 14);
    $pdf->Cell(45, 10, $data[1], 1, 0, 'C', true);
    $x += 48;
}
$pdf->Ln(20);

// ====================== BOOKING STATUS ======================
$pdf->SectionTitle('Booking Status Overview', 46, 204, 113);

$statusHeader = ['Status', 'Count', 'Percentage'];
$statusData = [
    ['Pending', number_format($pendingBookings), round(($pendingBookings / max($totalBookings, 1)) * 100, 1) . '%'],
    ['Confirmed', number_format($confirmedBookings), round(($confirmedBookings / max($totalBookings, 1)) * 100, 1) . '%'],
    ['Completed', number_format($completedBookings), round(($completedBookings / max($totalBookings, 1)) * 100, 1) . '%'],
    ['Cancelled', number_format($cancelledBookings), round(($cancelledBookings / max($totalBookings, 1)) * 100, 1) . '%']
];

$pdf->ColoredTable($statusHeader, $statusData, [60, 60, 60]);
$pdf->Ln(10);

// ====================== MONTHLY REVENUE ======================
if (!empty($monthlyRevenue)) {
    $pdf->SectionTitle('Monthly Revenue (Last 6 Months)', 155, 89, 182);

    $revenueHeader = ['Month', 'Bookings', 'Revenue'];
    $revenueData = [];
    foreach ($monthlyRevenue as $data) {
        $monthName = date('F Y', strtotime($data['month'] . '-01'));
        $revenueData[] = [
            $monthName,
            number_format($data['bookings']),
            'Rs. ' . number_format($data['revenue'])
        ];
    }

    $pdf->ColoredTable($revenueHeader, $revenueData, [65, 55, 60]);
    $pdf->Ln(10);
}

// ====================== TOP USERS ======================
if (!empty($topUsers)) {
    $pdf->AddPage();
    $pdf->SectionTitle('Top 10 Customers', 231, 76, 60);

    $userHeader = ['#', 'Name', 'Email', 'Bookings', 'Total Spent'];
    $userData = [];
    $i = 1;
    foreach ($topUsers as $user) {
        $userData[] = [
            $i++,
            substr($user['name'], 0, 22),
            substr($user['email'], 0, 30),
            number_format($user['total_bookings']),
            'Rs. ' . number_format($user['total_spent'])
        ];
    }

    $pdf->ColoredTable($userHeader, $userData, [10, 45, 60, 25, 40]);
    $pdf->Ln(10);
}

// ====================== RECENT BOOKINGS ======================
if (!empty($recentBookings)) {
    $pdf->AddPage();
    $pdf->SectionTitle('Recent Bookings', 52, 73, 94);

    $bookingHeader = ['Booking ID', 'Customer', 'Trip Start', 'Price', 'Status'];
    $bookingData = [];
    foreach ($recentBookings as $booking) {
        $bookingData[] = [
            $booking['booking_id'],
            substr($booking['user_name'], 0, 20),
            date('M d, Y', strtotime($booking['trip_start'])),
            'Rs. ' . number_format($booking['price']),
            ucfirst($booking['status'])
        ];
    }

    $pdf->ColoredTable($bookingHeader, $bookingData, [40, 45, 30, 30, 25]);
}

// ====================== OUTPUT PDF ======================
$filename = 'BookingNepal_Report_' . date('Y-m-d_His') . '.pdf';
$pdf->Output('D', $filename);
exit;
?>