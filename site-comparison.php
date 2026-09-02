<?php
require_once __DIR__ . '/includes/auth.php';
requireLogin();
require_once __DIR__ . '/includes/risk-engine.php';
$conn = getDbConnection();
$pageTitle = 'Site Comparison';

$sites = $conn->query('SELECT * FROM relocation_sites ORDER BY name')->fetch_all(MYSQLI_ASSOC);
$selectedIds = array_filter(array_map('intval', explode(',', $_GET['sites'] ?? '')));
if (empty($selectedIds) && count($sites) >= 2) {
    $selectedIds = [$sites[0]['id'], $sites[1]['id']];
}
$selected = array_values(array_filter($sites, fn($s) => in_array($s['id'], $selectedIds)));

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';
?>
<h3 class="text-white mb-1"><i class="fa-solid fa-scale-balanced me-2"></i>Site Comparison</h3>
<p class="text-dim small mb-3">Select 2–3 relocation sites to compare side by side.</p>

<form method="GET" class="hz-panel p-3 mb-4">
  <div class="row g-2 align-items-end">
    <div class="col-md-8">
      <label class="hz-label">Choose up to 3 sites</label>
      <select name="sites_multi[]" id="siteMulti" class="form-select hz-form-control" multiple size="4">
        <?php foreach ($sites as $s): ?>
          <option value="<?= $s['id'] ?>" <?= in_array($s['id'], $selectedIds) ? 'selected' : '' ?>><?= sanitize($s['name']) ?> — <?= sanitize($s['district']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-4">
      <input type="hidden" name="sites" id="sitesHidden">
      <button type="submit" class="btn btn-hz-primary w-100" onclick="document.getElementById('sitesHidden').value=[...document.getElementById('siteMulti').selectedOptions].map(o=>o.value).join(',')">Compare <i class="fa-solid fa-arrow-right ms-1"></i></button>
    </div>
  </div>
</form>

<?php if (count($selected) >= 2):
  $bestScore = max(array_column($selected, 'suitability_score'));
  $rows = [
    ['label'=>'Hazard Risk','key'=>'hazard_risk','suffix'=>'/100','lowerBetter'=>true],
    ['label'=>'Max Capacity','key'=>'max_capacity','format'=>'number'],
    ['label'=>'Water Availability','key'=>'water_availability','suffix'=>'/100'],
    ['label'=>'Healthcare','key'=>'healthcare','suffix'=>'/100'],
    ['label'=>'Road Connectivity','key'=>'road_connectivity','suffix'=>'/100'],
    ['label'=>'Education (Schools)','key'=>'schools','suffix'=>'/100'],
    ['label'=>'Distance from Red Zone','key'=>'distance_from_red_zone_km','suffix'=>' km','lowerBetter'=>true],
    ['label'=>'Suitability Score','key'=>'suitability_score','suffix'=>'%','highlight'=>true],
  ];
?>
<div class="hz-panel">
  <div class="table-responsive">
    <table class="hz-table">
      <thead><tr><th>Attribute</th>
        <?php foreach ($selected as $s): ?><th class="text-white"><?= sanitize($s['name']) ?><?= $s['suitability_score'] == $bestScore ? ' <span class="risk-badge risk-safe ms-1">BEST</span>' : '' ?></th><?php endforeach; ?>
      </tr></thead>
      <tbody>
      <?php foreach ($rows as $r): ?>
        <tr>
          <td class="text-dim"><?= $r['label'] ?></td>
          <?php foreach ($selected as $s):
            $val = $s[$r['key']];
            $display = ($r['format'] ?? '') === 'number' ? number_format($val) : $val . ($r['suffix'] ?? '');
            $isBest = $s['suitability_score'] == $bestScore;
          ?>
            <td class="<?= ($r['highlight'] ?? false) && $isBest ? 'fw-bold' : '' ?>" style="<?= ($r['highlight'] ?? false) && $isBest ? 'color:var(--hz-safe)' : '' ?>"><?= $display ?></td>
          <?php endforeach; ?>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php else: ?>
<div class="empty-state"><i class="fa-solid fa-scale-balanced"></i><div>Select at least 2 sites above to compare.</div></div>
<?php endif; ?>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
