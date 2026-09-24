<?php
// admin/tours.php — Manage tours & assign completed/upcoming tours to users
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/functions.php';
require_admin();

$conn = db();
$action = $_GET['action'] ?? 'list';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postAction = $_POST['action'] ?? '';

    if ($postAction === 'create_tour') {
        $tourName = trim($_POST['tour_name'] ?? '');
        $tourYear = (int)($_POST['tour_year'] ?? date('Y'));
        $description = trim($_POST['description'] ?? '');
        $tourDate = $_POST['tour_date'] ?? null;
        $status = $_POST['status'] ?? 'upcoming';
        if ($tourName && $tourYear) {
            $stmt = $conn->prepare("INSERT INTO tours (tour_name, tour_year, description, tour_date, status) VALUES (?,?,?,?,?)");
            $nullDate = $tourDate ?: null;
            $stmt->bind_param('sisss', $tourName, $tourYear, $description, $nullDate, $status);
            if ($stmt->execute()) {
                set_flash('success', 'Tour created.');
            } else {
                set_flash('error', 'Failed to create tour.');
            }
            $stmt->close();
        } else {
            set_flash('error', 'Tour name and year are required.');
        }
        redirect('tours.php');
    }

    if ($postAction === 'edit_tour') {
        $id = (int)$_POST['id'];
        $tourName = trim($_POST['tour_name'] ?? '');
        $tourYear = (int)($_POST['tour_year'] ?? date('Y'));
        $description = trim($_POST['description'] ?? '');
        $tourDate = $_POST['tour_date'] ?? null;
        $status = $_POST['status'] ?? 'upcoming';
        $nullDate = $tourDate ?: null;
        $stmt = $conn->prepare("UPDATE tours SET tour_name=?, tour_year=?, description=?, tour_date=?, status=? WHERE id=?");
        $stmt->bind_param('sisssi', $tourName, $tourYear, $description, $nullDate, $status, $id);
        if ($stmt->execute()) {
            set_flash('success', 'Tour updated.');
        } else {
            set_flash('error', 'Failed to update tour.');
        }
        $stmt->close();
        redirect('tours.php');
    }

    if ($postAction === 'assign') {
        $userId = (int)$_POST['user_id'];
        $tourId = (int)$_POST['tour_id'];
        $stmt = $conn->prepare("INSERT IGNORE INTO user_tours (user_id, tour_id) VALUES (?, ?)");
        $stmt->bind_param('ii', $userId, $tourId);
        if ($stmt->execute()) {
            set_flash('success', 'Tour assigned to user.');
        } else {
            set_flash('error', 'Failed to assign tour.');
        }
        $stmt->close();
        redirect('tours.php');
    }
}

if ($action === 'delete' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    // Delete tour images from disk
    $imgs = get_tour_images($id);
    foreach ($imgs as $img) {
        $file = __DIR__ . '/../' . $img['image_path'];
        if (file_exists($file)) @unlink($file);
    }
    $stmt = $conn->prepare("DELETE FROM tours WHERE id=?");
    $stmt->bind_param('i', $id);
    if ($stmt->execute()) {
        set_flash('success', 'Tour deleted.');
    } else {
        set_flash('error', 'Failed to delete tour.');
    }
    $stmt->close();
    redirect('tours.php');
}

if ($action === 'unassign' && isset($_GET['user_id']) && isset($_GET['tour_id'])) {
    $userId = (int)$_GET['user_id'];
    $tourId = (int)$_GET['tour_id'];
    $stmt = $conn->prepare("DELETE FROM user_tours WHERE user_id=? AND tour_id=?");
    $stmt->bind_param('ii', $userId, $tourId);
    $stmt->execute();
    $stmt->close();
    set_flash('success', 'Tour unassigned.');
    redirect('tours.php');
}

// Fetch data
$tours = get_tours();
$users = [];
$result = $conn->query("SELECT id, full_name, username FROM users ORDER BY full_name");
if ($result) { while ($r = $result->fetch_assoc()) $users[] = $r; }

