<?php
include 'config/db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'BOR') {
    header('Location: login.php');
    exit();
}

if (!isset($_GET['ticket_id']) || empty($_GET['ticket_id'])) {
    header('Location: dashboard_bor.php?status=error');
    exit();
}

$ticket_id = (int)$_GET['ticket_id'];
$bor_user_id = $_SESSION['user_id'];

$check_sql = "SELECT id, status, ticket_code FROM tr_tickets 
              WHERE id = '$ticket_id' 
              AND status = 'On Progress - BOR'";

$check_result = mysqli_query($conn, $check_sql);

if (mysqli_num_rows($check_result) == 0) {
    header('Location: dashboard_bor.php?status=error_invalid_ticket');
    exit();
}

$ticket_data = mysqli_fetch_assoc($check_result);

$dispatch_sql = "SELECT id FROM ms_users WHERE role = 'Dispatch' LIMIT 1";
$dispatch_result = mysqli_query($conn, $dispatch_sql);

if (mysqli_num_rows($dispatch_result) == 0) {
    header('Location: dashboard_bor.php?status=error_no_dispatch');
    exit();
}

$dispatch_user = mysqli_fetch_assoc($dispatch_result);
$dispatch_user_id = $dispatch_user['id'];

$vendor_sql = "SELECT id FROM ms_users WHERE role = 'Vendor IKR' LIMIT 1";
$vendor_result = mysqli_query($conn, $vendor_sql);

if (mysqli_num_rows($vendor_result) == 0) {
    $vendor_id = $dispatch_user_id;
} else {
    $vendor_user = mysqli_fetch_assoc($vendor_result);
    $vendor_id = $vendor_user['id'];
}

mysqli_autocommit($conn, FALSE);

try {
    $update_ticket_sql = "UPDATE tr_tickets SET 
                          status = 'Waiting for Dispatch',
                          current_owner_user_id = '$dispatch_user_id'
                          WHERE id = '$ticket_id'";
    
    if (!mysqli_query($conn, $update_ticket_sql)) {
        throw new Exception("Gagal update status ticket");
    }

    $insert_update_sql = "INSERT INTO tr_ticket_updates (ticket_id, user_id, update_type, description) 
                          VALUES ('$ticket_id', '$bor_user_id', 'Escalation', 
                                  'Ticket dikirim ke Dispatch - Memerlukan kunjungan teknisi lapangan')";
    
    if (!mysqli_query($conn, $insert_update_sql)) {
        throw new Exception("Gagal mencatat aktivitas dispatch");
    }

    $wo_code = 'WO-' . date('Ymd') . '-' . strtoupper(uniqid());
    
    $insert_wo_sql = "INSERT INTO tr_work_orders (
        wo_code, 
        ticket_id, 
        created_by_dispatch_id, 
        assigned_to_vendor_id,
        status,
        priority_level,
        created_at
    ) VALUES (
        '$wo_code', 
        '$ticket_id', 
        '$bor_user_id', 
        NULL, 
        'Sent to Dispatch',
        'Normal',
        NOW()
    )";
    
    if (!mysqli_query($conn, $insert_wo_sql)) {
        throw new Exception("Gagal membuat Work Order: " . mysqli_error($conn));
    }

    mysqli_commit($conn);
    
    header('Location: dashboard_bor.php?status=dispatched');
    exit();

} catch (Exception $e) {
    mysqli_rollback($conn);
    
    error_log("Error BOR dispatching ticket: " . $e->getMessage());
    header('Location: dashboard_bor.php?status=error_dispatch');
    exit();
}

mysqli_autocommit($conn, TRUE);
?>