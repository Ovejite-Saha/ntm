<?php
// admin/logout.php
require_once __DIR__ . '/../config/session.php';
session_unset();
session_destroy();
header('Location: ' . site_url('index.php'));
exit;
