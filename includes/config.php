<?php
// Set timezone for SalamiPay
date_default_timezone_set('Asia/Dhaka');

/**
 * SalamiPay - Database Connection (PDO)
 */

define('DB_HOST', 'localhost');
define('DB_NAME', 'tnllxbiz_salami');
define('DB_USER', 'tnllxbiz_salami');
define('DB_PASS', 'tnllxbiz_salami');
define('DB_CHARSET', 'utf8mb4');

try {
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
    // Set database timezone to match PHP
    $pdo->exec("SET time_zone = '+06:00'");

}
catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Start session if not already started with security settings
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    // Only set SameSite if technically possible or use cookie params
    session_start([
        'cookie_httponly' => true,
        'cookie_secure'   => isset($_SERVER['HTTPS']),
        'cookie_samesite' => 'Lax',
    ]);
}

// Base URL - adjust if needed
define('BASE_URL', 'https://xn--f6beex4abi.xn--45bl4db.xn--54b7fta0cc');

// Site Branding
define('SITE_NAME', 'সালামির পাতা');
define('SITE_FAVICON', '/assets/img/favicon.png');
