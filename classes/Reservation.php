<?php
/**
 * Reservation Class
 * Beheer van klantreserveringen
 */
class Reservation {
    private PDO $db;

    public function __construct() {
        $this->db = Database::getConnection();
    }

    /**
     * Unieke reserveringscode genereren (bijv. MBO-8K42)
     */
    private function generateCode(): string {
        $chars = '23456789ABCDEFGHJKLMNPQRSTUVWXYZ';
        $random = '';
        for ($i = 0; $i < 6; $i++) {
            $random .= $chars[random_int(0, strlen($chars) - 1)];
        }
        return 'MBO-' . $random;
    }

    /**
     * Nieuwe reservering aanmaken
     */
    public function create(int $screening_id, string $name, string $email, string $phone, int $seats): array {
        if ($seats <= 0) {
            return ['success' => false, 'message' => 'Aantal kaartjes moet minimaal 1 zijn.'];
        }

        $movieObj = new Movie();
        $available = $movieObj->getAvailableSeats($screening_id);

        if ($seats > $available) {
            return ['success' => false, 'message' => "Er zijn nog slechts {$available} plaatsen beschikbaar."];
        }

        $code = $this->generateCode();

        $stmt = $this->db->prepare(
            "INSERT INTO reservations (reservation_code, screening_id, customer_name, customer_email, customer_phone, seats, status)
             VALUES (:code, :screening_id, :name, :email, :phone, :seats, 'actief')"
        );

        $success = $stmt->execute([
            ':code' => $code,
            ':screening_id' => $screening_id,
            ':name' => $name,
            ':email' => $email,
            ':phone' => $phone,
            ':seats' => $seats
        ]);

        if ($success) {
            return ['success' => true, 'code' => $code];
        }

        return ['success' => false, 'message' => 'Kon reservering niet voltooien. Probeer het opnieuw.'];
    }

    /**
     * Reservering zoeken op unieke code
     */
    public function getByCode(string $code): ?array {
        $stmt = $this->db->prepare(
            "SELECT r.*, s.screening_time, m.title as movie_title, m.duration_minutes, m.poster_url, 
                    h.name as hall_name, h.capacity, l.name as location_name, l.city
             FROM reservations r
             JOIN screenings s ON r.screening_id = s.id
             JOIN movies m ON s.movie_id = m.id
             JOIN halls h ON s.hall_id = h.id
             JOIN locations l ON h.location_id = l.id
             WHERE r.reservation_code = :code"
        );
        $stmt->execute([':code' => strtoupper(trim($code))]);
        $res = $stmt->fetch();
        return $res ?: null;
    }

    /**
     * Reserveringen zoeken op e-mailadres
     */
    public function getByEmail(string $email): array {
        $stmt = $this->db->prepare(
            "SELECT r.*, s.screening_time, m.title as movie_title, 
                    h.name as hall_name, l.name as location_name, l.city
             FROM reservations r
             JOIN screenings s ON r.screening_id = s.id
             JOIN movies m ON s.movie_id = m.id
             JOIN halls h ON s.hall_id = h.id
             JOIN locations l ON h.location_id = l.id
             WHERE r.customer_email = :email
             ORDER BY r.created_at DESC"
        );
        $stmt->execute([':email' => strtolower(trim($email))]);
        return $stmt->fetchAll();
    }

    /**
     * Aantal kaartjes van een reservering wijzigen
     */
    public function updateSeats(int $id, int $newSeats): array {
        if ($newSeats <= 0) {
            return ['success' => false, 'message' => 'Aantal kaartjes moet minimaal 1 zijn.'];
        }

        $stmt = $this->db->prepare("SELECT screening_id, seats, status FROM reservations WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $current = $stmt->fetch();

        if (!$current) {
            return ['success' => false, 'message' => 'Reservering niet gevonden.'];
        }

        if ($current['status'] === 'geannuleerd') {
            return ['success' => false, 'message' => 'Een geannuleerde reservering kan niet worden gewijzigd.'];
        }

        $screeningObj = new Screening();
        $available = $screeningObj->getAvailableSeats($current['screening_id']);
        $diff = $newSeats - $current['seats'];

        if ($diff > 0 && $diff > $available) {
            return ['success' => false, 'message' => "Er zijn niet genoeg stoelen vrij. Slechts {$available} extra stoelen beschikbaar."];
        }

        $stmtUpdate = $this->db->prepare("UPDATE reservations SET seats = :seats WHERE id = :id");
        $stmtUpdate->execute([':seats' => $newSeats, ':id' => $id]);

        return ['success' => true, 'message' => 'Aantal kaartjes succesvol aangepast.'];
    }

    /**
     * Reservering annuleren
     */
    public function cancel(int $id): bool {
        $stmt = $this->db->prepare("UPDATE reservations SET status = 'geannuleerd' WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }

    /**
     * Alle reserveringen ophalen, optioneel gefilterd per bioscoop (voor medewerkers)
     */
    public function getAllByLocation(?int $location_id = null): array {
        $sql = "SELECT r.*, s.screening_time, m.title as movie_title, 
                       h.name as hall_name, h.capacity, l.name as location_name, l.city, l.id as location_id
                FROM reservations r
                JOIN screenings s ON r.screening_id = s.id
                JOIN movies m ON s.movie_id = m.id
                JOIN halls h ON s.hall_id = h.id
                JOIN locations l ON h.location_id = l.id";
        
        $params = [];
        if ($location_id) {
            $sql .= " WHERE l.id = :location_id";
            $params[':location_id'] = $location_id;
        }

        $sql .= " ORDER BY r.created_at DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
}
