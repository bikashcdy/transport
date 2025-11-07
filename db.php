<?php
// Database credentials
$host = "localhost";        // usually localhost
$username = "root";         // your MySQL username
$password = "";             // your MySQL password (empty for XAMPP default)
$dbname = "transport_db";      // your database name

// Create connection
$conn = new mysqli($host, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    // Log the error instead of showing sensitive info
    error_log("Database connection failed: " . $conn->connect_error);
    die("Database connection failed. Please try again later.");
}

// Optional: set charset to avoid encoding issues
$conn->set_charset("utf8mb4");
?>
