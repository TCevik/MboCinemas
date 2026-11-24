<?php
// Laad configuratie, database-instellingen en autoloader
require_once __DIR__ . '/classes/Database.php';

// Maak databaseverbinding en instantieer het Movie-model
$database = new Database();
$db = $database->getConnection();
$movieModel = new Movie($db);

// Lees eventuele filterwaarden uit de URL (?search=...&genre=...&location_id=...&date=...)
$search = '';
if (isset($_GET['search'])) {
    $search = trim($_GET['search']);
}

$genre = '';
if (isset($_GET['genre'])) {
    $genre = trim($_GET['genre']);
}

$locationId = '';
if (isset($_GET['location_id'])) {
    $locationId = trim($_GET['location_id']);
}

$date = '';
if (isset($_GET['date'])) {
    $date = trim($_GET['date']);
}

// Haal gefilterde voorstellingen, unieke genres en locaties op uit de database
$screenings = $movieModel->getFilteredScreenings($search, $genre, $locationId, $date);
$genres = $movieModel->getGenres();
$locations = $movieModel->getLocations();

require_once __DIR__ . '/includes/header.php';
?>

<section class="page-header">
    <h1>Actueel Filmaanbod</h1>
    <!-- Banner die via JavaScript (localStorage) zichtbaar wordt als de bezoeker nog een niet-afgeronde reservering heeft -->
    <div id="unfinished-reservation-banner" class="alert alert-info hidden">
        <span>Je hebt nog een niet-afgeronde reservering openstaan.</span>
        <a id="resume-reservation-link" href="#" class="btn btn-small">Verder gaan</a>
        <button type="button" id="discard-draft-btn" class="btn btn-small btn-secondary">Wissen</button>
    </div>
</section>

<!-- Filterformulier om voorstellingen te zoeken op titel, genre, locatie of datum -->
<section class="filter-section">
    <form method="GET" action="/index.php" id="filter-form" class="filter-form">
        <input type="text" name="search" id="filter-search" placeholder="Zoek op filmtitel..." value="<?php echo sanitize($search); ?>">
        <select name="genre" id="filter-genre">
            <option value="">Alle genres</option>
            <?php foreach ($genres as $genreItem): ?>
                <?php
                $selected = '';
                if ($genre === $genreItem) {
                    $selected = 'selected';
                }
                ?>
                <option value="<?php echo sanitize($genreItem); ?>" <?php echo $selected; ?>>
                    <?php echo sanitize($genreItem); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <select name="location_id" id="filter-location">
            <option value="">Alle locaties</option>
            <?php foreach ($locations as $location): ?>
                <?php
                $selected = '';
                if ((string)$locationId === (string)$location['id']) {
                    $selected = 'selected';
                }
                ?>
                <option value="<?php echo $location['id']; ?>" <?php echo $selected; ?>>
                    <?php echo sanitize($location['name']); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <input type="date" name="date" id="filter-date" value="<?php echo sanitize($date); ?>">
        <button type="submit" class="btn">Filteren</button>
        <a href="/index.php" class="btn btn-secondary">Reset</a>
    </form>
</section>

<!-- Overzicht van alle gevonden voorstellingen -->
<section class="movies-grid" id="movies-grid">
    <?php if (empty($screenings)): ?>
        <p>Geen voorstellingen gevonden voor de gekozen filters.</p>
    <?php else: ?>
        <?php foreach ($screenings as $screening): ?>
            <?php
            // Bereken het aantal nog beschikbare stoelen en formatteer datum/tijd
            $available = $screening['capacity'] - $screening['booked_seats'];
            $screeningTimestamp = strtotime($screening['screening_time']);
            $formattedDateTime = date('d-m-Y H:i', $screeningTimestamp);
            $filterDate = date('Y-m-d', $screeningTimestamp);
            ?>
            <!-- data-* attributen worden door main.js gebruikt voor direct (live) filteren zonder pagina-reload -->
            <article class="card movie-card"
                     data-title="<?php echo strtolower(sanitize($screening['title'])); ?>"
                     data-genre="<?php echo sanitize($screening['genre']); ?>"
                     data-location="<?php echo $screening['location_id']; ?>"
                     data-date="<?php echo $filterDate; ?>">
                <div class="card-body">
                    <h2><?php echo sanitize($screening['title']); ?></h2>
                    <p class="meta"><?php echo sanitize($screening['genre']); ?> &bull; <?php echo $screening['duration_minutes']; ?> min</p>
                    <p class="description"><?php echo sanitize($screening['description']); ?></p>
                    <div class="screening-info">
                        <p><strong>Locatie:</strong> <?php echo sanitize($screening['location_name']); ?> (<?php echo sanitize($screening['hall_name']); ?>)</p>
                        <p><strong>Datum &amp; Tijd:</strong> <?php echo $formattedDateTime; ?></p>
                        <p><strong>Beschikbaar:</strong> <?php echo $available; ?> van <?php echo $screening['capacity']; ?> stoelen</p>
                    </div>
                    <!-- Toon reserveerknop als er nog plek is, anders een 'Uitverkocht' label -->
                    <?php if ($available > 0): ?>
                        <a href="/reserve.php?screening_id=<?php echo $screening['screening_id']; ?>" class="btn">Kaartjes Reserveren</a>
                    <?php else: ?>
                        <span class="badge badge-danger">Uitverkocht</span>
                    <?php endif; ?>
                </div>
            </article>
        <?php endforeach; ?>
    <?php endif; ?>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
