<?php
/**
 * Database class: beheert de PDO-verbinding met de MySQL-database.
 */
class Database {
    private $pdo;

    // Constructor maakt direct verbinding met de database zodra een Database-object wordt aangemaakt
    public function __construct() {
        // DSN (Data Source Name) met host, poort, databasenaam en utf8mb4 voor speciale tekens/emoji's
        $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4";
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,       // Gooi een exception bij SQL-fouten
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,  // Geef resultaten standaard terug als associatieve array
            PDO::ATTR_EMULATE_PREPARES => false                // Gebruik echte prepared statements (veiliger tegen SQL-injectie)
        ];
        $this->pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
    }

    // Geeft het actieve PDO-object terug zodat andere classes queries kunnen uitvoeren
    public function getConnection() {
        return $this->pdo;
    }
}
