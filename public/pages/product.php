<?php
require_once __DIR__ . '/../../src/auth.php';
requireLogin();
$db = getDB();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$stmt = $db->prepare("SELECT * FROM products WHERE id = ?");
$stmt->execute([$id]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$product) {
    die("❌ Produkt nie znaleziony");
}
?>

<!DOCTYPE html>
<html lang="pl">
<head>
<meta charset="UTF-8">
<title><?= htmlspecialchars($product['name']) ?></title>
<style>
/* 🔹 Ogólny styl stron */
body { font-family: Arial; background:#f4f4f4; margin:0; }

/* 🔹 Pasek nawigacji */
.navbar {
  display:flex; justify-content:space-between; align-items:center;
  background:#5865F2; padding:12px 40px; color:white;
  box-shadow:0 2px 8px rgba(0,0,0,0.1);
}
.navbar a {
  color:white; text-decoration:none; margin-left:20px; font-weight:500;
}
.navbar a:hover { text-decoration:underline; }

/* 🔹 Kontener główny */
.container {
  max-width: 900px; margin: 80px auto; background: white; padding: 30px;
  border-radius: 15px; box-shadow: 0 3px 10px rgba(0,0,0,0.1);
}

/* 🔹 Układ produktu */
.product-box { display: flex; gap: 30px; flex-wrap: wrap; }

.product-image {
  width:300px; height:300px; 
  background:#ddd; border-radius:10px; 
  display:flex; align-items:center; justify-content:center;
  font-size: 14px; color:#666;
}

.product-info h2 { margin-top:0; }
.product-price { font-size:1.4em; color:#5865F2; font-weight:bold; }

/* 🔹 Przycisk */
button {
  background:#5865F2; color:white; border:none; border-radius:8px;
  padding:10px 20px; cursor:pointer; margin-top:15px;
}
button:hover { background:#4752c4; }

.back-link {
  display:inline-block; margin-top:20px; 
  color:#5865F2; text-decoration:none; font-weight:bold;
}
.back-link:hover { text-decoration:underline; }

</style>
</head>
<body>

<?php include '../includes/navbar.php'; ?>

<div class="container">

  <div class="product-box">

    <!-- 🔹 Miejsce na zdjęcie (zrobimy później) -->
    <div class="product-image">
      <?php if (!empty($product['image'])): ?>
        <img src="../uploads/products/<?= htmlspecialchars($product['image']) ?>" 
             style="max-width:100%; max-height:100%; border-radius:10px;">
      <?php else: ?>
        (brak zdjęcia produktu)
      <?php endif; ?>
    </div>

    <!-- 🔹 Info o produkcie -->
    <div class="product-info">
      <h2><?= htmlspecialchars($product['name']) ?></h2>
      <p><b>Kategoria:</b> <?= htmlspecialchars($product['category'] ?? 'Brak kategorii') ?></p>
      <p><?= nl2br(htmlspecialchars($product['description'])) ?></p>
      <p class="product-price"><?= number_format($product['price'], 2) ?> zł</p>
      <p><b>Dostępne:</b> <?= (int)$product['stock'] ?> szt.</p>

      <!-- 🔹 Dodawanie do koszyka -->
      <?php if ($_SESSION['role'] !== 'admin'): ?>
      <form method="post" action="cart.php">
        <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
        <input type="number" name="quantity" value="1" min="1" style="width:60px;">
        <button type="submit" name="add_to_cart">🛒 Dodaj do koszyka</button>
      </form>
      <?php endif; ?>
    </div>

  </div>

  <a href="shop.php" class="back-link">⬅️ Wróć do sklepu</a>

</div>
</body>
</html>
