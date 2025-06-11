<?php
include 'config/db_connect.php';

// Cek apakah user sudah login dan adalah BOR
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'BOR') {
    header('Location: login.php');
    exit();
}

// Cek parameter ticket_id
if (!isset($_GET['ticket_id']) || empty($_GET['ticket_id'])) {
    header('Location: dashboard_bor.php?status=error');
    exit();
}

$ticket_id = (int)$_GET['ticket_id'];
$bor_user_id = $_SESSION['user_id'];
$current_time = date('Y-m-d H:i:s');

// Validasi: Pastikan ticket bisa di-resolve oleh BOR
$check_sql = "SELECT id, status, ticket_code FROM tr_tickets 
              WHERE id = '$ticket_id' 
              AND status = 'On Progress - BOR'";

$check_result = mysqli_query($conn, $check_sql);

if (mysqli_num_rows($check_result) == 0) {
    header('Location: dashboard_bor.php?status=error_invalid_ticket');
    exit();
}

$ticket_data = mysqli_fetch_assoc($check_result);

// Mulai transaksi database
mysqli_autocommit($conn, FALSE);

try {
    // 1. Update status ticket menjadi "Closed - Solved"
    $update_ticket_sql = "UPDATE tr_tickets SET 
                          status = 'Closed - Solved',
                          closed_at = '$current_time'
                          WHERE id = '$ticket_id'";
    
    if (!mysqli_query($conn, $update_ticket_sql)) {
        throw new Exception("Gagal update status ticket");
    }

    // 2. Tambahkan record ke tr_ticket_updates
    $insert_update_sql = "INSERT INTO tr_ticket_updates (ticket_id, user_id, update_type, description) 
                          VALUES ('$ticket_id', '$bor_user_id', 'Status Change', 
                                  'Ticket diselesaikan oleh BOR - Masalah berhasil diperbaiki secara remote')";
    
    if (!mysqli_query($conn, $insert_update_sql)) {
        throw new Exception("Gagal mencatat aktivitas ticket");
    }

    // Commit transaksi
    mysqli_commit($conn);
    
    // Redirect dengan pesan sukses
    header('Location: dashboard_bor.php?status=resolved');
    exit();

} catch (Exception $e) {
    // Rollback jika ada error
    mysqli_rollback($conn);
    
    error_log("Error BOR resolving ticket: " . $e->getMessage());
    header('Location: dashboard_bor.php?status=error_resolve');
    exit();
}

mysqli_autocommit($conn, TRUE);
?>