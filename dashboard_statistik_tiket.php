<?php
include 'config/db_connect.php';
// session_start();  // HAPUS atau KOMENTARI baris ini!
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'Customer Care') {
    header('Location: login.php');
    exit();
}

// --- STATISTIK COMPREHENSIVE UNTUK CUSTOMER CARE ---

// 1. Statistik Overall Tickets
$sql_overall_stats = "SELECT 
    COUNT(*) as total_tickets,
    SUM(CASE WHEN status = 'Open' THEN 1 ELSE 0 END) as cc_progress,
    SUM(CASE WHEN status = 'On Progress - BOR' THEN 1 ELSE 0 END) as bor_progress,
    SUM(CASE WHEN status = 'Waiting for Dispatch' THEN 1 ELSE 0 END) as waiting_dispatch,
    SUM(CASE WHEN status = 'Waiting for BOR Review' THEN 1 ELSE 0 END) as waiting_review,
    SUM(CASE WHEN status LIKE 'Closed%' THEN 1 ELSE 0 END) as closed_tickets,
    SUM(CASE WHEN status = 'Closed - Solved' THEN 1 ELSE 0 END) as solved_tickets
FROM tr_tickets";
$result_overall = mysqli_query($conn, $sql_overall_stats);  
$overall_stats = mysqli_fetch_assoc($result_overall);

// 2. Statistik Kategori Gangguan (Enhanced)
$sql_category_stats = "SELECT 
    SUM(CASE WHEN title LIKE '%mati%' OR title LIKE '%putus%' OR title LIKE '%no internet%' OR title LIKE '%tidak konek%' THEN 1 ELSE 0 END) as connectivity_issues,
    SUM(CASE WHEN title LIKE '%lambat%' OR title LIKE '%lemot%' OR title LIKE '%pelan%' THEN 1 ELSE 0 END) as speed_issues,
    SUM(CASE WHEN title LIKE '%wifi%' OR title LIKE '%wireless%' THEN 1 ELSE 0 END) as wifi_issues,
    SUM(CASE WHEN title LIKE '%modem%' OR title LIKE '%router%' OR title LIKE '%ont%' THEN 1 ELSE 0 END) as equipment_issues
FROM tr_tickets";
$result_category = mysqli_query($conn, $sql_category_stats);
$category_stats = mysqli_fetch_assoc($result_category);

// 3. Statistik SLA Performance (Enhanced)
$sql_sla_stats = "SELECT 
    COUNT(*) as total_closed,
    SUM(CASE WHEN TIMESTAMPDIFF(HOUR, created_at, closed_at) <= 4 THEN 1 ELSE 0 END) as within_4h,
    SUM(CASE WHEN TIMESTAMPDIFF(HOUR, created_at, closed_at) <= 24 THEN 1 ELSE 0 END) as within_24h,
    SUM(CASE WHEN TIMESTAMPDIFF(HOUR, created_at, closed_at) > 24 THEN 1 ELSE 0 END) as over_24h,
    AVG(TIMESTAMPDIFF(HOUR, created_at, closed_at)) as avg_resolution_time
FROM tr_tickets
WHERE status = 'Closed - Solved' AND closed_at IS NOT NULL";
$result_sla = mysqli_query($conn, $sql_sla_stats);
$sla_stats = mysqli_fetch_assoc($result_sla);

// Calculate SLA percentages
$sla_4h_percentage = ($sla_stats['total_closed'] > 0) ? round(($sla_stats['within_4h'] * 100) / $sla_stats['total_closed']) : 0;
$sla_24h_percentage = ($sla_stats['total_closed'] > 0) ? round(($sla_stats['within_24h'] * 100) / $sla_stats['total_closed']) : 0;

// 4. Statistik Harian
$sql_daily_stats = "SELECT 
    COUNT(*) as tickets_today,  
    SUM(CASE WHEN status LIKE 'Closed%' THEN 1 ELSE 0 END) as closed_today,
    SUM(CASE WHEN status = 'Closed - Solved' THEN 1 ELSE 0 END) as solved_today
FROM tr_tickets 
WHERE DATE(created_at) = CURDATE()";
$result_daily = mysqli_query($conn, $sql_daily_stats);
$daily_stats = mysqli_fetch_assoc($result_daily);

// 5. Statistik Work Order Progress
$sql_wo_stats = "SELECT 
    COUNT(*) as total_work_orders,
    SUM(CASE WHEN status = 'Pending' THEN 1 ELSE 0 END) as pending_wo,
    SUM(CASE WHEN status = 'Scheduled by Admin IKR' THEN 1 ELSE 0 END) as scheduled_wo,
    SUM(CASE WHEN status = 'In Progress' THEN 1 ELSE 0 END) as progress_wo,
    SUM(CASE WHEN status = 'Closed by BOR' THEN 1 ELSE 0 END) as completed_wo
FROM tr_work_orders";
$result_wo = mysqli_query($conn, $sql_wo_stats);
$wo_stats = mysqli_fetch_assoc($result_wo);

// 6. Teknisi Performance Summary
$sql_technician_stats = "SELECT 
    COUNT(DISTINCT wo.assigned_to_vendor_id) as active_technicians,
    COUNT(CASE WHEN wo.status = 'Completed' AND DATE(wo.created_at) >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) THEN 1 END) as completed_this_week
