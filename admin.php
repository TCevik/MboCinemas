<?php
require_once __DIR__ . '/config.php';
requireLogin();

$database = new Database();
$db = $database->getConnection();
$movieModel = new Movie($db);
$reservationModel = new Reservation($db);
$userModel = new User($db);

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = '';
    if (isset($_POST['action'])) {
        $action = $_POST['action'];
    }

    if ($action === 'add_movie' || $action === 'update_movie') {
        $title = '';
        if (isset($_POST['title'])) {
            $title = trim($_POST['title']);
        }
        $description = '';
        if (isset($_POST['description'])) {
            $description = trim($_POST['description']);
        }
        $genre = '';
        if (isset($_POST['genre'])) {
            $genre = trim($_POST['genre']);
        }
        $duration = 0;
        if (isset($_POST['duration_minutes'])) {
            $duration = (int)$_POST['duration_minutes'];
        }

        if ($action === 'add_movie') {
            if ($title !== '' && $description !== '' && $genre !== '' && $duration > 0) {
                $movieModel->addMovie($title, $description, $genre, $duration);
                $message = 'Film toegevoegd.';
            } else {
                $error = 'Vul alle verplichte filmvelden in.';
            }
        } else {
            $movieId = 0;
            if (isset($_POST['movie_id'])) {
                $movieId = $_POST['movie_id'];
            }
            if ($movieId > 0 && $title !== '' && $description !== '' && $genre !== '' && $duration > 0) {
                $movieModel->updateMovie($movieId, $title, $description, $genre, $duration);
                $message = 'Film bijgewerkt.';
            } else {
                $error = 'Ongeldige filmgegevens.';
            }
        }
    } elseif ($action === 'delete_movie') {
        $movieId = 0;
        if (isset($_POST['movie_id'])) {
            $movieId = $_POST['movie_id'];
        }
        if ($movieId > 0) {
            $movieModel->deleteMovie($movieId);
            $message = 'Film verwijderd.';
        }
    } elseif ($action === 'add_screening') {
        $movieId = 0;
        if (isset($_POST['movie_id'])) {
            $movieId = $_POST['movie_id'];
        }
        $hallId = 0;
        if (isset($_POST['hall_id'])) {
            $hallId = $_POST['hall_id'];
        }
        $screeningTime = '';
        if (isset($_POST['screening_time'])) {
            $screeningTime = trim($_POST['screening_time']);
        }

        if ($movieId > 0 && $hallId > 0 && $screeningTime !== '') {
            $movieModel->addScreening($movieId, $hallId, str_replace('T', ' ', $screeningTime));
            $message = 'Voorstelling ingepland.';
        } else {
            $error = 'Vul alle velden voor de voorstelling in.';
        }
    } elseif ($action === 'delete_screening') {
        $screeningId = 0;
        if (isset($_POST['screening_id'])) {
            $screeningId = $_POST['screening_id'];
        }
        if ($screeningId > 0) {
            $movieModel->deleteScreening($screeningId);
            $message = 'Voorstelling verwijderd.';
        }
    } elseif ($action === 'update_reservation') {
        $reservationId = 0;
        if (isset($_POST['reservation_id'])) {
            $reservationId = $_POST['reservation_id'];
        }
        $seats = 0;
        if (isset($_POST['seats'])) {
            $seats = (int)$_POST['seats'];
        }
        if ($reservationId > 0 && $seats >= 1 && $seats <= 20) {
            $reservationModel->updateSeats($reservationId, $seats);
            $message = 'Reservering bijgewerkt.';
        } else {
            $error = 'Ongeldig aantal kaartjes.';
        }
    } elseif ($action === 'cancel_reservation') {
        $reservationId = 0;
        if (isset($_POST['reservation_id'])) {
            $reservationId = $_POST['reservation_id'];
        }
        if ($reservationId > 0) {
            $reservationModel->cancel($reservationId);
            $message = 'Reservering geannuleerd.';
        }
    } elseif ($action === 'update_account') {
        $name = '';
        if (isset($_POST['name'])) {
            $name = trim($_POST['name']);
        }
        $email = '';
        if (isset($_POST['email'])) {
            $email = trim($_POST['email']);
        }
        $newPassword = '';
        if (isset($_POST['new_password'])) {
            $newPassword = $_POST['new_password'];
        }

        if ($name !== '' && filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $userModel->updateAccount($_SESSION['user_id'], $name, $email, $newPassword);
            $_SESSION['user_name'] = $name;
            $message = 'Accountgegevens bijgewerkt.';
        } else {
            $error = 'Vul een geldige naam en e-mailadres in.';
        }
    }
}

$selectedLocation = '';
if (isset($_GET['location_id'])) {
    $selectedLocation = trim($_GET['location_id']);
}

$editMovieId = 0;
if (isset($_GET['edit_movie'])) {
    $editMovieId = $_GET['edit_movie'];
}

