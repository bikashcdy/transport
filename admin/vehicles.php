<?php
$pageTitle = "Vehicles";
include '../db.php';

// Handle Add
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add'])) {
    $vehicle_number = $conn->real_escape_string($_POST['vehicle_number']);
    $vehicle_type_id = intval($_POST['vehicle_type_id']);
    $capacity = intval($_POST['capacity']);
    $status = $conn->real_escape_string($_POST['status']);

    $conn->query("INSERT INTO vehicles (vehicle_number, vehicle_type_id, capacity, status) 
                  VALUES ('$vehicle_number','$vehicle_type_id','$capacity','$status')");
    header("Location: vehicles.php");
    exit;
}

// Handle Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update'])) {
    $id = intval($_POST['id']);
    $vehicle_number = $conn->real_escape_string($_POST['vehicle_number']);
    $vehicle_type_id = intval($_POST['vehicle_type_id']);
    $capacity = intval($_POST['capacity']);
    $status = $conn->real_escape_string($_POST['status']);

    $conn->query("UPDATE vehicles SET 
                    vehicle_number='$vehicle_number', 
                    vehicle_type_id='$vehicle_type_id', 
                    capacity='$capacity', 
                    status='$status' 
                  WHERE id=$id");
    header("Location: vehicles.php");
    exit;
}

// Handle Delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete'])) {
    $id = intval($_POST['id']);
    $conn->query("DELETE FROM vehicles WHERE id=$id");
    header("Location: vehicles.php");
    exit;
}

// Fetch vehicle types
$typesResult = $conn->query("SELECT * FROM vehicle_types");
$vehicleTypes = [];
while ($row = $typesResult->fetch_assoc()) {
    $vehicleTypes[] = $row;
}

// Fetch vehicles with type name
$result = $conn->query("SELECT v.*, t.type_name 
                        FROM vehicles v 
                        JOIN vehicle_types t ON v.vehicle_type_id = t.id");

function pageContent()
{
    global $result, $vehicleTypes; ?>


<table class="table table-striped">
    <tr>
        <th>SN</th>
        <th>Vehicle Number</th>
        <th>Type</th>
        <th>Capacity</th>
        <th>Status</th>
        <th>Actions</th>
    </tr>
    <?php $i = 1;
        while ($row = $result->fetch_assoc()): ?>
    <tr>
        <td><?= $i++; ?></td>
        <td><?= $row['vehicle_number']; ?></td>
        <td><?= $row['type_name']; ?></td>
        <td><?= $row['capacity']; ?></td>
        <td><?= ucfirst($row['status']); ?></td>
        <td>
            <!-- View -->
            <button class="btn btn-sm btn-info" data-bs-toggle="modal" data-bs-target="#viewModal<?= $row['id']; ?>"><i
                    class="fas fa-eye"></i></button>

            <!-- Edit -->
            <button class="btn btn-sm btn-warning" data-bs-toggle="modal"
                data-bs-target="#editModal<?= $row['id']; ?>"><i class="fas fa-edit"></i></button>

            <!-- Delete -->
            <button class="btn btn-sm btn-danger" data-bs-toggle="modal"
                data-bs-target="#deleteModal<?= $row['id']; ?>"><i class="fas fa-trash"></i></button>
        </td>
    </tr>

    <!-- View Modal -->
    <div class="modal fade" id="viewModal<?= $row['id']; ?>" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">View Vehicle</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p><strong>Vehicle Number:</strong> <?= $row['vehicle_number']; ?></p>
                    <p><strong>Type:</strong> <?= $row['type_name']; ?></p>
                    <p><strong>Capacity:</strong> <?= $row['capacity']; ?></p>
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
                        <h5 class="modal-title">Edit Vehicle</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="id" value="<?= $row['id']; ?>">

                        <label>Vehicle Number</label>
                        <input type="text" name="vehicle_number" value="<?= $row['vehicle_number']; ?>"
                            class="form-control mb-2" required>

                        <label>Vehicle Type</label>
                        <select name="vehicle_type_id" class="form-control mb-2" required>
                            <?php foreach ($vehicleTypes as $type): ?>
                            <option value="<?= $type['id']; ?>"
                                <?= $row['vehicle_type_id'] == $type['id'] ? 'selected' : ''; ?>>
                                <?= $type['type_name']; ?>
                            </option>
                            <?php endforeach; ?>
                        </select>

                        <label>Capacity</label>
                        <input type="number" name="capacity" value="<?= $row['capacity']; ?>" class="form-control mb-2"
                            required>

                        <label>Status</label>
                        <select name="status" class="form-control">
                            <option value="available" <?= $row['status'] == 'available' ? 'selected' : ''; ?>>Available
                            </option>
                            <option value="maintenance" <?= $row['status'] == 'maintenance' ? 'selected' : ''; ?>>
                                Maintenance</option>
                            <option value="unavailable" <?= $row['status'] == 'unavailable' ? 'selected' : ''; ?>>
                                Unavailable</option>
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
                    <div class="modal-header">
                        <h5 class="modal-title">Delete Vehicle</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        Are you sure you want to delete <strong><?= $row['vehicle_number']; ?></strong>?
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
</table>

<!-- Add Vehicle Modal -->
<div class="modal fade" id="addModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title">Add Vehicle</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <label>Vehicle Number</label>
                    <input type="text" name="vehicle_number" class="form-control mb-2" required>

                    <label>Vehicle Type</label>
                    <select name="vehicle_type_id" class="form-control mb-2" required>
                        <?php foreach ($vehicleTypes as $type): ?>
                        <option value="<?= $type['id']; ?>"><?= $type['type_name']; ?></option>
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

<?php }

include 'template.php';