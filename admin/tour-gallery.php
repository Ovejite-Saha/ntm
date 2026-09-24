<?php
// admin/tour-gallery.php — Tour image upload panel & slideshow selection toggle
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/functions.php';
require_admin();

$conn = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postAction = $_POST['action'] ?? '';

    if ($postAction === 'upload') {
        $tourId = (int)$_POST['tour_id'];
        $tour = null;
        $stmt = $conn->prepare("SELECT * FROM tours WHERE id = ?");
        $stmt->bind_param('i', $tourId);
        $stmt->execute();
        $result = $stmt->get_result();
        $tour = $result->fetch_assoc();
        $stmt->close();

        if (!$tour) {
            set_flash('error', 'Tour not found.');
            redirect('tour-gallery.php');
        }

        if (!isset($_FILES['tour_image']) || $_FILES['tour_image']['error'] !== UPLOAD_ERR_OK) {
            set_flash('error', 'No image uploaded.');
            redirect('tour-gallery.php?tour_id=' . $tourId);
        }

        $folderName = sanitize_folder_name($tour['tour_name'], $tour['tour_year']);
        $uploadDir = __DIR__ . '/../uploads/tour_photos/' . $folderName . '/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $ext = strtolower(pathinfo($_FILES['tour_image']['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, ['jpg', 'jpeg', 'png'])) {
            set_flash('error', 'Only JPG, PNG, JPEG formats allowed.');
            redirect('tour-gallery.php?tour_id=' . $tourId);
        }

        $baseName = pathinfo($_FILES['tour_image']['name'], PATHINFO_FILENAME);
        $destPath = $uploadDir . $baseName . '.jpg';
        // Avoid overwriting
        $counter = 1;
        while (file_exists($destPath)) {
            $destPath = $uploadDir . $baseName . '_' . $counter . '.jpg';
            $counter++;
        }

        $result = process_tour_image($_FILES['tour_image']['tmp_name'], $destPath);
        if (is_string($result) && file_exists($result)) {
            $relativePath = 'uploads/tour_photos/' . $folderName . '/' . basename($result);
            $isSlideshow = isset($_POST['is_slideshow']) ? 1 : 0;
            $stmt = $conn->prepare("INSERT INTO tour_images (tour_id, image_path, is_slideshow) VALUES (?, ?, ?)");
            $stmt->bind_param('isi', $tourId, $relativePath, $isSlideshow);
            if ($stmt->execute()) {
                set_flash('success', 'Tour image uploaded successfully.');
            } else {
                set_flash('error', 'Failed to save image record.');
            }
            $stmt->close();
        } else {
            set_flash('error', is_string($result) ? $result : 'Failed to process image.');
        }
        redirect('tour-gallery.php?tour_id=' . $tourId);
    }

    if ($postAction === 'toggle_slideshow') {
        $imageId = (int)$_POST['image_id'];
        $current = (int)$_POST['current'];
        $newVal = $current ? 0 : 1;
        $stmt = $conn->prepare("UPDATE tour_images SET is_slideshow = ? WHERE id = ?");
        $stmt->bind_param('ii', $newVal, $imageId);
        $stmt->execute();
        $stmt->close();
        // Redirect back with tour_id
        $tourId = (int)($_POST['tour_id'] ?? 0);
        redirect('tour-gallery.php' . ($tourId ? '?tour_id=' . $tourId : ''));
    }

    if ($postAction === 'delete_image') {
        $imageId = (int)$_POST['image_id'];
        $stmt = $conn->prepare("SELECT image_path FROM tour_images WHERE id = ?");
        $stmt->bind_param('i', $imageId);
        $stmt->execute();
        $res = $stmt->get_result();
        $img = $res->fetch_assoc();
        $stmt->close();
        if ($img) {
            $file = __DIR__ . '/../' . $img['image_path'];
            if (file_exists($file)) @unlink($file);
            $stmt = $conn->prepare("DELETE FROM tour_images WHERE id = ?");
            $stmt->bind_param('i', $imageId);
            $stmt->execute();
            $stmt->close();
            set_flash('success', 'Image deleted.');
        }
        $tourId = (int)($_POST['tour_id'] ?? 0);
        redirect('tour-gallery.php' . ($tourId ? '?tour_id=' . $tourId : ''));
    }
}

$tours = get_tours();
$selectedTourId = isset($_GET['tour_id']) ? (int)$_GET['tour_id'] : (!empty($tours) ? $tours[0]['id'] : 0);
$selectedTourImages = $selectedTourId ? get_tour_images($selectedTourId) : [];

