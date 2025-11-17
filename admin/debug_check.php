<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header("Location: ../index.php");
    exit;
}

require_once '../db.php';

echo "<h1>🔍 Database Debug Information</h1>";
echo "<style>
    body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
    .section { background: white; padding: 20px; margin: 20px 0; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
    h2 { color: #2c3e50; border-bottom: 2px solid #3498db; padding-bottom: 10px; }
    table { width: 100%; border-collapse: collapse; margin: 10px 0; }
    th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
    th { background: #3498db; color: white; }
    .success { color: #27ae60; font-weight: bold; }
    .error { color: #e74c3c; font-weight: bold; }
    .warning { color: #f39c12; font-weight: bold; }
</style>";

// Check database connection
echo "<div class='section'>";
echo "<h2>1. Database Connection</h2>";
if ($conn) {
    echo "<p class='success'>✅ Database connected successfully!</p>";
    echo "<p>Database: " . $conn->server_info . "</p>";
} else {
    echo "<p class='error'>❌ Database connection failed!</p>";
    exit;
}
echo "</div>";

// Check tables exist
echo "<div class='section'>";
echo "<h2>2. Tables Check</h2>";
$tables = ['bookings', 'users', 'vehicles'];
echo "<table><tr><th>Table Name</th><th>Status</th><th>Record Count</th></tr>";

foreach ($tables as $table) {
    $result = $conn->query("SHOW TABLES LIKE '$table'");
    if ($result && $result->num_rows > 0) {
        $countResult = $conn->query("SELECT COUNT(*) as count FROM $table");
        $count = $countResult->fetch_assoc()['count'];
        echo "<tr><td>$table</td><td class='success'>✅ Exists</td><td>$count records</td></tr>";
    } else {
        echo "<tr><td>$table</td><td class='error'>❌ Missing</td><td>-</td></tr>";
    }
}
echo "</table></div>";

// Check bookings data
echo "<div class='section'>";
echo "<h2>3. Bookings Table Details</h2>";
$bookingsResult = $conn->query("SELECT COUNT(*) as total FROM bookings");
if ($bookingsResult) {
    $total = $bookingsResult->fetch_assoc()['total'];
    echo "<p><strong>Total Bookings:</strong> " . ($total > 0 ? "<span class='success'>$total</span>" : "<span class='warning'>0 (No bookings yet)</span>") . "</p>";
    
    if ($total > 0) {
        echo "<h3>Sample Bookings:</h3>";
        $sample = $conn->query("SELECT booking_id, user_name, vehicle_id, price, status, created_at FROM bookings ORDER BY created_at DESC LIMIT 5");
        if ($sample && $sample->num_rows > 0) {
            echo "<table><tr><th>Booking ID</th><th>User</th><th>Vehicle</th><th>Price</th><th>Status</th><th>Created</th></tr>";
            while ($row = $sample->fetch_assoc()) {
                echo "<tr>";
                echo "<td>" . htmlspecialchars($row['booking_id']) . "</td>";
                echo "<td>" . htmlspecialchars($row['user_name']) . "</td>";
                echo "<td>" . htmlspecialchars($row['vehicle_id']) . "</td>";
                echo "<td>Rs. " . number_format($row['price']) . "</td>";
                echo "<td>" . htmlspecialchars($row['status']) . "</td>";
                echo "<td>" . date('M d, Y', strtotime($row['created_at'])) . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        }
        
        echo "<h3>Booking Status Breakdown:</h3>";
        $statusResult = $conn->query("SELECT status, COUNT(*) as count FROM bookings GROUP BY status");
        if ($statusResult && $statusResult->num_rows > 0) {
            echo "<table><tr><th>Status</th><th>Count</th></tr>";
            while ($row = $statusResult->fetch_assoc()) {
                echo "<tr><td>" . ucfirst($row['status']) . "</td><td>" . $row['count'] . "</td></tr>";
            }
            echo "</table>";
        }
    }
} else {
    echo "<p class='error'>Error querying bookings table: " . $conn->error . "</p>";
}
echo "</div>";

// Check users data
echo "<div class='section'>";
echo "<h2>4. Users Table Details</h2>";
$usersResult = $conn->query("SELECT COUNT(*) as total FROM users WHERE user_type='user'");
if ($usersResult) {
    $total = $usersResult->fetch_assoc()['total'];
    echo "<p><strong>Total Customers:</strong> " . ($total > 0 ? "<span class='success'>$total</span>" : "<span class='warning'>0 (No users yet)</span>") . "</p>";
    
    if ($total > 0) {
        echo "<h3>Sample Users:</h3>";
        $sample = $conn->query("SELECT id, name, email, user_type FROM users LIMIT 5");
        if ($sample && $sample->num_rows > 0) {
            echo "<table><tr><th>ID</th><th>Name</th><th>Email</th><th>Type</th></tr>";
            while ($row = $sample->fetch_assoc()) {
                echo "<tr>";
                echo "<td>" . $row['id'] . "</td>";
                echo "<td>" . htmlspecialchars($row['name']) . "</td>";
                echo "<td>" . htmlspecialchars($row['email']) . "</td>";
                echo "<td>" . htmlspecialchars($row['user_type']) . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        }
    }
} else {
    echo "<p class='error'>Error querying users table: " . $conn->error . "</p>";
}
echo "</div>";

// Check vehicles data
echo "<div class='section'>";
echo "<h2>5. Vehicles Table Details</h2>";
$vehiclesResult = $conn->query("SELECT COUNT(*) as total FROM vehicles");
if ($vehiclesResult) {
    $total = $vehiclesResult->fetch_assoc()['total'];
    echo "<p><strong>Total Vehicles:</strong> " . ($total > 0 ? "<span class='success'>$total</span>" : "<span class='warning'>0 (No vehicles yet)</span>") . "</p>";
    
    if ($total > 0) {
        echo "<h3>Sample Vehicles:</h3>";
        $sample = $conn->query("SELECT id, make, model, category FROM vehicles LIMIT 5");
        if ($sample && $sample->num_rows > 0) {
            echo "<table><tr><th>ID</th><th>Make</th><th>Model</th><th>Category</th></tr>";
            while ($row = $sample->fetch_assoc()) {
                echo "<tr>";
                echo "<td>" . $row['id'] . "</td>";
                echo "<td>" . htmlspecialchars($row['make']) . "</td>";
                echo "<td>" . htmlspecialchars($row['model']) . "</td>";
                echo "<td>" . htmlspecialchars($row['category']) . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        }
    }
} else {
    echo "<p class='error'>Error querying vehicles table: " . $conn->error . "</p>";
}
echo "</div>";

// Check revenue
echo "<div class='section'>";
echo "<h2>6. Revenue Information</h2>";
$revenueResult = $conn->query("SELECT COALESCE(SUM(price), 0) as total_revenue FROM bookings WHERE status='completed'");
if ($revenueResult) {
    $revenue = $revenueResult->fetch_assoc()['total_revenue'];
    echo "<p><strong>Total Revenue (Completed):</strong> <span class='success'>Rs. " . number_format($revenue) . "</span></p>";
    
    $allRevenueResult = $conn->query("SELECT COALESCE(SUM(price), 0) as total FROM bookings");
    $allRevenue = $allRevenueResult->fetch_assoc()['total'];
    echo "<p><strong>Total Booking Value (All Status):</strong> Rs. " . number_format($allRevenue) . "</p>";
}
echo "</div>";

// Check table structure
echo "<div class='section'>";
echo "<h2>7. Bookings Table Structure</h2>";
$structureResult = $conn->query("DESCRIBE bookings");
if ($structureResult && $structureResult->num_rows > 0) {
    echo "<table><tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th></tr>";
    while ($row = $structureResult->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['Field'] . "</td>";
        echo "<td>" . $row['Type'] . "</td>";
        echo "<td>" . $row['Null'] . "</td>";
        echo "<td>" . $row['Key'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
}
echo "</div>";

echo "<div class='section'>";
echo "<h2>✅ Recommendations</h2>";
echo "<ul>";
echo "<li>If bookings show 0, add some test bookings through your booking form</li>";
echo "<li>If users show 0, register some test customers</li>";
echo "<li>If vehicles show 0, add vehicles through your admin panel</li>";
echo "<li>Make sure the 'created_at' column exists in bookings table</li>";
echo "<li>Check that booking prices are being saved correctly</li>";
echo "</ul>";
echo "<p><a href='admin_report.php' style='display: inline-block; padding: 10px 20px; background: #3498db; color: white; text-decoration: none; border-radius: 5px; margin-top: 10px;'>← Back to Dashboard</a></p>";
echo "</div>";

$conn->close();
?>