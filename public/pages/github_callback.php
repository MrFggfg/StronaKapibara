<?php
require_once __DIR__ . '/../../src/auth.php';
$config = include __DIR__ . '/../../src/config_oauth.php';

if (!isset($_GET['code'])) die('Brak kodu autoryzacji.');

// 1. Pobranie access_token
$opts = ['http' => [
    'method' => 'POST',
    'header' => "Accept: application/json\r\n",
    'content' => http_build_query([
        'client_id' => $config['github']['client_id'],
        'client_secret' => $config['github']['client_secret'],
        'code' => $_GET['code'],
        'redirect_uri' => $config['github']['redirect_uri'],
    ])
]];
$context = stream_context_create($opts);
$response = file_get_contents('https://github.com/login/oauth/access_token', false, $context);
$data = json_decode($response, true);
$token = $data['access_token'] ?? null;
if (!$token) die('Błąd tokena.');

// 2. Pobranie danych użytkownika
$opts = ['http' => ['header' => "User-Agent: CapyWorld\r\nAuthorization: token $token\r\n"]];
$context = stream_context_create($opts);
$user_info = json_decode(file_get_contents('https://api.github.com/user', false, $context), true);

$email = $user_info['email'] ?? ($user_info['id'].'@github.local');
$username = $user_info['login'];
$avatar = $user_info['avatar_url'] ?? null;

// 3. Logowanie / rejestracja użytkownika
$db = getDB();
$stmt = $db->prepare("SELECT * FROM users WHERE email = :e");
$stmt->execute([':e' => $email]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    $stmt = $db->prepare("INSERT INTO users (username, email, password_hash) VALUES (:u,:e,'gh_oauth')");
    $stmt->execute([':u' => $username, ':e' => $email]);
    $user_id = $db->lastInsertId();
} else {
    $user_id = $user['id'];
}

$_SESSION['user_id'] = $user_id;
$_SESSION['username'] = $username;

header("Location: dashboard.php");
exit;
