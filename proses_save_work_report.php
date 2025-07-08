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

// Ambil data dari form
$wo_id = (int)$_POST['wo_id'];
$action = $_POST['action']; // 'save_draft', 'submit_final', atau 'auto_save'
$vendor_user_id = $_SESSION['user_id'];
$current_time = date('Y-m-d H:i:s');

// Basic form data
$completion_status = mysqli_real_escape_string($conn, $_POST['completion_status'] ?? '');
$work_description = mysqli_real_escape_string($conn, $_POST['work_description'] ?? '');
$work_duration = (int)($_POST['work_duration'] ?? 0);

// Technical data
$signal_before = mysqli_real_escape_string($conn, $_POST['signal_before'] ?? '');
$signal_after = mysqli_real_escape_string($conn, $_POST['signal_after'] ?? '');
$speed_test_result = mysqli_real_escape_string($conn, $_POST['speed_test_result'] ?? '');

// Equipment changes - Convert to JSON
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

// Ambil input equipment_removed
$equipment_removed = mysqli_real_escape_string($conn, $_POST['equipment_removed'] ?? '');
$equipment_removed_val = !empty($equipment_removed) ? "'$equipment_removed'" : "NULL";

// Materials Used - Convert to JSON
$materials_used = [];
if (!empty($_POST['materials'])) {
    foreach ($_POST['materials'] as $index => $material) {
        if (!empty($material['name'])) {
            $materials_used[] = [
                'name' => mysqli_real_escape_string($conn, $material['name']),
                'quantity' => (int)($material['quantity'] ?? 0),
                'unit' => mysqli_real_escape_string($conn, $material['unit'] ?? ''),
                'notes' => mysqli_real_escape_string($conn, $material['notes'] ?? '')
            ];
        }
    }
}

// Validasi untuk submit final
if ($action === 'submit_final') {
    if (empty($completion_status) || empty($work_description)) {
        header('Location: create_work_report.php?wo_id=' . $wo_id . '&status=error_missing_required');
        exit();
    }
}

// Validasi: Pastikan WO exists dan di-assign ke teknisi ini
$check_sql = "SELECT 
    wo.id,
    wo.wo_code,
    wo.ticket_id,
    wo.status as wo_status,
    wo.started_at,
    wo.estimated_duration,
    t.ticket_code,
    c.full_name as customer_name
FROM tr_work_orders wo
JOIN tr_tickets t ON wo.ticket_id = t.id
JOIN ms_customers c ON t.customer_id = c.id
WHERE wo.id = '$wo_id' 
AND wo.assigned_to_vendor_id = '$vendor_user_id' 
AND wo.status IN ('In Progress', 'Scheduled')";

$check_result = mysqli_query($conn, $check_sql);

if (mysqli_num_rows($check_result) == 0) {
    header('Location: dashboard_vendor.php?status=error_invalid_wo');
    exit();
}

$wo_data = mysqli_fetch_assoc($check_result);

// Calculate actual duration jika belum diisi manual
if (!$work_duration && $wo_data['started_at']) {
    $start_time = new DateTime($wo_data['started_at']);
    $end_time = new DateTime($current_time);
    $work_duration = $end_time->diff($start_time)->h * 60 + $end_time->diff($start_time)->i;
}

// Convert arrays to JSON for database storage
$equipment_json = !empty($equipment_changes) ? json_encode($equipment_changes) : NULL;
$materials_json = !empty($materials_used) ? json_encode($materials_used) : NULL;

// Prepare values for database
$signal_before_val = !empty($signal_before) ? "'$signal_before'" : "NULL";
$signal_after_val = !empty($signal_after) ? "'$signal_after'" : "NULL";
$speed_test_val = !empty($speed_test_result) ? "'$speed_test_result'" : "NULL";
$equipment_val = $equipment_json ? "'$equipment_json'" : "NULL";
$materials_val = $materials_json ? "'$materials_json'" : "NULL";
$completion_status_val = !empty($completion_status) ? "'$completion_status'" : "NULL";
$work_description_val = !empty($work_description) ? "'$work_description'" : "NULL";

// Mulai transaksi database
mysqli_autocommit($conn, FALSE);

