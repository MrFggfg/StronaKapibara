<?php
require_once __DIR__ . '/../../src/auth.php';
requireLogin();

$db = getDB();
$stmt = $db->prepare("SELECT role, email FROM users WHERE id = :id");
$stmt->execute([':id' => $_SESSION['user_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    echo "Nie znaleziono użytkownika.";
    exit;
}

$_SESSION['role'] = $user['role']; // zapamiętaj rolę w sesji
$_SESSION['email'] = $user['email'];

if ($user['role'] === 'admin') {
    include 'dashboard_admin.php';
} else {
    include 'dashboard_user.php';
}
