<?php
// forgot_password.php - Halaman Lupa Password
// Versi Final

session_start();
include 'assets/conn/config.php';

$message = '';
$message_type = '';
$step = 1; // 1: form username, 2: form reset password

// Cek apakah sudah ada session step
if (isset($_SESSION['reset_step'])) {
    $step = $_SESSION['reset_step'];
}

// Proses cek username
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cek_username'])) {
    $username = trim(mysqli_real_escape_string($conn, $_POST['username']));
    
    if (empty($username)) {
        $message = 'Username harus diisi!';
        $message_type = 'danger';
    } else {
        $query = "SELECT id, username, level FROM user WHERE username = '$username'";
        $result = mysqli_query($conn, $query);
        
        if (mysqli_num_rows($result) > 0) {
            $user = mysqli_fetch_assoc($result);
            $_SESSION['reset_username'] = $username;
            $_SESSION['reset_user_id'] = $user['id'];
            $_SESSION['reset_step'] = 2;
            $step = 2;
            $message = 'Username ditemukan! Silakan masukkan password baru.';
            $message_type = 'success';
        } else {
            $message = 'Username tidak ditemukan!';
            $message_type = 'danger';
        }
    }
}

// Proses reset password
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reset_password'])) {
    $new_password = mysqli_real_escape_string($conn, $_POST['new_password']);
    $confirm_password = mysqli_real_escape_string($conn, $_POST['confirm_password']);
    
    if (empty($new_password) || empty($confirm_password)) {
        $message = 'Semua field harus diisi!';
        $message_type = 'danger';
    } elseif (strlen($new_password) < 4) {
        $message = 'Password minimal 4 karakter!';
        $message_type = 'danger';
    } elseif ($new_password !== $confirm_password) {
        $message = 'Konfirmasi password tidak cocok!';
        $message_type = 'danger';
    } else {
        $username = $_SESSION['reset_username'];
        $user_id = $_SESSION['reset_user_id'];
        
        $query = "UPDATE user SET password = '$new_password' WHERE id = $user_id";
        
        if (mysqli_query($conn, $query)) {
            // Catat log aktivitas
            $log_query = "INSERT INTO log_aktivitas (username, aktivitas, ip_address, user_agent, tanggal) 
                          VALUES ('$username', 'Merubah password melalui fitur lupa password', '{$_SERVER['REMOTE_ADDR']}', '{$_SERVER['HTTP_USER_AGENT']}', NOW())";
            mysqli_query($conn, $log_query);
            
            // Hapus session reset
            unset($_SESSION['reset_step']);
            unset($_SESSION['reset_username']);
            unset($_SESSION['reset_user_id']);
            
            $message = 'Password berhasil direset! Silakan login dengan password baru Anda.';
            $message_type = 'success';
            
            // Redirect setelah 2 detik
            echo '<script>
                    setTimeout(function() {
                        window.location.href = "index.php?pesan=reset_success";
                    }, 2000);
                  </script>';
        } else {
            $message = 'Gagal mereset password: ' . mysqli_error($conn);
            $message_type = 'danger';
        }
    }
}

