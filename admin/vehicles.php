<?php
$pageTitle = "Vehicles";
include '../db.php';

// ======================= ADD VEHICLE =======================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add'])) {
    $vehicle_number = trim($_POST['vehicle_number']);
    $vehicle_name = trim($_POST['vehicle_name']);
    $vehicle_type_id = intval($_POST['vehicle_type_id']);
    $capacity = intval($_POST['capacity']);
    $status = trim($_POST['status']);
    $facilities = isset($_POST['facilities']) ? implode(', ', $_POST['facilities']) : '';

    $stmt = $conn->prepare("INSERT INTO vehicles (vehicle_number, vehicle_name, vehicle_type_id, capacity, facilities, status) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssiiss", $vehicle_number, $vehicle_name, $vehicle_type_id, $capacity, $facilities, $status);
    $stmt->execute();
    $stmt->close();

    header("Location: vehicles.php");
    exit;
}

// ======================= UPDATE VEHICLE =======================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update'])) {
    $id = intval($_POST['id']);
    $vehicle_number = trim($_POST['vehicle_number']);
    $vehicle_name = trim($_POST['vehicle_name']);
    $vehicle_type_id = intval($_POST['vehicle_type_id']);
    $capacity = intval($_POST['capacity']);
    $status = trim($_POST['status']);
    $facilities = isset($_POST['facilities']) ? implode(', ', $_POST['facilities']) : '';

    $stmt = $conn->prepare("UPDATE vehicles SET vehicle_number=?, vehicle_name=?, vehicle_type_id=?, capacity=?, facilities=?, status=? WHERE id=?");
    $stmt->bind_param("ssiissi", $vehicle_number, $vehicle_name, $vehicle_type_id, $capacity, $facilities, $status, $id);
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

// Available facilities options
$availableFacilities = [
    'AC' => 'Air Conditioning',
    'WiFi' => 'WiFi',
    'USB Charging' => 'USB Charging Ports',
    'Water Bottle' => 'Complimentary Water',
    'TV' => 'Entertainment System',
    'Reclining Seats' => 'Reclining Seats',
    'Reading Light' => 'Reading Lights',
    'Blanket' => 'Blankets',
    'Music System' => 'Music System',
    'GPS' => 'GPS Navigation'
];

