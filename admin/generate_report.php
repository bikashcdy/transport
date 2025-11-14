<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header("Location: ../index.php");
    exit;
}

require_once '../db.php';

// Get the same data as admin_report.php
$totalBookings = $conn->query("SELECT COUNT(*) AS total FROM bookings")->fetch_assoc()['total'];
$totalUsers = $conn->query("SELECT COUNT(*) AS total FROM users")->fetch_assoc()['total'];
$totalVehicles = $conn->query("SELECT COUNT(*) AS total FROM vehicles")->fetch_assoc()['total'];
$totalRevenue = $conn->query("SELECT SUM(price) AS total FROM bookings WHERE status='completed'")->fetch_assoc()['total'] ?? 0;

$userActivityData = [];
$result = $conn->query("
    SELECT 
        u.name, 
        u.email,
        COUNT(b.id) AS total_bookings, 
        COALESCE(SUM(b.price), 0) AS total_spent,
        MAX(b.created_at) AS last_booking
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
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Admin Report - <?= date('Y-m-d') ?></title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            background: white;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #333;
            padding-bottom: 20px;
        }
        .summary {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            margin-bottom: 40px;
        }
        .summary-item {
            text-align: center;
            padding: 20px;
            border: 2px solid #4CAF50;
            border-radius: 8px;
            background: #f9f9f9;
        }
        .summary-item h3 {
            margin: 0 0 10px 0;
            color: #333;
            font-size: 14px;
        }
        .summary-item p {
            margin: 0;
            font-size: 24px;
            font-weight: bold;
            color: #4CAF50;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 12px;
            text-align: left;
        }
        th {
            background-color: #4CAF50;
            color: white;
            font-weight: bold;
        }
        tr:nth-child(even) {
            background-color: #f2f2f2;
        }
        .print-btn {
            margin: 20px 0;
            padding: 12px 24px;
            background-color: #4CAF50;
            color: white;
            border: none;
            cursor: pointer;
            font-size: 16px;
            border-radius: 5px;
        }
        .print-btn:hover {
            background-color: #45a049;
        }
        @media print {
            .print-btn { display: none; }
        }
    </style>
</head>
<body>
    <button class="print-btn" onclick="window.print()">🖨️ Print/Save as PDF</button>
    
    <div class="header">
        <h1>Admin Dashboard Report</h1>
        <p>Vehicle Booking System - BookingNepal</p>
        <p><strong>Generated on:</strong> <?= date('F d, Y h:i A') ?></p>
    </div>

    <h2>Summary Statistics</h2>
    <div class="summary">
        <div class="summary-item">
            <h3>Total Bookings</h3>
            <p><?= number_format($totalBookings) ?></p>
        </div>
        <div class="summary-item">
            <h3>Total Users</h3>
            <p><?= number_format($totalUsers) ?></p>
        </div>
        <div class="summary-item">
            <h3>Total Vehicles</h3>
            <p><?= number_format($totalVehicles) ?></p>
        </div>
        <div class="summary-item">
            <h3>Total Revenue</h3>
            <p>Rs. <?= number_format($totalRevenue) ?></p>
        </div>
    </div>

    <h2>User Activity Report</h2>
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
        <?php if (empty($userActivityData)): ?>
            <tr>
                <td colspan="6" style="text-align: center;">No user activity data available</td>
            </tr>
        <?php else: ?>
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
        <?php endif; ?>
        </tbody>
    </table>
</body>
</html>