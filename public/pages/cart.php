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

// 🔹 Złożenie zamówienia
if (isset($_POST['checkout']) && !empty($_SESSION['cart'])) {
    $user_id = $_SESSION['user_id'];
    $total = 0;
    foreach ($_SESSION['cart'] as $item) {
        $total += $item['price'] * $item['quantity'];
    }

    $stmt = $db->prepare("INSERT INTO orders (user_id, total, status) VALUES (?, ?, 'pending')");
    $stmt->execute([$user_id, $total]);
    $order_id = $db->lastInsertId();

    // Zapisz pozycje zamówienia
    $stmt = $db->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
    foreach ($_SESSION['cart'] as $item) {
        $stmt->execute([$order_id, $item['id'], $item['quantity'], $item['price']]);
    }

    // (tu dodamy email w następnym kroku)
    $_SESSION['cart'] = [];
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
      <p class="total">Razem: <?= number_format($total, 2) ?> zł</p>
      <button type="submit" name="checkout">💳 Złóż zamówienie</button>
    </form>
  <?php endif; ?>
</div>

</body>
</html>
