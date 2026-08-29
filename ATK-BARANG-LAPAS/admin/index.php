<?php
// Mulai session di awal file
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
    <title>INVENTARIS BARANG - LAPAS Inventory Management System</title>
    
    <!-- Bootstrap 5 & Font Awesome -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    
    <style>
        /* ===== VARIABEL GLOBAL - Tema Lapas ===== */
        :root {
            --lapas-dark: #1e2a3a;
            --lapas-primary: #0f3b5c;
            --lapas-accent: #2c7da0;
            --lapas-gold: #c9a03d;
            --lapas-border: #e2e8f0;
            --lapas-light: #f8fafc;
            --lapas-gray: #5a6e7c;
            --shadow-sm: 0 4px 12px rgba(0, 0, 0, 0.04);
            --shadow-md: 0 8px 24px rgba(0, 0, 0, 0.08);
            --transition: all 0.3s ease;
        }
        
        /* ===== BODY & BACKGROUND ===== */
        body {
            font-family: 'Inter', 'Segoe UI', system-ui, -apple-system, sans-serif;
            background: #f0f2f5;
            min-height: 100vh;
            margin: 0;
            padding: 0;
        }
        
        /* ===== HERO SECTION - Tema Lapas ===== */
        .hero-section {
            background: linear-gradient(135deg, var(--lapas-dark) 0%, var(--lapas-primary) 100%);
            color: white;
            padding: 60px 0 80px;
            margin-top: -20px;
            position: relative;
            overflow: hidden;
        }
        
        .hero-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: 
                repeating-linear-gradient(45deg, rgba(255,255,255,0.02) 0px, rgba(255,255,255,0.02) 2px, transparent 2px, transparent 8px),
                radial-gradient(circle at 20% 80%, rgba(44,125,160,0.2) 0%, transparent 50%);
            pointer-events: none;
        }
        
        .hero-content {
            position: relative;
            z-index: 2;
            text-align: center;
            animation: fadeInUp 0.6s ease-out;
        }
        
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .hero-title {
            font-size: 2.8rem;
            font-weight: 700;
            margin-bottom: 20px;
            letter-spacing: -0.5px;
        }
        
        .hero-title i {
            color: var(--lapas-gold);
            margin-right: 12px;
        }
        
        .hero-subtitle {
            font-size: 1.2rem;
            font-weight: 400;
            max-width: 800px;
            margin: 0 auto 30px;
            line-height: 1.6;
            opacity: 0.95;
        }
        
        .hero-subtitle strong {
            color: var(--lapas-gold);
            font-weight: 600;
        }
        
        .user-greeting {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            background: rgba(255,255,255,0.12);
            padding: 12px 24px;
            border-radius: 50px;
            font-size: 1rem;
            font-weight: 500;
            backdrop-filter: blur(8px);
            border: 1px solid rgba(255,255,255,0.2);
        }
        
        .user-greeting i {
            color: var(--lapas-gold);
        }
        
        /* ===== STATS SECTION ===== */
        .stats-section {
            padding: 60px 0 40px;
        }
        
        .section-title {
            text-align: center;
            color: var(--lapas-dark);
            font-weight: 700;
            font-size: 2rem;
            margin-bottom: 50px;
            position: relative;
        }
        
        .section-title::after {
            content: '';
            position: absolute;
            bottom: -15px;
            left: 50%;
            transform: translateX(-50%);
            width: 80px;
            height: 4px;
            background: linear-gradient(90deg, var(--lapas-primary), var(--lapas-accent));
            border-radius: 4px;
        }
        
        .stat-card {
            background: white;
            border-radius: 20px;
            padding: 30px 20px;
            text-align: center;
            box-shadow: var(--shadow-sm);
            transition: var(--transition);
            border: 1px solid var(--lapas-border);
            height: 100%;
        }
        
        .stat-card:hover {
            transform: translateY(-8px);
            box-shadow: var(--shadow-md);
            border-color: var(--lapas-accent);
        }
        
        .stat-icon {
            width: 70px;
            height: 70px;
            margin: 0 auto 20px;
            background: linear-gradient(135deg, var(--lapas-primary), var(--lapas-accent));
            border-radius: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
            color: white;
            transition: var(--transition);
        }
        
        .stat-card:hover .stat-icon {
            transform: scale(1.05) rotate(5deg);
            border-radius: 24px;
        }
        
        .stat-header {
            font-size: 1rem;
            font-weight: 600;
            color: var(--lapas-gray);
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 15px;
        }
        
        .stat-number {
            font-size: 2.8rem;
            font-weight: 800;
            color: var(--lapas-dark);
            margin-bottom: 5px;
            line-height: 1;
        }
        
        .stat-label {
            font-size: 0.85rem;
            color: var(--lapas-gray);
        }
        
        /* ===== FEATURES SECTION ===== */
        .features-section {
            padding: 60px 0;
            background: white;
        }
        
        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 30px;
            margin-top: 20px;
        }
        
        .feature-card {
            background: var(--lapas-light);
            border-radius: 20px;
            padding: 30px 25px;
            transition: var(--transition);
            border: 1px solid var(--lapas-border);
            text-align: center;
        }
        
        .feature-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-md);
            border-color: var(--lapas-accent);
        }
        
        .feature-icon {
            width: 65px;
            height: 65px;
            margin: 0 auto 20px;
            background: linear-gradient(135deg, var(--lapas-primary), var(--lapas-accent));
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
            color: white;
        }
        
        .feature-title {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--lapas-dark);
            margin-bottom: 12px;
        }
        
        .feature-description {
            color: var(--lapas-gray);
            font-size: 0.9rem;
            line-height: 1.6;
        }
        
        /* ===== QUICK ACCESS SECTION ===== */
        .quick-access {
            padding: 40px 0 60px;
        }
        
        .quick-card {
            background: white;
            border-radius: 20px;
            padding: 25px;
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--lapas-border);
            transition: var(--transition);
            height: 100%;
            display: flex;
            align-items: center;
            gap: 20px;
        }
        
        .quick-card:hover {
            transform: translateX(5px);
            border-left: 4px solid var(--lapas-accent);
            box-shadow: var(--shadow-md);
        }
        
        .quick-icon {
            width: 55px;
            height: 55px;
            background: rgba(44,125,160,0.1);
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            color: var(--lapas-accent);
            flex-shrink: 0;
        }
        
        .quick-content h4 {
            font-size: 1.1rem;
            font-weight: 700;
            color: var(--lapas-dark);
            margin-bottom: 5px;
        }
        
        .quick-content p {
            font-size: 0.85rem;
            color: var(--lapas-gray);
            margin: 0;
        }
        
        /* ===== WELCOME PANEL ===== */
        .welcome-panel {
            background: linear-gradient(135deg, var(--lapas-light) 0%, white 100%);
            border-radius: 24px;
            padding: 40px;
            margin: 40px auto;
            max-width: 1200px;
            border: 1px solid var(--lapas-border);
            box-shadow: var(--shadow-sm);
        }
        
        .welcome-title {
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--lapas-dark);
            margin-bottom: 20px;
        }
        
        .welcome-content {
            color: var(--lapas-gray);
            font-size: 1rem;
            line-height: 1.7;
        }
        
        /* ===== FOOTER ===== */
        .footer {
            background: var(--lapas-dark);
            color: white;
            padding: 40px 0 30px;
            margin-top: 60px;
            text-align: center;
        }
        
        .footer-logo {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 15px;
        }
        
        .footer-logo i {
            color: var(--lapas-gold);
            margin-right: 10px;
        }
        
        .footer-text {
            opacity: 0.8;
            max-width: 500px;
            margin: 0 auto;
            font-size: 0.9rem;
        }
        
        .copyright {
            margin-top: 25px;
            opacity: 0.6;
            font-size: 0.8rem;
        }
        
        /* ===== RESPONSIVE ===== */
        @media (max-width: 992px) {
            .hero-title {
                font-size: 2.2rem;
            }
            
            .hero-subtitle {
                font-size: 1.1rem;
                padding: 0 20px;
            }
            
            .section-title {
                font-size: 1.8rem;
            }
            
            .stat-number {
                font-size: 2.2rem;
            }
        }
        
        @media (max-width: 768px) {
            .hero-section {
                padding: 40px 0 60px;
            }
            
            .hero-title {
                font-size: 1.8rem;
            }
            
            .features-grid {
                grid-template-columns: 1fr;
                gap: 20px;
            }
            
            .welcome-panel {
                padding: 25px;
                margin: 30px 20px;
            }
            
            .quick-card {
                flex-direction: column;
                text-align: center;
            }
        }
        
        /* ===== UTILITY ===== */
        .container {
            max-width: 1280px;
            padding: 0 24px;
        }
        
        a {
            text-decoration: none;
        }
    </style>
