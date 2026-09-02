<?php
require_once __DIR__ . '/config/database.php';
$conn = getDbConnection();
$habitations = $conn->query('SELECT name, district, latitude, longitude, risk_score, risk_level, population, primary_hazard FROM habitations')->fetch_all(MYSQLI_ASSOC);
$hazardZones = json_decode(file_get_contents(__DIR__ . '/data/sample-hazards.geojson'), true);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Public Risk Map Preview — RESQZONE</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<div class="demo-banner"><i class="fa-solid fa-flask"></i> DEMO / PROTOTYPE DATA — Public preview shows generalized risk levels only. Sign in as an authority for full habitation and relocation detail.</div>
<nav class="navbar public-nav">
  <div class="container d-flex justify-content-between">
    <a class="navbar-brand app-brand" href="index.php"><i class="fa-solid fa-tower-broadcast"></i> RESQZONE</a>
    <a href="login.php" class="btn btn-hz-outline btn-sm">Authority Login</a>
  </div>
</nav>
<div class="container-fluid px-3 py-4">
  <h4 class="text-white mb-1">Public Risk Map — Preview</h4>
  <p class="text-dim small mb-3">This is a limited public view. Population figures are rounded and habitation names are generalized.</p>
  <div id="riskMap" style="height:70vh;"></div>
  <div class="map-legend mt-3 d-inline-block">
    <span class="legend-dot" style="background:#e11d2e"></span>Critical
    <span class="legend-dot ms-3" style="background:#f97316"></span>High
    <span class="legend-dot ms-3" style="background:#eab308"></span>Moderate
    <span class="legend-dot ms-3" style="background:#22c55e"></span>Safe/Low
  </div>
</div>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
const map = L.map('riskMap').setView([21.5, 80.5], 5);
L.tileLayer('https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png',{attribution:'&copy; OpenStreetMap &copy; CARTO'}).addTo(map);
const riskColor = { CRITICAL:'#e11d2e', HIGH:'#f97316', MODERATE:'#eab308', LOW:'#3b82f6', SAFE:'#22c55e' };
const hazardZones = <?= json_encode($hazardZones) ?>;
hazardZones.features.forEach(f=>{
  const c = riskColor[f.properties.risk_level] || '#666';
  L.geoJSON(f,{style:{color:c,weight:1.2,fillColor:c,fillOpacity:0.15}}).bindPopup(f.properties.zone_name+' — '+f.properties.hazard_type).addTo(map);
});
const habitations = <?= json_encode($habitations) ?>;
habitations.forEach(h=>{
  const c = riskColor[h.risk_level] || '#666';
  L.circleMarker([h.latitude,h.longitude],{radius:7,color:'#fff',weight:1,fillColor:c,fillOpacity:0.85})
    .bindPopup(`<b>${h.district} district habitation</b><br>Risk Level: ${h.risk_level}<br>Primary Hazard: ${h.primary_hazard}<br><a href="login.php">Sign in for full detail</a>`)
    .addTo(map);
});
</script>
</body>
</html>
