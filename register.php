<?php
// 1. Sertakan file koneksi database
include 'config/db_connect.php';

// 2. Definisikan variabel untuk menyimpan pesan
$message = '';

// 3. Cek apakah form sudah di-submit menggunakan metode POST
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    // 4. Ambil data dari form dan bersihkan untuk keamanan dasar
    $full_name = mysqli_real_escape_string($conn, $_POST['full_name']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $password = $_POST['password'];
    $role = 'customer'; // Semua pendaftar baru otomatis menjadi 'customer'

    // 5. Validasi dasar (pastikan password tidak kosong)
    if (!empty($full_name) && !empty($email) && !empty($password)) {
        
        // 6. Enkripsi password sebelum disimpan! JANGAN PERNAH SIMPAN PASSWORD ASLI.
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // 7. Buat query SQL untuk memasukkan data
        $sql = "INSERT INTO users (full_name, email, password, role) VALUES ('$full_name', '$email', '$hashed_password', '$role')";

        // 8. Eksekusi query dan cek hasilnya
        if (mysqli_query($conn, $sql)) {
            $message = "Registrasi berhasil! Silakan <a href='login.php'>login</a>.";
        } else {
            // Cek apakah error karena email sudah ada
            if(mysqli_errno($conn) == 1062) { // 1062 adalah kode error untuk duplicate entry
                 $message = "Error: Email ini sudah terdaftar. Silakan gunakan email lain.";
            } else {
                 $message = "Error: Terjadi kesalahan. " . mysqli_error($conn);
            }
        }

    } else {
        $message = "Semua kolom wajib diisi!";
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrasi - Sistem Tiket</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        /* Style tambahan untuk pesan error/sukses */
        .message { padding: 10px; margin-bottom: 15px; border-radius: 5px; color: #fff; }
        .success { background-color: #28a745; }
        .error { background-color: #dc3545; }
    </style>
</head>
<body>

    <div class="form-container">
        <h2>Buat Akun Baru</h2>
        <p>Isi data di bawah untuk mendaftar</p>

        <?php 
        // 9. Tampilkan pesan jika ada
        if (!empty($message)): 
            // Cek apakah pesan mengandung kata 'berhasil' untuk menentukan style
            $message_class = (strpos($message, 'berhasil') !== false) ? 'success' : 'error';
        ?>
            <div class="message <?php echo $message_class; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <form action="register.php" method="POST">
            <div class="form-group">
                <label for="full_name">Nama Lengkap</label>
                <input type="text" id="full_name" name="full_name" placeholder="Masukkan nama lengkap" required>
            </div>
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" placeholder="Masukkan email aktif" required>
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" placeholder="Buat password Anda" required>
            </div>
            <button type="submit" class="btn">Daftar</button>
        </form>
        <div class="text-center">
            <p>Sudah punya akun? <a href="login.php">Login di sini</a></p>
        </div>
    </div>

</body>
</html>