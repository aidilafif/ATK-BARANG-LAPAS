<?php
// get_barang_info.php
session_start();
include '../assets/conn/config.php';

if (isset($_POST['kode_barang'])) {
    $kode_barang = mysqli_real_escape_string($conn, $_POST['kode_barang']);
    $query = "SELECT nama_barang, stok FROM barang WHERE kode_barang = '$kode_barang'";
    $result = mysqli_query($conn, $query);
    
    if ($result && mysqli_num_rows($result) > 0) {
        $data = mysqli_fetch_assoc($result);
        echo json_encode([
            'success' => true,
            'nama_barang' => $data['nama_barang'],
            'stok' => $data['stok']
        ]);
    } else {
        echo json_encode(['success' => false]);
    }
} else {
    echo json_encode(['success' => false]);
}
?>