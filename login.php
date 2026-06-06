<?php
session_start();
require_once 'db_connection.php';

if(isset($_SESSION['user_id'])) { header('Location: index.php'); exit(); }

$error = '';
if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = $_POST['password'];
    $remember = isset($_POST['remember']);
    
    $result = mysqli_query($conn, "SELECT * FROM users WHERE username = '$username' OR email = '$username'");
    
    if(mysqli_num_rows($result) == 1) {
        $user = mysqli_fetch_assoc($result);
        if(password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['user_role'] = $user['role'];
            
            if($remember) {
                setcookie('user_id', $user['id'], time() + 86400 * 30, "/");
                setcookie('user_name', $user['full_name'], time() + 86400 * 30, "/");
            }
            
            header('Location: ' . ($user['role'] == 'admin' ? 'admin/dashboard.php' : 'index.php'));
            exit();
        } else { $error = 'Invalid password!'; }
    } else { $error = 'User not found!'; }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - UHD-Wears</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow: hidden;
        }
        
        /* ============================================
           VIDEO BACKGROUND
           ============================================ */
        .video-background {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -2;
            object-fit: cover;
        }
        
        /* Dark Overlay for better text readability */
        .video-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.6);
            z-index: -1;
        }
        
        /* ============================================
           MOVING NEON BORDER ANIMATION
           ============================================ */
        .login-card {
            background: rgba(26, 26, 26, 0.95);
            border-radius: 20px;
            padding: 40px;
            max-width: 450px;
            width: 100%;
            position: relative;
            animation: slideUp 0.6s;
            z-index: 1;
        }
        
        /* Moving Neon Border Effect */
        .login-card::before {
            content: '';
            position: absolute;
            top: -2px;
            left: -2px;
            right: -2px;
            bottom: -2px;
            background: linear-gradient(
                45deg,
                #FFD700,
                #FFC107,
                #FFD700,
                #FFC107,
                #FFD700,
                #FFC107,
                #FFD700
            );
            background-size: 300% 300%;
            border-radius: 22px;
            z-index: -1;
            animation: neonMove 3s ease infinite;
            filter: blur(8px);
            opacity: 0.7;
        }
        
        /* Inner border for cleaner look */
        .login-card::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(26, 26, 26, 0.95);
            border-radius: 20px;
            z-index: -1;
        }
        
        @keyframes neonMove {
            0% {
                background-position: 0% 50%;
                opacity: 0.5;
            }
            50% {
                background-position: 100% 50%;
                opacity: 1;
            }
            100% {
                background-position: 0% 50%;
                opacity: 0.5;
            }
        }
        
        @keyframes slideUp {
            from { 
                opacity: 0; 
                transform: translateY(50px); 
            }
            to { 
                opacity: 1; 
                transform: translateY(0); 
            }
        }
        
        .login-card h2 { 
            text-align: center; 
            background: linear-gradient(135deg, #FFD700, #FFC107); 
            -webkit-background-clip: text; 
            -webkit-text-fill-color: transparent; 
            margin-bottom: 30px; 
        }
        
        .form-control { 
            background: #2a2a2a; 
            border: 1px solid #3a3a3a; 
            color: #fff; 
            padding: 12px; 
            border-radius: 10px; 
            transition: all 0.3s;
        }
        
        .form-control:focus { 
            border-color: #FFD700; 
            box-shadow: 0 0 0 3px rgba(255,215,0,0.1); 
            outline: none;
        }
        
        .btn-primary { 
            background: linear-gradient(135deg, #FFD700, #FFC107); 
            border: none; 
            padding: 12px; 
            width: 100%; 
            border-radius: 10px; 
            font-weight: 600; 
            color: #0a0a0a; 
            transition: all 0.3s; 
        }
        
        .btn-primary:hover { 
            transform: translateY(-2px); 
            box-shadow: 0 5px 15px rgba(255,215,0,0.4); 
        }
        
        .alert { 
            background: rgba(220,53,69,0.2); 
            color: #ff6b6b; 
            border: none; 
            border-radius: 10px; 
            padding: 10px 15px;
        }
        
        a { 
            color: #FFD700; 
            text-decoration: none; 
            transition: color 0.3s;
        }
        
        a:hover {
            color: #ffc107;
            text-decoration: underline;
        }
        
        .form-check-input:checked {
            background-color: #FFD700;
            border-color: #FFD700;
        }
        
        .form-check-input:focus {
            box-shadow: 0 0 0 2px rgba(255,215,0,0.25);
            border-color: #FFD700;
        }
        
        /* Responsive */
        @media (max-width: 576px) {
            .login-card {
                padding: 30px 25px;
                margin: 20px;
                max-width: calc(100% - 40px);
            }
            
            .login-card h2 {
                font-size: 24px;
                margin-bottom: 25px;
            }
            
            .form-control {
                padding: 10px;
                font-size: 14px;
            }
            
            .btn-primary {
                padding: 10px;
                font-size: 14px;
            }
        }
    </style>
</head>
<body>

<!-- ============================================ -->
<!-- VIDEO BACKGROUND - CHANGE THE PATH BELOW -->
<!-- ============================================ -->

<!-- Video Background -->
<video class="video-background" autoplay loop muted playsinline poster="images/login-poster.jpg">
    <!-- CHANGE THIS PATH TO YOUR VIDEO FILE -->
    <source src="uploads/video/login.mp4" type="video/mp4">
    <!-- Alternative format for better compatibility -->
    <!-- <source src="videos/login-bg.webm" type="video/webm"> -->
    Your browser does not support the video tag.
</video>

<!-- Dark Overlay for readability -->
<div class="video-overlay"></div>

<!-- Login Card with Moving Neon Border -->
<div class="login-card">
    <h2><i class="fas fa-tshirt"></i> UHD-Wears</h2>
    
    <?php if($error): ?>
        <div class="alert alert-danger mb-3"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <form method="POST">
        <div class="mb-3">
            <label>Username or Email</label>
            <input type="text" name="username" class="form-control" required>
        </div>
        
        <div class="mb-3">
            <label>Password</label>
            <input type="password" name="password" class="form-control" required>
            <div class="mt-1">
                <a href="forgot-password.php">Forgot Password?</a>
            </div>
        </div>
        
        <div class="mb-3 form-check">
            <input type="checkbox" name="remember" class="form-check-input" id="remember">
            <label class="form-check-label" for="remember">Remember Me</label>
        </div>
        
        <button type="submit" class="btn-primary">Login</button>
    </form>
    
    <p class="text-center mt-3">
        Don't have an account? <a href="signup.php">Sign Up</a>
    </p>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="js/toast.js"></script>
<script>
// Check if video exists, if not, it will show the fallback
const video = document.querySelector('.video-background');
if (video) {
    video.addEventListener('error', function() {
        console.log('Video failed to load. Using fallback background.');
        this.style.display = 'none';
    });
}

// Form submission loading state
document.querySelector('form').addEventListener('submit', function(e) {
    const btn = document.querySelector('.btn-primary');
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Logging in...';
    btn.disabled = true;
});
</script>
</body>
</html>