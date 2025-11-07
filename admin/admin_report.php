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

// Handle "Generate Report" action
$generateReport = isset($_POST['generate_report']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Dashboard | BookingNepal</title>
<link rel="stylesheet" href="admin_report.css">
<script>
function printReport() {
    window.print();
}
</script>
</head>
<body>
<div class="container">
    <header>
        <h1>Admin Dashboard</h1>
        <p>Vehicle Booking system</p>
    </header>

    <div class="summary-cards">
        <div class="card">
            <h3>Total Bookings</h3>
            <p><?= number_format($totalBookings) ?></p>
        </div>
        <div class="card">
            <h3>Total Users</h3>
            <p><?= number_format($totalUsers) ?></p>
        </div>
        <div class="card">
            <h3>Total Vehicles</h3>
            <p><?= number_format($totalVehicles) ?></p>
        </div>
        <div class="card">
            <h3>Total Revenue</h3>
            <p>Rs. <?= number_format($totalRevenue) ?></p>
        </div>
    </div>

    <div class="report-section">
        <h2>User Activity</h2>
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Total Bookings</th>
                        <th>Total Spent</th>
                        <th>Last Booking</th>
                    </tr>
                </thead>
                <tbody>
                <?php $i=1; foreach($userActivityData as $user): ?>
                    <tr>
                        <td><?= $i++ ?></td>
                        <td><?= htmlspecialchars($user['name']) ?></td>
                        <td><?= htmlspecialchars($user['email']) ?></td>
                        <td><?= $user['total_bookings'] ?></td>
                        <td>Rs. <?= number_format($user['total_spent']) ?></td>
                        <td><?= $user['last_booking'] ? date('M d, Y', strtotime($user['last_booking'])) : 'No bookings' ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="report-actions">
    <button onclick="window.location.href='../admin/generate_report.php'">📄 Generate PDF</button>


    </div>

</body>
</html>
