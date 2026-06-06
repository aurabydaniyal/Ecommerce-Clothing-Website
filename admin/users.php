<?php
session_start();
require_once '../db_connection.php';

if(!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'admin') {
    header('Location: ../login.php');
    exit();
}

if(isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    mysqli_query($conn, "DELETE FROM users WHERE id = '$id' AND role != 'admin'");
    header('Location: users.php?deleted=1');
    exit();
}

$users = mysqli_query($conn, "SELECT * FROM users ORDER BY created_at DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
    <title>Manage Users - Admin</title>
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
        
        /* ============================================
           NEON YELLOW GRID BACKGROUND
           ============================================ */
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
        
        /* ============================================
           GLASSY SIDEBAR
           ============================================ */
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
        .sidebar .nav-link i {
            width: 25px;
            margin-right: 10px;
        }
        
        /* Main Content */
        .col-md-10 {
            position: relative;
            z-index: 1;
        }
        
        /* ============================================
           GLASSY TABLE
           ============================================ */
        .user-table { 
            background: rgba(26, 26, 26, 0.85);
            backdrop-filter: blur(10px);
            border-radius: 20px; 
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            border: 1px solid rgba(255,215,0,0.15);
        }
        
        .user-table th { 
            background: linear-gradient(135deg, #FFD700, #FFC107);
            color: #000; 
            padding: 15px; 
            font-weight: 600;
            border-bottom: none;
        }
        
        .user-table td { 
            padding: 12px 15px; 
            border-bottom: 1px solid rgba(255,255,255,0.1); 
            color: #090909;
            vertical-align: middle;
        }
        
        .user-table tr:hover td {
            background: rgb(249, 236, 121);
        }
        
        /* Badges */
        .badge {
            padding: 5px 12px;
            border-radius: 50px;
            font-weight: 500;
            font-size: 11px;
        }
        
        .bg-danger {
            background: linear-gradient(135deg, #dc3545, #c82333);
        }
        
        .bg-success {
            background: linear-gradient(135deg, #28a745, #20c997);
        }
        
        .text-muted {
            color: #aaa !important;
        }
        
        h2 { 
            color: #FFD700;
            font-weight: 700;
        }
        
        /* Alert Styles */
        .alert-custom { 
            background: rgba(40,167,69,0.15);
            backdrop-filter: blur(10px);
            border: 1px solid #28a745; 
            color: #6bff8a; 
            padding: 12px 18px; 
            border-radius: 12px;
        }
        
        /* Delete Button */
        .btn-delete-user {
            background: rgba(220,53,69,0.2);
            color: #ff6b6b;
            border: 1px solid rgba(220,53,69,0.3);
            border-radius: 50px;
            padding: 5px 15px;
            font-size: 12px;
            transition: all 0.3s;
        }
        
        .btn-delete-user:hover {
            background: #dc3545;
            color: #fff;
            transform: translateY(-2px);
        }
        
        /* ============================================
           MOBILE RESPONSIVE STYLES
           ============================================ */
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
            .user-table { overflow-x: auto; display: block; }
            .user-table table { min-width: 800px; }
        }
        
        @media (max-width: 768px) {
            .sidebar { width: 240px; }
            h2 { font-size: 20px; }
            .p-4 { padding: 15px !important; }
            .user-table th, .user-table td { padding: 8px 10px; font-size: 12px; }
            .badge { font-size: 9px; }
        }
        
        @media (max-width: 576px) {
            .sidebar { width: 220px; }
            h2 { font-size: 18px; }
            .user-table th, .user-table td { padding: 6px 8px; font-size: 11px; }
            .btn-delete-user { padding: 3px 10px; font-size: 10px; }
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
                <a class="nav-link active" href="users.php"><i class="fas fa-users"></i> Users</a>
                <a class="nav-link" href="sliders.php"><i class="fas fa-image"></i> Sliders</a>
                <a class="nav-link" href="categories.php"><i class="fas fa-tags"></i> Categories</a>
                <hr style="border-color: rgba(255,255,255,0.1); margin: 15px;">
                <a class="nav-link" href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </nav>
        </div>
        
        <!-- Main Content -->
        <div class="col-md-10 p-4">
            <h2><i class="fas fa-users me-2"></i> Manage Users</h2>
            <p style="color:#aaa;">View and manage registered customers</p>
            
            <?php if(isset($_GET['deleted'])): ?>
            <div class="alert-custom mt-3"><i class="fas fa-check-circle me-2"></i> User deleted successfully!</div>
            <?php endif; ?>
            
            <div class="user-table mt-4">
                <div style="overflow-x: auto;">
                    <table class="table mb-0">
                        <thead>
                            <tr>
                                <th><i class="fas fa-hashtag"></i> ID</th>
                                <th><i class="fas fa-user"></i> Username</th>
                                <th><i class="fas fa-user-circle"></i> Full Name</th>
                                <th><i class="fas fa-envelope"></i> Email</th>
                                <th><i class="fas fa-phone"></i> Phone</th>
                                <th><i class="fas fa-shield-alt"></i> Role</th>
                                <th><i class="fas fa-calendar"></i> Joined</th>
                                <th><i class="fas fa-cog"></i> Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($u = mysqli_fetch_assoc($users)): ?>
                            <tr>
                                <td><strong><?php echo $u['id']; ?></strong></td>
                                <td><i class="fas fa-at"></i> <?php echo htmlspecialchars($u['username']); ?></td>
                                <td><?php echo htmlspecialchars($u['full_name']); ?></td>
                                <td><?php echo htmlspecialchars($u['email']); ?></td>
                                <td><?php echo htmlspecialchars($u['phone']) ?: '-'; ?></td>
                                <td>
                                    <?php if($u['role'] == 'admin'): ?>
                                        <span class="badge bg-danger"><i class="fas fa-crown me-1"></i> Admin</span>
                                    <?php else: ?>
                                        <span class="badge bg-success"><i class="fas fa-user me-1"></i> User</span>
                                    <?php endif; ?>
                                </td>
                                <td><i class="fas fa-calendar-alt me-1"></i> <?php echo date('M d, Y', strtotime($u['created_at'])); ?></td>
                                <td>
                                    <?php if($u['role'] != 'admin'): ?>
                                        <button class="btn-delete-user delete-user" data-id="<?php echo $u['id']; ?>" data-name="<?php echo htmlspecialchars($u['username']); ?>">
                                            <i class="fas fa-trash-alt me-1"></i> Delete
                                        </button>
                                    <?php else: ?>
                                        <span class="text-muted"><i class="fas fa-lock me-1"></i> Protected</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <!-- Stats Summary -->
            <div class="row mt-4 g-3">
                <div class="col-md-4">
                    <div class="stat-mini" style="background: rgba(26,26,26,0.7); backdrop-filter: blur(10px); border-radius: 16px; padding: 15px; border: 1px solid rgba(255,215,0,0.15);">
                        <i class="fas fa-users" style="color: #FFD700; font-size: 24px;"></i>
                        <span style="color: #fff; margin-left: 10px;">Total Users: <strong style="color: #FFD700;"><?php echo mysqli_num_rows($users); ?></strong></span>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stat-mini" style="background: rgba(26,26,26,0.7); backdrop-filter: blur(10px); border-radius: 16px; padding: 15px; border: 1px solid rgba(255,215,0,0.15);">
                        <i class="fas fa-crown" style="color: #FFD700; font-size: 24px;"></i>
                        <span style="color: #fff; margin-left: 10px;">Admin Users: <strong style="color: #FFD700;">1</strong></span>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stat-mini" style="background: rgba(26,26,26,0.7); backdrop-filter: blur(10px); border-radius: 16px; padding: 15px; border: 1px solid rgba(255,215,0,0.15);">
                        <i class="fas fa-user-check" style="color: #FFD700; font-size: 24px;"></i>
                        <span style="color: #fff; margin-left: 10px;">Regular Users: <strong style="color: #FFD700;"><?php echo mysqli_num_rows($users) - 1; ?></strong></span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
$(document).ready(function() {
    // SweetAlert for Delete User
    $('.delete-user').click(function() {
        var userId = $(this).data('id');
        var userName = $(this).data('name');
        Swal.fire({
            title: 'Delete User?',
            html: `Are you sure you want to delete <strong style="color: #FFD700;">${userName}</strong>?<br><br>This action cannot be undone.`,
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
                window.location.href = 'users.php?delete=' + userId;
            }
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