<?php require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/config/database.php';
$conn = getDbConnection();

$totalHab = $conn->query('SELECT COUNT(*) c FROM habitations')->fetch_assoc()['c'] ?? 0;
$criticalZones = $conn->query("SELECT COUNT(*) c FROM habitations WHERE risk_level='CRITICAL'")->fetch_assoc()['c'] ?? 0;
$popAtRisk = $conn->query("SELECT SUM(population) s FROM habitations WHERE risk_level IN ('CRITICAL','HIGH')")->fetch_assoc()['s'] ?? 0;
$relocCapacity = $conn->query('SELECT SUM(max_capacity - current_population) s FROM relocation_sites')->fetch_assoc()['s'] ?? 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>RESQZONE — Hazard Intelligence & Safe-Zone Analytics</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="bg-hz">
<div class="demo-banner"><i class="fa-solid fa-flask"></i> DEMO / PROTOTYPE PLATFORM — figures below are illustrative sample data, not real disaster statistics.</div>

<nav class="navbar navbar-expand-lg public-nav">
  <div class="container">
    <a class="navbar-brand app-brand" href="index.php"><i class="fa-solid fa-tower-broadcast"></i> RESQZONE</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navMain" style="border-color:rgba(255,255,255,0.2)">
      <i class="fa-solid fa-bars text-white"></i>
    </button>
    <div class="collapse navbar-collapse" id="navMain">
      <ul class="navbar-nav ms-auto align-items-lg-center gap-lg-4">
        <li class="nav-item"><a class="nav-link text-dim" href="#problem">The Problem</a></li>
        <li class="nav-item"><a class="nav-link text-dim" href="#how">How It Works</a></li>
        <li class="nav-item"><a class="nav-link text-dim" href="#capabilities">Capabilities</a></li>
        <li class="nav-item"><a class="nav-link text-dim" href="#impact">Impact</a></li>
        <li class="nav-item mt-2 mt-lg-0"><a class="btn btn-hz-outline btn-sm px-3" href="login.php">Authority Login</a></li>
      </ul>
    </div>
  </div>
</nav>

<!-- HERO -->
<header class="landing-hero console-grid">
  <div class="container position-relative">
    <div class="row align-items-center g-5">
      <div class="col-lg-7">
        <div class="eyebrow">// GIS DECISION SUPPORT SYSTEM — v1.0 PROTOTYPE</div>
        <h1>See Risk.<br>Plan Safety.<br><span style="color:var(--hz-accent)">Save Lives.</span></h1>
        <p class="lead">RESQZONE is an AI-inspired GIS decision-support platform that identifies hazard-prone Red Zones, assesses vulnerable habitations, evaluates safer relocation sites, and prioritizes communities for Immediate, Short-Term and Medium-Term relocation.</p>
        <div class="d-flex flex-wrap gap-3 mt-4">
          <a href="risk-map-preview.php" class="btn btn-hz-primary btn-lg px-4"><i class="fa-solid fa-map-location-dot me-2"></i>Explore Risk Map</a>
          <a href="login.php" class="btn btn-hz-outline btn-lg px-4"><i class="fa-solid fa-shield-halved me-2"></i>Authority Login</a>
        </div>
      </div>
      <div class="col-lg-5">
        <div class="hz-panel p-3">
          <div class="hz-panel-header"><h5><i class="fa-solid fa-satellite-dish me-2" style="color:var(--hz-accent-2)"></i>Live Risk Snapshot</h5><span class="chip mono">DEMO</span></div>
          <div class="hz-panel-body">
            <div class="d-flex justify-content-between align-items-center mb-2">
              <span class="text-dim small">Village Shivapur — Raigad</span>
              <span class="risk-badge risk-critical">Critical</span>
            </div>
            <div class="mono small text-dim mb-3">RISK SCORE <span class="text-white fw-bold fs-5 ms-1">91.4</span>/100</div>
            <div class="capacity-track mb-1"><div class="capacity-fill capacity-full" style="width:91%"></div></div>
            <div class="d-flex justify-content-between small text-faint mono"><span>Primary Hazard: Flood</span><span>Priority: IMMEDIATE</span></div>
            <hr class="border-secondary opacity-25">
            <div class="row text-center g-2">
              <div class="col-4"><div class="fw-bold text-white">4,820</div><div class="text-faint" style="font-size:0.68rem">POPULATION</div></div>
              <div class="col-4"><div class="fw-bold text-white">1,420</div><div class="text-faint" style="font-size:0.68rem">VULNERABLE</div></div>
              <div class="col-4"><div class="fw-bold text-white">6</div><div class="text-faint" style="font-size:0.68rem">EVENTS</div></div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</header>

