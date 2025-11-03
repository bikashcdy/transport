<?php
$pageTitle = "Vehicles";
include '../db.php';

// ======================= ADD VEHICLE =======================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add'])) {
    $vehicle_number = trim($_POST['vehicle_number']);
    $vehicle_type_id = intval($_POST['vehicle_type_id']);
    $capacity = intval($_POST['capacity']);
    $status = trim($_POST['status']);

    $stmt = $conn->prepare("INSERT INTO vehicles (vehicle_number, vehicle_type_id, capacity, status) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("siis", $vehicle_number, $vehicle_type_id, $capacity, $status);
    $stmt->execute();
    $stmt->close();

    header("Location: vehicles.php");
    exit;
}

// ======================= UPDATE VEHICLE =======================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update'])) {
    $id = intval($_POST['id']);
    $vehicle_number = trim($_POST['vehicle_number']);
    $vehicle_type_id = intval($_POST['vehicle_type_id']);
    $capacity = intval($_POST['capacity']);
    $status = trim($_POST['status']);

    $stmt = $conn->prepare("UPDATE vehicles SET vehicle_number=?, vehicle_type_id=?, capacity=?, status=? WHERE id=?");
    $stmt->bind_param("siisi", $vehicle_number, $vehicle_type_id, $capacity, $status, $id);
    $stmt->execute();
    $stmt->close();

    header("Location: vehicles.php");
    exit;
}

// ======================= DELETE VEHICLE =======================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete'])) {
    $id = intval($_POST['id']);

    // Delete related ways first if not using ON DELETE CASCADE
    $conn->query("DELETE FROM ways WHERE vehicle_id = $id");

    // Delete vehicle
    $stmt = $conn->prepare("DELETE FROM vehicles WHERE id = ?");
    $stmt->bind_param("i", $id);
    if (!$stmt->execute()) {
        die("Error deleting vehicle: " . $stmt->error);
    }
    $stmt->close();

    header("Location: vehicles.php");
    exit;
}

// ======================= FETCH DATA =======================
$typesResult = $conn->query("SELECT * FROM vehicle_types");
$vehicleTypes = [];
while ($row = $typesResult->fetch_assoc()) {
    $vehicleTypes[] = $row;
}

$result = $conn->query("SELECT v.*, t.type_name FROM vehicles v JOIN vehicle_types t ON v.vehicle_type_id = t.id");

// ======================= PAGE CONTENT =======================
function pageContent()
{
    global $result, $vehicleTypes;
?>
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="mb-0">Vehicles</h4>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addModal"><i class="fas fa-plus"></i> Add Vehicle</button>
    </div>

    <table class="table table-striped align-middle">
        <thead class="table-dark">
            <tr>
                <th>SN</th>
                <th>Vehicle Number</th>
                <th>Type</th>
                <th>Capacity</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php $i = 1;
            while ($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?= $i++; ?></td>
                    <td><?= htmlspecialchars($row['vehicle_number']); ?></td>
                    <td><?= htmlspecialchars($row['type_name']); ?></td>
                    <td><?= intval($row['capacity']); ?></td>
                    <td><span class="badge bg-<?= $row['status'] == 'available' ? 'success' : ($row['status'] == 'maintenance' ? 'warning' : 'secondary'); ?>">
                            <?= ucfirst($row['status']); ?></span></td>
                    <td>
                        <button class="btn btn-info btn-sm" data-bs-toggle="modal" data-bs-target="#viewModal<?= $row['id']; ?>"><i class="fas fa-eye"></i></button>
                        <button class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#editModal<?= $row['id']; ?>"><i class="fas fa-edit"></i></button>
                        <button class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#deleteModal<?= $row['id']; ?>"><i class="fas fa-trash"></i></button>
                    </td>
                </tr>

                <!-- View Modal -->
                <div class="modal fade" id="viewModal<?= $row['id']; ?>" tabindex="-1">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5>View Vehicle</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <p><strong>Vehicle Number:</strong> <?= htmlspecialchars($row['vehicle_number']); ?></p>
                                <p><strong>Type:</strong> <?= htmlspecialchars($row['type_name']); ?></p>
                                <p><strong>Capacity:</strong> <?= intval($row['capacity']); ?></p>
                                <p><strong>Status:</strong> <?= ucfirst($row['status']); ?></p>
                            </div>
                            <div class="modal-footer">
                                <button class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Edit Modal -->
                <div class="modal fade" id="editModal<?= $row['id']; ?>" tabindex="-1">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <form method="POST">
                                <div class="modal-header">
                                    <h5>Edit Vehicle</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body">
                                    <input type="hidden" name="id" value="<?= $row['id']; ?>">
                                    <label>Vehicle Number</label>
                                    <input type="text" name="vehicle_number" value="<?= htmlspecialchars($row['vehicle_number']); ?>" class="form-control mb-2" required>

                                    <label>Vehicle Type</label>
                                    <select name="vehicle_type_id" class="form-control mb-2" required>
                                        <?php foreach ($vehicleTypes as $type): ?>
                                            <option value="<?= $type['id']; ?>" <?= $row['vehicle_type_id'] == $type['id'] ? 'selected' : ''; ?>><?= htmlspecialchars($type['type_name']); ?></option>
                                        <?php endforeach; ?>
                                    </select>

                                    <label>Capacity</label>
                                    <input type="number" name="capacity" value="<?= intval($row['capacity']); ?>" class="form-control mb-2" required>

                                    <label>Status</label>
                                    <select name="status" class="form-control">
                                        <option value="available" <?= $row['status'] == 'available' ? 'selected' : ''; ?>>Available</option>
                                        <option value="maintenance" <?= $row['status'] == 'maintenance' ? 'selected' : ''; ?>>Maintenance</option>
                                        <option value="unavailable" <?= $row['status'] == 'unavailable' ? 'selected' : ''; ?>>Unavailable</option>
                                    </select>
                                </div>
                                <div class="modal-footer">
                                    <button type="submit" name="update" class="btn btn-warning">Update</button>
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Delete Modal -->
                <div class="modal fade" id="deleteModal<?= $row['id']; ?>" tabindex="-1">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <form method="POST">
                                <div class="modal-header bg-danger text-white">
                                    <h5>Delete Vehicle</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body">
                                    <p>Are you sure you want to delete <strong><?= htmlspecialchars($row['vehicle_number']); ?></strong>?</p>
                                    <input type="hidden" name="id" value="<?= $row['id']; ?>">
                                </div>
                                <div class="modal-footer">
                                    <button type="submit" name="delete" class="btn btn-danger">Yes, Delete</button>
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        </tbody>
    </table>

    <!-- Add Vehicle Modal -->
    <div class="modal fade" id="addModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <div class="modal-header">
                        <h5>Add Vehicle</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <label>Vehicle Number</label>
                        <input type="text" name="vehicle_number" class="form-control mb-2" required>

                        <label>Vehicle Type</label>
                        <select name="vehicle_type_id" class="form-control mb-2" required>
                            <?php foreach ($vehicleTypes as $type): ?>
                                <option value="<?= $type['id']; ?>"><?= htmlspecialchars($type['type_name']); ?></option>
                            <?php endforeach; ?>
                        </select>

                        <label>Capacity</label>
                        <input type="number" name="capacity" class="form-control mb-2" required>

                        <label>Status</label>
                        <select name="status" class="form-control">
                            <option value="available">Available</option>
                            <option value="maintenance">Maintenance</option>
                            <option value="unavailable">Unavailable</option>
                        </select>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" name="add" class="btn btn-primary">Save Vehicle</button>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
<?php
}

include 'template.php';
