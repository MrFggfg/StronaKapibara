<?php
require_once __DIR__ . '/../../src/auth.php';
requireLogin();

$db = getDB();
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
<title>Galeria</title>
<style>
body { font-family: Arial; background:#fafafa; margin:0; }

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
  max-width: 900px; margin: 80px auto; background:white; padding: 20px;
  border-radius: 10px; box-shadow: 0 0 8px rgba(0,0,0,0.1);
}
.gallery {
  display:grid; grid-template-columns: repeat(auto-fill,minmax(200px,1fr));
  gap:20px; margin-top:20px;
}
.photo {
  border-radius:8px; overflow:hidden; box-shadow:0 0 5px rgba(0,0,0,0.1);
}
.photo img { width:100%; height:200px; object-fit:cover; display:block; }
.photo-info { padding:10px; }
</style>
</head>
<body>

  <!-- 🔹 Pasek nawigacji -->
  <div class="navbar">
    <div class="logo">🐹 CapyWorld</div>
    <div class="links">
      <a href="../index.php">Strona główna</a>
      <a href="gallery.php">Galeria</a>
      <a href="upload_photo.php">Dodaj zdjęcie</a>
      <a href="posts.php">Posty</a>
      <a href="../../src/logout.php">Wyloguj</a>
    </div>
  </div>

  <div class="container">
    <h2>📸 Galeria użytkowników</h2>
    <div class="gallery">
      <?php foreach ($photos as $p): ?>
        <div class="photo">
          <img src="../uploads/<?= htmlspecialchars($p['filename']) ?>" alt="">
          <div class="photo-info">
            <b><?= htmlspecialchars($p['title'] ?: 'Bez tytułu') ?></b><br>
            <small>Autor: <?= htmlspecialchars($p['username']) ?></small>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>

</body>
</html>
