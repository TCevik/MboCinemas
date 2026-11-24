<?php
/**
 * Reservation class: beheert alle database-acties voor reserveringen (aanmaken, opvragen, wijzigen, annuleren).
 */
class Reservation {
    private $db;

    public function __construct($pdo) {
        $this->db = $pdo;
    }

    // Maakt een nieuwe reservering aan met een unieke 8-tekens reserveringscode
    public function create($screeningId, $name, $email, $phone, $seats) {
        // Genereer een willekeurige hoofdlettercode van 8 tekens (bijv. A3F9C1B2)
        $code = strtoupper(substr(md5(uniqid((string)mt_rand(), true)), 0, 8));
        $stmt = $this->db->prepare("INSERT INTO reservations (reservation_code, screening_id, customer_name, customer_email, customer_phone, seats, status) VALUES (?, ?, ?, ?, ?, ?, 'actief')");
        $stmt->execute([$code, (int)$screeningId, $name, $email, $phone, (int)$seats]);
        return $code;
    }

    // Haalt alle reserveringen op die horen bij een bepaald e-mailadres
    public function getByEmail($email) {
        $sql = "SELECT r.*, s.screening_time, m.title AS movie_title, h.name AS hall_name, h.capacity, l.name AS location_name
                FROM reservations r
                JOIN screenings s ON r.screening_id = s.id
                JOIN movies m ON s.movie_id = m.id
                JOIN halls h ON s.hall_id = h.id
                JOIN locations l ON h.location_id = l.id
                WHERE r.customer_email = ?
                ORDER BY s.screening_time DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$email]);
        return $stmt->fetchAll();
    }

    // Haalt één reservering op via het ID
    public function getById($id) {
        $stmt = $this->db->prepare("SELECT * FROM reservations WHERE id = ?");
        $stmt->execute([(int)$id]);
        return $stmt->fetch();
    }

    // Past het aantal stoelen aan voor een actieve reservering
    public function updateSeats($id, $seats) {
        $stmt = $this->db->prepare("UPDATE reservations SET seats = ? WHERE id = ? AND status = 'actief'");
        return $stmt->execute([(int)$seats, (int)$id]);
    }

    // Zet de status van een reservering op 'geannuleerd' (soft delete, zodat historie bewaard blijft)
    public function cancel($id) {
        $stmt = $this->db->prepare("UPDATE reservations SET status = 'geannuleerd' WHERE id = ?");
        return $stmt->execute([(int)$id]);
    }

    // Haalt alle reserveringen op voor het admin-overzicht, optioneel gefilterd op locatie
    public function getAllByLocation($locationId = '') {
        $sql = "SELECT r.*, s.screening_time, m.title AS movie_title, h.name AS hall_name, h.capacity, l.id AS location_id, l.name AS location_name
                FROM reservations r
                JOIN screenings s ON r.screening_id = s.id
                JOIN movies m ON s.movie_id = m.id
                JOIN halls h ON s.hall_id = h.id
                JOIN locations l ON h.location_id = l.id
                WHERE 1=1";
        $params = [];

        if ($locationId !== '') {
            $sql .= " AND l.id = ?";
            $params[] = (int)$locationId;
        }

        $sql .= " ORDER BY l.name ASC, s.screening_time ASC, r.created_at DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
}