<!-- STATS STRIP -->
<div class="hero-stat-strip">
  <div class="container">
    <div class="row g-0">
      <div class="col-6 col-md-3 hero-stat"><div class="num"><?= (int)$totalHab ?></div><div class="lbl">Habitations Assessed</div></div>
      <div class="col-6 col-md-3 hero-stat"><div class="num" style="color:var(--hz-critical)"><?= (int)$criticalZones ?></div><div class="lbl">Critical Zones</div></div>
      <div class="col-6 col-md-3 hero-stat"><div class="num"><?= number_format((int)$popAtRisk) ?></div><div class="lbl">Population at Risk</div></div>
      <div class="col-6 col-md-3 hero-stat"><div class="num" style="color:var(--hz-safe)"><?= number_format((int)$relocCapacity) ?></div><div class="lbl">Relocation Capacity</div></div>
    </div>
  </div>
</div>

<!-- PROBLEM -->
<section id="problem" class="landing-section">
  <div class="container">
    <div class="row g-5 align-items-center">
      <div class="col-lg-6">
        <div class="section-eyebrow">01 — The Problem</div>
        <h2 class="section-title">Disaster response is still reactive.</h2>
        <p class="text-dim mt-3">Most disaster-prone habitations are identified only after a flood, landslide, or cyclone strikes. Relocation decisions are made under pressure, without a clear picture of which safe zones can actually absorb displaced populations — leading to overcrowded shelters and repeated exposure to the same hazards.</p>
        <ul class="text-dim mt-3">
          <li class="mb-2">No unified hazard + habitation risk register</li>
          <li class="mb-2">Relocation planning done manually, site-by-site</li>
          <li class="mb-2">Carrying capacity of safe zones rarely modeled in advance</li>
        </ul>
      </div>
      <div class="col-lg-6">
        <div class="hz-panel p-4">
          <div class="d-flex justify-content-between text-dim small mb-3"><span>RESPONSE TIMELINE — TRADITIONAL</span><span class="mono">T+0 to T+72h</span></div>
          <div class="d-flex align-items-center gap-3 mb-3"><span class="risk-badge risk-critical">Event</span><div class="flex-grow-1 border-top border-secondary opacity-50"></div><span class="text-faint small">Disaster strikes</span></div>
          <div class="d-flex align-items-center gap-3 mb-3"><span class="risk-badge risk-high">+18h</span><div class="flex-grow-1 border-top border-secondary opacity-50"></div><span class="text-faint small">Manual assessment begins</span></div>
          <div class="d-flex align-items-center gap-3"><span class="risk-badge risk-moderate">+48h</span><div class="flex-grow-1 border-top border-secondary opacity-50"></div><span class="text-faint small">Relocation site identified — often unsuitable</span></div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- HOW IT WORKS -->
<section id="how" class="landing-section alt">
  <div class="container">
    <div class="section-eyebrow text-center">02 — How RESQZONE Works</div>
    <h2 class="section-title text-center mb-5">From raw hazard data to a relocation decision.</h2>
    <div class="row g-4">
      <?php $steps = [
        ['fa-map-pin','Map Habitations','Every habitation is geo-tagged with population, vulnerability and hazard exposure data.'],
        ['fa-calculator','Score the Risk','A transparent, weighted formula converts hazard, exposure, vulnerability and history into a 0–100 risk score.'],
        ['fa-diagram-project','Rank Priority','Habitations are automatically ranked IMMEDIATE / SHORT-TERM / MEDIUM-TERM / MONITOR.'],
        ['fa-route','Match a Safe Zone','Relocation sites are scored on hazard safety, capacity and infrastructure, then matched to at-risk habitations.'],
      ]; foreach ($steps as $i => $s): ?>
      <div class="col-md-6 col-lg-3">
        <div class="feature-card">
          <div class="step-num">STEP 0<?= $i+1 ?></div>
          <div class="fi mt-2" style="background:rgba(255,90,78,0.12);color:var(--hz-accent)"><i class="fa-solid <?= $s[0] ?>"></i></div>
          <h5 class="text-white"><?= $s[1] ?></h5>
          <p class="text-dim small mb-0"><?= $s[2] ?></p>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- CAPABILITIES -->
