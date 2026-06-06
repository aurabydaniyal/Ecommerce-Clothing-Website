<?php
session_start();
require_once 'db_connection.php';

$step = isset($_GET['step']) ? $_GET['step'] : 'request';
$message = '';
$error = '';

// Step 1: Request password reset
if($step == 'request' && $_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    
    // Check if email exists
    $check = mysqli_query($conn, "SELECT id, username FROM users WHERE email = '$email'");
    
    if(mysqli_num_rows($check) == 1) {
        $user = mysqli_fetch_assoc($check);
        
        // Generate unique token
        $token = bin2hex(random_bytes(32));
        $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
        
        // Save token in database
        mysqli_query($conn, "UPDATE users SET reset_token = '$token', reset_expires = '$expires' WHERE id = '{$user['id']}'");
        
        // For demo, show reset link directly
        $reset_link = "http://localhost/uhd-wears/forgot-password.php?step=reset&token=" . $token;
        
        $message = "
            <div class='alert alert-success'>
                <i class='fas fa-envelope'></i> 
                <strong>Password Reset Link Generated!</strong><br>
                Click the link below to reset your password:<br>
                <a href='$reset_link' class='reset-link'>$reset_link</a>
                <br><small class='text-muted'>In production, this would be sent to your email.</small>
            </div>
        ";
    } else {
        $error = "Email address not found in our records.";
    }
}

