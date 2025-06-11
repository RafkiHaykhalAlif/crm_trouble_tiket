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
$location_confirmed = isset($_POST['location_confirmed']) ? 1 : 0;
$pre_work_notes = mysqli_real_escape_string($conn, $_POST['pre_work_notes']);
$estimated_duration = (int)$_POST['estimated_duration']; // dalam menit
$vendor_user_id = $_SESSION['user_id'];
$current_time = date('Y-m-d H:i:s');

// Validasi input
if (empty($wo_id) || empty($estimated_duration)) {
    header('Location: dashboard_vendor.php?status=error_missing_data');
    exit();
}

// Validasi: Pastikan WO exists, di-assign ke teknisi ini, dan statusnya Scheduled
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

// Mulai transaksi database
mysqli_autocommit($conn, FALSE);

try {
    // 1. Update Work Order ke status "In Progress"
    $update_wo_sql = "UPDATE tr_work_orders SET 
                      status = 'In Progress',
                      started_at = '$current_time',
                      estimated_duration = '$estimated_duration',
                      pre_work_notes = '$pre_work_notes'
                      WHERE id = '$wo_id'";
    
    if (!mysqli_query($conn, $update_wo_sql)) {
        throw new Exception("Gagal update Work Order: " . mysqli_error($conn));
    }

    // 2. Log activity ke tr_ticket_updates
    $activity_description = "Teknisi IKR mulai mengerjakan Work Order";
    if ($location_confirmed) {
        $activity_description .= " - Konfirmasi sudah sampai di lokasi customer";
    }
    if (!empty($pre_work_notes)) {
        $activity_description .= ". Catatan awal: " . substr($pre_work_notes, 0, 100);
    }
    $activity_description .= ". Estimasi pengerjaan: $estimated_duration menit";
    
    $insert_update_sql = "INSERT INTO tr_ticket_updates (ticket_id, user_id, update_type, description) 
                          VALUES ('{$wo_data['ticket_id']}', '$vendor_user_id', 'Status Change', '$activity_description')";
    
    if (!mysqli_query($conn, $insert_update_sql)) {
        throw new Exception("Gagal mencatat aktivitas start work: " . mysqli_error($conn));
    }

    // Commit transaksi
    mysqli_commit($conn);
    
    // Redirect dengan pesan sukses
    header('Location: dashboard_vendor.php?status=work_started');
    exit();

} catch (Exception $e) {
    // Rollback jika ada error
    mysqli_rollback($conn);
    
    // Log error untuk debugging
    error_log("Error starting work: " . $e->getMessage());
    
    // Redirect dengan pesan error
    header('Location: dashboard_vendor.php?status=error_start');
    exit();
}

// Kembalikan autocommit ke true
mysqli_autocommit($conn, TRUE);
?>