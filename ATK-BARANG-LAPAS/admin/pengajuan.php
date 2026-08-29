<?php
// pengajuan.php
// Halaman untuk pengajuan barang rusak / habis oleh user

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

// Ambil data user
$username = $_SESSION['username'];
$user_level = $_SESSION['level'];

// ==================== PROSES TAMBAH PENGAJUAN ====================
if (isset($_POST['ajukan'])) {
    $nama_barang = mysqli_real_escape_string($conn, $_POST['nama_barang']);
    $jenis_pengajuan = mysqli_real_escape_string($conn, $_POST['jenis_pengajuan']);
    $jumlah = (int)$_POST['jumlah'];
    $alasan = mysqli_real_escape_string($conn, $_POST['alasan']);
    $keterangan_tambahan = mysqli_real_escape_string($conn, $_POST['keterangan_tambahan']);
    $nama_ruangan = mysqli_real_escape_string($conn, $_POST['nama_ruangan']);
    $tanggal_pengajuan = date('Y-m-d H:i:s');
    $status = 'Menunggu Verifikasi';
    
    // Kode barang diisi dengan nilai default
    $kode_barang = 'USR-' . date('YmdHis') . '-' . rand(100, 999);
    
    // Insert ke tabel pengajuan
    $query = "INSERT INTO pengajuan (kode_barang, nama_barang, jenis_pengajuan, jumlah, alasan, keterangan_tambahan, nama_ruangan, tanggal_pengajuan, status, diajukan_oleh) 
              VALUES ('$kode_barang', '$nama_barang', '$jenis_pengajuan', $jumlah, '$alasan', '$keterangan_tambahan', '$nama_ruangan', '$tanggal_pengajuan', '$status', '$username')";
    
    if (mysqli_query($conn, $query)) {
        $_SESSION['alert_type'] = 'success';
        $_SESSION['alert_message'] = 'Pengajuan berhasil dikirim! Menunggu verifikasi admin.';
    } else {
        $_SESSION['alert_type'] = 'error';
        $_SESSION['alert_message'] = 'Gagal mengirim pengajuan: ' . mysqli_error($conn);
    }
    
    header("Location: pengajuan.php");
    exit();
}

// ==================== PROSES BATAL PENGAJUAN (untuk user) ====================
if (isset($_GET['batal']) && $user_level == 'user') {
    $id_pengajuan = (int)$_GET['batal'];
    
    $query = "UPDATE pengajuan SET status = 'Dibatalkan' WHERE id_pengajuan = $id_pengajuan AND diajukan_oleh = '$username'";
    
    if (mysqli_query($conn, $query)) {
        $_SESSION['alert_type'] = 'success';
        $_SESSION['alert_message'] = 'Pengajuan berhasil dibatalkan!';
    } else {
        $_SESSION['alert_type'] = 'error';
        $_SESSION['alert_message'] = 'Gagal membatalkan pengajuan!';
    }
    
    header("Location: pengajuan.php");
    exit();
}

// ==================== PROSES HAPUS PENGAJUAN (untuk admin) ====================
if (isset($_GET['hapus']) && $user_level == 'admin') {
    $id_pengajuan = (int)$_GET['hapus'];
    
    $query = "DELETE FROM pengajuan WHERE id_pengajuan = $id_pengajuan";
    
    if (mysqli_query($conn, $query)) {
        $_SESSION['alert_type'] = 'success';
        $_SESSION['alert_message'] = 'Pengajuan berhasil dihapus!';
    } else {
        $_SESSION['alert_type'] = 'error';
        $_SESSION['alert_message'] = 'Gagal menghapus pengajuan: ' . mysqli_error($conn);
    }
    
    header("Location: pengajuan.php");
    exit();
}

// ==================== PROSES EDIT STATUS PENGAJUAN (untuk admin) ====================
if (isset($_POST['edit_status']) && $user_level == 'admin') {
    $id_pengajuan = (int)$_POST['id_pengajuan'];
    $status_baru = mysqli_real_escape_string($conn, $_POST['status_baru']);
    $catatan_admin = mysqli_real_escape_string($conn, $_POST['catatan_admin']);
    $tanggal_verifikasi = date('Y-m-d H:i:s');
    
    $query_update = "UPDATE pengajuan SET 
                     status = '$status_baru', 
                     catatan_admin = '$catatan_admin',
                     tanggal_verifikasi = '$tanggal_verifikasi',
                     diverifikasi_oleh = '$username'
                     WHERE id_pengajuan = $id_pengajuan";
    
    if (mysqli_query($conn, $query_update)) {
        $log_query = "INSERT INTO log_aktivitas (username, aktivitas, ip_address, user_agent, tanggal) 
                      VALUES ('$username', 'Mengubah status pengajuan ID $id_pengajuan menjadi $status_baru', '{$_SERVER['REMOTE_ADDR']}', '{$_SERVER['HTTP_USER_AGENT']}', NOW())";
        mysqli_query($conn, $log_query);
        
        $_SESSION['alert_type'] = 'success';
        $_SESSION['alert_message'] = 'Status pengajuan berhasil diubah menjadi ' . $status_baru . '!';
    } else {
        $_SESSION['alert_type'] = 'error';
        $_SESSION['alert_message'] = 'Gagal mengubah status pengajuan: ' . mysqli_error($conn);
    }
    
    header("Location: pengajuan.php");
    exit();
}

