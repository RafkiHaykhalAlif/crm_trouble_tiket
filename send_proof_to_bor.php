<?php
session_start();
include 'config/db_connect.php';

$wo_id = $_GET['wo_id'] ?? '';

if(empty($wo_id)) {
    header("Location: dashboard_dispatch.php?status=error&message=WO ID tidak valid");
    exit();
}

try {
    $sql = "UPDATE tr_work_orders SET 
            status = 'Waiting For BOR Review',
            reviewed_at = NOW()
            WHERE id = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $wo_id);  
    
    if($stmt->execute()) {
        if($stmt->affected_rows > 0) {
            header("Location: dashboard_dispatch.php?status=success&message=WO berhasil dikirim ke BOR");
        } else {
            header("Location: dashboard_dispatch.php?status=error&message=WO tidak ditemukan");
        }
    } else {
        header("Location: dashboard_dispatch.php?status=error&message=Database error: " . $conn->error);
    }
    
} catch(Exception $e) {
    header("Location: dashboard_dispatch.php?status=error&message=" . $e->getMessage());
}
?>