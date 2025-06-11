<?php
include 'config/db_connect.php';

// --- PENJAGA HALAMAN ---
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Cek apakah user adalah Vendor IKR, kalau bukan redirect ke dashboard biasa
if ($_SESSION['user_role'] !== 'Vendor IKR') {
    header('Location: dashboard.php');
    exit();
}
// --- AKHIR DARI PENJAGA HALAMAN ---

$vendor_user_id = $_SESSION['user_id'];

// --- AMBIL DATA WORK ORDER YANG DI-ASSIGN KE TEKNISI INI ---
$sql_get_my_work_orders = "SELECT 
    wo.id,
    wo.wo_code,
    wo.status as wo_status,
    wo.scheduled_visit_date,
    wo.visit_report,
    wo.started_at,
    wo.estimated_duration,
    wo.actual_duration,
    wo.pre_work_notes,
    wo.created_at as wo_created,
    t.id as ticket_id,
    t.ticket_code,
    t.title as ticket_title,
    t.description as ticket_description,
    t.status as ticket_status,
    c.full_name as customer_name,
    c.address as customer_address,
    c.phone_number as customer_phone,
    c.email as customer_email,
    u_dispatch.full_name as assigned_by_dispatch
FROM tr_work_orders wo
JOIN tr_tickets t ON wo.ticket_id = t.id
JOIN ms_customers c ON t.customer_id = c.id
JOIN ms_users u_dispatch ON wo.created_by_dispatch_id = u_dispatch.id
WHERE wo.assigned_to_vendor_id = '$vendor_user_id'
ORDER BY 
    CASE 
        WHEN wo.status = 'In Progress' THEN 1
        WHEN wo.status = 'Scheduled' AND DATE(wo.scheduled_visit_date) = CURDATE() THEN 2
        WHEN wo.status = 'Scheduled' AND wo.scheduled_visit_date > NOW() THEN 3
        WHEN wo.status = 'Scheduled' AND wo.scheduled_visit_date < NOW() THEN 4
        WHEN wo.status = 'Pending' THEN 5
        WHEN wo.status = 'Completed' THEN 6
        WHEN wo.status = 'Cancelled' THEN 7
        ELSE 8
    END,
    wo.scheduled_visit_date ASC";

$result_work_orders = mysqli_query($conn, $sql_get_my_work_orders);
$work_orders = mysqli_fetch_all($result_work_orders, MYSQLI_ASSOC);

// --- STATISTIK DASHBOARD VENDOR IKR ---
$sql_stats = "SELECT 
    SUM(CASE WHEN status = 'Scheduled' AND DATE(scheduled_visit_date) = CURDATE() THEN 1 ELSE 0 END) as today_visits,
    SUM(CASE WHEN status = 'Scheduled' AND scheduled_visit_date > NOW() THEN 1 ELSE 0 END) as upcoming_visits,
    SUM(CASE WHEN status = 'Completed' AND DATE(wo.created_at) >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) THEN 1 ELSE 0 END) as completed_this_month,
    SUM(CASE WHEN status = 'Scheduled' AND scheduled_visit_date < NOW() THEN 1 ELSE 0 END) as overdue_visits,
    SUM(CASE WHEN status = 'In Progress' THEN 1 ELSE 0 END) as in_progress_visits
FROM tr_work_orders wo
WHERE assigned_to_vendor_id = '$vendor_user_id'";

$result_stats = mysqli_query($conn, $sql_stats);
$stats = mysqli_fetch_assoc($result_stats);

