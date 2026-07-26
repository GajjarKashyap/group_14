<?php
// fragrances.php - Catalog & Search Filtering Page (Clean White Theme)
require_once __DIR__ . '/config/db.php';
$pdo = getPDO();

$q = isset($_GET['q']) ? trim($_GET['q']) : '';
$collection_id = isset($_GET['collection']) ? (int)$_GET['collection'] : 0;
$house_id = isset($_GET['house']) ? (int)$_GET['house'] : 0;
$audience = isset($_GET['audience']) ? trim($_GET['audience']) : '';
$family_id = isset($_GET['family']) ? (int)$_GET['family'] : 0;
$sort = isset($_GET['sort']) ? trim($_GET['sort']) : 'newest';

$where = ["f.record_state = 'Published'"];
$params = [];

if (!empty($q)) {
    $where[] = "(f.fragrance_name LIKE :q OR f.fragrance_code LIKE :q OR f.summary LIKE :q OR h.house_name LIKE :q)";
    $params['q'] = "%$q%";
}
if ($collection_id > 0) {
    $where[] = "f.collection_id = :collection_id";
    $params['collection_id'] = $collection_id;
}
if ($house_id > 0) {
    $where[] = "f.house_id = :house_id";
    $params['house_id'] = $house_id;
}
if (!empty($audience) && in_array($audience, ['Men', 'Women', 'Unisex'])) {
    $where[] = "f.audience = :audience";
    $params['audience'] = $audience;
}
if ($family_id > 0) {
    $where[] = "f.family_id = :family_id";
    $params['family_id'] = $family_id;
}

$where_clause = implode(" AND ", $where);
$sort_order = "f.fragrance_id DESC";
if ($sort === 'price_asc') $sort_order = "f.offer_price ASC";
if ($sort === 'price_desc') $sort_order = "f.offer_price DESC";
if ($sort === 'name_asc') $sort_order = "f.fragrance_name ASC";

$sql = "SELECT f.*, h.house_name, fam.family_name, 
    (SELECT remote_image_address FROM fragrance_media WHERE fragrance_id = f.fragrance_id ORDER BY featured_image DESC LIMIT 1) AS image_url 
    FROM fragrances f 
    JOIN perfume_houses h ON f.house_id = h.house_id 
    LEFT JOIN fragrance_families fam ON f.family_id = fam.family_id 
    WHERE {$where_clause} 
    ORDER BY {$sort_order}";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$fragrances = $stmt->fetchAll();

$houses = $pdo->query("SELECT * FROM perfume_houses ORDER BY house_name ASC")->fetchAll();
$families = $pdo->query("SELECT * FROM fragrance_families ORDER BY family_name ASC")->fetchAll();

$page_title = "Fragrance Catalog";
require_once __DIR__ . '/includes/header.php';
?>

