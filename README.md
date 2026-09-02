# [ResQzone — Hazard Intelligence & Safe-Zone Analytics](https://resqzone.my-board.org/)

*"See Risk. Plan Safety. Save Lives."*

A working PHP + MySQL + Leaflet.js + Chart.js GIS decision-support prototype for
disaster risk assessment and relocation planning. Built with plain PHP 8,
Bootstrap 5, and vanilla JS — no frameworks, no build step, runs on XAMPP/WAMP
or any shared PHP/MySQL host (e.g. InfinityFree).

> **DEMO / PROTOTYPE DATA.** Every habitation, relocation site, and statistic in
> this build is fictional sample data for demonstration only.

---

## 1. What's included

- Public landing page + a public (no-login) risk map preview
- Session-based login for two roles: `admin` and `authority`
- Authority dashboard with live KPIs and Chart.js analytics
- Interactive Leaflet.js GIS risk map with toggleable hazard layers
- Habitation management (add/edit/list/filter/search)
- A real, transparent **risk scoring engine** (PHP, not random numbers)
- Explainable "AI-inspired" risk analysis page with a live What-If simulator
- Relocation planner: site suitability, carrying-capacity bars, smart site
  matching, and a ranked relocation-priority table
- Site comparison tool (2–3 sites side by side)
- Report generator (5 report types) with print and CSV export
- Alerts panel (CRITICAL / WARNING / INFO), seeded + admin-creatable
- Admin panel: manage users, habitations, relocation sites, alerts, view
  system logs, and tune the risk-scoring weights live
- MySQL schema with 15 habitations, 10 relocation sites, hazard history,
  alerts, and starter risk-config weights — the app works immediately after
  import, no manual setup beyond the DB import

---

## 2. Project structure

```
/resqzone
├── index.php                 Public landing page
├── login.php / logout.php    Authentication
├── dashboard.php              Authority dashboard (KPIs + charts)
├── risk-map.php                Interactive GIS map (logged-in)
├── risk-map-preview.php        Public read-only map preview
├── habitations.php             Habitation list/search/filter
├── add-habitation.php / edit-habitation.php
├── ai-analysis.php             Explainable risk analysis + What-If tool
├── relocation.php               Relocation planner (sites, capacity, matching)
├── site-comparison.php          2–3 site comparison table
├── reports.php                  Report generator + CSV export
├── alerts.php                   Alerts panel
├── profile.php                  Change password
├── admin/
│   ├── users.php                 User CRUD
│   ├── data.php                   Habitation/site/alert data management + logs
│   └── settings.php               Risk-weight configuration
├── api/
│   ├── risk.php                   Recalculate-all endpoint
│   ├── habitations.php            JSON habitation queries
│   ├── relocation.php             JSON site + recommendation queries
│   └── alerts.php                  Unread alert count (navbar bell)
├── config/
│   └── database.php               DB connection constants
├── includes/
│   ├── auth.php                   Session/CSRF/sanitize helpers
│   ├── header.php / sidebar.php / footer.php
│   └── risk-engine.php            Core scoring formulas (see §5)
├── assets/
│   ├── css/style.css              Theme (dark command-console design)
│   └── js/main.js                 Toasts, table filters, alert polling
├── data/
│   └── sample-hazards.geojson     Sample multi-hazard zone polygons
└── database/
    └── resqzone_db.sql              Full schema + seed data
```

---

## 3. XAMPP / WAMP setup (local)

1. Copy the `resqzone` folder into your server's web root:
   - XAMPP: `C:\xampp\htdocs\resqzone` (Windows) or `/opt/lampp/htdocs/resqzone` (Linux)
   - WAMP: `C:\wamp64\www\resqzone`
2. Start **Apache** and **MySQL** from the XAMPP/WAMP control panel.
3. Open **phpMyAdmin** (`http://localhost/phpmyadmin`).
4. Create nothing manually — just click **Import**, choose
   `database/resqzone_db.sql`, and run it. It creates the `resqzone_db` database,
   all tables, and the seed data itself.
5. Open `config/database.php` and confirm the credentials match your local
   MySQL (defaults `root` / empty password work on a stock XAMPP install).
6. Visit `http://localhost/resqzone/` in your browser.
7. Log in at `http://localhost/resqzone/login.php` with the demo credentials
   below.

---


## 4. Demo login credentials

| Role      | Email                        | Password     |
|-----------|------------------------------|---------------|
| Admin     | admin@resqzone.gov.demo        | `ResQzone@123`  |
| Authority | authority@resqzone.gov.demo    | `ResQzone@123`  |

Passwords are stored using PHP's `password_hash()` (bcrypt) and verified with
`password_verify()`. Change them from **Profile** after first login.

---

## 5. Architecture overview

- **Front controller-free PHP**: each `.php` file is a self-contained page
  that requires `includes/auth.php` (session guard) and, where needed,
  `includes/risk-engine.php` (scoring). There's no router — this keeps the
  project trivially deployable on any shared host that just serves `.php`
  files directly.
- **`config/database.php`** exposes a single `getDbConnection()` that returns
  a lazily-created `mysqli` instance. All queries elsewhere use **prepared
  statements** (`bind_param`) to prevent SQL injection.
