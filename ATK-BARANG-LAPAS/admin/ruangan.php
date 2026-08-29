<?php
// ruangan.php
// Halaman untuk mengelola data ruangan (Admin only)

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include '../assets/conn/config.php';

// Pastikan session username tersedia
if (!isset($_SESSION['username'])) {
    header("location:../index.php");
    exit();
}

// Hanya admin yang bisa mengakses
if ($_SESSION['level'] != 'admin') {
    header("location:index.php");
    exit();
}

$username = $_SESSION['username'];

// Fungsi untuk generate kode ruangan otomatis (fallback jika AJAX gagal)
function generateKodeRuangan($conn) {
    $query = "SELECT kode_ruangan FROM ruangan ORDER BY kode_ruangan";
    $result = mysqli_query($conn, $query);
    
    $existing_numbers = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $angka = (int) preg_replace('/[^0-9]/', '', $row['kode_ruangan']);
        if ($angka > 0) {
            $existing_numbers[] = $angka;
        }
    }
    
    if (empty($existing_numbers)) {
        return 'R001';
    }
    
    sort($existing_numbers);
    $expected = 1;
    foreach ($existing_numbers as $num) {
        if ($num > $expected) {
            return 'R' . str_pad($expected, 3, '0', STR_PAD_LEFT);
        }
        $expected = $num + 1;
    }
    
    return 'R' . str_pad($expected, 3, '0', STR_PAD_LEFT);
}

// Proses Tambah Ruangan
if (isset($_POST['tambah'])) {
    $kode_ruangan = mysqli_real_escape_string($conn, $_POST['kode_ruangan']);
    $nama_ruangan = mysqli_real_escape_string($conn, $_POST['nama_ruangan']);
    $deskripsi = mysqli_real_escape_string($conn, $_POST['deskripsi']);
    $penanggung_jawab = mysqli_real_escape_string($conn, $_POST['penanggung_jawab']);
    $status = mysqli_real_escape_string($conn, $_POST['status']);
    
    // Jika kode ruangan kosong, generate otomatis
    if (empty($kode_ruangan)) {
        $kode_ruangan = generateKodeRuangan($conn);
    }
    
    // Cek apakah kode ruangan sudah ada
    $cek_query = "SELECT kode_ruangan FROM ruangan WHERE kode_ruangan = '$kode_ruangan'";
    $cek_result = mysqli_query($conn, $cek_query);
    
    if (mysqli_num_rows($cek_result) > 0) {
        $_SESSION['alert_type'] = 'error';
        $_SESSION['alert_message'] = 'Kode ruangan ' . $kode_ruangan . ' sudah ada!';
    } else {
        $query = "INSERT INTO ruangan (kode_ruangan, nama_ruangan, deskripsi, penanggung_jawab, status) 
                  VALUES ('$kode_ruangan', '$nama_ruangan', '$deskripsi', '$penanggung_jawab', '$status')";
        
        if (mysqli_query($conn, $query)) {
            $log_query = "INSERT INTO log_aktivitas (username, aktivitas, ip_address, user_agent, tanggal) 
                          VALUES ('$username', 'Menambahkan ruangan baru: $kode_ruangan - $nama_ruangan', '{$_SERVER['REMOTE_ADDR']}', '{$_SERVER['HTTP_USER_AGENT']}', NOW())";
            mysqli_query($conn, $log_query);
            
            $_SESSION['alert_type'] = 'success';
            $_SESSION['alert_message'] = 'Ruangan berhasil ditambahkan! Kode: ' . $kode_ruangan;
        } else {
            $_SESSION['alert_type'] = 'error';
            $_SESSION['alert_message'] = 'Gagal menambahkan ruangan: ' . mysqli_error($conn);
        }
    }
    
    header("Location: ruangan.php");
    exit();
}

