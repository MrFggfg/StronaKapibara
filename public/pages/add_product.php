<?php
require_once __DIR__ . '/../../src/auth.php';
requireLogin();

if ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'moderator') {
    die("❌ Brak dostępu");
}

$db = getDB();
function addNotification($db, $user_id, $message) {
    $stmt = $db->prepare("INSERT INTO notifications (user_id, message) VALUES (?, ?)");
    $stmt->execute([$user_id, $message]);
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    $price = (float)$_POST['price'];
    $stock = (int)$_POST['stock'];
    $image_name = null;

    // 🔹 Obsługa przesyłania zdjęcia
    if (!empty($_FILES['image']['name'])) {
        $target_dir = __DIR__ . "/../uploads/products/";
        $image_name = time() . "_" . basename($_FILES['image']['name']);
        $target_file = $target_dir . $image_name;
        $file_type = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

        // Walidacja
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        if (!in_array($file_type, $allowed)) {
            die("Nieprawidłowy format zdjęcia (dozwolone: JPG, PNG, GIF)");
        }
        if ($_FILES['image']['size'] > 2 * 1024 * 1024) {
            die("Plik jest za duży (max 2MB)");
        }

        move_uploaded_file($_FILES['image']['tmp_name'], $target_file);
    }

    // 🔹 Zapis produktu w bazie
    $category = $_POST['category'];

    $stmt = $db->prepare("
        INSERT INTO products (name, description, price, stock, image, category)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([$name, $description, $price, $stock, $image_name, $category]);

    // 🔔 POWIADOMIENIE — tylko jeśli sesja istnieje
    if (isset($_SESSION['user_id'])) {
        addNotification($db, $_SESSION['user_id'], "🛒 Nowy produkt '$name' został dodany do sklepu.");
    }

    header("Location: shop.php");
    exit;
}

?>

<!DOCTYPE html>
<html lang="pl">
<head>
<meta charset="UTF-8">
<title>Dodaj produkt</title>
<style>
body { font-family: Arial; background:#f0f0f5; margin:0; }
.container {
  max-width:600px; margin:100px auto; background:white; padding:30px;
  border-radius:15px; box-shadow:0 0 10px rgba(0,0,0,0.1);
}
label { display:block; margin-top:15px; font-weight:bold; }
input, textarea {
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
<div class="container">
  <h2>➕ Dodaj produkt do sklepu</h2>
  <form method="post" enctype="multipart/form-data">
    <label>Nazwa produktu:</label>
    <input type="text" name="name" required>

    <label>Opis:</label>
    <textarea name="description" rows="4" required></textarea>

    <label>Cena (zł):</label>
    <input type="number" name="price" step="0.01" required>

    <label>Dostępna ilość:</label>
    <input type="number" name="stock" required>
<label>Kategoria:</label>
<select name="category" required>
    <option>Ubrania</option>
    <option>Zabawki</option>
    <option>Akcesoria</option>
</select>

    <label>Zdjęcie produktu:</label>
    <input type="file" name="image" accept="image/*" required>

    <button type="submit">💾 Zapisz produkt</button>
  </form>
</div>
</body>
</html>
