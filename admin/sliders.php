<?php
session_start();
require_once '../db_connection.php';

if(!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'admin') {
    header('Location: ../login.php');
    exit();
}

// Get current slider mode (image or video)
$mode_result = mysqli_query($conn, "SELECT slider_type FROM slider_settings LIMIT 1");
$current_mode = mysqli_fetch_assoc($mode_result)['slider_type'] ?? 'image';

// Update slider mode
if(isset($_POST['update_mode'])) {
    $new_mode = mysqli_real_escape_string($conn, $_POST['slider_mode']);
    mysqli_query($conn, "UPDATE slider_settings SET slider_type = '$new_mode'");
    header('Location: sliders.php?mode_updated=1');
    exit();
}

// Add slider (supports both image and video)
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_slider'])) {
    $title = mysqli_real_escape_string($conn, $_POST['title']);
    $subtitle = mysqli_real_escape_string($conn, $_POST['subtitle']);
    $button_text = mysqli_real_escape_string($conn, $_POST['button_text']);
    $button_link = mysqli_real_escape_string($conn, $_POST['button_link']);
    $order_position = $_POST['order_position'];
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    $media_type = $current_mode; // Use current mode as media type
    
    $target_dir = "../uploads/sliders/";
    if(!file_exists($target_dir)) mkdir($target_dir, 0777, true);
    
    $image_path = '';
    $video_url = '';
    
    if($current_mode == 'image') {
        // Handle image upload
        if(isset($_FILES['slider_image']) && $_FILES['slider_image']['error'] == 0) {
            $filename = time() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '', $_FILES['slider_image']['name']);
            move_uploaded_file($_FILES['slider_image']['tmp_name'], $target_dir . $filename);
            $image_path = "uploads/sliders/" . $filename;
        }
    } else {
        // Handle video upload
        if(isset($_FILES['slider_video']) && $_FILES['slider_video']['error'] == 0) {
            $video_ext = pathinfo($_FILES['slider_video']['name'], PATHINFO_EXTENSION);
            $filename = time() . '_video_' . preg_replace('/[^a-zA-Z0-9._-]/', '', pathinfo($_FILES['slider_video']['name'], PATHINFO_FILENAME)) . '.' . $video_ext;
            move_uploaded_file($_FILES['slider_video']['tmp_name'], $target_dir . $filename);
            $video_url = "uploads/sliders/" . $filename;
        }
    }
    
    $sql = "INSERT INTO sliders (title, subtitle, image_path, video_url, media_type, button_text, button_link, order_position, is_active) 
            VALUES ('$title', '$subtitle', '$image_path', '$video_url', '$media_type', '$button_text', '$button_link', '$order_position', '$is_active')";
    
    if(mysqli_query($conn, $sql)) {
        header('Location: sliders.php?added=1');
        exit();
    }
}

// Delete slider
if(isset($_GET['delete'])) {
    $id = $_GET['delete'];
    mysqli_query($conn, "DELETE FROM sliders WHERE id = '$id'");
    header('Location: sliders.php?deleted=1');
    exit();
}

// Toggle active status
if(isset($_GET['toggle'])) {
    $id = $_GET['toggle'];
    $current = $_GET['status'];
    $new = $current == '1' ? '0' : '1';
    mysqli_query($conn, "UPDATE sliders SET is_active = '$new' WHERE id = '$id'");
    header('Location: sliders.php?toggled=1');
    exit();
}

