<?php
// admin/admins.php — Manage admins: create with username/password only, edit, delete
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/functions.php';
require_admin();

$conn = db();
$action = $_GET['action'] ?? 'list';
$currentAdminId = (int)$_SESSION['admin_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postAction = $_POST['action'] ?? '';

    if ($postAction === 'create') {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        if ($username && strlen($password) >= 4) {
            $chk = $conn->prepare("SELECT id FROM admins WHERE username = ?");
            $chk->bind_param('s', $username);
            $chk->execute();
            $chk->store_result();
            if ($chk->num_rows > 0) {
                $chk->close();
                set_flash('error', 'Username already exists.');
                redirect('admins.php?action=create');
            }
            $chk->close();

            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO admins (username, password) VALUES (?, ?)");
            $stmt->bind_param('ss', $username, $hash);
            if ($stmt->execute()) {
                $stmt->close();
                set_flash('success', 'Admin created. The new admin should login and complete their profile.');
            } else {
                set_flash('error', 'Failed to create admin.');
            }
            redirect('admins.php');
        } else {
            set_flash('error', 'Username and password (min 4 chars) required.');
            redirect('admins.php?action=create');
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

        // Handle profile photo
        $photoPath = null;
        if (isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] === UPLOAD_ERR_OK) {
            $admin = get_admin($id);
            $username = $admin['username'];
            $ext = strtolower(pathinfo($_FILES['profile_photo']['name'], PATHINFO_EXTENSION));
            if (in_array($ext, ['jpg', 'jpeg', 'png'])) {
                $destPath = __DIR__ . '/../uploads/profile_photos/' . $username . '.jpg';
                $result = process_profile_image($_FILES['profile_photo']['tmp_name'], $destPath);
                if (is_string($result) && file_exists($result)) {
                    $photoPath = 'uploads/profile_photos/' . $username . '.jpg';
                }
            }
        }

        if ($photoPath) {
            $stmt = $conn->prepare("UPDATE admins SET full_name=?, email=?, mobile=?, date_of_birth=?, marital_status=?, spouse_name=?, profile_photo=? WHERE id=?");
            $stmt->bind_param('sssssssi', $fullName, $email, $mobile, $dob, $marital, $spouse, $photoPath, $id);
        } else {
            $stmt = $conn->prepare("UPDATE admins SET full_name=?, email=?, mobile=?, date_of_birth=?, marital_status=?, spouse_name=? WHERE id=?");
            $stmt->bind_param('ssssssi', $fullName, $email, $mobile, $dob, $marital, $spouse, $id);
        }
        if ($stmt->execute()) {
            set_flash('success', 'Admin profile updated.');
        } else {
            set_flash('error', 'Failed to update admin.');
        }
        $stmt->close();
        redirect('admins.php');
    }

    if ($postAction === 'reset_password') {
        $id = (int)$_POST['id'];
        $newPass = $_POST['new_password'] ?? '';
        if ($id && strlen($newPass) >= 4) {
            $hash = password_hash($newPass, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE admins SET password=? WHERE id=?");
            $stmt->bind_param('si', $hash, $id);
            if ($stmt->execute()) {
                set_flash('success', 'Admin password changed.');
            } else {
                set_flash('error', 'Failed to change password.');
            }
            $stmt->close();
        } else {
            set_flash('error', 'Password must be at least 4 characters.');
        }
        redirect('admins.php');
    }
}

if ($action === 'delete' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    if ($id === $currentAdminId) {
        set_flash('error', 'You cannot delete your own account.');
        redirect('admins.php');
    }
    $admin = get_admin($id);
    if ($admin && $admin['profile_photo']) {
        $photoFile = __DIR__ . '/../' . $admin['profile_photo'];
        if (file_exists($photoFile)) @unlink($photoFile);
    }
    $stmt = $conn->prepare("DELETE FROM admins WHERE id=?");
    $stmt->bind_param('i', $id);
    if ($stmt->execute()) {
        set_flash('success', 'Admin deleted.');
    } else {
        set_flash('error', 'Failed to delete admin.');
    }
    $stmt->close();
    redirect('admins.php');
}

$admins = [];
$result = $conn->query("SELECT * FROM admins ORDER BY created_at DESC");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $admins[] = $row;
    }
}

