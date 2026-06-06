<?php
// Get categories for navbar dropdown
$men_cats = mysqli_query($conn, "SELECT * FROM categories WHERE type = 'men' AND is_active = 1 ORDER BY display_order");
$women_cats = mysqli_query($conn, "SELECT * FROM categories WHERE type = 'women' AND is_active = 1 ORDER BY display_order");
$kids_cats = mysqli_query($conn, "SELECT * FROM categories WHERE type = 'kids' AND is_active = 1 ORDER BY display_order");

$has_men_cats = mysqli_num_rows($men_cats) > 0;
$has_women_cats = mysqli_num_rows($women_cats) > 0;
$has_kids_cats = mysqli_num_rows($kids_cats) > 0;
?>

<nav class="navbar navbar-expand-lg fixed-top">
    <div class="container">
        <!-- Logo -->
        <a class="navbar-brand" href="index.php">
            <i class="fas fa-tshirt"></i> UHD-WEARS
        </a>
        
        <!-- Mobile Toggle -->
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        
        <!-- Navbar Content -->
        <div class="collapse navbar-collapse" id="mainNav">
            <!-- Main Menu - Left Side -->
            <ul class="navbar-nav me-auto">
                <li class="nav-item">
                    <a class="nav-link" href="index.php">Home</a>
                </li>
                
                <!-- Men Dropdown - Hover to show -->
                <?php if($has_men_cats): ?>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="men.php">Men</a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="men.php">All Men's</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <?php while($cat = mysqli_fetch_assoc($men_cats)): ?>
                        <li><a class="dropdown-item" href="men.php?category=<?php echo urlencode($cat['name']); ?>"><?php echo htmlspecialchars($cat['name']); ?></a></li>
                        <?php endwhile; ?>
                    </ul>
                </li>
                <?php else: ?>
                <li class="nav-item">
                    <a class="nav-link" href="men.php">Men</a>
                </li>
                <?php endif; ?>
                
                <!-- Women Dropdown - Hover to show -->
                <?php if($has_women_cats): ?>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="women.php">Women</a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="women.php">All Women's</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <?php while($cat = mysqli_fetch_assoc($women_cats)): ?>
                        <li><a class="dropdown-item" href="women.php?category=<?php echo urlencode($cat['name']); ?>"><?php echo htmlspecialchars($cat['name']); ?></a></li>
                        <?php endwhile; ?>
                    </ul>
                </li>
                <?php else: ?>
                <li class="nav-item">
                    <a class="nav-link" href="women.php">Women</a>
                </li>
                <?php endif; ?>
                
                <!-- Kids Dropdown - Hover to show -->
                <?php if($has_kids_cats): ?>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="kids.php">Kids</a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="kids.php">All Kids'</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <?php while($cat = mysqli_fetch_assoc($kids_cats)): ?>
                        <li><a class="dropdown-item" href="kids.php?category=<?php echo urlencode($cat['name']); ?>"><?php echo htmlspecialchars($cat['name']); ?></a></li>
                        <?php endwhile; ?>
                    </ul>
                </li>
                <?php else: ?>
                <li class="nav-item">
                    <a class="nav-link" href="kids.php">Kids</a>
                </li>
                <?php endif; ?>
                
                <li class="nav-item">
                    <a class="nav-link" href="sale.php">Sale</a>
                </li>
            </ul>
            
            <!-- Right Side Icons -->
            <div class="d-flex align-items-center gap-3">
                <!-- Wishlist -->
                <a href="wishlist.php" class="nav-icon">
                    <i class="fas fa-heart"></i>
                    <span class="badge" id="wishlistCount">0</span>
                </a>
                
                <!-- Cart -->
                <a href="cart.php" class="nav-icon">
                    <i class="fas fa-shopping-cart"></i>
                    <span class="badge" id="cartCount">0</span>
                </a>
                
                <!-- Search -->
                <form class="search-form" action="search.php" method="GET">
                    <div class="search-wrapper">
                        <input type="text" name="q" placeholder="Search" autocomplete="off">
                        <button type="submit"><i class="fas fa-search"></i></button>
                    </div>
                </form>
                
                <!-- User Section -->
                <?php if(isset($_SESSION['user_id'])): ?>
                <div class="dropdown">
                    <button class="user-btn dropdown-toggle" data-bs-toggle="dropdown">
                        <i class="fas fa-user-circle"></i> <?php echo $_SESSION['username']; ?>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="user/dashboard.php">Dashboard</a></li>
                        <li><a class="dropdown-item" href="user/orders.php">My Orders</a></li>
                        <li><a class="dropdown-item" href="user/track-order.php">Track Order</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item text-danger" href="logout.php">Logout</a></li>
                    </ul>
                </div>
                <?php else: ?>
                <div class="d-flex gap-2">
                    <a href="login.php" class="btn-login">Login</a>
                    <a href="signup.php" class="btn-signup">Sign Up</a>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</nav>

<style>
/* ============================================
   NAVBAR - HOVER DROPDOWN, NO DOTS
   ============================================ */

.navbar {
    background: #0a0a0a !important;
    padding: 12px 0;
    border-bottom: 2px solid #FFD700;
}

.navbar-brand {
    font-size: 24px;
    font-weight: 700;
    color: #FFD700 !important;
    text-decoration: none;
}

.navbar-brand i {
    color: #FFD700;
    margin-right: 5px;
}

/* Navigation Links - NO UNDERLINE/DOTS */
.navbar-nav .nav-link {
    color: #ffffff !important;
    font-weight: 500;
    padding: 8px 16px;
    transition: all 0.3s;
    text-decoration: none !important;
    list-style: none !important;
}

