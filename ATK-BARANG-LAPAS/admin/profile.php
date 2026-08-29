<?php
// profile.php
// Halaman profil user untuk sistem inventaris LAPAS

// Mulai session di awal file
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include '../assets/conn/config.php';

// Cek apakah user sudah login
if (!isset($_SESSION['username'])) {
    header("Location: ../index.php");
    exit();
}

$username = $_SESSION['username'];
$user_level = $_SESSION['level'];

// Ambil data user untuk profil
$user_query = "SELECT id, username, level FROM user WHERE username = '$username'";
$user_result = mysqli_query($conn, $user_query);

if (!$user_result || mysqli_num_rows($user_result) == 0) {
    die("Error: User tidak ditemukan!");
}

$user_data = mysqli_fetch_assoc($user_result);

// Variabel untuk pesan
$message = '';
$message_type = '';

// Proses ubah password
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $current_password = mysqli_real_escape_string($conn, $_POST['current_password']);
    $new_password = mysqli_real_escape_string($conn, $_POST['new_password']);
    $confirm_password = mysqli_real_escape_string($conn, $_POST['confirm_password']);
    
    // Validasi input
    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $message = 'Semua field harus diisi!';
        $message_type = 'danger';
    } elseif ($new_password !== $confirm_password) {
        $message = 'Password baru dan konfirmasi password tidak cocok!';
        $message_type = 'danger';
    } elseif (strlen($new_password) < 4) {
        $message = 'Password baru minimal 4 karakter!';
        $message_type = 'danger';
    } else {
        // Ambil password lama dari database
        $query = "SELECT password FROM user WHERE username = '$username'";
        $result = mysqli_query($conn, $query);
        
        if ($result && mysqli_num_rows($result) > 0) {
            $user_data_pass = mysqli_fetch_assoc($result);
            $stored_password = $user_data_pass['password'];
            
            // Verifikasi password lama (plaintext)
            if ($current_password === $stored_password) {
                // Update password baru
                $update_query = "UPDATE user SET password = '$new_password' WHERE username = '$username'";
                
                if (mysqli_query($conn, $update_query)) {
                    $message = 'Password berhasil diubah!';
                    $message_type = 'success';
                    
                    // Log aktivitas
                    logAktivitas($username, 'Mengubah password');
                    
                    // Reset form fields
                    $_POST = array();
                } else {
                    $message = 'Gagal mengubah password. Silakan coba lagi.';
                    $message_type = 'danger';
                }
            } else {
                $message = 'Password saat ini salah!';
                $message_type = 'danger';
            }
        } else {
            $message = 'User tidak ditemukan!';
            $message_type = 'danger';
        }
    }
}

// Hitung statistik berdasarkan level user
$total_barang = 0;
$total_pengajuan = 0;
$total_menunggu = 0;
$total_disetujui = 0;

if ($user_level == 'admin') {
    // Admin: hitung semua data
    $barang_query = "SELECT COUNT(*) as total FROM barang";
    $barang_result = mysqli_query($conn, $barang_query);
    if ($barang_result) {
        $barang_data = mysqli_fetch_assoc($barang_result);
        $total_barang = $barang_data['total'];
    }
    
    $pengajuan_query = "SELECT COUNT(*) as total FROM pengajuan";
    $pengajuan_result = mysqli_query($conn, $pengajuan_query);
    if ($pengajuan_result) {
        $pengajuan_data = mysqli_fetch_assoc($pengajuan_result);
        $total_pengajuan = $pengajuan_data['total'];
    }
    
    $menunggu_query = "SELECT COUNT(*) as total FROM pengajuan WHERE status = 'Menunggu Verifikasi'";
    $menunggu_result = mysqli_query($conn, $menunggu_query);
    if ($menunggu_result) {
        $menunggu_data = mysqli_fetch_assoc($menunggu_result);
        $total_menunggu = $menunggu_data['total'];
    }
    
    $disetujui_query = "SELECT COUNT(*) as total FROM pengajuan WHERE status = 'Disetujui'";
    $disetujui_result = mysqli_query($conn, $disetujui_query);
    if ($disetujui_result) {
        $disetujui_data = mysqli_fetch_assoc($disetujui_result);
        $total_disetujui = $disetujui_data['total'];
    }
} else {
    // User: hitung data milik sendiri
    $pengajuan_query = "SELECT COUNT(*) as total FROM pengajuan WHERE diajukan_oleh = '$username'";
    $pengajuan_result = mysqli_query($conn, $pengajuan_query);
    if ($pengajuan_result) {
        $pengajuan_data = mysqli_fetch_assoc($pengajuan_result);
        $total_pengajuan = $pengajuan_data['total'];
    }
    
    $menunggu_query = "SELECT COUNT(*) as total FROM pengajuan WHERE diajukan_oleh = '$username' AND status = 'Menunggu Verifikasi'";
    $menunggu_result = mysqli_query($conn, $menunggu_query);
    if ($menunggu_result) {
        $menunggu_data = mysqli_fetch_assoc($menunggu_result);
        $total_menunggu = $menunggu_data['total'];
    }
    
    $disetujui_query = "SELECT COUNT(*) as total FROM pengajuan WHERE diajukan_oleh = '$username' AND status = 'Disetujui'";
    $disetujui_result = mysqli_query($conn, $disetujui_query);
    if ($disetujui_result) {
        $disetujui_data = mysqli_fetch_assoc($disetujui_result);
        $total_disetujui = $disetujui_data['total'];
    }
}