// ==================== PROSES VERIFIKASI PENGAJUAN (untuk admin) ====================
if (isset($_POST['verifikasi']) && $user_level == 'admin') {
    $id_pengajuan = (int)$_POST['id_pengajuan'];
    $status_verifikasi = mysqli_real_escape_string($conn, $_POST['status_verifikasi']);
    $catatan_admin = mysqli_real_escape_string($conn, $_POST['catatan_admin']);
    $tanggal_verifikasi = date('Y-m-d H:i:s');
    
    mysqli_begin_transaction($conn);
    
    try {
        $query_pengajuan = "SELECT * FROM pengajuan WHERE id_pengajuan = $id_pengajuan";
        $result_pengajuan = mysqli_query($conn, $query_pengajuan);
        
        if ($result_pengajuan && mysqli_num_rows($result_pengajuan) > 0) {
            $pengajuan = mysqli_fetch_assoc($result_pengajuan);
            
            $query_update = "UPDATE pengajuan SET 
                             status = '$status_verifikasi', 
                             catatan_admin = '$catatan_admin',
                             tanggal_verifikasi = '$tanggal_verifikasi',
                             diverifikasi_oleh = '$username'
                             WHERE id_pengajuan = $id_pengajuan";
            
            if (mysqli_query($conn, $query_update)) {
                if ($status_verifikasi == 'Disetujui') {
                    $nama_barang = $pengajuan['nama_barang'];
                    $jenis = $pengajuan['jenis_pengajuan'];
                    $jumlah = $pengajuan['jumlah'];
                    
                    $cek_barang = "SELECT * FROM barang WHERE nama_barang = '$nama_barang'";
                    $result_cek = mysqli_query($conn, $cek_barang);
                    
                    if ($result_cek && mysqli_num_rows($result_cek) > 0) {
                        $barang = mysqli_fetch_assoc($result_cek);
                        $kode_barang = $barang['kode_barang'];
                        
                        if ($jenis == 'Habis') {
                            $query_stok = "UPDATE barang SET stok = stok - $jumlah WHERE kode_barang = '$kode_barang' AND stok >= $jumlah";
                            $result_stok = mysqli_query($conn, $query_stok);
                            
                            if (mysqli_affected_rows($conn) > 0) {
                                $query_cek_stok = "SELECT stok FROM barang WHERE kode_barang = '$kode_barang'";
                                $result_cek_stok = mysqli_query($conn, $query_cek_stok);
                                if ($result_cek_stok && mysqli_num_rows($result_cek_stok) > 0) {
                                    $stok_data = mysqli_fetch_assoc($result_cek_stok);
                                    if ($stok_data['stok'] <= 0) {
                                        mysqli_query($conn, "UPDATE barang SET status = 'Habis' WHERE kode_barang = '$kode_barang'");
                                    }
                                }
                                
                                $log_query = "INSERT INTO log_aktivitas (username, aktivitas, ip_address, user_agent, tanggal) 
                                              VALUES ('$username', 'Menyetujui pengajuan barang $nama_barang - mengurangi stok sebanyak $jumlah unit', '{$_SERVER['REMOTE_ADDR']}', '{$_SERVER['HTTP_USER_AGENT']}', NOW())";
                                mysqli_query($conn, $log_query);
                            } else {
                                throw new Exception("Stok tidak mencukupi untuk pengurangan!");
                            }
                        } elseif ($jenis == 'Rusak') {
                            $query_stok = "UPDATE barang SET stok = stok - $jumlah WHERE kode_barang = '$kode_barang' AND stok >= $jumlah";
                            $result_stok = mysqli_query($conn, $query_stok);
                            
                            if (mysqli_affected_rows($conn) > 0) {
                                $query_rusak = "INSERT INTO barang_rusak (kode_barang, nama_barang, jumlah, tanggal_rusak, keterangan) 
                                                VALUES ('$kode_barang', '$nama_barang', $jumlah, NOW(), '{$pengajuan['alasan']}')";
                                mysqli_query($conn, $query_rusak);
                                
                                $log_query = "INSERT INTO log_aktivitas (username, aktivitas, ip_address, user_agent, tanggal) 
                                              VALUES ('$username', 'Menyetujui pengajuan barang rusak $nama_barang - mengurangi stok sebanyak $jumlah unit', '{$_SERVER['REMOTE_ADDR']}', '{$_SERVER['HTTP_USER_AGENT']}', NOW())";
                                mysqli_query($conn, $log_query);
                            } else {
                                throw new Exception("Stok tidak mencukupi untuk pengurangan!");
                            }
                        }
                    } else {
                        $log_query = "INSERT INTO log_aktivitas (username, aktivitas, ip_address, user_agent, tanggal) 
                                      VALUES ('$username', 'Menyetujui pengajuan barang baru $nama_barang (tidak ada di database) sebanyak $jumlah unit', '{$_SERVER['REMOTE_ADDR']}', '{$_SERVER['HTTP_USER_AGENT']}', NOW())";
                        mysqli_query($conn, $log_query);
                    }
                }
                
                mysqli_commit($conn);
                $_SESSION['alert_type'] = 'success';
                $_SESSION['alert_message'] = 'Pengajuan berhasil diverifikasi!';
            } else {
                throw new Exception("Gagal mengupdate status pengajuan: " . mysqli_error($conn));
            }
        } else {
            throw new Exception("Data pengajuan tidak ditemukan!");
        }
    } catch (Exception $e) {
        mysqli_rollback($conn);
        $_SESSION['alert_type'] = 'error';
        $_SESSION['alert_message'] = $e->getMessage();
    }
    
    header("Location: pengajuan.php");
    exit();
}

