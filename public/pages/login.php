<?php
require_once __DIR__ . '/../../src/config.php';
require_once __DIR__ . '/../../src/auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    if (login($email, $password)) {
        header('Location: gallery.php');
        exit;
    } else {
        $error = 'Niepoprawny email lub hasło';
    }
}
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Logowanie - CapyWorld</title>
  <link rel="stylesheet" href="/assets/css/styles.css">
</head>
<body>
  <div class="header">CapyWorld — Logowanie</div>
  <div class="container">
    <?php if (!empty($error)) echo '<p style="color:red;">'.htmlspecialchars($error).'</p>'; ?>
    <form method="post" class="form">
      <label>Email <input name="email" type="email" required></label>
      <label>Hasło <input name="password" type="password" required></label>
      <button type="submit">Zaloguj</button>
    </form>
    <p>Nie masz konta? <a href="register.php">Zarejestruj się</a></p>
  </div>
</body>
</html>
