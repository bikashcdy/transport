<?php
$pageTitle = "Admin Dashboard";

function pageContent()
{
    global $conn;

    // Helper function for safe SQL execution
    function safeQuery($conn, $sql, $label)
    {
        $result = $conn->query($sql);
        if (!$result) {
            echo "<div class='alert alert-danger'>SQL Error in $label: " . htmlspecialchars($conn->error) . "</div>";
            return ['total' => 0];
        }
        return $result->fetch_assoc();
    }

    // ---- Fetch Dashboard Data ----
    $totalVehicles = safeQuery($conn, "SELECT COUNT(*) AS total FROM vehicles", "Vehicles")['total'];
    $totalUsers = safeQuery($conn, "SELECT COUNT(*) AS total FROM users", "Users")['total'];
    $activeBookings = safeQuery($conn, "SELECT COUNT(*) AS total FROM bookings WHERE status IN ('active','confirmed','completed')", "Active Bookings")['total'];
    $reports = safeQuery($conn, "SELECT COUNT(*) AS total FROM reports", "Reports")['total'];

    // ---- Calculate Total Revenue ----
    // Use 'price' column for total cost and count only relevant bookings
    $revenueQuery = "
        SELECT IFNULL(SUM(price), 0) AS total 
        FROM bookings 
        WHERE status IN ('active', 'confirmed', 'completed')
    ";
    $totalRevenue = safeQuery($conn, $revenueQuery, "Total Revenue")['total'];
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

    <!-- Total Revenue Card -->
    <div class="col-md-3 col-sm-6">
        <div class="card text-center shadow-sm border-0 h-100"
            style="background:linear-gradient(45deg,#3b82f6,#60a5fa);color:white;">
            <div class="card-body">
                <i class="fas fa-dollar-sign fa-2x mb-2"></i>
                <h5>Total Revenue</h5>
                <h2>Rs <?= number_format($totalRevenue, 2); ?></h2>
            </div>
        </div>
    </div>
</div>

<!-- Recent Activity -->
<div class="card mt-4 shadow-sm">
    <div class="card-header bg-white">
        <h5 class="mb-0"><i class="fas fa-history"></i> Recent Activity</h5>
    </div>
    <div class="card-body">
        <p class="text-muted mb-0">You can show recent bookings, vehicle updates, or reports here.</p>
    </div>
</div>

<?php
}

include 'template.php';
?>