$assignments = [];
$result = $conn->query("SELECT ut.*, u.full_name, u.username, t.tour_name, t.tour_year, t.status FROM user_tours ut JOIN users u ON ut.user_id = u.id JOIN tours t ON ut.tour_id = t.id ORDER BY u.full_name");
if ($result) { while ($r = $result->fetch_assoc()) $assignments[] = $r; }

$page_title = 'Manage Tours';
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
                    <a class="nav-link active" href="<?php echo site_url('admin/tours.php'); ?>"><i class="fas fa-map-marked-alt"></i> Manage Tours</a>
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
                <a class="nav-link text-light" href="<?php echo site_url('admin/admins.php'); ?>"><i class="fas fa-user-shield"></i> Manage Admins</a>
                <a class="nav-link text-light active" href="<?php echo site_url('admin/tours.php'); ?>"><i class="fas fa-map-marked-alt"></i> Manage Tours</a>
                <a class="nav-link text-light" href="<?php echo site_url('admin/tour-gallery.php'); ?>"><i class="fas fa-images"></i> Tour Gallery</a>
                <a class="nav-link text-light" href="<?php echo site_url('admin/messages.php'); ?>"><i class="fas fa-envelope"></i> Messages</a>
                <a class="nav-link text-light" href="<?php echo site_url('admin/logout.php'); ?>"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </nav></div>
        </div>

        <div class="col-lg-10 admin-content">
            <h2 class="mb-4"><i class="fas fa-map-marked-alt me-2"></i>Manage Tours</h2>
            <?php $flash = get_flash(); if ($flash): foreach ($flash as $type => $msg): ?>
                <div class="alert alert-<?php echo $type === 'error' ? 'danger' : 'success'; ?> alert-dismissible fade show">
                    <?php echo sanitize($msg); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endforeach; endif; ?>

            <div class="row g-4">
                <!-- Create/Edit Tour -->
                <div class="col-lg-5">
                    <div class="card shadow">
                        <div class="card-header"><h5 class="mb-0"><i class="fas fa-plus-circle me-2"></i>Add New Tour</h5></div>
                        <div class="card-body">
                            <form method="post">
                                <input type="hidden" name="action" value="create_tour">
                                <div class="mb-3">
                                    <label class="form-label">Tour Name <span class="text-danger">*</span></label>
                                    <input type="text" name="tour_name" class="form-control" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Year <span class="text-danger">*</span></label>
                                    <input type="number" name="tour_year" class="form-control" value="<?php echo date('Y'); ?>" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Tour Date</label>
                                    <input type="date" name="tour_date" class="form-control">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Status</label>
                                    <select name="status" class="form-select">
                                        <option value="upcoming">Upcoming</option>
                                        <option value="completed">Completed</option>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Description</label>
                                    <textarea name="description" class="form-control" rows="3"></textarea>
                                </div>
                                <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i>Create Tour</button>
                            </form>
                        </div>
                    </div>

                    <!-- Assign Tour to User -->
                    <div class="card shadow mt-4">
                        <div class="card-header"><h5 class="mb-0"><i class="fas fa-user-tag me-2"></i>Assign Tour to User</h5></div>
                        <div class="card-body">
                            <?php if (empty($users) || empty($tours)): ?>
                                <p class="text-muted">You need at least one user and one tour to assign.</p>
                            <?php else: ?>
                            <form method="post">
                                <input type="hidden" name="action" value="assign">
                                <div class="mb-3">
                                    <label class="form-label">User</label>
                                    <select name="user_id" class="form-select" required>
                                        <option value="">Select user...</option>
                                        <?php foreach ($users as $u): ?>
                                            <option value="<?php echo $u['id']; ?>"><?php echo sanitize($u['full_name'] . ' (' . $u['username'] . ')'); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Tour</label>
                                    <select name="tour_id" class="form-select" required>
                                        <option value="">Select tour...</option>
                                        <?php foreach ($tours as $t): ?>
                                            <option value="<?php echo $t['id']; ?>"><?php echo sanitize($t['tour_name'] . ' (' . $t['tour_year'] . ') - ' . $t['status']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <button type="submit" class="btn btn-success"><i class="fas fa-link me-1"></i>Assign</button>
                            </form>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Tours List -->
                <div class="col-lg-7">
                    <div class="card shadow mb-4">
                        <div class="card-header"><h5 class="mb-0"><i class="fas fa-list me-2"></i>All Tours</h5></div>
                        <div class="card-body">
                            <?php if (empty($tours)): ?>
                                <p class="text-muted">No tours yet.</p>
                            <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-sm table-hover align-middle">
                                    <thead><tr><th>Tour</th><th>Year</th><th>Status</th><th>Actions</th></tr></thead>
                                    <tbody>
                                        <?php foreach ($tours as $t): ?>
                                        <tr>
                                            <td><?php echo sanitize($t['tour_name']); ?></td>
                                            <td><?php echo sanitize($t['tour_year']); ?></td>
                                            <td><span class="badge <?php echo $t['status'] === 'completed' ? 'badge-completed' : 'badge-upcoming'; ?>"><?php echo ucfirst($t['status']); ?></span></td>
                                            <td>
                                                <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editTourModal<?php echo $t['id']; ?>"><i class="fas fa-edit"></i></button>
                                                <a href="?action=delete&id=<?php echo $t['id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this tour and its images?')"><i class="fas fa-trash"></i></a>
                                            </td>
                                        </tr>
                                        <div class="modal fade" id="editTourModal<?php echo $t['id']; ?>" tabindex="-1">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <div class="modal-header"><h5 class="modal-title">Edit Tour</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                                                    <form method="post">
                                                        <input type="hidden" name="action" value="edit_tour">
                                                        <input type="hidden" name="id" value="<?php echo $t['id']; ?>">
                                                        <div class="modal-body">
                                                            <div class="mb-3"><label class="form-label">Tour Name</label><input type="text" name="tour_name" class="form-control" value="<?php echo sanitize($t['tour_name']); ?>" required></div>
                                                            <div class="mb-3"><label class="form-label">Year</label><input type="number" name="tour_year" class="form-control" value="<?php echo sanitize($t['tour_year']); ?>" required></div>
                                                            <div class="mb-3"><label class="form-label">Tour Date</label><input type="date" name="tour_date" class="form-control" value="<?php echo $t['tour_date']; ?>"></div>
                                                            <div class="mb-3"><label class="form-label">Status</label><select name="status" class="form-select"><option value="upcoming" <?php echo $t['status'] === 'upcoming' ? 'selected' : ''; ?>>Upcoming</option><option value="completed" <?php echo $t['status'] === 'completed' ? 'selected' : ''; ?>>Completed</option></select></div>
                                                            <div class="mb-3"><label class="form-label">Description</label><textarea name="description" class="form-control" rows="3"><?php echo sanitize($t['description'] ?? ''); ?></textarea></div>
                                                        </div>
                                                        <div class="modal-footer"><button type="submit" class="btn btn-primary">Save</button><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button></div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Assignments -->
                    <div class="card shadow">
                        <div class="card-header"><h5 class="mb-0"><i class="fas fa-tasks me-2"></i>Tour Assignments</h5></div>
                        <div class="card-body">
                            <?php if (empty($assignments)): ?>
                                <p class="text-muted">No assignments yet.</p>
                            <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-sm table-hover">
                                    <thead><tr><th>User</th><th>Tour</th><th>Status</th><th></th></tr></thead>
                                    <tbody>
                                        <?php foreach ($assignments as $a): ?>
                                        <tr>
                                            <td><?php echo sanitize($a['full_name']); ?></td>
                                            <td><?php echo sanitize($a['tour_name'] . ' (' . $a['tour_year'] . ')'); ?></td>
                                            <td><span class="badge <?php echo $a['status'] === 'completed' ? 'badge-completed' : 'badge-upcoming'; ?>"><?php echo ucfirst($a['status']); ?></span></td>
                                            <td><a href="?action=unassign&user_id=<?php echo $a['user_id']; ?>&tour_id=<?php echo $a['tour_id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Unassign?')"><i class="fas fa-unlink"></i></a></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<script src="<?php echo site_url('assets/js/bootstrap.bundle.min.js'); ?>"></script>
<script src="<?php echo site_url('assets/js/all.min.js'); ?>"></script>
<script src="<?php echo site_url('assets/js/main.js'); ?>"></script>
<script src="<?php echo site_url('assets/js/session-timeout.js'); ?>"></script>
</body>
</html>
