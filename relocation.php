<?php
require_once __DIR__ . '/includes/auth.php';
requireLogin();
require_once __DIR__ . '/includes/risk-engine.php';
$conn = getDbConnection();
$pageTitle = 'Relocation Planner';

$sites = $conn->query('SELECT * FROM relocation_sites ORDER BY suitability_score DESC')->fetch_all(MYSQLI_ASSOC);
$habitations = $conn->query("SELECT * FROM habitations WHERE priority IN ('IMMEDIATE','SHORT-TERM') ORDER BY priority_score DESC")->fetch_all(MYSQLI_ASSOC);
$rankedAll = $conn->query('SELECT * FROM habitations ORDER BY priority_score DESC')->fetch_all(MYSQLI_ASSOC);

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';
?>
<h3 class="text-white mb-1"><i class="fa-solid fa-people-roof me-2"></i>Relocation Planner</h3>
<p class="text-dim small mb-3">Suitability, carrying capacity, and smart site matching for at-risk habitations.</p>

<!-- RELOCATION SITES -->
<div class="hz-panel mb-4">
  <div class="hz-panel-header"><h5>Relocation Sites — Suitability &amp; Capacity</h5><a href="site-comparison.php" class="btn btn-hz-outline btn-sm">Compare Sites <i class="fa-solid fa-scale-balanced ms-1"></i></a></div>
  <div class="table-responsive">
    <table class="hz-table">
      <thead><tr><th>Site</th><th>District</th><th>Suitability</th><th>Max Capacity</th><th>Occupancy</th><th>Available</th><th>Hazard Risk</th><th>Recommendation</th></tr></thead>
      <tbody>
      <?php foreach ($sites as $s):
        $suit = computeSiteSuitability($s);
        $status = occupancyStatus($suit['occupancy_pct']);
        $barClass = $status === 'FULL' ? 'capacity-full' : ($status === 'LIMITED' ? 'capacity-limited' : 'capacity-good');
      ?>
        <tr>
          <td class="text-white fw-semibold"><?= sanitize($s['name']) ?></td>
          <td><?= sanitize($s['district']) ?></td>
          <td><span class="fw-bold" style="color:<?= $s['suitability_score']>=85?'#22c55e':($s['suitability_score']>=70?'#3b82f6':'#eab308') ?>"><?= $s['suitability_score'] ?>%</span></td>
          <td><?= number_format($s['max_capacity']) ?></td>
          <td style="min-width:140px">
            <div class="capacity-track mb-1"><div class="capacity-fill <?= $barClass ?>" style="width:<?= $suit['occupancy_pct'] ?>%"></div></div>
            <span class="text-faint small"><?= $suit['occupancy_pct'] ?>% — <?= $status ?></span>
          </td>
          <td><?= number_format($suit['available_capacity']) ?></td>
          <td><?= $s['hazard_risk'] ?>/100</td>
          <td><span class="risk-badge <?= $suit['recommendation']==='HIGHLY SUITABLE'?'risk-safe':($suit['recommendation']==='SUITABLE'?'risk-low':($suit['recommendation']==='MODERATELY SUITABLE'?'risk-moderate':'risk-critical')) ?>"><?= $suit['recommendation'] ?></span></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- SMART RECOMMENDATIONS -->
<div class="hz-panel mb-4">
  <div class="hz-panel-header"><h5><i class="fa-solid fa-wand-magic-sparkles me-2" style="color:var(--hz-accent-2)"></i>Smart Relocation Recommendations</h5></div>
  <div class="hz-panel-body">
    <div class="row g-3">
    <?php foreach ($habitations as $h):
      $best = recommendBestSite($h, $sites);
      if (!$best) continue;
    ?>
      <div class="col-lg-6">
        <div class="hz-panel p-3" style="background:rgba(255,255,255,0.02)">
          <div class="d-flex justify-content-between align-items-start mb-2">
            <div>
              <div class="text-faint small">SOURCE</div>
              <div class="text-white fw-semibold"><?= sanitize($h['name']) ?> <span class="text-faint small">(<?= sanitize($h['district']) ?>)</span></div>
            </div>
            <span class="risk-badge risk-<?= strtolower($h['risk_level']) ?>"><?= $h['risk_score'] ?>/100</span>
          </div>
          <div class="row text-center g-2 mb-2">
            <div class="col-4"><div class="fw-bold text-white"><?= number_format($h['population']) ?></div><div class="text-faint" style="font-size:0.68rem">POPULATION</div></div>
            <div class="col-4"><div class="fw-bold text-white"><?= $best['name'] ?></div><div class="text-faint" style="font-size:0.68rem">RECOMMENDED SITE</div></div>
            <div class="col-4"><div class="fw-bold" style="color:var(--hz-safe)"><?= $best['suitability_score'] ?>%</div><div class="text-faint" style="font-size:0.68rem">SUITABILITY</div></div>
          </div>
          <div class="text-dim small mb-1">Available Capacity: <strong class="text-white"><?= number_format($best['available_capacity']) ?></strong></div>
          <div class="text-dim small fst-italic">"<?= sanitize($best['reason']) ?>"</div>
        </div>
      </div>
    <?php endforeach; ?>
    <?php if (empty($habitations)): ?><div class="empty-state"><i class="fa-solid fa-circle-check"></i><div>No habitations currently need urgent relocation matching.</div></div><?php endif; ?>
    </div>
  </div>
</div>

<!-- RELOCATION PRIORITY RANKING -->
<div class="hz-panel">
  <div class="hz-panel-header"><h5>Relocation Priority Ranking</h5></div>
  <div class="table-responsive">
    <table class="hz-table">
      <thead><tr><th>Rank</th><th>Habitation</th><th>Risk</th><th>Population</th><th>Vulnerability</th><th>Priority Score</th><th>Priority</th><th>Recommended Site</th></tr></thead>
      <tbody>
      <?php foreach ($rankedAll as $i => $h):
        $best = recommendBestSite($h, $sites);
        $priClass = 'priority-' . strtolower(str_replace(' ', '-', $h['priority']));
        $vulnPct = $h['population'] > 0 ? round(($h['vulnerable_population']/$h['population'])*100) : 0;
      ?>
        <tr>
          <td class="mono text-faint">#<?= $i+1 ?></td>
          <td class="text-white fw-semibold"><?= sanitize($h['name']) ?></td>
          <td><?= $h['risk_score'] ?></td>
          <td><?= number_format($h['population']) ?></td>
          <td><?= $vulnPct ?>%</td>
          <td class="mono"><?= $h['priority_score'] ?></td>
          <td><span class="risk-badge <?= $priClass ?>"><?= $h['priority'] ?></span></td>
          <td><?= $best ? sanitize($best['name']) : '—' ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
