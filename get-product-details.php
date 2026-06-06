<?php
session_start();
require_once 'db_connection.php';

if(isset($_POST['product_id'])) {
    $product_id = intval($_POST['product_id']);
    $result = mysqli_query($conn, "SELECT * FROM products WHERE id = '$product_id'");
    
    if($product = mysqli_fetch_assoc($result)) {
        // Collect all images
        $images = [$product['image_url']];
        if(!empty($product['image2'])) $images[] = $product['image2'];
        if(!empty($product['image3'])) $images[] = $product['image3'];
        
        // Parse colors and sizes
        $colors = !empty($product['colors']) ? explode(',', $product['colors']) : [];
        $sizes = !empty($product['sizes']) ? explode(',', $product['sizes']) : [];
        
        $response = [
            'success' => true,
            'id' => $product['id'],
            'name' => $product['name'],
            'description' => $product['description'],
            'price' => $product['price'],
            'sale_price' => $product['sale_price'],
            'image_url' => $product['image_url'],
            'images' => $images,
            'colors' => $colors,
            'sizes' => $sizes,
            'rating' => $product['rating'],
            'stock' => $product['stock']
        ];
        
        echo json_encode($response);
    } else {
        echo json_encode(['success' => false]);
    }
}
?>