FROM tr_work_orders wo
WHERE wo.assigned_to_vendor_id IS NOT NULL";
$result_tech = mysqli_query($conn, $sql_technician_stats);
$tech_stats = mysqli_fetch_assoc($result_tech);

// 7. Recent Activity (untuk animasi real-time)
$sql_recent = "SELECT title, status, created_at FROM tr_tickets ORDER BY created_at DESC LIMIT 5";
$result_recent = mysqli_query($conn, $sql_recent);
$recent_tickets = [];
while($row = mysqli_fetch_assoc($result_recent)) {
    $recent_tickets[] = $row;
}

// Statistik Tiket Berdasarkan Kota
$sql_city_stats = "
    SELECT 
        c.kota AS kota,
        SUM(CASE WHEN t.jenis_tiket = 'Maintenance' THEN 1 ELSE 0 END) AS total_maintenance,
        SUM(CASE WHEN t.jenis_tiket = 'Dismantle' THEN 1 ELSE 0 END) AS total_dismantle,
        COUNT(*) AS total_tiket
    FROM tr_tickets t
    JOIN ms_customers c ON t.customer_id = c.id
    GROUP BY c.kota
    ORDER BY total_tiket DESC
";
$result_city_stats = mysqli_query($conn, $sql_city_stats);
$city_stats = [];
while ($row = mysqli_fetch_assoc($result_city_stats)) {
    $city_stats[] = $row;
}

// Statistik Complain Channel
$sql_channel_stats = "
    SELECT 
        complain_channel, 
        COUNT(*) as total_channel
    FROM tr_tickets
    WHERE complain_channel IS NOT NULL AND complain_channel <> ''
    GROUP BY complain_channel
    ORDER BY total_channel DESC
";
$result_channel_stats = mysqli_query($conn, $sql_channel_stats);
$channel_stats = [];
while ($row = mysqli_fetch_assoc($result_channel_stats)) {
    $channel_stats[] = $row;
}

// Ambil bulan & tahun dari input, default ke bulan ini
if (isset($_GET['bulan']) && preg_match('/^\d{4}-\d{2}$/', $_GET['bulan'])) {
    $selected_month = $_GET['bulan'];
} else {
    $selected_month = date('Y-m');
}
list($selected_year, $selected_month_num) = explode('-', $selected_month);

$sql_daily_ticket_stats = "
    SELECT 
        DATE(t.created_at) AS tanggal,
        SUM(CASE WHEN t.jenis_tiket = 'Maintenance' THEN 1 ELSE 0 END) AS maintenance_count,
        SUM(CASE WHEN t.jenis_tiket = 'Dismantle' THEN 1 ELSE 0 END) AS dismantle_count
    FROM tr_tickets t
    WHERE MONTH(t.created_at) = '$selected_month_num' AND YEAR(t.created_at) = '$selected_year'
    GROUP BY tanggal
    ORDER BY tanggal ASC
";
$result_daily_ticket_stats = mysqli_query($conn, $sql_daily_ticket_stats);
$daily_ticket_stats = [];
while ($row = mysqli_fetch_assoc($result_daily_ticket_stats)) {
    $daily_ticket_stats[] = $row;
}

// Siapkan data untuk Chart.js
$chart_labels = [];
$chart_maintenance = [];
$chart_dismantle = [];
foreach ($daily_ticket_stats as $row) {
    $chart_labels[] = date('d', strtotime($row['tanggal']));
    $chart_maintenance[] = (int)$row['maintenance_count'];
    $chart_dismantle[] = (int)$row['dismantle_count'];
}

// Total Maintenance dan Dismantle per Bulan
$total_maintenance_bulan = 0;
$total_dismantle_bulan = 0;
$total_tiket_bulan = 0;
foreach ($daily_ticket_stats as $row) {
    $total_maintenance_bulan += $row['maintenance_count'];
    $total_dismantle_bulan += $row['dismantle_count'];
    $total_tiket_bulan += $row['maintenance_count'] + $row['dismantle_count'];
}

?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Statistik Tiket - Network Operations Center</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    
</head>

