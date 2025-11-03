<?php
$pageTitle = "Bookings";

include '../db.php';

// Include PHPMailer classes
require '../libs/PHPMailer/src/PHPMailer.php';
require '../libs/PHPMailer/src/SMTP.php';
require '../libs/PHPMailer/src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Initialize search variable
$search = '';

// Handle Update Status
if (isset($_POST['update_status'])) {
    $booking_id = $_POST['booking_id'];
    $status = $_POST['status'];

    // Update the status in the database
    $conn->query("UPDATE bookings SET status='$status' WHERE booking_id='$booking_id'");

    // If status is confirmed, send an email
    if ($status == 'confirmed') {
        // Fetch user details
        $userResult = $conn->query("SELECT name, email FROM users WHERE id = (SELECT user_id FROM bookings WHERE booking_id = '$booking_id')");
        $user = $userResult->fetch_assoc();

        // Send email confirmation
        sendBookingConfirmationEmail($user['email'], $user['name'], $booking_id);
    }

    header("Location: bookings.php");
    exit();
}

// Search by Booking ID
if (isset($_POST['search'])) {
    $search = mysqli_real_escape_string($conn, $_POST['search']);  // Sanitize input
    $result = $conn->query("
    SELECT bookings.booking_id, bookings.user_id, bookings.vehicle_id, bookings.origin, bookings.destination, 
           bookings.departure_time, bookings.arrival_time, 
           (SELECT ways.price 
            FROM ways 
            WHERE ways.vehicle_id = bookings.vehicle_id 
              AND ways.origin = bookings.origin 
              AND ways.destination = bookings.destination
            LIMIT 1) AS price, 
           bookings.status, 
           users.name AS user_name, users.email AS user_email
    FROM bookings
    LEFT JOIN users ON bookings.user_id = users.id
    WHERE bookings.booking_id LIKE '%$search%'
    ORDER BY bookings.departure_time DESC
");
} else {
    $result = $conn->query("
    SELECT bookings.booking_id, bookings.user_id, bookings.vehicle_id, bookings.origin, bookings.destination, 
           bookings.departure_time, bookings.arrival_time, 
           (SELECT ways.price 
            FROM ways 
            WHERE ways.vehicle_id = bookings.vehicle_id 
              AND ways.origin = bookings.origin 
              AND ways.destination = bookings.destination
            LIMIT 1) AS price, 
           bookings.status, 
           users.name AS user_name, users.email AS user_email
    FROM bookings
    LEFT JOIN users ON bookings.user_id = users.id
    WHERE bookings.booking_id LIKE '%$search%'
    ORDER BY bookings.departure_time DESC
");
}

function pageContent()
{
    global $result, $search, $pageTitle;
?>


    <form method="POST" class="mb-4">
        <div class="input-group">
            <input type="text" class="form-control" name="search" value="<?= htmlspecialchars($search); ?>" placeholder="Search Booking ID..." required>
            <button class="btn btn-primary" type="submit">Search</button>
        </div>
    </form>


    <table class="table table-striped">
        <tr>
            <th>SN</th>
            <th>Booking ID</th>
            <th>User Name</th>
            <th>Vehicle ID</th>
            <th>Origin</th>
            <th>Destination</th>
            <th>Departure Time</th>
            <th>Arrival Time</th>
            <th>Price</th>
            <th>Status</th>
            <th>Actions</th>
        </tr>
        <?php $i = 1;
        while ($row = $result->fetch_assoc()): ?>
            <tr>
                <td><?= $i++; ?></td>
                <td><?= $row['booking_id']; ?></td>
                <td><?= $row['user_name']; ?></td>
                <td><?= $row['vehicle_id']; ?></td>
                <td><?= $row['origin']; ?></td>
                <td><?= $row['destination']; ?></td>
                <td><?= $row['departure_time']; ?></td>
                <td><?= $row['arrival_time']; ?></td>
                <td><?= number_format($row['price'], 2); ?></td> <!-- Price formatted correctly -->
                <td><?= ucfirst($row['status']); ?></td>
                <td>
                    
                    <button class="btn btn-info btn-sm" data-bs-toggle="modal" data-bs-target="#viewModal<?= $row['booking_id']; ?>"><i class="fas fa-eye"></i></button>

                    <!-- Quick Confirm Button -->
                    <form method="POST" style="display:inline;">
                        <input type="hidden" name="booking_id" value="<?= $row['booking_id']; ?>">
                        <input type="hidden" name="status" value="confirmed">
                        <button type="submit" name="update_status" class="btn btn-success btn-sm" title="Confirm Booking">
                            <i class="fas fa-check-circle"></i>
                        </button>
                    </form>

                  
                    <button class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#editModal<?= $row['booking_id']; ?>"><i class="fas fa-edit"></i></button>
                </td>
            </tr>

           
            <div class="modal fade" id="viewModal<?= $row['booking_id']; ?>" tabindex="-1">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Booking Details</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <p><strong>Booking ID:</strong> <?= $row['booking_id']; ?></p>
                            <p><strong>User Name:</strong> <?= $row['user_name']; ?></p>
                            <p><strong>User Email:</strong> <?= $row['user_email']; ?></p>
                            <p><strong>Vehicle ID:</strong> <?= $row['vehicle_id']; ?></p>
                            <p><strong>Origin:</strong> <?= $row['origin']; ?></p>
                            <p><strong>Destination:</strong> <?= $row['destination']; ?></p>
                            <p><strong>Departure Time:</strong> <?= $row['departure_time']; ?></p>
                            <p><strong>Arrival Time:</strong> <?= $row['arrival_time']; ?></p>
                            <p><strong>Price:</strong> <?= number_format($row['price'], 2); ?></p>
                            <p><strong>Status:</strong> <?= ucfirst($row['status']); ?></p>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        </div>
                    </div>
                </div>
            </div>


            <div class="modal fade" id="editModal<?= $row['booking_id']; ?>" tabindex="-1">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <form method="POST" action="bookings.php">
                            <div class="modal-header">
                                <h5 class="modal-title">Update Booking Status</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <input type="hidden" name="booking_id" value="<?= $row['booking_id']; ?>">
                                <div class="mb-2"><label>Status</label>
                                    <select name="status" class="form-control" required>
                                        <option value="pending" <?= $row['status'] == 'pending' ? 'selected' : ''; ?>>Pending</option>
                                        <option value="confirmed" <?= $row['status'] == 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                                        <option value="completed" <?= $row['status'] == 'completed' ? 'selected' : ''; ?>>Completed</option>
                                        <option value="cancelled" <?= $row['status'] == 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                    </select>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="submit" name="update_status" class="btn btn-warning">Update Status</button>
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

        <?php endwhile; ?>
    </table>

<?php
} 





function sendBookingConfirmationEmail($userEmail, $userName, $bookingId)
{
    $mail = new PHPMailer(true);
    try {
       
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'bikashtransportt@gmail.com'; 
        $mail->Password = 'rhhi twul ebnl bwyc';   
        $mail->SMTPSecure = 'tls';
        $mail->Port = 587;

        
        $mail->setFrom('bikashtransportt@gmail.com', 'TMS Booking');
        $mail->addAddress($userEmail, $userName); 

        
        $mail->isHTML(true);
        $mail->Subject = 'Booking Confirmation - ' . $bookingId;
        $mail->Body = "
            <h2>Booking Confirmation</h2>
            <p>Dear $userName,</p>
            <p>Your booking has been confirmed with the following details:</p>
            <p><strong>Booking ID:</strong> $bookingId</p>
            <p>Thank you for booking with us!</p>
        ";

        $mail->send();
    } catch (Exception $e) {
        error_log("Email could not be sent. Mailer Error: {$mail->ErrorInfo}");
    }
}

include 'template.php';
?>