// Proses Edit Ruangan
if (isset($_POST['edit'])) {
    $id_ruangan = (int)$_POST['id_ruangan'];
    $kode_ruangan = mysqli_real_escape_string($conn, $_POST['kode_ruangan']);
    $nama_ruangan = mysqli_real_escape_string($conn, $_POST['nama_ruangan']);
    $deskripsi = mysqli_real_escape_string($conn, $_POST['deskripsi']);
    $penanggung_jawab = mysqli_real_escape_string($conn, $_POST['penanggung_jawab']);
    $status = mysqli_real_escape_string($conn, $_POST['status']);
    
    $query = "UPDATE ruangan SET 
              kode_ruangan='$kode_ruangan',
              nama_ruangan='$nama_ruangan', 
              deskripsi='$deskripsi',
              penanggung_jawab='$penanggung_jawab',
              status='$status'
              WHERE id_ruangan=$id_ruangan";
    
    if (mysqli_query($conn, $query)) {
        $log_query = "INSERT INTO log_aktivitas (username, aktivitas, ip_address, user_agent, tanggal) 
                      VALUES ('$username', 'Mengedit ruangan: $kode_ruangan - $nama_ruangan', '{$_SERVER['REMOTE_ADDR']}', '{$_SERVER['HTTP_USER_AGENT']}', NOW())";
        mysqli_query($conn, $log_query);
        
        $_SESSION['alert_type'] = 'success';
        $_SESSION['alert_message'] = 'Ruangan berhasil diperbarui!';
    } else {
        $_SESSION['alert_type'] = 'error';
        $_SESSION['alert_message'] = 'Gagal memperbarui ruangan: ' . mysqli_error($conn);
    }
    
    header("Location: ruangan.php");
    exit();
}

// Proses Hapus Ruangan
if (isset($_GET['hapus'])) {
    $id_ruangan = (int)$_GET['hapus'];
    
    // Ambil data ruangan untuk log
    $nama_query = "SELECT kode_ruangan, nama_ruangan FROM ruangan WHERE id_ruangan=$id_ruangan";
    $nama_result = mysqli_query($conn, $nama_query);
    $kode_ruangan = '';
    $nama_ruangan = '';
    if ($nama_result && mysqli_num_rows($nama_result) > 0) {
        $row = mysqli_fetch_assoc($nama_result);
        $kode_ruangan = $row['kode_ruangan'];
        $nama_ruangan = $row['nama_ruangan'];
    }
    
    $query = "DELETE FROM ruangan WHERE id_ruangan=$id_ruangan";
    
    if (mysqli_query($conn, $query)) {
        $log_query = "INSERT INTO log_aktivitas (username, aktivitas, ip_address, user_agent, tanggal) 
                      VALUES ('$username', 'Menghapus ruangan: $kode_ruangan - $nama_ruangan', '{$_SERVER['REMOTE_ADDR']}', '{$_SERVER['HTTP_USER_AGENT']}', NOW())";
        mysqli_query($conn, $log_query);
        
        $_SESSION['alert_type'] = 'success';
        $_SESSION['alert_message'] = 'Ruangan berhasil dihapus!';
    } else {
        $_SESSION['alert_type'] = 'error';
        $_SESSION['alert_message'] = 'Gagal menghapus ruangan: ' . mysqli_error($conn);
    }
    
    header("Location: ruangan.php");
    exit();
}

// Ambil data ruangan
$query = "SELECT * FROM ruangan ORDER BY kode_ruangan";
$result = mysqli_query($conn, $query);
$total_ruangan = mysqli_num_rows($result);
$total_aktif = 0;
$total_tidak_aktif = 0;

if ($total_ruangan > 0) {
    mysqli_data_seek($result, 0);
    while ($row = mysqli_fetch_assoc($result)) {
        if ($row['status'] == 'Aktif') {
            $total_aktif++;
        } else {
            $total_tidak_aktif++;
        }
    }
    mysqli_data_seek($result, 0);
}

$auto_kode = generateKodeRuangan($conn);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Ruangan - Inventaris LAPAS</title>
    
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
        
        .fade-in {
            animation: fadeIn 0.5s ease;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
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
            transition: all 0.3s;
        }
        
        .kode-input-group .refresh-btn:hover {
            color: var(--lapas-accent);
            transform: translateY(-50%) rotate(180deg);
        }
        
        .badge-auto {
            background: #e3f2fd;
            color: #0f3b5c;
            font-size: 11px;
            padding: 3px 8px;
            border-radius: 20px;
        }
        
        .timeline-item {
            padding: 10px 0;
            border-bottom: 1px solid #eee;
        }
        
        .timeline-item:last-child {
            border-bottom: none;
        }
    </style>
