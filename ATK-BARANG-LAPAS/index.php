<?php
// index.php - Halaman Login Sistem Inventaris LAPAS
// Versi Final

session_start();

// Jika sudah login, redirect ke halaman sesuai level
if (isset($_SESSION['login']) && $_SESSION['login'] == true) {
    if ($_SESSION['level'] == 'admin') {
        header("Location: admin/index.php");
        exit();
    } elseif ($_SESSION['level'] == 'user') {
        header("Location: admin/barang.php");
        exit();
    }
}

// Proses Login
if (isset($_GET['aksi']) && $_GET['aksi'] == 'login') {
    include 'assets/conn/config.php';

    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = mysqli_real_escape_string($conn, $_POST['password']);

    $query = mysqli_query($conn, "SELECT * FROM user WHERE username='$username' AND password='$password'");
    
    if (!$query) {
        die("Error: " . mysqli_error($conn));
    }

    $cek = mysqli_num_rows($query);
    if ($cek > 0) {
        $data = mysqli_fetch_array($query);

        $_SESSION['username'] = $data['username'];
        $_SESSION['level'] = $data['level'];
        $_SESSION['login'] = true;
        $_SESSION['user_id'] = $data['id'];

        // Catat log login
        $log_query = "INSERT INTO log_aktivitas (username, aktivitas, ip_address, user_agent, tanggal) 
                      VALUES ('{$data['username']}', 'Login ke sistem', '{$_SERVER['REMOTE_ADDR']}', '{$_SERVER['HTTP_USER_AGENT']}', NOW())";
        mysqli_query($conn, $log_query);

        if ($data['level'] == 'admin') {
            header("Location: admin/index.php");
            exit();
        } elseif ($data['level'] == 'user') {
            header("Location: admin/index.php");
            exit();
        } else {
            header("Location: index.php?pesan=level_tidak_dikenal");
            exit();
        }
    } else {
        header("Location: index.php?pesan=gagal");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Inventaris Barang Lembaga Pemasyarakatan</title>
    
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
            --lapas-gray: #5a6e7c;
            --shadow-sm: 0 4px 12px rgba(0, 0, 0, 0.04);
            --shadow-md: 0 8px 24px rgba(0, 0, 0, 0.08);
            --transition: all 0.3s ease;
        }
        
        body {
            font-family: 'Inter', 'Segoe UI', system-ui, -apple-system, sans-serif;
            min-height: 100vh;
            margin: 0;
            padding: 0;
            background: linear-gradient(135deg, #f5f7fa 0%, #e9eef3 100%);
            display: flex;
            justify-content: center;
            align-items: center;
            position: relative;
        }
        
        /* Background pattern - motif Lapas */
        body::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-image: 
                repeating-linear-gradient(45deg, rgba(44, 62, 80, 0.02) 0px, rgba(44, 62, 80, 0.02) 2px, transparent 2px, transparent 8px),
                repeating-linear-gradient(135deg, rgba(52, 152, 219, 0.02) 0px, rgba(52, 152, 219, 0.02) 1px, transparent 1px, transparent 6px);
            pointer-events: none;
        }
        
        .login-container {
            position: relative;
            z-index: 1;
            width: 100%;
            max-width: 440px;
            padding: 20px;
            animation: fadeInUp 0.5s ease-out;
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
        
        .login-card {
            background: white;
            border-radius: 24px;
            padding: 40px 36px;
            box-shadow: var(--shadow-md);
            border: 1px solid var(--lapas-border);
            transition: var(--transition);
        }
        
        .login-card:hover {
            box-shadow: 0 12px 32px rgba(0, 0, 0, 0.12);
        }
        
        /* Header Section */
        .login-header {
            text-align: center;
            margin-bottom: 36px;
        }
        
        .logo-wrapper {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 24px;
        }
        
        .logo-icon {
            width: 70px;
            height: 70px;
            background: linear-gradient(135deg, var(--lapas-primary) 0%, var(--lapas-accent) 100%);
            border-radius: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 8px 20px rgba(15, 59, 92, 0.25);
        }
        
        .logo-icon i {
            font-size: 32px;
            color: white;
        }
        
        .login-title {
            font-size: 1.75rem;
            font-weight: 700;
            color: var(--lapas-dark);
            margin-bottom: 8px;
            letter-spacing: -0.3px;
        }
        
        .login-subtitle {
            color: var(--lapas-gray);
            font-size: 0.85rem;
            font-weight: 500;
            margin: 0;
        }
        
        .institution-badge {
            display: inline-block;
            background: #f1f5f9;
            padding: 6px 14px;
            border-radius: 40px;
            font-size: 0.7rem;
            font-weight: 600;
            color: var(--lapas-primary);
            margin-top: 12px;
        }
        
        .institution-badge i {
            margin-right: 6px;
            font-size: 0.7rem;
        }
        
        /* Form Styles */
        .form-group {
            margin-bottom: 24px;
        }
        
        .form-label {
            font-weight: 600;
            font-size: 0.8rem;
            color: var(--lapas-dark);
            margin-bottom: 8px;
            display: block;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .input-group-custom {
            position: relative;
        }
        
        .form-control-custom {
            width: 100%;
            padding: 12px 16px 12px 44px;
            border: 1.5px solid var(--lapas-border);
            border-radius: 12px;
            font-size: 0.95rem;
            transition: var(--transition);
            background: var(--lapas-light);
            color: var(--lapas-dark);
        }
        
        .form-control-custom:focus {
            outline: none;
            border-color: var(--lapas-accent);
            background: white;
            box-shadow: 0 0 0 3px rgba(44, 125, 160, 0.1);
        }
        
        .input-icon {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--lapas-gray);
            font-size: 1rem;
            transition: var(--transition);
            pointer-events: none;
        }
        
        .form-control-custom:focus + .input-icon {
            color: var(--lapas-accent);
        }
        
        /* Button */
        .btn-login {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, var(--lapas-primary) 0%, var(--lapas-accent) 100%);
            border: none;
            border-radius: 12px;
            color: white;
            font-weight: 700;
            font-size: 0.95rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            transition: var(--transition);
            margin-top: 8px;
            position: relative;
            overflow: hidden;
        }
        
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(15, 59, 92, 0.3);
        }
        
        .btn-login:active {
            transform: translateY(0);
        }
        
        .btn-login i {
            margin-right: 8px;
        }
        
        /* Links Container */
        .links-container {
            display: flex;
            justify-content: space-between;
            margin-top: 20px;
            font-size: 0.85rem;
        }
        
        .link-item {
            color: var(--lapas-accent);
            text-decoration: none;
            transition: var(--transition);
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        
        .link-item:hover {
            color: var(--lapas-primary);
            text-decoration: underline;
        }
        
        .link-item i {
            font-size: 0.8rem;
        }
        
        /* Alert Styles */
        .alert-custom {
            border-radius: 12px;
            padding: 12px 16px;
            margin-bottom: 24px;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 0.85rem;
            font-weight: 500;
            animation: slideIn 0.3s ease-out;
        }
        
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateX(-10px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }
        
        .alert-danger-custom {
            background: #fef2f2;
            border-left: 3px solid #dc2626;
            color: #991b1b;
        }
        
        .alert-warning-custom {
            background: #fffbeb;
            border-left: 3px solid #f59e0b;
            color: #92400e;
        }
        
        .alert-success-custom {
            background: #f0fdf4;
            border-left: 3px solid #22c55e;
            color: #166534;
        }
        
        .alert-custom i {
            font-size: 1rem;
        }
        
        /* Footer */
        .login-footer {
            text-align: center;
            margin-top: 32px;
            padding-top: 24px;
            border-top: 1px solid var(--lapas-border);
            color: var(--lapas-gray);
            font-size: 0.7rem;
        }
        
        .login-footer i {
            margin-right: 6px;
        }
        
        /* Loading State */
        .btn-login.loading {
            position: relative;
            color: transparent !important;
        }
        
        .btn-login.loading::after {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 20px;
            height: 20px;
            margin: -10px 0 0 -10px;
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-top-color: white;
            border-radius: 50%;
            animation: spin 0.6s linear infinite;
        }
        
        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }
        
        /* Responsive */
        @media (max-width: 576px) {
            .login-container {
                padding: 16px;
            }
            
            .login-card {
                padding: 32px 24px;
            }
            
            .logo-icon {
                width: 60px;
                height: 60px;
            }
            
            .logo-icon i {
                font-size: 28px;
            }
            
            .login-title {
                font-size: 1.5rem;
            }
            
            .form-control-custom {
                padding: 10px 14px 10px 42px;
                font-size: 0.9rem;
            }
            
            .links-container {
                flex-direction: column;
                gap: 12px;
                text-align: center;
            }
        }
        
        /* Simple decoration */
        .decoration-line {
            width: 50px;
            height: 3px;
            background: linear-gradient(90deg, var(--lapas-primary), var(--lapas-accent));
            margin: 16px auto 0;
            border-radius: 3px;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <!-- Header -->
            <div class="login-header">
                <div class="logo-wrapper">
                    <div class="logo-icon">
                        <i class="fas fa-boxes"></i>
                    </div>
                </div>
                <h1 class="login-title">Inventaris Barang</h1>
                <p class="login-subtitle">Lembaga Pemasyarakatan Tanjung Pati</p>
                <div class="decoration-line"></div>
            </div>
            
            <!-- Alert Messages -->
            <div id="alertContainer">
                <?php
                if (isset($_GET['pesan'])) {
                    if ($_GET['pesan'] == 'gagal') {
                        echo '<div class="alert-custom alert-danger-custom">';
                        echo '<i class="fas fa-exclamation-circle"></i>';
                        echo '<span>Username atau password salah. Silakan coba kembali.</span>';
                        echo '</div>';
                    } elseif ($_GET['pesan'] == 'level_tidak_dikenal') {
                        echo '<div class="alert-custom alert-warning-custom">';
                        echo '<i class="fas fa-exclamation-triangle"></i>';
                        echo '<span>Level pengguna tidak dikenali. Hubungi administrator.</span>';
                        echo '</div>';
                    } elseif ($_GET['pesan'] == 'register_success') {
                        echo '<div class="alert-custom alert-success-custom">';
                        echo '<i class="fas fa-check-circle"></i>';
                        echo '<span>Pendaftaran berhasil! Silakan login dengan akun Anda.</span>';
                        echo '</div>';
                    } elseif ($_GET['pesan'] == 'reset_success') {
                        echo '<div class="alert-custom alert-success-custom">';
                        echo '<i class="fas fa-check-circle"></i>';
                        echo '<span>Password berhasil direset! Silakan login dengan password baru Anda.</span>';
                        echo '</div>';
                    }
                }
                ?>
            </div>
            
            <!-- Login Form -->
            <form id="loginForm" action="index.php?aksi=login" method="post">
                <div class="form-group">
                    <label class="form-label" for="username">
                        <i class="fas fa-user me-1"></i> Username
                    </label>
                    <div class="input-group-custom">
                        <input type="text" 
                               name="username" 
                               id="username" 
                               class="form-control-custom" 
                               required 
                               placeholder="Masukkan username"
                               autocomplete="username">
                        <i class="fas fa-user input-icon"></i>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="password">
                        <i class="fas fa-lock me-1"></i> Password
                    </label>
                    <div class="input-group-custom">
                        <input type="password" 
                               name="password" 
                               id="password" 
                               class="form-control-custom" 
                               required 
                               placeholder="Masukkan password"
                               autocomplete="current-password">
                        <i class="fas fa-key input-icon"></i>
                    </div>
                </div>
                
                <button type="submit" class="btn-login" id="loginButton">
                    <i class="fas fa-sign-in-alt"></i> Masuk
                </button>
            </form>
            
            <!-- Links: Register & Forgot Password -->
            <div class="links-container">
                <a href="register.php" class="link-item">
                    <i class="fas fa-user-plus"></i> Daftar Akun Baru
                </a>
                <a href="forgot_password.php" class="link-item">
                    <i class="fas fa-key"></i> Lupa Password?
                </a>
            </div>
            
            <!-- Footer -->
            <div class="login-footer">
                <i class="fas fa-boxes"></i>
                Sistem Inventaris Barang Lembaga Pemasyarakatan
                <br>
                <span style="font-size: 0.65rem;">© <?php echo date('Y'); ?> Astro</span>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Focus ke input username
            const usernameInput = document.getElementById('username');
            if (usernameInput) {
                usernameInput.focus();
            }
            
            // Form submission handler
            const loginForm = document.getElementById('loginForm');
            const loginButton = document.getElementById('loginButton');
            
            if (loginForm) {
                loginForm.addEventListener('submit', function(e) {
                    const username = document.getElementById('username').value.trim();
                    const password = document.getElementById('password').value.trim();
                    
                    if (!username || !password) {
                        e.preventDefault();
                        showAlert('Harap isi username dan password terlebih dahulu.', 'warning');
                        return false;
                    }
                    
                    // Show loading state
                    loginButton.classList.add('loading');
                    loginButton.disabled = true;
                    
                    return true;
                });
            }
            
            // Fungsi untuk menampilkan alert
            function showAlert(message, type) {
                const alertContainer = document.getElementById('alertContainer');
                const alertClass = type === 'warning' ? 'alert-warning-custom' : 'alert-danger-custom';
                const icon = type === 'warning' ? 'fa-exclamation-triangle' : 'fa-exclamation-circle';
                
                const alertDiv = document.createElement('div');
                alertDiv.className = `alert-custom ${alertClass}`;
                alertDiv.innerHTML = `
                    <i class="fas ${icon}"></i>
                    <span>${message}</span>
                `;
                
                // Hapus alert lama jika ada
                const existingAlerts = alertContainer.querySelectorAll('.alert-custom');
                existingAlerts.forEach(alert => alert.remove());
                
                alertContainer.appendChild(alertDiv);
                
                // Auto remove setelah 3 detik
                setTimeout(() => {
                    alertDiv.style.opacity = '0';
                    setTimeout(() => alertDiv.remove(), 300);
                }, 3000);
            }
            
            // Clear alert saat user mulai mengetik
            const clearAlertOnInput = () => {
                const alerts = document.querySelectorAll('.alert-custom');
                alerts.forEach(alert => {
                    alert.style.opacity = '0';
                    setTimeout(() => alert.remove(), 300);
                });
            };
            
            usernameInput?.addEventListener('input', clearAlertOnInput);
            document.getElementById('password')?.addEventListener('input', clearAlertOnInput);
        });
    </script>
</body>
</html>