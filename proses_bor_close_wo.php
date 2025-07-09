<?php
include 'config/db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'BOR') {
    header('Location: login.php');
    exit();
}

if (!isset($_GET['wo_id']) || !isset($_GET['action']) || empty($_GET['wo_id']) || empty($_GET['action'])) {
    header('Location: dashboard_bor.php?status=error_missing_params');
    exit();
}

$wo_id = (int)$_GET['wo_id'];
$action = $_GET['action'];
$bor_user_id = $_SESSION['user_id'];
$current_time = date('Y-m-d H:i:s');

$valid_actions = ['approve', 'unsolved'];
if (!in_array($action, $valid_actions)) {
    header('Location: dashboard_bor.php?status=error_invalid_action');
    exit();
}

$check_sql = "SELECT 
    wo.id, 
    wo.wo_code, 
    wo.ticket_id,
    wo.status,
    wo.work_quality_rating,
    wo.ticket_resolution_status,
    wo.dispatch_review_notes,
    wo.reviewed_by_dispatch_id,
    t.ticket_code,
    t.current_owner_user_id,
    t.status as ticket_status,
    c.full_name as customer_name,
    u_tech.full_name as technician_name,
    u_dispatch.full_name as dispatch_reviewer
FROM tr_work_orders wo
JOIN tr_tickets t ON wo.ticket_id = t.id
JOIN ms_customers c ON t.customer_id = c.id
LEFT JOIN ms_users u_tech ON wo.assigned_to_vendor_id = u_tech.id
LEFT JOIN ms_users u_dispatch ON wo.reviewed_by_dispatch_id = u_dispatch.id
WHERE wo.id = '$wo_id'";

$check_result = mysqli_query($conn, $check_sql);

if (mysqli_num_rows($check_result) == 0) {
    header('Location: dashboard_bor.php?status=error_wo_not_found');
    exit();
}

$wo_data = mysqli_fetch_assoc($check_result);

if ($wo_data['status'] != 'Waiting For BOR Review') {
    header('Location: dashboard_bor.php?status=error_wrong_status');
    exit();
}

if (empty($wo_data['reviewed_by_dispatch_id'])) {
    header('Location: dashboard_bor.php?status=error_not_reviewed');
    exit();
}

if ($action === 'approve') {
    $ticket_status = 'Closed - Solved';
    $wo_status = 'Closed by BOR';
    $activity_desc = "BOR APPROVED: Ticket ditutup sebagai SOLVED. Work Order telah diselesaikan dengan baik oleh teknisi.";
} else {
    $ticket_status = 'Closed - Unsolved';
    $wo_status = 'Closed by BOR';
    $activity_desc = "BOR UNSOLVED: Ticket ditutup sebagai UNSOLVED. Work Order tidak dapat menyelesaikan masalah customer sepenuhnya.";
}

mysqli_autocommit($conn, FALSE);

try {
    $update_wo_sql = "UPDATE tr_work_orders SET 
                      status = '$wo_status',
                      closed_at = '$current_time'
                      WHERE id = '$wo_id'";
    
    if (!mysqli_query($conn, $update_wo_sql)) {
        throw new Exception("Gagal update Work Order: " . mysqli_error($conn));
    }

    $update_ticket_sql = "UPDATE tr_tickets SET 
                          status = '$ticket_status',
                          closed_at = '$current_time'
                          WHERE id = '{$wo_data['ticket_id']}'";
    
    if (!mysqli_query($conn, $update_ticket_sql)) {
        throw new Exception("Gagal update ticket: " . mysqli_error($conn));
    }

    $full_activity_desc = $activity_desc . " ";
    $full_activity_desc .= "Teknisi: " . ($wo_data['technician_name'] ?: 'N/A') . ". ";
    $full_activity_desc .= "Reviewed by: " . ($wo_data['dispatch_reviewer'] ?: 'N/A') . ".";
    
    $insert_update_sql = "INSERT INTO tr_ticket_updates (ticket_id, user_id, update_type, description) 
                          VALUES ('{$wo_data['ticket_id']}', '$bor_user_id', 'Status Change', '$full_activity_desc')";
    
    if (!mysqli_query($conn, $insert_update_sql)) {
        throw new Exception("Gagal insert log: " . mysqli_error($conn));
    }

    mysqli_commit($conn);
    
    $status_msg = ($action === 'approve') ? 'wo_approved_closed' : 'wo_unsolved_closed';
    header('Location: dashboard_bor.php?status=' . $status_msg);
    exit();

} catch (Exception $e) {
    mysqli_rollback($conn);
    
    error_log("Error closing WO by BOR: " . $e->getMessage());
    
    header('Location: dashboard_bor.php?status=error_close_wo');
    exit();
}

mysqli_autocommit($conn, TRUE);
?>