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

// Validasi: Pastikan ticket bisa di-dispatch
$check_sql = "SELECT id, status, ticket_code FROM tr_tickets 
              WHERE id = '$ticket_id' 
              AND status = 'On Progress - BOR'";

$check_result = mysqli_query($conn, $check_sql);

if (mysqli_num_rows($check_result) == 0) {
    header('Location: dashboard_bor.php?status=error_invalid_ticket');
    exit();
}

$ticket_data = mysqli_fetch_assoc($check_result);

// Cari user Dispatch yang tersedia
$dispatch_sql = "SELECT id FROM ms_users WHERE role = 'Dispatch' LIMIT 1";
$dispatch_result = mysqli_query($conn, $dispatch_sql);

if (mysqli_num_rows($dispatch_result) == 0) {
    // Jika tidak ada user Dispatch, ubah langsung status jadi Waiting for Dispatch
    // dengan current_owner tetap BOR untuk sementara
    $dispatch_user_id = $bor_user_id; // Sementara tetap di BOR
} else {
    $dispatch_user = mysqli_fetch_assoc($dispatch_result);
    $dispatch_user_id = $dispatch_user['id'];
}

// Mulai transaksi database
mysqli_autocommit($conn, FALSE);

try {
    // 1. Update status ticket menjadi "Waiting for Dispatch"
    $update_ticket_sql = "UPDATE tr_tickets SET 
                          status = 'Waiting for Dispatch',
                          current_owner_user_id = '$dispatch_user_id'
                          WHERE id = '$ticket_id'";
    
    if (!mysqli_query($conn, $update_ticket_sql)) {
        throw new Exception("Gagal update status ticket");
    }

    // 2. Tambahkan record ke tr_ticket_updates
    $insert_update_sql = "INSERT INTO tr_ticket_updates (ticket_id, user_id, update_type, description) 
                          VALUES ('$ticket_id', '$bor_user_id', 'Escalation', 
                                  'Ticket dikirim ke Dispatch - Memerlukan kunjungan teknisi lapangan')";
    
    if (!mysqli_query($conn, $insert_update_sql)) {
        throw new Exception("Gagal mencatat aktivitas dispatch");
    }

    // 3. Buat Work Order otomatis (opsional - bisa dikembangkan nanti)
    $wo_code = 'WO-' . date('Ymd') . '-' . strtoupper(uniqid());
    
    $insert_wo_sql = "INSERT INTO tr_work_orders (wo_code, ticket_id, created_by_dispatch_id, assigned_to_vendor_id, status) 
                      VALUES ('$wo_code', '$ticket_id', '$dispatch_user_id', '$dispatch_user_id', 'Pending')";
    
    // Ini opsional, jadi kalaupun gagal tidak masalah
    mysqli_query($conn, $insert_wo_sql);

    // Commit transaksi
    mysqli_commit($conn);
    
    // Redirect dengan pesan sukses
    header('Location: dashboard_bor.php?status=dispatched');
    exit();

} catch (Exception $e) {
    // Rollback jika ada error
    mysqli_rollback($conn);
    
    error_log("Error BOR dispatching ticket: " . $e->getMessage());
    header('Location: dashboard_bor.php?status=error_dispatch');
    exit();
}

mysqli_autocommit($conn, TRUE);
?>