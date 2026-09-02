<?php
require_once __DIR__ . '/includes/auth.php';
requireLogin();
require_once __DIR__ . '/includes/risk-engine.php';
$conn = getDbConnection();
$pageTitle = 'Add Habitation';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf()) {
        $errors[] = 'Session expired, please resubmit.';
    } else {
        $name = sanitize($_POST['name'] ?? '');
        $district = sanitize($_POST['district'] ?? '');
        $lat = (float)($_POST['latitude'] ?? 0);
        $lng = (float)($_POST['longitude'] ?? 0);
        $population = (int)($_POST['population'] ?? 0);
        $vulnerable = (int)($_POST['vulnerable_population'] ?? 0);
        $flood = (int)($_POST['flood_risk'] ?? 0);
        $landslide = (int)($_POST['landslide_risk'] ?? 0);
        $cloudburst = (int)($_POST['cloudburst_risk'] ?? 0);
        $coastal = (int)($_POST['coastal_erosion_risk'] ?? 0);
        $events = (int)($_POST['historical_events'] ?? 0);
        $infra = (int)($_POST['infrastructure_risk'] ?? 0);

        if ($name === '' || $district === '') $errors[] = 'Name and district are required.';
        if ($vulnerable > $population) $errors[] = 'Vulnerable population cannot exceed total population.';

        if (empty($errors)) {
            $hazards = ['Flood'=>$flood,'Landslide'=>$landslide,'Cloudburst'=>$cloudburst,'Coastal Erosion'=>$coastal];
            arsort($hazards);
            $primaryHazard = array_key_first($hazards);

            $stmt = $conn->prepare('INSERT INTO habitations (name, district, latitude, longitude, population, vulnerable_population, flood_risk, landslide_risk, cloudburst_risk, coastal_erosion_risk, historical_events, infrastructure_risk, primary_hazard) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)');
            $stmt->bind_param('ssddiiiiiiiis', $name, $district, $lat, $lng, $population, $vulnerable, $flood, $landslide, $cloudburst, $coastal, $events, $infra, $primaryHazard);
            $stmt->execute();
            $newId = $stmt->insert_id;
            $stmt->close();

            $row = $conn->query("SELECT * FROM habitations WHERE id=$newId")->fetch_assoc();
            $r = computeHabitationRisk($row);
            $upd = $conn->prepare('UPDATE habitations SET risk_score=?, risk_level=?, priority_score=?, priority=? WHERE id=?');
            $upd->bind_param('dsdsi', $r['risk_score'], $r['risk_level'], $r['priority_score'], $r['priority'], $newId);
            $upd->execute();

            logAction('add_habitation', "Added habitation #$newId ($name)");
            header('Location: habitations.php?added=1');
            exit;
        }
    }
}

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';
?>
<h3 class="text-white mb-3"><i class="fa-solid fa-plus me-2"></i>Add Habitation</h3>
<?php foreach ($errors as $e): ?><div class="alert alert-danger py-2 small"><?= sanitize($e) ?></div><?php endforeach; ?>

<form method="POST" class="hz-panel p-4">
  <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
  <div class="row g-3">
    <div class="col-md-6"><label class="hz-label">Habitation Name *</label><input name="name" required class="form-control hz-form-control"></div>
    <div class="col-md-6"><label class="hz-label">District *</label><input name="district" required class="form-control hz-form-control"></div>
    <div class="col-md-6"><label class="hz-label">Latitude *</label><input name="latitude" type="number" step="0.000001" required class="form-control hz-form-control"></div>
    <div class="col-md-6"><label class="hz-label">Longitude *</label><input name="longitude" type="number" step="0.000001" required class="form-control hz-form-control"></div>
    <div class="col-md-6"><label class="hz-label">Population *</label><input name="population" type="number" min="0" required class="form-control hz-form-control"></div>
    <div class="col-md-6"><label class="hz-label">Vulnerable Population *</label><input name="vulnerable_population" type="number" min="0" required class="form-control hz-form-control"></div>

    <div class="col-12"><hr class="border-secondary opacity-25"><div class="text-dim small mb-1">Hazard Risk Scores (0–100)</div></div>
    <div class="col-md-3"><label class="hz-label">Flood Risk</label><input name="flood_risk" type="number" min="0" max="100" value="0" class="form-control hz-form-control"></div>
    <div class="col-md-3"><label class="hz-label">Landslide Risk</label><input name="landslide_risk" type="number" min="0" max="100" value="0" class="form-control hz-form-control"></div>
    <div class="col-md-3"><label class="hz-label">Cloudburst Risk</label><input name="cloudburst_risk" type="number" min="0" max="100" value="0" class="form-control hz-form-control"></div>
    <div class="col-md-3"><label class="hz-label">Coastal Erosion Risk</label><input name="coastal_erosion_risk" type="number" min="0" max="100" value="0" class="form-control hz-form-control"></div>

    <div class="col-md-6"><label class="hz-label">Historical Disaster Events</label><input name="historical_events" type="number" min="0" value="0" class="form-control hz-form-control"></div>
    <div class="col-md-6"><label class="hz-label">Infrastructure Risk (0–100, higher = weaker)</label><input name="infrastructure_risk" type="number" min="0" max="100" value="0" class="form-control hz-form-control"></div>
  </div>
  <div class="d-flex gap-2 mt-4">
    <button class="btn btn-hz-primary px-4"><i class="fa-solid fa-floppy-disk me-1"></i>Save Habitation</button>
    <a href="habitations.php" class="btn btn-hz-outline px-4">Cancel</a>
  </div>
</form>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
