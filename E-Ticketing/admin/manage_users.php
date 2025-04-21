<?php
session_start();
require_once '../config/database.php';

// Check if user is admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../auth/login.php');
    exit();
}

// Handle user actions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                $role = $_POST['role'];
                $username = $_POST['username'];
                $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
                
                // Check if username already exists
                $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
                $stmt->execute([$username]);
                if ($stmt->fetch()) {
                    $_SESSION['error'] = 'Username already exists. Please choose a different username.';
                    header('Location: ' . $_SERVER['PHP_SELF']);
                    exit();
                }
                
                if ($role === 'airline') {
                    try {
                        // Start transaction
                        $pdo->beginTransaction();
                        
                        $airline_name = $_POST['airline_name'];
                        
                        // Check if airline name already exists
                        $stmt = $pdo->prepare("SELECT id FROM airlines WHERE airline_name = ?");
                        $stmt->execute([$airline_name]);
                        if ($stmt->fetch()) {
                            throw new Exception('Airline name already exists. Please choose a different name.');
                        }
                        
                        // Insert into users table
                        $stmt = $pdo->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, 'airline')");
                        $stmt->execute([$username, $password]);
                        
                        $user_id = $pdo->lastInsertId();
                        
                        // Insert into airlines table
                        $stmt = $pdo->prepare("INSERT INTO airlines (user_id, airline_name) VALUES (?, ?)");
                        $stmt->execute([$user_id, $airline_name]);
                        
                        // Commit transaction
                        $pdo->commit();
                        $_SESSION['success'] = 'Airline user added successfully.';
                        
                    } catch (Exception $e) {
                        // Rollback on error
                        $pdo->rollBack();
                        $_SESSION['error'] = $e->getMessage();
                        header('Location: ' . $_SERVER['PHP_SELF']);
                        exit();
                    }
                } else {
                    // For regular user accounts
                    try {
                        $stmt = $pdo->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, 'user')");
                        $stmt->execute([$username, $password]);
                        $_SESSION['success'] = 'User added successfully.';
                    } catch (Exception $e) {
                        $_SESSION['error'] = 'Error adding user: ' . $e->getMessage();
                        header('Location: ' . $_SERVER['PHP_SELF']);
                        exit();
                    }
                }
                break;

            case 'edit':
                $id = $_POST['user_id'];
                if (!empty($_POST['new_password'])) {
                    $new_password = password_hash($_POST['new_password'], PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
                    $stmt->execute([$new_password, $id]);
                    $_SESSION['success'] = 'Password updated successfully.';
                }
                break;

            case 'delete':
                $id = $_POST['user_id'];
                
                // Start transaction
                $pdo->beginTransaction();
                
                try {
                    // Check if user is an airline
                    $stmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
                    $stmt->execute([$id]);
                    $user = $stmt->fetch();
                    
                    if ($user && $user['role'] === 'airline') {
                        // Delete from airlines table first
                        $stmt = $pdo->prepare("DELETE FROM airlines WHERE user_id = ?");
                        $stmt->execute([$id]);
                    }
                    
                    // Delete from bookings table
                    $stmt = $pdo->prepare("DELETE FROM bookings WHERE user_id = ?");
                    $stmt->execute([$id]);
                    
                    // Finally delete the user
                    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
                    $stmt->execute([$id]);
                    
                    // Commit transaction
                    $pdo->commit();
                    $_SESSION['success'] = 'User deleted successfully.';
                } catch (Exception $e) {
                    // Rollback on error
                    $pdo->rollBack();
                    $_SESSION['error'] = 'Error deleting user: ' . $e->getMessage();
                }
                break;
        }
    }
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit();
}

