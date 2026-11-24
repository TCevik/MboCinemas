<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Paginatitel wordt opgehaald uit de constante SITE_NAME in config.php -->
    <title><?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
<!-- Bovenste navigatiebalk die op elke pagina wordt ingeladen -->
<header class="site-header">
    <div class="container header-inner">
        <a href="/index.php" class="logo"><?php echo SITE_NAME; ?></a>
        <nav class="main-nav">
            <a href="/index.php">Films</a>
            <a href="/my-reservations.php">Mijn Reserveringen</a>
            <!-- Toon beheer- en uitloglinks alleen als een medewerker is ingelogd -->
            <?php if (isLoggedIn()): ?>
                <a href="/admin.php">Beheer</a>
                <a href="/logout.php">Uitloggen</a>
            <?php else: ?>
                <a href="/login.php">Medewerker Login</a>
            <?php endif; ?>
        </nav>
    </div>
</header>
<!-- Start van de hoofdinhoud (wordt afgesloten in footer.php) -->
<main class="container">
