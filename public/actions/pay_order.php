<?php
require_once __DIR__ . '/../../src/auth.php';
requireLogin();
$db = getDB();

$order_id = (int)($_POST['order_id'] ?? 0);

// 🔔 Funkcja powiadomień
function addNotification(PDO $db, int $user_id, string $message): void {
    $stmt = $db->prepare(
        "INSERT INTO notifications (user_id, message) VALUES (?, ?)"
    );
    $stmt->execute([$user_id, $message]);
}

// 🔹 Pobierz zamówienie (TYLKO pending)
$stmt = $db->prepare("
    SELECT * FROM orders
    WHERE id = ? AND user_id = ? AND status = 'pending'
");
$stmt->execute([$order_id, $_SESSION['user_id']]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

// ❌ Jeśli nie ma takiego zamówienia → koniec
if (!$order) {
    header("Location: ../pages/dashboard_user.php?error=not_pending");
    exit;
}

// ✅ ZMIANA STATUSU (SANDBOX PŁATNOŚCI)
$stmt = $db->prepare("UPDATE orders SET status = 'paid' WHERE id = ?");
$stmt->execute([$order_id]);

// 🔔 Powiadomienie użytkownika
addNotification(
    $db,
    $_SESSION['user_id'],
    "💳 Zamówienie #$order_id zostało opłacone"
);

// 🔔 Powiadomienie adminów
$stmt = $db->query("SELECT id FROM users WHERE role = 'admin'");
foreach ($stmt as $admin) {
    addNotification(
        $db,
        $admin['id'],
        "📦 Zamówienie #$order_id zostało opłacone"
    );
}

// 🔁 POWRÓT DO DASHBOARDU
header("Location: ../pages/dashboard_user.php?paid=1");
exit;
