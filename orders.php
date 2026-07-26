<?php
// orders.php - Customer Order History & Tracking (Clean White Theme)
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/helpers/auth.php';

require_user_login();

$pdo = getPDO();
$msg = '';

// Handle Order Cancellation BEFORE header output
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_order'])) {
    $purchase_id = (int)$_POST['purchase_id'];
    
    $chk_stmt = $pdo->prepare("SELECT * FROM purchase_orders WHERE purchase_id = :pid AND customer_id = :cid");
    $chk_stmt->execute(['pid' => $purchase_id, 'cid' => $_SESSION['user_id']]);
    $ord = $chk_stmt->fetch();

    if ($ord && $ord['fulfilment_state'] === 'Placed') {
        $pdo->beginTransaction();
        
        $upd = $pdo->prepare("UPDATE purchase_orders SET fulfilment_state = 'Cancelled' WHERE purchase_id = :pid");
        $upd->execute(['pid' => $purchase_id]);

        $lines_stmt = $pdo->prepare("SELECT * FROM purchase_lines WHERE purchase_id = :pid");
        $lines_stmt->execute(['pid' => $purchase_id]);
        $lines = $lines_stmt->fetchAll();

        $stk = $pdo->prepare("UPDATE fragrances SET available_units = available_units + :units WHERE fragrance_id = :fid");
        foreach ($lines as $l) {
            if (!empty($l['fragrance_id'])) {
                $stk->execute(['units' => $l['units'], 'fid' => $l['fragrance_id']]);
            }
        }

        $pdo->commit();
        $msg = "Order successfully cancelled and inventory stock restored.";
    }
}

// Fetch user orders
$stmt = $pdo->prepare("SELECT * FROM purchase_orders WHERE customer_id = :cid ORDER BY purchase_id DESC");
$stmt->execute(['cid' => $_SESSION['user_id']]);
$orders = $stmt->fetchAll();

$page_title = "My Orders";
require_once __DIR__ . '/includes/header.php';
?>

<div class="container py-5">
    <h2 class="text-dark brand-font mb-4"><i class="fa-solid fa-box-open text-gold me-2"></i> My Order History</h2>

    <?php if (!empty($msg)): ?>
        <div class="alert alert-success mb-4"><?php echo $msg; ?></div>
    <?php endif; ?>

    <?php if (!empty($orders)): ?>
        <div class="row g-4">
            <?php foreach ($orders as $o): ?>
                <?php
                $stmt_l = $pdo->prepare("SELECT * FROM purchase_lines WHERE purchase_id = :pid");
                $stmt_l->execute(['pid' => $o['purchase_id']]);
                $items = $stmt_l->fetchAll();
                ?>
                <div class="col-12">
                    <div class="card p-4 shadow-sm border-0 bg-white rounded-3">
                        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center pb-3 border-bottom mb-3">
                            <div>
                                <small class="text-gold text-uppercase fw-bold d-block">Order Reference</small>
                                <strong class="text-dark fs-5 font-monospace"><?php echo htmlspecialchars($o['purchase_reference']); ?></strong>
                                <small class="text-muted ms-3"><?php echo date('d M Y, h:i A', strtotime($o['placed_on'])); ?></small>
                            </div>
                            <div class="mt-2 mt-md-0 d-flex align-items-center gap-3">
                                <span class="badge <?php echo ($o['fulfilment_state'] === 'Cancelled') ? 'bg-danger' : 'bg-gold text-white'; ?> fs-6">
                                    <?php echo htmlspecialchars($o['fulfilment_state']); ?>
                                </span>
                                <?php if ($o['fulfilment_state'] === 'Placed'): ?>
                                    <form action="orders.php" method="POST" onsubmit="return confirm('Are you sure you want to cancel this order?');">
                                        <input type="hidden" name="purchase_id" value="<?php echo $o['purchase_id']; ?>">
                                        <button type="submit" name="cancel_order" class="btn btn-outline-danger btn-sm">Cancel Order</button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table class="table align-middle mb-0">
                                <thead>
                                    <tr class="text-muted small border-bottom">
                                        <th>Fragrance</th>
                                        <th>Volume & Edition</th>
                                        <th>Qty</th>
                                        <th>Price</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($items as $it): ?>
                                        <tr class="border-bottom">
                                            <td>
                                                <div class="d-flex align-items-center gap-2">
                                                    <img src="<?php echo htmlspecialchars($it['image_snapshot']); ?>" class="rounded" style="width: 40px; height: 40px; object-fit: cover;">
                                                    <div>
                                                        <strong class="text-dark brand-font"><?php echo htmlspecialchars($it['fragrance_name_snapshot']); ?></strong>
                                                        <small class="text-muted d-block"><?php echo htmlspecialchars($it['house_name_snapshot']); ?></small>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="small text-muted"><?php echo htmlspecialchars($it['volume_snapshot']); ?></td>
                                            <td class="text-dark fw-bold"><?php echo $it['units']; ?></td>
                                            <td class="text-gold fw-bold">₹<?php echo number_format($it['line_value'], 2); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <div class="d-flex justify-content-between align-items-center pt-3 mt-2 border-top small text-muted">
                            <div>Delivery Address: <?php echo htmlspecialchars($o['delivery_line_one']); ?>, <?php echo htmlspecialchars($o['delivery_city']); ?></div>
                            <div class="fs-6 text-dark">Total: <strong class="text-gold">₹<?php echo number_format($o['payable_value'], 2); ?></strong> (<?php echo htmlspecialchars($o['payment_choice']); ?>)</div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="text-center py-5 card shadow-sm border-0 bg-white rounded-3">
            <i class="fa-solid fa-box-open text-gold fa-3x mb-3"></i>
            <h4 class="text-dark">No Orders Placed Yet</h4>
            <p class="text-muted">You haven't placed any perfume orders yet.</p>
            <a href="fragrances.php" class="btn btn-gold"><i class="fa-solid fa-spray-can me-1"></i> Start Shopping</a>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
