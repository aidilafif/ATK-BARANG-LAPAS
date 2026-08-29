<?php
// get_kode_atk.php
session_start();
include '../assets/conn/config.php';

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

$kode_baru = generateKodeATK($conn);
echo json_encode(['kode_atk' => $kode_baru]);
?>