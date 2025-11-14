<?php
$pageTitle = "Bookings";
include '../db.php';

require '../libs/PHPMailer/src/PHPMailer.php';
require '../libs/PHPMailer/src/SMTP.php';
require '../libs/PHPMailer/src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$search = '';

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

if (isset($_POST['update_status'])) {
    $booking_id = $_POST['booking_id'];
    $status = $_POST['status'];

    $conn->query("UPDATE bookings SET status='$status' WHERE booking_id='$booking_id'");

    if ($status == 'confirmed') {
        $userResult = $conn->query("SELECT name, email FROM users WHERE id = (SELECT user_id FROM bookings WHERE booking_id = '$booking_id')");
        if ($userResult && $userResult->num_rows > 0) {
            $user = $userResult->fetch_assoc();
            sendBookingConfirmationEmail($user['email'], $user['name'], $booking_id);
        }
    }

    header("Location: bookings.php");
    exit();
}

if (isset($_POST['search'])) {
    $search = mysqli_real_escape_string($conn, $_POST['search']);
}

$query = "
    SELECT 
        bookings.booking_id, 
        bookings.user_id, 
        bookings.vehicle_id, 
        bookings.trip_start, 
        bookings.trip_end, 
        bookings.price,
        bookings.status, 
        bookings.user_name,
        users.email AS user_email
    FROM bookings
    LEFT JOIN users ON bookings.user_id = users.id
    WHERE bookings.booking_id LIKE '%$search%'
    ORDER BY bookings.created_at DESC
";

$result = $conn->query($query);

if ($result === false) {
    die("Query Error: " . $conn->error);
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

function pageContent()
{
    global $result, $search;
?>
    <form method="POST" class="mb-4">
        <div class="input-group">
            <input type="text" class="form-control" name="search" value="<?= htmlspecialchars($search); ?>" placeholder="Search Booking ID...">
            <button class="btn btn-primary" type="submit">Search</button>
        </div>
    </form>

    <?php if ($result && $result->num_rows > 0): ?>
    <table class="table table-striped table-hover">
        <thead>
            <tr>
                <th>SN</th>
                <th>Booking ID</th>
                <th>User Name</th>
                <th>Vehicle ID</th>
                <th>Trip Starts</th>
                <th>Trip Ends</th>
                <th>Price</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php $i = 1; while ($row = $result->fetch_assoc()): ?>
            <tr>
                <td><?= $i++; ?></td>
                <td><?= $row['booking_id']; ?></td>
                <td><?= $row['user_name']; ?></td>
                <td><?= $row['vehicle_id']; ?></td>
                <td><?= date('M d, Y h:i A', strtotime($row['trip_start'])); ?></td>
                <td><?= date('M d, Y h:i A', strtotime($row['trip_end'])); ?></td>
                <td>Rs. <?= number_format($row['price'], 2); ?></td>
                <td>
                    <?php
                    $statusClass = '';
                    switch($row['status']) {
                        case 'confirmed': $statusClass = 'badge bg-success'; break;
                        case 'completed': $statusClass = 'badge bg-primary'; break;
                        case 'cancelled': $statusClass = 'badge bg-danger'; break;
                        default: $statusClass = 'badge bg-warning text-dark';
                    }
                    ?>
                    <span class="<?= $statusClass; ?>"><?= ucfirst($row['status']); ?></span>
                </td>
                <td>
                    <button class="btn btn-info btn-sm" data-bs-toggle="modal" data-bs-target="#viewModal<?= $row['booking_id']; ?>" title="View Details">
                        <i class="fas fa-eye"></i>
                    </button>

                    <?php if ($row['status'] == 'pending'): ?>
                    <form method="POST" style="display:inline;">
                        <input type="hidden" name="booking_id" value="<?= $row['booking_id']; ?>">
                        <input type="hidden" name="status" value="confirmed">
                        <button type="submit" name="update_status" class="btn btn-success btn-sm" title="Confirm Booking">
                            <i class="fas fa-check-circle"></i>
                        </button>
                    </form>
                    <?php endif; ?>

                    <button class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#editModal<?= $row['booking_id']; ?>" title="Edit Status">
                        <i class="fas fa-edit"></i>
                    </button>

                    <form method="POST" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this booking?');">
                        <input type="hidden" name="booking_id" value="<?= $row['booking_id']; ?>">
                        <button type="submit" name="delete_booking" class="btn btn-danger btn-sm" title="Delete Booking">
                            <i class="fas fa-trash-alt"></i>
                        </button>
                    </form>
                </td>
            </tr>

            <div class="modal fade" id="viewModal<?= $row['booking_id']; ?>" tabindex="-1">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header bg-info text-white">
                            <h5 class="modal-title">Booking Details</h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <table class="table table-borderless">
                                <tr>
                                    <td><strong>Booking ID:</strong></td>
                                    <td><?= $row['booking_id']; ?></td>
                                </tr>
                                <tr>
                                    <td><strong>User Name:</strong></td>
                                    <td><?= $row['user_name']; ?></td>
                                </tr>
                                <tr>
                                    <td><strong>User Email:</strong></td>
                                    <td><?= $row['user_email']; ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Vehicle ID:</strong></td>
                                    <td><?= $row['vehicle_id']; ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Trip Starts:</strong></td>
                                    <td><?= $row['trip_start']; ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Trip Ends:</strong></td>
                                    <td><?= $row['trip_end']; ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Price:</strong></td>
                                    <td>Rs. <?= number_format($row['price'], 2); ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Status:</strong></td>
                                    <td><span class="badge bg-<?= $row['status'] == 'confirmed' ? 'success' : ($row['status'] == 'completed' ? 'primary' : ($row['status'] == 'cancelled' ? 'danger' : 'warning')); ?>"><?= ucfirst($row['status']); ?></span></td>
                                </tr>
                            </table>
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
                            <div class="modal-header bg-warning">
                                <h5 class="modal-title">Update Booking Status</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <input type="hidden" name="booking_id" value="<?= $row['booking_id']; ?>">
                                <div class="mb-3">
                                    <label class="form-label"><strong>Booking ID:</strong> <?= $row['booking_id']; ?></label>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Status</label>
                                    <select name="status" class="form-select" required>
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
        </tbody>
    </table>
    <?php else: ?>
        <div class="alert alert-info">
            <i class="fas fa-info-circle"></i> No bookings found.
        </div>
    <?php endif; ?>
<?php
}

include 'template.php';
?>