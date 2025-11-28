<?php
ob_start(); // Start output buffering

$pageTitle = "Bookings";
include '../db.php';

ini_set('max_execution_time', 120); // 2 minutes

require '../libs/PHPMailer/src/PHPMailer.php';
require '../libs/PHPMailer/src/SMTP.php';
require '../libs/PHPMailer/src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$search = '';

// Handle booking deletion
if (isset($_POST['delete_booking'])) {
    $booking_id = $_POST['booking_id'];
    $delete = $conn->prepare("DELETE FROM bookings WHERE booking_id = ?");
    $delete->bind_param("s", $booking_id);
    if ($delete->execute()) {
        echo "<script>alert('Booking deleted successfully!'); window.location='bookings.php';</script>";
        exit;
    } else {
        echo "<script>alert('Error deleting booking.'); window.location='bookings.php';</script>";
        exit;
    }
}
if (isset($_GET['term'])) {
    $term = $_GET['term'];

    $stmt = $conn->prepare("
        SELECT DISTINCT booking_id, user_name 
        FROM bookings 
        WHERE booking_id LIKE ? OR user_name LIKE ? 
        LIMIT 10
    ");
    $searchTerm = "%" . $term . "%";
    $stmt->bind_param("ss", $searchTerm, $searchTerm);
    $stmt->execute();
    $result = $stmt->get_result();

    $suggestions = [];
    while ($row = $result->fetch_assoc()) {
        // Suggest both booking ID and name
        if (!empty($row['booking_id'])) {
            $suggestions[] = $row['booking_id'];
        }
        if (!empty($row['user_name'])) {
            $suggestions[] = $row['user_name'];
        }
    }

    echo json_encode($suggestions);
    exit;
}

// Handle status update
if (isset($_POST['update_status'])) {
    $booking_id = $_POST['booking_id'];
    $status = $_POST['status'];
    
    // Validate status value
    $allowed_statuses = ['pending', 'confirmed', 'completed', 'cancelled'];
    if (!in_array($status, $allowed_statuses)) {
        echo "<script>alert('Invalid status value.'); window.location='bookings.php';</script>";
        exit;
    }

    // Use prepared statement - Update status first
    $stmt = $conn->prepare("UPDATE bookings SET status = ? WHERE booking_id = ?");
    $stmt->bind_param("ss", $status, $booking_id);
    
    if ($stmt->execute()) {
        $stmt->close();
        
        // Try to send email if status is confirmed, but don't block the update
        if ($status == 'confirmed') {
            // Get user details - check both booking email and user email
            $userStmt = $conn->prepare("
                SELECT 
                    COALESCE(b.email, u.email) as email,
                    COALESCE(b.user_name, u.name) as name
                FROM bookings b
                LEFT JOIN users u ON b.user_id = u.id
                WHERE b.booking_id = ?
            ");
            $userStmt->bind_param("s", $booking_id);
            $userStmt->execute();
            $userResult = $userStmt->get_result();
            
            if ($userResult && $userResult->num_rows > 0) {
                $user = $userResult->fetch_assoc();
                
                // Only attempt to send email if we have a valid email
                if (!empty($user['email']) && filter_var($user['email'], FILTER_VALIDATE_EMAIL)) {
                    // Send email asynchronously - don't wait for it
                    @sendBookingConfirmationEmail($user['email'], $user['name'], $booking_id);
                }
            }
            $userStmt->close();
        }
        
        echo "<script>alert('Booking status updated successfully!'); window.location='bookings.php';</script>";
        exit();
    } else {
        echo "<script>alert('Error updating booking status: " . $conn->error . "'); window.location='bookings.php';</script>";
        exit;
    }
}

// Handle search
if (isset($_POST['search'])) {
    $search = trim($_POST['search']);
}

// Build query with prepared statement for better security
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
        bookings.contact_number,
        bookings.alternative_number,
        bookings.email as booking_email,
        bookings.notes,
        users.email AS user_email,
        users.name AS registered_user_name,
        vehicles.vehicle_name,
        vehicles.vehicle_number
    FROM bookings
    LEFT JOIN users ON bookings.user_id = users.id
    LEFT JOIN vehicles ON bookings.vehicle_id = vehicles.id
";

if (!empty($search)) {
    $query .= " WHERE bookings.booking_id LIKE ? 
                OR bookings.user_name LIKE ? 
                OR users.name LIKE ?";
}

if (!empty($search)) {
    $stmt = $conn->prepare($query);
    $searchParam = "%$search%";
    $stmt->bind_param("sss", $searchParam, $searchParam, $searchParam);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $result = $conn->query($query);
}

if ($result === false) {
    die("Query Error: " . $conn->error);
}

// Store results in array to prevent multiple iterations
$bookings = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $bookings[] = $row;
    }
}

