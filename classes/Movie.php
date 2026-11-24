<?php
/**
 * Movie class: regelt alle database-acties rondom films, voorstellingen (screenings), zalen en locaties.
 */
class Movie {
    private $db;

    // Ontvangt de PDO-databaseverbinding via dependency injection
    public function __construct($pdo) {
        $this->db = $pdo;
    }

    // Haalt alle films op, alfabetisch gesorteerd op titel
    public function getAllMovies() {
        $stmt = $this->db->query("SELECT * FROM movies ORDER BY title ASC");
        return $stmt->fetchAll();
    }

    // Haalt één specifieke film op aan de hand van het ID
    public function getMovieById($id) {
        $stmt = $this->db->prepare("SELECT * FROM movies WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    // Haalt alle unieke genres op voor de filter-dropdown
    public function getGenres() {
        $stmt = $this->db->query("SELECT DISTINCT genre FROM movies ORDER BY genre ASC");
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    // Haalt alle bioscooplocaties op
    public function getLocations() {
        $stmt = $this->db->query("SELECT * FROM locations ORDER BY name ASC");
        return $stmt->fetchAll();
    }

    // Haalt alle zalen op inclusief de bijbehorende locatienaam (via JOIN)
    public function getHalls() {
        $sql = "SELECT h.*, l.name AS location_name
                FROM halls h
                JOIN locations l ON h.location_id = l.id
                ORDER BY l.name ASC, h.name ASC";
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll();
    }

    /**
     * Haalt voorstellingen op met optionele filters (zoekterm, genre, locatie, datum).
     * Berekent ook direct hoeveel stoelen er al geboekt zijn (alleen actieve reserveringen).
     */
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
                WHERE 1=1"; // 1=1 maakt het makkelijk om dynamisch AND-voorwaarden toe te voegen
        $params = [];

        // Voeg zoekfilter toe als er een zoekterm is ingevuld
        if ($search !== '') {
            $sql .= " AND (m.title LIKE ? OR m.description LIKE ?)";
            $params[] = '%' . $search . '%';
            $params[] = '%' . $search . '%';
        }
        // Filter op genre
        if ($genre !== '') {
            $sql .= " AND m.genre = ?";
            $params[] = $genre;
        }
        // Filter op locatie
        if ($locationId !== '') {
            $sql .= " AND l.id = ?";
            $params[] = $locationId;
        }
        // Filter op specifieke datum
        if ($date !== '') {
            $sql .= " AND DATE(s.screening_time) = ?";
            $params[] = $date;
        }

        // Groepeer per voorstelling (nodig voor SUM) en sorteer op tijdstip
        $sql .= " GROUP BY s.id ORDER BY s.screening_time ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    // Haalt alle details van één specifieke voorstelling op, inclusief het aantal geboekte stoelen
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

    // Voegt een nieuwe film toe aan de database (admin)
    public function addMovie($title, $description, $genre, $duration) {
        $stmt = $this->db->prepare("INSERT INTO movies (title, description, genre, duration_minutes) VALUES (?, ?, ?, ?)");
        return $stmt->execute([$title, $description, $genre, $duration]);
    }

    // Werkt de gegevens van een bestaande film bij (admin)
    public function updateMovie($id, $title, $description, $genre, $duration) {
        $stmt = $this->db->prepare("UPDATE movies SET title = ?, description = ?, genre = ?, duration_minutes = ? WHERE id = ?");
        return $stmt->execute([$title, $description, $genre, $duration, $id]);
    }

    // Verwijdert een film uit de database (admin)
    public function deleteMovie($id) {
        $stmt = $this->db->prepare("DELETE FROM movies WHERE id = ?");
        return $stmt->execute([$id]);
    }

    // Plant een nieuwe voorstelling in voor een film en zaal (admin)
    public function addScreening($movieId, $hallId, $screeningTime) {
        $stmt = $this->db->prepare("INSERT INTO screenings (movie_id, hall_id, screening_time) VALUES (?, ?, ?)");
        return $stmt->execute([$movieId, $hallId, $screeningTime]);
    }

    // Verwijdert een geplande voorstelling (admin)
    public function deleteScreening($id) {
        $stmt = $this->db->prepare("DELETE FROM screenings WHERE id = ?");
        return $stmt->execute([$id]);
    }
}
