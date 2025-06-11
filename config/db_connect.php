<?php
/**
 * File ini berfungsi sebagai pusat koneksi ke database.
 * Semua file lain yang butuh akses database akan menyertakan file ini.
 */

// Mulai session di setiap halaman yang menggunakan file ini.
// Ini akan kita butuhkan untuk menangani data login pengguna nanti.
// Cek apakah sesi sudah aktif sebelum memulainya
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// ---------------------------------------------
// --- KONFIGURASI KONEKSI DATABASE ---
// ---------------------------------------------

// Sesuaikan detail ini dengan pengaturan XAMPP Anda.
$host = 'localhost';      // Server database, biasanya 'localhost'
$db_user = 'root';        // Username database default XAMPP
$db_pass = '';            // Password database default XAMPP (biasanya kosong)

// PENTING: Sesuaikan nama database dengan yang baru kita buat.
$db_name = 'crm_retail_app';  

// ---------------------------------------------
// --- MEMBUAT KONEKSI ---
// ---------------------------------------------

// Perintah utama untuk mencoba menghubungkan PHP ke server MySQL
$conn = mysqli_connect($host, $db_user, $db_pass, $db_name);


// ---------------------------------------------
// --- PENGECEKAN KONEKSI ---
// ---------------------------------------------

// Ini adalah penjaga keamanan.
// Jika koneksi gagal, hentikan eksekusi seluruh skrip dan tampilkan pesan error yang jelas.
if (!$conn) {
    die("FATAL ERROR: Koneksi ke database gagal. Pesan error: " . mysqli_connect_error());
}

?>