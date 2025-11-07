<?php
session_start();
require_once '../db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['username'] ?? $_SESSION['user_name'] ?? 'User';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Bookings - BookingNepal</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="shortcut icon" href="favi.png" type="image/x-icon">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: #f5f7fa;
        }

        /* Header */
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 25px 0;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        }

        .header-content {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .header h1 {
            font-size: 1.8em;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .user-badge {
            background: rgba(255,255,255,0.2);
            padding: 8px 16px;
            border-radius: 25px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .logout-btn {
            background: rgba(255,255,255,0.2);
            color: white;
            padding: 10px 20px;
            border-radius: 8px;
            text-decoration: none;
            transition: background 0.3s;
            display: flex;
            align-items: center;
            gap: 8px;
            font-weight: 500;
        }

        .logout-btn:hover {
            background: rgba(255,255,255,0.3);
        }

        /* Main Container */
        .container {
            max-width: 1200px;
            margin: 30px auto;
            padding: 0 20px;
        }

        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: #667eea;
            text-decoration: none;
            margin-bottom: 25px;
            font-weight: 600;
            transition: color 0.3s;
        }

        .back-link:hover {
            color: #764ba2;
        }

        /* Page Title */
        .page-title {
            margin-bottom: 30px;
        }

        .page-title h2 {
            color: #2d3748;
            font-size: 2.2em;
            margin-bottom: 10px;
        }

        .page-title p {
            color: #718096;
            font-size: 1.05em;
        }

        /* Loading State */
        .loading {
            text-align: center;
            padding: 80px 20px;
        }

        .spinner {
            border: 4px solid #f3f3f3;
            border-top: 4px solid #667eea;
            border-radius: 50%;
            width: 60px;
            height: 60px;
            animation: spin 1s linear infinite;
            margin: 0 auto 20px;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        /* Bookings Grid */
        #bookings-list {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(380px, 1fr));
            gap: 25px;
        }

        /* Booking Card */
        .booking-card {
            background: white;
            border-radius: 15px;
            padding: 28px;
            box-shadow: 0 2px 15px rgba(0, 0, 0, 0.08);
            transition: all 0.3s ease;
            border: 2px solid transparent;
            position: relative;
            overflow: hidden;
        }

        .booking-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #667eea 0%, #764ba2 100%);
        }

        .booking-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
            border-color: #667eea;
        }

        .cancelled-card {
            opacity: 0.75;
            background: #f8f9fa;
        }

        .cancelled-card::before {
            background: #dc3545;
        }

        .booking-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 22px;
            padding-bottom: 18px;
            border-bottom: 2px solid #f0f0f0;
        }

        .booking-id {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .booking-id h3 {
            color: #2d3748;
            font-size: 1.35em;
            margin: 0;
        }

        .booking-id i {
            color: #667eea;
            font-size: 1.3em;
        }

        /* Status Badge */
        .status-badge {
            padding: 8px 16px;
            border-radius: 25px;
            font-size: 0.8em;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .status-active {
            background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%);
            color: #155724;
        }

        .status-cancelled {
            background: linear-gradient(135deg, #f8d7da 0%, #f5c6cb 100%);
            color: #721c24;
        }

        /* Booking Details */
        .booking-details {
            margin: 22px 0;
        }

        .detail-row {
            display: flex;
            align-items: flex-start;
            gap: 14px;
            padding: 12px 0;
            color: #4a5568;
        }

        .detail-row i {
            color: #667eea;
            font-size: 1.15em;
            width: 22px;
            margin-top: 3px;
        }

        .detail-row strong {
            color: #2d3748;
            font-weight: 600;
            display: block;
            margin-bottom: 3px;
        }

        .vehicle-badge {
            display: inline-block;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.85em;
            font-weight: 600;
            margin-top: 5px;
        }

        /* Buttons */
        .btn {
            padding: 13px 26px;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            font-size: 1em;
            font-weight: 600;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            text-decoration: none;
            justify-content: center;
        }

        .btn-cancel {
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
            color: white;
            width: 100%;
            margin-top: 18px;
            box-shadow: 0 4px 15px rgba(220, 53, 69, 0.3);
        }

        .btn-cancel:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(220, 53, 69, 0.4);
        }

        .btn-cancel:disabled {
            background: #ccc;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }

        .btn-danger {
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
            color: white;
        }

        .btn-danger:hover {
            background: linear-gradient(135deg, #c82333 0%, #bd2130 100%);
        }

        .btn-secondary {
            background: #6c757d;
            color: white;
        }

        .btn-secondary:hover {
            background: #5a6268;
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
        }

        /* Modal */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.6);
            backdrop-filter: blur(5px);
            justify-content: center;
            align-items: center;
            z-index: 1000;
            animation: fadeIn 0.3s;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        .modal-content {
            background: white;
            padding: 40px;
            border-radius: 20px;
            max-width: 480px;
            width: 90%;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            animation: slideUp 0.3s;
        }

        @keyframes slideUp {
            from {
                transform: translateY(50px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        .modal-content h3 {
            margin: 0 0 18px 0;
            color: #dc3545;
            font-size: 1.6em;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .modal-content p {
            color: #4a5568;
            margin: 18px 0 22px;
            line-height: 1.7;
            font-size: 1.05em;
        }

        .cancel-info {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            padding: 18px;
            border-radius: 12px;
            margin: 22px 0;
            border-left: 5px solid #667eea;
        }

        .cancel-info p {
            margin: 10px 0;
            color: #2d3748;
            font-size: 0.95em;
        }

        .modal-actions {
            display: flex;
            gap: 12px;
            justify-content: flex-end;
            margin-top: 28px;
        }

        .modal-actions .btn {
            flex: 1;
        }

        /* Notification */
        .notification {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 18px 30px;
            border-radius: 12px;
            color: white;
            font-weight: 600;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.25);
            opacity: 0;
            transform: translateX(400px);
            transition: all 0.4s cubic-bezier(0.68, -0.55, 0.265, 1.55);
            z-index: 1001;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .notification.show {
            opacity: 1;
            transform: translateX(0);
        }

        .notification-success {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
        }

        .notification-error {
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
        }

        .notification-info {
            background: linear-gradient(135deg, #17a2b8 0%, #138496 100%);
        }

        /* No bookings / Error messages */
        .no-bookings,
        .error-message {
            text-align: center;
            padding: 80px 20px;
            background: white;
            border-radius: 20px;
            box-shadow: 0 2px 15px rgba(0, 0, 0, 0.08);
        }

        .no-bookings i,
        .error-message i {
            font-size: 4em;
            margin-bottom: 25px;
        }

        .no-bookings i {
            color: #cbd5e0;
        }

        .error-message i {
            color: #dc3545;
        }

        .no-bookings h3,
        .error-message h3 {
            color: #2d3748;
            margin-bottom: 12px;
            font-size: 1.6em;
        }

        .no-bookings p,
        .error-message p {
            color: #718096;
            margin-bottom: 28px;
            font-size: 1.05em;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            #bookings-list {
                grid-template-columns: 1fr;
            }
            
            .header-content {
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }
            
            .header h1 {
                font-size: 1.5em;
            }

            .page-title h2 {
                font-size: 1.8em;
            }
            
            .booking-header {
                flex-direction: column;
                gap: 12px;
            }
            
            .modal-content {
                width: 95%;
                padding: 30px;
            }
            
            .modal-actions {
                flex-direction: column;
            }
            
            .notification {
                right: 10px;
                left: 10px;
                transform: translateY(-100px);
            }
            
            .notification.show {
                transform: translateY(0);
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="header">
        <div class="header-content">
            <h1>
                <i class="fas fa-ticket-alt"></i>
                My Bookings
            </h1>
            <div class="user-info">
                <div class="user-badge">
                    <i class="fas fa-user-circle"></i>
                    <span><?php echo htmlspecialchars($user_name); ?></span>
                </div>
                <a href="../logout.php" class="logout-btn">
                    <i class="fas fa-sign-out-alt"></i>
                    Logout
                </a>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="container">
        <!-- FIXED: Changed from dashboard.php to user_dashboard.php -->
        <a href="user_dashboard.php" class="back-link">
            <i class="fas fa-arrow-left"></i>
            Back to Dashboard
        </a>

        <div class="page-title">
            <h2>Your Bookings</h2>
            <p>Manage and view all your ticket reservations</p>
        </div>

        <!-- Loading State -->
        <div id="loading" class="loading">
            <div class="spinner"></div>
            <p>Loading your bookings...</p>
        </div>

        <!-- Bookings Section -->
        <div class="bookings-section" style="display: none;">
            <div id="bookings-list">
                <!-- Bookings will be loaded here dynamically -->
            </div>
        </div>
    </div>

    <!-- Cancel Confirmation Modal -->
    <div id="cancelModal" class="modal">
        <div class="modal-content">
            <h3>
                <i class="fas fa-exclamation-triangle"></i>
                Cancel Booking
            </h3>
            <p>Are you sure you want to cancel this booking? This action cannot be undone.</p>
            <div class="booking-info" id="cancelBookingInfo"></div>
            <div class="modal-actions">
                <button id="confirmCancel" class="btn btn-danger">
                    <i class="fas fa-times-circle"></i>
                    Yes, Cancel
                </button>
                <button id="closeModal" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i>
                    Keep It
                </button>
            </div>
        </div>
    </div>

    <script>
        // Load and display bookings
        function loadBookings() {
            const loadingEl = document.getElementById('loading');
            const bookingsSection = document.querySelector('.bookings-section');
            
            fetch('get_bookings.php')
                .then(response => response.json())
                .then(data => {
                    loadingEl.style.display = 'none';
                    bookingsSection.style.display = 'block';
                    
                    const bookingsList = document.getElementById('bookings-list');
                    
                    if (data.success && data.bookings.length > 0) {
                        bookingsList.innerHTML = data.bookings.map(booking => `
                            <div class="booking-card ${booking.status === 'cancelled' ? 'cancelled-card' : ''}" data-booking-id="${booking.id}">
                                <div class="booking-header">
                                    <div class="booking-id">
                                        <i class="fas fa-ticket-alt"></i>
                                        <h3>Booking #${booking.id}</h3>
                                    </div>
                                    <span class="status-badge status-${booking.status || 'active'}">
                                        <i class="fas fa-${booking.status === 'cancelled' ? 'times-circle' : 'check-circle'}"></i>
                                        ${booking.status === 'cancelled' ? 'Cancelled' : 'Active'}
                                    </span>
                                </div>
                                <div class="booking-details">
                                    <div class="detail-row">
                                        <i class="fas fa-bus"></i>
                                        <div>
                                            <strong>Vehicle</strong>
                                            <span class="vehicle-badge">${booking.vehicle_name || 'Bus'}</span>
                                        </div>
                                    </div>
                                    <div class="detail-row">
                                        <i class="fas fa-user"></i>
                                        <div>
                                            <strong>Passenger Name</strong>
                                            ${booking.name}
                                        </div>
                                    </div>
                                    <div class="detail-row">
                                        <i class="fas fa-envelope"></i>
                                        <div>
                                            <strong>Email</strong>
                                            ${booking.email}
                                        </div>
                                    </div>
                                    <div class="detail-row">
                                        <i class="fas fa-phone"></i>
                                        <div>
                                            <strong>Contact</strong>
                                            ${booking.contact}
                                        </div>
                                    </div>
                                    <div class="detail-row">
                                        <i class="fas fa-calendar"></i>
                                        <div>
                                            <strong>Booking Date</strong>
                                            ${formatDate(booking.booking_date)}
                                        </div>
                                    </div>
                                </div>
                                ${(!booking.status || booking.status !== 'cancelled') ? `
                                    <button class="btn btn-cancel" onclick="openCancelModal('${booking.id}', '${booking.vehicle_name}', '${booking.name}')">
                                        <i class="fas fa-times-circle"></i>
                                        Cancel Booking
                                    </button>
                                ` : ''}
                            </div>
                        `).join('');
                    } else {
                        bookingsList.innerHTML = `
                            <div class="no-bookings">
                                <i class="fas fa-inbox"></i>
                                <h3>No bookings found</h3>
                                <p>You haven't made any bookings yet.</p>
                                <a href="user_dashboard.php" class="btn btn-primary">
                                    <i class="fas fa-search"></i>
                                    Book Now
                                </a>
                            </div>
                        `;
                    }
                })
                .catch(error => {
                    console.error('Error loading bookings:', error);
                    loadingEl.style.display = 'none';
                    bookingsSection.style.display = 'block';
                    document.getElementById('bookings-list').innerHTML = `
                        <div class="error-message">
                            <i class="fas fa-exclamation-circle"></i>
                            <h3>Failed to load bookings</h3>
                            <p>Please try again later.</p>
                            <button onclick="loadBookings()" class="btn btn-primary">
                                <i class="fas fa-redo"></i>
                                Retry
                            </button>
                        </div>
                    `;
                });
        }

        function formatDate(dateString) {
            const date = new Date(dateString);
            const options = { year: 'numeric', month: 'long', day: 'numeric', hour: '2-digit', minute: '2-digit' };
            return date.toLocaleDateString('en-US', options);
        }

        let bookingToCancel = null;

        function openCancelModal(bookingId, vehicleName, passengerName) {
            bookingToCancel = bookingId;
            
            const infoEl = document.getElementById('cancelBookingInfo');
            infoEl.innerHTML = `
                <div class="cancel-info">
                    <p><strong>Booking ID:</strong> #${bookingId}</p>
                    <p><strong>Vehicle:</strong> ${vehicleName}</p>
                    <p><strong>Passenger:</strong> ${passengerName}</p>
                </div>
            `;
            
            document.getElementById('cancelModal').style.display = 'flex';
        }

        function closeCancelModal() {
            bookingToCancel = null;
            document.getElementById('cancelModal').style.display = 'none';
        }

        function cancelBooking() {
            if (!bookingToCancel) return;

            const formData = new FormData();
            formData.append('booking_id', bookingToCancel);

            const confirmBtn = document.getElementById('confirmCancel');
            const originalText = confirmBtn.innerHTML;
            confirmBtn.disabled = true;
            confirmBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Cancelling...';

            fetch('cancel_booking.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotification(data.message, 'success');
                    closeCancelModal();
                    loadBookings();
                } else {
                    showNotification(data.message, 'error');
                    confirmBtn.disabled = false;
                    confirmBtn.innerHTML = originalText;
                }
            })
            .catch(error => {
                console.error('Error cancelling booking:', error);
                showNotification('Failed to cancel booking. Please try again.', 'error');
                confirmBtn.disabled = false;
                confirmBtn.innerHTML = originalText;
            });
        }

        function showNotification(message, type = 'info') {
            const notification = document.createElement('div');
            notification.className = `notification notification-${type}`;
            
            const icon = type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-circle' : 'info-circle';
            notification.innerHTML = `<i class="fas fa-${icon}"></i><span>${message}</span>`;
            
            document.body.appendChild(notification);
            
            setTimeout(() => notification.classList.add('show'), 10);
            
            setTimeout(() => {
                notification.classList.remove('show');
                setTimeout(() => notification.remove(), 300);
            }, 3000);
        }

        document.addEventListener('DOMContentLoaded', function() {
            loadBookings();
            
            document.getElementById('closeModal').addEventListener('click', closeCancelModal);
            document.getElementById('confirmCancel').addEventListener('click', cancelBooking);
            
            document.getElementById('cancelModal').addEventListener('click', function(e) {
                if (e.target === this) {
                    closeCancelModal();
                }
            });
        });
    </script>
</body>
</html>