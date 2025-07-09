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
$location_confirmed = isset($_POST['location_confirmed']) ? 1 : 0;
$estimated_duration = (int)$_POST['estimated_duration'];
$pre_work_notes = mysqli_real_escape_string($conn, $_POST['pre_work_notes']);
$vendor_user_id = $_SESSION['user_id'];
$current_time = date('Y-m-d H:i:s');

if (empty($wo_id) || !$location_confirmed || empty($estimated_duration)) {
    header('Location: dashboard_vendor.php?status=error_missing_data');
    exit();
}

$check_sql = "SELECT 
    wo.id, 
    wo.wo_code, 
    wo.ticket_id, 
    wo.status,
    wo.scheduled_visit_date,
    t.ticket_code,
    c.full_name as customer_name,
    c.address as customer_address
FROM tr_work_orders wo
JOIN tr_tickets t ON wo.ticket_id = t.id
JOIN ms_customers c ON t.customer_id = c.id
WHERE wo.id = '$wo_id' 
AND wo.assigned_to_vendor_id = '$vendor_user_id' 
AND wo.status IN ('Scheduled', 'Scheduled by Admin IKR')";

$check_result = mysqli_query($conn, $check_sql);

if (mysqli_num_rows($check_result) == 0) {
    header('Location: dashboard_vendor.php?status=error_invalid_wo');
    exit();
}

$wo_data = mysqli_fetch_assoc($check_result);

mysqli_autocommit($conn, FALSE);

try {
    $update_wo_sql = "UPDATE tr_work_orders SET 
                      status = 'In Progress',
                      started_at = '$current_time',
                      estimated_duration = '$estimated_duration'";
    
    if (!empty($pre_work_notes)) {
        $update_wo_sql .= ", pre_work_notes = '$pre_work_notes'";
    }
    
    $update_wo_sql .= " WHERE id = '$wo_id'";
    
    if (!mysqli_query($conn, $update_wo_sql)) {
        throw new Exception("Gagal update Work Order status: " . mysqli_error($conn));
    }

    $activity_desc = "Teknisi IKR telah memulai Work Order. ";
    $activity_desc .= "Lokasi telah dikonfirmasi. ";
    $activity_desc .= "Estimasi waktu pengerjaan: $estimated_duration menit. ";
    
    if (!empty($pre_work_notes)) {
        $activity_desc .= "Catatan awal: " . substr($pre_work_notes, 0, 100);
        if (strlen($pre_work_notes) > 100) {
            $activity_desc .= "...";
        }
    }
    
    $insert_update_sql = "INSERT INTO tr_ticket_updates (ticket_id, user_id, update_type, description) 
                          VALUES ('{$wo_data['ticket_id']}', '$vendor_user_id', 'Status Change', '$activity_desc')";
    
    if (!mysqli_query($conn, $insert_update_sql)) {
        throw new Exception("Gagal mencatat aktivitas start work: " . mysqli_error($conn));
    }

    mysqli_commit($conn);
    
    header('Location: dashboard_vendor.php?status=work_started');
    exit();

} catch (Exception $e) {
    mysqli_rollback($conn);
    
    error_log("Error starting work: " . $e->getMessage());
    
    header('Location: dashboard_vendor.php?status=error_start');
    exit();
}

mysqli_autocommit($conn, TRUE);
?>