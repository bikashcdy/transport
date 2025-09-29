<?php
$pageTitle = "Admin Dashboard";

function pageContent()
{
    global $conn;

    // Fetching data from database using sql query 
    $totalVehicles = $conn->query("SELECT COUNT(*) AS total FROM vehicles")->fetch_assoc()['total'];
    $totalUsers = $conn->query("SELECT COUNT(*) AS total FROM users")->fetch_assoc()['total'];
    $activeBookings = $conn->query("SELECT COUNT(*) AS total FROM bookings WHERE status='active'")->fetch_assoc()['total'];
    $reports = $conn->query("SELECT COUNT(*) AS total FROM reports")->fetch_assoc()['total'];
    ?>

<div class="row g-4">
    <!-- Vehicles Card -->
    <div class="col-md-3 col-sm-6">
        <div class="card text-center shadow-sm border-0 h-100"
            style="background:linear-gradient(45deg,#4f46e5,#6366f1);color:white;">
            <div class="card-body">
                <i class="fas fa-bus fa-2x mb-2"></i>
                <h5>Total Vehicles</h5>
                <h2><?= $totalVehicles; ?></h2>
            </div>
        </div>
    </div>

    <!-- Users Card -->
    <div class="col-md-3 col-sm-6">
        <div class="card text-center shadow-sm border-0 h-100"
            style="background:linear-gradient(45deg,#10b981,#34d399);color:white;">
            <div class="card-body">
                <i class="fas fa-users fa-2x mb-2"></i>
                <h5>Total Users</h5>
                <h2><?= $totalUsers; ?></h2>
            </div>
        </div>
    </div>

    <!-- Active Bookings Card -->
    <div class="col-md-3 col-sm-6">
        <div class="card text-center shadow-sm border-0 h-100"
            style="background:linear-gradient(45deg,#f59e0b,#fbbf24);color:white;">
            <div class="card-body">
                <i class="fas fa-ticket-alt fa-2x mb-2"></i>
                <h5>Active Bookings</h5>
                <h2><?= $activeBookings; ?></h2>
            </div>
        </div>
    </div>

    <!-- Reports Card -->
    <div class="col-md-3 col-sm-6">
        <div class="card text-center shadow-sm border-0 h-100"
            style="background:linear-gradient(45deg,#ef4444,#f87171);color:white;">
            <div class="card-body">
                <i class="fas fa-chart-line fa-2x mb-2"></i>
                <h5>Reports Generated</h5>
                <h2><?= $reports; ?></h2>
            </div>
        </div>
    </div>
</div>

<!-- Recent activity table  -->
<div class="card mt-4 shadow-sm">
    <div class="card-header bg-white">
        <h5 class="mb-0"><i class="fas fa-history"></i> Recent Activity</h5>
    </div>
    <div class="card-body">
        <p class="text-muted mb-0">You can show recent bookings, vehicle updates, or reports here.</p>
    </div>
</div>

<?php }

include 'template.php';