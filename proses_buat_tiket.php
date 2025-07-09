<?php
include 'config/db_connect.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    $fullName = mysqli_real_escape_string($conn, $_POST['full_name']);
    $provinsi = mysqli_real_escape_string($conn, $_POST['provinsi']);
    $kota = mysqli_real_escape_string($conn, $_POST['kota']);
    $address = mysqli_real_escape_string($conn, $_POST['address']);
    $phoneNumber = mysqli_real_escape_string($conn, $_POST['phone_number']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    
    $jenis_tiket = mysqli_real_escape_string($conn, $_POST['jenis_tiket']);
    $jenisGangguan = mysqli_real_escape_string($conn, $_POST['jenis_gangguan']);
    $deskripsiGangguan = mysqli_real_escape_string($conn, $_POST['deskripsi_gangguan']);
    $createdByUserId = $_SESSION['user_id'];
    
    $complain_channel = mysqli_real_escape_string($conn, $_POST['complain_channel']);

    $customerIdNumber = 'CUST-' . time();

    $check_sql = "SELECT id FROM ms_customers WHERE email = '$email' OR phone_number = '$phoneNumber'";
    $check_result = mysqli_query($conn, $check_sql);
    
    if (mysqli_num_rows($check_result) > 0) {
        $existing_customer = mysqli_fetch_assoc($check_result);
        $last_customer_id = $existing_customer['id'];

        $sql_customer = "UPDATE ms_customers SET 
                         full_name = '$fullName', 
                         address = '$address', 
                         provinsi = '$provinsi', 
                         kota = '$kota' 
                         WHERE id = '$last_customer_id'";
        mysqli_query($conn, $sql_customer);
    } else {
        $sql_customer = "INSERT INTO ms_customers (customer_id_number, full_name, address, phone_number, email, provinsi, kota) 
                         VALUES ('$customerIdNumber', '$fullName', '$address', '$phoneNumber', '$email', '$provinsi', '$kota')";
        mysqli_query($conn, $sql_customer);
        $last_customer_id = mysqli_insert_id($conn);
    }

    if ($last_customer_id) {
        $ticketCode = 'TICKET-' . date('Ymd') . '-' . strtoupper(uniqid());

        $sql_ticket = "INSERT INTO tr_tickets 
            (ticket_code, customer_id, title, description, created_by_user_id, current_owner_user_id, complain_channel, jenis_tiket)
            VALUES 
            ('$ticketCode', '$last_customer_id', '$jenisGangguan', '$deskripsiGangguan', '$createdByUserId', '$createdByUserId', '$complain_channel', '$jenis_tiket')";

        if (mysqli_query($conn, $sql_ticket)) {
            header("Location: dashboard.php?status=sukses");
            exit();
        } else {
            echo "Error saat membuat tiket: " . mysqli_error($conn);
        }
    } else {
        echo "Error: Gagal mendapatkan ID Pelanggan.";
    }

} else {
    header("Location: dashboard.php");
    exit();
}
?>