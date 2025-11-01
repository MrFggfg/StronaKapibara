<?php
require_once __DIR__ . '/../db.php';

class User {
    public static function findByEmail(string $email) {
        $pdo = get_db();
        $stmt = $pdo->prepare('SELECT * FROM users WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);
        return $stmt->fetch();
    }

    public static function findById(int $id) {
        $pdo = get_db();
        $stmt = $pdo->prepare('SELECT id, name, email FROM users WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        return $stmt->fetch();
    }
}
