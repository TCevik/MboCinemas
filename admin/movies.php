<?php
require_once __DIR__ . '/../config.php';
requireLogin();

$movieModel = new Movie();
$message = null;
$messageType = 'success';

$editMovie = null;

// Bewerken: film ophalen
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $editMovie = $movieModel->getById((int)$_GET['edit']);
}

// Verwijderen
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    $id = (int)$_POST['movie_id'];
    if ($movieModel->delete($id)) {
        $message = "Film succesvol verwijderd.";
    } else {
        $message = "Kon film niet verwijderen.";
        $messageType = "danger";
    }
}

// Toevoegen of Bijwerken
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && in_array($_POST['action'], ['create', 'update'])) {
    $title = sanitize($_POST['title'] ?? '');
    $description = sanitize($_POST['description'] ?? '');
    $genre = sanitize($_POST['genre'] ?? '');
    $duration = (int)($_POST['duration_minutes'] ?? 0);
    $poster = sanitize($_POST['poster_url'] ?? '');

    if (empty($title) || empty($description) || empty($genre) || $duration <= 0) {
        $message = "Vul alstublieft alle verplichte velden correct in.";
        $messageType = "danger";
    } else {
        if ($_POST['action'] === 'create') {
            if ($movieModel->create($title, $description, $genre, $duration, $poster)) {
                $message = "Nieuwe film '{$title}' succesvol toegevoegd!";
            } else {
                $message = "Fout bij toevoegen van de film.";
                $messageType = "danger";
            }
        } elseif ($_POST['action'] === 'update') {
            $id = (int)$_POST['movie_id'];
            if ($movieModel->update($id, $title, $description, $genre, $duration, $poster)) {
                $message = "Film '{$title}' succesvol bijgewerkt!";
                $editMovie = null;
            } else {
                $message = "Fout bij bijwerken van de film.";
                $messageType = "danger";
            }
        }
    }
}

$movies = $movieModel->getAll();

$pageTitle = "Films Beheren";
require_once __DIR__ . '/../includes/header.php';
?>

<div class="admin-header">
    <div>
        <h1 style="font-size: 2rem;">Films Beheren</h1>
        <p style="color: var(--text-muted);">Voeg nieuwe films toe, pas titels of gegevens aan en verwijder oude films.</p>
    </div>
</div>

<div class="admin-nav">
    <a href="/admin/index.php" class="btn btn-sm btn-secondary">📋 Reserveringen & Zalen</a>
    <a href="/admin/movies.php" class="btn btn-sm">🎬 Films Beheren</a>
    <a href="/admin/screenings.php" class="btn btn-sm btn-secondary">📅 Zaalplanning</a>
    <a href="/admin/account.php" class="btn btn-sm btn-secondary">👤 Mijn Account</a>
</div>

<?php if ($message): ?>
    <div class="alert alert-<?= $messageType ?>">
        <?= sanitize($message) ?>
    </div>
<?php endif; ?>

<!-- Formulier voor Film Toevoegen / Bewerken -->
<div class="box-card">
    <h2 style="font-size: 1.3rem; margin-bottom: 20px;">
        <?= $editMovie ? '✏️ Film Bewerken: ' . sanitize($editMovie['title']) : '➕ Nieuwe Film Toevoegen' ?>
    </h2>

    <form method="POST" action="/admin/movies.php">
        <input type="hidden" name="action" value="<?= $editMovie ? 'update' : 'create' ?>">
        <?php if ($editMovie): ?>
            <input type="hidden" name="movie_id" value="<?= $editMovie['id'] ?>">
        <?php endif; ?>

        <div style="display: grid; grid-template-columns: 2fr 1fr 1fr; gap: 15px; margin-bottom: 15px;">
            <div class="form-group">
                <label for="title">Filmtitel *</label>
                <input type="text" id="title" name="title" class="form-control" value="<?= sanitize($editMovie['title'] ?? '') ?>" placeholder="Bijv. Dune: Part Two" required>
            </div>

            <div class="form-group">
                <label for="genre">Genre *</label>
                <input type="text" id="genre" name="genre" class="form-control" value="<?= sanitize($editMovie['genre'] ?? '') ?>" placeholder="Bijv. Sci-Fi, Actie" required>
            </div>

            <div class="form-group">
                <label for="duration_minutes">Speelduur (minuten) *</label>
                <input type="number" id="duration_minutes" name="duration_minutes" class="form-control" value="<?= (int)($editMovie['duration_minutes'] ?? 120) ?>" min="1" required>
            </div>
        </div>

        <div class="form-group" style="margin-bottom: 15px;">
            <label for="description">Beschrijving / Synopsis *</label>
            <textarea id="description" name="description" class="form-control" rows="3" placeholder="Korte samenvatting van de film..." required><?= sanitize($editMovie['description'] ?? '') ?></textarea>
        </div>

        <div class="form-group" style="margin-bottom: 20px;">
            <label for="poster_url">Poster Afbeeldings-URL</label>
            <input type="url" id="poster_url" name="poster_url" class="form-control" value="<?= sanitize($editMovie['poster_url'] ?? '') ?>" placeholder="https://voorbeeld.nl/poster.jpg">
            <small style="color: var(--text-muted);">Laat leeg voor een standaard filmposter.</small>
        </div>

        <div style="display: flex; gap: 10px;">
            <button type="submit" class="btn"><?= $editMovie ? 'Wijzigingen Opslaan' : 'Film Toevoegen' ?></button>
            <?php if ($editMovie): ?>
                <a href="/admin/movies.php" class="btn btn-secondary">Annuleren</a>
            <?php endif; ?>
        </div>
    </form>
</div>

<!-- Overzichtstabel van alle films -->
<h2 style="font-size: 1.4rem; margin-bottom: 15px;">Alle Films (<?= count($movies) ?>)</h2>
<div class="table-responsive">
    <table>
        <thead>
            <tr>
                <th>Poster</th>
                <th>Titel</th>
                <th>Genre</th>
                <th>Duur</th>
                <th>Acties</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($movies as $m): ?>
                <tr>
                    <td style="width: 70px;">
                        <img src="<?= sanitize($m['poster_url']) ?>" alt="poster" style="width: 50px; height: 70px; object-fit: cover; border-radius: 4px;">
                    </td>
                    <td>
                        <strong style="font-size: 1.05rem;"><?= sanitize($m['title']) ?></strong>
                        <div style="font-size: 0.85rem; color: var(--text-muted); max-width: 450px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                            <?= sanitize($m['description']) ?>
                        </div>
                    </td>
                    <td><span class="badge"><?= sanitize($m['genre']) ?></span></td>
                    <td><?= (int)$m['duration_minutes'] ?> min</td>
                    <td>
                        <div style="display: flex; gap: 8px;">
                            <a href="/admin/movies.php?edit=<?= $m['id'] ?>" class="btn btn-sm btn-secondary">Bewerken</a>
                            <form method="POST" action="/admin/movies.php" onsubmit="return confirm('Weet je zeker dat je deze film wilt verwijderen? Alle bijbehorende vertoningen en reserveringen worden ook verwijderd!');">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="movie_id" value="<?= $m['id'] ?>">
                                <button type="submit" class="btn btn-sm btn-danger">Verwijderen</button>
                            </form>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