$editMovie = null;
if ($editMovieId > 0) {
    $editMovie = $movieModel->getMovieById($editMovieId);
}

$movieFormAction = 'add_movie';
$movieFormButton = 'Film Toevoegen';
$editTitle = '';
$editGenre = '';
$editDuration = 120;
$editDescription = '';

if ($editMovie) {
    $movieFormAction = 'update_movie';
    $movieFormButton = 'Film Bijwerken';
    $editTitle = $editMovie['title'];
    $editGenre = $editMovie['genre'];
    $editDuration = $editMovie['duration_minutes'];
    $editDescription = $editMovie['description'];
}

$movies = $movieModel->getAllMovies();
$locations = $movieModel->getLocations();
$halls = $movieModel->getHalls();
$screenings = $movieModel->getFilteredScreenings('', '', $selectedLocation, '');
$reservations = $reservationModel->getAllByLocation($selectedLocation);
$currentUser = $userModel->getById($_SESSION['user_id']);

require_once __DIR__ . '/includes/header.php';
?>

<section class="page-header">
    <h1>Medewerker Beheer</h1>
    <p>Ingelogd als: <strong><?php echo sanitize($_SESSION['user_name']); ?></strong></p>
</section>

<?php if ($message !== ''): ?>
    <div class="alert alert-success"><?php echo sanitize($message); ?></div>
<?php endif; ?>
<?php if ($error !== ''): ?>
    <div class="alert alert-danger"><?php echo sanitize($error); ?></div>
<?php endif; ?>

<section class="admin-section">
    <h2>1. Films Beheren</h2>
    <form method="POST" action="/admin.php" class="card form-card">
        <input type="hidden" name="action" value="<?php echo $movieFormAction; ?>">
        <?php if ($editMovie): ?>
            <input type="hidden" name="movie_id" value="<?php echo $editMovie['id']; ?>">
        <?php endif; ?>

        <div class="form-group">
            <label for="title">Titel</label>
            <input type="text" id="title" name="title" value="<?php echo sanitize($editTitle); ?>" required>
        </div>
        <div class="form-group">
            <label for="genre">Genre</label>
            <input type="text" id="genre" name="genre" value="<?php echo sanitize($editGenre); ?>" required>
        </div>
        <div class="form-group">
            <label for="duration_minutes">Duur (minuten)</label>
            <input type="number" id="duration_minutes" name="duration_minutes" min="1" value="<?php echo $editDuration; ?>" required>
        </div>
        <div class="form-group">
            <label for="description">Beschrijving</label>
            <textarea id="description" name="description" rows="3" required><?php echo sanitize($editDescription); ?></textarea>
        </div>
        <button type="submit" class="btn"><?php echo $movieFormButton; ?></button>
        <?php if ($editMovie): ?>
            <a href="/admin.php" class="btn btn-secondary">Annuleren</a>
        <?php endif; ?>
    </form>

    <table class="data-table">
        <thead>
            <tr>
                <th>Titel</th>
                <th>Genre</th>
                <th>Duur</th>
                <th>Acties</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($movies as $movie): ?>
                <tr>
                    <td><?php echo sanitize($movie['title']); ?></td>
                    <td><?php echo sanitize($movie['genre']); ?></td>
                    <td><?php echo $movie['duration_minutes']; ?> min</td>
                    <td>
                        <a href="/admin.php?edit_movie=<?php echo $movie['id']; ?>" class="btn btn-small">Wijzig</a>
                        <form method="POST" action="/admin.php" class="inline-form confirm-cancel">
                            <input type="hidden" name="action" value="delete_movie">
                            <input type="hidden" name="movie_id" value="<?php echo $movie['id']; ?>">
                            <button type="submit" class="btn btn-small btn-danger">Verwijder</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</section>

