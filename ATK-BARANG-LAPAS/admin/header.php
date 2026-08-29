<?php
// header.php
// File header untuk halaman admin

// Mulai session di awal file - NO SPACES OR OUTPUT BEFORE THIS
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include '../assets/conn/config.php';

// Pastikan session username tersedia
if (!isset($_SESSION['username'])) {
    header("location:../index.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>INVENTARIS KANTOR PERSONAL - LAPAS</title>
    
    <!-- Bootstrap 5 & Font Awesome -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    
    <style>
        /* ===== VARIABEL GLOBAL ===== */
        :root {
            --lapas-dark: #0a1c2a;
            --lapas-primary: #0d3b4c;
            --lapas-secondary: #1a6d7a;
            --lapas-accent: #2c9aa8;
            --lapas-gold: #c9a03d;
            --lapas-border: #e2edf2;
            --lapas-light: #f5f9fc;
            --lapas-gray: #6a8294;
            --shadow-sm: 0 2px 8px rgba(0, 0, 0, 0.04);
            --shadow-md: 0 4px 16px rgba(0, 0, 0, 0.06);
            --shadow-lg: 0 8px 24px rgba(0, 0, 0, 0.1);
            --transition: all 0.25s ease;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', 'Segoe UI', system-ui, -apple-system, sans-serif;
            background: #f0f4f8;
            min-height: 100vh;
            margin: 0;
            padding: 0;
            padding-top: 70px;
        }
        
        /* ===== NAVBAR STYLES ===== */
        .navbar-lapas {
            background: linear-gradient(135deg, #0a2a3a 0%, #0d3b4c 100%);
            box-shadow: var(--shadow-md);
            padding: 0.6rem 1.5rem;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            z-index: 1030;
        }
        
        .navbar-container {
            max-width: 1400px;
            margin: 0 auto;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
        }
        
        /* Brand Logo */
        .brand-lapas {
            display: flex;
            align-items: center;
            gap: 12px;
            text-decoration: none;
            transition: var(--transition);
        }
        
        .brand-lapas:hover {
            opacity: 0.95;
        }
        
        .badge-icon {
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(4px);
            width: 44px;
            height: 44px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: var(--transition);
        }
        
        .badge-icon i {
            font-size: 24px;
            color: #ffd700;
        }
        
        .brand-text h1 {
            font-size: 1.3rem;
            font-weight: 700;
            margin: 0;
            color: white;
            letter-spacing: -0.3px;
        }
        
        .brand-text span {
            font-size: 0.65rem;
            color: rgba(255, 255, 255, 0.7);
            font-weight: 500;
            display: block;
            margin-top: 2px;
        }
        
        /* Mobile Toggle */
        .navbar-toggler-lapas {
            border: none;
            background: rgba(255, 255, 255, 0.1);
            font-size: 1.4rem;
            color: white;
            padding: 8px 14px;
            border-radius: 12px;
            transition: var(--transition);
            display: none;
        }
        
        .navbar-toggler-lapas:hover {
            background: rgba(255, 255, 255, 0.2);
        }
        
        /* Navigation Menu */
        .navbar-collapse-lapas {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex: 1;
        }
        
        .nav-menu-lapas {
            display: flex;
            align-items: center;
            gap: 14px;
            flex-wrap: wrap;
            list-style: none;
            margin: 0;
            padding: 1;
        }
        
        .nav-item-lapas {
            position: relative;
        }
        
        .nav-link-lapas {
            font-weight: 500;
            font-size: 0.85rem;
            padding: 0.5rem 1rem;
            border-radius: 40px;
            color: rgba(255, 255, 255, 0.85);
            transition: var(--transition);
            display: flex;
            align-items: center;
            gap: 8px;
            background: transparent;
            text-decoration: none;
            white-space: nowrap;
        }
        
        .nav-link-lapas i {
            font-size: 0.9rem;
            color: rgba(255, 255, 255, 0.6);
            transition: var(--transition);
        }
        
        .nav-link-lapas:hover {
            background: rgba(255, 255, 255, 0.1);
            color: white;
        }
        
        .nav-link-lapas:hover i {
            color: #ffd700;
        }
        
        .nav-link-lapas.active {
            background: rgba(255, 255, 255, 0.15);
            color: white;
            font-weight: 600;
        }
        
        .nav-link-lapas.active i {
            color: #ffd700;
        }
        
        /* User Profile Section */
        .user-section {
            display: flex;
            align-items: center;
            gap: 16px;
        }
        
        .user-card {
            display: flex;
            align-items: center;
            gap: 12px;
            background: rgba(255, 255, 255, 0.08);
            padding: 5px 14px 5px 8px;
            border-radius: 50px;
            cursor: pointer;
            border: 1px solid rgba(255, 255, 255, 0.15);
            transition: var(--transition);
        }
        
        .user-card:hover {
            background: rgba(255, 255, 255, 0.15);
            border-color: rgba(255, 255, 255, 0.25);
        }
        
        .avatar-initial {
            width: 34px;
            height: 34px;
            background: linear-gradient(135deg, #ffd700, #e6a800);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #0a2a3a;
            font-weight: 700;
            font-size: 0.9rem;
        }
        
        .user-info {
            text-align: left;
        }
        
        .user-info h6 {
            font-size: 0.8rem;
            font-weight: 600;
            margin: 0;
            line-height: 1.3;
            color: white;
        }
        
        .user-info p {
            font-size: 0.65rem;
            margin: 0;
            color: rgba(255, 255, 255, 0.6);
            text-transform: capitalize;
        }
        
        .user-card .fa-chevron-down {
            font-size: 10px;
            color: rgba(255, 255, 255, 0.5);
            transition: var(--transition);
        }
        
        .user-card:hover .fa-chevron-down {
            color: #ffd700;
        }
        
        /* Dropdown Menu */
        .dropdown-lapas {
            position: relative;
        }
        
        .dropdown-menu-custom {
            position: absolute;
            top: 52px;
            right: 0;
            background: white;
            border-radius: 16px;
            box-shadow: var(--shadow-lg);
            min-width: 220px;
            opacity: 0;
            visibility: hidden;
            transform: translateY(-10px);
            transition: 0.2s;
            z-index: 1050;
            border: none;
            overflow: hidden;
        }
        
        .dropdown-menu-custom.show {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }
        
        .dropdown-item-lapas {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 18px;
            color: #2c4a5e;
            text-decoration: none;
            font-size: 0.85rem;
            font-weight: 500;
            transition: background 0.15s;
            border-bottom: 1px solid #f0f4f8;
        }
        
        .dropdown-item-lapas:last-child {
            border-bottom: none;
        }
        
        .dropdown-item-lapas i {
            width: 20px;
            color: #7a9bb0;
            font-size: 0.9rem;
        }
        
        .dropdown-item-lapas:hover {
            background: #f5f9fc;
            color: #0d3b4c;
        }
        
        .dropdown-item-lapas:hover i {
            color: #2c9aa8;
        }
        
        .dropdown-item-lapas.logout-item {
            color: #c62828;
        }
        
        .dropdown-item-lapas.logout-item i {
            color: #c62828;
        }
        
        .dropdown-item-lapas.logout-item:hover {
            background: #ffebee;
        }
        
        /* ===== RESPONSIVE ===== */
        @media (max-width: 1100px) {
            .nav-link-lapas span {
                display: none;
            }
            .nav-link-lapas i {
                margin: 0;
                font-size: 1.1rem;
            }
            .nav-link-lapas {
                padding: 0.5rem 0.9rem;
            }
        }
        
        @media (max-width: 991px) {
            .navbar-container {
                flex-wrap: wrap;
            }
            
            .navbar-toggler-lapas {
                display: block;
            }
            
            .navbar-collapse-lapas {
                width: 100%;
                display: none;
                flex-direction: column;
                align-items: flex-start;
                padding-top: 16px;
            }
            
            .navbar-collapse-lapas.show {
                display: flex;
            }
            
            .nav-menu-lapas {
                flex-direction: column;
                align-items: flex-start;
                width: 100%;
                gap: 4px;
                margin-bottom: 16px;
            }
            
            .nav-link-lapas {
                width: 100%;
                border-radius: 12px;
                padding: 10px 16px;
            }
            
            .nav-link-lapas span {
                display: inline;
            }
            
            .user-section {
                width: 100%;
                justify-content: space-between;
            }
            
            .user-card {
                width: 100%;
                justify-content: space-between;
            }
            
            .dropdown-menu-custom {
                position: relative;
                top: 0;
                width: 100%;
                box-shadow: none;
                border: 1px solid #e2edf2;
                margin-top: 8px;
                background: #f8fafc;
            }
        }
        
        @media (max-width: 576px) {
            .brand-text h1 {
                font-size: 1rem;
            }
            .badge-icon {
                width: 38px;
                height: 38px;
            }
            .badge-icon i {
                font-size: 18px;
            }
            .user-info {
                display: none;
            }
            .navbar-lapas {
                padding: 0.5rem 1rem;
            }
        }
        
        /* ===== GLOBAL STYLES ===== */
        .container {
            max-width: 1280px;
            padding: 0 24px;
            margin: 0 auto;
        }
        
        .panel {
            background: white;
            border-radius: 20px;
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--lapas-border);
            margin-bottom: 30px;
        }
        
        .panel-heading {
            padding: 18px 24px;
            border-bottom: 1px solid var(--lapas-border);
            background: white;
            border-radius: 20px 20px 0 0;
        }
        
        .panel-body {
            padding: 24px;
        }
        
        .table thead th {
            background: var(--lapas-light);
            border-bottom: 2px solid var(--lapas-border);
            color: var(--lapas-dark);
            font-weight: 600;
            padding: 12px;
        }
        
        .table tbody tr:hover {
            background: var(--lapas-light);
        }
        
        .btn-primary {
            background: var(--lapas-primary);
            border: none;
            padding: 8px 18px;
            border-radius: 10px;
            font-weight: 500;
            transition: var(--transition);
        }
        
        .btn-primary:hover {
            background: var(--lapas-secondary);
            transform: translateY(-1px);
        }
        
        .btn-success {
            background: #2e7d32;
            border: none;
        }
        
        .btn-danger {
            background: #c62828;
            border: none;
        }
        
        .label-success {
            background: #e8f5e9;
            color: #2e7d32;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
            display: inline-block;
        }
        
        .label-danger {
            background: #ffebee;
            color: #c62828;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
            display: inline-block;
        }
        
        .label-warning {
            background: #fff8e1;
            color: #f57c00;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
            display: inline-block;
        }
        
        .label-info {
            background: #e3f2fd;
            color: #1565c0;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
            display: inline-block;
        }
        
        .modal-content {
            border-radius: 20px;
            border: none;
        }
        
        .modal-header {
            background: #f8fafc;
            border-bottom: 1px solid var(--lapas-border);
            border-radius: 20px 20px 0 0;
        }
        
        .text-muted {
            color: #6a8294 !important;
        }
        
        a {
            text-decoration: none;
        }
    </style>
</head>
<body>

<?php
// Get user data for navbar
$username = $_SESSION['username'];
$user_level = $_SESSION['level'];
$inisial = strtoupper(substr($username, 0, 1));
?>

<!-- NAVBAR -->
<nav class="navbar-lapas">
    <div class="navbar-container">
        <!-- Brand Logo -->
        <a class="brand-lapas" href="index.php">
            <div class="badge-icon">
                <i class="fas fa-archive"></i>
            </div>
            <div class="brand-text">
                <h1>Inventaris LAPAS</h1>
                <P1>KEMENIMIPAS</P1>
            </div>
        </a>

        <!-- Mobile Toggle Button -->
        <button class="navbar-toggler-lapas" type="button" id="mobileMenuToggle">
            <i class="fas fa-bars"></i>
        </button>

        <!-- Navbar Menu -->
        <div class="navbar-collapse-lapas" id="navbarLapasMenu">
            <!-- Main Navigation Menu -->
            <ul class="nav-menu-lapas">
                <li class="nav-item-lapas">
                    <a class="nav-link-lapas" href="index.php">
                        <i class="fas fa-home"></i> <span>Beranda</span>
                    </a>
                </li>
                
                <!-- Admin Menu (Only for admin) -->
                <?php if ($user_level == 'admin') : ?>
                <li class="nav-item-lapas">
                    <a class="nav-link-lapas" href="barang.php">
                        <i class="fas fa-laptop"></i> <span>Kantor Personal</span>
                    </a>
                </li>
                <li class="nav-item-lapas">
                    <a class="nav-link-lapas" href="atk.php">
                        <i class="fas fa-pencil-alt"></i> <span>ATK</span>
                    </a>
                </li>
                <li class="nav-item-lapas">
                    <a class="nav-link-lapas" href="ruangan.php">
                        <i class="fas fa-building"></i> <span>Ruangan</span>
                    </a>
                </li>
                <?php endif; ?>
                
                <!-- Pengajuan Menu -->
                <li class="nav-item-lapas">
                    <a class="nav-link-lapas" href="pengajuan.php">
                        <i class="fas fa-file-alt"></i> <span>Pengajuan Barang</span>
                    </a>
                </li>
                <li class="nav-item-lapas">
                    <a class="nav-link-lapas" href="pengajuan_atk.php">
                        <i class="fas fa-paper-plane"></i> <span>Pengajuan ATK</span>
                    </a>
                </li>
                
                <!-- Laporan Menu -->
                <li class="nav-item-lapas">
                    <a class="nav-link-lapas" href="laporan.php">
                        <i class="fas fa-print"></i> <span>Laporan</span>
                    </a>
                </li>
            </ul>

            <!-- User Profile Section -->
            <div class="user-section">
                <div class="dropdown-lapas">
                    <div class="user-card" id="userProfileBtn">
                        <div class="avatar-initial">
                            <?php echo htmlspecialchars($inisial); ?>
                        </div>
                        <div class="user-info">
                            <h6><?php echo htmlspecialchars($username); ?></h6>
                            <p><?php echo ucfirst($user_level); ?></p>
                        </div>
                        <i class="fas fa-chevron-down"></i>
                    </div>
                    <div class="dropdown-menu-custom" id="profileDropdown">
                        <a href="profile.php" class="dropdown-item-lapas">
                            <i class="fas fa-user-circle"></i> Profil Saya
                        </a>
                        <?php if ($user_level == 'admin') : ?>
                        <a href="tambah_admin.php" class="dropdown-item-lapas">
                            <i class="fas fa-user-plus"></i> Kelola Pengguna
                        </a>
                        <?php endif; ?>
                        <a href="logout.php" class="dropdown-item-lapas logout-item">
                            <i class="fas fa-sign-out-alt"></i> Keluar
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</nav>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>

<script>
$(document).ready(function() {
    // Active menu highlighting
    var currentUrl = window.location.pathname;
    var currentFile = currentUrl.substring(currentUrl.lastIndexOf('/') + 1);
    if (currentFile === '' || currentFile === 'index.php') {
        currentFile = 'index.php';
    }
    $('.nav-link-lapas').each(function() {
        var href = $(this).attr('href');
        if (href === currentFile) {
            $(this).addClass('active');
        }
    });
    
    // Mobile menu toggle
    $('#mobileMenuToggle').on('click', function(e) {
        e.stopPropagation();
        $('#navbarLapasMenu').slideToggle(250);
    });
    
    // Profile dropdown
    const $dropdownMenu = $('#profileDropdown');
    const $userBtn = $('#userProfileBtn');
    
    $userBtn.on('click', function(e) {
        e.stopPropagation();
        $dropdownMenu.toggleClass('show');
    });
    
    $(document).on('click', function(e) {
        if (!$(e.target).closest('.dropdown-lapas').length) {
            $dropdownMenu.removeClass('show');
        }
    });
    
    // Desktop menu always visible
    function checkWidth() {
        if ($(window).width() > 991) {
            $('#navbarLapasMenu').css('display', 'flex');
        } else {
            $('#navbarLapasMenu').css('display', '');
        }
    }
    checkWidth();
    $(window).resize(checkWidth);
    
    // Close mobile menu when clicking on a link
    $('.nav-link-lapas').on('click', function() {
        if ($(window).width() <= 991) {
            $('#navbarLapasMenu').slideUp(250);
        }
    });
});
</script>

<!-- Content starts here -->