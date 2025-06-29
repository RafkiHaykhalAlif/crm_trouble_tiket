<?php
include 'config/db_connect.php';

// Cek apakah user sudah login dan adalah Vendor IKR
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'Vendor IKR') {
    header('Location: login.php');
    exit();
}

// Cek apakah form di-submit dengan method POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: dashboard_vendor.php');
    exit();
}

// Ambil data dari form - Basic Info
$wo_id = (int)$_POST['wo_id'];
$completion_status = mysqli_real_escape_string($conn, $_POST['completion_status']);
$work_description = mysqli_real_escape_string($conn, $_POST['work_description']);
$customer_satisfaction = mysqli_real_escape_string($conn, $_POST['customer_satisfaction']);
$customer_notes = mysqli_real_escape_string($conn, $_POST['customer_notes']);

// Technical Details
$signal_before = mysqli_real_escape_string($conn, $_POST['signal_before']);
$signal_after = mysqli_real_escape_string($conn, $_POST['signal_after']);
$speed_test_result = mysqli_real_escape_string($conn, $_POST['speed_test_result']);

// Equipment Changes - Convert to JSON
$equipment_changes = [];
if (!empty($_POST['equipment_replaced'])) {
    $equipment_changes['equipment_replaced'] = $_POST['equipment_replaced'];
}
if (!empty($_POST['cables_replaced'])) {
    $equipment_changes['cables_replaced'] = $_POST['cables_replaced'];
}
if (!empty($_POST['new_installations'])) {
    $equipment_changes['new_installations'] = $_POST['new_installations'];
}

// Materials Used - Convert to JSON
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

// Validasi input wajib
if (empty($wo_id) || empty($completion_status) || empty($work_description)) {
    header('Location: dashboard_vendor.php?status=error_missing_data');
    exit();
}

// Validasi: Pastikan WO exists dan statusnya In Progress
$check_sql = "SELECT 
    wo.id, 
    wo.wo_code, 
    wo.ticket_id, 
    wo.status,
    wo.started_at,
    wo.estimated_duration,
    t.ticket_code,
    c.full_name as customer_name
FROM tr_work_orders wo
JOIN tr_tickets t ON wo.ticket_id = t.id
JOIN ms_customers c ON t.customer_id = c.id
WHERE wo.id = '$wo_id' 
AND wo.assigned_to_vendor_id = '$vendor_user_id' 
AND wo.status IN ('In Progress', 'Scheduled')"; // Allow both status

$check_result = mysqli_query($conn, $check_sql);

if (mysqli_num_rows($check_result) == 0) {
    header('Location: dashboard_vendor.php?status=error_invalid_wo');
    exit();
}

$wo_data = mysqli_fetch_assoc($check_result);

// Calculate actual duration
$actual_duration = null;
if ($wo_data['started_at']) {
    $start_time = new DateTime($wo_data['started_at']);
    $end_time = new DateTime($current_time);
    $actual_duration = $end_time->diff($start_time)->h * 60 + $end_time->diff($start_time)->i; // in minutes
}

// Tentukan status ticket berdasarkan completion status
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

// Convert arrays to JSON for database storage
$equipment_json = !empty($equipment_changes) ? json_encode($equipment_changes) : NULL;
$materials_json = !empty($materials_used) ? json_encode($materials_used) : NULL;

// Mulai transaksi database
mysqli_autocommit($conn, FALSE);

try {
    // 1. Update Work Order status ke Completed
    $update_wo_sql = "UPDATE tr_work_orders SET 
                      status = 'Completed',
                      visit_report = '$work_description',
                      actual_duration = " . ($actual_duration ? "'$actual_duration'" : "NULL") . "
                      WHERE id = '$wo_id'";
    
    if (!mysqli_query($conn, $update_wo_sql)) {
        throw new Exception("Gagal update Work Order: " . mysqli_error($conn));
    }

    // 2. Insert detailed work report
    $signal_before_val = !empty($signal_before) ? "'$signal_before'" : "NULL";
    $signal_after_val = !empty($signal_after) ? "'$signal_after'" : "NULL";
    $speed_test_val = !empty($speed_test_result) ? "'$speed_test_result'" : "NULL";
    $equipment_val = $equipment_json ? "'$equipment_json'" : "NULL";
    $materials_val = $materials_json ? "'$materials_json'" : "NULL";
    $customer_satisfaction_val = !empty($customer_satisfaction) ? "'$customer_satisfaction'" : "NULL";
    $customer_notes_val = !empty($customer_notes) ? "'$customer_notes'" : "NULL";
    
    $insert_report_sql = "INSERT INTO tr_work_reports (
        work_order_id, 
        technician_id, 
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
        $equipment_val,
        NULL,
        NULL,
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

    // 3. Update status ticket
    $update_ticket_sql = "UPDATE tr_tickets SET 
                          status = '$ticket_status',
                          closed_at = '$current_time'
                          WHERE id = '{$wo_data['ticket_id']}'";
    
    if (!mysqli_query($conn, $update_ticket_sql)) {
        throw new Exception("Gagal update status ticket: " . mysqli_error($conn));
    }

    // 4. Log activity dengan detail
    $status_description = "Work Order diselesaikan oleh teknisi IKR - Status: $completion_status";
    if ($actual_duration) {
        $estimated = $wo_data['estimated_duration'];
        $status_description .= ". Waktu pengerjaan: $actual_duration menit (estimasi: $estimated menit)";
    }
    if (!empty($equipment_changes)) {
        $status_description .= ". Ada pergantian equipment";
    }
    if (!empty($materials_used)) {
        $status_description .= ". Menggunakan " . count($materials_used) . " material";
    }
    if ($customer_satisfaction) {
        $status_description .= ". Customer satisfaction: $customer_satisfaction";
    }
    
    $insert_update_sql = "INSERT INTO tr_ticket_updates (ticket_id, user_id, update_type, description) 
                          VALUES ('{$wo_data['ticket_id']}', '$vendor_user_id', 'Status Change', '$status_description')";
    
    if (!mysqli_query($conn, $insert_update_sql)) {
        throw new Exception("Gagal mencatat aktivitas completion: " . mysqli_error($conn));
    }

    // Commit transaksi
    mysqli_commit($conn);
    
    // Redirect dengan pesan sukses
    header('Location: dashboard_vendor.php?status=work_completed');
    exit();

} catch (Exception $e) {
    // Rollback jika ada error
    mysqli_rollback($conn);
    
    // Log error untuk debugging
    error_log("Error completing work with details: " . $e->getMessage());
    
    // Redirect dengan pesan error
    header('Location: dashboard_vendor.php?status=error_complete');
    exit();
}

// Kembalikan autocommit ke true
mysqli_autocommit($conn, TRUE);
?>