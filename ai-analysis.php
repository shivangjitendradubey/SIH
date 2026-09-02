<?php
require_once __DIR__ . '/includes/auth.php';
requireLogin();
require_once __DIR__ . '/includes/risk-engine.php';
$conn = getDbConnection();
$pageTitle = 'Risk Analysis';

$habitations = $conn->query('SELECT id, name, district, risk_score FROM habitations ORDER BY risk_score DESC')->fetch_all(MYSQLI_ASSOC);
$selectedId = (int)($_GET['id'] ?? ($habitations[0]['id'] ?? 0));

$stmt = $conn->prepare('SELECT * FROM habitations WHERE id=?');
$stmt->bind_param('i', $selectedId);
$stmt->execute();
$hab = $stmt->get_result()->fetch_assoc();
$stmt->close();

$analysis = $hab ? computeHabitationRisk($hab) : null;

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';
?>
<h3 class="text-white mb-1"><i class="fa-solid fa-brain me-2"></i>Explainable Risk Analysis</h3>
<p class="text-dim small mb-3">A transparent, formula-driven decision engine — every score below shows exactly why it was produced. This is not a trained AI model; it is a rules-based analytical prototype.</p>

<div class="row g-3">
  <div class="col-lg-4">
    <div class="hz-panel p-3">
      <label class="hz-label">Select Habitation</label>
      <select class="form-select hz-form-control" onchange="window.location='ai-analysis.php?id='+this.value">
        <?php foreach ($habitations as $h): ?>
          <option value="<?= $h['id'] ?>" <?= $h['id'] == $selectedId ? 'selected' : '' ?>><?= sanitize($h['name']) ?> (<?= sanitize($h['district']) ?>) — <?= $h['risk_score'] ?></option>
        <?php endforeach; ?>
      </select>
    </div>
  </div>
</div>

