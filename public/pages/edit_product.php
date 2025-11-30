<?php
require_once __DIR__ . '/../../src/auth.php';
requireLogin();

if ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'moderator') {
    die("❌ Brak dostępu");
}

$db = getDB();

// Pobierz produkt
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$stmt = $db->prepare("SELECT * FROM products WHERE id = ?");
$stmt->execute([$id]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$product) {
    die("❌ Produkt nie znaleziony");
}

// Zapis zmian
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    $price = (float)$_POST['price'];
    $stock = (int)$_POST['stock'];
    $category = trim($_POST['category']);

    $image_name = $product['image']; // działa gdy nie wgrywamy nowego zdjęcia

    if (!empty($_FILES['image']['name'])) {
        $target_dir = __DIR__ . "/../uploads/products/";
        $image_name = time() . "_" . basename($_FILES['image']['name']);
        $target_file = $target_dir . $image_name;

        move_uploaded_file($_FILES['image']['tmp_name'], $target_file);
    }

    $stmt = $db->prepare("
        UPDATE products SET name = ?, description = ?, price = ?, stock = ?, category = ?, image = ? WHERE id = ?
    ");
    $stmt->execute([$name, $description, $price, $stock, $category, $image_name, $id]);

    header("Location: shop.php?edit=success");
    exit;
}
?>

<!DOCTYPE html>
<html lang="pl">
<head>
<meta charset="UTF-8">
<title>Edytuj produkt</title>

<!-- 🔹 TinyMCE – edytor HTML -->
<script src="https://cdn.tiny.cloud/1/no-api-key/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script>
<script>
  tinymce.init({
    selector: 'textarea',
    plugins: 'link lists image',
    toolbar: 'undo redo | bold italic underline | bullist numlist | link image',
    menubar: false
  });
</script>

<style>
body { font-family: Arial; background:#f0f0f5; margin:0; }
.container {
  max-width:600px; margin:100px auto; background:white; padding:30px;
  border-radius:15px; box-shadow:0 0 10px rgba(0,0,0,0.1);
}
label { display:block; margin-top:15px; font-weight:bold; }
input, textarea, select {
  width:100%; padding:10px; margin-top:5px;
  border:1px solid #ccc; border-radius:8px;
}
button {
  background:#5865F2; color:white; border:none; border-radius:8px;
  padding:10px 20px; margin-top:20px; cursor:pointer;
}
button:hover { background:#4752c4; }
</style>
</head>
<body>

<?php include '../includes/navbar.php'; ?>

<div class="container">
  <h2>✏️ Edytuj produkt</h2>

  <form method="post" enctype="multipart/form-data">

    <label>Nazwa:</label>
    <input type="text" name="name" value="<?= htmlspecialchars($product['name']) ?>" required>

    <label>Opis (HTML dozwolony):</label>
    <textarea name="description" rows="5"><?= htmlspecialchars($product['description']) ?></textarea>

    <label>Cena:</label>
    <input type="number" name="price" step="0.01" value="<?= $product['price'] ?>" required>

    <label>Ilość:</label>
    <input type="number" name="stock" value="<?= $product['stock'] ?>" required>

    <label>Kategoria:</label>
    <select name="category">
        <option <?= $product['category'] == 'Karma' ? 'selected' : '' ?>>Karma</option>
        <option <?= $product['category'] == 'Zabawki' ? 'selected' : '' ?>>Zabawki</option>
        <option <?= $product['category'] == 'Akcesoria' ? 'selected' : '' ?>>Akcesoria</option>
        <option <?= $product['category'] == 'Ubrania' ? 'selected' : '' ?>>Ubrania</option>
    </select>

    <label>Aktualne zdjęcie:</label>
    <?php if ($product['image']): ?>
      <img src="../uploads/products/<?= $product['image'] ?>" width="150" style="border-radius:8px;">
    <?php else: ?>
      <p><i>Brak zdjęcia</i></p>
    <?php endif; ?>

    <label>Nowe zdjęcie (opcjonalnie):</label>
    <input type="file" name="image">

    <button type="submit">💾 Zapisz zmiany</button>
  </form>
</div>

</body>
</html>
