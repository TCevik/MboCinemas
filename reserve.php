<?php
require_once __DIR__ . '/config.php';

$movieModel = new Movie();
$reservationModel = new Reservation();

$screeningId = isset($_GET['screening_id']) ? (int)$_GET['screening_id'] : 0;
$movieId = isset($_GET['movie_id']) ? (int)$_GET['movie_id'] : 0;

$screening = $screeningId ? $movieModel->getScreeningById($screeningId) : null;
if ($screening) {
    $movieId = (int)$screening['movie_id'];
}

$movie = $movieId ? $movieModel->getById($movieId) : null;
if (!$movie) {
    redirect('/index.php');
}

$screenings = $movieModel->getScreeningsByMovie($movieId);
if (!$screening && !empty($screenings)) {
    $screening = $movieModel->getScreeningById((int)$screenings[0]['id']);
    $screeningId = (int)$screening['id'];
}

$error = null;
$confirmedReservation = null;
$availableSeats = $screening ? (int)$screening['available_seats'] : 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postScreeningId = isset($_POST['screening_id']) ? (int)$_POST['screening_id'] : $screeningId;
    $screening = $movieModel->getScreeningById($postScreeningId);

    if (!$screening) {
        $error = "Ongeldige voorstelling geselecteerd.";
    } else {
        $screeningId = (int)$screening['id'];
        $availableSeats = (int)$screening['available_seats'];

        $name = sanitize($_POST['customer_name'] ?? '');
        $email = sanitize($_POST['customer_email'] ?? '');
        $phone = sanitize($_POST['customer_phone'] ?? '');
        $seats = isset($_POST['seats']) ? (int)$_POST['seats'] : 0;

        if (empty($name) || empty($email) || empty($phone)) {
            $error = "Vul alstublieft alle contactgegevens in.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = "Vul een geldig e-mailadres in.";
        } elseif ($seats < 1) {
            $error = "Selecteer minimaal 1 kaartje.";
        } elseif ($seats > $availableSeats) {
            $error = "Er zijn nog maar {$availableSeats} plaatsen beschikbaar.";
        } else {
            $result = $reservationModel->create($screeningId, $name, $email, $phone, $seats);
            if ($result['success']) {
                setcookie('mbo_customer_data', json_encode([
                    'name' => $name,
                    'email' => $email,
                    'phone' => $phone
                ]), time() + (30 * 86400), '/', '', false, false);

                $confirmedReservation = $reservationModel->getByCode($result['code']);
            } else {
                $error = $result['message'];
            }
        }
    }
}

$pageTitle = "Reserveren - " . $movie['title'];
require_once __DIR__ . '/includes/header.php';
?>

<div style="margin: 20px 0;">
    <a href="/index.php" class="btn btn-sm btn-secondary">&larr; Terug naar filmaanbod</a>
</div>