<?php if ($hab && $analysis): ?>
<div class="row g-3 mt-1">
  <div class="col-lg-5">
    <div class="hz-panel p-4 text-center">
      <div class="text-dim small mb-1">RISK SCORE</div>
      <div class="kpi-value" style="font-size:3rem;color:<?= riskLevelColor($analysis['risk_level']) ?>"><?= $analysis['risk_score'] ?><span class="fs-6 text-dim">/100</span></div>
      <span class="risk-badge risk-<?= strtolower($analysis['risk_level']) ?> mt-2 d-inline-block">STATUS: <?= $analysis['risk_level'] ?></span>
      <hr class="border-secondary opacity-25">
      <div class="text-start">
        <div class="d-flex justify-content-between small mb-1"><span class="text-dim">Hazard Risk (40%)</span><span class="mono"><?= $analysis['hazard_score'] ?></span></div>
        <div class="capacity-track mb-2"><div class="capacity-fill capacity-full" style="width:<?= $analysis['hazard_score'] ?>%"></div></div>
        <div class="d-flex justify-content-between small mb-1"><span class="text-dim">Population Vulnerability (25%)</span><span class="mono"><?= $analysis['vulnerability_score'] ?></span></div>
        <div class="capacity-track mb-2"><div class="capacity-fill capacity-limited" style="width:<?= $analysis['vulnerability_score'] ?>%"></div></div>
        <div class="d-flex justify-content-between small mb-1"><span class="text-dim">Exposure (20%)</span><span class="mono"><?= $analysis['exposure_score'] ?></span></div>
        <div class="capacity-track mb-2"><div class="capacity-fill capacity-limited" style="width:<?= $analysis['exposure_score'] ?>%"></div></div>
        <div class="d-flex justify-content-between small mb-1"><span class="text-dim">Historical Impact (15%)</span><span class="mono"><?= $analysis['historical_score'] ?></span></div>
        <div class="capacity-track"><div class="capacity-fill capacity-good" style="width:<?= $analysis['historical_score'] ?>%"></div></div>
      </div>
    </div>
  </div>

  <div class="col-lg-7">
    <div class="hz-panel p-4 mb-3">
      <h6 class="text-white mb-2"><i class="fa-solid fa-circle-exclamation me-2" style="color:var(--hz-accent)"></i>Major Contributing Factors</h6>
      <ul class="text-dim mb-0">
        <?php
        $factors = [];
        if ($hab['flood_risk'] >= 60) $factors[] = 'High flood exposure (' . $hab['flood_risk'] . '/100)';
        if ($hab['landslide_risk'] >= 60) $factors[] = 'High landslide susceptibility (' . $hab['landslide_risk'] . '/100)';
        if ($hab['cloudburst_risk'] >= 60) $factors[] = 'High cloudburst risk (' . $hab['cloudburst_risk'] . '/100)';
        if ($hab['coastal_erosion_risk'] >= 60) $factors[] = 'Significant coastal erosion (' . $hab['coastal_erosion_risk'] . '/100)';
        if ($analysis['vulnerability_score'] >= 40) $factors[] = 'High population vulnerability (' . round($analysis['vulnerability_score']) . '% of residents)';
        if ($hab['historical_events'] >= 5) $factors[] = 'Repeated historical disasters (' . $hab['historical_events'] . ' recorded events)';
        if ($hab['infrastructure_risk'] >= 55) $factors[] = 'Limited evacuation / infrastructure readiness';
        if (empty($factors)) $factors[] = 'No dominant risk driver — overall exposure is currently balanced across factors.';
        foreach ($factors as $f): ?>
          <li><?= sanitize($f) ?></li>
        <?php endforeach; ?>
      </ul>
    </div>
    <div class="hz-panel p-4">
      <h6 class="text-white mb-2"><i class="fa-solid fa-clipboard-check me-2" style="color:var(--hz-safe)"></i>Recommended Action</h6>
      <p class="mb-0 text-white">
        <?php
        echo match ($analysis['risk_level']) {
            'CRITICAL' => 'Immediate relocation assessment recommended. Coordinate with the relocation planner to identify and confirm a suitable safe zone within 30 days.',
            'HIGH' => 'Schedule a short-term relocation feasibility study and reinforce local hazard mitigation infrastructure.',
            'MODERATE' => 'Continue monitoring with quarterly reassessment; consider infrastructure upgrades to reduce exposure.',
            'LOW' => 'Routine monitoring is sufficient. Reassess if new hazard events are recorded.',
            default => 'No immediate action required. Maintain standard monitoring cadence.',
        };
        ?>
      </p>
    </div>
  </div>
</div>

<!-- WHAT-IF ANALYSIS -->
<div class="hz-panel p-4 mt-3">
  <h5 class="text-white mb-3"><i class="fa-solid fa-flask-vial me-2" style="color:var(--hz-accent-2)"></i>What-If Scenario Analysis</h5>
  <p class="text-dim small">Adjust the sliders to simulate a change in conditions and see the resulting risk shift — instantly, client-side, using the same formula as the live engine.</p>
  <div class="row g-4 align-items-center">
    <div class="col-md-4">
      <label class="hz-label d-flex justify-content-between">Flood Severity <span id="floodVal" class="mono"><?= $hab['flood_risk'] ?></span></label>
      <input type="range" min="0" max="100" value="<?= $hab['flood_risk'] ?>" class="form-range" id="scFlood">
    </div>
    <div class="col-md-4">
      <label class="hz-label d-flex justify-content-between">Population Exposure <span id="popVal" class="mono"><?= $hab['population'] ?></span></label>
      <input type="range" min="0" max="10000" step="100" value="<?= $hab['population'] ?>" class="form-range" id="scPop">
    </div>
    <div class="col-md-4">
      <label class="hz-label d-flex justify-content-between">Infrastructure Condition (risk) <span id="infraVal" class="mono"><?= $hab['infrastructure_risk'] ?></span></label>
      <input type="range" min="0" max="100" value="<?= $hab['infrastructure_risk'] ?>" class="form-range" id="scInfra">
    </div>
  </div>
  <button class="btn btn-hz-primary mt-3" id="runScenarioBtn"><i class="fa-solid fa-play me-1"></i>Run Scenario</button>

  <div id="scenarioResult" class="row g-3 mt-3" style="display:none">
    <div class="col-md-3"><div class="hz-panel p-3 text-center"><div class="text-dim small">Current Risk</div><div class="kpi-value" id="curRiskOut"></div></div></div>
    <div class="col-md-3"><div class="hz-panel p-3 text-center"><div class="text-dim small">Scenario Risk</div><div class="kpi-value" id="newRiskOut"></div></div></div>
    <div class="col-md-3"><div class="hz-panel p-3 text-center"><div class="text-dim small">Change</div><div class="kpi-value" id="changeOut"></div></div></div>
    <div class="col-md-3"><div class="hz-panel p-3 text-center"><div class="text-dim small">New Status</div><div class="kpi-value" id="statusOut"></div></div></div>
  </div>
