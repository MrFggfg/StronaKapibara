<?php
require_once __DIR__ . '/../../src/auth.php';
requireLogin();
$db = getDB();

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 🔹 Usuń produkt z koszyka
if (isset($_POST['remove_id'])) {
    $id = (int)$_POST['remove_id'];
    unset($_SESSION['cart'][$id]);
    header("Location: cart.php");
    exit;
}

if (isset($_POST['checkout']) && !empty($_SESSION['cart'])) {
    $user_id = $_SESSION['user_id'];
    $fullname = trim($_POST['fullname']);
    $address = trim($_POST['address']);
    $email = trim($_POST['email']);

    $total = 0;
    foreach ($_SESSION['cart'] as $item) {
        $total += $item['price'] * $item['quantity'];
    }

    // 🔹 Zapisz zamówienie
    $stmt = $db->prepare("INSERT INTO orders (user_id, total, status, created_at) VALUES (?, ?, 'pending', NOW())");
    $stmt->execute([$user_id, $total]);
    $order_id = $db->lastInsertId();

    // 🔹 Zapisz produkty
    $stmt = $db->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
    foreach ($_SESSION['cart'] as $item) {
        $stmt->execute([$order_id, $item['id'], $item['quantity'], $item['price']]);
    }

    // 🔹 Wyczyść koszyk
    $_SESSION['cart'] = [];

    // 🔹 Wyślij potwierdzenie e-mail
    $subject = "CapyWorld - potwierdzenie zamówienia #$order_id";
    $message = "Dziękujemy za złożenie zamówienia w CapyWorld!\n\n".
               "Numer zamówienia: #$order_id\n".
               "Imię i nazwisko: $fullname\n".
               "Adres: $address\n".
               "Kwota: " . number_format($total, 2) . " zł\n\n".
               "Status: Oczekuje na płatność 💸\n\n".
               "Pozdrawiamy,\nZespół CapyWorld 🐹";

    $headers = "From: CapyWorld <no-reply@capyworld.local>\r\nContent-Type: text/plain; charset=UTF-8\r\n";
    @mail($email, $subject, $message, $headers);

    $message = "✅ Zamówienie zostało złożone! Sprawdź e-mail z potwierdzeniem.";
}

?>

<!DOCTYPE html>
<html lang="pl">
<head>
<meta charset="UTF-8">
<title>Twój koszyk</title>
<style>
body { font-family: Arial; background:#f8f8fb; margin:0; }
.navbar {
  display:flex; justify-content:space-between; align-items:center;
  background:#5865F2; padding:12px 40px; color:white;
}
.navbar a { color:white; text-decoration:none; margin-left:20px; }
.container {
  max-width:900px; margin:80px auto; background:white; padding:30px;
  border-radius:15px; box-shadow:0 0 10px rgba(0,0,0,0.1);
}
table { width:100%; border-collapse:collapse; }
th, td { border-bottom:1px solid #ddd; padding:10px; text-align:left; }
th { background:#f2f2f2; }
.total { text-align:right; font-weight:bold; font-size:1.1em; }
button {
  background:#5865F2; color:white; border:none; border-radius:8px;
  padding:10px 20px; cursor:pointer; margin-top:15px;
}
button:hover { background:#4752c4; }
.remove-btn { background:#e74c3c; padding:6px 10px; }
.message { color:green; font-weight:bold; text-align:center; margin-bottom:20px; }
</style>
</head>
<body>

<?php include '../includes/navbar.php'; ?>


<div class="container">
  <h2>🛒 Twój koszyk</h2>
  <?php if (!empty($message)) echo "<p class='message'>$message</p>"; ?>

  <?php if (empty($_SESSION['cart'])): ?>
    <p>Twój koszyk jest pusty.</p>
  <?php else: ?>
    <form method="post">
      <table>
        <tr><th>Produkt</th><th>Ilość</th><th>Cena</th><th>Suma</th><th></th></tr>
        <?php $total = 0; foreach ($_SESSION['cart'] as $item): 
          $subtotal = $item['price'] * $item['quantity'];
          $total += $subtotal;
        ?>
        <tr>
          <td><?= htmlspecialchars($item['name']) ?></td>
          <td><?= $item['quantity'] ?></td>
          <td><?= number_format($item['price'], 2) ?> zł</td>
          <td><?= number_format($subtotal, 2) ?> zł</td>
          <td>
            <form method="post">
              <input type="hidden" name="remove_id" value="<?= $item['id'] ?>">
              <button class="remove-btn">Usuń</button>
            </form>
          </td>
        </tr>
        <?php endforeach; ?>
      </table>
</table>
<p class="total">Razem: <?= number_format($total, 2) ?> zł</p>

<div style="margin-top:30px; background:#f9f9ff; padding:20px; border-radius:12px;">
  <h3>📦 Dane wysyłki</h3>
  <?php
    // Pobierz dane użytkownika
    $stmt = $db->prepare("SELECT email, address FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
  ?>
  <form method="post">
    <label>Imię i nazwisko:</label><br>
    <input type="text" name="fullname" value="<?= htmlspecialchars($_SESSION['username']) ?>" required style="width:100%; padding:10px; border-radius:8px; border:1px solid #ccc;"><br><br>

    <label>Adres wysyłki:</label><br>
    <textarea name="address" rows="3" required style="width:100%; padding:10px; border-radius:8px; border:1px solid #ccc;"><?= htmlspecialchars($user['address'] ?? '') ?></textarea><br><br>

    <label>Email kontaktowy:</label><br>
    <input type="email" name="email" value="<?= htmlspecialchars($user['email'] ?? $_SESSION['email'] ?? '') ?>" required style="width:100%; padding:10px; border-radius:8px; border:1px solid #ccc;"><br><br>

    <button type="submit" name="checkout" style="background:#5865F2; color:white; border:none; padding:10px 20px; border-radius:8px; cursor:pointer;">✅ Potwierdź zamówienie</button>
  </form>
</div>

    </form>
  <?php endif; ?>
</div>

</body>
</html>
