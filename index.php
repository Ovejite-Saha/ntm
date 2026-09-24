<?php
// index.php — Homepage with slideshow, past/upcoming tours, contact form
require_once __DIR__ . '/config/session.php';
require_once __DIR__ . '/config/functions.php';

$slideshow = get_slideshow_images();
$pastTours = get_tours('completed');
$upcomingTours = get_tours('upcoming');
$page_title = 'Home';
require_once __DIR__ . '/includes/header.php';
?>

<!-- Slideshow -->
<section class="hero-section">
    <?php if (!empty($slideshow)): ?>
    <div id="mainCarousel" class="carousel slide carousel-fade" data-bs-ride="carousel" data-bs-interval="5000">
        <div class="carousel-indicators">
            <?php foreach ($slideshow as $i => $img): ?>
                <button type="button" data-bs-target="#mainCarousel" data-bs-slide-to="<?php echo $i; ?>" <?php echo $i === 0 ? 'class="active"' : ''; ?>></button>
            <?php endforeach; ?>
        </div>
        <div class="carousel-inner">
            <?php foreach ($slideshow as $i => $img): ?>
                <div class="carousel-item <?php echo $i === 0 ? 'active' : ''; ?>">
                    <img src="<?php echo site_url($img['image_path']); ?>" alt="<?php echo sanitize($img['tour_name'] . ' ' . $img['tour_year']); ?>">
                    <div class="carousel-caption d-none d-md-block">
                        <h3><?php echo sanitize($img['tour_name']); ?> — <?php echo sanitize($img['tour_year']); ?></h3>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <button class="carousel-control-prev" type="button" data-bs-target="#mainCarousel" data-bs-slide="prev">
            <span class="carousel-control-prev-icon"></span>
        </button>
        <button class="carousel-control-next" type="button" data-bs-target="#mainCarousel" data-bs-slide="next">
            <span class="carousel-control-next-icon"></span>
        </button>
    </div>
    <?php else: ?>
    <div class="d-flex align-items-center justify-content-center bg-dark" style="min-height:500px;">
        <div class="text-center text-white">
            <i class="fas fa-mountain-sun fa-4x mb-3"></i>
            <h1 class="display-4">MIS Tour Group</h1>
            <p class="lead">Explore the world with us</p>
        </div>
    </div>
    <?php endif; ?>
</section>

<!-- Welcome banner -->
<section class="py-5 text-center">
    <div class="container">
        <h2 class="display-5 fw-bold text-primary">Welcome to MIS Tour Group</h2>
        <p class="lead text-muted mx-auto" style="max-width:700px;">
            We organize unforgettable travel experiences. Browse our past adventures and upcoming journeys below.
        </p>
    </div>
</section>

<!-- Past Tours -->
<section id="past-tours" class="py-5 bg-light">
    <div class="container">
        <h2 class="text-center section-title">Past Tours</h2>
        <div class="row g-4">
            <?php if (empty($pastTours)): ?>
                <div class="col-12 text-center text-muted py-4">
                    <i class="fas fa-camera-retro fa-3x mb-3 opacity-50"></i>
                    <p>No past tours yet.</p>
                </div>
            <?php else: ?>
                <?php foreach ($pastTours as $tour): ?>
                    <?php
                        $imgs = get_tour_images($tour['id']);
                        $cover = !empty($imgs) ? site_url($imgs[0]['image_path']) : '';
                    ?>
                    <div class="col-md-6 col-lg-4">
                        <div class="card tour-card h-100 shadow-sm">
                            <?php if ($cover): ?>
                                <img src="<?php echo $cover; ?>" class="card-img-top" alt="<?php echo sanitize($tour['tour_name']); ?>">
                            <?php else: ?>
                                <div class="card-img-top d-flex align-items-center justify-content-center bg-secondary text-white" style="height:200px;">
                                    <i class="fas fa-image fa-3x"></i>
                                </div>
                            <?php endif; ?>
                            <div class="card-body">
                                <span class="badge badge-completed mb-2">Completed</span>
                                <h5 class="card-title"><?php echo sanitize($tour['tour_name']); ?> <small class="text-muted">(<?php echo sanitize($tour['tour_year']); ?>)</small></h5>
                                <p class="card-text text-muted small"><?php echo sanitize(substr($tour['description'] ?? '', 0, 120)); ?><?php echo strlen($tour['description'] ?? '') > 120 ? '...' : ''; ?></p>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- Upcoming Tours -->
<section id="upcoming-tours" class="py-5">
    <div class="container">
        <h2 class="text-center section-title">Upcoming Tours</h2>
        <div class="row g-4">
            <?php if (empty($upcomingTours)): ?>
                <div class="col-12 text-center text-muted py-4">
                    <i class="fas fa-route fa-3x mb-3 opacity-50"></i>
                    <p>No upcoming tours scheduled yet.</p>
                </div>
            <?php else: ?>
                <?php foreach ($upcomingTours as $tour): ?>
                    <div class="col-md-6 col-lg-4">
                        <div class="card tour-card h-100 shadow-sm border-warning">
                            <div class="card-img-top d-flex align-items-center justify-content-center bg-warning" style="height:200px;">
                                <i class="fas fa-route fa-3x text-white"></i>
                            </div>
                            <div class="card-body">
                                <span class="badge badge-upcoming mb-2">Upcoming</span>
                                <h5 class="card-title"><?php echo sanitize($tour['tour_name']); ?> <small class="text-muted">(<?php echo sanitize($tour['tour_year']); ?>)</small></h5>
                                <?php if ($tour['tour_date']): ?>
                                    <p class="text-muted"><i class="far fa-calendar me-1"></i><?php echo date('M j, Y', strtotime($tour['tour_date'])); ?></p>
                                <?php endif; ?>
                                <p class="card-text text-muted small"><?php echo sanitize(substr($tour['description'] ?? '', 0, 120)); ?><?php echo strlen($tour['description'] ?? '') > 120 ? '...' : ''; ?></p>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- Contact -->
<section id="contact" class="contact-section">
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
                                <input type="email" name="email" class="form-control form-control-lg" placeholder="you@example.com" required>
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

<?php require_once __DIR__ . '/includes/footer.php'; ?>
