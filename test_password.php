<?php
// File untuk test password hash
include 'config/db_connect.php';

echo "<h3>🔧 Debug Password Login BOR</h3>";

$username = 'bor_admin';
$password_input = '123456';

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
        echo $new_hash . "<br>";
        echo "<br><strong>Coba update database dengan hash ini:</strong><br>";
        echo "UPDATE ms_users SET password = '$new_hash' WHERE username = '$username';";
    }
    
} else {
    echo "<strong style='color: red;'>❌ User '$username' tidak ditemukan di database!</strong><br>";
    echo "Jumlah hasil query: " . mysqli_num_rows($result) . "<br>";
    
    // Tampilkan semua user yang ada
    $all_users_sql = "SELECT username, full_name, role FROM ms_users";
    $all_users_result = mysqli_query($conn, $all_users_sql);
    
    echo "<br><strong>Daftar semua user di database:</strong><br>";
    while ($row = mysqli_fetch_assoc($all_users_result)) {
        echo "- " . $row['username'] . " (" . $row['full_name'] . ") - " . $row['role'] . "<br>";
    }
}

mysqli_close($conn);
?>