<?php
require_once __DIR__ . '/includes/auth.php';
requireLogin();
require_once __DIR__ . '/includes/risk-engine.php';
$conn = getDbConnection();
$pageTitle = 'Reports';

$reportType = $_GET['type'] ?? '';
$data = [];
$title = '';

switch ($reportType) {
    case 'risk_assessment':
        $title = 'Risk Assessment Report';
        $data = $conn->query('SELECT name, district, population, risk_score, risk_level FROM habitations ORDER BY risk_score DESC')->fetch_all(MYSQLI_ASSOC);
        break;
    case 'red_zone':
        $title = 'Red Zone Report';
        $data = $conn->query("SELECT name, district, population, vulnerable_population, primary_hazard, risk_score FROM habitations WHERE risk_level='CRITICAL' ORDER BY risk_score DESC")->fetch_all(MYSQLI_ASSOC);
        break;
    case 'relocation_priority':
        $title = 'Relocation Priority Report';
        $data = $conn->query('SELECT name, district, population, priority_score, priority FROM habitations ORDER BY priority_score DESC')->fetch_all(MYSQLI_ASSOC);
        break;
    case 'site_capacity':
        $title = 'Relocation Site Capacity Report';
        $data = $conn->query('SELECT name, district, max_capacity, current_population, suitability_score FROM relocation_sites ORDER BY suitability_score DESC')->fetch_all(MYSQLI_ASSOC);
        break;
    case 'district_summary':
        $title = 'District Summary Report';
        $data = $conn->query('SELECT district, COUNT(*) habitations, SUM(population) population, AVG(risk_score) avg_risk FROM habitations GROUP BY district ORDER BY avg_risk DESC')->fetch_all(MYSQLI_ASSOC);
        break;
}

if ($reportType && isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="resqzone_' . $reportType . '.csv"');
    $out = fopen('php://output', 'w');
    if (!empty($data)) {
        fputcsv($out, array_keys($data[0]));
        foreach ($data as $row) fputcsv($out, $row);
    }
    fclose($out);
    exit;
}

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';
?>
<h3 class="text-white mb-1"><i class="fa-solid fa-file-lines me-2"></i>Reports</h3>
<p class="text-dim small mb-3">Generate operational reports from live RESQZONE data.</p>

<div class="row g-3 mb-4">
  <?php
  $types = [
    ['risk_assessment', 'Risk Assessment Report', 'fa-chart-line'],
    ['red_zone', 'Red Zone Report', 'fa-triangle-exclamation'],
    ['relocation_priority', 'Relocation Priority Report', 'fa-people-roof'],
    ['site_capacity', 'Relocation Site Capacity Report', 'fa-warehouse'],
    ['district_summary', 'District Summary Report', 'fa-map'],
  ];
  foreach ($types as $t): ?>
    <div class="col-md-6 col-lg-4">
      <a href="reports.php?type=<?= $t[0] ?>" class="text-decoration-none">
        <div class="hz-panel p-3 h-100 <?= $reportType === $t[0] ? 'border border-2' : '' ?>" style="<?= $reportType === $t[0] ? 'border-color:var(--hz-accent) !important' : '' ?>">
          <i class="fa-solid <?= $t[2] ?> mb-2" style="color:var(--hz-accent-2)"></i>
          <div class="text-white fw-semibold small"><?= $t[1] ?></div>
        </div>
      </a>
    </div>
  <?php endforeach; ?>
</div>

<?php if ($reportType && !empty($title)): ?>
<div class="hz-panel" id="reportArea">
  <div class="hz-panel-header">
    <div>
      <h5 class="mb-0"><i class="fa-solid fa-tower-broadcast me-2" style="color:var(--hz-accent)"></i>RESQZONE — <?= $title ?></h5>
      <div class="text-faint small">Generated <?= date('d M Y, H:i') ?> · DEMO / PROTOTYPE DATA</div>
    </div>
    <div class="d-flex gap-2 no-print">
      <button class="btn btn-hz-outline btn-sm" onclick="window.print()"><i class="fa-solid fa-print me-1"></i>Print</button>
      <a class="btn btn-hz-primary btn-sm" href="reports.php?type=<?= $reportType ?>&export=csv"><i class="fa-solid fa-file-csv me-1"></i>Export CSV</a>
    </div>
  </div>
  <div class="table-responsive hz-panel-body">
    <table class="hz-table">
      <thead><tr><?php foreach (array_keys($data[0] ?? []) as $col): ?><th><?= ucwords(str_replace('_',' ',$col)) ?></th><?php endforeach; ?></tr></thead>
      <tbody>
      <?php foreach ($data as $row): ?>
        <tr><?php foreach ($row as $k => $v): ?><td><?= is_numeric($v) ? (str_contains($k,'score')||str_contains($k,'risk') ? round($v,1) : number_format($v) ) : sanitize($v) ?></td><?php endforeach; ?></tr>
      <?php endforeach; ?>
      </tbody>
    </table>
    <?php if (empty($data)): ?><div class="empty-state"><i class="fa-solid fa-inbox"></i><div>No records match this report.</div></div><?php endif; ?>
  </div>
</div>
<style media="print">
  .app-navbar, .sidebar, .app-footer, .no-print, .demo-banner { display:none !important; }
  .main-content{ padding:0 !important; }
  body{ background:#fff !important; color:#000 !important; }
  .hz-table thead th, .hz-table tbody td{ color:#000 !important; border-color:#ccc !important; }
</style>
<?php endif; ?>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
