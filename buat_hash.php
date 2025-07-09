<?php

$password = '123456';
$hash = password_hash($password, PASSWORD_DEFAULT);

echo "<h3>Password Hash Generator</h3>";
echo "<p><strong>Password:</strong> $password</p>";
echo "<p><strong>Hash:</strong></p>";
echo "<textarea rows='3' style='width: 100%; font-family: monospace;' readonly>$hash</textarea>";

echo "<br><br><h4>Copy-paste query ini ke phpMyAdmin:</h4>";
echo "<textarea rows='5' style='width: 100%; font-family: monospace;' readonly>";
echo "-- Update password untuk semua user Dispatch dan Vendor IKR\n";
echo "UPDATE ms_users SET password = '$hash' WHERE username = 'dispatch_admin';\n";
echo "UPDATE ms_users SET password = '$hash' WHERE username = 'dispatch_andi';\n";
echo "UPDATE ms_users SET password = '$hash' WHERE username = 'dispatch_maya';\n";
echo "UPDATE ms_users SET password = '$hash' WHERE username = 'ikr_budi';\n";
echo "UPDATE ms_users SET password = '$hash' WHERE username = 'ikr_sari';\n";
echo "UPDATE ms_users SET password = '$hash' WHERE username = 'ikr_rudi';\n";
echo "UPDATE ms_users SET password = '$hash' WHERE username = 'ikr_lina';";
echo "</textarea>";

echo "<p><em>Jalankan query di atas di phpMyAdmin, terus coba login lagi pake password: <strong>123456</strong></em></p>";
?>