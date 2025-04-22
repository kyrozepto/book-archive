<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/Database.php';

class Auth {
    private $db;

    public function __construct() {
        $this->db = new Database();
    }

    public function register($username, $email, $password) {
        // Check if username or email already exists
        $stmt = $this->db->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $email]);
        if ($stmt->rowCount() > 0) {
            throw new Exception("Username or email already exists");
        }

        // Hash password
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        
        // Generate API key
        $api_key = bin2hex(random_bytes(32));

        // Insert new user
        $stmt = $this->db->prepare("INSERT INTO users (username, email, password, api_key) VALUES (?, ?, ?, ?)");
        return $stmt->execute([$username, $email, $hashedPassword, $api_key]);
    }

    public function login($username, $password) {
        $stmt = $this->db->prepare("SELECT id, password, api_key FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['api_key'] = $user['api_key'];
            return true;
        }

        return false;
    }

    public function logout() {
        session_destroy();
        return true;
    }

    public function isLoggedIn() {
        return isset($_SESSION['api_key']);
    }

    public function getCurrentUser() {
        if (!$this->isLoggedIn()) {
            return null;
        }

        $stmt = $this->db->prepare("SELECT id, username, email, api_key FROM users WHERE api_key = ?");
        $stmt->execute([$_SESSION['api_key']]);
        return $stmt->fetch();
    }

    public function validateApiKey($api_key) {
        $stmt = $this->db->prepare("SELECT id FROM users WHERE api_key = ?");
        $stmt->execute([$api_key]);
        return $stmt->rowCount() > 0;
    }
}
?> 