<?php 
// add_product.php - Clean Add Product Form
include 'db.php'; 
$message = ""; 

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_product'])) { 
    $name = mysqli_real_escape_string($conn, trim($_POST['name'])); 
    $category = mysqli_real_escape_string($conn, $_POST['category']); 
    $price = floatval($_POST['price']); 
    $description = mysqli_real_escape_string($conn, trim($_POST['description'])); 
    $image_url = 'https://images.unsplash.com/photo-1594035910387-fea47794261f?w=600'; 
    
    if (isset($_FILES['perfume_image']) && $_FILES['perfume_image']['error'] === 0) { 
        $file_name = time() . '_' . basename($_FILES['perfume_image']['name']); 
        $upload_dir = 'images/'; 
        if (!is_dir($upload_dir)) { mkdir($upload_dir, 0777, true); } 
        $target_file = $upload_dir . $file_name; 
        if (move_uploaded_file($_FILES['perfume_image']['tmp_name'], $target_file)) { 
            $image_url = $target_file; 
        } 
    } 
    
    if (!empty($name) && $price > 0) { 
        $sql = "INSERT INTO products (name, category, price, description, image_url) VALUES ('$name', '$category', $price, '$description', '$image_url')"; 
        if (mysqli_query($conn, $sql)) { 
            $message = "<div class='alert alert-success'>🎉 Product added successfully! <a href='dashboard.php'>View Dashboard</a></div>"; 
        } else { 
            $message = "<div class='alert alert-danger'>Database Error: " . mysqli_error($conn) . "</div>"; 
        } 
    } else { 
        $message = "<div class='alert alert-warning'>Product Name and Price are required!</div>"; 
    } 
} 

$page_title = "Add Product";
require_once __DIR__ . '/includes/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card p-4 shadow-sm border-0 bg-white rounded-3">
                <h3 class="brand-font text-dark mb-4"><i class="fa-solid fa-box text-gold me-2"></i> Add Product</h3>
                
                <?php echo $message; ?>

                <form action="add_product.php" method="POST" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label class="form-label text-muted small fw-bold">Product Name *</label>
                        <input type="text" name="name" class="form-control" required placeholder="e.g. Royal Oud">
                    </div>

                    <div class="mb-3">
                        <label class="form-label text-muted small fw-bold">Category</label>
                        <select name="category" class="form-select">
                            <option value="Men">Men Perfumes</option>
                            <option value="Women">Women Perfumes</option>
                            <option value="Unisex" selected>Unisex Perfumes</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label text-muted small fw-bold">Price (₹) *</label>
                        <input type="number" name="price" step="0.01" class="form-control" required placeholder="e.g. 1500">
                    </div>

                    <div class="mb-3">
                        <label class="form-label text-muted small fw-bold">Description</label>
                        <textarea name="description" class="form-control" rows="3" placeholder="Long lasting perfume..."></textarea>
                    </div>

                    <div class="mb-4">
                        <label class="form-label text-muted small fw-bold">Perfume Image</label>
                        <input type="file" name="perfume_image" class="form-control" accept="image/*">
                    </div>

                    <button type="submit" name="add_product" class="btn btn-gold w-100 py-2 fw-bold mb-2">Save Product</button>
                    <a href="dashboard.php" class="btn btn-outline-secondary btn-sm w-100">Back to Dashboard</a>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