include '../includes/header.php';
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3">Manage Users</h1>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addUserModal">
            <i class="fas fa-plus"></i> Add User
        </button>
    </div>

    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?php 
            echo $_SESSION['error'];
            unset($_SESSION['error']);
            ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php 
            echo $_SESSION['success'];
            unset($_SESSION['success']);
            ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <!-- Tabs for different user roles -->
    <ul class="nav nav-tabs mb-4" id="userTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="users-tab" data-bs-toggle="tab" data-bs-target="#users" type="button" role="tab" aria-controls="users" aria-selected="true">
                Users
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="airlines-tab" data-bs-toggle="tab" data-bs-target="#airlines" type="button" role="tab" aria-controls="airlines" aria-selected="false">
                Airlines
            </button>
        </li>
    </ul>

    <!-- Tab content -->
    <div class="tab-content" id="userTabsContent">
        <!-- Regular Users Tab -->
        <div class="tab-pane fade show active" id="users" role="tabpanel" aria-labelledby="users-tab">
            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Username</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                try {
                                    $query = "SELECT * FROM users WHERE role = 'user' ORDER BY created_at DESC";
                                    $stmt = $pdo->query($query);
                                    
                                    if ($stmt->rowCount() > 0) {
                                        while ($user = $stmt->fetch(PDO::FETCH_ASSOC)) {
                                            ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($user['username']); ?></td>
                                                <td>
                                                    <button class="btn btn-sm btn-info" data-bs-toggle="modal" data-bs-target="#editUserModal<?php echo $user['id']; ?>">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <button class="btn btn-sm btn-danger" onclick="deleteUser(<?php echo $user['id']; ?>)">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </td>
                                            </tr>

                                            <!-- Edit User Modal -->
                                            <div class="modal fade" id="editUserModal<?php echo $user['id']; ?>" tabindex="-1">
                                                <div class="modal-dialog">
                                                    <div class="modal-content">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title">Change Password</h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                        </div>
                                                        <form method="POST">
                                                            <div class="modal-body">
                                                                <input type="hidden" name="action" value="edit">
                                                                <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                                
                                                                <div class="mb-3">
                                                                    <label class="form-label">New Password</label>
                                                                    <input type="password" class="form-control" name="new_password" required>
                                                                </div>
                                                            </div>
                                                            <div class="modal-footer">
                                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                                <button type="submit" class="btn btn-primary">Save Changes</button>
                                                            </div>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                            <?php
                                        }
                                    } else {
                                        echo '<tr><td colspan="2" class="text-center">No users found</td></tr>';
                                    }
                                } catch (Exception $e) {
                                    echo '<tr><td colspan="2" class="text-danger">Error: ' . htmlspecialchars($e->getMessage()) . '</td></tr>';
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Airlines Tab -->
        <div class="tab-pane fade" id="airlines" role="tabpanel" aria-labelledby="airlines-tab">
            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Username</th>
                                    <th>Airline Name</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                try {
                                    $query = "SELECT u.*, a.airline_name 
                                             FROM users u 
                                             JOIN airlines a ON u.id = a.user_id 
                                             WHERE u.role = 'airline' 
                                             ORDER BY u.created_at DESC";
                                    
                                    $stmt = $pdo->query($query);
                                    
                                    if ($stmt->rowCount() > 0) {
                                        while ($airline = $stmt->fetch(PDO::FETCH_ASSOC)) {
                                            ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($airline['username']); ?></td>
                                                <td><?php echo htmlspecialchars($airline['airline_name']); ?></td>
                                                <td>
                                                    <button class="btn btn-sm btn-info" data-bs-toggle="modal" data-bs-target="#editUserModal<?php echo $airline['id']; ?>">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <button class="btn btn-sm btn-danger" onclick="deleteUser(<?php echo $airline['id']; ?>)">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </td>
                                            </tr>

                                            <!-- Edit Airline Modal -->
                                            <div class="modal fade" id="editUserModal<?php echo $airline['id']; ?>" tabindex="-1">
                                                <div class="modal-dialog">
                                                    <div class="modal-content">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title">Change Password</h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                        </div>
                                                        <form method="POST">
                                                            <div class="modal-body">
                                                                <input type="hidden" name="action" value="edit">
                                                                <input type="hidden" name="user_id" value="<?php echo $airline['id']; ?>">
                                                                
                                                                <div class="mb-3">
                                                                    <label class="form-label">New Password</label>
                                                                    <input type="password" class="form-control" name="new_password" required>
                                                                </div>
                                                            </div>
                                                            <div class="modal-footer">
                                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                                <button type="submit" class="btn btn-primary">Save Changes</button>
                                                            </div>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                            <?php
                                        }
                                    } else {
                                        echo '<tr><td colspan="3" class="text-center">No airlines found</td></tr>';
                                    }
                                } catch (Exception $e) {
                                    echo '<tr><td colspan="3" class="text-danger">Error: ' . htmlspecialchars($e->getMessage()) . '</td></tr>';
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add User Modal -->
<div class="modal fade" id="addUserModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New User</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="add">
                    
                    <div class="mb-3">
                        <label class="form-label">User Type</label>
                        <select class="form-select" name="role" id="userType" required>
                            <option value="user">Regular User</option>
                            <option value="airline">Airline</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Username</label>
                        <input type="text" class="form-control" name="username" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Password</label>
                        <input type="password" class="form-control" name="password" required>
                    </div>

                    <div id="airlineFields" style="display: none;">
                        <div class="mb-3">
                            <label class="form-label">Airline Name</label>
                            <input type="text" class="form-control" name="airline_name">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Add User</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.getElementById('userType').addEventListener('change', function() {
    const airlineFields = document.getElementById('airlineFields');
    const airlineNameInput = airlineFields.querySelector('[name="airline_name"]');
    
    if (this.value === 'airline') {
        airlineFields.style.display = 'block';
        airlineNameInput.required = true;
    } else {
        airlineFields.style.display = 'none';
        airlineNameInput.required = false;
    }
});

function deleteUser(userId) {
    if (confirm('Are you sure you want to delete this user?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="user_id" value="${userId}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}
</script>

<?php include '../includes/footer.php'; ?>
