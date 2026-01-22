<?php
require_once __DIR__ . '/../../src/auth.php';
requireLogin();
$db = getDB();
function addNotification($db, $user_id, $message) {
    $stmt = $db->prepare("INSERT INTO notifications (user_id, message) VALUES (?, ?)");
    $stmt->execute([$user_id, $message]);
}
// 🗑 USUŃ KOMENTARZ
// 🗑 USUNIĘCIE KOMENTARZA I ODPOWIEDZI
if (isset($_POST['delete_comment']) && ($_SESSION['role'] === 'moderator' || $_SESSION['role'] === 'admin')) {
    $id = (int)$_POST['comment_id'];

    // 🔎 Pobierz autora komentarza, żeby wysłać mu powiadomienie
    $stmt = $db->prepare("SELECT user_id FROM comments WHERE id = ?");
    $stmt->execute([$id]);
    $comment = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($comment) {
        // 🔔 Powiadom autora komentarza
        addNotification($db, $comment['user_id'], "❌ Twój komentarz został usunięty przez moderatora.");
    }

    // 🧽 Usuń odpowiedzi
    $db->prepare("DELETE FROM comments WHERE parent_id = ?")->execute([$id]);

    // 🗑 Usuń główny komentarz
    $db->prepare("DELETE FROM comments WHERE id = ?")->execute([$id]);

    $_SESSION['msg'] = "🗑 Komentarz i wszystkie odpowiedzi zostały usunięte!";
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}


// 🧹 OZNACZ JAKO OK – komentarz zaakceptowany
if (isset($_POST['clear_report']) && $_SESSION['role'] === 'moderator') {
    $id = (int)$_POST['comment_id'];

    // 🔎 Znajdź autora komentarza
    $stmt = $db->prepare("SELECT user_id FROM comments WHERE id = ?");
    $stmt->execute([$id]);
    $comment = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($comment) {
        // 🔔 Powiadom autora komentarza o akceptacji
        addNotification($db, $comment['user_id'], "✔️ Twój komentarz został sprawdzony i zaakceptowany!");
    }

    $db->prepare("UPDATE comments SET reported = 0 WHERE id = ?")->execute([$id]);
    
    $_SESSION['msg'] = "✔️ Komentarz został zaakceptowany!";
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}


