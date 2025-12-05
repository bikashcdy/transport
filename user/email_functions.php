<?php
// email_functions.php - PHPMailer Version for Local Development

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// Load PHPMailer files
require __DIR__ . '/PHPMailer/src/Exception.php';
require __DIR__ . '/PHPMailer/src/PHPMailer.php';
require __DIR__ . '/PHPMailer/src/SMTP.php';

// ============================================
// GMAIL SMTP CONFIGURATION - UPDATE THESE!
// ============================================
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_USERNAME', 'bikashtransportt@gmail.com');
define('SMTP_PASSWORD', 'nodc knlq hxgf inul');  // <-- PUT YOUR 16-CHAR APP PASSWORD HERE
define('SMTP_PORT', 587);
define('ADMIN_EMAIL', 'bikashtransportt@gmail.com');

// ============================================
// MAIN FUNCTION - Sends both emails
// ============================================
function sendBookingConfirmationEmail($bookingData) {
    $userEmailSent = sendUserEmail($bookingData);
    $adminEmailSent = sendAdminEmail($bookingData);
    return $userEmailSent && $adminEmailSent;
}

// ============================================
// CREATE MAILER - Reusable PHPMailer setup
// ============================================
function createMailer() {
    $mail = new PHPMailer(true);
    $mail->isSMTP();
    $mail->Host = SMTP_HOST;
    $mail->SMTPAuth = true;
    $mail->Username = SMTP_USERNAME;
    $mail->Password = SMTP_PASSWORD;
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = SMTP_PORT;
    $mail->isHTML(true);
    $mail->CharSet = 'UTF-8';
    $mail->setFrom(SMTP_USERNAME, 'BookingNepal');
    return $mail;
}

// ============================================
// EMAIL TO USER - Booking Confirmation
// ============================================
function sendUserEmail($booking) {
    try {
        $mail = createMailer();
        $mail->addAddress($booking['email'], $booking['user_name']);
        $mail->Subject = "Booking Confirmation - " . $booking['booking_id'] . " | BookingNepal";
        $mail->Body = generateUserEmailHTML($booking);
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("User email failed: " . $e->getMessage());
        return false;
    }
}

// ============================================
// EMAIL TO ADMIN - New Booking Alert
// ============================================
function sendAdminEmail($booking) {
    try {
        $mail = createMailer();
        $mail->addAddress(ADMIN_EMAIL);
        $mail->addReplyTo($booking['email'], $booking['user_name']);
        $mail->Subject = "New Booking - " . $booking['user_name'] . " | " . $booking['booking_id'];
        $mail->Body = generateAdminEmailHTML($booking);
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Admin email failed: " . $e->getMessage());
        return false;
    }
}

