<?php
include 'config/db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'Dispatch') {
    header('Location: login.php');
    exit();
}

if (!isset($_GET['wo_id']) || empty($_GET['wo_id'])) {
    header('Location: dashboard_dispatch.php');
    exit();
}

$wo_id = (int)$_GET['wo_id'];

$sql_wo = "SELECT 
    wo.id,
    wo.wo_code,
    wo.status,
    wo.scheduled_visit_date,
    wo.assigned_to_vendor_id,
    wo.visit_report,
    t.id as ticket_id,
    t.ticket_code,
    t.title as ticket_title,
    t.description,
    t.status as ticket_status,
    c.full_name as customer_name,
    c.address as customer_address,
    c.phone_number as customer_phone,
    u_bor.full_name as created_by_bor,
    u_vendor.full_name as current_vendor
FROM tr_work_orders wo
JOIN tr_tickets t ON wo.ticket_id = t.id
JOIN ms_customers c ON t.customer_id = c.id
JOIN ms_users u_bor ON wo.created_by_dispatch_id = u_bor.id
LEFT JOIN ms_users u_vendor ON wo.assigned_to_vendor_id = u_vendor.id
WHERE wo.id = '$wo_id'";

$result_wo = mysqli_query($conn, $sql_wo);

if (mysqli_num_rows($result_wo) == 0) {
    header('Location: dashboard_dispatch.php?status=wo_not_found');
    exit();
}

$wo = mysqli_fetch_assoc($result_wo);

$sql_vendors = "SELECT id, full_name FROM ms_users WHERE role = 'Vendor IKR' ORDER BY full_name";
$result_vendors = mysqli_query($conn, $sql_vendors);
$vendors = mysqli_fetch_all($result_vendors, MYSQLI_ASSOC);

