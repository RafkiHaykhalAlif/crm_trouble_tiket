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
$work_description = mysqli_real_escape_string($conn, $_POST['work_description']);
$customer_satisfaction = mysqli_real_escape_string($conn, $_POST['customer_satisfaction']);
$customer_notes = mysqli_real_escape_string($conn, $_POST['customer_notes']);

$signal_before = mysqli_real_escape_string($conn, $_POST['signal_before']);
$signal_after = mysqli_real_escape_string($conn, $_POST['signal_after']);
$speed_test_result = mysqli_real_escape_string($conn, $_POST['speed_test_result']);
$equipment_replaced = mysqli_real_escape_string($conn, $_POST['equipment_replaced']);
$cables_replaced = mysqli_real_escape_string($conn, $_POST['cables_replaced']);
$new_installations = mysqli_real_escape_string($conn, $_POST['new_installations']);

$materials_used = [];
if (!empty($_POST['materials'])) {
    foreach ($_POST['materials'] as $index => $material) {
        if (!empty($material['name'])) {
            $materials_used[] = [
                'name' => mysqli_real_escape_string($conn, $material['name']),
                'quantity' => (int)$material['quantity'],
                'unit' => mysqli_real_escape_string($conn, $material['unit']),
                'notes' => mysqli_real_escape_string($conn, $material['notes'])
            ];
        }
    }
}

$vendor_user_id = $_SESSION['user_id'];
$current_time = date('Y-m-d H:i:s');

if (empty($wo_id) || empty($completion_status) || empty($work_description)) {
    header('Location: dashboard_vendor.php?status=error_missing_data');
    exit();
}

$check_sql = "SELECT 
    wo.id, 
    wo.wo_code, 
    wo.ticket_id, 
    wo.status,
    wo.started_at,
    wo.estimated_duration,
    t.ticket_code,
    t.current_owner_user_id,
    c.full_name as customer_name
FROM tr_work_orders wo
JOIN tr_tickets t ON wo.ticket_id = t.id
JOIN ms_customers c ON t.customer_id = c.id
WHERE wo.id = '$wo_id' 
AND wo.assigned_to_vendor_id = '$vendor_user_id' 
AND wo.status IN ('In Progress', 'Scheduled', 'Scheduled by Admin IKR')";

$check_result = mysqli_query($conn, $check_sql);

if (mysqli_num_rows($check_result) == 0) {
    header('Location: dashboard_vendor.php?status=error_invalid_wo');
    exit();
}

$wo_data = mysqli_fetch_assoc($check_result);

$actual_duration = null;
if ($wo_data['started_at']) {
    $start_time = new DateTime($wo_data['started_at']);
    $end_time = new DateTime($current_time);
    $actual_duration = $end_time->diff($start_time)->h * 60 + $end_time->diff($start_time)->i; // in minutes
}

$materials_json = !empty($materials_used) ? json_encode($materials_used) : NULL;

mysqli_autocommit($conn, FALSE);

try {
    $update_wo_sql = "UPDATE tr_work_orders SET 
                      status = 'Completed by Technician',
                      visit_report = '$work_description',
                      actual_duration = " . ($actual_duration ? "'$actual_duration'" : "NULL") . "
                      WHERE id = '$wo_id'";
    
    if (!mysqli_query($conn, $update_wo_sql)) {
        throw new Exception("Gagal update Work Order: " . mysqli_error($conn));
    }

    $equipment_replaced_val = !empty($equipment_replaced) ? "'$equipment_replaced'" : "NULL";
    $cables_replaced_val = !empty($cables_replaced) ? "'$cables_replaced'" : "NULL";
    $new_installations_val = !empty($new_installations) ? "'$new_installations'" : "NULL";
    $signal_before_val = !empty($signal_before) ? "'$signal_before'" : "NULL";
    $signal_after_val = !empty($signal_after) ? "'$signal_after'" : "NULL";
    $speed_test_val = !empty($speed_test_result) ? "'$speed_test_result'" : "NULL";
    $materials_val = $materials_json ? "'$materials_json'" : "NULL";
    $customer_satisfaction_val = !empty($customer_satisfaction) ? "'$customer_satisfaction'" : "NULL";
    $customer_notes_val = !empty($customer_notes) ? "'$customer_notes'" : "NULL";
    
    $insert_report_sql = "INSERT INTO tr_work_reports (
        work_order_id, 
        technician_id, 
        completion_status,
        work_description,
        equipment_replaced, 
        cables_replaced, 
        new_installations, 
        signal_before, 
        signal_after, 
        speed_test_result, 
        materials_used, 
        customer_satisfaction, 
        customer_notes
    ) VALUES (
        '$wo_id',
        '$vendor_user_id',
        '$completion_status',
        '$work_description',
        $equipment_replaced_val,
        $cables_replaced_val,
        $new_installations_val,
        $signal_before_val,
        $signal_after_val,
        $speed_test_val,
        $materials_val,
        $customer_satisfaction_val,
        $customer_notes_val
    )";
    
    if (!mysqli_query($conn, $insert_report_sql)) {
        throw new Exception("Gagal insert work report: " . mysqli_error($conn));
    }

    $update_ticket_sql = "UPDATE tr_tickets SET 
                          status = 'Waiting for Dispatch'
                          WHERE id = '{$wo_data['ticket_id']}'";
    
    if (!mysqli_query($conn, $update_ticket_sql)) {
        throw new Exception("Gagal update status ticket: " . mysqli_error($conn));
    }

    $status_description = "Work Order diselesaikan oleh teknisi IKR - Status: $completion_status. ";
    $status_description .= "Laporan pekerjaan telah disubmit dan menunggu review dari Dispatch. ";
    
    if ($actual_duration) {
        $estimated = $wo_data['estimated_duration'];
        $status_description .= "Waktu pengerjaan: $actual_duration menit (estimasi: $estimated menit). ";
    }
    if (!empty($equipment_replaced)) {
        $status_description .= "Ada pergantian equipment. ";
    }
    if (!empty($materials_used)) {
        $status_description .= "Menggunakan " . count($materials_used) . " material. ";
    }
    if ($customer_satisfaction) {
        $status_description .= "Customer satisfaction: $customer_satisfaction.";
    }
    
    $insert_update_sql = "INSERT INTO tr_ticket_updates (ticket_id, user_id, update_type, description) 
                          VALUES ('{$wo_data['ticket_id']}', '$vendor_user_id', 'Status Change', '$status_description')";
    
    if (!mysqli_query($conn, $insert_update_sql)) {
        throw new Exception("Gagal mencatat aktivitas completion: " . mysqli_error($conn));
    }

    mysqli_commit($conn);
    
    header('Location: dashboard_vendor.php?status=work_completed_pending_review');
    exit();

} catch (Exception $e) {
    mysqli_rollback($conn);
    
    error_log("Error completing work with details: " . $e->getMessage());
    
    header('Location: dashboard_vendor.php?status=error_complete');
    exit();
}

mysqli_autocommit($conn, TRUE);
?>