<?php
class Reservation {
    private $db;

    public function __construct($pdo) {
        $this->db = $pdo;
    }

    public function create($screeningId, $name, $email, $phone, $seats) {
        $code = strtoupper(substr(md5(uniqid((string)mt_rand(), true)), 0, 8));
        $stmt = $this->db->prepare("INSERT INTO reservations (reservation_code, screening_id, customer_name, customer_email, customer_phone, seats, status) VALUES (?, ?, ?, ?, ?, ?, 'actief')");
        $stmt->execute([$code, (int)$screeningId, $name, $email, $phone, (int)$seats]);
        return $code;
    }

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

    public function getById($id) {
        $stmt = $this->db->prepare("SELECT * FROM reservations WHERE id = ?");
        $stmt->execute([(int)$id]);
        return $stmt->fetch();
    }

    public function updateSeats($id, $seats) {
        $stmt = $this->db->prepare("UPDATE reservations SET seats = ? WHERE id = ? AND status = 'actief'");
        return $stmt->execute([(int)$seats, (int)$id]);
    }

    public function cancel($id) {
        $stmt = $this->db->prepare("UPDATE reservations SET status = 'geannuleerd' WHERE id = ?");
        return $stmt->execute([(int)$id]);
    }

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
