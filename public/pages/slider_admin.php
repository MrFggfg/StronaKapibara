<?php
require_once __DIR__ . '/../../src/auth.php';
requireLogin();

if ($_SESSION['role'] !== 'admin') {
    die("Brak dostępu");
}

$db = getDB();

// Zmiana statusu zdjęcia
if (isset($_POST['photo_id'])) {
    $id = (int)$_POST['photo_id'];
    $toggle = $_POST['toggle'] === '1' ? 1 : 0;
    $stmt = $db->prepare("UPDATE photos SET in_slider = :val WHERE id = :id");
    $stmt->execute([':val' => $toggle, ':id' => $id]);
}

// Pobranie wszystkich zdjęć
$photos = $db->query("
  SELECT photos.*, users.username 
  FROM photos 
  JOIN users ON photos.user_id = users.id
  ORDER BY photos.created_at DESC
")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="pl">
<head>
<meta charset="UTF-8">
<title>Panel slidera</title>
<style>
body { font-family: Arial; background:#f5f5f5; margin:0; }
.navbar {
  display:flex; justify-content:space-between; align-items:center;
  background:#5865F2; padding:12px 40px; color:white;
  box-shadow:0 2px 8px rgba(0,0,0,0.1);
}
.navbar a { color:white; text-decoration:none; margin-left:20px; }
.container {
  max-width:900px; margin:80px auto; background:white; padding:20px;
  border-radius:10px; box-shadow:0 0 10px rgba(0,0,0,0.1);
}
.photo-list { display:grid; grid-template-columns:repeat(auto-fill, minmax(250px,1fr)); gap:20px; }
.photo-card {
  border:1px solid #ccc; border-radius:10px; overflow:hidden;
  display:flex; flex-direction:column; justify-content:space-between;
}
.photo-card img { width:100%; height:180px; object-fit:cover; }
.info { padding:10px; }
button {
  margin:10px; padding:8px 12px; border:none; border-radius:8px;
  cursor:pointer; color:white;
}
.btn-on { background:#27ae60; }
.btn-off { background:#e74c3c; }
</style>
</head>
<body>

<div class="navbar">
  <div class="logo">🐹 CapyWorld</div>
  <div>
    <a href="../index.php">Strona główna</a>
    <a href="gallery.php">Galeria</a>
    <a href="upload_photo.php">Dodaj zdjęcie</a>
    <a href="../../src/logout.php">Wyloguj</a>
  </div>
</div>

<div class="container">
  <h2>🎛️ Panel zarządzania sliderem</h2>
  <div class="photo-list">
    <?php foreach ($photos as $p): ?>
      <div class="photo-card">
        <img src="../uploads/<?= htmlspecialchars($p['filename']) ?>" alt="">
        <div class="info">
          <b><?= htmlspecialchars($p['title']) ?></b><br>
          <small><?= htmlspecialchars($p['username']) ?></small><br>
          <small><?= htmlspecialchars($p['description']) ?></small>
        </div>
        <form method="post">
          <input type="hidden" name="photo_id" value="<?= $p['id'] ?>">
          <?php if ($p['in_slider']): ?>
            <input type="hidden" name="toggle" value="0">
            <button class="btn-off">Usuń ze slidera</button>
          <?php else: ?>
            <input type="hidden" name="toggle" value="1">
            <button class="btn-on">Dodaj do slidera</button>
          <?php endif; ?>
        </form>
      </div>
    <?php endforeach; ?>
  </div>
</div>
</body>
</html>
