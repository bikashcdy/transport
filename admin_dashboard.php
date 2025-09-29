<?php
session_start();

// Allow only admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
  header("Location: index.php");
  exit();
}
include 'db.php';
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - TMS</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
    body {
        margin: 0;
        font-family: Arial, sans-serif;
        display: flex;
        height: 100vh;
        background: #f4f6f9;
    }

    /* Sidebar */
    .sidebar {
        width: 250px;
        background: #1a1b5e;
        color: white;
        display: flex;
        flex-direction: column;
        padding: 20px 0;
    }

    .sidebar h2 {
        text-align: center;
        margin-bottom: 30px;
    }

    .sidebar a {
        display: block;
        padding: 12px 20px;
        color: white;
        text-decoration: none;
        transition: background 0.3s;
    }

    .sidebar a:hover {
        background: #4f46e5;
    }

    /* Main */
    .main {
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

    .cards {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        gap: 20px;
    }

    .card {
        background: white;
        padding: 20px;
        border-radius: 8px;
        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        text-align: center;
    }

    .card h3 {
        margin-bottom: 10px;
        color: #4f46e5;
    }

    .card p {
        font-size: 1.5rem;
        font-weight: bold;
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
        <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>

    <!-- Main -->
    <div class="main">
        <header>
            <h2>Admin Dashboard</h2>
            <p>Welcome, <?= $_SESSION['username'] ?? 'Admin'; ?>!</p>
        </header>

        <div class="cards">
            <div class="card">
                <h3>Total Vehicles</h3>
                <p>
                    <?php
          $result = $conn->query("SELECT COUNT(*) AS total FROM vehicles");
          echo $result->fetch_assoc()['total'];
          ?>
                </p>
            </div>

            <div class="card">
                <h3>Total Users</h3>
                <p>
                    <?php
          $result = $conn->query("SELECT COUNT(*) AS total FROM users");
          echo $result->fetch_assoc()['total'];
          ?>
                </p>
            </div>

            <div class="card">
                <h3>Active Bookings</h3>
                <p>
                    <?php
          $result = $conn->query("SELECT COUNT(*) AS total FROM bookings WHERE status='active'");
          echo $result->fetch_assoc()['total'];
          ?>
                </p>
            </div>

            <div class="card">
                <h3>Reports Generated</h3>
                <p>
                    <?php
          $result = $conn->query("SELECT COUNT(*) AS total FROM reports");
          echo $result->fetch_assoc()['total'];
          ?>
                </p>
            </div>
        </div>
    </div>

</body>

</html>