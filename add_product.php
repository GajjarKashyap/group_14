<?php 
// add_product.php - Build Mart Style Product Creation Session with Dynamic Image Gallery Manager
include 'db.php'; 
$message = ""; 

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_product'])) { 
    $name = mysqli_real_escape_string($conn, trim($_POST['name'])); 
    $brand = mysqli_real_escape_string($conn, trim($_POST['brand'] ?? 'Perfume Hub'));
    $category = mysqli_real_escape_string($conn, $_POST['category'] ?? 'Unisex'); 
    $mrp = floatval($_POST['mrp'] ?? 0); 
    $price = floatval($_POST['price'] ?? 0); 
    if ($mrp <= 0) { $mrp = $price * 1.15; }
    $stock = max(1, intval($_POST['stock'] ?? 50));
    $description = mysqli_real_escape_string($conn, trim($_POST['description'] ?? '')); 
    
    // Notes Pyramid
    $top_notes = mysqli_real_escape_string($conn, trim($_POST['top_notes'] ?? ''));
    $heart_notes = mysqli_real_escape_string($conn, trim($_POST['heart_notes'] ?? ''));
    $base_notes = mysqli_real_escape_string($conn, trim($_POST['base_notes'] ?? ''));

    // Image URLs (Parsed from hidden fields populated dynamically by JavaScript)
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

            // Sync to perfume_houses
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
            
            $sql_frag = "INSERT INTO fragrances (`fragrance_code`, `fragrance_name`, `house_id`, `audience`, `summary`, `full_story`, `list_price`, `offer_price`, `discount_rate`, `available_units`) 
                         VALUES ('$code', '$name', $house_id, '$category', '$description', '$description', $mrp, $price, $discount_rate, $stock)";
            if (mysqli_query($conn, $sql_frag)) {
                $frag_id = mysqli_insert_id($conn);
                
                // Add Media
                foreach ($image_urls_array as $idx => $remote_url) {
                    $esc_url = mysqli_real_escape_string($conn, $remote_url);
                    $is_featured = ($idx === 0) ? 1 : 0;
                    mysqli_query($conn, "INSERT INTO fragrance_media (`fragrance_id`, `remote_image_address`, `media_origin`, `image_type`, `featured_image`) 
                                         VALUES ($frag_id, '$esc_url', 'external_link', 'Main', $is_featured)");
                }

                // Add Notes if provided
                if (!empty($top_notes)) {
                    foreach (explode(',', $top_notes) as $pos => $n_item) {
                        $n_clean = mysqli_real_escape_string($conn, trim($n_item));
                        if (!empty($n_clean)) {
                            mysqli_query($conn, "INSERT INTO fragrance_notes (`fragrance_id`, `note_stage`, `ingredient_name`, `display_position`) VALUES ($frag_id, 'opening', '$n_clean', " . ($pos + 1) . ")");
                        }
                    }
                }
                if (!empty($heart_notes)) {
                    foreach (explode(',', $heart_notes) as $pos => $n_item) {
                        $n_clean = mysqli_real_escape_string($conn, trim($n_item));
                        if (!empty($n_clean)) {
                            mysqli_query($conn, "INSERT INTO fragrance_notes (`fragrance_id`, `note_stage`, `ingredient_name`, `display_position`) VALUES ($frag_id, 'heart', '$n_clean', " . ($pos + 1) . ")");
                        }
                    }
                }
                if (!empty($base_notes)) {
                    foreach (explode(',', $base_notes) as $pos => $n_item) {
                        $n_clean = mysqli_real_escape_string($conn, trim($n_item));
                        if (!empty($n_clean)) {
                            mysqli_query($conn, "INSERT INTO fragrance_notes (`fragrance_id`, `note_stage`, `ingredient_name`, `display_position`) VALUES ($frag_id, 'dry_down', '$n_clean', " . ($pos + 1) . ")");
                        }
                    }
                }
            }

            $message = "<div class='alert alert-success shadow-sm rounded-4 p-4 mb-4 border-0 bg-white border-start border-4 border-success'>
                <div class='d-flex align-items-center gap-3'>
                    <i class='fa-solid fa-circle-check text-success fa-3x'></i>
                    <div>
                        <h5 class='text-dark brand-font mb-1 fw-bold'>Product Created Successfully!</h5>
                        <p class='text-muted small mb-2'>Item details, prices, stock, and remote online images have been published to your store catalog.</p>
                        <div class='d-flex gap-2'>
                            <a href='fragrance.php?id=$product_id' class='btn btn-gold btn-sm rounded-pill px-3'><i class='fa-solid fa-eye me-1'></i> View Product Page</a>
                            <a href='dashboard.php' class='btn btn-outline-gold btn-sm rounded-pill px-3'><i class='fa-solid fa-layer-group me-1'></i> Go to Dashboard</a>
                        </div>
                    </div>
                </div>
            </div>"; 
        } else { 
            $message = "<div class='alert alert-danger shadow-sm rounded-3 py-3'>Database Error: " . mysqli_error($conn) . "</div>"; 
        } 
    } else { 
        $message = "<div class='alert alert-warning shadow-sm rounded-3 py-3'>Product Name and Selling Price are required!</div>"; 
    } 
} 