</div>

<script>
const base = {
  flood: <?= (int)$hab['flood_risk'] ?>, landslide: <?= (int)$hab['landslide_risk'] ?>,
  cloudburst: <?= (int)$hab['cloudburst_risk'] ?>, coastal: <?= (int)$hab['coastal_erosion_risk'] ?>,
  population: <?= (int)$hab['population'] ?>, vulnerable: <?= (int)$hab['vulnerable_population'] ?>,
  infra: <?= (int)$hab['infrastructure_risk'] ?>, events: <?= (int)$hab['historical_events'] ?>,
  currentRisk: <?= (float)$analysis['risk_score'] ?>
};
function classify(score){ if(score>=86) return 'CRITICAL'; if(score>=71) return 'HIGH'; if(score>=51) return 'MODERATE'; if(score>=31) return 'LOW'; return 'SAFE'; }
function colorFor(level){ return {CRITICAL:'#e11d2e',HIGH:'#f97316',MODERATE:'#eab308',LOW:'#3b82f6',SAFE:'#22c55e'}[level]; }

function computeScenario(flood, population, infra){
  const hazards = [flood, base.landslide, base.cloudburst, base.coastal];
  const maxH = Math.max(...hazards); const avgH = hazards.reduce((a,b)=>a+b,0)/hazards.length;
  const hazardScore = maxH*0.7 + avgH*0.3;
  const vulnShare = Math.min(100, (base.vulnerable / Math.max(1,population)) * 100);
  const popExposure = Math.min(100, (population/6000)*100);
  const exposureScore = popExposure*0.5 + infra*0.5;
  const historicalScore = Math.min(100, (base.events/10)*100);
  const risk = hazardScore*0.40 + vulnShare*0.25 + exposureScore*0.20 + historicalScore*0.15;
  return Math.max(0, Math.min(100, Math.round(risk*10)/10));
}

['scFlood','scPop','scInfra'].forEach(id=>{
  document.getElementById(id).addEventListener('input', e=>{
    document.getElementById({scFlood:'floodVal',scPop:'popVal',scInfra:'infraVal'}[id]).textContent = e.target.value;
  });
});

document.getElementById('runScenarioBtn').addEventListener('click', ()=>{
  const flood = parseInt(document.getElementById('scFlood').value);
  const pop = parseInt(document.getElementById('scPop').value);
  const infra = parseInt(document.getElementById('scInfra').value);
  const newRisk = computeScenario(flood, pop, infra);
  const change = Math.round((newRisk - base.currentRisk)*10)/10;
  const status = classify(newRisk);

  document.getElementById('curRiskOut').textContent = base.currentRisk.toFixed(1);
  document.getElementById('newRiskOut').textContent = newRisk.toFixed(1);
  document.getElementById('newRiskOut').style.color = colorFor(status);
  const changeEl = document.getElementById('changeOut');
  changeEl.textContent = (change>=0?'+':'') + change.toFixed(1);
  changeEl.style.color = change>0 ? '#e11d2e' : (change<0 ? '#22c55e' : '#93a0bb');
  document.getElementById('statusOut').textContent = status;
  document.getElementById('statusOut').style.color = colorFor(status);
  document.getElementById('scenarioResult').style.display = 'flex';
  HZ.toast('Scenario recalculated', 'info');
});
</script>
<?php else: ?>
<div class="empty-state"><i class="fa-solid fa-inbox"></i><div>No habitation selected.</div></div>
<?php endif; ?>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
