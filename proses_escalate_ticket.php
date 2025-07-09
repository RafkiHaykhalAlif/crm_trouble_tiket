<?php
include 'config/db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

if (!isset($_GET['ticket_id']) || empty($_GET['ticket_id'])) {
    header('Location: dashboard.php?status=error');
    exit();
}

$ticket_id = (int)$_GET['ticket_id'];
$user_id = $_SESSION['user_id'];

$check_sql = "SELECT id, status, ticket_code FROM tr_tickets WHERE id = '$ticket_id' AND (status = 'Open' OR status = 'On Progress - Customer Care')";
$check_result = mysqli_query($conn, $check_sql);

if (mysqli_num_rows($check_result) == 0) {
    header('Location: dashboard.php?status=error_invalid_ticket');
    exit();
}

$ticket_data = mysqli_fetch_assoc($check_result);

$bor_sql = "SELECT id FROM ms_users WHERE role = 'BOR' LIMIT 1";
$bor_result = mysqli_query($conn, $bor_sql);

if (mysqli_num_rows($bor_result) == 0) {
    header('Location: dashboard.php?status=error_no_bor');
    exit();
}

$bor_user = mysqli_fetch_assoc($bor_result);
$bor_user_id = $bor_user['id'];

mysqli_autocommit($conn, FALSE);

try {
    $update_ticket_sql = "UPDATE tr_tickets SET 
                          status = 'On Progress - BOR',
                          current_owner_user_id = '$bor_user_id'
                          WHERE id = '$ticket_id'";
    
    if (!mysqli_query($conn, $update_ticket_sql)) {
        throw new Exception("Gagal update status ticket");
    }

    $insert_update_sql = "INSERT INTO tr_ticket_updates (ticket_id, user_id, update_type, description) 
                          VALUES ('$ticket_id', '$user_id', 'Escalation', 'Ticket di-escalate dari Customer Care ke BOR')";
    
    if (!mysqli_query($conn, $insert_update_sql)) {
        throw new Exception("Gagal mencatat aktivitas escalation");
    }

    mysqli_commit($conn);
    
    header('Location: dashboard.php?status=escalated');
    exit();

} catch (Exception $e) {
    mysqli_rollback($conn);
    
    error_log("Error escalating ticket: " . $e->getMessage());
    
    header('Location: dashboard.php?status=error_escalate');
    exit();
}

mysqli_autocommit($conn, TRUE);
?>