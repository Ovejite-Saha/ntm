<?php
// config/session.php — session security & 20-minute inactivity timeout

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Regenerate session ID periodically to prevent fixation
if (!isset($_SESSION['initiated'])) {
    session_regenerate_id(true);
    $_SESSION['initiated'] = true;
}

// 20-minute (1200 seconds) inactivity timeout for logged-in users
if (isset($_SESSION['last_activity'])) {
    $inactive = 1200;
    if (time() - $_SESSION['last_activity'] > $inactive) {
        session_unset();
        session_destroy();
        header('Location: ' . base_url('login-redirect.php?timeout=1'));
        exit;
    }
}
$_SESSION['last_activity'] = time();

// Determine if current user is admin or user
function is_admin_logged_in() {
    return isset($_SESSION['admin_id']);
}

function is_user_logged_in() {
    return isset($_SESSION['user_id']);
}

function is_logged_in() {
    return is_admin_logged_in() || is_user_logged_in();
}

function require_admin() {
    if (!is_admin_logged_in()) {
        header('Location: ' . base_url('login-redirect.php'));
        exit;
    }
}

function require_user() {
    if (!is_user_logged_in()) {
        header('Location: ' . base_url('login-redirect.php'));
        exit;
    }
}

function base_url($path = '') {
    $base = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
    // Go to project root from current subfolder
    if (strpos($_SERVER['SCRIPT_NAME'], '/admin/') !== false || strpos($_SERVER['SCRIPT_NAME'], '/user/') !== false) {
        $base = substr($base, 0, strrpos($base, '/'));
    }
    return rtrim($base, '/') . '/' . ltrim($path, '/');
}

function site_url($path = '') {
    // Compute project root URL
    $script = $_SERVER['SCRIPT_NAME'];
    $root = '/';
    if (strpos($script, '/admin/') !== false) {
        $root = substr($script, 0, strpos($script, '/admin/')) . '/';
    } elseif (strpos($script, '/user/') !== false) {
        $root = substr($script, 0, strpos($script, '/user/')) . '/';
    } elseif (basename($script) === 'index.php' || basename($script) === 'contact-process.php' || basename($script) === 'login-redirect.php') {
        $root = rtrim(dirname($script), '/') . '/';
        if ($root === '\\/' || $root === '') {
            $root = '/';
        }
    }
    return $root . ltrim($path, '/');
}
