<?php
// get_kode_ruangan.php
// Endpoint untuk mendapatkan kode ruangan otomatis (mengisi kode yang kosong)

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include '../assets/conn/config.php';

header('Content-Type: application/json');

// Fungsi untuk mengekstrak angka dari kode ruangan
function extractNumber($kode) {
    $angka = (int) preg_replace('/[^0-9]/', '', $kode);
    return $angka;
}

// Fungsi untuk mencari kode yang kosong dalam sequence
function findMissingKode($conn) {
    $query = "SELECT kode_ruangan FROM ruangan ORDER BY kode_ruangan";
    $result = mysqli_query($conn, $query);
    
    $existing_numbers = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $angka = extractNumber($row['kode_ruangan']);
        if ($angka > 0) {
            $existing_numbers[] = $angka;
        }
    }
    
    // Jika tidak ada data, mulai dari 1
    if (empty($existing_numbers)) {
        return 1;
    }
    
    // Cari angka terkecil yang hilang
    sort($existing_numbers);
    $expected = 1;
    foreach ($existing_numbers as $num) {
        if ($num > $expected) {
            return $expected;
        }
        $expected = $num + 1;
    }
    
    // Jika semua angka berurutan, return angka selanjutnya
    return $expected;
}

// Generate kode dengan format RXXX (3 digit)
function formatKode($angka) {
    return 'R' . str_pad($angka, 3, '0', STR_PAD_LEFT);
}

// Cari kode yang kosong
$missing_number = findMissingKode($conn);
$new_kode = formatKode($missing_number);

echo json_encode([
    'kode_ruangan' => $new_kode, 
    'mode' => 'fill_gap', 
    'nomor_urut' => $missing_number,
    'status' => 'success'
]);

mysqli_close($conn);
?>