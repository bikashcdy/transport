<?php
session_start();
require_once '../db.php';
require '../libs/PHPMailer/src/PHPMailer.php';
require '../libs/PHPMailer/src/SMTP.php';
require '../libs/PHPMailer/src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'user') {
    header("Location: ../index.php");
    exit;
}

$userId = $_SESSION['user_id'];

// Get user info
$userStmt = $conn->prepare("SELECT email, name FROM users WHERE id = ?");
$userStmt->bind_param("i", $userId);
$userStmt->execute();
$userResult = $userStmt->get_result();
$user = $userResult->fetch_assoc();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['way_id'])) {
        die("Invalid request.");
    }

    $wayId = intval($_POST['way_id']);

    // Get way and vehicle info
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

    $bookingId = 'BK' . strtoupper(uniqid());
    $vehicleId = $way['vehicle_id'];
    $origin = $way['origin'];
    $destination = $way['destination'];

    $today = date('Y-m-d');
    $departureTime = $today . ' ' . $way['departure_time'];
    $arrivalTime = $today . ' ' . $way['arrival_time'];

    $status = 'pending';

    // Insert booking
    $insert = $conn->prepare("
        INSERT INTO bookings (booking_id, user_id, vehicle_id, origin, destination, departure_time, arrival_time, status)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $insert->bind_param(
        "siisssss",
        $bookingId,
        $userId,
        $vehicleId,
        $origin,
        $destination,
        $departureTime,
        $arrivalTime,
        $status
    );

    if ($insert->execute()) {
        // Get transit stops
        $transitStmt = $conn->prepare("
            SELECT transit_point, transit_time, transit_duration
            FROM way_transits
            WHERE way_id = ?
        ");
        $transitStmt->bind_param("i", $wayId);
        $transitStmt->execute();
        $transitResult = $transitStmt->get_result();

        $transitsHTML = '';
        if ($transitResult->num_rows > 0) {
            $transitsHTML .= "<ul>";
            while ($row = $transitResult->fetch_assoc()) {
                $transitTimeFormatted = date("g:i A", strtotime($row['transit_time']));
                $duration = (int) $row['transit_duration'];
                $transitsHTML .= "<li><strong>{$row['transit_point']}</strong> - {$transitTimeFormatted}, {$duration} min</li>";
            }
            $transitsHTML .= "</ul>";
        } else {
            $transitsHTML = "<p>No transit stops.</p>";
        }

        // Send email using PHPMailer
        $mail = new PHPMailer(true);

        try {
            $mail->SMTPDebug = 2; // Show detailed debug output
            $mail->Debugoutput = 'html';

            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'bikashtransportt@gmail.com';
            $mail->Password = 'YOUR_APP_PASSWORD'; // Use valid App Password
            $mail->SMTPSecure = 'tls';
            $mail->Port = 587;

            $mail->setFrom('bikashtransportt@gmail.com', 'BookingNepal');
            $mail->addAddress($user['email'], $user['name']);

            $depTime = date("g:i A", strtotime($departureTime));
            $arrTime = date("g:i A", strtotime($arrivalTime));

            $mail->isHTML(true);
            $mail->Subject = 'Your Booking is Pending - ' . $bookingId;
            $mail->Body = "
                <h2>Booking Pending</h2>
                <p>Dear <strong>{$user['name']}</strong>,</p>
                <p>Your booking has been successfully created and is now pending.</p>
                <p><strong>Booking ID:</strong> {$bookingId}</p>
                <p><strong>From:</strong> {$origin} <br>
                <strong>To:</strong> {$destination}</p>
                <p><strong>Departure:</strong> {$depTime} <br>
                <strong>Arrival:</strong> {$arrTime}</p>
                <p><strong>Transit Stops:</strong><br>{$transitsHTML}</p>
                <p>We will notify you once your booking has been confirmed.</p>
                <p>Thank you for choosing TMS!</p>
            ";

            $mail->send();
            echo "<p>Email sent successfully to {$user['email']}</p>";
        } catch (Exception $e) {
            echo "<p>Email could not be sent. Mailer Error: {$mail->ErrorInfo}</p>";
        }

        header("Location: my_bookings.php?success=1");
        exit;
    } else {
        echo "Booking failed. Please try again.";
    }
} else {
    echo "Invalid access method.";
}
?>
