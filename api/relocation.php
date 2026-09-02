<?php
require_once __DIR__ . '/../includes/auth.php';
requireLogin();
require_once __DIR__ . '/../includes/risk-engine.php';
header('Content-Type: application/json');
$conn = getDbConnection();

$action = $_GET['action'] ?? 'list';

if ($action === 'list') {
    $rows = $conn->query('SELECT * FROM relocation_sites ORDER BY suitability_score DESC')->fetch_all(MYSQLI_ASSOC);
    foreach ($rows as &$r) {
        $r['computed'] = computeSiteSuitability($r);
    }
    echo json_encode(['ok' => true, 'data' => $rows]);
    exit;
}

if ($action === 'recommend' && isset($_GET['habitation_id'])) {
    $id = (int)$_GET['habitation_id'];
    $stmt = $conn->prepare('SELECT * FROM habitations WHERE id=?');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $hab = $stmt->get_result()->fetch_assoc();
    if (!$hab) { echo json_encode(['ok' => false, 'error' => 'Habitation not found']); exit; }

    $sites = $conn->query('SELECT * FROM relocation_sites')->fetch_all(MYSQLI_ASSOC);
    foreach ($sites as &$s) { $s['suitability_score'] = computeSiteSuitability($s)['suitability_score']; }
    $best = recommendBestSite($hab, $sites);
    echo json_encode(['ok' => true, 'recommendation' => $best]);
    exit;
}

http_response_code(400);
echo json_encode(['ok' => false, 'error' => 'Unsupported action']);
