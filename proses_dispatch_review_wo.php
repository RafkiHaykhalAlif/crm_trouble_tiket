<?php
include 'config/db_connect.php';

// Cek apakah user sudah login dan adalah Dispatch
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'Dispatch') {
    header('Location: login.php');
    exit();
}

// Cek apakah form di-submit dengan method POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: dashboard_dispatch.php');
    exit();
}

// Ambil data dari form
$wo_id = (int)$_POST['wo_id'];
$work_quality = mysqli_real_escape_string($conn, $_POST['work_quality']);
$ticket_resolution = mysqli_real_escape_string($conn, $_POST['ticket_resolution']);
$dispatch_notes = mysqli_real_escape_string($conn, $_POST['dispatch_notes']);
$bor_summary = mysqli_real_escape_string($conn, $_POST['bor_summary']);
$customer_feedback = mysqli_real_escape_string($conn, $_POST['customer_feedback']);
$dispatch_user_id = $_SESSION['user_id'];
$current_time = date('Y-m-d H:i:s');

// Validasi input wajib
if (empty($wo_id) || empty($work_quality) || empty($ticket_resolution) || empty($dispatch_notes) || empty($bor_summary)) {
    header('Location: dashboard_dispatch.php?status=error_missing_data');
    exit();
}

// Validasi: Pastikan WO exists dan statusnya "Completed by Technician"
$check_sql = "SELECT 
    wo.id, 
    wo.wo_code, 
    wo.ticket_id,
    wo.status,
    wo.visit_report,
    wo.assigned_to_vendor_id,
    t.ticket_code,
    t.current_owner_user_id,
    c.full_name as customer_name,
    u_tech.full_name as technician_name
FROM tr_work_orders wo
JOIN tr_tickets t ON wo.ticket_id = t.id
JOIN ms_customers c ON t.customer_id = c.id
LEFT JOIN ms_users u_tech ON wo.assigned_to_vendor_id = u_tech.id
WHERE wo.id = '$wo_id' AND wo.status = 'Completed by Technician'";

$check_result = mysqli_query($conn, $check_sql);

if (mysqli_num_rows($check_result) == 0) {
    header('Location: dashboard_dispatch.php?status=error_invalid_wo');
    exit();
}

$wo_data = mysqli_fetch_assoc($check_result);

// Cari user BOR untuk transfer ownership
$bor_sql = "SELECT id FROM ms_users WHERE role = 'BOR' LIMIT 1";
$bor_result = mysqli_query($conn, $bor_sql);

if (mysqli_num_rows($bor_result) == 0) {
    header('Location: dashboard_dispatch.php?status=error_no_bor');
    exit();
}

$bor_user = mysqli_fetch_assoc($bor_result);
$bor_user_id = $bor_user['id'];

// Mulai transaksi database
mysqli_autocommit($conn, FALSE);

try {
    // 1. Update Work Order - INI YANG DIPERBAIKI! Status harus jadi "Waiting For BOR Review"
    $update_wo_sql = "UPDATE tr_work_orders SET 
                      status = 'Waiting For BOR Review',
                      reviewed_by_dispatch_id = '$dispatch_user_id',
                      reviewed_at = '$current_time',
                      dispatch_review_notes = '$dispatch_notes',
                      work_quality_rating = '$work_quality',
                      ticket_resolution_status = '$ticket_resolution'
                      WHERE id = '$wo_id'";
    
    if (!mysqli_query($conn, $update_wo_sql)) {
        throw new Exception("Gagal update Work Order status: " . mysqli_error($conn));
    }

    // 2. Update ticket - status dan owner ke BOR
    $update_ticket_sql = "UPDATE tr_tickets SET 
                          status = 'Waiting For BOR Review',
                          current_owner_user_id = '$bor_user_id'
                          WHERE id = '{$wo_data['ticket_id']}'";
    
    if (!mysqli_query($conn, $update_ticket_sql)) {
        throw new Exception("Gagal update ticket owner ke BOR: " . mysqli_error($conn));
    }

    // 3. Log activity ke tr_ticket_updates
    $activity_desc = "Work Order telah di-review oleh Dispatch dengan hasil: ";
    $activity_desc .= "Kualitas kerja: $work_quality. ";
    $activity_desc .= "Status penyelesaian: $ticket_resolution. ";
    $activity_desc .= "Catatan Dispatch: " . substr($dispatch_notes, 0, 100) . "... ";
    $activity_desc .= "Dikirim ke BOR untuk penutupan ticket.";
    
    $insert_update_sql = "INSERT INTO tr_ticket_updates (ticket_id, user_id, update_type, description) 
                          VALUES ('{$wo_data['ticket_id']}', '$dispatch_user_id', 'Status Change', '$activity_desc')";
    
    if (!mysqli_query($conn, $insert_update_sql)) {
        throw new Exception("Gagal mencatat aktivitas review: " . mysqli_error($conn));
    }

    // Commit transaksi
    mysqli_commit($conn);
    
    // Redirect dengan pesan sukses
    header('Location: dashboard_dispatch.php?status=reviewed_and_sent_to_bor');
    exit();

} catch (Exception $e) {
    // Rollback jika ada error
    mysqli_rollback($conn);
    
    // Log error untuk debugging
    error_log("Error reviewing WO by Dispatch: " . $e->getMessage());
    
    // Redirect dengan pesan error
    header('Location: dashboard_dispatch.php?status=error_review');
    exit();
}

// Kembalikan autocommit ke true
mysqli_autocommit($conn, TRUE);
?>