<?php
$pageTitle = "Ways";

include '../db.php';

// Fetch vehicles
$vehicleResult = $conn->query("SELECT * FROM vehicles");
$vehicles = [];
while ($row = $vehicleResult->fetch_assoc()) {
    $vehicles[] = $row;
}

// Handle Add Way
if (isset($_POST['add_way'])) {
    $vehicle_id = $_POST['vehicle_id'];
    $origin = $_POST['origin'];
    $destination = $_POST['destination'];
    $departure_time = $_POST['departure_time'];
    $arrival_time = $_POST['arrival_time'];
    $price = $_POST['price'];

    $conn->query("INSERT INTO ways (vehicle_id, origin, destination, departure_time, arrival_time, price)
                  VALUES ('$vehicle_id','$origin','$destination','$departure_time','$arrival_time','$price')");
    $way_id = $conn->insert_id;

    // Save transit points if any
    if (isset($_POST['transit_point'])) {
        foreach ($_POST['transit_point'] as $i => $point) {
            $duration = $_POST['transit_duration'][$i];
            $time = $_POST['transit_time'][$i];
            $conn->query("INSERT INTO way_transits (way_id, transit_point, transit_duration, transit_time)
                          VALUES ('$way_id','$point','$duration','$time')");
        }
    }

    header("Location: ways.php");
    exit;
}

// Fetch all ways
$waysResult = $conn->query("SELECT w.*, v.vehicle_number 
                            FROM ways w 
                            JOIN vehicles v ON w.vehicle_id = v.id
                            ORDER BY w.id DESC");
?>

<?php function pageContent()
{
    global $waysResult, $vehicles, $conn; ?>


<table class="table table-hover table-striped align-middle">
    <thead class="table-dark">
        <tr>
            <th>ID</th>
            <th>Vehicle</th>
            <th>Origin</th>
            <th>Destination</th>
            <th>Departure</th>
            <th>Arrival</th>
            <th>Price</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        <?php while ($way = $waysResult->fetch_assoc()): ?>
        <tr>
            <td><?= $way['id']; ?></td>
            <td><?= $way['vehicle_number']; ?></td>
            <td><?= $way['origin']; ?></td>
            <td><?= $way['destination']; ?></td>
            <td><?= date("Y-m-d h:i A", strtotime($way['departure_time'])); ?></td>
            <td><?= date("Y-m-d h:i A", strtotime($way['arrival_time'])); ?></td>
            <td>Rs. <?= number_format($way['price'], 2); ?></td>
            <td>
                <!-- Action buttons -->
                <button class="btn btn-info btn-sm" data-bs-toggle="modal"
                    data-bs-target="#viewWayModal<?= $way['id']; ?>">
                    <i class="fas fa-eye"></i>
                </button>
                <button class="btn btn-warning btn-sm" data-bs-toggle="modal"
                    data-bs-target="#editWayModal<?= $way['id']; ?>">
                    <i class="fas fa-edit"></i>
                </button>
                <button class="btn btn-danger btn-sm" data-bs-toggle="modal"
                    data-bs-target="#deleteWayModal<?= $way['id']; ?>">
                    <i class="fas fa-trash"></i>
                </button>
            </td>
        </tr>

        <!-- View Way Modal -->
        <div class="modal fade" id="viewWayModal<?= $way['id']; ?>" tabindex="-1">
            <div class="modal-dialog modal-lg">
                <div class="modal-content p-3">
                    <h5>Way Details</h5>
                    <p><strong>Vehicle:</strong> <?= $way['vehicle_number']; ?></p>
                    <p><strong>Origin:</strong> <?= $way['origin']; ?></p>
                    <p><strong>Destination:</strong> <?= $way['destination']; ?></p>
                    <p><strong>Departure:</strong> <?= date("Y-m-d h:i A", strtotime($way['departure_time'])); ?></p>
                    <p><strong>Arrival:</strong> <?= date("Y-m-d h:i A", strtotime($way['arrival_time'])); ?></p>
                    <p><strong>Price:</strong> Rs. <?= number_format($way['price'], 2); ?></p>
                    <hr>
                    <h6>Transit Points</h6>
                    <?php
                            $tid = $way['id'];
                            $transits = $conn->query("SELECT * FROM way_transits WHERE way_id=$tid");
                            if ($transits->num_rows): ?>
                    <ul>
                        <?php while ($t = $transits->fetch_assoc()): ?>
                        <li><?= $t['transit_point']; ?> - Duration: <?= $t['transit_duration']; ?> min - Time:
                            <?= $t['transit_time']; ?>
                        </li>
                        <?php endwhile; ?>
                    </ul>
                    <?php else: ?>
                    <p>No transit points.</p>
                    <?php endif; ?>
                    <div class="text-end"><button class="btn btn-secondary" data-bs-dismiss="modal">Close</button></div>
                </div>
            </div>
        </div>

        <!-- TODO: Edit & Delete Modals can be added later -->
        <?php endwhile; ?>
    </tbody>
</table>

<!-- Add Way Modal -->
<div class="modal fade" id="addModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <form method="POST" class="modal-content">
            <div class="modal-header">
                <h5>Add Way</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-2">
                    <label>Vehicle</label>
                    <select name="vehicle_id" class="form-control" required>
                        <option value="">Select Vehicle</option>
                        <?php foreach ($vehicles as $v): ?>
                        <option value="<?= $v['id']; ?>"><?= $v['vehicle_number']; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-2"><label>Origin</label><input type="text" name="origin" class="form-control" required>
                </div>
                <div class="mb-2"><label>Destination</label><input type="text" name="destination" class="form-control"
                        required></div>
                <div class="mb-2"><label>Departure Date & Time</label><input type="datetime-local" name="departure_time"
                        class="form-control" required></div>
                <div class="mb-2"><label>Arrival Date & Time</label><input type="datetime-local" name="arrival_time"
                        class="form-control" required></div>
                <div class="mb-2"><label>Price</label><input type="number" step="0.01" name="price" class="form-control"
                        required></div>

                <hr>
                <h6>Transit Points</h6>
                <div id="transitContainer"></div>
                <button type="button" class="btn btn-outline-secondary btn-sm" onclick="addTransit()">+ Add
                    Transit</button>
            </div>
            <div class="modal-footer">
                <button type="submit" name="add_way" class="btn btn-primary">Save Way</button>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            </div>
        </form>
    </div>
</div>

<script>
let transitIndex = 0;

function addTransit() {
    transitIndex++;
    const container = document.getElementById('transitContainer');
    const html = `<div class="mb-2 border p-2 rounded bg-light" id="transitRow${transitIndex}">
            <input type="text" name="transit_point[]" placeholder="Transit Point" class="form-control mb-1" required>
            <input type="number" name="transit_duration[]" placeholder="Duration (min)" class="form-control mb-1" required>
            <input type="time" name="transit_time[]" class="form-control mb-1" required>
            <button type="button" class="btn btn-sm btn-danger" onclick="removeTransit(${transitIndex})">Remove</button>
        </div>`;
    container.insertAdjacentHTML('beforeend', html);
}

function removeTransit(index) {
    document.getElementById('transitRow' + index).remove();
}
</script>

<?php } ?>
<?php include 'template.php'; ?>