.navbar-nav .nav-link:hover {
    color: #FFD700 !important;
}

/* Remove all dots, underlines, and pseudo-elements */
.navbar-nav, .navbar-nav li, .navbar-nav ul, 
.dropdown-menu, .dropdown-item, .nav-link, 
.navbar-nav .nav-link, .navbar-brand {
    text-decoration: none !important;
    list-style: none !important;
}

/* REMOVE WHITE DOTS - This removes the ::after pseudo-element */
.navbar-nav .nav-link::after,
.navbar-nav .nav-link::before,
.dropdown-toggle::after {
    display: none !important;
}

/* Dropdown - HOVER TO SHOW */
.dropdown:hover .dropdown-menu {
    display: block;
    margin-top: 0;
}

.dropdown-menu {
    background: #1a1a1a;
    border: 1px solid #2a2a2a;
    border-radius: 12px;
    margin-top: 10px;
    padding: 8px 0;
    min-width: 180px;
    display: none;
}

.dropdown-item {
    color: #ffffff !important;
    padding: 8px 20px;
    font-size: 14px;
    transition: all 0.3s;
    text-decoration: none !important;
}

.dropdown-item:hover {
    background: #FFD700 !important;
    color: #000000 !important;
}

.dropdown-divider {
    border-color: #2a2a2a;
    margin: 5px 0;
}

/* Icons */
.nav-icon {
    position: relative;
    color: #ffffff;
    font-size: 20px;
    transition: all 0.3s;
    text-decoration: none;
}

.nav-icon:hover {
    color: #FFD700;
    transform: translateY(-2px);
}

.nav-icon .badge {
    position: absolute;
    top: -10px;
    right: -12px;
    background: #FFD700;
    color: #000;
    font-size: 10px;
    font-weight: bold;
    padding: 2px 6px;
    border-radius: 50%;
}

/* Search Form */
.search-form {
    margin: 0;
}

.search-wrapper {
    position: relative;
    width: 200px;
}

.search-wrapper input {
    width: 100%;
    padding: 7px 35px 7px 15px;
    background: #1a1a1a;
    border: 1px solid #2a2a2a;
    border-radius: 30px;
    color: #fff;
    font-size: 13px;
}

.search-wrapper input:focus {
    outline: none;
    border-color: #FFD700;
}

.search-wrapper input::placeholder {
    color: #888;
}

.search-wrapper button {
    position: absolute;
    right: 5px;
    top: 50%;
    transform: translateY(-50%);
    background: none;
    border: none;
    color: #FFD700;
    cursor: pointer;
    padding: 5px 8px;
}

.search-wrapper button:hover {
    color: #fff;
}

/* User Button */
.user-btn {
    background: #1a1a1a;
    border: 1px solid #FFD700;
    color: #FFD700;
    padding: 6px 16px;
    border-radius: 30px;
    font-size: 13px;
    font-weight: 500;
    cursor: pointer;
}

.user-btn:hover {
    background: #FFD700;
    color: #000;
}

/* Login/Signup Buttons */
.btn-login {
    color: #fff;
    padding: 6px 16px;
    text-decoration: none;
    font-size: 13px;
    font-weight: 500;
    border-radius: 30px;
}

.btn-login:hover {
    background: rgba(255,215,0,0.1);
    color: #FFD700;
}

.btn-signup {
    background: #FFD700;
    color: #000;
    padding: 6px 16px;
    border-radius: 30px;
    text-decoration: none;
    font-size: 13px;
    font-weight: 600;
}

.btn-signup:hover {
    background: #ffc107;
    transform: translateY(-1px);
}

/* Mobile Responsive */
@media (max-width: 992px) {
    .navbar-collapse {
        background: #0a0a0a;
        padding: 15px;
        border-radius: 12px;
        margin-top: 10px;
    }
    
    .navbar-nav {
        margin-bottom: 10px;
    }
    
    .d-flex.align-items-center {
        flex-wrap: wrap;
        justify-content: center;
        gap: 12px !important;
        padding-top: 10px;
        border-top: 1px solid #2a2a2a;
    }
    
    .search-wrapper {
        width: 100%;
    }
    
    .search-form {
        width: 100%;
        margin: 5px 0;
    }
    
    /* On mobile, dropdown works by click not hover */
    .dropdown .dropdown-menu {
        display: none;
        position: static;
        float: none;
        width: auto;
        margin-top: 0;
        background-color: transparent;
        border: 0;
        box-shadow: none;
    }
    
    .dropdown .dropdown-menu.show {
        display: block;
    }
    
    .dropdown .dropdown-item {
        padding-left: 30px;
    }
}

@media (max-width: 576px) {
    .navbar-brand {
        font-size: 20px;
    }
    
    .navbar-nav .nav-link {
        padding: 6px 12px;
        font-size: 14px;
    }
}
</style>

<script>
function updateCounts() {
    $.ajax({url: 'get-cart-count.php', success: function(data) {$('#cartCount').text(data);}});
    $.ajax({url: 'get-wishlist-count.php', success: function(data) {$('#wishlistCount').text(data);}});
}
$(document).ready(function() { 
    updateCounts();
    
    // For mobile: enable dropdown toggle
    $('.dropdown-toggle').on('click', function(e) {
        if ($(window).width() <= 992) {
            e.preventDefault();
            $(this).next('.dropdown-menu').toggleClass('show');
        }
    });
});
</script>