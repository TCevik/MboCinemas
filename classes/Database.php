<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

define('SITE_NAME', 'MBO Cinemas');
define('TICKET_PRICE', 11.50);

spl_autoload_register(function ($className) {
    $file = __DIR__ . '/' . $className . '.php';
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

class Database {
    private $pdo;

    public function __construct() {
        $host = getenv('DB_HOST');
        if (!$host) {
            $host = 'db';
        }

        $dbname = getenv('DB_NAME');
        if (!$dbname) {
            $dbname = 'phples';
        }

        $user = getenv('DB_USER');
        if (!$user) {
            $user = 'root';
        }

        $pass = getenv('DB_PASS');
        if (!$pass) {
            $pass = 'supergeheim';
        }

        $port = getenv('DB_PORT');
        if (!$port) {
            $port = '3306';
        }

        $dsn = "mysql:host=" . $host . ";port=" . $port . ";dbname=" . $dbname . ";charset=utf8mb4";
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ];
        $this->pdo = new PDO($dsn, $user, $pass, $options);
    }

    public function getConnection() {
        return $this->pdo;
    }
}
