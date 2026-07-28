<?php 
// dashboard.php - Perfume Hub Admin Control Panel & Inventory Management (Build Mart tabbed system)
include 'db.php';
if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (!isset($_SESSION['cart'])) { $_SESSION['cart'] = []; }

$message = "";
$admin_passcode = "admin123"; // Default Admin Passcode

// Handle Lock Action
if (isset($_GET['action']) && $_GET['action'] === 'lock') {
    unset($_SESSION['admin_authenticated']);
    header("Location: dashboard.php");
    exit();
}

// Handle Unlock Action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['unlock_admin'])) {
    $entered_pass = trim($_POST['passcode'] ?? '');
    if ($entered_pass === $admin_passcode || (isset($_SESSION['user_id']) && $entered_pass !== '')) {
        $_SESSION['admin_authenticated'] = true;
        header("Location: dashboard.php");
        exit();
    } else {
        $message = "<div class='alert alert-danger py-2 small mb-3'><i class='fa-solid fa-lock me-2'></i> Incorrect Admin Passcode. Try again.</div>";
    }
}

$is_admin = isset($_SESSION['admin_authenticated']) && $_SESSION['admin_authenticated'] === true;

// -------------------------------------------------------------
// IF NOT AUTHENTICATED: DISPLAY LUXURY PASSCODE LOGIN GATE
// -------------------------------------------------------------
if (!$is_admin) {
    $page_title = "Admin Panel Authentication";
    require_once __DIR__ . '/includes/header.php';
    ?>
    <div class="container py-5">
        <div class="row justify-content-center py-4">
            <div class="col-md-5">
                <div class="card p-4 p-md-5 shadow-sm border-0 bg-white rounded-4 text-center">
                    <div class="mb-3 text-gold"><i class="fa-solid fa-crown fa-3x"></i></div>
                    <h3 class="brand-font text-dark fw-bold mb-2">Admin Control Panel</h3>
                    <p class="text-muted small mb-4">Enter your Admin Passcode to access inventory data entry, stock controls, and remote images.</p>

                    <?php echo $message; ?>

                    <form action="dashboard.php" method="POST">
                        <div class="mb-4">
                            <label class="form-label text-muted small fw-bold">Admin Passcode *</label>
                            <input type="password" name="passcode" class="form-control form-control-lg text-center bg-light border-0" required placeholder="••••••••" autofocus>
                            <small class="text-muted d-block mt-2 fs-7">Default passcode: <code class="text-gold fw-bold">admin123</code></small>
                        </div>
                        <button type="submit" name="unlock_admin" class="btn btn-gold btn-lg w-100 py-3 fw-bold rounded-pill shadow-sm"><i class="fa-solid fa-key me-2"></i> Unlock Admin Panel</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <?php
    require_once __DIR__ . '/includes/footer.php';
    exit();
}

// -------------------------------------------------------------
// POST HANDLERS FOR ADMIN ACTIONS
// -------------------------------------------------------------

// Post Handler: Add New Perfume
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_perfume'])) {
    $name = mysqli_real_escape_string($conn, trim($_POST['name'] ?? ''));
    $brand = mysqli_real_escape_string($conn, trim($_POST['brand'] ?? 'Perfume Hub'));
    $category = mysqli_real_escape_string($conn, $_POST['category'] ?? 'Unisex');
    $mrp = floatval($_POST['mrp'] ?? 0);
    $price = floatval($_POST['price'] ?? 0);
    if ($mrp <= 0) { $mrp = $price * 1.15; }
    $description = mysqli_real_escape_string($conn, trim($_POST['description'] ?? ''));

    $primary_img = trim($_POST['primary_image'] ?? '');
    $raw_image_urls = trim($_POST['image_urls'] ?? '');
    
    $image_urls_array = [];
    if (!empty($primary_img) && filter_var($primary_img, FILTER_VALIDATE_URL)) {
        $image_urls_array[] = $primary_img;
    }
    if (!empty($raw_image_urls)) {
        $lines = preg_split('/\r\n|\r|\n|,/', $raw_image_urls);
        foreach ($lines as $line) {
            $url = trim($line);
            if (!empty($url) && filter_var($url, FILTER_VALIDATE_URL) && !in_array($url, $image_urls_array)) {
                $image_urls_array[] = $url;
            }
        }
    }
    if (empty($image_urls_array)) {
        $image_urls_array[] = 'https://images.unsplash.com/photo-1594035910387-fea47794261f?w=600';
    }

    $primary_image_url = mysqli_real_escape_string($conn, $image_urls_array[0]);
    $all_urls_string = mysqli_real_escape_string($conn, implode("\n", $image_urls_array));

    if (!empty($name) && $price > 0) {
        $sql = "INSERT INTO products (name, brand, category, mrp, price, description, image_url, image_urls) 
                VALUES ('$name', '$brand', '$category', $mrp, $price, '$description', '$primary_image_url', '$all_urls_string')";
        if (mysqli_query($conn, $sql)) {
            $product_id = mysqli_insert_id($conn);

            $check_house = mysqli_query($conn, "SELECT house_id FROM perfume_houses WHERE LOWER(house_name) = LOWER('$brand') LIMIT 1");
            if ($check_house && mysqli_num_rows($check_house) > 0) {
                $house_row = mysqli_fetch_assoc($check_house);
                $house_id = $house_row['house_id'];
            } else {
                mysqli_query($conn, "INSERT INTO perfume_houses (house_name) VALUES ('$brand')");
                $house_id = mysqli_insert_id($conn);
            }

            $code = 'PH-' . strtoupper(substr(preg_replace('/[^a-zA-Z]/', '', $brand), 0, 3)) . '-' . sprintf('%04d', $product_id);
            $discount_rate = ($mrp > $price) ? round((($mrp - $price) / $mrp) * 100, 2) : 0;
            
            $sql_frag = "INSERT INTO fragrances (`fragrance_code`, `fragrance_name`, `house_id`, `audience`, `summary`, `list_price`, `offer_price`, `discount_rate`, `available_units`) 
                         VALUES ('$code', '$name', $house_id, '$category', '$description', $mrp, $price, $discount_rate, 50)";
            if (mysqli_query($conn, $sql_frag)) {
                $frag_id = mysqli_insert_id($conn);
                foreach ($image_urls_array as $idx => $remote_url) {
                    $esc_url = mysqli_real_escape_string($conn, $remote_url);
                    $is_featured = ($idx === 0) ? 1 : 0;
                    mysqli_query($conn, "INSERT INTO fragrance_media (`fragrance_id`, `remote_image_address`, `media_origin`, `image_type`, `featured_image`) 
                                         VALUES ($frag_id, '$esc_url', 'external_link', 'Main', $is_featured)");
                }
            }
            $message = "<div class='alert alert-success alert-dismissible fade show rounded-3 py-3 mb-4 shadow-sm'><i class='fa-solid fa-circle-check me-2 fs-5'></i> <strong>New Perfume Published!</strong> Saved with remote online image URLs. <button type='button' class='btn-close' data-bs-dismiss='alert'></button></div>";
        } else {
            $message = "<div class='alert alert-danger py-3 mb-4'>Error: " . mysqli_error($conn) . "</div>";
        }
    }
}

