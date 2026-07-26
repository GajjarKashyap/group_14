<?php
// index.php - Perfume Hub Homepage
$page_title = "Home - Luxury Fragrance Boutique";
require_once __DIR__ . '/includes/header.php';

$pdo = getPDO();

// Fetch Featured Fragrances
$stmt_featured = $pdo->query("SELECT f.*, h.house_name, fam.family_name, 
    (SELECT remote_image_address FROM fragrance_media WHERE fragrance_id = f.fragrance_id ORDER BY featured_image DESC LIMIT 1) AS image_url 
    FROM fragrances f 
    JOIN perfume_houses h ON f.house_id = h.house_id 
    LEFT JOIN fragrance_families fam ON f.family_id = fam.family_id 
    WHERE f.record_state = 'Published' 
    ORDER BY f.fragrance_id DESC LIMIT 8");
$featured_fragrances = $stmt_featured->fetchAll();
?>

<!-- Hero Banner Section -->
<section class="py-5 text-center bg-light border-bottom">
    <div class="container py-4">
        <span class="badge bg-gold text-white px-3 py-2 text-uppercase mb-3">Authentic Luxury Perfumery</span>
        <h1 class="display-4 font-brand text-dark mb-3">Discover Your Signature Scent</h1>
        <p class="lead text-muted max-w-700 mx-auto mb-4">Explore world-renowned perfume houses, rare oriental attars, body mists, and luxury gift collections.</p>
        <div class="d-flex justify-content-center gap-3">
            <a href="fragrances.php" class="btn btn-gold btn-lg px-4"><i class="fa-solid fa-compass me-2"></i> Explore All Perfumes</a>
            <a href="dashboard.php" class="btn btn-outline-gold btn-lg px-4"><i class="fa-solid fa-store me-2"></i> Shop Dashboard</a>
        </div>
    </div>
</section>

<!-- Featured Products Grid -->
<section class="py-5">
    <div class="container">
        <div class="d-flex justify-content-between align-items-end mb-4">
            <div>
                <span class="text-gold text-uppercase small fw-bold">Exclusive Catalog</span>
                <h2 class="text-dark mb-0">Featured Fragrances</h2>
            </div>
            <a href="fragrances.php" class="text-gold text-decoration-none fw-bold">View All <i class="fa-solid fa-arrow-right ms-1"></i></a>
        </div>

        <div class="row g-4">
            <?php foreach ($featured_fragrances as $item): ?>
                <div class="col-lg-3 col-md-6">
                    <div class="card card-custom h-100 p-3 d-flex flex-column">
                        <img src="<?php echo htmlspecialchars($item['image_url'] ?: 'https://images.unsplash.com/photo-1594035910387-fea47794261f?w=600&auto=format&fit=crop&q=80'); ?>" alt="<?php echo htmlspecialchars($item['fragrance_name']); ?>" class="card-img-top rounded mb-3" style="height: 220px; object-fit: cover;">
                        <small class="text-gold text-uppercase fw-bold mb-1"><?php echo htmlspecialchars($item['house_name']); ?></small>
                        <h5 class="text-dark brand-font mb-2"><?php echo htmlspecialchars($item['fragrance_name']); ?></h5>
                        <p class="text-muted small mb-3 flex-grow-1" style="height: 40px; overflow: hidden;"><?php echo htmlspecialchars($item['summary']); ?></p>

                        <div class="d-flex align-items-center justify-content-between mt-auto pt-2 border-top">
                            <div>
                                <span class="fs-5 fw-bold text-gold">₹<?php echo number_format($item['offer_price'], 2); ?></span>
                            </div>
                            <a href="fragrance.php?code=<?php echo urlencode($item['fragrance_code']); ?>" class="btn btn-outline-gold btn-sm"><i class="fa-solid fa-eye me-1"></i> View</a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>