<?php
/**
 * RESQZONE - Risk Calculation Engine
 * Transparent, explainable, formula-driven scoring (no black-box "AI").
 */
require_once __DIR__ . '/../config/database.php';

/** Fetch configurable weights from risk_config table (admin-editable). Falls back to spec defaults. */
function getRiskWeights(): array
{
    static $weights = null;
    if ($weights !== null) return $weights;

    $defaults = [
        'weight_hazard' => 0.40, 'weight_vulnerability' => 0.25,
        'weight_exposure' => 0.20, 'weight_historical' => 0.15,
        'priority_weight_risk' => 0.50, 'priority_weight_vulnerability' => 0.25,
        'priority_weight_exposure' => 0.15, 'priority_weight_historical' => 0.10,
    ];
    try {
        $conn = getDbConnection();
        $res = $conn->query('SELECT config_key, config_value FROM risk_config');
        if ($res) {
            while ($row = $res->fetch_assoc()) {
                $defaults[$row['config_key']] = (float)$row['config_value'];
            }
        }
    } catch (Throwable $e) { /* use defaults */ }
    $weights = $defaults;
    return $weights;
}

/**
 * Core risk scoring function required by spec.
 * Inputs are already normalized 0-100 component scores.
 * Overall Risk = 40% Hazard + 25% Vulnerability + 20% Exposure + 15% Historical
 */
function calculateRiskScore(float $hazard_score, float $vulnerability_score, float $exposure_score, float $historical_score): array
{
    $w = getRiskWeights();
    $score = ($hazard_score * $w['weight_hazard'])
           + ($vulnerability_score * $w['weight_vulnerability'])
           + ($exposure_score * $w['weight_exposure'])
           + ($historical_score * $w['weight_historical']);
    $score = max(0, min(100, round($score, 2)));

    return ['risk_score' => $score, 'risk_level' => classifyRiskLevel($score)];
}

function classifyRiskLevel(float $score): string
{
    if ($score >= 86) return 'CRITICAL';
    if ($score >= 71) return 'HIGH';
    if ($score >= 51) return 'MODERATE';
    if ($score >= 31) return 'LOW';
    return 'SAFE';
}

function riskLevelColor(string $level): string
{
    return match ($level) {
        'CRITICAL' => '#e11d2e',
        'HIGH'     => '#f97316',
        'MODERATE' => '#eab308',
        'LOW'      => '#3b82f6',
        default    => '#22c55e', // SAFE
    };
}

/**
 * Derive the four 0-100 component scores directly from a habitation row,
 * then compute the overall risk score. This is what powers the live
 * database-driven dashboard (not random/static numbers).
 */
function computeHabitationRisk(array $hab): array
{
    // Hazard score = worst-case of the 4 hazard sub-scores (a habitation is as
    // risky as its dominant hazard), softened slightly by the average of the rest.
    $hazards = [
        (float)$hab['flood_risk'], (float)$hab['landslide_risk'],
        (float)$hab['cloudburst_risk'], (float)$hab['coastal_erosion_risk'],
    ];
    $maxHazard = max($hazards);
    $avgHazard = array_sum($hazards) / count($hazards);
    $hazard_score = ($maxHazard * 0.7) + ($avgHazard * 0.3);

    // Vulnerability score = share of population that is vulnerable, scaled to 0-100
    $population = max(1, (int)$hab['population']);
    $vulnShare = ($hab['vulnerable_population'] / $population) * 100;
    $vulnerability_score = min(100, $vulnShare);

    // Exposure score = population size pressure + infrastructure weakness
    $popExposure = min(100, ($population / 6000) * 100); // 6000 treated as high-exposure ceiling
    $exposure_score = ($popExposure * 0.5) + ((float)$hab['infrastructure_risk'] * 0.5);

    // Historical score = frequency of recorded disaster events, capped at 10 events = 100
    $historical_score = min(100, ((int)$hab['historical_events'] / 10) * 100);

    $result = calculateRiskScore($hazard_score, $vulnerability_score, $exposure_score, $historical_score);
    $result['hazard_score'] = round($hazard_score, 2);
    $result['vulnerability_score'] = round($vulnerability_score, 2);
    $result['exposure_score'] = round($exposure_score, 2);
    $result['historical_score'] = round($historical_score, 2);

    // Priority score = 50% risk + 25% vulnerability + 15% exposure + 10% historical
    $w = getRiskWeights();
    $priority_score = ($result['risk_score'] * $w['priority_weight_risk'])
        + ($vulnerability_score * $w['priority_weight_vulnerability'])
        + ($popExposure * $w['priority_weight_exposure'])
        + ($historical_score * $w['priority_weight_historical']);
    $priority_score = max(0, min(100, round($priority_score, 2)));

    $result['priority_score'] = $priority_score;
    $result['priority'] = classifyPriority($priority_score);
    return $result;
}

function classifyPriority(float $score): string
{
    if ($score >= 85) return 'IMMEDIATE';
    if ($score >= 65) return 'SHORT-TERM';
    if ($score >= 45) return 'MEDIUM-TERM';
    return 'MONITOR';
}