<body>
    <div class="container-fluid px-4">
        <!-- Header -->
        <div class="dashboard-header">
            <div class="dashboard-title">
                <i class="fas fa-chart-line me-3"></i>
                Dashboard Statistik Tiket
            </div>
            <div class="dashboard-subtitle">
                Network Operations Center - Real-time Service Monitoring
            </div>
        </div>

        <!-- Key Performance Indicators -->
        <div class="section-header">
            <div class="section-title">
                <i class="fas fa-tachometer-alt me-2"></i>
                Key Performance Indicators
            </div>
            <div class="section-description">Monitor performa layanan secara real-time</div>
        </div>

        <div class="kpi-grid">
            <div class="kpi-card kpi-total">
                <div class="kpi-header">
                    <div class="kpi-icon">
                        <i class="fas fa-ticket-alt"></i>
                    </div>
                    <div class="kpi-trend trend-up">
                        <i class="fas fa-arrow-up"></i> +12%
                    </div>
                </div>
                <div class="kpi-number"><?php echo number_format($overall_stats['total_tickets']); ?></div>
                <div class="kpi-label">Total Tiket</div>
                <div class="kpi-subtext">Semua tiket yang pernah dibuat</div>
            </div>

            <div class="kpi-card kpi-active">
                <div class="kpi-header">
                    <div class="kpi-icon">
                        <i class="fas fa-sync-alt"></i>
                    </div>
                    <div class="kpi-trend trend-down">
                        <i class="fas fa-arrow-down"></i> -5%
                    </div>
                </div>
                <div class="kpi-number"><?php echo number_format($overall_stats['cc_progress'] + $overall_stats['bor_progress'] + $overall_stats['waiting_dispatch']); ?></div>
                <div class="kpi-label">Tiket Aktif</div>
                <div class="kpi-subtext">Memerlukan perhatian segera</div>
            </div>

            <div class="kpi-card kpi-today">
                <div class="kpi-header">
                    <div class="kpi-icon">
                        <i class="fas fa-calendar-day"></i>
                    </div>
                    <div class="kpi-trend trend-up">
                        <i class="fas fa-arrow-up"></i> +8%
                    </div>
                </div>
                <div class="kpi-number"><?php echo number_format($daily_stats['tickets_today']); ?></div>
                <div class="kpi-label">Tiket Hari Ini</div>
                <div class="kpi-subtext"><?php echo $daily_stats['solved_today']; ?> diselesaikan</div>
            </div>

            <div class="kpi-card kpi-connectivity">
                <div class="kpi-header">
                    <div class="kpi-icon">
                        <i class="fas fa-plug"></i>
                    </div>
                </div>
                <div class="kpi-number"><?php echo number_format($category_stats['connectivity_issues'] ?? 0); ?></div>
                <div class="kpi-label">Masalah Konektivitas</div>
                <div class="kpi-subtext">Internet mati/putus</div>
            </div>

            <div class="kpi-card kpi-speed">
                <div class="kpi-header">
                    <div class="kpi-icon">
                        <i class="fas fa-tachometer-alt"></i>
                    </div>
                </div>
                <div class="kpi-number"><?php echo number_format($category_stats['speed_issues'] ?? 0); ?></div>
                <div class="kpi-label">Masalah Kecepatan</div>
                <div class="kpi-subtext">Koneksi lambat</div>
            </div>

            <div class="kpi-card kpi-equipment">
                <div class="kpi-header">
                    <div class="kpi-icon">
                        <i class="fas fa-tools"></i>
                    </div>
                </div>
                <div class="kpi-number"><?php echo number_format($category_stats['equipment_issues'] ?? 0); ?></div>
                <div class="kpi-label">Masalah Perangkat</div>
                <div class="kpi-subtext">Hardware bermasalah</div>
            </div>
        </div>

        <!-- Workflow Progress -->
        <div class="section-header">
            <div class="section-title">
                <i class="fas fa-project-diagram me-2"></i>
                Alur Proses Tiket
            </div>
            <div class="section-description">Visualisasi progress penanganan tiket</div>
        </div>

        <div class="workflow-container">
            <div class="workflow-pipeline">
                <div class="workflow-stage">
                    <div class="stage-bar progress" style="height: <?php echo min(150, max(20, ($overall_stats['cc_progress'] / max(1, $overall_stats['total_tickets'])) * 150)); ?>px;"></div>
                    <div class="stage-number"><?php echo $overall_stats['cc_progress']; ?></div>
                    <div class="stage-label">Customer Care</div>
                </div>
                
                <div class="workflow-stage">
                    <div class="stage-bar bor" style="height: <?php echo min(150, max(20, ($overall_stats['bor_progress'] / max(1, $overall_stats['total_tickets'])) * 150)); ?>px;"></div>
                    <div class="stage-number"><?php echo $overall_stats['bor_progress']; ?></div>
                    <div class="stage-label">BOR Handling</div>
                </div>
                
                <div class="workflow-stage">
                    <div class="stage-bar dispatch" style="height: <?php echo min(150, max(20, ($overall_stats['waiting_dispatch'] / max(1, $overall_stats['total_tickets'])) * 150)); ?>px;"></div>
                    <div class="stage-number"><?php echo $overall_stats['waiting_dispatch']; ?></div>
                    <div class="stage-label">Field Work</div>
                </div>
                
                <div class="workflow-stage">
                    <div class="stage-bar review" style="height: <?php echo min(150, max(20, ($overall_stats['waiting_review'] / max(1, $overall_stats['total_tickets'])) * 150)); ?>px;"></div>
                    <div class="stage-number"><?php echo $overall_stats['waiting_review']; ?></div>
                    <div class="stage-label">Review</div>
                </div>
                
                <div class="workflow-stage">
                    <div class="stage-bar resolved" style="height: <?php echo min(150, max(20, ($overall_stats['solved_tickets'] / max(1, $overall_stats['total_tickets'])) * 150)); ?>px;"></div>
                    <div class="stage-number"><?php echo $overall_stats['solved_tickets']; ?></div>
                    <div class="stage-label">Diselesaikan</div>
                </div>
            </div>
        </div>

        <!-- Field Operations Status -->
        <div class="section-header">
            <div class="section-title">
                <i class="fas fa-hard-hat me-2"></i>
                Status Operasi Lapangan
            </div>
            <div class="section-description">Monitor aktivitas teknisi dan work order</div>
        </div>

        <div class="field-ops-grid">
            <div class="field-card">
                <div class="field-number"><?php echo number_format($wo_stats['pending_wo']); ?></div>
                <div class="field-label">Pending WO</div>
            </div>
            
            <div class="field-card">
                <div class="field-number"><?php echo number_format($wo_stats['scheduled_wo']); ?></div>
                <div class="field-label">Terjadwal</div>
            </div>
            
            <div class="field-card">
                <div class="field-number"><?php echo number_format($wo_stats['progress_wo']); ?></div>
                <div class="field-label">Sedang Dikerjakan</div>
            </div>
            
            <div class="field-card">
                <div class="field-number"><?php echo number_format($wo_stats['completed_wo']); ?></div>
                <div class="field-label">Selesai</div>
            </div>
            
            <div class="field-card">
                <div class="field-number"><?php echo number_format($tech_stats['active_technicians']); ?></div>
                <div class="field-label">Teknisi Aktif</div>
            </div>
        </div>

        <!-- Statistics Tables -->
        <div class="row mt-5">
            <div class="col-lg-6">
                <div class="stats-table-container">
                    <h4 class="mb-4">
                        <i class="fas fa-chart-pie me-2"></i>
                        Statistik SLA Performance
                    </h4>
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Kriteria SLA</th>
                                <th>Jumlah</th>
                                <th>Persentase</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>Diselesaikan ≤ 4 Jam</td>
                                <td><?php echo number_format($sla_stats['within_4h']); ?></td>
                                <td><span class="badge bg-success"><?php echo $sla_4h_percentage; ?>%</span></td>
                            </tr>
                            <tr>
                                <td>Diselesaikan ≤ 24 Jam</td>
                                <td><?php echo number_format($sla_stats['within_24h']); ?></td>
                                <td><span class="badge bg-info"><?php echo $sla_24h_percentage; ?>%</span></td>
                            </tr>
                            <tr>
                                <td>Lebih dari 24 Jam</td>
                                <td><?php echo number_format($sla_stats['over_24h']); ?></td>
                                <td><span class="badge bg-warning"><?php echo 100 - $sla_24h_percentage; ?>%</span></td>
                            </tr>
                            <tr class="table-info">
                                <td><strong>Rata-rata Waktu Penyelesaian</strong></td>
                                <td colspan="2">
                                    <strong><?php echo round($sla_stats['avg_resolution_time'], 1); ?> Jam</strong>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="stats-table-container">
                    <h4 class="mb-4">
                        <i class="fas fa-list-alt me-2"></i>
                        Status Tiket Saat Ini
                    </h4>
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Status</th>
                                <th>Jumlah</th>
                                <th>Persentase</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><i class="fas fa-sync text-info me-2"></i>On Progress - CC</td>
                                <td><?php echo number_format($overall_stats['cc_progress']); ?></td>
                                <td>
                                    <?php 
                                    $cc_percentage = ($overall_stats['total_tickets'] > 0) ? 
                                        round(($overall_stats['cc_progress'] * 100) / $overall_stats['total_tickets'], 1) : 0; 
                                    ?>
                                    <span class="badge bg-info"><?php echo $cc_percentage; ?>%</span>
                                </td>
                            </tr>
                            <tr>
                                <td><i class="fas fa-cogs text-primary me-2"></i>On Progress - BOR</td>
                                <td><?php echo number_format($overall_stats['bor_progress']); ?></td>
                                <td>
                                    <?php 
                                    $bor_percentage = ($overall_stats['total_tickets'] > 0) ? 
                                        round(($overall_stats['bor_progress'] * 100) / $overall_stats['total_tickets'], 1) : 0; 
                                    ?>
                                    <span class="badge bg-primary"><?php echo $bor_percentage; ?>%</span>
                                </td>
                            </tr>
                            <tr>
                                <td><i class="fas fa-truck text-secondary me-2"></i>Waiting Dispatch</td>
                                <td><?php echo number_format($overall_stats['waiting_dispatch']); ?></td>
                                <td>
                                    <?php 
                                    $dispatch_percentage = ($overall_stats['total_tickets'] > 0) ? 
                                        round(($overall_stats['waiting_dispatch'] * 100) / $overall_stats['total_tickets'], 1) : 0; 
                                    ?>
                                    <span class="badge bg-secondary"><?php echo $dispatch_percentage; ?>%</span>
                                </td>
                            </tr>
                            <tr>
                                <td><i class="fas fa-clipboard-check text-success me-2"></i>Closed - Solved</td>
                                <td><?php echo number_format($overall_stats['solved_tickets']); ?></td>
                                <td>
                                    <?php 
                                    $solved_percentage = ($overall_stats['total_tickets'] > 0) ? 
                                        round(($overall_stats['solved_tickets'] * 100) / $overall_stats['total_tickets'], 1) : 0; 
                                    ?>
                                    <span class="badge bg-success"><?php echo $solved_percentage; ?>%</span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Recent Activity -->
        <div class="section-header">
            <div class="section-title">
                <i class="fas fa-clock me-2"></i>
                Aktivitas Terbaru
            </div>
            <div class="section-description">Tiket terbaru yang masuk ke sistem</div>
        </div>

        <div class="stats-table-container">
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th><i class="fas fa-hashtag me-1"></i>No</th>
                            <th><i class="fas fa-ticket-alt me-1"></i>Judul Tiket</th>
                            <th><i class="fas fa-info-circle me-1"></i>Status</th>
                            <th><i class="fas fa-calendar me-1"></i>Dibuat</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($recent_tickets) > 0): ?>
                            <?php foreach ($recent_tickets as $index => $ticket): ?>
                                <tr>
                                    <td><?php echo $index + 1; ?></td>
                                    <td>
                                        <div class="ticket-title">
                                            <?php echo htmlspecialchars(substr($ticket['title'], 0, 50)); ?>
                                            <?php if (strlen($ticket['title']) > 50): ?>...<?php endif; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <?php 
                                        $status_class = '';
                                        $status_icon = '';
                                        switch($ticket['status']) {
                                            case 'Open':
                                                $status_class = 'bg-warning';
                                                $status_icon = 'fas fa-plus-circle';
                                                break;
                                            case 'On Progress - Customer Care':
                                                $status_class = 'bg-info';
                                                $status_icon = 'fas fa-sync';
                                                break;
                                            case 'On Progress - BOR':
                                                $status_class = 'bg-primary';
                                                $status_icon = 'fas fa-cogs';
                                                break;
                                            case 'Waiting for Dispatch':
                                                $status_class = 'bg-secondary';
                                                $status_icon = 'fas fa-truck';
                                                break;
                                            case 'Closed - Solved':
                                                $status_class = 'bg-success';
                                                $status_icon = 'fas fa-check-circle';
                                                break;
                                            case 'Closed - Unsolved':
                                                $status_class = 'bg-danger';
                                                $status_icon = 'fas fa-times-circle';
                                                break;
                                            default:
                                                $status_class = 'bg-light text-dark';
                                                $status_icon = 'fas fa-question-circle';
                                        }
                                        ?>
                                        <span class="badge <?php echo $status_class; ?>">
                                            <i class="<?php echo $status_icon; ?> me-1"></i>
                                            <?php echo $ticket['status']; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <small class="text-muted">
                                            <i class="fas fa-clock me-1"></i>
                                            <?php 
                                            $created_date = new DateTime($ticket['created_at']);
                                            echo $created_date->format('d/m/Y H:i'); 
                                            ?>
                                        </small>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4" class="text-center text-muted py-4">
                                    <i class="fas fa-inbox fa-2x mb-2"></i><br>
                                    Belum ada tiket terbaru
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Performance Summary -->
        <div class="section-header">
            <div class="section-title">
                <i class="fas fa-chart-bar me-2"></i>
                Ringkasan Performa
            </div>
            <div class="section-description">Metrik kinerja layanan pelanggan</div>
        </div>

        <div class="row mb-4">
            <div class="col-lg-4">
                <div class="stats-table-container text-center">
                    <div class="mb-3">
                        <i class="fas fa-trophy fa-3x text-warning"></i>
                    </div>
                    <h3 class="text-primary"><?php echo $sla_4h_percentage; ?>%</h3>
                    <p class="text-muted mb-0">Target SLA 4 Jam</p>
                    <small class="text-success">
                        <i class="fas fa-arrow-up"></i> 
                        <?php echo ($sla_4h_percentage >= 80) ? 'Target Tercapai' : 'Perlu Peningkatan'; ?>
                    </small>
                </div>
            </div>
            
            <div class="col-lg-4">
                <div class="stats-table-container text-center">
                    <div class="mb-3">
                        <i class="fas fa-users fa-3x text-info"></i>
                    </div>
                    <h3 class="text-primary"><?php echo $tech_stats['active_technicians']; ?></h3>
                    <p class="text-muted mb-0">Teknisi Aktif</p>
                    <small class="text-info">
                        <i class="fas fa-tools"></i> 
                        Siap menangani lapangan
                    </small>
                </div>
            </div>
            
            <div class="col-lg-4">
                <div class="stats-table-container text-center">
                    <div class="mb-3">
                        <i class="fas fa-chart-line fa-3x text-success"></i>
                    </div>
                    <h3 class="text-primary"><?php echo $solved_percentage; ?>%</h3>
                    <p class="text-muted mb-0">Tingkat Penyelesaian</p>
                    <small class="text-success">
                        <i class="fas fa-check-circle"></i> 
                        Total diselesaikan
                    </small>
                </div>
            </div>
        </div>

        <!-- Statistik Tiket Per Hari Dalam Bulan Ini -->
            <div class="section-header mt-5">
                <div class="section-title">
                    <i class="fas fa-calendar-alt me-2"></i>
                    Statistik Tiket Per Hari 
                </div>
                <div class="section-description">Jumlah tiket Maintenance & Dismantle per hari</div>
            </div>

            <form method="get" class="mb-4">
                <label for="bulan" class="form-label fw-bold">Pilih Bulan:</label>
                <input type="month" id="bulan" name="bulan" class="form-control d-inline-block w-auto" value="<?php echo isset($_GET['bulan']) ? $_GET['bulan'] : date('Y-m'); ?>">
                <button type="submit" class="btn btn-primary ms-2">Tampilkan</button>
            </form>

            <div class="stats-table-container mb-4">
                <canvas id="chartTiketHarian" height="80"></canvas>
            </div>

            <div class="stats-table-container">
                <div class="table-responsive">
                    <table class="table table-bordered align-middle">
                        <thead class="table-primary">
                            <tr>
                                <th>Tanggal</th>
                                <th>Maintenance</th>
                                <th>Dismantle</th>
                                <th>Total Tiket</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($daily_ticket_stats) > 0): ?>
                                <?php foreach ($daily_ticket_stats as $row): ?>
                                    <tr>
                                        <td><?php echo date('d M Y', strtotime($row['tanggal'])); ?></td>
                                        <td><span class="badge bg-warning text-dark"><?php echo $row['maintenance_count']; ?></span></td>
                                        <td><span class="badge bg-info text-dark"><?php echo $row['dismantle_count']; ?></span></td>
                                        <td>
                                            <span class="badge bg-primary">
                                                <?php echo $row['maintenance_count'] + $row['dismantle_count']; ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" class="text-center text-muted">Belum ada data tiket bulan ini.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <th>Total Bulan Ini</th>
                                <td><span class="badge bg-warning text-dark"><?php echo $total_maintenance_bulan; ?></span></td>
                                <td><span class="badge bg-info text-dark"><?php echo $total_dismantle_bulan; ?></span></td>
                                <td><span class="badge bg-primary"><?php echo $total_tiket_bulan; ?></span></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>        

        <!-- Statistik Tiket Berdasarkan Kota -->
        <div class="section-header mt-5">
            <div class="section-title">
                <i class="fas fa-city me-2"></i>
                Statistik Tiket per Kota
            </div>
            <div class="section-description">Jumlah tiket Maintenance & Dismantle di setiap kota</div>
        </div>

        <div class="stats-table-container">
            <div class="table-responsive">
                <table class="table table-bordered align-middle">
                    <thead class="table-primary">
                        <tr>
                            <th>No</th>
                            <th>Kota</th>
                            <th>Total Tiket</th>
                            <th>Tiket Maintenance</th>
                            <th>Tiket Dismantle</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($city_stats) > 0): ?>
                            <?php foreach ($city_stats as $i => $row): ?>
                                <tr>
                                    <td><?php echo $i + 1; ?></td>
                                    <td><?php echo htmlspecialchars($row['kota']); ?></td>
                                    <td><span class="badge bg-primary"><?php echo $row['total_tiket']; ?></span></td>
                                    <td><span class="badge bg-warning text-dark"><?php echo $row['total_maintenance']; ?></span></td>
                                    <td><span class="badge bg-info text-dark"><?php echo $row['total_dismantle']; ?></span></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="text-center text-muted">Belum ada data tiket per kota.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    
        <!-- Statistik Complain Channel -->
        <div class="section-header mt-5">
            <div class="section-title">
                <i class="fas fa-headset me-2"></i>
                Statistik Complain Channel
            </div>
            <div class="section-description">Jumlah tiket berdasarkan channel pengaduan</div>
        </div>

        <div class="stats-table-container">
            <div class="table-responsive">
                <table class="table table-bordered align-middle">
                    <thead class="table-primary">
                        <tr>
                            <th>No</th>
                            <th>Complain Channel</th>
                            <th>Jumlah Tiket</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($channel_stats) > 0): ?>
                            <?php foreach ($channel_stats as $i => $row): ?>
                                <tr>
                                    <td><?php echo $i + 1; ?></td>
                                    <td><?php echo htmlspecialchars($row['complain_channel']); ?></td>
                                    <td><span class="badge bg-primary"><?php echo $row['total_channel']; ?></span></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="3" class="text-center text-muted">Belum ada data tiket berdasarkan channel pengaduan.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>



        <!-- Footer Actions -->
        <div class="text-center mt-5 mb-4">
            <a href="dashboard.php" class="btn btn-secondary btn-lg">
                <i class="fas fa-arrow-left me-2"></i>
                Kembali ke Dashboard
            </a>
        </div>

        <!-- Auto Refresh Notification -->
        <div class="text-center mt-3 mb-2">
            <small class="text-muted">
                <i class="fas fa-sync-alt me-1"></i>
                Halaman akan otomatis refresh setiap 5 menit untuk data terbaru
            </small>
        </div>
    </div> 

    <!-- Live timestamp display -->
    <div class="fixed-bottom">
        <div class="container-fluid">
            <div class="text-end pe-3 pb-2">
                <small id="last-update" class="text-muted"></small>
            </div>
        </div>
    </div>  

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
    const ctx = document.getElementById('chartTiketHarian').getContext('2d');
    const chartTiketHarian = new Chart(ctx, {
        type: 'line',
        data: {
            labels: <?php echo json_encode($chart_labels); ?>,
            datasets: [
                {
                    label: 'Maintenance',
                    data: <?php echo json_encode($chart_maintenance); ?>,
                    borderColor: '#ffc107',
                    backgroundColor: 'rgba(255,193,7,0.1)',
                    tension: 0.3,
                    fill: true
                },
                {
                    label: 'Dismantle',
                    data: <?php echo json_encode($chart_dismantle); ?>,
                    borderColor: '#17a2b8',
                    backgroundColor: 'rgba(23,162,184,0.1)',
                    tension: 0.3,
                    fill: true
                }
            ]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { position: 'top' },
                title: {
                    display: true,
                    text: 'Statistik Tiket Per Hari Bulan <?php echo date("F Y", strtotime($selected_year . "-" . $selected_month_num . "-01")); ?>'
                }
            },
            scales: {
                y: { beginAtZero: true, precision: 0 }
            }
        }
    });
    </script>
    
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Auto refresh setiap 5 menit (300000 ms)
        setTimeout(function() {
            location.reload();
        }, 300000);

        // Animation untuk angka yang berubah
        function animateNumbers() {
            const numbers = document.querySelectorAll('.kpi-number, .field-number');
            numbers.forEach(number => {
                const finalValue = parseInt(number.textContent.replace(/,/g, ''));
                const duration = 2000;
                const steps = 50;
                const increment = finalValue / steps;
                let currentValue = 0;
                let step = 0;

                const timer = setInterval(() => {
                    currentValue += increment;
                    step++;
                    
                    if (step >= steps) {
                        currentValue = finalValue;
                        clearInterval(timer);
                    }
                    
                    number.textContent = Math.floor(currentValue).toLocaleString();
                }, duration / steps);
            });
        }

        // Jalankan animasi saat halaman dimuat
        document.addEventListener('DOMContentLoaded', function() {
            setTimeout(animateNumbers, 500);
        });

        // Highlight cards on hover
        document.querySelectorAll('.kpi-card, .field-card').forEach(card => {
            card.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-8px) scale(1.02)';
            });
            
            card.addEventListener('mouseleave', function() {
                this.style.transform = 'translateY(0) scale(1)';
            });
        });

        // Update timestamp
        function updateTimestamp() {
            const now = new Date();
            const timestamp = now.toLocaleString('id-ID', {
                day: '2-digit',
                month: '2-digit', 
                year: 'numeric',
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit'
            });
            
            const timestampElement = document.getElementById('last-update');
            if (timestampElement) {
                timestampElement.textContent = `Terakhir diperbarui: ${timestamp}`;
            }
        }

        // Update timestamp setiap detik
        setInterval(updateTimestamp, 1000);
        updateTimestamp(); // Panggil sekali saat load

        // Smooth scroll untuk anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });
    </script>