// Get sliders based on current mode
$sliders = mysqli_query($conn, "SELECT * FROM sliders WHERE media_type = '$current_mode' ORDER BY order_position");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
    <title>Manage Sliders - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            background: #0a0a0a; 
            font-family: 'Poppins', sans-serif;
            position: relative;
        }
        
        /* Neon Yellow Grid Background */
        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-image: 
                linear-gradient(rgba(255,215,0,0.04) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255,215,0,0.04) 1px, transparent 1px);
            background-size: 50px 50px;
            pointer-events: none;
            z-index: 0;
        }
        
        body::after {
            content: '';
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 800px;
            height: 800px;
            background: radial-gradient(circle, rgba(255,215,0,0.08) 0%, transparent 70%);
            pointer-events: none;
            z-index: 0;
        }
        
        /* Glassy Sidebar */
        .sidebar { 
            background: rgba(10, 10, 10, 0.95);
            backdrop-filter: blur(10px);
            min-height: 100vh; 
            border-right: 1px solid rgba(255,215,0,0.2);
            position: sticky;
            top: 0;
            z-index: 1;
        }
        
        .sidebar .nav-link { 
            color: #fff; 
            padding: 12px 20px; 
            transition: all 0.3s; 
            border-radius: 12px; 
            margin: 5px 15px;
            font-weight: 500;
        }
        .sidebar .nav-link:hover, 
        .sidebar .nav-link.active { 
            background: linear-gradient(135deg, #FFD700, #FFC107);
            color: #000; 
            transform: translateX(5px);
            box-shadow: 0 5px 15px rgba(255,215,0,0.2);
        }
        .sidebar .nav-link i { width: 25px; margin-right: 10px; }
        
        /* Mode Toggle Buttons */
        .mode-toggle-container {
            background: rgba(26, 26, 26, 0.85);
            backdrop-filter: blur(10px);
            border-radius: 60px;
            padding: 8px;
            display: inline-flex;
            gap: 10px;
            border: 1px solid rgba(255,215,0,0.2);
            margin-bottom: 25px;
        }
        
        .mode-btn {
            padding: 12px 30px;
            border-radius: 50px;
            font-weight: 600;
            font-size: 16px;
            transition: all 0.3s;
            cursor: pointer;
            border: none;
            background: transparent;
            color: #fff;
        }
        
        .mode-btn.active {
            background: linear-gradient(135deg, #FFD700, #FFC107);
            color: #000;
            box-shadow: 0 5px 15px rgba(255,215,0,0.3);
        }
        
        .mode-btn i { margin-right: 8px; }
        
        .mode-btn:not(.active):hover {
            background: rgba(255,215,0,0.2);
            transform: translateY(-2px);
        }
        
        /* Glassy Slider Cards */
        .slider-card { 
            background: rgba(26, 26, 26, 0.85);
            backdrop-filter: blur(10px);
            border-radius: 20px; 
            overflow: hidden; 
            margin-bottom: 25px; 
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            border: 1px solid rgba(255,215,0,0.15);
            height: 100%;
        }
        .slider-card:hover { 
            transform: translateY(-8px); 
            border-color: rgba(255,215,0,0.4);
            box-shadow: 0 15px 35px rgba(255,215,0,0.15);
        }
        .slider-img, .slider-video { 
            width: 100%; 
            height: 200px; 
            object-fit: cover; 
            transition: transform 0.5s;
        }
        .slider-video { object-fit: cover; }
        .slider-card:hover .slider-img,
        .slider-card:hover .slider-video { transform: scale(1.02); }
        
        .slider-card h5 { color: #fff; font-weight: 600; margin-bottom: 8px; }
        .slider-card p { color: #aaa; font-size: 13px; margin-bottom: 8px; }
        
        h2 { color: #FFD700; font-weight: 700; }
        
        /* Buttons */
        .btn-warning-custom {
            background: linear-gradient(135deg, #FFD700, #FFC107);
            color: #000;
            border: none;
            padding: 10px 25px;
            border-radius: 50px;
            font-weight: 600;
            transition: all 0.3s;
        }
        .btn-warning-custom:hover { transform: translateY(-2px); box-shadow: 0 8px 20px rgba(255,215,0,0.3); }
        
        .btn-test { 
            background: linear-gradient(135deg, #17a2b8, #138496);
            color: #fff;
            border: none;
            border-radius: 50px;
            padding: 5px 15px;
            font-size: 12px;
            transition: all 0.3s;
        }
        .btn-test:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(23,162,184,0.3); }
        
        .btn-delete-slider {
            background: rgba(220,53,69,0.2);
            color: #ff6b6b;
            border: 1px solid rgba(220,53,69,0.3);
            border-radius: 50px;
            padding: 5px 15px;
            font-size: 12px;
            transition: all 0.3s;
        }
        .btn-delete-slider:hover { background: #dc3545; color: #fff; transform: translateY(-2px); }
        
        .btn-toggle {
            border-radius: 50px;
            padding: 5px 15px;
            font-size: 12px;
            transition: all 0.3s;
        }
        .btn-toggle:hover { transform: translateY(-2px); }
        
        /* Alert Styles */
        .alert-custom { 
            background: rgba(40,167,69,0.15);
            backdrop-filter: blur(10px);
            border: 1px solid #28a745; 
            color: #6bff8a; 
            padding: 12px 18px; 
            border-radius: 12px;
        }
        .alert-danger-custom {
            background: rgba(220,53,69,0.15);
            backdrop-filter: blur(10px);
            border: 1px solid #dc3545;
            color: #ff6b6b;
            padding: 12px 18px;
            border-radius: 12px;
        }
        .alert-info-custom {
            background: rgba(23,162,184,0.15);
            backdrop-filter: blur(10px);
            border: 1px solid #17a2b8;
            color: #6be3ff;
            padding: 12px 18px;
            border-radius: 12px;
        }
        
        /* Modal Styles */
        .modal-content {
            background: rgba(26, 26, 26, 0.95);
            backdrop-filter: blur(15px);
            border: 1px solid rgba(255,215,0,0.3);
            border-radius: 20px;
        }
        .modal-header {
            background: linear-gradient(135deg, #FFD700, #FFC107);
            border-radius: 20px 20px 0 0;
            border-bottom: none;
        }
        .modal-header h5 { color: #000; font-weight: 700; }
        .modal-body label { color: #fff; font-weight: 500; margin-bottom: 8px; }
        .form-control, .form-select {
            background: rgba(42, 42, 42, 0.9);
            border: 1px solid rgba(255,255,255,0.1);
            color: #fff;
            border-radius: 12px;
            padding: 10px 15px;
        }
        .form-control:focus, .form-select:focus {
            border-color: #FFD700;
            box-shadow: 0 0 0 3px rgba(255,215,0,0.1);
            outline: none;
        }
        .form-check-label { color: #fff; }
        .modal-footer { border-top: 1px solid rgba(255,255,255,0.1); }
        
        /* Media Type Badge */
        .media-badge {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 50px;
            font-size: 10px;
            font-weight: 600;
            margin-left: 10px;
        }
        .media-badge.image { background: rgba(40,167,69,0.2); color: #6bff8a; }
        .media-badge.video { background: rgba(23,162,184,0.2); color: #6be3ff; }
        
        /* Mobile Responsive */
        .sidebar-toggle {
            display: none;
            position: fixed;
            top: 15px;
            left: 15px;
            z-index: 1060;
            background: linear-gradient(135deg, #FFD700, #FFC107);
            color: #000;
            border: none;
            padding: 10px 16px;
            border-radius: 50px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 600;
            box-shadow: 0 5px 15px rgba(0,0,0,0.3);
        }
        
        @media (max-width: 992px) {
            .sidebar {
                position: fixed;
                top: 0;
                left: -280px;
                width: 260px;
                height: 100%;
                z-index: 1050;
                transition: left 0.3s ease;
                overflow-y: auto;
            }
            .sidebar.show { left: 0; }
            .sidebar-toggle { display: block; }
            .col-md-10 { width: 100%; margin-left: 0; padding: 15px; }
            body { padding-top: 70px; }
            h2 { font-size: 22px; }
            .d-flex.justify-content-between { flex-direction: column; gap: 15px; align-items: flex-start; }
            .mode-toggle-container { width: 100%; justify-content: center; }
            .mode-btn { padding: 8px 20px; font-size: 14px; }
        }
        
        @media (max-width: 768px) {
            .sidebar { width: 240px; }
            h2 { font-size: 20px; }
            .p-4 { padding: 15px !important; }
            .slider-card h5 { font-size: 14px; }
            .col-md-4 { width: 100%; }
            .slider-img, .slider-video { height: 180px; }
            .mode-btn { padding: 6px 15px; font-size: 12px; }
        }
        
        @media (max-width: 576px) {
            .sidebar { width: 220px; }
            h2 { font-size: 18px; }
            .slider-img, .slider-video { height: 160px; }
        }
    </style>
</head>
<body>

<!-- Mobile Sidebar Toggle Button -->
<button class="sidebar-toggle" id="sidebarToggle">
    <i class="fas fa-bars"></i> Menu
</button>

<div class="container-fluid">
    <div class="row">
        <!-- Glassy Sidebar -->
        <div class="col-md-2 p-0 sidebar" id="sidebar">
            <div class="p-4 text-center">
                <div class="logo-circle" style="width: 50px; height: 50px; background: linear-gradient(135deg, #FFD700, #FFC107); border-radius: 12px; display: flex; align-items: center; justify-content: center; margin: 0 auto 12px;">
                    <i class="fas fa-tshirt" style="font-size: 24px; color: #000;"></i>
                </div>
                <h3 style="color: #FFD700; font-size: 18px;">UHD-Wears</h3>
                <p style="color:#888; font-size: 11px;">Admin Panel</p>
            </div>
            <nav class="nav flex-column">
                <a class="nav-link" href="dashboard.php"><i class="fas fa-chart-line"></i> Dashboard</a>
                <a class="nav-link" href="products.php"><i class="fas fa-box"></i> Products</a>
                <a class="nav-link" href="orders.php"><i class="fas fa-shopping-cart"></i> Orders</a>
                <a class="nav-link" href="users.php"><i class="fas fa-users"></i> Users</a>
                <a class="nav-link active" href="sliders.php"><i class="fas fa-sliders-h"></i> Sliders</a>
                <a class="nav-link" href="categories.php"><i class="fas fa-tags"></i> Categories</a>
                <hr style="border-color: rgba(255,255,255,0.1); margin: 15px;">
                <a class="nav-link" href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </nav>
        </div>
        
        <!-- Main Content -->
        <div class="col-md-10 p-4">
            <div class="d-flex justify-content-between mb-4 flex-wrap align-items-center">
                <div>
                    <h2><i class="fas fa-sliders-h me-2"></i> Manage Sliders</h2>
                    <p class="text-muted" style="color: #aaa; font-size: 13px;">Manage homepage hero sliders (images or videos)</p>
                </div>
                <button class="btn-warning-custom" data-bs-toggle="modal" data-bs-target="#addModal">
                    <i class="fas fa-plus me-2"></i> Add Slider
                </button>
            </div>
            
            <!-- Mode Toggle Buttons -->
            <div class="mode-toggle-container">
                <form method="POST" id="modeForm" style="display: flex; gap: 10px;">
                    <input type="hidden" name="slider_mode" id="slider_mode_input" value="">
                    <button type="submit" name="update_mode" class="mode-btn <?php echo $current_mode == 'image' ? 'active' : ''; ?>" data-mode="image" onclick="setMode('image')">
                        <i class="fas fa-image"></i> Image Mode
                    </button>
                    <button type="submit" name="update_mode" class="mode-btn <?php echo $current_mode == 'video' ? 'active' : ''; ?>" data-mode="video" onclick="setMode('video')">
                        <i class="fas fa-video"></i> Video Mode
                    </button>
                </form>
            </div>
            
            <?php if(isset($_GET['added'])): ?>
            <div class="alert-custom mt-2"><i class="fas fa-check-circle me-2"></i> Slider added successfully!</div>
            <?php endif; ?>
            <?php if(isset($_GET['deleted'])): ?>
            <div class="alert-danger-custom mt-2"><i class="fas fa-trash me-2"></i> Slider deleted successfully!</div>
            <?php endif; ?>
            <?php if(isset($_GET['toggled'])): ?>
            <div class="alert-info-custom mt-2"><i class="fas fa-sync-alt me-2"></i> Status updated successfully!</div>
            <?php endif; ?>
            <?php if(isset($_GET['mode_updated'])): ?>
            <div class="alert-info-custom mt-2"><i class="fas fa-exchange-alt me-2"></i> Slider mode changed to <?php echo ucfirst($current_mode); ?> mode!</div>
            <?php endif; ?>
            
            <div class="row mt-4 g-4">
                <?php if(mysqli_num_rows($sliders) == 0): ?>
                    <div class="col-12">
                        <div class="text-center" style="background: rgba(26,26,26,0.7); backdrop-filter: blur(10px); border-radius: 20px; padding: 50px; border: 1px solid rgba(255,215,0,0.2);">
                            <i class="fas <?php echo $current_mode == 'image' ? 'fa-image' : 'fa-video'; ?> fa-3x mb-3" style="color: #FFD700;"></i>
                            <h5 style="color: #fff;">No <?php echo $current_mode; ?> sliders yet</h5>
                            <p style="color: #aaa;">Click "Add Slider" to create your first <?php echo $current_mode; ?> slider</p>
                        </div>
                    </div>
                <?php else: ?>
                    <?php while($s = mysqli_fetch_assoc($sliders)): 
                        $media_src = '';
                        $is_video = ($s['media_type'] == 'video');
                        if($is_video && !empty($s['video_url'])) {
                            $media_src = '../' . $s['video_url'];
                        } elseif(!$is_video && !empty($s['image_path'])) {
                            $media_src = '../' . $s['image_path'];
                        } else {
                            $media_src = 'https://via.placeholder.com/400x200?text=No+Media';
                        }
                        
                        $test_url = '../' . $s['button_link'];
                    ?>
                    <div class="col-lg-4 col-md-6">
                        <div class="slider-card">
                            <?php if($is_video): ?>
                                <video class="slider-video" muted loop>
                                    <source src="<?php echo $media_src; ?>" type="video/mp4">
                                    Your browser does not support the video tag.
                                </video>
                            <?php else: ?>
                                <img src="<?php echo $media_src; ?>" class="slider-img" alt="<?php echo htmlspecialchars($s['title']); ?>" onerror="this.src='https://via.placeholder.com/400x200?text=Image+Error'">
                            <?php endif; ?>
                            <div class="p-3">
                                <div class="d-flex justify-content-between align-items-center flex-wrap">
                                    <h5><i class="fas fa-tag me-1"></i> <?php echo htmlspecialchars($s['title']); ?>
                                        <span class="media-badge <?php echo $is_video ? 'video' : 'image'; ?>">
                                            <i class="fas <?php echo $is_video ? 'fa-video' : 'fa-image'; ?>"></i> <?php echo $is_video ? 'Video' : 'Image'; ?>
                                        </span>
                                    </h5>
                                    <a href="?toggle=<?php echo $s['id']; ?>&status=<?php echo $s['is_active']; ?>" class="btn btn-toggle <?php echo $s['is_active']==1?'btn-success':'btn-secondary'; ?>">
                                        <i class="fas <?php echo $s['is_active']==1?'fa-eye':'fa-eye-slash'; ?> me-1"></i>
                                        <?php echo $s['is_active']==1?'Active':'Inactive'; ?>
                                    </a>
                                </div>
                                <p class="mt-2"><i class="fas fa-quote-left me-1" style="color: #FFD700; font-size: 10px;"></i> <?php echo htmlspecialchars($s['subtitle']); ?></p>
                                <small class="text-muted"><i class="fas fa-link me-1"></i> Button: <?php echo htmlspecialchars($s['button_text']); ?> → <?php echo htmlspecialchars($s['button_link']); ?></small>
                                <div class="mt-3 d-flex gap-2 flex-wrap">
                                    <a href="<?php echo $test_url; ?>" class="btn-test" target="_blank">
                                        <i class="fas fa-external-link-alt me-1"></i> Test Button
                                    </a>
                                    <button class="btn-delete-slider delete-slider" data-id="<?php echo $s['id']; ?>">
                                        <i class="fas fa-trash-alt me-1"></i> Delete
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endwhile; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Add Slider Modal (Dynamic based on mode) -->
<div class="modal fade" id="addModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-plus-circle me-2"></i> Add New <?php echo ucfirst($current_mode); ?> Slider</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" enctype="multipart/form-data">
                <div class="modal-body">
                    <div class="mb-3">
                        <label><i class="fas fa-heading me-1"></i> Title</label>
                        <input type="text" name="title" class="form-control" placeholder="e.g., Summer Collection 2024" required>
                    </div>
                    <div class="mb-3">
                        <label><i class="fas fa-quote-right me-1"></i> Subtitle</label>
                        <input type="text" name="subtitle" class="form-control" placeholder="e.g., Up to 50% off on selected items" required>
                    </div>
                    
                    <?php if($current_mode == 'image'): ?>
                    <div class="mb-3">
                        <label><i class="fas fa-image me-1"></i> Slider Image</label>
                        <input type="file" name="slider_image" class="form-control" accept="image/*" required>
                        <small class="text-muted"><i class="fas fa-info-circle me-1"></i> Recommended size: 1920x600 pixels</small>
                    </div>
                    <?php else: ?>
                    <div class="mb-3">
                        <label><i class="fas fa-video me-1"></i> Slider Video (MP4)</label>
                        <input type="file" name="slider_video" class="form-control" accept="video/mp4,video/webm" required>
                        <small class="text-muted"><i class="fas fa-info-circle me-1"></i> Recommended: MP4 format, 5-10 seconds duration</small>
                    </div>
                    <?php endif; ?>
                    
                    <div class="mb-3">
                        <label><i class="fas fa-square me-1"></i> Button Text</label>
                        <input type="text" name="button_text" class="form-control" value="Shop Now">
                    </div>
                    <div class="mb-3">
                        <label><i class="fas fa-link me-1"></i> Button Link</label>
                        <select name="button_link" class="form-select">
                            <option value="men.php">👔 Men's Collection</option>
                            <option value="women.php">👗 Women's Collection</option>
                            <option value="kids.php">🧸 Kids' Collection</option>
                            <option value="sale.php">🔥 Sale Collection</option>
                            <option value="index.php">🏠 Homepage</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label><i class="fas fa-sort-numeric-down me-1"></i> Order Position</label>
                        <input type="number" name="order_position" class="form-control" value="1">
                        <small class="text-muted"><i class="fas fa-info-circle me-1"></i> 1 = first, 2 = second, etc.</small>
                    </div>
                    <div class="mb-3 form-check">
                        <input type="checkbox" name="is_active" class="form-check-input" id="is_active" checked>
                        <label class="form-check-label" for="is_active">
                            <i class="fas fa-check-circle me-1"></i> Active (show on homepage)
                        </label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn" style="background: rgba(108,117,125,0.5); color: #fff; border-radius: 50px;" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="add_slider" class="btn" style="background: linear-gradient(135deg, #FFD700, #FFC107); color: #000; border-radius: 50px; padding: 8px 25px; font-weight: 600;">
                        <i class="fas fa-save me-2"></i> Add Slider
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
function setMode(mode) {
    $('#slider_mode_input').val(mode);
    $('#modeForm').submit();
}

// SweetAlert for Delete Slider
$(document).ready(function() {
    $('.delete-slider').click(function() {
        var sliderId = $(this).data('id');
        Swal.fire({
            title: 'Delete Slider?',
            text: 'This action cannot be undone!',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, delete it!',
            cancelButtonText: 'Cancel',
            background: '#1a1a1a',
            color: '#fff'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = 'sliders.php?delete=' + sliderId;
            }
        });
    });
    
    // Video preview on hover
    $('.slider-video').each(function() {
        this.addEventListener('mouseenter', function() {
            this.play();
        });
        this.addEventListener('mouseleave', function() {
            this.pause();
            this.currentTime = 0;
        });
    });
});

// Mobile Sidebar Toggle
document.getElementById('sidebarToggle').addEventListener('click', function() {
    document.getElementById('sidebar').classList.toggle('show');
});

// Close sidebar when clicking outside on mobile
document.addEventListener('click', function(e) {
    if (window.innerWidth <= 992) {
        const sidebar = document.getElementById('sidebar');
        const toggle = document.getElementById('sidebarToggle');
        if (sidebar && toggle && !sidebar.contains(e.target) && !toggle.contains(e.target) && sidebar.classList.contains('show')) {
            sidebar.classList.remove('show');
        }
    }
});
</script>
</body>
</html>