<?php
session_start();
require_once '../db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$user_id = $_SESSION['user_id'];

// Fetch bookings with vehicle information
$sql = "SELECT p.*, v.vehicle_name 
        FROM passengers p 
        LEFT JOIN vehicles v ON p.vehicle_id = v.id 
        WHERE p.user_id = ? 
        ORDER BY p.booking_date DESC, p.id DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $user_id);
$stmt->execute();
$result = $stmt->get_result();

$bookings = [];
while ($row = $result->fetch_assoc()) {
    $bookings[] = $row;
}

echo json_encode(['success' => true, 'bookings' => $bookings]);

$stmt->close();
$conn->close();
?>