<?php
require_once __DIR__ . '/../../src/auth.php';
requireLogin();
$db = getDB();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$stmt = $db->prepare("SELECT * FROM products WHERE id = ?");
$stmt->execute([$id]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);
function addNotification($db, $user_id, $message) {
    $stmt = $db->prepare("INSERT INTO notifications (user_id, message) VALUES (?, ?)");
    $stmt->execute([$user_id, $message]);
}
if (!$product) {
    die("❌ Produkt nie znaleziony");
}
// --- Zgłaszanie komentarza ---
if (isset($_POST['report_comment'])) {
    $comment_id = (int)$_POST['report_id'];
    $stmt = $db->prepare("UPDATE comments SET reported = 1 WHERE id = ?");
    $stmt->execute([$comment_id]);

    $_SESSION['msg'] = "🔔 Komentarz został zgłoszony do moderatora.";
    addNotification($db, $_SESSION['user_id'], "🚨 Twój komentarz został zgłoszony do moderacji.");
    header("Location: product.php?id=" . $id);
    exit;
}

// 🔹 Dodawanie odpowiedzi i komentarzy
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['comment'])) {
    $comment = trim($_POST['comment']);
    $user_id = $_SESSION['user_id'];
    $parent_id = $_POST['parent_id'] ?? null; // 🧠 WAŻNE — odpowiedź na komentarz

    if ($comment !== '') {
        $stmt = $db->prepare("INSERT INTO comments (product_id, user_id, content, parent_id) VALUES (?, ?, ?, ?)");
        $stmt->execute([$id, $user_id, $comment, $parent_id]);
    }

    header("Location: product.php?id=" . $id);
    addNotification($db, $user_id, "💬 Twój komentarz został dodany do produktu.");
    exit;
}

// --- Głosowanie ---
if (isset($_POST['vote_id'])) {
    $vote_id = (int)$_POST['vote_id'];
    $user_id = $_SESSION['user_id'];

    // 🔎 Sprawdź autora komentarza
    $stmt = $db->prepare("SELECT user_id FROM comments WHERE id = ?");
    $stmt->execute([$vote_id]);
    $commentOwner = $stmt->fetchColumn();

    if ($commentOwner == $user_id) {
        $_SESSION['vote_error'] = "❌ Nie możesz głosować na własny komentarz!";
        header("Location: product.php?id=" . $id);
        exit;
    }

    // 🔎 Sprawdź czy już głosował
    $stmt = $db->prepare("SELECT vote_value FROM comment_votes WHERE comment_id = ? AND user_id = ?");
    $stmt->execute([$vote_id, $user_id]);
    $prevVote = $stmt->fetchColumn();

    if ($prevVote !== false) {
        $_SESSION['vote_error'] = "⚠️ Już głosowałeś na ten komentarz!";
        header("Location: product.php?id=" . $id);
        exit;
    }

    // 🟢 Zapisujemy głos
    $voteValue = isset($_POST['vote_plus']) ? 1 : -1;
    $stmt = $db->prepare("INSERT INTO comment_votes (comment_id, user_id, vote_value) VALUES (?, ?, ?)");
    $stmt->execute([$vote_id, $user_id, $voteValue]);

    // 🔄 Aktualizujemy tabelę komentarzy
    $stmt = $db->prepare("UPDATE comments SET votes = votes + ? WHERE id = ?");
    $stmt->execute([$voteValue, $vote_id]);

    header("Location: product.php?id=" . $id);
    exit;
}


