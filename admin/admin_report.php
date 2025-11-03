<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header("Location: ../index.php");
    exit;
}

require_once '../db.php';
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// ====================== SUMMARY DATA ======================
$totalBookings = $conn->query("SELECT COUNT(*) AS total FROM bookings")->fetch_assoc()['total'];
$totalUsers = $conn->query("SELECT COUNT(*) AS total FROM users")->fetch_assoc()['total'];
$totalVehicles = $conn->query("SELECT COUNT(*) AS total FROM vehicles")->fetch_assoc()['total'];
$totalRevenue = $conn->query("SELECT SUM(price) AS total FROM bookings WHERE status='completed'")->fetch_assoc()['total'] ?? 0;

// Calculate growth metrics (comparing to previous period)
$prevMonthBookings = $conn->query("SELECT COUNT(*) AS total FROM bookings WHERE booking_date < DATE_SUB(NOW(), INTERVAL 1 MONTH)")->fetch_assoc()['total'];
$bookingGrowth = $prevMonthBookings > 0 ? round((($totalBookings - $prevMonthBookings) / $prevMonthBookings) * 100, 1) : 0;

// ====================== VEHICLE PERFORMANCE ======================
$vehiclePerformanceData = [];
$result = $conn->query("
    SELECT 
        v.vehicle_name, 
        COUNT(b.id) AS total_trips,
        COALESCE(SUM(b.price), 0) AS revenue
    FROM vehicles v
    LEFT JOIN bookings b ON v.id = b.vehicle_id AND b.status='completed'
    GROUP BY v.id
    ORDER BY total_trips DESC
");
while ($row = $result->fetch_assoc()) {
    $vehiclePerformanceData[] = $row;
}

// ====================== ROUTE BOOKINGS ======================
$routeBookingData = [];
$result = $conn->query("
    SELECT 
        CONCAT(w.origin, ' → ', w.destination) AS route_name, 
        COUNT(b.id) AS total_bookings,
        COALESCE(SUM(b.price), 0) AS revenue
    FROM ways w
    LEFT JOIN bookings b ON w.id = b.route_id
    GROUP BY w.id
    ORDER BY total_bookings DESC
    LIMIT 10
");
while ($row = $result->fetch_assoc()) {
    $routeBookingData[] = $row;
}

// ====================== USER ACTIVITY ======================
$userActivityData = [];
$result = $conn->query("
    SELECT 
        u.name, 
        u.email,
        COUNT(b.id) AS total_bookings, 
        COALESCE(SUM(b.price), 0) AS total_spent,
        MAX(b.booking_date) AS last_booking
    FROM users u
    LEFT JOIN bookings b ON u.id = b.user_id
    WHERE u.user_type = 'user'
    GROUP BY u.id
    ORDER BY total_bookings DESC
    LIMIT 20
");
while ($row = $result->fetch_assoc()) {
    $userActivityData[] = $row;
}

// ====================== BOOKING TRENDS ======================
$bookingTrends = [];
$result = $conn->query("
    SELECT 
        DATE_FORMAT(booking_date, '%Y-%m') AS month,
        COUNT(*) AS bookings,
        COALESCE(SUM(price), 0) AS revenue
    FROM bookings
    WHERE booking_date >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
    GROUP BY month
    ORDER BY month ASC
");
while ($row = $result->fetch_assoc()) {
    $bookingTrends[] = $row;
}

// ====================== STATUS BREAKDOWN ======================
$statusBreakdown = [];
$result = $conn->query("
    SELECT status, COUNT(*) AS count
    FROM bookings
    GROUP BY status
");
while ($row = $result->fetch_assoc()) {
    $statusBreakdown[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Analytics Dashboard | Transportation Management</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<link rel="stylesheet" href="report.css">
</head>

<body>
<!-- Header -->
<div class="dashboard-header">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h1 class="header-title"><i class="fas fa-chart-line me-2"></i>Analytics Dashboard</h1>
                <p class="header-subtitle">Transportation Management System</p>
            </div>
            <div class="text-end">
                <p class="mb-0 text-white-50 small">Last Updated</p>
                <p class="mb-0 fw-bold"><?= date('M d, Y - h:i A') ?></p>
            </div>
        </div>
    </div>
</div>

<div class="container dashboard-container">
    
    <!-- SUMMARY CARDS -->
    <div class="row g-4 mb-5">
        <div class="col-lg-3 col-md-6">
            <div class="metric-card">
                <div class="metric-icon bg-primary">
                    <i class="fas fa-calendar-check"></i>
                </div>
                <div class="metric-content">
                    <p class="metric-label">Total Bookings</p>
                    <h2 class="metric-value"><?= number_format($totalBookings) ?></h2>
                    <span class="metric-trend trend-up">
                        <i class="fas fa-arrow-up"></i> <?= abs($bookingGrowth) ?>%
                    </span>
                </div>
            </div>
        </div>
        
        <div class="col-lg-3 col-md-6">
            <div class="metric-card">
                <div class="metric-icon bg-success">
                    <i class="fas fa-users"></i>
                </div>
                <div class="metric-content">
                    <p class="metric-label">Total Users</p>
                    <h2 class="metric-value"><?= number_format($totalUsers) ?></h2>
                    <span class="metric-trend trend-neutral">
                        <i class="fas fa-minus"></i> Active
                    </span>
                </div>
            </div>
        </div>
        
        <div class="col-lg-3 col-md-6">
            <div class="metric-card">
                <div class="metric-icon bg-info">
                    <i class="fas fa-car"></i>
                </div>
                <div class="metric-content">
                    <p class="metric-label">Total Vehicles</p>
                    <h2 class="metric-value"><?= number_format($totalVehicles) ?></h2>
                    <span class="metric-trend trend-neutral">
                        <i class="fas fa-check-circle"></i> Fleet
                    </span>
                </div>
            </div>
        </div>
        
        <div class="col-lg-3 col-md-6">
            <div class="metric-card">
                <div class="metric-icon bg-warning">
                    <i class="fas fa-rupee-sign"></i>
                </div>
                <div class="metric-content">
                    <p class="metric-label">Total Revenue</p>
                    <h2 class="metric-value">Rs. <?= number_format($totalRevenue) ?></h2>
                    <span class="metric-trend trend-up">
                        <i class="fas fa-arrow-up"></i> Completed
                    </span>
                </div>
            </div>
        </div>
    </div>

    <!-- CHARTS ROW 1 -->
    <div class="row g-4 mb-4">
        <!-- BOOKING TRENDS -->
        <div class="col-lg-8">
            <div class="chart-card">
                <div class="chart-header">
                    <h5 class="chart-title"><i class="fas fa-chart-area me-2"></i>Booking Trends (6 Months)</h5>
                    <span class="badge bg-primary">Live Data</span>
                </div>
                <canvas id="trendChart"></canvas>
            </div>
        </div>
        
        <!-- STATUS BREAKDOWN -->
        <div class="col-lg-4">
            <div class="chart-card">
                <div class="chart-header">
                    <h5 class="chart-title"><i class="fas fa-circle-notch me-2"></i>Booking Status</h5>
                </div>
                <canvas id="statusChart"></canvas>
            </div>
        </div>
    </div>

    <!-- CHARTS ROW 2 -->
    <div class="row g-4 mb-4">
        <!-- VEHICLE PERFORMANCE -->
        <div class="col-lg-6">
            <div class="chart-card">
                <div class="chart-header">
                    <h5 class="chart-title"><i class="fas fa-car-side me-2"></i>Vehicle Performance</h5>
                    <span class="badge bg-info">Top Performers</span>
                </div>
                <canvas id="vehicleChart"></canvas>
            </div>
        </div>
        
        <!-- ROUTE BOOKINGS -->
        <div class="col-lg-6">
            <div class="chart-card">
                <div class="chart-header">
                    <h5 class="chart-title"><i class="fas fa-route me-2"></i>Popular Routes</h5>
                    <span class="badge bg-success">Top 10</span>
                </div>
                <canvas id="routeChart"></canvas>
            </div>
        </div>
    </div>

    <!-- USER ACTIVITY TABLE -->
    <div class="chart-card mb-5">
        <div class="chart-header">
            <h5 class="chart-title"><i class="fas fa-users-cog me-2"></i>Top Users Activity</h5>
            <div class="table-actions">
                <button class="btn btn-sm btn-outline-primary" onclick="exportTable()">
                    <i class="fas fa-download me-1"></i>Export
                </button>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table data-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>User Name</th>
                        <th>Email</th>
                        <th class="text-center">Total Bookings</th>
                        <th class="text-end">Total Spent</th>
                        <th>Last Booking</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $i = 1; foreach ($userActivityData as $row): ?>
                    <tr>
                        <td><?= $i++ ?></td>
                        <td>
                            <div class="user-info">
                                <div class="user-avatar"><?= strtoupper(substr($row['name'], 0, 1)) ?></div>
                                <span class="fw-semibold"><?= htmlspecialchars($row['name']) ?></span>
                            </div>
                        </td>
                        <td class="text-muted"><?= htmlspecialchars($row['email']) ?></td>
                        <td class="text-center">
                            <span class="badge bg-primary-subtle text-primary"><?= $row['total_bookings'] ?></span>
                        </td>
                        <td class="text-end fw-semibold">Rs. <?= number_format($row['total_spent']) ?></td>
                        <td>
                            <?php if ($row['last_booking']): ?>
                                <span class="text-success">
                                    <i class="fas fa-calendar-check me-1"></i>
                                    <?= date('M d, Y', strtotime($row['last_booking'])) ?>
                                </span>
                            <?php else: ?>
                                <span class="text-muted">No bookings</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Footer -->
<div class="dashboard-footer">
    <div class="container text-center">
        <p class="mb-0">© <?= date('Y') ?> Transportation Management System. All rights reserved.</p>
    </div>
</div>

<!-- CHART JS SCRIPTS -->
<script>
// Chart.js Global Configuration
Chart.defaults.font.family = "'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif";
Chart.defaults.plugins.legend.display = true;
Chart.defaults.plugins.legend.position = 'bottom';

// Trend Chart
const trendData = <?= json_encode($bookingTrends) ?>;
new Chart(document.getElementById('trendChart'), {
    type: 'line',
    data: {
        labels: trendData.map(d => d.month),
        datasets: [{
            label: 'Bookings',
            data: trendData.map(d => d.bookings),
            borderColor: '#4e73df',
            backgroundColor: 'rgba(78, 115, 223, 0.1)',
            tension: 0.4,
            fill: true,
            yAxisID: 'y'
        }, {
            label: 'Revenue (Rs.)',
            data: trendData.map(d => d.revenue),
            borderColor: '#1cc88a',
            backgroundColor: 'rgba(28, 200, 138, 0.1)',
            tension: 0.4,
            fill: true,
            yAxisID: 'y1'
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
            y: {
                type: 'linear',
                position: 'left',
                title: { display: true, text: 'Bookings' }
            },
            y1: {
                type: 'linear',
                position: 'right',
                title: { display: true, text: 'Revenue (Rs.)' },
                grid: { drawOnChartArea: false }
            }
        }
    }
});

// Status Chart
const statusData = <?= json_encode($statusBreakdown) ?>;
new Chart(document.getElementById('statusChart'), {
    type: 'doughnut',
    data: {
        labels: statusData.map(d => d.status.charAt(0).toUpperCase() + d.status.slice(1)),
        datasets: [{
            data: statusData.map(d => d.count),
            backgroundColor: ['#1cc88a', '#4e73df', '#f6c23e', '#e74a3b', '#858796']
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { position: 'bottom' }
        }
    }
});

// Vehicle Chart
const vehicleLabels = <?= json_encode(array_column($vehiclePerformanceData, 'vehicle_name')) ?>;
const vehicleData = <?= json_encode(array_column($vehiclePerformanceData, 'total_trips')) ?>;
new Chart(document.getElementById('vehicleChart'), {
    type: 'bar',
    data: {
        labels: vehicleLabels,
        datasets: [{
            label: 'Total Trips',
            data: vehicleData,
            backgroundColor: 'rgba(78, 115, 223, 0.8)',
            borderColor: '#4e73df',
            borderWidth: 2,
            borderRadius: 6
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { display: false }
        },
        scales: {
            y: { beginAtZero: true }
        }
    }
});

// Route Chart
const routeLabels = <?= json_encode(array_column($routeBookingData, 'route_name')) ?>;
const routeData = <?= json_encode(array_column($routeBookingData, 'total_bookings')) ?>;
new Chart(document.getElementById('routeChart'), {
    type: 'horizontalBar',
    data: {
        labels: routeLabels,
        datasets: [{
            label: 'Bookings',
            data: routeData,
            backgroundColor: [
                '#4e73df', '#1cc88a', '#36b9cc', '#f6c23e', '#e74a3b',
                '#858796', '#5a5c69', '#2e59d9', '#17a673', '#2c9faf'
            ],
            borderRadius: 6
        }]
    },
    options: {
        indexAxis: 'y',
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { display: false }
        },
        scales: {
            x: { beginAtZero: true }
        }
    }
});

// Export Function
function exportTable() {
    alert('Export functionality can be implemented with libraries like SheetJS or server-side PDF generation.');
}
</script>

</body>
</html>