<?php
$config = include __DIR__ . '/../../src/config_oauth.php';

$params = [
    'client_id' => $config['discord']['client_id'],
    'redirect_uri' => $config['discord']['redirect_uri'],
    'response_type' => 'code',
    'scope' => 'identify email'
];

$url = 'https://discord.com/api/oauth2/authorize?' . http_build_query($params);
header("Location: $url");
exit;
?>