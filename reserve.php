<?php
require_once __DIR__ . '/config.php';

$database = new Database();
$db = $database->getConnection();
$movieModel = new Movie($db);
$reservationModel = new Reservation($db);

$screeningId = 0;
if (isset($_GET['screening_id'])) {
    $screeningId = $_GET['screening_id'];
} elseif (isset($_POST['screening_id'])) {
    $screeningId = $_POST['screening_id'];
}

$screening = $movieModel->getScreeningById($screeningId);
if (!$screening) {
    redirect('/index.php');
}

$availableSeats = $screening['capacity'] - $screening['booked_seats'];
$formattedDateTime = date('d-m-Y H:i', strtotime($screening['screening_time']));
$formattedTicketPrice = number_format(TICKET_PRICE, 2, ',', '.');
$errors = [];
$confirmationCode = '';

$customerName = '';
if (isset($_COOKIE['customer_name'])) {
    $customerName = $_COOKIE['customer_name'];
}

$customerEmail = '';
if (isset($_COOKIE['customer_email'])) {
    $customerEmail = $_COOKIE['customer_email'];
}

$customerPhone = '';
$seats = 1;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['customer_name'])) {
        $customerName = trim($_POST['customer_name']);
    }
    if (isset($_POST['customer_email'])) {
        $customerEmail = trim($_POST['customer_email']);
    }
    if (isset($_POST['customer_phone'])) {
        $customerPhone = trim($_POST['customer_phone']);
    }
    if (isset($_POST['seats'])) {
        $seats = (int)$_POST['seats'];
    }

    if ($customerName === '') {
        $errors[] = 'Vul uw naam in.';
    }
    if (!filter_var($customerEmail, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Vul een geldig e-mailadres in.';
    }
    if ($customerPhone === '') {
        $errors[] = 'Vul uw telefoonnummer in.';
    }
    if ($seats < 1 || $seats > $availableSeats) {
        $errors[] = 'Kies een geldig aantal kaartjes (maximaal ' . $availableSeats . ').';
    }

    if (empty($errors)) {
        $confirmationCode = $reservationModel->create($screeningId, $customerName, $customerEmail, $customerPhone, $seats);
        setcookie('customer_name', $customerName, time() + (30 * 24 * 60 * 60), '/');
        setcookie('customer_email', $customerEmail, time() + (30 * 24 * 60 * 60), '/');
    }
}

$formattedTotalPrice = number_format($seats * TICKET_PRICE, 2, ',', '.');

require_once __DIR__ . '/includes/header.php';
?>

<section class="page-header">
    <h1>Kaartjes Reserveren</h1>
    <a href="/index.php" class="btn btn-secondary">Terug naar overzicht</a>
</section>

<?php if ($confirmationCode !== ''): ?>
    <div class="alert alert-success" id="reservation-confirmed" data-clear-draft="true">
        <h2>Reservering Bevestigd!</h2>
        <p>Bedankt <strong><?php echo sanitize($customerName); ?></strong>. Uw reservering is opgeslagen.</p>
        <p><strong>Reserveringscode:</strong> <?php echo sanitize($confirmationCode); ?></p>
        <p><strong>Film:</strong> <?php echo sanitize($screening['title']); ?></p>
        <p><strong>Locatie:</strong> <?php echo sanitize($screening['location_name']); ?> (<?php echo sanitize($screening['hall_name']); ?>)</p>
        <p><strong>Tijdstip:</strong> <?php echo $formattedDateTime; ?></p>
        <p><strong>Aantal kaartjes:</strong> <?php echo $seats; ?> (&euro;<?php echo $formattedTotalPrice; ?>)</p>
        <a href="/my-reservations.php?email=<?php echo urlencode($customerEmail); ?>" class="btn">Bekijk mijn reserveringen</a>
    </div>
<?php else: ?>
    <div class="card screening-summary">
        <h2><?php echo sanitize($screening['title']); ?></h2>
        <p><strong>Locatie:</strong> <?php echo sanitize($screening['location_name']); ?> - <?php echo sanitize($screening['hall_name']); ?></p>
        <p><strong>Datum &amp; Tijd:</strong> <?php echo $formattedDateTime; ?></p>
        <p><strong>Beschikbare stoelen:</strong> <?php echo $availableSeats; ?></p>
        <p><strong>Prijs per kaartje:</strong> &euro;<?php echo $formattedTicketPrice; ?></p>
    </div>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <ul>
                <?php foreach ($errors as $error): ?>
                    <li><?php echo sanitize($error); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form method="POST" action="/reserve.php" id="reservation-form" class="card form-card" data-screening-id="<?php echo $screeningId; ?>">
        <input type="hidden" name="screening_id" value="<?php echo $screeningId; ?>">

        <div class="form-group">
            <label for="customer_name">Naam</label>
            <input type="text" id="customer_name" name="customer_name" value="<?php echo sanitize($customerName); ?>" required>
        </div>

        <div class="form-group">
            <label for="customer_email">E-mailadres</label>
            <input type="email" id="customer_email" name="customer_email" value="<?php echo sanitize($customerEmail); ?>" required>
        </div>

        <div class="form-group">
            <label for="customer_phone">Telefoonnummer</label>
            <input type="tel" id="customer_phone" name="customer_phone" value="<?php echo sanitize($customerPhone); ?>" required>
        </div>

        <div class="form-group">
            <label for="seats">Aantal kaartjes</label>
            <input type="number" id="seats" name="seats" min="1" max="<?php echo $availableSeats; ?>" value="<?php echo $seats; ?>" required>
        </div>

        <p class="total-price">Totaalprijs: <strong id="total-price-display">&euro;<?php echo $formattedTotalPrice; ?></strong></p>

        <button type="submit" class="btn">Reservering Bevestigen</button>
    </form>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
