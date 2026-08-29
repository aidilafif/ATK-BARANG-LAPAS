<?php
// register.php - Halaman Registrasi Akun Baru
// Versi Final

session_start();
include 'assets/conn/config.php';

$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim(mysqli_real_escape_string($conn, $_POST['username']));
    $password = mysqli_real_escape_string($conn, $_POST['password']);
    $confirm_password = mysqli_real_escape_string($conn, $_POST['confirm_password']);
    
    // Validasi
    if (empty($username) || empty($password) || empty($confirm_password)) {
        $message = 'Semua field harus diisi!';
        $message_type = 'danger';
    } elseif (strlen($username) < 3) {
        $message = 'Username minimal 3 karakter!';
        $message_type = 'danger';
    } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
        $message = 'Username hanya boleh berisi huruf, angka, dan underscore!';
        $message_type = 'danger';
    } elseif (strlen($password) < 4) {
        $message = 'Password minimal 4 karakter!';
        $message_type = 'danger';
    } elseif ($password !== $confirm_password) {
        $message = 'Konfirmasi password tidak cocok!';
        $message_type = 'danger';
    } else {
        // Cek apakah username sudah ada
        $check_query = "SELECT id FROM user WHERE username = '$username'";
        $check_result = mysqli_query($conn, $check_query);
        
        if (mysqli_num_rows($check_result) > 0) {
            $message = 'Username sudah terdaftar! Silakan gunakan username lain.';
            $message_type = 'danger';
        } else {
            // Insert user baru (default level = user)
            $query = "INSERT INTO user (username, password, level) VALUES ('$username', '$password', 'user')";
            
            if (mysqli_query($conn, $query)) {
                // Catat log aktivitas
                $log_query = "INSERT INTO log_aktivitas (username, aktivitas, ip_address, user_agent, tanggal) 
                              VALUES ('$username', 'Mendaftarkan akun baru', '{$_SERVER['REMOTE_ADDR']}', '{$_SERVER['HTTP_USER_AGENT']}', NOW())";
                mysqli_query($conn, $log_query);
                
                $message = 'Pendaftaran berhasil! Silakan login dengan akun Anda.';
                $message_type = 'success';
                
                // Redirect setelah 2 detik
                echo '<script>
                        setTimeout(function() {
                            window.location.href = "index.php?pesan=register_success";
                        }, 2000);
                      </script>';
            } else {
                $message = 'Gagal mendaftar: ' . mysqli_error($conn);
                $message_type = 'danger';
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrasi - Inventaris Barang LAPAS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #0f3b5c 0%, #1e6b8c 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', system-ui, sans-serif;
        }
        .register-card {
            background: white;
            border-radius: 24px;
            padding: 40px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.15);
            max-width: 450px;
            width: 100%;
            margin: 20px;
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
        .logo-icon {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, #0f3b5c, #1e6b8c);
            border-radius: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
        }
        .logo-icon i {
            font-size: 28px;
            color: white;
        }
        .btn-register {
            background: linear-gradient(135deg, #0f3b5c, #1e6b8c);
            border: none;
            padding: 12px;
            font-weight: 600;
            width: 100%;
            transition: all 0.3s;
        }
        .btn-register:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(15, 59, 92, 0.3);
        }
        .form-control:focus {
            border-color: #1e6b8c;
            box-shadow: 0 0 0 0.2rem rgba(30, 107, 140, 0.25);
        }
        .decoration-line {
            width: 50px;
            height: 3px;
            background: linear-gradient(90deg, #0f3b5c, #1e6b8c);
            margin: 16px auto 0;
            border-radius: 3px;
        }
    </style>
</head>
<body>
    <div class="register-card">
        <div class="logo-icon">
            <i class="fas fa-user-plus"></i>
        </div>
        <h3 class="text-center mb-2">Daftar Akun Baru</h3>
        <div class="decoration-line"></div>
        <p class="text-muted text-center mt-3 mb-4">Silakan isi form di bawah untuk mendaftar</p>
        
        <?php if ($message): ?>
            <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show">
                <i class="fas fa-<?php echo $message_type == 'success' ? 'check-circle' : 'exclamation-circle'; ?> me-2"></i>
                <?php echo $message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="" id="registerForm">
            <div class="mb-3">
                <label class="form-label">Username</label>
                <input type="text" name="username" class="form-control" required minlength="3" 
                       pattern="[a-zA-Z0-9_]+" title="Hanya huruf, angka, dan underscore"
                       placeholder="Masukkan username">
                <small class="text-muted">Minimal 3 karakter (huruf, angka, underscore)</small>
            </div>
            
            <div class="mb-3">
                <label class="form-label">Password</label>
                <input type="password" name="password" id="password" class="form-control" required minlength="4" placeholder="Masukkan password">
                <small class="text-muted">Minimal 4 karakter</small>
            </div>
            
            <div class="mb-3">
                <label class="form-label">Konfirmasi Password</label>
                <input type="password" name="confirm_password" id="confirm_password" class="form-control" required placeholder="Konfirmasi password">
                <div id="passwordError" class="text-danger mt-1" style="font-size: 0.8rem; display: none;">Password tidak cocok!</div>
            </div>
            
            <button type="submit" class="btn btn-primary btn-register" id="registerBtn">
                <i class="fas fa-user-plus me-2"></i>Daftar
            </button>
        </form>
        
        <div class="text-center mt-4">
            <a href="index.php" class="text-decoration-none">
                <i class="fas fa-arrow-left me-1"></i> Kembali ke Login
            </a>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const password = document.getElementById('password');
        const confirmPassword = document.getElementById('confirm_password');
        const passwordError = document.getElementById('passwordError');
        const registerBtn = document.getElementById('registerBtn');
        
        function validatePassword() {
            if (password.value !== confirmPassword.value) {
                passwordError.style.display = 'block';
                registerBtn.disabled = true;
                return false;
            } else {
                passwordError.style.display = 'none';
                registerBtn.disabled = false;
                return true;
            }
        }
        
        password.addEventListener('keyup', validatePassword);
        confirmPassword.addEventListener('keyup', validatePassword);
        
        document.getElementById('registerForm').addEventListener('submit', function(e) {
            if (!validatePassword()) {
                e.preventDefault();
            }
        });
    </script>
</body>
</html>