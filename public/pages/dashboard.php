<?php
require_once __DIR__ . '/../../src/config.php';
require_once __DIR__ . '/../../src/db.php';

// Simple gallery listing files from uploads
$uploadsDir = __DIR__ . '/../uploads';
$images = [];
if (is_dir($uploadsDir)) {
    $files = scandir($uploadsDir);
    foreach ($files as $f) {
        if (in_array($f, ['.','..'])) continue;
        if (preg_match('/\.(jpe?g|png|gif)$/i', $f)) {
            $images[] = '/uploads/' . $f;
        }
    }
}
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Galeria - CapyWorld</title>
  <link rel="stylesheet" href="/assets/css/styles.css">
</head>
<body>
  <div class="header">CapyWorld — Galeria</div>
  <div class="container">
    <p><a href="register.php">Rejestracja</a> · <a href="login.php">Logowanie</a></p>
    <div class="gallery">
      <?php foreach ($images as $img): ?>
        <div class="card"><img src="<?= htmlspecialchars($img) ?>" alt="photo"></div>
      <?php endforeach; ?>
      <?php if (empty($images)) echo '<p>Brak zdjęć. Prześlij pierwsze!</p>'; ?>
    </div>
  </div>
  <script src="/assets/js/main.js"></script>
</body>
</html>
