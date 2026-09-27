<?php
// upgrade_chat.php — run once to add chat + contact reply columns on existing DB
// Delete this file after running.
require_once __DIR__ . '/config/db.php';

$conn = db();
$ok = true;
$messages = [];

function run_sql($conn, $sql, &$ok, &$messages) {
    if ($conn->query($sql)) {
        $messages[] = ['success', $sql];
    } else {
        // Ignore "duplicate column/table" style errors for re-runs
        if (strpos($conn->error, 'Duplicate') !== false) {
            $messages[] = ['info', 'Already applied: ' . $conn->error];
        } else {
            $ok = false;
            $messages[] = ['error', $conn->error . ' — ' . $sql];
        }
    }
}

// Add columns to contact_messages if missing
$cols = [];
$res = $conn->query("SHOW COLUMNS FROM contact_messages");
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $cols[] = $row['Field'];
    }
}

if (!in_array('user_id', $cols, true)) {
    run_sql($conn, "ALTER TABLE contact_messages ADD COLUMN user_id INT DEFAULT NULL AFTER id", $ok, $messages);
}
if (!in_array('admin_reply', $cols, true)) {
    run_sql($conn, "ALTER TABLE contact_messages ADD COLUMN admin_reply TEXT DEFAULT NULL AFTER message", $ok, $messages);
}
if (!in_array('replied_at', $cols, true)) {
    run_sql($conn, "ALTER TABLE contact_messages ADD COLUMN replied_at TIMESTAMP NULL DEFAULT NULL AFTER admin_reply", $ok, $messages);
}

// Create chat_messages table
run_sql($conn, "CREATE TABLE IF NOT EXISTS chat_messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sender_id INT NOT NULL,
    receiver_id INT NOT NULL,
    message TEXT NOT NULL,
    is_read TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (receiver_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_chat_pair (sender_id, receiver_id),
    INDEX idx_chat_receiver (receiver_id, is_read)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", $ok, $messages);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Upgrade Chat</title>
    <link rel="stylesheet" href="assets/css/bootstrap.min.css">
</head>
<body class="p-4">
    <div class="container">
        <h2>Chat / Contact Reply Upgrade</h2>
        <?php foreach ($messages as [$type, $msg]): ?>
            <div class="alert alert-<?php echo $type === 'error' ? 'danger' : ($type === 'info' ? 'info' : 'success'); ?>">
                <?php echo htmlspecialchars($msg); ?>
            </div>
        <?php endforeach; ?>
        <?php if ($ok): ?>
            <div class="alert alert-success">Upgrade completed. <strong>Delete upgrade_chat.php</strong> for security.</div>
            <a href="user/dashboard.php" class="btn btn-primary">Go to User Dashboard</a>
        <?php else: ?>
            <div class="alert alert-danger">Some steps failed. Check errors above.</div>
        <?php endif; ?>
    </div>
</body>
</html>
