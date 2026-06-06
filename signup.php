<?php
session_start();
require_once 'db_connection.php';

if(isset($_SESSION['user_id'])) { header('Location: index.php'); exit(); }

$response = ['success' => false, 'message' => ''];

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $password = $_POST['password'];
    $confirm = $_POST['confirm_password'];
    $full_name = mysqli_real_escape_string($conn, $_POST['full_name']);
    $phone = mysqli_real_escape_string($conn, $_POST['phone']);
    
    // First check if username or email already exists
    $check_sql = "SELECT id, username, email FROM users WHERE username = '$username' OR email = '$email'";
    $check_result = mysqli_query($conn, $check_sql);
    
    $username_exists = false;
    $email_exists = false;
    
    while($row = mysqli_fetch_assoc($check_result)) {
        if($row['username'] == $username) {
            $username_exists = true;
        }
        if($row['email'] == $email) {
            $email_exists = true;
        }
    }
    
    // Validation
    if(!preg_match('/^03[0-9]{9}$/', $phone)) {
        $response['message'] = 'Phone must start with 03 and be 11 digits (e.g., 03123456789)';
    } elseif($password !== $confirm) {
        $response['message'] = 'Passwords do not match!';
    } elseif(strlen($password) < 6) {
        $response['message'] = 'Password must be at least 6 characters!';
    } elseif($username_exists) {
        $response['message'] = 'Username "' . htmlspecialchars($username) . '" is already taken!';
    } elseif($email_exists) {
        $response['message'] = 'Email "' . htmlspecialchars($email) . '" is already registered!';
    } else {
        $hashed = password_hash($password, PASSWORD_DEFAULT);
        $sql = "INSERT INTO users (username, email, password, full_name, phone) VALUES ('$username', '$email', '$hashed', '$full_name', '$phone')";
        if(mysqli_query($conn, $sql)) {
            $response['success'] = true;
            $response['message'] = 'Account created successfully!';
        } else { 
            $response['message'] = 'Registration failed! Please try again.'; 
        }
    }
    
    // Return JSON response for AJAX
    header('Content-Type: application/json');
    echo json_encode($response);
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up - UHD-Wears</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
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
            overflow-x: hidden;
            padding: 50px 0;
        }
        
        .video-background {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -2;
            object-fit: cover;
        }
        
        .video-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.65);
            z-index: -1;
        }
        
        .signup-card {
            background: rgba(26, 26, 26, 0.95);
            border-radius: 20px;
            padding: 40px;
            max-width: 500px;
            width: 100%;
            position: relative;
            animation: slideUp 0.6s;
            z-index: 1;
        }
        
        .signup-card::before {
            content: '';
            position: absolute;
            top: -2px;
            left: -2px;
            right: -2px;
            bottom: -2px;
            background: linear-gradient(45deg, #FFD700, #FFC107, #FFD700, #FFC107, #FFD700, #FFC107, #FFD700);
            background-size: 300% 300%;
            border-radius: 22px;
            z-index: -1;
            animation: neonMove 3s ease infinite;
            filter: blur(8px);
            opacity: 0.7;
        }
        
        .signup-card::after {
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
            0% { background-position: 0% 50%; opacity: 0.5; }
            50% { background-position: 100% 50%; opacity: 1; }
            100% { background-position: 0% 50%; opacity: 0.5; }
        }
        
        @keyframes slideUp {
            from { opacity: 0; transform: translateY(50px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .signup-card h2 { 
            text-align: center; 
            background: linear-gradient(135deg, #FFD700, #FFC107); 
            -webkit-background-clip: text; 
            -webkit-text-fill-color: transparent; 
            margin-bottom: 30px; 
        }
        
        .signup-card h2 i { margin-right: 10px; }
        
        .form-control { 
            background: #2a2a2a; 
            border: 1px solid #3a3a3a; 
            color: #fff; 
            padding: 12px; 
            border-radius: 10px; 
            transition: all 0.3s;
            width: 100%;
        }
        
        .form-control:focus { 
            border-color: #FFD700; 
            box-shadow: 0 0 0 3px rgba(255,215,0,0.1); 
            outline: none;
        }
        
        .form-control.error {
            border-color: #ff3366;
            box-shadow: 0 0 5px #ff3366, 0 0 10px rgba(255,51,102,0.3);
        }
        
        .error-message {
            color: #ff3366 !important;
            font-size: 11px;
            margin-top: 5px;
            display: block;
        }
        
        .success-message {
            color: #6bff8a !important;
            font-size: 11px;
            margin-top: 5px;
            display: block;
        }
        
        .form-control::placeholder { color: #666; }
        
        .btn-primary { 
            background: linear-gradient(135deg, #FFD700, #FFC107); 
            border: none; 
            padding: 12px; 
            width: 100%; 
            border-radius: 10px; 
            font-weight: 600; 
            color: #0a0a0a; 
            transition: all 0.3s; 
            cursor: pointer;
        }
        
        .btn-primary:hover { 
            transform: translateY(-2px); 
            box-shadow: 0 5px 15px rgba(255,215,0,0.4); 
        }
        
        .btn-primary:disabled {
            opacity: 0.7;
            cursor: not-allowed;
        }
        
        a { color: #FFD700; text-decoration: none; transition: color 0.3s; }
        a:hover { color: #ffc107; text-decoration: underline; }
        
        .phone-hint, .password-hint { 
            font-size: 11px; 
            color: #888; 
            margin-top: 5px; 
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .phone-hint i, .password-hint i { color: #FFD700; }
        
        label { color: #e0e0e0; font-size: 14px; margin-bottom: 8px; display: block; }
        label i { color: #FFD700; margin-right: 6px; }
        
        @media (max-width: 576px) {
            .signup-card { padding: 30px 25px; margin: 20px; max-width: calc(100% - 40px); }
            .signup-card h2 { font-size: 24px; margin-bottom: 25px; }
            .form-control { padding: 10px; font-size: 14px; }
            .btn-primary { padding: 10px; font-size: 14px; }
            label { font-size: 13px; }
            .phone-hint, .password-hint { font-size: 10px; }
        }
    </style>
</head>
<body>

<video class="video-background" autoplay loop muted playsinline poster="images/login-poster.jpg">
    <source src="uploads/video/sign.mp4" type="video/mp4">
    Your browser does not support the video tag.
</video>

<div class="video-overlay"></div>

<div class="signup-card">
    <h2><i class="fas fa-user-plus"></i> Create Account</h2>
    
    <form method="POST" id="signupForm">
        <div class="mb-3">
            <label><i class="fas fa-user"></i> Full Name</label>
            <input type="text" name="full_name" id="full_name" class="form-control" placeholder="Enter your full name" required>
            <div class="error-message" id="fullNameError" style="display: none;"></div>
        </div>
        
        <div class="mb-3">
            <label><i class="fas fa-at"></i> Username</label>
            <input type="text" name="username" id="username" class="form-control" placeholder="Choose a username" required>
            <div class="error-message" id="usernameError" style="display: none;"></div>
            <div class="success-message" id="usernameSuccess" style="display: none;"></div>
        </div>
        
        <div class="mb-3">
            <label><i class="fas fa-envelope"></i> Email</label>
            <input type="email" name="email" id="email" class="form-control" placeholder="Enter your email address" required>
            <div class="error-message" id="emailError" style="display: none;"></div>
            <div class="success-message" id="emailSuccess" style="display: none;"></div>
        </div>
        
        <div class="mb-3">
            <label><i class="fas fa-phone"></i> Phone Number</label>
            <input type="tel" name="phone" id="phone" class="form-control" placeholder="03XXXXXXXXX" required>
            <div class="error-message" id="phoneError" style="display: none;"></div>
            <div class="phone-hint">
                <i class="fas fa-info-circle"></i> Must start with 03 and be exactly 11 digits (e.g., 03123456789)
            </div>
        </div>
        
        <div class="mb-3">
            <label><i class="fas fa-lock"></i> Password</label>
            <input type="password" name="password" id="password" class="form-control" placeholder="Create a password" required>
            <div class="error-message" id="passwordError" style="display: none;"></div>
            <div class="password-hint">
                <i class="fas fa-info-circle"></i> Password must be at least 6 characters long
            </div>
        </div>
        
        <div class="mb-3">
            <label><i class="fas fa-check-circle"></i> Confirm Password</label>
            <input type="password" name="confirm_password" id="confirm_password" class="form-control" placeholder="Confirm your password" required>
            <div class="error-message" id="confirmError" style="display: none;"></div>
        </div>
        
        <button type="submit" class="btn-primary">Sign Up</button>
    </form>
    
    <p class="text-center mt-3">
        Already have an account? <a href="login.php">Login</a>
    </p>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Live username check
let usernameTimeout;
$('#username').on('input', function() {
    clearTimeout(usernameTimeout);
    const username = $(this).val();
    const errorDiv = $('#usernameError');
    const successDiv = $('#usernameSuccess');
    
    if(username.length < 3) {
        errorDiv.hide().empty();
        successDiv.hide().empty();
        $('#username').removeClass('error');
        return;
    }
    
    usernameTimeout = setTimeout(function() {
        $.ajax({
            url: 'check-availability.php',
            method: 'POST',
            data: { type: 'username', value: username },
            dataType: 'json',
            success: function(response) {
                if(response.exists) {
                    errorDiv.html('<i class="fas fa-times-circle"></i> Username already taken!').show();
                    successDiv.hide();
                    $('#username').addClass('error');
                } else {
                    successDiv.html('<i class="fas fa-check-circle"></i> Username available!').show();
                    errorDiv.hide();
                    $('#username').removeClass('error');
                }
            }
        });
    }, 500);
});

// Live email check
let emailTimeout;
$('#email').on('input', function() {
    clearTimeout(emailTimeout);
    const email = $(this).val();
    const errorDiv = $('#emailError');
    const successDiv = $('#emailSuccess');
    
    if(email.length < 5 || !email.includes('@')) {
        errorDiv.hide().empty();
        successDiv.hide().empty();
        $('#email').removeClass('error');
        return;
    }
    
    emailTimeout = setTimeout(function() {
        $.ajax({
            url: 'check-availability.php',
            method: 'POST',
            data: { type: 'email', value: email },
            dataType: 'json',
            success: function(response) {
                if(response.exists) {
                    errorDiv.html('<i class="fas fa-times-circle"></i> Email already registered!').show();
                    successDiv.hide();
                    $('#email').addClass('error');
                } else {
                    successDiv.html('<i class="fas fa-check-circle"></i> Email available!').show();
                    errorDiv.hide();
                    $('#email').removeClass('error');
                }
            }
        });
    }, 500);
});

// Remove error on focus
$('#full_name, #username, #email, #phone, #password, #confirm_password').on('focus', function() {
    $(this).removeClass('error');
    $(`#${this.id}Error`).hide().empty();
});

// Form submission
document.getElementById('signupForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    // Reset errors
    $('.form-control').removeClass('error');
    $('.error-message').hide().empty();
    $('.success-message').hide().empty();
    
    const fullName = $('#full_name').val().trim();
    const username = $('#username').val().trim();
    const email = $('#email').val().trim();
    const phone = $('#phone').val().trim();
    const password = $('#password').val();
    const confirm = $('#confirm_password').val();
    
    let hasError = false;
    
    if(fullName === '') {
        $('#fullNameError').html('<i class="fas fa-exclamation-circle"></i> Please enter your full name!').show();
        $('#full_name').addClass('error');
        hasError = true;
    }
    
    if(username === '') {
        $('#usernameError').html('<i class="fas fa-exclamation-circle"></i> Please choose a username!').show();
        $('#username').addClass('error');
        hasError = true;
    } else if(username.length < 3) {
        $('#usernameError').html('<i class="fas fa-exclamation-circle"></i> Username must be at least 3 characters!').show();
        $('#username').addClass('error');
        hasError = true;
    }
    
    if(email === '') {
        $('#emailError').html('<i class="fas fa-exclamation-circle"></i> Please enter your email address!').show();
        $('#email').addClass('error');
        hasError = true;
    } else if(!email.includes('@') || !email.includes('.')) {
        $('#emailError').html('<i class="fas fa-exclamation-circle"></i> Please enter a valid email address!').show();
        $('#email').addClass('error');
        hasError = true;
    }
    
    const phoneRegex = /^03[0-9]{9}$/;
    if(phone === '') {
        $('#phoneError').html('<i class="fas fa-exclamation-circle"></i> Please enter your phone number!').show();
        $('#phone').addClass('error');
        hasError = true;
    } else if(!phoneRegex.test(phone)) {
        $('#phoneError').html('<i class="fas fa-exclamation-circle"></i> Phone must start with 03 and be 11 digits!').show();
        $('#phone').addClass('error');
        hasError = true;
    }
    
    if(password === '') {
        $('#passwordError').html('<i class="fas fa-exclamation-circle"></i> Please create a password!').show();
        $('#password').addClass('error');
        hasError = true;
    } else if(password.length < 6) {
        $('#passwordError').html('<i class="fas fa-exclamation-circle"></i> Password must be at least 6 characters!').show();
        $('#password').addClass('error');
        hasError = true;
    }
    
    if(confirm === '') {
        $('#confirmError').html('<i class="fas fa-exclamation-circle"></i> Please confirm your password!').show();
        $('#confirm_password').addClass('error');
        hasError = true;
    } else if(password !== confirm) {
        $('#confirmError').html('<i class="fas fa-exclamation-circle"></i> Passwords do not match!').show();
        $('#confirm_password').addClass('error');
        hasError = true;
    }
    
    if(hasError) {
        $('html, body').animate({ scrollTop: $('.form-control.error:first').offset().top - 100 }, 500);
        return false;
    }
    
    // Show loading
    Swal.fire({
        title: 'Creating Account...',
        text: 'Please wait',
        allowOutsideClick: false,
        didOpen: () => { Swal.showLoading(); },
        background: '#1a1a1a',
        color: '#fff'
    });
    
    $.ajax({
        url: 'signup.php',
        method: 'POST',
        data: $(this).serialize(),
        dataType: 'json',
        success: function(response) {
            if(response.success) {
                Swal.fire({
                    icon: 'success',
                    title: 'Account Created! 🎉',
                    html: 'Your account has been created successfully!<br>Redirecting to login page...',
                    background: '#1a1a1a',
                    color: '#fff',
                    confirmButtonColor: '#FFD700',
                    timer: 2000,
                    showConfirmButton: false
                }).then(() => {
                    window.location.href = 'login.php';
                });
            } else {
                let errorMsg = response.message;
                
                if(errorMsg.includes('Username')) {
                    $('#usernameError').html('<i class="fas fa-exclamation-circle"></i> ' + errorMsg).show();
                    $('#username').addClass('error');
                } else if(errorMsg.includes('Email')) {
                    $('#emailError').html('<i class="fas fa-exclamation-circle"></i> ' + errorMsg).show();
                    $('#email').addClass('error');
                } else if(errorMsg.includes('Phone')) {
                    $('#phoneError').html('<i class="fas fa-exclamation-circle"></i> ' + errorMsg).show();
                    $('#phone').addClass('error');
                } else if(errorMsg.includes('Passwords')) {
                    $('#confirmError').html('<i class="fas fa-exclamation-circle"></i> ' + errorMsg).show();
                    $('#confirm_password').addClass('error');
                } else if(errorMsg.includes('Password')) {
                    $('#passwordError').html('<i class="fas fa-exclamation-circle"></i> ' + errorMsg).show();
                    $('#password').addClass('error');
                }
                
                Swal.fire({
                    icon: 'error',
                    title: 'Registration Failed',
                    html: errorMsg,
                    background: '#1a1a1a',
                    color: '#fff',
                    confirmButtonColor: '#FFD700'
                });
            }
        },
        error: function() {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Something went wrong. Please try again.',
                background: '#1a1a1a',
                color: '#fff',
                confirmButtonColor: '#FFD700'
            });
        }
    });
});
</script>
</body>
</html>