<?php
// File untuk debug password Admin IKR
include 'config/db_connect.php';

echo "<h3>🔧 Debug Password Login Admin IKR</h3>";

$username = 'admin_ikr';
$password_input = '123456'; // Password yang mau kita test

// Ambil data dari database
$sql = "SELECT id, username, password, full_name, role FROM ms_users WHERE username = '$username'";
$result = mysqli_query($conn, $sql);

if (mysqli_num_rows($result) == 1) {
    $user = mysqli_fetch_assoc($result);
    
    echo "<strong>✅ User ditemukan di database:</strong><br>";
    echo "ID: " . $user['id'] . "<br>";
    echo "Username: " . $user['username'] . "<br>";
    echo "Full Name: " . $user['full_name'] . "<br>";
    echo "Role: " . $user['role'] . "<br>";
    echo "Hash di DB: " . $user['password'] . "<br><br>";
    
    // Test password verification
    if (password_verify($password_input, $user['password'])) {
        echo "<strong style='color: green;'>✅ Password COCOK! Login seharusnya berhasil.</strong><br>";
        
        // Simulasi session seperti di login.php
        echo "<br><strong>Session yang akan dibuat:</strong><br>";
        echo "user_id: " . $user['id'] . "<br>";
        echo "user_username: " . $user['username'] . "<br>";
        echo "user_full_name: " . $user['full_name'] . "<br>";
        echo "user_role: " . $user['role'] . "<br>";
        
    } else {
        echo "<strong style='color: red;'>❌ Password TIDAK COCOK!</strong><br>";
        echo "Password input: '$password_input'<br>";
        echo "Hash di database: " . $user['password'] . "<br>";
        
        // Bikin hash baru untuk perbandingan
        $new_hash = password_hash($password_input, PASSWORD_DEFAULT);
        echo "<br><strong>Hash baru untuk '$password_input':</strong><br>";
        echo "<textarea rows='3' style='width: 100%; font-family: monospace;' readonly>$new_hash</textarea><br>";
        
        echo "<br><strong>Query untuk update database:</strong><br>";
        echo "<textarea rows='2' style='width: 100%; font-family: monospace;' readonly>";
        echo "UPDATE ms_users SET password = '$new_hash' WHERE username = '$username';";
        echo "</textarea>";
    }
    
} else {
    echo "<strong style='color: red;'>❌ User '$username' tidak ditemukan di database!</strong><br>";
    echo "Jumlah hasil query: " . mysqli_num_rows($result) . "<br>";
    
    // Tampilkan semua user yang ada
    $all_users_sql = "SELECT username, full_name, role FROM ms_users ORDER BY role, username";
    $all_users_result = mysqli_query($conn, $all_users_sql);
    
    echo "<br><strong>📋 Daftar semua user di database:</strong><br>";
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr style='background-color: #f0f0f0;'><th>Username</th><th>Full Name</th><th>Role</th></tr>";
    while ($row = mysqli_fetch_assoc($all_users_result)) {
        echo "<tr>";
        echo "<td>" . $row['username'] . "</td>";
        echo "<td>" . $row['full_name'] . "</td>";
        echo "<td>" . $row['role'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Kasih script untuk insert user baru kalau belum ada
    $new_hash = password_hash($password_input, PASSWORD_DEFAULT);
    echo "<br><br><strong>🔧 Script untuk insert user Admin IKR baru:</strong><br>";
    echo "<textarea rows='4' style='width: 100%; font-family: monospace;' readonly>";
    echo "INSERT INTO ms_users (username, password, full_name, role) VALUES\n";
    echo "('admin_ikr', '$new_hash', 'Admin Back Office IKR', 'Admin IKR');";
    echo "</textarea>";
}

echo "<br><br><strong>💡 Tips:</strong><br>";
echo "1. Pastikan role 'Admin IKR' sudah ditambahkan ke enum di tabel ms_users<br>";
echo "2. Kalau mau ganti password semua user ke '123456', bisa pakai script buat_hash.php<br>";
echo "3. Always use password_hash() untuk enkripsi password!<br>";

mysqli_close($conn);
?>