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
$visit_date = mysqli_real_escape_string($conn, $_POST['visit_date']);
$assigned_vendor = (int)$_POST['assigned_vendor'];
$dispatch_user_id = $_SESSION['user_id'];

// Validasi input
if (empty($wo_id) || empty($visit_date) || empty($assigned_vendor)) {
    header('Location: dashboard_dispatch.php?status=error_missing_data');
    exit();
}

// Validasi: Pastikan WO exists dan statusnya Pending
$check_sql = "SELECT id, wo_code, ticket_id FROM tr_work_orders 
              WHERE id = '$wo_id' AND status = 'Pending'";
$check_result = mysqli_query($conn, $check_sql);

if (mysqli_num_rows($check_result) == 0) {
    header('Location: dashboard_dispatch.php?status=error_invalid_wo');
    exit();
}

$wo_data = mysqli_fetch_assoc($check_result);

// Validasi: Pastikan vendor exists dan role-nya Vendor IKR
$vendor_check_sql = "SELECT id, full_name FROM ms_users 
                     WHERE id = '$assigned_vendor' AND role = 'Vendor IKR'";
$vendor_result = mysqli_query($conn, $vendor_check_sql);

if (mysqli_num_rows($vendor_result) == 0) {
    header('Location: dashboard_dispatch.php?status=error_invalid_vendor');
    exit();
}

$vendor_data = mysqli_fetch_assoc($vendor_result);

// Mulai transaksi database
mysqli_autocommit($conn, FALSE);

try {
    // 1. Update Work Order dengan jadwal dan assign vendor
    $update_wo_sql = "UPDATE tr_work_orders SET 
                      status = 'Scheduled',
                      scheduled_visit_date = '$visit_date',
                      assigned_to_vendor_id = '$assigned_vendor'
                      WHERE id = '$wo_id'";
    
    if (!mysqli_query($conn, $update_wo_sql)) {
        throw new Exception("Gagal update Work Order");
    }

    // 2. Update status ticket jadi "Waiting for Dispatch" (kalau belum)
    $update_ticket_sql = "UPDATE tr_tickets SET 
                          status = 'Waiting for Dispatch'
                          WHERE id = '{$wo_data['ticket_id']}'";
    
    mysqli_query($conn, $update_ticket_sql); // Ini optional, jadi ga throw error

    // 3. Tambahkan record ke tr_ticket_updates untuk tracking
    $insert_update_sql = "INSERT INTO tr_ticket_updates (ticket_id, user_id, update_type, description) 
                          VALUES ('{$wo_data['ticket_id']}', '$dispatch_user_id', 'Status Change', 
                                  'Work Order dijadwalkan untuk kunjungan teknisi pada " . date('d/m/Y H:i', strtotime($visit_date)) . " oleh {$vendor_data['full_name']}')";
    
    if (!mysqli_query($conn, $insert_update_sql)) {
        throw new Exception("Gagal mencatat aktivitas scheduling");
    }

    // Commit transaksi
    mysqli_commit($conn);
    
    // Redirect dengan pesan sukses
    header('Location: dashboard_dispatch.php?status=scheduled');
    exit();

} catch (Exception $e) {
    // Rollback jika ada error
    mysqli_rollback($conn);
    
    // Log error untuk debugging
    error_log("Error scheduling WO: " . $e->getMessage());
    
    // Redirect dengan pesan error
    header('Location: dashboard_dispatch.php?status=error_schedule');
    exit();
}

// Kembalikan autocommit ke true
mysqli_autocommit($conn, TRUE);
?>