// Proses batal reset
if (isset($_GET['batal'])) {
    unset($_SESSION['reset_step']);
    unset($_SESSION['reset_username']);
    unset($_SESSION['reset_user_id']);
    header("Location: index.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lupa Password - Inventaris Barang LAPAS</title>
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
        .reset-card {
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
        .btn-reset {
            background: linear-gradient(135deg, #0f3b5c, #1e6b8c);
            border: none;
            padding: 12px;
            font-weight: 600;
            width: 100%;
            transition: all 0.3s;
        }
        .btn-reset:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(15, 59, 92, 0.3);
        }
        .step-indicator {
            display: flex;
            justify-content: center;
            align-items: center;
            margin-bottom: 30px;
        }
        .step {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: #e9ecef;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            color: #6c757d;
        }
        .step.active {
            background: linear-gradient(135deg, #0f3b5c, #1e6b8c);
            color: white;
        }
        .step.completed {
            background: #28a745;
            color: white;
        }
        .step-line {
            width: 50px;
            height: 2px;
            background: #e9ecef;
            margin: 0 5px;
        }
        .step-line.active {
            background: linear-gradient(90deg, #0f3b5c, #1e6b8c);
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
    <div class="reset-card">
        <div class="logo-icon">
            <i class="fas fa-key"></i>
        </div>
        <h3 class="text-center mb-2">Lupa Password</h3>
        <div class="decoration-line"></div>
        <p class="text-muted text-center mt-3 mb-4">Reset password akun Anda</p>
        
        <!-- Step Indicator -->
        <div class="step-indicator">
            <div class="step <?php echo $step >= 1 ? 'active' : ''; ?> <?php echo $step > 1 ? 'completed' : ''; ?>">1</div>
            <div class="step-line <?php echo $step >= 2 ? 'active' : ''; ?>"></div>
            <div class="step <?php echo $step >= 2 ? 'active' : ''; ?>">2</div>
        </div>
        
        <?php if ($message): ?>
            <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show">
                <i class="fas fa-<?php echo $message_type == 'success' ? 'check-circle' : 'exclamation-circle'; ?> me-2"></i>
                <?php echo $message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <?php if ($step == 1): ?>
            <!-- Step 1: Cek Username -->
            <form method="POST" action="">
                <div class="mb-4">
                    <label class="form-label">Masukkan Username Anda</label>
                    <input type="text" name="username" class="form-control" required placeholder="Contoh: admin, user, staff">
                    <small class="text-muted">Masukkan username yang terdaftar di sistem</small>
                </div>
                
                <button type="submit" name="cek_username" class="btn btn-primary btn-reset">
                    <i class="fas fa-search me-2"></i>Cek Username
                </button>
                
                <div class="text-center mt-3">
                    <a href="index.php" class="text-decoration-none">
                        <i class="fas fa-arrow-left me-1"></i> Kembali ke Login
                    </a>
                </div>
            </form>
        <?php else: ?>
            <!-- Step 2: Reset Password -->
            <form method="POST" action="">
                <div class="mb-3">
                    <label class="form-label">Username</label>
                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($_SESSION['reset_username'] ?? ''); ?>" disabled>
                    <small class="text-muted">Password akan direset untuk akun ini</small>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Password Baru</label>
                    <input type="password" name="new_password" id="new_password" class="form-control" required minlength="4" placeholder="Masukkan password baru">
                    <small class="text-muted">Minimal 4 karakter</small>
                </div>
                
                <div class="mb-4">
                    <label class="form-label">Konfirmasi Password Baru</label>
                    <input type="password" name="confirm_password" id="confirm_password" class="form-control" required placeholder="Konfirmasi password baru">
                    <div id="passwordError" class="text-danger mt-1" style="font-size: 0.8rem; display: none;">Password tidak cocok!</div>
                </div>
                
                <button type="submit" name="reset_password" class="btn btn-primary btn-reset" id="resetBtn">
                    <i class="fas fa-save me-2"></i>Reset Password
                </button>
                
                <div class="text-center mt-3">
                    <a href="?batal=1" class="text-decoration-none text-danger">
                        <i class="fas fa-times me-1"></i> Batalkan
                    </a>
                </div>
            </form>
        <?php endif; ?>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        <?php if ($step == 2): ?>
        const newPassword = document.getElementById('new_password');
        const confirmPassword = document.getElementById('confirm_password');
        const passwordError = document.getElementById('passwordError');
        const resetBtn = document.getElementById('resetBtn');
        
        function validatePassword() {
            if (newPassword.value !== confirmPassword.value) {
                passwordError.style.display = 'block';
                resetBtn.disabled = true;
                return false;
            } else {
                passwordError.style.display = 'none';
                resetBtn.disabled = false;
                return true;
            }
        }
        
        newPassword.addEventListener('keyup', validatePassword);
        confirmPassword.addEventListener('keyup', validatePassword);
        
        document.querySelector('form').addEventListener('submit', function(e) {
            if (!validatePassword()) {
                e.preventDefault();
            }
        });
        <?php endif; ?>
    </script>
</body>
</html>