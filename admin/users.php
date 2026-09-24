<?php
// admin/users.php — Manage users: create with full fields, edit, delete, reset password
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/functions.php';
require_admin();

$conn = db();
$action = $_GET['action'] ?? 'list';

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postAction = $_POST['action'] ?? '';

    if ($postAction === 'create') {
        $fullName = trim($_POST['full_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $mobile = trim($_POST['mobile'] ?? '');
        $dob = $_POST['date_of_birth'] ?? '';
        $marital = $_POST['marital_status'] ?? 'single';
        $spouse = trim($_POST['spouse_name'] ?? '');
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        if ($fullName && $email && $mobile && $dob && $username && $password) {
            // Check username uniqueness
            $chk = $conn->prepare("SELECT id FROM users WHERE username = ?");
            $chk->bind_param('s', $username);
            $chk->execute();
            $chk->store_result();
            if ($chk->num_rows > 0) {
                $chk->close();
                set_flash('error', 'Username already exists.');
                redirect('users.php?action=create');
            }
            $chk->close();

            $chk2 = $conn->prepare("SELECT id FROM users WHERE email = ?");
            $chk2->bind_param('s', $email);
            $chk2->execute();
            $chk2->store_result();
            if ($chk2->num_rows > 0) {
                $chk2->close();
                set_flash('error', 'Email already exists.');
                redirect('users.php?action=create');
            }
            $chk2->close();

            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO users (full_name, email, mobile, date_of_birth, marital_status, spouse_name, username, password) VALUES (?,?,?,?,?,?,?,?)");
            $stmt->bind_param('ssssssss', $fullName, $email, $mobile, $dob, $marital, $spouse, $username, $hash);
            if ($stmt->execute()) {
                $userId = $stmt->insert_id;
                $stmt->close();

                // Handle profile photo upload
                if (isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] === UPLOAD_ERR_OK) {
                    $ext = strtolower(pathinfo($_FILES['profile_photo']['name'], PATHINFO_EXTENSION));
                    if (in_array($ext, ['jpg', 'jpeg', 'png'])) {
                        $destPath = __DIR__ . '/../uploads/profile_photos/' . $username . '.jpg';
                        $result = process_profile_image($_FILES['profile_photo']['tmp_name'], $destPath);
                        if (is_string($result) && file_exists($result)) {
                            $photoPath = 'uploads/profile_photos/' . $username . '.jpg';
                            $upd = $conn->prepare("UPDATE users SET profile_photo = ? WHERE id = ?");
                            $upd->bind_param('si', $photoPath, $userId);
                            $upd->execute();
                            $upd->close();
                        }
                    }
                }
                set_flash('success', 'User created successfully.');
            } else {
                set_flash('error', 'Failed to create user.');
            }
            redirect('users.php');
        } else {
            set_flash('error', 'All required fields must be filled.');
            redirect('users.php?action=create');
        }
    }

    if ($postAction === 'edit') {
        $id = (int)$_POST['id'];
        $fullName = trim($_POST['full_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $mobile = trim($_POST['mobile'] ?? '');
        $dob = $_POST['date_of_birth'] ?? '';
        $marital = $_POST['marital_status'] ?? 'single';
        $spouse = trim($_POST['spouse_name'] ?? '');
        $username = trim($_POST['username'] ?? '');

        if ($fullName && $email && $mobile && $dob && $username && $id) {
            $stmt = $conn->prepare("UPDATE users SET full_name=?, email=?, mobile=?, date_of_birth=?, marital_status=?, spouse_name=?, username=? WHERE id=?");
            $stmt->bind_param('sssssssi', $fullName, $email, $mobile, $dob, $marital, $spouse, $username, $id);
            if ($stmt->execute()) {
                $stmt->close();

                if (isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] === UPLOAD_ERR_OK) {
                    $ext = strtolower(pathinfo($_FILES['profile_photo']['name'], PATHINFO_EXTENSION));
                    if (in_array($ext, ['jpg', 'jpeg', 'png'])) {
                        $destPath = __DIR__ . '/../uploads/profile_photos/' . $username . '.jpg';
                        $result = process_profile_image($_FILES['profile_photo']['tmp_name'], $destPath);
                        if (is_string($result) && file_exists($result)) {
                            $photoPath = 'uploads/profile_photos/' . $username . '.jpg';
                            $upd = $conn->prepare("UPDATE users SET profile_photo = ? WHERE id = ?");
                            $upd->bind_param('si', $photoPath, $id);
                            $upd->execute();
                            $upd->close();
                        }
                    }
                }
                set_flash('success', 'User updated successfully.');
            } else {
                set_flash('error', 'Failed to update user.');
            }
            redirect('users.php');
        }
    }

    if ($postAction === 'reset_password') {
        $id = (int)$_POST['id'];
        $newPass = $_POST['new_password'] ?? '';
        if ($id && strlen($newPass) >= 4) {
            $hash = password_hash($newPass, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE users SET password=? WHERE id=?");
            $stmt->bind_param('si', $hash, $id);
            if ($stmt->execute()) {
                set_flash('success', 'Password reset successfully.');
            } else {
                set_flash('error', 'Failed to reset password.');
            }
            $stmt->close();
        } else {
            set_flash('error', 'Password must be at least 4 characters.');
        }
        redirect('users.php');
    }
}

// Handle delete
if ($action === 'delete' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $user = get_user($id);
    if ($user && $user['profile_photo']) {
        $photoFile = __DIR__ . '/../' . $user['profile_photo'];
        if (file_exists($photoFile)) {
            @unlink($photoFile);
        }
    }
    $stmt = $conn->prepare("DELETE FROM users WHERE id=?");
    $stmt->bind_param('i', $id);
    if ($stmt->execute()) {
        set_flash('success', 'User deleted successfully.');
    } else {
        set_flash('error', 'Failed to delete user.');
    }
    $stmt->close();
    redirect('users.php');
}

// Fetch users for list
$users = [];
$result = $conn->query("SELECT * FROM users ORDER BY created_at DESC");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
    }
}

