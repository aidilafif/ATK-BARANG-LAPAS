<?php
// laporan.php
// Halaman untuk mencetak laporan pengajuan yang sudah disetujui

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

// ==================== FUNGSI UNTUK MENCARI PATH LOGO ====================
function cariPathLogo() {
    // Daftar kemungkinan path logo
    $possible_paths = [
        '../assets/image/lapas.png',
        '../../assets/image/lapas.png',
        'assets/image/lapas.png',
        '../assets/images/lapas.png',
        'images/lapas.png',
        '../images/lapas.png',
        '../../../assets/image/lapas.png'
    ];
    
    foreach ($possible_paths as $path) {
        if (file_exists($path)) {
            return $path;
        }
    }
    return false;
}

// Cari path logo
$logo_path = cariPathLogo();
$logo_exists = ($logo_path !== false);

// ==================== AMBIL DATA LAPORAN DENGAN FILTER TANGGAL ====================
// Ambil parameter filter tanggal
$tanggal_mulai = isset($_GET['tanggal_mulai']) && !empty($_GET['tanggal_mulai']) ? mysqli_real_escape_string($conn, $_GET['tanggal_mulai']) : '';
$tanggal_selesai = isset($_GET['tanggal_selesai']) && !empty($_GET['tanggal_selesai']) ? mysqli_real_escape_string($conn, $_GET['tanggal_selesai']) : '';
$cetak = isset($_GET['cetak']) ? $_GET['cetak'] : '';

// Bangun query untuk laporan pengajuan yang DISETUJUI
$where_condition = "status = 'Disetujui'";

if ($tanggal_mulai && $tanggal_selesai) {
    $where_condition .= " AND DATE(tanggal_pengajuan) BETWEEN '$tanggal_mulai' AND '$tanggal_selesai'";
}

// Query untuk pengajuan barang
$query_barang = "SELECT * FROM pengajuan WHERE $where_condition ORDER BY tanggal_pengajuan DESC";
$result_barang = mysqli_query($conn, $query_barang);

// Query untuk pengajuan ATK
$query_atk = "SELECT * FROM pengajuan_atk WHERE $where_condition ORDER BY tanggal_pengajuan DESC";
$result_atk = mysqli_query($conn, $query_atk);

// Hitung total
$total_barang = $result_barang ? mysqli_num_rows($result_barang) : 0;
$total_atk = $result_atk ? mysqli_num_rows($result_atk) : 0;
$total_semua = $total_barang + $total_atk;

// Hitung total jumlah barang yang diajukan
$total_jumlah_barang = 0;
if ($result_barang) {
    mysqli_data_seek($result_barang, 0);
    while ($row = mysqli_fetch_assoc($result_barang)) {
        $total_jumlah_barang += $row['jumlah'];
    }
    mysqli_data_seek($result_barang, 0);
}

$total_jumlah_atk = 0;
if ($result_atk) {
    mysqli_data_seek($result_atk, 0);
    while ($row = mysqli_fetch_assoc($result_atk)) {
        $total_jumlah_atk += $row['jumlah'];
    }
    mysqli_data_seek($result_atk, 0);
}

