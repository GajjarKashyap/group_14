<?php 
// signup_action.php - Legacy Signup Endpoint
if (session_status() === PHP_SESSION_NONE) { session_start(); }
include('db_config.php'); 

if ($_SERVER["REQUEST_METHOD"] == "POST") { 
    $user = mysqli_real_escape_string($conn, trim($_POST['username'] ?? '')); 
    $pass = trim($_POST['password'] ?? ''); 

    if(empty($user) || empty($pass)) { 
        show_signup_popup('Error: Username or Password is empty!', 'signup.php'); 
        exit(); 
    } 

    $check_user = "SELECT * FROM users WHERE username='$user' OR email='$user'"; 
    $result = $conn->query($check_user); 

    if ($result && $result->num_rows > 0) { 
        show_signup_popup('Username or Email already exists!', 'signup.php'); 
        exit(); 
    } else { 
        $hash = password_hash($pass, PASSWORD_DEFAULT);
        $sql = "INSERT INTO users (full_name, email, password_hash) VALUES ('$user', '$user@example.com', '$hash')"; 
        
        if ($conn->query($sql) === TRUE) { 
            $_SESSION['user_id'] = $conn->insert_id;
            $_SESSION['full_name'] = $user;
            $_SESSION['email'] = "$user@example.com";
            show_signup_popup('Registration Successful! Welcome to Perfume Hub.', 'dashboard.php'); 
            exit(); 
        } else { 
            show_signup_popup("Database Error: " . $conn->error, 'signup.php'); 
        } 
    } 
} else { 
    show_signup_popup("Error: Please submit the signup form.", 'signup.php'); 
} 

function show_signup_popup($message, $redirect_url) {
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