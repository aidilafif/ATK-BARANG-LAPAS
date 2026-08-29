<?php
// barang.php
// Halaman kelola data barang inventaris dengan auto generate kode

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

// Fungsi untuk generate kode barang otomatis
function generateKodeBarang($conn) {
    // Ambil kode barang terakhir
    $query = "SELECT kode_barang FROM barang ORDER BY kode_barang DESC LIMIT 1";
    $result = mysqli_query($conn, $query);
    
    if (mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        $last_kode = $row['kode_barang'];
        
        // Ambil angka dari kode (misal B025 -> 25)
        $angka = (int) substr($last_kode, 1);
        $angka_baru = $angka + 1;
        
        // Format dengan 3 digit (B001, B002, dst)
        $kode_baru = 'B' . str_pad($angka_baru, 3, '0', STR_PAD_LEFT);
        
        return $kode_baru;
    } else {
        // Jika belum ada data, mulai dari B001
        return 'B001';
    }
}

// Proses Tambah Barang
if (isset($_POST['tambah'])) {
    // Jika kode_barang dikirim dari form (auto generate atau manual)
    if (isset($_POST['kode_barang']) && !empty($_POST['kode_barang'])) {
        $kode_barang = mysqli_real_escape_string($conn, $_POST['kode_barang']);
    } else {
        // Auto generate kode
        $kode_barang = generateKodeBarang($conn);
    }
    
    $nama_barang = mysqli_real_escape_string($conn, $_POST['nama_barang']);
    $stok = (int)$_POST['stok'];
    $status = mysqli_real_escape_string($conn, $_POST['status']);
    $keterangan = mysqli_real_escape_string($conn, $_POST['keterangan']);
    
    // Cek apakah kode barang sudah ada
    $cek_query = "SELECT kode_barang FROM barang WHERE kode_barang = '$kode_barang'";
    $cek_result = mysqli_query($conn, $cek_query);
    
    if (mysqli_num_rows($cek_result) > 0) {
        $_SESSION['alert_type'] = 'error';
        $_SESSION['alert_message'] = 'Kode barang sudah ada! Generate ulang atau gunakan kode manual.';
    } else {
        $query = "INSERT INTO barang (kode_barang, nama_barang, stok, status, keterangan) 
                  VALUES ('$kode_barang', '$nama_barang', $stok, '$status', '$keterangan')";
        
        if (mysqli_query($conn, $query)) {
            $_SESSION['alert_type'] = 'success';
            $_SESSION['alert_message'] = 'Barang berhasil ditambahkan! Kode: ' . $kode_barang;
        } else {
            $_SESSION['alert_type'] = 'error';
            $_SESSION['alert_message'] = 'Gagal menambahkan barang: ' . mysqli_error($conn);
        }
    }
    
    header("Location: barang.php");
    exit();
}

// Proses Edit Barang
if (isset($_POST['edit'])) {
    $kode_barang = mysqli_real_escape_string($conn, $_POST['kode_barang']);
    $nama_barang = mysqli_real_escape_string($conn, $_POST['nama_barang']);
    $stok = (int)$_POST['stok'];
    $status = mysqli_real_escape_string($conn, $_POST['status']);
    $keterangan = mysqli_real_escape_string($conn, $_POST['keterangan']);
    
    $query = "UPDATE barang SET 
              nama_barang='$nama_barang', 
              stok=$stok, 
              status='$status', 
              keterangan='$keterangan' 
              WHERE kode_barang='$kode_barang'";
    
    if (mysqli_query($conn, $query)) {
        $_SESSION['alert_type'] = 'success';
        $_SESSION['alert_message'] = 'Barang berhasil diperbarui!';
    } else {
        $_SESSION['alert_type'] = 'error';
        $_SESSION['alert_message'] = 'Gagal memperbarui barang: ' . mysqli_error($conn);
    }
    
    header("Location: barang.php");
    exit();
}

// Proses Verifikasi Barang (Update Status)
if (isset($_GET['verifikasi'])) {
    $kode_barang = mysqli_real_escape_string($conn, $_GET['verifikasi']);
    $status_baru = mysqli_real_escape_string($conn, $_GET['status']);
    
    $query = "UPDATE barang SET status='$status_baru' WHERE kode_barang='$kode_barang'";
    
    if (mysqli_query($conn, $query)) {
        $_SESSION['alert_type'] = 'success';
        $_SESSION['alert_message'] = 'Status barang berhasil diverifikasi!';
    } else {
        $_SESSION['alert_type'] = 'error';
        $_SESSION['alert_message'] = 'Gagal memverifikasi barang: ' . mysqli_error($conn);
    }
    
    header("Location: barang.php");
    exit();
}

