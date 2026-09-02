<?php
require_once __DIR__ . '/../includes/auth.php';
requireLogin();
require_once __DIR__ . '/../includes/risk-engine.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'recalculate_all') {
    if (!verifyCsrf()) {
        http_response_code(403);
        echo json_encode(['ok' => false, 'error' => 'Invalid session token']);
        exit;
    }
    $count = recalculateAllHabitations();
    logAction('recalculate_risk', "Recalculated risk for $count habitations");

    // If this was a normal form post (not fetch/AJAX), redirect back for a nicer UX
    if (!isset($_SERVER['HTTP_X_REQUESTED_WITH'])) {
        header('Location: ../dashboard.php?recalculated=' . $count);
        exit;
    }
    echo json_encode(['ok' => true, 'recalculated' => $count]);
    exit;
}

http_response_code(400);
echo json_encode(['ok' => false, 'error' => 'Unsupported action']);
