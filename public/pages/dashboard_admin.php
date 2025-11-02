<?php
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
.container {
  max-width: 900px; margin: 60px auto; background: white; padding: 30px;
  border-radius: 15px; box-shadow: 0 0 10px rgba(0,0,0,0.1);
}
table { width:100%; border-collapse: collapse; margin-top:20px; }
th, td { padding:10px; border-bottom:1px solid #ccc; text-align:left; }
th { background:#5865F2; color:white; }
.btn { display:inline-block; margin-top:15px; padding:10px 20px;
  background:#e74c3c; color:white; border-radius:8px; text-decoration:none;
}
</style>
</head>
<body>
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
