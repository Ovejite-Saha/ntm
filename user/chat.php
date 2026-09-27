<?php
// user/chat.php — User-to-user chat
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/functions.php';
require_user();

$userId = (int)$_SESSION['user_id'];
$user = get_user($userId);
$conn = db();

$withId = isset($_GET['with']) ? (int)$_GET['with'] : 0;

// Send message
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'send_chat') {
    $receiverId = (int)($_POST['receiver_id'] ?? 0);
    $message = trim($_POST['message'] ?? '');

    if ($receiverId > 0 && $receiverId !== $userId && $message !== '') {
        // Ensure receiver is a real user
        $check = $conn->prepare("SELECT id FROM users WHERE id = ?");
        $check->bind_param('i', $receiverId);
        $check->execute();
        $exists = $check->get_result()->fetch_assoc();
        $check->close();

        if ($exists) {
            $stmt = $conn->prepare("INSERT INTO chat_messages (sender_id, receiver_id, message) VALUES (?, ?, ?)");
            $stmt->bind_param('iis', $userId, $receiverId, $message);
            $stmt->execute();
            $stmt->close();
            set_flash('success', 'Message sent.');
        } else {
            set_flash('error', 'User not found.');
        }
    } else {
        set_flash('error', 'Please enter a message.');
    }
    redirect('chat.php?with=' . $receiverId);
}

// All other users
$members = [];
$stmt = $conn->prepare("SELECT id, full_name, username, profile_photo FROM users WHERE id != ? ORDER BY full_name ASC");
$stmt->bind_param('i', $userId);
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) {
    $members[] = $row;
}
$stmt->close();

// Unread counts per user
$unreadMap = [];
$stmt = $conn->prepare("
    SELECT sender_id, COUNT(*) AS cnt
    FROM chat_messages
    WHERE receiver_id = ? AND is_read = 0
    GROUP BY sender_id
");
$stmt->bind_param('i', $userId);
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) {
    $unreadMap[(int)$row['sender_id']] = (int)$row['cnt'];
}
$stmt->close();

$chatPartner = null;
$messages = [];

if ($withId > 0 && $withId !== $userId) {
    $chatPartner = get_user($withId);
    if ($chatPartner) {
        // Mark incoming messages as read
        $stmt = $conn->prepare("UPDATE chat_messages SET is_read = 1 WHERE sender_id = ? AND receiver_id = ? AND is_read = 0");
        $stmt->bind_param('ii', $withId, $userId);
        $stmt->execute();
        $stmt->close();

        // Load conversation
        $stmt = $conn->prepare("
            SELECT * FROM chat_messages
            WHERE (sender_id = ? AND receiver_id = ?) OR (sender_id = ? AND receiver_id = ?)
            ORDER BY created_at ASC
        ");
        $stmt->bind_param('iiii', $userId, $withId, $withId, $userId);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($row = $res->fetch_assoc()) {
            $messages[] = $row;
        }
        $stmt->close();
    } else {
        $withId = 0;
    }
}

$page_title = 'Chat';
$nav_active = 'user_chat';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container mt-4 mb-5">
    <h2 class="mb-4"><i class="fas fa-comments me-2"></i>Chat with Members</h2>

    <div class="row g-3 chat-layout">
        <!-- Members list -->
        <div class="col-md-4 col-lg-3">
            <div class="card shadow h-100">
                <div class="card-header bg-white">
                    <strong><i class="fas fa-users me-1"></i>Members</strong>
                </div>
                <div class="list-group list-group-flush chat-member-list">
                    <?php if (empty($members)): ?>
                        <div class="p-3 text-muted small">No other members yet.</div>
                    <?php else: foreach ($members as $m): ?>
                        <?php
                            $active = ($withId === (int)$m['id']);
                            $unread = $unreadMap[(int)$m['id']] ?? 0;
                        ?>
                        <a href="<?php echo site_url('user/chat.php?with=' . (int)$m['id']); ?>"
                           class="list-group-item list-group-item-action d-flex align-items-center <?php echo $active ? 'active' : ''; ?>">
                            <?php if (!empty($m['profile_photo']) && file_exists(__DIR__ . '/../' . $m['profile_photo'])): ?>
                                <img src="<?php echo site_url($m['profile_photo']); ?>" class="chat-avatar me-2" alt="">
                            <?php else: ?>
                                <span class="chat-avatar-placeholder me-2"><i class="fas fa-user"></i></span>
                            <?php endif; ?>
                            <span class="flex-grow-1 text-truncate">
                                <?php echo sanitize($m['full_name']); ?>
                                <br><small class="<?php echo $active ? 'text-white-50' : 'text-muted'; ?>">@<?php echo sanitize($m['username']); ?></small>
                            </span>
                            <?php if ($unread > 0 && !$active): ?>
                                <span class="badge bg-danger rounded-pill"><?php echo $unread; ?></span>
                            <?php endif; ?>
                        </a>
                    <?php endforeach; endif; ?>
                </div>
            </div>
        </div>

        <!-- Conversation -->
        <div class="col-md-8 col-lg-9">
            <div class="card shadow h-100 d-flex flex-column">
                <?php if (!$chatPartner): ?>
                    <div class="card-body d-flex align-items-center justify-content-center text-muted">
                        <div class="text-center">
                            <i class="fas fa-comments fa-3x mb-3 opacity-50"></i>
                            <p class="mb-0">Select a member to start chatting.</p>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="card-header bg-white d-flex align-items-center">
                        <?php if (!empty($chatPartner['profile_photo']) && file_exists(__DIR__ . '/../' . $chatPartner['profile_photo'])): ?>
                            <img src="<?php echo site_url($chatPartner['profile_photo']); ?>" class="chat-avatar me-2" alt="">
                        <?php else: ?>
                            <span class="chat-avatar-placeholder me-2"><i class="fas fa-user"></i></span>
                        <?php endif; ?>
                        <div>
                            <strong><?php echo sanitize($chatPartner['full_name']); ?></strong>
                            <br><small class="text-muted">@<?php echo sanitize($chatPartner['username']); ?></small>
                        </div>
                    </div>

                    <div class="card-body chat-messages" id="chatMessages">
                        <?php if (empty($messages)): ?>
                            <p class="text-muted text-center small my-4">No messages yet. Say hello!</p>
                        <?php else: foreach ($messages as $msg): ?>
                            <?php $mine = ((int)$msg['sender_id'] === $userId); ?>
                            <div class="chat-bubble-row <?php echo $mine ? 'mine' : 'theirs'; ?>">
                                <div class="chat-bubble <?php echo $mine ? 'chat-bubble-mine' : 'chat-bubble-theirs'; ?>">
                                    <div><?php echo nl2br(sanitize($msg['message'])); ?></div>
                                    <div class="chat-time"><?php echo date('M j, g:i A', strtotime($msg['created_at'])); ?></div>
                                </div>
                            </div>
                        <?php endforeach; endif; ?>
                    </div>

                    <div class="card-footer bg-white">
                        <form method="post" class="d-flex gap-2">
                            <input type="hidden" name="action" value="send_chat">
                            <input type="hidden" name="receiver_id" value="<?php echo (int)$chatPartner['id']; ?>">
                            <input type="text" name="message" class="form-control" placeholder="Type a message..." required autocomplete="off" maxlength="2000">
                            <button type="submit" class="btn btn-primary"><i class="fas fa-paper-plane"></i></button>
                        </form>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    var box = document.getElementById('chatMessages');
    if (box) box.scrollTop = box.scrollHeight;
})();
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
