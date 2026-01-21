<?php
require_once __DIR__ . '/../../src/auth.php';
requireLogin();

$db = getDB();
$theme = $_POST['theme'] ?? 'theme-capybara';

$allowed = ['theme-capybara', 'theme-night', 'theme-forest'];
if (!in_array($theme, $allowed)) {
    http_response_code(400);
    exit;
}

$stmt = $db->prepare("UPDATE users SET theme = ? WHERE id = ?");
$stmt->execute([$theme, $_SESSION['user_id']]);

echo "ok";
