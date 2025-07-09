<?php

include 'config/db_connect.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$wo_id = $_POST['wo_id'] ?? '';
$dispatch_notes = $_POST['dispatch_notes'] ?? '';
$dispatch_id = $_SESSION['user_id'] ?? null;

if (!$wo_id || !$dispatch_notes || !$dispatch_id) {
    header('Location: dashboard_dispatch.php?status=error_missing_data');
    exit();
}

$sql = "UPDATE tr_work_orders 
        SET status = 'Waiting For BOR Review', 
            dispatch_review_notes = ?, 
            reviewed_by_dispatch_id = ?, 
            reviewed_at = NOW()
        WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('sii', $dispatch_notes, $dispatch_id, $wo_id);
$stmt->execute();

header('Location: dashboard_dispatch.php?status=wo_closed');
exit();

?>