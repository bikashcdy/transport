<?php
session_start();
require_once '../db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'user') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $booking_id = $_POST['booking_id'] ?? '';

    if (empty($booking_id)) {
        echo json_encode(['success' => false, 'message' => 'Booking ID is required']);
        exit;
    }

    $user_id = $_SESSION['user_id'];
    $sql = "UPDATE bookings SET status = 'cancelled' WHERE booking_id = ? AND user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('si', $booking_id, $user_id);

    if ($stmt->execute() && $stmt->affected_rows > 0) {
        echo json_encode(['success' => true, 'message' => 'Booking cancelled successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to cancel booking or booking not found']);
    }
    $stmt->close();
}
$conn->close();
