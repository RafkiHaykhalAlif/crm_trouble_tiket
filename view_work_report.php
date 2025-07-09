<?php
include 'config/db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

if (!isset($_GET['wo_id']) || empty($_GET['wo_id'])) {
    header('Location: dashboard_dispatch.php');
    exit();
}

$wo_id = (int)$_GET['wo_id'];

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
    t.jenis_tiket,
    t.title as ticket_title,
    t.description as ticket_description,
    t.status as ticket_status,
    t.created_at as ticket_created,
    c.customer_id_number,
    c.full_name as customer_name,
    c.address as customer_address,
    c.provinsi as customer_provinsi,
    c.kota as customer_kota,
    c.phone_number as customer_phone,
    c.email as customer_email,
    u_creator.full_name as created_by_name,
    u_vendor.full_name as technician_name,
    u_vendor.id as technician_id,
    wr.equipment_replaced,
    wr.equipment_removed,
    wr.cables_replaced,
    wr.new_installations,
    wr.signal_before,
    wr.signal_after,
    wr.speed_test_result,
    wr.materials_used,
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

$equipment_data = $wo['equipment_replaced'] ? json_decode($wo['equipment_replaced'], true) : null;
$equipment_removed = $wo['equipment_removed'] ?? null;
$materials_data = $wo['materials_used'] ? json_decode($wo['materials_used'], true) : null;

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
            <h1>Laporan Work Order Detail</h1>
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
            }
            ?>
            <a href="<?php echo $back_url; ?>" class="btn-back">← Kembali ke Dashboard</a>
            <a href="view_work_order.php?id=<?php echo $wo['id']; ?>" class="btn-back" style="background-color: #6c757d; margin-left: 10px;">👁 View WO Basic</a>
        </div>

        <?php
            $allowed_statuses = [
                'Completed',
                'Completed by Technician',
                'Closed',
                'Closed by BOR',
                'Waiting For BOR Review'
            ];
            if (!in_array($wo['wo_status'], $allowed_statuses) || empty($wo['visit_report'])):
            ?>
            <div class="report-grid">
                <div class="card">
                    <div style="text-align: center; padding: 40px;">
                        <div style="font-size: 48px; margin-bottom: 10px;"></div>
                        <h4>Work Order Belum Selesai</h4>
                        <p>Laporan detail akan tersedia setelah teknisi menyelesaikan pekerjaan.</p>
                        <p><strong>Status saat ini:</strong> <?php echo htmlspecialchars($wo['wo_status']); ?></p>
                    </div>
                </div>
            </div>
            <?php endif; ?>

        <div class="wo-letter">
            <div class="wo-letter-header">
                <h2 style="margin:0;">LAPORAN PEKERJAAN WO</h2>
                <div>No. WO: <strong><?php echo htmlspecialchars($wo['wo_code']); ?></strong></div>
                <div>Jenis Work Order: <strong><?php echo htmlspecialchars($wo['jenis_tiket']); ?></strong></div>
                <div>Tanggal: <strong><?php echo $wo['report_created'] ? formatTanggalIndonesia($wo['report_created']) : '-'; ?></strong></div>
            </div>
            <div class="wo-letter-body">
                <p>Kepada Yth,</p>
                <p>Customer: <strong><?php echo htmlspecialchars($wo['customer_name']); ?></strong></p>
                <p>Alamat: <?php echo htmlspecialchars($wo['customer_address']); ?></p>
                <p>Provinsi: <?php echo htmlspecialchars($wo['customer_provinsi']); ?></p>
                <p>Kabupaten/Kota: <?php echo htmlspecialchars($wo['customer_kota']); ?></p>
                <p>Telepon: <?php echo htmlspecialchars($wo['customer_phone']); ?></p>
                <p>Email: <?php echo htmlspecialchars($wo['customer_email']); ?></p>
                <br>
                <p>Dengan ini kami menyampaikan bahwa pekerjaan dengan rincian sebagai berikut telah selesai dilaksanakan:</p>
                <table class="wo-letter-table">
                    <tr>
                        <td><b>Jenis Work Order</b></td>
                        <td>: <?php echo htmlspecialchars($wo['jenis_tiket']); ?></td>
                    </tr>
                    <tr>
                        <td><b>Masalah</b></td>
                        <td>: <?php echo htmlspecialchars($wo['ticket_title']); ?></td>
                    </tr>
                    <tr>
                        <td><b>Deskripsi Pekerjaan</b></td>
                        <td>: <?php echo nl2br(htmlspecialchars($wo['visit_report'])); ?></td>
                    </tr>
                    <tr>
                        <td><b>Waktu Mulai</b></td>
                        <td>: <?php echo $wo['started_at'] ? formatTanggalIndonesia($wo['started_at']) : '-'; ?></td>
                    </tr>
                    <tr>
                        <td><b>Waktu Selesai</b></td>
                        <td>: <?php echo $wo['report_created'] ? formatTanggalIndonesia($wo['report_created']) : '-'; ?></td>
                    </tr>
                    <tr>
                        <td><b>Estimasi Durasi</b></td>
                        <td>: <?php echo formatDuration($wo['estimated_duration']); ?></td>
                    </tr>
                    <tr>
                        <td><b>Durasi Aktual</b></td>
                        <td>: <?php echo formatDuration($wo['actual_duration']); ?></td>
                    </tr>
                    <tr>
                        <td><b>Teknisi</b></td>
                        <td>: <?php echo htmlspecialchars($wo['technician_name']); ?></td>
                    </tr>
                </table>
                <br>

                <b>Pergantian Equipment:</b>
                <ul>
                    <?php if ($equipment_data && !empty($equipment_data['equipment_replaced'])): ?>
                        <li>Equipment Diganti: <?php echo nl2br(htmlspecialchars($equipment_data['equipment_replaced'])); ?></li>
                    <?php endif; ?>
                    <?php if ($equipment_data && !empty($equipment_data['cables_replaced'])): ?>
                        <li>Kabel Diganti: <?php echo nl2br(htmlspecialchars($equipment_data['cables_replaced'])); ?></li>
                    <?php endif; ?>
                    <?php if ($equipment_data && !empty($equipment_data['new_installations'])): ?>
                        <li>Instalasi Baru: <?php echo nl2br(htmlspecialchars($equipment_data['new_installations'])); ?></li>
                    <?php endif; ?>
                    <?php if (!$equipment_data || (empty($equipment_data['equipment_replaced']) && empty($equipment_data['cables_replaced']) && empty($equipment_data['new_installations']))): ?>
                        <li>Tidak ada pergantian equipment yang dilaporkan.</li>
                    <?php endif; ?>
                </ul>

                <b>Pencabutan Equipment:</b>
                <ul>
                    <li>
                        Equipment Dicabut: 
                        <?php
                            if ($equipment_removed && trim($equipment_removed) !== '') {
                                echo nl2br(htmlspecialchars($equipment_removed));
                            } else {
                                echo '-';
                            }
                        ?>
                    </li>
                </ul>

                <b>Pengukuran Teknis:</b>
                <ul>
                    <li>Signal Sebelum: <?php echo $wo['signal_before'] ? htmlspecialchars($wo['signal_before']) : '-'; ?></li>
                    <li>Signal Sesudah: <?php echo $wo['signal_after'] ? htmlspecialchars($wo['signal_after']) : '-'; ?></li>
                    <li>Speed Test Result: <?php echo $wo['speed_test_result'] ? htmlspecialchars($wo['speed_test_result']) : '-'; ?></li>
                </ul>

                <b>Material yang Digunakan:</b>
                <?php if ($materials_data && !empty($materials_data)): ?>
                    <table class="wo-letter-table" style="margin-top:8px;">
                        <tr>
                            <th>Material</th>
                            <th>Qty</th>
                            <th>Unit</th>
                            <th>Catatan</th>
                        </tr>
                        <?php foreach ($materials_data as $material): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($material['name']); ?></td>
                            <td><?php echo htmlspecialchars($material['quantity']); ?></td>
                            <td><?php echo htmlspecialchars($material['unit']); ?></td>
                            <td><?php echo htmlspecialchars($material['notes']); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </table>
                <?php else: ?>
                    <ul><li>Tidak ada material yang dilaporkan digunakan.</li></ul>
                <?php endif; ?>

                <br>
                <p>Demikian surat laporan ini dibuat sebagai bukti pekerjaan telah selesai dilaksanakan dengan baik.</p>
                <br>
                <div class="wo-letter-sign">
                    <div>
                        <b>Teknisi</b><br>
                        <br><br>
                        <u><?php echo htmlspecialchars($wo['technician_name']); ?></u>
                    </div>
                    <div>
                        <b>Customer</b><br>
                        <br><br>
                        <u><?php echo htmlspecialchars($wo['customer_name']); ?></u>
                    </div>
                </div>
            </div>
        </div>

    </main>

    <style>
        body {
        background: linear-gradient(135deg, #0d47a1 0%, #1976d2 100%);
        min-height: 100vh;
        margin: 0;
        font-family: 'Segoe UI', Arial, sans-serif;
        }
        
        .user-role-dispatch {
            background-color: #17a2b8 !important;
        }

        .user-role-bor {
            background-color: #fd7e14 !important;
        }

        .status-badges {
            display: flex;
            gap: 10px;
            font-size: 1rem;
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

        .wo-letter {
            margin: 36px auto 48px auto;
            max-width: 1100px;
            min-width: 0;
            background: #fff;
            border: 1.5px solid #1976d2;
            border-radius: 12px;
            padding: 48px 60px 40px 60px;
            box-shadow: 0 4px 24px #1976d233;
            font-family: 'Segoe UI', Arial, sans-serif;
            color: #222;
        }

        .wo-letter-header {
            text-align: center;
            margin-bottom: 24px;
        }

        .wo-letter-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 8px;
            margin-bottom: 8px;
        }

        .wo-letter-table th, .wo-letter-table td {
            padding: 4px 8px;
            border: 1px solid #ddd;
            vertical-align: top;
            font-size: 14px;
        }

        .wo-letter-sign {
            display: flex;
            justify-content: space-between;
            margin-top: 40px;
            gap: 40px;
        }

        .wo-letter-sign div {
            text-align: center;
            width: 40%;
        }

        @media (max-width: 600px) {
            .wo-letter {
                padding: 16px 6vw;
            }
            .wo-letter-sign {
                flex-direction: column;
                gap: 24px;
            }
            .wo-letter-sign div {
                width: 100%;
            }
        }
    </style>

</body>
</html>