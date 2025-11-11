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

if (isset($_POST['update_status'])) {
    $order_id = (int)$_POST['order_id'];
    $status = $_POST['status'];

    $stmt = $db->prepare("UPDATE orders SET status = ? WHERE id = ?");
    $stmt->execute([$status, $order_id]);

    // ✅ Zapisz komunikat i odśwież stronę (żeby zapobiec podwójnemu klikaniu)
    $_SESSION['order_message'] = "✅ Zmieniono status zamówienia #$order_id na '$status'";
    header("Location: dashboard_admin.php");
    exit;
}


?>

<!DOCTYPE html>
<html lang="pl">
<head>
<meta charset="UTF-8">
<title>Panel administratora</title>
<style>
body { font-family: Arial; background:#f0f0f5; margin:0; }

/* 🔹 Pasek nawigacji */
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

/* 🔹 Zawartość panelu */
.container {
  max-width: 900px; margin: 80px auto; background: white; padding: 30px;
  border-radius: 15px; box-shadow: 0 0 10px rgba(0,0,0,0.1);
}
table { width:100%; border-collapse: collapse; margin-top:20px; }
th, td { padding:10px; border-bottom:1px solid #ccc; text-align:left; }
th { background:#5865F2; color:white; }
.btn {
  display:inline-block; margin-top:15px; padding:10px 20px;
  background:#e74c3c; color:white; border-radius:8px; text-decoration:none;
}
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
      <td><?= $u['created_at'] ?></td>
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
