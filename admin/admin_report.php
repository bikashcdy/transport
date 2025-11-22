<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header("Location: ../index.php");
    exit;
}

require_once '../db.php';
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// ====================== SUMMARY DATA ======================
$totalBookingsResult = $conn->query("SELECT COUNT(*) AS total FROM bookings");
$totalBookings = $totalBookingsResult ? $totalBookingsResult->fetch_assoc()['total'] : 0;

$totalUsersResult = $conn->query("SELECT COUNT(*) AS total FROM users WHERE user_type='user'");
$totalUsers = $totalUsersResult ? $totalUsersResult->fetch_assoc()['total'] : 0;

$totalVehiclesResult = $conn->query("SELECT COUNT(*) AS total FROM vehicles");
$totalVehicles = $totalVehiclesResult ? $totalVehiclesResult->fetch_assoc()['total'] : 0;

$totalRevenueResult = $conn->query("SELECT COALESCE(SUM(price), 0) AS total FROM bookings WHERE status='completed'");
$totalRevenue = $totalRevenueResult ? $totalRevenueResult->fetch_assoc()['total'] : 0;

// ====================== BOOKING STATUS ======================
$pendingBookingsResult = $conn->query("SELECT COUNT(*) AS total FROM bookings WHERE status='pending'");
$pendingBookings = $pendingBookingsResult ? $pendingBookingsResult->fetch_assoc()['total'] : 0;

$confirmedBookingsResult = $conn->query("SELECT COUNT(*) AS total FROM bookings WHERE status='confirmed'");
$confirmedBookings = $confirmedBookingsResult ? $confirmedBookingsResult->fetch_assoc()['total'] : 0;

$completedBookingsResult = $conn->query("SELECT COUNT(*) AS total FROM bookings WHERE status='completed'");
$completedBookings = $completedBookingsResult ? $completedBookingsResult->fetch_assoc()['total'] : 0;

$cancelledBookingsResult = $conn->query("SELECT COUNT(*) AS total FROM bookings WHERE status='cancelled'");
$cancelledBookings = $cancelledBookingsResult ? $cancelledBookingsResult->fetch_assoc()['total'] : 0;

// ====================== USER ACTIVITY ======================
$userActivityData = [];
$result = $conn->query("
    SELECT u.id, u.name, u.email, COUNT(b.id) AS total_bookings, 
           COALESCE(SUM(b.price), 0) AS total_spent, MAX(b.created_at) AS last_booking
    FROM users u LEFT JOIN bookings b ON u.id = b.user_id
    WHERE u.user_type = 'user' GROUP BY u.id, u.name, u.email
    ORDER BY total_bookings DESC LIMIT 20
");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $userActivityData[] = $row;
    }
}

