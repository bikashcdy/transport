<?php
$pageTitle = "Vehicles";
include '../db.php';

// ======================= ADD VEHICLE =======================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add'])) {
    $vehicle_number = trim($_POST['vehicle_number']);
    $vehicle_name = trim($_POST['vehicle_name']);
    $vehicle_type_id = intval($_POST['vehicle_type_id']);
    $capacity = intval($_POST['capacity']);
    $price = floatval($_POST['price']);
    $status = trim($_POST['status']);
    $facilities = isset($_POST['facilities']) ? implode(', ', $_POST['facilities']) : '';

    // ---- PHP SERVER-SIDE VALIDATION ----
    if (!preg_match('/^[a-zA-Z\s\-]+$/', $vehicle_name) || strlen(trim($vehicle_name)) < 2) {
        die("Invalid vehicle name. Only letters, spaces, and hyphens are allowed (no numbers or special characters).");
    }
    $vn_letters = preg_match_all('/[a-zA-Z]/', $vehicle_number);
    $vn_digits  = preg_match_all('/[0-9]/',    $vehicle_number);
    if (preg_match('/[^a-zA-Z0-9 ]/', $vehicle_number) || $vn_letters < 2 || $vn_digits < 3 || trim($vehicle_number) === '') {
        die("Invalid vehicle number. Must contain at least 2 letters and 3 numbers (e.g., BA 2 KHA 8848).");
    }

    $sql = "INSERT INTO vehicles (vehicle_number, vehicle_name, vehicle_type_id, capacity, price, facilities, status) VALUES (?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    
    if ($stmt === false) {
        die("SQL Prepare Error: " . $conn->error);
    }
    
    $stmt->bind_param("ssiidss", $vehicle_number, $vehicle_name, $vehicle_type_id, $capacity, $price, $facilities, $status);
    
    if (!$stmt->execute()) {
        die("Execute Error: " . $stmt->error);
    }
    
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
    $price = floatval($_POST['price']);
    $status = trim($_POST['status']);
    $facilities = isset($_POST['facilities']) ? implode(', ', $_POST['facilities']) : '';

    // ---- PHP SERVER-SIDE VALIDATION ----
    if (!preg_match('/^[a-zA-Z\s\-]+$/', $vehicle_name) || strlen(trim($vehicle_name)) < 2) {
        die("Invalid vehicle name. Only letters, spaces, and hyphens are allowed (no numbers or special characters).");
    }
    $vn_letters = preg_match_all('/[a-zA-Z]/', $vehicle_number);
    $vn_digits  = preg_match_all('/[0-9]/',    $vehicle_number);
    if (preg_match('/[^a-zA-Z0-9 ]/', $vehicle_number) || $vn_letters < 2 || $vn_digits < 3 || trim($vehicle_number) === '') {
        die("Invalid vehicle number. Must contain at least 2 letters and 3 numbers (e.g., BA 2 KHA 8848).");
    }

    $sql = "UPDATE vehicles SET vehicle_number=?, vehicle_name=?, vehicle_type_id=?, capacity=?, price=?, facilities=?, status=? WHERE id=?";
    $stmt = $conn->prepare($sql);
    
    if ($stmt === false) {
        die("SQL Prepare Error: " . $conn->error);
    }
    
    $stmt->bind_param("ssiidssi", $vehicle_number, $vehicle_name, $vehicle_type_id, $capacity, $price, $facilities, $status, $id);
    
    if (!$stmt->execute()) {
        die("Execute Error: " . $stmt->error);
    }
    
    $stmt->close();

    header("Location: vehicles.php");
    exit;
}

// ======================= DELETE VEHICLE =======================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete'])) {
    $id = intval($_POST['id']);

    // Delete related ways first if not using ON DELETE CASCADE
    $deleteWays = $conn->prepare("DELETE FROM ways WHERE vehicle_id = ?");
    if ($deleteWays) {
        $deleteWays->bind_param("i", $id);
        $deleteWays->execute();
        $deleteWays->close();
    }

    // Delete vehicle
    $sql = "DELETE FROM vehicles WHERE id = ?";
    $stmt = $conn->prepare($sql);
    
    if ($stmt === false) {
        die("SQL Prepare Error: " . $conn->error);
    }
    
    $stmt->bind_param("i", $id);
    
    if (!$stmt->execute()) {
        die("Execute Error: " . $stmt->error);
    }
    
    $stmt->close();

    header("Location: vehicles.php");
    exit;
}

