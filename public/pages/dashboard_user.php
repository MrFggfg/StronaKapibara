<?php
$username = htmlspecialchars($_SESSION['username']);
$email = htmlspecialchars($_SESSION['email']);
?>

<!DOCTYPE html>
<html lang="pl">
<head>
<meta charset="UTF-8">
<title>Panel użytkownika</title>
<style>
body { font-family: Arial; background: #f5f5f5; margin: 0; }

.navbar {
  display: flex; justify-content: space-between; align-items: center;
  background: #5865F2; padding: 12px 40px; color: white;
  box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}
.navbar .logo {
  font-weight: bold; font-size: 1.2em;
}
.navbar a {
  color: white; text-decoration: none; margin-left: 20px;
  transition: 0.2s; font-weight: 500;
}
.navbar a:hover {
  text-decoration: underline;
}

.container {
  max-width: 700px; margin: 80px auto; background: white; padding: 30px;
  border-radius: 15px; box-shadow: 0 0 10px rgba(0,0,0,0.1);
}
h2 { color: #333; }
.profile { display: flex; align-items: center; gap: 20px; }
.avatar {
  width: 100px; height: 100px; border-radius: 50%;
  background: #ddd url('https://cdn-icons-png.flaticon.com/512/149/149071.png') center/cover;
}
.btn {
  display:inline-block; margin-top:15px; padding:10px 20px;
  background:#5865F2; color:white; border-radius:8px; text-decoration:none;
}
</style>
</head>
<body>

<?php include '../includes/navbar.php'; ?>


  <!-- 🔹 Zawartość strony -->
  <div class="container">
    <h2>Witaj, <?= $username ?> 👋</h2>
    <div class="profile">
      <div class="avatar"></div>
      <div>
        <p><b>E-mail:</b> <?= $email ?></p>
        <p><b>Rola:</b> użytkownik</p>
        <a href="../../src/logout.php" class="btn">Wyloguj się</a>
      </div>
    </div>
  </div>

</body>
</html>
