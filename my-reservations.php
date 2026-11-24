<?php
$pageTitle = "Mijn Reserveringen";
require_once __DIR__ . '/includes/header.php';

$reservationModel = new Reservation();
$message = null;
$messageType = 'success';

// Actie: Kaartjes aanpassen
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_seats') {
    $resId = (int)$_POST['reservation_id'];
    $newSeats = (int)$_POST['seats'];
    $result = $reservationModel->updateSeats($resId, $newSeats);
    $message = $result['message'];
    $messageType = $result['success'] ? 'success' : 'danger';
}

// Actie: Reservering annuleren
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'cancel') {
    $resId = (int)$_POST['reservation_id'];
    if ($reservationModel->cancel($resId)) {
        $message = "Je reservering is succesvol geannuleerd.";
        $messageType = "info";
    } else {
        $message = "Annuleren is mislukt.";
        $messageType = "danger";
    }
}

// Zoeken op code of e-mail
$searchQuery = trim($_GET['search'] ?? '');
$reservations = [];

if (!empty($searchQuery)) {
    if (str_starts_with(strtoupper($searchQuery), 'MBO-')) {
        $single = $reservationModel->getByCode($searchQuery);
        if ($single) $reservations[] = $single;
    } else {
        $reservations = $reservationModel->getByEmail($searchQuery);
    }
}
?>

<div style="max-width: 850px; margin: 30px auto;">
    
    <div style="text-align: center; margin-bottom: 30px;">
        <h1 style="font-size: 2.2rem; margin-bottom: 8px;">Mijn Reserveringen</h1>
        <p style="color: var(--text-muted);">
            Bekijk je geplande bezoeken, wijzig het aantal kaartjes of annuleer je reservering kosteloos.
        </p>
    </div>

    <?php if ($message): ?>
        <div class="alert alert-<?= $messageType ?>">
            <?= sanitize($message) ?>
        </div>
    <?php endif; ?>

    <!-- Zoekformulier -->
    <div class="box-card">
        <form method="GET" action="/my-reservations.php" style="display: flex; gap: 10px;">
            <input type="text" name="search" class="form-control" 
                   placeholder="Vul je reserveringscode (bijv. MBO-A1B2C3) of e-mailadres in..." 
                   value="<?= sanitize($searchQuery) ?>" required>
            <button type="submit" class="btn">Reservering Zoeken</button>
        </form>
    </div>

    <!-- Resultaten -->
    <?php if (!empty($searchQuery)): ?>
        <?php if (empty($reservations)): ?>
            <div class="alert alert-danger" style="text-align: center;">
                Geen reserveringen gevonden voor "<strong><?= sanitize($searchQuery) ?></strong>". Controleer de code of het e-mailadres.
            </div>
        <?php else: ?>
            <div style="display: flex; flex-direction: column; gap: 20px;">
                <?php foreach ($reservations as $r): 
                    $time = new DateTime($r['screening_time']);
                    $isCancelled = ($r['status'] === 'geannuleerd');
                ?>
                    <div class="box-card" style="border-left: 4px solid <?= $isCancelled ? 'var(--danger)' : 'var(--primary)' ?>;">
                        <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 15px; border-bottom: 1px solid var(--border); padding-bottom: 12px;">
                            <div>
                                <span style="font-size: 0.8rem; color: var(--text-muted); text-transform: uppercase;">Code</span>
                                <h3 style="font-size: 1.3rem; color: var(--primary);"><?= sanitize($r['reservation_code']) ?></h3>
                            </div>
                            <div>
                                <?php if ($isCancelled): ?>
                                    <span class="badge badge-danger">Geannuleerd</span>
                                <?php else: ?>
                                    <span class="badge badge-success">Actief</span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 20px; align-items: center;">
                            <div>
                                <h2 style="font-size: 1.3rem; margin-bottom: 6px;"><?= sanitize($r['movie_title']) ?></h2>
                                <p style="color: var(--text-muted); font-size: 0.95rem;">
                                    📍 <?= sanitize($r['location_name']) ?> (<?= sanitize($r['city']) ?>) &bull; <?= sanitize($r['hall_name']) ?>
                                </p>
                                <p style="color: var(--text-muted); font-size: 0.95rem; margin-top: 4px;">
                                    📅 <?= $time->format('d-m-Y') ?> om <?= $time->format('H:i') ?> uur
                                </p>
                                <p style="color: var(--text-muted); font-size: 0.95rem; margin-top: 4px;">
                                    👤 <?= sanitize($r['customer_name']) ?> (<?= sanitize($r['customer_email']) ?>)
                                </p>
                            </div>

                            <div style="background: #0f1626; padding: 15px; border-radius: 8px; text-align: center;">
                                <div style="font-size: 0.85rem; color: var(--text-muted);">Aantal Kaartjes</div>
                                <div style="font-size: 1.6rem; font-weight: 700; color: var(--text-main); margin: 4px 0;">
                                    <?= (int)$r['seats'] ?>
                                </div>
                                <div style="font-size: 0.9rem; color: var(--primary); font-weight: 600;">
                                    € <?= number_format($r['seats'] * TICKET_PRICE, 2, ',', '.') ?>
                                </div>
                            </div>
                        </div>

                        <?php if (!$isCancelled): ?>
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 20px; padding-top: 15px; border-top: 1px solid var(--border); flex-wrap: wrap; gap: 15px;">
                                
                                <!-- Kaartjes wijzigen -->
                                <form method="POST" action="/my-reservations.php?search=<?= urlencode($searchQuery) ?>" style="display: flex; gap: 8px; align-items: center;">
                                    <input type="hidden" name="action" value="update_seats">
                                    <input type="hidden" name="reservation_id" value="<?= $r['id'] ?>">
                                    <label style="font-size: 0.9rem; color: var(--text-muted);">Aantal wijzigen:</label>
                                    <input type="number" name="seats" class="form-control" min="1" max="10" value="<?= (int)$r['seats'] ?>" style="width: 70px;">
                                    <button type="submit" class="btn btn-sm">Opslaan</button>
                                </form>

                                <!-- Annuleren -->
                                <form method="POST" action="/my-reservations.php?search=<?= urlencode($searchQuery) ?>" onsubmit="return confirm('Weet je zeker dat je deze reservering wilt annuleren?');">
                                    <input type="hidden" name="action" value="cancel">
                                    <input type="hidden" name="reservation_id" value="<?= $r['id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-danger">Reservering Annuleren</button>
                                </form>

                            </div>
                        <?php endif; ?>

                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>

</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
