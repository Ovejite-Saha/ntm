<?php
// login-redirect.php — shows login page or redirects after timeout
// This is the "login page" shown when not on homepage (e.g. after timeout from admin/user panel)
require_once __DIR__ . '/config/session.php';
require_once __DIR__ . '/config/functions.php';

if (is_logged_in()) {
    if (is_admin_logged_in()) {
        redirect('admin/index.php');
    } else {
        redirect('user/dashboard.php');
    }
}
$page_title = 'Login';
$timeout_msg = isset($_GET['timeout']);
require_once __DIR__ . '/includes/header.php';
?>
<div class="container mt-5 mb-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <?php if ($timeout_msg): ?>
                <div class="alert alert-warning">
                    <i class="fas fa-clock me-1"></i>Your session expired due to 2 minutes of inactivity. Please login again.
                </div>
            <?php endif; ?>
            <div class="card shadow">
                <div class="card-body">
                    <h3 class="text-center mb-3"><i class="fas fa-sign-in-alt me-2"></i>Login</h3>
                    <ul class="nav nav-tabs mb-3">
                        <li class="nav-item">
                            <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#userLogin">
                                <i class="fas fa-user me-1"></i>User Login
                            </button>
                        </li>
                        <li class="nav-item">
                            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#adminLogin">
                                <i class="fas fa-user-shield me-1"></i>Admin Login
                            </button>
                        </li>
                    </ul>
                    <div class="tab-content">
                        <div class="tab-pane fade show active" id="userLogin">
                            <form action="<?php echo site_url('login-process.php?type=user'); ?>" method="post">
                                <div class="mb-3">
                                    <label class="form-label">Username</label>
                                    <input type="text" name="username" class="form-control" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Password</label>
                                    <input type="password" name="password" class="form-control" required>
                                </div>
                                <button type="submit" class="btn btn-primary w-100"><i class="fas fa-sign-in-alt me-1"></i>Login as User</button>
                            </form>
                        </div>
                        <div class="tab-pane fade" id="adminLogin">
                            <form action="<?php echo site_url('login-process.php?type=admin'); ?>" method="post">
                                <div class="mb-3">
                                    <label class="form-label">Username</label>
                                    <input type="text" name="username" class="form-control" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Password</label>
                                    <input type="password" name="password" class="form-control" required>
                                </div>
                                <button type="submit" class="btn btn-dark w-100"><i class="fas fa-user-shield me-1"></i>Login as Admin</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            <p class="text-center mt-3"><a href="<?php echo site_url('index.php'); ?>"><i class="fas fa-home me-1"></i>Back to Home</a></p>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
