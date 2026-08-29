<?php
// atk.php
// Halaman kelola data ATK (Alat Tulis Kantor) dengan auto generate kode

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

$username = $_SESSION['username'];
$user_level = $_SESSION['level'];

// Fungsi untuk generate kode ATK otomatis
function generateKodeATK($conn) {
    $query = "SELECT kode_atk FROM atk ORDER BY kode_atk DESC LIMIT 1";
    $result = mysqli_query($conn, $query);
    
    if (mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        $last_kode = $row['kode_atk'];
        $angka = (int) substr($last_kode, 1);
        $angka_baru = $angka + 1;
        $kode_baru = 'A' . str_pad($angka_baru, 3, '0', STR_PAD_LEFT);
        return $kode_baru;
    } else {
        return 'A001';
    }
}

// Proses Tambah ATK
if (isset($_POST['tambah'])) {
    $kode_atk = !empty($_POST['kode_atk']) ? mysqli_real_escape_string($conn, $_POST['kode_atk']) : generateKodeATK($conn);
    $nama_atk = mysqli_real_escape_string($conn, $_POST['nama_atk']);
    $stok = (int)$_POST['stok'];
    $satuan = mysqli_real_escape_string($conn, $_POST['satuan']);
    $status = ($stok <= 0) ? 'Habis' : 'Tersedia';
    $keterangan = mysqli_real_escape_string($conn, $_POST['keterangan']);
    
    // Cek apakah kode ATK sudah ada
    $cek_query = "SELECT kode_atk FROM atk WHERE kode_atk = '$kode_atk'";
    $cek_result = mysqli_query($conn, $cek_query);
    
    if (mysqli_num_rows($cek_result) > 0) {
        $_SESSION['alert_type'] = 'error';
        $_SESSION['alert_message'] = 'Kode ATK sudah ada!';
    } else {
        $query = "INSERT INTO atk (kode_atk, nama_atk, stok, satuan, status, keterangan) 
                  VALUES ('$kode_atk', '$nama_atk', $stok, '$satuan', '$status', '$keterangan')";
        
        if (mysqli_query($conn, $query)) {
            // Catat log
            $log_query = "INSERT INTO log_aktivitas (username, aktivitas, ip_address, user_agent, tanggal) 
                          VALUES ('$username', 'Menambahkan ATK baru: $kode_atk - $nama_atk', '{$_SERVER['REMOTE_ADDR']}', '{$_SERVER['HTTP_USER_AGENT']}', NOW())";
            mysqli_query($conn, $log_query);
            
            $_SESSION['alert_type'] = 'success';
            $_SESSION['alert_message'] = 'ATK berhasil ditambahkan! Kode: ' . $kode_atk;
        } else {
            $_SESSION['alert_type'] = 'error';
            $_SESSION['alert_message'] = 'Gagal menambahkan ATK: ' . mysqli_error($conn);
        }
    }
    
    header("Location: atk.php");
    exit();
}

// Proses Edit ATK
if (isset($_POST['edit'])) {
    $kode_atk = mysqli_real_escape_string($conn, $_POST['kode_atk']);
    $nama_atk = mysqli_real_escape_string($conn, $_POST['nama_atk']);
    $stok = (int)$_POST['stok'];
    $satuan = mysqli_real_escape_string($conn, $_POST['satuan']);
    $keterangan = mysqli_real_escape_string($conn, $_POST['keterangan']);
    
    // Status akan diatur otomatis oleh trigger BEFORE UPDATE
    $query = "UPDATE atk SET 
              nama_atk='$nama_atk', 
              stok=$stok, 
              satuan='$satuan',
              keterangan='$keterangan' 
              WHERE kode_atk='$kode_atk'";
    
    if (mysqli_query($conn, $query)) {
        // Catat log
        $log_query = "INSERT INTO log_aktivitas (username, aktivitas, ip_address, user_agent, tanggal) 
                      VALUES ('$username', 'Mengedit ATK: $kode_atk - $nama_atk', '{$_SERVER['REMOTE_ADDR']}', '{$_SERVER['HTTP_USER_AGENT']}', NOW())";
        mysqli_query($conn, $log_query);
        
        $_SESSION['alert_type'] = 'success';
        $_SESSION['alert_message'] = 'ATK berhasil diperbarui!';
    } else {
        $_SESSION['alert_type'] = 'error';
        $_SESSION['alert_message'] = 'Gagal memperbarui ATK: ' . mysqli_error($conn);
    }
    
    header("Location: atk.php");
    exit();
}

