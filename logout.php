<?php
// Selalu panggil session_start() di awal
session_start();

// Hapus semua variabel session
session_unset();

// Hancurkan sesi
session_destroy();

// Arahkan kembali ke halaman login
header("Location: login.php");
exit();
?>