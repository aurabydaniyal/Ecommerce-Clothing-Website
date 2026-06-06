<!-- Footer -->
<footer class="footer">
    <div class="container">
        <div class="row">
            <div class="col-md-4 mb-4">
                <h4>About UHD-Wears</h4>
                <p>Premium clothing brand offering the latest fashion for men, women, and kids. Quality and style guaranteed.</p>
                <div class="social-links mt-3">
                    <a href="#"><i class="fab fa-facebook-f"></i></a>
                    <a href="#"><i class="fab fa-instagram"></i></a>
                    <a href="#"><i class="fab fa-twitter"></i></a>
                    <a href="#"><i class="fab fa-youtube"></i></a>
                </div>
            </div>
            <div class="col-md-4 mb-4">
                <h4>Quick Links</h4>
                <ul>
                    <li><a href="index.php">Home</a></li>
                    <li><a href="men.php">Men</a></li>
                    <li><a href="women.php">Women</a></li>
                    <li><a href="kids.php">Kids</a></li>
                    <li><a href="sale.php">Sale</a></li>
                </ul>
            </div>
            <div class="col-md-4 mb-4">
                <h4>Contact Info</h4>
                <p><i class="fas fa-map-marker-alt"></i> Gulberg, Lahore, Pakistan</p>
                <p><i class="fas fa-phone"></i> +92 3XX XXXXXXX</p>
                <p><i class="fas fa-envelope"></i> info@uhdwears.com</p>
                <p><i class="fas fa-clock"></i> Mon-Sat: 10AM - 8PM</p>
            </div>
        </div>
        <div class="text-center mt-4 pt-3 border-top border-secondary">
            <p>&copy; 2024 UHD-Wears. All rights reserved. | Designed for fashion lovers</p>
        </div>
    </div>
</footer>

<script>
// Update cart and wishlist counts on all pages
function updateCartCount() {
    $.ajax({url: 'get-cart-count.php', success: function(data) {$('#cartCount').text(data);}});
}
function updateWishlistCount() {
    $.ajax({url: 'get-wishlist-count.php', success: function(data) {$('#wishlistCount').text(data);}});
}
$(document).ready(function() {
    updateCartCount();
    updateWishlistCount();
});
</script>