<?php
// src/db.php

function getDB() {
    static $db = null;

    if ($db === null) {
        $config = include __DIR__ . '/config.php';
        try {
            $db = new PDO(
                "mysql:host={$config['db_host']};dbname={$config['db_name']};charset=utf8mb4",
                $config['db_user'],
                $config['db_pass'],
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );
        } catch (PDOException $e) {
            die("Błąd połączenia z bazą: " . $e->getMessage());
        }
    }

    return $db;
}