</head>
<body>

<?php include 'header.php'; ?>

<div class="container mt-4 fade-in">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="mb-1" style="color: var(--lapas-dark);">
                <i class="fas fa-building me-2" style="color: var(--lapas-accent);"></i>Kelola Ruangan
            </h2>
            <p class="text-muted small">Manajemen data ruangan kantor LAPAS</p>
        </div>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#tambahModal">
            <i class="fas fa-plus me-2"></i>Tambah Ruangan
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
        <div class="col-md-4 mb-3">
            <div class="stats-card">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <p class="stats-label">Total Ruangan</p>
                        <h3 class="stats-number"><?php echo $total_ruangan; ?></h3>
                    </div>
                    <div class="stats-icon bg-primary bg-opacity-10 text-primary">
                        <i class="fas fa-building"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-3">
            <div class="stats-card">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <p class="stats-label">Aktif</p>
                        <h3 class="stats-number text-success"><?php echo $total_aktif; ?></h3>
                    </div>
                    <div class="stats-icon bg-success bg-opacity-10 text-success">
                        <i class="fas fa-check-circle"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-3">
            <div class="stats-card">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <p class="stats-label">Tidak Aktif</p>
                        <h3 class="stats-number text-danger"><?php echo $total_tidak_aktif; ?></h3>
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
                <h5 class="mb-0"><i class="fas fa-table me-2"></i>Daftar Ruangan</h5>
                <div>
                    <button class="btn btn-sm btn-outline-success" id="exportExcel">
                        <i class="fas fa-file-excel me-1"></i> Export Excel
                    </button>
                </div>
            </div>
        </div>
        <div class="panel-body">
            <div class="table-responsive">
                <table class="table table-hover" id="ruanganTable">
                    <thead>
                        <tr>
                            <th width="5%">No</th>
                            <th width="15%">Kode</th>
                            <th width="35%">Nama Ruangan</th>
                            <th width="25%">Penanggung Jawab</th>
                            <th width="10%">Status</th>
                            <th width="10%">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($total_ruangan > 0): ?>
                            <?php $no = 1; while($row = mysqli_fetch_assoc($result)): ?>
                                <tr>
                                    <td><?php echo $no++; ?></td>
                                    <td><strong><?php echo htmlspecialchars($row['kode_ruangan']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($row['nama_ruangan']); ?></td>
                                    <td><?php echo htmlspecialchars($row['penanggung_jawab'] ?: '-'); ?></td>
                                    <td>
                                        <span class="label-<?php echo $row['status'] == 'Aktif' ? 'success' : 'danger'; ?>">
                                            <i class="fas fa-<?php echo $row['status'] == 'Aktif' ? 'check' : 'times'; ?> me-1"></i>
                                            <?php echo $row['status']; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <button onclick="openEditModal(
                                                <?php echo $row['id_ruangan']; ?>,
                                                '<?php echo addslashes($row['kode_ruangan']); ?>',
                                                '<?php echo addslashes($row['nama_ruangan']); ?>',
                                                '<?php echo addslashes($row['deskripsi']); ?>',
                                                '<?php echo addslashes($row['penanggung_jawab']); ?>',
                                                '<?php echo $row['status']; ?>'
                                            )" class="btn btn-info btn-action" title="Edit Ruangan">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <a href="?hapus=<?php echo $row['id_ruangan']; ?>" 
                                               class="btn btn-danger btn-action"
                                               onclick="return confirm('Yakin ingin menghapus ruangan <?php echo addslashes($row['nama_ruangan']); ?>?')"
                                               title="Hapus Ruangan">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                            <button onclick="viewDetail(
                                                '<?php echo addslashes($row['kode_ruangan']); ?>',
                                                '<?php echo addslashes($row['nama_ruangan']); ?>',
                                                '<?php echo addslashes($row['deskripsi']); ?>',
                                                '<?php echo addslashes($row['penanggung_jawab']); ?>',
                                                '<?php echo $row['status']; ?>',
                                                '<?php echo $row['created_at']; ?>',
                                                '<?php echo $row['updated_at']; ?>'
                                            )" class="btn btn-secondary btn-action" title="Lihat Detail">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center py-5">
                                    <i class="fas fa-building fa-3x text-muted mb-3 d-block"></i>
                                    <p class="text-muted">Belum ada data ruangan</p>
                                    <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#tambahModal">
                                        <i class="fas fa-plus me-1"></i>Tambah Ruangan Pertama
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

