<?php
function uploadImage($file, $folder = 'products') {
    $target_dir = $_SERVER['DOCUMENT_ROOT'] . "/uhd-wears/uploads/$folder/";
    
    // Create folder if not exists
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0777, true);
    }
    
    $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    
    if (!in_array($file_extension, $allowed_extensions)) {
        return ['error' => 'Only JPG, PNG, GIF files are allowed'];
    }
    
    if ($file['size'] > 5000000) {
        return ['error' => 'File size must be less than 5MB'];
    }
    
    $new_filename = time() . '_' . uniqid() . '.' . $file_extension;
    $target_file = $target_dir . $new_filename;
    
    if (move_uploaded_file($file['tmp_name'], $target_file)) {
        return ['success' => true, 'path' => "/uhd-wears/uploads/$folder/$new_filename"];
    } else {
        return ['error' => 'Failed to upload file'];
    }
}
?>