$page_title = "Build Mart - Product Entry Session";
require_once __DIR__ . '/includes/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-9 col-md-11">
            <?php echo $message; ?>

            <!-- Build Mart Form Container -->
            <div class="card p-4 p-md-5 shadow-sm border-0 bg-white rounded-4">
                <!-- Session Header -->
                <div class="d-flex align-items-center justify-content-between mb-4 pb-3 border-bottom">
                    <div>
                        <span class="badge bg-gold text-white px-3 py-2 text-uppercase mb-2 rounded-pill fs-7 tracking-wider">
                            <i class="fa-solid fa-boxes-packing me-1"></i> Build Mart Inventory Engine
                        </span>
                        <h2 class="brand-font text-dark mb-0 fw-bold">New Product Creation Session</h2>
                    </div>
                    <a href="dashboard.php" class="btn btn-outline-gold rounded-pill px-4"><i class="fa-solid fa-arrow-left me-1"></i> Dashboard</a>
                </div>

                <!-- Section 0: Amazon Link Auto-Fetcher -->
                <div class="p-4 rounded-4 bg-light border mb-4 shadow-sm">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <label class="form-label text-gold fw-bold mb-0 fs-6"><i class="fa-brands fa-amazon me-2"></i> Amazon & Web Link Quick Importer</label>
                        <span class="badge bg-white text-gold border rounded-pill px-3 py-1 fs-7">Auto-Fill Engine</span>
                    </div>
                    <p class="text-muted small mb-3">Paste an Amazon product link below and click <strong>Auto-Fetch Details</strong> to instantly populate title, brand, prices, and high-res images into this session.</p>
                    <div class="input-group input-group-lg">
                        <input type="text" id="add_amazon_url" class="form-control bg-white border-0 shadow-sm fs-6" placeholder="Paste Amazon product or e-commerce URL here...">
                        <button type="button" class="btn btn-gold px-4 fw-bold shadow-sm" onclick="fetchProductData();">
                            <i class="fa-solid fa-bolt me-1"></i> Auto-Fetch Details
                        </button>
                    </div>
                    <small id="add_fetch_status" class="text-muted d-block mt-2 fs-7"></small>
                </div>

                <form action="add_product.php" method="POST" onsubmit="return prepareImageSubmit();">
                    <!-- Hidden fields to hold final compiled image values -->
                    <input type="hidden" name="primary_image" id="add_primary_image">
                    <textarea name="image_urls" id="add_image_urls" class="d-none"></textarea>

                    <!-- SECTION 1: PRODUCT BASICS -->
                    <div class="mb-4">
                        <h5 class="text-dark brand-font fw-bold pb-2 border-bottom mb-3"><i class="fa-solid fa-tag text-gold me-2"></i> 1. Product Essentials</h5>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label text-muted small fw-bold">Item / Perfume Name *</label>
                                <input type="text" name="name" id="add_name" class="form-control form-control-lg bg-light border-0" required placeholder="e.g. Creed Aventus Parfum">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label text-muted small fw-bold">Brand Name *</label>
                                <input type="text" name="brand" id="add_brand" class="form-control form-control-lg bg-light border-0" required placeholder="e.g. Creed, Tom Ford, Dior">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label text-muted small fw-bold">Category / Target Audience</label>
                                <select name="category" id="add_category" class="form-select form-select-lg bg-light border-0">
                                    <option value="Unisex" selected>Unisex Perfume</option>
                                    <option value="Men">Men Perfume</option>
                                    <option value="Women">Women Perfume</option>
                                    <option value="Luxury">Luxury Attar</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label text-muted small fw-bold">Available Stock Quantity</label>
                                <input type="number" name="stock" class="form-control form-control-lg bg-light border-0" value="50" min="1">
                            </div>
                        </div>
                    </div>

                    <!-- SECTION 2: PRICING & DISCOUNTS -->
                    <div class="mb-4">
                        <h5 class="text-dark brand-font fw-bold pb-2 border-bottom mb-3"><i class="fa-solid fa-indian-rupee-sign text-gold me-2"></i> 2. Pricing & Savings</h5>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label text-muted small fw-bold">MRP (List Price ₹)</label>
                                <input type="number" step="0.01" name="mrp" id="add_mrp" class="form-control form-control-lg bg-light border-0" placeholder="e.g. 32000" oninput="calculateSavings();">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label text-muted small fw-bold">Selling Offer Price (₹) *</label>
                                <input type="number" step="0.01" name="price" id="add_price" class="form-control form-control-lg bg-light border-0" required placeholder="e.g. 28500" oninput="calculateSavings();">
                            </div>
                            <div class="col-12">
                                <div id="savings_display_badge" class="p-3 bg-light rounded-3 text-gold fw-bold small border text-center" style="display: none;">
                                    <i class="fa-solid fa-percent me-1"></i> Customer Savings: <span id="savings_amount">₹0.00</span> (<span id="savings_pct">0%</span> OFF)
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- SECTION 3: DYNAMIC IMAGE GALLERY MANAGER -->
                    <div class="mb-4">
                        <h5 class="text-dark brand-font fw-bold pb-2 border-bottom mb-3"><i class="fa-solid fa-images text-gold me-2"></i> 3. Dynamic Image Gallery Manager</h5>
                        <p class="text-muted small mb-3">Add image URLs below, change order, set the primary featured image, or remove thumbnails live.</p>
                        
                        <div id="image_manager_container" class="d-flex flex-column gap-2 mb-3">
                            <!-- Dynamic image input rows will be appended here by JS -->
                        </div>

                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-outline-gold btn-sm rounded-pill px-3 fw-bold" onclick="addImageRow();">
                                <i class="fa-solid fa-circle-plus me-1"></i> Add Image URL Row
                            </button>
                            <button type="button" class="btn btn-outline-secondary btn-sm rounded-pill px-3" onclick="clearAllImageRows();">
                                <i class="fa-solid fa-trash-can me-1"></i> Clear Gallery
                            </button>
                        </div>
                    </div>

                    <!-- SECTION 4: SPECIFICATIONS & SCENT NOTES -->
                    <div class="mb-4">
                        <h5 class="text-dark brand-font fw-bold pb-2 border-bottom mb-3"><i class="fa-solid fa-flask-vial text-gold me-2"></i> 4. Specifications & Olfactory Notes</h5>
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label text-muted small fw-bold">Product Summary & Story</label>
                                <textarea name="description" id="add_description" class="form-control bg-light border-0" rows="3" placeholder="Sensual, audacious and contemporary luxury scent story..."></textarea>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label text-muted small fw-bold">Top Notes (Comma separated)</label>
                                <input type="text" name="top_notes" class="form-control bg-light border-0" placeholder="Bergamot, Pineapple, Lemon">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label text-muted small fw-bold">Heart Notes (Comma separated)</label>
                                <input type="text" name="heart_notes" class="form-control bg-light border-0" placeholder="Jasmine, Patchouli, Rose">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label text-muted small fw-bold">Base Notes (Comma separated)</label>
                                <input type="text" name="base_notes" class="form-control bg-light border-0" placeholder="Oakmoss, Ambergris, Vanilla">
                            </div>
                        </div>
                    </div>

                    <div class="mt-4 pt-3 border-top">
                        <button type="submit" name="add_product" class="btn btn-gold btn-lg w-100 py-3 fw-bold rounded-3 shadow-sm"><i class="fa-solid fa-check-circle me-2"></i> Complete Session & Publish Product</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
