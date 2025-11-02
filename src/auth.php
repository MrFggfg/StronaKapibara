<?php
// src/auth.php
require_once __DIR__ . '/db.php';

session_set_cookie_params([
    'lifetime' => 0,               // sesja wygasa po zamknięciu przeglądarki
    'path' => '/',
    'domain' => '',                // zostaw puste, działa lokalnie
    'secure' => isset($_SERVER['HTTPS']), // true tylko jeśli HTTPS
    'httponly' => true,            // zapobiega odczytaniu ciasteczka przez JS
    'samesite' => 'Lax'            // ochrona przed CSRF przez linki z zewnątrz
]);
session_start();


function registerUser($username, $email, $password) {
    $db = getDB();

    // Sprawdź czy użytkownik istnieje
    $stmt = $db->prepare("SELECT id FROM users WHERE username = :u OR email = :e");
    $stmt->execute([':u' => $username, ':e' => $email]);

    if ($stmt->fetch()) {
        return "Użytkownik o takiej nazwie lub e-mailu już istnieje.";
    }

    // Hashowanie hasła
    $hash = password_hash($password, PASSWORD_DEFAULT);

    $stmt = $db->prepare("INSERT INTO users (username, email, password_hash) VALUES (:u, :e, :p)");
    $stmt->execute([':u' => $username, ':e' => $email, ':p' => $hash]);

    return true;
}

function loginUser($username, $password) {
    $db = getDB();

    $stmt = $db->prepare("SELECT * FROM users WHERE username = :u");
    $stmt->execute([':u' => $username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['password_hash'])) {
        session_regenerate_id(true); // nowy identyfikator po zalogowaniu
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['last_activity'] = time();
        return true;
    }

    return false;
}

function requireLogin() {
    if (!isset($_SESSION['user_id'])) {
        header("Location: login.php");
        exit();
    }

    // automatyczne wylogowanie po 30 minutach bezczynności
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 1800)) {
        logoutUser();
        header("Location: login.php?timeout=1");
        exit();
    }

    $_SESSION['last_activity'] = time();
}

function logoutUser() {
    $_SESSION = [];
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    session_destroy();
}



function generateCsrfToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCsrfToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}