$page_title = 'Manage Admins';
$adminUser = get_admin($currentAdminId);
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
                    <a class="nav-link" href="<?php echo site_url('admin/users.php'); ?>"><i class="fas fa-users"></i> Manage Users</a>
                    <a class="nav-link active" href="<?php echo site_url('admin/admins.php'); ?>"><i class="fas fa-user-shield"></i> Manage Admins</a>
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
                <a class="nav-link text-light" href="<?php echo site_url('admin/users.php'); ?>"><i class="fas fa-users"></i> Manage Users</a>
                <a class="nav-link text-light active" href="<?php echo site_url('admin/admins.php'); ?>"><i class="fas fa-user-shield"></i> Manage Admins</a>
                <a class="nav-link text-light" href="<?php echo site_url('admin/tours.php'); ?>"><i class="fas fa-map-marked-alt"></i> Manage Tours</a>
                <a class="nav-link text-light" href="<?php echo site_url('admin/tour-gallery.php'); ?>"><i class="fas fa-images"></i> Tour Gallery</a>
                <a class="nav-link text-light" href="<?php echo site_url('admin/messages.php'); ?>"><i class="fas fa-envelope"></i> Messages</a>
                <a class="nav-link text-light" href="<?php echo site_url('admin/logout.php'); ?>"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </nav></div>
        </div>

        <div class="col-lg-10 admin-content">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2><i class="fas fa-user-shield me-2"></i>Manage Admins</h2>
                <a href="?action=create" class="btn btn-dark"><i class="fas fa-user-plus me-1"></i>Add New Admin</a>
            </div>
            <?php $flash = get_flash(); if ($flash): foreach ($flash as $type => $msg): ?>
                <div class="alert alert-<?php echo $type === 'error' ? 'danger' : 'success'; ?> alert-dismissible fade show">
                    <?php echo sanitize($msg); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endforeach; endif; ?>

            <?php if ($action === 'create'): ?>
                <div class="card shadow">
                    <div class="card-header"><h5 class="mb-0"><i class="fas fa-user-plus me-2"></i>Create New Admin</h5></div>
                    <div class="card-body">
                        <div class="alert alert-info"><i class="fas fa-info-circle me-1"></i> Only username and password are needed. The new admin can complete their profile after logging in.</div>
                        <form method="post">
                            <input type="hidden" name="action" value="create">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Username <span class="text-danger">*</span></label>
                                    <input type="text" name="username" class="form-control" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Password <span class="text-danger">*</span></label>
                                    <input type="text" name="password" class="form-control" required minlength="4">
                                </div>
                            </div>
                            <div class="mt-4">
                                <button type="submit" class="btn btn-dark"><i class="fas fa-save me-1"></i>Create Admin</button>
                                <a href="admins.php" class="btn btn-secondary">Cancel</a>
                            </div>
                        </form>
                    </div>
                </div>

            <?php elseif ($action === 'edit' && isset($_GET['id'])):
                $editAdmin = get_admin((int)$_GET['id']);
                if ($editAdmin): ?>
                <div class="card shadow">
                    <div class="card-header"><h5 class="mb-0"><i class="fas fa-user-edit me-2"></i>Edit Admin: <?php echo sanitize($editAdmin['username']); ?></h5></div>
                    <div class="card-body">
                        <form method="post" enctype="multipart/form-data">
                            <input type="hidden" name="action" value="edit">
                            <input type="hidden" name="id" value="<?php echo $editAdmin['id']; ?>">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Full Name</label>
                                    <input type="text" name="full_name" class="form-control" value="<?php echo sanitize($editAdmin['full_name'] ?? ''); ?>">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Email</label>
                                    <input type="email" name="email" class="form-control" value="<?php echo sanitize($editAdmin['email'] ?? ''); ?>">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Mobile Number</label>
                                    <input type="text" name="mobile" class="form-control" value="<?php echo sanitize($editAdmin['mobile'] ?? ''); ?>">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Date of Birth</label>
                                    <input type="date" name="date_of_birth" class="form-control" value="<?php echo $editAdmin['date_of_birth'] ?? ''; ?>">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Marital Status</label>
                                    <select name="marital_status" class="form-select" data-marital-toggle="adminSpouseField">
                                        <option value="single" <?php echo ($editAdmin['marital_status'] ?? '') === 'single' ? 'selected' : ''; ?>>Single</option>
                                        <option value="married" <?php echo ($editAdmin['marital_status'] ?? '') === 'married' ? 'selected' : ''; ?>>Married</option>
                                    </select>
                                </div>
                                <div class="col-md-6" id="adminSpouseField" style="display:none;">
                                    <label class="form-label">Spouse Name</label>
                                    <input type="text" name="spouse_name" class="form-control" value="<?php echo sanitize($editAdmin['spouse_name'] ?? ''); ?>">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Profile Photo (JPG/PNG/JPEG, max 1MB)</label>
                                    <input type="file" name="profile_photo" class="form-control" accept=".jpg,.jpeg,.png" data-image-only>
                                    <?php if (!empty($editAdmin['profile_photo'])): ?>
                                        <img src="<?php echo site_url($editAdmin['profile_photo']); ?>" class="rounded mt-2" style="width:60px;height:60px;object-fit:cover;">
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="mt-4">
                                <button type="submit" class="btn btn-dark"><i class="fas fa-save me-1"></i>Update Admin</button>
                                <a href="admins.php" class="btn btn-secondary">Cancel</a>
                            </div>
                        </form>
                    </div>
                </div>
                <?php else: ?>
                    <div class="alert alert-warning">Admin not found.</div>
                <?php endif; ?>

            <?php else: ?>
                <div class="card shadow">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead>
                                    <tr>
                                        <th>Photo</th>
                                        <th>Username</th>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($admins as $a): ?>
                                    <tr>
                                        <td>
                                            <?php if (!empty($a['profile_photo']) && file_exists(__DIR__ . '/../' . $a['profile_photo'])): ?>
                                                <img src="<?php echo site_url($a['profile_photo']); ?>" style="width:40px;height:40px;object-fit:cover;border-radius:50%;">
                                            <?php else: ?>
                                                <i class="fas fa-user-shield fa-2x text-dark"></i>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo sanitize($a['username']); ?><?php echo $a['id'] === $currentAdminId ? ' <span class="badge bg-info">You</span>' : ''; ?></td>
                                        <td><?php echo sanitize($a['full_name'] ?? '<span class="text-muted fst-italic">Incomplete</span>'); ?></td>
                                        <td><?php echo sanitize($a['email'] ?? '<span class="text-muted">—</span>'); ?></td>
                                        <td>
                                            <a href="?action=edit&id=<?php echo $a['id']; ?>" class="btn btn-sm btn-outline-primary" title="Edit"><i class="fas fa-edit"></i></a>
                                            <button class="btn btn-sm btn-outline-warning" data-bs-toggle="modal" data-bs-target="#adminResetModal<?php echo $a['id']; ?>" title="Change Password"><i class="fas fa-key"></i></button>
                                            <?php if ($a['id'] !== $currentAdminId): ?>
                                            <a href="?action=delete&id=<?php echo $a['id']; ?>" class="btn btn-sm btn-outline-danger" title="Delete" onclick="return confirm('Delete this admin?')"><i class="fas fa-trash"></i></a>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <div class="modal fade" id="adminResetModal<?php echo $a['id']; ?>" tabindex="-1">
                                        <div class="modal-dialog modal-dialog-centered">
                                            <div class="modal-content">
                                                <div class="modal-header"><h5 class="modal-title">Change Password: <?php echo sanitize($a['username']); ?></h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                                                <form method="post">
                                                    <input type="hidden" name="action" value="reset_password">
                                                    <input type="hidden" name="id" value="<?php echo $a['id']; ?>">
                                                    <div class="modal-body">
                                                        <label class="form-label">New Password</label>
                                                        <input type="text" name="new_password" class="form-control" required minlength="4">
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="submit" class="btn btn-warning"><i class="fas fa-key me-1"></i>Change Password</button>
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
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
