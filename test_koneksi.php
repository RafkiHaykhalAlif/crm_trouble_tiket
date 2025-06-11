<?php
echo "Mencoba menyertakan file koneksi...<br>";

// Memanggil file jembatan kita
include 'config/db_connect.php';

echo "File koneksi berhasil disertakan.<br>";

// Cek apakah variabel $conn dari file sebelah berhasil dibuat
if ($conn) {
    echo "<h3>Selamat! Koneksi ke database `".$db_name."` berhasil.</h3>";

    // Selalu tutup koneksi setelah selesai menggunakannya
    mysqli_close($conn);
    echo "Koneksi ditutup.";
} else {
    echo "<h3>Maaf, koneksi gagal. Cek kembali file db_connect.php</h3>";
}
?>