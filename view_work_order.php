<?php
include 'config/db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: dashboard_dispatch.php');
    exit();
}

$wo_id = (int)$_GET['id'];

$sql_wo_detail = "SELECT 
    wo.id,
    wo.wo_code,
    wo.status as wo_status,
    wo.scheduled_visit_date,
    wo.visit_report,
    t.id as ticket_id,
    t.ticket_code,
    t.jenis_tiket,
    t.title as ticket_title,
    t.description as ticket_description,
    t.status as ticket_status,
    t.created_at as ticket_created,
    c.customer_id_number,
    c.full_name as customer_name,
    c.address as customer_address,
    c.provinsi,
    c.kota,
    c.phone_number as customer_phone,
    c.email as customer_email,
    u_creator.full_name as created_by_name,
    u_creator.role as creator_role,
    u_vendor.full_name as assigned_vendor_name,
    u_vendor.id as vendor_id
FROM tr_work_orders wo
JOIN tr_tickets t ON wo.ticket_id = t.id
JOIN ms_customers c ON t.customer_id = c.id
JOIN ms_users u_creator ON wo.created_by_dispatch_id = u_creator.id
LEFT JOIN ms_users u_vendor ON wo.assigned_to_vendor_id = u_vendor.id
WHERE wo.id = '$wo_id'";

$result_wo = mysqli_query($conn, $sql_wo_detail);

if (mysqli_num_rows($result_wo) == 0) {
    header('Location: dashboard_dispatch.php?status=wo_not_found');
    exit();
}

$wo = mysqli_fetch_assoc($result_wo);

$sql_updates = "SELECT 
    tu.update_type,
    tu.description,
    tu.created_at,
    u.full_name as user_name,
    u.role as user_role
FROM tr_ticket_updates tu
JOIN ms_users u ON tu.user_id = u.id
WHERE tu.ticket_id = '{$wo['ticket_id']}'
ORDER BY tu.created_at DESC";

$result_updates = mysqli_query($conn, $sql_updates);
$updates = mysqli_fetch_all($result_updates, MYSQLI_ASSOC);