// ====================== MONTHLY REVENUE ======================
$monthlyRevenue = [];
$result = $conn->query("
    SELECT DATE_FORMAT(created_at, '%Y-%m') AS month, COALESCE(SUM(price), 0) AS revenue, COUNT(*) AS bookings
    FROM bookings WHERE status='completed' AND created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
    GROUP BY DATE_FORMAT(created_at, '%Y-%m') ORDER BY month ASC
");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $monthlyRevenue[] = $row;
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Dashboard | BookingNepal</title>
<link rel="stylesheet" href="admin_report.css">
<style>
.status-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 15px; margin: 20px 0; }
.status-card { background: white; border-radius: 8px; padding: 20px; text-align: center; box-shadow: 0 2px 8px rgba(0,0,0,0.1); border-left: 4px solid; transition: transform 0.2s; }
.status-card:hover { transform: translateY(-5px); box-shadow: 0 4px 12px rgba(0,0,0,0.15); }
.status-card.pending { border-color: #f39c12; }
.status-card.confirmed { border-color: #27ae60; }
.status-card.completed { border-color: #3498db; }
.status-card.cancelled { border-color: #e74c3c; }
.status-card h4 { margin: 0 0 10px 0; font-size: 14px; color: #7f8c8d; text-transform: uppercase; }
.status-card .count { font-size: 32px; font-weight: bold; color: #2c3e50; }
.status-card .percentage { font-size: 12px; color: #95a5a6; margin-top: 5px; }
.chart-container { background: white; border-radius: 8px; padding: 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); margin: 20px 0; }
.report-actions { display: flex; gap: 10px; margin: 20px 0; flex-wrap: wrap; }
.report-actions button { flex: 1; min-width: 200px; padding: 15px 25px; font-size: 16px; font-weight: 600; border: none; border-radius: 8px; cursor: pointer; transition: all 0.3s; display: flex; align-items: center; justify-content: center; gap: 8px; }
.btn-primary { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; }
.btn-primary:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4); }
.btn-secondary { background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); color: white; }
.btn-secondary:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(245, 87, 108, 0.4); }
.btn-success { background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); color: white; }
.btn-success:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(79, 172, 254, 0.4); }
.btn-save { background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%); color: white; }
.btn-save:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(56, 239, 125, 0.4); }
.summary-cards { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin: 20px 0; }
.card { background: white; border-radius: 10px; padding: 25px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); text-align: center; transition: transform 0.2s; }
.card:hover { transform: translateY(-5px); box-shadow: 0 4px 15px rgba(0,0,0,0.15); }
.card h3 { margin: 0 0 10px 0; font-size: 16px; color: #7f8c8d; text-transform: uppercase; }
.card p { margin: 0; font-size: 32px; font-weight: bold; color: #2c3e50; }
.empty-state { text-align: center; padding: 40px; background: #f8f9fa; border-radius: 8px; margin: 20px 0; }
.empty-state h3 { color: #6c757d; margin-bottom: 10px; }
.empty-state p { color: #adb5bd; }
.alert-success { background: #d4edda; color: #155724; padding: 15px; border-radius: 8px; margin: 20px 0; border: 1px solid #c3e6cb; }
.alert-error { background: #f8d7da; color: #721c24; padding: 15px; border-radius: 8px; margin: 20px 0; border: 1px solid #f5c6cb; }
@media print { .report-actions { display: none !important; } }
</style>
</head>
<body>
<div class="container">
    <header>
        <h1>📊 Admin Dashboard</h1>
        <p>Vehicle Booking Management System</p>
    </header>

    <div class="summary-cards">
        <div class="card" style="border-top: 4px solid #3498db;">
            <h3>📋 Total Bookings</h3>
            <p><?= number_format($totalBookings) ?></p>
        </div>
        <div class="card" style="border-top: 4px solid #2ecc71;">
            <h3>👥 Total Users</h3>
            <p><?= number_format($totalUsers) ?></p>
        </div>
        <div class="card" style="border-top: 4px solid #9b59b6;">
            <h3>🚗 Total Vehicles</h3>
            <p><?= number_format($totalVehicles) ?></p>
        </div>
        <div class="card" style="border-top: 4px solid #e74c3c;">
            <h3>💰 Total Revenue</h3>
            <p>Rs. <?= number_format($totalRevenue) ?></p>
        </div>
    </div>

    <?php if ($totalBookings == 0): ?>
    <div class="empty-state">
        <h3>🔍 No Data Available Yet</h3>
        <p>Start adding bookings to see analytics and reports.</p>
    </div>
    <?php else: ?>

    <div class="report-section">
        <h2>📈 Booking Status Overview</h2>
        <div class="status-grid">
            <div class="status-card pending">
                <h4>⏳ Pending</h4>
                <div class="count"><?= number_format($pendingBookings) ?></div>
                <div class="percentage"><?= $totalBookings > 0 ? round(($pendingBookings / $totalBookings) * 100, 1) : 0 ?>% of total</div>
            </div>
            <div class="status-card confirmed">
                <h4>✅ Confirmed</h4>
                <div class="count"><?= number_format($confirmedBookings) ?></div>
                <div class="percentage"><?= $totalBookings > 0 ? round(($confirmedBookings / $totalBookings) * 100, 1) : 0 ?>% of total</div>
            </div>
            <div class="status-card completed">
                <h4>🎉 Completed</h4>
                <div class="count"><?= number_format($completedBookings) ?></div>
                <div class="percentage"><?= $totalBookings > 0 ? round(($completedBookings / $totalBookings) * 100, 1) : 0 ?>% of total</div>
            </div>
            <div class="status-card cancelled">
                <h4>❌ Cancelled</h4>
                <div class="count"><?= number_format($cancelledBookings) ?></div>
                <div class="percentage"><?= $totalBookings > 0 ? round(($cancelledBookings / $totalBookings) * 100, 1) : 0 ?>% of total</div>
            </div>
        </div>
    </div>

    <?php if (!empty($monthlyRevenue)): ?>
    <div class="chart-container">
        <h2>💰 Monthly Revenue Trend</h2>
        <table style="width: 100%; border-collapse: collapse;">
            <thead>
                <tr style="background: #3498db; color: white;">
                    <th style="padding: 12px; text-align: left;">Month</th>
                    <th style="padding: 12px; text-align: center;">Bookings</th>
                    <th style="padding: 12px; text-align: right;">Revenue</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($monthlyRevenue as $data): ?>
                <tr style="border-bottom: 1px solid #ddd;">
                    <td style="padding: 10px;"><?= date('F Y', strtotime($data['month'] . '-01')) ?></td>
                    <td style="padding: 10px; text-align: center;"><?= number_format($data['bookings']) ?></td>
                    <td style="padding: 10px; text-align: right; font-weight: bold;">Rs. <?= number_format($data['revenue']) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>

    <div class="report-section">
        <h2>👥 Top Customer Activity</h2>
        <div class="table-wrapper">
            <table style="width: 100%; border-collapse: collapse; background: white;">
                <thead>
                    <tr style="background: #2c3e50; color: white;">
                        <th style="padding: 12px; text-align: center;">#</th>
                        <th style="padding: 12px; text-align: left;">Name</th>
                        <th style="padding: 12px; text-align: left;">Email</th>
                        <th style="padding: 12px; text-align: center;">Total Bookings</th>
                        <th style="padding: 12px; text-align: right;">Total Spent</th>
                        <th style="padding: 12px; text-align: center;">Last Booking</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($userActivityData)): ?>
                    <tr><td colspan="6" style="text-align: center; padding: 40px; color: #95a5a6;"><strong>No customer activity data available</strong></td></tr>
                <?php else: ?>
                    <?php $i=1; foreach($userActivityData as $user): ?>
                    <tr style="border-bottom: 1px solid #ecf0f1;">
                        <td style="padding: 12px; text-align: center;"><?= $i++ ?></td>
                        <td style="padding: 12px;"><?= htmlspecialchars($user['name']) ?></td>
                        <td style="padding: 12px;"><?= htmlspecialchars($user['email']) ?></td>
                        <td style="padding: 12px; text-align: center; font-weight: bold;"><?= number_format($user['total_bookings']) ?></td>
                        <td style="padding: 12px; text-align: right; color: #27ae60; font-weight: bold;">Rs. <?= number_format($user['total_spent']) ?></td>
                        <td style="padding: 12px; text-align: center;"><?= $user['last_booking'] ? date('M d, Y', strtotime($user['last_booking'])) : 'No bookings' ?></td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>

    <div class="report-actions">
        <button class="btn-primary" onclick="window.location.href='generate_report.php'" title="Download PDF report">
            <span>📄</span> Generate PDF Report
        </button>
        <button class="btn-secondary" onclick="window.print()" title="Print this page">
            <span>🖨️</span> Print Dashboard
        </button>
    </div>
</div>

<script>
window.onbeforeprint = function() { document.querySelectorAll('.report-actions').forEach(el => el.style.display = 'none'); };
window.onafterprint = function() { document.querySelectorAll('.report-actions').forEach(el => el.style.display = 'flex'); };
</script>
</body>
</html>