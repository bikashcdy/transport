<?php
$pageTitle = "Users";

include '../db.php';

// Handle Add
if (isset($_POST['add'])) {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $role = $_POST['role'];
    $status = $_POST['status'];

    $conn->query("INSERT INTO users (name, email, password, user_type, status) 
                  VALUES ('$name','$email','$password','$role','$status')");
    header("Location: users.php");
    exit();
}

// Handle Update
if (isset($_POST['update'])) {
    $id = $_POST['id'];
    $name = $_POST['name'];
    $email = $_POST['email'];
    $role = $_POST['role'];
    $status = $_POST['status'];

    $conn->query("UPDATE users SET name='$name', email='$email', user_type='$role', status='$status' 
                  WHERE id=$id");
    header("Location: users.php");
    exit();
}

// Handle Delete
if (isset($_POST['delete'])) {
    $id = $_POST['id'];
    $conn->query("DELETE FROM users WHERE id=$id");
    header("Location: users.php");
    exit();
}

// Fetch users
$result = $conn->query("SELECT * FROM users ORDER BY created_at DESC");

function pageContent()
{
    global $result, $pageTitle;
    ?>


<!-- Users Table -->
<table class="table table-striped">
    <tr>
        <th>SN</th>
        <th>Name</th>
        <th>Email</th>
        <th>Role</th>
        <th>Created</th>
        <th>Actions</th>
    </tr>
    <?php $i = 1;
        while ($row = $result->fetch_assoc()): ?>
    <tr>
        <td><?= $i++; ?></td>
        <td><?= $row['name']; ?></td>
        <td><?= $row['email']; ?></td>
        <td><?= ucfirst($row['user_type']); ?></td>
        <td><?= $row['created_at']; ?></td>
        <td>
            <button class="btn btn-info btn-sm" data-bs-toggle="modal" data-bs-target="#viewModal<?= $row['id']; ?>"><i
                    class="fas fa-eye"></i></button>
            <button class="btn btn-warning btn-sm" data-bs-toggle="modal"
                data-bs-target="#editModal<?= $row['id']; ?>"><i class="fas fa-edit"></i></button>
            <button class="btn btn-danger btn-sm" data-bs-toggle="modal"
                data-bs-target="#deleteModal<?= $row['id']; ?>"><i class="fas fa-trash"></i></button>
        </td>
    </tr>

    <!-- View Modal -->
    <div class="modal fade" id="viewModal<?= $row['id']; ?>" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">User Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p><strong>Name:</strong> <?= $row['name']; ?></p>
                    <p><strong>Email:</strong> <?= $row['email']; ?></p>
                    <p><strong>Role:</strong> <?= ucfirst($row['user_type']); ?></p>
                    <p><strong>Created At:</strong> <?= $row['created_at']; ?></p>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-secondary"
                        data-bs-dismiss="modal">Close</button></div>
            </div>
        </div>
    </div>

    <!-- Edit Modal -->
    <div class="modal fade" id="editModal<?= $row['id']; ?>" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" action="users.php">
                    <div class="modal-header">
                        <h5 class="modal-title">Edit User</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="id" value="<?= $row['id']; ?>">
                        <div class="mb-2"><label>Name</label>
                            <input type="text" name="name" class="form-control" value="<?= $row['name']; ?>" required>
                        </div>
                        <div class="mb-2"><label>Email</label>
                            <input type="email" name="email" class="form-control" value="<?= $row['email']; ?>"
                                required>
                        </div>
                        <div class="mb-2"><label>Role</label>
                            <select name="role" class="form-control" required>
                                <option value="admin" <?= $row['user_type'] == 'admin' ? 'selected' : ''; ?>>Admin
                                </option>
                                <option value="user" <?= $row['user_type'] == 'user' ? 'selected' : ''; ?>>User
                                </option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer"><button type="submit" name="update"
                            class="btn btn-warning">Update</button>
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
                <form method="POST" action="users.php">
                    <div class="modal-header">
                        <h5 class="modal-title">Delete User</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <p>Are you sure you want to delete <strong><?= $row['name']; ?></strong>?</p>
                        <input type="hidden" name="id" value="<?= $row['id']; ?>">
                    </div>
                    <div class="modal-footer"><button type="submit" name="delete" class="btn btn-danger">Delete</button>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <?php endwhile; ?>
</table>

<!-- Add User Modal -->
<div class="modal fade" id="addModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="users.php">
                <div class="modal-header">
                    <h5 class="modal-title">Add User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-2"><label>Name</label>
                        <input type="text" name="name" class="form-control" required>
                    </div>
                    <div class="mb-2"><label>Email</label>
                        <input type="email" name="email" class="form-control" required>
                    </div>
                    <div class="mb-2"><label>Password</label>
                        <input type="password" name="password" class="form-control" required>
                    </div>
                    <div class="mb-2"><label>Role</label>
                        <select name="user_type" class="form-control" required>
                            <option value="admin">Admin</option>
                            <option value="user">User</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer"><button type="submit" name="add" class="btn btn-primary">Add
                        User</button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
} // end of pageContent()

include 'template.php';
?>