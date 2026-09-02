<?php
require_once __DIR__ . '/includes/auth.php';
requireLogin();
require_once __DIR__ . '/includes/risk-engine.php';
$conn = getDbConnection();
$pageTitle = 'Habitations';

$districts = $conn->query('SELECT DISTINCT district FROM habitations ORDER BY district')->fetch_all(MYSQLI_ASSOC);
$rows = $conn->query('SELECT * FROM habitations ORDER BY risk_score DESC')->fetch_all(MYSQLI_ASSOC);

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';
?>
<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
  <div><h3 class="text-white mb-0">Habitation Management</h3><div class="text-dim small"><?= count($rows) ?> habitations tracked</div></div>
  <a href="add-habitation.php" class="btn btn-hz-primary btn-sm"><i class="fa-solid fa-plus me-1"></i>Add Habitation</a>
</div>

<div class="hz-panel p-3 mb-3">
  <div class="row g-2">
    <div class="col-md-3"><input id="fSearch" class="form-control hz-form-control" placeholder="Search habitation..."></div>
    <div class="col-md-3">
      <select id="fDistrict" class="form-select hz-form-control">
        <option value="">All Districts</option>
        <?php foreach ($districts as $d): ?><option value="<?= sanitize($d['district']) ?>"><?= sanitize($d['district']) ?></option><?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-3">
      <select id="fRisk" class="form-select hz-form-control">
        <option value="">All Risk Levels</option>
        <option>CRITICAL</option><option>HIGH</option><option>MODERATE</option><option>LOW</option><option>SAFE</option>
      </select>
    </div>
    <div class="col-md-3">
      <select id="fPriority" class="form-select hz-form-control">
        <option value="">All Priorities</option>
        <option value="IMMEDIATE">IMMEDIATE</option><option value="SHORT-TERM">SHORT-TERM</option>
        <option value="MEDIUM-TERM">MEDIUM-TERM</option><option value="MONITOR">MONITOR</option>
      </select>
    </div>
  </div>
</div>

<div class="hz-panel">
  <div class="table-responsive">
    <table class="hz-table" id="habTable">
      <thead><tr>
        <th>ID</th><th>Habitation</th><th>District</th><th>Population</th><th>Vulnerable</th>
        <th>Flood</th><th>Landslide</th><th>Events</th><th>Overall Risk</th><th>Priority</th><th>Action</th>
      </tr></thead>
      <tbody>
      <?php foreach ($rows as $h):
        $riskClass = 'risk-' . strtolower($h['risk_level'] ?? 'safe');
        $priClass = 'priority-' . strtolower(str_replace(' ', '-', $h['priority'] ?? 'monitor'));
      ?>
        <tr data-name="<?= sanitize($h['name']) ?>" data-district="<?= sanitize($h['district']) ?>"
            data-risk="<?= sanitize($h['risk_level'] ?? '') ?>" data-priority="<?= sanitize($h['priority'] ?? '') ?>">
          <td class="mono text-faint">#<?= $h['id'] ?></td>
          <td class="text-white fw-semibold"><?= sanitize($h['name']) ?></td>
          <td><?= sanitize($h['district']) ?></td>
          <td><?= number_format($h['population']) ?></td>
          <td><?= number_format($h['vulnerable_population']) ?></td>
          <td><?= (int)$h['flood_risk'] ?>/100</td>
          <td><?= (int)$h['landslide_risk'] ?>/100</td>
          <td><?= (int)$h['historical_events'] ?></td>
          <td><span class="risk-badge <?= $riskClass ?>"><?= sanitize($h['risk_level'] ?? '—') ?> · <?= $h['risk_score'] ?? '—' ?></span></td>
          <td><span class="risk-badge <?= $priClass ?>"><?= sanitize($h['priority'] ?? '—') ?></span></td>
          <td class="text-nowrap">
            <a href="edit-habitation.php?id=<?= $h['id'] ?>" class="btn btn-hz-ghost btn-sm" title="Edit"><i class="fa-solid fa-pen"></i></a>
            <a href="ai-analysis.php?id=<?= $h['id'] ?>" class="btn btn-hz-ghost btn-sm" title="Analyze"><i class="fa-solid fa-brain"></i></a>
            <a href="risk-map.php?focus=<?= $h['id'] ?>" class="btn btn-hz-ghost btn-sm" title="View on map"><i class="fa-solid fa-map-location-dot"></i></a>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
    <?php if (empty($rows)): ?><div class="empty-state"><i class="fa-solid fa-inbox"></i><div>No habitations yet. Add one to get started.</div></div><?php endif; ?>
  </div>
</div>

<script>
function applyFilters(){
  HZ.filterTable('habTable', {
    name: document.getElementById('fSearch').value,
    district: document.getElementById('fDistrict').value,
    risk: document.getElementById('fRisk').value,
    priority: document.getElementById('fPriority').value,
  });
}
['fSearch','fDistrict','fRisk','fPriority'].forEach(id => document.getElementById(id).addEventListener('input', applyFilters));
document.getElementById('fDistrict').addEventListener('change', applyFilters);
document.getElementById('fRisk').addEventListener('change', applyFilters);
document.getElementById('fPriority').addEventListener('change', applyFilters);
</script>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
