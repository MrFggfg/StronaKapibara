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
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm'] ?? '';

    if ($password !== $confirm) {
        $message = "Hasła się nie zgadzają.";
    } else {
        $result = registerUser($username, $email, $password);
        if ($result === true) {
             $user_id = $db->lastInsertId();
            addNotification($db, $_SESSION['user_id'], "🎉 Utworzono konto");
            header("Location: login.php?registered=1");
            exit();
        } else {
            $message = $result;
        }
    }
}
?>

<!doctype html>
<html lang="pl">
<head>
  <meta charset="utf-8" />
  <title>Rejestracja — CapyWorld</title>
  <link rel="stylesheet" href="../assets/css/styles.css" />
</head>
<body>
  <div class="auth-container">
    <div class="auth-box">
      <h1>Utwórz konto</h1>

      <?php if ($message): ?>
        <p class="error"><?= htmlspecialchars($message) ?></p>
      <?php endif; ?>

      <form method="post">
        <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">

        <label>Nazwa użytkownika</label>
        <input type="text" name="username" value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" required>

        <label>E-mail</label>
        <input type="email" name="email" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>

        <label>Hasło</label>
        <input type="password" name="password" required>

        <label>Powtórz hasło</label>
        <input type="password" name="confirm" required>

        <button type="submit" class="btn">Zarejestruj</button>
      </form>

      <p>Masz już konto? <a href="login.php">Zaloguj się</a></p>
    </div>
  </div>
</body>
</html>