// --- Cek pesan dari proses ---
$message = '';
if (isset($_GET['status'])) {
    switch($_GET['status']) {
        case 'work_started':
            $message = '<div class="alert alert-success">🚀 Work Order berhasil dimulai! Selamat bekerja!</div>';
            break;
        case 'work_completed':
            $message = '<div class="alert alert-success">✅ Work Order berhasil diselesaikan dengan laporan detail!</div>';
            break;
        case 'completed':
            $message = '<div class="alert alert-success">✅ Work Order berhasil diselesaikan!</div>';
            break;
        case 'updated':
            $message = '<div class="alert alert-success">📝 Status Work Order berhasil diupdate!</div>';
            break;
        case 'report_submitted':
            $message = '<div class="alert alert-success">📋 Laporan kunjungan berhasil disimpan!</div>';
            break;
        case 'error_start':
            $message = '<div class="alert alert-error">❌ Gagal memulai Work Order. Silakan coba lagi.</div>';
            break;
        case 'error_complete':
            $message = '<div class="alert alert-error">❌ Gagal menyelesaikan Work Order. Silakan coba lagi.</div>';
            break;
        case 'error_missing_data':
            $message = '<div class="alert alert-error">❌ Data tidak lengkap. Mohon isi semua field yang diperlukan.</div>';
            break;
    }
}

// Function untuk format tanggal Indonesia
function formatTanggalIndonesia($datetime) {
    if (!$datetime) return '-';
    
    $tanggal = new DateTime($datetime);
    $bulan = [
        1 => 'Jan', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr',
        5 => 'Mei', 6 => 'Jun', 7 => 'Jul', 8 => 'Agu',
        9 => 'Sep', 10 => 'Okt', 11 => 'Nov', 12 => 'Des'
    ];
    
    $hari = $tanggal->format('d');
    $bulan_nama = $bulan[(int)$tanggal->format('m')];
    $jam = $tanggal->format('H:i');
    
    return "$hari $bulan_nama $jam";
}