$user_id = $_SESSION['user_id'];
// 🔹 Pobierz zamówienia użytkownika
$stmt = $db->prepare("
    SELECT id, total, status, created_at 
    FROM orders 
    WHERE user_id = ? 
    ORDER BY created_at DESC
");
$stmt->execute([$user_id]);
$user_orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 🔹 Pobierz dane użytkownika
$stmt = $db->prepare("SELECT username, email, address, role FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $address = trim($_POST['address']);

    $stmt = $db->prepare("UPDATE users SET email = ?, address = ? WHERE id = ?");
    $stmt->execute([$email, $address, $user_id]);

    echo "<script>alert('✅ Dane zaktualizowane pomyślnie!'); window.location='dashboard_user.php';</script>";
    exit;
}
$isModerator = ($_SESSION['role'] === 'moderator');

$username = htmlspecialchars($user['username']);
$email = htmlspecialchars($user['email']);
$address = htmlspecialchars($user['address'] ?? '');
$role = htmlspecialchars($user['role']);

// pobierz powiadomienia z paginacją
$limit = 5;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

$stmt = $db->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT ? OFFSET ?");
$stmt->bindValue(1, $_SESSION['user_id'], PDO::PARAM_INT);
$stmt->bindValue(2, $limit, PDO::PARAM_INT);
$stmt->bindValue(3, $offset, PDO::PARAM_INT);
$stmt->execute();
$notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>
<?php if (isset($_GET['paid'])): ?>
    <div class="message success">
        ✅ Zamówienie zostało opłacone pomyślnie!
    </div>
<?php endif; ?>

<?php if (isset($_GET['error']) && $_GET['error'] === 'not_pending'): ?>
    <div class="message error">
        ⚠️ To zamówienie nie oczekuje na płatność
    </div>
<?php endif; ?>

<!DOCTYPE html>
<html lang="pl">
<head>
<meta charset="UTF-8">
<link rel="stylesheet" href="/stronakapibara/public/assets/css/style.css">
<title>Panel użytkownika</title>
<style>

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

h2 { color: #333; }
.profile { display: flex; align-items: center; gap: 20px; margin-bottom: 30px; }
.avatar {
  width: 100px; height: 100px; border-radius: 50%;
  background: #ddd url('https://cdn-icons-png.flaticon.com/512/149/149071.png') center/cover;
}
.btn {
  display:inline-block; margin-top:15px; padding:10px 20px;
  background:#5865F2; color:white; border-radius:8px; text-decoration:none;
}
form label {
  display:block; font-weight:bold; margin-top:10px;
}
form input, form textarea {
  width:100%; padding:10px; border:1px solid #ccc;
  border-radius:8px; margin-top:5px;
}
form button {
  background:#5865F2; color:white; border:none;
  border-radius:8px; padding:10px 20px; margin-top:15px;
  cursor:pointer;
}
form button:hover {
  background:#4752c4;
}

</style>
</head>
<body>

<?php include '../includes/navbar.php'; ?>

<div class="container">
  <h2>Witaj, <?= $username ?> 👋</h2>
  <div class="profile">
    <div class="avatar"></div>
    <div>
      <p><b>E-mail:</b> <?= $email ?></p>
      <p><b>Rola:</b> <?= $role ?></p>
      <a href="../../src/logout.php" class="btn">Wyloguj się</a>
    </div>
  </div>

  <hr>
  <h3>🎨 Wybierz motyw</h3>

<button onclick="setTheme('theme-capybara')"> Kapibara</button>
<button onclick="setTheme('theme-night')">🌙 Nocny</button>
<button onclick="setTheme('theme-forest')">🌲 Leśny</button>


  <h3>✏️ Edytuj dane profilu</h3>
  <form method="post">
    <label for="email">Adres e-mail:</label>
    <input type="email" name="email" id="email" value="<?= $email ?>" required>

    <label for="address">Adres wysyłki:</label>
    <textarea name="address" id="address" rows="3" placeholder="np. ul. Kapibarowa 12, 15-000 Białystok"><?= $address ?></textarea>

    <button type="submit">💾 Zapisz zmiany</button>
  </form>

  <hr style="margin:40px 0;">

<h3>📦 Twoje zamówienia</h3>

<?php if (empty($user_orders)): ?>
  <p>Nie masz jeszcze żadnych zamówień.</p>
<?php else: ?>
  <table style="width:100%; border-collapse:collapse; margin-top:20px;">
    <tr style="background:#f0f0f5;">
      <th>ID</th>
      <th>Kwota</th>
      <th>Status</th>
      <th>Data</th>
      <th>Akcja</th>
    </tr>
    <?php foreach ($user_orders as $order): ?>
    <tr style="border-bottom:1px solid #ddd;">
      <td>#<?= $order['id'] ?></td>
      <td><?= number_format($order['total'], 2) ?> zł</td>
      <td>
        <span style="font-weight:bold; color:
          <?= $order['status'] === 'pending' ? '#e67e22' :
             ($order['status'] === 'paid' ? '#27ae60' :
             ($order['status'] === 'shipped' ? '#2980b9' : '#8e44ad')) ?>">
          <?= htmlspecialchars($order['status']) ?>
        </span>
      </td>
      <td><?= $order['created_at'] ?></td>
      <td>
        <button class="details-btn" data-id="<?= $order['id'] ?>" style="background:#5865F2; color:white; border:none; border-radius:8px; padding:6px 12px; cursor:pointer;">Szczegóły</button>
        <?php if ($order['status'] === 'pending'): ?>
<form method="get" action="payment_sandbox.php">
    <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
    <button>💳 Zapłać</button>
</form>

<?php else: ?>
    <span style="color:green;font-weight:bold;">✔ Opłacone</span>
<?php endif; ?>

      </td>



    </tr>
    <?php endforeach; ?>
  </table>
<?php endif; ?>
<?php if ($isModerator): ?>
<br><hr>
<h2>🚨 Zgłoszone komentarze – do moderacji</h2>

<?php
$stmt = $db->prepare("
    SELECT c.id, c.content, c.created_at,
           u.username, p.name AS product_name
    FROM comments c
    JOIN users u ON c.user_id = u.id
    JOIN products p ON c.product_id = p.id
    WHERE c.reported = 1
      AND p.category = ?
    ORDER BY c.created_at DESC
");

$stmt->execute([$_SESSION['moderated_category']]);
$reported_comments = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<?php if (empty($reported_comments)): ?>
    <p>Brak zgłoszonych komentarzy 🎉</p>
<?php else: ?>
<table>
  <tr>
    <th>ID</th>
    <th>Użytkownik</th>
    <th>Komentarz</th>
    <th>Produkt</th>
    <th>Data</th>
    <th>Akcja</th>
  </tr>

  <?php foreach ($reported_comments as $c): ?>
  <tr>
    <td><?= $c['id'] ?></td>
    <td><?= htmlspecialchars($c['username']) ?></td>
    <td><?= nl2br(htmlspecialchars($c['content'])) ?></td>
    <td><?= htmlspecialchars($c['product_name']) ?></td>
    <td><?= $c['created_at'] ?></td>
    <td>
      <form method="post" style="display:inline;">
        <input type="hidden" name="comment_id" value="<?= $c['id'] ?>">
        <button name="delete_comment" style="color:red;">❌ Usuń</button>
      </form>

      <form method="post" style="display:inline;">
        <input type="hidden" name="comment_id" value="<?= $c['id'] ?>">
        <button name="clear_report" style="color:green;">✔ OK</button>
      </form>
    </td>
  </tr>
  <?php endforeach; ?>
</table>
<?php endif; ?>
<?php endif; ?>
<h3>🔔 Twoje powiadomienia</h3>

<?php if (empty($notifications)): ?>
  <p>Brak powiadomień 😊</p>
<?php else: ?>
  <?php foreach ($notifications as $n): ?>
    <div style="background:#f5f5f5; border-radius:8px; padding:10px; margin-bottom:8px;">
      <p><?= nl2br(htmlspecialchars($n['message'])) ?></p>
      <small><?= $n['created_at'] ?></small>
    </div>
  <?php endforeach; ?>
  <a href="?page=<?= max(1, $page - 1) ?>">⬅️ Poprzednia</a> |
<a href="?page=<?= $page + 1 ?>">Następna ➡️</a>

<?php endif; ?>

</div>
<!-- 🔹 Modal -->
<div id="orderModal" style="
  display:none; position:fixed; top:0; left:0; width:100%; height:100%;
  background:rgba(0,0,0,0.6); justify-content:center; align-items:center;
">
  <div style="
    background:white; padding:20px 30px; border-radius:12px;
    max-width:500px; width:90%; box-shadow:0 0 20px rgba(0,0,0,0.3);
    position:relative;
  ">
    <span id="closeModal" style="position:absolute; top:10px; right:15px; cursor:pointer; font-size:20px;">✖</span>
    <h3>🧾 Szczegóły zamówienia</h3>
    <div id="orderDetails" style="margin-top:15px;"></div>
  </div>
</div>

<script>
document.querySelectorAll('.details-btn').forEach(btn => {
  btn.addEventListener('click', async () => {
    const id = btn.dataset.id;
    const modal = document.getElementById('orderModal');
    const detailsBox = document.getElementById('orderDetails');
    modal.style.display = 'flex';
    detailsBox.innerHTML = '<p>⏳ Wczytywanie...</p>';

    const response = await fetch('../ajax/get_order_details.php?id=' + id);
    const html = await response.text();
    detailsBox.innerHTML = html;
  });
});

document.getElementById('closeModal').addEventListener('click', () => {
  document.getElementById('orderModal').style.display = 'none';
});
</script>

</body>
</html>
