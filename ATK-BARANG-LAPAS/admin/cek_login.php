<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['username'])) {
    header("Location: index.php");
    exit;
}
if ($_SESSION['level'] != 'admin' && $_SESSION['level'] != 'pimpinan') {
    header("Location: index.php");
    exit;
}
?>
