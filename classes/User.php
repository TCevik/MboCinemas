<?php
/**
 * User class: regelt authenticatie (inloggen) en accountbeheer van gebruikers.
 */
class User {
    private $db;

    public function __construct($pdo) {
        $this->db = $pdo;
    }

    // Controleert e-mailadres en wachtwoord; geeft de gebruiker terug als het klopt, anders false
    public function login($email, $password) {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        // password_verify vergelijkt het ingevoerde wachtwoord veilig met de opgeslagen hash
        if ($user && password_verify($password, $user['password'])) {
            return $user;
        }
        return false;
    }

    // Haalt gebruikersgegevens op (zonder wachtwoordhash) op basis van ID
    public function getById($id) {
        $stmt = $this->db->prepare("SELECT id, name, email, role FROM users WHERE id = ?");
        $stmt->execute([(int)$id]);
        return $stmt->fetch();
    }

    // Werkt naam, e-mailadres en optioneel het wachtwoord bij
    public function updateAccount($id, $name, $email, $newPassword = '') {
        if ($newPassword !== '') {
            // Hash het nieuwe wachtwoord voordat het wordt opgeslagen
            $hash = password_hash($newPassword, PASSWORD_DEFAULT);
            $stmt = $this->db->prepare("UPDATE users SET name = ?, email = ?, password = ? WHERE id = ?");
            return $stmt->execute([$name, $email, $hash, (int)$id]);
        }
        // Als er geen nieuw wachtwoord is ingevuld, blijf het oude wachtwoord behouden
        $stmt = $this->db->prepare("UPDATE users SET name = ?, email = ? WHERE id = ?");
        return $stmt->execute([$name, $email, (int)$id]);
    }
}
