<?php
$pageTitle = "Nu in de Bioscoop";
require_once __DIR__ . '/includes/header.php';

$movieModel = new Movie();

$search = $_GET['search'] ?? null;
$genre = $_GET['genre'] ?? null;
$location_id = isset($_GET['location']) && is_numeric($_GET['location']) ? (int)$_GET['location'] : null;
$date = $_GET['date'] ?? null;

$movies = $movieModel->getAll($genre, $location_id, $date, $search);
$genres = $movieModel->getGenres();
$locations = $movieModel->getLocations();
?>

<div class="hero">
    <h1>Nu in de Bioscoop</h1>
    <p>Ontdek de nieuwste topfilms, bekijk de actuele vertoningen en reserveer eenvoudig je bioscoopstoelen online.</p>
</div>

<form method="GET" action="/index.php" class="filter-bar">
    <div class="form-group">
        <label for="search-movie">Zoek film</label>
        <input type="text" id="search-movie" name="search" class="form-control" placeholder="Typ filmtitel..." value="<?= sanitize($search ?? '') ?>">
    </div>

    <div class="form-group">
        <label for="filter-genre">Genre</label>
        <select id="filter-genre" name="genre" class="form-control">
            <option value="">Alle genres</option>
            <?php foreach ($genres as $g): ?>
                <option value="<?= sanitize($g) ?>" <?= ($genre === $g) ? 'selected' : '' ?>><?= sanitize($g) ?></option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="form-group">
        <label for="filter-location">Bioscoop</label>
        <select id="filter-location" name="location" class="form-control">
            <option value="">Alle locaties</option>
            <?php foreach ($locations as $loc): ?>
                <option value="<?= $loc['id'] ?>" <?= ($location_id == $loc['id']) ? 'selected' : '' ?>><?= sanitize($loc['name']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="form-group">
        <label for="filter-date">Datum</label>
        <input type="date" id="filter-date" name="date" class="form-control" value="<?= sanitize($date ?? '') ?>">
    </div>

    <div class="form-group">
        <button type="submit" class="btn">Filteren</button>
    </div>
</form>

<div id="no-movies-found" style="display: <?= empty($movies) ? 'block' : 'none' ?>; text-align: center; padding: 40px 0;">
    <h3 style="color: var(--text-muted);">Geen films gevonden die aan de filters voldoen.</h3>
    <a href="/index.php" class="btn btn-secondary" style="margin-top: 15px;">Filters wissen</a>
</div>

<div class="movies-grid">
    <?php foreach ($movies as $movie): 
        $screenings = $movieModel->getScreeningsByMovie($movie['id']);
        $locationIds = array_unique(array_column($screenings, 'location_id'));
        $dates = array_unique(array_map(fn($s) => substr($s['screening_time'], 0, 10), $screenings));
    ?>
        <div class="movie-card movie-card-item" 
             data-title="<?= strtolower(sanitize($movie['title'])) ?>"
             data-genre="<?= sanitize($movie['genre']) ?>"
             data-locations="<?= implode(',', $locationIds) ?>"
             data-dates="<?= implode(',', $dates) ?>">
            <img src="<?= sanitize($movie['poster_url'] ?: '/assets/img/default-poster.jpg') ?>" 
                 alt="<?= sanitize($movie['title']) ?>" 
                 class="movie-poster"
                 loading="lazy">
            <div class="movie-info">
                <div class="movie-meta">
                    <span class="badge"><?= sanitize($movie['genre']) ?></span>
                    <span class="badge badge-muted"><?= (int)$movie['duration_minutes'] ?> min</span>
                </div>
                <h2 class="movie-title"><?= sanitize($movie['title']) ?></h2>
                <p class="movie-desc"><?= sanitize($movie['description']) ?></p>
                <a href="/reserve.php?movie_id=<?= $movie['id'] ?>" class="btn btn-block">Bekijk tijden &amp; Reserveer</a>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
