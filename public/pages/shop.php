<?php
require_once __DIR__ . '/../../src/auth.php';
requireLogin();

$db = getDB();
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../../src/auth.php';
requireLogin();

$db = getDB();

// 🔹 Dodawanie produktu do koszyka
if (isset($_POST['add_to_cart'])) {
    $product_id = (int)$_POST['product_id'];
    $quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 1;

    // Pobierz dane produktu
    $stmt = $db->prepare("SELECT id, name, price, stock FROM products WHERE id = ?");
    $stmt->execute([$product_id]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($product) {
        if (!isset($_SESSION['cart'])) $_SESSION['cart'] = [];

        // Jeśli produkt już jest w koszyku → zwiększ ilość
        if (isset($_SESSION['cart'][$product_id])) {
            $_SESSION['cart'][$product_id]['quantity'] += $quantity;
        } else {
            $_SESSION['cart'][$product_id] = [
                'id' => $product['id'],
                'name' => $product['name'],
                'price' => $product['price'],
                'quantity' => $quantity
            ];
        }
    }

    header("Location: cart.php");
    exit;
}

// 🔹 Usuwanie produktu (tylko admin)
if ($_SESSION['role'] === 'admin' && isset($_POST['delete_product_id'])) {
    $id = (int)$_POST['delete_product_id'];
    $stmt = $db->prepare("DELETE FROM products WHERE id = ?");
    $stmt->execute([$id]);
    header("Location: shop.php");
    exit;
}

// 🔹 Pobranie produktów
$products = $db->query("SELECT * FROM products ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="pl">
<head>
<meta charset="UTF-8">
<title>Sklep CapyWorld</title>
<style>
body { font-family: Arial; background:#f0f0f5; margin:0; }
.navbar {
  display:flex; justify-content:space-between; align-items:center;
  background:#5865F2; padding:12px 40px; color:white;
  box-shadow:0 2px 8px rgba(0,0,0,0.1);
}
.navbar .logo { font-weight:bold; font-size:1.2em; }
.navbar a { color:white; text-decoration:none; margin-left:20px; }
.navbar a:hover { text-decoration:underline; }

.container {
  max-width:1000px; margin:80px auto; background:white; padding:30px;
  border-radius:15px; box-shadow:0 0 10px rgba(0,0,0,0.1);
}

.products {
  display:grid;
  grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
  gap:20px;
}
body {
  font-family: Arial, sans-serif;
  background-color: #f5f6fa;
  margin: 0;
  padding: 0;
}

.container {
  max-width: 1200px;
  margin: 60px auto;
  padding: 20px;
}

.products-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
  gap: 20px;
}

.card {
  background: white;
  border-radius: 12px;
  box-shadow: 0 2px 10px rgba(0,0,0,0.1);
  overflow: hidden;
  transition: transform 0.2s ease;
}

.card:hover {
  transform: translateY(-5px);
}

.card img {
  width: 100%;
  height: 200px;
  object-fit: cover;
}

.card .info {
  padding: 15px;
}

.card .info h3 {
  margin: 0;
  font-size: 1.2em;
}

.card .info p {
  color: #666;
  font-size: 0.95em;
  margin-top: 5px;
}

.card .price {
  color: #5865F2;
  font-weight: bold;
  font-size: 1.1em;
  margin-top: 10px;
}

.card button {
  margin-top: 10px;
  background: #5865F2;
  color: white;
  border: none;
  padding: 10px 15px;
  border-radius: 8px;
  cursor: pointer;
  transition: 0.2s;
}

.card button:hover {
  background: #4752c4;
}

.card {
  border-radius:10px; background:white; box-shadow:0 0 8px rgba(0,0,0,0.1);
  display:flex; flex-direction:column; justify-content:space-between;
  transition: transform 0.2s ease;
}
.card:hover { transform: scale(1.03); }
.card .info { padding:15px; }
.card h3 { margin:0 0 5px 0; }
.price { color:#27ae60; font-weight:bold; font-size:1.1em; }

button {
  background:#5865F2; color:white; border:none; border-radius:8px;
  padding:8px 16px; cursor:pointer; margin-top:10px;
}
button:hover { background:#4752c4; }
.delete-btn {
  background:#e74c3c; margin-left:5px;
}
.products-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
  gap: 20px;
  margin-top: 20px;
}

.card {
  background: white;
  border-radius: 12px;
  box-shadow: 0 2px 10px rgba(0,0,0,0.1);
  overflow: hidden;
  transition: transform 0.2s ease;
}

.card:hover {
  transform: translateY(-5px);
}

.card .info {
  padding: 15px;
}

.card .price {
  color: #5865F2;
  font-weight: bold;
  margin-top: 8px;
}

.delete-btn,
.card button {
  background: #5865F2;
  color: white;
  border: none;
  border-radius: 8px;
  padding: 8px 15px;
  cursor: pointer;
  transition: 0.2s;
}

.delete-btn:hover,
.card button:hover {
  background: #4752c4;
}

</style>
</head>
<body>

<?php include '../includes/navbar.php'; ?>


<div class="container">
  <h2>🛍️ Sklep CapyWorld</h2>

  <div class="products-grid">
    <?php foreach ($products as $p): ?>
      <div class="card">
        <?php if (!empty($p['image'])): ?>
          <img src="../uploads/products/<?= htmlspecialchars($p['image']) ?>" alt="<?= htmlspecialchars($p['name']) ?>" style="width:100%; height:200px; object-fit:cover; border-radius:10px 10px 0 0;">
        <?php endif; ?>
        <div class="info">
          <h3><?= htmlspecialchars($p['name']) ?></h3>
          <p><?= nl2br(htmlspecialchars($p['description'])) ?></p>
          <p class="price"><?= number_format($p['price'], 2) ?> zł</p>
          <p><small>Dostępne: <?= (int)$p['stock'] ?> szt.</small></p>

          <?php if ($_SESSION['role'] === 'admin'): ?>
            <form method="post" style="margin-top:10px;">
              <input type="hidden" name="delete_product_id" value="<?= $p['id'] ?>">
              <button class="delete-btn" onclick="return confirm('Na pewno usunąć ten produkt?')">Usuń</button>
            </form>
          <?php else: ?>
            <form method="post" style="margin-top:10px;">
              <input type="hidden" name="product_id" value="<?= $p['id'] ?>">
              <input type="number" name="quantity" value="1" min="1" style="width:60px;">
              <button type="submit" name="add_to_cart">🛒 Dodaj do koszyka</button>
            </form>
          <?php endif; ?>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
</div>


</body>
</html>
