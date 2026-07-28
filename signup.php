<?php
// signup.php - Customer Account Registration Page (Clean White Theme)
$page_title = "Register Account";
require_once __DIR__ . '/includes/header.php';

$pdo = getPDO();
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = sanitize_input($_POST['full_name'] ?? '');
    $email = sanitize_input($_POST['email'] ?? '');
    $phone = sanitize_input($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (empty($full_name) || empty($email) || empty($password)) {
        $error = "Please fill in all mandatory fields.";
    } elseif ($password !== $confirm_password) {
        $error = "Password confirmation does not match.";
    } else {
        $chk = $pdo->prepare("SELECT COUNT(*) FROM users WHERE LOWER(email) = LOWER(:email)");
        $chk->execute(['email' => $email]);
        if ($chk->fetchColumn() > 0) {
            $error = "An account with this email address already exists.";
        } else {
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (`full_name`, `email`, `phone`, `password_hash`) VALUES (:name, :email, :phone, :hash)");
            if ($stmt->execute(['name' => $full_name, 'email' => $email, 'phone' => $phone, 'hash' => $password_hash])) {
                $user_id = $pdo->lastInsertId();
                $_SESSION['user_id'] = $user_id;
                $_SESSION['full_name'] = $full_name;
                $_SESSION['email'] = $email;
                header("Location: index.php");
                exit();
            } else {
                $error = "Registration failed. Please try again.";
            }
        }
    }
}
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card p-4 shadow-sm border-0 bg-white rounded-3">
                <h3 class="text-dark brand-font text-center mb-4"><i class="fa-solid fa-user-plus text-gold me-2"></i> Register Account</h3>

                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger mb-3 py-2 small"><?php echo $error; ?></div>
                <?php endif; ?>

                <form action="signup.php" method="POST">
                    <div class="mb-3">
                        <label class="form-label text-muted small fw-bold">Full Name *</label>
                        <input type="text" name="full_name" class="form-control" required placeholder="John Doe">
                    </div>

                    <div class="mb-3">
                        <label class="form-label text-muted small fw-bold">Email Address *</label>
                        <input type="email" name="email" class="form-control" required placeholder="user@example.com">
                    </div>

                    <div class="mb-3">
                        <label class="form-label text-muted small fw-bold">Phone Number</label>
                        <input type="text" name="phone" class="form-control" placeholder="9876543210">
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label text-muted small fw-bold">Password *</label>
                            <input type="password" name="password" class="form-control" required placeholder="••••••••">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-muted small fw-bold">Confirm Password *</label>
                            <input type="password" name="confirm_password" class="form-control" required placeholder="••••••••">
                        </div>
                    </div>

                    <button type="submit" class="btn btn-gold w-100 py-2 fw-bold mb-3">Create Perfume Hub Account</button>

                    <div class="text-center text-muted small">
                        Already registered? <a href="login.php" class="text-gold text-decoration-none fw-bold">Sign In</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
