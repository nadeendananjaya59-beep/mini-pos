<?php
/**
 * Database & application configuration.
 * Edit these values if your XAMPP/WAMP setup is different.
 */

define('DB_HOST', 'localhost');
define('DB_NAME', 'mini_pos');
define('DB_USER', 'root');
define('DB_PASS', '');            // XAMPP default is an empty password

// URL path of the project (folder name inside htdocs). No trailing slash.
define('BASE_URL', '/mini-pos');

// Session expires after this many seconds of inactivity (15 minutes).
define('SESSION_TIMEOUT', 900);

// Currency label shown across the system.
define('CURRENCY', 'Rs.');

/**
 * Returns a shared PDO connection (created on first use).
 */
function db(): PDO
{
    static $pdo = null;

    if ($pdo === null) {
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        } catch (PDOException $e) {
            // Do not leak connection details to the browser.
            http_response_code(500);
            exit('Database connection failed. Check config/db.php and make sure MySQL is running.');
        }
    }

    return $pdo;
}
