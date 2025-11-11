<?php
require_once __DIR__ . '/../../src/auth.php';
requireLogin();

if ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'moderator') {
    http_response_code(403);
    echo "Brak dostępu ❌";
    exit;
}

$db = getDB();
$order_id = (int)($_GET['id'] ?? 0);

$stmt = $db->prepare("
  SELECT o.*, u.username, u.email, u.address
  FROM orders o
  JOIN users u ON o.user_id = u.id
  WHERE o.id = ?
");
$stmt->execute([$order_id]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    echo "<p>❌ Nie znaleziono zamówienia.</p>";
    exit;
}

$stmt = $db->prepare("
  SELECT p.name, oi.quantity, oi.price
  FROM order_items oi
  JOIN products p ON oi.product_id = p.id
  WHERE oi.order_id = ?
");
$stmt->execute([$order_id]);
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<p><b>Numer zamówienia:</b> #{$order['id']}</p>";
echo "<p><b>Użytkownik:</b> " . htmlspecialchars($order['username']) . " (" . htmlspecialchars($order['email']) . ")</p>";
echo "<p><b>Adres:</b> " . htmlspecialchars($order['address']) . "</p>";
echo "<p><b>Status:</b> " . htmlspecialchars($order['status']) . "</p>";
echo "<p><b>Kwota łączna:</b> " . number_format($order['total'], 2) . " zł</p>";
echo "<p><b>Data:</b> {$order['created_at']}</p>";
echo "<hr>";

if (empty($items)) {
    echo "<p>Brak pozycji w tym zamówieniu.</p>";
} else {
    echo "<table style='width:100%; border-collapse:collapse;'>";
    echo "<tr style='background:#f0f0f5;'><th>Produkt</th><th>Ilość</th><th>Cena</th></tr>";
    foreach ($items as $i) {
        echo "<tr style='border-bottom:1px solid #ddd;'>
                <td>" . htmlspecialchars($i['name']) . "</td>
                <td>{$i['quantity']}</td>
                <td>" . number_format($i['price'], 2) . " zł</td>
              </tr>";
    }
    echo "</table>";
}