// ==================== AMBIL DATA RUANGAN DARI DATABASE (URUTAN BERDASARKAN KODE) ====================
$query_ruangan_db = "SELECT kode_ruangan, nama_ruangan, status FROM ruangan WHERE status = 'Aktif' ORDER BY kode_ruangan ASC";
$result_ruangan_db = mysqli_query($conn, $query_ruangan_db);
$daftar_ruangan_db = [];
if ($result_ruangan_db && mysqli_num_rows($result_ruangan_db) > 0) {
    while ($row = mysqli_fetch_assoc($result_ruangan_db)) {
        $daftar_ruangan_db[] = $row;
    }
}

// ==================== AMBIL DATA PENGAJUAN DENGAN FILTER ====================
$tanggal_mulai = isset($_GET['tanggal_mulai']) && !empty($_GET['tanggal_mulai']) ? mysqli_real_escape_string($conn, $_GET['tanggal_mulai']) : '';
$tanggal_selesai = isset($_GET['tanggal_selesai']) && !empty($_GET['tanggal_selesai']) ? mysqli_real_escape_string($conn, $_GET['tanggal_selesai']) : '';
$filter_ruangan = isset($_GET['filter_ruangan']) && !empty($_GET['filter_ruangan']) ? mysqli_real_escape_string($conn, $_GET['filter_ruangan']) : '';

$where_condition = "";
if ($user_level != 'admin') {
    $where_condition = "diajukan_oleh = '$username'";
}

if ($tanggal_mulai && $tanggal_selesai) {
    $filter_tanggal = "DATE(tanggal_pengajuan) BETWEEN '$tanggal_mulai' AND '$tanggal_selesai'";
    if ($where_condition) {
        $where_condition .= " AND $filter_tanggal";
    } else {
        $where_condition = $filter_tanggal;
    }
}

if ($filter_ruangan) {
    $filter_ruangan_condition = "nama_ruangan = '$filter_ruangan'";
    if ($where_condition) {
        $where_condition .= " AND $filter_ruangan_condition";
    } else {
        $where_condition = $filter_ruangan_condition;
    }
}

if ($where_condition) {
    $where_clause = "WHERE $where_condition";
} else {
    $where_clause = "";
}

if ($user_level == 'admin') {
    $query_pengajuan = "SELECT * FROM pengajuan $where_clause ORDER BY 
                        CASE status 
                            WHEN 'Menunggu Verifikasi' THEN 1
                            WHEN 'Disetujui' THEN 2
                            WHEN 'Ditolak' THEN 3
                            WHEN 'Dibatalkan' THEN 4
                        END, 
                        tanggal_pengajuan DESC";
} else {
    $query_pengajuan = "SELECT * FROM pengajuan $where_clause ORDER BY tanggal_pengajuan DESC";
}

$result_pengajuan = mysqli_query($conn, $query_pengajuan);

// Ambil daftar ruangan unik untuk dropdown filter dari data pengajuan (urut berdasarkan kode)
$query_ruangan_filter = "SELECT DISTINCT p.nama_ruangan, r.kode_ruangan 
                         FROM pengajuan p 
                         LEFT JOIN ruangan r ON p.nama_ruangan = r.nama_ruangan 
                         WHERE p.nama_ruangan IS NOT NULL AND p.nama_ruangan != '' 
                         ORDER BY r.kode_ruangan ASC, p.nama_ruangan ASC";
$result_ruangan_filter = mysqli_query($conn, $query_ruangan_filter);
$daftar_ruangan_filter = [];
if ($result_ruangan_filter && mysqli_num_rows($result_ruangan_filter) > 0) {
    while ($row = mysqli_fetch_assoc($result_ruangan_filter)) {
        $daftar_ruangan_filter[] = $row['nama_ruangan'];
    }
}

