<?php
// fragrance.php - Perfume Details & Fragrance Notes Pyramid (Clean White Theme)
require_once __DIR__ . '/config/db.php';
$pdo = getPDO();

$code = isset($_GET['code']) ? trim($_GET['code']) : '';
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!empty($code)) {
    $stmt = $pdo->prepare("SELECT f.*, h.house_name, col.collection_name, fam.family_name, str.strength_name 
        FROM fragrances f 
        JOIN perfume_houses h ON f.house_id = h.house_id 
        JOIN fragrance_collections col ON f.collection_id = col.collection_id 
        LEFT JOIN fragrance_families fam ON f.family_id = fam.family_id 
        LEFT JOIN fragrance_strengths str ON f.strength_id = str.strength_id 
        WHERE f.fragrance_code = :code");
    $stmt->execute(['code' => $code]);
} elseif ($id > 0) {
    $stmt = $pdo->prepare("SELECT f.*, h.house_name, col.collection_name, fam.family_name, str.strength_name 
        FROM fragrances f 
        JOIN perfume_houses h ON f.house_id = h.house_id 
        JOIN fragrance_collections col ON f.collection_id = col.collection_id 
        LEFT JOIN fragrance_families fam ON f.family_id = fam.family_id 
        LEFT JOIN fragrance_strengths str ON f.strength_id = str.strength_id 
        WHERE f.fragrance_id = :id");
    $stmt->execute(['id' => $id]);
} else {
    header("Location: fragrances.php");
    exit();
}

$fragrance = $stmt->fetch();
if (!$fragrance) {
    die("Error: Requested fragrance was not found in catalog.");
}

$fragrance_id = (int)$fragrance['fragrance_id'];
$page_title = $fragrance['fragrance_name'] . " (" . $fragrance['house_name'] . ")";

$upd_vc = $pdo->prepare("UPDATE fragrances SET view_count = view_count + 1 WHERE fragrance_id = :fid");
$upd_vc->execute(['fid' => $fragrance_id]);

// Fetch Notes
$stmt_notes = $pdo->prepare("SELECT * FROM fragrance_notes WHERE fragrance_id = :fid ORDER BY note_stage DESC, display_position ASC");
$stmt_notes->execute(['fid' => $fragrance_id]);
$all_notes = $stmt_notes->fetchAll();

$top_notes = array_filter($all_notes, fn($n) => $n['note_stage'] === 'opening');
$heart_notes = array_filter($all_notes, fn($n) => $n['note_stage'] === 'heart');
$base_notes = array_filter($all_notes, fn($n) => $n['note_stage'] === 'dry_down');

// Fetch Media
$stmt_media = $pdo->prepare("SELECT * FROM fragrance_media WHERE fragrance_id = :fid ORDER BY featured_image DESC LIMIT 1");
$stmt_media->execute(['fid' => $fragrance_id]);
$media = $stmt_media->fetch();
$primary_img = $media['remote_image_address'] ?? 'https://images.unsplash.com/photo-1594035910387-fea47794261f?w=800';

require_once __DIR__ . '/includes/header.php';
?>

<div class="container py-5">
    <div class="row g-5">
        <!-- Image Gallery Column -->
        <div class="col-lg-5 text-center">
            <div class="card p-3 shadow-sm border-0 bg-light rounded-3">
                <img id="mainPerfumeImage" src="<?php echo htmlspecialchars($primary_img); ?>" alt="<?php echo htmlspecialchars($fragrance['fragrance_name']); ?>" class="img-fluid rounded" style="max-height: 400px; object-fit: contain;">
            </div>
        </div>

        <!-- Product Details Column -->
        <div class="col-lg-7">
            <span class="text-gold text-uppercase fw-bold"><?php echo htmlspecialchars($fragrance['house_name']); ?></span>
            <h1 class="text-dark brand-font display-5 mb-2"><?php echo htmlspecialchars($fragrance['fragrance_name']); ?></h1>

            <div class="d-flex align-items-baseline gap-3 mb-3">
                <span class="display-6 fw-bold text-gold">₹<span id="displayPrice"><?php echo number_format($fragrance['offer_price'], 2); ?></span></span>
                <?php if ($fragrance['list_price'] > $fragrance['offer_price']): ?>
                    <span class="text-muted text-decoration-line-through fs-5">₹<?php echo number_format($fragrance['list_price'], 2); ?></span>
                <?php endif; ?>
            </div>

            <p class="text-muted lead fs-6 mb-4"><?php echo htmlspecialchars($fragrance['summary']); ?></p>

            <form action="cart.php" method="POST" class="card p-4 shadow-sm border-0 bg-light rounded-3 mb-4">
                <input type="hidden" name="action" value="add">
                <input type="hidden" name="fragrance_id" value="<?php echo $fragrance_id; ?>">

                <div class="row align-items-center g-3">
                    <div class="col-md-4">
                        <div class="input-group">
                            <span class="input-group-text bg-white text-muted">Qty</span>
                            <input type="number" name="units" class="form-control text-center bg-white" value="1" min="1" max="<?php echo $fragrance['available_units']; ?>">
                        </div>
                    </div>
                    <div class="col-md-8">
                        <button type="submit" class="btn btn-gold btn-lg w-100 fw-bold"><i class="fa-solid fa-basket-shopping me-2"></i> Add to Basket</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Notes Pyramid -->
    <div class="row mt-5">
        <div class="col-12">
            <div class="card p-4 shadow-sm border-0 bg-light rounded-3 text-center">
                <span class="text-gold text-uppercase fw-bold small">Olfactory Composition</span>
                <h3 class="text-dark brand-font mb-4"><i class="fa-solid fa-layer-group text-gold me-2"></i> Fragrance Notes Pyramid</h3>

                <div class="row g-4">
                    <div class="col-md-4">
                        <div class="p-3 rounded bg-white border h-100">
                            <div class="text-gold mb-2"><i class="fa-solid fa-sun fa-2x"></i></div>
                            <h5 class="text-dark brand-font">Top Notes (Opening)</h5>
                            <small class="text-muted d-block mb-3">First 15-30 minutes</small>
                            <?php foreach ($top_notes as $n): ?>
                                <span class="badge bg-gold text-white fs-6 m-1 px-3 py-2"><?php echo htmlspecialchars($n['ingredient_name']); ?></span>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="p-3 rounded bg-white border h-100">
                            <div class="text-gold mb-2"><i class="fa-solid fa-heart fa-2x"></i></div>
                            <h5 class="text-dark brand-font">Heart Notes (Middle)</h5>
                            <small class="text-muted d-block mb-3">Lasts 2-4 hours</small>
                            <?php foreach ($heart_notes as $n): ?>
                                <span class="badge bg-gold text-white fs-6 m-1 px-3 py-2"><?php echo htmlspecialchars($n['ingredient_name']); ?></span>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="p-3 rounded bg-white border h-100">
                            <div class="text-gold mb-2"><i class="fa-solid fa-tree fa-2x"></i></div>
                            <h5 class="text-dark brand-font">Base Notes (Dry-Down)</h5>
                            <small class="text-muted d-block mb-3">Lasts 8+ hours</small>
                            <?php foreach ($base_notes as $n): ?>
                                <span class="badge bg-gold text-white fs-6 m-1 px-3 py-2"><?php echo htmlspecialchars($n['ingredient_name']); ?></span>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