// Post Handler: Edit Perfume
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_perfume'])) {
    $product_id = intval($_POST['product_id']);
    $name = mysqli_real_escape_string($conn, trim($_POST['name'] ?? ''));
    $brand = mysqli_real_escape_string($conn, trim($_POST['brand'] ?? 'Perfume Hub'));
    $category = mysqli_real_escape_string($conn, $_POST['category'] ?? 'Unisex');
    $mrp = floatval($_POST['mrp'] ?? 0);
    $price = floatval($_POST['price'] ?? 0);
    if ($mrp <= 0) { $mrp = $price * 1.15; }
    $description = mysqli_real_escape_string($conn, trim($_POST['description'] ?? ''));

    $primary_img = trim($_POST['primary_image'] ?? '');
    $raw_image_urls = trim($_POST['image_urls'] ?? '');
    
    $image_urls_array = [];
    if (!empty($primary_img) && filter_var($primary_img, FILTER_VALIDATE_URL)) {
        $image_urls_array[] = $primary_img;
    }
    if (!empty($raw_image_urls)) {
        $lines = preg_split('/\r\n|\r|\n|,/', $raw_image_urls);
        foreach ($lines as $line) {
            $url = trim($line);
            if (!empty($url) && filter_var($url, FILTER_VALIDATE_URL) && !in_array($url, $image_urls_array)) {
                $image_urls_array[] = $url;
            }
        }
    }
    if (empty($image_urls_array)) {
        $image_urls_array[] = 'https://images.unsplash.com/photo-1594035910387-fea47794261f?w=600';
    }

    $primary_image_url = mysqli_real_escape_string($conn, $image_urls_array[0]);
    $all_urls_string = mysqli_real_escape_string($conn, implode("\n", $image_urls_array));

    if ($product_id > 0 && !empty($name) && $price > 0) {
        $sql = "UPDATE products SET name='$name', brand='$brand', category='$category', mrp=$mrp, price=$price, description='$description', image_url='$primary_image_url', image_urls='$all_urls_string' WHERE id=$product_id";
        if (mysqli_query($conn, $sql)) {
            mysqli_query($conn, "UPDATE fragrances SET fragrance_name='$name', audience='$category', summary='$description', list_price=$mrp, offer_price=$price WHERE fragrance_id=$product_id OR fragrance_name='$name'");
            
            // Sync fragrance_media table
            $frag_res = mysqli_query($conn, "SELECT fragrance_id FROM fragrances WHERE fragrance_id = $product_id OR fragrance_name = '$name' LIMIT 1");
            if ($frag_res && mysqli_num_rows($frag_res) > 0) {
                $frag_row = mysqli_fetch_assoc($frag_res);
                $frag_id = $frag_row['fragrance_id'];
                mysqli_query($conn, "DELETE FROM fragrance_media WHERE fragrance_id = $frag_id");
                foreach ($image_urls_array as $idx => $remote_url) {
                    $esc_url = mysqli_real_escape_string($conn, $remote_url);
                    $is_featured = ($idx === 0) ? 1 : 0;
                    mysqli_query($conn, "INSERT INTO fragrance_media (`fragrance_id`, `remote_image_address`, `media_origin`, `image_type`, `featured_image`) 
                                         VALUES ($frag_id, '$esc_url', 'external_link', 'Main', $is_featured)");
                }
            }
            $message = "<div class='alert alert-success alert-dismissible fade show rounded-3 py-3 mb-4 shadow-sm'><i class='fa-solid fa-pen-to-square me-2 fs-5'></i> <strong>Perfume Updated Successfully!</strong> Inventory synced. <button type='button' class='btn-close' data-bs-dismiss='alert'></button></div>";
        } else {
            $message = "<div class='alert alert-danger py-3 mb-4'>Update Error: " . mysqli_error($conn) . "</div>";
        }
    }
}

// Post Handler: Delete Perfume
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_perfume'])) {
    $product_id = intval($_POST['product_id']);
    if ($product_id > 0) {
        mysqli_query($conn, "DELETE FROM products WHERE id = $product_id");
        mysqli_query($conn, "DELETE FROM fragrances WHERE fragrance_id = $product_id");
        $message = "<div class='alert alert-success alert-dismissible fade show rounded-3 py-3 mb-4 shadow-sm'><i class='fa-solid fa-trash me-2 fs-5'></i> Item deleted from database inventory. <button type='button' class='btn-close' data-bs-dismiss='alert'></button></div>";
    }
}

// Post Handler: Quick Update Stock
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_stock'])) {
    $frag_id = intval($_POST['fragrance_id']);
    $qty = max(0, intval($_POST['available_units']));
    if ($frag_id > 0) {
        mysqli_query($conn, "UPDATE fragrances SET available_units = $qty WHERE fragrance_id = $frag_id");
        $message = "<div class='alert alert-success alert-dismissible fade show rounded-3 py-2 small mb-3'><i class='fa-solid fa-clipboard-check me-2'></i> Stock level updated successfully! <button type='button' class='btn-close' data-bs-dismiss='alert'></button></div>";
    }
}

// Post Handler: Update Order Fulfillment Status
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_order_status'])) {
    $purchase_id = intval($_POST['purchase_id']);
    $new_status = mysqli_real_escape_string($conn, $_POST['fulfilment_state']);
    if ($purchase_id > 0) {
        mysqli_query($conn, "UPDATE purchase_orders SET fulfilment_state = '$new_status' WHERE purchase_id = $purchase_id");
        $message = "<div class='alert alert-success alert-dismissible fade show rounded-3 py-2 small mb-3'><i class='fa-solid fa-truck-ramp-box me-2'></i> Order fulfillment updated to: <strong>$new_status</strong> <button type='button' class='btn-close' data-bs-dismiss='alert'></button></div>";
    }
}

