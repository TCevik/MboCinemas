<?php
class Movie {
    private $db;

    public function __construct($pdo) {
        $this->db = $pdo;
    }

    public function getAllMovies() {
        $stmt = $this->db->query("SELECT * FROM movies ORDER BY title ASC");
        return $stmt->fetchAll();
    }

    public function getMovieById($id) {
        $stmt = $this->db->prepare("SELECT * FROM movies WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public function getGenres() {
        $stmt = $this->db->query("SELECT DISTINCT genre FROM movies ORDER BY genre ASC");
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    public function getLocations() {
        $stmt = $this->db->query("SELECT * FROM locations ORDER BY name ASC");
        return $stmt->fetchAll();
    }

    public function getHalls() {
        $sql = "SELECT h.*, l.name AS location_name
                FROM halls h
                JOIN locations l ON h.location_id = l.id
                ORDER BY l.name ASC, h.name ASC";
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll();
    }

    public function getFilteredScreenings($search = '', $genre = '', $locationId = '', $date = '') {
        $sql = "SELECT s.id AS screening_id, s.screening_time,
                       m.id AS movie_id, m.title, m.description, m.genre, m.duration_minutes,
                       h.id AS hall_id, h.name AS hall_name, h.capacity,
                       l.id AS location_id, l.name AS location_name, l.city,
                       COALESCE(SUM(CASE WHEN r.status = 'actief' THEN r.seats ELSE 0 END), 0) AS booked_seats
                FROM screenings s
                JOIN movies m ON s.movie_id = m.id
                JOIN halls h ON s.hall_id = h.id
                JOIN locations l ON h.location_id = l.id
                LEFT JOIN reservations r ON r.screening_id = s.id
                WHERE 1=1";
        $params = [];

        if ($search !== '') {
            $sql .= " AND (m.title LIKE ? OR m.description LIKE ?)";
            $params[] = '%' . $search . '%';
            $params[] = '%' . $search . '%';
        }
        if ($genre !== '') {
            $sql .= " AND m.genre = ?";
            $params[] = $genre;
        }
        if ($locationId !== '') {
            $sql .= " AND l.id = ?";
            $params[] = $locationId;
        }
        if ($date !== '') {
            $sql .= " AND DATE(s.screening_time) = ?";
            $params[] = $date;
        }

        $sql .= " GROUP BY s.id ORDER BY s.screening_time ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function getScreeningById($id) {
        $sql = "SELECT s.id AS screening_id, s.screening_time,
                       m.id AS movie_id, m.title, m.description, m.genre, m.duration_minutes,
                       h.id AS hall_id, h.name AS hall_name, h.capacity,
                       l.id AS location_id, l.name AS location_name, l.city,
                       COALESCE(SUM(CASE WHEN r.status = 'actief' THEN r.seats ELSE 0 END), 0) AS booked_seats
                FROM screenings s
                JOIN movies m ON s.movie_id = m.id
                JOIN halls h ON s.hall_id = h.id
                JOIN locations l ON h.location_id = l.id
                LEFT JOIN reservations r ON r.screening_id = s.id
                WHERE s.id = ?
                GROUP BY s.id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public function addMovie($title, $description, $genre, $duration) {
        $stmt = $this->db->prepare("INSERT INTO movies (title, description, genre, duration_minutes) VALUES (?, ?, ?, ?)");
        return $stmt->execute([$title, $description, $genre, $duration]);
    }

    public function updateMovie($id, $title, $description, $genre, $duration) {
        $stmt = $this->db->prepare("UPDATE movies SET title = ?, description = ?, genre = ?, duration_minutes = ? WHERE id = ?");
        return $stmt->execute([$title, $description, $genre, $duration, $id]);
    }

    public function deleteMovie($id) {
        $stmt = $this->db->prepare("DELETE FROM movies WHERE id = ?");
        return $stmt->execute([$id]);
    }

    public function addScreening($movieId, $hallId, $screeningTime) {
        $stmt = $this->db->prepare("INSERT INTO screenings (movie_id, hall_id, screening_time) VALUES (?, ?, ?)");
        return $stmt->execute([$movieId, $hallId, $screeningTime]);
    }

    public function deleteScreening($id) {
        $stmt = $this->db->prepare("DELETE FROM screenings WHERE id = ?");
        return $stmt->execute([$id]);
    }
}