function getStatusPriority($wo) {
    if ($wo['wo_status'] == 'In Progress') {
        return 'in-progress';
    } elseif ($wo['wo_status'] == 'Scheduled') {
        $visit_date = new DateTime($wo['scheduled_visit_date']);
        $today = new DateTime();
        
        if ($visit_date->format('Y-m-d') == $today->format('Y-m-d')) {
            return 'today';
        } elseif ($visit_date < $today) {
            return 'overdue';
        } else {
            return 'upcoming';
        }
    }
    return strtolower($wo['wo_status']);
}

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Dashboard Teknisi IKR - <?php echo htmlspecialchars($_SESSION['user_full_name']); ?></title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

    <header class="main-header">
        <div class="container">
            <h1>🔧 Dashboard Teknisi IKR</h1>
            <div class="user-info">
                <span>Selamat datang, <strong><?php echo htmlspecialchars($_SESSION['user_full_name']); ?></strong>!</span>
                <span class="user-role user-role-vendor">[<?php echo htmlspecialchars($_SESSION['user_role']); ?>]</span>
                <a href="logout.php" class="btn-logout">Logout</a>
            </div>
        </div>
    </header>

    <main class="container">
        <?php echo $message; ?>
        
        <!-- Statistik Dashboard IKR -->
        <div class="stats-grid">
            <div class="stat-card stat-today">
                <div class="stat-number"><?php echo $stats['today_visits']; ?></div>
                <div class="stat-label">Kunjungan Hari Ini</div>
                <div class="stat-icon">🗓️</div>
            </div>
            <div class="stat-card stat-progress">
                <div class="stat-number"><?php echo $stats['in_progress_visits']; ?></div>
                <div class="stat-label">Sedang Dikerjakan</div>
                <div class="stat-icon">🔧</div>
            </div>
            <div class="stat-card stat-upcoming">
                <div class="stat-number"><?php echo $stats['upcoming_visits']; ?></div>
                <div class="stat-label">Kunjungan Mendatang</div>
                <div class="stat-icon">📅</div>
            </div>
            <div class="stat-card stat-completed-month">
                <div class="stat-number"><?php echo $stats['completed_this_month']; ?></div>
                <div class="stat-label">Selesai Bulan Ini</div>
                <div class="stat-icon">✅</div>
            </div>
            <?php if ($stats['overdue_visits'] > 0): ?>
            <div class="stat-card stat-overdue">
                <div class="stat-number"><?php echo $stats['overdue_visits']; ?></div>
                <div class="stat-label">Terlambat</div>
                <div class="stat-icon">⚠️</div>
            </div>
            <?php endif; ?>
        </div>

        <div class="dashboard-vendor-grid">
            
            <!-- Quick Actions IKR -->
            <div class="vendor-action-column">
                <section class="card">
                    <h3>⚡ Quick Actions</h3>
                    
                    <div class="quick-actions">
                        <button onclick="showAllWO()" class="btn-quick-action btn-primary">
                            📋 Semua WO
                        </button>
                        <button onclick="showInProgressWO()" class="btn-quick-action btn-warning">
                            🔧 Sedang Dikerjakan
                        </button>
                        <button onclick="showTodayWO()" class="btn-quick-action btn-danger">
                            🗓️ Hari Ini
                        </button>
                        <button onclick="showUpcomingWO()" class="btn-quick-action btn-info">
                            📅 Mendatang
                        </button>
                        <button onclick="showCompletedWO()" class="btn-quick-action btn-success">
                            ✅ Selesai
                        </button>
                        <?php if ($stats['overdue_visits'] > 0): ?>
                        <button onclick="showOverdueWO()" class="btn-quick-action btn-danger">
                            ⚠️ Terlambat (<?php echo $stats['overdue_visits']; ?>)
                        </button>
                        <?php endif; ?>
                    </div>

                    <div class="vendor-info-box">
                        <h4>📋 Info Teknisi</h4>
                        <p><strong>Total WO:</strong> <?php echo count($work_orders); ?> assignment</p>
                        <?php if ($stats['in_progress_visits'] > 0): ?>
                        <p><strong>🔧 Aktif:</strong> <span style="color: #fd7e14; font-weight: 600;"><?php echo $stats['in_progress_visits']; ?> WO sedang dikerjakan</span></p>
                        <?php endif; ?>
                        <?php if ($stats['today_visits'] > 0): ?>
                        <p><strong>Prioritas:</strong> <span style="color: #dc3545; font-weight: 600;">Ada <?php echo $stats['today_visits']; ?> kunjungan hari ini!</span></p>
                        <?php endif; ?>
                        <?php if ($stats['overdue_visits'] > 0): ?>
                        <p><strong>⚠️ Peringatan:</strong> <span style="color: #dc3545; font-weight: 600;"><?php echo $stats['overdue_visits']; ?> kunjungan terlambat</span></p>
                        <?php endif; ?>
                        <?php if ($stats['completed_this_month'] > 0): ?>
                        <p><strong>📊 Performa:</strong> <span style="color: #28a745; font-weight: 600;"><?php echo $stats['completed_this_month']; ?> WO selesai bulan ini</span></p>
                        <?php endif; ?>
                    </div>
                </section>
            </div>

            <!-- Daftar Work Orders Teknisi -->
            <div class="work-order-list-column">
                <section class="card">
                    <h3>🛠️ Work Orders Saya</h3>
                    
                    <div class="table-container">
                        <table class="ticket-table" id="vendorWorkOrderTable">
                            <thead>
                                <tr>
                                    <th>WO Code</th>
                                    <th>Customer & Masalah</th>
                                    <th>Jadwal Kunjungan</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($work_orders)): ?>
                                    <tr>
                                        <td colspan="5" style="text-align: center; padding: 40px;">
                                            <div class="no-tickets">
                                                <div style="font-size: 48px; margin-bottom: 10px;">🎉</div>
                                                <h4>Belum ada Work Order!</h4>
                                                <p>Anda belum mendapat assignment WO dari Dispatch.</p>
                                            </div>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($work_orders as $wo): ?>
                                        <?php $priority = getStatusPriority($wo); ?>
                                        <tr data-status="<?php echo $priority; ?>" class="wo-row-<?php echo $priority; ?>">
                                            <td>
                                                <strong style="color: #17a2b8;"><?php echo htmlspecialchars($wo['wo_code']); ?></strong>
                                                <br>
                                                <small class="text-muted">
                                                    Ticket: <?php echo htmlspecialchars($wo['ticket_code']); ?>
                                                </small>
                                                <?php if ($wo['started_at']): ?>
                                                <br>
                                                <small style="color: #fd7e14; font-weight: 500;">
                                                    🚀 Mulai: <?php echo formatTanggalIndonesia($wo['started_at']); ?>
                                                </small>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div class="customer-info">
                                                    <strong><?php echo htmlspecialchars($wo['customer_name']); ?></strong>
                                                    <br>
                                                    <small style="color: #28a745; font-weight: 500;">📞 <?php echo htmlspecialchars($wo['customer_phone']); ?></small>
                                                    <br>
                                                    <small class="text-muted">📍 <?php echo htmlspecialchars(substr($wo['customer_address'], 0, 40)); ?>...</small>
                                                </div>
                                                <div class="ticket-problem" style="margin-top: 8px; padding: 8px; background-color: #f8f9fa; border-radius: 4px;">
                                                    <strong style="font-size: 13px; color: #495057;"><?php echo htmlspecialchars($wo['ticket_title']); ?></strong>
                                                    <br>
                                                    <small style="color: #666;"><?php echo htmlspecialchars(substr($wo['ticket_description'], 0, 60)); ?>...</small>
                                                </div>
                                            </td>
                                            <td>
                                                <?php if ($wo['scheduled_visit_date']): ?>
                                                    <?php
                                                    $visit_date = new DateTime($wo['scheduled_visit_date']);
                                                    $today = new DateTime();
                                                    $diff = $visit_date->diff($today);
                                                    
                                                    if ($visit_date->format('Y-m-d') == $today->format('Y-m-d')) {
                                                        $date_color = '#dc3545'; // Merah untuk hari ini
                                                        $date_label = 'HARI INI';
                                                    } elseif ($visit_date < $today) {
                                                        $date_color = '#dc3545'; // Merah untuk terlambat
                                                        $date_label = 'TERLAMBAT';
                                                    } else {
                                                        $date_color = '#28a745'; // Hijau untuk mendatang
                                                        $date_label = $diff->days . ' hari lagi';
                                                    }
                                                    ?>
                                                    <div style="color: <?php echo $date_color; ?>; font-weight: 600;">
                                                        📅 <?php echo formatTanggalIndonesia($wo['scheduled_visit_date']); ?>
                                                    </div>
                                                    <small style="color: <?php echo $date_color; ?>; font-weight: 500;">
                                                        <?php echo $date_label; ?>
                                                    </small>
                                                    <?php if ($wo['estimated_duration']): ?>
                                                    <br>
                                                    <small class="text-muted">
                                                        ⏱️ Est: <?php echo $wo['estimated_duration']; ?> menit
                                                    </small>
                                                    <?php endif; ?>
                                                <?php else: ?>
                                                    <span class="text-muted">Belum dijadwalkan</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php 
                                                $status_color = '';
                                                $status_icon = '';
                                                switch($wo['wo_status']) {
                                                    case 'Pending': 
                                                        $status_color = 'background-color: #ffc107; color: #212529;'; 
                                                        $status_icon = '⏳';
                                                        break;
                                                    case 'In Progress': 
                                                        $status_color = 'background-color: #fd7e14; color: white;';
                                                        $status_icon = '🔧';
                                                        break;
                                                    case 'Scheduled': 
                                                        if ($priority == 'today') {
                                                            $status_color = 'background-color: #dc3545; color: white;';
                                                            $status_icon = '🔥';
                                                        } elseif ($priority == 'overdue') {
                                                            $status_color = 'background-color: #dc3545; color: white;';
                                                            $status_icon = '⚠️';
                                                        } else {
                                                            $status_color = 'background-color: #17a2b8; color: white;';
                                                            $status_icon = '📅';
                                                        }
                                                        break;
                                                    case 'Completed': 
                                                        $status_color = 'background-color: #28a745; color: white;'; 
                                                        $status_icon = '✅';
                                                        break;
                                                    case 'Cancelled': 
                                                        $status_color = 'background-color: #6c757d; color: white;'; 
                                                        $status_icon = '❌';
                                                        break;
                                                }
                                                ?>
                                                <span class="status" style="<?php echo $status_color; ?>">
                                                    <?php echo $status_icon; ?> <?php echo htmlspecialchars($wo['wo_status']); ?>
                                                </span>
                                            </td>
                                            <td class="vendor-action-buttons">
                                                <?php if ($wo['wo_status'] == 'Scheduled'): ?>
                                                    <button onclick="startWork(<?php echo $wo['id']; ?>)" 
                                                            class="btn-vendor-action btn-start" title="Mulai Kerjakan">
                                                        🚀 Mulai
                                                    </button>
                                                    <button onclick="completeWork(<?php echo $wo['id']; ?>)" 
                                                            class="btn-vendor-action btn-complete" title="Selesaikan WO">
                                                        ✅ Selesai
                                                    </button>
                                                <?php elseif ($wo['wo_status'] == 'In Progress'): ?>
                                                    <button onclick="completeWork(<?php echo $wo['id']; ?>)" 
                                                            class="btn-vendor-action btn-complete" title="Selesaikan WO" 
                                                            style="background-color: #dc3545;">
                                                        ✅ Selesaikan
                                                    </button>
                                                    <span class="text-info" style="font-size: 12px;">
                                                        🔧 Sedang Dikerjakan
                                                    </span>
                                                <?php elseif ($wo['wo_status'] == 'Completed'): ?>
                                                    <?php if ($wo['visit_report']): ?>
                                                        <span class="text-success" style="font-size: 12px;">
                                                            📋 Laporan OK
                                                        </span>
                                                    <?php else: ?>
                                                        <button onclick="addReport(<?php echo $wo['id']; ?>)" 
                                                                class="btn-vendor-action btn-report" title="Tambah Laporan">
                                                            📝 Laporan
                                                        </button>
                                                    <?php endif; ?>
                                                <?php endif; ?>
                                                
                                                <a href="view_work_order.php?id=<?php echo $wo['id']; ?>" 
                                                   class="btn-vendor-action btn-view" title="Lihat Detail">
                                                    👁 Detail
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </section>
            </div>

        </div>
    </main>

    <!-- Modal untuk Start Work -->
    <div id="startModal" class="modal" style="display: none;">
        <div class="modal-content">
            <span class="close" onclick="closeStartModal()">&times;</span>
            <h3>🚀 Mulai Work Order</h3>
            <form id="startForm" action="proses_start_work.php" method="POST">
                <input type="hidden" id="startWoId" name="wo_id">
                
                <div class="form-group">
                    <label>
                        <input type="checkbox" id="location_confirmed" name="location_confirmed" required>
                        ✅ Konfirmasi: Saya sudah sampai di lokasi customer
                    </label>
                </div>
                
                <div class="form-group">
                    <label for="estimated_duration">Estimasi Waktu Pengerjaan (menit)</label>
                    <input type="number" id="estimated_duration" name="estimated_duration" 
                           min="5" max="480" value="60" required>
                    <small class="text-muted">Estimasi berapa menit untuk menyelesaikan pekerjaan ini</small>
                </div>
                
                <div class="form-group">
                    <label for="pre_work_notes">Catatan Awal (Optional)</label>
                    <textarea id="pre_work_notes" name="pre_work_notes" rows="3" 
                              placeholder="Kondisi awal, observasi masalah, atau catatan lainnya..."></textarea>
                </div>
                
                <button type="submit" class="btn">🚀 Mulai Kerja</button>
            </form>
        </div>
    </div>

    <!-- Enhanced Modal untuk Complete Work Order -->
    <div id="completeModal" class="modal" style="display: none;">
        <div class="modal-content modal-large">
            <span class="close" onclick="closeCompleteModal()">&times;</span>
            <h3>✅ Selesaikan Work Order</h3>
            <form id="completeForm" action="proses_complete_work_enhanced.php" method="POST">
                <input type="hidden" id="completeWoId" name="wo_id">
                
                <!-- Basic Completion Info -->
                <div class="form-section">
                    <h4>📋 Informasi Penyelesaian</h4>
                    
                    <div class="form-group">
                        <label for="completion_status">Status Penyelesaian</label>
                        <select id="completion_status" name="completion_status" required>
                            <option value="">Pilih status...</option>
                            <option value="Solved">✅ Masalah Berhasil Diperbaiki</option>
                            <option value="Partial">⚠️ Diperbaiki Sebagian (Perlu Follow-up)</option>
                            <option value="Cannot Fix">❌ Tidak Bisa Diperbaiki</option>
                            <option value="Customer Not Available">👤 Customer Tidak Ada</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="work_description">Deskripsi Pekerjaan yang Dilakukan</label>
                        <textarea id="work_description" name="work_description" rows="4" 
                                  placeholder="Tuliskan detail pekerjaan yang dilakukan:&#10;- Masalah yang ditemukan&#10;- Tindakan yang dilakukan&#10;- Kondisi akhir&#10;- Catatan tambahan" required></textarea>
                    </div>
                </div>

                <!-- Equipment Changes -->
                <div class="form-section">
                    <h4>🔧 Pergantian Equipment</h4>
                    
                    <div class="form-group">
                        <label for="equipment_replaced">Equipment yang Diganti</label>
                        <textarea id="equipment_replaced" name="equipment_replaced" rows="2"
                                  placeholder="Contoh: Ganti router Huawei HG8245H ke ZTE F609, Serial: ABC123"></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="cables_replaced">Kabel yang Diganti</label>
                        <textarea id="cables_replaced" name="cables_replaced" rows="2"
                                  placeholder="Contoh: Ganti kabel UTP Cat6 sepanjang 20 meter, Ganti kabel fiber indoor 5 meter"></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="new_installations">Instalasi Baru</label>
                        <textarea id="new_installations" name="new_installations" rows="2"
                                  placeholder="Contoh: Pasang splitter 1:2, Tambah socket baru di kamar"></textarea>
                    </div>
                </div>

                <!-- Technical Measurements -->
                <div class="form-section">
                    <h4>📊 Pengukuran Teknis</h4>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="signal_before">Signal Sebelum (dBm)</label>
                            <input type="text" id="signal_before" name="signal_before" 
                                   placeholder="Contoh: -15.2 dBm">
                        </div>
                        
                        <div class="form-group">
                            <label for="signal_after">Signal Sesudah (dBm)</label>
                            <input type="text" id="signal_after" name="signal_after" 
                                   placeholder="Contoh: -12.8 dBm">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="speed_test_result">Hasil Speed Test</label>
                        <input type="text" id="speed_test_result" name="speed_test_result" 
                               placeholder="Contoh: Download: 95 Mbps, Upload: 45 Mbps, Ping: 12ms">
                    </div>
                </div>

                <!-- Materials Used -->
                <div class="form-section">
                    <h4>📦 Material yang Digunakan</h4>
                    <div id="materials-container">
                        <div class="material-row">
                            <div class="form-row">
                                <div class="form-group">
                                    <input type="text" name="materials[0][name]" placeholder="Nama material">
                                </div>
                                <div class="form-group" style="width: 80px;">
                                    <input type="number" name="materials[0][quantity]" placeholder="Qty" min="1">
                                </div>
                                <div class="form-group" style="width: 80px;">
                                    <input type="text" name="materials[0][unit]" placeholder="Unit">
                                </div>
                                <div class="form-group">
                                    <input type="text" name="materials[0][notes]" placeholder="Catatan">
                                </div>
                            </div>
                        </div>
                    </div>
                    <button type="button" onclick="addMaterialRow()" class="btn-small">+ Tambah Material</button>
                </div>

                <!-- Customer Feedback -->
                <div class="form-section">
                    <h4>👤 Feedback Customer</h4>
                    
                    <div class="form-group">
                        <label for="customer_satisfaction">Kepuasan Customer</label>
                        <select id="customer_satisfaction" name="customer_satisfaction">
                            <option value="">Pilih tingkat kepuasan...</option>
                            <option value="Very Satisfied">😊 Sangat Puas</option>
                            <option value="Satisfied">🙂 Puas</option>
                            <option value="Neutral">😐 Biasa Saja</option>
                            <option value="Unsatisfied">😞 Tidak Puas</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="customer_notes">Catatan dari Customer</label>
                        <textarea id="customer_notes" name="customer_notes" rows="2"
                                  placeholder="Komentar, keluhan, atau request tambahan dari customer..."></textarea>
                    </div>
                </div>
                
                <button type="submit" class="btn btn-large">✅ Selesaikan Work Order</button>
            </form>
        </div>
    </div>

    <script>
    // Modal functions untuk start work
    function startWork(woId) {
        document.getElementById('startWoId').value = woId;
        document.getElementById('startModal').style.display = 'block';
    }

    function closeStartModal() {
        document.getElementById('startModal').style.display = 'none';
    }

    // Modal functions untuk complete WO
    function completeWork(woId) {
        document.getElementById('completeWoId').value = woId;
        document.getElementById('completeModal').style.display = 'block';
    }

    function closeCompleteModal() {
        document.getElementById('completeModal').style.display = 'none';
    }

    function addReport(woId) {
        // Redirect ke halaman add report atau buka modal
        window.location.href = 'add_report.php?wo_id=' + woId;
    }

    // Function untuk add material row
    let materialIndex = 1;
    function addMaterialRow() {
        const container = document.getElementById('materials-container');
        const newRow = document.createElement('div');
        newRow.className = 'material-row';
        newRow.innerHTML = `
            <div class="form-row">
                <div class="form-group">
                    <input type="text" name="materials[${materialIndex}][name]" placeholder="Nama material">
                </div>
                <div class="form-group" style="width: 80px;">
                    <input type="number" name="materials[${materialIndex}][quantity]" placeholder="Qty" min="1">
                </div>
                <div class="form-group" style="width: 80px;">
                    <input type="text" name="materials[${materialIndex}][unit]" placeholder="Unit">
                </div>
                <div class="form-group">
                    <input type="text" name="materials[${materialIndex}][notes]" placeholder="Catatan">
                </div>
                <button type="button" onclick="removeMaterialRow(this)" class="btn-remove">×</button>
            </div>
        `;
        container.appendChild(newRow);
        materialIndex++;
    }

    function removeMaterialRow(button) {
        button.closest('.material-row').remove();
    }

    // Filter functions
    function showAllWO() {
        const rows = document.querySelectorAll('#vendorWorkOrderTable tbody tr');
        rows.forEach(row => row.style.display = '');
    }

    function showTodayWO() {
        const rows = document.querySelectorAll('#vendorWorkOrderTable tbody tr');
        rows.forEach(row => {
            if (row.dataset.status === 'today') {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    }

    function showUpcomingWO() {
        const rows = document.querySelectorAll('#vendorWorkOrderTable tbody tr');
        rows.forEach(row => {
            if (row.dataset.status === 'upcoming') {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    }

    function showCompletedWO() {
        const rows = document.querySelectorAll('#vendorWorkOrderTable tbody tr');
        rows.forEach(row => {
            if (row.dataset.status === 'completed') {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    }

    function showOverdueWO() {
        const rows = document.querySelectorAll('#vendorWorkOrderTable tbody tr');
        rows.forEach(row => {
            if (row.dataset.status === 'overdue') {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    }

    // Close modal when clicking outside
    window.onclick = function(event) {
        const completeModal = document.getElementById('completeModal');
        const startModal = document.getElementById('startModal');
        
        if (event.target == completeModal) {
            completeModal.style.display = 'none';
        }
        if (event.target == startModal) {
            startModal.style.display = 'none';
        }
    }
    </script>

    <style>
    /* Vendor IKR specific styles */
    .user-role-vendor {
        background-color: #28a745 !important;
    }

    .dashboard-vendor-grid {
        display: grid;
        grid-template-columns: 1fr 2fr;
        gap: 30px;
    }

    @media (max-width: 992px) {
        .dashboard-vendor-grid {
            grid-template-columns: 1fr;
        }
    }

    /* New stat card colors for vendor */
    .stat-today {
        background: linear-gradient(135deg, #dc3545 0%, #fd7e14 100%);
    }

    .stat-upcoming {
        background: linear-gradient(135deg, #17a2b8 0%, #20c997 100%);
    }

    .stat-completed-month {
        background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
    }

    .stat-overdue {
        background: linear-gradient(135deg, #dc3545 0%, #6f42c1 100%);
    }

    /* Work Order row highlighting */
    .wo-row-today {
        background-color: #fff5f5 !important;
        border-left: 4px solid #dc3545;
    }

    .wo-row-overdue {
        background-color: #fff5f5 !important;
        border-left: 4px solid #dc3545;
        animation: pulse 2s infinite;
    }

    @keyframes pulse {
        0% { border-left-color: #dc3545; }
        50% { border-left-color: #fd7e14; }
        100% { border-left-color: #dc3545; }
    }

    .wo-row-upcoming {
        background-color: #f0f8ff !important;
        border-left: 4px solid #17a2b8;
    }

    .wo-row-completed {
        background-color: #f8fff8 !important;
        border-left: 4px solid #28a745;
    }

    /* Vendor action buttons */
    .vendor-action-buttons {
        display: flex;
        flex-direction: column;
        gap: 6px;
        width: 100%;
    }

    .btn-vendor-action {
        padding: 5px 10px;
        border: none;
        border-radius: 4px;
        font-size: 11px;
        font-weight: 500;
        cursor: pointer;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 4px;
        transition: all 0.2s ease;
        white-space: nowrap;
    }

    .btn-start {
        background-color: #007bff;
        color: white;
    }

    .btn-start:hover {
        background-color: #0056b3;
        transform: scale(1.05);
    }

    .btn-complete {
        background-color: #28a745;
        color: white;
    }

    .btn-complete:hover {
        background-color: #218838;
        transform: scale(1.05);
    }

    .btn-report {
        background-color: #ffc107;
        color: #212529;
    }

    .btn-report:hover {
        background-color: #e0a800;
        transform: scale(1.05);
    }

    .vendor-info-box {
        background-color: #f8f9fa;
        padding: 15px;
        border-radius: 6px;
        border-left: 4px solid #28a745;
        margin-top: 20px;
    }

    .vendor-info-box h4 {
        margin-top: 0;
        color: #28a745;
        font-size: 1rem;
    }

    .vendor-info-box p {
        margin: 8px 0;
        font-size: 14px;
    }

    .customer-info {
        margin-bottom: 8px;
    }

    .ticket-problem {
        font-size: 12px;
    }

    /* Modal styles */
    .modal {
        position: fixed;
        z-index: 1000;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0,0,0,0.5);
    }

    .modal-content {
        background-color: white;
        margin: 5% auto;
        padding: 30px;
        border-radius: 10px;
        width: 90%;
        max-width: 500px;
        position: relative;
    }

    .close {
        position: absolute;
        right: 15px;
        top: 15px;
        font-size: 28px;
        font-weight: bold;
        cursor: pointer;
    }

    .close:hover {
        color: #dc3545;
    }

    /* Responsive */
    @media (max-width: 768px) {
        .vendor-action-buttons {
            gap: 4px;
        }
        
        .btn-vendor-action {
            font-size: 10px;
            padding: 4px 8px;
        }
    }
    </style>

</body>
</html>