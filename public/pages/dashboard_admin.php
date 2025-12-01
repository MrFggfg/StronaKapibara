<?php
require_once __DIR__ . '/../../src/auth.php';
requireLogin();

$db = getDB();
$users = $db->query("SELECT id, username, email, role, created_at FROM users ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);
$orders = $db->query("
    SELECT o.*, u.username, u.email 
    FROM orders o 
    JOIN users u ON o.user_id = u.id 
    ORDER BY o.created_at DESC
    LIMIT 10
")->fetchAll(PDO::FETCH_ASSOC);
function addNotification($db, $user_id, $message) {
    $stmt = $db->prepare("INSERT INTO notifications (user_id, message) VALUES (?, ?)");
    $stmt->execute([$user_id, $message]);
}
if (isset($_POST['update_status'])) {
    $order_id = (int)$_POST['order_id'];
    $status = $_POST['status'];

    $stmt = $db->prepare("UPDATE orders SET status = ? WHERE id = ?");
    $stmt->execute([$status, $order_id]);

    // 🔎 Pobierz user_id do powiadomienia
    $stmt = $db->prepare("SELECT user_id FROM orders WHERE id = ?");
    $stmt->execute([$order_id]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
    $user_id = $order['user_id'];

    // 🔔 Powiadom użytkownika
    addNotification($db, $user_id, "📦 Status Twojego zamówienia #$order_id zmieniono na **$status**");

    $_SESSION['order_message'] = "✅ Zmieniono status zamówienia #$order_id na '$status'";
    header("Location: dashboard_admin.php");
    exit;
}

// Pobierz użytkowników
$users = $db->query("SELECT id, username, email, role, created_at FROM users ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);

// Obsługa zmiany roli
if (isset($_POST['update_role'])) {
    $user_id  = (int)$_POST['user_id'];
    $new_role = $_POST['new_role'];

    if (in_array($new_role, ['user','moderator'])) {
        $stmt = $db->prepare("UPDATE users SET role = ? WHERE id = ?");
        $stmt->execute([$new_role, $user_id]);

        // 🔔 Powiadom użytkownika!
        addNotification($db, $user_id, "🛡️ Twoja rola została zmieniona na: $new_role");
    }
    header("Location: dashboard_admin.php");
    exit;
}

// Pobierz zgłoszone komentarze
$stmt = $db->query("
    SELECT c.id, c.content, c.created_at, u.username, p.name AS product_name
    FROM comments c
    JOIN users u ON c.user_id = u.id
    JOIN products p ON c.product_id = p.id
    WHERE c.reported = 1
    ORDER BY c.created_at DESC
");
$reported_comments = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 🗑 USUŃ KOMENTARZ
if (isset($_POST['delete_comment'])) {
    $id = (int)$_POST['comment_id'];

    // pobierz użytkownika komentarza
    $stmt = $db->prepare("SELECT user_id FROM comments WHERE id = ?");
    $stmt->execute([$id]);
    $comment = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($comment) {
        addNotification($db, $comment['user_id'], "❌ Twój komentarz został usunięty przez moderatora.");
    }

    $db->prepare("DELETE FROM comments WHERE id = ?")->execute([$id]);
    header("Location: dashboard_admin.php");
    exit;
}


// 🧹 WYCZYŚC ZGŁOSZENIE
if (isset($_POST['clear_report'])) {
    $id = (int)$_POST['comment_id'];

    // pobierz user_id autora komentarza
    $stmt = $db->prepare("SELECT user_id FROM comments WHERE id = ?");
    $stmt->execute([$id]);
    $comment = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($comment) {
        addNotification($db, $comment['user_id'], "✔️ Twój komentarz został sprawdzony i zaakceptowany.");
    }

    $db->prepare("UPDATE comments SET reported = 0 WHERE id = ?")->execute([$id]);
    header("Location: dashboard_admin.php");
    exit;
}

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

<!DOCTYPE html>
<html lang="pl">
<head>
<meta charset="UTF-8">
<link rel="stylesheet" href="/stronakapibara/public/assets/css/style.css">

<title>Panel administratora</title>
<style>

/* 🔹 Pasek nawigacji */

.navbar .logo { font-weight:bold; font-size:1.2em; }
.navbar a {
  color:white; text-decoration:none; margin-left:20px;
  transition:0.2s; font-weight:500;
}
.navbar a:hover { text-decoration:underline; }

/* 🔹 Zawartość panelu */

table { width:100%; border-collapse: collapse; margin-top:20px; }
th, td { padding:10px; border-bottom:1px solid #ccc; text-align:left; }
th { background:#5865F2; color:white; }
.btn {
  display:inline-block; margin-top:15px; padding:10px 20px;
  background:#e74c3c; color:white; border-radius:8px; text-decoration:none;
}
table { width: 100%; border-collapse: collapse; margin-top:20px; }
table tr:nth-child(even) { background: #f8f8f8; }
table th, table td { padding: 10px; border-bottom: 1px solid #ddd; }

</style>
</head>
<body>

<?php include '../includes/navbar.php'; ?>


<!-- 🔹 Zawartość panelu -->
<div class="container">
  <h2>Panel administratora 🛡️</h2>
  <p>Zalogowano jako: <b><?= htmlspecialchars($_SESSION['username']) ?></b></p>

  <table>
    <tr>
      <th>ID</th>
      <th>Nazwa użytkownika</th>
      <th>Email</th>
      <th>Rola</th>
      <th>Data rejestracji</th>
    </tr>
    <?php foreach ($users as $u): ?>
    <tr>
      <td><?= $u['id'] ?></td>
      <td><?= htmlspecialchars($u['username']) ?></td>
      <td><?= htmlspecialchars($u['email']) ?></td>
      <td><?= htmlspecialchars($u['role']) ?></td>
<td><?= date('d.m.Y H:i', strtotime($u['created_at'])) ?></td>
    </tr>
    <?php endforeach; ?>
  </table>

    <h2 style="margin-top:50px;">📦 Ostatnie zamówienia</h2>

  <?php if (!empty($_POST['update_status'])): ?>
    <p style="color:green; font-weight:bold;">✅ Status zamówienia został zaktualizowany!</p>
  <?php endif; ?>
<?php if (!empty($_SESSION['order_message'])): ?>
  <p style="color:green; font-weight:bold;"><?= $_SESSION['order_message']; ?></p>
  <?php unset($_SESSION['order_message']); ?>
<?php endif; ?>

  <table>
    <tr>
      <th>ID</th>
      <th>Użytkownik</th>
      <th>Email</th>
      <th>Kwota</th>
      <th>Status</th>
      <th>Data</th>
      <th>Akcja</th>
      <th>Szczegóły</th>

    </tr>

    <?php foreach ($orders as $order): ?>
    <tr>
      <td>#<?= $order['id'] ?></td>
      <td><?= htmlspecialchars($order['username']) ?></td>
      <td><?= htmlspecialchars($order['email']) ?></td>
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
        <form method="post" style="display:flex; gap:5px;">
          <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
          <select name="status" style="padding:5px; border-radius:6px;">
            <option value="pending" <?= $order['status'] === 'pending' ? 'selected' : '' ?>>pending</option>
            <option value="paid" <?= $order['status'] === 'paid' ? 'selected' : '' ?>>paid</option>
            <option value="shipped" <?= $order['status'] === 'shipped' ? 'selected' : '' ?>>shipped</option>
            <option value="delivered" <?= $order['status'] === 'delivered' ? 'selected' : '' ?>>delivered</option>
          </select>
          <button type="submit" name="update_status" style="background:#5865F2; color:white; border:none; border-radius:8px; padding:6px 10px; cursor:pointer;">💾</button>
</form>
</td>
<td>
  <button class="details-btn" data-id="<?= $order['id'] ?>" style="background:#5865F2; color:white; border:none; border-radius:8px; padding:6px 12px; cursor:pointer;">Szczegóły</button>
</td>
</tr>

    <?php endforeach; ?>
  </table>
<br><hr><h2>👥 Zarządzanie użytkownikami</h2>

<table>
  <tr>
    <th>ID</th>
    <th>Użytkownik</th>
    <th>Email</th>
    <th>Aktualna rola</th>
    <th>Zmień na</th>
  </tr>

  <?php foreach ($users as $u): ?>
  <tr>
    <td><?= $u['id'] ?></td>
    <td><?= htmlspecialchars($u['username']) ?></td>
    <td><?= htmlspecialchars($u['email']) ?></td>
    <td><b><?= htmlspecialchars($u['role']) ?></b></td>
    <td>
      <?php if ($u['role'] !== 'admin'): ?> <!-- Admina nie zmieniamy! -->
        <form method="post" style="display:flex; gap:5px;">
          <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
          <select name="new_role" style="padding:5px; border-radius:6px;">
            <option value="user" <?= $u['role']=='user'?'selected':'' ?>>User</option>
            <option value="moderator" <?= $u['role']=='moderator'?'selected':'' ?>>Moderator</option>
          </select>
          <button type="submit" name="update_role" style="background:#5865F2; color:white; border:none; border-radius:8px; padding:6px 10px; cursor:pointer;">💾 Zapisz</button>
        </form>
      <?php else: ?>
        <i>Admin – brak zmian</i>
      <?php endif; ?>
    </td>
  </tr>
  <?php endforeach; ?>
</table>
<br><hr>
<h2>🚨 Zgłoszone komentarze</h2>

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
<?php endif; ?>
<a href="?page=<?= max(1, $page - 1) ?>">⬅️ Poprzednia</a> |
<a href="?page=<?= $page + 1 ?>">Następna ➡️</a>
<a href="../../src/logout.php" class="btn">Wyloguj się</a>
</div>
<!-- 🔹 Modal szczegółów -->
<div id="orderModal" style="
  display:none; position:fixed; top:0; left:0; width:100%; height:100%;
  background:rgba(0,0,0,0.6); justify-content:center; align-items:center;
">
  <div style="
    background:white; padding:20px 30px; border-radius:12px;
    max-width:600px; width:90%; box-shadow:0 0 20px rgba(0,0,0,0.3);
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

    const response = await fetch('../ajax/get_order_details_admin.php?id=' + id);
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