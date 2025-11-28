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
    
    // Calculate time remaining for cancellation IN SECONDS
    $timeRemaining = max(0, 300 - $timeDiff);
    $booking['time_remaining'] = $timeRemaining;
    
    // Convert to minutes and seconds for display
    $booking['minutes_remaining'] = floor($timeRemaining / 60);
    $booking['seconds_remaining'] = $timeRemaining % 60;
    
    // Store the actual timestamps for JavaScript
    $booking['created_at_timestamp'] = $createdAt;
    $booking['current_time_timestamp'] = $currentTime;
}
unset($booking); // Break reference
// DEBUG: Check what values we're getting
echo "<!-- DEBUG INFO:\n";
if (isset($bookings[0])) {
    $testBooking = $bookings[0];
    echo "created_at from DB: " . $testBooking['created_at'] . "\n";
    echo "created_at_timestamp: " . $testBooking['created_at_timestamp'] . "\n";
    echo "current_time_timestamp: " . $testBooking['current_time_timestamp'] . "\n";
    echo "Time difference: " . ($testBooking['current_time_timestamp'] - $testBooking['created_at_timestamp']) . " seconds\n";
    echo "Minutes remaining: " . $testBooking['minutes_remaining'] . "\n";
    echo "Seconds remaining: " . $testBooking['seconds_remaining'] . "\n";
}
echo "-->\n";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Bookings | BookingNepal</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css">
    <link rel="shortcut icon" href="favi.png" type="image/x-icon">
    <link rel="stylesheet" href="cancel_booking.css">
    <style>
       
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
     data-created-at="<?= intval($booking['created_at_timestamp']) ?>"
     data-debug-current="<?= intval($booking['current_time_timestamp']) ?>"
     data-debug-raw-created="<?= htmlspecialchars($booking['created_at']) ?>"
     data-debug-diff="<?= intval($booking['current_time_timestamp'] - $booking['created_at_timestamp']) ?>">
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
  console.log('=== BOOKING COUNTDOWN DEBUG ===');

// Log all timer elements on page load
document.querySelectorAll('.countdown-timer').forEach(timer => {
    console.log('\n--- Timer Debug Info ---');
    console.log('Booking ID:', timer.dataset.bookingId);
    console.log('Raw created_at from DB:', timer.dataset.debugRawCreated);
    console.log('Created timestamp:', timer.dataset.createdAt);
    console.log('Current timestamp:', timer.dataset.debugCurrent);
    console.log('Difference (from PHP):', timer.dataset.debugDiff, 'seconds');
    
    const created = parseInt(timer.dataset.createdAt);
    const current = parseInt(timer.dataset.debugCurrent);
    const diff = current - created;
    
    console.log('JavaScript calculation:');
    console.log('  Created:', created);
    console.log('  Current:', current);
    console.log('  Difference:', diff, 'seconds');
    console.log('  Time remaining:', (300 - diff), 'seconds');
    console.log('  Should show:', Math.floor((300 - diff) / 60) + ':' + ((300 - diff) % 60).toString().padStart(2, '0'));
});

function updateCountdowns() {
    const timers = document.querySelectorAll('.countdown-timer');
    
    timers.forEach(timer => {
        const bookingId = timer.dataset.bookingId;
        const createdAt = parseInt(timer.dataset.createdAt);
        const serverCurrent = parseInt(timer.dataset.debugCurrent);
        
        // Calculate current time
        const now = Math.floor(Date.now() / 1000);
        
        // Calculate elapsed time since booking was created
        const elapsed = now - createdAt;
        
        // Calculate remaining time (300 seconds = 5 minutes)
        const remaining = Math.max(0, 300 - elapsed);
        
        const minutes = Math.floor(remaining / 60);
        const seconds = remaining % 60;
        
        const timeValue = timer.querySelector('.time-value');
        
        if (remaining > 0 && timeValue) {
            timeValue.textContent = `${minutes}:${seconds.toString().padStart(2, '0')}`;
            
            timer.classList.remove('warning', 'critical');
            if (remaining <= 60) {
                timer.classList.add('critical');
            } else if (remaining <= 120) {
                timer.classList.add('warning');
            }
        } else if (remaining <= 0) {
            const cancelBtn = document.getElementById(`cancel-btn-${bookingId}`);
            if (cancelBtn && !cancelBtn.disabled) {
                cancelBtn.disabled = true;
                cancelBtn.innerHTML = '<i class="fas fa-ban"></i> Cancellation Expired';
                timer.innerHTML = '<i class="fas fa-clock"></i> <span style="color: #64748b;">Cancellation period ended</span>';
                timer.style.background = '#f1f5f9';
                timer.style.borderColor = '#cbd5e0';
                timer.style.color = '#64748b';
                
                setTimeout(() => location.reload(), 2000);
            }
        }
    });
}

updateCountdowns();
setInterval(updateCountdowns, 1000);

function confirmCancel(bookingId) {
    document.getElementById('cancelBookingId').value = bookingId;
    document.getElementById('cancelModal').classList.add('active');
}

function closeModal() {
    document.getElementById('cancelModal').classList.remove('active');
}

document.getElementById('cancelModal').addEventListener('click', function(e) {
    if (e.target === this) closeModal();
});

document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') closeModal();
});
    </script>
</body>
</html>