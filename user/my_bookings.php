<?php
session_start();
require_once '../db.php'; // your DB connection

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

$user_id = $_SESSION['user_id'];

// Fetch bookings with vehicle info and vehicle type name
$sql = "
    SELECT 
        b.*, 
        v.vehicle_number, 
        vt.type_name AS vehicle_type
    FROM bookings b
    JOIN vehicles v ON b.vehicle_id = v.id
    JOIN vehicle_types vt ON v.vehicle_type_id = vt.id
    WHERE b.user_id = ?
    ORDER BY b.created_at DESC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $user_id);
$stmt->execute();
$result = $stmt->get_result();

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <title>My Bookings</title>
    <style>
    body {
        font-family: Arial, sans-serif;
        max-width: 900px;
        margin: auto;
        padding: 20px;
    }

    h1 {
        margin-bottom: 1rem;
    }

    table {
        width: 100%;
        border-collapse: collapse;
    }

    th,
    td {
        padding: 12px;
        border: 1px solid #ccc;
        text-align: left;
    }

    th {
        background: #f5f5f5;
    }

    .status-pending {
        color: orange;
    }

    .status-confirmed {
        color: green;
    }

    .status-completed {
        color: blue;
    }

    .status-cancelled {
        color: red;
    }

    .success-message {
        background-color: #d4edda;
        color: #155724;
        border: 1px solid #c3e6cb;
        padding: 10px;
        margin-bottom: 20px;
        border-radius: 4px;
    }
    </style>
</head>

<body>

    <h1>My Bookings</h1>

    <?php if (isset($_GET['success'])): ?>
    <div class="success-message">Booking successful!</div>
    <?php endif; ?>


    <?php if ($result->num_rows > 0): ?>
    <table>
        <thead>
            <tr>
                <th>Booking ID</th>
                <th>Vehicle</th>
                <th>Origin</th>
                <th>Destination</th>
                <th>Departure</th>
                <th>Arrival</th>
                <th>Status</th>
                <th>Booked On</th>
                <th>Download</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($booking = $result->fetch_assoc()): ?>

            <tr>
                <td><?= htmlspecialchars($booking['booking_id']) ?></td>
                <td>
                    <?= htmlspecialchars($booking['vehicle_type']) ?>
                    (<?= htmlspecialchars($booking['vehicle_number']) ?>)
                </td>
                <td><?= htmlspecialchars($booking['origin']) ?></td>
                <td><?= htmlspecialchars($booking['destination']) ?></td>
                <td><?= date("g:i A, d M Y", strtotime($booking['departure_time'])) ?></td>
                <td><?= date("g:i A, d M Y", strtotime($booking['arrival_time'])) ?></td>
                <td class="status-<?= htmlspecialchars($booking['status']) ?>">
                    <?= ucfirst($booking['status']) ?>
                </td>
                <td><?= date("d M Y, g:i A", strtotime($booking['created_at'])) ?></td>
                <td><a href="booking_pdf.php?booking_id=<?= urlencode($booking['booking_id']) ?>" target="_blank">
                        📄 Download PDF
                    </a></td>
            </tr>

            <?php endwhile; ?>
        </tbody>
    </table>


    <?php else: ?>
    <p>You have no bookings yet.</p>
    <?php endif; ?>

</body>

</html>