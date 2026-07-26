<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (!isset($_SESSION['username']) && !isset($_SESSION['user_id'])) {
    header("Location: p1.php");
    exit();
}
$display_name = $_SESSION['full_name'] ?? $_SESSION['username'] ?? 'User';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome - Perfume Hub</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: Arial, sans-serif; }
        body { background: #f8f9fa; color: #333; }
        nav { display: flex; justify-content: space-between; align-items: center; padding: 20px 50px; background: #ffffff; border-bottom: 1px solid #eee; }
        nav h2 { color: #c5a059; font-size: 26px; }
        .logout-btn { background: #c5a059; color: white; padding: 8px 20px; text-decoration: none; border-radius: 4px; font-weight: bold; }
        .welcome-box { text-align: center; margin-top: 80px; padding: 40px; background: white; max-width: 600px; margin-left: auto; margin-right: auto; border-radius: 10px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); }
        .welcome-box h1 { font-size: 36px; color: #222; margin-bottom: 10px; }
        .welcome-box p { font-size: 18px; color: #666; margin-bottom: 25px; }
        .btn-home { background: #222; color: white; padding: 10px 25px; text-decoration: none; border-radius: 4px; font-weight: bold; display: inline-block; }
    </style>
</head>
<body>

    <nav>
        <h2>PERFUME HUB</h2>
        <a href="logout.php" class="logout-btn">Logout</a>
    </nav>

    <div class="welcome-box">
        <h1>Welcome, <?php echo htmlspecialchars($display_name); ?>! 🎉</h1>
        <p>Aapne successfully login kar liya hai.</p>
        <a href="index.php" class="btn-home">Go to Storefront</a>
    </div>

</body>
</html>
