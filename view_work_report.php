<?php
include 'config/db_connect.php';

// Cek apakah user sudah login
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Cek parameter ID work order
if (!isset($_GET['wo_id']) || empty($_GET['wo_id'])) {
    header('Location: dashboard_dispatch.php');
    exit();
}

$wo_id = (int)$_GET['wo_id'];

// Query untuk ambil detail work order dengan report
$sql_wo_report = "SELECT 
    wo.id,
    wo.wo_code,
    wo.status as wo_status,
    wo.scheduled_visit_date,
    wo.visit_report,
    wo.started_at,
    wo.estimated_duration,
    wo.actual_duration,
    wo.pre_work_notes,
    t.id as ticket_id,
    t.ticket_code,
    t.title as ticket_title,
    t.description as ticket_description,
    t.status as ticket_status,
    t.created_at as ticket_created,
    c.customer_id_number,
    c.full_name as customer_name,
    c.address as customer_address,
    c.phone_number as customer_phone,
    c.email as customer_email,
    u_creator.full_name as created_by_name,
    u_vendor.full_name as technician_name,
    u_vendor.id as technician_id,
    wr.equipment_replaced,
    wr.cables_replaced,
    wr.new_installations,
    wr.signal_before,
    wr.signal_after,
    wr.speed_test_result,
    wr.materials_used,
    wr.customer_satisfaction,
    wr.customer_notes,
    wr.created_at as report_created
FROM tr_work_orders wo
JOIN tr_tickets t ON wo.ticket_id = t.id
JOIN ms_customers c ON t.customer_id = c.id
JOIN ms_users u_creator ON wo.created_by_dispatch_id = u_creator.id
LEFT JOIN ms_users u_vendor ON wo.assigned_to_vendor_id = u_vendor.id
LEFT JOIN tr_work_reports wr ON wo.id = wr.work_order_id
WHERE wo.id = '$wo_id'";

$result_wo = mysqli_query($conn, $sql_wo_report);

if (mysqli_num_rows($result_wo) == 0) {
    header('Location: dashboard_dispatch.php?status=wo_not_found');
    exit();
}

$wo = mysqli_fetch_assoc($result_wo);

// Parse JSON data
$equipment_data = $wo['equipment_replaced'] ? json_decode($wo['equipment_replaced'], true) : null;
$materials_data = $wo['materials_used'] ? json_decode($wo['materials_used'], true) : null;

// Fungsi helper untuk format tanggal Indonesia
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

