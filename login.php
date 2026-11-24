<?php
require_once __DIR__ . '/config.php';

// Als de medewerker al is ingelogd, stuur direct door naar het beheerpaneel
if (isLoggedIn()) {
    redirect('/admin.php');
}

$database = new Database();
$db = $database->getConnection();
$userModel = new User($db);
$error = '';

// Verwerk het inlogformulier bij een POST-verzoek
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = '';
    if (isset($_POST['email'])) {
        $email = trim($_POST['email']);
    }

    $password = '';
    if (isset($_POST['password'])) {
        $password = $_POST['password'];
    }

    // Controleer of beide velden zijn ingevuld
    if ($email === '' || $password === '') {
        $error = 'Vul zowel e-mailadres als wachtwoord in.';
    } else {
        // Controleer de inloggegevens via de User-class
        $user = $userModel->login($email, $password);
        if ($user) {
            // Sla gebruikersgegevens op in de sessie en stuur door naar admin.php
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            redirect('/admin.php');
        } else {
            $error = 'Ongeldige inloggegevens.';
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<section class="page-header">
    <h1>Medewerker Inloggen</h1>
</section>

<!-- Toon een foutmelding als het inloggen is mislukt -->
<?php if ($error !== ''): ?>
    <div class="alert alert-danger"><?php echo sanitize($error); ?></div>
<?php endif; ?>

<form method="POST" action="/login.php" class="card form-card">
    <div class="form-group">
        <label for="email">E-mailadres</label>
        <input type="email" id="email" name="email" required>
    </div>
    <div class="form-group">
        <label for="password">Wachtwoord</label>
        <input type="password" id="password" name="password" required>
    </div>
    <button type="submit" class="btn">Inloggen</button>
</form>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
