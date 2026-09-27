<?php
// includes/header.php — unified top navbar (guest / user / admin)
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/functions.php';
$flash = get_flash();
$nav = $nav_active ?? '';
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
        <?php if (is_admin_logged_in()): ?>
            <a class="navbar-brand" href="<?php echo site_url('admin/index.php'); ?>">
                <i class="fas fa-mountain-sun me-2"></i>MIS Tour Group
            </a>
        <?php elseif (is_user_logged_in()): ?>
            <a class="navbar-brand" href="<?php echo site_url('user/dashboard.php'); ?>">
                <i class="fas fa-mountain-sun me-2"></i>MIS Tour Group
            </a>
        <?php else: ?>
            <a class="navbar-brand" href="<?php echo site_url('index.php'); ?>">
                <i class="fas fa-mountain-sun me-2"></i>MIS Tour Group
            </a>
        <?php endif; ?>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navMain">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navMain">
            <ul class="navbar-nav ms-auto align-items-lg-center">

                <?php if (is_admin_logged_in()): ?>
                    <!-- Admin navbar -->
                    <li class="nav-item">
                        <a class="nav-link <?php echo $nav === 'admin_dashboard' ? 'active' : ''; ?>" href="<?php echo site_url('admin/index.php'); ?>">
                            <i class="fas fa-tachometer-alt me-1"></i>Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $nav === 'admin_users' ? 'active' : ''; ?>" href="<?php echo site_url('admin/users.php'); ?>">
                            <i class="fas fa-users me-1"></i>Manage Users
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $nav === 'admin_admins' ? 'active' : ''; ?>" href="<?php echo site_url('admin/admins.php'); ?>">
                            <i class="fas fa-user-shield me-1"></i>Manage Admins
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $nav === 'admin_tours' ? 'active' : ''; ?>" href="<?php echo site_url('admin/tours.php'); ?>">
                            <i class="fas fa-map-marked-alt me-1"></i>Manage Tours
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $nav === 'admin_gallery' ? 'active' : ''; ?>" href="<?php echo site_url('admin/tour-gallery.php'); ?>">
                            <i class="fas fa-images me-1"></i>Tour Gallery
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $nav === 'admin_messages' ? 'active' : ''; ?>" href="<?php echo site_url('admin/messages.php'); ?>">
                            <i class="fas fa-envelope me-1"></i>Messages
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo site_url('index.php'); ?>">
                            <i class="fas fa-home me-1"></i>Home
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="btn btn-outline-light btn-sm ms-lg-2" href="<?php echo site_url('admin/logout.php'); ?>">
                            <i class="fas fa-sign-out-alt"></i> Logout
                        </a>
                    </li>

                <?php elseif (is_user_logged_in()): ?>
                    <!-- User navbar -->
                    <li class="nav-item">
                        <a class="nav-link <?php echo $nav === 'user_dashboard' ? 'active' : ''; ?>" href="<?php echo site_url('user/dashboard.php'); ?>">
                            <i class="fas fa-tachometer-alt me-1"></i>Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $nav === 'user_profile' ? 'active' : ''; ?>" href="<?php echo site_url('user/profile.php'); ?>">
                            <i class="fas fa-user me-1"></i>My Profile
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $nav === 'user_chat' ? 'active' : ''; ?>" href="<?php echo site_url('user/chat.php'); ?>">
                            <i class="fas fa-comments me-1"></i>Chat
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $nav === 'user_contact' ? 'active' : ''; ?>" href="<?php echo site_url('user/dashboard.php#contact'); ?>">
                            <i class="fas fa-envelope me-1"></i>Contact
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo site_url('index.php'); ?>">
                            <i class="fas fa-home me-1"></i>Home
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="btn btn-outline-light btn-sm ms-lg-2" href="<?php echo site_url('user/logout.php'); ?>">
                            <i class="fas fa-sign-out-alt"></i> Logout
                        </a>
                    </li>

                <?php else: ?>
                    <!-- Guest / public navbar -->
                    <li class="nav-item">
                        <a class="nav-link <?php echo $nav === 'home' ? 'active' : ''; ?>" href="<?php echo site_url('index.php'); ?>">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo site_url('index.php#past-tours'); ?>">Past Tours</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo site_url('index.php#upcoming-tours'); ?>">Upcoming Tours</a>
                    </li>
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
        <i class="fas fa-clock me-1"></i>You were logged out due to 20 minutes of inactivity.
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
</div>
<?php endif; ?>

<?php if (!is_logged_in()): ?>
<?php require_once __DIR__ . '/login-modal.php'; ?>
<?php endif; ?>
