<?php if (session_status() === PHP_SESSION_NONE) session_start(); ?>
<script src="/stronakapibara/public/assets/js/theme.js"></script>


<div class="navbar">
  <div class="logo">🐹 CapyWorld</div>
  <div class="links">
    <a href="../index.php">Strona główna</a>
    <a href="gallery.php">Galeria</a>
    <a href="shop.php">Sklep</a>
    <a href="cart.php">Koszyk</a>
    <a href="upload_photo.php">Dodaj zdjęcie</a>
    <?php if ($_SESSION['role'] === 'admin' || $_SESSION['role'] === 'moderator'): ?>
    <a href="add_product.php">Dodaj produkt</a>
<?php endif; ?>

    <?php if ($_SESSION['role'] === 'admin'): ?>
      <a href="slider_admin.php">Panel slidera</a>
      <a href="dashboard_admin.php">Panel admina</a>
    <?php endif; ?>
    <a href="../../src/logout.php">Wyloguj</a>
  </div>
</div>
