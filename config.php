<?php
// Application Configuration
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/error.log');

// Database Configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'barangay_db');
define('DB_USER', 'root');
define('DB_PASS', '');

// Application Settings
define('APP_NAME', 'Barangay Bonbon Portal');
define('APP_VERSION', '1.0.0');
define('SESSION_TIMEOUT', 3600);

// CORS Headers
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Start Session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
