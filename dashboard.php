<?php
require_once __DIR__ . '/includes/auth.php';
requireLogin();
require_once __DIR__ . '/includes/risk-engine.php';
$conn = getDbConnection();
$pageTitle = 'Dashboard';

$totalHab = $conn->query('SELECT COUNT(*) c FROM habitations')->fetch_assoc()['c'];
$criticalCount = $conn->query("SELECT COUNT(*) c FROM habitations WHERE risk_level='CRITICAL'")->fetch_assoc()['c'];
$popAtRisk = $conn->query("SELECT SUM(population) s FROM habitations WHERE risk_level IN ('CRITICAL','HIGH')")->fetch_assoc()['s'] ?? 0;
$immediateCount = $conn->query("SELECT COUNT(*) c FROM habitations WHERE priority='IMMEDIATE'")->fetch_assoc()['c'];
$availCapacity = $conn->query('SELECT SUM(max_capacity-current_population) s FROM relocation_sites')->fetch_assoc()['s'] ?? 0;

$riskDist = $conn->query("SELECT risk_level, COUNT(*) c FROM habitations GROUP BY risk_level")->fetch_all(MYSQLI_ASSOC);
$hazardDist = $conn->query("SELECT primary_hazard, COUNT(*) c FROM habitations GROUP BY primary_hazard")->fetch_all(MYSQLI_ASSOC);
$priorityDist = $conn->query("SELECT priority, COUNT(*) c FROM habitations GROUP BY priority")->fetch_all(MYSQLI_ASSOC);
$exposureRows = $conn->query("SELECT name, population FROM habitations ORDER BY population DESC LIMIT 8")->fetch_all(MYSQLI_ASSOC);

$alerts = $conn->query("SELECT * FROM alerts ORDER BY FIELD(type,'CRITICAL','WARNING','INFO'), created_at DESC LIMIT 6")->fetch_all(MYSQLI_ASSOC);

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';
?>
<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
  <div>
    <h3 class="text-white mb-0">Operations Dashboard</h3>
    <div class="text-dim small">Live overview of habitation risk and relocation readiness</div>
  </div>
  <form method="post" action="api/risk.php">
    <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
    <button class="btn btn-hz-outline btn-sm" name="action" value="recalculate_all"><i class="fa-solid fa-arrows-rotate me-1"></i>Recalculate Risk Scores</button>
  </form>
</div>

<div class="row g-3 mb-4">
  <div class="col-6 col-lg-3">
    <div class="hz-panel kpi-card"><i class="fa-solid fa-house-chimney-crack kpi-icon"></i>
      <div class="kpi-label">Total Habitations</div><div class="kpi-value"><?= (int)$totalHab ?></div>
      <div class="kpi-trend flat"><i class="fa-solid fa-minus"></i> tracked across 12 districts</div></div>
  </div>
  <div class="col-6 col-lg-3">
    <div class="hz-panel kpi-card"><i class="fa-solid fa-triangle-exclamation kpi-icon" style="color:var(--hz-critical)"></i>
      <div class="kpi-label">Critical / Red Zones</div><div class="kpi-value" style="color:var(--hz-critical)"><?= (int)$criticalCount ?></div>
      <div class="kpi-trend up"><i class="fa-solid fa-arrow-up"></i> requires urgent review</div></div>
  </div>
  <div class="col-6 col-lg-3">
    <div class="hz-panel kpi-card"><i class="fa-solid fa-users kpi-icon"></i>
      <div class="kpi-label">Population at Risk</div><div class="kpi-value"><?= number_format((int)$popAtRisk) ?></div>
      <div class="kpi-trend up"><i class="fa-solid fa-arrow-up"></i> in HIGH/CRITICAL zones</div></div>
  </div>
  <div class="col-6 col-lg-3">
    <div class="hz-panel kpi-card"><i class="fa-solid fa-truck-fast kpi-icon" style="color:var(--hz-high)"></i>
      <div class="kpi-label">Immediate Relocation</div><div class="kpi-value" style="color:var(--hz-high)"><?= (int)$immediateCount ?></div>
      <div class="kpi-trend flat">habitations flagged IMMEDIATE</div></div>
  </div>
