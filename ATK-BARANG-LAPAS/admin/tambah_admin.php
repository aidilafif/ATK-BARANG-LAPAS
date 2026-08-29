<?php
// tambah_admin.php
// Halaman untuk mengelola admin/user (CRUD) - hanya bisa diakses admin

// Mulai session di awal file
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include '../assets/conn/config.php';

// Pastikan session username tersedia dan level admin
if (!isset($_SESSION['username']) || $_SESSION['level'] != 'admin') {
    header("location:../index.php");
    exit();
}

$username = $_SESSION['username'];
$message = '';
$message_type = '';

// Fungsi log aktivitas (DIPERBAIKI)
function logAktivitas($conn, $username, $aktivitas) {
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
    $query = "INSERT INTO log_aktivitas (username, aktivitas, ip_address, user_agent, tanggal) 
              VALUES ('$username', '$aktivitas', '$ip', '$user_agent', NOW())";
    return mysqli_query($conn, $query);
}

// Proses Tambah User
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['tambah_user'])) {
    $username_baru = mysqli_real_escape_string($conn, $_POST['username']);
    $password = mysqli_real_escape_string($conn, $_POST['password']);
    $level = mysqli_real_escape_string($conn, $_POST['level']);
    
    if (empty($username_baru) || empty($password) || empty($level)) {
        $message = 'Semua field harus diisi!';
        $message_type = 'danger';
    } elseif (strlen($username_baru) < 3) {
        $message = 'Username minimal 3 karakter!';
        $message_type = 'danger';
    } elseif (strlen($password) < 4) {
        $message = 'Password minimal 4 karakter!';
        $message_type = 'danger';
    } else {
        $check_query = "SELECT id FROM user WHERE username = '$username_baru'";
        $check_result = mysqli_query($conn, $check_query);
        
        if (mysqli_num_rows($check_result) > 0) {
            $message = 'Username sudah digunakan!';
            $message_type = 'danger';
        } else {
            $query = "INSERT INTO user (username, password, level) VALUES ('$username_baru', '$password', '$level')";
            
            if (mysqli_query($conn, $query)) {
                $message = 'User berhasil ditambahkan!';
                $message_type = 'success';
                logAktivitas($conn, $username, "Menambahkan user baru: $username_baru ($level)");
            } else {
                $message = 'Gagal menambahkan user: ' . mysqli_error($conn);
                $message_type = 'danger';
            }
        }
    }
}

// Proses Edit User
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_user'])) {
    $id = (int)$_POST['id'];
    $username_edit = mysqli_real_escape_string($conn, $_POST['username']);
    $level = mysqli_real_escape_string($conn, $_POST['level']);
    $password = !empty($_POST['password']) ? mysqli_real_escape_string($conn, $_POST['password']) : null;
    
    if (empty($username_edit) || empty($level)) {
        $message = 'Username dan Level harus diisi!';
        $message_type = 'danger';
    } elseif (strlen($username_edit) < 3) {
        $message = 'Username minimal 3 karakter!';
        $message_type = 'danger';
    } else {
        // Cek apakah username sudah digunakan oleh user lain
        $check_query = "SELECT id FROM user WHERE username = '$username_edit' AND id != $id";
        $check_result = mysqli_query($conn, $check_query);
        
        if (mysqli_num_rows($check_result) > 0) {
            $message = 'Username sudah digunakan oleh user lain!';
            $message_type = 'danger';
        } else {
            if ($password) {
                if (strlen($password) < 4) {
                    $message = 'Password minimal 4 karakter!';
                    $message_type = 'danger';
                } else {
                    $query = "UPDATE user SET username='$username_edit', password='$password', level='$level' WHERE id=$id";
                }
            } else {
                $query = "UPDATE user SET username='$username_edit', level='$level' WHERE id=$id";
            }
            
            if (isset($query) && mysqli_query($conn, $query)) {
                $message = 'User berhasil diperbarui!';
                $message_type = 'success';
                logAktivitas($conn, $username, "Mengedit user: $username_edit ($level)");
            } elseif (isset($query)) {
                $message = 'Gagal memperbarui user: ' . mysqli_error($conn);
                $message_type = 'danger';
            }
        }
    }
}

