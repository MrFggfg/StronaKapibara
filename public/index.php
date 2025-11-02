<?php
session_start();

// Jeśli użytkownik już jest zalogowany, przenosimy go do dashboarda
if (isset($_SESSION['user_id'])) {
    header("Location: pages/dashboard.php");
    exit();
}
?>
<!doctype html>
<html lang="pl">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>CapyWorld — strona główna</title>
  <link rel="stylesheet" href="assets/css/styles.css" />
</head>
<body>
  <header class="site-header">
    <nav class="nav container">
      <a class="brand" href="index.php">CapyWorld</a>
      <ul class="nav-links">
        <li><a href="pages/gallery.php">Galeria</a></li>
        <li><a href="pages/login.php">Zaloguj</a></li>
        <li><a href="pages/register.php">Zarejestruj</a></li>
      </ul>
    </nav>
  </header>

  <main class="container hero">
    <h1>Witaj w CapyWorld 🦦</h1>
    <p>Dołącz do społeczności miłośników kapibar!<br>
    Oglądaj zdjęcia, komentuj, kupuj gadżety i baw się dobrze.</p>
    <a class="btn" href="pages/gallery.php">Zobacz galerię</a>
  </main>

  <footer class="site-footer">
    <p>© 2025 CapyWorld </p>
  </footer>
</body>
</html>
