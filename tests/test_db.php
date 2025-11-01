<?php
require_once __DIR__ . '/../src/db.php';
try {
    $pdo = get_db();
    $stmt = $pdo->query('SELECT 1');
    echo "DB OK\n";
} catch (Exception $e) {
    echo "DB ERROR: " . $e->getMessage();
}
