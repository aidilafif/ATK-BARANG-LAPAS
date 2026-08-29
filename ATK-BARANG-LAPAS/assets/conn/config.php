<?php
$host = "localhost"; // sesuaikan dengan host Anda
$username = "root"; // sesuaikan dengan username database Anda
$password = ""; // sesuaikan dengan password database Anda
$database = "lapaspyk"; // sesuaikan dengan nama database Anda

// Buat koneksi
$conn = mysqli_connect($host, $username, $password, $database);

// Cek koneksi
if (!$conn) {
    die("Koneksi gagal: " . mysqli_connect_error());
} else {
    // echo "Koneksi berhasil!";
}
?>