function sendBookingConfirmationEmail($userEmail, $userName, $bookingId)
{
    // Set a timeout to prevent hanging
    ini_set('max_execution_time', 10);
    
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'bikashtransportt@gmail.com';
        $mail->Password = 'nodc knlq hxgf inul';
        $mail->SMTPSecure = 'tls';
        $mail->Port = 587;
    $mail->Timeout = 60;         // 60 seconds for SMTP connection
$mail->SMTPKeepAlive = false; // optional, prevents hanging


        
        // Set timeout for SMTP connection
        $mail->Timeout = 5;
        $mail->SMTPKeepAlive = false;

        $mail->setFrom('bikashtransportt@gmail.com', 'BookingNepal');
        $mail->addAddress($userEmail, $userName);

        $mail->isHTML(true);
        $mail->Subject = 'Booking Confirmation - ' . htmlspecialchars($bookingId);
        $mail->Body = "
            <h2>Booking Confirmation</h2>
            <p>Dear " . htmlspecialchars($userName) . ",</p>
            <p>Your booking has been confirmed with the following details:</p>
            <p><strong>Booking ID:</strong> " . htmlspecialchars($bookingId) . "</p>
            <p>Thank you for booking with us!</p>
        ";

        $mail->send();
        return true;
    } catch (Exception $e) {
        // Log the error but don't stop execution
        error_log("Email could not be sent. Mailer Error: {$mail->ErrorInfo}");
        return false;
    }
}

