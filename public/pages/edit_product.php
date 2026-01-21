<?php
require_once __DIR__ . '/../../src/auth.php';
requireLogin();

if ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'moderator') {
    die("Brak dostępu");
}

$db = getDB();
$id = (int)($_GET['id'] ?? 0);

$stmt = $db->prepare("SELECT * FROM products WHERE id = ?");
$stmt->execute([$id]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$product) {
    die("Produkt nie istnieje");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $description = $_POST['description']; 
    $price = (float)$_POST['price'];
    $stock = (int)$_POST['stock'];

    $stmt = $db->prepare("
        UPDATE products
        SET name = ?, description = ?, price = ?, stock = ?
        WHERE id = ?
    ");
    $stmt->execute([$name, $description, $price, $stock, $id]);

    $_SESSION['msg'] = "✅ Produkt został zaktualizowany";
    header("Location: shop.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="pl">
<head>
<meta charset="UTF-8">
<title>Edytuj produkt</title>
<link rel="stylesheet" href="../assets/css/style.css">
<style>
.container{
  max-width:700px;
  margin:80px auto;
  background:white;
  padding:30px;
  border-radius:15px;
}
label{ font-weight:bold; margin-top:15px; display:block; }
input, textarea{
  width:100%;
  padding:10px;
  margin-top:5px;
  border-radius:8px;
  border:1px solid #ccc;
}
button{
  margin-top:20px;
  background:var(--primary);
  color:white;
  border:none;
  padding:10px 20px;
  border-radius:8px;
}
</style>
</head>
<body>

<?php include '../includes/navbar.php'; ?>

<div class="container">
<h2>✏️ Edytuj produkt</h2>

<form method="post">
  <label>Nazwa:</label>
  <input type="text" name="name" value="<?= htmlspecialchars($product['name']) ?>" required>

  <label>Opis (HTML dozwolony):</label>
  <textarea name="description" rows="6"><?= htmlspecialchars($product['description']) ?></textarea>

  <label>Cena (zł):</label>
  <input type="number" step="0.01" name="price" value="<?= $product['price'] ?>" required>

  <label>Stan magazynu:</label>
  <input type="number" name="stock" value="<?= $product['stock'] ?>" required>

  <button type="submit">💾 Zapisz zmiany</button>
</form>

</div>
</body>
</html>