function priorityColor(string $priority): string
{
    return match ($priority) {
        'IMMEDIATE'   => '#e11d2e',
        'SHORT-TERM'  => '#f97316',
        'MEDIUM-TERM' => '#eab308',
        default       => '#22c55e', // MONITOR
    };
}

/**
 * Relocation Site Suitability Score
 * 25% Hazard Safety + 20% Available Capacity + 15% Infrastructure (avg of
 * water+electricity+roads is folded per spec into infra/water/health/road/edu)
 * Spec weights: 25% hazard safety, 20% capacity, 15% infra, 15% water,
 * 10% healthcare, 10% roads, 5% education
 */
function computeSiteSuitability(array $site): array
{
    $maxCapacity = max(1, (int)$site['max_capacity']);
    $available = max(0, $maxCapacity - (int)$site['current_population']);
    $capacityScore = min(100, ($available / $maxCapacity) * 100);
    $hazardSafety = 100 - (float)$site['hazard_risk']; // invert: lower hazard risk = safer

    $score = ($hazardSafety * 0.25)
           + ($capacityScore * 0.20)
           + ((float)$site['electricity'] * 0.15)
           + ((float)$site['water_availability'] * 0.15)
           + ((float)$site['healthcare'] * 0.10)
           + ((float)$site['road_connectivity'] * 0.10)
           + ((float)$site['schools'] * 0.05);

    $score = max(0, min(100, round($score, 2)));

    return [
        'suitability_score' => $score,
        'available_capacity' => $available,
        'occupancy_pct' => round(($site['current_population'] / $maxCapacity) * 100, 1),
        'recommendation' => suitabilityLabel($score),
    ];
}

function suitabilityLabel(float $score): string
{
    if ($score >= 85) return 'HIGHLY SUITABLE';
    if ($score >= 70) return 'SUITABLE';
    if ($score >= 50) return 'MODERATELY SUITABLE';
    return 'NOT RECOMMENDED';
}

function occupancyStatus(float $occupancyPct): string
{
    if ($occupancyPct >= 95) return 'FULL';
    if ($occupancyPct >= 80) return 'LIMITED';
    return 'GOOD';
}

/** Recalculate and persist risk + priority for every habitation. Returns count updated. */
function recalculateAllHabitations(): int
{
    $conn = getDbConnection();
    $result = $conn->query('SELECT * FROM habitations');
    $count = 0;
    $stmt = $conn->prepare('UPDATE habitations SET risk_score=?, risk_level=?, priority_score=?, priority=? WHERE id=?');
    while ($row = $result->fetch_assoc()) {
        $r = computeHabitationRisk($row);
        $stmt->bind_param('dsdsi', $r['risk_score'], $r['risk_level'], $r['priority_score'], $r['priority'], $row['id']);
        $stmt->execute();

        $logStmt = $conn->prepare('INSERT INTO risk_assessments (habitation_id, hazard_score, vulnerability_score, exposure_score, historical_score, risk_score, risk_level) VALUES (?,?,?,?,?,?,?)');
        $logStmt->bind_param('iddddds', $row['id'], $r['hazard_score'], $r['vulnerability_score'], $r['exposure_score'], $r['historical_score'], $r['risk_score'], $r['risk_level']);
        $logStmt->execute();
        $logStmt->close();
        $count++;
    }
    $stmt->close();

    // Recalculate site suitability too
    $sites = $conn->query('SELECT * FROM relocation_sites');
    $siteStmt = $conn->prepare('UPDATE relocation_sites SET suitability_score=? WHERE id=?');
    while ($site = $sites->fetch_assoc()) {
        $s = computeSiteSuitability($site);
        $siteStmt->bind_param('di', $s['suitability_score'], $site['id']);
        $siteStmt->execute();
    }
    $siteStmt->close();

    return $count;
}

/** Find the best relocation site for a given habitation (highest suitability, in-district first). */
function recommendBestSite(array $habitation, array $allSites): ?array
{
    if (empty($allSites)) return null;

    $sameDistrict = array_values(array_filter($allSites, fn($s) => $s['district'] === $habitation['district']));
    $pool = !empty($sameDistrict) ? $sameDistrict : $allSites;

    usort($pool, fn($a, $b) => $b['suitability_score'] <=> $a['suitability_score']);
    $best = $pool[0];

    $reasons = [];
    if ($best['hazard_risk'] < 20) $reasons[] = 'low hazard exposure';
    $suit = computeSiteSuitability($best);
    if ($suit['available_capacity'] > 1000) $reasons[] = 'adequate carrying capacity';
    if ($best['road_connectivity'] >= 75) $reasons[] = 'strong road connectivity';
    if ($best['healthcare'] >= 70) $reasons[] = 'nearby healthcare infrastructure';
    if ($best['water_availability'] >= 75) $reasons[] = 'reliable water availability';
    if (empty($reasons)) $reasons[] = 'best available balance of safety and capacity in the region';

    $best['available_capacity'] = $suit['available_capacity'];
    $best['reason'] = 'Site selected based on ' . implode(', ', $reasons) . '.';
    return $best;
}
