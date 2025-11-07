<?php
session_start();
require_once '../db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

$user_id = $_SESSION['user_id'];

// Handle form submission
$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $contact = trim($_POST['contact']);
    $vehicle_id = intval($_POST['vehicle_id']);

    // Validation
    if (empty($name)) {
        $errors[] = "Name is required.";
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email address.";
    }

    if (!preg_match('/^(98|97)\d{8}$/', $contact)) {
        $errors[] = "Contact number must start with 98 or 97 and contain 10 digits.";
    }
    if (empty($_GET['vehicle_id']) || intval($_GET['vehicle_id']) <= 0) {
    die("Invalid vehicle selected.");
    }
        

    if (empty($errors)) {
        // Get vehicle name from database
        $vehicle_stmt = $conn->prepare("SELECT vehicle_name FROM vehicles WHERE id = ?");
        if ($vehicle_stmt === false) {
            $errors[] = "Database error: " . $conn->error;
        } else {
            $vehicle_stmt->bind_param("i", $vehicle_id);
            $vehicle_stmt->execute();
            $vehicle_result = $vehicle_stmt->get_result();
            $vehicle_data = $vehicle_result->fetch_assoc();
            $vehicle_type = $vehicle_data['vehicle_name'] ?? 'Vehicle';

            // Save to database
            $stmt = $conn->prepare("INSERT INTO passengers (user_id, vehicle_id, name, email, contact) VALUES (?, ?, ?, ?, ?)");
            if ($stmt === false) {
                $errors[] = "Database error: " . $conn->error;
            } else {
                $stmt->bind_param("iisss", $user_id, $vehicle_id, $name, $email, $contact);
                
                if ($stmt->execute()) {
                    $success = true;

            // Send email notification
            $to = "bikashtransportt@gmail.com";
            $subject = "New Booking: " . ucfirst($vehicle_type) . " Ticket";
            
            $message = "<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; background-color: #f9f9f9; }
        .header { background-color: #007bff; color: white; padding: 20px; text-align: center; border-radius: 8px 8px 0 0; }
        .content { background-color: white; padding: 30px; border-radius: 0 0 8px 8px; }
        .booking-info { background-color: #f0f2f5; padding: 15px; border-radius: 8px; margin: 20px 0; }
        .info-row { display: flex; padding: 10px 0; border-bottom: 1px solid #e0e0e0; }
        .info-label { font-weight: bold; width: 150px; color: #555; }
        .info-value { color: #333; }
        .footer { text-align: center; margin-top: 20px; font-size: 12px; color: #777; }
    </style>
</head>
<body>
    <div class='container'>
        <div class='header'>
            <h2>🎫 New Booking Received</h2>
        </div>
        <div class='content'>
            <p>A new <strong>" . ucfirst($vehicle_type) . "</strong> ticket has been booked.</p>
            
            <div class='booking-info'>
                <h3 style='margin-top: 0; color: #007bff;'>Passenger Details</h3>
                <div class='info-row'>
                    <div class='info-label'>Name:</div>
                    <div class='info-value'>" . htmlspecialchars($name) . "</div>
                </div>
                <div class='info-row'>
                    <div class='info-label'>Email:</div>
                    <div class='info-value'>" . htmlspecialchars($email) . "</div>
                </div>
                <div class='info-row'>
                    <div class='info-label'>Contact:</div>
                    <div class='info-value'>" . htmlspecialchars($contact) . "</div>
                </div>
                <div class='info-row' style='border-bottom: none;'>
                    <div class='info-label'>Vehicle Type:</div>
                    <div class='info-value'>" . ucfirst(htmlspecialchars($vehicle_type)) . "</div>
                </div>
            </div>
            
            <p style='margin-top: 20px; color: #666;'>
                <strong>Booking Date:</strong> " . date('F j, Y - g:i A') . "
            </p>
        </div>
        <div class='footer'>
            <p>This is an automated message from your booking system.</p>
        </div>
    </div>
</body>
</html>";

            $headers = "MIME-Version: 1.0" . "\r\n";
            $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
            $headers .= "From: Booking System <noreply@yourdomain.com>" . "\r\n";

            mail($to, $subject, $message, $headers);
        } else {
            $errors[] = "Failed to save booking. Please try again.";
        }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book Your Ticket - Bikash Transport</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 20px;
        }

        .form-wrapper {
            width: 100%;
            max-width: 500px;
        }

        .form-container {
            background: #fff;
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            position: relative;
            overflow: hidden;
        }

        .form-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 5px;
            background: linear-gradient(90deg, #667eea 0%, #764ba2 100%);
        }

        .form-header {
            text-align: center;
            margin-bottom: 35px;
        }

        .form-icon {
            width: 70px;
            height: 70px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            font-size: 35px;
        }

        h2 {
            color: #2d3748;
            font-weight: 700;
            font-size: 28px;
            margin-bottom: 8px;
        }

        .subtitle {
            color: #718096;
            font-size: 14px;
        }

        .form-group {
            margin-bottom: 25px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #4a5568;
            font-size: 14px;
        }

        input {
            width: 100%;
            padding: 14px 16px;
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            font-size: 15px;
            transition: all 0.3s;
            background: #f7fafc;
        }

        input:focus {
            border-color: #667eea;
            background: #fff;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
            outline: none;
        }

        input::placeholder {
            color: #a0aec0;
        }

        button {
            margin-top: 10px;
            padding: 16px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            width: 100%;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            text-transform: uppercase;
            letter-spacing: 1px;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
        }

        button:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.5);
        }

        button:active {
            transform: translateY(0);
        }

        .error {
            background: linear-gradient(135deg, #fc8181 0%, #f56565 100%);
            color: white;
            padding: 15px 18px;
            border-radius: 10px;
            margin-bottom: 20px;
            font-size: 14px;
            box-shadow: 0 4px 15px rgba(245, 101, 101, 0.3);
        }

        .success {
            background: linear-gradient(135deg, #68d391 0%, #48bb78 100%);
            color: white;
            padding: 15px 18px;
            border-radius: 10px;
            margin-bottom: 20px;
            font-size: 14px;
            box-shadow: 0 4px 15px rgba(72, 187, 120, 0.3);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .success::before {
            content: '✓';
            font-size: 20px;
            font-weight: bold;
        }

        .input-icon {
            position: relative;
        }

        @media (max-width: 600px) {
            .form-container {
                padding: 30px 25px;
            }

            h2 {
                font-size: 24px;
            }

            .form-icon {
                width: 60px;
                height: 60px;
                font-size: 30px;
            }
        }
    </style>
    <script>
        function validateForm() {
            const name = document.getElementById('name').value.trim();
            const email = document.getElementById('email').value.trim();
            const contact = document.getElementById('contact').value.trim();
            let errors = [];

            if (name === '') {
                errors.push("❌ Name is required.");
            }

            const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailPattern.test(email)) {
                errors.push("❌ Invalid email address.");
            }

            const contactPattern = /^(98|97)\d{8}$/;
            if (!contactPattern.test(contact)) {
                errors.push("❌ Contact number must start with 98 or 97 and contain 10 digits.");
            }

            if (errors.length > 0) {
                alert(errors.join("\n"));
                return false;
            }
            return true;
        }
    </script>
</head>

<body>

    <div class="form-wrapper">
        <div class="form-container">
            <div class="form-header">
                <div class="form-icon">🎫</div>
                <h2>Book Your Ticket</h2>
                <p class="subtitle">Please fill in your details below</p>
            </div>

            <?php if ($success): ?>
            <div class="success">Booking confirmed! Check your email for details.</div>
            <?php endif; ?>

            <?php if (!empty($errors)): ?>
            <div class="error"><?= implode('<br>', $errors) ?></div>
            <?php endif; ?>

            <form method="POST" onsubmit="return validateForm();">
                <input type="hidden" name="vehicle_id"
                    value="<?= isset($_GET['vehicle_id']) ? intval($_GET['vehicle_id']) : '' ?>">

                <div class="form-group">
                    <label for="name">Full Name *</label>
                    <input type="text" name="name" id="name" placeholder="Enter your full name" required>
                </div>

                <div class="form-group">
                    <label for="email">Email Address *</label>
                    <input type="email" name="email" id="email" placeholder="example@mail.com" required>
                </div>

                <div class="form-group">
                    <label for="contact">Contact Number *</label>
                    <input type="text" name="contact" id="contact" placeholder="98XXXXXXXX" required>
                </div>

                <button type="submit">Confirm Booking</button>
            </form>
        </div>
    </div>

</body>

</html>