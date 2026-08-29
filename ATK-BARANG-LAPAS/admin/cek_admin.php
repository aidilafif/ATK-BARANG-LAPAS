<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


// Cek sudah login
if (!isset($_SESSION['username'])) {
    header("Location: ../index.php");
    exit;
}

// Cek apakah level admin
if ($_SESSION['level'] != 'admin') {
    header("Location: ../hasil.php"); // Arahkan ke hasil jika bukan admin
    exit;
}
?>