if (!$result_pengajuan) {
    $result_pengajuan = false;
    $total_pengajuan = 0;
    $menunggu = 0;
    $disetujui = 0;
    $ditolak = 0;
    $dibatalkan = 0;
} else {
    $total_pengajuan = mysqli_num_rows($result_pengajuan);
    
    $menunggu = 0;
    $disetujui = 0;
    $ditolak = 0;
    $dibatalkan = 0;
    
    if ($total_pengajuan > 0) {
        mysqli_data_seek($result_pengajuan, 0);
        while ($row = mysqli_fetch_assoc($result_pengajuan)) {
            switch ($row['status']) {
                case 'Menunggu Verifikasi':
                    $menunggu++;
                    break;
                case 'Disetujui':
                    $disetujui++;
                    break;
                case 'Ditolak':
                    $ditolak++;
                    break;
                case 'Dibatalkan':
                    $dibatalkan++;
                    break;
            }
        }
        mysqli_data_seek($result_pengajuan, 0);
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pengajuan Barang - Inventaris LAPAS</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
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
        
        .status-badge {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            display: inline-block;
        }
        
        .status-menungguverifikasi {
            background: #fff3cd;
            color: #856404;
        }
        
        .status-disetujui {
            background: #d4edda;
            color: #155724;
        }
        
        .status-ditolak {
            background: #f8d7da;
            color: #721c24;
        }
        
        .status-dibatalkan {
            background: #e2e3e5;
            color: #383d41;
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
        
        .btn-action {
            padding: 5px 12px;
            font-size: 12px;
            margin: 2px;
            border-radius: 6px;
        }
        
        .timeline {
            position: relative;
            padding-left: 30px;
        }
        
        .timeline::before {
            content: '';
            position: absolute;
            left: 10px;
            top: 0;
            bottom: 0;
            width: 2px;
            background: #e2e8f0;
        }
        
        .timeline-item {
            position: relative;
            margin-bottom: 20px;
        }
        
        .timeline-item::before {
            content: '';
            position: absolute;
            left: -24px;
            top: 5px;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: var(--lapas-accent);
            border: 2px solid white;
            box-shadow: 0 0 0 2px var(--lapas-accent);
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .fade-in {
            animation: fadeIn 0.5s ease;
        }
        
        .text-nowrap {
            white-space: nowrap;
        }
        
        .table-responsive {
            overflow-x: auto;
        }
        
        .table th, .table td {
            vertical-align: middle;
        }
        
        .ruangan-badge {
            background: #e8f0fe;
            color: #1e4663;
            padding: 4px 8px;
            border-radius: 6px;
            font-size: 11px;
            font-weight: 500;
            display: inline-block;
        }
        
        .filter-section {
            background: white;
            padding: 15px 20px;
            border-radius: 12px;
            margin-bottom: 20px;
            border: 1px solid var(--lapas-border);
        }
        
        .btn-filter {
            background: var(--lapas-primary);
            color: white;
            border: none;
            padding: 8px 20px;
            border-radius: 8px;
            transition: all 0.3s;
        }
        
        .btn-filter:hover {
            background: var(--lapas-accent);
            color: white;
        }
        
        .btn-reset {
            background: #6c757d;
            color: white;
            border: none;
            padding: 8px 20px;
            border-radius: 8px;
            transition: all 0.3s;
        }
        
        .btn-reset:hover {
            background: #5a6268;
            color: white;
        }
        
        textarea {
            resize: vertical;
        }
        
        .btn-edit-status {
            background: #6c757d;
            color: white;
            border: none;
            transition: all 0.3s;
        }
        
        .btn-edit-status:hover {
            background: #5a6268;
            color: white;
        }
        
        .ruangan-select {
            background-color: #f8f9fa;
        }
        
        /* Tambahan style untuk kode ruangan */
        .ruangan-kode {
            font-size: 10px;
            color: #6c757d;
            margin-left: 5px;
        }
    </style>
</head>
<body>

<?php include 'header.php'; ?>

<!-- Main Content -->
<div class="container mt-4 fade-in">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h2 class="mb-1" style="color: var(--lapas-dark);">
                <i class="fas fa-file-alt me-2" style="color: var(--lapas-accent);"></i>Pengajuan Barang
            </h2>
            <p class="text-muted small">
                <i class="fas fa-info-circle me-1"></i>
                <?php echo $user_level == 'admin' ? 'Kelola pengajuan barang rusak/habis dari user' : 'Ajukan permintaan untuk barang rusak atau habis'; ?>
            </p>
        </div>
        <?php if ($user_level == 'user'): ?>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#ajukanModal">
            <i class="fas fa-plus me-2"></i>Ajukan Barang
        </button>
        <?php endif; ?>
    </div>
    
    <?php if (isset($_SESSION['alert_type']) && isset($_SESSION['alert_message'])): ?>
        <div class="alert alert-<?php echo $_SESSION['alert_type']; ?> alert-dismissible fade show" role="alert">
            <i class="fas fa-<?php echo $_SESSION['alert_type'] == 'success' ? 'check-circle' : 'exclamation-circle'; ?> me-2"></i>
            <?php 
            echo $_SESSION['alert_message'];
            unset($_SESSION['alert_type']);
            unset($_SESSION['alert_message']);
            ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
    
    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-md-3 mb-3">
            <div class="stats-card">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <p class="stats-label">Total Pengajuan</p>
                        <h3 class="stats-number"><?php echo isset($total_pengajuan) ? $total_pengajuan : 0; ?></h3>
                    </div>
                    <div class="stats-icon bg-primary bg-opacity-10 text-primary">
                        <i class="fas fa-file-alt"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="stats-card">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <p class="stats-label">Menunggu Verifikasi</p>
                        <h3 class="stats-number text-warning"><?php echo isset($menunggu) ? $menunggu : 0; ?></h3>
                    </div>
                    <div class="stats-icon bg-warning bg-opacity-10 text-warning">
                        <i class="fas fa-clock"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="stats-card">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <p class="stats-label">Disetujui</p>
                        <h3 class="stats-number text-success"><?php echo isset($disetujui) ? $disetujui : 0; ?></h3>
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
                        <p class="stats-label">Ditolak / Dibatalkan</p>
                        <h3 class="stats-number text-danger"><?php echo (isset($ditolak) ? $ditolak : 0) + (isset($dibatalkan) ? $dibatalkan : 0); ?></h3>
                    </div>
                    <div class="stats-icon bg-danger bg-opacity-10 text-danger">
                        <i class="fas fa-times-circle"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Filter Tanggal dan Ruangan -->
    <div class="filter-section">
        <form method="GET" action="" class="row g-3 align-items-end">
            <div class="col-md-3">
                <label class="form-label fw-bold">
                    <i class="fas fa-calendar-alt me-1"></i>Dari Tanggal
                </label>
                <input type="date" name="tanggal_mulai" id="tanggal_mulai" class="form-control" 
                       value="<?php echo $tanggal_mulai; ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label fw-bold">
                    <i class="fas fa-calendar-check me-1"></i>Sampai Tanggal
                </label>
                <input type="date" name="tanggal_selesai" id="tanggal_selesai" class="form-control" 
                       value="<?php echo $tanggal_selesai; ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label fw-bold">
                    <i class="fas fa-building me-1"></i>Filter Ruangan
                </label>
                <select name="filter_ruangan" class="form-select">
                    <option value="">-- Semua Ruangan --</option>
                    <?php foreach ($daftar_ruangan_filter as $ruang): ?>
                        <option value="<?php echo htmlspecialchars($ruang); ?>" 
                            <?php echo $filter_ruangan == $ruang ? 'selected' : ''; ?>>
                            🏢 <?php echo htmlspecialchars($ruang); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <div class="d-flex gap-2">
                    <button type="submit" class="btn-filter flex-grow-1">
                        <i class="fas fa-filter me-1"></i> Filter
                    </button>
                    <a href="pengajuan.php" class="btn-reset">
                        <i class="fas fa-undo-alt me-1"></i> Reset
                    </a>
                </div>
            </div>
        </form>
        
        <?php if ($tanggal_mulai || $tanggal_selesai || $filter_ruangan): ?>
        <div class="mt-3 pt-2 border-top">
            <small class="text-muted">
                <i class="fas fa-filter me-1"></i> Filter aktif:
                <?php if ($tanggal_mulai && $tanggal_selesai): ?>
                    <span class="badge bg-info">Tanggal: <?php echo date('d/m/Y', strtotime($tanggal_mulai)); ?> - <?php echo date('d/m/Y', strtotime($tanggal_selesai)); ?></span>
                <?php endif; ?>
                <?php if ($filter_ruangan): ?>
                    <span class="badge bg-primary">Ruangan: <?php echo htmlspecialchars($filter_ruangan); ?></span>
                <?php endif; ?>
            </small>
        </div>
        <?php endif; ?>
    </div>
    
    <!-- Data Table Pengajuan -->
    <div class="panel">
        <div class="panel-heading">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                <h5 class="mb-0">
                    <i class="fas fa-list me-2"></i>
                    Daftar Pengajuan
                    <?php if ($filter_ruangan): ?>
                        <small class="text-muted">(Ruangan: <?php echo htmlspecialchars($filter_ruangan); ?>)</small>
                    <?php endif; ?>
                </h5>
                <div>
                    <input type="text" id="searchInput" class="form-control form-control-sm" placeholder="Cari data..." style="width: 200px; display: inline-block;">
                </div>
            </div>
        </div>
        <div class="panel-body">
            <div class="table-responsive">
                <table class="table table-hover" id="pengajuanTable">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Tanggal</th>
                            <th>Nama Barang</th>
                            <th>Jenis</th>
                            <th>Jumlah</th>
                            <th>Ruangan</th>
                            <th>Alasan</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody id="tableBody">
                        <?php if (isset($result_pengajuan) && $result_pengajuan && mysqli_num_rows($result_pengajuan) > 0): ?>
                            <?php $no = 1; while($row = mysqli_fetch_assoc($result_pengajuan)): ?>
                                <tr>
                                    <td><?php echo $no++; ?></td>
                                    <td>
                                        <small><?php echo date('d/m/Y', strtotime($row['tanggal_pengajuan'])); ?></small>
                                        <br>
                                        <small class="text-muted"><?php echo date('H:i', strtotime($row['tanggal_pengajuan'])); ?></small>
                                    </td>
                                    <td><strong><?php echo htmlspecialchars($row['nama_barang']); ?></strong></td>
                                    <td>
                                        <span class="badge bg-<?php echo $row['jenis_pengajuan'] == 'Habis' ? 'warning' : 'danger'; ?>">
                                            <i class="fas fa-<?php echo $row['jenis_pengajuan'] == 'Habis' ? 'box-open' : 'tools'; ?> me-1"></i>
                                            <?php echo $row['jenis_pengajuan']; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge bg-secondary">
                                            <?php echo $row['jumlah']; ?> Unit
                                        </span>
                                    </td>
                                    <td>
                                        <span class="ruangan-badge">
                                            <i class="fas fa-building me-1"></i>
                                            <?php echo htmlspecialchars($row['nama_ruangan'] ?? '-'); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <small><?php echo htmlspecialchars(substr($row['alasan'], 0, 50)) . (strlen($row['alasan']) > 50 ? '...' : ''); ?></small>
                                        <?php if (!empty($row['keterangan_tambahan'])): ?>
                                            <br>
                                            <small class="text-muted">
                                                <i class="fas fa-comment"></i> <?php echo htmlspecialchars(substr($row['keterangan_tambahan'], 0, 40)); ?>
                                            </small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="status-badge status-<?php echo strtolower(str_replace(' ', '', $row['status'])); ?>">
                                            <i class="fas fa-<?php 
                                                echo $row['status'] == 'Menunggu Verifikasi' ? 'clock' : 
                                                     ($row['status'] == 'Disetujui' ? 'check' : 
                                                     ($row['status'] == 'Ditolak' ? 'times' : 'ban')); 
                                            ?> me-1"></i>
                                            <?php echo $row['status']; ?>
                                        </span>
                                        <?php if (!empty($row['catatan_admin'])): ?>
                                            <br>
                                            <small class="text-muted">
                                                <i class="fas fa-sticky-note"></i> <?php echo htmlspecialchars(substr($row['catatan_admin'], 0, 30)); ?>
                                            </small>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-nowrap">
                                        <?php if ($user_level == 'admin'): ?>
                                            <?php if ($row['status'] == 'Menunggu Verifikasi'): ?>
                                                <button onclick="openVerifikasiModal(
                                                    <?php echo $row['id_pengajuan']; ?>,
                                                    '<?php echo addslashes($row['nama_barang']); ?>',
                                                    '<?php echo $row['jenis_pengajuan']; ?>',
                                                    <?php echo $row['jumlah']; ?>,
                                                    '<?php echo addslashes($row['alasan']); ?>',
                                                    '<?php echo addslashes($row['nama_ruangan'] ?? ''); ?>'
                                                )" class="btn btn-sm btn-success btn-action" title="Verifikasi">
                                                    <i class="fas fa-check-circle"></i>
                                                </button>
                                            <?php endif; ?>
                                            
                                            <button onclick="openEditStatusModal(
                                                <?php echo $row['id_pengajuan']; ?>,
                                                '<?php echo addslashes($row['status']); ?>',
                                                '<?php echo addslashes($row['catatan_admin']); ?>'
                                            )" class="btn btn-sm btn-edit-status btn-action" title="Edit Status">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            
                                            <a href="?hapus=<?php echo $row['id_pengajuan']; ?>" 
                                               class="btn btn-sm btn-danger btn-action"
                                               onclick="return confirm('Yakin ingin menghapus pengajuan ini?')"
                                               title="Hapus Pengajuan">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                            
                                            <button class="btn btn-sm btn-info btn-action" 
                                                    onclick="viewDetail(
                                                        '<?php echo addslashes($row['nama_barang']); ?>',
                                                        '<?php echo $row['jenis_pengajuan']; ?>',
                                                        <?php echo $row['jumlah']; ?>,
                                                        '<?php echo addslashes($row['alasan']); ?>',
                                                        '<?php echo addslashes($row['keterangan_tambahan']); ?>',
                                                        '<?php echo $row['status']; ?>',
                                                        '<?php echo addslashes($row['catatan_admin']); ?>',
                                                        '<?php echo $row['tanggal_verifikasi']; ?>',
                                                        '<?php echo addslashes($row['diverifikasi_oleh']); ?>',
                                                        '<?php echo addslashes($row['nama_ruangan'] ?? ''); ?>'
                                                    )" title="Lihat Detail">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                        <?php elseif ($user_level == 'user' && $row['status'] == 'Menunggu Verifikasi'): ?>
                                            <a href="?batal=<?php echo $row['id_pengajuan']; ?>" 
                                               class="btn btn-sm btn-danger btn-action"
                                               onclick="return confirm('Batalkan pengajuan ini?')"
                                               title="Batalkan">
                                                <i class="fas fa-times"></i>
                                            </a>
                                            <button class="btn btn-sm btn-info btn-action" 
                                                    onclick="viewDetail(
                                                        '<?php echo addslashes($row['nama_barang']); ?>',
                                                        '<?php echo $row['jenis_pengajuan']; ?>',
                                                        <?php echo $row['jumlah']; ?>,
                                                        '<?php echo addslashes($row['alasan']); ?>',
                                                        '<?php echo addslashes($row['keterangan_tambahan']); ?>',
                                                        '<?php echo $row['status']; ?>',
                                                        '<?php echo addslashes($row['catatan_admin']); ?>',
                                                        '<?php echo $row['tanggal_verifikasi']; ?>',
                                                        '<?php echo addslashes($row['diverifikasi_oleh']); ?>',
                                                        '<?php echo addslashes($row['nama_ruangan'] ?? ''); ?>'
                                                    )" title="Lihat Detail">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                        <?php else: ?>
                                            <button class="btn btn-sm btn-info btn-action" 
                                                    onclick="viewDetail(
                                                        '<?php echo addslashes($row['nama_barang']); ?>',
                                                        '<?php echo $row['jenis_pengajuan']; ?>',
                                                        <?php echo $row['jumlah']; ?>,
                                                        '<?php echo addslashes($row['alasan']); ?>',
                                                        '<?php echo addslashes($row['keterangan_tambahan']); ?>',
                                                        '<?php echo $row['status']; ?>',
                                                        '<?php echo addslashes($row['catatan_admin']); ?>',
                                                        '<?php echo $row['tanggal_verifikasi']; ?>',
                                                        '<?php echo addslashes($row['diverifikasi_oleh']); ?>',
                                                        '<?php echo addslashes($row['nama_ruangan'] ?? ''); ?>'
                                                    )" title="Lihat Detail">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="9" class="text-center py-5">
                                    <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                                    <p class="text-muted">Tidak ada data pengajuan</p>
                                    <?php if ($user_level == 'user'): ?>
                                    <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#ajukanModal">
                                        <i class="fas fa-plus me-1"></i>Ajukan Barang
                                    </button>
                                    <?php endif; ?>
                                  </td>
                              </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal Ajukan Barang -->
