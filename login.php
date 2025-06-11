<?php
// 1. Panggil jembatan koneksi ke database
include 'config/db_connect.php';

$error_message = '';

// 2. Cek apakah pengguna menekan tombol login (form di-submit)
if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    // 3. Ambil data yang diketik pengguna dari form
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = $_POST['password'];

    // 4. Buat query untuk mencari pengguna dengan username yang cocok
    $sql = "SELECT id, username, password, full_name, role FROM ms_users WHERE username = '$username'";
    $result = mysqli_query($conn, $sql);

    // 5. Cek apakah pengguna ditemukan (harus ada 1 baris hasil)
    if (mysqli_num_rows($result) == 1) {
        
        // Ambil data pengguna dari hasil query
        $user = mysqli_fetch_assoc($result);

        // 6. Verifikasi password yang diketik dengan hash di database
        if (password_verify($password, $user['password'])) {
            
            // 7. Jika password cocok, simpan data ke session
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_username'] = $user['username'];
            $_SESSION['user_full_name'] = $user['full_name'];
            $_SESSION['user_role'] = $user['role'];

            // 8. Arahkan pengguna ke halaman dashboard
            header("Location: dashboard.php");
            exit();

        } else {
            // Jika password tidak cocok
            $error_message = "Username atau password salah!";
        }
    } else {
        // Jika username tidak ditemukan
        $error_message = "Username atau password salah!";
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - CRM Ticketing</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="form-container">
        <h2>Sistem Ticketing CRM</h2>
        <p>Silakan login untuk melanjutkan</p>

        <?php if (!empty($error_message)): ?>
            <p style="color: red; background-color: #ffdddd; padding: 10px; border-radius: 5px;"><?php echo $error_message; ?></p>
        <?php endif; ?>

        <form action="login.php" method="POST">
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" required>
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
            </div>
            <button type="submit" class="btn">Login</button>
        </form>
    </div>
</body>
</html>