<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'user') {
    header("Location: ../index.php");
    exit;
}

require_once '../db.php';
// ADD THIS LINE: Include the email functions
require_once 'email_functions.php';

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['error'] = "Invalid request method.";
    header("Location: user_dashboard.php");
    exit;
}

// Get form data
$vehicle_id = intval($_POST['vehicle_id'] ?? 0);
$start_date = $_POST['start_date'] ?? '';
$end_date = $_POST['end_date'] ?? '';
$user_id = $_SESSION['user_id'];

// Get additional booking details from form
$full_name = trim($_POST['full_name'] ?? '');
$contact_number = trim($_POST['contact_number'] ?? '');
$alternative_number = trim($_POST['alternative_number'] ?? '');
$email = trim($_POST['email'] ?? '');
$notes = trim($_POST['notes'] ?? '');

// Validate required fields
if (empty($full_name) || empty($contact_number) || empty($email)) {
    $_SESSION['error'] = "Please fill in all required fields (Full Name, Contact Number, Email).";
    header("Location: user_dashboard.php");
    exit;
}

// Validate email format
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $_SESSION['error'] = "Please enter a valid email address.";
    header("Location: user_dashboard.php");
    exit;
}

// Validate contact number (10 digits)
if (!preg_match('/^\d{10}$/', str_replace(' ', '', $contact_number))) {
    $_SESSION['error'] = "Please enter a valid 10-digit contact number.";
    header("Location: user_dashboard.php");
    exit;
}

// Validate alternative number if provided (10 digits)
if (!empty($alternative_number) && !preg_match('/^\d{10}$/', str_replace(' ', '', $alternative_number))) {
    $_SESSION['error'] = "Please enter a valid 10-digit alternative contact number.";
    header("Location: user_dashboard.php");
    exit;
}

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

// Validate vehicle_id
if ($vehicle_id <= 0) {
    $_SESSION['error'] = "Invalid vehicle selected.";
    header("Location: user_dashboard.php");
    exit;
}

// Get vehicle details
$vehicleQuery = "SELECT v.*, vt.type_name 
                 FROM vehicles v 
                 JOIN vehicle_types vt ON v.vehicle_type_id = vt.id 
                 WHERE v.id = ? AND v.status = 'available'";
$stmt = $conn->prepare($vehicleQuery);
if (!$stmt) {
    $_SESSION['error'] = "Database error: " . $conn->error;
    header("Location: user_dashboard.php");
    exit;
}

$stmt->bind_param("i", $vehicle_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $_SESSION['error'] = "Vehicle not found or not available.";
    $stmt->close();
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
if (!$checkStmt) {
    $_SESSION['error'] = "Database error: " . $conn->error;
    header("Location: user_dashboard.php");
    exit;
}

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

// Generate unique booking ID
$booking_id = 'BK' . date('Ymd') . rand(1000, 9999);

// Check if booking ID already exists
$idCheckQuery = "SELECT COUNT(*) as count FROM bookings WHERE booking_id = ?";
$idCheckStmt = $conn->prepare($idCheckQuery);
if (!$idCheckStmt) {
    $_SESSION['error'] = "Database error: " . $conn->error;
    header("Location: user_dashboard.php");
    exit;
}

$idCheckStmt->bind_param("s", $booking_id);
$idCheckStmt->execute();
$idCheckResult = $idCheckStmt->get_result();
$idCheckRow = $idCheckResult->fetch_assoc();
$idCheckStmt->close();

// Regenerate if exists
$attempts = 0;
while ($idCheckRow['count'] > 0 && $attempts < 10) {
    $booking_id = 'BK' . date('Ymd') . rand(1000, 9999);
    $idCheckStmt = $conn->prepare($idCheckQuery);
    $idCheckStmt->bind_param("s", $booking_id);
    $idCheckStmt->execute();
    $idCheckResult = $idCheckStmt->get_result();
    $idCheckRow = $idCheckResult->fetch_assoc();
    $idCheckStmt->close();
    $attempts++;
}

// Insert booking with new fields
$insertQuery = "INSERT INTO bookings (
    booking_id, 
    user_id, 
    vehicle_id, 
    trip_start, 
    trip_end, 
    price, 
    status, 
    user_name, 
    contact_number, 
    alternative_number, 
    email, 
    notes
) VALUES (?, ?, ?, ?, ?, ?, 'pending', ?, ?, ?, ?, ?)";

$insertStmt = $conn->prepare($insertQuery);
if (!$insertStmt) {
    $_SESSION['error'] = "Database error: " . $conn->error;
    header("Location: user_dashboard.php");
    exit;
}

$insertStmt->bind_param(
    "siissdsssss",
    $booking_id,
    $user_id,
    $vehicle_id,
    $start_date,
    $end_date,
    $vehicle['price'],
    $full_name,
    $contact_number,
    $alternative_number,
    $email,
    $notes
);

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
        'user_name' => $full_name,
        'user_email' => $email,
        'contact_number' => $contact_number,
        'alternative_number' => $alternative_number,
        'notes' => $notes
    ];
    
    // ===== ADD EMAIL SENDING HERE =====
    // Prepare booking data for email
    $emailData = [
        'booking_id' => $booking_id,
        'user_name' => $full_name,
        'user_email' => $email,
        'contact_number' => $contact_number,
        'alternative_number' => $alternative_number,
        'vehicle_name' => $vehicle['vehicle_name'],
        'vehicle_number' => $vehicle['vehicle_number'],
        'vehicle_type' => $vehicle['type_name'],
        'trip_start' => $start_date,
        'trip_end' => $end_date,
        'price' => $vehicle['price'],
        'notes' => $notes
    ];
    
    // Send confirmation email
    $emailSent = sendBookingConfirmationEmail($emailData);
    
    // Optional: Log email status
    if ($emailSent) {
        error_log("Booking confirmation email sent successfully to: " . $email);
    } else {
        error_log("Failed to send booking confirmation email to: " . $email);
        // Note: We don't stop the process if email fails
        // The booking is still successful
    }
    // ===== END EMAIL SENDING =====
    
    header("Location: booking_success.php");
    exit;
} else {
    $error_msg = $insertStmt->error;
    $insertStmt->close();
    $_SESSION['error'] = "Failed to create booking. Please try again. Error: " . $error_msg;
    header("Location: user_dashboard.php");
    exit;
}
?>