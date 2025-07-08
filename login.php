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
    <title>Login - PT MORA TELEMATIKA INDONESIA TBK</title>
    <link rel="icon" type="image/png" href="https://moratelindo.co.id/assets/images/favicon.png">
    <style>
        body {
            background: linear-gradient(135deg, #0d47a1 0%, #1976d2 100%);
            min-height: 100vh;
            margin: 0;
            font-family: 'Segoe UI', Arial, sans-serif;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-wrapper {
            background: #fff;
            border-radius: 18px;
            box-shadow: 0 8px 32px rgba(44,62,80,0.18);
            padding: 42px 38px 32px 38px;
            width: 100%;
            max-width: 400px;
            text-align: center;
        }
        .company-logo {
            width: 90px;
            margin-bottom: 16px;
        }
        .company-name {
            font-size: 1.25rem;
            font-weight: bold;
            color: #0d47a1;
            margin-bottom: 4px;
            letter-spacing: 1px;
        }
        .system-title {
            font-size: 1.05rem;
            color: #1976d2;
            margin-bottom: 18px;
            font-weight: 500;
        }
        .form-group {
            text-align: left;
            margin-bottom: 18px;
        }
        label {
            font-size: 0.98rem;
            color: #0d47a1;
            font-weight: 500;
            margin-bottom: 6px;
            display: block;
        }
        input[type="text"], input[type="password"] {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #b0bec5;
            border-radius: 7px;
            font-size: 1rem;
            background: #f7fafd;
            transition: border 0.2s;
        }
        input[type="text"]:focus, input[type="password"]:focus {
            border: 1.5px solid #1976d2;
            outline: none;
            background: #fff;
        }
        .btn {
            width: 100%;
            padding: 12px 0;
            background: linear-gradient(90deg, #1976d2 60%, #0d47a1 100%);
            color: #fff;
            font-size: 1.08rem;
            font-weight: bold;
            border: none;
            border-radius: 7px;
            cursor: pointer;
            margin-top: 8px;
            box-shadow: 0 2px 8px rgba(25, 118, 210, 0.08);
            transition: background 0.2s;
        }
        .btn:hover {
            background: linear-gradient(90deg, #0d47a1 60%, #1976d2 100%);
        }
        .login-footer {
            margin-top: 28px;
            font-size: 0.93rem;
            color: #789;
        }
        .error-message {
            color: #b71c1c;
            background: #ffebee;
            border: 1px solid #ffcdd2;
            padding: 11px 0;
            border-radius: 7px;
            margin-bottom: 18px;
            font-size: 1rem;
        }
        @media (max-width: 500px) {
            .login-wrapper { padding: 24px 8px 20px 8px; }
        }
    </style>
</head>
<body>
    <div class="login-wrapper">
        <img src="https://www.idn.id/wp-content/uploads/2024/07/MORATELINDO.png" alt="Moratelindo Logo" class="company-logo">
        <div class="company-name">PT MORA TELEMATIKA INDONESIA TBK</div>
        <div class="system-title">CRM Trouble Ticketing System</div>

        <?php if (!empty($error_message)): ?>
            <div class="error-message"><?php echo $error_message; ?></div>
        <?php endif; ?>

        <form action="login.php" method="POST" autocomplete="off">
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" placeholder="Masukkan username Anda" required autofocus>
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" placeholder="Masukkan password" required>
            </div>
            <button type="submit" class="btn">Login</button>
        </form>
        <div class="login-footer">
            &copy; <?php echo date('Y'); ?> PT Mora Telematika Indonesia Tbk. All rights reserved.
        </div>
    </div>
</body>
</html>