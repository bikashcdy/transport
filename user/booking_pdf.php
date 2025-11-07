<?php
session_start();
require('../db.php');           // DB connection
require('fpdf/fpdf.php');       // FPDF
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Include PHPMailer
require '../phpmailer/src/Exception.php';
require '../phpmailer/src/PHPMailer.php';
require '../phpmailer/src/SMTP.php';

// ----------------- 1. Validate POST Data -----------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user_id'] ?? null;
    $vehicle_id = $_POST['vehicle_id'] ?? null;
    $origin = $_POST['origin'] ?? '';
    $destination = $_POST['destination'] ?? '';
    $departure_time = $_POST['departure_time'] ?? '';
    $arrival_time = $_POST['arrival_time'] ?? '';
    $price = $_POST['price'] ?? 0;

    if (!$user_id || !$vehicle_id || !$origin || !$destination || !$departure_time || !$arrival_time || !$price) {
        die("Missing required booking data.");
    }

    // ----------------- 2. Generate Booking ID -----------------
    $booking_id = 'BKT' . time() . rand(100, 999);
    $status = 'confirmed'; // Automatically confirmed; counts in total revenue

    // ----------------- 3. Insert Booking -----------------
    $stmt = $conn->prepare("INSERT INTO bookings 
        (booking_id, user_id, vehicle_id, origin, destination, departure_time, arrival_time, status, price, created_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");

    $stmt->bind_param('siissssd', $booking_id, $user_id, $vehicle_id, $origin, $destination, $departure_time, $arrival_time, $status, $price);

    if ($stmt->execute()) {

        // ----------------- 4. Fetch Booking & User Info -----------------
        $stmt2 = $conn->prepare("
            SELECT b.booking_id, b.origin, b.destination, b.departure_time, b.arrival_time, b.status, b.price,
                   v.vehicle_number, vt.type_name,
                   u.name AS user_name, u.email AS user_email
            FROM bookings b
            JOIN vehicles v ON b.vehicle_id = v.id
            JOIN vehicle_types vt ON v.vehicle_type_id = vt.id
            JOIN users u ON b.user_id = u.id
            WHERE b.booking_id = ?
        ");
        $stmt2->bind_param('s', $booking_id);
        $stmt2->execute();
        $result = $stmt2->get_result();
        $data = $result->fetch_assoc();

        // ----------------- 5. Generate PDF Ticket -----------------
        $pdf = new FPDF();
        $pdf->AddPage();

        $pdf->SetFont('Arial', 'B', 22);
        $pdf->Cell(0, 10, 'Bikash Transport', 0, 1, 'C');
        $pdf->SetFont('Arial', '', 12);
        $pdf->Cell(0, 8, 'Official Ticket Receipt', 0, 1, 'C');
        $pdf->Ln(8);

        $pdf->SetFont('Arial', 'B', 16);
        $pdf->Cell(0, 10, 'Booking Confirmation', 0, 1, 'C');
        $pdf->Ln(4);

        // Passenger info
        $pdf->SetFont('Arial', 'B', 12);
        $pdf->Cell(50, 8, 'Passenger Name:', 0);
        $pdf->SetFont('Arial', '', 12);
        $pdf->Cell(0, 8, $data['user_name'], 0, 1);

        $pdf->SetFont('Arial', 'B', 12);
        $pdf->Cell(50, 8, 'Email:', 0);
        $pdf->SetFont('Arial', '', 12);
        $pdf->Cell(0, 8, $data['user_email'], 0, 1);
        $pdf->Ln(4);

        // Booking info
        $pdf->SetFont('Arial', 'B', 12);
        $pdf->Cell(50, 8, 'Booking ID:', 0);
        $pdf->SetFont('Arial', '', 12);
        $pdf->Cell(0, 8, $data['booking_id'], 0, 1);

        $pdf->SetFont('Arial', 'B', 12);
        $pdf->Cell(50, 8, 'Origin:', 0);
        $pdf->SetFont('Arial', '', 12);
        $pdf->Cell(0, 8, $data['origin'], 0, 1);

        $pdf->SetFont('Arial', 'B', 12);
        $pdf->Cell(50, 8, 'Destination:', 0);
        $pdf->SetFont('Arial', '', 12);
        $pdf->Cell(0, 8, $data['destination'], 0, 1);

        $pdf->SetFont('Arial', 'B', 12);
        $pdf->Cell(50, 8, 'Departure:', 0);
        $pdf->SetFont('Arial', '', 12);
        $pdf->Cell(0, 8, date("d M Y, h:i A", strtotime($data['departure_time'])), 0, 1);

        $pdf->SetFont('Arial', 'B', 12);
        $pdf->Cell(50, 8, 'Arrival:', 0);
        $pdf->SetFont('Arial', '', 12);
        $pdf->Cell(0, 8, date("d M Y, h:i A", strtotime($data['arrival_time'])), 0, 1);

        $pdf->SetFont('Arial', 'B', 12);
        $pdf->Cell(50, 8, 'Vehicle:', 0);
        $pdf->SetFont('Arial', '', 12);
        $pdf->Cell(0, 8, $data['vehicle_number'] . ' (' . $data['type_name'] . ')', 0, 1);

        $pdf->SetFont('Arial', 'B', 12);
        $pdf->Cell(50, 8, 'Status:', 0);
        $pdf->SetFont('Arial', '', 12);
        $pdf->Cell(0, 8, ucfirst($data['status']), 0, 1);

        // Price
        $pdf->Ln(5);
        $pdf->SetFont('Arial', 'B', 14);
        $pdf->Cell(50, 10, 'Total Fare:', 0);
        $pdf->SetFont('Arial', 'B', 14);
        $pdf->SetTextColor(0, 102, 204);
        $pdf->Cell(0, 10, 'Rs ' . number_format($data['price'], 2), 0, 1);
        $pdf->SetTextColor(0, 0, 0);

        // Footer
        $pdf->Ln(12);
        $pdf->SetFont('Arial', 'I', 11);
        $pdf->Cell(0, 10, 'Thank you for booking with Bikash Transport!', 0, 1, 'C');

        // Save PDF temporarily
        if(!is_dir('tickets')) mkdir('tickets', 0777, true); // create folder if not exists
        $pdf_file = "tickets/booking_" . $booking_id . ".pdf";
        $pdf->Output('F', $pdf_file);

        // ----------------- 6. Send Email to Admin -----------------
        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'bikashtransportt@gmail.com'; // your Gmail
            $mail->Password = 'YOUR_APP_PASSWORD_HERE';    // Gmail App Password
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;

            $mail->setFrom('bikashtransportt@gmail.com', 'Bikash Transport System');
            $mail->addAddress('bikashtransportt@gmail.com'); // Admin
            $mail->addAttachment($pdf_file);

            $mail->isHTML(true);
            $mail->Subject = "New Ticket Booking - {$booking_id}";
            $mail->Body = "
                <h2>New Ticket Booked</h2>
                <p><strong>Booking ID:</strong> {$booking_id}</p>
                <p><strong>Passenger:</strong> {$data['user_name']} ({$data['user_email']})</p>
                <p><strong>Route:</strong> {$origin} → {$destination}</p>
                <p><strong>Departure:</strong> {$departure_time}</p>
                <p><strong>Arrival:</strong> {$arrival_time}</p>
                <p><strong>Price:</strong> Rs {$price}</p>
            ";
            $mail->send();
        } catch (Exception $e) {
            error_log("Mail Error: {$mail->ErrorInfo}");
        }

        // ----------------- 7. Show PDF in Browser -----------------
        $pdf->Output('I', 'booking_' . $booking_id . '.pdf');
        exit;

    } else {
        die("Error saving booking: " . $conn->error);
    }
} else {
    die("Invalid request.");
}
?>
