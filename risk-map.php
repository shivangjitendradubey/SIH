<?php
require_once __DIR__ . '/includes/auth.php';
requireLogin();
require_once __DIR__ . '/includes/risk-engine.php';
$conn = getDbConnection();
$pageTitle = 'Risk Map';

$habitations = $conn->query('SELECT * FROM habitations')->fetch_all(MYSQLI_ASSOC);
$sites = $conn->query('SELECT * FROM relocation_sites')->fetch_all(MYSQLI_ASSOC);
$hazardZones = json_decode(file_get_contents(__DIR__ . '/data/sample-hazards.geojson'), true);

$habJson = [];
foreach ($habitations as $h) {
    $habJson[] = [
        'name' => $h['name'], 'district' => $h['district'],
        'lat' => (float)$h['latitude'], 'lng' => (float)$h['longitude'],
        'risk_score' => (float)$h['risk_score'], 'risk_level' => $h['risk_level'],
        'population' => (int)$h['population'], 'vulnerable' => (int)$h['vulnerable_population'],
        'hazard' => $h['primary_hazard'], 'events' => (int)$h['historical_events'],
        'priority' => $h['priority'],
    ];
}
$siteJson = [];
foreach ($sites as $s) {
    $siteJson[] = [
        'name' => $s['name'], 'district' => $s['district'],
        'lat' => (float)$s['latitude'], 'lng' => (float)$s['longitude'],
        'capacity' => (int)$s['max_capacity'], 'current' => (int)$s['current_population'],
        'suitability' => (float)$s['suitability_score'], 'hazard_risk' => (int)$s['hazard_risk'],
    ];
}

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';
?>
<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
  <div><h3 class="text-white mb-0">Interactive GIS Risk Map</h3><div class="text-dim small">Multi-hazard overlay — click any marker for details</div></div>
</div>

<div class="row g-3">
  <div class="col-lg-3">
    <div class="hz-panel p-3 mb-3">
      <h6 class="text-white mb-3"><i class="fa-solid fa-layer-group me-2"></i>Layer Controls</h6>
      <div class="form-check form-switch mb-2"><input class="form-check-input" type="checkbox" id="lyrMultiHazard" checked><label class="form-check-label small" for="lyrMultiHazard">Multi-Hazard Zones</label></div>
      <div class="form-check form-switch mb-2"><input class="form-check-input" type="checkbox" id="lyrFlood" checked><label class="form-check-label small" for="lyrFlood">Flood</label></div>
      <div class="form-check form-switch mb-2"><input class="form-check-input" type="checkbox" id="lyrLandslide" checked><label class="form-check-label small" for="lyrLandslide">Landslide</label></div>
      <div class="form-check form-switch mb-2"><input class="form-check-input" type="checkbox" id="lyrCloudburst" checked><label class="form-check-label small" for="lyrCloudburst">Cloudburst</label></div>
      <div class="form-check form-switch mb-2"><input class="form-check-input" type="checkbox" id="lyrCoastal" checked><label class="form-check-label small" for="lyrCoastal">Coastal Erosion</label></div>
      <hr class="border-secondary opacity-25">
      <div class="form-check form-switch mb-2"><input class="form-check-input" type="checkbox" id="lyrHabitations" checked><label class="form-check-label small" for="lyrHabitations">Vulnerable Habitations</label></div>
      <div class="form-check form-switch mb-2"><input class="form-check-input" type="checkbox" id="lyrSites" checked><label class="form-check-label small" for="lyrSites">Relocation Sites</label></div>
    </div>
    <div class="map-legend">
      <div class="fw-semibold text-white mb-2 small">LEGEND</div>
      <div><span class="legend-dot" style="background:#e11d2e"></span>Critical / Red Zone</div>
      <div><span class="legend-dot" style="background:#f97316"></span>High Risk</div>
      <div><span class="legend-dot" style="background:#eab308"></span>Moderate Risk</div>
      <div><span class="legend-dot" style="background:#22c55e"></span>Safe / Relocation Site</div>
    </div>
  </div>
  <div class="col-lg-9">
    <div id="riskMap"></div>
  </div>
</div>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
const map = L.map('riskMap', { zoomControl: true }).setView([21.5, 80.5], 5);
L.tileLayer('https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png', {
  attribution: '&copy; OpenStreetMap &copy; CARTO', maxZoom: 18
}).addTo(map);

const riskColor = { CRITICAL:'#e11d2e', HIGH:'#f97316', MODERATE:'#eab308', LOW:'#3b82f6', SAFE:'#22c55e' };
const hazardZones = <?= json_encode($hazardZones) ?>;
const habitations = <?= json_encode($habJson) ?>;
const sites = <?= json_encode($siteJson) ?>;