<!-- Modal Tambah Ruangan -->
<div class="modal fade" id="tambahModal" tabindex="-1" aria-labelledby="tambahModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" action="" id="formTambahRuangan">
                <div class="modal-header">
                    <h5 class="modal-title" id="tambahModalLabel">
                        <i class="fas fa-plus-circle me-2"></i>Tambah Ruangan Baru
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">
                                Kode Ruangan 
                                <span class="badge-auto ms-2">
                                    <i class="fas fa-magic me-1"></i>Auto Generate
                                </span>
                            </label>
                            <div class="kode-input-group">
                                <input type="text" class="form-control" name="kode_ruangan" id="kode_ruangan" 
                                       maxlength="10" placeholder="Kosongkan untuk auto generate" 
                                       style="font-family: monospace;">
                                <button type="button" class="refresh-btn" onclick="refreshAutoKode()" title="Generate ulang kode">
                                    <i class="fas fa-sync-alt"></i>
                                </button>
                            </div>
                            <small class="text-muted" id="kodeInfo">
                                <i class="fas fa-info-circle me-1"></i>Kode otomatis: <strong id="previewKode"><?php echo $auto_kode; ?></strong>
                            </small>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Nama Ruangan <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="nama_ruangan" required maxlength="100" 
                                   placeholder="Contoh: Ruangan Rapat, Ruangan Server">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Penanggung Jawab</label>
                            <input type="text" class="form-control" name="penanggung_jawab" maxlength="60" 
                                   placeholder="Nama penanggung jawab ruangan">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Status <span class="text-danger">*</span></label>
                            <select class="form-select" name="status" required>
                                <option value="Aktif">Aktif</option>
                                <option value="Tidak Aktif">Tidak Aktif</option>
                            </select>
                        </div>
                        <div class="col-12 mb-3">
                            <label class="form-label">Deskripsi</label>
                            <textarea class="form-control" name="deskripsi" rows="3" 
                                      placeholder="Deskripsi ruangan, fasilitas, dll..."></textarea>
                        </div>
                    </div>
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

<!-- Modal Edit Ruangan -->
<div class="modal fade" id="editModal" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" action="">
                <div class="modal-header">
                    <h5 class="modal-title" id="editModalLabel">
                        <i class="fas fa-edit me-2"></i>Edit Ruangan
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="id_ruangan" id="edit_id_ruangan">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Kode Ruangan <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="kode_ruangan" id="edit_kode_ruangan" required maxlength="10" style="font-family: monospace;">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Nama Ruangan <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="nama_ruangan" id="edit_nama_ruangan" required maxlength="100">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Penanggung Jawab</label>
                            <input type="text" class="form-control" name="penanggung_jawab" id="edit_penanggung_jawab" maxlength="60">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Status <span class="text-danger">*</span></label>
                            <select class="form-select" name="status" id="edit_status" required>
                                <option value="Aktif">Aktif</option>
                                <option value="Tidak Aktif">Tidak Aktif</option>
                            </select>
                        </div>
                        <div class="col-12 mb-3">
                            <label class="form-label">Deskripsi</label>
                            <textarea class="form-control" name="deskripsi" id="edit_deskripsi" rows="3"></textarea>
                        </div>
                    </div>
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

<!-- Modal Detail Ruangan -->
<div class="modal fade" id="detailModal" tabindex="-1" aria-labelledby="detailModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="detailModalLabel">
                    <i class="fas fa-info-circle me-2"></i>Detail Ruangan
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

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>

<script>
var autoKode = '<?php echo $auto_kode; ?>';

$(document).ready(function() {
    if ($('#ruanganTable tbody tr').length > 0) {
        $('#ruanganTable').DataTable({
            language: {
                url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/id.json'
            },
            order: [[1, 'asc']],
            pageLength: 10,
            columnDefs: [
                { orderable: false, targets: [5] }
            ]
        });
    }
    
    $('#exportExcel').click(function() {
        var table = document.getElementById('ruanganTable');
        var wb = XLSX.utils.table_to_book(table, {sheet: "Data Ruangan", raw: true});
        XLSX.writeFile(wb, "Data_Ruangan_LAPAS.xlsx");
    });
    
    $('#tambahModal').on('show.bs.modal', function() {
        refreshAutoKode();
    });
    
    $('#tambahModal').on('hidden.bs.modal', function() {
        $('#formTambahRuangan')[0].reset();
        refreshAutoKode();
    });
    
    setTimeout(function() {
        $('.alert').fadeOut('slow');
    }, 3000);
});