- **`includes/auth.php`** centralizes session handling, `requireLogin()` /
  `requireAdmin()` guards, CSRF token generation/verification
  (`csrfToken()` / `verifyCsrf()`), output sanitization (`sanitize()` — used
  everywhere user or DB text is echoed, to prevent XSS), and an audit logger
  (`logAction()`) that writes to `system_logs`.
- **`includes/risk-engine.php`** is the analytical core (see §7 below) —
  pure functions that take habitation/site data and return scores, so the
  same formulas back the dashboard, the map popups, the AI-analysis page, the
  What-If simulator (mirrored in JS for instant client-side feedback), and
  the relocation planner.
- **GIS layer**: Leaflet.js renders a dark CARTO basemap; hazard zones are
  static sample GeoJSON (`data/sample-hazards.geojson`), habitations and
  relocation sites are live MySQL-backed markers with popups built from
  `risk-engine.php` output.
- **Charts**: Chart.js reads server-computed aggregates (risk distribution,
  hazard distribution, priority distribution, population exposure) rendered
  as JSON directly into the page.
- **AJAX/API layer** (`api/*.php`) returns JSON for the navbar alert-count
  poller and can be used to build further client-side features (e.g. a
  custom map widget) without a full page reload.
- **Security**: CSRF tokens on every state-changing form, prepared statements
  everywhere, `password_hash()`/`password_verify()`, `htmlspecialchars()` via
  `sanitize()` on all echoed output, and role-based route guards
  (`requireAdmin()` on all `/admin` pages).

---

## 6. Risk & relocation formulas

All formulas are implemented in `includes/risk-engine.php` and are
**fully transparent** — nothing is a black box or a random number.

### 6.1 Overall Risk Score (0–100)

```
Overall Risk = 40% × Hazard Score
             + 25% × Vulnerability Score
             + 20% × Exposure Score
             + 15% × Historical Impact Score
```

Where, per habitation:
- **Hazard Score** = `0.7 × max(flood, landslide, cloudburst, coastal)` +
  `0.3 × average(all four)` — dominated by the worst hazard, softened by the
  overall hazard profile.
- **Vulnerability Score** = `vulnerable_population / population × 100`.
- **Exposure Score** = `0.5 × population-pressure(0–100, capped at 6000)` +
  `0.5 × infrastructure_risk`.
- **Historical Impact Score** = `min(100, historical_events / 10 × 100)`.

**Classification:**

| Score  | Level    |
|--------|----------|
| 0–30   | SAFE     |
| 31–50  | LOW      |
| 51–70  | MODERATE |
| 71–85  | HIGH     |
| 86–100 | CRITICAL |

### 6.2 Relocation Priority Score (0–100)

```
Priority Score = 50% × Overall Risk
               + 25% × Vulnerability Score
               + 15% × Population Exposure
               + 10% × Historical Impact Score
```

| Score  | Priority     |
|--------|--------------|
| 85–100 | IMMEDIATE    |
| 65–84  | SHORT-TERM   |
| 45–64  | MEDIUM-TERM  |
| < 45   | MONITOR      |

### 6.3 Relocation Site Suitability Score (0–100)

```
Suitability = 25% × Hazard Safety   (= 100 − hazard_risk)
            + 20% × Available-Capacity Share
            + 15% × Electricity
            + 15% × Water Availability
            + 10% × Healthcare
            + 10% × Road Connectivity
            + 5%  × Schools
```

`Available Capacity = Max Capacity − Current Population`, and occupancy status
is `GOOD` (<80%), `LIMITED` (80–95%), or `FULL` (≥95%).

### 6.4 Smart Site Matching

For each at-risk habitation, `recommendBestSite()` prefers relocation sites in
the **same district**, falling back to the whole dataset if none exist, then
picks the highest-suitability site and generates a plain-language reason
(low hazard exposure, available capacity, road connectivity, healthcare,
water — whichever conditions the winning site actually meets).

### 6.5 Admin-configurable weights

All eight weights above (`weight_hazard`, `weight_vulnerability`, ... in the
`risk_config` table) can be edited from **Admin → Settings**. Saving
recalculates every habitation's risk and priority scores immediately and logs
a `risk_assessments` snapshot for history.

---

## 7. Notes on the "AI-inspired" language

Per the brief, ResQzone does **not** claim to run a trained machine-learning
model. The "AI-inspired" / "explainable AI" language on the Risk Analysis page
refers to a transparent, rules-based decision engine: every score shown is
traceable to an explicit formula and the exact inputs that produced it. This
is intentional and stated on the page itself, so the prototype does not
overstate its own capabilities.

---

## 8. Known prototype limitations

- Password reset via email is not implemented (change password from Profile
  or ask an Admin to edit the account instead).
- CSV export and print are implemented; PDF export is not (browser print-to-PDF
  covers this for a hackathon/demo context).
- The public map preview intentionally omits exact habitation names and
  precise population counts for a lighter "public" data-sensitivity posture.
- Hazard zone polygons are static sample GeoJSON, not live satellite/remote-sensing
  data — swap `data/sample-hazards.geojson` for real data to go beyond the demo.

---

## 👥 Credits
This platform was envisioned, designed, and developed by:

* **Project Owner & Lead Developer:** [Shivang Dubey](https://shivangdubey.site.je/)
* **Core Engineering & Analytics Team:**
  * Divya Behera
  * Shriya Bowlekar
  * Aryan Gupta
  * Anurag Giri
  * Amit Kushwaha
