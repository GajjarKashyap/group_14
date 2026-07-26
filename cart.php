<?php
// cart.php - Perfume Basket & Quantity Engine (Minimal Luxury Gold Theme)
require_once __DIR__ . '/config/db.php';

if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Action Handler BEFORE any HTML output
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add') {
        $fragrance_id = (int)($_POST['fragrance_id'] ?? 0);
        $volume_id = (int)($_POST['volume_id'] ?? 0);
        $edition_id = (int)($_POST['edition_id'] ?? 0);
        $units = max(1, (int)($_POST['units'] ?? 1));

        $cart_key = "{$fragrance_id}_{$volume_id}_{$edition_id}";

        if (isset($_SESSION['cart'][$cart_key])) {
            $_SESSION['cart'][$cart_key]['qty'] += $units;
        } else {
            $_SESSION['cart'][$cart_key] = [
                'fragrance_id' => $fragrance_id,
                'volume_id' => $volume_id,
                'edition_id' => $edition_id,
                'qty' => $units
            ];
        }

        header("Location: cart.php");
        exit();
    }

    if ($action === 'update') {
        $cart_key = $_POST['cart_key'] ?? '';
        $qty = (int)($_POST['qty'] ?? 1);
        if ($qty <= 0) {
            unset($_SESSION['cart'][$cart_key]);
        } else {
            if (isset($_SESSION['cart'][$cart_key])) {
                $_SESSION['cart'][$cart_key]['qty'] = $qty;
            }
        }
        header("Location: cart.php");
        exit();
    }

    if ($action === 'remove') {
        $cart_key = $_POST['cart_key'] ?? '';
        unset($_SESSION['cart'][$cart_key]);
        header("Location: cart.php");
        exit();
    }

    if ($action === 'clear') {
        $_SESSION['cart'] = [];
        header("Location: cart.php");
        exit();
    }
}

// Resolve cart item details from Database
$pdo = getPDO();
$cart_items = [];
$subtotal = 0.00;