<div class="container py-5">
    <div class="row g-4">
        <!-- Sidebar Filters -->
        <div class="col-lg-3">
            <div class="card p-4 shadow-sm border-0 bg-light rounded-3">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="text-dark mb-0 brand-font"><i class="fa-solid fa-sliders text-gold me-2"></i> Filter Perfumes</h5>
                    <a href="fragrances.php" class="text-muted small text-decoration-none">Reset All</a>
                </div>

                <form action="fragrances.php" method="GET">
                    <div class="mb-3">
                        <label class="form-label text-muted small fw-bold">Search</label>
                        <div class="input-group">
                            <input type="text" name="q" class="form-control form-control-sm" placeholder="Name, Note..." value="<?php echo htmlspecialchars($q); ?>">
                            <button type="submit" class="btn btn-gold btn-sm"><i class="fa-solid fa-magnifying-glass"></i></button>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label text-muted small fw-bold">Target Audience</label>
                        <select name="audience" class="form-select form-select-sm" onchange="this.form.submit()">
                            <option value="">All Audiences</option>
                            <option value="Men" <?php if($audience === 'Men') echo 'selected'; ?>>Men's Fragrances</option>
                            <option value="Women" <?php if($audience === 'Women') echo 'selected'; ?>>Women's Fragrances</option>
                            <option value="Unisex" <?php if($audience === 'Unisex') echo 'selected'; ?>>Unisex Scents</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label text-muted small fw-bold">Perfume House</label>
                        <select name="house" class="form-select form-select-sm" onchange="this.form.submit()">
                            <option value="0">All Perfume Houses</option>
                            <?php foreach ($houses as $h): ?>
                                <option value="<?php echo $h['house_id']; ?>" <?php if($house_id == $h['house_id']) echo 'selected'; ?>>
                                    <?php echo htmlspecialchars($h['house_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label text-muted small fw-bold">Scent Family</label>
                        <select name="family" class="form-select form-select-sm" onchange="this.form.submit()">
                            <option value="0">All Families</option>
                            <?php foreach ($families as $f): ?>
                                <option value="<?php echo $f['family_id']; ?>" <?php if($family_id == $f['family_id']) echo 'selected'; ?>>
                                    <?php echo htmlspecialchars($f['family_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <button type="submit" class="btn btn-gold btn-sm w-100 mt-2">Apply Filters</button>
                </form>
            </div>
        </div>

        <!-- Product Grid -->
        <div class="col-lg-9">
            <div class="d-flex justify-content-between align-items-center mb-4 p-3 bg-light rounded border">
                <div class="text-muted small">
                    Showing <strong class="text-gold"><?php echo count($fragrances); ?></strong> perfume products found
                </div>
                <div class="d-flex align-items-center gap-2">
                    <label class="text-muted small text-nowrap">Sort By:</label>
                    <select class="form-select form-select-sm" onchange="location = this.value;">
                        <option value="<?php echo 'fragrances.php?' . http_build_query(array_merge($_GET, ['sort' => 'newest'])); ?>" <?php if($sort === 'newest') echo 'selected'; ?>>Newest Arrivals</option>
                        <option value="<?php echo 'fragrances.php?' . http_build_query(array_merge($_GET, ['sort' => 'price_asc'])); ?>" <?php if($sort === 'price_asc') echo 'selected'; ?>>Price: Low to High</option>
                        <option value="<?php echo 'fragrances.php?' . http_build_query(array_merge($_GET, ['sort' => 'price_desc'])); ?>" <?php if($sort === 'price_desc') echo 'selected'; ?>>Price: High to Low</option>
                        <option value="<?php echo 'fragrances.php?' . http_build_query(array_merge($_GET, ['sort' => 'name_asc'])); ?>" <?php if($sort === 'name_asc') echo 'selected'; ?>>Name A to Z</option>
                    </select>
                </div>
            </div>

            <?php if (!empty($fragrances)): ?>
                <div class="row g-4">
                    <?php foreach ($fragrances as $item): ?>
                        <div class="col-md-4 col-6">
                            <div class="card card-custom h-100 p-3 d-flex flex-column">
                                <img src="<?php echo htmlspecialchars($item['image_url'] ?: 'https://images.unsplash.com/photo-1594035910387-fea47794261f?w=600'); ?>" class="card-img-top rounded mb-2" style="height: 180px; object-fit: cover;">
                                <small class="text-gold text-uppercase fw-bold mb-1"><?php echo htmlspecialchars($item['house_name']); ?></small>
                                <h5 class="text-dark brand-font mb-2"><?php echo htmlspecialchars($item['fragrance_name']); ?></h5>
                                <p class="text-muted small mb-3 flex-grow-1" style="height: 38px; overflow: hidden;"><?php echo htmlspecialchars($item['summary']); ?></p>

                                <div class="d-flex align-items-center justify-content-between mt-auto pt-2 border-top">
                                    <span class="fs-5 fw-bold text-gold">₹<?php echo number_format($item['offer_price'], 2); ?></span>
                                    <a href="fragrance.php?code=<?php echo urlencode($item['fragrance_code']); ?>" class="btn btn-outline-gold btn-sm"><i class="fa-solid fa-eye me-1"></i> View</a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="text-center py-5 card shadow-sm border-0 bg-white rounded-3">
                    <i class="fa-solid fa-spray-can text-gold fa-3x mb-3"></i>
                    <h4 class="text-dark">No Fragrances Found</h4>
                    <p class="text-muted">No perfume matches your filter criteria.</p>
                    <a href="fragrances.php" class="btn btn-gold btn-sm">Reset Filters</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
