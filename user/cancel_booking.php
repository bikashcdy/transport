<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'user') {
    header("Location: ../index.php");
    exit;
}

require_once '../db.php';

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'] ?? 'User';

// Handle booking cancellation
if (isset($_POST['cancel_booking'])) {
    $booking_id = $_POST['booking_id'];
    
    // Verify booking belongs to user and is not already cancelled
    $verifyQuery = "SELECT status, created_at FROM bookings WHERE booking_id = ? AND user_id = ?";
    $verifyStmt = $conn->prepare($verifyQuery);
    $verifyStmt->bind_param("si", $booking_id, $user_id);
    $verifyStmt->execute();
    $verifyResult = $verifyStmt->get_result();
    
    if ($verifyResult->num_rows > 0) {
        $booking = $verifyResult->fetch_assoc();

        // Check 5-minute cancellation window
        $bookingTime = strtotime($booking['created_at']);
        $currentTime = time();
        $timeDiff = $currentTime - $bookingTime; // seconds

        if ($timeDiff > 300) { // 300 seconds = 5 minutes (change to 60 for 1 minute testing)
            $_SESSION['error'] = "You can only cancel within 5 minutes after booking.";
            header("Location: cancel_booking.php");
            exit;
        }

        if ($booking['status'] === 'cancelled') {
            $_SESSION['error'] = "This booking is already cancelled.";
        } else {
            // Update booking status to cancelled
            $cancelQuery = "UPDATE bookings SET status = 'cancelled' WHERE booking_id = ? AND user_id = ?";
            $cancelStmt = $conn->prepare($cancelQuery);
            $cancelStmt->bind_param("si", $booking_id, $user_id);
            
            if ($cancelStmt->execute()) {
                $_SESSION['success'] = "Booking cancelled successfully!";
            } else {
                $_SESSION['error'] = "Failed to cancel booking. Please try again.";
            }
            $cancelStmt->close();
        }
    } else {
        $_SESSION['error'] = "Booking not found.";
    }
    $verifyStmt->close();
    
    header("Location: cancel_booking.php");
    exit;
}

// Fetch all bookings for the user
$bookingsQuery = "SELECT 
    b.id,
    b.booking_id,
    b.vehicle_id,
    b.trip_start,
    b.trip_end,
    b.price,
    b.status,
    b.created_at,
    b.user_name,
    b.contact_number,
    b.alternative_number,
    b.email,
    b.notes,
    v.vehicle_name,
    v.vehicle_number,
    vt.type_name AS vehicle_type
FROM bookings b
JOIN vehicles v ON b.vehicle_id = v.id
JOIN vehicle_types vt ON v.vehicle_type_id = vt.id
WHERE b.user_id = ?
ORDER BY b.created_at DESC";

