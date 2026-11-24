<?php
require_once __DIR__ . '/../config.php';
requireLogin();

$movieModel = new Movie();

$message = null;
$messageType = 'success';

// Vertoning inplannen
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create_screening') {
    $movieId = (int)($_POST['movie_id'] ?? 0);
    $hallId = (int)($_POST['hall_id'] ?? 0);
    $time = trim($_POST['screening_time'] ?? '');

    if ($movieId <= 0 || $hallId <= 0 || empty($time)) {
        $message = "Vul alle velden in om een voorstelling in te plannen.";
        $messageType = "danger";
    } else {
        if ($movieModel->createScreening($movieId, $hallId, $time)) {
            $message = "Voorstelling succesvol ingepland in de gekozen zaal!";
        } else {
            $message = "Kon voorstelling niet inplannen.";
            $messageType = "danger";
        }
    }
}

// Vertoning annuleren / verwijderen
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_screening') {
    $id = (int)$_POST['screening_id'];
    if ($movieModel->deleteScreening($id)) {
        $message = "Voorstelling succesvol verwijderd.";
    } else {
        $message = "Kon voorstelling niet verwijderen.";
        $messageType = "danger";
    }
}

$movies = $movieModel->getAll();
$halls = $movieModel->getHalls();
$screenings = $movieModel->getAllScreeningsWithDetails();

$pageTitle = "Zaalplanning";
require_once __DIR__ . '/../includes/header.php';
?>

<div class="admin-header">
    <div>
        <h1 style="font-size: 2rem;">Zaalplanning & Voorstellingen</h1>
        <p style="color: var(--text-muted);">Plan films in specifieke zalen van MBO Cinemas vestigingen op datum en tijd.</p>
    </div>
</div>

<div class="admin-nav">
    <a href="/admin/index.php" class="btn btn-sm btn-secondary">Reserveringen &amp; Zalen</a>
    <a href="/admin/movies.php" class="btn btn-sm btn-secondary">Films Beheren</a>
    <a href="/admin/screenings.php" class="btn btn-sm">Zaalplanning</a>
</div>

<?php if ($message): ?>
    <div class="alert alert-<?= $messageType ?>">
        <?= sanitize($message) ?>
    </div>
<?php endif; ?>

<!-- Formulier om voorstelling te plannen -->
<div class="box-card">
    <h2 style="font-size: 1.3rem; margin-bottom: 20px;">📅 Nieuwe Voorstelling Inplannen</h2>

    <form method="POST" action="/admin/screenings.php">
        <input type="hidden" name="action" value="create_screening">

        <div style="display: grid; grid-template-columns: 1fr 1fr 1fr auto; gap: 15px; align-items: end;">
            <div class="form-group">
                <label for="movie_id">Kies Film *</label>
                <select name="movie_id" id="movie_id" class="form-control" required>
                    <option value="">-- Selecteer film --</option>
                    <?php foreach ($movies as $m): ?>
                        <option value="<?= $m['id'] ?>"><?= sanitize($m['title']) ?> (<?= (int)$m['duration_minutes'] ?> min)</option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="hall_id">Kies Zaal & Locatie *</label>
                <select name="hall_id" id="hall_id" class="form-control" required>
                    <option value="">-- Selecteer zaal --</option>
                    <?php foreach ($halls as $h): ?>
                        <option value="<?= $h['id'] ?>">
                            <?= sanitize($h['location_name']) ?> &bull; <?= sanitize($h['name']) ?> (<?= (int)$h['capacity'] ?> stoelen)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="screening_time">Datum en Aanvangstijd *</label>
                <input type="datetime-local" name="screening_time" id="screening_time" class="form-control" required>
            </div>

            <button type="submit" class="btn" style="height: 42px;">Inplannen</button>
        </div>
    </form>
</div>

<!-- Overzicht van geplande voorstellingen -->
<h2 style="font-size: 1.4rem; margin-bottom: 15px;">Actuele Voorstellingen (<?= count($screenings) ?>)</h2>
<div class="table-responsive">
    <table>
        <thead>
            <tr>
                <th>Datum & Tijd</th>
                <th>Film</th>
                <th>Locatie & Zaal</th>
                <th>Capaciteit</th>
                <th>Geboekt</th>
                <th>Acties</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($screenings)): ?>
                <tr><td colspan="6" style="text-align: center; color: var(--text-muted);">Er zijn nog geen voorstellingen ingepland.</td></tr>
            <?php else: ?>
                <?php foreach ($screenings as $s): 
                    $time = new DateTime($s['screening_time']);
                ?>
                    <tr>
                        <td><strong><?= $time->format('d-m-Y H:i') ?></strong></td>
                        <td><strong><?= sanitize($s['movie_title']) ?></strong></td>
                        <td>
                            <?= sanitize($s['location_name']) ?> (<?= sanitize($s['city']) ?>)<br>
                            <small style="color: var(--text-muted);"><?= sanitize($s['hall_name']) ?></small>
                        </td>
                        <td><?= (int)$s['capacity'] ?> plaatsen</td>
                        <td>
                            <span class="badge <?= ((int)$s['booked_seats'] > 0) ? 'badge-success' : '' ?>">
                                <?= (int)$s['booked_seats'] ?> gereserveerd
                            </span>
                        </td>
                        <td>
                            <form method="POST" action="/admin/screenings.php" onsubmit="return confirm('Weet je zeker dat je deze voorstelling wilt verwijderen?');">
                                <input type="hidden" name="action" value="delete_screening">
                                <input type="hidden" name="screening_id" value="<?= $s['id'] ?>">
                                <button type="submit" class="btn btn-sm btn-danger">Verwijderen</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
