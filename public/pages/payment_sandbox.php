<?php
require_once __DIR__ . '/../../src/auth.php';
requireLogin();
$db = getDB();

$order_id = (int)($_GET['order_id'] ?? 0);

// pobierz zamówienie pending
$stmt = $db->prepare("
    SELECT * FROM orders
    WHERE id = ? AND user_id = ? AND status = 'pending'
");
$stmt->execute([$order_id, $_SESSION['user_id']]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    die("⚠️ To zamówienie nie oczekuje na płatność");
}
?>

<!DOCTYPE html>
<html lang="pl">
<head>
<meta charset="UTF-8">
<title>Płatność – sandbox</title>
<link rel="stylesheet" href="../assets/css/style.css">
<style>
.payment-box {
    max-width: 500px;
    margin: 100px auto;
    background: white;
    padding: 30px;
    border-radius: 15px;
    text-align: center;
}
.pay-btn {
    display: block;
    width: 100%;
    margin: 10px 0;
    padding: 12px;
    border-radius: 10px;
    border: none;
    cursor: pointer;
    font-size: 16px;
}
.blik { background:#111; color:white; }
.payu { background:#00a650; color:white; }
.card { background:#5865F2; color:white; }
</style>
</head>
<body>

<?php include '../includes/navbar.php'; ?>

<div class="payment-box">
    <h2>💳 Płatność</h2>
    <p>Zamówienie #<?= $order['id'] ?></p>
    <p>Kwota: <b><?= number_format($order['total'], 2) ?> zł</b></p>

    <form method="post" action="../actions/pay_order.php">
        <input type="hidden" name="order_id" value="<?= $order['id'] ?>">

        <button class="pay-btn blik">📱 BLIK </button>
        <button class="pay-btn payu">💰 PayU </button>
        <button class="pay-btn card">💳 Karta </button>
    </form>

</div>

</body>
</html>