$stmt = $conn->prepare($bookingsQuery);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$bookings = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Calculate cancellation availability for each booking
// NOTE: 300 seconds = 5 minutes. Change to 60 for 1 minute testing if needed.
foreach ($bookings as &$booking) {
    $createdAt = strtotime($booking['created_at']);
    $currentTime = time();
    $timeDiff = $currentTime - $createdAt;
    
    // Can cancel if within 5 minutes (300 seconds) and not already cancelled/completed
    $booking['can_cancel'] = ($timeDiff <= 300) && 
                             ($booking['status'] !== 'cancelled') && 
                             ($booking['status'] !== 'completed');
    
    // Calculate time remaining for cancellation
    $timeRemaining = max(0, 300 - $timeDiff);
    $booking['time_remaining'] = $timeRemaining;
    $booking['minutes_remaining'] = floor($timeRemaining / 60);
    $booking['seconds_remaining'] = $timeRemaining % 60;
}
unset($booking); // Break reference
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Bookings | BookingNepal</title>
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
            padding: 20px;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .header {
            background: white;
            padding: 20px 30px;
            border-radius: 15px;
            margin-bottom: 30px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 20px;
        }

        .logo-section {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .logo {
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.5rem;
        }

        .logo-section h2 {
            color: #2d3748;
            font-size: 1.5rem;
        }

        .header-actions {
            display: flex;
            gap: 15px;
            align-items: center;
        }

        .user-info {
            color: #2d3748;
            font-weight: 600;
        }

        .btn {
            padding: 12px 24px;
            border-radius: 10px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            border: none;
            cursor: pointer;
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
        }

        .btn-secondary {
            background: #e2e8f0;
            color: #2d3748;
        }

        .btn-secondary:hover {
            background: #cbd5e0;
        }

        .page-title {
            background: white;
            padding: 30px;
            border-radius: 15px;
            margin-bottom: 30px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            text-align: center;
        }

        .page-title h1 {
            font-size: 2rem;
            color: #2d3748;
            margin-bottom: 10px;
        }

        .page-title p {
            color: #64748b;
            font-size: 1.1rem;
        }

        .alert {
            padding: 15px 20px;
            border-radius: 12px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: 600;
        }

        .alert-success {
            background: #d1fae5;
            color: #065f46;
            border: 2px solid #10b981;
        }

        .alert-error {
            background: #fee2e2;
            color: #991b1b;
            border: 2px solid #ef4444;
        }

        .bookings-grid {
            display: grid;
            gap: 25px;
        }

        .booking-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            transition: all 0.3s ease;
        }

        .booking-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.15);
        }

        .booking-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
        }

        .booking-header.cancelled {
            background: linear-gradient(135deg, #a0aec0 0%, #718096 100%);
        }

        .booking-header.confirmed {
            background: linear-gradient(135deg, #48bb78 0%, #38a169 100%);
        }

        .booking-header.completed {
            background: linear-gradient(135deg, #4299e1 0%, #3182ce 100%);
        }

        .booking-id {
            font-size: 1.3rem;
            font-weight: 700;
            font-family: 'Courier New', monospace;
        }

        .status-badge {
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 600;
            background: rgba(255, 255, 255, 0.3);
            text-transform: uppercase;
        }

        .booking-body {
            padding: 25px;
        }

        .booking-info {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }

        .info-block {
            display: flex;
            gap: 15px;
            align-items: start;
        }

        .info-icon {
            width: 45px;
            height: 45px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.2rem;
            flex-shrink: 0;
        }

        .info-content h4 {
            font-size: 0.85rem;
            color: #64748b;
            text-transform: uppercase;
            font-weight: 600;
            margin-bottom: 5px;
        }

        .info-content p {
            font-size: 1.05rem;
            color: #2d3748;
            font-weight: 600;
        }

        .price-badge {
            background: linear-gradient(135deg, #48bb78 0%, #38a169 100%);
            color: white;
            padding: 15px 20px;
            border-radius: 12px;
            text-align: center;
            margin: 20px 0;
        }

        .price-badge .label {
            font-size: 0.9rem;
            opacity: 0.9;
        }

        .price-badge .amount {
            font-size: 1.8rem;
            font-weight: 700;
        }

        .notes-section {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 10px;
            margin-top: 15px;
        }

        .notes-section h4 {
            color: #2d3748;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .notes-section p {
            color: #64748b;
            line-height: 1.6;
        }

        .booking-footer {
            padding: 20px 25px;
            background: #f8f9fa;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
        }

        .booking-date {
            color: #64748b;
            font-size: 0.9rem;
        }

        .btn-cancel {
            background: linear-gradient(135deg, #f56565 0%, #c53030 100%);
            color: white;
            padding: 12px 24px;
            border-radius: 10px;
            border: none;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .btn-cancel:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(245, 101, 101, 0.4);
        }

        .btn-cancel:disabled {
            background: #cbd5e0;
            cursor: not-allowed;
            transform: none;
        }

        .countdown-timer {
            font-size: 0.85rem;
            color: #f56565;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 6px;
            background: #fff5f5;
            padding: 6px 12px;
            border-radius: 8px;
            border: 1px solid #feb2b2;
        }

        .countdown-timer .time-value {
            font-family: 'Courier New', monospace;
            font-size: 1rem;
        }

        .countdown-timer.warning {
            background: #fffbeb;
            border-color: #fbbf24;
            color: #b45309;
        }

        .countdown-timer.critical {
            background: #fee2e2;
            border-color: #f87171;
            color: #991b1b;
            animation: pulse 1s infinite;
        }

        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.7; }
        }

        .status-info {
            padding: 12px 20px;
            border-radius: 10px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .cancelled-info {
            background: #fee2e2;
            color: #991b1b;
        }

        .completed-info {
            background: #d1fae5;
            color: #065f46;
        }

        .empty-state {
            background: white;
            padding: 60px 20px;
            border-radius: 15px;
            text-align: center;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        }

        .empty-icon {
            font-size: 4rem;
            color: #cbd5e0;
            margin-bottom: 20px;
        }

        .empty-state h3 {
            font-size: 1.5rem;
            color: #2d3748;
            margin-bottom: 10px;
        }

        .empty-state p {
            color: #64748b;
            margin-bottom: 25px;
        }

        .modal-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.6);
            align-items: center;
            justify-content: center;
            z-index: 1000;
            padding: 20px;
        }

        .modal-overlay.active {
            display: flex;
        }

        .modal {
            background: white;
            border-radius: 15px;
            max-width: 500px;
            width: 100%;
            padding: 30px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        }

        .modal h3 {
            color: #2d3748;
            margin-bottom: 15px;
            font-size: 1.5rem;
        }

        .modal p {
            color: #64748b;
            margin-bottom: 25px;
            line-height: 1.6;
        }

        .modal-actions {
            display: flex;
            gap: 15px;
            justify-content: flex-end;
        }

        @media (max-width: 768px) {
            .header {
                flex-direction: column;
                text-align: center;
            }

            .booking-info {
                grid-template-columns: 1fr;
            }

            .booking-footer {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <div class="logo-section">
                <div class="logo">
                    <i class="fas fa-bus-alt"></i>
                </div>
                <h2>BookingNepal</h2>
            </div>
            <div class="header-actions">
                <span class="user-info">
                    <i class="fas fa-user-circle"></i> <?= htmlspecialchars($username) ?>
                </span>
                <a href="user_dashboard.php" class="btn btn-primary">
                    <i class="fas fa-home"></i> Dashboard
                </a>
                <a href="../logout.php" class="btn btn-secondary">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </div>

        <!-- Page Title -->
        <div class="page-title">
            <h1><i class="fas fa-list"></i> My Bookings</h1>
            <p>View and manage your vehicle bookings</p>
        </div>

        <!-- Success/Error Messages -->
        <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i>
            <?= htmlspecialchars($_SESSION['success']) ?>
        </div>
        <?php unset($_SESSION['success']); endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-error">
            <i class="fas fa-exclamation-circle"></i>
            <?= htmlspecialchars($_SESSION['error']) ?>
        </div>
        <?php unset($_SESSION['error']); endif; ?>

        <!-- Bookings Grid -->
        <?php if (count($bookings) > 0): ?>
        <div class="bookings-grid">
            <?php foreach ($bookings as $booking): ?>
            <div class="booking-card">
                <div class="booking-header <?= strtolower($booking['status']) ?>">
                    <div class="booking-id">
                        <i class="fas fa-ticket-alt"></i> <?= htmlspecialchars($booking['booking_id']) ?>
                    </div>
                    <div class="status-badge">
                        <?= htmlspecialchars(ucfirst($booking['status'])) ?>
                    </div>
                </div>

                <div class="booking-body">
                    <div class="booking-info">
                        <div class="info-block">
                            <div class="info-icon">
                                <i class="fas fa-bus"></i>
                            </div>
                            <div class="info-content">
                                <h4>Vehicle</h4>
                                <p><?= htmlspecialchars($booking['vehicle_name']) ?></p>
                                <p style="font-size: 0.9rem; color: #64748b;">
                                    <?= htmlspecialchars($booking['vehicle_number']) ?>
                                </p>
                            </div>
                        </div>

                        <div class="info-block">
                            <div class="info-icon">
                                <i class="fas fa-user"></i>
                            </div>
                            <div class="info-content">
                                <h4>Passenger</h4>
                                <p><?= htmlspecialchars($booking['user_name']) ?></p>
                            </div>
                        </div>

                        <div class="info-block">
                            <div class="info-icon">
                                <i class="fas fa-phone"></i>
                            </div>
                            <div class="info-content">
                                <h4>Contact</h4>
                                <p><?= htmlspecialchars($booking['contact_number']) ?></p>
                                <?php if (!empty($booking['alternative_number'])): ?>
                                <p style="font-size: 0.9rem; color: #64748b;">
                                    Alt: <?= htmlspecialchars($booking['alternative_number']) ?>
                                </p>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="info-block">
                            <div class="info-icon">
                                <i class="fas fa-envelope"></i>
                            </div>
                            <div class="info-content">
                                <h4>Email</h4>
                                <p style="font-size: 0.95rem;"><?= htmlspecialchars($booking['email']) ?></p>
                            </div>
                        </div>

                        <div class="info-block">
                            <div class="info-icon">
                                <i class="fas fa-calendar-check"></i>
                            </div>
                            <div class="info-content">
                                <h4>Trip Starts</h4>
                                <p><?= date('M d, Y', strtotime($booking['trip_start'])) ?></p>
                            </div>
                        </div>

                        <div class="info-block">
                            <div class="info-icon">
                                <i class="fas fa-calendar-times"></i>
                            </div>
                            <div class="info-content">
                                <h4>Trip Ends</h4>
                                <p><?= date('M d, Y', strtotime($booking['trip_end'])) ?></p>
                            </div>
                        </div>
                    </div>

                    <?php if (!empty($booking['notes'])): ?>
                    <div class="notes-section">
                        <h4><i class="fas fa-sticky-note"></i> Additional Notes</h4>
                        <p><?= nl2br(htmlspecialchars($booking['notes'])) ?></p>
                    </div>
                    <?php endif; ?>

                    <div class="price-badge">
                        <div class="label">Total Amount</div>
                        <div class="amount">Rs. <?= number_format($booking['price'], 2) ?></div>
                    </div>
                </div>

                <div class="booking-footer">
                    <div class="booking-date">
                        <i class="fas fa-clock"></i> 
                        Booked on <?= date('M d, Y - h:i A', strtotime($booking['created_at'])) ?>
                    </div>
                    
                    <?php if ($booking['status'] !== 'cancelled' && $booking['status'] !== 'completed'): ?>
                        <?php if ($booking['can_cancel']): ?>
                            <div style="display: flex; flex-direction: column; gap: 8px; align-items: flex-end;">
                                <button 
                                    class="btn-cancel" 
                                    onclick="confirmCancel('<?= htmlspecialchars($booking['booking_id']) ?>')"
                                    id="cancel-btn-<?= htmlspecialchars($booking['booking_id']) ?>"
                                >
                                    <i class="fas fa-times-circle"></i> Cancel Booking
                                </button>
                                <div class="countdown-timer" 
                                     id="timer-<?= htmlspecialchars($booking['booking_id']) ?>"
                                     data-booking-id="<?= htmlspecialchars($booking['booking_id']) ?>"
                                     data-created-at="<?= strtotime($booking['created_at']) ?>"
                                     data-debug-created="<?= $booking['created_at'] ?>"
                                     data-debug-timestamp="<?= strtotime($booking['created_at']) ?>"
                                     data-debug-current="<?= time() ?>">
                                    <i class="fas fa-hourglass-half"></i>
                                    <span class="timer-text">
                                        Time left: <strong><span class="time-value"><?= sprintf('%d:%02d', $booking['minutes_remaining'], $booking['seconds_remaining']) ?></span></strong>
                                    </span>
                                </div>
                            </div>
                        <?php else: ?>
                            <button class="btn-cancel" disabled title="Cancellation window expired (5 minutes)">
                                <i class="fas fa-ban"></i> Cancellation Expired
                            </button>
                        <?php endif; ?>
                    <?php elseif ($booking['status'] === 'cancelled'): ?>
                        <div class="status-info cancelled-info">
                            <i class="fas fa-times-circle"></i> Booking Cancelled
                        </div>
                    <?php elseif ($booking['status'] === 'completed'): ?>
                        <div class="status-info completed-info">
                            <i class="fas fa-check-circle"></i> Trip Completed
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <div class="empty-state">
            <div class="empty-icon">
                <i class="fas fa-inbox"></i>
            </div>
            <h3>No Bookings Yet</h3>
            <p>You haven't made any bookings yet. Start exploring available vehicles!</p>
            <a href="user_dashboard.php" class="btn btn-primary">
                <i class="fas fa-search"></i> Browse Vehicles
            </a>
        </div>
        <?php endif; ?>
    </div>

    <!-- Confirmation Modal -->
    <div class="modal-overlay" id="cancelModal">
        <div class="modal">
            <h3><i class="fas fa-exclamation-triangle" style="color: #f56565;"></i> Cancel Booking?</h3>
            <p>Are you sure you want to cancel this booking? This action cannot be undone.</p>
            <form method="POST" id="cancelForm">
                <input type="hidden" name="booking_id" id="cancelBookingId">
                <input type="hidden" name="cancel_booking" value="1">
                <div class="modal-actions">
                    <button type="button" class="btn btn-secondary" onclick="closeModal()">
                        No, Keep It
                    </button>
                    <button type="submit" class="btn-cancel">
                        <i class="fas fa-check"></i> Yes, Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Real-time countdown timer
        function updateCountdowns() {
            const timers = document.querySelectorAll('.countdown-timer');
            
            timers.forEach(timer => {
                const bookingId = timer.dataset.bookingId;
                const createdAt = parseInt(timer.dataset.createdAt);
                const currentTime = Math.floor(Date.now() / 1000);
                const elapsed = currentTime - createdAt;
                const timeLeft = Math.max(0, 300 - elapsed); // 300 seconds = 5 minutes
                
                // Debug: Log values to console
                console.log('Booking:', bookingId);
                console.log('Created At (timestamp):', createdAt);
                console.log('Current Time:', currentTime);
                console.log('Elapsed:', elapsed, 'seconds');
                console.log('Time Left:', timeLeft, 'seconds');
                console.log('---');
                
                if (timeLeft > 0) {
                    const minutes = Math.floor(timeLeft / 60);
                    const seconds = timeLeft % 60;
                    const timeValue = timer.querySelector('.time-value');
                    
                    if (timeValue) {
                        timeValue.textContent = `${minutes}:${seconds.toString().padStart(2, '0')}`;
                    }
                    
                    // Change color based on time remaining
                    timer.classList.remove('warning', 'critical');
                    if (timeLeft <= 60) {
                        timer.classList.add('critical');
                    } else if (timeLeft <= 120) {
                        timer.classList.add('warning');
                    }
                } else {
                    // Time expired - disable button and update UI
                    const cancelBtn = document.getElementById(`cancel-btn-${bookingId}`);
                    if (cancelBtn && !cancelBtn.disabled) {
                        cancelBtn.disabled = true;
                        cancelBtn.innerHTML = '<i class="fas fa-ban"></i> Cancellation Expired';
                        cancelBtn.title = 'Cancellation window expired (5 minutes)';
                        timer.innerHTML = '<i class="fas fa-clock"></i> <span style="color: #64748b;">Cancellation period ended</span>';
                        timer.classList.remove('warning', 'critical');
                        timer.style.background = '#f1f5f9';
                        timer.style.borderColor = '#cbd5e0';
                        timer.style.color = '#64748b';
                    }
                }
            });
        }

        // Update every second
        setInterval(updateCountdowns, 1000);

        // Initial update
        updateCountdowns();

        function confirmCancel(bookingId) {
            document.getElementById('cancelBookingId').value = bookingId;
            document.getElementById('cancelModal').classList.add('active');
        }

        function closeModal() {
            document.getElementById('cancelModal').classList.remove('active');
        }

        // Close modal on overlay click
        document.getElementById('cancelModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeModal();
            }
        });

        // Close modal on ESC key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeModal();
            }
        });
    </script>
</body>
</html>