// ======================= PAGE CONTENT =======================
function pageContent()
{
    global $result, $vehicleTypes, $availableFacilities;
?>
    <style>
        .facilities-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 10px;
            max-height: 300px;
            overflow-y: auto;
            padding: 10px;
            background: #f8f9fa;
            border-radius: 8px;
        }
        
        .facility-checkbox {
            display: flex;
            align-items: center;
            padding: 8px;
            background: white;
            border-radius: 5px;
            border: 1px solid #dee2e6;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .facility-checkbox:hover {
            background: #e9ecef;
            border-color: #0d6efd;
        }
        
        .facility-checkbox input[type="checkbox"] {
            margin-right: 8px;
            cursor: pointer;
        }
        
        .facility-checkbox label {
            cursor: pointer;
            margin: 0;
            font-size: 0.9rem;
        }
        
        .facilities-badge {
            display: inline-block;
            padding: 4px 10px;
            margin: 2px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 15px;
            font-size: 0.75rem;
        }
        
        .view-facilities {
            display: flex;
            flex-wrap: wrap;
            gap: 5px;
        }
        
        .no-facilities {
            color: #6c757d;
            font-style: italic;
        }
    </style>

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="mb-0">Vehicles</h4>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addModal"><i class="fas fa-plus"></i> Add Vehicle</button>
    </div>

    <table class="table table-striped align-middle">
        <thead class="table-dark">
            <tr>
                <th>SN</th>
                <th>Vehicle Number</th>
                <th>Vehicle Name</th>
                <th>Type</th>
                <th>Capacity</th>
                <th>Facilities</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php $i = 1;
            while ($row = $result->fetch_assoc()): 
                $facilities = !empty($row['facilities']) ? explode(', ', $row['facilities']) : [];
            ?>
                <tr>
                    <td><?= $i++; ?></td>
                    <td><?= htmlspecialchars($row['vehicle_number']); ?></td>
                    <td><?= htmlspecialchars($row['vehicle_name'] ?? 'N/A'); ?></td>
                    <td><?= htmlspecialchars($row['type_name']); ?></td>
                    <td><?= intval($row['capacity']); ?></td>
                    <td>
                        <?php if (!empty($facilities)): ?>
                            <span class="badge bg-info"><?= count($facilities); ?> facilities</span>
                        <?php else: ?>
                            <span class="text-muted">None</span>
                        <?php endif; ?>
                    </td>
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
                                <p><strong>Vehicle Name:</strong> <?= htmlspecialchars($row['vehicle_name'] ?? 'N/A'); ?></p>
                                <p><strong>Type:</strong> <?= htmlspecialchars($row['type_name']); ?></p>
                                <p><strong>Capacity:</strong> <?= intval($row['capacity']); ?> seats</p>
                                <p><strong>Status:</strong> <?= ucfirst($row['status']); ?></p>
                                <p><strong>Facilities:</strong></p>
                                <div class="view-facilities">
                                    <?php if (!empty($facilities)): ?>
                                        <?php foreach ($facilities as $facility): ?>
                                            <span class="facilities-badge">
                                                <i class="fas fa-check-circle"></i> <?= htmlspecialchars(trim($facility)); ?>
                                            </span>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <span class="no-facilities">No facilities added</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Edit Modal -->
                <div class="modal fade" id="editModal<?= $row['id']; ?>" tabindex="-1">
                    <div class="modal-dialog modal-lg">
                        <div class="modal-content">
                            <form method="POST">
                                <div class="modal-header">
                                    <h5>Edit Vehicle</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body">
                                    <input type="hidden" name="id" value="<?= $row['id']; ?>">
                                    
                                    <div class="row">
                                        <div class="col-md-6">
                                            <label>Vehicle Number</label>
                                            <input type="text" name="vehicle_number" value="<?= htmlspecialchars($row['vehicle_number']); ?>" class="form-control mb-2" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label>Vehicle Name</label>
                                            <input type="text" name="vehicle_name" value="<?= htmlspecialchars($row['vehicle_name'] ?? ''); ?>" class="form-control mb-2" required>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-6">
                                            <label>Vehicle Type</label>
                                            <select name="vehicle_type_id" class="form-control mb-2" required>
                                                <?php foreach ($vehicleTypes as $type): ?>
                                                    <option value="<?= $type['id']; ?>" <?= $row['vehicle_type_id'] == $type['id'] ? 'selected' : ''; ?>><?= htmlspecialchars($type['type_name']); ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="col-md-6">
                                            <label>Capacity</label>
                                            <input type="number" name="capacity" value="<?= intval($row['capacity']); ?>" class="form-control mb-2" required>
                                        </div>
                                    </div>

                                    <label>Status</label>
                                    <select name="status" class="form-control mb-3">
                                        <option value="available" <?= $row['status'] == 'available' ? 'selected' : ''; ?>>Available</option>
                                        <option value="maintenance" <?= $row['status'] == 'maintenance' ? 'selected' : ''; ?>>Maintenance</option>
                                        <option value="unavailable" <?= $row['status'] == 'unavailable' ? 'selected' : ''; ?>>Unavailable</option>
                                    </select>

                                    <label class="mb-2">Facilities & Amenities</label>
                                    <div class="facilities-grid">
                                        <?php foreach ($availableFacilities as $key => $label): ?>
                                            <div class="facility-checkbox">
                                                <input type="checkbox" 
                                                       name="facilities[]" 
                                                       value="<?= htmlspecialchars($key); ?>" 
                                                       id="edit_facility_<?= $row['id']; ?>_<?= str_replace(' ', '_', $key); ?>"
                                                       <?= in_array($key, $facilities) ? 'checked' : ''; ?>>
                                                <label for="edit_facility_<?= $row['id']; ?>_<?= str_replace(' ', '_', $key); ?>">
                                                    <?= htmlspecialchars($label); ?>
                                                </label>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
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
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form method="POST">
                    <div class="modal-header">
                        <h5>Add Vehicle</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6">
                                <label>Vehicle Number</label>
                                <input type="text" name="vehicle_number" class="form-control mb-2" placeholder="e.g., BA-1-KHA-1234" required>
                            </div>
                            <div class="col-md-6">
                                <label>Vehicle Name</label>
                                <input type="text" name="vehicle_name" class="form-control mb-2" placeholder="e.g., Deluxe Express" required>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <label>Vehicle Type</label>
                                <select name="vehicle_type_id" class="form-control mb-2" required>
                                    <option value="">Select Type</option>
                                    <?php foreach ($vehicleTypes as $type): ?>
                                        <option value="<?= $type['id']; ?>"><?= htmlspecialchars($type['type_name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label>Capacity</label>
                                <input type="number" name="capacity" class="form-control mb-2" placeholder="e.g., 45" min="1" required>
                            </div>
                        </div>

                        <label>Status</label>
                        <select name="status" class="form-control mb-3">
                            <option value="available">Available</option>
                            <option value="maintenance">Maintenance</option>
                            <option value="unavailable">Unavailable</option>
                        </select>

                        <label class="mb-2">Facilities & Amenities</label>
                        <div class="facilities-grid">
                            <?php foreach ($availableFacilities as $key => $label): ?>
                                <div class="facility-checkbox">
                                    <input type="checkbox" 
                                           name="facilities[]" 
                                           value="<?= htmlspecialchars($key); ?>" 
                                           id="add_facility_<?= str_replace(' ', '_', $key); ?>">
                                    <label for="add_facility_<?= str_replace(' ', '_', $key); ?>">
                                        <?= htmlspecialchars($label); ?>
                                    </label>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <small class="text-muted">Select all facilities available in this vehicle</small>
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
?>