// --- Multi-hazard polygon layers, split by hazard type for independent toggles ---
const hazardLayers = { Flood: L.layerGroup(), Landslide: L.layerGroup(), Cloudburst: L.layerGroup(), 'Coastal Erosion': L.layerGroup() };
const multiHazardLayer = L.layerGroup();

hazardZones.features.forEach(f => {
  const color = riskColor[f.properties.risk_level] || '#666';
  const poly = L.geoJSON(f, {
    style: { color: color, weight: 1.5, fillColor: color, fillOpacity: 0.18 }
  }).bindPopup(`<div class="map-popup"><h6>${f.properties.zone_name}</h6>
      <div class="mp-row"><span>Hazard</span><span>${f.properties.hazard_type}</span></div>
      <div class="mp-row"><span>Risk Level</span><span>${f.properties.risk_level}</span></div></div>`);
  const type = f.properties.hazard_type;
  if (hazardLayers[type]) poly.addTo(hazardLayers[type]);
  poly.addTo(multiHazardLayer);
});

// --- Habitation markers ---
const habLayer = L.layerGroup();
habitations.forEach(h => {
  const color = riskColor[h.risk_level] || '#666';
  const marker = L.circleMarker([h.lat, h.lng], {
    radius: 8, color: '#fff', weight: 1.5, fillColor: color, fillOpacity: 0.9
  }).bindPopup(`<div class="map-popup"><h6>${h.name}</h6>
      <div class="mp-row"><span>Risk Score</span><span>${h.risk_score}/100</span></div>
      <div class="mp-row"><span>Population</span><span>${h.population.toLocaleString()}</span></div>
      <div class="mp-row"><span>Vulnerable Population</span><span>${h.vulnerable.toLocaleString()}</span></div>
      <div class="mp-row"><span>Primary Hazard</span><span>${h.hazard}</span></div>
      <div class="mp-row"><span>Disaster Events</span><span>${h.events}</span></div>
      <div class="mp-row"><span>Relocation Priority</span><span>${h.priority}</span></div></div>`);
  marker.addTo(habLayer);
});

// --- Relocation site markers ---
const siteLayer = L.layerGroup();
sites.forEach(s => {
  const avail = s.capacity - s.current;
  const marker = L.marker([s.lat, s.lng], {
    icon: L.divIcon({ className:'', html:`<div style="background:#22c55e;width:16px;height:16px;border-radius:4px;border:2px solid #fff;transform:rotate(45deg)"></div>`, iconSize:[16,16] })
  }).bindPopup(`<div class="map-popup"><h6><i class="fa-solid fa-house-flag me-1"></i>${s.name}</h6>
      <div class="mp-row"><span>Suitability</span><span>${s.suitability}%</span></div>
      <div class="mp-row"><span>Max Capacity</span><span>${s.capacity.toLocaleString()}</span></div>
      <div class="mp-row"><span>Available Capacity</span><span>${avail.toLocaleString()}</span></div>
      <div class="mp-row"><span>Hazard Risk</span><span>${s.hazard_risk}/100</span></div></div>`);
  marker.addTo(siteLayer);
});

multiHazardLayer.addTo(map);
habLayer.addTo(map);
siteLayer.addTo(map);

document.getElementById('lyrMultiHazard').addEventListener('change', e => e.target.checked ? multiHazardLayer.addTo(map) : map.removeLayer(multiHazardLayer));
document.getElementById('lyrFlood').addEventListener('change', e => e.target.checked ? hazardLayers['Flood'].addTo(map) : map.removeLayer(hazardLayers['Flood']));
document.getElementById('lyrLandslide').addEventListener('change', e => e.target.checked ? hazardLayers['Landslide'].addTo(map) : map.removeLayer(hazardLayers['Landslide']));
document.getElementById('lyrCloudburst').addEventListener('change', e => e.target.checked ? hazardLayers['Cloudburst'].addTo(map) : map.removeLayer(hazardLayers['Cloudburst']));
document.getElementById('lyrCoastal').addEventListener('change', e => e.target.checked ? hazardLayers['Coastal Erosion'].addTo(map) : map.removeLayer(hazardLayers['Coastal Erosion']));
document.getElementById('lyrHabitations').addEventListener('change', e => e.target.checked ? habLayer.addTo(map) : map.removeLayer(habLayer));
document.getElementById('lyrSites').addEventListener('change', e => e.target.checked ? siteLayer.addTo(map) : map.removeLayer(siteLayer));
</script>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