// Proses Hapus User
if (isset($_GET['hapus'])) {
    $id = (int)$_GET['hapus'];
    
    // Cek apakah user yang akan dihapus bukan admin yang sedang login
    $cek_query = "SELECT username FROM user WHERE id = $id";
    $cek_result = mysqli_query($conn, $cek_query);
    $user_to_delete = '';
    if ($cek_result && mysqli_num_rows($cek_result) > 0) {
        $row = mysqli_fetch_assoc($cek_result);
        $user_to_delete = $row['username'];
    }
    
    if ($user_to_delete == $username) {
        $message = 'Anda tidak dapat menghapus akun sendiri!';
        $message_type = 'danger';
    } else {
        $query = "DELETE FROM user WHERE id = $id";
        
        if (mysqli_query($conn, $query)) {
            $message = 'User berhasil dihapus!';
            $message_type = 'success';
            logAktivitas($conn, $username, "Menghapus user: $user_to_delete");
        } else {
            $message = 'Gagal menghapus user: ' . mysqli_error($conn);
            $message_type = 'danger';
        }
    }
}

// Ambil daftar user
$query_user = "SELECT id, username, level, 
               (SELECT COUNT(*) FROM pengajuan WHERE diajukan_oleh = user.username) as total_pengajuan 
               FROM user ORDER BY id";
$result_user = mysqli_query($conn, $query_user);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Pengguna - Inventaris LAPAS</title>
    
    <!-- Bootstrap 5 & Font Awesome -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css">
    
    <style>
        :root {
            --lapas-dark: #1e2a3a;
            --lapas-primary: #0f3b5c;
            --lapas-accent: #2c7da0;
            --lapas-border: #e2e8f0;
            --shadow-sm: 0 4px 12px rgba(0, 0, 0, 0.04);
            --shadow-md: 0 8px 24px rgba(0, 0, 0, 0.08);
        }
        
        body {
            background: #f0f2f5;
            font-family: 'Segoe UI', system-ui, sans-serif;
        }
        
        .panel {
            background: white;
            border-radius: 20px;
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--lapas-border);
            margin-bottom: 30px;
        }
        
        .panel-heading {
            padding: 20px 24px;
            border-bottom: 1px solid var(--lapas-border);
            background: white;
            border-radius: 20px 20px 0 0;
        }
        
        .panel-body {
            padding: 24px;
        }
        
        .btn-submit {
            background: linear-gradient(135deg, var(--lapas-primary), var(--lapas-accent));
            color: white;
            border: none;
            padding: 10px 24px;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.3s;
        }
        
        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(44, 125, 160, 0.3);
            color: white;
        }
        
        .badge-admin {
            background: #dc2626;
            color: white;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 11px;
        }
        
        .badge-user {
            background: #059669;
            color: white;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 11px;
        }
        
        .btn-action {
            padding: 5px 10px;
            font-size: 12px;
            margin: 2px;
            border-radius: 6px;
        }
        
        .table th {
            background: #f8f9fa;
            font-weight: 600;
        }
        
        .fade-in {
            animation: fadeIn 0.5s ease;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body>

<?php include 'header.php'; ?>

<div class="container mt-4 fade-in">
    <div class="row">
        <div class="col-md-5">
            <!-- Form Tambah User -->
            <div class="panel">
                <div class="panel-heading">
                    <h5 class="mb-0">
                        <i class="fas fa-user-plus me-2"></i>Tambah Pengguna Baru
                    </h5>
                </div>
                <div class="panel-body">
                    <?php if ($message): ?>
                        <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show">
                            <i class="fas fa-<?php echo $message_type == 'success' ? 'check-circle' : 'exclamation-circle'; ?> me-2"></i>
                            <?php echo $message; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST" action="">
                        <div class="mb-3">
                            <label class="form-label">Username <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="username" required minlength="3" placeholder="Masukkan username">
                            <small class="text-muted">Minimal 3 karakter</small>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Password <span class="text-danger">*</span></label>
                            <input type="password" class="form-control" name="password" required minlength="4" placeholder="Masukkan password">
                            <small class="text-muted">Minimal 4 karakter</small>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Level Akses <span class="text-danger">*</span></label>
                            <select class="form-select" name="level" required>
                                <option value="user">User (Pengguna Biasa)</option>
                                <option value="admin">Admin (Administrator)</option>
                            </select>
                        </div>
                        
                        <button type="submit" name="tambah_user" class="btn-submit w-100">
                            <i class="fas fa-save me-2"></i>Tambah Pengguna
                        </button>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="col-md-7">
            <!-- Daftar User -->
            <div class="panel">
                <div class="panel-heading">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <i class="fas fa-users me-2"></i>Daftar Pengguna
                        </h5>
                        <div>
                            <button class="btn btn-sm btn-outline-secondary" onclick="window.print()">
                                <i class="fas fa-print me-1"></i> Print
                            </button>
                        </div>
                    </div>
                </div>
                <div class="panel-body">
                    <div class="table-responsive">
                        <table class="table table-hover" id="userTable">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Username</th>
                                    <th>Level</th>
                                    <th>Total Pengajuan</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (mysqli_num_rows($result_user) > 0): ?>
                                    <?php $no = 1; while($row = mysqli_fetch_assoc($result_user)): ?>
                                        <tr>
                                            <td><?php echo $no++; ?></td>
                                            <td>
                                                <strong><?php echo htmlspecialchars($row['username']); ?></strong>
                                                <?php if ($row['username'] == $username): ?>
                                                    <span class="badge bg-info ms-2">Anda</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span class="badge <?php echo $row['level'] == 'admin' ? 'badge-admin' : 'badge-user'; ?>">
                                                    <i class="fas fa-<?php echo $row['level'] == 'admin' ? 'shield-alt' : 'user'; ?> me-1"></i>
                                                    <?php echo ucfirst($row['level']); ?>
                                                </span>
                                            </td>
                                            <td><?php echo $row['total_pengajuan']; ?> kali</td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <button onclick="openEditModal(
                                                        <?php echo $row['id']; ?>,
                                                        '<?php echo addslashes($row['username']); ?>',
                                                        '<?php echo $row['level']; ?>'
                                                    )" class="btn btn-info btn-action" title="Edit User">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <?php if ($row['username'] != $username): ?>
                                                        <a href="?hapus=<?php echo $row['id']; ?>" 
                                                           class="btn btn-danger btn-action"
                                                           onclick="return confirm('Yakin ingin menghapus user <?php echo addslashes($row['username']); ?>?')"
                                                           title="Hapus User">
                                                            <i class="fas fa-trash"></i>
                                                        </a>
                                                    <?php else: ?>
                                                        <button class="btn btn-secondary btn-action" disabled title="Tidak dapat menghapus akun sendiri">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="5" class="text-center py-5">
                                            <i class="fas fa-users fa-3x text-muted mb-3"></i>
                                            <p class="text-muted">Belum ada data pengguna</p>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Edit User -->
