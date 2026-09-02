<?php
/**
 * RESQZONE - Database Configuration
 * Update these constants to match your XAMPP/WAMP or shared hosting credentials.
 */
define('DB_HOST', 'localhost');
define('DB_NAME', 'resqzone_db');
define('DB_USER', 'root');
define('DB_PASS', '');       // InfinityFree/shared hosting: set the DB password provided by your host
define('DB_PORT', 3306);

// App-wide constants
define('APP_NAME', 'RESQZONE');
define('APP_TAGLINE', 'Hazard Intelligence & Safe-Zone Analytics');
define('DEMO_MODE', true); // Keeps "DEMO / PROTOTYPE DATA" banners visible

mysqli_report(MYSQLI_REPORT_OFF); // we handle errors manually to avoid leaking details

function getDbConnection(): mysqli
{
    static $conn = null;
    if ($conn === null) {
        $conn = @new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);
        if ($conn->connect_error) {
            http_response_code(500);
            die('<div style="font-family:sans-serif;padding:40px;color:#b91c1c">
                <h2>RESQZONE Database Connection Error</h2>
                <p>Could not connect to MySQL. Please verify config/database.php credentials and
                that the <code>resqzone_db</code> database has been imported.</p>
                </div>');
        }
        $conn->set_charset('utf8mb4');
    }
    return $conn;
}