// Hitung total user (untuk admin)
$total_user = 0;
if ($user_level == 'admin') {
    $user_count_query = "SELECT COUNT(*) as total FROM user";
    $user_count_result = mysqli_query($conn, $user_count_query);
    if ($user_count_result) {
        $user_count_data = mysqli_fetch_assoc($user_count_result);
        $total_user = $user_count_data['total'];
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil - Inventaris LAPAS</title>
    
    <!-- Bootstrap 5 & Font Awesome -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    
    <style>
        :root {
            --lapas-dark: #1e2a3a;
            --lapas-primary: #0f3b5c;
            --lapas-accent: #2c7da0;
            --lapas-gold: #c9a03d;
            --lapas-border: #e2e8f0;
            --lapas-light: #f8fafc;
            --shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            --transition: all 0.3s ease;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f0f2f5;
            color: var(--lapas-dark);
            padding-top: 80px;
        }
        
        .profile-container {
            max-width: 1000px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .profile-card {
            background: white;
            border-radius: 20px;
            box-shadow: var(--shadow);
            overflow: hidden;
            margin-bottom: 30px;
        }
        
        .profile-header {
            background: linear-gradient(135deg, var(--lapas-primary), var(--lapas-accent));
            color: white;
            padding: 30px;
            text-align: center;
            position: relative;
        }
        
        .profile-avatar {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background: linear-gradient(45deg, var(--lapas-gold), #e6b422);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            border: 5px solid white;
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
        
        .profile-avatar-text {
            font-size: 48px;
            font-weight: bold;
            color: white;
        }
        
        .profile-name {
            font-size: 28px;
            font-weight: 600;
            margin-bottom: 5px;
        }
        
        .profile-role {
            background: rgba(255,255,255,0.2);
            padding: 5px 15px;
            border-radius: 20px;
            display: inline-block;
            font-size: 14px;
            font-weight: 500;
        }
        
        .profile-body {
            padding: 30px;
        }
        
        .profile-section {
            margin-bottom: 30px;
        }
        
        .section-title {
            font-size: 20px;
            font-weight: 600;
            color: var(--lapas-primary);
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid var(--lapas-accent);
            display: flex;
            align-items: center;
        }
        
        .section-title i {
            margin-right: 10px;
            color: var(--lapas-accent);
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
        }
        
        .info-item {
            background: var(--lapas-light);
            padding: 15px;
            border-radius: 12px;
            border-left: 4px solid var(--lapas-accent);
        }
        
        .info-label {
            font-size: 13px;
            color: #6c757d;
            margin-bottom: 5px;
            font-weight: 500;
        }
        
        .info-value {
            font-size: 16px;
            color: var(--lapas-dark);
            font-weight: 600;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-top: 10px;
        }
        
        .stat-card {
            background: linear-gradient(135deg, white, var(--lapas-light));
            padding: 20px;
            border-radius: 12px;
            text-align: center;
            border: 1px solid var(--lapas-border);
            transition: var(--transition);
        }
        
        .stat-card:hover {
            transform: translateY(-3px);
            box-shadow: var(--shadow);
        }
        
        .stat-number {
            font-size: 32px;
            font-weight: 700;
            color: var(--lapas-accent);
            margin: 10px 0;
        }
        
        .stat-label {
            font-size: 13px;
            color: #6c757d;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .password-form {
            background: var(--lapas-light);
            padding: 25px;
            border-radius: 12px;
            border: 1px solid var(--lapas-border);
        }
        
        .form-group {
            margin-bottom: 20px;
            position: relative;
        }
        
        .form-label {
            font-weight: 600;
            color: var(--lapas-primary);
            margin-bottom: 8px;
            display: flex;
            align-items: center;
        }
        
        .form-label i {
            margin-right: 8px;
            color: var(--lapas-accent);
        }
        
        .form-control {
            border: 2px solid #dee2e6;
            border-radius: 10px;
            padding: 12px 45px 12px 15px;
            font-size: 14px;
            transition: var(--transition);
        }
        
        .form-control:focus {
            border-color: var(--lapas-accent);
            box-shadow: 0 0 0 0.25rem rgba(44, 125, 160, 0.25);
        }
        
        .btn-submit {
            background: linear-gradient(135deg, var(--lapas-primary), var(--lapas-accent));
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 10px;
            font-weight: 600;
            transition: var(--transition);
            display: inline-flex;
            align-items: center;
            gap: 10px;
        }
        
        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(44, 125, 160, 0.3);
            color: white;
        }
        
        .btn-back {
            background: #6c757d;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            font-weight: 600;
            transition: var(--transition);
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .btn-back:hover {
            background: #5a6268;
            color: white;
        }
        
        .alert-message {
            border-radius: 12px;
            border: none;
            padding: 15px 20px;
            margin-bottom: 20px;
            font-weight: 500;
        }
        
        .toggle-password {
            position: absolute;
            right: 15px;
            top: 40px;
            background: none;
            border: none;
            color: #6c757d;
            cursor: pointer;
            z-index: 10;
        }
        
        .toggle-password:hover {
            color: var(--lapas-accent);
        }
        
        .password-strength {
            margin-top: 10px;
        }
        
        .strength-meter {
            height: 5px;
            background: #e9ecef;
            border-radius: 3px;
            margin-top: 5px;
            overflow: hidden;
        }
        
        .strength-fill {
            height: 100%;
            width: 0%;
            border-radius: 3px;
            transition: width 0.3s ease;
        }
        
        .strength-weak { background-color: #dc3545; }
        .strength-medium { background-color: #fd7e14; }
        .strength-strong { background-color: #28a745; }
        
        .password-requirements {
            font-size: 12px;
            color: #6c757d;
            margin-top: 10px;
        }
        
        .requirement-item {
            display: flex;
            align-items: center;
            margin-bottom: 5px;
        }
        
        .requirement-item i {
            margin-right: 8px;
            font-size: 11px;
        }
        
        .requirement-valid {
            color: #28a745;
        }
        
        .requirement-invalid {
            color: #6c757d;
        }
        
        .badge-custom {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        
        @media (max-width: 768px) {
            .profile-container {
                padding: 15px;
            }
            
            .profile-header {
                padding: 20px;
            }
            
            .profile-body {
                padding: 20px;
            }
            
            .profile-avatar {
                width: 100px;
                height: 100px;
            }
            
            .profile-avatar-text {
                font-size: 36px;
            }
            
            .info-grid, .stats-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>

<?php include 'header.php'; ?>

<div class="profile-container">
    <!-- Pesan Notifikasi -->
    <?php if ($message): ?>
    <div class="alert alert-<?php echo $message_type; ?> alert-message alert-dismissible fade show" role="alert">
        <i class="fas <?php echo $message_type == 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'; ?> me-2"></i>
        <?php echo $message; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php endif; ?>
    
    <div class="profile-card">
        <!-- Header Profil -->
        <div class="profile-header">
            <div class="profile-avatar">
                <div class="profile-avatar-text">
                    <?php echo strtoupper(substr($username, 0, 1)); ?>
                </div>
            </div>
            <h1 class="profile-name"><?php echo htmlspecialchars($username); ?></h1>
            <div class="profile-role">
                <i class="fas fa-user-tag me-2"></i>
                <?php echo ($user_level == 'admin') ? 'Administrator' : 'Pengguna'; ?>
            </div>
        </div>
        
        <!-- Body Profil -->
        <div class="profile-body">
            <!-- Informasi User -->
            <div class="profile-section">
                <h3 class="section-title">
                    <i class="fas fa-info-circle"></i>
                    Informasi Akun
                </h3>
                <div class="info-grid">
                    <div class="info-item">
                        <div class="info-label">User ID</div>
                        <div class="info-value"><?php echo $user_data['id']; ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Username</div>
                        <div class="info-value"><?php echo htmlspecialchars($user_data['username']); ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Level Akses</div>
                        <div class="info-value">
                            <span class="badge-custom" style="background: <?php echo ($user_level == 'admin') ? '#0f3b5c' : '#28a745'; ?>; color: white;">
                                <i class="fas fa-<?php echo ($user_level == 'admin') ? 'shield-alt' : 'user'; ?> me-1"></i>
                                <?php echo ($user_level == 'admin') ? 'Administrator' : 'User'; ?>
                            </span>
                        </div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Status</div>
                        <div class="info-value">
                            <span class="badge-custom" style="background: #28a745; color: white;">
                                <i class="fas fa-check-circle me-1"></i>
                                Aktif
                            </span>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Statistik Sistem -->
            <div class="profile-section">
                <h3 class="section-title">
                    <i class="fas fa-chart-bar"></i>
                    Statistik
                </h3>
                
                <?php if ($user_level == 'admin'): ?>
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-number"><?php echo $total_barang; ?></div>
                        <div class="stat-label">Total Barang</div>
                        <i class="fas fa-boxes fa-2x text-muted mt-2"></i>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number"><?php echo $total_pengajuan; ?></div>
                        <div class="stat-label">Total Pengajuan</div>
                        <i class="fas fa-file-alt fa-2x text-muted mt-2"></i>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number" style="color: #fd7e14;"><?php echo $total_menunggu; ?></div>
                        <div class="stat-label">Menunggu Verifikasi</div>
                        <i class="fas fa-clock fa-2x text-muted mt-2"></i>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number" style="color: #28a745;"><?php echo $total_disetujui; ?></div>
                        <div class="stat-label">Disetujui</div>
                        <i class="fas fa-check-circle fa-2x text-muted mt-2"></i>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number"><?php echo $total_user; ?></div>
                        <div class="stat-label">Total Pengguna</div>
                        <i class="fas fa-users fa-2x text-muted mt-2"></i>
                    </div>
                </div>
                <?php else: ?>
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-number"><?php echo $total_pengajuan; ?></div>
                        <div class="stat-label">Total Pengajuan</div>
                        <i class="fas fa-file-alt fa-2x text-muted mt-2"></i>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number" style="color: #fd7e14;"><?php echo $total_menunggu; ?></div>
                        <div class="stat-label">Menunggu Verifikasi</div>
                        <i class="fas fa-clock fa-2x text-muted mt-2"></i>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number" style="color: #28a745;"><?php echo $total_disetujui; ?></div>
                        <div class="stat-label">Disetujui</div>
                        <i class="fas fa-check-circle fa-2x text-muted mt-2"></i>
                    </div>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- Form Ubah Password -->
            <div class="profile-section">
                <h3 class="section-title">
                    <i class="fas fa-key"></i>
                    Ubah Password
                </h3>
                <div class="password-form">
                    <form method="POST" action="" id="passwordForm">
                        <div class="form-group">
                            <label class="form-label" for="current_password">
                                <i class="fas fa-lock"></i>
                                Password Saat Ini
                            </label>
                            <input type="password" 
                                   class="form-control" 
                                   id="current_password" 
                                   name="current_password" 
                                   required
                                   placeholder="Masukkan password saat ini">
                            <button type="button" class="toggle-password" data-target="current_password">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label" for="new_password">
                                <i class="fas fa-key"></i>
                                Password Baru
                            </label>
                            <input type="password" 
                                   class="form-control" 
                                   id="new_password" 
                                   name="new_password" 
                                   required
                                   placeholder="Masukkan password baru (min. 4 karakter)"
                                   onkeyup="checkPasswordStrength()">
                            <button type="button" class="toggle-password" data-target="new_password">
                                <i class="fas fa-eye"></i>
                            </button>
                            
                            <!-- Password Strength Meter -->
                            <div class="password-strength">
                                <div class="d-flex justify-content-between">
                                    <span>Kekuatan Password:</span>
                                    <span id="strengthText">Lemah</span>
                                </div>
                                <div class="strength-meter">
                                    <div class="strength-fill" id="strengthBar"></div>
                                </div>
                            </div>
                            
                            <!-- Password Requirements -->
                            <div class="password-requirements">
                                <div class="requirement-item">
                                    <i class="fas fa-check requirement-invalid" id="reqLength"></i>
                                    <span>Minimal 4 karakter</span>
                                </div>
                                <div class="requirement-item">
                                    <i class="fas fa-check requirement-invalid" id="reqMatch"></i>
                                    <span>Password baru dan konfirmasi cocok</span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label" for="confirm_password">
                                <i class="fas fa-key"></i>
                                Konfirmasi Password Baru
                            </label>
                            <input type="password" 
                                   class="form-control" 
                                   id="confirm_password" 
                                   name="confirm_password" 
                                   required
                                   placeholder="Konfirmasi password baru"
                                   onkeyup="checkPasswordMatch()">
                            <button type="button" class="toggle-password" data-target="confirm_password">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                        
                        <div class="d-flex justify-content-between align-items-center mt-4 flex-wrap gap-2">
                            <a href="index.php" class="btn-back">
                                <i class="fas fa-arrow-left"></i>
                                Kembali ke Dashboard
                            </a>
                            <button type="submit" 
                                    name="change_password" 
                                    class="btn-submit"
                                    id="submitBtn">
                                <i class="fas fa-save"></i>
                                Simpan Password Baru
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Info Sistem -->
            <div class="profile-section">
                <h3 class="section-title">
                    <i class="fas fa-info-circle"></i>
                    Informasi Sistem
                </h3>
                <div class="alert alert-info" style="border-radius: 12px;">
                    <h5><i class="fas fa-exclamation-circle me-2"></i>Catatan Penting:</h5>
                    <ul class="mb-0">
                        <li>Password minimal 4 karakter</li>
                        <li>Gunakan password yang mudah diingat namun aman</li>
                        <li>Jangan bagikan password Anda kepada siapapun</li>
                        <li>Hubungi administrator jika lupa password</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
<script>
    function checkPasswordStrength() {
        var password = document.getElementById('new_password').value;
        var strengthBar = document.getElementById('strengthBar');
        var strengthText = document.getElementById('strengthText');
        var reqLength = document.getElementById('reqLength');
        
        // Reset
        strengthBar.style.width = '0%';
        strengthBar.className = 'strength-fill';
        strengthText.textContent = 'Lemah';
        
        // Check length (minimal 4 karakter)
        if (password.length >= 4) {
            reqLength.className = 'fas fa-check requirement-valid';
        } else {
            reqLength.className = 'fas fa-check requirement-invalid';
        }
        
        // Calculate strength
        var strength = 0;
        if (password.length >= 4) strength += 1;
        if (password.length >= 6) strength += 1;
        if (/[A-Z]/.test(password)) strength += 1;
        if (/[0-9]/.test(password)) strength += 1;
        if (/[^A-Za-z0-9]/.test(password)) strength += 1;
        
        // Update strength meter
        if (password.length > 0) {
            var percentage = (strength / 5) * 100;
            strengthBar.style.width = percentage + '%';
            
            if (strength <= 1) {
                strengthBar.className = 'strength-fill strength-weak';
                strengthText.textContent = 'Lemah';
            } else if (strength <= 3) {
                strengthBar.className = 'strength-fill strength-medium';
                strengthText.textContent = 'Sedang';
            } else {
                strengthBar.className = 'strength-fill strength-strong';
                strengthText.textContent = 'Kuat';
            }
        }
        
        checkPasswordMatch();
    }
    
    function checkPasswordMatch() {
        var password = document.getElementById('new_password').value;
        var confirm = document.getElementById('confirm_password').value;
        var reqMatch = document.getElementById('reqMatch');
        var submitBtn = document.getElementById('submitBtn');
        
        if (confirm.length > 0) {
            if (password === confirm) {
                reqMatch.className = 'fas fa-check requirement-valid';
                submitBtn.disabled = false;
            } else {
                reqMatch.className = 'fas fa-check requirement-invalid';
                submitBtn.disabled = true;
            }
        } else {
            reqMatch.className = 'fas fa-check requirement-invalid';
            submitBtn.disabled = false;
        }
    }
    
    // Toggle password visibility
    $(document).ready(function() {
        $('.toggle-password').click(function() {
            var targetId = $(this).data('target');
            var input = $('#' + targetId);
            var icon = $(this).find('i');
            
            if (input.attr('type') === 'password') {
                input.attr('type', 'text');
                icon.removeClass('fa-eye').addClass('fa-eye-slash');
            } else {
                input.attr('type', 'password');
                icon.removeClass('fa-eye-slash').addClass('fa-eye');
            }
        });
        
        checkPasswordStrength();
        
        // Form validation
        $('#passwordForm').submit(function(e) {
            var current = $('#current_password').val();
            var newPass = $('#new_password').val();
            var confirm = $('#confirm_password').val();
            
            if (!current || !newPass || !confirm) {
                e.preventDefault();
                alert('Semua field harus diisi!');
                return false;
            }
            
            if (newPass !== confirm) {
                e.preventDefault();
                alert('Password baru dan konfirmasi password tidak cocok!');
                return false;
            }
            
            if (newPass.length < 4) {
                e.preventDefault();
                alert('Password baru minimal 4 karakter!');
                return false;
            }
            
            return true;
        });
    });
</script>

</body>
</html>