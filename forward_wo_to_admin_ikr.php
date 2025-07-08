<?php
include 'config/db_connect.php';

// Cek apakah user sudah login dan adalah Dispatch
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'Dispatch') {
    header('Location: login.php');
    exit();
}

// Cek parameter wo_id
if (!isset($_GET['wo_id']) || empty($_GET['wo_id'])) {
    header('Location: dashboard_dispatch.php?status=error_missing_id');
    exit();
}

$wo_id = (int)$_GET['wo_id'];
$dispatch_user_id = $_SESSION['user_id'];
$current_time = date('Y-m-d H:i:s');

// Validasi: Pastikan WO exists dan statusnya "Sent to Dispatch"
$check_sql = "SELECT 
    wo.id, 
    wo.wo_code, 
    wo.ticket_id, 
    wo.status,
    t.ticket_code,
    c.full_name as customer_name
FROM tr_work_orders wo
JOIN tr_tickets t ON wo.ticket_id = t.id
JOIN ms_customers c ON t.customer_id = c.id
WHERE wo.id = '$wo_id' AND wo.status = 'Sent to Dispatch'";

$check_result = mysqli_query($conn, $check_sql);

if (mysqli_num_rows($check_result) == 0) {
    header('Location: dashboard_dispatch.php?status=error_invalid_wo');
    exit();
}

$wo_data = mysqli_fetch_assoc($check_result);

// Mulai transaksi database
mysqli_autocommit($conn, FALSE);

try {
    // 1. Update Work Order status ke "Received by Admin IKR"
    $update_wo_sql = "UPDATE tr_work_orders SET 
                      status = 'Received by Admin IKR',
                      forwarded_to_admin_at = '$current_time'
                      WHERE id = '$wo_id'";
    
    if (!mysqli_query($conn, $update_wo_sql)) {
        throw new Exception("Gagal update Work Order status: " . mysqli_error($conn));
    }

    // 2. Log activity
    $activity_desc = "Work Order telah diteruskan oleh Dispatch ke Admin Back Office IKR untuk penjadwalan dan assignment teknisi.";
    
    $insert_update_sql = "INSERT INTO tr_ticket_updates (ticket_id, user_id, update_type, description) 
                          VALUES ('{$wo_data['ticket_id']}', '$dispatch_user_id', 'Status Change', '$activity_desc')";
    
    if (!mysqli_query($conn, $insert_update_sql)) {
        throw new Exception("Gagal mencatat aktivitas forward: " . mysqli_error($conn));
    }

    // Commit transaksi
    mysqli_commit($conn);
    
    // Redirect dengan pesan sukses
    header('Location: dashboard_dispatch.php?status=forwarded_to_admin');
    exit();

} catch (Exception $e) {
    // Rollback jika ada error
    mysqli_rollback($conn);
    
    // Log error untuk debugging
    error_log("Error forwarding WO to Admin IKR: " . $e->getMessage());
    
    // Redirect dengan pesan error
    header('Location: dashboard_dispatch.php?status=error_forward');
    exit();
}

// Kembalikan autocommit ke true
mysqli_autocommit($conn, TRUE);
?>