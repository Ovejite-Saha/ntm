<?php
// user/dashboard.php — User dashboard: view assigned completed & upcoming tours
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/functions.php';
require_user();

$userId = (int)$_SESSION['user_id'];
$user = get_user($userId);

$conn = db();

// Get assigned tours
$stmt = $conn->prepare("
    SELECT t.*, ut.assigned_at FROM tours t
    JOIN user_tours ut ON ut.tour_id = t.id
    WHERE ut.user_id = ?
    ORDER BY t.tour_year DESC, t.tour_date DESC
");
$stmt->bind_param('i', $userId);
$stmt->execute();
$result = $stmt->get_result();
$allTours = [];
while ($row = $result->fetch_assoc()) {
    $allTours[] = $row;
}
$stmt->close();

$completedTours = array_filter($allTours, fn($t) => $t['status'] === 'completed');
$upcomingTours = array_filter($allTours, fn($t) => $t['status'] === 'upcoming');

$page_title = 'My Dashboard';
$nav_active = 'user_dashboard';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container mt-4">
    <!-- Welcome -->
    <div class="d-flex align-items-center mb-4">
        <?php if ($user['profile_photo'] && file_exists(__DIR__ . '/../' . $user['profile_photo'])): ?>
            <img src="<?php echo site_url($user['profile_photo']); ?>" class="profile-img me-3" style="width:60px;height:60px;">
        <?php else: ?>
            <i class="fas fa-user-circle fa-3x text-muted me-3"></i>
        <?php endif; ?>
        <div>
            <h2 class="mb-0">Welcome, <?php echo sanitize($user['full_name']); ?>!</h2>
            <small class="text-muted">Here are your assigned tours.</small>
        </div>
    </div>

    <div class="row g-4">
        <!-- Completed Tours -->
        <div class="col-lg-6">
            <div class="card shadow h-100">
                <div class="card-header bg-white">
                    <h4 class="mb-0"><i class="fas fa-check-circle text-success me-2"></i>Completed Tours</h4>
                </div>
                <div class="card-body">
                    <?php if (empty($completedTours)): ?>
                        <p class="text-muted text-center py-4"><i class="fas fa-camera-retro fa-2x d-block mb-2 opacity-50"></i>No completed tours assigned yet.</p>
                    <?php else: foreach ($completedTours as $tour): ?>
                        <?php
                            $imgs = get_tour_images($tour['id']);
                            $cover = !empty($imgs) ? site_url($imgs[0]['image_path']) : '';
                        ?>
                        <div class="card tour-card mb-3">
                            <?php if ($cover): ?>
                                <img src="<?php echo $cover; ?>" class="card-img-top" style="height:150px;object-fit:cover;">
                            <?php endif; ?>
                            <div class="card-body">
                                <h5><?php echo sanitize($tour['tour_name']); ?> <small class="text-muted">(<?php echo sanitize($tour['tour_year']); ?>)</small></h5>
                                <p class="small text-muted"><?php echo sanitize($tour['description'] ?? ''); ?></p>
                                <?php if (!empty($imgs)): ?>
                                    <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#tourModal<?php echo $tour['id']; ?>">
                                        <i class="fas fa-images me-1"></i>View <?php echo count($imgs); ?> Photos
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                        <!-- Photo modal -->
                        <?php if (!empty($imgs)): ?>
                        <div class="modal fade" id="tourModal<?php echo $tour['id']; ?>" tabindex="-1">
                            <div class="modal-dialog modal-lg">
                                <div class="modal-content">
                                    <div class="modal-header"><h5 class="modal-title"><?php echo sanitize($tour['tour_name'] . ' (' . $tour['tour_year'] . ')'); ?></h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                                    <div class="modal-body">
                                        <div class="row g-2">
                                            <?php foreach ($imgs as $img): ?>
                                            <div class="col-md-4 col-sm-6"><img src="<?php echo site_url($img['image_path']); ?>" class="img-fluid rounded"></div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                    <?php endforeach; endif; ?>
                </div>
            </div>
        </div>

        <!-- Upcoming Tours -->
        <div class="col-lg-6">
            <div class="card shadow h-100">
                <div class="card-header bg-white">
                    <h4 class="mb-0"><i class="fas fa-clock text-warning me-2"></i>Upcoming Tours</h4>
                </div>
                <div class="card-body">
                    <?php if (empty($upcomingTours)): ?>
                        <p class="text-muted text-center py-4"><i class="fas fa-route fa-2x d-block mb-2 opacity-50"></i>No upcoming tours assigned yet.</p>
                    <?php else: foreach ($upcomingTours as $tour): ?>
                        <div class="card mb-3 border-warning">
                            <div class="card-body">
                                <h5><?php echo sanitize($tour['tour_name']); ?> <small class="text-muted">(<?php echo sanitize($tour['tour_year']); ?>)</small></h5>
                                <?php if ($tour['tour_date']): ?>
                                    <p class="text-muted mb-1"><i class="far fa-calendar me-1"></i><?php echo date('F j, Y', strtotime($tour['tour_date'])); ?></p>
                                <?php endif; ?>
                                <p class="small text-muted"><?php echo sanitize($tour['description'] ?? ''); ?></p>
                                <span class="badge badge-upcoming">Upcoming</span>
                            </div>
                        </div>
                    <?php endforeach; endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Contact -->
<section id="contact" class="contact-section mt-5">
    <div class="container">
        <h2 class="text-center section-title">Contact Us</h2>
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card shadow">
                    <div class="card-body p-4">
                        <p class="text-muted text-center mb-4">Have a question? Send us a message and we'll get back to you.</p>
                        <form action="<?php echo site_url('contact-process.php'); ?>" method="post">
                            <div class="mb-3">
                                <label class="form-label"><i class="fas fa-envelope me-1"></i>Your Email</label>
                                <input type="email" name="email" class="form-control form-control-lg" placeholder="you@example.com" value="<?php echo sanitize($user['email'] ?? ''); ?>" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label"><i class="fas fa-tag me-1"></i>Subject</label>
                                <input type="text" name="subject" class="form-control form-control-lg" placeholder="Subject" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label"><i class="fas fa-comment me-1"></i>Message</label>
                                <textarea name="message" class="form-control form-control-lg" rows="5" placeholder="Your message..." required></textarea>
                            </div>
                            <button type="submit" class="btn btn-primary btn-lg w-100"><i class="fas fa-paper-plane me-1"></i>Send Message</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