<section id="capabilities" class="landing-section">
  <div class="container">
    <div class="section-eyebrow text-center">03 — Key Capabilities</div>
    <h2 class="section-title text-center mb-5">Multi-Hazard Intelligence &amp; Smart Relocation</h2>
    <div class="row g-4">
      <?php $caps = [
        ['fa-water','var(--hz-info)','Multi-Hazard Intelligence','Flood, landslide, cloudburst and coastal erosion layers combined into one live GIS risk map.'],
        ['fa-house-flag','var(--hz-critical)','Red Zone Identification','Automatic classification of habitations into SAFE / LOW / MODERATE / HIGH / CRITICAL.'],
        ['fa-people-roof','var(--hz-safe)','Smart Relocation','Relocation sites ranked by suitability score, with live carrying-capacity tracking.'],
        ['fa-brain','var(--hz-accent-2)','Explainable Risk Analysis','Every score shows its contributing factors and a plain-language recommendation — no black box.'],
        ['fa-file-lines','var(--hz-moderate)','One-Click Reporting','Generate and export Risk, Red Zone, Relocation Priority and District Summary reports instantly.'],
        ['fa-triangle-exclamation','var(--hz-high)','Live Alerting','Automatic CRITICAL / WARNING / INFO alerts when risk scores spike or sites approach capacity.'],
      ]; foreach ($caps as $c): ?>
      <div class="col-md-6 col-lg-4">
        <div class="feature-card">
          <div class="fi" style="background:color-mix(in srgb, <?= $c[1] ?> 15%, transparent);color:<?= $c[1] ?>"><i class="fa-solid <?= $c[0] ?>"></i></div>
          <h5 class="text-white"><?= $c[2] ?></h5>
          <p class="text-dim small mb-0"><?= $c[3] ?></p>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- IMPACT -->
<section id="impact" class="landing-section alt">
  <div class="container">
    <div class="row align-items-center g-5">
      <div class="col-lg-6">
        <div class="section-eyebrow">04 — Impact</div>
        <h2 class="section-title">Built for command-center decision making.</h2>
        <p class="text-dim mt-3">RESQZONE turns scattered hazard records into a single, prioritized action list — so disaster management authorities can move from data to decision in minutes, not days.</p>
        <a href="login.php" class="btn btn-hz-primary btn-lg mt-3 px-4">Sign in to the Authority Dashboard <i class="fa-solid fa-arrow-right ms-2"></i></a>
      </div>
      <div class="col-lg-6">
        <div class="row g-3">
          <div class="col-6"><div class="hz-panel p-3 text-center"><div class="kpi-value" style="color:var(--hz-critical)"><?= (int)$criticalZones ?></div><div class="kpi-label">Critical Zones Flagged</div></div></div>
          <div class="col-6"><div class="hz-panel p-3 text-center"><div class="kpi-value"><?= (int)$totalHab ?></div><div class="kpi-label">Habitations Modeled</div></div></div>
          <div class="col-6"><div class="hz-panel p-3 text-center"><div class="kpi-value" style="color:var(--hz-safe)">10</div><div class="kpi-label">Relocation Sites Ready</div></div></div>
          <div class="col-6"><div class="hz-panel p-3 text-center"><div class="kpi-value"><?= number_format((int)$relocCapacity) ?></div><div class="kpi-label">Safe Capacity Available</div></div></div>
        </div>
      </div>
    </div>
  </div>
</section>

<footer class="app-footer">
  <div class="container py-4 d-flex flex-wrap justify-content-between small">
    <span>&copy; <?= date('Y') ?> RESQZONE. College / Hackathon Prototype — DEMO DATA ONLY.</span>
    <span><a href="login.php" class="text-dim">Authority Login</a></span>
  </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