function formatTanggalIndonesia($datetime) {
    if (!$datetime) return '-';
    
    $tanggal = new DateTime($datetime);
    $bulan = [
        1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
        5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
        9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
    ];
    
    $hari = $tanggal->format('d');
    $bulan_nama = $bulan[(int)$tanggal->format('m')];
    $tahun = $tanggal->format('Y');
    $jam = $tanggal->format('H:i');
    
    return "$hari $bulan_nama $tahun pukul $jam";
}

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Detail Work Order - <?php echo htmlspecialchars($wo['wo_code']); ?></title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

    <header class="main-header">
        <div class="container">
            <h1>Detail Work Order</h1>
            <div class="user-info">
                <span>Selamat datang, <strong><?php echo htmlspecialchars($_SESSION['user_full_name']); ?></strong>!</span>
                <span class="user-role <?php echo $_SESSION['user_role'] === 'Dispatch' ? 'user-role-dispatch' : ($_SESSION['user_role'] === 'BOR' ? 'user-role-bor' : ''); ?>">
                    [<?php echo htmlspecialchars($_SESSION['user_role']); ?>]
                </span>
                <a href="logout.php" class="btn-logout">Logout</a>
            </div>
        </div>
    </header>

    <main class="container">
        
        <div class="back-button-section">
            <?php 
            $back_url = 'dashboard.php';
            if ($_SESSION['user_role'] === 'Dispatch') {
                $back_url = 'dashboard_dispatch.php';
            } elseif ($_SESSION['user_role'] === 'BOR') {
                $back_url = 'dashboard_bor.php';
            } elseif ($_SESSION['user_role'] === 'Vendor IKR') {
                $back_url = 'dashboard_vendor.php';
            }
            ?>
            <a href="<?php echo $back_url; ?>" class="btn-back">← Kembali ke Dashboard</a>
        </div>

        <div class="ticket-detail-grid">
            
            <section class="card ticket-info-card">
                <div class="card-header">
                    <h3>Informasi Work Order</h3>
                    <div class="ticket-status-badge">
                        <?php 
                        $status_color = '';
                        switch($wo['wo_status']) {
                            case 'Pending': $status_color = 'background-color: #ffc107; color: #212529;'; break;
                            case 'Scheduled': $status_color = 'background-color: #17a2b8; color: white;'; break;
                            case 'Completed': $status_color = 'background-color: #28a745; color: white;'; break;
                            case 'Cancelled': $status_color = 'background-color: #dc3545; color: white;'; break;
                            default: $status_color = 'background-color: #6c757d; color: white;';
                        }
                        ?>
                        <span class="status" style="<?php echo $status_color; ?>">
                            <?php echo htmlspecialchars($wo['wo_status']); ?>
                        </span>
                    </div>
                </div>
                
                <div class="ticket-info">
                    <div class="info-row">
                        <label>WO Code:</label>
                        <strong style="color: #17a2b8;"><?php echo htmlspecialchars($wo['wo_code']); ?></strong>
                    </div>
                    <div class="info-row">
                        <label>Related Ticket:</label>
                        <a href="view_ticket.php?id=<?php echo $wo['ticket_id']; ?>" style="color: #007bff; text-decoration: none;">
                            <?php echo htmlspecialchars($wo['ticket_code']); ?>
                        </a>
                    </div>
                    <div class="info-row">
                        <label>Jenis Work Order:</label>
                        <span style="font-weight: 500; color:rgb(0, 0, 0);">
                        <?php echo htmlspecialchars($wo['jenis_tiket']); ?>
                        </span>
                    </div>
                    <div class="info-row">
                        <label>Jenis Masalah:</label>
                        <span style="font-weight: 500;"><?php echo htmlspecialchars($wo['ticket_title']); ?></span>
                    </div>
                    <div class="info-row">
                        <label>Deskripsi Masalah:</label>
                        <div class="description-box">
                            <?php echo nl2br(htmlspecialchars($wo['ticket_description'])); ?>
                        </div>
                    </div>
                    <div class="info-row">
                        <label>Created By:</label>
                        <span><?php echo htmlspecialchars($wo['created_by_name']); ?> 
                              <small style="color: #64748b;">(<?php echo htmlspecialchars($wo['creator_role']); ?>)</small>
                        </span>
                    </div>
                    <div class="info-row">
                        <label>Assigned Technician:</label>
                        <?php if ($wo['assigned_vendor_name']): ?>
                            <span style="font-weight: 500; color: #28a745;">
                                <?php echo htmlspecialchars($wo['assigned_vendor_name']); ?>
                            </span>
                        <?php else: ?>
                            <span style="color: #ffc107;"> Belum di-assign</span>
                        <?php endif; ?>
                    </div>
                    <?php if ($wo['scheduled_visit_date']): ?>
                    <div class="info-row">
                        <label>Scheduled Visit:</label>
                        <span style="font-weight: 500; color: #17a2b8;">
                            <?php echo formatTanggalIndonesia($wo['scheduled_visit_date']); ?>
                        </span>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($wo['visit_report']): ?>
                    <div class="info-row">
                        <label>Visit Report:</label>
                        <div class="description-box" style="border-left-color: #28a745;">
                            <?php echo nl2br(htmlspecialchars($wo['visit_report'])); ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>

                <?php if ($_SESSION['user_role'] === 'Dispatch'): ?>
                <div class="ticket-actions">
                    <?php if ($wo['wo_status'] == 'Pending'): ?>
                        <button onclick="scheduleWO(<?php echo $wo['id']; ?>)" class="btn-action btn-schedule" style="background-color: #17a2b8; color: white;">
                            Schedule WO
                        </button>
                        <a href="assign_technician.php?wo_id=<?php echo $wo['id']; ?>" class="btn-action btn-assign" style="background-color: #28a745; color: white; text-decoration: none;">
                            Assign Technician
                        </a>
                    <?php elseif ($wo['wo_status'] == 'Scheduled'): ?>
                        <button onclick="editSchedule(<?php echo $wo['id']; ?>)" class="btn-action btn-edit" style="background-color: #ffc107; color: #212529;">
                            Edit Schedule
                        </button>
                        <a href="assign_technician.php?wo_id=<?php echo $wo['id']; ?>" class="btn-action btn-assign" style="background-color: #28a745; color: white; text-decoration: none;">
                            Reassign
                        </a>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </section>

            <section class="card customer-info-card">
                <div class="card-header">
                    <h3>Informasi Customer</h3>
                </div>
                <div class="customer-info">
                    <div class="info-row">
                        <label>Customer ID:</label>
                        <strong style="color: #17a2b8;"><?php echo htmlspecialchars($wo['customer_id_number']); ?></strong>
                    </div>
                    <div class="info-row">
                        <label>Nama Lengkap:</label>
                        <span style="font-weight: 500;"><?php echo htmlspecialchars($wo['customer_name']); ?></span>
                    </div>
                    <div class="info-row">
                        <label>Email:</label>
                        <span><?php echo htmlspecialchars($wo['customer_email']); ?></span>
                    </div>
                    <div class="info-row">
                        <label>No. Telepon:</label>
                        <span style="font-weight: 500; color: #28a745;">
                            <?php echo htmlspecialchars($wo['customer_phone']); ?>
                        </span>
                    </div>
                    <div class="info-row">
                        <label>Alamat Kunjungan:</label>
                        <div class="address-box" style="border-left-color: #17a2b8;">
                            <?php echo nl2br(htmlspecialchars($wo['customer_address'])); ?>
                        </div>
                    </div>
                    <div class="info-row">
                        <label>Provinsi:</label>
                        <span><?php echo htmlspecialchars($wo['provinsi'] ?? '-'); ?></span>
                    </div>
                    <div class="info-row">
                        <label>Kabupaten/Kota:</label>
                        <span><?php echo htmlspecialchars($wo['kota'] ?? '-'); ?></span>
                    </div>
            
                </div>
            </section>

        </div>

        <section class="card timeline-card">
            <div class="card-header">
                <h3>Riwayat Aktivitas Ticket & Work Order</h3>
            </div>
            <div class="ticket-info">
                <?php if (empty($updates)): ?>
                    <div class="no-updates">
                        <p>Belum ada aktivitas pada ticket ini.</p>
                    </div>
                <?php else: ?>
                    <div class="timeline">
                        <?php foreach ($updates as $update): ?>
                            <div class="timeline-item">
                                <div class="timeline-marker">
                                    <?php 
                                    $icon = '';
                                    switch ($update['update_type']) {
                                        case 'Status Change': $icon = '🔄'; break;
                                        case 'Escalation': $icon = '↗️'; break;
                                        case 'Comment': $icon = '💬'; break;
                                        case 'First Level Handling': $icon = '🎯'; break;
                                        default: $icon = '📝';
                                    }
                                    echo $icon;
                                    ?>
                                </div>
                                <div class="timeline-content">
                                    <div class="timeline-header">
                                        <span class="update-type"><?php echo htmlspecialchars($update['update_type']); ?></span>
                                        <span class="update-time"><?php echo formatTanggalIndonesia($update['created_at']); ?></span>
                                    </div>
                                    <div class="timeline-desc" style="margin: 6px 0 4px 0;">
                                        <?php echo nl2br(htmlspecialchars($update['description'])); ?>
                                    </div>
                                    <div class="timeline-meta" style="font-size:12px; color:#888;">
                                        Oleh: <b><?php echo htmlspecialchars($update['user_name']); ?></b>
                                        (<?php echo htmlspecialchars($update['user_role']); ?>)
                                    </div>
                                </div>  
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </section>

    <style>
        body {
            background: linear-gradient(135deg, #0d47a1 0%, #1976d2 100%);
            min-height: 100vh;
            margin: 0;
            font-family: 'Segoe UI', Arial, sans-serif;
            }
    </style>


</main>
</body>
</html>


    