$stmt = $db->prepare("
    SELECT c.*, u.username
    FROM comments c
    JOIN users u ON c.user_id = u.id
    WHERE c.product_id = ?
    ORDER BY c.parent_id ASC, c.votes DESC, c.created_at DESC
");
$stmt->execute([$id]);
$comments = $stmt->fetchAll(PDO::FETCH_ASSOC);


?>

<!DOCTYPE html>
<html lang="pl">
<head>
  
<meta charset="UTF-8">
<link rel="stylesheet" href="/stronakapibara/public/assets/css/style.css">

<title><?= htmlspecialchars($product['name']) ?></title>
<style>

.navbar a {
  color:white; text-decoration:none; margin-left:20px; font-weight:500;
}
.navbar a:hover { text-decoration:underline; }



/* 🔹 Układ produktu */
.product-box { display: flex; gap: 30px; flex-wrap: wrap; }

.product-image {
  width:300px; height:300px; 
  background:#ddd; border-radius:10px; 
  display:flex; align-items:center; justify-content:center;
  font-size: 14px; color:#666;
}

.product-info h2 { margin-top:0; }
.product-price { font-size:1.4em; color:#5865F2; font-weight:bold; }

/* 🔹 Przycisk */
button {
  background:#5865F2; color:white; border:none; border-radius:8px;
  padding:10px 20px; cursor:pointer; margin-top:15px;
}
button:hover { background:#4752c4; }

.back-link {
  display:inline-block; margin-top:20px; 
  color:#5865F2; text-decoration:none; font-weight:bold;
}
.back-link:hover { text-decoration:underline; }
.comment-form textarea {
    width: 100%;
    padding: 10px;
    border-radius: 6px;
    border: 1px solid #ccc;
}

.comment-form button {
    background: #5865F2;
    color: white;
    padding: 8px 16px;
    border: none;
    border-radius: 6px;
    cursor: pointer;
}

.comment-box {
    background: #f5f5f5;
    padding: 10px;
    border-radius: 8px;
    margin-bottom: 10px;
}
.comment-box {
    background: #f5f5f5;
    padding: 10px;
    border-radius: 8px;
    margin-bottom: 10px;
}

.comment-form textarea {
    width: 100%;
    padding: 10px;
    border-radius: 6px;
    border: 1px solid #ccc;
}

.comment-box {
    background: #f5f5f5;
    padding: 10px;
    border-radius: 8px;
    margin-bottom: 10px;

    max-width: 600px;   /* ⬅️ USTAW SZEROKOŚĆ KOMENTARZA */
    width: 100%;        /* ⬅️ Dostosowuje się do ekranu */
    word-wrap: break-word;     /* ⬅️ Zawijanie słów */
    overflow-wrap: break-word; /* ⬅️ Zawijanie bardzo długich słów */
}
@media (max-width: 600px) {
  .comment-box {
      max-width: 100%;
  }
}


</style>
</head>
<body>

<?php include '../includes/navbar.php'; ?>

<div class="container">

  <div class="product-box">

    <!-- 🔹 Miejsce na zdjęcie (zrobimy później) -->
    <div class="product-image">
      <?php if (!empty($product['image'])): ?>
        <img src="../uploads/products/<?= htmlspecialchars($product['image']) ?>" 
             style="max-width:100%; max-height:100%; border-radius:10px;">
      <?php else: ?>
        (brak zdjęcia produktu)
      <?php endif; ?>
    </div>

    <!-- 🔹 Info o produkcie -->
    <div class="product-info">
      <h2><?= htmlspecialchars($product['name']) ?></h2>
      <p><b>Kategoria:</b> <?= htmlspecialchars($product['category'] ?? 'Brak kategorii') ?></p>
      <p><?= nl2br(htmlspecialchars($product['description'])) ?></p>
      <p class="product-price"><?= number_format($product['price'], 2) ?> zł</p>
      <p><b>Dostępne:</b> <?= (int)$product['stock'] ?> szt.</p>

      <!-- 🔹 Dodawanie do koszyka -->
      <?php if ($_SESSION['role'] !== 'admin'): ?>
      <form method="post" action="cart.php">
        <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
        <input type="number" name="quantity" value="1" min="1" style="width:60px;">
        <button type="submit" name="add_to_cart">🛒 Dodaj do koszyka</button>
      </form>
      <?php endif; ?>
    </div>

  </div>

  <?php if(isset($_SESSION['msg'])): ?>
    <p style="color:green;"><b><?= $_SESSION['msg'] ?></b></p>
    <?php unset($_SESSION['msg']); ?>
<?php endif; ?>

<h3>💬 Komentarze</h3>

<?php if (isset($_SESSION['user_id'])): ?>
<form method="post" class="comment-form">
    <textarea name="comment" rows="3" placeholder="Napisz komentarz..." required></textarea>
    <button type="submit">📩 Dodaj komentarz</button>
</form>
<?php else: ?>
<p><a href="../login.php">Zaloguj się</a>, aby dodać komentarz.</p>
<?php endif; ?>

<hr>

<?php if (empty($comments)): ?>
    <p>Brak komentarzy. Bądź pierwszy!</p>
<?php else: ?>
    <?php foreach ($comments as $c): ?>
        <div class="comment-box" style="<?= $c['parent_id'] ? 'margin-left:30px; background:#ececec;' : '' ?>">
            <p><b><?= htmlspecialchars($c['username']) ?></b> napisał:</p>
            <p><?= nl2br(htmlspecialchars($c['content'])) ?></p>
            <small><?= $c['created_at'] ?></small>

            <!-- 🔸 Głosowanie -->
            <form method="post" style="display:inline;">
                <input type="hidden" name="vote_id" value="<?= $c['id'] ?>">
                <button name="vote_plus">👍</button>
                <button name="vote_minus">👎</button>
            </form>
            <!-- 🔺 Zgłoś komentarz -->
<form method="post" style="display:inline;">
    <input type="hidden" name="report_id" value="<?= $c['id'] ?>">
    <button type="submit" name="report_comment" style="color:#e74c3c;">🚨 Zgłoś</button>
</form>

            <span><b><?= $c['votes'] ?></b> punktów</span>

            <!-- 🔸 Odpowiedź -->
            <details>
                <summary>💬 Odpowiedz</summary>
                <form method="post" class="comment-form">
                    <input type="hidden" name="parent_id" value="<?= $c['id'] ?>">
                    <textarea name="comment" rows="2" placeholder="Napisz odpowiedź..." required></textarea>
                    <button type="submit">📩 Wyślij</button>
                </form>
            </details>
        </div>
        
        <hr>
    <?php endforeach; ?>
    <?php if (!empty($_SESSION['vote_error'])): ?>
  <p style="color:red;"><b><?= $_SESSION['vote_error'] ?></b></p>
  <?php unset($_SESSION['vote_error']); ?>
<?php endif; ?>

<?php endif; ?>


  <a href="shop.php" class="back-link">⬅️ Wróć do sklepu</a>

</div>
</body>
</html>