// ======================= FETCH DATA =======================
// Ensure required vehicle types exist
$requiredTypes = ['Bus', 'Micro', 'Taxi'];
foreach ($requiredTypes as $typeName) {
    $checkStmt = $conn->prepare("SELECT id FROM vehicle_types WHERE type_name = ?");
    if ($checkStmt) {
        $checkStmt->bind_param("s", $typeName);
        $checkStmt->execute();
        $checkResult = $checkStmt->get_result();
        
        if ($checkResult->num_rows === 0) {
            $insertStmt = $conn->prepare("INSERT INTO vehicle_types (type_name) VALUES (?)");
            if ($insertStmt) {
                $insertStmt->bind_param("s", $typeName);
                $insertStmt->execute();
                $insertStmt->close();
            }
        }
        $checkStmt->close();
    }
}

// Fetch vehicle types
$typesResult = $conn->query("SELECT * FROM vehicle_types WHERE type_name IN ('Bus', 'Micro', 'Taxi') ORDER BY type_name");
if ($typesResult === false) {
    die("Query Error (vehicle_types): " . $conn->error);
}

$vehicleTypes = [];
while ($row = $typesResult->fetch_assoc()) {
    $vehicleTypes[] = $row;
}

// Fetch vehicles with their types
$vehiclesQuery = "SELECT v.*, t.type_name FROM vehicles v LEFT JOIN vehicle_types t ON v.vehicle_type_id = t.id ORDER BY v.id DESC";
$result = $conn->query($vehiclesQuery);

