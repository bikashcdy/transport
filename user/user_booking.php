<?php
session_start();
require_once '../db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'user') {
    header("Location: ../index.php");
    exit;
}

$userId = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['way_id'])) {
        die("Invalid request.");
    }

    $wayId = intval($_POST['way_id']);

    // Fetch way details
    $stmt = $conn->prepare("
        SELECT w.*, v.id as vehicle_id
        FROM ways w
        JOIN vehicles v ON w.vehicle_id = v.id
        WHERE w.id = ?
    ");
    $stmt->bind_param("i", $wayId);
    $stmt->execute();
    $result = $stmt->get_result();
    $way = $result->fetch_assoc();

    if (!$way) {
        die("Route not found.");
    }

    // Create booking
    $bookingId = 'BK' . strtoupper(uniqid());
    $vehicleId = $way['vehicle_id'];
    $origin = $way['origin'];
    $destination = $way['destination'];
    $departureTime = date('Y-m-d') . ' ' . $way['departure_time'];
    $arrivalTime = date('Y-m-d') . ' ' . $way['arrival_time'];

    $insert = $conn->prepare("
        INSERT INTO bookings (booking_id, user_id, vehicle_id, origin, destination, departure_time, arrival_time)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    $insert->bind_param(
        "siissss",
        $bookingId,
        $userId,
        $vehicleId,
        $origin,
        $destination,
        $departureTime,
        $arrivalTime
    );

    if ($insert->execute()) {
        header("Location: my_bookings.php?success=1");
        exit;
    } else {
        echo "Booking failed. Please try again.";
    }
} else {
    echo "Invalid access method.";
}