<?php
// includes/login-modal.php — Bootstrap 5 modal for User & Admin login popup
?>
<div class="modal fade" id="loginModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title"><i class="fas fa-sign-in-alt me-2"></i>Login</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <ul class="nav nav-tabs mb-3" id="loginTabs">
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
    </div>
</div>
