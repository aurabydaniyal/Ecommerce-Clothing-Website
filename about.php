<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About Us - UHD-Wears</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark fixed-top">
    <div class="container">
        <a class="navbar-brand" href="index.php"><i class="fas fa-tshirt"></i> UHD-Wears</a>
        <?php include 'includes/nav-menu.php'; ?>
    </div>
</nav>

<div class="container mt-5 pt-4">
    <div class="row">
        <div class="col-md-6">
            <h2>About UHD-Wears</h2>
            <p class="lead">Premium clothing brand for the modern generation.</p>
            <p>Founded in 2024, UHD-Wears has quickly become a leading name in fashion, offering high-quality clothing for men, women, and kids. Our mission is to provide stylish, comfortable, and affordable fashion to everyone.</p>
            <p>We believe that fashion is a form of self-expression, and we're committed to bringing you the latest trends with the best quality materials.</p>
            <h4 class="mt-4">Why Choose Us?</h4>
            <ul>
                <li><i class="fas fa-check-circle text-success"></i> Premium Quality Materials</li>
                <li><i class="fas fa-check-circle text-success"></i> Free Shipping on Orders $50+</li>
                <li><i class="fas fa-check-circle text-success"></i> 30-Day Return Policy</li>
                <li><i class="fas fa-check-circle text-success"></i> 24/7 Customer Support</li>
            </ul>
        </div>
        <div class="col-md-6">
            <img src="https://via.placeholder.com/500x400" class="img-fluid rounded">
        </div>
    </div>
</div>

<footer class="footer mt-5">
    <div class="container">
        <div class="text-center">
            <p>&copy; 2024 UHD-Wears. All rights reserved.</p>
        </div>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>