function refreshAutoKode() {
    $.ajax({
        url: 'get_kode_ruangan.php',
        type: 'GET',
        dataType: 'json',
        success: function(data) {
            if (data.kode_ruangan && data.status === 'success') {
                $('#kode_ruangan').val(data.kode_ruangan);
                $('#previewKode').html(data.kode_ruangan);
                
                var infoText = '';
                if (data.mode === 'fill_gap') {
                    infoText = '<i class="fas fa-search me-1"></i>Mengisi kode kosong: <strong>' + data.kode_ruangan + '</strong>';
                } else {
                    infoText = '<i class="fas fa-arrow-right me-1"></i>Kode berikutnya: <strong>' + data.kode_ruangan + '</strong>';
                }
                $('#kodeInfo').html(infoText);
            } else {
                $('#kode_ruangan').val('');
                $('#previewKode').html(autoKode);
                $('#kodeInfo').html('<i class="fas fa-info-circle me-1"></i>Kode otomatis: <strong>' + autoKode + '</strong>');
            }
        },
        error: function() {
            $('#kode_ruangan').val('');
            $('#previewKode').html(autoKode);
            $('#kodeInfo').html('<i class="fas fa-info-circle me-1"></i>Kode otomatis: <strong>' + autoKode + '</strong>');
        }
    });
}

function openEditModal(id, kode, nama, deskripsi, penanggung_jawab, status) {
    document.getElementById('edit_id_ruangan').value = id;
    document.getElementById('edit_kode_ruangan').value = kode;
    document.getElementById('edit_nama_ruangan').value = nama;
    document.getElementById('edit_deskripsi').value = deskripsi || '';
    document.getElementById('edit_penanggung_jawab').value = penanggung_jawab || '';
    document.getElementById('edit_status').value = status;
    
    var editModal = new bootstrap.Modal(document.getElementById('editModal'));
    editModal.show();
}

function viewDetail(kode, nama, deskripsi, penanggung_jawab, status, created_at, updated_at) {
    var content = `
        <div class="timeline">
            <div class="timeline-item">
                <strong><i class="fas fa-building me-2"></i>Informasi Ruangan</strong><br>
                <div class="mt-2">
                    <span class="text-muted" style="width: 130px; display: inline-block;">Kode Ruangan:</span>
                    <strong>${kode}</strong><br>
                    <span class="text-muted" style="width: 130px; display: inline-block;">Nama Ruangan:</span>
                    ${nama}
                </div>
            </div>
            <div class="timeline-item">
                <strong><i class="fas fa-user-tie me-2"></i>Penanggung Jawab</strong><br>
                <span class="ms-3">${penanggung_jawab || '-'}</span>
            </div>
            <div class="timeline-item">
                <strong><i class="fas fa-info-circle me-2"></i>Deskripsi</strong><br>
                <div class="ms-3">${deskripsi || '-'}</div>
            </div>
            <div class="timeline-item">
                <strong><i class="fas fa-clipboard-list me-2"></i>Status</strong><br>
                <span class="ms-3">
                    <span class="label-${status == 'Aktif' ? 'success' : 'danger'}">
                        <i class="fas fa-${status == 'Aktif' ? 'check' : 'times'} me-1"></i>
                        ${status}
                    </span>
                </span>
            </div>
            <div class="timeline-item">
                <strong><i class="fas fa-calendar-alt me-2"></i>Waktu</strong><br>
                <div class="ms-3">
                    Dibuat: ${created_at || '-'}<br>
                    Diupdate: ${updated_at || '-'}
                </div>
            </div>
        </div>
    `;
    
    document.getElementById('detailContent').innerHTML = content;
    var modal = new bootstrap.Modal(document.getElementById('detailModal'));
    modal.show();
}
</script>

</body>
</html>