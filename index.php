<?php
// index.php - Perfume Hub Homepage (Minimal Luxury Gold Theme)
$page_title = "Home - Luxury Fragrance Boutique";
require_once __DIR__ . '/includes/header.php';

$pdo = getPDO();

// Fetch Featured Fragrances
$stmt_featured = $pdo->query("SELECT f.*, h.house_name, fam.family_name, p.image_url AS image_url 
    FROM fragrances f 
    LEFT JOIN products p ON f.fragrance_id = p.id
    LEFT JOIN perfume_houses h ON f.house_id = h.house_id 
    LEFT JOIN fragrance_families fam ON f.family_id = fam.family_id 
    WHERE f.record_state = 'Published' 
    ORDER BY f.fragrance_id DESC LIMIT 8");
$featured_fragrances = $stmt_featured->fetchAll();
?>

<!-- Hero Banner Section -->
<section class="py-5 text-center bg-light border-bottom position-relative">
    <div class="container py-5">
        <span class="badge bg-gold text-white px-3 py-2 text-uppercase mb-3 rounded-pill fs-7 tracking-wider"><i class="fa-solid fa-crown me-1"></i> Authentic Luxury Perfumery</span>
        <h1 class="display-4 brand-font text-dark mb-3 fw-bold">Discover Your Signature Scent</h1>
        <p class="lead text-muted max-w-700 mx-auto mb-4 fs-5">Explore world-renowned perfume houses, rare oriental attars, body mists, and luxury gift collections.</p>
        <div class="d-flex justify-content-center gap-3">
            <a href="fragrances.php" class="btn btn-gold btn-lg px-4 rounded-pill fw-bold shadow-sm"><i class="fa-solid fa-gem me-2"></i> Explore Collections</a>
            <a href="dashboard.php" class="btn btn-outline-gold btn-lg px-4 rounded-pill fw-bold"><i class="fa-solid fa-layer-group me-2"></i> Inventory Dashboard</a>
        </div>
    </div>
</section>

<!-- Featured Products Grid -->
<section class="py-5">
    <div class="container">
        <div class="d-flex justify-content-between align-items-end mb-4">
            <div>
                <span class="text-gold text-uppercase small fw-bold"><i class="fa-solid fa-sparkles me-1"></i> Exclusive Catalog</span>
                <h2 class="text-dark brand-font mb-0">Featured Fragrances</h2>
            </div>
            <a href="fragrances.php" class="text-gold text-decoration-none fw-bold">View All <i class="fa-solid fa-arrow-right ms-1"></i></a>
        </div>

        <div class="row g-4">
            <?php foreach ($featured_fragrances as $item): ?>
                <?php 
                    $mrp_val = floatval($item['list_price']);
                    $offer_val = floatval($item['offer_price']);
                    if ($mrp_val <= 0) $mrp_val = $offer_val * 1.15;
                    $discount_pct = ($mrp_val > $offer_val) ? round((($mrp_val - $offer_val) / $mrp_val) * 100) : 0;
                    $brand_name = !empty($item['house_name']) ? $item['house_name'] : 'Perfume Hub';
                ?>
                <div class="col-lg-3 col-md-6">
                    <div class="card card-custom h-100 p-3 rounded-4 d-flex flex-column">
                        <div class="position-relative mb-3">
                            <img src="<?php echo htmlspecialchars(optimize_image_url($item['image_url'], 'card')); ?>" alt="<?php echo htmlspecialchars($item['fragrance_name']); ?>" class="card-img-top rounded-3 p-2" style="height: 220px; object-fit: contain; background-color: #fafafa;" loading="lazy">
                            <?php if ($discount_pct > 0): ?>
                                <span class="position-absolute top-0 end-0 bg-gold text-white small fw-bold px-2 py-1 m-2 rounded-pill fs-7 shadow-sm">
                                    SAVE <?php echo $discount_pct; ?>%
                                </span>
                            <?php endif; ?>
                        </div>

                        <small class="text-gold text-uppercase fw-bold mb-1 fs-7"><?php echo htmlspecialchars($brand_name); ?></small>
                        <h5 class="text-dark brand-font mb-2"><?php echo htmlspecialchars($item['fragrance_name']); ?></h5>
                        <p class="text-muted small mb-3 flex-grow-1" style="height: 40px; overflow: hidden;"><?php echo htmlspecialchars($item['summary']); ?></p>

                        <div class="d-flex align-items-center justify-content-between mt-auto pt-2 border-top">
                            <div>
                                <span class="fs-5 fw-bold text-gold">₹<?php echo number_format($offer_val, 2); ?></span>
                                <?php if ($mrp_val > $offer_val): ?>
                                    <small class="text-muted text-decoration-line-through d-block fs-7">₹<?php echo number_format($mrp_val, 2); ?></small>
                                <?php endif; ?>
                            </div>
                            <a href="fragrance.php?code=<?php echo urlencode($item['fragrance_code']); ?>" class="btn btn-outline-gold btn-sm rounded-pill px-3"><i class="fa-solid fa-eye me-1"></i> View</a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>