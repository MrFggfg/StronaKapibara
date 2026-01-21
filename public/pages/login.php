<?php
require_once __DIR__ . '/../../src/auth.php';
$db = getDB();
$message = '';
$csrf_token = generateCsrfToken();
function addNotification($db, $user_id, $message) {
    $stmt = $db->prepare("INSERT INTO notifications (user_id, message) VALUES (?, ?)");
    $stmt->execute([$user_id, $message]);
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        die("Błąd bezpieczeństwa: CSRF token nieprawidłowy.");
    }

    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (loginUser($username, $password)) {

    // 🔹 Zapamiętaj mnie
    if (!empty($_POST['remember_me'])) {
        $token = bin2hex(random_bytes(32));

        $stmt = $db->prepare("UPDATE users SET remember_token = ? WHERE id = ?");
        $stmt->execute([$token, $_SESSION['user_id']]);

        setcookie(
            'remember_me',
            $token,
            time() + (60 * 60 * 24 * 30), // 30 dni
            '/',
            '',
            false,
            true // HttpOnly
        );
    }

    addNotification($db, $_SESSION['user_id'], "🔑 Zalogowano pomyślnie");
    header("Location: dashboard.php");
    exit();
}
else {
        $message = "Niepoprawny login lub hasło.";
    }
}
?>

<!doctype html>
<html lang="pl">
<head>
  
  <meta charset="utf-8" />
  <title>Logowanie — CapyWorld</title>
<link rel="stylesheet" href="/stronakapibara/public/assets/css/style.css">
</head>
<body>
  <div class="auth-container">
    <div class="auth-box">
      <h1>Zaloguj się</h1>

      <?php if (isset($_GET['registered'])): ?>
        <p class="success">Rejestracja zakończona! Możesz się zalogować.</p>
      <?php endif; ?>

      <?php if ($message): ?>
        <p class="error"><?= htmlspecialchars($message) ?></p>
      <?php endif; ?>

      <form method="post">
        <label>Nazwa użytkownika</label>
        <input type="text" name="username" value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" required>

        <label>Hasło</label>
        <input type="password" name="password" required>
<label style="margin-top:10px; display:block;">
  <input type="checkbox" name="remember_me"> Zapamiętaj mnie
</label>

        <button type="submit" class="btn">Zaloguj</button>
<div style="margin-top: 20px;">
  <p>Lub zaloguj się przez:</p>
  <a class="btn" style="background:#333; color:white;" href="github_login.php">GitHub</a>
  <a class="btn" style="background:#5865F2; color:white;" href="discord_login.php">Discord</a>
</div>

        <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">

      </form>

      <p>Nie masz konta? <a href="register.php">Zarejestruj się</a></p>
    </div>
  </div>
</body>
</html>
