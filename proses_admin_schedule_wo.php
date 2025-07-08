<?php
include 'config/db_connect.php';

// Cek apakah user sudah login dan adalah Admin IKR
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'Admin IKR') {
    header('Location: login.php');
    exit();
}

// Cek apakah form di-submit dengan method POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: dashboard_admin_ikr.php');
    exit();
}

// Ambil data dari form
$wo_id = (int)$_POST['wo_id'];
$visit_date = mysqli_real_escape_string($conn, $_POST['visit_date']);
$assigned_vendor = (int)$_POST['assigned_vendor'];
$special_notes = mysqli_real_escape_string($conn, $_POST['special_notes']);
$admin_user_id = $_SESSION['user_id'];

// Validasi input
if (empty($wo_id) || empty($visit_date) || empty($assigned_vendor)) {
    header('Location: dashboard_admin_ikr.php?status=error_missing_data');
    exit();
}

// Validasi: Pastikan WO exists dan statusnya "Received by Admin IKR"
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
WHERE wo.id = '$wo_id' AND wo.status IN ('Received by Admin IKR', 'Pending')";

$check_result = mysqli_query($conn, $check_sql);

if (mysqli_num_rows($check_result) == 0) {
    header('Location: dashboard_admin_ikr.php?status=error_invalid_wo');
    exit();
}

$wo_data = mysqli_fetch_assoc($check_result);

// Validasi: Pastikan vendor exists dan role-nya Vendor IKR
$vendor_check_sql = "SELECT id, full_name FROM ms_users 
                     WHERE id = '$assigned_vendor' AND role = 'Vendor IKR'";
$vendor_result = mysqli_query($conn, $vendor_check_sql);

if (mysqli_num_rows($vendor_result) == 0) {
    header('Location: dashboard_admin_ikr.php?status=error_invalid_vendor');
    exit();
}

$vendor_data = mysqli_fetch_assoc($vendor_result);

// Mulai transaksi database
mysqli_autocommit($conn, FALSE);

try {
    // 1. Update Work Order dengan jadwal, assign vendor, dan ubah status
    $update_wo_sql = "UPDATE tr_work_orders SET 
        status = 'Scheduled by Admin IKR',
        scheduled_visit_date = '$visit_date',
        assigned_to_vendor_id = '$assigned_vendor',
        admin_notes = '$special_notes',
        scheduled_by_admin_id = '$admin_user_id',
        scheduled_at = NOW()
        WHERE id = '$wo_id'";

    if (!mysqli_query($conn, $update_wo_sql)) {
        throw new Exception("Gagal update Work Order: " . mysqli_error($conn));
    }

    // 2. Tambahkan record ke tr_ticket_updates untuk tracking
    $activity_desc = "Work Order dijadwalkan oleh Admin IKR untuk kunjungan pada " . 
                     date('d/m/Y H:i', strtotime($visit_date)) . 
                     " dan di-assign ke teknisi " . $vendor_data['full_name'];
    
    if (!empty($special_notes)) {
        $activity_desc .= ". Instruksi khusus: " . substr($special_notes, 0, 100);
    }
    
    $insert_update_sql = "INSERT INTO tr_ticket_updates (ticket_id, user_id, update_type, description) 
                          VALUES ('{$wo_data['ticket_id']}', '$admin_user_id', 'Status Change', '$activity_desc')";
    
    if (!mysqli_query($conn, $insert_update_sql)) {
        throw new Exception("Gagal mencatat aktivitas scheduling: " . mysqli_error($conn));
    }

        // Jika
        // (Tambahkan logika lanjutan di sini jika diperlukan)
    }
    catch (Exception $e) {
        mysqli_rollback($conn);
        header('Location: dashboard_admin_ikr.php?status=error&message=' . urlencode($e->getMessage()));
        exit();
    }
    
    mysqli_commit($conn);
    header('Location: dashboard_admin_ikr.php?status=success');
    exit();