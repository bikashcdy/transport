<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'user') {
    header("Location: ../index.php");
    exit;
}

require_once '../db.php';

// Check if vehicle data was posted
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['vehicle_id'])) {
    header("Location: user_dashboard.php");
    exit;
}

$vehicle_id = intval($_POST['vehicle_id']);
$start_date = $_POST['start_date'];
$end_date = $_POST['end_date'];

// Get vehicle details
$vehicleQuery = "SELECT v.*, vt.type_name 
                 FROM vehicles v 
                 JOIN vehicle_types vt ON v.vehicle_type_id = vt.id 
                 WHERE v.id = ? AND v.status = 'available'";
$stmt = $conn->prepare($vehicleQuery);
$stmt->bind_param("i", $vehicle_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $_SESSION['error'] = "Vehicle not found or not available.";
    header("Location: user_dashboard.php");
    exit;
}

$vehicle = $result->fetch_assoc();
$stmt->close();

// ===== GET USER DETAILS FOR AUTO-FILL =====
$user_id = $_SESSION['user_id'];
$user_full_name = '';
$user_email = '';

$userQuery = "SELECT name, email FROM users WHERE id = ?";
$userStmt = $conn->prepare($userQuery);

if ($userStmt) {
    $userStmt->bind_param("i", $user_id);
    $userStmt->execute();
    $userResult = $userStmt->get_result();
    
    if ($userResult && $userResult->num_rows > 0) {
        $userData = $userResult->fetch_assoc();
        $user_full_name = $userData['name'] ?? '';
        $user_email = $userData['email'] ?? '';
    }
    $userStmt->close();
}
// ===== END USER DETAILS =====

// ===== CALCULATE TOTAL PRICE BASED ON DAYS =====
$start = new DateTime($start_date);
$end = new DateTime($end_date);
$days = $start->diff($end)->days;
if ($days == 0) $days = 1; // Minimum 1 day

$daily_rate = $vehicle['price'];
$total_price = $daily_rate * $days;
// ===== END CALCULATION =====

