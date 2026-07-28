<?php
// checkout.php - Perfume Checkout & Order Placement Engine
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/helpers/auth.php';
require_once __DIR__ . '/helpers/fragrance_helpers.php';

require_user_login();

$pdo = getPDO();

if (empty($_SESSION['cart'])) {
    header("Location: cart.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Calculate cart totals & build lines
$cart_items = [];
$subtotal = 0.00;

foreach ($_SESSION['cart'] as $key => $item) {
    if (isset($item['name']) && isset($item['price'])) {
        $price = (float)$item['price'];
        $qty = (int)$item['qty'];
        $line_total = $price * $qty;
        $subtotal += $line_total;

        $cart_items[] = [
            'fragrance_id' => $key,
            'fragrance_name' => $item['name'],
            'fragrance_code' => 'PROD-' . $key,
            'house_name' => 'Perfume Hub',
            'volume_label' => 'Standard Pack',
            'edition_title' => 'Standard Edition',
            'unit_price' => $price,
            'qty' => $qty,
            'line_total' => $line_total,
            'image_url' => $item['image_url'] ?: 'https://images.unsplash.com/photo-1594035910387-fea47794261f?w=300'
        ];
    } else {
        $fid = (int)($item['fragrance_id'] ?? 0);
        $vid = (int)($item['volume_id'] ?? 0);
        $eid = (int)($item['edition_id'] ?? 0);
        $qty = (int)($item['qty'] ?? 1);

        $stmt = $pdo->prepare("SELECT f.*, h.house_name, 
            (SELECT remote_image_address FROM fragrance_media WHERE fragrance_id = f.fragrance_id ORDER BY featured_image DESC LIMIT 1) AS image_url 
            FROM fragrances f 
            JOIN perfume_houses h ON f.house_id = h.house_id 
            WHERE f.fragrance_id = :fid");
        $stmt->execute(['fid' => $fid]);
        $frag = $stmt->fetch();

        if ($frag) {
            $price = (float)$frag['offer_price'];
            $vol_label = "Standard Bottle ({$frag['primary_volume_ml']} ml)";
            $ed_title = "Standard Pack";

            if ($vid > 0) {
                $v_stmt = $pdo->prepare("SELECT * FROM fragrance_volumes WHERE volume_id = :vid");
                $v_stmt->execute(['vid' => $vid]);
                $v_data = $v_stmt->fetch();
                if ($v_data) {
                    $price = (float)$v_data['volume_price'];
                    $vol_label = $v_data['volume_label'];
                }
            }

            if ($eid > 0) {
                $e_stmt = $pdo->prepare("SELECT * FROM fragrance_editions WHERE edition_id = :eid");
                $e_stmt->execute(['eid' => $eid]);
                $e_data = $e_stmt->fetch();
                if ($e_data) {
                    $ed_title = $e_data['edition_title'];
                }
            }

            $line_total = $price * $qty;
            $subtotal += $line_total;

            $cart_items[] = [
                'fragrance_id' => $fid,
                'fragrance_name' => $frag['fragrance_name'],
                'fragrance_code' => $frag['fragrance_code'],
                'house_name' => $frag['house_name'],
                'volume_label' => $vol_label,
                'edition_title' => $ed_title,
                'unit_price' => $price,
                'qty' => $qty,
                'line_total' => $line_total,
                'image_url' => $frag['image_url'] ?: 'https://images.unsplash.com/photo-1594035910387-fea47794261f?w=300'
            ];
        }
    }
}

// Process Order Submission BEFORE any HTML output
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['place_order'])) {
    verify_csrf_token($_POST['csrf_token'] ?? '');

    $recipient_name = sanitize_input($_POST['recipient_name'] ?? '');
    $recipient_phone = sanitize_input($_POST['recipient_phone'] ?? '');
    $delivery_line_one = sanitize_input($_POST['delivery_line_one'] ?? '');
    $delivery_line_two = sanitize_input($_POST['delivery_line_two'] ?? '');
    $delivery_city = sanitize_input($_POST['delivery_city'] ?? '');
    $delivery_state = sanitize_input($_POST['delivery_state'] ?? '');
    $delivery_postcode = sanitize_input($_POST['delivery_postcode'] ?? '');
    $payment_choice = sanitize_input($_POST['payment_choice'] ?? 'COD');

    if (empty($recipient_name) || empty($recipient_phone) || empty($delivery_line_one) || empty($delivery_city) || empty($delivery_state) || empty($delivery_postcode)) {
        $error = "Please fill in all mandatory delivery address fields.";
    } else {
        try {
            $pdo->beginTransaction();

            $purchase_reference = generate_purchase_reference($pdo);

            $stmt_order = $pdo->prepare("INSERT INTO purchase_orders 
                (`purchase_reference`, `customer_id`, `recipient_name`, `recipient_phone`, `delivery_line_one`, `delivery_line_two`, `delivery_city`, `delivery_state`, `delivery_postcode`, `items_value`, `payable_value`, `payment_choice`, `payment_state`, `fulfilment_state`) 
                VALUES 
                (:ref, :cid, :rname, :rphone, :line1, :line2, :city, :state, :postcode, :val, :payable, :pay_choice, 'Pending', 'Placed')");
            
            $stmt_order->execute([
                'ref' => $purchase_reference,
                'cid' => $user_id,
                'rname' => $recipient_name,
                'rphone' => $recipient_phone,
                'line1' => $delivery_line_one,
                'line2' => $delivery_line_two,
                'city' => $delivery_city,
                'state' => $delivery_state,
                'postcode' => $delivery_postcode,
                'val' => $subtotal,
                'payable' => $subtotal,
                'pay_choice' => $payment_choice
            ]);

            $purchase_id = $pdo->lastInsertId();

            $stmt_line = $pdo->prepare("INSERT INTO purchase_lines 
                (`purchase_id`, `fragrance_id`, `fragrance_name_snapshot`, `fragrance_code_snapshot`, `house_name_snapshot`, `volume_snapshot`, `edition_snapshot`, `units`, `price_per_unit`, `line_value`, `image_snapshot`) 
                VALUES 
                (:pid, :fid, :fname, :fcode, :hname, :vsnap, :esnap, :units, :price, :lval, :img)");

            $stmt_stock = $pdo->prepare("UPDATE fragrances SET available_units = GREATEST(0, available_units - :units) WHERE fragrance_id = :fid");

            foreach ($cart_items as $ci) {
                $fid_val = is_numeric($ci['fragrance_id']) ? (int)$ci['fragrance_id'] : 0;
                $stmt_line->execute([
                    'pid' => $purchase_id,
                    'fid' => $fid_val > 0 ? $fid_val : null,
                    'fname' => $ci['fragrance_name'],
                    'fcode' => $ci['fragrance_code'],
                    'hname' => $ci['house_name'],
                    'vsnap' => $ci['volume_label'],
                    'esnap' => $ci['edition_title'],
                    'units' => $ci['qty'],
                    'price' => $ci['unit_price'],
                    'lval' => $ci['line_total'],
                    'img' => $ci['image_url']
                ]);

                if ($fid_val > 0) {
                    $stmt_stock->execute(['units' => $ci['qty'], 'fid' => $fid_val]);
                }
            }

            $stmt_pay = $pdo->prepare("INSERT INTO payment_records (`purchase_id`, `payment_method`, `transaction_ref`, `amount`, `payment_status`) VALUES (:pid, :pm, :tx, :amt, 'Completed')");
            $stmt_pay->execute([
                'pid' => $purchase_id,
                'pm' => $payment_choice,
                'tx' => 'TXN-' . strtoupper(bin2hex(random_bytes(4))),
                'amt' => $subtotal
            ]);

            $pdo->commit();
            $_SESSION['cart'] = [];

            header("Location: order_success.php?ref=" . urlencode($purchase_reference));
            exit();
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = "Order Placement Failed: " . $e->getMessage();
        }
    }
}

// Now include header AFTER all action redirects
$page_title = "Checkout Order";
require_once __DIR__ . '/includes/header.php';
?>

<div class="container py-5">
    <h2 class="text-dark brand-font mb-4"><i class="fa-solid fa-truck-ramp-box text-gold me-2"></i> Secure Checkout</h2>

    <?php if (!empty($error)): ?>
        <div class="alert alert-danger mb-4"><?php echo $error; ?></div>
    <?php endif; ?>

    <form action="checkout.php" method="POST">
        <input type="hidden" name="csrf_token" value="<?php echo get_csrf_token(); ?>">
        
        <div class="row g-4">
            <!-- Shipping Details -->
            <div class="col-lg-7">
                <div class="card p-4 shadow-sm border-0 bg-white rounded-3 mb-4">
                    <h4 class="text-dark brand-font mb-3"><i class="fa-solid fa-location-dot text-gold me-2"></i> Delivery Address</h4>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label text-muted small fw-bold">Recipient Full Name *</label>
                            <input type="text" name="recipient_name" class="form-control" required value="<?php echo htmlspecialchars($_SESSION['full_name'] ?? ''); ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-muted small fw-bold">Recipient Phone Number *</label>
                            <input type="text" name="recipient_phone" class="form-control" required placeholder="e.g. 9876543210">
                        </div>

                        <div class="col-12">
                            <label class="form-label text-muted small fw-bold">Address Line 1 *</label>
                            <input type="text" name="delivery_line_one" class="form-control" required placeholder="House No., Street Name">
                        </div>

                        <div class="col-12">
                            <label class="form-label text-muted small fw-bold">Address Line 2 (Optional)</label>
                            <input type="text" name="delivery_line_two" class="form-control" placeholder="Landmark / Area">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label text-muted small fw-bold">City *</label>
                            <input type="text" name="delivery_city" class="form-control" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label text-muted small fw-bold">State *</label>
                            <input type="text" name="delivery_state" class="form-control" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label text-muted small fw-bold">Postcode / PIN *</label>
                            <input type="text" name="delivery_postcode" class="form-control" required>
                        </div>
                    </div>
                </div>

                <!-- Payment Choice -->
                <div class="card p-4 shadow-sm border-0 bg-white rounded-3">
                    <h4 class="text-dark brand-font mb-3"><i class="fa-solid fa-wallet text-gold me-2"></i> Payment Option</h4>

                    <div class="form-check p-3 rounded border mb-2 bg-light">
                        <input class="form-check-input" type="radio" name="payment_choice" id="payCOD" value="COD" checked>
                        <label class="form-check-label text-dark ms-2" for="payCOD">
                            <strong>Cash on Delivery (COD)</strong>
                            <small class="d-block text-muted">Pay cash upon delivery to your doorstep</small>
                        </label>
                    </div>

                    <div class="form-check p-3 rounded border mb-2 bg-light">
                        <input class="form-check-input" type="radio" name="payment_choice" id="payUPI" value="UPI">
                        <label class="form-check-label text-dark ms-2" for="payUPI">
                            <strong>Instant UPI / QR Code</strong>
                            <small class="d-block text-muted">Pay via GPay, PhonePe, Paytm or BHIM</small>
                        </label>
                    </div>

                    <div class="form-check p-3 rounded border bg-light">
                        <input class="form-check-input" type="radio" name="payment_choice" id="payCard" value="Card">
                        <label class="form-check-label text-dark ms-2" for="payCard">
                            <strong>Credit / Debit Card</strong>
                            <small class="d-block text-muted">Visa, Mastercard, RuPay & Amex</small>
                        </label>
                    </div>
                </div>
            </div>

            <!-- Order Review -->
            <div class="col-lg-5">
                <div class="card p-4 shadow-sm border-0 bg-white rounded-3">
                    <h4 class="text-dark brand-font mb-3">Order Items (<?php echo count($cart_items); ?>)</h4>

                    <div class="mb-3" style="max-height: 280px; overflow-y: auto;">
                        <?php foreach ($cart_items as $ci): ?>
                            <div class="d-flex align-items-center gap-3 mb-3 pb-3 border-bottom">
                                <img src="<?php echo htmlspecialchars($ci['image_url']); ?>" class="rounded" style="width: 48px; height: 48px; object-fit: cover;">
                                <div class="flex-grow-1">
                                    <h6 class="text-dark mb-0 brand-font"><?php echo htmlspecialchars($ci['fragrance_name']); ?></h6>
                                    <small class="text-muted d-block"><?php echo htmlspecialchars($ci['volume_label']); ?> &bull; Qty: <?php echo $ci['qty']; ?></small>
                                </div>
                                <strong class="text-gold">₹<?php echo number_format($ci['line_total'], 2); ?></strong>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="d-flex justify-content-between text-muted mb-2">
                        <span>Subtotal</span>
                        <strong class="text-dark">₹<?php echo number_format($subtotal, 2); ?></strong>
                    </div>

                    <div class="d-flex justify-content-between text-muted mb-2">
                        <span>Shipping</span>
                        <strong class="text-success">FREE</strong>
                    </div>

                    <hr class="my-3">

                    <div class="d-flex justify-content-between align-items-baseline mb-4">
                        <span class="fs-5 text-dark">Total Amount</span>
                        <strong class="fs-3 text-gold">₹<?php echo number_format($subtotal, 2); ?></strong>
                    </div>

                    <button type="submit" name="place_order" class="btn btn-gold btn-lg w-100 py-3 fw-bold"><i class="fa-solid fa-lock me-2"></i> Confirm & Place Order</button>
                </div>
            </div>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>