if ($result === false) {
    die("Query Error (vehicles): " . $conn->error . "<br>Query: " . $vehiclesQuery);
}

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

        .price-badge {
            display: inline-block;
            padding: 6px 12px;
            background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
            color: white;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.9rem;
        }

        /* Validation styles */
        input.is-invalid {
            border-color: #dc3545 !important;
            background-image: none !important;
        }
        input.is-valid {
            border-color: #198754 !important;
            background-image: none !important;
        }
        .field-error {
            color: #dc3545;
            font-size: 0.8rem;
            margin-top: 3px;
            display: block;
        }
        .input-hint {
            color: #6c757d;
            font-size: 0.75rem;
            margin-top: 2px;
            display: block;
        }
        /* Search hide — overrides template.php !important */
        tr[data-row].row-hidden {
            display: none !important;
        }
    </style>

    <div class="d-flex justify-content-end align-items-center mb-3">
        <div class="input-group" style="width: 280px;">
            <span class="input-group-text bg-white"><i class="fas fa-search text-muted"></i></span>
            <input type="text" id="vehicleSearch" class="form-control" placeholder="Search vehicles...">
        </div>
    </div>

    <?php if ($result && $result->num_rows > 0): ?>
    <table class="table table-striped align-middle">
        <thead class="table-dark">
            <tr>
                <th>SN</th>
                <th>Vehicle Number</th>
                <th>Vehicle Name</th>
                <th>Type</th>
                <th>Capacity</th>
                <th>Price</th>
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
                <tr data-row>
                    <td class="cell-data"><?= $i++; ?></td>
                    <td class="cell-data"><?= htmlspecialchars($row['vehicle_number']); ?></td>
                    <td class="cell-data"><?= htmlspecialchars($row['vehicle_name'] ?? 'N/A'); ?></td>
                    <td class="cell-data"><?= htmlspecialchars($row['type_name'] ?? 'N/A'); ?></td>
                    <td class="cell-data"><?= intval($row['capacity']); ?></td>
                    <td class="cell-data"><span class="price-badge">Rs. <?= number_format($row['price'] ?? 0, 2); ?></span></td>
                    <td>
                        <?php if (!empty($facilities)): ?>
                            <span class="badge bg-info"><?= count($facilities); ?> facilities</span>
                        <?php else: ?>
                            <span class="text-muted">None</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <span class="badge bg-<?= $row['status'] == 'available' ? 'success' : ($row['status'] == 'maintenance' ? 'warning' : 'secondary'); ?>">
                            <?= ucfirst($row['status']); ?>
                        </span>
                    </td>
                    <td>
                        <button class="btn btn-info btn-sm" data-bs-toggle="modal" data-bs-target="#viewModal<?= $row['id']; ?>">
                            <i class="fas fa-eye"></i>
                        </button>
                        <button class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#editModal<?= $row['id']; ?>">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#deleteModal<?= $row['id']; ?>">
                            <i class="fas fa-trash"></i>
                        </button>
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
                                <p><strong>Type:</strong> <?= htmlspecialchars($row['type_name'] ?? 'N/A'); ?></p>
                                <p><strong>Capacity:</strong> <?= intval($row['capacity']); ?> seats</p>
                                <p><strong>Price:</strong> <span class="price-badge">Rs. <?= number_format($row['price'] ?? 0, 2); ?></span></p>
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
                            <form method="POST" class="vehicle-form">
                                <div class="modal-header">
                                    <h5>Edit Vehicle</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body">
                                    <input type="hidden" name="id" value="<?= $row['id']; ?>">
                                    
                                    <div class="row">
                                        <div class="col-md-6 mb-2">
                                            <label>Vehicle Number</label>
                                            <input type="text"
                                                   name="vehicle_number"
                                                   value="<?= htmlspecialchars($row['vehicle_number']); ?>"
                                                   class="form-control vehicle-number-input"
                                                   placeholder="e.g., 1234"
                                                   required>
                                            <span class="input-hint">Nepali plate format: letters, numbers and spaces only (e.g., BA 2 KHA 8848)</span>
                                            <span class="field-error" style="display:none;"></span>
                                        </div>
                                        <div class="col-md-6 mb-2">
                                            <label>Vehicle Name</label>
                                            <input type="text"
                                                   name="vehicle_name"
                                                   value="<?= htmlspecialchars($row['vehicle_name'] ?? ''); ?>"
                                                   class="form-control vehicle-name-input"
                                                   placeholder="e.g., Deluxe Express"
                                                   required>
                                            <span class="input-hint">Letters and spaces only (no numbers or symbols)</span>
                                            <span class="field-error" style="display:none;"></span>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-4">
                                            <label>Vehicle Type</label>
                                            <select name="vehicle_type_id" class="form-control mb-2" required>
                                                <?php foreach ($vehicleTypes as $type): ?>
                                                    <option value="<?= $type['id']; ?>" <?= $row['vehicle_type_id'] == $type['id'] ? 'selected' : ''; ?>>
                                                        <?= htmlspecialchars($type['type_name']); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="col-md-4">
                                            <label>Capacity</label>
                                            <input type="number" name="capacity" value="<?= intval($row['capacity']); ?>" class="form-control mb-2" required>
                                        </div>
                                        <div class="col-md-4">
                                            <label>Price (Rs.)</label>
                                            <input type="number" name="price" value="<?= floatval($row['price'] ?? 0); ?>" step="0.01" min="0" class="form-control mb-2" required>
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
                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
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
    <?php else: ?>
        <div class="alert alert-info">
            <i class="fas fa-info-circle"></i> No vehicles found. Click "Add Vehicle" to add your first vehicle.
        </div>
    <?php endif; ?>

    <!-- Add Vehicle Modal -->
    <div class="modal fade" id="addModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form method="POST" class="vehicle-form">
                    <div class="modal-header">
                        <h5>Add Vehicle</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6 mb-2">
                                <label>Vehicle Number</label>
                                <input type="text"
                                       name="vehicle_number"
                                       class="form-control vehicle-number-input"
                                       placeholder="e.g., 1234"
                                       required>
                                <span class="input-hint">Nepali plate format: letters, numbers and spaces only (e.g., BA 2 KHA 8848)</span>
                                <span class="field-error" style="display:none;"></span>
                            </div>
                            <div class="col-md-6 mb-2">
                                <label>Vehicle Name</label>
                                <input type="text"
                                       name="vehicle_name"
                                       class="form-control vehicle-name-input"
                                       placeholder="e.g., Deluxe Express"
                                       required>
                                <span class="input-hint">Letters and spaces only (no numbers or symbols)</span>
                                <span class="field-error" style="display:none;"></span>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4">
                                <label>Vehicle Type</label>
                                <select name="vehicle_type_id" class="form-control mb-2" required>
                                    <option value="">Select Type</option>
                                    <?php foreach ($vehicleTypes as $type): ?>
                                        <option value="<?= $type['id']; ?>"><?= htmlspecialchars($type['type_name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label>Capacity</label>
                                <input type="number" name="capacity" class="form-control mb-2" placeholder="e.g., 45" min="1" required>
                            </div>
                            <div class="col-md-4">
                                <label>Price (Rs.)</label>
                                <input type="number" name="price" class="form-control mb-2" placeholder="e.g., 5000" step="0.01" min="0" required>
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

    <!-- ======================= VALIDATION SCRIPT ======================= -->
    <script>
    (function () {

        // ── Helpers ──────────────────────────────────────────────────────────

        function showError(input, message) {
            input.classList.add('is-invalid');
            input.classList.remove('is-valid');
            const errSpan = input.parentNode.querySelector('.field-error');
            if (errSpan) {
                errSpan.textContent = message;
                errSpan.style.display = 'block';
            }
        }

        function showValid(input) {
            input.classList.remove('is-invalid');
            input.classList.add('is-valid');
            const errSpan = input.parentNode.querySelector('.field-error');
            if (errSpan) {
                errSpan.textContent = '';
                errSpan.style.display = 'none';
            }
        }

        function clearState(input) {
            input.classList.remove('is-invalid', 'is-valid');
            const errSpan = input.parentNode.querySelector('.field-error');
            if (errSpan) {
                errSpan.textContent = '';
                errSpan.style.display = 'none';
            }
        }

        // ── Validate Functions ────────────────────────────────────────────────

        // Vehicle NUMBER: Nepali plate format e.g. "BA 2 KHA 8848"
        // Allows letters, digits, spaces — min 2 letters + min 3 digits, no special chars
        function validateNumber(input) {
            const val = input.value;
            if (val === '') {
                showError(input, 'Vehicle number is required.');
                return false;
            }
            if (/[^a-zA-Z0-9 ]/.test(val)) {
                showError(input, 'Vehicle number can only contain letters, numbers, and spaces (e.g., BA 2 KHA 8848).');
                return false;
            }
            if (val.trim() === '') {
                showError(input, 'Vehicle number cannot be spaces only.');
                return false;
            }
            const letterCount = (val.match(/[a-zA-Z]/g) || []).length;
            const digitCount  = (val.match(/[0-9]/g)    || []).length;
            if (letterCount < 2 && digitCount < 3) {
                showError(input, 'Vehicle number must have at least 2 letters and 3 numbers (e.g., BA 2 KHA 8848).');
                return false;
            }
            if (letterCount < 2) {
                showError(input, 'Vehicle number must have at least 2 letters. You have ' + letterCount + '.');
                return false;
            }
            if (digitCount < 3) {
                showError(input, 'Vehicle number must have at least 3 numbers. You have ' + digitCount + '.');
                return false;
            }
            showValid(input);
            return true;
        }

        // Vehicle NAME: letters and spaces only, no digits, no special chars
        function validateName(input) {
            const val = input.value.trim();
            if (val === '') {
                showError(input, 'Vehicle name is required.');
                return false;
            }
            if (/\d/.test(val)) {
                showError(input, 'Vehicle name cannot contain numbers.');
                return false;
            }
            if (/[^a-zA-Z\s\-]/.test(val)) {
                showError(input, 'Vehicle name can only contain letters, spaces, and hyphens — no special characters or symbols.');
                return false;
            }
            if (val.length < 2) {
                showError(input, 'Vehicle name must be at least 2 characters.');
                return false;
            }
            if (val.length > 100) {
                showError(input, 'Vehicle name cannot exceed 100 characters.');
                return false;
            }
            showValid(input);
            return true;
        }

        // ── Live Input Blocking (as-you-type) ────────────────────────────────

        document.addEventListener('input', function (e) {
            const el = e.target;

            // --- Number field: allow letters, digits, spaces — block special chars only ---
            if (el.classList.contains('vehicle-number-input')) {
                if (/[^a-zA-Z0-9 ]/.test(el.value)) {
                    const pos = el.selectionStart;
                    el.value = el.value.replace(/[^a-zA-Z0-9 ]/g, '');
                    el.setSelectionRange(pos - 1, pos - 1);
                }
                if (el.value.length === 0) {
                    clearState(el);
                } else {
                    validateNumber(el);
                }
            }

            // --- Name field: block digits and special characters ---
            if (el.classList.contains('vehicle-name-input')) {
                // Only replace if there's actually an invalid character — prevents cursor jump on space
                if (/[^a-zA-Z\s\-]/.test(el.value)) {
                    const pos = el.selectionStart;
                    el.value = el.value.replace(/[^a-zA-Z\s\-]/g, '');
                    el.setSelectionRange(pos - 1, pos - 1);
                }
                if (el.value.length === 0) {
                    clearState(el);
                } else {
                    validateName(el);
                }
            }
        });

        // Also validate on blur (when user leaves the field)
        document.addEventListener('focusout', function (e) {
            const el = e.target;
            if (el.classList.contains('vehicle-number-input') && el.value.length > 0) {
                validateNumber(el);
            }
            if (el.classList.contains('vehicle-name-input') && el.value.length > 0) {
                validateName(el);
            }
        });

        // ── Form Submit Validation ────────────────────────────────────────────

        document.addEventListener('submit', function (e) {
            const form = e.target;

            // Only intercept vehicle forms (add / edit)
            if (!form.classList.contains('vehicle-form')) return;

            let valid = true;

            const numberInput = form.querySelector('.vehicle-number-input');
            const nameInput   = form.querySelector('.vehicle-name-input');

            if (numberInput && !validateNumber(numberInput)) valid = false;
            if (nameInput   && !validateName(nameInput))     valid = false;

            if (!valid) {
                e.preventDefault();
                e.stopPropagation();

                // Scroll to the first invalid field inside the modal
                const firstInvalid = form.querySelector('.is-invalid');
                if (firstInvalid) {
                    firstInvalid.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    firstInvalid.focus();
                }
            }
        });

        // ── Reset validation state when a modal is closed ────────────────────
        document.addEventListener('hidden.bs.modal', function (e) {
            const modal = e.target;
            modal.querySelectorAll('.vehicle-number-input, .vehicle-name-input').forEach(function (input) {
                clearState(input);
            });
        });

        // ── Vehicle Table Search ──────────────────────────────────────────────
        const searchInput = document.getElementById('vehicleSearch');
        if (searchInput) {
            searchInput.addEventListener('input', function () {
                const keyword = this.value.trim().toLowerCase();
                // Only grab real data rows — each data row has a data-vehicle-id attribute
                const rows = document.querySelectorAll('table tbody tr[data-row]');
                let anyVisible = false;

                rows.forEach(function (row) {
                    // Read only the direct td text, ignoring hidden modal content
                    const cells = row.querySelectorAll('td.cell-data');
                    let text = '';
                    cells.forEach(function (td) { text += ' ' + td.textContent; });
                    text = text.toLowerCase();

                    const match = keyword === '' || text.includes(keyword);
                    if (match) {
                        row.classList.remove('row-hidden');
                        anyVisible = true;
                    } else {
                        row.classList.add('row-hidden');
                    }
                });

                // Show / hide no-results message
                let noResult = document.getElementById('searchNoResult');
                if (!anyVisible && keyword !== '') {
                    if (!noResult) {
                        noResult = document.createElement('tr');
                        noResult.id = 'searchNoResult';
                        noResult.innerHTML = '<td colspan="9" class="text-center text-muted py-3"><i class="fas fa-search me-2"></i>No vehicles match your search.</td>';
                        const tbody = document.querySelector('table tbody');
                        if (tbody) tbody.appendChild(noResult);
                    }
                    noResult.style.display = '';
                } else if (noResult) {
                    noResult.style.display = 'none';
                }
            });
        }

    })();
    </script>
<?php
}

include 'template.php';
?>