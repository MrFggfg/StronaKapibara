<?php
require_once __DIR__ . '/../../src/config.php';
require_once __DIR__ . '/../../src/auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'] ?? '';
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $result = register($name, $email, $password);
    if ($result === true) {
        header('Location: login.php');
        exit;
    } else {
        $error = $result;
    }
}
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Rejestracja - CapyWorld</title>
  <link rel="stylesheet" href="/assets/css/styles.css">
</head>
<body>
  <div class="header">CapyWorld — Rejestracja</div>
  <div class="container">
    <?php if (!empty($error)) echo '<p style="color:red;">'.htmlspecialchars($error).'</p>'; ?>
    <form method="post" class="form">
      <label>Nazwa <input name="name" required></label>
      <label>Email <input name="email" type="email" required></label>
      <label>Hasło <input name="password" type="password" required></label>
      <button type="submit">Zarejestruj</button>
    </form>
    <p>Masz konto? <a href="login.php">Zaloguj się</a></p>
  </div>
</body>
</html>
