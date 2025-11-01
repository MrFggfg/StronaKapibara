<?php
$config = require __DIR__ . '/config.php';
$db = $config['db'];

$dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=%s', $db['host'], $db['port'], $db['dbname'], $db['charset']);

try {
    $pdo = new PDO($dsn, $db['user'], $db['pass'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (PDOException $e) {
    // For development show a simple message
    http_response_code(500);
    echo 'DB connection error: ' . htmlspecialchars($e->getMessage());
    exit;
}

// Convenience function
function get_db(): PDO {
    global $pdo;
    return $pdo;
}
