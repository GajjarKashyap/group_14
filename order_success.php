<?php
// order_success.php - Order Confirmation View (Clean White Theme)
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/helpers/auth.php';
require_user_login();

$pdo = getPDO();
$ref = isset($_GET['ref']) ? trim($_GET['ref']) : '';

$stmt = $pdo->prepare("SELECT * FROM purchase_orders WHERE purchase_reference = :ref AND customer_id = :cid");
$stmt->execute(['ref' => $ref, 'cid' => $_SESSION['user_id']]);
$order = $stmt->fetch();

if (!$order) {
    header("Location: index.php");
    exit();
}

$stmt_lines = $pdo->prepare("SELECT * FROM purchase_lines WHERE purchase_id = :pid");
$stmt_lines->execute(['pid' => $order['purchase_id']]);
$lines = $stmt_lines->fetchAll();

$page_title = "Order Placed Successfully";
require_once __DIR__ . '/includes/header.php';
?>

<div class="container py-5 text-center">
    <div class="card p-5 shadow-sm border-0 bg-white rounded-3 max-w-700 mx-auto">
        <div class="text-success mb-3"><i class="fa-solid fa-circle-check fa-4x"></i></div>
        <h2 class="text-dark brand-font display-6 mb-2">Thank You for Your Order!</h2>
        <p class="text-muted mb-4">Your order has been placed successfully and is being prepared with luxury gift wrapping.</p>

        <div class="p-3 rounded bg-light border mb-4 text-start">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <small class="text-gold text-uppercase fw-bold d-block">Order Reference</small>
                    <strong class="text-dark fs-4 font-monospace"><?php echo htmlspecialchars($order['purchase_reference']); ?></strong>
                </div>
                <div class="col-md-6 text-md-end mt-2 mt-md-0">
                    <small class="text-muted d-block">Placed On: <?php echo date('d M Y, h:i A', strtotime($order['placed_on'])); ?></small>
                    <span class="badge bg-success text-white mt-1"><?php echo htmlspecialchars($order['fulfilment_state']); ?></span>
                </div>
            </div>
        </div>

        <h5 class="text-dark brand-font text-start mb-3">Order Items Snapshot</h5>
        <div class="table-responsive text-start mb-4">
            <table class="table border-bottom align-middle">
                <thead>
                    <tr class="text-muted small">
                        <th>Fragrance</th>
                        <th>Variant</th>
                        <th>Qty</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($lines as $l): ?>
                        <tr>
                            <td>
                                <strong><?php echo htmlspecialchars($l['fragrance_name_snapshot']); ?></strong>
                                <small class="text-muted d-block font-monospace"><?php echo htmlspecialchars($l['fragrance_code_snapshot']); ?></small>
                            </td>
                            <td><?php echo htmlspecialchars($l['volume_snapshot']); ?></td>
                            <td><?php echo $l['units']; ?></td>
                            <td class="text-gold fw-bold">₹<?php echo number_format($l['line_value'], 2); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="d-flex justify-content-center gap-3">
            <a href="orders.php" class="btn btn-gold"><i class="fa-solid fa-box-open me-2"></i> Track Order Status</a>
            <a href="fragrances.php" class="btn btn-outline-gold"><i class="fa-solid fa-spray-can me-2"></i> Continue Shopping</a>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
