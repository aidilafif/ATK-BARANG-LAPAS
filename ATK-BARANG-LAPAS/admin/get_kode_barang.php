<?php
// get_kode_barang.php
// File untuk mengambil kode barang terbaru via AJAX

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include '../assets/conn/config.php';

// Fungsi untuk generate kode barang otomatis
function generateKodeBarang($conn) {
    $query = "SELECT kode_barang FROM barang ORDER BY kode_barang DESC LIMIT 1";
    $result = mysqli_query($conn, $query);
    
    if (mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        $last_kode = $row['kode_barang'];
        $angka = (int) substr($last_kode, 1);
        $angka_baru = $angka + 1;
        $kode_baru = 'B' . str_pad($angka_baru, 3, '0', STR_PAD_LEFT);
        return $kode_baru;
    } else {
        return 'B001';
    }
}

// Return JSON response
header('Content-Type: application/json');
echo json_encode([
    'kode_barang' => generateKodeBarang($conn),
    'success' => true
]);
?>