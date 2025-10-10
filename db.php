<?php
$host = "localhost";
$user = "root";
$pass = "";
$dbname = "transport_db";

$conn = new mysqli($host, $user, $pass, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// ✅ Helper function to check table existence
function tableExists($conn, $table)
{
    $result = $conn->query("SHOW TABLES LIKE '$table'");
    return $result && $result->num_rows > 0;
}

// ✅ Create tables if not exist

// 1. vehicle_types
if (!tableExists($conn, 'vehicle_types')) {
    $conn->query("
        CREATE TABLE vehicle_types (
            id INT AUTO_INCREMENT PRIMARY KEY,
            type_name VARCHAR(50) NOT NULL
        );
    ");
    $conn->query("
        INSERT INTO vehicle_types (type_name) VALUES
        ('Bus'), ('Truck'), ('Van'), ('Car');
    ");
    echo "✅ Created table: vehicle_types<br>";
}

// 2. vehicles
if (!tableExists($conn, 'vehicles')) {
    $conn->query("
        CREATE TABLE vehicles (
            id INT AUTO_INCREMENT PRIMARY KEY,
            vehicle_number VARCHAR(50) NOT NULL,
            vehicle_type_id INT NOT NULL,
            capacity INT NOT NULL,
            status VARCHAR(20) DEFAULT 'Available',
            FOREIGN KEY (vehicle_type_id) REFERENCES vehicle_types(id)
        );
    ");
    echo "✅ Created table: vehicles<br>";
}

// 3. reports
if (!tableExists($conn, 'reports')) {
    $conn->query("
        CREATE TABLE reports (
            id INT AUTO_INCREMENT PRIMARY KEY,
            report_name VARCHAR(100),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        );
    ");
    echo "✅ Created table: reports<br>";
}

// 4. bookings
if (!tableExists($conn, 'bookings')) {
    $conn->query("
        CREATE TABLE bookings (
            id INT AUTO_INCREMENT PRIMARY KEY,
            booking_id VARCHAR(50) UNIQUE,
            user_id INT NOT NULL,
            vehicle_id INT NOT NULL,
            origin VARCHAR(100) NOT NULL,
            destination VARCHAR(100) NOT NULL,
            departure_time DATETIME NOT NULL,
            arrival_time DATETIME NOT NULL,
            status ENUM('pending','confirmed','completed','cancelled') DEFAULT 'pending',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id),
            FOREIGN KEY (vehicle_id) REFERENCES vehicles(id)
        );
    ");
    echo "✅ Created table: bookings<br>";
}

echo "<br>✅ All tables are ready.";

$conn->close();
?>