// Proses Hapus Barang
if (isset($_GET['hapus'])) {
    $kode_barang = mysqli_real_escape_string($conn, $_GET['hapus']);
    
    $query = "DELETE FROM barang WHERE kode_barang='$kode_barang'";
    
    if (mysqli_query($conn, $query)) {
        $_SESSION['alert_type'] = 'success';
        $_SESSION['alert_message'] = 'Barang berhasil dihapus!';
    } else {
        $_SESSION['alert_type'] = 'error';
        $_SESSION['alert_message'] = 'Gagal menghapus barang: ' . mysqli_error($conn);
    }
    
    header("Location: barang.php");
    exit();
}

// Ambil data barang
$query = "SELECT * FROM barang ORDER BY kode_barang";
$result = mysqli_query($conn, $query);
$total_barang = mysqli_num_rows($result);
$total_stok = 0;
$total_tersedia = 0;
$total_habis = 0;

// Hitung statistik
while ($row = mysqli_fetch_assoc($result)) {
    $total_stok += $row['stok'];
    if ($row['status'] == 'Tersedia') {
        $total_tersedia++;
    } else {
        $total_habis++;
    }
}

// Reset pointer result
mysqli_data_seek($result, 0);

// Generate kode otomatis untuk form tambah
$auto_kode = generateKodeBarang($conn);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Barang - Inventaris LAPAS</title>
    
    <!-- Bootstrap 5 & Font Awesome -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    
    <!-- DataTables -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css">
    
    <style>
        /* Additional styles for barang page */
        .stats-card {
            background: white;
            border-radius: 16px;
            padding: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            border: 1px solid #e2e8f0;
            transition: all 0.3s ease;
        }
        
        .stats-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.1);
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
        
        .auto-kode-badge {
            background: #e8f0fe;
            border: 1px solid #c2d6f0;
            color: #1e4663;
            font-family: monospace;
            font-weight: bold;
            font-size: 16px;
            padding: 8px 12px;
            border-radius: 8px;
            display: inline-block;
        }
        
        .refresh-kode {
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .refresh-kode:hover {
            transform: rotate(180deg);
            color: var(--lapas-primary);
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
            padding: 0;
        }
        
        .modal-xl {
            max-width: 800px;
        }
        
        .table-responsive {
            border-radius: 12px;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .fade-in {
            animation: fadeIn 0.5s ease;
        }
        
        .badge-stok {
            font-size: 11px;
            padding: 4px 8px;
        }
        
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }
        
        .auto-badge {
            animation: pulse 1s ease;
        }
    </style>
</head>
<body>

<?php include 'header.php'; ?>

<!-- Main Content -->
<div class="container mt-4 fade-in">
    <!-- Page Title -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="mb-1" style="color: var(--lapas-dark);">
                <i class="fas fa-boxes me-2" style="color: var(--lapas-accent);"></i>Kelola Barang
            </h2>
            <p class="text-muted small">Manajemen data inventaris barang LAPAS</p>
        </div>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#tambahModal" onclick="refreshAutoKode()">
            <i class="fas fa-plus me-2"></i>Tambah Barang
        </button>
    </div>
    
    <!-- Alert Messages -->
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
                        <p class="stats-label">Total Barang</p>
                        <h3 class="stats-number"><?php echo $total_barang; ?></h3>
                    </div>
                    <div class="stats-icon bg-primary bg-opacity-10 text-primary">
                        <i class="fas fa-boxes"></i>
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
                <h5 class="mb-0"><i class="fas fa-table me-2"></i>Daftar Barang</h5>
                <div>
                    <button class="btn btn-sm btn-outline-success" id="exportExcel">
                        <i class="fas fa-file-excel me-1"></i> Export Excel
                    </button>
                </div>
            </div>
        </div>
        <div class="panel-body">
            <div class="table-responsive">
                <table class="table table-hover" id="barangTable">
                    <thead>
                        <tr>
                            <th width="5%">No</th>
                            <th width="10%">Kode</th>
                            <th width="30%">Nama Barang</th>
                            <th width="10%">Stok</th>
                            <th width="12%">Status</th>
                            <th width="20%">Keterangan</th>
                            <th width="13%">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (mysqli_num_rows($result) > 0): ?>
                            <?php $no = 1; while($row = mysqli_fetch_assoc($result)): ?>
                                <tr>
                                    <td><?php echo $no++; ?></td>
                                    <td><strong><?php echo htmlspecialchars($row['kode_barang']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($row['nama_barang']); ?></td>
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
                                            <i class="fas fa-cube me-1"></i><?php echo $row['stok']; ?> Unit
                                        </span>
                                    </td>
                                    <td>
                                        <span class="label label-<?php echo $row['status'] == 'Tersedia' ? 'success' : 'danger'; ?>">
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
                                        <div class="btn-group btn-group-sm" role="group">
                                            <!-- Tombol Verifikasi -->
                                            <?php if ($row['status'] == 'Tersedia'): ?>
                                                <a href="?verifikasi=<?php echo $row['kode_barang']; ?>&status=Habis" 
                                                   class="btn btn-warning btn-action"
                                                   onclick="return confirm('Verifikasi barang <?php echo addslashes($row['nama_barang']); ?> menjadi HABIS?')"
                                                   data-bs-toggle="tooltip" title="Verifikasi Habis">
                                                    <i class="fas fa-times-circle"></i>
                                                </a>
                                            <?php else: ?>
                                                <a href="?verifikasi=<?php echo $row['kode_barang']; ?>&status=Tersedia" 
                                                   class="btn btn-success btn-action"
                                                   onclick="return confirm('Verifikasi barang <?php echo addslashes($row['nama_barang']); ?> menjadi TERSEDIA?')"
                                                   data-bs-toggle="tooltip" title="Verifikasi Tersedia">
                                                    <i class="fas fa-check-circle"></i>
                                                </a>
                                            <?php endif; ?>
                                            
                                            <!-- Tombol Edit -->
                                            <button onclick="openEditModal(
                                                '<?php echo $row['kode_barang']; ?>',
                                                '<?php echo addslashes($row['nama_barang']); ?>',
                                                <?php echo $row['stok']; ?>,
                                                '<?php echo $row['status']; ?>',
                                                '<?php echo addslashes($row['keterangan']); ?>'
                                            )" class="btn btn-info btn-action" data-bs-toggle="tooltip" title="Edit Barang">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            
                                            <!-- Tombol Hapus -->
                                            <a href="?hapus=<?php echo $row['kode_barang']; ?>" 
                                               class="btn btn-danger btn-action"
                                               onclick="return confirm('Yakin ingin menghapus barang <?php echo addslashes($row['nama_barang']); ?>?')"
                                               data-bs-toggle="tooltip" title="Hapus Barang">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="text-center py-5">
                                    <i class="fas fa-box-open fa-3x text-muted mb-3"></i>
                                    <p class="text-muted">Belum ada data barang</p>
                                    <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#tambahModal" onclick="refreshAutoKode()">
                                        <i class="fas fa-plus me-1"></i>Tambah Barang Pertama
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

<!-- Modal Tambah Barang dengan Auto Generate Kode -->
<div class="modal fade" id="tambahModal" tabindex="-1" aria-labelledby="tambahModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" action="" id="formTambahBarang">
                <div class="modal-header">
                    <h5 class="modal-title" id="tambahModalLabel">
                        <i class="fas fa-plus-circle me-2"></i>Tambah Barang Baru
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">
                                Kode Barang 
                                <span class="text-danger">*</span>
                                <small class="text-muted">(Auto Generate)</small>
                            </label>
                            <div class="kode-input-group">
                                <input type="text" class="form-control" name="kode_barang" id="kode_barang" 
                                       maxlength="5" placeholder="Kode akan otomatis terisi" 
                                       style="font-family: monospace; font-weight: bold;">
                                <button type="button" class="refresh-btn" onclick="refreshAutoKode()" title="Generate ulang kode">
                                    <i class="fas fa-sync-alt"></i>
                                </button>
                            </div>
                            <small class="text-muted" id="kodeInfo">
                                <i class="fas fa-magic me-1"></i>Kode akan otomatis terisi: <strong id="previewKode"><?php echo $auto_kode; ?></strong>
                            </small>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Nama Barang <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="nama_barang" required maxlength="60" 
                                   placeholder="Contoh: Laptop ASUS ROG">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Stok <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" name="stok" required min="0" value="0">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Status <span class="text-danger">*</span></label>
                            <select class="form-select" name="status" required>
                                <option value="Tersedia">Tersedia</option>
                                <option value="Habis">Habis</option>
                            </select>
                        </div>
                        <div class="col-12 mb-3">
                            <label class="form-label">Keterangan</label>
                            <textarea class="form-control" name="keterangan" rows="3" 
                                      placeholder="Deskripsi barang, spesifikasi, dll..."></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" name="tambah" class="btn btn-primary" id="btnSimpan">
                        <i class="fas fa-save me-1"></i>Simpan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Edit Barang -->
<div class="modal fade" id="editModal" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" action="">
                <div class="modal-header">
                    <h5 class="modal-title" id="editModalLabel">
                        <i class="fas fa-edit me-2"></i>Edit Barang
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="kode_barang" id="edit_kode_barang">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Kode Barang</label>
                            <input type="text" class="form-control" id="edit_kode_display" disabled>
                            <small class="text-muted">Kode barang tidak dapat diubah</small>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Nama Barang <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="nama_barang" id="edit_nama_barang" required maxlength="60">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Stok <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" name="stok" id="edit_stok" required min="0">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Status <span class="text-danger">*</span></label>
                            <select class="form-select" name="status" id="edit_status" required>
                                <option value="Tersedia">Tersedia</option>
                                <option value="Habis">Habis</option>
                            </select>
                        </div>
                        <div class="col-12 mb-3">
                            <label class="form-label">Keterangan</label>
                            <textarea class="form-control" name="keterangan" id="edit_keterangan" rows="3"></textarea>
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

<!-- Scripts -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>

<script>
// Data kode barang dari PHP
var autoKode = '<?php echo $auto_kode; ?>';

$(document).ready(function() {
    // Initialize DataTable
    $('#barangTable').DataTable({
        language: {
            url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/id.json',
            search: "Cari:",
            lengthMenu: "Tampilkan _MENU_ data",
            info: "Menampilkan _START_ sampai _END_ dari _TOTAL_ data",
            paginate: {
                first: "Pertama",
                last: "Terakhir",
                next: "Selanjutnya",
                previous: "Sebelumnya"
            }
        },
        order: [[1, 'asc']],
        pageLength: 10,
        columnDefs: [
            { orderable: false, targets: [6] } // Disable sorting on action column
        ]
    });
    
    // Initialize tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
    
    // Export to Excel
    $('#exportExcel').click(function() {
        var table = document.getElementById('barangTable');
        var wb = XLSX.utils.table_to_book(table, {sheet: "Data Barang", raw: true});
        XLSX.writeFile(wb, "Data_Barang_LAPAS.xlsx");
    });
    
    // Set auto kode saat modal dibuka
    $('#tambahModal').on('show.bs.modal', function() {
        refreshAutoKode();
    });
    
    // Reset form saat modal ditutup
    $('#tambahModal').on('hidden.bs.modal', function() {
        $('#formTambahBarang')[0].reset();
        refreshAutoKode();
    });
    
    // Validasi sebelum submit
    $('#formTambahBarang').on('submit', function(e) {
        var kode = $('#kode_barang').val();
        if (!kode) {
            e.preventDefault();
            alert('Kode barang harus diisi!');
            return false;
        }
        
        // Validasi format kode
        if (!/^B[0-9]{3}$/.test(kode)) {
            e.preventDefault();
            alert('Format kode harus BXXX (contoh: B001, B025, B100)');
            return false;
        }
        
        return true;
    });
});

// Function to refresh auto kode
function refreshAutoKode() {
    $.ajax({
        url: 'get_kode_barang.php',
        type: 'GET',
        dataType: 'json',
        success: function(data) {
            if (data.kode_barang) {
                $('#kode_barang').val(data.kode_barang);
                $('#previewKode').html(data.kode_barang);
                $('#kodeInfo').addClass('auto-badge');
                setTimeout(function() {
                    $('#kodeInfo').removeClass('auto-badge');
                }, 1000);
            } else {
                // Fallback ke kode dari PHP
                $('#kode_barang').val(autoKode);
                $('#previewKode').html(autoKode);
            }
        },
        error: function() {
            // Gunakan kode dari PHP jika AJAX gagal
            $('#kode_barang').val(autoKode);
            $('#previewKode').html(autoKode);
        }
    });
}

// Function to open edit modal
function openEditModal(kode, nama, stok, status, keterangan) {
    document.getElementById('edit_kode_barang').value = kode;
    document.getElementById('edit_kode_display').value = kode;
    document.getElementById('edit_nama_barang').value = nama;
    document.getElementById('edit_stok').value = stok;
    document.getElementById('edit_status').value = status;
    document.getElementById('edit_keterangan').value = keterangan;
    
    var editModal = new bootstrap.Modal(document.getElementById('editModal'));
    editModal.show();
}

// Auto-hide alerts after 5 seconds
setTimeout(function() {
    $('.alert').fadeOut('slow');
}, 5000);
</script>

</body>
</html>