$message = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'];
    
    if ($action == 'update') {
        $new_schedule = mysqli_real_escape_string($conn, $_POST['scheduled_visit_date']);
        $new_vendor = (int)$_POST['assigned_vendor'];
        $special_instructions = mysqli_real_escape_string($conn, $_POST['special_instructions']);
        $priority = mysqli_real_escape_string($conn, $_POST['priority']);
        
        if (empty($new_schedule) || empty($new_vendor)) {
            $message = '<div class="alert alert-error">Jadwal dan teknisi harus diisi!</div>';
        } else {
            $vendor_check = "SELECT full_name FROM ms_users WHERE id = '$new_vendor' AND role = 'Vendor IKR'";
            $vendor_result = mysqli_query($conn, $vendor_check);
            
            if (mysqli_num_rows($vendor_result) == 1) {
                $vendor_data = mysqli_fetch_assoc($vendor_result);
                
                mysqli_autocommit($conn, FALSE);
                
                try {
                    $update_sql = "UPDATE tr_work_orders SET 
                                   scheduled_visit_date = '$new_schedule',
                                   assigned_to_vendor_id = '$new_vendor'
                                   WHERE id = '$wo_id'";
                    
                    if (!mysqli_query($conn, $update_sql)) {
                        throw new Exception("Gagal update Work Order");
                    }
                    
                    $activity_desc = "Work Order dijadwalkan ulang untuk " . date('d/m/Y H:i', strtotime($new_schedule)) . 
                                   " dan di-assign ke " . $vendor_data['full_name'];
                    if (!empty($special_instructions)) {
                        $activity_desc .= ". Instruksi khusus: " . $special_instructions;
                    }
                    
                    $log_sql = "INSERT INTO tr_ticket_updates (ticket_id, user_id, update_type, description) 
                               VALUES ('{$wo['ticket_id']}', '{$_SESSION['user_id']}', 'Status Change', '$activity_desc')";
                    
                    if (!mysqli_query($conn, $log_sql)) {
                        throw new Exception("Gagal log activity");
                    }
                    
                    mysqli_commit($conn);
                    $message = '<div class="alert alert-success">Work Order berhasil diupdate!</div>';
                    
                    // Refresh data
                    $wo['scheduled_visit_date'] = $new_schedule;
                    $wo['assigned_to_vendor_id'] = $new_vendor;
                    $wo['current_vendor'] = $vendor_data['full_name'];
                    
                } catch (Exception $e) {
                    mysqli_rollback($conn);
                    $message = '<div class="alert alert-error">Gagal update: ' . $e->getMessage() . '</div>';
                }
                
                mysqli_autocommit($conn, TRUE);
            } else {
                $message = '<div class="alert alert-error">Teknisi tidak valid!</div>';
            }
        }
        
    } elseif ($action == 'cancel') {
        $cancel_reason = mysqli_real_escape_string($conn, $_POST['cancel_reason']);
        
        if (empty($cancel_reason)) {
            $message = '<div class="alert alert-error">Alasan pembatalan harus diisi!</div>';
        } else {
            mysqli_autocommit($conn, FALSE);
            
            try {
                $cancel_sql = "UPDATE tr_work_orders SET status = 'Cancelled' WHERE id = '$wo_id'";
                if (!mysqli_query($conn, $cancel_sql)) {
                    throw new Exception("Gagal cancel Work Order");
                }
                
                $ticket_sql = "UPDATE tr_tickets SET status = 'On Progress - BOR' WHERE id = '{$wo['ticket_id']}'";
                mysqli_query($conn, $ticket_sql);
                
                $log_sql = "INSERT INTO tr_ticket_updates (ticket_id, user_id, update_type, description) 
                           VALUES ('{$wo['ticket_id']}', '{$_SESSION['user_id']}', 'Status Change', 
                                   'Work Order dibatalkan oleh Dispatch. Alasan: $cancel_reason')";
                
                if (!mysqli_query($conn, $log_sql)) {
                    throw new Exception("Gagal log cancellation");
                }
                
                mysqli_commit($conn);
                
                header('Location: dashboard_dispatch.php?status=cancelled');
                exit();
                
            } catch (Exception $e) {
                mysqli_rollback($conn);
                $message = '<div class="alert alert-error">Gagal cancel: ' . $e->getMessage() . '</div>';
            }
            
            mysqli_autocommit($conn, TRUE);
        }
        
    } elseif ($action == 'postpone') {
        $postpone_reason = mysqli_real_escape_string($conn, $_POST['postpone_reason']);
        
        if (empty($postpone_reason)) {
            $message = '<div class="alert alert-error">Alasan penundaan harus diisi!</div>';
        } else {
            mysqli_autocommit($conn, FALSE);
            
            try {
                $postpone_sql = "UPDATE tr_work_orders SET 
                                status = 'Pending',
                                scheduled_visit_date = NULL
                                WHERE id = '$wo_id'";
                
                if (!mysqli_query($conn, $postpone_sql)) {
                    throw new Exception("Gagal postpone Work Order");
                }
                
                $log_sql = "INSERT INTO tr_ticket_updates (ticket_id, user_id, update_type, description) 
                           VALUES ('{$wo['ticket_id']}', '{$_SESSION['user_id']}', 'Status Change', 
                                   'Work Order ditunda oleh Dispatch. Alasan: $postpone_reason')";
                
                if (!mysqli_query($conn, $log_sql)) {
                    throw new Exception("Gagal log postponement");
                }
                
                mysqli_commit($conn);
                $message = '<div class="alert alert-success">⏳ Work Order berhasil ditunda dan akan dijadwalkan ulang.</div>';
                
                $wo['status'] = 'Pending';
                $wo['scheduled_visit_date'] = null;
                
            } catch (Exception $e) {
                mysqli_rollback($conn);
                $message = '<div class="alert alert-error">Gagal postpone: ' . $e->getMessage() . '</div>';
            }
            
            mysqli_autocommit($conn, TRUE);
        }
    }
}

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Edit Work Order - <?php echo htmlspecialchars($wo['wo_code']); ?></title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

    <header class="main-header">
        <div class="container">
            <h1>Edit Work Order - Full Management</h1>
            <div class="user-info">
                <span>Selamat datang, <strong><?php echo htmlspecialchars($_SESSION['user_full_name']); ?></strong>!</span>
                <span class="user-role user-role-dispatch">[<?php echo htmlspecialchars($_SESSION['user_role']); ?>]</span>
                <a href="logout.php" class="btn-logout">Logout</a>
            </div>
        </div>
    </header>

    <main class="container">
        
        <div class="back-button-section">
            <a href="dashboard_dispatch.php" class="btn-back">← Kembali ke Dashboard</a>
            <a href="view_work_order.php?id=<?php echo $wo['id']; ?>" class="btn-back" style="background-color: #17a2b8; margin-left: 10px;">👁 View Detail WO</a>
        </div>

        <?php echo $message; ?>

        <div class="card" style="margin-bottom: 20px;">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <h3 style="margin: 0;">🛠️ <?php echo htmlspecialchars($wo['wo_code']); ?></h3>
                    <p style="margin: 5px 0; color: #666;">
                        Ticket: <strong><?php echo htmlspecialchars($wo['ticket_code']); ?></strong> | 
                        Customer: <strong><?php echo htmlspecialchars($wo['customer_name']); ?></strong>
                    </p>
                </div>
                <div>
                    <?php 
                    $status_color = '';
                    switch($wo['status']) {
                        case 'Pending': $status_color = 'background-color: #ffc107; color: #212529;'; break;
                        case 'Scheduled': $status_color = 'background-color: #17a2b8; color: white;'; break;
                        case 'Completed': $status_color = 'background-color: #28a745; color: white;'; break;
                        case 'Cancelled': $status_color = 'background-color: #dc3545; color: white;'; break;
                    }
                    ?>
                    <span class="status" style="<?php echo $status_color; ?>; font-size: 14px; padding: 8px 16px;">
                        <?php echo htmlspecialchars($wo['status']); ?>
                    </span>
                </div>
            </div>
        </div>

        <div class="dashboard-grid">
            
            <div class="main-action-column">
                <section class="card">
                    <h3>Update Schedule & Assignment</h3>
                    
                    <form method="POST">
                        <input type="hidden" name="action" value="update">
                        
                        <div class="form-group">
                            <label for="scheduled_visit_date">Jadwal Kunjungan</label>
                            <input type="datetime-local" id="scheduled_visit_date" name="scheduled_visit_date" 
                                   value="<?php echo $wo['scheduled_visit_date'] ? date('Y-m-d\TH:i', strtotime($wo['scheduled_visit_date'])) : ''; ?>" 
                                   required>
                        </div>
                        
                        <div class="form-group">
                            <label for="assigned_vendor">👨‍🔧 Assign Teknisi</label>
                            <select id="assigned_vendor" name="assigned_vendor" required>
                                <option value="">-- Pilih Teknisi --</option>
                                <?php foreach ($vendors as $vendor): ?>
                                    <option value="<?php echo $vendor['id']; ?>" 
                                            <?php echo ($wo['assigned_to_vendor_id'] == $vendor['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($vendor['full_name']); ?>
                                        <?php echo ($wo['assigned_to_vendor_id'] == $vendor['id']) ? ' (Current)' : ''; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="priority">⚡ Priority Level</label>
                            <select id="priority" name="priority">
                                <option value="Normal">Normal</option>
                                <option value="High">High Priority</option>
                                <option value="Urgent">Urgent</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="special_instructions">Instruksi Khusus</label>
                            <textarea id="special_instructions" name="special_instructions" rows="4" 
                                      placeholder="Catatan khusus untuk teknisi (opsional)&#10;Contoh: &#10;- Bawa peralatan khusus untuk fiber optic&#10;- Customer di lantai 3, apartemen blok B&#10;- Hubungi customer 30 menit sebelum datang"></textarea>
                        </div>
                        
                        <button type="submit" class="btn" style="background-color: #28a745;">
                            Update Work Order
                        </button>
                    </form>
                </section>
            </div>

            <div class="ticket-list-column">
                
                <section class="card" style="margin-bottom: 20px;">
                    <h3>Current Status</h3>

                    <div class="info-row">
                        <label>Status WO:</label>
                        <span class="status" style="<?php echo $status_color; ?>">
                            <?php echo htmlspecialchars($wo['status']); ?>
                        </span>
                    </div>
                    
                    <?php if ($wo['scheduled_visit_date']): ?>
                    <div class="info-row">
                        <label>Scheduled:</label>
                        <span style="font-weight: 500; color: #17a2b8;">
                            <?php echo date('d/m/Y H:i', strtotime($wo['scheduled_visit_date'])); ?>
                        </span>
                    </div>
                    <?php endif; ?>
                    
                    <div class="info-row">
                        <label>Assigned To:</label>
                        <?php if ($wo['current_vendor']): ?>
                            <span style="font-weight: 500; color: #28a745;">
                                <?php echo htmlspecialchars($wo['current_vendor']); ?>
                            </span>
                        <?php else: ?>
                            <span style="color: #ffc107;">Belum di-assign</span>
                        <?php endif; ?>
                    </div>
                    
                    <div class="info-row">
                        <label>Customer:</label>
                        <span><?php echo htmlspecialchars($wo['customer_name']); ?></span>
                        <br><small style="color: #666;"><?php echo htmlspecialchars($wo['customer_phone']); ?></small>
                    </div>
                </section>

                <section class="card" style="margin-bottom: 20px;">
                    <h3>Postpone Work Order</h3>
                    <p style="font-size: 14px; color: #666; margin-bottom: 15px;">
                        Tunda jadwal kunjungan dan ubah status kembali ke Pending untuk dijadwalkan ulang nanti.
                    </p>
                    
                    <form method="POST" onsubmit="return confirm('Yakin ingin menunda Work Order ini?')">
                        <input type="hidden" name="action" value="postpone">
                        
                        <div class="form-group">
                            <label for="postpone_reason">Alasan Penundaan</label>
                            <textarea id="postpone_reason" name="postpone_reason" rows="3" 
                                      placeholder="Contoh: Customer request reschedule, Teknisi sakit, Peralatan belum ready" required></textarea>
                        </div>
                        
                        <button type="submit" class="btn" style="background-color: #ffc107; color: #212529;">
                            Postpone WO
                        </button>
                    </form>
                </section>

                <section class="card">
                    <h3>Cancel Work Order</h3>
                    <p style="font-size: 14px; color: #dc3545; margin-bottom: 15px;">
                        <strong>Perhatian:</strong> Pembatalan akan mengembalikan ticket ke BOR dan menandai WO sebagai cancelled.
                    </p>
                    
                    <form method="POST" onsubmit="return confirm('YAKIN ingin membatalkan Work Order ini? Aksi ini tidak bisa dibatalkan!')">
                        <input type="hidden" name="action" value="cancel">
                        
                        <div class="form-group">
                            <label for="cancel_reason">Alasan Pembatalan</label>
                            <textarea id="cancel_reason" name="cancel_reason" rows="3" 
                                      placeholder="Contoh: Customer batal layanan, Masalah sudah teratasi sendiri, Duplikasi WO" required></textarea>
                        </div>
                        
                        <button type="submit" class="btn" style="background-color: #dc3545;">
                            Cancel Work Order
                        </button>
                    </form>
                </section>

            </div>

        </div>
    </main>

    <style>
        .user-role-dispatch {
            background-color: #17a2b8 !important;
        }
        
        .info-row {
            display: flex;
            margin-bottom: 10px;
            align-items: flex-start;
        }
        
        .info-row label {
            min-width: 100px;
            font-weight: 600;
            color: #495057;
            margin-right: 10px;
            flex-shrink: 0;
            font-size: 14px;
        }
        
        .alert {
            padding: 15px 20px;
            margin-bottom: 20px;
            border-radius: 8px;
            font-weight: 500;
            border: 1px solid;
        }
        
        .alert-success {
            background-color: #d1fae5;
            color: #065f46;
            border-color: #a7f3d0;
        }
        
        .alert-error {
            background-color: #fecaca;
            color: #991b1b;
            border-color: #fca5a5;
        }
    </style>

</body>
</html>