<?php
include 'config/db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'BOR') {
    header('Location: login.php');
    exit();
}

if (!isset($_GET['wo_id']) || !isset($_GET['decision']) || empty($_GET['wo_id']) || empty($_GET['decision'])) {
    header('Location: dashboard_bor.php?status=error');
    exit();
}

$wo_id = (int)$_GET['wo_id'];
$decision = $_GET['decision']; 
$bor_user_id = $_SESSION['user_id'];
$current_time = date('Y-m-d H:i:s');

if (!in_array($decision, ['approve', 'reject'])) {
    header('Location: dashboard_bor.php?status=error_invalid_decision');
    exit();
}

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

mysqli_autocommit($conn, FALSE);

try {

    $update_wo_sql = "UPDATE tr_work_orders SET 
                      status = 'Closed by BOR',
                      closed_by_bor_id = '$bor_user_id',
                      closed_at = '$current_time',
                      bor_decision = '$decision'
                      WHERE id = '$wo_id'";

    if (!mysqli_query($conn, $update_wo_sql)) {
        throw new Exception("Gagal update Work Order status: " . mysqli_error($conn));
    }

    $update_ticket_sql = "UPDATE tr_tickets SET 
                          status = '$final_ticket_status',
                          closed_at = '$current_time'
                          WHERE id = '{$wo_data['ticket_id']}'";
    
    if (!mysqli_query($conn, $update_ticket_sql)) {
        throw new Exception("Gagal update status ticket: " . mysqli_error($conn));
    }

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
    
    mysqli_query($conn, $customer_notification_sql);

    mysqli_commit($conn);
    
    $status_message = ($decision == 'approve') ? 'approved_and_closed' : 'rejected_and_closed';
    header('Location: dashboard_bor.php?status=' . $status_message);
    exit();

} catch (Exception $e) {
    mysqli_rollback($conn);
    
    error_log("Error BOR reviewing dispatch proof: " . $e->getMessage());
    mysqli_query($conn, $customer_notification_sql);
    header('Location: dashboard_bor.php?status=error_review');
    exit();
    }

mysqli_autocommit($conn, TRUE);
?>