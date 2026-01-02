<?php
// Database configuration
// Automated detection: If hosted on InfinityFree, use live credentials; otherwise use local.
$is_live = (strpos($_SERVER['HTTP_HOST'], 'infinityfree') !== false || strpos($_SERVER['HTTP_HOST'], 'epizy.com') !== false);

if ($is_live) {
    // LIVE SETTINGS (InfinityFree)
    define('DB_HOST', 'sql307.infinityfree.com');
    define('DB_USER', 'if0_40697639');
    define('DB_PASS', 'vSIwLXUU0wRcXn');
    define('DB_NAME', 'if0_40697639_agrimarket');
} else {
    // LOCAL SETTINGS (XAMPP)
    define('DB_HOST', 'localhost');
    define('DB_USER', 'root');
    define('DB_PASS', '');
    define('DB_NAME', 'agrimarket_db');
}

// Establish connection using PDO
try {
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
    $pdo = new PDO($dsn, DB_USER, DB_PASS);
    
    // Set the PDO error mode to exception for easier debugging (hidden in live)
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    if ($is_live) {
        // Hide technical errors from users on the live site
        error_log("Connection failed: " . $e->getMessage());
        die("Connection failed. Please check back later.");
    }
    die("DATABASE ERROR: " . $e->getMessage());
}

// Base URL Configuration
// This ensures that all links, images, and styles work regardless of where you host it.
$protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'];

if ($is_live) {
    // On the live site, the project is in the root folder (/)
    $base_url = "$protocol://$host/";
} else {
    // Locally, it's usually in /Aggricultural_Market/
    $base_url = "$protocol://$host/Aggricultural_Market/";
}

define('BASE_URL', $base_url);
?>
