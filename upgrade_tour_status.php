<?php
// upgrade_tour_status.php — run once to add 'not_published' to tours.status
// Delete this file after running.
require_once __DIR__ . '/config/db.php';

$conn = db();
$ok = true;
$messages = [];

$sql = "ALTER TABLE tours MODIFY COLUMN status ENUM('completed','upcoming','not_published') NOT NULL DEFAULT 'upcoming'";
if ($conn->query($sql)) {
    $messages[] = ['success', 'tours.status updated to include not_published'];
} else {
    // Safe to re-run if already applied
    if (strpos($conn->error, 'not_published') !== false || strpos($conn->error, 'Duplicate') !== false) {
        $messages[] = ['info', 'Already applied or no change needed: ' . $conn->error];
    } else {
        $ok = false;
        $messages[] = ['error', $conn->error];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Upgrade Tour Status</title>
    <link rel="stylesheet" href="assets/css/bootstrap.min.css">
</head>
<body class="p-4">
    <div class="container">
        <h2>Tour Status Upgrade</h2>
        <?php foreach ($messages as [$type, $msg]): ?>
            <div class="alert alert-<?php echo $type === 'error' ? 'danger' : ($type === 'info' ? 'info' : 'success'); ?>">
                <?php echo htmlspecialchars($msg); ?>
            </div>
        <?php endforeach; ?>
        <?php if ($ok): ?>
            <div class="alert alert-success">Upgrade completed. <strong>Delete upgrade_tour_status.php</strong> for security.</div>
            <a href="admin/tours.php" class="btn btn-primary">Go to Manage Tours</a>
        <?php else: ?>
            <div class="alert alert-danger">Upgrade failed. Check the error above.</div>
        <?php endif; ?>
    </div>
</body>
</html>