</head>

<body>
    <!-- Include Header -->
    <?php include 'header.php'; ?>
    
    <!-- Hero Section -->
    <section class="hero-section">
        <div class="container">
            <div class="hero-content">
                <h1 class="hero-title">
                    <i class="fas fa-boxes"></i>
                    INVENTARIS BARANG LAPAS
                </h1>
                <div class="hero-subtitle">
                    Sistem Manajemen Inventaris Barang <strong>Lembaga Pemasyarakatan</strong><br>
                    Kelola stok, status, dan informasi barang dengan mudah dan terintegrasi
                </div>
                <div class="user-greeting">
                    <i class="fas fa-user-shield"></i>
                    Selamat datang, <?php echo htmlspecialchars($_SESSION['username']); ?>
                </div>
            </div>
        </div>
    </section>
    
    <!-- HAPUS JIKA INGIN MENAMPILKAN DASHBOARD -->
    <!-- <section class="stats-section">
        <div class="container">
            <h2 class="section-title">Dashboard Inventaris</h2>
            
            <?php
            // Query data dari database barang
            $sqlBarang = "SELECT COUNT(*) AS count FROM barang";
            $sqlBarangTersedia = "SELECT COUNT(*) AS count FROM barang WHERE status = 'Tersedia'";
            $sqlBarangHabis = "SELECT COUNT(*) AS count FROM barang WHERE status = 'Habis'";
            $sqlTotalStok = "SELECT SUM(stok) AS total FROM barang";
            $sqlUser = "SELECT COUNT(*) AS count FROM user";
            
            $resultBarang = $conn->query($sqlBarang);
            $resultBarangTersedia = $conn->query($sqlBarangTersedia);
            $resultBarangHabis = $conn->query($sqlBarangHabis);
            $resultTotalStok = $conn->query($sqlTotalStok);
            $resultUser = $conn->query($sqlUser);
            
            $barangCount = $resultBarang ? $resultBarang->fetch_assoc()['count'] : 0;
            $barangTersedia = $resultBarangTersedia ? $resultBarangTersedia->fetch_assoc()['count'] : 0;
            $barangHabis = $resultBarangHabis ? $resultBarangHabis->fetch_assoc()['count'] : 0;
            $totalStok = $resultTotalStok ? $resultTotalStok->fetch_assoc()['total'] : 0;
            ?>
            
            <div class="row g-4">
                <div class="col-lg-3 col-md-6">
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-boxes"></i>
                        </div>
                        <div class="stat-header">TOTAL BARANG</div>
                        <div class="stat-number" data-count="<?php echo $barangCount; ?>">0</div>
                        <div class="stat-label">Jenis Barang Terdaftar</div>
                    </div>
                </div>
                
                <div class="col-lg-3 col-md-6">
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <div class="stat-header">BARANG TERSEDIA</div>
                        <div class="stat-number" data-count="<?php echo $barangTersedia; ?>">0</div>
                        <div class="stat-label">Dalam Kondisi Siap Pakai</div>
                    </div>
                </div>
                
                <div class="col-lg-3 col-md-6">
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-times-circle"></i>
                        </div>
                        <div class="stat-header">BARANG HABIS</div>
                        <div class="stat-number" data-count="<?php echo $barangHabis; ?>">0</div>
                        <div class="stat-label">Perlu Pengadaan Ulang</div>
                    </div>
                </div>
                
                <div class="col-lg-3 col-md-6">
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-cubes"></i>
                        </div>
                        <div class="stat-header">TOTAL STOK</div>
                        <div class="stat-number" data-count="<?php echo $totalStok; ?>">0</div>
                        <div class="stat-label">Jumlah Unit Barang</div>
                    </div>
                </div>
            </div>
        </div>
    </section>      -->
    
    <!-- Welcome Panel -->
    <div class="container">
        <div class="welcome-panel">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h3 class="welcome-title">
                        <i class="fas fa-warehouse me-2" style="color: var(--lapas-accent);"></i>
                        Sistem Manajemen Inventaris Barang
                    </h3>
                    <div class="welcome-content">
                        <p>Sistem ini dirancang untuk membantu Lembaga Pemasyarakatan dalam mengelola inventaris barang secara efektif dan efisien. Dengan sistem ini, Anda dapat:</p>
                        <ul class="mt-2">
                            <li><i class="fas fa-check-circle me-2" style="color: var(--lapas-accent);"></i>Mencatat dan memantau stok barang secara real-time</li>
                            <li><i class="fas fa-check-circle me-2" style="color: var(--lapas-accent);"></i>Mengelola status ketersediaan barang (Tersedia/Habis/Rusak/Dalam Perbaikan)</li>
                            <li><i class="fas fa-check-circle me-2" style="color: var(--lapas-accent);"></i>Mendapatkan informasi lengkap tentang setiap barang</li>
                            <li><i class="fas fa-check-circle me-2" style="color: var(--lapas-accent);"></i>Memudahkan proses pencarian dan pelaporan inventaris</li>
                        </ul>
                    </div>
                </div>
                <div class="col-md-4 text-center mt-4 mt-md-0">
                    <i class="fas fa-clipboard-list" style="font-size: 100px; color: rgba(44,125,160,0.2);"></i>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Features Section -->
    <section class="features-section">
        <div class="container">
            <h2 class="section-title">Fitur Unggulan</h2>
            <div class="features-grid">
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-database"></i>
                    </div>
                    <h3 class="feature-title">Manajemen Data Kantor Personal</h3>
                    <p class="feature-description">Kelola data barang dengan mudah, termasuk kode barang, nama, stok, status, dan keterangan lengkap.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <h3 class="feature-title">Manajemen Data ATK</h3>
                    <p class="feature-description">Kelola data ATK dengan mudah, termasuk kode barang, nama, stok, status, dan keterangan lengkap.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-file-alt"></i>
                    </div>
                    <h3 class="feature-title">Laporan Inventaris</h3>
                    <p class="feature-description">Hasilkan laporan inventaris yang rapi dan profesional untuk keperluan administrasi.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-shield-alt"></i>
                    </div>
                    <h3 class="feature-title">Keamanan Data</h3>
                    <p class="feature-description">Sistem dilengkapi dengan keamanan data dan manajemen akses berbasis peran user.</p>
                </div>
            </div>
        </div>
    </section>
    
    <!-- Quick Access Section -->
    <?php if (isset($_SESSION['level']) && $_SESSION['level'] == 'admin') : ?>
    <section class="quick-access">
        <div class="container">
            <h2 class="section-title">Akses Cepat</h2>
            <div class="row g-3">
                <div class="col-md-6">
                    <a href="barang.php">
                        <div class="quick-card">
                            <div class="quick-icon">
                                <i class="fas fa-boxes"></i>
                            </div>
                            <div class="quick-content">
                                <h4>Kelola Data Kantor Personal</h4>
                                <p>Tambah, edit, atau hapus data inventaris kantor personal</p>
                            </div>
                        </div>
                    </a>
                </div>
                <div class="col-md-6">
                    <a href="atk.php">
                        <div class="quick-card">
                            <div class="quick-icon">
                                <i class="fas fa-chart-simple"></i>
                            </div>
                            <div class="quick-content">
                                <h4>Kelola Data ATK</h4>
                                <p>Tambah, edit, atau hapus data inventaris ATK</p>
                            </div>
                        </div>
                    </a>
                </div>
                <div class="col-md-6">
                    <a href="laporan.php">
                        <div class="quick-card">
                            <div class="quick-icon">
                                <i class="fas fa-print"></i>
                            </div>
                            <div class="quick-content">
                                <h4>Cetak Laporan</h4>
                                <p>Cetak laporan inventaris barang</p>
                            </div>
                        </div>
                    </a>
                </div>
                <div class="col-md-6">
                    <a href="tambah_admin.php">
                        <div class="quick-card">
                            <div class="quick-icon">
                                <i class="fas fa-users"></i>
                            </div>
                            <div class="quick-content">
                                <h4>Tambah Admin / User</h4>
                                <p>Atur akses dan hak pengguna sistem</p>
                            </div>
                        </div>
                    </a>
                </div>
            </div>
        </div>
    </section>
    <?php endif; ?>
    
    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="footer-logo">
                <i class="fas fa-boxes"></i>
                INVENTARIS BARANG LAPAS
            </div>
            <p class="footer-text">
                Sistem Manajemen Inventaris Barang Lembaga Pemasyarakatan<br>
                Kelola Stok Barang dengan Mudah dan Efisien
            </p>
            <p class="copyright">
                <i class="fas fa-copyright"></i> <?php echo date('Y'); ?> - Astro
            </p>
        </div>
    </footer>
    
    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Counter animation untuk statistik
            const statNumbers = document.querySelectorAll('.stat-number');
            statNumbers.forEach(stat => {
                const target = parseInt(stat.getAttribute('data-count'));
                if (!isNaN(target) && target >= 0) {
                    let current = 0;
                    const increment = Math.max(1, Math.ceil(target / 40));
                    const timer = setInterval(() => {
                        current += increment;
                        if (current >= target) {
                            current = target;
                            clearInterval(timer);
                        }
                        stat.textContent = current.toLocaleString('id-ID');
                    }, 30);
                }
            });
            
            // Fade in animation untuk cards
            const cards = document.querySelectorAll('.stat-card, .feature-card, .quick-card');
            cards.forEach((card, index) => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(20px)';
                
                setTimeout(() => {
                    card.style.transition = 'all 0.5s ease';
                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0)';
                }, index * 100);
            });
            
            // Highlight menu aktif
            const currentPage = window.location.pathname.split('/').pop();
            if (currentPage === 'index.php' || currentPage === '') {
                const homeLink = document.querySelector('a[href="index.php"]');
                if (homeLink) {
                    homeLink.classList.add('active');
                }
            }
        });
    </script>
</body>
</html>