<div style="max-width: 850px; margin: 0 auto;">
    <div class="box-card" style="margin-bottom: 24px;">
        <div style="display: flex; gap: 24px; flex-wrap: wrap; align-items: flex-start;">
            <img src="<?= sanitize($movie['poster_url'] ?: '/assets/img/default-poster.jpg') ?>"
                 alt="<?= sanitize($movie['title']) ?>"
                 style="width: 150px; height: 220px; object-fit: cover; border-radius: 8px;">
            <div style="flex: 1; min-width: 240px;">
                <div class="movie-meta" style="margin-bottom: 8px;">
                    <span class="badge"><?= sanitize($movie['genre']) ?></span>
                    <span class="badge badge-muted"><?= (int)$movie['duration_minutes'] ?> min</span>
                </div>
                <h1 style="font-size: 1.8rem; margin-bottom: 10px;"><?= sanitize($movie['title']) ?></h1>
                <p style="color: var(--text-muted); line-height: 1.6;"><?= sanitize($movie['description']) ?></p>
            </div>
        </div>
    </div>

    <?php if ($confirmedReservation): ?>
        <?php $resTime = new DateTime($confirmedReservation['screening_time']); ?>
        <div id="confirmation-box" class="box-card" data-confirmed="true">
            <div class="alert alert-success">
                Reservering bevestigd! Bewaar je reserveringscode goed.
            </div>
            <h2 style="margin-bottom: 12px;">Reserveringsbevestiging</h2>
            <p style="margin-bottom: 16px;">
                Code: <strong style="font-size: 1.3rem; color: var(--primary);"><?= sanitize($confirmedReservation['reservation_code']) ?></strong>
            </p>
            <table class="table">
                <tbody>
                    <tr><th>Film</th><td><?= sanitize($confirmedReservation['movie_title']) ?></td></tr>
                    <tr><th>Locatie</th><td><?= sanitize($confirmedReservation['location_name']) ?> (<?= sanitize($confirmedReservation['hall_name']) ?>)</td></tr>
                    <tr><th>Datum &amp; Tijd</th><td><?= $resTime->format('d-m-Y') ?> om <?= $resTime->format('H:i') ?> uur</td></tr>
                    <tr><th>Aantal kaartjes</th><td><?= (int)$confirmedReservation['seats'] ?> (&euro; <?= number_format($confirmedReservation['seats'] * TICKET_PRICE, 2, ',', '.') ?>)</td></tr>
                    <tr><th>Naam</th><td><?= sanitize($confirmedReservation['customer_name']) ?></td></tr>
                    <tr><th>E-mail</th><td><?= sanitize($confirmedReservation['customer_email']) ?></td></tr>
                    <tr><th>Telefoon</th><td><?= sanitize($confirmedReservation['customer_phone']) ?></td></tr>
                </tbody>
            </table>
            <div style="margin-top: 20px; display: flex; gap: 10px; flex-wrap: wrap;">
                <a href="/my-reservations.php?code=<?= urlencode($confirmedReservation['reservation_code']) ?>" class="btn">Bekijk in Mijn Reserveringen</a>
                <a href="/index.php" class="btn btn-secondary">Terug naar homepage</a>
            </div>
        </div>
    <?php else: ?>
        <div id="pending-reservation-banner" class="alert alert-info" style="display: none;">
            <div>
                <strong>Je hebt een eerdere niet-afgeronde reservering gevonden.</strong>
                <div style="font-size: 0.9rem;">Wil je verdergaan waar je gebleven was?</div>
            </div>
            <div style="display: flex; gap: 8px;">
                <button type="button" id="restore-draft-btn" class="btn btn-sm">Verdergaan</button>
                <button type="button" id="dismiss-draft-btn" class="btn btn-sm btn-secondary">Negeren</button>
            </div>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?= sanitize($error) ?></div>
        <?php endif; ?>

        <div class="box-card">
            <h2 style="margin-bottom: 16px;">Kies een voorstelling &amp; reserveer</h2>

            <?php if (empty($screenings)): ?>
                <p style="color: var(--text-muted);">Er zijn momenteel geen geplande voorstellingen voor deze film.</p>
            <?php else: ?>
                <div style="display: flex; flex-wrap: wrap; gap: 10px; margin-bottom: 24px;">
                    <?php foreach ($screenings as $s):
                        $sTime = new DateTime($s['screening_time']);
                        $isSelected = ((int)$s['id'] === $screeningId);
                    ?>
                        <a href="/reserve.php?movie_id=<?= $movieId ?>&screening_id=<?= (int)$s['id'] ?>"
                           class="btn btn-sm <?= $isSelected ? '' : 'btn-secondary' ?>">
                            <?= sanitize($s['location_name']) ?> &bull; <?= $sTime->format('d-m H:i') ?>
                        </a>
                    <?php endforeach; ?>
                </div>

                <?php if ($screening):
                    $time = new DateTime($screening['screening_time']);
                ?>
                    <div style="background: #0f1626; border: 1px solid var(--border); border-radius: 8px; padding: 14px; margin-bottom: 20px;">
                        <strong><?= sanitize($screening['location_name']) ?></strong> &bull; <?= sanitize($screening['hall_name']) ?><br>
                        <span><?= $time->format('d-m-Y') ?> om <?= $time->format('H:i') ?> uur</span> &bull;
                        <span class="badge badge-success"><?= $availableSeats ?> plaatsen beschikbaar</span>
                    </div>

                    <form id="reservation-form" method="POST" action="/reserve.php?movie_id=<?= $movieId ?>&screening_id=<?= $screeningId ?>"
                          data-screening-id="<?= $screeningId ?>"
                          data-ticket-price="<?= TICKET_PRICE ?>">
                        <input type="hidden" name="screening_id" value="<?= $screeningId ?>">

                        <div class="form-group" style="margin-bottom: 16px;">
                            <label for="seats">Aantal kaartjes (&euro; <?= number_format(TICKET_PRICE, 2, ',', '.') ?> per stuk)</label>
                            <input type="number" id="seats" name="seats" class="form-control" min="1" max="<?= max(1, min(10, $availableSeats)) ?>" value="<?= isset($_POST['seats']) ? (int)$_POST['seats'] : 1 ?>" required>
                        </div>

                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 16px;">
                            <div class="form-group">
                                <label for="customer_name">Volledige Naam</label>
                                <input type="text" id="customer_name" name="customer_name" class="form-control" value="<?= sanitize($_POST['customer_name'] ?? '') ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="customer_email">E-mailadres</label>
                                <input type="email" id="customer_email" name="customer_email" class="form-control" value="<?= sanitize($_POST['customer_email'] ?? '') ?>" required>
                            </div>
                        </div>

                        <div class="form-group" style="margin-bottom: 20px;">
                            <label for="customer_phone">Telefoonnummer</label>
                            <input type="tel" id="customer_phone" name="customer_phone" class="form-control" value="<?= sanitize($_POST['customer_phone'] ?? '') ?>" required>
                        </div>

                        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
                            <div>
                                Totaal: <strong id="total-price">&euro; <?= number_format(TICKET_PRICE, 2, ',', '.') ?></strong>
                            </div>
                            <button type="submit" class="btn">Reservering Bevestigen</button>
                        </div>
                    </form>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