<div class="modal fade" id="ajukanModal" tabindex="-1" aria-labelledby="ajukanModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" action="" id="formAjukan">
                <div class="modal-header">
                    <h5 class="modal-title" id="ajukanModalLabel">
                        <i class="fas fa-paper-plane me-2"></i>Ajukan Barang
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Nama Barang <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="nama_barang" id="nama_barang" required 
                                   placeholder="Masukkan nama barang yang dibutuhkan">
                            <small class="text-muted">Contoh: Kertas HVS A4, Tinta Printer, Mouse, Keyboard, dll.</small>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Jenis Pengajuan <span class="text-danger">*</span></label>
                            <select class="form-select" name="jenis_pengajuan" id="jenis_pengajuan" required>
                                <option value="">-- Pilih Jenis --</option>
                                <option value="Habis">Habis / Kehabisan Stok</option>
                                <option value="Rusak">Rusak / Tidak Berfungsi</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Jumlah <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" name="jumlah" id="jumlah" required min="1" value="1">
                            <small class="text-muted">Masukkan jumlah yang dibutuhkan</small>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Pilih Ruangan <span class="text-danger">*</span></label>
                            <select class="form-select" name="nama_ruangan" id="nama_ruangan" required>
                                <option value="">-- Pilih Ruangan --</option>
                                <?php foreach ($daftar_ruangan_db as $ruang): ?>
                                    <option value="<?php echo htmlspecialchars($ruang['nama_ruangan']); ?>">
                                        🏢 <?php echo htmlspecialchars($ruang['nama_ruangan']); ?>
                                        <span class="ruangan-kode">(<?php echo htmlspecialchars($ruang['kode_ruangan']); ?>)</span>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <small class="text-muted">Pilih ruangan yang membutuhkan barang</small>
                        </div>
                        <div class="col-12 mb-3">
                            <label class="form-label">Alasan Pengajuan <span class="text-danger">*</span></label>
                            <textarea class="form-control" name="alasan" id="alasan" rows="4" required 
                                      placeholder="Jelaskan secara detail alasan pengajuan barang ini..."></textarea>
                            <small class="text-muted">Contoh: Stok habis karena penggunaan bulan ini meningkat, Barang rusak setelah pemakaian 2 tahun, dll.</small>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" name="ajukan" class="btn btn-primary">
                        <i class="fas fa-paper-plane me-1"></i>Ajukan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Verifikasi (Admin) -->
