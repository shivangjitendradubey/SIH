<?php
require_once __DIR__ . '/../includes/auth.php';
requireAdmin();
require_once __DIR__ . '/../includes/risk-engine.php';
$conn = getDbConnection();
$pageTitle = 'Settings';
$base = '../';
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCsrf()) {
    $keys = ['weight_hazard','weight_vulnerability','weight_exposure','weight_historical',
             'priority_weight_risk','priority_weight_vulnerability','priority_weight_exposure','priority_weight_historical'];
    $stmt = $conn->prepare('UPDATE risk_config SET config_value=? WHERE config_key=?');
    foreach ($keys as $k) {
        if (isset($_POST[$k])) {
            $val = (float)$_POST[$k];
            $stmt->bind_param('ds', $val, $k);
            $stmt->execute();
        }
    }
    logAction('update_risk_config', 'Admin updated risk scoring weights');
    $recalculated = recalculateAllHabitations();
    $message = "Risk configuration updated. $recalculated habitations recalculated.";
}

$config = [];
$res = $conn->query('SELECT * FROM risk_config');
while ($row = $res->fetch_assoc()) { $config[$row['config_key']] = $row['config_value']; }

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>
<h3 class="text-white mb-1"><i class="fa-solid fa-sliders me-2"></i>Risk Engine Settings</h3>
<p class="text-dim small mb-3">Adjust the weighting of the risk and priority formulas. Changes apply immediately and all habitations are recalculated on save.</p>
<?php if ($message): ?><div class="alert alert-info py-2 small"><?= sanitize($message) ?></div><?php endif; ?>

<form method="POST">
  <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
  <div class="row g-3">
    <div class="col-lg-6">
      <div class="hz-panel p-4">
        <h6 class="text-white mb-3">Overall Risk Score Weights</h6>
        <?php $riskLabels = ['weight_hazard'=>'Hazard Risk','weight_vulnerability'=>'Population Vulnerability','weight_exposure'=>'Exposure','weight_historical'=>'Historical Disaster Impact'];
        foreach ($riskLabels as $k => $label): ?>
        <label class="hz-label d-flex justify-content-between"><?= $label ?> <span class="mono"><?= round($config[$k]*100) ?>%</span></label>
        <input type="number" step="0.01" min="0" max="1" name="<?= $k ?>" value="<?= $config[$k] ?>" class="form-control hz-form-control mb-3">
        <?php endforeach; ?>
        <div class="text-faint small">Weights should sum to 1.0 (100%) for a normalized score.</div>
      </div>
    </div>
    <div class="col-lg-6">
      <div class="hz-panel p-4">
        <h6 class="text-white mb-3">Relocation Priority Score Weights</h6>
        <?php $priLabels = ['priority_weight_risk'=>'Overall Risk','priority_weight_vulnerability'=>'Vulnerability','priority_weight_exposure'=>'Population Exposure','priority_weight_historical'=>'Historical Disaster Impact'];
        foreach ($priLabels as $k => $label): ?>
        <label class="hz-label d-flex justify-content-between"><?= $label ?> <span class="mono"><?= round($config[$k]*100) ?>%</span></label>
        <input type="number" step="0.01" min="0" max="1" name="<?= $k ?>" value="<?= $config[$k] ?>" class="form-control hz-form-control mb-3">
        <?php endforeach; ?>
        <div class="text-faint small">Weights should sum to 1.0 (100%) for a normalized score.</div>
      </div>
    </div>
  </div>
  <button class="btn btn-hz-primary px-4 mt-3"><i class="fa-solid fa-floppy-disk me-1"></i>Save &amp; Recalculate All</button>
</form>

<div class="hz-panel p-4 mt-4">
  <h6 class="text-white mb-2">Classification Thresholds (fixed by spec)</h6>
  <div class="row text-center g-2">
    <div class="col"><span class="risk-badge risk-safe">SAFE</span><div class="text-faint small mt-1">0–30</div></div>
    <div class="col"><span class="risk-badge risk-low">LOW</span><div class="text-faint small mt-1">31–50</div></div>
    <div class="col"><span class="risk-badge risk-moderate">MODERATE</span><div class="text-faint small mt-1">51–70</div></div>
    <div class="col"><span class="risk-badge risk-high">HIGH</span><div class="text-faint small mt-1">71–85</div></div>
    <div class="col"><span class="risk-badge risk-critical">CRITICAL</span><div class="text-faint small mt-1">86–100</div></div>
  </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
