<?php
// admin/messages.php — View all contact messages & reply to senders
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/functions.php';
require_admin();

$conn = db();
$action = $_GET['action'] ?? 'list';
$messageId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Handle reply send
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postAction = $_POST['action'] ?? '';

    if ($postAction === 'send_reply') {
        $to = trim($_POST['to_email'] ?? '');
        $subject = trim($_POST['subject'] ?? '');
        $body = trim($_POST['message'] ?? '');

        if ($to && $subject && $body) {
            if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
                set_flash('error', 'Invalid recipient email address.');
                redirect('messages.php?action=view&id=' . (int)$_POST['msg_id']);
            }

            $sent = send_contact_mail($to, 'admin@tourgroup.local', $subject, $body);

            if ($sent) {
                set_flash('success', 'Reply sent successfully to ' . $to);
            } else {
                set_flash('error', 'Failed to send email. The mail server may not be configured.');
            }
        } else {
            set_flash('error', 'All fields are required to send a reply.');
        }
        redirect('messages.php?action=view&id=' . (int)$_POST['msg_id']);
    }

    if ($postAction === 'send_new') {
        $to = trim($_POST['to_email'] ?? '');
        $subject = trim($_POST['subject'] ?? '');
        $body = trim($_POST['message'] ?? '');

        if ($to && $subject && $body) {
            if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
                set_flash('error', 'Invalid recipient email address.');
                redirect('messages.php?action=compose');
            }

            $sent = send_contact_mail($to, 'admin@tourgroup.local', $subject, $body);

            if ($sent) {
                set_flash('success', 'Message sent successfully to ' . $to);
            } else {
                set_flash('error', 'Failed to send email. The mail server may not be configured.');
            }
        } else {
            set_flash('error', 'All fields are required.');
        }
        redirect('messages.php');
    }
}

// Handle delete
if ($action === 'delete' && $messageId) {
    $stmt = $conn->prepare("DELETE FROM contact_messages WHERE id = ?");
    $stmt->bind_param('i', $messageId);
    if ($stmt->execute()) {
        set_flash('success', 'Message deleted.');
    } else {
        set_flash('error', 'Failed to delete message.');
    }
    $stmt->close();
    redirect('messages.php');
}

// Fetch all messages for list view
$allMessages = [];
$result = $conn->query("SELECT * FROM contact_messages ORDER BY sent_at DESC");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $allMessages[] = $row;
    }
}
$msgCount = count($allMessages);

// Fetch single message for detail view
$viewMessage = null;
if ($action === 'view' && $messageId) {
    $stmt = $conn->prepare("SELECT * FROM contact_messages WHERE id = ?");
    $stmt->bind_param('i', $messageId);
    $stmt->execute();
    $res = $stmt->get_result();
    $viewMessage = $res->fetch_assoc();
    $stmt->close();
}