// Jika tombol cetak ditekan, tampilkan halaman print
if ($cetak == 'true') {
    ?>
    <!DOCTYPE html>
    <html lang="id">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Laporan Pengajuan Disetujui - LAPAS</title>
        <style>
            * {
                margin: 0;
                padding: 0;
                box-sizing: border-box;
            }
            
            body {
                font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
                background: white;
                padding: 20px;
            }
            
            .laporan-container {
                max-width: 1200px;
                margin: 0 auto;
                background: white;
            }
            
            .header-laporan {
                text-align: center;
                margin-bottom: 30px;
                padding-bottom: 20px;
                border-bottom: 2px solid #1e2a3a;
            }
            
            .logo {
                display: flex;
                align-items: center;
                justify-content: center;
                gap: 20px;
                margin-bottom: 15px;
            }
            
            .logo-img {
                width: 80px;
                height: 80px;
                object-fit: contain;
            }
            
            .logo-placeholder {
                width: 80px;
                height: 80px;
                background: linear-gradient(135deg, #1e2a3a, #2c7da0);
                border-radius: 15px;
                display: flex;
                align-items: center;
                justify-content: center;
                color: white;
            }
            
            .logo-placeholder i {
                font-size: 40px;
            }
            
            .logo h1 {
                font-size: 24px;
                color: #1e2a3a;
                margin: 0;
            }
            
            .logo p {
                font-size: 14px;
                color: #6c757d;
                margin: 5px 0 0;
            }
            
            .judul-laporan {
                font-size: 20px;
                font-weight: 600;
                margin-top: 10px;
            }
            
            .periode {
                font-size: 14px;
                color: #6c757d;
                margin-top: 5px;
            }
            
            .info-print {
                display: flex;
                justify-content: space-between;
                margin-bottom: 20px;
                font-size: 12px;
                color: #6c757d;
                padding: 10px;
                background: #f8f9fa;
                border-radius: 5px;
            }
            
            .table {
                width: 100%;
                border-collapse: collapse;
                margin-bottom: 20px;
                font-size: 12px;
            }
            
            .table th {
                background: #1e2a3a;
                color: white;
                padding: 10px;
                text-align: left;
            }
            
            .table td {
                padding: 8px 10px;
                border-bottom: 1px solid #dee2e6;
            }
            
            .table tr:hover {
                background: #f8f9fa;
            }
            
            .sub-section {
                margin-top: 30px;
                margin-bottom: 15px;
                padding-bottom: 10px;
                border-bottom: 2px solid #dee2e6;
            }
            
            .sub-section h3 {
                font-size: 18px;
                color: #1e2a3a;
            }
            
            .footer-laporan {
                margin-top: 30px;
                padding-top: 20px;
                border-top: 1px solid #dee2e6;
                text-align: center;
                font-size: 12px;
                color: #6c757d;
            }
            
            .ringkasan {
                background: #f8f9fa;
                padding: 15px;
                border-radius: 8px;
                margin-bottom: 20px;
                display: flex;
                justify-content: space-around;
                flex-wrap: wrap;
                gap: 15px;
            }
            
            .ringkasan-item {
                text-align: center;
            }
            
            .ringkasan-item .label {
                font-size: 12px;
                color: #6c757d;
            }
            
            .ringkasan-item .value {
                font-size: 24px;
                font-weight: 700;
                color: #1e2a3a;
            }
            
            @media print {
                body {
                    padding: 0;
                    margin: 0;
                }
                
                .no-print {
                    display: none;
                }
                
                .table th {
                    background: #1e2a3a !important;
                    color: white !important;
                    -webkit-print-color-adjust: exact;
                    print-color-adjust: exact;
                }
                
                .ringkasan {
                    background: #f8f9fa !important;
                    -webkit-print-color-adjust: exact;
                    print-color-adjust: exact;
                }
            }
        </style>
    </head>
    <body>
        <div class="laporan-container">
            <div class="header-laporan">
                <div class="logo">
                    <?php if ($logo_exists): ?>
                        <img src="<?php echo $logo_path; ?>" alt="Logo LAPAS" class="logo-img">
                    <?php else: ?>
                        <div class="logo-placeholder">
                            <i class="fas fa-prison"></i>
                        </div>
                    <?php endif; ?>
                    <div>
                        <h1>LAPAS Tanjung Pati</h1>
                        <p>Lembaga Pemasyarakatan Kelas IIB Tanjung Pati</p>
                    </div>
                </div>
                <div class="judul-laporan">
                    LAPORAN PENGAJUAN BARANG & ATK<br>
                    (STATUS DISETUJUI)
                </div>
                <div class="periode">
                    Periode: <?php echo $tanggal_mulai && $tanggal_selesai ? date('d/m/Y', strtotime($tanggal_mulai)) . ' - ' . date('d/m/Y', strtotime($tanggal_selesai)) : 'Semua Data'; ?>
                </div>
            </div>
            
            <div class="info-print">
                <span>Tanggal Cetak: <?php echo date('d/m/Y H:i:s'); ?></span>
                <span>Dicetak oleh: <?php echo htmlspecialchars($username); ?> (<?php echo ucfirst($user_level); ?>)</span>
            </div>
            
            <div class="ringkasan">
                <div class="ringkasan-item">
                    <div class="value"><?php echo $total_barang; ?></div>
                    <div class="label">Pengajuan Barang</div>
                </div>
                <div class="ringkasan-item">
                    <div class="value"><?php echo $total_atk; ?></div>
                    <div class="label">Pengajuan ATK</div>
                </div>
                <div class="ringkasan-item">
                    <div class="value"><?php echo $total_semua; ?></div>
                    <div class="label">Total Pengajuan</div>
                </div>
                <div class="ringkasan-item">
                    <div class="value"><?php echo $total_jumlah_barang; ?></div>
                    <div class="label">Total Barang Diajukan</div>
                </div>
                <div class="ringkasan-item">
                    <div class="value"><?php echo $total_jumlah_atk; ?></div>
                    <div class="label">Total ATK Diajukan</div>
                </div>
            </div>
            
            <!-- Tabel Pengajuan Barang -->
            <div class="sub-section">
                <h3>A. LAPORAN PENGAJUAN BARANG (DISETUJUI)</h3>
            </div>
            
            <table class="table">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Tanggal Pengajuan</th>
                        <th>Kode Barang</th>
                        <th>Nama Barang</th>
                        <th>Jenis</th>
                        <th>Jumlah</th>
                        <th>Ruangan</th>
                        <th>Diajukan Oleh</th>
                        <th>Tanggal Verifikasi</th>
                        <th>Diverifikasi Oleh</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result_barang && mysqli_num_rows($result_barang) > 0): ?>
                        <?php $no = 1; while($row = mysqli_fetch_assoc($result_barang)): ?>
                            <tr>
                                <td><?php echo $no++; ?></td>
                                <td><?php echo date('d/m/Y H:i', strtotime($row['tanggal_pengajuan'])); ?></td>
                                <td><?php echo htmlspecialchars($row['kode_barang']); ?></td>
                                <td><?php echo htmlspecialchars($row['nama_barang']); ?></td>
                                <td><?php echo $row['jenis_pengajuan']; ?></td>
                                <td><?php echo $row['jumlah']; ?> Unit</td>
                                <td><?php echo htmlspecialchars($row['nama_ruangan'] ?? '-'); ?></td>
                                <td><?php echo htmlspecialchars($row['diajukan_oleh']); ?></td>
                                <td><?php echo date('d/m/Y H:i', strtotime($row['tanggal_verifikasi'])); ?></td>
                                <td><?php echo htmlspecialchars($row['diverifikasi_oleh']); ?></td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="10" style="text-align: center;">Tidak ada data pengajuan barang yang disetujui</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
            
            <!-- Tabel Pengajuan ATK -->
            <div class="sub-section">
                <h3>B. LAPORAN PENGAJUAN ATK (DISETUJUI)</h3>
            </div>
            
            <table class="table">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Tanggal Pengajuan</th>
                        <th>Kode ATK</th>
                        <th>Nama ATK</th>
                        <th>Jenis</th>
                        <th>Jumlah</th>
                        <th>Satuan</th>
                        <th>Ruangan</th>
                        <th>Diajukan Oleh</th>
                        <th>Tanggal Verifikasi</th>
                        <th>Diverifikasi Oleh</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result_atk && mysqli_num_rows($result_atk) > 0): ?>
                        <?php $no = 1; while($row = mysqli_fetch_assoc($result_atk)): 
                            // Ambil satuan
                            $satuan_query = "SELECT satuan FROM atk WHERE kode_atk = '{$row['kode_atk']}'";
                            $satuan_result = mysqli_query($conn, $satuan_query);
                            $satuan = 'Pcs';
                            if ($satuan_result && mysqli_num_rows($satuan_result) > 0) {
                                $satuan_data = mysqli_fetch_assoc($satuan_result);
                                $satuan = $satuan_data['satuan'];
                            }
                        ?>
                            <tr>
                                <td><?php echo $no++; ?></td>
                                <td><?php echo date('d/m/Y H:i', strtotime($row['tanggal_pengajuan'])); ?></td>
                                <td><?php echo htmlspecialchars($row['kode_atk']); ?></td>
                                <td><?php echo htmlspecialchars($row['nama_atk']); ?></td>
                                <td><?php echo $row['jenis_pengajuan']; ?></td>
                                <td><?php echo $row['jumlah']; ?></td>
                                <td><?php echo $satuan; ?></td>
                                <td><?php echo htmlspecialchars($row['nama_ruangan'] ?? '-'); ?></td>
                                <td><?php echo htmlspecialchars($row['diajukan_oleh']); ?></td>
                                <td><?php echo date('d/m/Y H:i', strtotime($row['tanggal_verifikasi'])); ?></td>
                                <td><?php echo htmlspecialchars($row['diverifikasi_oleh']); ?></td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="11" style="text-align: center;">Tidak ada data pengajuan ATK yang disetujui</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
            
            <div class="footer-laporan">
                <p>Dokumen ini adalah laporan resmi dari sistem inventaris LAPAS Tanjung Pati</p>
                <p>Dicetak pada: <?php echo date('d/m/Y H:i:s'); ?></p>
            </div>
        </div>
        
        <script>
            // Auto print
            window.print();
        </script>
    </body>
    </html>
    <?php
    exit();
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Pengajuan - Inventaris LAPAS</title>
    
    <!-- Bootstrap 5 & Font Awesome -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    
    <!-- SweetAlert2 -->
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
        
        .filter-section {
            background: white;
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 20px;
            border: 1px solid var(--lapas-border);
        }
        
        .btn-cetak {
            background: linear-gradient(135deg, var(--lapas-primary), var(--lapas-accent));
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 10px;
            font-weight: 600;
            transition: all 0.3s;
        }
        
        .btn-cetak:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(44, 125, 160, 0.3);
            color: white;
        }
        
        .btn-reset {
            background: #6c757d;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            transition: all 0.3s;
        }
        
        .btn-reset:hover {
            background: #5a6268;
            color: white;
        }
        
        .preview-card {
            background: #f8f9fa;
            border-radius: 12px;
            padding: 15px;
            margin-top: 20px;
        }
        
        .preview-item {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #dee2e6;
        }
        
        .preview-item:last-child {
            border-bottom: none;
        }
        
        .info-logo {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .logo-preview {
            width: 60px;
            height: 60px;
            object-fit: contain;
            border-radius: 10px;
        }
        
        .logo-placeholder-preview {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, #1e2a3a, #2c7da0);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
        }
        
        .logo-placeholder-preview i {
            font-size: 30px;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .fade-in {
            animation: fadeIn 0.5s ease;
        }
    </style>
</head>
<body>

<?php include 'header.php'; ?>

<!-- Main Content -->
<div class="container mt-4 fade-in">
    <!-- Page Title -->
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h2 class="mb-1" style="color: var(--lapas-dark);">
                <i class="fas fa-print me-2" style="color: var(--lapas-accent);"></i>Laporan Pengajuan
            </h2>
            <p class="text-muted small">
                <i class="fas fa-info-circle me-1"></i>
                Cetak laporan pengajuan barang dan ATK yang telah disetujui oleh admin
            </p>
        </div>
    </div>
    
    <!-- Filter Section -->
    <div class="filter-section">
        <h5 class="mb-3"><i class="fas fa-filter me-2"></i>Filter Laporan</h5>
        <form method="GET" action="" id="formFilter" target="_blank">
            <div class="row g-3 align-items-end">
                <div class="col-md-5">
                    <label class="form-label fw-bold">Dari Tanggal</label>
                    <input type="date" name="tanggal_mulai" id="tanggal_mulai" class="form-control">
                </div>
                <div class="col-md-5">
                    <label class="form-label fw-bold">Sampai Tanggal</label>
                    <input type="date" name="tanggal_selesai" id="tanggal_selesai" class="form-control">
                </div>
                <div class="col-md-2">
                    <button type="submit" name="cetak" value="true" class="btn-cetak w-100">
                        <i class="fas fa-print me-2"></i>Cetak Laporan
                    </button>
                </div>
            </div>
            <div class="row mt-3">
                <div class="col-12">
                    <a href="laporan.php" class="btn-reset">
                        <i class="fas fa-undo-alt me-1"></i> Reset Filter
                    </a>
                </div>
            </div>
        </form>
    </div>
    
    <!-- Preview Informasi -->
    <div class="panel">
        <div class="panel-heading">
            <h5 class="mb-0"><i class="fas fa-eye me-2"></i>Preview Laporan</h5>
        </div>
        <div class="panel-body">
            <div class="info-logo">
                <?php if ($logo_exists): ?>
                    <img src="<?php echo $logo_path; ?>" alt="Logo LAPAS" class="logo-preview">
                <?php else: ?>
                    <div class="logo-placeholder-preview">
                        <i class="fas fa-prison"></i>
                    </div>
                <?php endif; ?>
                <div>
                    <h4 class="mb-0">Lapas Tanjung Pati</h4>
                    <small class="text-muted">Lembaga Pemasyarakatan  Kelas IIB Tanjung Pati</small>
                </div>
            </div>
            
            <div class="preview-card">
                <div class="preview-item">
                    <span><i class="fas fa-calendar-alt me-2"></i>Periode Laporan:</span>
                    <span id="previewPeriode" class="fw-bold">Semua Data</span>
                </div>
                <div class="preview-item">
                    <span><i class="fas fa-chart-bar me-2"></i>Status:</span>
                    <span class="fw-bold text-success"><i class="fas fa-check-circle me-1"></i>Disetujui</span>
                </div>
                <div class="preview-item">
                    <span><i class="fas fa-file-alt me-2"></i>Jenis Laporan:</span>
                    <span>Pengajuan Barang & ATK</span>
                </div>
                <div class="preview-item">
                    <span><i class="fas fa-user me-2"></i>Dicetak Oleh:</span>
                    <span><?php echo htmlspecialchars($username); ?> (<?php echo ucfirst($user_level); ?>)</span>
                </div>
            </div>
            
            <div class="alert alert-info mt-3">
                <i class="fas fa-info-circle me-2"></i>
                Laporan ini akan menampilkan semua pengajuan barang dan ATK dengan status <strong>DISETUJUI</strong> oleh admin.
                Silakan pilih rentang tanggal untuk memfilter data yang ingin dicetak.
            </div>
        </div>
    </div>
    
    <!-- Statistik Ringkas -->
    <div class="row mb-4">
        <div class="col-md-4 mb-3">
            <div class="stats-card">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <p class="stats-label">Pengajuan Disetujui (Barang)</p>
                        <h3 class="stats-number text-success"><?php 
                            $count = mysqli_query($conn, "SELECT COUNT(*) as total FROM pengajuan WHERE status = 'Disetujui'");
                            $row = mysqli_fetch_assoc($count);
                            echo $row['total'];
                        ?></h3>
                    </div>
                    <div class="stats-icon bg-success bg-opacity-10 text-success">
                        <i class="fas fa-box"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-3">
            <div class="stats-card">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <p class="stats-label">Pengajuan Disetujui (ATK)</p>
                        <h3 class="stats-number text-success"><?php 
                            $count = mysqli_query($conn, "SELECT COUNT(*) as total FROM pengajuan_atk WHERE status = 'Disetujui'");
                            $row = mysqli_fetch_assoc($count);
                            echo $row['total'];
                        ?></h3>
                    </div>
                    <div class="stats-icon bg-success bg-opacity-10 text-success">
                        <i class="fas fa-pencil-alt"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-3">
            <div class="stats-card">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <p class="stats-label">Total Pengajuan Disetujui</p>
                        <h3 class="stats-number text-success"><?php 
                            $count1 = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM pengajuan WHERE status = 'Disetujui'"));
                            $count2 = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM pengajuan_atk WHERE status = 'Disetujui'"));
                            echo $count1['total'] + $count2['total'];
                        ?></h3>
                    </div>
                    <div class="stats-icon bg-success bg-opacity-10 text-success">
                        <i class="fas fa-check-double"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>

<script>
$(document).ready(function() {
    // Preview periode
    $('#tanggal_mulai, #tanggal_selesai').on('change', function() {
        var mulai = $('#tanggal_mulai').val();
        var selesai = $('#tanggal_selesai').val();
        
        if (mulai && selesai) {
            var tanggalMulai = new Date(mulai).toLocaleDateString('id-ID');
            var tanggalSelesai = new Date(selesai).toLocaleDateString('id-ID');
            $('#previewPeriode').html(tanggalMulai + ' - ' + tanggalSelesai);
        } else {
            $('#previewPeriode').html('Semua Data');
        }
    });
    
    // Form submit
    $('#formFilter').on('submit', function(e) {
        var mulai = $('#tanggal_mulai').val();
        var selesai = $('#tanggal_selesai').val();
        
        if ((mulai && !selesai) || (!mulai && selesai)) {
            e.preventDefault();
            Swal.fire({
                icon: 'warning',
                title: 'Periode Tidak Lengkap',
                text: 'Silakan pilih tanggal mulai dan selesai secara lengkap!'
            });
            return false;
        }
        
        if (mulai && selesai && new Date(mulai) > new Date(selesai)) {
            e.preventDefault();
            Swal.fire({
                icon: 'error',
                title: 'Tanggal Tidak Valid',
                text: 'Tanggal mulai harus lebih kecil dari tanggal selesai!'
            });
            return false;
        }
        
        return true;
    });
});
</script>

</body>
</html>