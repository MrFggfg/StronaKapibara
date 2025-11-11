<?php
require_once __DIR__ . '/../../src/auth.php';
requireLogin();
$db = getDB();

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

$username = htmlspecialchars($user['username']);
$email = htmlspecialchars($user['email']);
$address = htmlspecialchars($user['address'] ?? '');
$role = htmlspecialchars($user['role']);
?>

<!DOCTYPE html>
<html lang="pl">
<head>
<meta charset="UTF-8">
<title>Panel użytkownika</title>
<style>
body { font-family: Arial; background: #f5f5f5; margin: 0; }

.navbar {
  display: flex; justify-content: space-between; align-items: center;
  background: #5865F2; padding: 12px 40px; color: white;
  box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}
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

.container {
  max-width: 700px; margin: 80px auto; background: white; padding: 30px;
  border-radius: 15px; box-shadow: 0 0 10px rgba(0,0,0,0.1);
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
      </td>
    </tr>
    <?php endforeach; ?>
  </table>
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
