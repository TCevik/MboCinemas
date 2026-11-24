<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$dbHost = getenv('DB_HOST');
if (!$dbHost) {
    $dbHost = 'db';
}
$dbName = getenv('DB_NAME');
if (!$dbName) {
    $dbName = 'phples';
}
$dbUser = getenv('DB_USER');
if (!$dbUser) {
    $dbUser = 'root';
}
$dbPass = getenv('DB_PASS');
if (!$dbPass) {
    $dbPass = 'supergeheim';
}
$dbPort = getenv('DB_PORT');
if (!$dbPort) {
    $dbPort = '3306';
}

define('DB_HOST', $dbHost);
define('DB_NAME', $dbName);
define('DB_USER', $dbUser);
define('DB_PASS', $dbPass);
define('DB_PORT', $dbPort);

define('SITE_NAME', 'MBO Cinemas');
define('TICKET_PRICE', 11.50);

spl_autoload_register(function ($class_name) {
    $file = __DIR__ . '/classes/' . $class_name . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});

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
