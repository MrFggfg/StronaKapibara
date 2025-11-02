<?php
$config = include __DIR__ . '/../../src/config_oauth.php';
$params = [
    'client_id' => $config['github']['client_id'],
    'redirect_uri' => $config['github']['redirect_uri'],
    'scope' => 'read:user user:email',
];
$url = 'https://github.com/login/oauth/authorize?' . http_build_query($params);
header("Location: $url");
exit;
?>