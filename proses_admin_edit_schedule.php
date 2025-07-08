<?php
// filepath: d:\XAMPP\htdocs\crm_trouble_tiket\proses_admin_edit_schedule.php
include 'config/db_connect.php';
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'Admin IKR') {
    header('Location: login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: dashboard_admin_ikr.php?status=error');
    exit();
}

$wo_id = (int)$_POST['wo_id'];
$visit_date = mysqli_real_escape_string($conn, $_POST['visit_date']);
$assigned_vendor = (int)$_POST['assigned_vendor'];
$special_notes = mysqli_real_escape_string($conn, $_POST['special_notes'] ?? '');

$sql = "UPDATE tr_work_orders SET 
            scheduled_visit_date = '$visit_date',
            assigned_to_vendor_id = '$assigned_vendor'
        WHERE id = '$wo_id'";

if (mysqli_query($conn, $sql)) {
    header('Location: dashboard_admin_ikr.php?status=scheduled');
} else {
    header('Location: dashboard_admin_ikr.php?status=error');
}
exit();
?>