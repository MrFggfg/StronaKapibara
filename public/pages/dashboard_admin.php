<?php
require_once __DIR__ . '/../../src/auth.php';
requireLogin();

$db = getDB();
$users = $db->query("SELECT id, username, email, role, created_at FROM users ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="pl">
<head>
<meta charset="UTF-8">
<title>Panel administratora</title>
<style>
body { font-family: Arial; background:#f0f0f5; margin:0; }

/* 🔹 Pasek nawigacji */
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

/* 🔹 Zawartość panelu */
.container {
  max-width: 900px; margin: 80px auto; background: white; padding: 30px;
  border-radius: 15px; box-shadow: 0 0 10px rgba(0,0,0,0.1);
}
table { width:100%; border-collapse: collapse; margin-top:20px; }
th, td { padding:10px; border-bottom:1px solid #ccc; text-align:left; }
th { background:#5865F2; color:white; }
.btn {
  display:inline-block; margin-top:15px; padding:10px 20px;
  background:#e74c3c; color:white; border-radius:8px; text-decoration:none;
}
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
    <a href="slider_user.php">Slider</a>
    <?php if ($_SESSION['role'] === 'admin'): ?>
      <a href="slider_admin.php">Panel slidera</a>
    <?php endif; ?>
    <a href="../../src/logout.php">Wyloguj</a>
  </div>
</div>

<!-- 🔹 Zawartość panelu -->
<div class="container">
  <h2>Panel administratora 🛡️</h2>
  <p>Zalogowano jako: <b><?= htmlspecialchars($_SESSION['username']) ?></b></p>

  <table>
    <tr>
      <th>ID</th>
      <th>Nazwa użytkownika</th>
      <th>Email</th>
      <th>Rola</th>
      <th>Data rejestracji</th>
    </tr>
    <?php foreach ($users as $u): ?>
    <tr>
      <td><?= $u['id'] ?></td>
      <td><?= htmlspecialchars($u['username']) ?></td>
      <td><?= htmlspecialchars($u['email']) ?></td>
      <td><?= htmlspecialchars($u['role']) ?></td>
      <td><?= $u['created_at'] ?></td>
    </tr>
    <?php endforeach; ?>
  </table>

  <a href="../../src/logout.php" class="btn">Wyloguj się</a>
</div>

</body>
</html>
