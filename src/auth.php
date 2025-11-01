<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/models/User.php';

session_start();

function register(string $name, string $email, string $password) {
    $pdo = get_db();
    // basic validation
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) return 'Niepoprawny email';
    if (strlen($password) < 6) return 'Hasło musi mieć co najmniej 6 znaków';

    // check exists
    $u = User::findByEmail($email);
    if ($u) return 'Użytkownik o tym emailu już istnieje';

    $hash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare('INSERT INTO users (name, email, password_hash) VALUES (?, ?, ?)');
    $stmt->execute([$name, $email, $hash]);
    return true;
}

function login(string $email, string $password): bool {
    $u = User::findByEmail($email);
    if (!$u) return false;
    if (password_verify($password, $u['password_hash'])) {
        $_SESSION['user_id'] = $u['id'];
        return true;
    }
    return false;
}

function current_user() {
    if (!empty($_SESSION['user_id'])) {
        return User::findById($_SESSION['user_id']);
    }
    return null;
}

function require_auth() {
    if (empty($_SESSION['user_id'])) {
        header('Location: /pages/login.php');
        exit;
    }
}
