<?php
require_once __DIR__ . '/../includes/auth.php';
requireAdmin();
require_once __DIR__ . '/../includes/risk-engine.php';
$conn = getDbConnection();
$pageTitle = 'Data Management';
$base = '../';
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCsrf()) {
    $action = $_POST['action'] ?? '';
    if ($action === 'add_site') {
        $stmt = $conn->prepare('INSERT INTO relocation_sites (name,district,latitude,longitude,available_land_acres,current_population,max_capacity,water_availability,electricity,healthcare,schools,road_connectivity,hazard_risk,distance_from_red_zone_km) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)');
        $name = sanitize($_POST['name']); $district = sanitize($_POST['district']);
        $lat=(float)$_POST['latitude']; $lng=(float)$_POST['longitude']; $land=(float)$_POST['land'];
        $curPop=(int)$_POST['current_population']; $maxCap=(int)$_POST['max_capacity'];
        $water=(int)$_POST['water']; $elec=(int)$_POST['electricity']; $health=(int)$_POST['healthcare'];
        $schools=(int)$_POST['schools']; $road=(int)$_POST['road']; $hazard=(int)$_POST['hazard_risk']; $dist=(float)$_POST['distance'];
        $stmt->bind_param('ssdddiiiiiiiid', $name,$district,$lat,$lng,$land,$curPop,$maxCap,$water,$elec,$health,$schools,$road,$hazard,$dist);
        $stmt->execute();
        $id = $stmt->insert_id;
        $row = $conn->query("SELECT * FROM relocation_sites WHERE id=$id")->fetch_assoc();
        $s = computeSiteSuitability($row);
        $upd = $conn->prepare('UPDATE relocation_sites SET suitability_score=? WHERE id=?');
        $upd->bind_param('di', $s['suitability_score'], $id); $upd->execute();
        $message = 'Relocation site added.';
        logAction('add_site', "Added relocation site #$id ($name)");
    } elseif ($action === 'delete_site') {
        $id = (int)$_POST['id'];
        $stmt = $conn->prepare('DELETE FROM relocation_sites WHERE id=?'); $stmt->bind_param('i', $id); $stmt->execute();
        $message = 'Relocation site deleted.'; logAction('delete_site', "Deleted site #$id");
    } elseif ($action === 'delete_habitation') {
        $id = (int)$_POST['id'];
        $stmt = $conn->prepare('DELETE FROM habitations WHERE id=?'); $stmt->bind_param('i', $id); $stmt->execute();
        $message = 'Habitation deleted.'; logAction('delete_habitation', "Deleted habitation #$id");
    } elseif ($action === 'add_alert') {
        $type = in_array($_POST['type'], ['CRITICAL','WARNING','INFO']) ? $_POST['type'] : 'INFO';
        $title = sanitize($_POST['title']); $msg = sanitize($_POST['message']);
        $stmt = $conn->prepare('INSERT INTO alerts (type,title,message) VALUES (?,?,?)');
        $stmt->bind_param('sss', $type, $title, $msg); $stmt->execute();
        $message = 'Alert created.'; logAction('add_alert', "Created alert: $title");
    }
}

$habitations = $conn->query('SELECT id, name, district FROM habitations ORDER BY name')->fetch_all(MYSQLI_ASSOC);
$sites = $conn->query('SELECT * FROM relocation_sites ORDER BY name')->fetch_all(MYSQLI_ASSOC);
$logs = $conn->query('SELECT l.*, u.name as user_name FROM system_logs l LEFT JOIN users u ON l.user_id=u.id ORDER BY l.created_at DESC LIMIT 40')->fetch_all(MYSQLI_ASSOC);

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>
<h3 class="text-white mb-1"><i class="fa-solid fa-database me-2"></i>Data Management</h3>
<?php if ($message): ?><div class="alert alert-info py-2 small"><?= sanitize($message) ?></div><?php endif; ?>

<ul class="nav nav-tabs mb-3" style="border-color:var(--hz-border)">
  <li class="nav-item"><a class="nav-link active" data-bs-toggle="tab" href="#tabHab" style="color:#fff">Habitations</a></li>
  <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tabSites" style="color:var(--hz-text-dim)">Relocation Sites</a></li>
  <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tabAlerts" style="color:var(--hz-text-dim)">Add Alert</a></li>
  <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tabLogs" style="color:var(--hz-text-dim)">System Logs</a></li>
</ul>

