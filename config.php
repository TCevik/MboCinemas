<?php
// Start een PHP-sessie als er nog geen sessie actief is (nodig voor inloggen en gebruikersgegevens)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Haal database-instellingen op uit omgevingsvariabelen (handig voor Docker), met standaardwaarden als fallback
$dbHost = getenv('DB_HOST');
if (!$dbHost) {
    $dbHost = 'db'; // Servicenaam van de MySQL-container in docker-compose.yml
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

// Definieer globale constanten voor de databaseverbinding en website-instellingen
define('DB_HOST', $dbHost);
define('DB_NAME', $dbName);
define('DB_USER', $dbUser);
define('DB_PASS', $dbPass);
define('DB_PORT', $dbPort);

define('SITE_NAME', 'MBO Cinemas');
define('TICKET_PRICE', 11.50); // Vaste ticketprijs in euro's

// Autoloader: laadt automatisch class-bestanden uit de map /classes/ zodra een class wordt aangeroepen
spl_autoload_register(function ($class_name) {
    $file = __DIR__ . '/classes/' . $class_name . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});

// Hulpfunctie om gebruikersinvoer veilig te maken tegen XSS (Cross-Site Scripting)
function sanitize($data) {
    return htmlspecialchars(trim((string)$data), ENT_QUOTES, 'UTF-8');
}

// Hulpfunctie om de gebruiker door te sturen naar een andere pagina
function redirect($url) {
    header("Location: " . $url);
    exit;
}

// Controleert of er momenteel een gebruiker is ingelogd via de sessie
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Beveiligt pagina's: als de gebruiker niet is ingelogd, stuur door naar de inlogpagina
function requireLogin() {
    if (!isLoggedIn()) {
        redirect('/login.php');
    }
}