// Dynamic Image Manager Script
let rowCounter = 0;

function addImageRow(url = '') {
    const container = document.getElementById('image_manager_container');
    rowCounter++;

    const row = document.createElement('div');
    row.className = 'image-row d-flex align-items-center gap-2 p-2 bg-light rounded border border-2 border-light';
    row.id = `image_row_${rowCounter}`;

    row.innerHTML = `
        <img src="${url || 'https://images.unsplash.com/photo-1594035910387-fea47794261f?w=150'}" 
             class="img-preview rounded border shadow-sm" 
             style="width: 50px; height: 50px; object-fit: cover;"
             onerror="this.src='https://images.unsplash.com/photo-1594035910387-fea47794261f?w=150';">
        
        <input type="text" 
               class="form-control form-control-sm bg-white font-monospace small flex-grow-1 image-url-input" 
               value="${url}" 
               placeholder="Paste online remote image URL address..." 
               oninput="updateRowPreview(this);">
        
        <div class="form-check ms-2 me-1">
            <input class="form-check-input primary-radio" 
                   type="radio" 
                   name="primary_image_index" 
                   id="radio_${rowCounter}"
                   onchange="updatePrimaryRadioSelection();">
            <label class="form-check-label small fw-bold text-muted" for="radio_${rowCounter}">Primary</label>
        </div>
        
        <button type="button" class="btn btn-outline-danger btn-sm rounded-circle p-1" style="width:28px; height:28px; line-height:10px;" onclick="removeImageRow('${row.id}');">
            <i class="fa-solid fa-trash-can small"></i>
        </button>
    `;

    container.appendChild(row);

    // If it's the first row, make it primary automatically
    const radios = container.querySelectorAll('.primary-radio');
    if (radios.length === 1) {
        radios[0].checked = true;
        updatePrimaryRadioSelection();
    }
}

