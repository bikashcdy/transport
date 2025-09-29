<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header("Location: ../index.php");
    exit();
}
include '../db.php';

// Default values (can be overridden by included pages)
$pageTitle = $pageTitle ?? "Admin Panel";
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle; ?> - TMS</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
    body {
        margin: 0;
        font-family: Arial, sans-serif;
        display: flex;
        background: #f4f6f9;
        min-height: 100vh;
    }

    .sidebar {
        width: 220px;
        background: #1a1b5e;
        color: white;
        padding: 20px 0;
        height: 100vh;
        position: fixed;
        top: 0;
    }

    .sidebar h2 {
        text-align: center;
        margin-bottom: 30px;
    }

    .sidebar a {
        display: block;
        color: white;
        padding: 12px 20px;
        text-decoration: none;
        transition: 0.3s;
    }

    .sidebar a:hover {
        background: #4f46e5;
    }

    .main {
        margin-left: 220px;
        flex: 1;
        padding: 20px;
        overflow-y: auto;
    }

    header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        background: white;
        padding: 15px 20px;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        margin-bottom: 20px;
    }
    </style>
</head>

<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <h2>🚛 TMS Admin</h2>
        <a href="admin_dashboard.php"><i class="fas fa-home"></i> Dashboard</a>
        <a href="vehicles.php"><i class="fas fa-bus"></i> Vehicles</a>
        <a href="users.php"><i class="fas fa-users"></i> Users</a>
        <a href="schedules.php"><i class="fas fa-calendar-alt"></i> Schedules</a>
        <a href="reports.php"><i class="fas fa-chart-line"></i> Reports</a>
        <a href="settings.php"><i class="fas fa-cogs"></i> Settings</a>
        <a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>

    <!-- Main -->
    <div class="main">
        <header>
            <h2><?= $pageTitle; ?></h2>

            <?php if ($pageTitle === "Admin Dashboard"): ?>
            <!-- Show only on Dashboard -->
            <div class="d-flex align-items-center gap-2">
                <p class="mb-0">Welcome, <?= $_SESSION['user_name'] ?? 'Admin'; ?> !</p>
            </div>
            <?php else: ?>
            <!-- Show only Add button on other pages -->
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addModal">
                <i class="fas fa-plus"></i> Add <?= $pageTitle; ?>
            </button>
            <?php endif; ?>
        </header>

        <!-- Page Content -->
        <?php if (function_exists('pageContent')) {
            pageContent();
        } ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>