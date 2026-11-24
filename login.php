<?php
require_once __DIR__ . '/config.php';

if (isLoggedIn()) {
    redirect('/admin/index.php');
}

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    $userModel = new User();
    $result = $userModel->login($email, $password);

    if ($result['success']) {
        redirect('/admin/index.php');
    } else {
        $error = $result['message'];
    }
}

$pageTitle = "Medewerkers Inloggen";
require_once __DIR__ . '/includes/header.php';
?>

<div style="max-width: 450px; margin: 60px auto;">
    
    <div class="box-card">
        <h1 style="font-size: 1.8rem; margin-bottom: 8px; text-align: center;">Medewerkers Login</h1>
        <p style="color: var(--text-muted); text-align: center; margin-bottom: 25px; font-size: 0.95rem;">
            Toegang tot het MBO Cinemas beheerportaal
        </p>

        <?php if ($error): ?>
            <div class="alert alert-danger">
                <?= sanitize($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="/login.php">
            <div class="form-group" style="margin-bottom: 18px;">
                <label for="email">E-mailadres</label>
                <input type="email" id="email" name="email" class="form-control" placeholder="naam@mbocinemas.nl" required autofocus>
            </div>

            <div class="form-group" style="margin-bottom: 25px;">
                <label for="password">Wachtwoord</label>
                <input type="password" id="password" name="password" class="form-control" placeholder="••••••••" required>
            </div>

            <button type="submit" class="btn btn-block" style="padding: 12px;">Inloggen</button>
        </form>

        <div style="margin-top: 25px; padding-top: 15px; border-top: 1px solid var(--border); font-size: 0.85rem; color: var(--text-muted); text-align: center;">
            <strong>Testaccount:</strong><br>
            E-mail: <code style="color: var(--primary);">admin@mbocinemas.nl</code><br>
            Wachtwoord: <code style="color: var(--primary);">Welkom123!</code>
        </div>
    </div>

</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