function removeImageRow(rowId) {
    const row = document.getElementById(rowId);
    if (!row) return;

    const wasChecked = row.querySelector('.primary-radio').checked;
    row.remove();

    // Re-assign primary if the removed one was primary
    if (wasChecked) {
        const remainingRadios = document.querySelectorAll('.primary-radio');
        if (remainingRadios.length > 0) {
            remainingRadios[0].checked = true;
        }
    }
    updatePrimaryRadioSelection();
}

function clearAllImageRows() {
    document.getElementById('image_manager_container').innerHTML = '';
}

function updateRowPreview(input) {
    const row = input.closest('.image-row');
    const img = row.querySelector('.img-preview');
    const url = input.value.trim();
    img.src = url || 'https://images.unsplash.com/photo-1594035910387-fea47794261f?w=150';
}

function updatePrimaryRadioSelection() {
    const rows = document.querySelectorAll('.image-row');
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

function prepareImageSubmit() {
    const rows = document.querySelectorAll('.image-row');
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

    document.getElementById('add_primary_image').value = primaryUrl;
    document.getElementById('add_image_urls').value = extraUrls.join('\n');
    return true;
}

function calculateSavings() {
    const mrp = parseFloat(document.getElementById('add_mrp').value) || 0;
    const price = parseFloat(document.getElementById('add_price').value) || 0;
    const badge = document.getElementById('savings_display_badge');
    
    if (mrp > 0 && price > 0 && mrp > price) {
        const diff = mrp - price;
        const pct = Math.round((diff / mrp) * 100);
        document.getElementById('savings_amount').textContent = '₹' + diff.toFixed(2);
        document.getElementById('savings_pct').textContent = pct + '%';
        badge.style.display = 'block';
    } else {
        badge.style.display = 'none';
    }
}

function fetchProductData() {
    const urlInput = document.getElementById('add_amazon_url');
    const statusMsg = document.getElementById('add_fetch_status');
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
                if (data.name) document.getElementById('add_name').value = data.name;
                if (data.brand) document.getElementById('add_brand').value = data.brand;
                if (data.mrp && data.mrp > 0) document.getElementById('add_mrp').value = data.mrp;
                if (data.price && data.price > 0) document.getElementById('add_price').value = data.price;
                
                clearAllImageRows();
                if (data.image_urls && data.image_urls.length > 0) {
                    data.image_urls.forEach((imgUrl, idx) => {
                        addImageRow(imgUrl);
                    });
                } else {
                    addImageRow();
                }

                calculateSavings();

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
    // Start with 1 default image input row
    addImageRow();
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
