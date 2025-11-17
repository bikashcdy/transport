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
    <title><?= $pageTitle; ?> - NepalBooking</title>
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

    /* ========== BOOKINGS TABLE STYLES ========== */
    
    /* Force proper table display */
    .table-responsive {
        display: block !important;
        width: 100% !important;
        overflow-x: auto !important;
        -webkit-overflow-scrolling: touch !important;
    }
    
    table.table {
        display: table !important;
        width: 100% !important;
        margin-bottom: 1rem !important;
        border-collapse: collapse !important;
    }
    
    table.table thead {
        display: table-header-group !important;
    }
    
    table.table tbody {
        display: table-row-group !important;
    }
    
    table.table tr {
        display: table-row !important;
    }
    
    table.table th,
    table.table td {
        display: table-cell !important;
    }
    
    /* Table Header Styling */
    .table thead th {
        background-color: #007bff !important;
        color: white !important;
        text-align: center;
        font-weight: 600;
        padding: 12px 8px;
        border-bottom: 2px solid #0056b3;
    }

    /* Table Cell Styling */
    .table td, .table th {
        vertical-align: middle;
        text-align: center;
        padding: 10px 8px;
    }

    /* Table Row Hover Effect */
    .table-hover tbody tr:hover {
        background-color: #f8f9fa;
        cursor: pointer;
        transition: background-color 0.2s ease;
    }

    /* Action Buttons */
    .btn-sm {
        margin: 2px;
        padding: 5px 10px;
        font-size: 0.875rem;
        border-radius: 4px;
        transition: all 0.2s ease;
    }

    .btn-sm:hover {
        transform: translateY(-1px);
        box-shadow: 0 2px 4px rgba(0,0,0,0.2);
    }

    /* Action Column - Prevent Wrapping */
    .table td.text-nowrap {
        white-space: nowrap;
    }

    /* Status Badges */
    .badge {
        padding: 6px 12px;
        font-size: 0.85rem;
        font-weight: 500;
        border-radius: 20px;
    }

    /* Table Responsive Wrapper */
    .table-responsive {
        border-radius: 8px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        margin-bottom: 20px;
        background: white;
    }

    /* Table Striped Rows */
    .table-striped tbody tr:nth-of-type(odd) {
        background-color: #f9f9f9;
    }

    /* Search Input Group */
    .input-group {
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        border-radius: 6px;
        overflow: hidden;
    }

    .input-group .form-control {
        border: 1px solid #ced4da;
        padding: 10px 15px;
    }

    .input-group .form-control:focus {
        border-color: #007bff;
        box-shadow: 0 0 0 0.2rem rgba(0,123,255,0.25);
    }

    /* Modal Styling */
    .modal-header {
        border-bottom: 2px solid #dee2e6;
    }

    .modal-header.bg-info {
        background-color: #17a2b8 !important;
    }

    .modal-header.bg-warning {
        background-color: #ffc107 !important;
    }

    .modal-body table tr td:first-child {
        font-weight: 600;
        color: #495057;
    }

    .modal-lg .table-sm td {
        padding: 8px;
    }

    /* Modal Section Headers */
    .modal-body h6 {
        font-weight: 600;
        border-bottom: 2px solid #007bff;
        padding-bottom: 8px;
        margin-bottom: 15px;
    }

    .modal-body h6 i {
        margin-right: 8px;
    }

    /* Alert Styling */
    .alert {
        border-radius: 6px;
        padding: 15px 20px;
        font-size: 0.95rem;
    }

    .alert i {
        margin-right: 8px;
    }

    /* Booking ID Column - Make it Stand Out */
    .table tbody td:nth-child(2) {
        font-weight: 600;
        color: #007bff;
        font-family: 'Courier New', monospace;
    }

    /* Price Column - Highlight */
    .table tbody td:nth-child(7) {
        font-weight: 600;
        color: #28a745;
    }

    /* Empty State */
    .alert-info {
        background-color: #d1ecf1;
        border-color: #bee5eb;
        color: #0c5460;
        text-align: center;
        padding: 30px;
        font-size: 1rem;
    }

    /* Form Select in Modal */
    .modal-body .form-select {
        border: 2px solid #ced4da;
        padding: 10px;
        border-radius: 6px;
        font-size: 0.95rem;
    }

    .modal-body .form-select:focus {
        border-color: #007bff;
        box-shadow: 0 0 0 0.2rem rgba(0,123,255,0.25);
    }

    /* Status Color Variations */
    .bg-success {
        background-color: #28a745 !important;
    }

    .bg-warning {
        background-color: #ffc107 !important;
        color: #000 !important;
    }

    .bg-danger {
        background-color: #dc3545 !important;
    }

    .bg-primary {
        background-color: #007bff !important;
    }

    /* Custom Scrollbar for Table */
    .table-responsive::-webkit-scrollbar {
        height: 8px;
    }

    .table-responsive::-webkit-scrollbar-track {
        background: #f1f1f1;
        border-radius: 10px;
    }

    .table-responsive::-webkit-scrollbar-thumb {
        background: #007bff;
        border-radius: 10px;
    }

    .table-responsive::-webkit-scrollbar-thumb:hover {
        background: #0056b3;
    }

    /* Responsive Adjustments */
    @media (max-width: 768px) {
        .table th, .table td {
            font-size: 0.85rem;
            padding: 8px 4px;
        }
        
        .btn-sm {
            padding: 4px 8px;
            font-size: 0.75rem;
            margin: 1px;
        }
        
        .badge {
            font-size: 0.75rem;
            padding: 4px 8px;
        }
    }
    </style>
</head>

<body>
    <!-- Sidebar -->
    <nav class="sidebar">
        <a href="admin_dashboard.php"><i class="fas fa-home"></i> Dashboard</a>
        <a href="vehicles.php"><i class="fas fa-bus"></i> Vehicles</a>
        <a href="users.php"><i class="fas fa-users"></i> Users</a>
        <a href="bookings.php"><i class="fas fa-calendar-alt"></i> Booking</a>
        <a href="admin_report.php"><i class="fas fa-chart-line"></i> Reports</a>
        <a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </nav>

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