<?php
require_once __DIR__ . '/../../src/auth.php';
$config = include __DIR__ . '/../../src/config_oauth.php';

if (!isset($_GET['code'])) die('Brak kodu autoryzacji.');

// 1️⃣ Wymiana code → access_token
$token_url = 'https://discord.com/api/oauth2/token';
$data = [
    'client_id' => $config['discord']['client_id'],
    'client_secret' => $config['discord']['client_secret'],
    'grant_type' => 'authorization_code',
    'code' => $_GET['code'],
    'redirect_uri' => $config['discord']['redirect_uri']
];
$options = [
    'http' => [
        'method' => 'POST',
        'header' => "Content-Type: application/x-www-form-urlencoded\r\n",
        'content' => http_build_query($data)
    ]
];
$response = file_get_contents($token_url, false, stream_context_create($options));
$token_data = json_decode($response, true);

$access_token = $token_data['access_token'] ?? null;
if (!$access_token) die('Błąd tokena.');

// 2️⃣ Pobranie danych użytkownika
$opts = ['http' => [
    'header' => "Authorization: Bearer $access_token\r\nUser-Agent: CapyWorld\r\n"
]];
$context = stream_context_create($opts);
$user_info = json_decode(file_get_contents('https://discord.com/api/users/@me', false, $context), true);

$email = $user_info['email'] ?? ($user_info['id'].'@discord.local');
$username = $user_info['username'];
$discriminator = $user_info['discriminator'];
$avatar = isset($user_info['avatar'])
    ? "https://cdn.discordapp.com/avatars/{$user_info['id']}/{$user_info['avatar']}.png"
    : null;

// 3️⃣ Logowanie / rejestracja
$db = getDB();
$stmt = $db->prepare("SELECT * FROM users WHERE email = :e");
$stmt->execute([':e' => $email]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    $stmt = $db->prepare("INSERT INTO users (username, email, password_hash) VALUES (:u,:e,'discord_oauth')");
    $stmt->execute([':u' => "$username#$discriminator", ':e' => $email]);
    $user_id = $db->lastInsertId();
} else {
    $user_id = $user['id'];
}

$_SESSION['user_id'] = $user_id;
$_SESSION['username'] = "$username#$discriminator";

header("Location: dashboard.php");
exit;
?>