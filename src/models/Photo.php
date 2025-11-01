<?php
require_once __DIR__ . '/../db.php';

class Photo {
    public static function createForUser(int $userId, string $filename) {
        $pdo = get_db();
        $stmt = $pdo->prepare('INSERT INTO photos (user_id, filename, created_at) VALUES (?, ?, NOW())');
        $stmt->execute([$userId, $filename]);
        return $pdo->lastInsertId();
    }

    public static function all() {
        $pdo = get_db();
        $stmt = $pdo->query('SELECT p.*, u.name as user_name FROM photos p LEFT JOIN users u ON p.user_id = u.id ORDER BY p.created_at DESC');
        return $stmt->fetchAll();
    }
}