// Step 2: Reset password with token
if($step == 'reset' && isset($_GET['token'])) {
    $token = mysqli_real_escape_string($conn, $_GET['token']);
    $current_time = date('Y-m-d H:i:s');
    
    // Check if token is valid
    $check = mysqli_query($conn, "SELECT id, username FROM users WHERE reset_token = '$token' AND reset_expires > '$current_time'");
    
    if(mysqli_num_rows($check) == 1) {
        $user = mysqli_fetch_assoc($check);
        
        if($_SERVER['REQUEST_METHOD'] == 'POST') {
            $new_password = $_POST['new_password'];
            $confirm_password = $_POST['confirm_password'];
            
            if($new_password !== $confirm_password) {
                $error = "Passwords do not match!";
            } elseif(strlen($new_password) < 6) {
                $error = "Password must be at least 6 characters!";
            } else {
                // Hash new password
                $hashed = password_hash($new_password, PASSWORD_DEFAULT);
                
                // Update password and clear token
                mysqli_query($conn, "UPDATE users SET password = '$hashed', reset_token = NULL, reset_expires = NULL WHERE id = '{$user['id']}'");
                
                $message = "
                    <div class='alert alert-success'>
                        <i class='fas fa-check-circle'></i> 
                        <strong>Password Reset Successful!</strong><br>
                        Your password has been changed. Redirecting to login...
                    </div>
                ";
                echo "<meta http-equiv='refresh' content='3;url=login.php'>";
            }
        }
        ?>
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Reset Password - UHD-Wears</title>
            <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
            <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
            <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
            <style>
                * { margin: 0; padding: 0; box-sizing: border-box; }
                body {
                    font-family: 'Poppins', sans-serif;
                    background: linear-gradient(135deg, #0a0a0a 0%, #1a1a1a 100%);
                    min-height: 100vh;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    padding: 20px;
                }
                .reset-card {
                    background: #1a1a1a;
                    border-radius: 20px;
                    padding: 40px;
                    width: 100%;
                    max-width: 500px;
                    box-shadow: 0 25px 50px rgba(0,0,0,0.5);
                    border: 1px solid rgba(255,215,0,0.2);
                    animation: slideUp 0.6s ease-out;
                }
                @keyframes slideUp {
                    from { opacity: 0; transform: translateY(50px); }
                    to { opacity: 1; transform: translateY(0); }
                }
                .reset-card h2 {
                    text-align: center;
                    background: linear-gradient(135deg, #FFD700 0%, #FFC107 100%);
                    -webkit-background-clip: text;
                    -webkit-text-fill-color: transparent;
                    background-clip: text;
                    margin-bottom: 10px;
                    font-weight: 700;
                }
                .reset-card h4 {
                    text-align: center;
                    color: #fff;
                    margin-bottom: 30px;
                    font-size: 16px;
                    font-weight: 400;
                }
                .form-control {
                    background: #2a2a2a;
                    border: 1px solid #3a3a3a;
                    color: #fff;
                    padding: 12px 15px;
                    border-radius: 12px;
                    transition: all 0.3s;
                }
                .form-control:focus {
                    border-color: #FFD700;
                    outline: none;
                    box-shadow: 0 0 0 3px rgba(255,215,0,0.1);
                }
                .form-control::placeholder {
                    color: #888;
                }
                .btn-reset {
                    background: linear-gradient(135deg, #FFD700 0%, #FFC107 100%);
                    border: none;
                    padding: 12px;
                    width: 100%;
                    border-radius: 12px;
                    font-weight: 600;
                    color: #0a0a0a;
                    transition: all 0.3s;
                    cursor: pointer;
                    font-size: 16px;
                }
                .btn-reset:hover {
                    transform: translateY(-2px);
                    box-shadow: 0 5px 20px rgba(255,215,0,0.3);
                }
                .btn-back {
                    background: transparent;
                    border: 1px solid #FFD700;
                    color: #FFD700;
                    padding: 12px;
                    width: 100%;
                    border-radius: 12px;
                    font-weight: 600;
                    transition: all 0.3s;
                    text-decoration: none;
                    display: block;
                    text-align: center;
                }
                .btn-back:hover {
                    background: #FFD700;
                    color: #000;
                }
                .alert {
                    padding: 15px;
                    border-radius: 12px;
                    margin-bottom: 20px;
                    font-size: 14px;
                }
                .alert-success {
                    background: rgba(40,167,69,0.2);
                    color: #6bff8a;
                    border: 1px solid rgba(40,167,69,0.3);
                }
                .alert-danger {
                    background: rgba(220,53,69,0.2);
                    color: #ff6b6b;
                    border: 1px solid rgba(220,53,69,0.3);
                }
                .input-group-text {
                    background: #2a2a2a;
                    border: 1px solid #3a3a3a;
                    color: #FFD700;
                }
                .back-to-login {
                    text-align: center;
                    margin-top: 20px;
                }
                .back-to-login a {
                    color: #FFD700;
                    text-decoration: none;
                }
                .back-to-login a:hover {
                    text-decoration: underline;
                }
            </style>
        </head>
        <body>
        <div class="reset-card">
            <h2><i class="fas fa-key"></i> Reset Password</h2>
            <h4>Create a new password for <span style="color:#FFD700;"><?php echo htmlspecialchars($user['username']); ?></span></h4>
            
            <?php if($message): ?>
                <?php echo $message; ?>
            <?php else: ?>
                <?php if($error): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <form method="POST">
                    <div class="mb-3">
                        <label style="color:#fff; margin-bottom: 8px; display: block;">New Password</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-lock"></i></span>
                            <input type="password" name="new_password" class="form-control" placeholder="Enter new password" required minlength="6">
                        </div>
                        <small class="text-muted">Minimum 6 characters</small>
                    </div>
                    <div class="mb-4">
                        <label style="color:#fff; margin-bottom: 8px; display: block;">Confirm Password</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-check-circle"></i></span>
                            <input type="password" name="confirm_password" class="form-control" placeholder="Confirm your password" required>
                        </div>
                    </div>
                    <button type="submit" class="btn-reset">Reset Password</button>
                </form>
            <?php endif; ?>
            
            <div class="back-to-login">
                <a href="login.php"><i class="fas fa-arrow-left"></i> Back to Login</a>
            </div>
        </div>
        </body>
        </html>
        <?php
        exit();
    } else {
        $error = "Invalid or expired reset link. Please request a new password reset.";
        $step = 'request';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - UHD-Wears</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #0a0a0a 0%, #1a1a1a 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .forgot-card {
            background: #1a1a1a;
            border-radius: 20px;
            padding: 40px;
            width: 100%;
            max-width: 500px;
            box-shadow: 0 25px 50px rgba(0,0,0,0.5);
            border: 1px solid rgba(255,215,0,0.2);
            animation: fadeInUp 0.6s ease-out;
        }
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(50px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .forgot-card h2 {
            text-align: center;
            background: linear-gradient(135deg, #FFD700 0%, #FFC107 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 10px;
            font-weight: 700;
        }
        .forgot-card h4 {
            text-align: center;
            color: #fff;
            margin-bottom: 30px;
            font-size: 16px;
            font-weight: 400;
        }
        .form-control {
            background: #2a2a2a;
            border: 1px solid #3a3a3a;
            color: #fff;
            padding: 12px 15px;
            border-radius: 12px;
            transition: all 0.3s;
        }
        .form-control:focus {
            border-color: #FFD700;
            outline: none;
            box-shadow: 0 0 0 3px rgba(255,215,0,0.1);
        }
        .form-control::placeholder {
            color: #888;
        }
        .btn-send {
            background: linear-gradient(135deg, #FFD700 0%, #FFC107 100%);
            border: none;
            padding: 12px;
            width: 100%;
            border-radius: 12px;
            font-weight: 600;
            color: #0a0a0a;
            transition: all 0.3s;
            cursor: pointer;
            font-size: 16px;
        }
        .btn-send:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(255,215,0,0.3);
        }
        .alert {
            padding: 15px;
            border-radius: 12px;
            margin-bottom: 20px;
            font-size: 14px;
        }
        .alert-success {
            background: rgba(40,167,69,0.2);
            color: #6bff8a;
            border: 1px solid rgba(40,167,69,0.3);
        }
        .alert-danger {
            background: rgba(220,53,69,0.2);
            color: #ff6b6b;
            border: 1px solid rgba(220,53,69,0.3);
        }
        .reset-link {
            color: #FFD700;
            word-break: break-all;
            text-decoration: none;
            display: inline-block;
            margin-top: 10px;
        }
        .reset-link:hover {
            text-decoration: underline;
        }
        .input-group-text {
            background: #2a2a2a;
            border: 1px solid #3a3a3a;
            color: #FFD700;
        }
        .back-to-login {
            text-align: center;
            margin-top: 20px;
        }
        .back-to-login a {
            color: #FFD700;
            text-decoration: none;
        }
        .back-to-login a:hover {
            text-decoration: underline;
        }
        .info-text {
            text-align: center;
            color: #888;
            font-size: 13px;
            margin-top: 15px;
        }
    </style>
</head>
<body>
<div class="forgot-card">
    <h2><i class="fas fa-lock"></i> Forgot Password?</h2>
    <h4>Don't worry! Enter your email and we'll help you reset it.</h4>
    
    <?php if($message): ?>
        <?php echo $message; ?>
        <div class="back-to-login mt-3">
            <a href="login.php"><i class="fas fa-arrow-left"></i> Back to Login</a>
        </div>
    <?php else: ?>
        <?php if($error): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-triangle"></i> <?php echo $error; ?>
            </div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="mb-4">
                <label style="color:#fff; margin-bottom: 8px; display: block;">Email Address</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                    <input type="email" name="email" class="form-control" placeholder="Enter your registered email" required>
                </div>
            </div>
            <button type="submit" class="btn-send">
                <i class="fas fa-paper-plane"></i> Send Reset Link
            </button>
        </form>
        
        <div class="back-to-login">
            <a href="login.php"><i class="fas fa-arrow-left"></i> Back to Login</a>
        </div>
        <div class="info-text">
            <i class="fas fa-info-circle"></i> You'll receive a link to reset your password
        </div>
    <?php endif; ?>
</div>
</body>
</html>