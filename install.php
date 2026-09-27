<?php
// install.php — run once to create database tables and default admin
require_once __DIR__ . '/config/db.php';

$sqlFile = __DIR__ . '/database.sql';
$sql = file_get_contents($sqlFile);

// We need a connection without specifying DB first for CREATE DATABASE
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS);
if ($conn->connect_error) {
    die('Connection failed: ' . $conn->connect_error);
}

// Split and execute statements
$statements = array_filter(array_map('trim', explode(';', $sql)));
$ok = true;
foreach ($statements as $stmt) {
    if (empty($stmt) || strpos($stmt, '--') === 0) continue;
    if (!$conn->query($stmt)) {
        echo "SQL Error: " . $conn->error . "<br>";
        $ok = false;
    }
}

// Create default admin with properly hashed password
$conn->select_db(DB_NAME);
$check = $conn->query("SELECT id FROM admins WHERE username = 'admin'");
if ($check && $check->num_rows === 0) {
    $hash = password_hash('admin123', PASSWORD_DEFAULT);
    $stmt = $conn->prepare("INSERT INTO admins (username, password) VALUES ('admin', ?)");
    $stmt->bind_param('s', $hash);
    $stmt->execute();
    $stmt->close();
    echo "<p>Default admin created. Username: <strong>admin</strong> Password: <strong>admin123</strong></p>";
} else {
    echo "<p>Default admin already exists.</p>";
}

$conn->close();

if ($ok) {
    echo '<p class="text-success">Database installed successfully! Please delete this file.</p>';
    echo '<p><a href="index.php">Go to Homepage</a></p>';
} else {
    echo '<p class="text-danger">Some errors occurred. Check above.</p>';
}
?>