<div class="modal fade" id="verifikasiModal" tabindex="-1" aria-labelledby="verifikasiModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="">
                <div class="modal-header">
                    <h5 class="modal-title" id="verifikasiModalLabel">
                        <i class="fas fa-check-circle me-2"></i>Verifikasi Pengajuan
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="id_pengajuan" id="verif_id_pengajuan">
                    
                    <div class="alert alert-info">
                        <strong>Detail Pengajuan:</strong><br>
                        <i class="fas fa-box"></i> Nama Barang: <strong><span id="verif_nama_barang"></span></strong><br>
                        <i class="fas fa-tag"></i> Jenis: <span id="verif_jenis"></span><br>
                        <i class="fas fa-cubes"></i> Jumlah: <span id="verif_jumlah"></span> Unit<br>
                        <i class="fas fa-building"></i> Ruangan: <span id="verif_ruangan"></span><br>
                        <i class="fas fa-comment"></i> Alasan: <span id="verif_alasan"></span>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Status Verifikasi <span class="text-danger">*</span></label>
                        <select class="form-select" name="status_verifikasi" id="status_verifikasi" required>
                            <option value="Disetujui">Setujui</option>
                            <option value="Ditolak">Tolak</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Catatan Admin</label>
                        <textarea class="form-control" name="catatan_admin" rows="3" 
                                  placeholder="Tambahkan catatan jika diperlukan..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" name="verifikasi" class="btn btn-primary">
                        <i class="fas fa-save me-1"></i>Simpan Verifikasi
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Edit Status (Admin) -->
<div class="modal fade" id="editStatusModal" tabindex="-1" aria-labelledby="editStatusModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="">
                <div class="modal-header">
                    <h5 class="modal-title" id="editStatusModalLabel">
                        <i class="fas fa-edit me-2"></i>Edit Status Pengajuan
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="id_pengajuan" id="edit_id_pengajuan">
                    
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        Ubah status pengajuan sesuai kebutuhan.
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Status <span class="text-danger">*</span></label>
                        <select class="form-select" name="status_baru" id="status_baru" required>
                            <option value="Menunggu Verifikasi">⏳ Menunggu Verifikasi</option>
                            <option value="Disetujui">✅ Disetujui</option>
                            <option value="Ditolak">❌ Ditolak</option>
                        </select>
                        <small class="text-muted">Pilih status baru untuk pengajuan ini</small>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Catatan Admin</label>
                        <textarea class="form-control" name="catatan_admin" id="edit_catatan_admin" rows="3" 
                                  placeholder="Tambahkan catatan untuk perubahan status ini..."></textarea>
                        <small class="text-muted">Catatan akan membantu user memahami perubahan status</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" name="edit_status" class="btn btn-primary">
                        <i class="fas fa-save me-1"></i>Simpan Perubahan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Detail -->
