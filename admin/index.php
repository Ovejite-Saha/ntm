<?php
// admin/index.php — Admin Dashboard
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/functions.php';
require_admin();

$conn = db();
$userCount = $conn->query("SELECT COUNT(*) as c FROM users")->fetch_assoc()['c'];
$adminCount = $conn->query("SELECT COUNT(*) as c FROM admins")->fetch_assoc()['c'];
$tourCount = $conn->query("SELECT COUNT(*) as c FROM tours")->fetch_assoc()['c'];
$completedCount = $conn->query("SELECT COUNT(*) as c FROM tours WHERE status='completed'")->fetch_assoc()['c'];
$upcomingCount = $conn->query("SELECT COUNT(*) as c FROM tours WHERE status='upcoming'")->fetch_assoc()['c'];
$slideCount = $conn->query("SELECT COUNT(*) as c FROM tour_images WHERE is_slideshow=1")->fetch_assoc()['c'];
$msgCount = $conn->query("SELECT COUNT(*) as c FROM contact_messages")->fetch_assoc()['c'];

$messages = [];
$msgResult = $conn->query("SELECT * FROM contact_messages ORDER BY sent_at DESC LIMIT 10");
if ($msgResult) {
    while ($row = $msgResult->fetch_assoc()) {
        $messages[] = $row;
    }
}

$admin = get_admin($_SESSION['admin_id']);
$page_title = 'Admin Dashboard';
$nav_active = 'admin_dashboard';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container mt-4 mb-5">
            <h2 class="mb-4"><i class="fas fa-tachometer-alt me-2"></i>Dashboard</h2>
            <div class="row g-3 mb-4">
                <div class="col-md-4 col-sm-6">
                    <div class="card text-white bg-primary shadow">
                        <div class="card-body d-flex justify-content-between align-items-center">
                            <div><h3 class="mb-0"><?php echo $userCount; ?></h3><small>Users</small></div>
                            <i class="fas fa-users fa-2x opacity-50"></i>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 col-sm-6">
                    <div class="card text-white bg-dark shadow">
                        <div class="card-body d-flex justify-content-between align-items-center">
                            <div><h3 class="mb-0"><?php echo $adminCount; ?></h3><small>Admins</small></div>
                            <i class="fas fa-user-shield fa-2x opacity-50"></i>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 col-sm-6">
                    <div class="card text-white bg-info shadow">
                        <div class="card-body d-flex justify-content-between align-items-center">
                            <div><h3 class="mb-0"><?php echo $tourCount; ?></h3><small>Total Tours</small></div>
                            <i class="fas fa-map-marked-alt fa-2x opacity-50"></i>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 col-sm-6">
                    <div class="card text-white shadow" style="background:#2c7a7b;">
                        <div class="card-body d-flex justify-content-between align-items-center">
                            <div><h3 class="mb-0"><?php echo $completedCount; ?></h3><small>Completed Tours</small></div>
                            <i class="fas fa-check-circle fa-2x opacity-50"></i>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 col-sm-6">
                    <div class="card text-white bg-warning shadow">
                        <div class="card-body d-flex justify-content-between align-items-center">
                            <div><h3 class="mb-0"><?php echo $upcomingCount; ?></h3><small>Upcoming Tours</small></div>
                            <i class="fas fa-clock fa-2x opacity-50"></i>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 col-sm-6">
                    <div class="card text-white bg-secondary shadow">
                        <div class="card-body d-flex justify-content-between align-items-center">
                            <div><h3 class="mb-0"><?php echo $slideCount; ?></h3><small>Slideshow Images</small></div>
                            <i class="fas fa-images fa-2x opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>

            <?php if (!$admin['full_name']): ?>
            <div class="alert alert-info">
                <i class="fas fa-info-circle me-1"></i> Welcome! Your admin profile is incomplete. <a href="<?php echo site_url('admin/admins.php?action=edit&id=' . $admin['id']); ?>">Complete your profile</a>.
            </div>
            <?php endif; ?>

            <!-- Contact Messages -->
            <div class="card shadow mt-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-envelope me-2"></i>Contact Messages
                        <?php if ($msgCount > 0): ?><span class="badge bg-danger ms-1"><?php echo $msgCount; ?></span><?php endif; ?>
                    </h5>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($messages)): ?>
                        <p class="text-muted text-center py-4 mb-0"><i class="fas fa-inbox fa-2x d-block mb-2 opacity-50"></i>No contact messages yet.</p>
                    <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead>
                                <tr>
                                    <th style="width:30px;"></th>
                                    <th>Email</th>
                                    <th>Subject</th>
                                    <th>Message</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($messages as $m): ?>
                                <tr>
                                    <td><i class="fas fa-envelope text-primary"></i></td>
                                    <td><a href="mailto:<?php echo sanitize($m['email']); ?>"><?php echo sanitize($m['email']); ?></a></td>
                                    <td><?php echo sanitize($m['subject']); ?></td>
                                    <td class="text-muted small" style="max-width:300px;"><?php echo sanitize(mb_strimwidth($m['message'], 0, 80, '...')); ?></td>
                                    <td class="small text-muted"><?php echo date('M j, Y g:i A', strtotime($m['sent_at'])); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php if ($msgCount > count($messages)): ?>
                    <div class="text-center py-2 border-top">
                        <small class="text-muted">Showing latest <?php echo count($messages); ?> of <?php echo $msgCount; ?> messages</small>
                    </div>
                    <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>

            <p class="mt-4"><a href="<?php echo site_url('index.php'); ?>" class="btn btn-outline-primary"><i class="fas fa-home me-1"></i>View Homepage</a></p>
        </div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
