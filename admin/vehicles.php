<?php
$pageTitle = "Vehicles";

// Your PHP logic (CRUD)
include '../db.php';

// Fetch vehicle types
$typesResult = $conn->query("SELECT * FROM vehicle_types");
$vehicleTypes = [];
while ($row = $typesResult->fetch_assoc()) {
    $vehicleTypes[] = $row;
}

// Fetch vehicles with type name
$result = $conn->query("SELECT v.*, t.type_name FROM vehicles v JOIN vehicle_types t ON v.vehicle_type_id = t.id");

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
        <td><?= $row['status']; ?></td>
        <td>
            <!-- Action Buttons (View/Edit/Delete modals) -->
            <button class="btn btn-sm btn-info"><i class="fas fa-eye"></i></button>
            <button class="btn btn-sm btn-warning"><i class="fas fa-edit"></i></button>
            <button class="btn btn-sm btn-danger"><i class="fas fa-trash"></i></button>
        </td>
    </tr>
    <?php endwhile; ?>
</table>

<!-- Add Vehicle Modal -->
<div class="modal fade" id="addModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form method="POST" class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add Vehicle</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <label>Vehicle Number</label>
                <input type="text" name="vehicle_number" class="form-control mb-2" required>

                <label>Vehicle Type</label>
                <select name="vehicle_type_id" class="form-control mb-2">
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
                <button class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="submit" class="btn btn-primary">Save Vehicle</button>
            </div>
        </form>
    </div>
</div>

<?php }

include 'template.php';