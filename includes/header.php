<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
<header class="site-header">
    <div class="container header-inner">
        <a href="/index.php" class="logo"><?php echo SITE_NAME; ?></a>
        <nav class="main-nav">
            <a href="/index.php">Films</a>
            <a href="/my-reservations.php">Mijn Reserveringen</a>
            <?php if (isLoggedIn()): ?>
                <a href="/admin.php">Beheer</a>
                <a href="/logout.php">Uitloggen</a>
            <?php else: ?>
                <a href="/login.php">Medewerker Login</a>
            <?php endif; ?>
        </nav>
    </div>
</header>
<main class="container">