<section class="admin-section">
    <h2>2. Voorstellingen Inplannen &amp; Zaalbezetting</h2>
    <form method="POST" action="/admin.php" class="card form-card">
        <input type="hidden" name="action" value="add_screening">
        <div class="form-group">
            <label for="screening_movie_id">Film</label>
            <select id="screening_movie_id" name="movie_id" required>
                <?php foreach ($movies as $movie): ?>
                    <option value="<?php echo $movie['id']; ?>"><?php echo sanitize($movie['title']); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label for="screening_hall_id">Locatie &amp; Zaal</label>
            <select id="screening_hall_id" name="hall_id" required>
                <?php foreach ($halls as $hall): ?>
                    <option value="<?php echo $hall['id']; ?>">
                        <?php echo sanitize($hall['location_name']); ?> - <?php echo sanitize($hall['name']); ?> (<?php echo $hall['capacity']; ?> stoelen)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label for="screening_time">Datum &amp; Tijd</label>
            <input type="datetime-local" id="screening_time" name="screening_time" required>
        </div>
        <button type="submit" class="btn">Voorstelling Inplannen</button>
    </form>

    <table class="data-table">
        <thead>
            <tr>
                <th>Film</th>
                <th>Locatie &amp; Zaal</th>
                <th>Datum &amp; Tijd</th>
                <th>Bezetting</th>
                <th>Actie</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($screenings as $screening): ?>
                <?php $formattedDateTime = date('d-m-Y H:i', strtotime($screening['screening_time'])); ?>
                <tr>
                    <td><?php echo sanitize($screening['title']); ?></td>
                    <td><?php echo sanitize($screening['location_name']); ?> (<?php echo sanitize($screening['hall_name']); ?>)</td>
                    <td><?php echo $formattedDateTime; ?></td>
                    <td><?php echo $screening['booked_seats']; ?> / <?php echo $screening['capacity']; ?></td>
                    <td>
                        <form method="POST" action="/admin.php" class="inline-form confirm-cancel">
                            <input type="hidden" name="action" value="delete_screening">
                            <input type="hidden" name="screening_id" value="<?php echo $screening['screening_id']; ?>">
                            <button type="submit" class="btn btn-small btn-danger">Verwijder</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</section>

<section class="admin-section">
    <h2>3. Reserveringen per Bioscooplocatie</h2>
    <form method="GET" action="/admin.php" class="filter-form">
        <select name="location_id">
            <option value="">Alle locaties</option>
            <?php foreach ($locations as $location): ?>
                <?php
                $selected = '';
                if ((string)$selectedLocation === (string)$location['id']) {
                    $selected = 'selected';
                }
                ?>
                <option value="<?php echo $location['id']; ?>" <?php echo $selected; ?>>
                    <?php echo sanitize($location['name']); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <button type="submit" class="btn">Filter</button>
    </form>

    <table class="data-table">
        <thead>
            <tr>
                <th>Code</th>
                <th>Klant</th>
                <th>Film &amp; Tijd</th>
                <th>Locatie &amp; Zaal</th>
                <th>Kaartjes</th>
                <th>Status</th>
                <th>Acties</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($reservations as $reservation): ?>
                <?php
                $formattedDateTime = date('d-m-Y H:i', strtotime($reservation['screening_time']));
                $badgeClass = 'badge-danger';
                if ($reservation['status'] === 'actief') {
                    $badgeClass = 'badge-success';
                }
                ?>
                <tr>
                    <td><?php echo sanitize($reservation['reservation_code']); ?></td>
                    <td><?php echo sanitize($reservation['customer_name']); ?><br><small><?php echo sanitize($reservation['customer_email']); ?></small></td>
                    <td><?php echo sanitize($reservation['movie_title']); ?><br><small><?php echo $formattedDateTime; ?></small></td>
                    <td><?php echo sanitize($reservation['location_name']); ?> (<?php echo sanitize($reservation['hall_name']); ?>)</td>
                    <td><?php echo $reservation['seats']; ?></td>
                    <td>
                        <span class="badge <?php echo $badgeClass; ?>">
                            <?php echo sanitize($reservation['status']); ?>
                        </span>
                    </td>
                    <td>
                        <?php if ($reservation['status'] === 'actief'): ?>
                            <form method="POST" action="/admin.php" class="inline-form">
                                <input type="hidden" name="action" value="update_reservation">
                                <input type="hidden" name="reservation_id" value="<?php echo $reservation['id']; ?>">
                                <input type="number" name="seats" value="<?php echo $reservation['seats']; ?>" min="1" max="20" class="input-small">
                                <button type="submit" class="btn btn-small">Wijzig</button>
                            </form>
                            <form method="POST" action="/admin.php" class="inline-form confirm-cancel">
                                <input type="hidden" name="action" value="cancel_reservation">
                                <input type="hidden" name="reservation_id" value="<?php echo $reservation['id']; ?>">
                                <button type="submit" class="btn btn-small btn-danger">Annuleer</button>
                            </form>
                        <?php else: ?>
                            -
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</section>

<section class="admin-section">
    <h2>4. Eigen Account Beheren</h2>
    <form method="POST" action="/admin.php" class="card form-card">
        <input type="hidden" name="action" value="update_account">
        <div class="form-group">
            <label for="acc_name">Naam</label>
            <input type="text" id="acc_name" name="name" value="<?php echo sanitize($currentUser['name']); ?>" required>
        </div>
        <div class="form-group">
            <label for="acc_email">E-mailadres</label>
            <input type="email" id="acc_email" name="email" value="<?php echo sanitize($currentUser['email']); ?>" required>
        </div>
        <div class="form-group">
            <label for="acc_password">Nieuw wachtwoord (laat leeg om te behouden)</label>
            <input type="password" id="acc_password" name="new_password">
        </div>
        <button type="submit" class="btn">Account Opslaan</button>
    </form>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
