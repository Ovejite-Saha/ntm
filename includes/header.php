<?php
// includes/header.php — navigation header with login modal popup
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/functions.php';
$flash = get_flash();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? sanitize($page_title) . ' - ' : ''; ?>MIS Tour Group</title>
    <link rel="stylesheet" href="<?php echo site_url('assets/css/bootstrap.min.css'); ?>">
    <link rel="stylesheet" href="<?php echo site_url('assets/css/all.min.css'); ?>">
    <link rel="stylesheet" href="<?php echo site_url('assets/css/style.css'); ?>">
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark sticky-top">
    <div class="container">
        <a class="navbar-brand" href="<?php echo site_url('index.php'); ?>">
            <i class="fas fa-mountain-sun me-2"></i>MIS Tour Group
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navMain">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navMain">
            <ul class="navbar-nav ms-auto align-items-lg-center">
                <li class="nav-item"><a class="nav-link" href="<?php echo site_url('index.php'); ?>">Home</a></li>
                <li class="nav-item"><a class="nav-link" href="<?php echo site_url('index.php#past-tours'); ?>">Past Tours</a></li>
                <li class="nav-item"><a class="nav-link" href="<?php echo site_url('index.php#upcoming-tours'); ?>">Upcoming Tours</a></li>
                <li class="nav-item"><a class="nav-link" href="<?php echo site_url('index.php#contact'); ?>">Contact</a></li>
                <?php if (is_admin_logged_in()): ?>
                    <li class="nav-item"><a class="btn btn-warning btn-sm ms-lg-2" href="<?php echo site_url('admin/index.php'); ?>"><i class="fas fa-user-shield me-1"></i>Admin Panel</a></li>
                    <li class="nav-item"><a class="btn btn-outline-light btn-sm ms-2" href="<?php echo site_url('admin/logout.php'); ?>"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                <?php elseif (is_user_logged_in()): ?>
                    <li class="nav-item"><a class="btn btn-info btn-sm ms-lg-2" href="<?php echo site_url('user/dashboard.php'); ?>"><i class="fas fa-user me-1"></i>Dashboard</a></li>
                    <li class="nav-item"><a class="btn btn-outline-light btn-sm ms-2" href="<?php echo site_url('user/logout.php'); ?>"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                <?php else: ?>
                    <li class="nav-item">
                        <button class="btn btn-primary btn-sm ms-lg-2" data-bs-toggle="modal" data-bs-target="#loginModal">
                            <i class="fas fa-sign-in-alt me-1"></i>Login
                        </button>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

<?php if ($flash): ?>
<div class="container mt-3">
    <?php foreach ($flash as $type => $msg): ?>
        <div class="alert alert-<?php echo $type === 'error' ? 'danger' : ($type === 'success' ? 'success' : 'info'); ?> alert-dismissible fade show">
            <?php echo sanitize($msg); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<?php if (isset($_GET['timeout']) && $_GET['timeout'] == 1 && !is_logged_in()): ?>
<div class="container mt-3">
    <div class="alert alert-warning alert-dismissible fade show">
        <i class="fas fa-clock me-1"></i>You were logged out due to 2 minutes of inactivity.
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/login-modal.php'; ?>
