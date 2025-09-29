<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header("Location: index.php");
    exit();
}
include 'db.php';

// Fetch vehicle types
$typesResult = $conn->query("SELECT * FROM vehicle_types");
$vehicleTypes = [];
while ($row = $typesResult->fetch_assoc()) {
    $vehicleTypes[] = $row;
}

// Handle Add
if (isset($_POST['add'])) {
    $vehicle_number = $_POST['vehicle_number'];
    $vehicle_type_id = $_POST['vehicle_type_id'];
    $capacity = $_POST['capacity'];
    $conn->query("INSERT INTO vehicles (vehicle_number, vehicle_type_id, capacity) 
                  VALUES ('$vehicle_number','$vehicle_type_id','$capacity')");
    header("Location: vehicles.php");
    exit();
}

// Handle Update
if (isset($_POST['update'])) {
    $id = $_POST['id'];
    $vehicle_number = $_POST['vehicle_number'];
    $vehicle_type_id = $_POST['vehicle_type_id'];
    $capacity = $_POST['capacity'];
    $status = $_POST['status'];
    $conn->query("UPDATE vehicles 
                  SET vehicle_number='$vehicle_number', vehicle_type_id='$vehicle_type_id', capacity='$capacity', status='$status' 
                  WHERE id=$id");
    header("Location: vehicles.php");
    exit();
}

// Handle Delete
if (isset($_POST['delete'])) {
    $id = $_POST['id'];
    $conn->query("DELETE FROM vehicles WHERE id=$id");
    header("Location: vehicles.php");
    exit();
}

// Fetch vehicles with type name
$result = $conn->query("
    SELECT v.*, t.type_name 
    FROM vehicles v 
    JOIN vehicle_types t ON v.vehicle_type_id = t.id
");
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vehicle Management - Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
    body {
        margin: 0;
        font-family: Arial, sans-serif;
        display: flex;
        background: #f4f6f9;
        min-height: 100vh;
    }

    .sidebar {
        width: 220px;
        background: #1a1b5e;
        color: white;
        padding: 20px 0;
        height: 100vh;
        position: fixed;
        top: 0;
    }

    .sidebar h2 {
        text-align: center;
        margin-bottom: 30px;
    }

    .sidebar a {
        display: block;
        color: white;
        padding: 12px 20px;
        text-decoration: none;
        transition: background 0.3s;
    }

    .sidebar a:hover {
        background: #4f46e5;
    }

    .main {
        margin-left: 220px;
        flex: 1;
        padding: 20px;
        overflow-y: auto;
    }

    header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        background: white;
        padding: 15px 20px;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        margin-bottom: 20px;
    }

    h2.page-title {
        margin: 0;
    }

    table {
        width: 100%;
        border-collapse: collapse;
        background: white;
        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
    }

    th,
    td {
        border: 1px solid #ccc;
        padding: 10px;
        text-align: center;
    }
    </style>
</head>

<body>

    <div class="sidebar">
        <h2>🚛 TMS Admin</h2>
        <a href="admin_dashboard.php"><i class="fas fa-home"></i> Dashboard</a>
        <a href="vehicles.php"><i class="fas fa-bus"></i> Vehicles</a>
        <a href="users.php"><i class="fas fa-users"></i> Users</a>
        <a href="schedules.php"><i class="fas fa-calendar-alt"></i> Schedules</a>
        <a href="reports.php"><i class="fas fa-chart-line"></i> Reports</a>
        <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>

    <div class="main">
        <header>
            <h2 class="page-title">Vehicle Management</h2>
            <!-- Add Vehicle Button -->
            <button class="btn btn-primary mb-3" data-bs-toggle="modal" data-bs-target="#addModal">
                <i class="fas fa-plus"></i> Add Vehicle
            </button>

        </header>

        <!-- Vehicles Table -->
        <table class="table table-striped">
            <tr>
                <th>SN</th>
                <th>Vehicle Number</th>
                <th>Type</th>
                <th>Capacity</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
            <?php $i = 1; while ($row = $result->fetch_assoc()): ?>
            <tr>
                <td><?= $i++; ?></td>
                <td><?= $row['vehicle_number']; ?></td>
                <td><?= $row['type_name']; ?></td>
                <td><?= $row['capacity']; ?></td>
                <td><?= $row['status']; ?></td>
                <td>
                    <!-- View Button -->
                    <button class="btn btn-info btn-sm" data-bs-toggle="modal"
                        data-bs-target="#viewModal<?= $row['id']; ?>"><i class="fas fa-eye"></i></button>
                    <!-- Edit Button -->
                    <button class="btn btn-warning btn-sm" data-bs-toggle="modal"
                        data-bs-target="#editModal<?= $row['id']; ?>"><i class="fas fa-pencil-alt"></i></button>
                    <!-- Delete Button -->
                    <button class="btn btn-danger btn-sm" data-bs-toggle="modal"
                        data-bs-target="#deleteModal<?= $row['id']; ?>"><i class="fas fa-trash"></i></button>
                </td>
            </tr>

            <!-- View Modal -->
            <div class="modal fade" id="viewModal<?= $row['id']; ?>" tabindex="-1">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Vehicle Details</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <p><strong>Vehicle Number:</strong> <?= $row['vehicle_number']; ?></p>
                            <p><strong>Vehicle Type:</strong> <?= $row['type_name']; ?></p>
                            <p><strong>Capacity:</strong> <?= $row['capacity']; ?></p>
                            <p><strong>Status:</strong> <?= $row['status']; ?></p>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Edit Modal -->
            <div class="modal fade" id="editModal<?= $row['id']; ?>" tabindex="-1">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <form method="POST" action="vehicles.php">
                            <div class="modal-header">
                                <h5 class="modal-title">Edit Vehicle</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <input type="hidden" name="id" value="<?= $row['id']; ?>">
                                <div class="mb-2">
                                    <label>Vehicle Number</label>
                                    <input type="text" name="vehicle_number" class="form-control"
                                        value="<?= $row['vehicle_number']; ?>" required>
                                </div>
                                <div class="mb-2">
                                    <label>Vehicle Type</label>
                                    <select name="vehicle_type_id" class="form-control" required>
                                        <?php foreach ($vehicleTypes as $type): ?>
                                        <option value="<?= $type['id']; ?>"
                                            <?= $type['id'] == $row['vehicle_type_id'] ? 'selected' : ''; ?>>
                                            <?= $type['type_name']; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="mb-2">
                                    <label>Capacity</label>
                                    <input type="number" name="capacity" class="form-control"
                                        value="<?= $row['capacity']; ?>" required>
                                </div>
                                <div class="mb-2">
                                    <label>Status</label>
                                    <select name="status" class="form-control">
                                        <option <?= $row['status'] == 'Available' ? 'selected' : ''; ?>>Available
                                        </option>
                                        <option <?= $row['status'] == 'In Use' ? 'selected' : ''; ?>>In Use</option>
                                        <option <?= $row['status'] == 'Maintenance' ? 'selected' : ''; ?>>Maintenance
                                        </option>
                                    </select>
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
                        <form method="POST" action="vehicles.php">
                            <div class="modal-header">
                                <h5 class="modal-title">Delete Vehicle</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <p>Are you sure you want to delete <strong><?= $row['vehicle_number']; ?></strong>?</p>
                                <input type="hidden" name="id" value="<?= $row['id']; ?>">
                            </div>
                            <div class="modal-footer">
                                <button type="submit" name="delete" class="btn btn-danger">Delete</button>
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <?php endwhile; ?>
        </table>

    </div>

    <!-- Add Vehicle Modal -->
    <div class="modal fade" id="addModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" action="vehicles.php">
                    <div class="modal-header">
                        <h5 class="modal-title">Add Vehicle</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-2">
                            <label>Vehicle Number</label>
                            <input type="text" name="vehicle_number" class="form-control" required>
                        </div>
                        <div class="mb-2">
                            <label>Vehicle Type</label>
                            <select name="vehicle_type_id" class="form-control" required>
                                <option value="">-- Select Type --</option>
                                <?php foreach ($vehicleTypes as $type): ?>
                                <option value="<?= $type['id']; ?>"><?= $type['type_name']; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-2">
                            <label>Capacity</label>
                            <input type="number" name="capacity" class="form-control" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" name="add" class="btn btn-primary">Add Vehicle</button>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>