<?php
$pageTitle = "Bookings";
include '../db.php';

// Include PHPMailer classes
require '../libs/PHPMailer/src/PHPMailer.php';
require '../libs/PHPMailer/src/SMTP.php';
require '../libs/PHPMailer/src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$search = '';

// =========================
// 🧩 HANDLE DELETE BOOKING
// =========================
if (isset($_POST['delete_booking'])) {
    $booking_id = intval($_POST['booking_id']);
    $delete = $conn->prepare("DELETE FROM bookings WHERE booking_id = ?");
    $delete->bind_param("i", $booking_id);
    if ($delete->execute()) {
        echo "<script>alert('Booking deleted successfully!'); window.location='bookings.php';</script>";
        exit;
    } else {
        echo "<script>alert('Error deleting booking.'); window.location='bookings.php';</script>";
        exit;
    }
}

// =========================
// 🧩 HANDLE STATUS UPDATE
// =========================
if (isset($_POST['update_status'])) {
    $booking_id = $_POST['booking_id'];
    $status = $_POST['status'];

    $conn->query("UPDATE bookings SET status='$status' WHERE booking_id='$booking_id'");

    if ($status == 'confirmed') {
        $userResult = $conn->query("SELECT name, email FROM users WHERE id = (SELECT user_id FROM bookings WHERE booking_id = '$booking_id')");
        $user = $userResult->fetch_assoc();
        sendBookingConfirmationEmail($user['email'], $user['name'], $booking_id);
    }

    header("Location: bookings.php");
    exit();
}

// =========================
// 🔍 SEARCH HANDLING
// =========================
if (isset($_POST['search'])) {
    $search = mysqli_real_escape_string($conn, $_POST['search']);
}

$query = "
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
";
$result = $conn->query($query);

// =========================
// 📧 EMAIL FUNCTION
// =========================
function sendBookingConfirmationEmail($userEmail, $userName, $bookingId)
{
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'bikashtransportt@gmail.com';
        $mail->Password = 'rhhi twul ebnl bwyc'; // App Password
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

// =========================
// 🖥️ PAGE CONTENT
// =========================
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
                <td><?= number_format($row['price'], 2); ?></td>
                <td><?= ucfirst($row['status']); ?></td>
                <td>
                    <!-- View -->
                    <button class="btn btn-info btn-sm" data-bs-toggle="modal" data-bs-target="#viewModal<?= $row['booking_id']; ?>"><i class="fas fa-eye"></i></button>

                    <!-- Confirm -->
                    <form method="POST" style="display:inline;">
                        <input type="hidden" name="booking_id" value="<?= $row['booking_id']; ?>">
                        <input type="hidden" name="status" value="confirmed">
                        <button type="submit" name="update_status" class="btn btn-success btn-sm" title="Confirm Booking">
                            <i class="fas fa-check-circle"></i>
                        </button>
                    </form>

                    <!-- Edit -->
                    <button class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#editModal<?= $row['booking_id']; ?>"><i class="fas fa-edit"></i></button>

                    <!-- Delete -->
                    <form method="POST" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this booking?');">
                        <input type="hidden" name="booking_id" value="<?= $row['booking_id']; ?>">
                        <button type="submit" name="delete_booking" class="btn btn-danger btn-sm" title="Delete Booking">
                            <i class="fas fa-trash-alt"></i>
                        </button>
                    </form>
                </td>
            </tr>

            <!-- View Modal -->
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

            <!-- Edit Modal -->
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

include 'template.php';
?>