// Proses Verifikasi ATK (Update Status) - LANGSUNG UPDATE TANPA TRIGGER BERMASALAH
if (isset($_GET['verifikasi'])) {
    $kode_atk = mysqli_real_escape_string($conn, $_GET['verifikasi']);
    $status_baru = mysqli_real_escape_string($conn, $_GET['status']);
    
    // Update status langsung - trigger baru akan menangani konsistensi
    $query = "UPDATE atk SET status='$status_baru' WHERE kode_atk='$kode_atk'";
    
    if (mysqli_query($conn, $query)) {
        // Catat log
        $log_query = "INSERT INTO log_aktivitas (username, aktivitas, ip_address, user_agent, tanggal) 
                      VALUES ('$username', 'Mengubah status ATK $kode_atk menjadi $status_baru', '{$_SERVER['REMOTE_ADDR']}', '{$_SERVER['HTTP_USER_AGENT']}', NOW())";
        mysqli_query($conn, $log_query);
        
        $_SESSION['alert_type'] = 'success';
        $_SESSION['alert_message'] = 'Status ATK berhasil diverifikasi menjadi ' . $status_baru . '!';
    } else {
        $_SESSION['alert_type'] = 'error';
        $_SESSION['alert_message'] = 'Gagal memverifikasi ATK: ' . mysqli_error($conn);
    }
    
    header("Location: atk.php");
    exit();
}

// Proses Hapus ATK
if (isset($_GET['hapus'])) {
    $kode_atk = mysqli_real_escape_string($conn, $_GET['hapus']);
    
    // Ambil nama ATK untuk log
    $nama_query = "SELECT nama_atk FROM atk WHERE kode_atk='$kode_atk'";
    $nama_result = mysqli_query($conn, $nama_query);
    $nama_atk = '';
    if ($nama_result && mysqli_num_rows($nama_result) > 0) {
        $row = mysqli_fetch_assoc($nama_result);
        $nama_atk = $row['nama_atk'];
    }
    
    $query = "DELETE FROM atk WHERE kode_atk='$kode_atk'";
    
    if (mysqli_query($conn, $query)) {
        // Catat log
        $log_query = "INSERT INTO log_aktivitas (username, aktivitas, ip_address, user_agent, tanggal) 
                      VALUES ('$username', 'Menghapus ATK: $kode_atk - $nama_atk', '{$_SERVER['REMOTE_ADDR']}', '{$_SERVER['HTTP_USER_AGENT']}', NOW())";
        mysqli_query($conn, $log_query);
        
        $_SESSION['alert_type'] = 'success';
        $_SESSION['alert_message'] = 'ATK berhasil dihapus!';
    } else {
        $_SESSION['alert_type'] = 'error';
        $_SESSION['alert_message'] = 'Gagal menghapus ATK: ' . mysqli_error($conn);
    }
    
    header("Location: atk.php");
    exit();
}

// Ambil data ATK
$query = "SELECT * FROM atk ORDER BY kode_atk";
$result = mysqli_query($conn, $query);
$total_atk = mysqli_num_rows($result);
$total_stok = 0;
$total_tersedia = 0;
$total_habis = 0;

if ($total_atk > 0) {
    mysqli_data_seek($result, 0);
    while ($row = mysqli_fetch_assoc($result)) {
        $total_stok += $row['stok'];
        if ($row['status'] == 'Tersedia') {
            $total_tersedia++;
        } else {
            $total_habis++;
        }
    }
    mysqli_data_seek($result, 0);
}