foreach ($_SESSION['cart'] as $key => $item) {
    if (isset($item['name']) && isset($item['price'])) {
        // Products table item (dashboard)
        $price = (float)$item['price'];
        $qty = (int)$item['qty'];
        $line_total = $price * $qty;
        $subtotal += $line_total;

        $cart_items[] = [
            'cart_key' => $key,
            'fragrance_name' => $item['name'],
            'house_name' => 'Perfume Hub',
            'volume_label' => 'Standard Bottle',
            'edition_title' => 'Standard Pack',
            'unit_price' => $price,
            'qty' => $qty,
            'line_total' => $line_total,
            'image_url' => $item['image_url'] ?: 'https://images.unsplash.com/photo-1594035910387-fea47794261f?w=300'
        ];
    } else {
        // Fragrances table item
        $fid = (int)($item['fragrance_id'] ?? 0);
        $vid = (int)($item['volume_id'] ?? 0);
        $eid = (int)($item['edition_id'] ?? 0);
        $qty = (int)($item['qty'] ?? 1);

        $stmt = $pdo->prepare("SELECT f.*, h.house_name, 
            (SELECT remote_image_address FROM fragrance_media WHERE fragrance_id = f.fragrance_id ORDER BY featured_image DESC LIMIT 1) AS image_url 
            FROM fragrances f 
            LEFT JOIN perfume_houses h ON f.house_id = h.house_id 
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
                'cart_key' => $key,
                'fragrance_id' => $fid,
                'fragrance_name' => $frag['fragrance_name'],
                'house_name' => $frag['house_name'] ?: 'Perfume Hub',
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

// Now include header AFTER all action redirects
$page_title = "Shopping Basket";
require_once __DIR__ . '/includes/header.php';
?>

<div class="container py-5">
    <h2 class="text-dark brand-font mb-4"><i class="fa-solid fa-basket-shopping text-gold me-2"></i> Your Shopping Basket</h2>

    <?php if (!empty($cart_items)): ?>
        <div class="row g-4">
            <div class="col-lg-8">
                <div class="card p-4 shadow-sm border-0 bg-white rounded-4">
                    <div class="table-responsive">
                        <table class="table align-middle mb-0">
                            <thead>
                                <tr class="text-muted small border-bottom">
                                    <th>Item Details</th>
                                    <th>Price</th>
                                    <th style="width: 120px;">Quantity</th>
                                    <th>Total</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($cart_items as $ci): ?>
                                    <tr class="border-bottom">
                                        <td>
                                            <div class="d-flex align-items-center gap-3">
                                                <img src="<?php echo htmlspecialchars($ci['image_url']); ?>" alt="Perfume" class="rounded-3" style="width: 60px; height: 60px; object-fit: cover;">
                                                <div>
                                                    <small class="text-gold text-uppercase fw-bold d-block fs-7"><?php echo htmlspecialchars($ci['house_name']); ?></small>
                                                    <strong class="text-dark brand-font fs-6"><?php echo htmlspecialchars($ci['fragrance_name']); ?></strong>
                                                    <div class="text-muted small">
                                                        <span><?php echo htmlspecialchars($ci['volume_label']); ?></span>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="text-dark fw-bold">₹<?php echo number_format($ci['unit_price'], 2); ?></td>
                                        <td>
                                            <form action="cart.php" method="POST" class="d-flex align-items-center">
                                                <input type="hidden" name="action" value="update">
                                                <input type="hidden" name="cart_key" value="<?php echo $ci['cart_key']; ?>">
                                                <input type="number" name="qty" class="form-control form-control-sm text-center rounded-pill" value="<?php echo $ci['qty']; ?>" min="1" onchange="this.form.submit();">
                                            </form>
                                        </td>
                                        <td class="text-gold fw-bold">₹<?php echo number_format($ci['line_total'], 2); ?></td>
                                        <td>
                                            <form action="cart.php" method="POST">
                                                <input type="hidden" name="action" value="remove">
                                                <input type="hidden" name="cart_key" value="<?php echo $ci['cart_key']; ?>">
                                                <button type="submit" class="btn btn-outline-danger btn-sm rounded-circle"><i class="fa-solid fa-trash"></i></button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mt-3 pt-3 border-top">
                        <a href="fragrances.php" class="btn btn-outline-gold btn-sm rounded-pill px-3"><i class="fa-solid fa-arrow-left me-1"></i> Continue Shopping</a>
                        <form action="cart.php" method="POST">
                            <input type="hidden" name="action" value="clear">
                            <button type="submit" class="btn btn-sm btn-outline-secondary rounded-pill px-3"><i class="fa-solid fa-ban me-1"></i> Clear Basket</button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Order Summary -->
            <div class="col-lg-4">
                <div class="card p-4 shadow-sm border-0 bg-white rounded-4">
                    <h4 class="text-dark brand-font mb-3">Order Summary</h4>

                    <div class="d-flex justify-content-between text-muted mb-2">
                        <span>Items Subtotal</span>
                        <strong class="text-dark">₹<?php echo number_format($subtotal, 2); ?></strong>
                    </div>

                    <div class="d-flex justify-content-between text-muted mb-2">
                        <span>Express Delivery</span>
                        <strong class="text-success">FREE</strong>
                    </div>

                    <hr class="my-3">

                    <div class="d-flex justify-content-between align-items-baseline mb-4">
                        <span class="fs-5 text-dark">Payable Amount</span>
                        <strong class="fs-3 text-gold">₹<?php echo number_format($subtotal, 2); ?></strong>
                    </div>

                    <a href="checkout.php" class="btn btn-gold btn-lg w-100 py-3 fw-bold rounded-pill shadow-sm"><i class="fa-solid fa-credit-card me-2"></i> Proceed to Checkout</a>
                </div>
            </div>
        </div>
    <?php else: ?>
        <div class="text-center py-5 card shadow-sm border-0 bg-white rounded-4">
            <i class="fa-solid fa-basket-shopping text-gold fa-4x mb-3"></i>
            <h3 class="text-dark brand-font">Your Basket is Empty</h3>
            <p class="text-muted mb-4">Looks like you haven't added any luxury fragrances to your basket yet.</p>
            <div><a href="fragrances.php" class="btn btn-gold rounded-pill px-4"><i class="fa-solid fa-gem me-2"></i> Explore Fragrances</a></div>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
