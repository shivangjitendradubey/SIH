<?php
require_once __DIR__ . '/../includes/auth.php';
requireLogin();
header('Content-Type: application/json');
$conn = getDbConnection();

$action = $_GET['action'] ?? 'list';

if ($action === 'list') {
    $district = $_GET['district'] ?? '';
    $risk = $_GET['risk'] ?? '';
    $sql = 'SELECT * FROM habitations WHERE 1=1';
    $params = []; $types = '';
    if ($district !== '') { $sql .= ' AND district = ?'; $params[] = $district; $types .= 's'; }
    if ($risk !== '') { $sql .= ' AND risk_level = ?'; $params[] = $risk; $types .= 's'; }
    $sql .= ' ORDER BY risk_score DESC';
    $stmt = $conn->prepare($sql);
    if ($params) $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    echo json_encode(['ok' => true, 'data' => $rows]);
    exit;
}

if ($action === 'get' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $stmt = $conn->prepare('SELECT * FROM habitations WHERE id=?');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    echo json_encode(['ok' => (bool)$row, 'data' => $row]);
    exit;
}

http_response_code(400);
echo json_encode(['ok' => false, 'error' => 'Unsupported action']);
