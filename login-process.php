<?php
// login-process.php — handles login from modal for both user and admin
require_once __DIR__ . '/config/session.php';
require_once __DIR__ . '/config/functions.php';

$type = $_GET['type'] ?? '';
$username = trim($_POST['username'] ?? '');
$password = $_POST['password'] ?? '';

if ($username === '' || $password === '') {
    set_flash('error', 'Username and password are required.');
    redirect('index.php');
}

$conn = db();

if ($type === 'admin') {
    $stmt = $conn->prepare("SELECT id, username, password FROM admins WHERE username = ?");
    $stmt->bind_param('s', $username);
    $stmt->execute();
    $result = $stmt->get_result();
    $admin = $result->fetch_assoc();
    $stmt->close();

    if ($admin && password_verify($password, $admin['password'])) {
        session_regenerate_id(true);
        $_SESSION['admin_id'] = $admin['id'];
        $_SESSION['admin_username'] = $admin['username'];
        $_SESSION['last_activity'] = time();
        redirect('admin/index.php');
    } else {
        set_flash('error', 'Invalid admin credentials.');
        redirect('index.php');
    }
} elseif ($type === 'user') {
    $stmt = $conn->prepare("SELECT id, username, password FROM users WHERE username = ?");
    $stmt->bind_param('s', $username);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();

    if ($user && password_verify($password, $user['password'])) {
        session_regenerate_id(true);
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_username'] = $user['username'];
        $_SESSION['last_activity'] = time();
        redirect('user/dashboard.php');
    } else {
        set_flash('error', 'Invalid user credentials.');
        redirect('index.php');
    }
} else {
    set_flash('error', 'Invalid login type.');
    redirect('index.php');
}