<div class="modal fade" id="detailModal" tabindex="-1" aria-labelledby="detailModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="detailModalLabel">
                    <i class="fas fa-info-circle me-2"></i>Detail Pengajuan
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="detailContent"></div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>

<script>
$(document).ready(function() {
    // Filter/Search functionality
    $("#searchInput").on("keyup", function() {
        var value = $(this).val().toLowerCase();
        $("#tableBody tr").filter(function() {
            $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1);
        });
    });
    
    // Auto-hide alerts
    setTimeout(function() {
        $('.alert').fadeOut('slow');
    }, 5000);
});

// Validate before submit
var formAjukan = document.getElementById('formAjukan');
if (formAjukan) {
    formAjukan.addEventListener('submit', function(e) {
        var jumlah = parseInt(document.getElementById('jumlah').value);
        var namaBarang = document.getElementById('nama_barang').value.trim();
        var ruangan = document.getElementById('nama_ruangan').value;
        
        if (!namaBarang) {
            e.preventDefault();
            Swal.fire({
                icon: 'error',
                title: 'Nama Barang Kosong',
                text: 'Silakan masukkan nama barang yang dibutuhkan'
            });
            return false;
        }
        
        if (namaBarang.length < 3) {
            e.preventDefault();
            Swal.fire({
                icon: 'error',
                title: 'Nama Barang Terlalu Pendek',
                text: 'Silakan masukkan nama barang dengan minimal 3 karakter'
            });
            return false;
        }
        
        if (!ruangan) {
            e.preventDefault();
            Swal.fire({
                icon: 'error',
                title: 'Pilih Ruangan',
                text: 'Silakan pilih ruangan terlebih dahulu'
            });
            return false;
        }
        
        var alasan = document.getElementById('alasan').value.trim();
        if (!alasan) {
            e.preventDefault();
            Swal.fire({
                icon: 'error',
                title: 'Alasan Kosong',
                text: 'Silakan isi alasan pengajuan'
            });
            return false;
        }
        
        if (alasan.length < 10) {
            e.preventDefault();
            Swal.fire({
                icon: 'error',
                title: 'Alasan Terlalu Pendek',
                text: 'Silakan berikan alasan yang lebih jelas (minimal 10 karakter)'
            });
            return false;
        }
        
        if (jumlah < 1) {
            e.preventDefault();
            Swal.fire({
                icon: 'error',
                title: 'Jumlah Tidak Valid',
                text: 'Jumlah minimal 1 unit'
            });
            return false;
        }
        
        if (jumlah > 1000) {
            e.preventDefault();
            Swal.fire({
                icon: 'warning',
                title: 'Jumlah Besar',
                text: 'Apakah Anda yakin dengan jumlah yang diajukan?',
                showCancelButton: true,
                confirmButtonText: 'Ya, Lanjutkan',
                cancelButtonText: 'Batalkan'
            }).then((result) => {
                if (result.isConfirmed) {
                    formAjukan.submit();
                }
            });
            return false;
        }
        
        return true;
    });
}

