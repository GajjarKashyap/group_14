<?php
// login_action.php - Legacy Login Endpoint
if (session_status() === PHP_SESSION_NONE) { session_start(); }
include('db_config.php');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $input_user = mysqli_real_escape_string($conn, trim($_POST['username'] ?? ''));
    $pass = trim($_POST['password'] ?? '');

    if (empty($input_user) || empty($pass)) {
        show_popup("Error: Username/Email or Password field is empty!", "p1.php");
        exit();
    }

    $sql = "SELECT * FROM users WHERE username = '$input_user' OR email = '$input_user' LIMIT 1";
    $result = $conn->query($sql);

    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $ok = (!empty($row['password_hash']) && password_verify($pass, $row['password_hash'])) || (isset($row['password']) && $pass === $row['password']);

        if ($ok) {
            $_SESSION['user_id'] = $row['user_id'] ?? 1;
            $_SESSION['username'] = $row['username'] ?? $row['full_name'];
            $_SESSION['full_name'] = $row['full_name'] ?? $row['username'];
            $_SESSION['email'] = $row['email'] ?? $input_user;
            
            show_popup("Login Successful! Welcome to Perfume Hub.", "dashboard.php");
            exit();
        }
    }
    show_popup("Invalid credentials! Please try again.", "p1.php");
    exit();
}

function show_popup($message, $redirect_url) {
    echo "
    <!DOCTYPE html>
    <html>
    <head><title>Perfume Hub</title></head>
    <body style='background:#f8f9fa; font-family:sans-serif; display:flex; align-items:center; justify-content:center; height:100vh; margin:0;'>
        <script>
            alert('$message');
            window.location.href = '$redirect_url';
        </script>
    </body>
    </html>";
}
$conn->close();
?>