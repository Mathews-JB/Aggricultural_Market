<?php
// Database configuration
if ($_SERVER['HTTP_HOST'] === 'CrestHN.infinityfree.me' || strpos($_SERVER['HTTP_HOST'], 'infinityfree') !== false) {
    define('DB_HOST', 'sql307.infinityfree.com');
    define('DB_USER', 'if0_40697639');
    define('DB_PASS', 'vSIwLXUU0wRcXn');
    define('DB_NAME', 'if0_40697639_agrimarket');
} else {
    define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
    define('DB_USER', getenv('DB_USER') ?: 'root');
    define('DB_PASS', getenv('DB_PASS') ?: '');
    define('DB_NAME', getenv('DB_NAME') ?: 'agrimarket_db');
}

// Establish connection
try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    // Set the PDO error mode to exception
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    // On production, we might want to hide the specific error message
    if (getenv('ENVIRONMENT') === 'production' || strpos($_SERVER['HTTP_HOST'], 'infinityfree') !== false) {
        die("Connection failed. Please try again later.");
    }
    die("ERROR: Could not connect. " . $e->getMessage());
}

// Base path
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'];
$base_url = getenv('BASE_URL') ?: "$protocol://$host/Aggricultural_Market/";
if (strpos($host, 'infinityfree') !== false) {
    $base_url = "$protocol://$host/";
}
define('BASE_URL', $base_url);
?>
