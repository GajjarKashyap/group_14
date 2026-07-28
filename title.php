<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
include('db_config.php');
$error = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['username'])) {
    $user = mysqli_real_escape_string($conn, trim($_POST['username']));
    $pass = trim($_POST['password']);

    $sql = "SELECT * FROM users WHERE username='$user' OR email='$user' LIMIT 1";
    $result = $conn->query($sql);

    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        if ((!empty($row['password_hash']) && password_verify($pass, $row['password_hash'])) || (isset($row['password']) && $pass === $row['password'])) {
            $_SESSION['user_id'] = $row['user_id'] ?? 1;
            $_SESSION['username'] = $row['username'] ?? $row['full_name'];
            $_SESSION['full_name'] = $row['full_name'] ?? $row['username'];
            header("Location: title.php?page=welcome");
            exit();
        }
    }
    $error = "Invalid Username or Password";
}

$page = isset($_GET['page']) ? $_GET['page'] : 'home';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Perfume Hub</title>
<style>
*{ margin:0; padding:0; box-sizing:border-box; font-family:Arial,sans-serif; }
body{ background:#ffffff; color:#333; }
nav{ display:flex; justify-content:space-between; align-items:center; padding:20px 50px; background:#fff; border-bottom:1px solid #eee; }
nav h2{ color:#c5a059; font-size:28px; }
nav ul{ display:flex; list-style:none; gap:20px; }
nav ul li a{ color:#444; text-decoration:none; font-weight:bold; }
nav ul li a:hover{ color:#c5a059; }
.hero{ min-height:70vh; display:flex; justify-content:center; align-items:center; flex-direction:column; text-align:center; padding:40px; }
.hero h1{ color:#222; font-size:48px; margin-bottom:15px; }
.hero p{ color:#666; max-width:600px; font-size:18px; margin-bottom:25px; }
.btn-gold{ padding:12px 30px; font-size:16px; border:none; border-radius:4px; background:#c5a059; color:white; font-weight:bold; text-decoration:none; cursor:pointer; }
.login-box{ width:340px; background:#fff; padding:30px; border-radius:8px; border:1px solid #ddd; text-align:center; box-shadow:0 4px 15px rgba(0,0,0,0.05); }
.login-box input{ width:100%; padding:10px; margin:10px 0; border:1px solid #ccc; border-radius:4px; }
.error{ color:red; margin-top:10px; font-size:14px; }
</style>
</head>
<body>

<nav>
<h2>PERFUME HUB</h2>
<ul>
<li><a href="index.php">Home</a></li>
<li><a href="fragrances.php">Collection</a></li>
<li><a href="dashboard.php">Dashboard</a></li>
</ul>
</nav>

<?php if($page == 'home'){ ?>
<div class="hero">
<h1>Welcome to Perfume Hub</h1>
<p>Explore our exclusive collection of luxury perfumes designed for every personality and occasion.</p>
<a href="title.php?page=login" class="btn-gold">Login</a>
</div>

<?php } elseif($page == 'login'){ ?>
<div class="hero">
<div class="login-box">
<h2>Login</h2>
<form action="title.php?page=login" method="POST">
<input type="text" name="username" placeholder="Enter Username / Email" required>
<input type="password" name="password" placeholder="Enter Password" required>
<button class="btn-gold" type="submit">Submit</button>
</form>
<?php if($error != ""){ echo "<p class='error'>$error</p>"; } ?>
<br><a href="title.php" style="color:#666; text-decoration:none;">⬅ Back</a>
</div>
</div>

<?php } elseif($page == 'welcome'){ ?>
<div class="hero">
<h1>Welcome, <?php echo htmlspecialchars($_SESSION['username'] ?? 'User'); ?>!</h1>
<p>Login Successful 🎉</p>
<a href="logout.php" class="btn-gold">Logout</a>
</div>
<?php } ?>

</body>
</html>