$username = $_SESSION['username'] ?? 'User';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Complete Booking | BookingNepal</title>
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
            padding: 1rem;
        }

        .modal-overlay {
            display: flex;
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.6);
            align-items: center;
            justify-content: center;
            padding: 1rem;
            z-index: 50;
            animation: fadeIn 0.3s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        .modal-content {
            background: white;
            border-radius: 1.5rem;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.4);
            max-width: 32rem;
            width: 100%;
            max-height: 90vh;
            overflow-y: auto;
            animation: slideUp 0.4s ease;
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

        .modal-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-radius: 1.5rem 1.5rem 0 0;
        }

        .modal-header h3 {
            font-size: 1.75rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .close-btn {
            background: rgba(255, 255, 255, 0.2);
            border: none;
            color: white;
            border-radius: 50%;
            padding: 0.5rem;
            cursor: pointer;
            transition: all 0.3s;
            width: 2.5rem;
            height: 2.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
        }

        .close-btn:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: rotate(90deg);
        }

        .vehicle-summary {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            padding: 1.5rem;
            border-bottom: 2px dashed #cbd5e0;
        }

        .vehicle-summary-header {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .vehicle-icon-box {
            width: 3.5rem;
            height: 3.5rem;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 0.75rem;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.5rem;
        }

        .vehicle-info h4 {
            font-size: 1.25rem;
            color: #2d3748;
            font-weight: 700;
            margin-bottom: 0.25rem;
        }

        .vehicle-info p {
            color: #64748b;
            font-weight: 600;
        }

        .date-range-box {
            background: white;
            padding: 1rem;
            border-radius: 0.75rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.75rem;
            color: #667eea;
            font-weight: 600;
            margin-top: 1rem;
        }

        .price-box {
            background: linear-gradient(135deg, #48bb78 0%, #38a169 100%);
            color: white;
            padding: 1rem;
            border-radius: 0.75rem;
            text-align: center;
            margin-top: 1rem;
        }

        .price-label {
            font-size: 0.9rem;
            opacity: 0.95;
            margin-bottom: 0.25rem;
        }

        .price-amount {
            font-size: 1.75rem;
            font-weight: 700;
        }

        .modal-body {
            padding: 2rem;
        }

        .section-title {
            font-size: 1.25rem;
            color: #2d3748;
            font-weight: 700;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .form-group {
            margin-bottom: 1.25rem;
        }

        .form-label {
            display: block;
            color: #2d3748;
            font-weight: 600;
            margin-bottom: 0.5rem;
            font-size: 0.95rem;
        }

        .required {
            color: #e53e3e;
        }

        .form-input,
        .form-textarea {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 2px solid #e2e8f0;
            border-radius: 0.75rem;
            font-size: 1rem;
            transition: all 0.3s;
            font-family: inherit;
        }

        .form-input:focus,
        .form-textarea:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .form-input.error,
        .form-textarea.error {
            border-color: #e53e3e;
        }

        .form-textarea {
            resize: vertical;
            min-height: 5rem;
        }

        .error-message {
            color: #e53e3e;
            font-size: 0.875rem;
            margin-top: 0.375rem;
            display: none;
        }

        .error-message.active {
            display: block;
        }

        .form-actions {
            display: flex;
            gap: 1rem;
            margin-top: 2rem;
        }

        .btn {
            flex: 1;
            padding: 1rem;
            border: none;
            border-radius: 0.75rem;
            font-size: 1.05rem;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        .btn-confirm {
            background: linear-gradient(135deg, #48bb78 0%, #38a169 100%);
            color: white;
            box-shadow: 0 4px 15px rgba(72, 187, 120, 0.3);
        }

        .btn-confirm:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(72, 187, 120, 0.4);
        }

        .btn-cancel {
            background: #e2e8f0;
            color: #2d3748;
        }

        .btn-cancel:hover {
            background: #cbd5e0;
        }

        @media (max-width: 768px) {
            .modal-header {
                padding: 1.5rem;
            }

            .modal-header h3 {
                font-size: 1.5rem;
            }

            .modal-body {
                padding: 1.5rem;
            }

            .form-actions {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <!-- Booking Modal (Auto-opens) -->
    <div class="modal-overlay" id="bookingModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>
                    <i class="fas fa-ticket-alt"></i>
                    Complete Your Booking
                </h3>
                <button class="close-btn" onclick="closeModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <div class="vehicle-summary">
                <div class="vehicle-summary-header">
                    <div class="vehicle-icon-box">
                        <?php if ($vehicle['type_name'] == 'bus'): ?>
                        <i class="fas fa-bus"></i>
                        <?php elseif ($vehicle['type_name'] == 'taxi'): ?>
                        <i class="fas fa-taxi"></i>
                        <?php else: ?>
                        <i class="fas fa-van-shuttle"></i>
                        <?php endif; ?>
                    </div>
                    <div class="vehicle-info">
                        <h4><?= htmlspecialchars($vehicle['vehicle_name']) ?></h4>
                        <p><i class="fas fa-hashtag"></i> <?= htmlspecialchars($vehicle['vehicle_number']) ?></p>
                    </div>
                </div>

                <div class="date-range-box">
                    <span><?= date('M d, Y', strtotime($start_date)) ?></span>
                    <i class="fas fa-arrow-right"></i>
                    <span><?= date('M d, Y', strtotime($end_date)) ?></span>
                </div>

                <div class="price-box">
                    <div class="price-label">
                        Daily Rate: Rs. <?= number_format($daily_rate, 2) ?> × <?= $days ?> day<?= $days > 1 ? 's' : '' ?>
                    </div>
                    <div class="price-amount">Rs. <?= number_format($total_price, 2) ?></div>
                </div>
            </div>
            
            <div class="modal-body">
                <h4 class="section-title">
                    <i class="fas fa-user-edit"></i>
                    Your Details
                </h4>

                <form id="bookingForm" action="user_booking.php" method="POST">
                    <input type="hidden" name="vehicle_id" value="<?= $vehicle_id ?>">
                    <input type="hidden" name="start_date" value="<?= htmlspecialchars($start_date) ?>">
                    <input type="hidden" name="end_date" value="<?= htmlspecialchars($end_date) ?>">
                    <input type="hidden" name="total_price" value="<?= $total_price ?>">
                    <input type="hidden" name="daily_rate" value="<?= $daily_rate ?>">
                    <input type="hidden" name="days" value="<?= $days ?>">

                    <div class="form-group">
                        <label class="form-label">Full Name <span class="required">*</span></label>
                        <input
                            type="text"
                            name="full_name"
                            id="fullName"
                            class="form-input"
                            placeholder="Enter your full name"
                            value="<?= htmlspecialchars($user_full_name) ?>"
                            required
                        />
                        <div class="error-message" id="fullNameError">Full name is required</div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Contact Number <span class="required">*</span></label>
                        <input
                            type="tel"
                            name="contact_number"
                            id="contactNumber"
                            class="form-input"
                            placeholder="9XXXXXXXXX"
                            required
                        />
                        <div class="error-message" id="contactNumberError">Please enter a valid 10-digit number</div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Alternative Contact Number</label>
                        <input
                            type="tel"
                            name="alternative_number"
                            id="alternativeNumber"
                            class="form-input"
                            placeholder="9XXXXXXXXX (Optional)"
                        />
                        <div class="error-message" id="alternativeNumberError">Please enter a valid 10-digit number</div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Email Address <span class="required">*</span></label>
                        <input
                            type="email"
                            name="email"
                            id="email"
                            class="form-input"
                            placeholder="your.email@example.com"
                            value="<?= htmlspecialchars($user_email) ?>"
                            required
                        />
                        <div class="error-message" id="emailError">Please enter a valid email</div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Additional Notes</label>
                        <textarea
                            name="notes"
                            id="notes"
                            class="form-textarea"
                            placeholder="Any special requests or information (Optional)"
                        ></textarea>
                    </div>

                    <div class="form-actions">
                        <button type="button" class="btn btn-cancel" onclick="closeModal()">
                            <i class="fas fa-times"></i>
                            Cancel
                        </button>
                        <button type="submit" class="btn btn-confirm">
                            <i class="fas fa-check-circle"></i>
                            Confirm Booking
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function closeModal() {
            // Redirect back to dashboard when closing
            window.location.href = 'user_dashboard.php';
        }

        // Form validation
        document.getElementById('bookingForm').addEventListener('submit', function(e) {
            let isValid = true;
            
            // Clear previous errors
            document.querySelectorAll('.error-message').forEach(el => el.classList.remove('active'));
            document.querySelectorAll('.form-input').forEach(el => el.classList.remove('error'));
            
            // Full Name
            const fullName = document.getElementById('fullName');
            if (!fullName.value.trim()) {
                fullName.classList.add('error');
                document.getElementById('fullNameError').classList.add('active');
                isValid = false;
            }
            
            // Contact Number
            const contactNumber = document.getElementById('contactNumber');
            const phoneRegex = /^\d{10}$/;
            if (!phoneRegex.test(contactNumber.value.replace(/\s/g, ''))) {
                contactNumber.classList.add('error');
                document.getElementById('contactNumberError').classList.add('active');
                isValid = false;
            }
            
            // Alternative Number
            const alternativeNumber = document.getElementById('alternativeNumber');
            if (alternativeNumber.value.trim() && !phoneRegex.test(alternativeNumber.value.replace(/\s/g, ''))) {
                alternativeNumber.classList.add('error');
                document.getElementById('alternativeNumberError').classList.add('active');
                isValid = false;
            }
            
            // Email
            const email = document.getElementById('email');
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(email.value.trim())) {
                email.classList.add('error');
                document.getElementById('emailError').classList.add('active');
                isValid = false;
            }
            
            if (!isValid) {
                e.preventDefault();
                // Scroll to first error
                const firstError = document.querySelector('.form-input.error');
                if (firstError) {
                    firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
            }
        });

        // Clear error on input
        document.querySelectorAll('.form-input, .form-textarea').forEach(input => {
            input.addEventListener('input', function() {
                this.classList.remove('error');
                const errorId = this.id + 'Error';
                const errorElement = document.getElementById(errorId);
                if (errorElement) {
                    errorElement.classList.remove('active');
                }
            });
        });
    </script>
</body>
</html>