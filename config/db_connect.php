<?php

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$host = 'localhost';     
$db_user = 'root'; 
$db_pass = '';
$db_name = 'crm_retail_app';  
$conn = mysqli_connect($host, $db_user, $db_pass, $db_name);

if (!$conn) {
    die("FATAL ERROR: Koneksi ke database gagal. Pesan error: " . mysqli_connect_error());
}

?>