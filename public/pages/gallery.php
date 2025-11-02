<?php
require_once __DIR__ . '/../../src/auth.php';
requireLogin();
$db = getDB();
// 🔹 Usuwanie zdjęcia (tylko dla administratora)
if ($_SESSION['role'] === 'admin' && isset($_POST['delete_photo_id'])) {
    $photo_id = (int)$_POST['delete_photo_id'];

    // Pobierz nazwę pliku
    $stmt = $db->prepare("SELECT filename FROM photos WHERE id = ?");
    $stmt->execute([$photo_id]);
    $photo = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($photo) {
        $filepath = __DIR__ . "/../uploads/" . $photo['filename'];
        if (file_exists($filepath)) {
            unlink($filepath); // usuń plik z dysku
        }

        // Usuń rekord z bazy
        $stmt = $db->prepare("DELETE FROM photos WHERE id = ?");
        $stmt->execute([$photo_id]);
    }

    header("Location: gallery.php");
    exit;
}



// Zdjęcia do slidera (wybrane przez admina)
$slider_photos = $db->query("
  SELECT * FROM photos 
  WHERE in_slider = 1 
  ORDER BY created_at DESC
")->fetchAll(PDO::FETCH_ASSOC);

// Wszystkie zdjęcia z galerii
$photos = $db->query("
  SELECT photos.*, users.username 
  FROM photos 
  JOIN users ON photos.user_id = users.id 
  ORDER BY photos.created_at DESC
")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="pl">
<head>
<meta charset="UTF-8">
<title>Galeria kapibar</title>
<style>
body { font-family: Arial; background:#fafafa; margin:0; }

/* Pasek nawigacji */
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

/* Sekcja slidera */
.slider-container {
  max-width:1000px; margin:60px auto 30px auto; overflow:hidden; position:relative;
}
.slider {
  display:flex; transition:transform 0.7s ease-in-out;
}
.slide {
  flex: 0 0 33.333%; /* 3 zdjęcia na ekranie */
  box-sizing:border-box; padding:10px;
}
@media (max-width:900px) { .slide { flex:0 0 50%; } } /* 2 na ekranie */
@media (max-width:600px) { .slide { flex:0 0 100%; } } /* 1 na ekranie */
.card {
  background:white; border-radius:10px; box-shadow:0 0 8px rgba(0,0,0,0.1);
  height:100%; display:flex; flex-direction:column;
}
.card img {
  width:100%; height:250px; object-fit:cover; border-radius:10px 10px 0 0;
}
.card .info { padding:15px; flex-grow:1; }
.card h3 { margin:0 0 5px 0; }

/* Galeria */
.container {
  max-width: 1000px; margin: 20px auto; background:white; padding: 20px;
  border-radius: 10px; box-shadow: 0 0 8px rgba(0,0,0,0.1);
}
.gallery {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
  gap: 20px;
  margin-top: 20px;
  align-items: stretch; /* najważniejsze: wszystkie karty równej wysokości */
}

.photo {
  display: flex;
  flex-direction: column;
  justify-content: space-between;
  background: white;
  border-radius: 10px;
  overflow: hidden;
  box-shadow: 0 0 5px rgba(0,0,0,0.1);
  transition: transform 0.2s ease;
}

.photo:hover {
  transform: scale(1.02);
}

.photo img {
  width: 100%;
  height: 200px;
  object-fit: cover;
  border-bottom: 1px solid #eee;
}

.photo-info {
  flex-grow: 1; /* opis rozciąga się, jeśli dłuższy */
  padding: 10px;
  display: flex;
  flex-direction: column;
  justify-content: space-between;
}

.btn-add {
  background:#5865F2; color:white; padding:8px 16px;
  border-radius:8px; text-decoration:none; transition:0.2s;
}
.btn-add:hover { background:#4752c4; }

</style>
</head>
<body>

<?php include '../includes/navbar.php'; ?>



  <!-- 🔹 SLIDER -->
  <?php if (count($slider_photos) > 0): ?>
  <div class="slider-container">
    <div class="slider" id="slider">
      <?php foreach ($slider_photos as $p): ?>
        <div class="slide">
          <div class="card">
            <img src="../uploads/<?= htmlspecialchars($p['filename']) ?>" alt="">
            <div class="info">
              <h3><?= htmlspecialchars($p['title'] ?: 'Bez tytułu') ?></h3>
              <p><?= htmlspecialchars($p['description'] ?: 'Brak opisu') ?></p>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
  <?php endif; ?>

  <!-- 🔹 GALERIA -->
  <div class="container">
    <div style="display:flex; justify-content:space-between; align-items:center;">
      <h2>📸 Galeria użytkowników</h2>
      <a href="upload_photo.php" class="btn-add">➕ Dodaj zdjęcie</a>
    </div>

    <div class="gallery">
      <?php foreach ($photos as $p): ?>
        <div class="photo">
          <img src="../uploads/<?= htmlspecialchars($p['filename']) ?>" alt="">
<div class="photo-info">
  <b><?= htmlspecialchars($p['title'] ?: 'Bez tytułu') ?></b><br>
  <small>Autor: <?= htmlspecialchars($p['username']) ?></small>

  <?php if ($_SESSION['role'] === 'admin'): ?>
    <form method="post" onsubmit="return confirm('Czy na pewno chcesz usunąć to zdjęcie?');" style="margin-top:10px;">
      <input type="hidden" name="delete_photo_id" value="<?= $p['id'] ?>">
      <button type="submit" style="
        background:#e74c3c;
        border:none;
        padding:6px 12px;
        color:white;
        border-radius:6px;
        cursor:pointer;
      ">🗑️ Usuń</button>
    </form>
  <?php endif; ?>
</div>

        </div>
      <?php endforeach; ?>
    </div>
  </div>

<script>
const slider = document.getElementById('slider');
if (slider) {
  // Duplikujemy slajdy, żeby stworzyć efekt nieskończoności
  slider.innerHTML += slider.innerHTML;

  let position = 0;
  let speed = 2; // szybkość przesuwania (piksele na klatkę)

  function animate() {
    position -= speed;
    if (Math.abs(position) >= slider.scrollWidth / 2) {
      position = 0; // po połowie wracamy na start bez zacięcia
    }
    slider.style.transform = `translateX(${position}px)`;
    requestAnimationFrame(animate);
  }

  animate();
}
</script>


</body>
</html>
