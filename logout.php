<?php
require_once __DIR__ . '/includes/auth.php';
if (isLoggedIn()) { logAction('logout', 'User logged out'); }
$_SESSION = [];
session_destroy();
header('Location: login.php?logged_out=1');
exit;