$page_title = 'Manage Users';
$adminUser = get_admin($_SESSION['admin_id']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo sanitize($page_title); ?> - Tour Management</title>
    <link rel="stylesheet" href="<?php echo site_url('assets/css/bootstrap.min.css'); ?>">
    <link rel="stylesheet" href="<?php echo site_url('assets/css/all.min.css'); ?>">
    <link rel="stylesheet" href="<?php echo site_url('assets/css/style.css'); ?>">
</head>
<body>
<div class="container-fluid">
    <div class="row">
        <div class="col-lg-2 sidebar p-0 d-none d-lg-block">
            <div class="d-flex flex-column">
                <div class="p-3 text-white border-bottom border-secondary">
                    <h5 class="mb-0"><i class="fas fa-user-shield me-2"></i>Admin Panel</h5>
                    <small class="text-muted"><?php echo sanitize($adminUser['username']); ?></small>
                </div>
                <nav class="nav flex-column mt-2">
                    <a class="nav-link" href="<?php echo site_url('admin/index.php'); ?>"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
                    <a class="nav-link active" href="<?php echo site_url('admin/users.php'); ?>"><i class="fas fa-users"></i> Manage Users</a>
                    <a class="nav-link" href="<?php echo site_url('admin/admins.php'); ?>"><i class="fas fa-user-shield"></i> Manage Admins</a>
                    <a class="nav-link" href="<?php echo site_url('admin/tours.php'); ?>"><i class="fas fa-map-marked-alt"></i> Manage Tours</a>
                    <a class="nav-link" href="<?php echo site_url('admin/tour-gallery.php'); ?>"><i class="fas fa-images"></i> Tour Gallery</a>
                    <a class="nav-link" href="<?php echo site_url('admin/messages.php'); ?>"><i class="fas fa-envelope"></i> Messages</a>
                    <a class="nav-link" href="<?php echo site_url('admin/logout.php'); ?>"><i class="fas fa-sign-out-alt"></i> Logout</a>
                </nav>
            </div>
        </div>
        <div class="d-lg-none bg-dark text-white p-2 d-flex justify-content-between align-items-center">
            <span><i class="fas fa-user-shield me-1"></i>Admin</span>
            <button class="btn btn-sm btn-outline-light" data-bs-toggle="offcanvas" data-bs-target="#mobileNav"><i class="fas fa-bars"></i></button>
        </div>
        <div class="offcanvas offcanvas-start bg-dark" id="mobileNav">
            <div class="offcanvas-header text-white"><h5>Admin Menu</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas"></button></div>
            <div class="offcanvas-body"><nav class="nav flex-column">
                <a class="nav-link text-light" href="<?php echo site_url('admin/index.php'); ?>"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
                <a class="nav-link text-light active" href="<?php echo site_url('admin/users.php'); ?>"><i class="fas fa-users"></i> Manage Users</a>
                <a class="nav-link text-light" href="<?php echo site_url('admin/admins.php'); ?>"><i class="fas fa-user-shield"></i> Manage Admins</a>
                <a class="nav-link text-light" href="<?php echo site_url('admin/tours.php'); ?>"><i class="fas fa-map-marked-alt"></i> Manage Tours</a>
                <a class="nav-link text-light" href="<?php echo site_url('admin/tour-gallery.php'); ?>"><i class="fas fa-images"></i> Tour Gallery</a>
                <a class="nav-link text-light" href="<?php echo site_url('admin/messages.php'); ?>"><i class="fas fa-envelope"></i> Messages</a>
                <a class="nav-link text-light" href="<?php echo site_url('admin/logout.php'); ?>"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </nav></div>
        </div>

        <div class="col-lg-10 admin-content">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2><i class="fas fa-users me-2"></i>Manage Users</h2>
                <a href="?action=create" class="btn btn-primary"><i class="fas fa-user-plus me-1"></i>Add New User</a>
            </div>
            <?php $flash = get_flash(); if ($flash): foreach ($flash as $type => $msg): ?>
                <div class="alert alert-<?php echo $type === 'error' ? 'danger' : 'success'; ?> alert-dismissible fade show">
                    <?php echo sanitize($msg); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endforeach; endif; ?>

            <?php if ($action === 'create'): ?>
                <!-- Create User Form -->
                <div class="card shadow">
                    <div class="card-header"><h5 class="mb-0"><i class="fas fa-user-plus me-2"></i>Register New User</h5></div>
                    <div class="card-body">
                        <form method="post" enctype="multipart/form-data">
                            <input type="hidden" name="action" value="create">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Full Name <span class="text-danger">*</span></label>
                                    <input type="text" name="full_name" class="form-control" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Email <span class="text-danger">*</span></label>
                                    <input type="email" name="email" class="form-control" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Mobile Number <span class="text-danger">*</span></label>
                                    <input type="text" name="mobile" class="form-control" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Date of Birth <span class="text-danger">*</span></label>
                                    <input type="date" name="date_of_birth" class="form-control" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Marital Status</label>
                                    <select name="marital_status" class="form-select" data-marital-toggle="spouseField">
                                        <option value="single">Single</option>
                                        <option value="married">Married</option>
                                    </select>
                                </div>
                                <div class="col-md-6" id="spouseField" style="display:none;">
                                    <label class="form-label">Spouse Name</label>
                                    <input type="text" name="spouse_name" class="form-control">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Profile Photo (JPG/PNG/JPEG, max 1MB)</label>
                                    <input type="file" name="profile_photo" class="form-control" accept=".jpg,.jpeg,.png" data-image-only>
                                </div>
                                <div class="col-md-6"></div>
                                <div class="col-md-6">
                                    <label class="form-label">Username <span class="text-danger">*</span></label>
                                    <input type="text" name="username" class="form-control" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Password <span class="text-danger">*</span></label>
                                    <input type="text" name="password" class="form-control" required>
                                </div>
                            </div>
                            <div class="mt-4">
                                <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i>Create User</button>
                                <a href="users.php" class="btn btn-secondary">Cancel</a>
                            </div>
                        </form>
                    </div>
                </div>

            <?php elseif ($action === 'edit' && isset($_GET['id'])):
                $editUser = get_user((int)$_GET['id']);
                if ($editUser): ?>
                <div class="card shadow">
                    <div class="card-header"><h5 class="mb-0"><i class="fas fa-user-edit me-2"></i>Edit User: <?php echo sanitize($editUser['username']); ?></h5></div>
                    <div class="card-body">
                        <form method="post" enctype="multipart/form-data">
                            <input type="hidden" name="action" value="edit">
                            <input type="hidden" name="id" value="<?php echo $editUser['id']; ?>">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Full Name</label>
                                    <input type="text" name="full_name" class="form-control" value="<?php echo sanitize($editUser['full_name']); ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Email</label>
                                    <input type="email" name="email" class="form-control" value="<?php echo sanitize($editUser['email']); ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Mobile Number</label>
                                    <input type="text" name="mobile" class="form-control" value="<?php echo sanitize($editUser['mobile']); ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Date of Birth</label>
                                    <input type="date" name="date_of_birth" class="form-control" value="<?php echo $editUser['date_of_birth']; ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Marital Status</label>
                                    <select name="marital_status" class="form-select" data-marital-toggle="spouseFieldEdit">
                                        <option value="single" <?php echo $editUser['marital_status'] === 'single' ? 'selected' : ''; ?>>Single</option>
                                        <option value="married" <?php echo $editUser['marital_status'] === 'married' ? 'selected' : ''; ?>>Married</option>
                                    </select>
                                </div>
                                <div class="col-md-6" id="spouseFieldEdit" style="display:none;">
                                    <label class="form-label">Spouse Name</label>
                                    <input type="text" name="spouse_name" class="form-control" value="<?php echo sanitize($editUser['spouse_name'] ?? ''); ?>">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Profile Photo (replaces current)</label>
                                    <input type="file" name="profile_photo" class="form-control" accept=".jpg,.jpeg,.png" data-image-only>
                                    <?php if ($editUser['profile_photo']): ?>
                                        <img src="<?php echo site_url($editUser['profile_photo']); ?>" class="rounded mt-2" style="width:60px;height:60px;object-fit:cover;">
                                    <?php endif; ?>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Username</label>
                                    <input type="text" name="username" class="form-control" value="<?php echo sanitize($editUser['username']); ?>" required>
                                </div>
                            </div>
                            <div class="mt-4">
                                <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i>Update User</button>
                                <a href="users.php" class="btn btn-secondary">Cancel</a>
                            </div>
                        </form>
                    </div>
                </div>
                <?php else: ?>
                    <div class="alert alert-warning">User not found.</div>
                <?php endif; ?>

            <?php else: ?>
                <!-- User List -->
                <div class="card shadow">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead>
                                    <tr>
                                        <th>Photo</th>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>Mobile</th>
                                        <th>Username</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($users)): ?>
                                        <tr><td colspan="6" class="text-center text-muted py-4">No users registered yet. <a href="?action=create">Add one now</a>.</td></tr>
                                    <?php else: foreach ($users as $u): ?>
                                    <tr>
                                        <td>
                                            <?php if ($u['profile_photo'] && file_exists(__DIR__ . '/../' . $u['profile_photo'])): ?>
                                                <img src="<?php echo site_url($u['profile_photo']); ?>" style="width:40px;height:40px;object-fit:cover;border-radius:50%;">
                                            <?php else: ?>
                                                <i class="fas fa-user-circle fa-2x text-muted"></i>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo sanitize($u['full_name']); ?></td>
                                        <td><?php echo sanitize($u['email']); ?></td>
                                        <td><?php echo sanitize($u['mobile']); ?></td>
                                        <td><?php echo sanitize($u['username']); ?></td>
                                        <td>
                                            <a href="?action=edit&id=<?php echo $u['id']; ?>" class="btn btn-sm btn-outline-primary" title="Edit"><i class="fas fa-edit"></i></a>
                                            <button class="btn btn-sm btn-outline-warning" data-bs-toggle="modal" data-bs-target="#resetModal<?php echo $u['id']; ?>" title="Reset Password"><i class="fas fa-key"></i></button>
                                            <a href="?action=delete&id=<?php echo $u['id']; ?>" class="btn btn-sm btn-outline-danger" title="Delete" onclick="return confirm('Delete this user?')"><i class="fas fa-trash"></i></a>
                                        </td>
                                    </tr>
                                    <!-- Reset Password Modal -->
                                    <div class="modal fade" id="resetModal<?php echo $u['id']; ?>" tabindex="-1">
                                        <div class="modal-dialog modal-dialog-centered">
                                            <div class="modal-content">
                                                <div class="modal-header"><h5 class="modal-title">Reset Password for <?php echo sanitize($u['username']); ?></h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                                                <form method="post">
                                                    <input type="hidden" name="action" value="reset_password">
                                                    <input type="hidden" name="id" value="<?php echo $u['id']; ?>">
                                                    <div class="modal-body">
                                                        <label class="form-label">New Password</label>
                                                        <input type="text" name="new_password" class="form-control" required minlength="4">
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="submit" class="btn btn-warning"><i class="fas fa-key me-1"></i>Reset Password</button>
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endforeach; endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
<script src="<?php echo site_url('assets/js/bootstrap.bundle.min.js'); ?>"></script>
<script src="<?php echo site_url('assets/js/all.min.js'); ?>"></script>
<script src="<?php echo site_url('assets/js/main.js'); ?>"></script>
<script src="<?php echo site_url('assets/js/session-timeout.js'); ?>"></script>
</body>
</html>
