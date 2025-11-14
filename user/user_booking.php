<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'user') {
    header("Location: ../index.php");
    exit;
}

require_once '../db.php';

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: user_dashboard.php");
    exit;
}

// Get form data
$vehicle_id = intval($_POST['vehicle_id']);
$start_date = $_POST['start_date'];
$end_date = $_POST['end_date'];
$user_id = $_SESSION['user_id'];

// Validate dates
if (empty($start_date) || empty($end_date)) {
    $_SESSION['error'] = "Please select both start and end dates.";
    header("Location: user_dashboard.php");
    exit;
}

if (strtotime($end_date) < strtotime($start_date)) {
    $_SESSION['error'] = "End date cannot be before start date.";
    header("Location: user_dashboard.php");
    exit;
}

// Get vehicle details
$vehicleQuery = "SELECT v.*, vt.type_name 
                 FROM vehicles v 
                 JOIN vehicle_types vt ON v.vehicle_type_id = vt.id 
                 WHERE v.id = ? AND v.status = 'available'";
$stmt = $conn->prepare($vehicleQuery);
$stmt->bind_param("i", $vehicle_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $_SESSION['error'] = "Vehicle not found or not available.";
    header("Location: user_dashboard.php");
    exit;
}

$vehicle = $result->fetch_assoc();
$stmt->close();

// Check if vehicle is already booked for these dates
$checkQuery = "SELECT COUNT(*) as count FROM bookings 
               WHERE vehicle_id = ? 
               AND status IN ('pending', 'confirmed')
               AND ((trip_start BETWEEN ? AND ?) 
                    OR (trip_end BETWEEN ? AND ?)
                    OR (trip_start <= ? AND trip_end >= ?))";
$checkStmt = $conn->prepare($checkQuery);
$checkStmt->bind_param("issssss", $vehicle_id, $start_date, $end_date, $start_date, $end_date, $start_date, $end_date);
$checkStmt->execute();
$checkResult = $checkStmt->get_result();
$checkRow = $checkResult->fetch_assoc();
$checkStmt->close();

if ($checkRow['count'] > 0) {
    $_SESSION['error'] = "This vehicle is already booked for the selected dates.";
    header("Location: user_dashboard.php");
    exit;
}

// Get user details
$userQuery = "SELECT name, email FROM users WHERE id = ?";
$userStmt = $conn->prepare($userQuery);
$userStmt->bind_param("i", $user_id);
$userStmt->execute();
$userResult = $userStmt->get_result();
$user = $userResult->fetch_assoc();
$userStmt->close();

// Generate unique booking ID
$booking_id = 'BK' . date('Ymd') . rand(1000, 9999);

// Check if booking ID already exists
$idCheckQuery = "SELECT COUNT(*) as count FROM bookings WHERE booking_id = ?";
$idCheckStmt = $conn->prepare($idCheckQuery);
$idCheckStmt->bind_param("s", $booking_id);
$idCheckStmt->execute();
$idCheckResult = $idCheckStmt->get_result();
$idCheckRow = $idCheckResult->fetch_assoc();
$idCheckStmt->close();

// Regenerate if exists
while ($idCheckRow['count'] > 0) {
    $booking_id = 'BK' . date('Ymd') . rand(1000, 9999);
    $idCheckStmt = $conn->prepare($idCheckQuery);
    $idCheckStmt->bind_param("s", $booking_id);
    $idCheckStmt->execute();
    $idCheckResult = $idCheckStmt->get_result();
    $idCheckRow = $idCheckResult->fetch_assoc();
    $idCheckStmt->close();
}

// Insert booking
$insertQuery = "INSERT INTO bookings (booking_id, user_id, vehicle_id, trip_start, trip_end, price, status, user_name) 
                VALUES (?, ?, ?, ?, ?, ?, 'pending', ?)";
$insertStmt = $conn->prepare($insertQuery);
$insertStmt->bind_param("siissds", $booking_id, $user_id, $vehicle_id, $start_date, $end_date, $vehicle['price'], $user['name']);

if ($insertStmt->execute()) {
    $insertStmt->close();
    
    // Store booking details in session for success page
    $_SESSION['booking_success'] = [
        'booking_id' => $booking_id,
        'vehicle_name' => $vehicle['vehicle_name'],
        'vehicle_number' => $vehicle['vehicle_number'],
        'vehicle_type' => $vehicle['type_name'],
        'trip_start' => $start_date,
        'trip_end' => $end_date,
        'price' => $vehicle['price'],
        'user_name' => $user['name'],
        'user_email' => $user['email']
    ];
    
    header("Location: booking_success.php");
    exit;
} else {
    $_SESSION['error'] = "Failed to create booking. Please try again.";
    header("Location: user_dashboard.php");
    exit;
}
?>