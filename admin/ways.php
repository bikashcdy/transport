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
    $departure_time = date('Y-m-d H:i:s', strtotime($_POST['departure_time']));
    $arrival_time = date('Y-m-d H:i:s', strtotime($_POST['arrival_time']));
    $price = $_POST['price'];

    $stmt = $conn->prepare("INSERT INTO ways (vehicle_id, origin, destination, departure_time, arrival_time, price)
                  VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("issssd", $vehicle_id, $origin, $destination, $departure_time, $arrival_time, $price);
    $stmt->execute();
    $way_id = $stmt->insert_id;
    $stmt->close();

    // Save transit points if any
    if (isset($_POST['transit_point']) && is_array($_POST['transit_point'])) {
        $stmt = $conn->prepare("INSERT INTO way_transits (way_id, transit_point, transit_duration, transit_time)
                          VALUES (?, ?, ?, ?)");
        foreach ($_POST['transit_point'] as $i => $point) {
            if (!empty($point)) {
                $duration = $_POST['transit_duration'][$i];
                $time = $_POST['transit_time'][$i];
                $stmt->bind_param("isis", $way_id, $point, $duration, $time);
                $stmt->execute();
            }
        }
        $stmt->close();
    }

    header("Location: ways.php");
    exit;
}

// Handle Edit Way
if (isset($_POST['edit_way'])) {
    $way_id = $_POST['way_id'];
    $vehicle_id = $_POST['vehicle_id'];
    $origin = $_POST['origin'];
    $destination = $_POST['destination'];
    $departure_time = date('Y-m-d H:i:s', strtotime($_POST['departure_time']));
    $arrival_time = date('Y-m-d H:i:s', strtotime($_POST['arrival_time']));
    $price = $_POST['price'];

    $stmt = $conn->prepare("UPDATE ways SET vehicle_id=?, origin=?, destination=?, 
                           departure_time=?, arrival_time=?, price=? WHERE id=?");
    $stmt->bind_param("issssdi", $vehicle_id, $origin, $destination, $departure_time, $arrival_time, $price, $way_id);
    $stmt->execute();
    $stmt->close();

    $conn->query("DELETE FROM way_transits WHERE way_id=$way_id");
    
    if (isset($_POST['transit_point']) && is_array($_POST['transit_point'])) {
        $stmt = $conn->prepare("INSERT INTO way_transits (way_id, transit_point, transit_duration, transit_time)
                          VALUES (?, ?, ?, ?)");
        foreach ($_POST['transit_point'] as $i => $point) {
            if (!empty($point)) {
                $duration = $_POST['transit_duration'][$i];
                $time = $_POST['transit_time'][$i];
                $stmt->bind_param("isis", $way_id, $point, $duration, $time);
                $stmt->execute();
            }
        }
        $stmt->close();
    }

    header("Location: ways.php");
    exit;
}

// Handle Delete Way
if (isset($_POST['delete_way'])) {
    $way_id = $_POST['way_id'];
    $conn->query("DELETE FROM way_transits WHERE way_id=$way_id");
    
    $stmt = $conn->prepare("DELETE FROM ways WHERE id=?");
    $stmt->bind_param("i", $way_id);
    $stmt->execute();
    $stmt->close();

    header("Location: ways.php");
    exit;
}

// Fetch all ways
$waysResult = $conn->query("SELECT w.*, v.vehicle_number, v.vehicle_name 
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
            <th>Vehicle Name</th>
            <th>Vehicle Number</th>
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
            <td><?= $way['vehicle_name']; ?></td>
            <td><?= $way['vehicle_number']; ?></td>
            <td><?= $way['origin']; ?></td>
            <td><?= $way['destination']; ?></td>
            <td><?= date("Y-m-d h:i A", strtotime($way['departure_time'])); ?></td>
            <td><?= date("Y-m-d h:i A", strtotime($way['arrival_time'])); ?></td>
            <td>Rs. <?= number_format($way['price'], 2); ?></td>
            <td>
                <button class="btn btn-info btn-sm" data-bs-toggle="modal"
                    data-bs-target="#viewWayModal<?= $way['id']; ?>"><i class="fas fa-eye"></i></button>
                <button class="btn btn-warning btn-sm" data-bs-toggle="modal"
                    data-bs-target="#editWayModal<?= $way['id']; ?>"><i class="fas fa-edit"></i></button>
                <button class="btn btn-danger btn-sm" data-bs-toggle="modal"
                    data-bs-target="#deleteWayModal<?= $way['id']; ?>"><i class="fas fa-trash"></i></button>
            </td>
        </tr>

        <!-- View Modal -->
        <div class="modal fade" id="viewWayModal<?= $way['id']; ?>" tabindex="-1">
            <div class="modal-dialog modal-lg">
                <div class="modal-content p-3">
                    <h5>Way Details</h5>
                    <p><strong>Vehicle:</strong> <?= $way['vehicle_name']; ?> (<?= $way['vehicle_number']; ?>)</p>
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
                            <?= $t['transit_time']; ?></li>
                        <?php endwhile; ?>
                    </ul>
                    <?php else: ?>
                    <p>No transit points.</p>
                    <?php endif; ?>
                    <div class="text-end"><button class="btn btn-secondary" data-bs-dismiss="modal">Close</button></div>
                </div>
            </div>
        </div>

        <!-- Edit Modal -->
        <div class="modal fade" id="editWayModal<?= $way['id']; ?>" tabindex="-1">
            <div class="modal-dialog modal-lg">
                <form method="POST" class="modal-content">
                    <input type="hidden" name="way_id" value="<?= $way['id']; ?>">
                    <div class="modal-header">
                        <h5>Edit Way</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-2">
                            <label>Vehicle Name</label>
                            <select name="vehicle_id" id="vehicleSelect<?= $way['id']; ?>" class="form-control" required onchange="fillEditVehicleDetails(<?= $way['id']; ?>)">
                                <option value="">Select Vehicle</option>
                                <?php foreach ($vehicles as $v): ?>
                                <option value="<?= $v['id']; ?>" 
                                        data-vehicle-number="<?= htmlspecialchars($v['vehicle_number']); ?>"
                                        <?= $v['id'] == $way['vehicle_id'] ? 'selected' : ''; ?>>
                                    <?= htmlspecialchars($v['vehicle_name']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-2">
                            <label>Vehicle Number</label>
                            <input type="text" id="vehicleNumber<?= $way['id']; ?>" 
                                   class="form-control" 
                                   value="<?= htmlspecialchars($way['vehicle_number']); ?>" readonly>
                        </div>
                        <div class="mb-2">
                            <label>Origin</label>
                            <input type="text" name="origin" class="form-control" value="<?= $way['origin']; ?>" required>
                        </div>
                        <div class="mb-2">
                            <label>Destination</label>
                            <input type="text" name="destination" class="form-control" value="<?= $way['destination']; ?>" required>
                        </div>
                        <div class="mb-2">
                            <label>Departure</label>
                            <input type="datetime-local" name="departure_time" class="form-control" 
                                   value="<?= date('Y-m-d\TH:i', strtotime($way['departure_time'])); ?>" required>
                        </div>
                        <div class="mb-2">
                            <label>Arrival</label>
                            <input type="datetime-local" name="arrival_time" class="form-control" 
                                   value="<?= date('Y-m-d\TH:i', strtotime($way['arrival_time'])); ?>" required>
                        </div>
                        <div class="mb-2">
                            <label>Price</label>
                            <input type="number" step="0.01" name="price" class="form-control" value="<?= $way['price']; ?>" required>
                        </div>
                        <hr>
                        <h6>Transit Points</h6>
                        <div id="editTransitContainer<?= $way['id']; ?>">
                            <?php
                            $transits = $conn->query("SELECT * FROM way_transits WHERE way_id={$way['id']}");
                            while ($t = $transits->fetch_assoc()): ?>
                            <div class="mb-2 border p-2 rounded bg-light">
                                <input type="text" name="transit_point[]" placeholder="Transit Point" 
                                       class="form-control mb-1" value="<?= $t['transit_point']; ?>" required>
                                <input type="number" name="transit_duration[]" placeholder="Duration (min)" 
                                       class="form-control mb-1" value="<?= $t['transit_duration']; ?>" required>
                                <input type="time" name="transit_time[]" class="form-control mb-1" 
                                       value="<?= $t['transit_time']; ?>" required>
                                <button type="button" class="btn btn-sm btn-danger" 
                                        onclick="this.parentElement.remove()">Remove</button>
                            </div>
                            <?php endwhile; ?>
                        </div>
                        <button type="button" class="btn btn-outline-secondary btn-sm" 
                                onclick="addEditTransit(<?= $way['id']; ?>)">+ Add Transit</button>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" name="edit_way" class="btn btn-warning">Update Way</button>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Delete Modal -->
        <div class="modal fade" id="deleteWayModal<?= $way['id']; ?>" tabindex="-1">
            <div class="modal-dialog">
                <form method="POST" class="modal-content">
                    <input type="hidden" name="way_id" value="<?= $way['id']; ?>">
                    <div class="modal-header bg-danger text-white">
                        <h5>Delete Way</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <p>Are you sure you want to delete this way?</p>
                        <div class="alert alert-warning">
                            <strong>Vehicle:</strong> <?= $way['vehicle_name']; ?> (<?= $way['vehicle_number']; ?>)<br>
                            <strong>Route:</strong> <?= $way['origin']; ?> → <?= $way['destination']; ?><br>
                            <strong>Price:</strong> Rs. <?= number_format($way['price'], 2); ?>
                        </div>
                        <p class="text-danger"><small><i class="fas fa-exclamation-triangle"></i> This action cannot be undone. All transit points will also be deleted.</small></p>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" name="delete_way" class="btn btn-danger">
                            <i class="fas fa-trash"></i> Delete
                        </button>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    </div>
                </form>
            </div>
        </div>

        <?php endwhile; ?>
    </tbody>
</table>

<!-- Add Modal -->
<div class="modal fade" id="addModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <form method="POST" class="modal-content">
            <div class="modal-header">
                <h5>Add Way</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-2">
                    <label>Vehicle Name</label>
                    <select name="vehicle_id" id="vehicleSelect" class="form-control" required onchange="fillVehicleDetails()">
                        <option value="">Select Vehicle</option>
                        <?php foreach ($vehicles as $v): ?>
                        <option value="<?= $v['id']; ?>" 
                                data-vehicle-number="<?= htmlspecialchars($v['vehicle_number']); ?>">
                            <?= htmlspecialchars($v['vehicle_name']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-2">
                    <label>Vehicle Number</label>
                    <input type="text" id="vehicleNumber" class="form-control" readonly>
                </div>
                <div class="mb-2"><label>Origin</label><input type="text" name="origin" class="form-control" required></div>
                <div class="mb-2"><label>Destination</label><input type="text" name="destination" class="form-control" required></div>
                <div class="mb-2"><label>Departure</label><input type="datetime-local" name="departure_time" class="form-control" required></div>
                <div class="mb-2"><label>Arrival</label><input type="datetime-local" name="arrival_time" class="form-control" required></div>
                <div class="mb-2"><label>Price</label><input type="number" step="0.01" name="price" class="form-control" required></div>
                <hr>
                <h6>Transit Points</h6>
                <div id="transitContainer"></div>
                <button type="button" class="btn btn-outline-secondary btn-sm" onclick="addTransit()">+ Add Transit</button>
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

function addEditTransit(wayId) {
    const container = document.getElementById('editTransitContainer' + wayId);
    const html = `<div class="mb-2 border p-2 rounded bg-light">
            <input type="text" name="transit_point[]" placeholder="Transit Point" class="form-control mb-1" required>
            <input type="number" name="transit_duration[]" placeholder="Duration (min)" class="form-control mb-1" required>
            <input type="time" name="transit_time[]" class="form-control mb-1" required>
            <button type="button" class="btn btn-sm btn-danger" onclick="this.parentElement.remove()">Remove</button>
        </div>`;
    container.insertAdjacentHTML('beforeend', html);
}

// Auto-fill for add modal
function fillVehicleDetails() {
    const select = document.getElementById('vehicleSelect');
    const selectedOption = select.options[select.selectedIndex];
    const vehicleNumber = selectedOption.getAttribute('data-vehicle-number') || '';
    document.getElementById('vehicleNumber').value = vehicleNumber;
}

// Auto-fill for edit modal
function fillEditVehicleDetails(wayId) {
    const select = document.getElementById('vehicleSelect' + wayId);
    const selectedOption = select.options[select.selectedIndex];
    const vehicleNumber = selectedOption.getAttribute('data-vehicle-number') || '';
    document.getElementById('vehicleNumber' + wayId).value = vehicleNumber;
}
</script>

<?php } ?>
<?php include 'template.php'; ?>
