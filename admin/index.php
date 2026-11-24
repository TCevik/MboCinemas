<?php
require_once __DIR__ . '/../config.php';
requireLogin();

$reservationModel = new Reservation();
$movieModel = new Movie();
$userModel = new User();

$message = null;
$messageType = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];

    if ($action === 'update_seats') {
        $resId = (int)$_POST['reservation_id'];
        $newSeats = (int)$_POST['seats'];
        $result = $reservationModel->updateSeats($resId, $newSeats);
        $message = $result['message'];
        $messageType = $result['success'] ? 'success' : 'danger';
    } elseif ($action === 'cancel') {
        $resId = (int)$_POST['reservation_id'];
        if ($reservationModel->cancel($resId)) {
            $message = "Reservering succesvol geannuleerd.";
            $messageType = 'info';
        } else {
            $message = "Annuleren is mislukt.";
            $messageType = 'danger';
        }
    } elseif ($action === 'change_password') {
        $current = $_POST['current_password'] ?? '';
        $new = $_POST['new_password'] ?? '';
        $confirm = $_POST['confirm_password'] ?? '';

        if ($new !== $confirm) {
            $message = "Nieuwe wachtwoorden komen niet overeen.";
            $messageType = 'danger';
        } else {
            $result = $userModel->updatePassword((int)$_SESSION['user_id'], $current, $new);
            $message = $result['message'];
            $messageType = $result['success'] ? 'success' : 'danger';
        }
    }
}

$selectedLocation = isset($_GET['location']) && is_numeric($_GET['location']) ? (int)$_GET['location'] : null;
$locations = $movieModel->getLocations();
$reservations = $reservationModel->getAllByLocation($selectedLocation);

$pageTitle = "Beheerders Dashboard";
require_once __DIR__ . '/../includes/header.php';
?>

<div class="admin-header">
    <div>
        <h1 style="font-size: 2rem;">Beheerders Dashboard</h1>
        <p style="color: var(--text-muted);">Welkom, <?= sanitize($_SESSION['user_name']) ?>! Beheer hier reserveringen en je account.</p>
    </div>
</div>

<div class="admin-nav">
    <a href="/admin/index.php" class="btn btn-sm">Reserveringen</a>
    <a href="/admin/movies.php" class="btn btn-sm btn-secondary">Films Beheren</a>
    <a href="/admin/screenings.php" class="btn btn-sm btn-secondary">Zaalplanning</a>
</div>

<?php if ($message): ?>
    <div class="alert alert-<?= $messageType ?>">
        <?= sanitize($message) ?>
    </div>
<?php endif; ?>

<div class="box-card" style="padding: 18px 24px;">
    <form method="GET" action="/admin/index.php" style="display: flex; gap: 15px; align-items: center; flex-wrap: wrap;">
        <label for="location" style="font-weight: 600;">Filter op bioscoop:</label>
        <select name="location" id="location" class="form-control" style="width: auto; min-width: 240px;" onchange="this.form.submit()">
            <option value="">Alle bioscopen</option>
            <?php foreach ($locations as $loc): ?>
                <option value="<?= $loc['id'] ?>" <?= ($selectedLocation == $loc['id']) ? 'selected' : '' ?>>
                    <?= sanitize($loc['name']) ?> (<?= sanitize($loc['city']) ?>)
                </option>
            <?php endforeach; ?>
        </select>
        <?php if ($selectedLocation): ?>
            <a href="/admin/index.php" class="btn btn-sm btn-secondary">Filter wissen</a>
        <?php endif; ?>
    </form>
</div>

<h2 style="font-size: 1.4rem; margin-bottom: 15px;">Reserveringen</h2>
<div class="table-responsive">
    <table>
        <thead>
            <tr>
                <th>Code</th>
                <th>Klant</th>
                <th>Film</th>
                <th>Bioscoop &amp; Zaal</th>
                <th>Datum &amp; Tijd</th>
                <th>Kaartjes</th>
                <th>Status</th>
                <th>Acties</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($reservations)): ?>
                <tr><td colspan="8" style="text-align: center; color: var(--text-muted);">Geen reserveringen gevonden.</td></tr>
            <?php else: ?>
                <?php foreach ($reservations as $res):
                    $time = new DateTime($res['screening_time']);
                ?>
                    <tr>
                        <td><strong><?= sanitize($res['reservation_code']) ?></strong></td>
                        <td>
                            <?= sanitize($res['customer_name']) ?><br>
                            <small style="color: var(--text-muted);"><?= sanitize($res['customer_email']) ?></small>
                        </td>
                        <td><?= sanitize($res['movie_title']) ?></td>
                        <td><?= sanitize($res['location_name']) ?> (<?= sanitize($res['hall_name']) ?>)</td>
                        <td><?= $time->format('d-m-Y H:i') ?></td>
                        <td>
                            <?php if ($res['status'] === 'actief'): ?>
                                <form method="POST" action="/admin/index.php<?= $selectedLocation ? '?location=' . $selectedLocation : '' ?>" style="display: flex; gap: 6px; align-items: center;">
                                    <input type="hidden" name="action" value="update_seats">
                                    <input type="hidden" name="reservation_id" value="<?= (int)$res['id'] ?>">
                                    <input type="number" name="seats" value="<?= (int)$res['seats'] ?>" min="1" max="10" class="form-control" style="width: 70px; padding: 6px;">
                                    <button type="submit" class="btn btn-sm">Opslaan</button>
                                </form>
                            <?php else: ?>
                                <?= (int)$res['seats'] ?>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="badge <?= $res['status'] === 'actief' ? 'badge-success' : 'badge-danger' ?>">
                                <?= sanitize($res['status']) ?>
                            </span>
                        </td>
                        <td>
                            <?php if ($res['status'] === 'actief'): ?>
                                <form method="POST" action="/admin/index.php<?= $selectedLocation ? '?location=' . $selectedLocation : '' ?>" onsubmit="return confirm('Weet je zeker dat je deze reservering wilt annuleren?');">
                                    <input type="hidden" name="action" value="cancel">
                                    <input type="hidden" name="reservation_id" value="<?= (int)$res['id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-danger">Annuleren</button>
                                </form>
                            <?php else: ?>
                                -
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<div id="account" class="box-card" style="margin-top: 40px; max-width: 520px;">
    <h2 style="font-size: 1.3rem; margin-bottom: 16px;">Accountbeheer: Wachtwoord wijzigen</h2>
    <form method="POST" action="/admin/index.php">
        <input type="hidden" name="action" value="change_password">
        <div class="form-group" style="margin-bottom: 14px;">
            <label for="current_password">Huidig wachtwoord</label>
            <input type="password" id="current_password" name="current_password" class="form-control" required>
        </div>
        <div class="form-group" style="margin-bottom: 14px;">
            <label for="new_password">Nieuw wachtwoord (min. 6 tekens)</label>
            <input type="password" id="new_password" name="new_password" class="form-control" minlength="6" required>
        </div>
        <div class="form-group" style="margin-bottom: 18px;">
            <label for="confirm_password">Bevestig nieuw wachtwoord</label>
            <input type="password" id="confirm_password" name="confirm_password" class="form-control" minlength="6" required>
        </div>
        <button type="submit" class="btn">Wachtwoord bijwerken</button>
    </form>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