$admin = get_admin($_SESSION['admin_id']);
$page_title = 'Messages';
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
        <!-- Sidebar -->
        <div class="col-lg-2 sidebar p-0 d-none d-lg-block">
            <div class="d-flex flex-column">
                <div class="p-3 text-white border-bottom border-secondary">
                    <h5 class="mb-0"><i class="fas fa-user-shield me-2"></i>Admin Panel</h5>
                    <small class="text-muted"><?php echo sanitize($admin['username']); ?></small>
                </div>
                <nav class="nav flex-column mt-2">
                    <a class="nav-link" href="<?php echo site_url('admin/index.php'); ?>"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
                    <a class="nav-link" href="<?php echo site_url('admin/users.php'); ?>"><i class="fas fa-users"></i> Manage Users</a>
                    <a class="nav-link" href="<?php echo site_url('admin/admins.php'); ?>"><i class="fas fa-user-shield"></i> Manage Admins</a>
                    <a class="nav-link" href="<?php echo site_url('admin/tours.php'); ?>"><i class="fas fa-map-marked-alt"></i> Manage Tours</a>
                    <a class="nav-link" href="<?php echo site_url('admin/tour-gallery.php'); ?>"><i class="fas fa-images"></i> Tour Gallery</a>
                    <a class="nav-link active" href="<?php echo site_url('admin/messages.php'); ?>"><i class="fas fa-envelope"></i> Messages <?php if ($msgCount > 0): ?><span class="badge bg-danger ms-1"><?php echo $msgCount; ?></span><?php endif; ?></a>
                    <a class="nav-link" href="<?php echo site_url('admin/logout.php'); ?>"><i class="fas fa-sign-out-alt"></i> Logout</a>
                </nav>
            </div>
        </div>

        <!-- Mobile top bar -->
        <div class="d-lg-none bg-dark text-white p-2 d-flex justify-content-between align-items-center">
            <span><i class="fas fa-user-shield me-1"></i>Admin</span>
            <button class="btn btn-sm btn-outline-light" data-bs-toggle="offcanvas" data-bs-target="#mobileNav">
                <i class="fas fa-bars"></i>
            </button>
        </div>
        <div class="offcanvas offcanvas-start bg-dark" id="mobileNav">
            <div class="offcanvas-header text-white">
                <h5>Admin Menu</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas"></button>
            </div>
            <div class="offcanvas-body">
                <nav class="nav flex-column">
                    <a class="nav-link text-light" href="<?php echo site_url('admin/index.php'); ?>"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
                    <a class="nav-link text-light" href="<?php echo site_url('admin/users.php'); ?>"><i class="fas fa-users"></i> Manage Users</a>
                    <a class="nav-link text-light" href="<?php echo site_url('admin/admins.php'); ?>"><i class="fas fa-user-shield"></i> Manage Admins</a>
                    <a class="nav-link text-light" href="<?php echo site_url('admin/tours.php'); ?>"><i class="fas fa-map-marked-alt"></i> Manage Tours</a>
                    <a class="nav-link text-light" href="<?php echo site_url('admin/tour-gallery.php'); ?>"><i class="fas fa-images"></i> Tour Gallery</a>
                    <a class="nav-link text-light active" href="<?php echo site_url('admin/messages.php'); ?>"><i class="fas fa-envelope"></i> Messages <?php if ($msgCount > 0): ?><span class="badge bg-danger ms-1"><?php echo $msgCount; ?></span><?php endif; ?></a>
                    <a class="nav-link text-light" href="<?php echo site_url('admin/logout.php'); ?>"><i class="fas fa-sign-out-alt"></i> Logout</a>
                </nav>
            </div>
        </div>

        <!-- Main content -->
        <div class="col-lg-10 admin-content">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2><i class="fas fa-envelope me-2"></i>Contact Messages</h2>
                <a href="?action=compose" class="btn btn-primary"><i class="fas fa-pen me-1"></i>Compose New</a>
            </div>

            <?php $flash = get_flash(); if ($flash): foreach ($flash as $type => $msg): ?>
                <div class="alert alert-<?php echo $type === 'error' ? 'danger' : 'success'; ?> alert-dismissible fade show">
                    <?php echo sanitize($msg); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endforeach; endif; ?>

            <?php if ($action === 'view' && $viewMessage): ?>
                <!-- Single message detail view -->
                <div class="card shadow mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="fas fa-envelope-open me-2"></i><?php echo sanitize($viewMessage['subject']); ?></h5>
                        <div>
                            <a href="messages.php" class="btn btn-sm btn-secondary"><i class="fas fa-arrow-left me-1"></i>Back</a>
                            <a href="?action=delete&id=<?php echo $viewMessage['id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this message?')"><i class="fas fa-trash me-1"></i>Delete</a>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <p class="mb-1"><strong><i class="fas fa-user me-1"></i>From:</strong>
                                    <a href="mailto:<?php echo sanitize($viewMessage['email']); ?>"><?php echo sanitize($viewMessage['email']); ?></a>
                                </p>
                            </div>
                            <div class="col-md-6 text-md-end">
                                <p class="mb-1 text-muted"><i class="far fa-clock me-1"></i><?php echo date('F j, Y g:i A', strtotime($viewMessage['sent_at'])); ?></p>
                            </div>
                        </div>
                        <hr>
                        <div class="message-body p-3 bg-light rounded">
                            <?php echo nl2br(sanitize($viewMessage['message'])); ?>
                        </div>
                    </div>
                </div>

                <!-- Reply form -->
                <div class="card shadow">
                    <div class="card-header"><h5 class="mb-0"><i class="fas fa-reply me-2"></i>Reply to <?php echo sanitize($viewMessage['email']); ?></h5></div>
                    <div class="card-body">
                        <form method="post">
                            <input type="hidden" name="action" value="send_reply">
                            <input type="hidden" name="msg_id" value="<?php echo $viewMessage['id']; ?>">
                            <div class="mb-3">
                                <label class="form-label">To</label>
                                <input type="email" name="to_email" class="form-control" value="<?php echo sanitize($viewMessage['email']); ?>" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Subject</label>
                                <input type="text" name="subject" class="form-control" value="Re: <?php echo sanitize($viewMessage['subject']); ?>" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Message</label>
                                <textarea name="message" class="form-control" rows="6" required></textarea>
                            </div>
                            <button type="submit" class="btn btn-primary"><i class="fas fa-paper-plane me-1"></i>Send Reply</button>
                            <a href="messages.php" class="btn btn-secondary">Cancel</a>
                        </form>
                    </div>
                </div>

            <?php elseif ($action === 'compose'): ?>
                <!-- Compose new message -->
                <div class="card shadow">
                    <div class="card-header"><h5 class="mb-0"><i class="fas fa-pen me-2"></i>Compose New Message</h5></div>
                    <div class="card-body">
                        <form method="post">
                            <input type="hidden" name="action" value="send_new">
                            <div class="mb-3">
                                <label class="form-label">To (recipient email)</label>
                                <input type="email" name="to_email" class="form-control" placeholder="recipient@example.com" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Subject</label>
                                <input type="text" name="subject" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Message</label>
                                <textarea name="message" class="form-control" rows="8" required></textarea>
                            </div>
                            <button type="submit" class="btn btn-primary"><i class="fas fa-paper-plane me-1"></i>Send Message</button>
                            <a href="messages.php" class="btn btn-secondary">Cancel</a>
                        </form>
                    </div>
                </div>

            <?php else: ?>
                <!-- Message list -->
                <div class="card shadow">
                    <div class="card-body p-0">
                        <?php if (empty($allMessages)): ?>
                            <p class="text-muted text-center py-5 mb-0"><i class="fas fa-inbox fa-3x d-block mb-3 opacity-50"></i>No contact messages yet.</p>
                        <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th style="width:30px;"></th>
                                        <th>From</th>
                                        <th>Subject</th>
                                        <th>Message</th>
                                        <th>Date</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($allMessages as $m): ?>
                                    <tr>
                                        <td><i class="fas fa-envelope-open text-primary"></i></td>
                                        <td><a href="mailto:<?php echo sanitize($m['email']); ?>"><?php echo sanitize($m['email']); ?></a></td>
                                        <td><a href="?action=view&id=<?php echo $m['id']; ?>" class="text-decoration-none fw-semibold"><?php echo sanitize($m['subject']); ?></a></td>
                                        <td class="text-muted small" style="max-width:250px;"><?php echo sanitize(mb_strimwidth($m['message'], 0, 80, '...')); ?></td>
                                        <td class="small text-muted"><?php echo date('M j, Y', strtotime($m['sent_at'])); ?></td>
                                        <td>
                                            <a href="?action=view&id=<?php echo $m['id']; ?>" class="btn btn-sm btn-outline-primary" title="View & Reply"><i class="fas fa-eye"></i></a>
                                            <a href="?action=delete&id=<?php echo $m['id']; ?>" class="btn btn-sm btn-outline-danger" title="Delete" onclick="return confirm('Delete this message?')"><i class="fas fa-trash"></i></a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php endif; ?>
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