function formatDuration($minutes) {
    if (!$minutes) return '-';
    
    $hours = floor($minutes / 60);
    $mins = $minutes % 60;
    
    if ($hours > 0) {
        return $hours . ' jam ' . $mins . ' menit';
    } else {
        return $mins . ' menit';
    }
}

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Work Order - <?php echo htmlspecialchars($wo['wo_code']); ?></title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

    <header class="main-header">
        <div class="container">
            <h1>📋 Laporan Work Order Detail</h1>
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
        
        <!-- Tombol Kembali -->
        <div class="back-button-section">
            <?php 
            $back_url = 'dashboard.php';
            if ($_SESSION['user_role'] === 'Dispatch') {
                $back_url = 'dashboard_dispatch.php';
            } elseif ($_SESSION['user_role'] === 'BOR') {
                $back_url = 'dashboard_bor.php';
            }
            ?>
            <a href="<?php echo $back_url; ?>" class="btn-back">← Kembali ke Dashboard</a>
            <a href="view_work_order.php?id=<?php echo $wo['id']; ?>" class="btn-back" style="background-color: #6c757d; margin-left: 10px;">👁 View WO Basic</a>
        </div>

        <!-- Header Info WO -->
        <div class="card report-header">
            <div class="report-title">
                <h2>🛠️ <?php echo htmlspecialchars($wo['wo_code']); ?></h2>
                <div class="status-badges">
                    <?php 
                    $wo_status_color = '';
                    switch($wo['wo_status']) {
                        case 'Pending': $wo_status_color = 'background-color: #ffc107; color: #212529;'; break;
                        case 'Scheduled': $wo_status_color = 'background-color: #17a2b8; color: white;'; break;
                        case 'In Progress': $wo_status_color = 'background-color: #fd7e14; color: white;'; break;
                        case 'Completed': $wo_status_color = 'background-color: #28a745; color: white;'; break;
                        case 'Cancelled': $wo_status_color = 'background-color: #dc3545; color: white;'; break;
                        default: $wo_status_color = 'background-color: #6c757d; color: white;';
                    }
                    ?>
                    <span class="status" style="<?php echo $wo_status_color; ?>">
                        <?php echo htmlspecialchars($wo['wo_status']); ?>
                    </span>
                </div>
            </div>
            
            <div class="report-summary">
                <div class="summary-item">
                    <label>Ticket:</label>
                    <span><?php echo htmlspecialchars($wo['ticket_code']); ?></span>
                </div>
                <div class="summary-item">
                    <label>Customer:</label>
                    <span><?php echo htmlspecialchars($wo['customer_name']); ?></span>
                </div>
                <div class="summary-item">
                    <label>Teknisi:</label>
                    <span><?php echo htmlspecialchars($wo['technician_name']); ?></span>
                </div>
                <div class="summary-item">
                    <label>Tanggal Laporan:</label>
                    <span><?php echo $wo['report_created'] ? formatTanggalIndonesia($wo['report_created']) : '-'; ?></span>
                </div>
            </div>
        </div>

        <?php if ($wo['wo_status'] == 'Completed' && $wo['report_created']): ?>
        
        <div class="report-grid">
            
            <!-- Work Summary -->
            <div class="report-section">
                <section class="card">
                    <h3>📋 Ringkasan Pekerjaan</h3>
                    
                    <div class="info-row">
                        <label>Masalah:</label>
                        <span><?php echo htmlspecialchars($wo['ticket_title']); ?></span>
                    </div>
                    
                    <div class="info-row">
                        <label>Deskripsi Pekerjaan:</label>
                        <div class="description-box">
                            <?php echo nl2br(htmlspecialchars($wo['visit_report'])); ?>
                        </div>
                    </div>

                    <div class="timing-info">
                        <div class="timing-item">
                            <label>⏱️ Waktu Mulai:</label>
                            <span><?php echo $wo['started_at'] ? formatTanggalIndonesia($wo['started_at']) : '-'; ?></span>
                        </div>
                        <div class="timing-item">
                            <label>📅 Waktu Selesai:</label>
                            <span><?php echo $wo['report_created'] ? formatTanggalIndonesia($wo['report_created']) : '-'; ?></span>
                        </div>
                        <div class="timing-item">
                            <label>⏳ Estimasi:</label>
                            <span><?php echo formatDuration($wo['estimated_duration']); ?></span>
                        </div>
                        <div class="timing-item">
                            <label>✅ Aktual:</label>
                            <span style="font-weight: 600; color: <?php echo ($wo['actual_duration'] <= $wo['estimated_duration']) ? '#28a745' : '#dc3545'; ?>;">
                                <?php echo formatDuration($wo['actual_duration']); ?>
                            </span>
                        </div>
                    </div>
                </section>
            </div>

            <!-- Equipment & Technical -->
            <div class="report-section">
                <section class="card">
                    <h3>🔧 Pergantian Equipment</h3>
                    
                    <?php if ($equipment_data): ?>
                        <?php if (!empty($equipment_data['equipment_replaced'])): ?>
                        <div class="equipment-item">
                            <h4>📟 Equipment Diganti:</h4>
                            <div class="equipment-details">
                                <?php echo nl2br(htmlspecialchars($equipment_data['equipment_replaced'])); ?>
                            </div>
                        </div>
                        <?php endif; ?>

                        <?php if (!empty($equipment_data['cables_replaced'])): ?>
                        <div class="equipment-item">
                            <h4>🔌 Kabel Diganti:</h4>
                            <div class="equipment-details">
                                <?php echo nl2br(htmlspecialchars($equipment_data['cables_replaced'])); ?>
                            </div>
                        </div>
                        <?php endif; ?>

                        <?php if (!empty($equipment_data['new_installations'])): ?>
                        <div class="equipment-item">
                            <h4>🆕 Instalasi Baru:</h4>
                            <div class="equipment-details">
                                <?php echo nl2br(htmlspecialchars($equipment_data['new_installations'])); ?>
                            </div>
                        </div>
                        <?php endif; ?>
                    <?php else: ?>
                        <p class="no-data">Tidak ada pergantian equipment yang dilaporkan.</p>
                    <?php endif; ?>
                </section>
            </div>

            <!-- Technical Measurements -->
            <div class="report-section">
                <section class="card">
                    <h3>📊 Pengukuran Teknis</h3>
                    
                    <div class="measurements-grid">
                        <div class="measurement-item">
                            <label>Signal Sebelum:</label>
                            <span class="measurement-value">
                                <?php echo $wo['signal_before'] ? htmlspecialchars($wo['signal_before']) : '-'; ?>
                            </span>
                        </div>
                        <div class="measurement-item">
                            <label>Signal Sesudah:</label>
                            <span class="measurement-value" style="color: #28a745; font-weight: 600;">
                                <?php echo $wo['signal_after'] ? htmlspecialchars($wo['signal_after']) : '-'; ?>
                            </span>
                        </div>
                        <div class="measurement-item full-width">
                            <label>Speed Test Result:</label>
                            <span class="measurement-value">
                                <?php echo $wo['speed_test_result'] ? htmlspecialchars($wo['speed_test_result']) : '-'; ?>
                            </span>
                        </div>
                    </div>
                </section>
            </div>

            <!-- Materials Used -->
            <div class="report-section">
                <section class="card">
                    <h3>📦 Material yang Digunakan</h3>
                    
                    <?php if ($materials_data && !empty($materials_data)): ?>
                        <div class="materials-table">
                            <table class="ticket-table">
                                <thead>
                                    <tr>
                                        <th>Material</th>
                                        <th>Qty</th>
                                        <th>Unit</th>
                                        <th>Catatan</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($materials_data as $material): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($material['name']); ?></td>
                                            <td><?php echo htmlspecialchars($material['quantity']); ?></td>
                                            <td><?php echo htmlspecialchars($material['unit']); ?></td>
                                            <td><?php echo htmlspecialchars($material['notes']); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="no-data">Tidak ada material yang dilaporkan digunakan.</p>
                    <?php endif; ?>
                </section>
            </div>

            <!-- Customer Feedback -->
            <div class="report-section">
                <section class="card">
                    <h3>👤 Feedback Customer</h3>
                    
                    <?php if ($wo['customer_satisfaction']): ?>
                        <div class="satisfaction-badge">
                            <?php
                            $satisfaction_icon = '';
                            $satisfaction_color = '';
                            switch($wo['customer_satisfaction']) {
                                case 'Very Satisfied': 
                                    $satisfaction_icon = '😊'; 
                                    $satisfaction_color = '#28a745'; 
                                    break;
                                case 'Satisfied': 
                                    $satisfaction_icon = '🙂'; 
                                    $satisfaction_color = '#28a745'; 
                                    break;
                                case 'Neutral': 
                                    $satisfaction_icon = '😐'; 
                                    $satisfaction_color = '#ffc107'; 
                                    break;
                                case 'Unsatisfied': 
                                    $satisfaction_icon = '😞'; 
                                    $satisfaction_color = '#dc3545'; 
                                    break;
                            }
                            ?>
                            <span style="color: <?php echo $satisfaction_color; ?>; font-size: 1.2em; font-weight: 600;">
                                <?php echo $satisfaction_icon; ?> <?php echo htmlspecialchars($wo['customer_satisfaction']); ?>
                            </span>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($wo['customer_notes']): ?>
                        <div class="customer-notes">
                            <h4>💬 Catatan Customer:</h4>
                            <div class="description-box">
                                <?php echo nl2br(htmlspecialchars($wo['customer_notes'])); ?>
                            </div>
                        </div>
                    <?php else: ?>
                        <p class="no-data">Tidak ada catatan khusus dari customer.</p>
                    <?php endif; ?>
                </section>
            </div>

        </div>

        <?php else: ?>
        
        <!-- WO belum completed -->
        <div class="card">
            <div style="text-align: center; padding: 40px;">
                <div style="font-size: 48px; margin-bottom: 10px;">⏳</div>
                <h4>Work Order Belum Selesai</h4>
                <p>Laporan detail akan tersedia setelah teknisi menyelesaikan pekerjaan.</p>
                <p><strong>Status saat ini:</strong> <?php echo htmlspecialchars($wo['wo_status']); ?></p>
            </div>
        </div>

        <?php endif; ?>

    </main>

    <style>
    .user-role-dispatch {
        background-color: #17a2b8 !important;
    }

    .user-role-bor {
        background-color: #fd7e14 !important;
    }

    .report-header {
        margin-bottom: 30px;
    }

    .report-title {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
        padding-bottom: 15px;
        border-bottom: 2px solid #e9ecef;
    }

    .report-title h2 {
        margin: 0;
        color: #495057;
    }

    .status-badges {
        display: flex;
        gap: 10px;
    }

    .report-summary {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 15px;
    }

    .summary-item {
        display: flex;
        flex-direction: column;
        gap: 5px;
    }

    .summary-item label {
        font-weight: 600;
        color: #6c757d;
        font-size: 14px;
    }

    .summary-item span {
        font-weight: 500;
        color: #495057;
    }

    .report-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
        gap: 20px;
    }

    @media (max-width: 992px) {
        .report-grid {
            grid-template-columns: 1fr;
        }
    }

    .timing-info {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 15px;
        margin-top: 20px;
        padding: 15px;
        background-color: #f8f9fa;
        border-radius: 6px;
        border-left: 4px solid #17a2b8;
    }

    .timing-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .timing-item label {
        font-size: 14px;
        color: #495057;
    }

    .equipment-item {
        margin-bottom: 20px;
    }

    .equipment-item h4 {
        margin: 0 0 8px 0;
        color: #495057;
        font-size: 1rem;
    }

    .equipment-details {
        padding: 12px;
        background-color: #f8f9fa;
        border-radius: 4px;
        border-left: 3px solid #28a745;
        line-height: 1.5;
    }

    .measurements-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 15px;
    }

    .measurement-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 10px;
        background-color: #f8f9fa;
        border-radius: 4px;
    }

    .measurement-item.full-width {
        grid-column: 1 / -1;
    }

    .measurement-item label {
        font-weight: 600;
        color: #495057;
    }

    .measurement-value {
        font-weight: 500;
        color: #495057;
    }

    .materials-table {
        overflow-x: auto;
    }

    .satisfaction-badge {
        text-align: center;
        padding: 15px;
        margin-bottom: 15px;
        background-color: #f8f9fa;
        border-radius: 8px;
    }

    .customer-notes h4 {
        margin-bottom: 10px;
        color: #495057;
    }

    .no-data {
        text-align: center;
        color: #6c757d;
        font-style: italic;
        padding: 20px;
    }

    .info-row {
        display: flex;
        margin-bottom: 15px;
        align-items: flex-start;
    }

    .info-row label {
        min-width: 140px;
        font-weight: 600;
        color: #495057;
        margin-right: 15px;
        flex-shrink: 0;
    }

    .description-box {
        background-color: #f8f9fa;
        padding: 12px;
        border-radius: 4px;
        border-left: 4px solid #007bff;
        line-height: 1.5;
        max-width: 100%;
        word-wrap: break-word;
    }

    @media (max-width: 768px) {
        .report-summary {
            grid-template-columns: 1fr;
        }

        .timing-info {
            grid-template-columns: 1fr;
        }

        .measurements-grid {
            grid-template-columns: 1fr;
        }

        .info-row {
            flex-direction: column;
        }

        .info-row label {
            min-width: auto;
            margin-bottom: 5px;
        }
    }
    </style>

</body>
</html>