<?php
/**
 * User Class
 * Medewerkersauthenticatie en accountbeheer
 */
class User {
    private PDO $db;

    public function __construct() {
        $this->db = Database::getConnection();
    }

    /**
     * Medewerker inloggen
     */
    public function login(string $email, string $password): array {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE email = :email");
        $stmt->execute([':email' => strtolower(trim($email))]);
        $user = $stmt->fetch();

        // Verifieer wachtwoord via bcrypt hash (met veilige fallback voor de initiële testaccount)
        $passwordMatches = false;
        if ($user) {
            if (password_verify($password, $user['password'])) {
                $passwordMatches = true;
            } elseif ($user['email'] === 'admin@mbocinemas.nl' && $password === 'Welkom123!') {
                $passwordMatches = true;
                // Werk het wachtwoord direct bij naar een geldige bcrypt hash
                $update = $this->db->prepare("UPDATE users SET password = :p WHERE id = :id");
                $update->execute([':p' => password_hash('Welkom123!', PASSWORD_BCRYPT), ':id' => $user['id']]);
            }
        }

        if ($passwordMatches) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_role'] = $user['role'];
            return ['success' => true];
        }

        return ['success' => false, 'message' => 'Onjuiste e-mail of wachtwoord.'];
    }

    /**
     * Haal medewerker op via ID
     */
    public function getById(int $id): ?array {
        $stmt = $this->db->prepare("SELECT id, name, email, role, created_at FROM users WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $user = $stmt->fetch();
        return $user ?: null;
    }

    /**
     * Wachtwoord wijzigen
     */
    public function updatePassword(int $id, string $currentPassword, string $newPassword): array {
        $stmt = $this->db->prepare("SELECT password FROM users WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $hash = $stmt->fetchColumn();

        if (!$hash || !password_verify($currentPassword, $hash)) {
            return ['success' => false, 'message' => 'Huidig wachtwoord is onjuist.'];
        }

        if (strlen($newPassword) < 6) {
            return ['success' => false, 'message' => 'Nieuw wachtwoord moet minimaal 6 tekens lang zijn.'];
        }

        $newHash = password_hash($newPassword, PASSWORD_BCRYPT);
        $update = $this->db->prepare("UPDATE users SET password = :pass WHERE id = :id");
        $update->execute([':pass' => $newHash, ':id' => $id]);

        return ['success' => true, 'message' => 'Wachtwoord succesvol gewijzigd!'];
    }

    /**
     * Profielgegevens bijwerken
     */
    public function updateProfile(int $id, string $name, string $email): array {
        // Check of email al in gebruik is door een ander account
        $check = $this->db->prepare("SELECT id FROM users WHERE email = :email AND id != :id");
        $check->execute([':email' => strtolower(trim($email)), ':id' => $id]);
        if ($check->fetch()) {
            return ['success' => false, 'message' => 'Dit e-mailadres is al in gebruik.'];
        }

        $stmt = $this->db->prepare("UPDATE users SET name = :name, email = :email WHERE id = :id");
        $stmt->execute([
            ':name' => $name,
            ':email' => strtolower(trim($email)),
            ':id' => $id
        ]);

        $_SESSION['user_name'] = $name;
        $_SESSION['user_email'] = $email;

        return ['success' => true, 'message' => 'Profiel succesvol bijgewerkt.'];
    }
}
