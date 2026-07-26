<?php
// fragrance.php - Interactive Multi-Image Product Details & Olfactory Notes Pyramid
require_once __DIR__ . '/config/db.php';
$pdo = getPDO();

$code = isset($_GET['code']) ? trim($_GET['code']) : '';
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$fragrance = null;
$images = [];

if (!empty($code)) {
    $stmt = $pdo->prepare("SELECT f.*, h.house_name, col.collection_name, fam.family_name, str.strength_name 
        FROM fragrances f 
        LEFT JOIN perfume_houses h ON f.house_id = h.house_id 
        LEFT JOIN fragrance_collections col ON f.collection_id = col.collection_id 
        LEFT JOIN fragrance_families fam ON f.family_id = fam.family_id 
        LEFT JOIN fragrance_strengths str ON f.strength_id = str.strength_id 
        WHERE f.fragrance_code = :code");
    $stmt->execute(['code' => $code]);
    $fragrance = $stmt->fetch();
} elseif ($id > 0) {
    $stmt = $pdo->prepare("SELECT f.*, h.house_name, col.collection_name, fam.family_name, str.strength_name 
        FROM fragrances f 
        LEFT JOIN perfume_houses h ON f.house_id = h.house_id 
        LEFT JOIN fragrance_collections col ON f.collection_id = col.collection_id 
        LEFT JOIN fragrance_families fam ON f.family_id = fam.family_id 
        LEFT JOIN fragrance_strengths str ON f.strength_id = str.strength_id 
        WHERE f.fragrance_id = :id");
    $stmt->execute(['id' => $id]);
    $fragrance = $stmt->fetch();
}

// Fallback lookup from products table if not found in fragrances
if (!$fragrance && $id > 0) {
    $p_stmt = $pdo->prepare("SELECT * FROM products WHERE id = :id");
    $p_stmt->execute(['id' => $id]);
    $prod = $p_stmt->fetch();
    if ($prod) {
        $mrp_val = floatval($prod['mrp'] > 0 ? $prod['mrp'] : $prod['price'] * 1.15);
        $price_val = floatval($prod['price']);
        $discount = ($mrp_val > $price_val) ? round((($mrp_val - $price_val) / $mrp_val) * 100, 2) : 0;
        
        $fragrance = [
            'fragrance_id' => $prod['id'],
            'fragrance_code' => 'PROD-' . $prod['id'],
            'fragrance_name' => $prod['name'],
            'house_name' => $prod['brand'] ?: 'Perfume Hub',
            'audience' => $prod['category'],
            'summary' => $prod['description'] ?: 'Luxury long-lasting fragrance',
            'full_story' => $prod['description'] ?: 'Luxury long-lasting fragrance crafted with royal ingredients.',
            'list_price' => $mrp_val,
            'offer_price' => $price_val,
            'discount_rate' => $discount,
            'available_units' => 50,
            'primary_volume_ml' => 100
        ];

        // Parse multiple image URLs from products.image_urls
        if (!empty($prod['image_urls'])) {
            $lines = preg_split('/\r\n|\r|\n|,/', $prod['image_urls']);
            foreach ($lines as $line) {
                $u = trim($line);
                if (!empty($u)) $images[] = $u;
            }
        }
        if (empty($images) && !empty($prod['image_url'])) {
            $images[] = $prod['image_url'];
        }
    }
}

if (!$fragrance) {
    header("Location: fragrances.php");
    exit();
}

$fragrance_id = (int)($fragrance['fragrance_id'] ?? 0);
$brand_title = $fragrance['house_name'] ?? 'Perfume Hub';
$page_title = $fragrance['fragrance_name'] . " (" . $brand_title . ")";

// Increment view count if table exists
if ($fragrance_id > 0 && isset($fragrance['fragrance_code'])) {
    $upd_vc = $pdo->prepare("UPDATE fragrances SET view_count = view_count + 1 WHERE fragrance_id = :fid");
    $upd_vc->execute(['fid' => $fragrance_id]);
}

// Fetch Notes if available
$all_notes = [];
if ($fragrance_id > 0) {
    $stmt_notes = $pdo->prepare("SELECT * FROM fragrance_notes WHERE fragrance_id = :fid ORDER BY note_stage DESC, display_position ASC");
    $stmt_notes->execute(['fid' => $fragrance_id]);
    $all_notes = $stmt_notes->fetchAll();
}

$top_notes = array_filter($all_notes, fn($n) => $n['note_stage'] === 'opening');
$heart_notes = array_filter($all_notes, fn($n) => $n['note_stage'] === 'heart');
$base_notes = array_filter($all_notes, fn($n) => $n['note_stage'] === 'dry_down');

// Fetch images from products table (since products is the master inventory table)
if (empty($images) && $fragrance_id > 0) {
    $stmt_p = $pdo->prepare("SELECT image_url, image_urls FROM products WHERE id = :fid");
    $stmt_p->execute(['fid' => $fragrance_id]);
    $prod_images = $stmt_p->fetch();
    if ($prod_images) {
        if (!empty($prod_images['image_urls'])) {
            $lines = preg_split('/\r\n|\r|\n|,/', $prod_images['image_urls']);
            foreach ($lines as $line) {
                $u = trim($line);
                if (!empty($u) && !in_array($u, $images)) {
                    $images[] = $u;
                }
            }
        }
        if (empty($images) && !empty($prod_images['image_url'])) {
            $images[] = $prod_images['image_url'];
        }
    }
}

// Fetch Media from fragrance_media if images array is still empty
if (empty($images) && $fragrance_id > 0) {
    $stmt_media = $pdo->prepare("SELECT * FROM fragrance_media WHERE fragrance_id = :fid ORDER BY featured_image DESC, media_id ASC");
    $stmt_media->execute(['fid' => $fragrance_id]);
    $media_rows = $stmt_media->fetchAll();
    foreach ($media_rows as $m) {
        if (!empty($m['remote_image_address']) && !in_array($m['remote_image_address'], $images)) {
            $images[] = $m['remote_image_address'];
        }
    }
}


// Fallback default image
if (empty($images)) {
    $images[] = 'https://images.unsplash.com/photo-1594035910387-fea47794261f?w=800';
}

$main_image = $images[0];
$mrp_price = floatval($fragrance['list_price']);
$offer_price = floatval($fragrance['offer_price']);
if ($mrp_price <= 0) $mrp_price = $offer_price * 1.15;
$discount_pct = ($mrp_price > $offer_price) ? round((($mrp_price - $offer_price) / $mrp_price) * 100) : 0;

require_once __DIR__ . '/includes/header.php';
?>

<div class="container py-5">
    <div class="row g-5 align-items-start">
        <!-- Interactive Multi-Image Gallery Column -->
        <div class="col-lg-6">
            <div class="card p-4 shadow-sm border-0 bg-white rounded-4 text-center">
                <!-- Main Featured Remote Image -->
                <div class="position-relative mb-4">
                    <img id="mainPerfumeImage" src="<?php echo htmlspecialchars(optimize_image_url($main_image, 'full')); ?>" alt="<?php echo htmlspecialchars($fragrance['fragrance_name']); ?>" class="img-fluid rounded-3 transition-all" style="max-height: 420px; object-fit: contain;" fetchpriority="high" decoding="async">
                    <?php if ($discount_pct > 0): ?>
                        <span class="position-absolute top-0 end-0 bg-gold text-white fw-bold px-3 py-1 rounded-pill fs-7 shadow-sm">
                            SAVE <?php echo $discount_pct; ?>%
                        </span>
                    <?php endif; ?>
                </div>

                <!-- Multi-Image Thumbnails Bar -->
                <?php if (count($images) > 1): ?>
                    <div class="d-flex justify-content-center gap-3 overflow-x-auto py-2 border-top">
                        <?php foreach ($images as $idx => $img_url): ?>
                            <img src="<?php echo htmlspecialchars(optimize_image_url($img_url, 'thumbnail')); ?>" 
                                 class="img-thumbnail rounded-3 cursor-pointer thumbnail-gallery-item <?php echo ($idx === 0) ? 'border-gold shadow-sm' : ''; ?>" 
                                 style="width: 75px; height: 75px; object-fit: cover;"
                                 onclick="switchProductImage('<?php echo htmlspecialchars(optimize_image_url($img_url, 'full'), ENT_QUOTES); ?>', this);">
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Product Details & Purchase Controls Column -->
        <div class="col-lg-6">
            <div class="ps-lg-3">
                <span class="badge bg-gold text-white text-uppercase px-3 py-2 mb-2 rounded-pill fs-7 tracking-wider">
                    <i class="fa-solid fa-crown me-1"></i> <?php echo htmlspecialchars($brand_title); ?>
                </span>
                <h1 class="text-dark brand-font display-5 fw-bold mb-3"><?php echo htmlspecialchars($fragrance['fragrance_name']); ?></h1>

                <div class="d-flex align-items-baseline gap-3 mb-4">
                    <span class="display-6 fw-bold text-gold">₹<span id="displayPrice"><?php echo number_format($offer_price, 2); ?></span></span>
                    <?php if ($mrp_price > $offer_price): ?>
                        <span class="text-muted text-decoration-line-through fs-5">₹<?php echo number_format($mrp_price, 2); ?></span>
                        <span class="badge bg-success text-white small rounded-pill px-2 py-1">Save ₹<?php echo number_format($mrp_price - $offer_price, 2); ?></span>
                    <?php endif; ?>
                </div>

                <p class="text-muted lead fs-6 mb-4 leading-relaxed"><?php echo htmlspecialchars($fragrance['summary']); ?></p>

                <!-- Purchase Form -->
                <form action="cart.php" method="POST" class="card p-4 shadow-sm border-0 bg-light rounded-4 mb-4">
                    <input type="hidden" name="action" value="add">
                    <input type="hidden" name="fragrance_id" value="<?php echo $fragrance_id; ?>">

                    <div class="row align-items-center g-3">
                        <div class="col-md-4">
                            <div class="input-group">
                                <span class="input-group-text bg-white border-0 text-muted small fw-bold">Qty</span>
                                <input type="number" name="units" class="form-control form-control-lg text-center bg-white border-0 fw-bold" value="1" min="1" max="<?php echo $fragrance['available_units'] ?? 50; ?>">
                            </div>
                        </div>
                        <div class="col-md-8">
                            <button type="submit" class="btn btn-gold btn-lg w-100 fw-bold rounded-3 shadow-sm py-3"><i class="fa-solid fa-basket-shopping me-2"></i> Add to Basket</button>
                        </div>
                    </div>
                </form>

                <!-- Key Attributes Badges -->
                <div class="row g-3 text-muted small border-top pt-4">
                    <div class="col-6">
                        <i class="fa-solid fa-truck-fast text-gold me-2"></i> <strong>Free Express Shipping</strong> across India
                    </div>
                    <div class="col-6">
                        <i class="fa-solid fa-shield-check text-gold me-2"></i> <strong>100% Genuine</strong> Remote Direct Stock
                    </div>
                    <div class="col-6">
                        <i class="fa-solid fa-clock-rotate-left text-gold me-2"></i> <strong>Long Lasting</strong> 12+ Hours Longevity
                    </div>
                    <div class="col-6">
                        <i class="fa-solid fa-gift text-gold me-2"></i> <strong>Luxury Gift Packaging</strong> Included
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Notes Pyramid Section -->
    <?php if (!empty($all_notes)): ?>
        <div class="row mt-5 pt-4">
            <div class="col-12">
                <div class="card p-4 p-md-5 shadow-sm border-0 bg-light rounded-4 text-center">
                    <span class="text-gold text-uppercase fw-bold small"><i class="fa-solid fa-layer-group me-1"></i> Olfactory Composition</span>
                    <h3 class="text-dark brand-font mb-4"><i class="fa-solid fa-layer-group text-gold me-2"></i> Fragrance Notes Pyramid</h3>

                    <div class="row g-4">
                        <div class="col-md-4">
                            <div class="p-4 rounded-4 bg-white border h-100 shadow-sm">
                                <div class="text-gold mb-2"><i class="fa-solid fa-sun fa-2x"></i></div>
                                <h5 class="text-dark brand-font fw-bold">Top Notes (Opening)</h5>
                                <small class="text-muted d-block mb-3">First 15-30 minutes</small>
                                <?php foreach ($top_notes as $n): ?>
                                    <span class="badge bg-gold text-white fs-6 m-1 px-3 py-2 rounded-pill"><?php echo htmlspecialchars($n['ingredient_name']); ?></span>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="p-4 rounded-4 bg-white border h-100 shadow-sm">
                                <div class="text-gold mb-2"><i class="fa-solid fa-heart fa-2x"></i></div>
                                <h5 class="text-dark brand-font fw-bold">Heart Notes (Middle)</h5>
                                <small class="text-muted d-block mb-3">Lasts 2-4 hours</small>
                                <?php foreach ($heart_notes as $n): ?>
                                    <span class="badge bg-gold text-white fs-6 m-1 px-3 py-2 rounded-pill"><?php echo htmlspecialchars($n['ingredient_name']); ?></span>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="p-4 rounded-4 bg-white border h-100 shadow-sm">
                                <div class="text-gold mb-2"><i class="fa-solid fa-tree fa-2x"></i></div>
                                <h5 class="text-dark brand-font fw-bold">Base Notes (Dry-Down)</h5>
                                <small class="text-muted d-block mb-3">Lasts 8+ hours</small>
                                <?php foreach ($base_notes as $n): ?>
                                    <span class="badge bg-gold text-white fs-6 m-1 px-3 py-2 rounded-pill"><?php echo htmlspecialchars($n['ingredient_name']); ?></span>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
function switchProductImage(imageUrl, element) {
    const mainImg = document.getElementById('mainPerfumeImage');
    if (mainImg) {
        mainImg.style.opacity = '0.4';
        setTimeout(() => {
            mainImg.src = imageUrl;
            mainImg.style.opacity = '1';
        }, 150);
    }
    document.querySelectorAll('.thumbnail-gallery-item').forEach(el => {
        el.classList.remove('border-gold', 'shadow-sm');
    });
    element.classList.add('border-gold', 'shadow-sm');
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