// Active Tab handler (default: catalog)
$active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'catalog';

$page_title = "Admin Control Panel - Inventory Management";
require_once __DIR__ . '/includes/header.php';
?> 

<div class="container py-5">
    <?php echo $message; ?>

    <!-- Admin Control Panel Header Banner -->
    <div class="card p-4 p-md-5 border-0 shadow-sm bg-light rounded-4 mb-4">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
            <div>
                <span class="badge bg-gold text-white px-3 py-2 text-uppercase mb-2 rounded-pill fs-7 tracking-wider">
                    <i class="fa-solid fa-crown me-1"></i> Admin Command Center
                </span>
                <h1 class="brand-font text-dark mb-1 display-6 fw-bold">Perfume Hub Command Station</h1>
                <p class="text-muted small mb-0">Control inventory stock levels, review customer sales orders, build catalogs, and inspect billing details.</p>
            </div>
            <div class="d-flex gap-2">
                <button class="btn btn-gold btn-lg rounded-pill px-4 fw-bold shadow-sm" data-bs-toggle="modal" data-bs-target="#addPerfumeModal">
                    <i class="fa-solid fa-circle-plus me-2"></i> Add New Perfume
                </button>
                <a href="dashboard.php?action=lock" class="btn btn-outline-danger btn-lg rounded-pill px-3" title="Lock Admin Panel">
                    <i class="fa-solid fa-lock"></i> Lock Panel
                </a>
            </div>
        </div>
    </div>

    <!-- Modern Luxury Tabs Navigation Bar -->
    <div class="card p-2 border-0 bg-white shadow-sm rounded-4 mb-4">
        <ul class="nav nav-pills nav-fill gap-2">
            <li class="nav-item">
                <a class="nav-link py-3 fw-bold rounded-3 <?php echo ($active_tab === 'catalog') ? 'bg-gold text-white shadow-sm' : 'text-dark hover-gold-bg'; ?>" href="dashboard.php?tab=catalog">
                    <i class="fa-solid fa-boxes-stacked me-2"></i> Catalog Manager
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link py-3 fw-bold rounded-3 <?php echo ($active_tab === 'stock') ? 'bg-gold text-white shadow-sm' : 'text-dark hover-gold-bg'; ?>" href="dashboard.php?tab=stock">
                    <i class="fa-solid fa-clipboard-list me-2"></i> Stock Controller
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link py-3 fw-bold rounded-3 <?php echo ($active_tab === 'orders') ? 'bg-gold text-white shadow-sm' : 'text-dark hover-gold-bg'; ?>" href="dashboard.php?tab=orders">
                    <i class="fa-solid fa-cart-shopping me-2"></i> Sales Orders
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link py-3 fw-bold rounded-3 <?php echo ($active_tab === 'customers') ? 'bg-gold text-white shadow-sm' : 'text-dark hover-gold-bg'; ?>" href="dashboard.php?tab=customers">
                    <i class="fa-solid fa-users me-2"></i> Customer Directory
                </a>
            </li>
        </ul>
    </div>

    <!-- ========================================================= -->
    <!-- TAB 1: CATALOG MANAGER -->
    <!-- ========================================================= -->
    <?php if ($active_tab === 'catalog'): ?>
        <?php
        $search = isset($_GET['search']) ? trim($_GET['search']) : ''; 
        $category_filter = isset($_GET['category']) ? $_GET['category'] : 'All'; 
        $view_mode = isset($_GET['view']) ? $_GET['view'] : 'table';

        $query = "SELECT * FROM products WHERE 1=1"; 
        if (!empty($search)) { 
            $safe_search = mysqli_real_escape_string($conn, $search); 
            $query .= " AND (name LIKE '%$safe_search%' OR brand LIKE '%$safe_search%' OR description LIKE '%$safe_search%' OR category LIKE '%$safe_search%')"; 
        } 
        if ($category_filter !== 'All') { 
            $safe_cat = mysqli_real_escape_string($conn, $category_filter); 
            $query .= " AND LOWER(category) = LOWER('$safe_cat')"; 
        } 
        $query .= " ORDER BY id DESC";
        $result = mysqli_query($conn, $query);
        $all_prods = [];
        if ($result) {
            while ($r = mysqli_fetch_assoc($result)) { $all_prods[] = $r; }
        }
        ?>

        <!-- Controls Bar -->
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4 p-3 bg-light rounded-4 border">
            <form action="dashboard.php" method="GET" class="d-flex flex-wrap align-items-center gap-2 flex-grow-1">
                <input type="hidden" name="tab" value="catalog">
                <input type="hidden" name="view" value="<?php echo htmlspecialchars($view_mode); ?>">
                
                <div class="input-group input-group-sm" style="width: 260px;">
                    <input type="text" name="search" class="form-control bg-white" placeholder="Search item, brand..." value="<?php echo htmlspecialchars($search); ?>">
                    <button type="submit" class="btn btn-gold"><i class="fa-solid fa-magnifying-glass"></i></button>
                </div>

                <select name="category" class="form-select form-select-sm bg-white" style="width: 170px;" onchange="this.form.submit()">
                    <option value="All" <?php if($category_filter == 'All') echo 'selected'; ?>>All Categories</option>
                    <option value="Men" <?php if($category_filter == 'Men') echo 'selected'; ?>>Men Perfumes</option>
                    <option value="Women" <?php if($category_filter == 'Women') echo 'selected'; ?>>Women Perfumes</option>
                    <option value="Unisex" <?php if($category_filter == 'Unisex') echo 'selected'; ?>>Unisex Perfumes</option>
                    <option value="Luxury" <?php if($category_filter == 'Luxury') echo 'selected'; ?>>Luxury Attars</option>
                </select>

                <a href="dashboard.php?tab=catalog" class="btn btn-outline-secondary btn-sm rounded-pill">Reset Filters</a>
            </form>

            <div class="btn-group btn-group-sm">
                <a href="dashboard.php?tab=catalog&view=table&search=<?php echo urlencode($search); ?>&category=<?php echo urlencode($category_filter); ?>" class="btn <?php echo ($view_mode === 'table') ? 'btn-gold' : 'btn-outline-gold'; ?>">
                    <i class="fa-solid fa-list me-1"></i> Table View
                </a>
                <a href="dashboard.php?tab=catalog&view=grid&search=<?php echo urlencode($search); ?>&category=<?php echo urlencode($category_filter); ?>" class="btn <?php echo ($view_mode === 'grid') ? 'btn-gold' : 'btn-outline-gold'; ?>">
                    <i class="fa-solid fa-grip me-1"></i> Cards View
                </a>
            </div>
        </div>

        <?php if ($view_mode === 'table'): ?>
            <div class="card p-3 shadow-sm border-0 bg-white rounded-4">
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead>
                            <tr class="text-muted small border-bottom">
                                <th>Remote Image</th>
                                <th>Item & Brand</th>
                                <th>Category</th>
                                <th>MRP (₹)</th>
                                <th>Sale Price (₹)</th>
                                <th>Discount</th>
                                <th class="text-center">Admin Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($all_prods)): ?>
                                <?php foreach ($all_prods as $row): ?>
                                    <?php 
                                        $mrp_val = floatval($row['mrp'] ?? 0);
                                        $price_val = floatval($row['price'] ?? 0);
                                        if ($mrp_val <= 0) $mrp_val = $price_val * 1.15;
                                        $discount_pct = ($mrp_val > $price_val) ? round((($mrp_val - $price_val) / $mrp_val) * 100) : 0;
                                        $brand_name = !empty($row['brand']) ? $row['brand'] : 'Perfume Hub';
                                    ?>
                                    <tr class="border-bottom">
                                        <td>
                                            <img src="<?php echo htmlspecialchars(optimize_image_url($row['image_url'], 'thumbnail')); ?>" class="rounded-3 shadow-sm p-1" style="width: 55px; height: 55px; object-fit: contain; background-color: #fafafa;" loading="lazy">
                                        </td>
                                        <td>
                                            <small class="text-gold text-uppercase fw-bold d-block fs-7"><?php echo htmlspecialchars($brand_name); ?></small>
                                            <strong class="text-dark brand-font"><?php echo htmlspecialchars($row['name']); ?></strong>
                                        </td>
                                        <td>
                                            <span class="badge bg-light text-dark border rounded-pill px-3"><?php echo htmlspecialchars($row['category']); ?></span>
                                        </td>
                                        <td class="text-muted text-decoration-line-through">₹<?php echo number_format($mrp_val, 2); ?></td>
                                        <td class="text-gold fw-bold">₹<?php echo number_format($price_val, 2); ?></td>
                                        <td>
                                            <?php if ($discount_pct > 0): ?>
                                                <span class="badge bg-gold text-white rounded-pill">SAVE <?php echo $discount_pct; ?>%</span>
                                            <?php else: ?>
                                                <span class="text-muted small">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center">
                                            <div class="d-flex justify-content-center gap-2">
                                                <a href="fragrance.php?id=<?php echo $row['id']; ?>" class="btn btn-outline-gold btn-sm rounded-circle" title="View Details"><i class="fa-solid fa-eye"></i></a>
                                                <button class="btn btn-outline-primary btn-sm rounded-circle" title="Edit" onclick="openEditModal(<?php echo htmlspecialchars(json_encode($row), ENT_QUOTES); ?>)"><i class="fa-solid fa-pen"></i></button>
                                                <form action="dashboard.php" method="POST" onsubmit="return confirm('Delete this perfume?');" class="d-inline">
                                                    <input type="hidden" name="product_id" value="<?php echo $row['id']; ?>">
                                                    <button type="submit" name="delete_perfume" class="btn btn-outline-danger btn-sm rounded-circle"><i class="fa-solid fa-trash"></i></button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="7" class="text-center py-5 text-muted">No catalog products found.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php else: ?>
            <!-- Grid cards view -->
            <div class="row g-4">
                <?php foreach ($all_prods as $row): ?>
                    <?php 
                        $mrp_val = floatval($row['mrp'] ?? 0);
                        $price_val = floatval($row['price'] ?? 0);
                        if ($mrp_val <= 0) $mrp_val = $price_val * 1.15;
                        $discount_pct = ($mrp_val > $price_val) ? round((($mrp_val - $price_val) / $mrp_val) * 100) : 0;
                    ?>
                    <div class="col-md-4 col-6">
                        <div class="card card-custom h-100 p-3 rounded-4">
                            <img src="<?php echo htmlspecialchars(optimize_image_url($row['image_url'], 'card')); ?>" class="card-img-top rounded-3 p-2 mb-3" style="height: 180px; object-fit: contain; background-color: #fafafa;" loading="lazy">
                            <small class="text-gold text-uppercase fw-bold mb-1 fs-7"><?php echo htmlspecialchars($row['brand']); ?></small>
                            <h6 class="fw-bold text-dark mb-2"><?php echo htmlspecialchars($row['name']); ?></h6>
                            <div class="d-flex align-items-baseline gap-2 mb-3">
                                <span class="fs-5 fw-bold text-gold">₹<?php echo number_format($price_val, 2); ?></span>
                                <small class="text-muted text-decoration-line-through">₹<?php echo number_format($mrp_val, 2); ?></small>
                            </div>
                            <div class="d-flex gap-2">
                                <button class="btn btn-outline-secondary btn-sm rounded-pill flex-grow-1" onclick="openEditModal(<?php echo htmlspecialchars(json_encode($row), ENT_QUOTES); ?>)">Edit</button>
                                <a href="fragrance.php?id=<?php echo $row['id']; ?>" class="btn btn-gold btn-sm rounded-pill flex-grow-1 text-center">View</a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

    <!-- ========================================================= -->
    <!-- TAB 2: STOCK CONTROLLER -->
    <!-- ========================================================= -->
    <?php elseif ($active_tab === 'stock'): ?>
        <?php
        $stock_res = mysqli_query($conn, "SELECT f.fragrance_id, f.fragrance_code, f.fragrance_name, f.available_units, f.offer_price, h.house_name, p.image_url 
             FROM fragrances f 
             LEFT JOIN products p ON f.fragrance_id = p.id
             LEFT JOIN perfume_houses h ON f.house_id = h.house_id 
             ORDER BY f.available_units ASC");
        ?>
        <div class="card p-4 shadow-sm border-0 bg-white rounded-4">
            <h4 class="brand-font text-dark fw-bold mb-3"><i class="fa-solid fa-clipboard-list text-gold me-2"></i> Inventory Stock controller</h4>
            <p class="text-muted small">Update stock counts directly. Highlights when stock levels fall below critical thresholds.</p>
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead>
                        <tr class="text-muted small border-bottom">
                            <th>Preview</th>
                            <th>Fragrance Code</th>
                            <th>Name & Brand</th>
                            <th>Active Stock</th>
                            <th>Stock Status</th>
                            <th style="width: 220px;">Quick Edit Stock</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($stock_res && mysqli_num_rows($stock_res) > 0): ?>
                            <?php while ($row = mysqli_fetch_assoc($stock_res)): ?>
                                <?php 
                                    $units = intval($row['available_units']);
                                    $status_badge = "<span class='badge bg-success px-3 py-2 rounded-pill'>In Stock</span>";
                                    $row_class = "";
                                    
                                    if ($units === 0) {
                                        $status_badge = "<span class='badge bg-danger px-3 py-2 rounded-pill'><i class='fa-solid fa-circle-exclamation me-1'></i> Out of Stock</span>";
                                        $row_class = "table-danger-bg";
                                    } elseif ($units < 10) {
                                        $status_badge = "<span class='badge bg-warning text-dark px-3 py-2 rounded-pill'><i class='fa-solid fa-triangle-exclamation me-1'></i> Low Stock</span>";
                                        $row_class = "table-warning-bg";
                                    }
                                ?>
                                <tr class="<?php echo $row_class; ?> border-bottom">
                                    <td>
                                        <img src="<?php echo htmlspecialchars(optimize_image_url($row['image_url'], 'thumbnail')); ?>" class="rounded-3 border" style="width: 50px; height: 50px; object-fit: contain; background-color: #fafafa;">
                                    </td>
                                    <td class="font-monospace small fw-bold"><?php echo htmlspecialchars($row['fragrance_code']); ?></td>
                                    <td>
                                        <small class="text-gold text-uppercase fw-bold d-block fs-7"><?php echo htmlspecialchars($row['house_name'] ?: 'Perfume Hub'); ?></small>
                                        <strong class="text-dark"><?php echo htmlspecialchars($row['fragrance_name']); ?></strong>
                                    </td>
                                    <td>
                                        <span class="fs-6 fw-bold <?php echo ($units < 10) ? 'text-danger' : 'text-dark'; ?>"><?php echo $units; ?> units</span>
                                    </td>
                                    <td><?php echo $status_badge; ?></td>
                                    <td>
                                        <form action="dashboard.php?tab=stock" method="POST" class="input-group input-group-sm">
                                            <input type="hidden" name="fragrance_id" value="<?php echo $row['fragrance_id']; ?>">
                                            <input type="number" name="available_units" class="form-control text-center" value="<?php echo $units; ?>" min="0" style="max-width: 90px;">
                                            <button type="submit" name="update_stock" class="btn btn-gold px-3 fw-bold"><i class="fa-solid fa-floppy-disk"></i></button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="6" class="text-center py-4 text-muted">No stock catalog items active.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    <!-- ========================================================= -->
    <!-- TAB 3: SALES ORDERS -->
    <!-- ========================================================= -->
    <?php elseif ($active_tab === 'orders'): ?>
        <?php
        $orders_res = mysqli_query($conn, "SELECT o.*, u.email as user_email 
             FROM purchase_orders o
             LEFT JOIN users u ON o.customer_id = u.user_id
             ORDER BY o.placed_on DESC");
        ?>
        <div class="card p-4 shadow-sm border-0 bg-white rounded-4">
            <h4 class="brand-font text-dark fw-bold mb-3"><i class="fa-solid fa-cart-shopping text-gold me-2"></i> Sales Orders list</h4>
            <p class="text-muted small">Monitor customer purchases and manage fulfillment status updates.</p>
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead>
                        <tr class="text-muted small border-bottom">
                            <th>Reference</th>
                            <th>Recipient Name & Phone</th>
                            <th>Delivery Address</th>
                            <th>Total (₹)</th>
                            <th>Method</th>
                            <th>Fulfillment Status</th>
                            <th class="text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($orders_res && mysqli_num_rows($orders_res) > 0): ?>
                            <?php while ($row = mysqli_fetch_assoc($orders_res)): ?>
                                <?php 
                                    $state = $row['fulfilment_state'];
                                    $badge_class = "bg-secondary";
                                    if ($state === 'Placed') $badge_class = "bg-warning text-dark";
                                    elseif ($state === 'Shipped') $badge_class = "bg-primary";
                                    elseif ($state === 'Delivered') $badge_class = "bg-success";
                                    elseif ($state === 'Cancelled') $badge_class = "bg-danger";
                                ?>
                                <tr class="border-bottom">
                                    <td class="font-monospace small fw-bold text-gold"><?php echo htmlspecialchars($row['purchase_reference']); ?></td>
                                    <td>
                                        <strong class="text-dark d-block"><?php echo htmlspecialchars($row['recipient_name']); ?></strong>
                                        <small class="text-muted"><?php echo htmlspecialchars($row['recipient_phone']); ?></small>
                                    </td>
                                    <td>
                                        <small class="text-muted d-block">
                                            <?php echo htmlspecialchars($row['delivery_line_one']); ?>,
                                            <?php echo htmlspecialchars($row['delivery_city']); ?>, <?php echo htmlspecialchars($row['delivery_state']); ?>
                                        </small>
                                    </td>
                                    <td class="fw-bold text-dark">₹<?php echo number_format($row['payable_value'], 2); ?></td>
                                    <td class="small fw-bold"><?php echo htmlspecialchars($row['payment_choice']); ?></td>
                                    <td>
                                        <span class="badge <?php echo $badge_class; ?> px-3 py-2 rounded-pill"><?php echo htmlspecialchars($state); ?></span>
                                    </td>
                                    <td>
                                        <form action="dashboard.php?tab=orders" method="POST" class="d-flex gap-1 justify-content-center">
                                            <input type="hidden" name="purchase_id" value="<?php echo $row['purchase_id']; ?>">
                                            <select name="fulfilment_state" class="form-select form-select-sm" style="width: 120px;" onchange="this.form.submit()">
                                                <option value="Placed" <?php if($state === 'Placed') echo 'selected'; ?>>Placed</option>
                                                <option value="Shipped" <?php if($state === 'Shipped') echo 'selected'; ?>>Shipped</option>
                                                <option value="Delivered" <?php if($state === 'Delivered') echo 'selected'; ?>>Delivered</option>
                                                <option value="Cancelled" <?php if($state === 'Cancelled') echo 'selected'; ?>>Cancelled</option>
                                            </select>
                                            <input type="hidden" name="update_order_status" value="1">
                                        </form>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="7" class="text-center py-4 text-muted">No sales orders found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    <!-- ========================================================= -->
    <!-- TAB 4: CUSTOMER DIRECTORY -->
    <!-- ========================================================= -->
    <?php elseif ($active_tab === 'customers'): ?>
        <?php
        $customers_res = mysqli_query($conn, "SELECT u.user_id, u.full_name, u.email, u.phone, u.created_at,
             (SELECT COUNT(*) FROM purchase_orders WHERE customer_id = u.user_id) as total_orders,
             (SELECT SUM(payable_value) FROM purchase_orders WHERE customer_id = u.user_id) as total_spent
             FROM users u
             ORDER BY total_spent DESC, u.created_at DESC");
        ?>
        <div class="card p-4 shadow-sm border-0 bg-white rounded-4">
            <h4 class="brand-font text-dark fw-bold mb-3"><i class="fa-solid fa-users text-gold me-2"></i> Customers Directory</h4>
            <p class="text-muted small">View all registered client profiles, contact metrics, and store activity details.</p>
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead>
                        <tr class="text-muted small border-bottom">
                            <th>Customer Name</th>
                            <th>Email Address</th>
                            <th>Phone Contact</th>
                            <th>Total Orders Placed</th>
                            <th>Total Amount Spent (₹)</th>
                            <th>Joined On Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($customers_res && mysqli_num_rows($customers_res) > 0): ?>
                            <?php while ($row = mysqli_fetch_assoc($customers_res)): ?>
                                <tr class="border-bottom">
                                    <td>
                                        <strong class="text-dark"><?php echo htmlspecialchars($row['full_name']); ?></strong>
                                    </td>
                                    <td class="font-monospace small"><?php echo htmlspecialchars($row['email']); ?></td>
                                    <td class="text-muted"><?php echo htmlspecialchars($row['phone'] ?: 'N/A'); ?></td>
                                    <td>
                                        <span class="badge bg-light text-dark border px-3 py-2 rounded-pill fw-bold"><?php echo intval($row['total_orders']); ?> Orders</span>
                                    </td>
                                    <td class="fw-bold text-gold">₹<?php echo number_format(floatval($row['total_spent'] ?? 0), 2); ?></td>
                                    <td class="small text-muted"><?php echo date('d M Y, h:i A', strtotime($row['created_at'])); ?></td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="6" class="text-center py-4 text-muted">No customers registered yet.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- ========================================================= -->
<!-- MODAL 1: ADD NEW PERFUME (Admin Form + Dynamic Image Gallery Manager) -->
<!-- ========================================================= -->
<div class="modal fade" id="addPerfumeModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header border-bottom py-3 bg-light">
                <h5 class="modal-title brand-font text-dark"><i class="fa-solid fa-circle-plus text-gold me-2"></i> Admin - Add New Perfume</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="dashboard.php" method="POST" onsubmit="return prepareImageSubmit('add');">
                <!-- Hidden inputs populated dynamically -->
                <input type="hidden" name="primary_image" id="add_primary_image">
                <textarea name="image_urls" id="add_image_urls" class="d-none"></textarea>

                <div class="modal-body p-4">
                    <!-- Amazon Link Auto-Fetch Bar -->
                    <div class="p-3 rounded-3 bg-light border mb-4">
                        <label class="form-label text-gold fw-bold small mb-1"><i class="fa-brands fa-amazon me-1"></i> Import Details from Amazon / Web Link</label>
                        <div class="input-group">
                            <input type="text" id="add_amazon_url" class="form-control form-control-sm bg-white" placeholder="Paste Amazon product or web URL here...">
                            <button type="button" class="btn btn-gold btn-sm px-3 fw-bold" onclick="fetchProductData('add');">
                                <i class="fa-solid fa-bolt me-1"></i> Auto-Fetch Details
                            </button>
                        </div>
                        <small id="add_fetch_status" class="text-muted d-block mt-1 fs-7">Auto-populates Name, Brand, MRP, Price, and Remote Image URLs directly into the form.</small>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label text-muted small fw-bold">Perfume / Item Name *</label>
                            <input type="text" name="name" id="add_name" class="form-control bg-light border-0" required placeholder="e.g. Creed Aventus">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-muted small fw-bold">Brand Name *</label>
                            <input type="text" name="brand" id="add_brand" class="form-control bg-light border-0" required placeholder="e.g. Creed, Tom Ford, Dior">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label text-muted small fw-bold">Category</label>
                            <select name="category" id="add_category" class="form-select bg-light border-0">
                                <option value="Unisex" selected>Unisex Perfume</option>
                                <option value="Men">Men Perfume</option>
                                <option value="Women">Women Perfume</option>
                                <option value="Luxury">Luxury Attar</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label text-muted small fw-bold">MRP List Price (₹)</label>
                            <input type="number" step="0.01" name="mrp" id="add_mrp" class="form-control bg-light border-0" placeholder="e.g. 32000">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label text-muted small fw-bold">Selling Offer Price (₹) *</label>
                            <input type="number" step="0.01" name="price" id="add_price" class="form-control bg-light border-0" required placeholder="e.g. 28500">
                        </div>

                        <!-- Dynamic Image Gallery Manager -->
                        <div class="col-12 border-top pt-3 mt-3">
                            <label class="form-label text-gold fw-bold small mb-1"><i class="fa-solid fa-images me-1"></i> Image Gallery Manager</label>
                            <p class="text-muted small mb-2">Paste remote URL addresses, dynamically set primary image, or delete URLs.</p>
                            
                            <div id="add_image_manager_container" class="d-flex flex-column gap-2 mb-3">
                                <!-- Appended dynamically by JavaScript -->
                            </div>

                            <div class="d-flex gap-2">
                                <button type="button" class="btn btn-outline-gold btn-sm rounded-pill px-3 fw-bold" onclick="addImageRow('add');">
                                    <i class="fa-solid fa-circle-plus me-1"></i> Add Image URL Row
                                </button>
                                <button type="button" class="btn btn-outline-secondary btn-sm rounded-pill px-3" onclick="clearAllImageRows('add');">
                                    <i class="fa-solid fa-trash-can me-1"></i> Clear Gallery
                                </button>
                            </div>
                        </div>

                        <div class="col-12 mt-3">
                            <label class="form-label text-muted small fw-bold">Description / Fragrance Profile</label>
                            <textarea name="description" id="add_description" class="form-control bg-light border-0" rows="3" placeholder="Fragrance story, notes, and longevity..."></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-top p-3 bg-light">
                    <button type="button" class="btn btn-outline-secondary rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="add_perfume" class="btn btn-gold rounded-pill px-4 fw-bold"><i class="fa-solid fa-check me-1"></i> Publish Perfume</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ========================================================= -->
<!-- MODAL 2: EDIT PERFUME (Admin Form + Dynamic Image Gallery Manager) -->
<!-- ========================================================= -->
<div class="modal fade" id="editPerfumeModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header border-bottom py-3 bg-light">
                <h5 class="modal-title brand-font text-dark"><i class="fa-solid fa-pen-to-square text-gold me-2"></i> Admin - Edit Perfume</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="dashboard.php" method="POST" onsubmit="return prepareImageSubmit('edit');">
                <!-- Hidden inputs populated dynamically -->
                <input type="hidden" name="primary_image" id="edit_primary_image">
                <textarea name="image_urls" id="edit_image_urls" class="d-none"></textarea>
                <input type="hidden" name="product_id" id="edit_product_id">

                <div class="modal-body p-4">
                    <!-- Amazon Link Auto-Fetch Bar -->
                    <div class="p-3 rounded-3 bg-light border mb-4">
                        <label class="form-label text-gold fw-bold small mb-1"><i class="fa-brands fa-amazon me-1"></i> Auto-Update from Amazon / Web Link</label>
                        <div class="input-group">
                            <input type="text" id="edit_amazon_url" class="form-control form-control-sm bg-white" placeholder="Paste Amazon product or web URL here...">
                            <button type="button" class="btn btn-gold btn-sm px-3 fw-bold" onclick="fetchProductData('edit');">
                                <i class="fa-solid fa-bolt me-1"></i> Auto-Fetch Details
                            </button>
                        </div>
                        <small id="edit_fetch_status" class="text-muted d-block mt-1 fs-7">Auto-populates Name, Brand, MRP, Price, and Remote Image URLs directly into the form.</small>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label text-muted small fw-bold">Perfume / Item Name *</label>
                            <input type="text" name="name" id="edit_name" class="form-control bg-light border-0" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-muted small fw-bold">Brand Name *</label>
                            <input type="text" name="brand" id="edit_brand" class="form-control bg-light border-0" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label text-muted small fw-bold">Category</label>
                            <select name="category" id="edit_category" class="form-select bg-light border-0">
                                <option value="Unisex">Unisex Perfume</option>
                                <option value="Men">Men Perfume</option>
                                <option value="Women">Women Perfume</option>
                                <option value="Luxury">Luxury Attar</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label text-muted small fw-bold">MRP List Price (₹)</label>
                            <input type="number" step="0.01" name="mrp" id="edit_mrp" class="form-control bg-light border-0">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label text-muted small fw-bold">Selling Offer Price (₹) *</label>
                            <input type="number" step="0.01" name="price" id="edit_price" class="form-control bg-light border-0" required>
                        </div>

                        <!-- Dynamic Image Gallery Manager for Edit -->
                        <div class="col-12 border-top pt-3 mt-3">
                            <label class="form-label text-gold fw-bold small mb-1"><i class="fa-solid fa-images me-1"></i> Image Gallery Manager</label>
                            <p class="text-muted small mb-2">Modify existing image URLs, change the primary cover image, or delete thumbnails.</p>
                            
                            <div id="edit_image_manager_container" class="d-flex flex-column gap-2 mb-3">
                                <!-- Appended dynamically -->
                            </div>

                            <div class="d-flex gap-2">
                                <button type="button" class="btn btn-outline-gold btn-sm rounded-pill px-3 fw-bold" onclick="addImageRow('edit');">
                                    <i class="fa-solid fa-circle-plus me-1"></i> Add Image URL Row
                                </button>
                                <button type="button" class="btn btn-outline-secondary btn-sm rounded-pill px-3" onclick="clearAllImageRows('edit');">
                                    <i class="fa-solid fa-trash-can me-1"></i> Clear Gallery
                                </button>
                            </div>
                        </div>

                        <div class="col-12 mt-3">
                            <label class="form-label text-muted small fw-bold">Description</label>
                            <textarea name="description" id="edit_description" class="form-control bg-light border-0" rows="3"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-top p-3 bg-light">
                    <button type="button" class="btn btn-outline-secondary rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="edit_perfume" class="btn btn-gold rounded-pill px-4 fw-bold"><i class="fa-solid fa-floppy-disk me-1"></i> Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
let addRowCounter = 0;
let editRowCounter = 0;

function addImageRow(prefix, url = '') {
    const container = document.getElementById(prefix + '_image_manager_container');
    if (!container) return;

    let counter = (prefix === 'add') ? ++addRowCounter : ++editRowCounter;
    const row = document.createElement('div');
    row.className = 'image-row d-flex align-items-center gap-2 p-2 bg-light rounded border border-2 border-light';
    row.id = `${prefix}_image_row_${counter}`;

    row.innerHTML = `
        <img src="${url || 'https://images.unsplash.com/photo-1594035910387-fea47794261f?w=150'}" 
             class="img-preview rounded border shadow-sm" 
             style="width: 50px; height: 50px; object-fit: cover;"
             onerror="this.src='https://images.unsplash.com/photo-1594035910387-fea47794261f?w=150';">
        
        <input type="text" 
               class="form-control form-control-sm bg-white font-monospace small flex-grow-1 image-url-input" 
               value="${url}" 
               placeholder="Paste remote image URL..." 
               oninput="updateRowPreview(this);">
        
        <div class="form-check ms-2 me-1">
            <input class="form-check-input primary-radio" 
                   type="radio" 
                   name="${prefix}_primary_image_index" 
                   id="${prefix}_radio_${counter}"
                   onchange="updatePrimaryRadioSelection('${prefix}');">
            <label class="form-check-label small fw-bold text-muted" for="${prefix}_radio_${counter}">Primary</label>
        </div>
        
        <button type="button" class="btn btn-outline-danger btn-sm rounded-circle p-1" style="width:28px; height:28px; line-height:10px;" onclick="removeImageRow('${prefix}', '${row.id}');">
            <i class="fa-solid fa-trash-can small"></i>
        </button>
    `;

    container.appendChild(row);

    const radios = container.querySelectorAll('.primary-radio');
    if (radios.length === 1) {
        radios[0].checked = true;
        updatePrimaryRadioSelection(prefix);
    }
}

function removeImageRow(prefix, rowId) {
    const row = document.getElementById(rowId);
    if (!row) return;

    const wasChecked = row.querySelector('.primary-radio').checked;
    row.remove();

    if (wasChecked) {
        const remainingRadios = document.querySelectorAll(`#${prefix}_image_manager_container .primary-radio`);
        if (remainingRadios.length > 0) {
            remainingRadios[0].checked = true;
        }
    }
    updatePrimaryRadioSelection(prefix);
}

function clearAllImageRows(prefix) {
    const container = document.getElementById(prefix + '_image_manager_container');
    if (container) container.innerHTML = '';
}

function updateRowPreview(input) {
    const row = input.closest('.image-row');
    const img = row.querySelector('.img-preview');
    img.src = input.value.trim() || 'https://images.unsplash.com/photo-1594035910387-fea47794261f?w=150';
}

function updatePrimaryRadioSelection(prefix) {
    const rows = document.querySelectorAll(`#${prefix}_image_manager_container .image-row`);
    rows.forEach(row => {
        const radio = row.querySelector('.primary-radio');
        if (radio.checked) {
            row.classList.remove('border-light');
            row.classList.add('border-gold', 'bg-white');
            row.querySelector('label').classList.remove('text-muted');
            row.querySelector('label').classList.add('text-gold');
        } else {
            row.classList.remove('border-gold', 'bg-white');
            row.classList.add('border-light');
            row.querySelector('label').classList.remove('text-gold');
            row.querySelector('label').classList.add('text-muted');
        }
    });
}

function prepareImageSubmit(prefix) {
    const rows = document.querySelectorAll(`#${prefix}_image_manager_container .image-row`);
    let primaryUrl = '';
    let extraUrls = [];

    rows.forEach(row => {
        const url = row.querySelector('.image-url-input').value.trim();
        const isPrimary = row.querySelector('.primary-radio').checked;

        if (url !== '') {
            if (isPrimary) {
                primaryUrl = url;
            } else {
                extraUrls.push(url);
            }
        }
    });

    if (primaryUrl === '' && extraUrls.length > 0) {
        primaryUrl = extraUrls.shift();
    }

    document.getElementById(prefix + '_primary_image').value = primaryUrl;
    document.getElementById(prefix + '_image_urls').value = extraUrls.join('\n');
    return true;
}

function openEditModal(prodData) {
    document.getElementById('edit_product_id').value = prodData.id || '';
    document.getElementById('edit_name').value = prodData.name || '';
    document.getElementById('edit_brand').value = prodData.brand || 'Perfume Hub';
    document.getElementById('edit_category').value = prodData.category || 'Unisex';
    document.getElementById('edit_mrp').value = prodData.mrp || prodData.price || '';
    document.getElementById('edit_price').value = prodData.price || '';
    document.getElementById('edit_description').value = prodData.description || '';

    clearAllImageRows('edit');
    let primary = prodData.image_url || '';
    let extras = prodData.image_urls ? prodData.image_urls.split('\n') : [];

    if (primary !== '') {
        addImageRow('edit', primary);
    }
    extras.forEach(extra => {
        const clean = extra.trim();
        if (clean !== '' && clean !== primary) {
            addImageRow('edit', clean);
        }
    });

    if (document.querySelectorAll('#edit_image_manager_container .image-row').length === 0) {
        addImageRow('edit');
    }

    const modal = new bootstrap.Modal(document.getElementById('editPerfumeModal'));
    modal.show();
}

function fetchProductData(prefix) {
    const urlInput = document.getElementById(prefix + '_amazon_url');
    const statusMsg = document.getElementById(prefix + '_fetch_status');
    const url = urlInput ? urlInput.value.trim() : '';

    if (!url) {
        alert('Please paste an Amazon or Web product link first.');
        return;
    }

    if (statusMsg) {
        statusMsg.innerHTML = '<span class="text-gold fw-bold"><i class="fa-solid fa-spinner fa-spin me-1"></i> Fetching product details from Amazon/Web...</span>';
    }

    fetch('fetch_product_info.php?url=' + encodeURIComponent(url))
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                if (data.name) document.getElementById(prefix + '_name').value = data.name;
                if (data.brand) document.getElementById(prefix + '_brand').value = data.brand;
                if (data.mrp && data.mrp > 0) document.getElementById(prefix + '_mrp').value = data.mrp;
                if (data.price && data.price > 0) document.getElementById(prefix + '_price').value = data.price;
                
                clearAllImageRows(prefix);
                if (data.image_urls && data.image_urls.length > 0) {
                    data.image_urls.forEach((imgUrl, idx) => {
                        addImageRow(prefix, imgUrl);
                    });
                } else {
                    addImageRow(prefix);
                }

                if (statusMsg) {
                    statusMsg.innerHTML = '<span class="text-success fw-bold"><i class="fa-solid fa-check-circle me-1"></i> Details successfully fetched and filled!</span>';
                }
            } else {
                if (statusMsg) {
                    statusMsg.innerHTML = '<span class="text-danger fw-bold"><i class="fa-solid fa-triangle-exclamation me-1"></i> ' + (data.error || 'Could not auto-fetch details.') + '</span>';
                }
            }
        })
        .catch(err => {
            if (statusMsg) {
                statusMsg.innerHTML = '<span class="text-danger fw-bold"><i class="fa-solid fa-triangle-exclamation me-1"></i> Network error fetching details.</span>';
            }
        });
}

document.addEventListener('DOMContentLoaded', function() {
    <?php if ($active_tab === 'catalog'): ?>
    addImageRow('add');
    <?php endif; ?>
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