// Open verifikasi modal
function openVerifikasiModal(id, nama, jenis, jumlah, alasan, ruangan) {
    document.getElementById('verif_id_pengajuan').value = id;
    document.getElementById('verif_nama_barang').innerHTML = nama;
    document.getElementById('verif_jenis').innerHTML = jenis;
    document.getElementById('verif_jumlah').innerHTML = jumlah;
    document.getElementById('verif_ruangan').innerHTML = ruangan || '-';
    document.getElementById('verif_alasan').innerHTML = alasan;
    
    var modal = new bootstrap.Modal(document.getElementById('verifikasiModal'));
    modal.show();
}

// Open edit status modal
function openEditStatusModal(id, statusSekarang, catatan) {
    document.getElementById('edit_id_pengajuan').value = id;
    document.getElementById('status_baru').value = statusSekarang;
    document.getElementById('edit_catatan_admin').value = catatan || '';
    
    var modal = new bootstrap.Modal(document.getElementById('editStatusModal'));
    modal.show();
}

// View detail
function viewDetail(nama, jenis, jumlah, alasan, keterangan, status, catatan, tgl_verif, diverifikasi, ruangan) {
    var statusIcon = '';
    
    switch(status) {
        case 'Menunggu Verifikasi':
            statusIcon = 'clock';
            break;
        case 'Disetujui':
            statusIcon = 'check';
            break;
        case 'Ditolak':
            statusIcon = 'times';
            break;
        case 'Dibatalkan':
            statusIcon = 'ban';
            break;
        default:
            statusIcon = 'circle';
    }
    
    var content = `
        <div class="timeline">
            <div class="timeline-item">
                <strong><i class="fas fa-box"></i> Informasi Barang</strong><br>
                Nama: <strong>${nama}</strong><br>
                Jenis Pengajuan: <span class="badge bg-${jenis == 'Habis' ? 'warning' : 'danger'}">${jenis}</span><br>
                Jumlah: ${jumlah} Unit
            </div>
            <div class="timeline-item">
                <strong><i class="fas fa-building"></i> Lokasi Ruangan</strong><br>
                ${ruangan || '-'}
            </div>
            <div class="timeline-item">
                <strong><i class="fas fa-comment"></i> Alasan & Keterangan</strong><br>
                Alasan: ${alasan}<br>
                Keterangan Tambahan: ${keterangan || '-'}
            </div>
            <div class="timeline-item">
                <strong><i class="fas fa-clipboard-list"></i> Status Pengajuan</strong><br>
                <span class="status-badge status-${status.toLowerCase().replace(' ', '')}">
                    <i class="fas fa-${statusIcon} me-1"></i> ${status}
                </span>
                ${catatan ? `<br><small><i class="fas fa-sticky-note"></i> Catatan Admin: ${catatan}</small>` : ''}
            </div>
    `;
    
    if (tgl_verif && tgl_verif != '0000-00-00 00:00:00' && tgl_verif != '') {
        content += `
            <div class="timeline-item">
                <strong><i class="fas fa-user-check"></i> Verifikasi</strong><br>
                Diverifikasi oleh: ${diverifikasi || '-'}<br>
                Tanggal: ${new Date(tgl_verif).toLocaleString('id-ID')}
            </div>
        `;
    }
    
    content += `</div>`;
    
    document.getElementById('detailContent').innerHTML = content;
    var modal = new bootstrap.Modal(document.getElementById('detailModal'));
    modal.show();
}
</script>

</body>
</html>