<?php
/**
 * MBO Cinemas - Centrale Configuratie
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Database configuratie
define('DB_HOST', getenv('DB_HOST') ?: 'db');
define('DB_NAME', getenv('DB_NAME') ?: 'phples');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') ?: 'supergeheim');
define('DB_PORT', getenv('DB_PORT') ?: '3306');

// Algemene instellingen
define('SITE_NAME', 'MBO Cinemas');
define('TICKET_PRICE', 11.50);

// Autoloader voor classes
spl_autoload_register(function ($class_name) {
    $file = __DIR__ . '/classes/' . $class_name . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});

// Helper functies voor veiligheid en gemak
function sanitize($data) {
    return htmlspecialchars(trim((string)$data), ENT_QUOTES, 'UTF-8');
}

function redirect($url) {
    header("Location: " . $url);
    exit;
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function requireLogin() {
    if (!isLoggedIn()) {
        redirect('/login.php');
    }
}
