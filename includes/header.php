<?php
// includes/header.php - Global Navbar & Luxury Layout Header
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../helpers/auth.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$cart_count = 0;
if (!empty($_SESSION['cart']) && is_array($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $c_item) {
        $cart_count += (int)($c_item['qty'] ?? 1);
    }
}

$is_logged_in = isset($_SESSION['user_id']);
$user_name = $_SESSION['full_name'] ?? $_SESSION['username'] ?? 'Account';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? htmlspecialchars($page_title) . " - Perfume Hub" : "Perfume Hub - Luxury Fragrance Boutique"; ?></title>
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@500;600;700;800&family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Preconnect to Image CDNs for Faster Load -->
    <link rel="preconnect" href="https://m.media-amazon.com">
    <link rel="preconnect" href="https://images.unsplash.com">
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FontAwesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <!-- Custom Style -->
    <link rel="stylesheet" href="style.css">
</head>
<body class="bg-white text-dark d-flex flex-column min-vh-100">

<!-- Header Navigation Bar -->
<header class="sticky-top bg-white border-bottom shadow-sm">
    <nav class="navbar navbar-expand-lg navbar-light py-3">
        <div class="container">
            <!-- Brand Logo with Crown Icon -->
            <a class="navbar-brand d-flex align-items-center gap-2" href="index.php">
                <i class="fa-solid fa-crown text-gold fs-3"></i>
                <span class="brand-font fs-3 text-dark fw-bold tracking-wider">PERFUME<span class="text-gold">HUB</span></span>
            </a>

            <!-- Mobile Menu Toggle -->
            <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navbarMain" aria-controls="navbarMain" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>

            <!-- Navbar Menu Links (Public Menu) -->
            <div class="collapse navbar-collapse" id="navbarMain">
                <ul class="navbar-nav mx-auto mb-2 mb-lg-0 gap-lg-4 text-uppercase small fw-bold">
                    <li class="nav-item">
                        <a class="nav-link text-dark text-hover-gold" href="index.php"><i class="fa-solid fa-house me-1 text-gold"></i> Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link text-dark text-hover-gold" href="fragrances.php"><i class="fa-solid fa-gem me-1 text-gold"></i> Collection</a>
                    </li>
                </ul>

                <!-- User & Cart Quick Controls -->
                <div class="d-flex align-items-center gap-3">
                    <a href="cart.php" class="btn btn-outline-gold position-relative rounded-pill px-3 py-1">
                        <i class="fa-solid fa-basket-shopping me-1"></i> Basket
                        <?php if ($cart_count > 0): ?>
                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-gold text-white fs-7">
                                <?php echo $cart_count; ?>
                            </span>
                        <?php endif; ?>
                    </a>

                    <?php if ($is_logged_in): ?>
                        <div class="dropdown">
                            <button class="btn btn-gold dropdown-toggle rounded-pill px-3 py-1 fw-bold" type="button" id="userMenu" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="fa-solid fa-circle-user me-1"></i> <?php echo htmlspecialchars($user_name); ?>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end shadow border-0 py-2 mt-2 rounded-3" aria-labelledby="userMenu">
                                <li><a class="dropdown-item py-2" href="orders.php"><i class="fa-solid fa-box-open text-gold me-2"></i> My Orders</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item py-2 text-danger" href="logout.php"><i class="fa-solid fa-right-from-bracket me-2"></i> Logout</a></li>
                            </ul>
                        </div>
                    <?php else: ?>
                        <a href="login.php" class="btn btn-gold rounded-pill px-3 py-1 fw-bold"><i class="fa-solid fa-right-to-bracket me-1"></i> Sign In</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>
</header>
<main class="flex-grow-1">
