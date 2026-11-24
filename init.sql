-- Maak de database aan (indien nog niet bestaand) en selecteer deze
CREATE DATABASE IF NOT EXISTS phples CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE phples;

-- Tabel voor de verschillende bioscooplocaties
CREATE TABLE IF NOT EXISTS locations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    city VARCHAR(100) NOT NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS halls (
    id INT AUTO_INCREMENT PRIMARY KEY,
    location_id INT NOT NULL,
    name VARCHAR(50) NOT NULL,
    capacity INT NOT NULL DEFAULT 60,
    FOREIGN KEY (location_id) REFERENCES locations(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS movies (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(150) NOT NULL,
    description TEXT NOT NULL,
    genre VARCHAR(50) NOT NULL,
    duration_minutes INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS screenings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    movie_id INT NOT NULL,
    hall_id INT NOT NULL,
    screening_time DATETIME NOT NULL,
    FOREIGN KEY (movie_id) REFERENCES movies(id) ON DELETE CASCADE,
    FOREIGN KEY (hall_id) REFERENCES halls(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS reservations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    reservation_code VARCHAR(12) NOT NULL UNIQUE,
    screening_id INT NOT NULL,
    customer_name VARCHAR(100) NOT NULL,
    customer_email VARCHAR(150) NOT NULL,
    customer_phone VARCHAR(20) NOT NULL,
    seats INT NOT NULL DEFAULT 1,
    status ENUM('actief', 'geannuleerd') DEFAULT 'actief',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (screening_id) REFERENCES screenings(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role VARCHAR(50) DEFAULT 'medewerker',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

INSERT INTO locations (id, name, city) VALUES
(1, 'MBO Cinemas Leiden', 'Leiden'),
(2, 'MBO Cinemas Gouda', 'Gouda'),
(3, 'MBO Cinemas Alphen', 'Alphen a/d Rijn')
ON DUPLICATE KEY UPDATE name=VALUES(name);

INSERT INTO halls (id, location_id, name, capacity) VALUES
(1, 1, 'Zaal 1 (Laser ULTRA)', 80),
(2, 1, 'Zaal 2 (Comfort)', 50),
(3, 2, 'Zaal 1 (Dolby Atmos)', 70),
(4, 2, 'Zaal 2 (Classic)', 45),
(5, 3, 'Zaal 1 (Main Stage)', 65)
ON DUPLICATE KEY UPDATE name=VALUES(name);

INSERT INTO movies (id, title, description, genre, duration_minutes) VALUES
(1, 'Dune: Part Two', 'Paul Atreides verenigt zich met Chani en de Vrijmans terwijl hij zint op wraak tegen de samenzweerders die zijn familie hebben vernietigd.', 'Sci-Fi', 166),
(2, 'Inside Out 2', 'Disney en Pixar keren terug naar het hoofd van tiener Riley, net wanneer het hoofdkwartier een plotselinge sloop ondergaat om ruimte te maken voor nieuwe emoties.', 'Animatie', 96),
(3, 'Gladiator II', 'Jaren nadat hij getuige was van de dood van Maximus, moet Lucius zelf het Colosseum betreden nadat zijn thuis is veroverd door tirannieke keizers.', 'Actie', 148),
(4, 'Oppenheimer', 'Het verhaal van de Amerikaanse wetenschapper J. Robert Oppenheimer en zijn rol in de ontwikkeling van de atoombom.', 'Drama', 180),
(5, 'Spider-Man: Across the Spider-Verse', 'Miles Morales wordt door het multiversum geslingerd waar hij een team van Spider-Mensen tegenkomt die belast zijn met het beschermen van het bestaan ervan.', 'Animatie', 140)
ON DUPLICATE KEY UPDATE title=VALUES(title);

INSERT INTO screenings (id, movie_id, hall_id, screening_time) VALUES
(1, 1, 1, DATE_ADD(CURRENT_DATE, INTERVAL '19:00:00' HOUR_SECOND)),
(2, 1, 3, DATE_ADD(CURRENT_DATE, INTERVAL '20:30:00' HOUR_SECOND)),
(3, 2, 2, DATE_ADD(CURRENT_DATE, INTERVAL '15:30:00' HOUR_SECOND)),
(4, 2, 4, DATE_ADD(CURRENT_DATE, INTERVAL '16:00:00' HOUR_SECOND)),
(5, 3, 1, DATE_ADD(DATE_ADD(CURRENT_DATE, INTERVAL 1 DAY), INTERVAL '20:00:00' HOUR_SECOND)),
(6, 4, 3, DATE_ADD(DATE_ADD(CURRENT_DATE, INTERVAL 1 DAY), INTERVAL '19:15:00' HOUR_SECOND)),
(7, 5, 2, DATE_ADD(DATE_ADD(CURRENT_DATE, INTERVAL 2 DAY), INTERVAL '14:30:00' HOUR_SECOND))
ON DUPLICATE KEY UPDATE screening_time=VALUES(screening_time);

INSERT INTO users (id, name, email, password, role) VALUES
(1, 'Bioscoop Medewerker', 'admin@mbocinemas.nl', '$2y$10$si/kXOh4.L6qN3FZsLKXjugLDmKGbrF6VQvOxsVAQVz0poY1KzxhG', 'medewerker')
ON DUPLICATE KEY UPDATE email=VALUES(email);
