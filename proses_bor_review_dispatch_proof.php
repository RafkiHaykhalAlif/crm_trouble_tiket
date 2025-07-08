<?php
include 'config/db_connect.php';

// Cek apakah user sudah login dan adalah BOR
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'BOR') {
    header('Location: login.php');
    exit();
}

// Cek parameter
if (!isset($_GET['wo_id']) || !isset($_GET['decision']) || empty($_GET['wo_id']) || empty($_GET['decision'])) {
    header('Location: dashboard_bor.php?status=error');
    exit();
}

$wo_id = (int)$_GET['wo_id'];
$decision = $_GET['decision']; // 'approve' atau 'reject'
$bor_user_id = $_SESSION['user_id'];
$current_time = date('Y-m-d H:i:s');

// Validasi decision
if (!in_array($decision, ['approve', 'reject'])) {
    header('Location: dashboard_bor.php?status=error_invalid_decision');
    exit();
}

// Validasi: Pastikan WO exists dan statusnya "Reviewed by Dispatch"
$check_sql = "SELECT 
    wo.id, 
    wo.wo_code, 
    wo.ticket_id,
    wo.status,
    wo.ticket_resolution_status,
    wo.dispatch_review_notes,
    t.ticket_code,
    t.status as ticket_status,
    c.full_name as customer_name,
    dr.bor_summary,
    dr.work_quality
FROM tr_work_orders wo
JOIN tr_tickets t ON wo.ticket_id = t.id
JOIN ms_customers c ON t.customer_id = c.id
LEFT JOIN tr_dispatch_reviews dr ON wo.id = dr.work_order_id
WHERE wo.id = '$wo_id' AND wo.status = 'Reviewed by Dispatch'";

$check_result = mysqli_query($conn, $check_sql);

if (mysqli_num_rows($check_result) == 0) {
    header('Location: dashboard_bor.php?status=error_invalid_wo');
    exit();
}

$wo_data = mysqli_fetch_assoc($check_result);

// Tentukan status ticket berdasarkan decision dan resolution status
$final_ticket_status = '';
if ($decision == 'approve') {
    if ($wo_data['ticket_resolution_status'] == 'Fully Resolved') {
        $final_ticket_status = 'Closed - Solved';
    } else {
        $final_ticket_status = 'Closed - Unsolved';
    }
} else {
    $final_ticket_status = 'Closed - Unsolved';
}

// Mulai transaksi database
mysqli_autocommit($conn, FALSE);

try {
    // 1. Update Work Order status ke "Closed by BOR"
    $update_wo_sql = "UPDATE tr_work_orders SET 
                      status = 'Closed by BOR',
                      closed_by_bor_id = '$bor_user_id',
                      closed_at = '$current_time',
                      bor_decision = '$decision'
                      WHERE id = '$wo_id'";
    
    if (!mysqli_query($conn, $update_wo_sql)) {
        throw new Exception("Gagal update Work Order status: " . mysqli_error($conn));
    }

    // 2. Update status ticket ke final status
    $update_ticket_sql = "UPDATE tr_tickets SET 
                          status = '$final_ticket_status',
                          closed_at = '$current_time'
                          WHERE id = '{$wo_data['ticket_id']}'";
    
    if (!mysqli_query($conn, $update_ticket_sql)) {
        throw new Exception("Gagal update status ticket: " . mysqli_error($conn));
    }

    // 3. Log activity ke tr_ticket_updates
    $activity_desc = '';
    if ($decision == 'approve') {
        $activity_desc = "BOR menyetujui hasil kerja teknisi. ";
        $activity_desc .= "Work Order {$wo_data['wo_code']} disetujui dan ticket ditutup sebagai ";
        $activity_desc .= ($final_ticket_status == 'Closed - Solved') ? 'SOLVED' : 'UNSOLVED';
        $activity_desc .= ". Kualitas kerja: {$wo_data['work_quality']}.";
    } else {
        $activity_desc = "BOR menolak hasil kerja teknisi. ";
        $activity_desc .= "Work Order {$wo_data['wo_code']} ditolak dan ticket ditutup sebagai UNSOLVED. ";
        $activity_desc .= "Diperlukan follow-up lebih lanjut.";
    }
    
    $insert_update_sql = "INSERT INTO tr_ticket_updates (ticket_id, user_id, update_type, description) 
                          VALUES ('{$wo_data['ticket_id']}', '$bor_user_id', 'Status Change', '$activity_desc')";
    
    if (!mysqli_query($conn, $insert_update_sql)) {
        throw new Exception("Gagal mencatat aktivitas BOR review: " . mysqli_error($conn));
    }

    // 4. Insert BOR final review
    $insert_bor_review_sql = "INSERT INTO tr_bor_final_reviews (
        work_order_id,
        ticket_id,
        bor_user_id,
        decision,
        final_ticket_status,
        review_notes,
        created_at
    ) VALUES (
        '$wo_id',
        '{$wo_data['ticket_id']}',
        '$bor_user_id',
        '$decision',
        '$final_ticket_status',
        'BOR review based on Dispatch proof and technician work quality assessment',
        '$current_time'
    )";
    
    if (!mysqli_query($conn, $insert_bor_review_sql)) {
        throw new Exception("Gagal insert BOR final review: " . mysqli_error($conn));
    }

    // 5. (Optional) Update customer notification status
    $customer_notification_sql = "INSERT INTO customer_notifications (
        ticket_id,
        customer_message,
        notification_type,
        created_at
    ) VALUES (
        '{$wo_data['ticket_id']}',
        'Your service request {$wo_data['ticket_code']} has been " . ($decision == 'approve' ? 'resolved' : 'closed') . ". Thank you for using our service.',
        'ticket_closure',
        '$current_time'
    )";
    
    // Ini optional, jadi tidak throw error jika gagal
    mysqli_query($conn, $customer_notification_sql);

    // Commit transaksi
    mysqli_commit($conn);
    
    // Redirect dengan pesan sukses
    $status_message = ($decision == 'approve') ? 'approved_and_closed' : 'rejected_and_closed';
    header('Location: dashboard_bor.php?status=' . $status_message);
    exit();

} catch (Exception $e) {
    // Rollback jika ada error
    mysqli_rollback($conn);
    
    // Log error untuk debugging
    error_log("Error BOR reviewing dispatch proof: " . $e->getMessage());
    
    // Redirect dengan pesan error
    header('Location: dashboard_bor.php?status=error_review');
    exit();
}

// Kembalikan autocommit ke true
mysqli_autocommit($conn, TRUE);
?>