// ============================================
// USER EMAIL HTML TEMPLATE
// ============================================
function generateUserEmailHTML($booking) {
    $html = '
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking Confirmation</title>
    <style>
        /* === RESET STYLES === */
        body {
            margin: 0;
            padding: 0;
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            line-height: 1.6;
        }

        /* === MAIN CONTAINER === */
        .container {
            max-width: 600px;
            margin: 20px auto;
            background-color: #ffffff;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        /* === HEADER SECTION === */
        .header {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: #ffffff;
            padding: 40px 20px;
            text-align: center;
        }

        .header h1 {
            margin: 0;
            font-size: 28px;
            font-weight: bold;
        }

        .badge {
            background-color: #48bb78;
            color: #ffffff;
            display: inline-block;
            padding: 10px 20px;
            border-radius: 25px;
            margin-top: 15px;
            font-weight: 600;
        }

        /* === CONTENT SECTION === */
        .content {
            padding: 30px 20px;
        }

        .greeting {
            font-size: 16px;
            color: #2d3748;
            margin-bottom: 10px;
        }

        .intro-text {
            font-size: 14px;
            color: #64748b;
            margin-bottom: 25px;
        }

        /* === BOOKING ID BOX === */
        .booking-box {
            background: linear-gradient(135deg, #f7fafc, #edf2f7);
            border-left: 4px solid #667eea;
            padding: 20px;
            margin-bottom: 30px;
            border-radius: 5px;
        }

        .booking-label {
            font-size: 12px;
            color: #64748b;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 8px;
        }

        .booking-id {
            font-size: 24px;
            font-weight: 700;
            color: #667eea;
            font-family: "Courier New", monospace;
        }

        /* === SECTION TITLES === */
        .section-title {
            font-size: 18px;
            color: #2d3748;
            font-weight: 700;
            margin: 25px 0 15px 0;
            padding-bottom: 10px;
            border-bottom: 2px solid #e2e8f0;
        }

        /* === DETAIL ROWS === */
        .row {
            margin-bottom: 12px;
            padding: 12px;
            background-color: #f8f9fa;
            border-radius: 5px;
        }

        .label {
            font-weight: 600;
            color: #64748b;
            font-size: 14px;
        }

        .value {
            color: #2d3748;
            font-size: 14px;
            font-weight: 500;
        }

        /* === PRICE BOX === */
        .price-box {
            background: linear-gradient(135deg, #48bb78, #38a169);
            color: #ffffff;
            padding: 20px;
            text-align: center;
            border-radius: 8px;
            margin: 25px 0;
        }

        .price-label {
            font-size: 14px;
            opacity: 0.95;
            margin-bottom: 8px;
        }

        .price-value {
            font-size: 32px;
            font-weight: 700;
        }

        /* === NOTICE BOX === */
        .notice {
            background-color: #fff3cd;
            border: 2px solid #ffc107;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
        }

        .notice h3 {
            color: #856404;
            margin: 0 0 10px 0;
            font-size: 16px;
        }

        .notice ul {
            margin: 10px 0;
            padding-left: 20px;
            color: #856404;
        }

        .notice li {
            margin-bottom: 8px;
        }

        /* === CONTACT SECTION === */
        .contact-text {
            font-size: 14px;
            color: #64748b;
            margin-top: 25px;
        }

        .contact-link {
            color: #667eea;
            text-decoration: none;
        }

        /* === FOOTER === */
        .footer {
            background-color: #2d3748;
            color: #cbd5e0;
            padding: 20px;
            text-align: center;
            font-size: 12px;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- HEADER -->
        <div class="header">
            <h1>Booking Confirmed!</h1>
           <div class="badge">✓ Confirmed & Ready</div>
        </div>

        <!-- CONTENT -->
        <div class="content">
            <p class="greeting">Dear <strong>' . htmlspecialchars($booking['user_name']) . '</strong>,</p>
            <p class="intro-text">Thank you for choosing BookingNepal! You have successfully booked a vehicle.</p>

            <!-- BOOKING ID -->
            <div class="booking-box">
                <div class="booking-label">Your Booking ID</div>
                <div class="booking-id">' . htmlspecialchars($booking['booking_id']) . '</div>
            </div>

            <!-- VEHICLE DETAILS -->
            <h3 class="section-title">Vehicle Details</h3>
            <div class="row">
                <span class="label">Vehicle Name:</span>
                <span class="value">' . htmlspecialchars($booking['vehicle_name']) . '</span>
            </div>
            <div class="row">
                <span class="label">Vehicle Number:</span>
                <span class="value">' . htmlspecialchars($booking['vehicle_number']) . '</span>
            </div>
            <div class="row">
                <span class="label">Vehicle Type:</span>
                <span class="value">' . ucfirst(htmlspecialchars($booking['vehicle_type'])) . '</span>
            </div>

            <!-- TRIP SCHEDULE -->
            <h3 class="section-title">Your Trip Schedule</h3>
            <div class="row">
                <span class="label">Trip Starts:</span>
                <span class="value">' . date('F d, Y (l)', strtotime($booking['trip_start'])) . '</span>
            </div>
            <div class="row">
                <span class="label">Trip Ends:</span>
                <span class="value">' . date('F d, Y (l)', strtotime($booking['trip_end'])) . '</span>
            </div>
            <div class="row">
                <span class="label">Booking Time:</span>
                <span class="value">' . date('F d, Y - h:i A') . '</span>
            </div>

            <!-- PRICE -->
            <div class="price-box">
                <div class="price-label">Total Amount</div>
                <div class="price-value">Rs. ' . number_format($booking['price'], 2) . '</div>
            </div>

            <!-- IMPORTANT NOTICE -->
            <div class="notice">
                <h3>Important Information</h3>
                <ul>
                    <li>Your booking has been <strong>CONFIRMED</strong> ✓</li>
                    <li>Please save your Booking ID: <strong>' . htmlspecialchars($booking['booking_id']) . '</strong></li>
                    <li>Arrive 15 minutes before your scheduled trip start time.</li>
                    <li>We will contact you at <strong>' . htmlspecialchars($booking['contact_number']) . '</strong> if needed.</li>
                </ul>
            </div>

            <!-- CONTACT -->
            <p class="contact-text">Questions? Contact us:</p>
            <p>
                <a href="mailto:bikashtransportt@gmail.com" class="contact-link">bikashtransportt@gmail.com</a>
            </p>
        </div>

        <!-- FOOTER -->
        <div class="footer">
            <p>&copy; ' . date('Y') . ' BookingNepal. All rights reserved.</p>
        </div>
    </div>
</body>
</html>';

    return $html;
}

// ============================================
// ADMIN EMAIL HTML TEMPLATE
// ============================================
function generateAdminEmailHTML($booking) {
    $html = '
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New Booking Alert</title>
    <style>
        /* === RESET STYLES === */
        body {
            margin: 0;
            padding: 0;
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            line-height: 1.6;
        }

        /* === MAIN CONTAINER === */
        .container {
            max-width: 600px;
            margin: 20px auto;
            background-color: #ffffff;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        /* === HEADER (RED FOR ADMIN ALERT) === */
        .header {
            background: linear-gradient(135deg, #e53e3e, #c53030);
            color: #ffffff;
            padding: 30px 20px;
            text-align: center;
        }

        .header h1 {
            margin: 0;
            font-size: 24px;
            font-weight: bold;
        }

        .header p {
            margin: 10px 0 0 0;
            opacity: 0.9;
        }

        /* === CONTENT === */
        .content {
            padding: 30px 20px;
        }

        /* === ALERT BOX === */
        .alert-box {
            background-color: #fed7d7;
            border-left: 4px solid #e53e3e;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
        }

        .alert-box strong {
            color: #c53030;
        }

        .alert-box small {
            color: #718096;
        }

        /* === INFO SECTIONS === */
        .section {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .section h3 {
            margin: 0 0 15px 0;
            color: #2d3748;
            font-size: 16px;
            padding-bottom: 10px;
            border-bottom: 2px solid #e2e8f0;
        }

        /* === DETAIL ROWS === */
        .row {
            margin-bottom: 10px;
        }

        .label {
            font-weight: 600;
            color: #64748b;
            display: inline-block;
            width: 140px;
        }

        .value {
            color: #2d3748;
            font-weight: 500;
        }

        /* === SPECIAL HIGHLIGHTS === */
        .highlight {
            background-color: #667eea;
            color: #ffffff;
            padding: 3px 8px;
            border-radius: 4px;
            font-family: "Courier New", monospace;
        }

        .status-pending {
            background-color: #ffc107;
            color: #000000;
            padding: 3px 8px;
            border-radius: 4px;
            font-weight: 600;
        }

        .price {
            font-size: 24px;
            font-weight: 700;
            color: #48bb78;
        }

        /* === FOOTER === */
        .footer {
            background-color: #2d3748;
            color: #cbd5e0;
            padding: 15px;
            text-align: center;
            font-size: 12px;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- HEADER -->
        <div class="header">
            <h1>NEW VEHICLE BOOKING</h1>
            <p>New Booking - Already Confirmed</p>
```

---

## 📧 Email Sending Status:

Your email function **should be working** now. Let me verify the configuration:

✅ PHPMailer is loaded correctly  
✅ SMTP settings are configured (Gmail)  
✅ Your email: `bikashtransportt@gmail.com`  
✅ App password is set: `nodc knlq hxgf inul`  
✅ Function sends emails to both user and admin  

## 🧪 Testing:

To test if emails are working:

1. Make a test booking
2. Check your server error logs for these messages:
```
   "User email failed: [error message]"
   "Admin email failed: [error message]"
        </div>

        <!-- CONTENT -->
        <div class="content">
            <!-- ALERT BOX -->
            <div class="alert-box">
                <strong>' . htmlspecialchars($booking['user_name']) . '</strong> has booked a vehicle!<br>
                <small>Received on ' . date('F d, Y \a\t h:i A') . '</small>
            </div>

            <!-- BOOKING INFO -->
            <div class="section">
                <h3>Booking Information</h3>
                <div class="row">
                    <span class="label">Booking ID:</span>
                    <span class="highlight">' . htmlspecialchars($booking['booking_id']) . '</span>
                </div>
                <div class="row">
                    <span class="label">Status:</span>
                   <span class="status-confirmed" style="background-color: #48bb78; color: #ffffff; padding: 3px 8px; border-radius: 4px; font-weight: 600;">CONFIRMED</span>
                </div>
                <div class="row">
                    <span class="label">Total Price:</span>
                    <span class="price">Rs. ' . number_format($booking['price'], 2) . '</span>
                </div>
            </div>

            <!-- CUSTOMER INFO -->
            <div class="section">
                <h3>Customer Details</h3>
                <div class="row">
                    <span class="label">Full Name:</span>
                    <span class="value">' . htmlspecialchars($booking['user_name']) . '</span>
                </div>
                <div class="row">
                    <span class="label">Email:</span>
                    <span class="value">
                        <a href="mailto:' . htmlspecialchars($booking['user_email']) . '">' . htmlspecialchars($booking['user_email']) . '</a>
                    </span>
                </div>
                <div class="row">
                    <span class="label">Contact:</span>
                    <span class="value">' . htmlspecialchars($booking['contact_number']) . '</span>
                </div>';

    // Add alternative number if provided
    if (!empty($booking['alternative_number'])) {
        $html .= '
                <div class="row">
                    <span class="label">Alt. Contact:</span>
                    <span class="value">' . htmlspecialchars($booking['alternative_number']) . '</span>
                </div>';
    }

    $html .= '
            </div>

            <!-- VEHICLE INFO -->
            <div class="section">
                <h3>Vehicle Details</h3>
                <div class="row">
                    <span class="label">Vehicle Name:</span>
                    <span class="value">' . htmlspecialchars($booking['vehicle_name']) . '</span>
                </div>
                <div class="row">
                    <span class="label">Vehicle Number:</span>
                    <span class="value"><strong>' . htmlspecialchars($booking['vehicle_number']) . '</strong></span>
                </div>
                <div class="row">
                    <span class="label">Vehicle Type:</span>
                    <span class="value">' . ucfirst(htmlspecialchars($booking['vehicle_type'])) . '</span>
                </div>
            </div>

            <!-- TRIP SCHEDULE -->
            <div class="section">
                <h3>Trip Schedule</h3>
                <div class="row">
                    <span class="label">Trip Start:</span>
                    <span class="value"><strong>' . date('F d, Y (l)', strtotime($booking['trip_start'])) . '</strong></span>
                </div>
                <div class="row">
                    <span class="label">Trip End:</span>
                    <span class="value"><strong>' . date('F d, Y (l)', strtotime($booking['trip_end'])) . '</strong></span>
                </div>
            </div>';

    // Add notes if provided
    if (!empty($booking['notes'])) {
        $html .= '
            <!-- CUSTOMER NOTES -->
            <div class="section">
                <h3>Customer Notes</h3>
                <p style="margin: 0; color: #2d3748;">' . nl2br(htmlspecialchars($booking['notes'])) . '</p>
            </div>';
    }

    $html .= '
        </div>

        <!-- FOOTER -->
        <div class="footer">
            <p>BookingNepal Admin Notification System</p>
        </div>
    </div>
</body>
</html>';

    return $html;
}
?>