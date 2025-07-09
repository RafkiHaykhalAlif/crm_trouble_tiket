<?php
include 'config/db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'Vendor IKR') {
    header('Location: login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: dashboard_vendor.php');
    exit();
}

$wo_id = (int)$_POST['wo_id'];
$completion_status = mysqli_real_escape_string($conn, $_POST['completion_status']);
$visit_report = mysqli_real_escape_string($conn, $_POST['visit_report']);
$vendor_user_id = $_SESSION['user_id'];
$current_time = date('Y-m-d H:i:s');

if (empty($wo_id) || empty($completion_status) || empty($visit_report)) {
    header('Location: dashboard_vendor.php?status=error_missing_data');
    exit();
}

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
WHERE wo.id = '$wo_id' 
AND wo.assigned_to_vendor_id = '$vendor_user_id' 
AND wo.status = 'Scheduled'";

$check_result = mysqli_query($conn, $check_sql);

if (mysqli_num_rows($check_result) == 0) {
    header('Location: dashboard_vendor.php?status=error_invalid_wo');
    exit();
}

$wo_data = mysqli_fetch_assoc($check_result);

$ticket_status = '';
switch($completion_status) {
    case 'Solved':
        $ticket_status = 'Closed - Solved';
        break;
    case 'Partial':
    case 'Cannot Fix':
    case 'Customer Not Available':
        $ticket_status = 'Closed - Unsolved';
        break;
    default:
        $ticket_status = 'Closed - Unsolved';
}

mysqli_autocommit($conn, FALSE);

try {
    $update_wo_sql = "UPDATE tr_work_orders SET 
                      status = 'Completed',
                      visit_report = '$visit_report'
                      WHERE id = '$wo_id'";
    
    if (!mysqli_query($conn, $update_wo_sql)) {
        throw new Exception("Gagal update Work Order: " . mysqli_error($conn));
    }

    $update_ticket_sql = "UPDATE tr_tickets SET 
                          status = '$ticket_status',
                          closed_at = '$current_time'
                          WHERE id = '{$wo_data['ticket_id']}'";
    
    if (!mysqli_query($conn, $update_ticket_sql)) {
        throw new Exception("Gagal update status ticket: " . mysqli_error($conn));
    }

    $status_description = '';
    switch($completion_status) {
        case 'Solved':
            $status_description = "Work Order diselesaikan oleh teknisi IKR - Masalah berhasil diperbaiki";
            break;
        case 'Partial':
            $status_description = "Work Order diselesaikan oleh teknisi IKR - Diperbaiki sebagian, perlu follow-up";
            break;
        case 'Cannot Fix':
            $status_description = "Work Order diselesaikan oleh teknisi IKR - Masalah tidak bisa diperbaiki";
            break;
        case 'Customer Not Available':
            $status_description = "Work Order diselesaikan oleh teknisi IKR - Customer tidak tersedia saat kunjungan";
            break;
    }
    
    $status_description .= ". Laporan: " . substr($visit_report, 0, 100) . "...";
    
    $insert_update_sql = "INSERT INTO tr_ticket_updates (ticket_id, user_id, update_type, description) 
                          VALUES ('{$wo_data['ticket_id']}', '$vendor_user_id', 'Status Change', '$status_description')";
    
    if (!mysqli_query($conn, $insert_update_sql)) {
        throw new Exception("Gagal mencatat aktivitas completion: " . mysqli_error($conn));
    }

    mysqli_commit($conn);
    
    header('Location: dashboard_vendor.php?status=completed');
    exit();

} catch (Exception $e) {
    mysqli_rollback($conn);

    error_log("Error completing WO: " . $e->getMessage());
    
    header('Location: dashboard_vendor.php?status=error_complete');
    exit();
}

mysqli_autocommit($conn, TRUE);
?>