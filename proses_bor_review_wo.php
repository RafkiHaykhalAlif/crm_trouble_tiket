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

if (!in_array($decision, ['solved', 'unsolved'])) {
    header('Location: dashboard_bor.php?status=error_invalid_decision');
    exit();
}

$check_sql = "SELECT 
    wo.id,
    wo.wo_code,
    wo.ticket_id,
    wo.status as wo_status,
    wo.visit_report,
    t.ticket_code,
    t.status as ticket_status,
    t.current_owner_user_id,
    c.full_name as customer_name,
    u_vendor.full_name as technician_name
FROM tr_work_orders wo
JOIN tr_tickets t ON wo.ticket_id = t.id
JOIN ms_customers c ON t.customer_id = c.id
LEFT JOIN ms_users u_vendor ON wo.assigned_to_vendor_id = u_vendor.id
WHERE wo.id = '$wo_id' 
AND wo.status = 'Completed'
AND t.status = 'Waiting for BOR Review'";

$check_result = mysqli_query($conn, $check_sql);

if (mysqli_num_rows($check_result) == 0) {
    header('Location: dashboard_bor.php?status=error_invalid_wo_review');
    exit();
}

$wo_data = mysqli_fetch_assoc($check_result);

$final_ticket_status = ($decision === 'solved') ? 'Closed - Solved' : 'Closed - Unsolved';

mysqli_autocommit($conn, FALSE);

try {
    $update_ticket_sql = "UPDATE tr_tickets SET 
                          status = '$final_ticket_status',
                          closed_at = '$current_time
                          WHERE id = '{$wo_data['ticket_id']}'";
    
    if (!mysqli_query($conn, $update_ticket_sql)) {
        throw new Exception("Gagal update status ticket: " . mysqli_error($conn));
    }

    $review_description = '';
    if ($decision === 'solved') {
        $review_description = "BOR telah mereview Work Order {$wo_data['wo_code']} dan menyetujui bahwa masalah telah berhasil diselesaikan oleh teknisi {$wo_data['technician_name']}. Ticket ditutup sebagai SOLVED.";
    } else {
        $review_description = "BOR telah mereview Work Order {$wo_data['wo_code']} dan menilai bahwa masalah belum terselesaikan dengan baik. Ticket ditutup sebagai UNSOLVED.";
    }
    
    $insert_review_sql = "INSERT INTO tr_ticket_updates (ticket_id, user_id, update_type, description) 
                          VALUES ('{$wo_data['ticket_id']}', '$bor_user_id', 'Status Change', '$review_description')";
    
    if (!mysqli_query($conn, $insert_review_sql)) {
        throw new Exception("Gagal mencatat aktivitas review BOR: " . mysqli_error($conn));
    }

    $bor_review_note = ($decision === 'solved') ? 'Approved by BOR - Problem resolved' : 'Reviewed by BOR - Problem not fully resolved';
    
    $update_wo_sql = "UPDATE tr_work_orders SET 
                      visit_report = CONCAT(visit_report, '\n\n--- BOR REVIEW ---\n', '$bor_review_note')
                      WHERE id = '$wo_id'";
    
    mysqli_query($conn, $update_wo_sql); 
    mysqli_commit($conn);
    
    if ($decision === 'solved') {
        header('Location: dashboard_bor.php?status=reviewed_solved');
    } else {
        header('Location: dashboard_bor.php?status=reviewed_unsolved');
    }
    exit();

} catch (Exception $e) {
    mysqli_rollback($conn);
    
    error_log("Error BOR reviewing WO: " . $e->getMessage());
    
    header('Location: dashboard_bor.php?status=error_review');
    exit();
}

mysqli_autocommit($conn, TRUE);
?>