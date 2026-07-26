<?php 
// dashboard.php - Clean Shopping Dashboard
include 'db.php';
if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (!isset($_SESSION['cart'])) { $_SESSION['cart'] = []; }

// Handle Cart Addition
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $product_id = intval($_POST['product_id']);
    $prod_chk = mysqli_query($conn, "SELECT * FROM products WHERE id = $product_id");
    if ($prod_chk && mysqli_num_rows($prod_chk) > 0) {
        $prod_data = mysqli_fetch_assoc($prod_chk);
        if (isset($_SESSION['cart'][$product_id])) {
            $_SESSION['cart'][$product_id]['qty'] += 1;
        } else {
            $_SESSION['cart'][$product_id] = [
                'name' => $prod_data['name'],
                'price' => $prod_data['price'],
                'image_url' => $prod_data['image_url'],
                'qty' => 1
            ];
        }
    }
    header("Location: dashboard.php");
    exit();
}

$search = isset($_GET['search']) ? trim($_GET['search']) : ''; 
$category_filter = isset($_GET['category']) ? $_GET['category'] : 'All'; 
$price_filter = isset($_GET['price']) ? $_GET['price'] : 'Any'; 

// Check if products table exists
$chk_tbl = mysqli_query($conn, "SHOW TABLES LIKE 'products'");
if (!$chk_tbl || mysqli_num_rows($chk_tbl) == 0) {
    mysqli_query($conn, "CREATE TABLE products (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        category VARCHAR(100) DEFAULT 'Unisex',
        price DECIMAL(10,2) NOT NULL,
        description TEXT,
        image_url VARCHAR(255) DEFAULT 'https://images.unsplash.com/photo-1594035910387-fea47794261f?w=600',
        availability VARCHAR(50) DEFAULT 'Ready'
    )");
    mysqli_query($conn, "INSERT INTO products (name, category, price, description) VALUES
        ('Creed Aventus', 'Men', 28500.00, 'Luxury royal perfume for men'),
        ('Tom Ford Black Orchid', 'Unisex', 15900.00, 'Sensual dark orchid fragrance'),
        ('Dior Sauvage Elixir', 'Men', 14200.00, 'Concentrated spicy fragrance'),
        ('Lattafa Khamrah', 'Unisex', 2999.00, 'Warm oriental spicy gourmet scent')");
}

$query = "SELECT * FROM products WHERE 1=1"; 

if(!empty($search)) { 
    $safe_search = mysqli_real_escape_string($conn, $search); 
    $query .= " AND (name LIKE '%$safe_search%' OR description LIKE '%$safe_search%' OR category LIKE '%$safe_search%')"; 
} 

if ($category_filter !== 'All') { 
    $safe_cat = mysqli_real_escape_string($conn, $category_filter); 
    $query .= " AND LOWER(category) = LOWER('$safe_cat')"; 
} 

if ($price_filter !== 'Any') {
    if ($price_filter == '500_1000') $query .= " AND price BETWEEN 500 AND 1000";
    elseif ($price_filter == '1000_1500') $query .= " AND price BETWEEN 1000 AND 1500";
    elseif ($price_filter == '1500_5000') $query .= " AND price BETWEEN 1500 AND 5000";
    elseif ($price_filter == '5000_50000') $query .= " AND price BETWEEN 5000 AND 50000";
}

$result = mysqli_query($conn, $query); 
$page_title = "Shop Dashboard";
require_once __DIR__ . '/includes/header.php';
?> 

<div class="container py-4">
    <div class="row g-4">
        <!-- Sidebar Filter -->
        <div class="col-lg-3">
            <div class="card p-3 shadow-sm border-0 bg-light">
                <h5 class="brand-font text-dark mb-3"><i class="fa-solid fa-sliders text-gold me-2"></i> Filter Products</h5>
                <form action="dashboard.php" method="GET" id="filterForm">
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Search</label>
                        <input type="text" name="search" class="form-control form-control-sm" placeholder="Search..." value="<?php echo htmlspecialchars($search); ?>">
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold">Category</label>
                        <select name="category" class="form-select form-select-sm" onchange="this.form.submit()">
                            <option value="All" <?php if($category_filter == 'All') echo 'selected'; ?>>All Categories</option>
                            <option value="Men" <?php if($category_filter == 'Men') echo 'selected'; ?>>Men Perfumes</option>
                            <option value="Women" <?php if($category_filter == 'Women') echo 'selected'; ?>>Women Perfumes</option>
                            <option value="Unisex" <?php if($category_filter == 'Unisex') echo 'selected'; ?>>Unisex Perfumes</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold">Price Range</label>
                        <select name="price" class="form-select form-select-sm" onchange="this.form.submit()">
                            <option value="Any" <?php if($price_filter == 'Any') echo 'selected'; ?>>Any Price</option>
                            <option value="500_1000" <?php if($price_filter == '500_1000') echo 'selected'; ?>>₹500 - ₹1,000</option>
                            <option value="1000_1500" <?php if($price_filter == '1000_1500') echo 'selected'; ?>>₹1,000 - ₹1,500</option>
                            <option value="1500_5000" <?php if($price_filter == '1500_5000') echo 'selected'; ?>>₹1,500 - ₹5,000</option>
                            <option value="5000_50000" <?php if($price_filter == '5000_50000') echo 'selected'; ?>>₹5,000+</option>
                        </select>
                    </div>

                    <button type="submit" class="btn btn-gold btn-sm w-100 mb-2">Apply Filters</button>
                    <a href="dashboard.php" class="btn btn-outline-secondary btn-sm w-100">Clear Filters</a>
                </form>
            </div>
        </div>

        <!-- Product Grid -->
        <div class="col-lg-9">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="brand-font text-dark mb-0">All Products</h4>
                <a href="add_product.php" class="btn btn-gold btn-sm"><i class="fa-solid fa-plus me-1"></i> Add Product</a>
            </div>

            <div class="row g-3">
                <?php if ($result && mysqli_num_rows($result) > 0): ?>
                    <?php while($row = mysqli_fetch_assoc($result)): ?>
                        <div class="col-md-4 col-6">
                            <div class="card card-custom h-100 p-3 text-center">
                                <img src="<?php echo htmlspecialchars($row['image_url'] ?: 'https://images.unsplash.com/photo-1594035910387-fea47794261f?w=600'); ?>" class="card-img-top rounded mb-2" style="height: 160px; object-fit: cover;">
                                <h6 class="fw-bold text-dark mb-1"><?php echo htmlspecialchars($row['name']); ?></h6>
                                <small class="text-muted d-block mb-1"><?php echo htmlspecialchars($row['category']); ?></small>
                                <div class="text-gold fw-bold fs-5 mb-2">₹<?php echo number_format($row['price'], 2); ?></div>

                                <form action="dashboard.php" method="POST" class="mt-auto">
                                    <input type="hidden" name="product_id" value="<?php echo $row['id']; ?>">
                                    <button type="submit" name="action" value="add" class="btn btn-gold btn-sm w-100"><i class="fa-solid fa-cart-plus me-1"></i> Add to Cart</button>
                                </form>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="col-12 text-center py-5">
                        <i class="fa-solid fa-spray-can text-gold fa-3x mb-3"></i>
                        <h5>No Perfumes Found</h5>
                        <p class="text-muted">Try resetting your search or filter options.</p>
                        <a href="dashboard.php" class="btn btn-gold btn-sm">Reset Filters</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
