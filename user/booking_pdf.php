<?php
require('fpdf/fpdf.php'); // <- This is now the correct path
require('../db.php'); // Update path if your DB connection file is elsewhere

if (!isset($_GET['booking_id'])) {
    die('Booking ID missing.');
}

$booking_id = $_GET['booking_id'];

$stmt = $conn->prepare("
    SELECT b.booking_id, b.origin, b.destination, b.departure_time, b.arrival_time, b.status, b.created_at,
           v.vehicle_number, vt.type_name
    FROM bookings b
    JOIN vehicles v ON b.vehicle_id = v.id
    JOIN vehicle_types vt ON v.vehicle_type_id = vt.id
    WHERE b.booking_id = ?
");
$stmt->bind_param('s', $booking_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die('Booking not found.');
}

$data = $result->fetch_assoc();

$pdf = new FPDF();
$pdf->AddPage();
$pdf->SetFont('Arial', 'B', 22);
$pdf->Cell(0, 10, 'Bikash Transport', 0, 1, 'C');
$pdf->Ln(5);

$pdf->SetFont('Arial', 'B', 16);
$pdf->Cell(0, 10, 'Booking Confirmation', 0, 1, 'C');

$pdf->SetFont('Arial', '', 12);
$pdf->Cell(50, 10, 'Booking ID:', 0);
$pdf->Cell(0, 10, $data['booking_id'], 0, 1);

$pdf->Cell(50, 10, 'Origin:', 0);
$pdf->Cell(0, 10, $data['origin'], 0, 1);

$pdf->Cell(50, 10, 'Destination:', 0);
$pdf->Cell(0, 10, $data['destination'], 0, 1);

$pdf->Cell(50, 10, 'Departure:', 0);
$pdf->Cell(0, 10, date("d M Y, h:i A", strtotime($data['departure_time'])), 0, 1);

$pdf->Cell(50, 10, 'Arrival:', 0);
$pdf->Cell(0, 10, date("d M Y, h:i A", strtotime($data['arrival_time'])), 0, 1);

$pdf->Cell(50, 10, 'Vehicle:', 0);
$pdf->Cell(0, 10, $data['vehicle_number'] . ' (' . $data['type_name'] . ')', 0, 1);

$pdf->Cell(50, 10, 'Status:', 0);
$pdf->Cell(0, 10, ucfirst($data['status']), 0, 1);

$pdf->Ln(10);
$pdf->Cell(0, 10, 'Thank you for booking with us!', 0, 1, 'C');

// Show PDF in browser
$pdf->Output('I', 'booking_' . $booking_id . '.pdf');
exit;