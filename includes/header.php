<?php
require_once __DIR__ . '/../config.php';
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($pageTitle) ? sanitize($pageTitle) . ' - ' : '' ?><?= SITE_NAME ?></title>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>

<header class="navbar">
    <div class="nav-container">
        <a href="/index.php" class="brand">
            🎬 <?= SITE_NAME ?>
        </a>

        <ul class="nav-links">
            <li><a href="/index.php" class="<?= $currentPage === 'index.php' && !str_contains($_SERVER['REQUEST_URI'], '/admin/') ? 'active' : '' ?>">Films</a></li>
            <li><a href="/my-reservations.php" class="<?= $currentPage === 'my-reservations.php' ? 'active' : '' ?>">Mijn Reserveringen</a></li>
            
            <?php if (isLoggedIn()): ?>
                <li><a href="/admin/index.php" class="<?= str_contains($_SERVER['REQUEST_URI'], '/admin/') ? 'active' : '' ?>">Beheer (<?= sanitize($_SESSION['user_name']) ?>)</a></li>
                <li><a href="/logout.php" class="btn btn-sm btn-secondary">Uitloggen</a></li>
            <?php else: ?>
                <li><a href="/login.php" class="btn-nav">Medewerkers</a></li>
            <?php endif; ?>
        </ul>
    </div>
</header>

<main class="container">