<div class="tab-content">
  <div class="tab-pane fade show active" id="tabHab">
    <div class="hz-panel">
      <div class="hz-panel-header"><h5>Habitations</h5><a href="../add-habitation.php" class="btn btn-hz-primary btn-sm">Add New</a></div>
      <div class="table-responsive"><table class="hz-table">
        <thead><tr><th>ID</th><th>Name</th><th>District</th><th>Action</th></tr></thead>
        <tbody><?php foreach ($habitations as $h): ?>
          <tr><td class="mono text-faint">#<?= $h['id'] ?></td><td class="text-white"><?= sanitize($h['name']) ?></td><td><?= sanitize($h['district']) ?></td>
          <td>
            <a href="../edit-habitation.php?id=<?= $h['id'] ?>" class="btn btn-hz-ghost btn-sm"><i class="fa-solid fa-pen"></i></a>
            <form method="POST" class="d-inline" onsubmit="return confirm('Delete this habitation and all its history?')">
              <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>"><input type="hidden" name="action" value="delete_habitation"><input type="hidden" name="id" value="<?= $h['id'] ?>">
              <button class="btn btn-hz-ghost btn-sm text-danger"><i class="fa-solid fa-trash"></i></button>
            </form>
          </td></tr>
        <?php endforeach; ?></tbody>
      </table></div>
    </div>
  </div>

  <div class="tab-pane fade" id="tabSites">
    <div class="hz-panel mb-3">
      <div class="hz-panel-header"><h5>Add Relocation Site</h5></div>
      <div class="hz-panel-body">
        <form method="POST">
          <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>"><input type="hidden" name="action" value="add_site">
          <div class="row g-2">
            <div class="col-md-4"><input name="name" placeholder="Site Name" required class="form-control hz-form-control"></div>
            <div class="col-md-4"><input name="district" placeholder="District" required class="form-control hz-form-control"></div>
            <div class="col-md-4"><input name="land" type="number" step="0.01" placeholder="Land (acres)" class="form-control hz-form-control"></div>
            <div class="col-md-3"><input name="latitude" type="number" step="0.000001" placeholder="Latitude" required class="form-control hz-form-control"></div>
            <div class="col-md-3"><input name="longitude" type="number" step="0.000001" placeholder="Longitude" required class="form-control hz-form-control"></div>
            <div class="col-md-3"><input name="current_population" type="number" placeholder="Current Population" class="form-control hz-form-control"></div>
            <div class="col-md-3"><input name="max_capacity" type="number" placeholder="Max Capacity" required class="form-control hz-form-control"></div>
            <div class="col-md-2"><input name="water" type="number" placeholder="Water 0-100" class="form-control hz-form-control"></div>
            <div class="col-md-2"><input name="electricity" type="number" placeholder="Electricity 0-100" class="form-control hz-form-control"></div>
            <div class="col-md-2"><input name="healthcare" type="number" placeholder="Healthcare 0-100" class="form-control hz-form-control"></div>
            <div class="col-md-2"><input name="schools" type="number" placeholder="Schools 0-100" class="form-control hz-form-control"></div>
            <div class="col-md-2"><input name="road" type="number" placeholder="Roads 0-100" class="form-control hz-form-control"></div>
            <div class="col-md-2"><input name="hazard_risk" type="number" placeholder="Hazard Risk 0-100" class="form-control hz-form-control"></div>
            <div class="col-md-3"><input name="distance" type="number" step="0.1" placeholder="Distance from Red Zone (km)" class="form-control hz-form-control"></div>
          </div>
          <button class="btn btn-hz-primary btn-sm mt-3">Add Site</button>
        </form>
      </div>
    </div>
    <div class="hz-panel">
      <div class="table-responsive"><table class="hz-table">
        <thead><tr><th>Name</th><th>District</th><th>Capacity</th><th>Suitability</th><th>Action</th></tr></thead>
        <tbody><?php foreach ($sites as $s): ?>
          <tr><td class="text-white"><?= sanitize($s['name']) ?></td><td><?= sanitize($s['district']) ?></td><td><?= number_format($s['max_capacity']) ?></td><td><?= $s['suitability_score'] ?>%</td>
          <td><form method="POST" onsubmit="return confirm('Delete this site?')">
            <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>"><input type="hidden" name="action" value="delete_site"><input type="hidden" name="id" value="<?= $s['id'] ?>">
            <button class="btn btn-hz-ghost btn-sm text-danger"><i class="fa-solid fa-trash"></i></button>
          </form></td></tr>
        <?php endforeach; ?></tbody>
      </table></div>
    </div>
  </div>

  <div class="tab-pane fade" id="tabAlerts">
    <div class="hz-panel">
      <div class="hz-panel-header"><h5>Create Alert</h5></div>
      <div class="hz-panel-body">
        <form method="POST">
          <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>"><input type="hidden" name="action" value="add_alert">
          <div class="row g-2">
            <div class="col-md-2"><select name="type" class="form-select hz-form-control"><option>CRITICAL</option><option>WARNING</option><option selected>INFO</option></select></div>
            <div class="col-md-4"><input name="title" placeholder="Alert title" required class="form-control hz-form-control"></div>
            <div class="col-md-6"><input name="message" placeholder="Message" required class="form-control hz-form-control"></div>
          </div>
          <button class="btn btn-hz-primary btn-sm mt-3">Publish Alert</button>
        </form>
      </div>
    </div>
  </div>

  <div class="tab-pane fade" id="tabLogs">
    <div class="hz-panel">
      <div class="table-responsive"><table class="hz-table">
        <thead><tr><th>User</th><th>Action</th><th>Details</th><th>IP</th><th>Time</th></tr></thead>
        <tbody><?php foreach ($logs as $l): ?>
          <tr><td class="text-white"><?= sanitize($l['user_name'] ?? 'System') ?></td><td class="mono small"><?= sanitize($l['action']) ?></td><td class="text-dim small"><?= sanitize($l['details'] ?? '') ?></td><td class="text-faint small"><?= sanitize($l['ip_address'] ?? '') ?></td><td class="text-faint small"><?= date('d M H:i', strtotime($l['created_at'])) ?></td></tr>
        <?php endforeach; ?></tbody>
      </table></div>
    </div>
  </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
