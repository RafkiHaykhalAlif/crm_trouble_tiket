<?php
include 'config/db_connect.php';

// Cek apakah user sudah login
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Cek apakah ada parameter ticket_id
if (!isset($_GET['ticket_id']) || empty($_GET['ticket_id'])) {
    header('Location: dashboard.php?status=error');
    exit();
}

$ticket_id = (int)$_GET['ticket_id'];
$user_id = $_SESSION['user_id'];
$current_time = date('Y-m-d H:i:s');

// Validasi: Pastikan ticket masih bisa di-solve (statusnya Open atau On Progress - Customer Care)
$check_sql = "SELECT id, status, ticket_code FROM tr_tickets WHERE id = '$ticket_id' AND (status = 'Open' OR status = 'On Progress - Customer Care')";
$check_result = mysqli_query($conn, $check_sql);

if (mysqli_num_rows($check_result) == 0) {
    // Ticket tidak ditemukan atau statusnya sudah tidak bisa di-solve
    header('Location: dashboard.php?status=error_invalid_ticket');
    exit();
}

$ticket_data = mysqli_fetch_assoc($check_result);

// Mulai transaksi database untuk memastikan data konsisten
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

    // 2. Tambahkan record ke tr_ticket_updates untuk mencatat aktivitas ini
    $insert_update_sql = "INSERT INTO tr_ticket_updates (ticket_id, user_id, update_type, description) 
                          VALUES ('$ticket_id', '$user_id', 'Status Change', 'Ticket diselesaikan oleh Customer Care')";
    
    if (!mysqli_query($conn, $insert_update_sql)) {
        throw new Exception("Gagal mencatat aktivitas ticket");
    }

    // Jika semua berhasil, commit transaksi
    mysqli_commit($conn);
    
    // Redirect ke dashboard dengan pesan sukses
    header('Location: dashboard.php?status=solved');
    exit();

} catch (Exception $e) {
    // Jika ada error, rollback semua perubahan
    mysqli_rollback($conn);
    
    // Log error untuk debugging (opsional)
    error_log("Error solving ticket: " . $e->getMessage());
    
    // Redirect dengan pesan error
    header('Location: dashboard.php?status=error_solve');
    exit();
}

// Kembalikan autocommit ke true
mysqli_autocommit($conn, TRUE);
?>