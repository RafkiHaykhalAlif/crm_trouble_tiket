<?php
include 'config/db_connect.php';
session_start();

$wo_id = intval($_POST['wo_id']);
$pending_reason = mysqli_real_escape_string($conn, $_POST['pending_reason']);
$user_id = $_SESSION['user_id'];

if ($wo_id && $pending_reason) {
    $sql = "UPDATE tr_work_orders SET status='Pending', pending_reason='$pending_reason', pending_by='$user_id', pending_at=NOW() WHERE id='$wo_id'";
    if (mysqli_query($conn, $sql)) {
        header("Location: dashboard_vendor.php?status=wo_pending");
        exit();
    } else {
        header("Location: dashboard_vendor.php?status=error_pending");
        exit();
    }
} else {
    header("Location: dashboard_vendor.php?status=error_missing_data");
    exit();
}
?>