$auto_kode = generateKodeATK($conn);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola ATK - Inventaris LAPAS</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
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
        
        .stats-card {
            background: white;
            border-radius: 16px;
            padding: 20px;
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--lapas-border);
            transition: all 0.3s ease;
        }
        
        .stats-card:hover {
            transform: translateY(-3px);
            box-shadow: var(--shadow-md);
        }
        
        .stats-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
        }
        
        .stats-number {
            font-size: 28px;
            font-weight: 700;
            margin: 0;
            line-height: 1.2;
        }
        
        .stats-label {
            font-size: 13px;
            color: #6c757d;
            margin: 0;
        }
        
        .btn-action {
            padding: 5px 12px;
            font-size: 12px;
            margin: 2px;
            border-radius: 6px;
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
        
        .label-success {
            background: #d4edda;
            color: #155724;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            display: inline-block;
        }
        
        .label-danger {
            background: #f8d7da;
            color: #721c24;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            display: inline-block;
        }
        
        .badge-stok {
            font-size: 11px;
            padding: 4px 8px;
        }
        
        .satuan-badge {
            background: #e9ecef;
            color: #495057;
            font-size: 11px;
            padding: 2px 6px;
            border-radius: 4px;
            margin-left: 5px;
        }
        
        .kode-input-group {
            position: relative;
        }
        
        .kode-input-group input {
            padding-right: 40px;
        }
        
        .kode-input-group .refresh-btn {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #6c757d;
            cursor: pointer;
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
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="mb-1" style="color: var(--lapas-dark);">
                <i class="fas fa-pencil-alt me-2" style="color: var(--lapas-accent);"></i>Kelola ATK
            </h2>
            <p class="text-muted small">Manajemen data Alat Tulis Kantor (ATK) LAPAS</p>
        </div>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#tambahModal">
            <i class="fas fa-plus me-2"></i>Tambah ATK
        </button>
    </div>
    
    <?php if (isset($_SESSION['alert_type']) && isset($_SESSION['alert_message'])): ?>
        <div class="alert alert-<?php echo $_SESSION['alert_type']; ?> alert-dismissible fade show" role="alert">
            <i class="fas fa-<?php echo $_SESSION['alert_type'] == 'success' ? 'check-circle' : 'exclamation-circle'; ?> me-2"></i>
            <?php 
            echo $_SESSION['alert_message'];
            unset($_SESSION['alert_type']);
            unset($_SESSION['alert_message']);
            ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    
    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-md-3 mb-3">
            <div class="stats-card">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <p class="stats-label">Total ATK</p>
                        <h3 class="stats-number"><?php echo $total_atk; ?></h3>
                    </div>
                    <div class="stats-icon bg-primary bg-opacity-10 text-primary">
                        <i class="fas fa-pencil-alt"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="stats-card">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <p class="stats-label">Total Stok</p>
                        <h3 class="stats-number"><?php echo $total_stok; ?></h3>
                    </div>
                    <div class="stats-icon bg-success bg-opacity-10 text-success">
                        <i class="fas fa-cubes"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="stats-card">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <p class="stats-label">Tersedia</p>
                        <h3 class="stats-number text-success"><?php echo $total_tersedia; ?></h3>
                    </div>
                    <div class="stats-icon bg-success bg-opacity-10 text-success">
                        <i class="fas fa-check-circle"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="stats-card">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <p class="stats-label">Habis</p>
                        <h3 class="stats-number text-danger"><?php echo $total_habis; ?></h3>
                    </div>
                    <div class="stats-icon bg-danger bg-opacity-10 text-danger">
                        <i class="fas fa-times-circle"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Data Table -->
    <div class="panel">
        <div class="panel-heading">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="fas fa-table me-2"></i>Daftar Alat Tulis Kantor (ATK)</h5>
                <div>
                    <button class="btn btn-sm btn-outline-success" id="exportExcel">
                        <i class="fas fa-file-excel me-1"></i> Export Excel
                    </button>
                </div>
            </div>
        </div>
        <div class="panel-body">
            <div class="table-responsive">
                <table class="table table-hover" id="atkTable">
                    <thead>
                        <tr>
                            <th width="5%">No</th>
                            <th width="10%">Kode</th>
                            <th width="25%">Nama ATK</th>
                            <th width="10%">Stok</th>
                            <th width="10%">Satuan</th>
                            <th width="12%">Status</th>
                            <th width="18%">Keterangan</th>
                            <th width="10%">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($total_atk > 0): ?>
                            <?php $no = 1; while($row = mysqli_fetch_assoc($result)): ?>
                                <tr>
                                    <td><?php echo $no++; ?></td>
                                    <td><strong><?php echo htmlspecialchars($row['kode_atk']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($row['nama_atk']); ?></td>
                                    <td>
                                        <?php
                                        $stok_class = 'secondary';
                                        if ($row['stok'] <= 2) {
                                            $stok_class = 'danger';
                                        } elseif ($row['stok'] <= 5) {
                                            $stok_class = 'warning';
                                        }
                                        ?>
                                        <span class="badge bg-<?php echo $stok_class; ?> badge-stok">
                                            <i class="fas fa-cube me-1"></i><?php echo $row['stok']; ?>
                                        </span>
                                    </td>
                                    <td><span class="satuan-badge"><?php echo htmlspecialchars($row['satuan']); ?></span></td>
                                    <td>
                                        <span class="label-<?php echo $row['status'] == 'Tersedia' ? 'success' : 'danger'; ?>">
                                            <i class="fas fa-<?php echo $row['status'] == 'Tersedia' ? 'check' : 'times'; ?> me-1"></i>
                                            <?php echo $row['status']; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <small class="text-muted">
                                            <?php echo htmlspecialchars($row['keterangan']) ?: '<em class="text-muted">-</em>'; ?>
                                        </small>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <!-- Tombol Verifikasi Status -->
                                            <?php if ($row['status'] == 'Tersedia'): ?>
                                                <a href="?verifikasi=<?php echo $row['kode_atk']; ?>&status=Habis" 
                                                   class="btn btn-warning btn-action"
                                                   onclick="return confirmVerifikasi('<?php echo addslashes($row['nama_atk']); ?>', 'Habis')"
                                                   title="Ubah menjadi Habis">
                                                    <i class="fas fa-times-circle"></i>
                                                </a>
                                            <?php else: ?>
                                                <a href="?verifikasi=<?php echo $row['kode_atk']; ?>&status=Tersedia" 
                                                   class="btn btn-success btn-action"
                                                   onclick="return confirmVerifikasi('<?php echo addslashes($row['nama_atk']); ?>', 'Tersedia')"
                                                   title="Ubah menjadi Tersedia">
                                                    <i class="fas fa-check-circle"></i>
                                                </a>
                                            <?php endif; ?>
                                            
                                            <!-- Tombol Edit -->
                                            <button onclick="openEditModal(
                                                '<?php echo $row['kode_atk']; ?>',
                                                '<?php echo addslashes($row['nama_atk']); ?>',
                                                <?php echo $row['stok']; ?>,
                                                '<?php echo addslashes($row['satuan']); ?>',
                                                '<?php echo $row['status']; ?>',
                                                '<?php echo addslashes($row['keterangan']); ?>'
                                            )" class="btn btn-info btn-action" title="Edit ATK">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            
                                            <!-- Tombol Hapus -->
                                            <a href="?hapus=<?php echo $row['kode_atk']; ?>" 
                                               class="btn btn-danger btn-action"
                                               onclick="return confirm('Yakin ingin menghapus ATK <?php echo addslashes($row['nama_atk']); ?>?')"
                                               title="Hapus ATK">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" class="text-center py-5">
                                    <i class="fas fa-box-open fa-3x text-muted mb-3"></i>
                                    <p class="text-muted">Belum ada data ATK</p>
                                    <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#tambahModal">
                                        <i class="fas fa-plus me-1"></i>Tambah ATK Pertama
                                    </button>
                                 </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal Tambah ATK -->
<div class="modal fade" id="tambahModal" tabindex="-1" aria-labelledby="tambahModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" action="" id="formTambahATK">
                <div class="modal-header">
                    <h5 class="modal-title" id="tambahModalLabel">
                        <i class="fas fa-plus-circle me-2"></i>Tambah ATK Baru
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">
                                Kode ATK 
                                <span class="text-danger">*</span>
                                <small class="text-muted">(Auto Generate)</small>
                            </label>
                            <div class="kode-input-group">
                                <input type="text" class="form-control" name="kode_atk" id="kode_atk" 
                                       maxlength="5" placeholder="Kosongkan untuk auto generate" 
                                       style="font-family: monospace;">
                                <button type="button" class="refresh-btn" onclick="refreshAutoKode()" title="Generate ulang kode">
                                    <i class="fas fa-sync-alt"></i>
                                </button>
                            </div>
                            <small class="text-muted" id="kodeInfo">
                                <i class="fas fa-magic me-1"></i>Kode otomatis: <strong id="previewKode"><?php echo $auto_kode; ?></strong>
                            </small>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Nama ATK <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="nama_atk" required maxlength="60" 
                                   placeholder="Contoh: Pulpen Hitam, Buku Tulis, Spidol">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Stok <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" name="stok" id="tambah_stok" required min="0" value="0">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Satuan <span class="text-danger">*</span></label>
                            <select class="form-select" name="satuan" required>
                                <option value="Pcs">Pcs</option>
                                <option value="Buah">Buah</option>
                                <option value="Rim">Rim</option>
                                <option value="Kotak">Kotak</option>
                                <option value="Pack">Pack</option>
                                <option value="Lembar">Lembar</option>
                                <option value="botol">Botol</option>
                                <option value="Rol">Rol</option>
                                <option value="tubes">Tubes</option>
                            </select>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Status</label>
                            <input type="text" class="form-control" id="tambah_status_display" readonly value="Habis">
                            <small class="text-muted">Status akan otomatis berdasarkan stok</small>
                        </div>
                        <div class="col-12 mb-3">
                            <label class="form-label">Keterangan</label>
                            <textarea class="form-control" name="keterangan" rows="3" 
                                      placeholder="Deskripsi ATK, spesifikasi, dll..."></textarea>
                        </div>
                    </div>
                    <input type="hidden" name="status" id="tambah_status_hidden" value="Habis">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" name="tambah" class="btn btn-primary">
                        <i class="fas fa-save me-1"></i>Simpan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Edit ATK -->
<div class="modal fade" id="editModal" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" action="">
                <div class="modal-header">
                    <h5 class="modal-title" id="editModalLabel">
                        <i class="fas fa-edit me-2"></i>Edit ATK
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="kode_atk" id="edit_kode_atk">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Kode ATK</label>
                            <input type="text" class="form-control" id="edit_kode_display" disabled>
                            <small class="text-muted">Kode ATK tidak dapat diubah</small>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Nama ATK <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="nama_atk" id="edit_nama_atk" required maxlength="200">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Stok <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" name="stok" id="edit_stok" required min="0">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Satuan <span class="text-danger">*</span></label>
                            <select class="form-select" name="satuan" id="edit_satuan" required>
                                <option value="Pcs">Pcs</option>
                                <option value="Buah">Buah</option>
                                <option value="Rim">Rim</option>
                                <option value="Kotak">Kotak</option>
                                <option value="Pack">Pack</option>
                                <option value="Lembar">Lembar</option>
                                <option value="botol">Botol</option>
                                <option value="Rol">Rol</option>
                                <option value="tubes">Tubes</option>
                            </select>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Status</label>
                            <input type="text" class="form-control" id="edit_status_display" readonly>
                            <small class="text-muted">Status akan otomatis berdasarkan stok</small>
                        </div>
                        <div class="col-12 mb-3">
                            <label class="form-label">Keterangan</label>
                            <textarea class="form-control" name="keterangan" id="edit_keterangan" rows="3"></textarea>
                        </div>
                    </div>
                    <input type="hidden" name="status" id="edit_status_hidden">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" name="edit" class="btn btn-primary">
                        <i class="fas fa-save me-1"></i>Update
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
<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>

<script>
var autoKode = '<?php echo $auto_kode; ?>';

$(document).ready(function() {
    if ($('#atkTable tbody tr').length > 0) {
        $('#atkTable').DataTable({
            language: {
                url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/id.json'
            },
            order: [[1, 'asc']],
            pageLength: 10,
            columnDefs: [
                { orderable: false, targets: [7] }
            ]
        });
    }
    
    $('#exportExcel').click(function() {
        var table = document.getElementById('atkTable');
        var wb = XLSX.utils.table_to_book(table, {sheet: "Data ATK", raw: true});
        XLSX.writeFile(wb, "Data_ATK_LAPAS.xlsx");
    });
    
    $('#tambahModal').on('show.bs.modal', function() {
        refreshAutoKode();
        // Update status display based on stok value
        var stok = parseInt($('#tambah_stok').val()) || 0;
        if (stok <= 0) {
            $('#tambah_status_display').val('Habis');
            $('#tambah_status_hidden').val('Habis');
        } else {
            $('#tambah_status_display').val('Tersedia');
            $('#tambah_status_hidden').val('Tersedia');
        }
    });
    
    $('#tambahModal').on('hidden.bs.modal', function() {
        $('#formTambahATK')[0].reset();
        refreshAutoKode();
    });
    
    // Update status when stok changes in tambah modal
    $('#tambah_stok').on('change keyup', function() {
        var stok = parseInt($(this).val()) || 0;
        if (stok <= 0) {
            $('#tambah_status_display').val('Habis');
            $('#tambah_status_hidden').val('Habis');
        } else {
            $('#tambah_status_display').val('Tersedia');
            $('#tambah_status_hidden').val('Tersedia');
        }
    });
    
    setTimeout(function() {
        $('.alert').fadeOut('slow');
    }, 3000);
});

function confirmVerifikasi(nama, status) {
    return confirm(`Verifikasi ATK "${nama}" menjadi ${status}?`);
}

function refreshAutoKode() {
    $.ajax({
        url: 'get_kode_atk.php',
        type: 'GET',
        dataType: 'json',
        success: function(data) {
            if (data.kode_atk) {
                $('#kode_atk').val(data.kode_atk);
                $('#previewKode').html(data.kode_atk);
            } else {
                $('#kode_atk').val('');
                $('#previewKode').html(autoKode);
            }
        },
        error: function() {
            $('#kode_atk').val('');
            $('#previewKode').html(autoKode);
        }
    });
}

function openEditModal(kode, nama, stok, satuan, status, keterangan) {
    document.getElementById('edit_kode_atk').value = kode;
    document.getElementById('edit_kode_display').value = kode;
    document.getElementById('edit_nama_atk').value = nama;
    document.getElementById('edit_stok').value = stok;
    document.getElementById('edit_satuan').value = satuan;
    document.getElementById('edit_keterangan').value = keterangan || '';
    
    // Set status display based on stok
    if (stok <= 0) {
        document.getElementById('edit_status_display').value = 'Habis';
        document.getElementById('edit_status_hidden').value = 'Habis';
    } else {
        document.getElementById('edit_status_display').value = 'Tersedia';
        document.getElementById('edit_status_hidden').value = 'Tersedia';
    }
    
    // Add event listener for stok change in edit modal
    $('#edit_stok').off('change').on('change keyup', function() {
        var newStok = parseInt($(this).val()) || 0;
        if (newStok <= 0) {
            $('#edit_status_display').val('Habis');
            $('#edit_status_hidden').val('Habis');
        } else {
            $('#edit_status_display').val('Tersedia');
            $('#edit_status_hidden').val('Tersedia');
        }
    });
    
    var editModal = new bootstrap.Modal(document.getElementById('editModal'));
    editModal.show();
}
</script>

</body>
</html>