function pageContent()
{
    global $bookings, $search;
?>
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <!-- Search Form -->
                <div class="mb-4">
                    <form method="POST" class="mb-3">
                        <div class="input-group" style="max-width: 500px;">
                            <input type="text" class="form-control" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search Booking ID...">
                            <button class="btn btn-primary" type="submit">Search</button>
                        </div>
                    </form>
                </div>

                <?php if (!empty($bookings)): ?>
                <!-- Table Section -->
                <div class="table-responsive" style="clear: both; display: block; width: 100%;">
                    <table class="table table-striped table-hover" style="width: 100%; table-layout: auto;">
                        <thead>
                            <tr>
                                <th>Booking ID</th>
                                <th>User Name</th>
                                <th>Vehicle ID</th>
                                <th>Vehicle Name</th>
                                <th>Trip Start</th>
                                <th>Trip End</th>
                                <th>Duration</th>
                                <th>Price</th>
                                <th>Status</th>
                                <th>Notes</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
            <?php 
            foreach ($bookings as $row) {
                // Use user_name from bookings table
                $displayName = !empty($row['user_name']) ? $row['user_name'] : 
                              (!empty($row['registered_user_name']) ? $row['registered_user_name'] : 'N/A');
                
                // Calculate duration
                $start = new DateTime($row['trip_start']);
                $end = new DateTime($row['trip_end']);
                $diff = $start->diff($end);
                $duration = $diff->days . ' day' . ($diff->days != 1 ? 's' : '');
                
                // Vehicle name
                $vehicleName = !empty($row['vehicle_name']) ? $row['vehicle_name'] : 'N/A';
                
                // Email
                $displayEmail = !empty($row['booking_email']) ? $row['booking_email'] : 
                               (!empty($row['user_email']) ? $row['user_email'] : 'N/A');
                
                // Status badge color
                $statusColors = [
                    'pending' => 'warning',
                    'confirmed' => 'success',
                    'completed' => 'info',
                    'cancelled' => 'danger'
                ];
                $statusColor = isset($statusColors[$row['status']]) ? $statusColors[$row['status']] : 'secondary';
            ?>
                <tr>
                    <td><?php echo htmlspecialchars($row['booking_id']); ?></td>
                    <td><?php echo htmlspecialchars($displayName); ?></td>
                    <td><?php echo htmlspecialchars($row['vehicle_id']); ?></td>
                    <td><?php echo htmlspecialchars($vehicleName); ?></td>
                    <td><?php echo date('M d, Y', strtotime($row['trip_start'])); ?></td>
                    <td><?php echo date('M d, Y', strtotime($row['trip_end'])); ?></td>
                    <td><?php echo $duration; ?></td>
                    <td>Rs. <?php echo number_format($row['price'], 2); ?></td>
                    <td><span class="badge bg-<?php echo $statusColor; ?>"><?php echo ucfirst($row['status']); ?></span></td>
                    <td><?php echo !empty($row['notes']) ? htmlspecialchars(substr($row['notes'], 0, 50)) . (strlen($row['notes']) > 50 ? '...' : '') : 'N/A'; ?></td>
                    <td class="text-nowrap">
                        <!-- View Button -->
                        <button class="btn btn-info btn-sm" onclick="viewBooking('<?php echo htmlspecialchars($row['booking_id'], ENT_QUOTES); ?>')" title="View Details">
                            <i class="fas fa-eye"></i>
                        </button>
                        
                        <!-- Edit/Update Status Button -->
                        <button class="btn btn-warning btn-sm" onclick="updateBooking('<?php echo htmlspecialchars($row['booking_id'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($row['status'], ENT_QUOTES); ?>')" title="Update Status">
                            <i class="fas fa-edit"></i>
                        </button>
                        
                        <!-- Delete Button -->
                        <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this booking?');">
                            <input type="hidden" name="booking_id" value="<?php echo htmlspecialchars($row['booking_id']); ?>">
                            <button type="submit" name="delete_booking" class="btn btn-danger btn-sm" title="Delete">
                                <i class="fas fa-trash"></i>
                            </button>
                        </form>
                    </td>
                </tr>
            <?php } // End foreach ?>
            </tbody>
        </table>
    </div>

                <?php else: ?>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i> No bookings found.
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- View Booking Modal -->
    <div class="modal fade" id="viewModal" tabindex="-1" aria-labelledby="viewModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="viewModalLabel">Booking Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="viewModalBody">
                    <!-- Content will be loaded here -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Update Status Modal -->
    <div class="modal fade" id="updateModal" tabindex="-1" aria-labelledby="updateModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="updateModalLabel">Update Booking Status</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="booking_id" id="update_booking_id">
                        <div class="mb-3">
                            <label for="status" class="form-label">Status</label>
                            <select class="form-select" name="status" id="status" required>
                                <option value="pending">Pending</option>
                                <option value="confirmed">Confirmed</option>
                                <option value="completed">Completed</option>
                                <option value="cancelled">Cancelled</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" name="update_status" class="btn btn-primary">Update Status</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

 <!-- jQuery UI for Autocomplete -->
    <link rel="stylesheet" href="https://code.jquery.com/ui/1.13.2/themes/base/jquery-ui.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://code.jquery.com/ui/1.13.2/jquery-ui.js"></script>
    
    <!-- Bootstrap 5 JS (if not already loaded in template.php) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>

    <script>
    // Booking data from PHP
    const bookingsData = <?php echo json_encode($bookings); ?>;

    function viewBooking(bookingId) {
        console.log('viewBooking called with ID:', bookingId);
        
        const booking = bookingsData.find(b => b.booking_id === bookingId);
        
        if (booking) {
            const displayName = booking.user_name || booking.registered_user_name || 'N/A';
            const displayEmail = booking.booking_email || booking.user_email || 'N/A';
            const vehicleName = booking.vehicle_name || 'N/A';
            const vehicleNumber = booking.vehicle_number || 'N/A';
            
            const content = `
                <div class="row">
                    <div class="col-md-6">
                        <p><strong>Booking ID:</strong> ${booking.booking_id}</p>
                        <p><strong>User Name:</strong> ${displayName}</p>
                        <p><strong>Email:</strong> ${displayEmail}</p>
                        <p><strong>Contact:</strong> ${booking.contact_number || 'N/A'}</p>
                        <p><strong>Alternative Contact:</strong> ${booking.alternative_number || 'N/A'}</p>
                    </div>
                    <div class="col-md-6">
                        <p><strong>Vehicle:</strong> ${vehicleName}</p>
                        <p><strong>Vehicle Number:</strong> ${vehicleNumber}</p>
                        <p><strong>Trip Start:</strong> ${new Date(booking.trip_start).toLocaleDateString()}</p>
                        <p><strong>Trip End:</strong> ${new Date(booking.trip_end).toLocaleDateString()}</p>
                        <p><strong>Price:</strong> Rs. ${parseFloat(booking.price).toLocaleString()}</p>
                        <p><strong>Status:</strong> <span class="badge bg-primary">${booking.status}</span></p>
                    </div>
                    <div class="col-12 mt-3">
                        <p><strong>Notes:</strong></p>
                        <p>${booking.notes || 'No notes available'}</p>
                    </div>
                </div>
            `;
            
            document.getElementById('viewModalBody').innerHTML = content;
            
            // Universal modal opener - works with Bootstrap 3, 4, or 5
            const modalElement = document.getElementById('viewModal');
            
            if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
                // Bootstrap 5
                const modal = new bootstrap.Modal(modalElement);
                modal.show();
            } else if (typeof $ !== 'undefined' && $.fn.modal) {
                // Bootstrap 3/4 with jQuery
                $('#viewModal').modal('show');
            } else {
                // Manual fallback
                modalElement.classList.add('show');
                modalElement.style.display = 'block';
                document.body.classList.add('modal-open');
                
                // Create backdrop
                const backdrop = document.createElement('div');
                backdrop.className = 'modal-backdrop fade show';
                backdrop.id = 'viewModalBackdrop';
                document.body.appendChild(backdrop);
            }
        } else {
            alert('Booking not found!');
        }
    }

    function updateBooking(bookingId, currentStatus) {
        console.log('updateBooking called:', bookingId, currentStatus);
        
        document.getElementById('update_booking_id').value = bookingId;
        document.getElementById('status').value = currentStatus;
        
        // Universal modal opener
        const modalElement = document.getElementById('updateModal');
        
        if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
            // Bootstrap 5
            const modal = new bootstrap.Modal(modalElement);
            modal.show();
        } else if (typeof $ !== 'undefined' && $.fn.modal) {
            // Bootstrap 3/4 with jQuery
            $('#updateModal').modal('show');
        } else {
            // Manual fallback
            modalElement.classList.add('show');
            modalElement.style.display = 'block';
            document.body.classList.add('modal-open');
            
            // Create backdrop
            const backdrop = document.createElement('div');
            backdrop.className = 'modal-backdrop fade show';
            backdrop.id = 'updateModalBackdrop';
            document.body.appendChild(backdrop);
        }
    }

    // Close modal function for manual fallback
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('btn-close') || 
            e.target.classList.contains('btn-secondary') ||
            e.target.classList.contains('modal-backdrop')) {
            
            // Close all modals
            document.querySelectorAll('.modal').forEach(modal => {
                modal.classList.remove('show');
                modal.style.display = 'none';
            });
            
            // Remove backdrops
            document.querySelectorAll('.modal-backdrop').forEach(backdrop => {
                backdrop.remove();
            });
            
            document.body.classList.remove('modal-open');
        }
    });

    // jQuery Autocomplete
    $(function() {
        $("input[name='search']").autocomplete({
            source: function(request, response) {
                $.ajax({
                    url: "bookings.php",
                    dataType: "json",
                    data: { term: request.term },
                    success: function(data) {
                        response(data);
                    }
                });
            },
            minLength: 2
        });
    });
    </script>
<?php
}

include 'template.php';
?>