</div>
<div class="row g-3 mb-4">
  <div class="col-lg-4">
    <div class="hz-panel kpi-card"><i class="fa-solid fa-warehouse kpi-icon" style="color:var(--hz-safe)"></i>
      <div class="kpi-label">Available Relocation Capacity</div><div class="kpi-value" style="color:var(--hz-safe)"><?= number_format((int)$availCapacity) ?></div>
      <div class="kpi-trend down"><i class="fa-solid fa-arrow-down"></i> across 10 relocation sites</div></div>
  </div>
  <div class="col-lg-8">
    <div class="hz-panel h-100">
      <div class="hz-panel-header"><h5><i class="fa-solid fa-bolt me-2" style="color:var(--hz-accent)"></i>Critical Alerts</h5><a href="alerts.php" class="btn btn-hz-ghost btn-sm">View all <i class="fa-solid fa-arrow-right ms-1"></i></a></div>
      <div class="hz-panel-body" style="max-height:230px; overflow-y:auto;">
        <?php if (empty($alerts)): ?>
          <div class="empty-state py-3"><i class="fa-solid fa-check-circle"></i><div>No active alerts.</div></div>
        <?php else: foreach ($alerts as $a): ?>
          <div class="alert-item <?= $a['type'] ?>">
            <span class="alert-tag <?= $a['type'] ?>"><?= $a['type'] ?></span>
            <div class="text-white small fw-semibold mt-1"><?= sanitize($a['title']) ?></div>
            <div class="text-dim small"><?= sanitize($a['message']) ?></div>
          </div>
        <?php endforeach; endif; ?>
      </div>
    </div>
  </div>
</div>

<div class="row g-3 mb-4">
  <div class="col-lg-6">
    <div class="hz-panel h-100"><div class="hz-panel-header"><h5>Risk Distribution</h5></div>
      <div class="hz-panel-body"><canvas id="riskDistChart" height="220"></canvas></div></div>
  </div>
  <div class="col-lg-6">
    <div class="hz-panel h-100"><div class="hz-panel-header"><h5>Hazard Distribution</h5></div>
      <div class="hz-panel-body"><canvas id="hazardDistChart" height="220"></canvas></div></div>
  </div>
</div>
<div class="row g-3 mb-4">
  <div class="col-lg-6">
    <div class="hz-panel h-100"><div class="hz-panel-header"><h5>Relocation Priority</h5></div>
      <div class="hz-panel-body"><canvas id="priorityChart" height="220"></canvas></div></div>
  </div>
  <div class="col-lg-6">
    <div class="hz-panel h-100"><div class="hz-panel-header"><h5>Population Exposure (Top Habitations)</h5></div>
      <div class="hz-panel-body"><canvas id="exposureChart" height="220"></canvas></div></div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
<script>
Chart.defaults.color = '#93a0bb';
Chart.defaults.borderColor = 'rgba(148,163,184,0.14)';
const riskColors = {CRITICAL:'#e11d2e',HIGH:'#f97316',MODERATE:'#eab308',LOW:'#3b82f6',SAFE:'#22c55e'};
const priColors = {IMMEDIATE:'#e11d2e','SHORT-TERM':'#f97316','MEDIUM-TERM':'#eab308',MONITOR:'#22c55e'};

const riskData = <?= json_encode($riskDist) ?>;
new Chart(document.getElementById('riskDistChart'), {
  type: 'doughnut',
  data: { labels: riskData.map(r=>r.risk_level), datasets:[{ data: riskData.map(r=>r.c), backgroundColor: riskData.map(r=>riskColors[r.risk_level]||'#666'), borderWidth:0 }] },
  options: { plugins:{ legend:{ position:'bottom' } }, cutout:'62%' }
});

const hazardData = <?= json_encode($hazardDist) ?>;
new Chart(document.getElementById('hazardDistChart'), {
  type: 'bar',
  data: { labels: hazardData.map(r=>r.primary_hazard), datasets:[{ label:'Habitations', data: hazardData.map(r=>r.c), backgroundColor:'#3b82f6', borderRadius:6 }] },
  options: { plugins:{legend:{display:false}}, scales:{ y:{beginAtZero:true, grid:{color:'rgba(148,163,184,0.08)'}}, x:{grid:{display:false}} } }
});

const priorityData = <?= json_encode($priorityDist) ?>;
new Chart(document.getElementById('priorityChart'), {
  type: 'pie',
  data: { labels: priorityData.map(r=>r.priority), datasets:[{ data: priorityData.map(r=>r.c), backgroundColor: priorityData.map(r=>priColors[r.priority]||'#666'), borderWidth:0 }] },
  options: { plugins:{legend:{position:'bottom'}} }
});

const exposureData = <?= json_encode($exposureRows) ?>;
new Chart(document.getElementById('exposureChart'), {
  type: 'bar',
  data: { labels: exposureData.map(r=>r.name), datasets:[{ label:'Population', data: exposureData.map(r=>r.population), backgroundColor:'#ff5a4e', borderRadius:6 }] },
  options: { indexAxis:'y', plugins:{legend:{display:false}}, scales:{ x:{beginAtZero:true, grid:{color:'rgba(148,163,184,0.08)'}}, y:{grid:{display:false}} } }
});
</script>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
