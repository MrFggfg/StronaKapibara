<?php
require_once __DIR__ . '/../../src/auth.php';
requireLogin();
$db = getDB();

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['photo'])) {
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $file = $_FILES['photo'];

    if ($file['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];

        if (in_array($ext, $allowed)) {
            $newName = uniqid('photo_', true) . '.' . $ext;
            $target = __DIR__ . '/../uploads/' . $newName;

            if (move_uploaded_file($file['tmp_name'], $target)) {
                $stmt = $db->prepare("INSERT INTO photos (user_id, filename, title, description) VALUES (:uid, :f, :t, :d)");
                $stmt->execute([
                    ':uid' => $_SESSION['user_id'],
                    ':f' => $newName,
                    ':t' => $title,
                    ':d' => $description
                ]);
                $message = "✅ Zdjęcie zostało dodane!";
            } else {
                $message = "❌ Nie udało się zapisać pliku.";
            }
        } else {
            $message = "❌ Dozwolone formaty: JPG, PNG, GIF.";
        }
    } else {
        $message = "❌ Błąd podczas przesyłania pliku.";
    }
}
?>

<!DOCTYPE html>
<html lang="pl">
<head>
<meta charset="UTF-8">
<title>Dodaj zdjęcie</title>
<style>
body { font-family: Arial; background:#f5f5f5; margin:0; }

.navbar {
  display:flex; justify-content:space-between; align-items:center;
  background:#5865F2; padding:12px 40px; color:white;
  box-shadow:0 2px 8px rgba(0,0,0,0.1);
}
.navbar .logo { font-weight:bold; font-size:1.2em; }
.navbar a {
  color:white; text-decoration:none; margin-left:20px;
  transition:0.2s; font-weight:500;
}
.navbar a:hover { text-decoration:underline; }

.container {
  max-width: 600px; margin: 80px auto; background:white; padding: 30px;
  border-radius: 15px; box-shadow: 0 0 10px rgba(0,0,0,0.1);
}
input, textarea {
  width:100%; padding:10px; margin:10px 0; border:1px solid #ccc; border-radius:8px;
}
.btn {
  display:inline-block; padding:10px 20px;
  background:#5865F2; color:white; border:none; border-radius:8px; cursor:pointer;
}
</style>
</head>
<body>

  <!-- 🔹 Pasek nawigacji -->
  <div class="navbar">
    <div class="logo"> CapyWorld</div>
    <div class="links">
      <a href="../index.php">Strona główna</a>
      <a href="gallery.php">Galeria</a>
      <a href="upload_photo.php">Dodaj zdjęcie</a>
      <a href="posts.php">Posty</a>
      <a href="../../src/logout.php">Wyloguj</a>
    </div>
  </div>

<div class="container">
  <h2>📤 Dodaj zdjęcie do galerii</h2>
  <?php if ($message): ?>
    <p><?= htmlspecialchars($message) ?></p>
  <?php endif; ?>
  <form method="post" enctype="multipart/form-data">
    <label>Tytuł:</label>
    <input type="text" name="title" required>
    <label>Opis:</label>
    <textarea name="description"></textarea>
    <label>Wybierz zdjęcie:</label>
    <input type="file" name="photo" accept="image/*" required>
    <button class="btn" type="submit">Dodaj</button>
  </form>
  <p><a href="gallery.php">📸 Zobacz galerię</a></p>
</div>
</body>
</html>