try {
    // Cek apakah sudah ada report untuk WO ini
    $existing_sql = "SELECT id FROM tr_work_reports WHERE work_order_id = '$wo_id'";
    $existing_result = mysqli_query($conn, $existing_sql);
    $existing_report = mysqli_num_rows($existing_result) > 0;

    if ($existing_report) {
        // Update existing report
        $update_report_sql = "UPDATE tr_work_reports SET 
            equipment_replaced = $equipment_val,
            equipment_removed = $equipment_removed_val,
            signal_before = $signal_before_val,
            signal_after = $signal_after_val,
            speed_test_result = $speed_test_val,
            materials_used = $materials_val,
            updated_at = '$current_time'
            WHERE work_order_id = '$wo_id'";
        
        if (!mysqli_query($conn, $update_report_sql)) {
            throw new Exception("Gagal update work report: " . mysqli_error($conn));
        }
        
    } else {
        // Insert new report
        $insert_report_sql = "INSERT INTO tr_work_reports (
            work_order_id, 
            technician_id, 
            equipment_replaced, 
            equipment_removed,
            signal_before, 
            signal_after, 
            speed_test_result, 
            materials_used, 
            created_at
        ) VALUES (
            '$wo_id',
            '$vendor_user_id',
            $equipment_val,
            $equipment_removed_val,
            $signal_before_val,
            $signal_after_val,
            $speed_test_val,
            $materials_val,
            '$current_time'
        )";
        
        if (!mysqli_query($conn, $insert_report_sql)) {
            throw new Exception("Gagal insert work report: " . mysqli_error($conn));
        }
    }

    // Jika submit final, update WO dan ticket status
    if ($action === 'submit_final') {
        // 1. Update Work Order ke Completed by Technician
        $update_wo_sql = "UPDATE tr_work_orders SET 
                          status = 'Completed by Technician',
                          visit_report = $work_description_val,
                          actual_duration = " . ($work_duration ? "'$work_duration'" : "NULL") . "
                          WHERE id = '$wo_id'";
        
        if (!mysqli_query($conn, $update_wo_sql)) {
            throw new Exception("Gagal update Work Order: " . mysqli_error($conn));
        }

        // 2. Update ticket status ke "Waiting for BOR Review"
        $update_ticket_sql = "UPDATE tr_tickets SET 
                              status = 'Waiting for BOR Review'
                              WHERE id = '{$wo_data['ticket_id']}'";
        
        if (!mysqli_query($conn, $update_ticket_sql)) {
            throw new Exception("Gagal update status ticket: " . mysqli_error($conn));
        }

        // 3. Log activity
        $status_description = "Work Order diselesaikan oleh teknisi IKR - Status: $completion_status. ";
        $status_description .= "Laporan pekerjaan telah disubmit dan menunggu review dari BOR. ";
        
        if ($work_duration) {
            $estimated = $wo_data['estimated_duration'];
            $status_description .= "Waktu pengerjaan: $work_duration menit" . ($estimated ? " (estimasi: $estimated menit)" : "") . ". ";
        }
        if (!empty($equipment_changes)) {
            $status_description .= "Ada pergantian equipment. ";
        }
        if (!empty($materials_used)) {
            $status_description .= "Menggunakan " . count($materials_used) . " material. ";
        }
        
        $insert_update_sql = "INSERT INTO tr_ticket_updates (ticket_id, user_id, update_type, description) 
                              VALUES ('{$wo_data['ticket_id']}', '$vendor_user_id', 'Status Change', '$status_description')";
        
        if (!mysqli_query($conn, $insert_update_sql)) {
            throw new Exception("Gagal mencatat aktivitas completion: " . mysqli_error($conn));
        }
    }

    // Commit transaksi
    mysqli_commit($conn);
    
    // Handle response berdasarkan action
    if ($action === 'auto_save') {
        // Return JSON untuk AJAX
        header('Content-Type: application/json');
        echo json_encode(['status' => 'success', 'message' => 'Auto-save berhasil']);
        exit();
        
    } elseif ($action === 'save_draft') {
        // Redirect ke form dengan pesan draft saved
        header('Location: create_work_report.php?wo_id=' . $wo_id . '&status=draft_saved');
        exit();
        
    } elseif ($action === 'submit_final') {
        // Redirect ke dashboard dengan pesan success
        header('Location: dashboard_vendor.php?status=work_completed_pending_review');
        exit();
    }

} catch (Exception $e) {
    // Rollback jika ada error
    mysqli_rollback($conn);
    
    // Log error untuk debugging
    error_log("Error saving work report: " . $e->getMessage());
    
    // Handle response berdasarkan action
    if ($action === 'auto_save') {
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'message' => 'Auto-save gagal']);
        exit();
    } else {
        header('Location: create_work_report.php?wo_id=' . $wo_id . '&status=error_save');
        exit();
    }
}

// Kembalikan autocommit ke true
mysqli_autocommit($conn, TRUE);
?>