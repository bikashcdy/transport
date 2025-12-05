<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'user') {
    header("Location: ../index.php");
    exit;
}

// Check if booking success data exists
if (!isset($_SESSION['booking_success'])) {
    header("Location: user_dashboard.php");
    exit;
}

$booking = $_SESSION['booking_success'];
unset($_SESSION['booking_success']); // Clear after displaying

$username = $_SESSION['username'] ?? 'User';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking Success | BookingNepal</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css">
    <link rel="shortcut icon" href="favi.png" type="image/x-icon">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .success-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            max-width: 700px;
            width: 100%;
            overflow: hidden;
            animation: slideUp 0.5s ease-out;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .success-header {
            background: linear-gradient(135deg, #48bb78 0%, #38a169 100%);
            color: white;
            padding: 40px;
            text-align: center;
            position: relative;
        }

        .success-icon {
            width: 100px;
            height: 100px;
            background: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            animation: checkmark 0.8s ease-in-out;
        }

        @keyframes checkmark {
            0% {
                transform: scale(0);
                opacity: 0;
            }
            50% {
                transform: scale(1.2);
            }
            100% {
                transform: scale(1);
                opacity: 1;
            }
        }

        .success-icon i {
            font-size: 3.5rem;
            color: #48bb78;
        }

        .success-title {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 10px;
        }

        .success-subtitle {
            font-size: 1.1rem;
            opacity: 0.95;
        }

        .booking-details {
            padding: 40px;
        }

        .booking-id-section {
            background: linear-gradient(135deg, #f7fafc 0%, #edf2f7 100%);
            padding: 25px;
            border-radius: 12px;
            margin-bottom: 30px;
            text-align: center;
        }

        .booking-id-label {
            font-size: 0.9rem;
            color: #64748b;
            font-weight: 600;
            margin-bottom: 10px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .booking-id-value {
            font-size: 2rem;
            font-weight: 700;
            color: #667eea;
            font-family: 'Courier New', monospace;
        }

        .details-grid {
            display: grid;
            gap: 20px;
            margin-bottom: 30px;
        }

        .detail-item {
            display: flex;
            align-items: flex-start;
            gap: 15px;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 12px;
            border-left: 4px solid #667eea;
            transition: all 0.3s ease;
        }

        .detail-item:hover {
            background: #e9ecef;
            transform: translateX(5px);
        }

        .detail-icon {
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.3rem;
            flex-shrink: 0;
        }

        .detail-content {
            flex: 1;
        }

        .detail-label {
            font-size: 0.85rem;
            color: #64748b;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 5px;
        }

        .detail-value {
            font-size: 1.1rem;
            color: #2d3748;
            font-weight: 600;
            word-break: break-word;
        }

        .price-highlight {
            background: linear-gradient(135deg, #48bb78 0%, #38a169 100%);
            color: white;
            padding: 25px;
            border-radius: 12px;
            text-align: center;
            margin-bottom: 30px;
        }

        .price-label {
            font-size: 1rem;
            margin-bottom: 10px;
            opacity: 0.95;
        }

        .price-value {
            font-size: 2.5rem;
            font-weight: 700;
        }

        .notes-section {
            background: #e3f2fd;
            border: 2px solid #2196f3;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 30px;
        }

        .notes-section h4 {
            color: #1565c0;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 1.1rem;
        }

        .notes-section p {
            color: #1565c0;
            line-height: 1.6;
        }

        .important-note {
            background: #fff3cd;
            border: 2px solid #ffc107;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 30px;
        }

        .important-note h4 {
            color: #856404;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 1.1rem;
        }

        .important-note ul {
            margin-left: 20px;
            color: #856404;
        }

        .important-note li {
            margin-bottom: 8px;
            line-height: 1.6;
        }

        .action-buttons {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
        }

        .btn {
            flex: 1;
            padding: 18px 30px;
            border: none;
            border-radius: 12px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            text-decoration: none;
            min-width: 200px;
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.5);
        }

        .btn-secondary {
            background: white;
            color: #667eea;
            border: 2px solid #667eea;
        }

        .btn-secondary:hover {
            background: #f7fafc;
            transform: translateY(-2px);
        }

        .confetti {
            position: fixed;
            width: 10px;
            height: 10px;
            background: #f0f;
            position: absolute;
            animation: confetti-fall 3s linear infinite;
        }

        @keyframes confetti-fall {
            to {
                transform: translateY(100vh) rotate(360deg);
                opacity: 0;
            }
        }

        @media (max-width: 768px) {
            .success-header {
                padding: 30px 20px;
            }

            .success-title {
                font-size: 1.5rem;
            }

            .booking-details {
                padding: 30px 20px;
            }

            .action-buttons {
                flex-direction: column;
            }

            .btn {
                min-width: 100%;
            }

            .booking-id-value {
                font-size: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="success-container">
        <div class="success-header">
            <div class="success-icon">
                <i class="fas fa-check"></i>
            </div>
            <h1 class="success-title">Booking Confirmed!</h1>
            <p class="success-subtitle">Your vehicle has been successfully booked</p>
        </div>

        <div class="booking-details">
            <div class="booking-id-section">
                <div class="booking-id-label">Your Booking ID</div>
                <div class="booking-id-value"><?= htmlspecialchars($booking['booking_id']) ?></div>
            </div>

            <div class="details-grid">
                <div class="detail-item">
                    <div class="detail-icon">
                        <i class="fas fa-user"></i>
                    </div>
                    <div class="detail-content">
                        <div class="detail-label">Passenger Name</div>
                        <div class="detail-value"><?= htmlspecialchars($booking['user_name']) ?></div>
                    </div>
                </div>

                <div class="detail-item">
                    <div class="detail-icon">
                        <i class="fas fa-phone"></i>
                    </div>
                    <div class="detail-content">
                        <div class="detail-label">Contact Number</div>
                        <div class="detail-value"><?= htmlspecialchars($booking['contact_number']) ?></div>
                    </div>
                </div>

                <?php if (!empty($booking['alternative_number'])): ?>
                <div class="detail-item">
                    <div class="detail-icon">
                        <i class="fas fa-phone-alt"></i>
                    </div>
                    <div class="detail-content">
                        <div class="detail-label">Alternative Contact</div>
                        <div class="detail-value"><?= htmlspecialchars($booking['alternative_number']) ?></div>
                    </div>
                </div>
                <?php endif; ?>

                <div class="detail-item">
                    <div class="detail-icon">
                        <i class="fas fa-envelope"></i>
                    </div>
                    <div class="detail-content">
                        <div class="detail-label">Email Address</div>
                        <div class="detail-value"><?= htmlspecialchars($booking['user_email']) ?></div>
                    </div>
                </div>

                <div class="detail-item">
                    <div class="detail-icon">
                        <i class="fas fa-bus"></i>
                    </div>
                    <div class="detail-content">
                        <div class="detail-label">Vehicle</div>
                        <div class="detail-value">
                            <?= htmlspecialchars($booking['vehicle_name']) ?> 
                            (<?= htmlspecialchars($booking['vehicle_number']) ?>)
                        </div>
                    </div>
                </div>

                <div class="detail-item">
                    <div class="detail-icon">
                        <i class="fas fa-tag"></i>
                    </div>
                    <div class="detail-content">
                        <div class="detail-label">Vehicle Type</div>
                        <div class="detail-value"><?= ucfirst(htmlspecialchars($booking['vehicle_type'])) ?></div>
                    </div>
                </div>

                <div class="detail-item">
                    <div class="detail-icon">
                        <i class="fas fa-calendar-check"></i>
                    </div>
                    <div class="detail-content">
                        <div class="detail-label">Trip Starts</div>
                        <div class="detail-value"><?= date('F d, Y', strtotime($booking['trip_start'])) ?></div>
                    </div>
                </div>

                <div class="detail-item">
                    <div class="detail-icon">
                        <i class="fas fa-calendar-times"></i>
                    </div>
                    <div class="detail-content">
                        <div class="detail-label">Trip Ends</div>
                        <div class="detail-value"><?= date('F d, Y', strtotime($booking['trip_end'])) ?></div>
                    </div>
                </div>
            </div>

            <?php if (!empty($booking['notes'])): ?>
            <div class="notes-section">
                <h4>
                    <i class="fas fa-sticky-note"></i>
                    Additional Notes
                </h4>
                <p><?= nl2br(htmlspecialchars($booking['notes'])) ?></p>
            </div>
            <?php endif; ?>

           <div class="price-highlight">
    <div class="price-label">
        <?php if (isset($booking['daily_rate']) && isset($booking['days'])): ?>
            Daily Rate: Rs. <?= number_format($booking['daily_rate'], 2) ?> × <?= $booking['days'] ?> day<?= $booking['days'] > 1 ? 's' : '' ?>
        <?php else: ?>
            Total Amount
        <?php endif; ?>
    </div>
    <div class="price-value">Rs. <?= number_format($booking['price'], 2) ?></div>
</div>
           <div class="important-note">
    <h4>
        <i class="fas fa-exclamation-triangle"></i>
        Important Information
    </h4>
    <ul>
        <li>Please save your <strong>Booking ID: <?= htmlspecialchars($booking['booking_id']) ?></strong> for future reference</li>
        <li>Your booking has been <strong>Confirmed</strong> ✓</li>
        <li>A confirmation email has been sent to <strong><?= htmlspecialchars($booking['user_email']) ?></strong></li>
        <li>We will contact you on <strong><?= htmlspecialchars($booking['contact_number']) ?></strong> if needed</li>
        <li>Please arrive 15 minutes before your trip starts</li>
        <li>Keep your Booking ID handy for the trip</li>
    </ul>
</div>

            <div class="action-buttons">
                <a href="user_dashboard.php" class="btn btn-primary">
                    <i class="fas fa-home"></i>
                    Back to Dashboard
                </a>
                <a href="cancel_booking.php" class="btn btn-secondary">
                    <i class="fas fa-list"></i>
                    View My Bookings
                </a>
            </div>
        </div>
    </div>

    <script>
        // Create confetti effect
        function createConfetti() {
            const colors = ['#667eea', '#764ba2', '#48bb78', '#38a169', '#f6ad55', '#ed8936'];
            for (let i = 0; i < 50; i++) {
                setTimeout(() => {
                    const confetti = document.createElement('div');
                    confetti.className = 'confetti';
                    confetti.style.left = Math.random() * 100 + '%';
                    confetti.style.background = colors[Math.floor(Math.random() * colors.length)];
                    confetti.style.animationDelay = Math.random() * 3 + 's';
                    confetti.style.animationDuration = Math.random() * 3 + 2 + 's';
                    document.body.appendChild(confetti);

                    setTimeout(() => confetti.remove(), 5000);
                }, i * 30);
            }
        }

        // Trigger confetti on page load
        window.addEventListener('load', createConfetti);
    </script>
</body>
</html>