<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login - Perfume Hub</title>
<style>
*{ margin:0; padding:0; box-sizing:border-box; font-family:Arial,sans-serif; }
body{ background:#f8f9fa; height:100vh; display:flex; justify-content:center; align-items:center; }
.login-card{ width:360px; background:white; padding:35px; border-radius:12px; text-align:center; box-shadow:0 4px 20px rgba(0,0,0,0.08); border:1px solid #eee; }
.login-card h2{ color:#222; margin-bottom:20px; font-size:26px; }
.login-card input{ width:100%; padding:12px; margin:10px 0; font-size:15px; border:1px solid #ddd; border-radius:6px; background:#fff; }
.btn-gold{ width:100%; padding:12px; font-size:16px; border:none; border-radius:6px; background:#c5a059; color:white; font-weight:bold; cursor:pointer; margin-top:10px; }
.btn-gold:hover{ background:#a37f3a; }
.link-btn{ display:inline-block; margin-top:15px; color:#555; font-size:14px; text-decoration:none; }
.link-btn:hover{ text-decoration:underline; color:#c5a059; }
</style>
</head>
<body>
<div class="login-card">
    <h2>Perfume Hub</h2>
    <form action="login_action.php" method="POST"> 
        <input type="text" name="username" placeholder="Enter Username / Email" required>
        <input type="password" name="password" placeholder="Enter Password" required>
        <button class="btn-gold" type="submit">Login</button>
    </form>
    <a href="signup.php" class="link-btn">New user? Create an account</a><br>
    <a href="index.php" class="link-btn">⬅ Back to Home</a>
</div>
</body>
</html>