<div class="modal fade" id="editModal" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="">
                <div class="modal-header">
                    <h5 class="modal-title" id="editModalLabel">
                        <i class="fas fa-edit me-2"></i>Edit Pengguna
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="id" id="edit_id">
                    
                    <div class="mb-3">
                        <label class="form-label">Username <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="username" id="edit_username" required minlength="3">
                        <small class="text-muted">Minimal 3 karakter</small>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Password <span class="text-muted">(Kosongkan jika tidak diubah)</span></label>
                        <input type="password" class="form-control" name="password" id="edit_password" minlength="4" placeholder="Masukkan password baru">
                        <small class="text-muted">Minimal 4 karakter, isi hanya jika ingin mengganti password</small>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Level Akses <span class="text-danger">*</span></label>
                        <select class="form-select" name="level" id="edit_level" required>
                            <option value="user">User (Pengguna Biasa)</option>
                            <option value="admin">Admin (Administrator)</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" name="edit_user" class="btn btn-primary">
                        <i class="fas fa-save me-1"></i>Simpan Perubahan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>

<script>
$(document).ready(function() {
    // Initialize DataTable
    if ($('#userTable tbody tr').length > 0) {
        $('#userTable').DataTable({
            language: {
                url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/id.json'
            },
            order: [[1, 'asc']],
            pageLength: 10,
            columnDefs: [
                { orderable: false, targets: [4] }
            ]
        });
    }
    
    // Auto-hide alerts
    setTimeout(function() {
        $('.alert').fadeOut('slow');
    }, 3000);
});

function openEditModal(id, username, level) {
    document.getElementById('edit_id').value = id;
    document.getElementById('edit_username').value = username;
    document.getElementById('edit_level').value = level;
    document.getElementById('edit_password').value = '';
    
    var editModal = new bootstrap.Modal(document.getElementById('editModal'));
    editModal.show();
}
</script>

</body>
</html>