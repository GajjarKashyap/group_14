<?php
// login.php - Customer Login Page (Clean White Theme)
$page_title = "Customer Login";
require_once __DIR__ . '/includes/header.php';

$pdo = getPDO();
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitize_input($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = "Please enter both your email/username and password.";
    } else {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = :email OR username = :username LIMIT 1");
        $stmt->execute(['email' => $email, 'username' => $email]);
        $user = $stmt->fetch();

        if ($user && (!empty($user['password_hash']) ? password_verify($password, $user['password_hash']) : ($password === ($user['password'] ?? '')))) {
            session_regenerate_id(true);
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['username'] = $user['username'] ?? $user['full_name'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['email'] = $user['email'];

            header("Location: index.php");
            exit();
        } else {
            $error = "Invalid credentials. Please try again.";
        }
    }
}
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-5">
            <div class="card p-4 shadow-sm border-0 bg-white rounded-3">
                <h3 class="text-dark brand-font text-center mb-4"><i class="fa-solid fa-right-to-bracket text-gold me-2"></i> Account Login</h3>

                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger mb-3 py-2 small"><?php echo $error; ?></div>
                <?php endif; ?>

                <form action="login.php" method="POST">
                    <div class="mb-3">
                        <label class="form-label text-muted small fw-bold">Email or Username</label>
                        <input type="text" name="email" class="form-control" required placeholder="user@example.com">
                    </div>

                    <div class="mb-4">
                        <label class="form-label text-muted small fw-bold">Password</label>
                        <input type="password" name="password" class="form-control" required placeholder="••••••••">
                    </div>

                    <button type="submit" class="btn btn-gold w-100 py-2 fw-bold mb-3">Sign In to Perfume Hub</button>

                    <div class="text-center text-muted small">
                        Don't have an account? <a href="signup.php" class="text-gold text-decoration-none fw-bold">Create Account</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
