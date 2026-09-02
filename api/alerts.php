<?php
require_once __DIR__ . '/../includes/auth.php';
requireLogin();
header('Content-Type: application/json');
$conn = getDbConnection();

$action = $_GET['action'] ?? 'list';

if ($action === 'unread_count') {
    $count = $conn->query('SELECT COUNT(*) c FROM alerts WHERE is_read = 0')->fetch_assoc()['c'] ?? 0;
    echo json_encode(['ok' => true, 'count' => (int)$count]);
    exit;
}

if ($action === 'list') {
    $rows = $conn->query("SELECT * FROM alerts ORDER BY FIELD(type,'CRITICAL','WARNING','INFO'), created_at DESC")->fetch_all(MYSQLI_ASSOC);
    echo json_encode(['ok' => true, 'data' => $rows]);
    exit;
}

http_response_code(400);
echo json_encode(['ok' => false, 'error' => 'Unsupported action']);
