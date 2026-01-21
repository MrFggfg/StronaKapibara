<?php
require_once __DIR__ . '/auth.php';
logoutUser();
header("Location: ../public/pages/login.php");
setcookie('remember_me', '', time() - 3600, '/');

$stmt = $db->prepare("UPDATE users SET remember_token = NULL WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);

session_destroy();

exit();
