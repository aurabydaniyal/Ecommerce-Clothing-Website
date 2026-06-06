<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Us - UHD-Wears</title>
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
            <h2>Contact Us</h2>
            <p>Have questions? We'd love to hear from you!</p>
            
            <div class="mt-4">
                <h5><i class="fas fa-map-marker-alt text-primary"></i> Our Location</h5>
                <p>123 Fashion Street, New York, NY 10001, USA</p>
                
                <h5><i class="fas fa-phone text-primary"></i> Phone</h5>
                <p>+1 (234) 567-8900</p>
                
                <h5><i class="fas fa-envelope text-primary"></i> Email</h5>
                <p>info@uhdwears.com</p>
                
                <h5><i class="fas fa-clock text-primary"></i> Business Hours</h5>
                <p>Monday - Friday: 9:00 AM - 6:00 PM<br>
                Saturday: 10:00 AM - 4:00 PM<br>
                Sunday: Closed</p>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h4>Send us a Message</h4>
                </div>
                <div class="card-body">
                    <form>
                        <div class="mb-3">
                            <label>Your Name</label>
                            <input type="text" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label>Your Email</label>
                            <input type="email" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label>Subject</label>
                            <input type="text" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label>Message</label>
                            <textarea class="form-control" rows="5" required></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary">Send Message</button>
                    </form>
                </div>
            </div>
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