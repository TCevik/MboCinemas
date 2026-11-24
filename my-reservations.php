<?php
require_once __DIR__ . '/classes/Database.php';

$database = new Database();
$db = $database->getConnection();
$reservationModel = new Reservation($db);

// Haal e-mailadres op uit de URL (GET) of uit de opgeslagen cookie
$email = '';
if (isset($_GET['email'])) {
    $email = trim($_GET['email']);
} elseif (isset($_COOKIE['customer_email'])) {
    $email = $_COOKIE['customer_email'];
}

$message = '';
$error = '';

// Verwerk acties van de klant: aantal kaartjes wijzigen of reservering annuleren
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = '';
    if (isset($_POST['action'])) {
        $action = $_POST['action'];
    }

    $reservationId = 0;
    if (isset($_POST['reservation_id'])) {
        $reservationId = $_POST['reservation_id'];
    }

    if (isset($_POST['email'])) {
        $email = trim($_POST['email']);
    }

    if ($action === 'update') {
        $seats = 0;
        if (isset($_POST['seats'])) {
            $seats = (int)$_POST['seats'];
        }
        // Controleer of het nieuwe aantal stoelen binnen de toegestane limiet (1 t/m 20) valt
        if ($seats >= 1 && $seats <= 20) {
            $reservationModel->updateSeats($reservationId, $seats);
            $message = 'Het aantal kaartjes is bijgewerkt.';
        } else {
            $error = 'Kies een geldig aantal kaartjes (1 t/m 20).';
        }
    } elseif ($action === 'cancel') {
        $reservationModel->cancel($reservationId);
        $message = 'De reservering is geannuleerd.';
    }
}

// Haal alle reserveringen op als er een geldig e-mailadres bekend is
$reservations = [];
if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $reservations = $reservationModel->getByEmail($email);
}

require_once __DIR__ . '/includes/header.php';
?>

<section class="page-header">
    <h1>Mijn Reserveringen</h1>
</section>

<!-- Zoekformulier om reserveringen op te vragen via e-mailadres -->
<section class="filter-section">
    <form method="GET" action="/my-reservations.php" class="filter-form">
        <input type="email" name="email" placeholder="Vul uw e-mailadres in..." value="<?php echo sanitize($email); ?>" required>
        <button type="submit" class="btn">Reserveringen Ophalen</button>
    </form>
</section>

<!-- Meldingen voor geslaagde acties of fouten -->
<?php if ($message !== ''): ?>
    <div class="alert alert-success"><?php echo sanitize($message); ?></div>
<?php endif; ?>
<?php if ($error !== ''): ?>
    <div class="alert alert-danger"><?php echo sanitize($error); ?></div>
<?php endif; ?>

<?php if ($email !== ''): ?>
    <?php if (empty($reservations)): ?>
        <p>Geen reserveringen gevonden voor <?php echo sanitize($email); ?>.</p>
    <?php else: ?>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Code</th>
                    <th>Film</th>
                    <th>Locatie &amp; Zaal</th>
                    <th>Datum &amp; Tijd</th>
                    <th>Kaartjes</th>
                    <th>Status</th>
                    <th>Acties</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($reservations as $reservation): ?>
                    <?php
                    $formattedDateTime = date('d-m-Y H:i', strtotime($reservation['screening_time']));
                    // Geef actieve reserveringen een groene badge en geannuleerde een rode
                    $badgeClass = 'badge-danger';
                    if ($reservation['status'] === 'actief') {
                        $badgeClass = 'badge-success';
                    }
                    ?>
                    <tr>
                        <td><?php echo sanitize($reservation['reservation_code']); ?></td>
                        <td><?php echo sanitize($reservation['movie_title']); ?></td>
                        <td><?php echo sanitize($reservation['location_name']); ?> (<?php echo sanitize($reservation['hall_name']); ?>)</td>
                        <td><?php echo $formattedDateTime; ?></td>
                        <td><?php echo $reservation['seats']; ?></td>
                        <td>
                            <span class="badge <?php echo $badgeClass; ?>">
                                <?php echo sanitize($reservation['status']); ?>
                            </span>
                        </td>
                        <td>
                            <!-- Alleen actieve reserveringen kunnen nog gewijzigd of geannuleerd worden -->
                            <?php if ($reservation['status'] === 'actief'): ?>
                                <form method="POST" action="/my-reservations.php" class="inline-form">
                                    <input type="hidden" name="reservation_id" value="<?php echo $reservation['id']; ?>">
                                    <input type="hidden" name="email" value="<?php echo sanitize($email); ?>">
                                    <input type="hidden" name="action" value="update">
                                    <input type="number" name="seats" value="<?php echo $reservation['seats']; ?>" min="1" max="20" class="input-small">
                                    <button type="submit" class="btn btn-small">Wijzig</button>
                                </form>
                                <form method="POST" action="/my-reservations.php" class="inline-form confirm-cancel">
                                    <input type="hidden" name="reservation_id" value="<?php echo $reservation['id']; ?>">
                                    <input type="hidden" name="email" value="<?php echo sanitize($email); ?>">
                                    <input type="hidden" name="action" value="cancel">
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
    <?php endif; ?>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
