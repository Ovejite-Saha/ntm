<?php
// user/profile.php — View & edit profile info, upload/replace profile photo
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/functions.php';
require_user();

$userId = (int)$_SESSION['user_id'];
$conn = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postAction = $_POST['action'] ?? '';

    if ($postAction === 'update_profile') {
        $fullName = trim($_POST['full_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $mobile = trim($_POST['mobile'] ?? '');
        $dob = $_POST['date_of_birth'] ?? '';
        $marital = $_POST['marital_status'] ?? 'single';
        $spouse = trim($_POST['spouse_name'] ?? '');

        if ($fullName && $email && $mobile && $dob) {
            $stmt = $conn->prepare("UPDATE users SET full_name=?, email=?, mobile=?, date_of_birth=?, marital_status=?, spouse_name=? WHERE id=?");
            $stmt->bind_param('ssssssi', $fullName, $email, $mobile, $dob, $marital, $spouse, $userId);
            if ($stmt->execute()) {
                $stmt->close();

                // Handle profile photo upload
                if (isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] === UPLOAD_ERR_OK) {
                    $user = get_user($userId);
                    $username = $user['username'];
                    $ext = strtolower(pathinfo($_FILES['profile_photo']['name'], PATHINFO_EXTENSION));
                    if (in_array($ext, ['jpg', 'jpeg', 'png'])) {
                        // Delete old photo if exists (replace previous image stored as username)
                        $oldPhoto = __DIR__ . '/../uploads/profile_photos/' . $username . '.jpg';
                        if (file_exists($oldPhoto)) @unlink($oldPhoto);

                        $destPath = __DIR__ . '/../uploads/profile_photos/' . $username . '.jpg';
                        $result = process_profile_image($_FILES['profile_photo']['tmp_name'], $destPath);
                        if (is_string($result) && file_exists($result)) {
                            $photoPath = 'uploads/profile_photos/' . $username . '.jpg';
                            $upd = $conn->prepare("UPDATE users SET profile_photo = ? WHERE id = ?");
                            $upd->bind_param('si', $photoPath, $userId);
                            $upd->execute();
                            $upd->close();
                            set_flash('success', 'Profile and photo updated successfully.');
                        } else {
                            set_flash('error', 'Profile updated but image processing failed: ' . (is_string($result) ? $result : 'Unknown error'));
                        }
                        redirect('profile.php');
                    } else {
                        set_flash('error', 'Only JPG, PNG, JPEG formats allowed.');
                    }
                }
                set_flash('success', 'Profile updated successfully.');
            } else {
                set_flash('error', 'Failed to update profile.');
            }
            redirect('profile.php');
        } else {
            set_flash('error', 'All required fields must be filled.');
            redirect('profile.php');
        }
    }

    if ($postAction === 'change_password') {
        $current = $_POST['current_password'] ?? '';
        $new = $_POST['new_password'] ?? '';
        $confirm = $_POST['confirm_password'] ?? '';

        $user = get_user($userId);
        if (!password_verify($current, $user['password'])) {
            set_flash('error', 'Current password is incorrect.');
            redirect('profile.php');
        }
        if (strlen($new) < 4) {
            set_flash('error', 'New password must be at least 4 characters.');
            redirect('profile.php');
        }
        if ($new !== $confirm) {
            set_flash('error', 'New passwords do not match.');
            redirect('profile.php');
        }
        $hash = password_hash($new, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE users SET password=? WHERE id=?");
        $stmt->bind_param('si', $hash, $userId);
        if ($stmt->execute()) {
            set_flash('success', 'Password changed successfully.');
        } else {
            set_flash('error', 'Failed to change password.');
        }
        $stmt->close();
        redirect('profile.php');
    }
}

$user = get_user($userId);
$page_title = 'My Profile';
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
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container">
        <a class="navbar-brand" href="<?php echo site_url('user/dashboard.php'); ?>"><i class="fas fa-mountain-sun me-2"></i>Tour Group</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#userNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="userNav">
            <ul class="navbar-nav ms-auto align-items-lg-center">
                <li class="nav-item"><a class="nav-link" href="<?php echo site_url('user/dashboard.php'); ?>"><i class="fas fa-tachometer-alt me-1"></i>Dashboard</a></li>
                <li class="nav-item"><a class="nav-link active" href="<?php echo site_url('user/profile.php'); ?>"><i class="fas fa-user me-1"></i>My Profile</a></li>
                <li class="nav-item"><a class="nav-link" href="<?php echo site_url('index.php'); ?>"><i class="fas fa-home me-1"></i>Home</a></li>
                <li class="nav-item"><a class="btn btn-outline-light btn-sm ms-lg-2" href="<?php echo site_url('user/logout.php'); ?>"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </div>
    </div>
</nav>

<div class="container mt-4 mb-5">
    <?php $flash = get_flash(); if ($flash): foreach ($flash as $type => $msg): ?>
        <div class="alert alert-<?php echo $type === 'error' ? 'danger' : 'success'; ?> alert-dismissible fade show">
            <?php echo sanitize($msg); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endforeach; endif; ?>

    <h2 class="mb-4"><i class="fas fa-user me-2"></i>My Profile</h2>

    <div class="row g-4">
        <!-- Profile Info -->
        <div class="col-lg-8">
            <div class="card shadow">
                <div class="card-header"><h5 class="mb-0"><i class="fas fa-id-card me-2"></i>Edit Profile Information</h5></div>
                <div class="card-body">
                    <form method="post" enctype="multipart/form-data">
                        <input type="hidden" name="action" value="update_profile">

                        <!-- Current Photo -->
                        <div class="text-center mb-4">
                            <?php if ($user['profile_photo'] && file_exists(__DIR__ . '/../' . $user['profile_photo'])): ?>
                                <img src="<?php echo site_url($user['profile_photo']); ?>" class="profile-img" id="previewImg">
                            <?php else: ?>
                                <i class="fas fa-user-circle fa-5x text-muted" id="previewImg"></i>
                            <?php endif; ?>
                        </div>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Full Name <span class="text-danger">*</span></label>
                                <input type="text" name="full_name" class="form-control" value="<?php echo sanitize($user['full_name']); ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Email <span class="text-danger">*</span></label>
                                <input type="email" name="email" class="form-control" value="<?php echo sanitize($user['email']); ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Mobile Number <span class="text-danger">*</span></label>
                                <input type="text" name="mobile" class="form-control" value="<?php echo sanitize($user['mobile']); ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Date of Birth <span class="text-danger">*</span></label>
                                <input type="date" name="date_of_birth" class="form-control" value="<?php echo $user['date_of_birth']; ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Marital Status</label>
                                <select name="marital_status" class="form-select" data-marital-toggle="spouseField">
                                    <option value="single" <?php echo $user['marital_status'] === 'single' ? 'selected' : ''; ?>>Single</option>
                                    <option value="married" <?php echo $user['marital_status'] === 'married' ? 'selected' : ''; ?>>Married</option>
                                </select>
                            </div>
                            <div class="col-md-6" id="spouseField" style="display:none;">
                                <label class="form-label">Spouse Name</label>
                                <input type="text" name="spouse_name" class="form-control" value="<?php echo sanitize($user['spouse_name'] ?? ''); ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Username (cannot change)</label>
                                <input type="text" class="form-control" value="<?php echo sanitize($user['username']); ?>" disabled>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Profile Photo (JPG/PNG/JPEG, max 1MB)</label>
                                <input type="file" name="profile_photo" class="form-control" accept=".jpg,.jpeg,.png" data-image-only>
                                <small class="text-muted">Uploading a new photo will replace your current one.</small>
                            </div>
                        </div>
                        <div class="mt-4">
                            <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i>Save Changes</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Change Password -->
        <div class="col-lg-4">
            <div class="card shadow">
                <div class="card-header"><h5 class="mb-0"><i class="fas fa-key me-2"></i>Change Password</h5></div>
                <div class="card-body">
                    <form method="post">
                        <input type="hidden" name="action" value="change_password">
                        <div class="mb-3">
                            <label class="form-label">Current Password</label>
                            <input type="password" name="current_password" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">New Password</label>
                            <input type="password" name="new_password" class="form-control" required minlength="4">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Confirm New Password</label>
                            <input type="password" name="confirm_password" class="form-control" required>
                        </div>
                        <button type="submit" class="btn btn-warning w-100"><i class="fas fa-key me-1"></i>Change Password</button>
                    </form>
                </div>
            </div>

            <!-- Account Info -->
            <div class="card shadow mt-4">
                <div class="card-header"><h5 class="mb-0"><i class="fas fa-info-circle me-2"></i>Account Info</h5></div>
                <div class="card-body">
                    <p class="mb-1"><strong>Username:</strong> <?php echo sanitize($user['username']); ?></p>
                    <p class="mb-1"><strong>Member since:</strong> <?php echo date('M j, Y', strtotime($user['created_at'])); ?></p>
                </div>
            </div>
        </div>
    </div>
</div>

<footer class="bg-dark text-light mt-5 py-4">
    <div class="container text-center">
        <p class="mb-1"><i class="fas fa-mountain-sun me-2"></i>Tour Group Management System</p>
        <small class="text-muted">&copy; <?php echo date('Y'); ?> Tour Group. All rights reserved.</small>
    </div>
</footer>
<script src="<?php echo site_url('assets/js/bootstrap.bundle.min.js'); ?>"></script>
<script src="<?php echo site_url('assets/js/all.min.js'); ?>"></script>
<script src="<?php echo site_url('assets/js/main.js'); ?>"></script>
<script src="<?php echo site_url('assets/js/session-timeout.js'); ?>"></script>
</body>
</html>
