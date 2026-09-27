<?php
// contact-process.php — handles contact form (user → admin)
require_once __DIR__ . '/config/session.php';
require_once __DIR__ . '/config/functions.php';

if (!is_user_logged_in()) {
    set_flash('error', 'Please log in to send a message.');
    redirect('index.php');
}

$redirectTo = 'user/dashboard.php#contact';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect($redirectTo);
}

$userId = (int)$_SESSION['user_id'];
$email = trim($_POST['email'] ?? '');
$subject = trim($_POST['subject'] ?? '');
$message = trim($_POST['message'] ?? '');

if ($email === '' || $subject === '' || $message === '') {
    set_flash('error', 'All fields are required.');
    redirect($redirectTo);
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    set_flash('error', 'Please enter a valid email address.');
    redirect($redirectTo);
}

$conn = db();
$stmt = $conn->prepare("INSERT INTO contact_messages (user_id, email, subject, message) VALUES (?, ?, ?, ?)");
$stmt->bind_param('isss', $userId, $email, $subject, $message);
$stmt->execute();
$stmt->close();

$to = 'admin@tourgroup.local';
$mailBody = "You received a new contact message:\n\nFrom: $email\nSubject: $subject\n\nMessage:\n$message";
$sent = send_contact_mail($to, $email, $subject, $mailBody);

if ($sent) {
    set_flash('success', 'Your message has been sent successfully!');
} else {
    set_flash('success', 'Your message has been received! We will get back to you soon.');
}

redirect($redirectTo);