<style>
        :root {
            --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --success-gradient: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            --warning-gradient: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
            --danger-gradient: linear-gradient(135deg, #ff9a9e 0%, #fecfef 100%);
            --info-gradient: linear-gradient(135deg, #a8edea 0%, #fed6e3 100%);
            --dark-gradient: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background: linear-gradient(135deg, #0d47a1 0%, #1976d2 100%);
            min-height: 100vh;
            margin: 0;
            font-family: 'Segoe UI', Arial, sans-serif;
        }

        .dashboard-header {
            background: white;
            color:rgb(0, 0, 0);
            padding: 2rem 0;
            margin-bottom: 2rem;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }

        .dashboard-title {
            font-size: 2.5rem;
            font-weight: 700;
            text-align: center;
            margin-bottom: 0.5rem;
            text-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .dashboard-subtitle {
            text-align: center;
            font-size: 1.1rem;
            opacity: 0.9;
            font-weight: 300;
        }

        .section-header {
            margin: 3rem 0 1.5rem 0;
            padding-bottom: 1rem;
            border-bottom: 3px solid #e9ecef;
            position: relative;
        }

        .section-header::before {
            content: '';
            position: absolute;
            bottom: -3px;
            left: 0;
            width: 60px;
            height: 3px;
            background: while;
            border-radius: 2px;
        }

        .section-title {
            font-size: 1.8rem;
            font-weight: 600;
            color:rgb(255, 255, 255);
            margin-bottom: 0.5rem;
        }

        .section-description {
            color:rgb(255, 255, 255);
            font-size: 1rem;
        }

        /* KPI Cards */
        .kpi-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 1.5rem;
            margin-bottom: 3rem;
        }

        .kpi-card {
            background: white;
            border-radius: 20px;
            padding: 2rem 1.5rem;
            box-shadow: 0 10px 25px rgba(0,0,0,0.08);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            border: 1px solid rgba(255,255,255,0.2);
        }

        .kpi-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(135deg, #0d47a1 0%, #1976d2 100%);
        }

        .kpi-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 40px rgba(0,0,0,0.12);
        }

        .kpi-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }

        .kpi-icon {
            width: 60px;
            height: 60px;
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: white;
        }

        .kpi-total .kpi-icon { background: var(--primary-gradient); }
        .kpi-active .kpi-icon { background: var(--warning-gradient); }
        .kpi-today .kpi-icon { background: var(--success-gradient); }
        .kpi-connectivity .kpi-icon { background: var(--info-gradient); }
        .kpi-speed .kpi-icon { background: var(--danger-gradient); }
        .kpi-equipment .kpi-icon { background: var(--dark-gradient); }

        .kpi-number {
            font-size: 2.5rem;
            font-weight: 700;
            color: #2c3e50;
            line-height: 1;
        }

        .kpi-label {
            font-size: 1.1rem;
            font-weight: 600;
            color: #495057;
            margin-top: 0.5rem;
        }

        .kpi-subtext {
            font-size: 0.9rem;
            color: #6c757d;
            margin-top: 0.25rem;
        }

        .kpi-trend {
            font-size: 0.8rem;
            padding: 0.25rem 0.5rem;
            border-radius: 15px;
            font-weight: 500;
        }

        .trend-up {
            background: #d4edda;
            color: #155724;
        }

        .trend-down {
            background: #f8d7da;
            color: #721c24;
        }

        /* Workflow Progress */
        .workflow-container {
            background: white;
            border-radius: 20px;
            padding: 2rem;
            box-shadow: 0 10px 25px rgba(0,0,0,0.08);
            margin-bottom: 3rem;
        }

        .workflow-pipeline {
            display: flex;
            justify-content: space-between;
            align-items: end;
            margin: 2rem 0;
            min-height: 200px;
            background: #f8f9fa;
            border-radius: 15px;
            padding: 1.5rem 1rem 0.5rem 1rem;
        }

        .workflow-stage {
            flex: 1;
            text-align: center;
            position: relative;
            margin: 0 0.5rem;
        }

        .stage-bar {
            width: 100%;
            min-height: 20px;
            border-radius: 8px 8px 0 0;
            transition: all 0.4s ease;
            position: relative;
            background: linear-gradient(135deg, #0d47a1 0%, #1976d2 100%);
        }

        .stage-bar.open { background: linear-gradient(to top, #ffc107, #ffeb3b); }
        .stage-bar.progress { background: linear-gradient(to top, #17a2b8, #20c997); }
        .stage-bar.bor { background: linear-gradient(to top, #fd7e14, #ff9800); }
        .stage-bar.dispatch { background: linear-gradient(to top, #6f42c1, #8e44ad); }
        .stage-bar.review { background: linear-gradient(to top, #e83e8c, #f06292); }
        .stage-bar.resolved { background: linear-gradient(to top, #28a745, #4caf50); }

        .stage-number {
            position: absolute;
            top: -35px;
            left: 50%;
            transform: translateX(-50%);
            font-size: 1.5rem;
            font-weight: 700;
            color: #2c3e50;
            background: white;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }

        .stage-label {
            margin-top: 1rem;
            font-size: 0.9rem;
            font-weight: 600;
            color: #495057;
        }

        /* Field Operations */
        .field-ops-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
        }

        .field-card {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            text-align: center;
            box-shadow: 0 8px 20px rgba(0,0,0,0.08);
            transition: all 0.3s ease;
            border-left: 4px solid transparent;
        }

        .field-card:nth-child(1) { border-left-color: #ffc107; }
        .field-card:nth-child(2) { border-left-color: #17a2b8; }
        .field-card:nth-child(3) { border-left-color: #fd7e14; }
        .field-card:nth-child(4) { border-left-color: #28a745; }
        .field-card:nth-child(5) { border-left-color: #6f42c1; }

        .field-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 25px rgba(0,0,0,0.12);
        }

        .field-icon {
            font-size: 2rem;
            margin-bottom: 1rem;
        }

        .field-number {
            font-size: 2rem;
            font-weight: 700;
            color: #2c3e50;
            margin-bottom: 0.5rem;
        }

        .field-label {
            font-size: 1rem;
            font-weight: 500;
            color: #6c757d;
        }

        /* Statistics Tables */
        .stats-table-container {
            background: white;
            border-radius: 20px;
            padding: 2rem;
            box-shadow: 0 10px 25px rgba(0,0,0,0.08);
            margin-bottom: 2rem;
        }

        .table {
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
        }

        .table thead th {
            background: linear-gradient(135deg, #0d47a1 0%, #1976d2 100%);
            color: white;
            font-weight: 600;
            border: none;
            padding: 1rem;
        }

        .table tbody td {
            padding: 1rem;
            border-bottom: 1px solid #e9ecef;
        }

        .table tbody tr:hover {
            background-color: #f8f9fa;
        }

        /* Tambahkan di <style> */
        .table tfoot th, .table tfoot td {
            font-weight: 600;
            background: #fff;
            color: #212529;
            text-align: left;
            vertical-align: middle;
            padding: 1rem;
        }

        /* Table Badges */
        .table .badge {
            font-size: 0.8rem;
            padding: 0.4rem 0.6rem;
            border-radius: 0.5rem;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .dashboard-title {
                font-size: 2rem;
            }
            
            .kpi-grid {
                grid-template-columns: 1fr;
            }
            
            .workflow-pipeline {
                flex-direction: column;
                align-items: center;
                min-height: auto;
            }
            
            .workflow-stage {
                margin: 1rem 0;
                width: 80%;
            }
            
            .stage-bar {
                height: 40px !important;
                width: 100%;
            }
        }

        /* Animation */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .kpi-card, .workflow-container, .stats-table-container {
            animation: fadeInUp 0.6s ease-out;
        }

        .kpi-card:nth-child(2) { animation-delay: 0.1s; }
        .kpi-card:nth-child(3) { animation-delay: 0.2s; }
        .kpi-card:nth-child(4) { animation-delay: 0.3s; }

        label[for="bulan"] {
            color: #fff !important;
        }
    </style>
    
</body>
</html>