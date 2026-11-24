<?php
class Movie {
    private PDO $db;

    public function __construct() {
        $this->db = Database::getConnection();
    }

    public function getAll(?string $genre = null, ?int $location_id = null, ?string $date = null, ?string $search = null): array {
        $sql = "SELECT DISTINCT m.* FROM movies m";
        $joins = [];
        $conditions = [];
        $params = [];

        if ($location_id || $date) {
            $joins[] = "JOIN screenings s ON m.id = s.movie_id";
            if ($location_id) {
                $joins[] = "JOIN halls h ON s.hall_id = h.id";
                $conditions[] = "h.location_id = :location_id";
                $params[':location_id'] = $location_id;
            }
            if ($date) {
                $conditions[] = "DATE(s.screening_time) = :date";
                $params[':date'] = $date;
            }
        }

        if ($genre) {
            $conditions[] = "m.genre = :genre";
            $params[':genre'] = $genre;
        }

        if ($search) {
            $conditions[] = "(m.title LIKE :search OR m.description LIKE :search)";
            $params[':search'] = '%' . $search . '%';
        }

        if (!empty($joins)) {
            $sql .= " " . implode(" ", array_unique($joins));
        }

        if (!empty($conditions)) {
            $sql .= " WHERE " . implode(" AND ", $conditions);
        }

        $sql .= " ORDER BY m.id DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function getById(int $id): ?array {
        $stmt = $this->db->prepare("SELECT * FROM movies WHERE id = :id");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public function getGenres(): array {
        $stmt = $this->db->query("SELECT DISTINCT genre FROM movies ORDER BY genre ASC");
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    public function create(string $title, string $description, string $genre, int $duration, ?string $poster_url): bool {
        $stmt = $this->db->prepare(
            "INSERT INTO movies (title, description, genre, duration_minutes, poster_url) VALUES (:title, :description, :genre, :duration, :poster)"
        );
        return $stmt->execute([
            ':title' => $title,
            ':description' => $description,
            ':genre' => $genre,
            ':duration' => $duration,
            ':poster' => $poster_url ?: 'https://images.unsplash.com/photo-1489599849927-2ee91cede3ba?w=500&q=80'
        ]);
    }

    public function update(int $id, string $title, string $description, string $genre, int $duration, ?string $poster_url): bool {
        $stmt = $this->db->prepare(
            "UPDATE movies SET title = :title, description = :description, genre = :genre, duration_minutes = :duration, poster_url = :poster WHERE id = :id"
        );
        return $stmt->execute([
            ':id' => $id,
            ':title' => $title,
            ':description' => $description,
            ':genre' => $genre,
            ':duration' => $duration,
            ':poster' => $poster_url
        ]);
    }

    public function delete(int $id): bool {
        $stmt = $this->db->prepare("DELETE FROM movies WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }

    public function getLocations(): array {
        $stmt = $this->db->query("SELECT * FROM locations ORDER BY city ASC");
        return $stmt->fetchAll();
    }

    public function getHalls(?int $location_id = null): array {
        if ($location_id) {
            $stmt = $this->db->prepare(
                "SELECT h.*, l.name as location_name, l.city FROM halls h JOIN locations l ON h.location_id = l.id WHERE h.location_id = :location_id ORDER BY h.name ASC"
            );
            $stmt->execute([':location_id' => $location_id]);
        } else {
            $stmt = $this->db->query(
                "SELECT h.*, l.name as location_name, l.city FROM halls h JOIN locations l ON h.location_id = l.id ORDER BY l.city, h.name ASC"
            );
        }
        return $stmt->fetchAll();
    }

    public function getScreeningsByMovie(int $movie_id): array {
        $stmt = $this->db->prepare(
            "SELECT s.*, h.name as hall_name, h.capacity, l.name as location_name, l.city 
             FROM screenings s 
             JOIN halls h ON s.hall_id = h.id 
             JOIN locations l ON h.location_id = l.id 
             WHERE s.movie_id = :movie_id AND s.screening_time >= NOW() - INTERVAL 2 HOUR
             ORDER BY s.screening_time ASC"
        );
        $stmt->execute([':movie_id' => $movie_id]);
        return $stmt->fetchAll();
    }

    public function getScreeningById(int $id): ?array {
        $stmt = $this->db->prepare(
            "SELECT s.*, m.title as movie_title, m.description as movie_description, m.genre, m.duration_minutes, m.poster_url, 
                    h.name as hall_name, h.capacity, l.name as location_name, l.city, l.id as location_id 
             FROM screenings s 
             JOIN movies m ON s.movie_id = m.id 
             JOIN halls h ON s.hall_id = h.id 
             JOIN locations l ON h.location_id = l.id 
             WHERE s.id = :id"
        );
        $stmt->execute([':id' => $id]);
        $screening = $stmt->fetch();
        if (!$screening) return null;

        $screening['available_seats'] = $this->getAvailableSeats($id, (int)$screening['capacity']);
        return $screening;
    }

    public function getAvailableSeats(int $screening_id, ?int $capacity = null): int {
        if ($capacity === null) {
            $stmtHall = $this->db->prepare("SELECT h.capacity FROM screenings s JOIN halls h ON s.hall_id = h.id WHERE s.id = :id");
            $stmtHall->execute([':id' => $screening_id]);
            $capacity = (int)$stmtHall->fetchColumn();
        }

        $stmtRes = $this->db->prepare("SELECT COALESCE(SUM(seats), 0) FROM reservations WHERE screening_id = :id AND status = 'actief'");
        $stmtRes->execute([':id' => $screening_id]);
        $booked = (int)$stmtRes->fetchColumn();

        return max(0, $capacity - $booked);
    }

    public function getAllScreeningsWithDetails(?int $location_id = null): array {
        $sql = "SELECT s.*, m.title as movie_title, h.name as hall_name, h.capacity, l.name as location_name, l.city,
                       COALESCE((SELECT SUM(r.seats) FROM reservations r WHERE r.screening_id = s.id AND r.status = 'actief'), 0) as booked_seats
                FROM screenings s
                JOIN movies m ON s.movie_id = m.id
                JOIN halls h ON s.hall_id = h.id
                JOIN locations l ON h.location_id = l.id";
        $params = [];
        if ($location_id) {
            $sql .= " WHERE l.id = :location_id";
            $params[':location_id'] = $location_id;
        }
        $sql .= " ORDER BY s.screening_time ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function createScreening(int $movie_id, int $hall_id, string $screening_time): bool {
        $stmt = $this->db->prepare(
            "INSERT INTO screenings (movie_id, hall_id, screening_time) VALUES (:movie_id, :hall_id, :time)"
        );
        return $stmt->execute([
            ':movie_id' => $movie_id,
            ':hall_id' => $hall_id,
            ':time' => $screening_time
        ]);
    }

    public function deleteScreening(int $id): bool {
        $stmt = $this->db->prepare("DELETE FROM screenings WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }
}