$page_title = 'Tour Gallery';
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
                    <a class="nav-link" href="<?php echo site_url('admin/users.php'); ?>"><i class="fas fa-users"></i> Manage Users</a>
                    <a class="nav-link" href="<?php echo site_url('admin/admins.php'); ?>"><i class="fas fa-user-shield"></i> Manage Admins</a>
                    <a class="nav-link" href="<?php echo site_url('admin/tours.php'); ?>"><i class="fas fa-map-marked-alt"></i> Manage Tours</a>
                    <a class="nav-link active" href="<?php echo site_url('admin/tour-gallery.php'); ?>"><i class="fas fa-images"></i> Tour Gallery</a>
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
                <a class="nav-link text-light" href="<?php echo site_url('admin/admins.php'); ?>"><i class="fas fa-user-shield"></i> Manage Admins</a>
                <a class="nav-link text-light" href="<?php echo site_url('admin/tours.php'); ?>"><i class="fas fa-map-marked-alt"></i> Manage Tours</a>
                <a class="nav-link text-light active" href="<?php echo site_url('admin/tour-gallery.php'); ?>"><i class="fas fa-images"></i> Tour Gallery</a>
                <a class="nav-link text-light" href="<?php echo site_url('admin/messages.php'); ?>"><i class="fas fa-envelope"></i> Messages</a>
                <a class="nav-link text-light" href="<?php echo site_url('admin/logout.php'); ?>"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </nav></div>
        </div>

        <div class="col-lg-10 admin-content">
            <h2 class="mb-4"><i class="fas fa-images me-2"></i>Tour Gallery & Slideshow</h2>
            <?php $flash = get_flash(); if ($flash): foreach ($flash as $type => $msg): ?>
                <div class="alert alert-<?php echo $type === 'error' ? 'danger' : 'success'; ?> alert-dismissible fade show">
                    <?php echo sanitize($msg); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endforeach; endif; ?>

            <?php if (empty($tours)): ?>
                <div class="alert alert-info"><i class="fas fa-info-circle me-1"></i> You need to create a tour first before uploading images. <a href="<?php echo site_url('admin/tours.php'); ?>">Create a tour</a>.</div>
            <?php else: ?>
            <div class="row g-4">
                <!-- Tour selector + upload -->
                <div class="col-lg-4">
                    <div class="card shadow">
                        <div class="card-header"><h5 class="mb-0"><i class="fas fa-upload me-2"></i>Upload Tour Image</h5></div>
                        <div class="card-body">
                            <form method="post" enctype="multipart/form-data">
                                <input type="hidden" name="action" value="upload">
                                <div class="mb-3">
                                    <label class="form-label">Select Tour</label>
                                    <select name="tour_id" class="form-select" required onchange="window.location.href='?tour_id='+this.value">
                                        <?php foreach ($tours as $t): ?>
                                            <option value="<?php echo $t['id']; ?>" <?php echo $t['id'] == $selectedTourId ? 'selected' : ''; ?>>
                                                <?php echo sanitize($t['tour_name'] . ' (' . $t['tour_year'] . ')'); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Image (JPG/PNG/JPEG, max 3MB)</label>
                                    <input type="file" name="tour_image" class="form-control" accept=".jpg,.jpeg,.png" data-image-only required>
                                </div>
                                <div class="form-check mb-3">
                                    <input type="checkbox" name="is_slideshow" class="form-check-input" id="slideCheck">
                                    <label class="form-check-label" for="slideCheck">Show in homepage slideshow</label>
                                </div>
                                <button type="submit" class="btn btn-primary w-100"><i class="fas fa-upload me-1"></i>Upload Image</button>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Gallery -->
                <div class="col-lg-8">
                    <div class="card shadow">
                        <div class="card-header"><h5 class="mb-0">
                            <i class="fas fa-photo-film me-2"></i>Images for:
                            <?php
                                foreach ($tours as $t) { if ($t['id'] == $selectedTourId) { echo sanitize($t['tour_name'] . ' (' . $t['tour_year'] . ')'); break; } }
                            ?>
                        </h5></div>
                        <div class="card-body">
                            <?php if (empty($selectedTourImages)): ?>
                                <p class="text-muted text-center py-4">No images uploaded for this tour yet.</p>
                            <?php else: ?>
                            <div class="row g-3 tour-gallery">
                                <?php foreach ($selectedTourImages as $img): ?>
                                <div class="col-md-4 col-sm-6">
                                    <div class="position-relative">
                                        <img src="<?php echo site_url($img['image_path']); ?>" class="img-fluid w-100" alt="Tour image">
                                        <?php if ($img['is_slideshow']): ?>
                                            <span class="badge bg-success position-absolute top-0 start-0 m-1"><i class="fas fa-star me-1"></i>Slideshow</span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="d-flex gap-1 mt-1">
                                        <form method="post" class="d-inline">
                                            <input type="hidden" name="action" value="toggle_slideshow">
                                            <input type="hidden" name="image_id" value="<?php echo $img['id']; ?>">
                                            <input type="hidden" name="current" value="<?php echo $img['is_slideshow']; ?>">
                                            <input type="hidden" name="tour_id" value="<?php echo $selectedTourId; ?>">
                                            <button type="submit" class="btn btn-sm <?php echo $img['is_slideshow'] ? 'btn-success' : 'btn-outline-secondary'; ?>" title="Toggle slideshow">
                                                <i class="fas fa-star"></i>
                                            </button>
                                        </form>
                                        <form method="post" class="d-inline" onsubmit="return confirm('Delete this image?')">
                                            <input type="hidden" name="action" value="delete_image">
                                            <input type="hidden" name="image_id" value="<?php echo $img['id']; ?>">
                                            <input type="hidden" name="tour_id" value="<?php echo $selectedTourId; ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete"><i class="fas fa-trash"></i></button>
                                        </form>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <?php endif; ?>
                        </div>
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
