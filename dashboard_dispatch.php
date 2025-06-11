<?php
include 'config/db_connect.php';

// --- PENJAGA HALAMAN ---
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Cek apakah user adalah Dispatch, kalau bukan redirect ke dashboard biasa
if ($_SESSION['user_role'] !== 'Dispatch') {
    header('Location: dashboard.php');
    exit();
}
// --- AKHIR DARI PENJAGA HALAMAN ---

// --- AMBIL DATA WORK ORDER SEMUA STATUS (UPDATED!) ---
$dispatch_user_id = $_SESSION['user_id'];

// Query untuk ambil SEMUA Work Order (termasuk completed & cancelled)
$sql_get_work_orders = "SELECT 
    wo.id,
    wo.wo_code,
    wo.status as wo_status,
    wo.scheduled_visit_date,
    wo.created_at as wo_created,
    t.id as ticket_id,
    t.ticket_code,
    t.title as ticket_title,
    t.description as ticket_description,
    t.status as ticket_status,
    c.full_name as customer_name,
    c.address as customer_address,
    c.phone_number as customer_phone,
    u_bor.full_name as created_by_bor,
    u_vendor.full_name as assigned_vendor
FROM tr_work_orders wo
JOIN tr_tickets t ON wo.ticket_id = t.id
JOIN ms_customers c ON t.customer_id = c.id
JOIN ms_users u_bor ON wo.created_by_dispatch_id = u_bor.id
LEFT JOIN ms_users u_vendor ON wo.assigned_to_vendor_id = u_vendor.id
ORDER BY 
    CASE 
        WHEN wo.status = 'Pending' THEN 1
        WHEN wo.status = 'Scheduled' THEN 2
        WHEN wo.status = 'Completed' THEN 3
        WHEN wo.status = 'Cancelled' THEN 4
        ELSE 5
    END,
    wo.created_at DESC"; // Yang terbaru dulu untuk completed

$result_work_orders = mysqli_query($conn, $sql_get_work_orders);
$work_orders = mysqli_fetch_all($result_work_orders, MYSQLI_ASSOC);

// --- STATISTIK DASHBOARD DISPATCH (UPDATED!) ---
$sql_stats = "SELECT 
    SUM(CASE WHEN status = 'Pending' THEN 1 ELSE 0 END) as pending_wo,
    SUM(CASE WHEN status = 'Scheduled' THEN 1 ELSE 0 END) as scheduled_wo,
    SUM(CASE WHEN status = 'Completed' THEN 1 ELSE 0 END) as completed_wo,
    SUM(CASE WHEN status = 'Cancelled' THEN 1 ELSE 0 END) as cancelled_wo
FROM tr_work_orders wo";

$result_stats = mysqli_query($conn, $sql_stats);
$stats = mysqli_fetch_assoc($result_stats);

// --- AMBIL DAFTAR TEKNISI IKR YANG AVAILABLE ---
$sql_vendors = "SELECT id, full_name FROM ms_users WHERE role = 'Vendor IKR' ORDER BY full_name";
$result_vendors = mysqli_query($conn, $sql_vendors);
$vendors = mysqli_fetch_all($result_vendors, MYSQLI_ASSOC);

// --- Cek pesan dari proses ---
$message = '';
if (isset($_GET['status'])) {
    switch($_GET['status']) {
        case 'scheduled':
            $message = '<div class="alert alert-success">📅 Work Order berhasil dijadwalkan!</div>';
            break;
        case 'assigned':
            $message = '<div class="alert alert-success">👨‍🔧 Teknisi berhasil di-assign ke Work Order!</div>';
            break;
        case 'updated':
            $message = '<div class="alert alert-success">📝 Work Order berhasil diupdate!</div>';
            break;
        case 'cancelled':
            $message = '<div class="alert alert-success">❌ Work Order berhasil dibatalkan!</div>';
            break;
    }
}

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Dashboard Dispatch - CRM Ticketing</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

    <header class="main-header">
        <div class="container">
            <h1>Dashboard Dispatch - Work Order Management</h1>
            <div class="user-info">
                <span>Selamat datang, <strong><?php echo htmlspecialchars($_SESSION['user_full_name']); ?></strong>!</span>
                <span class="user-role user-role-dispatch">[<?php echo htmlspecialchars($_SESSION['user_role']); ?>]</span>
                <a href="logout.php" class="btn-logout">Logout</a>
            </div>
        </div>
    </header>

    <main class="container">
        <?php echo $message; ?>
        
        <!-- Statistik Dashboard Dispatch (UPDATED STATS!) -->
        <div class="stats-grid">
            <div class="stat-card stat-pending">
                <div class="stat-number"><?php echo $stats['pending_wo']; ?></div>
                <div class="stat-label">Pending WO</div>
                <div class="stat-icon">⏳</div>
            </div>
            <div class="stat-card stat-scheduled">
                <div class="stat-number"><?php echo $stats['scheduled_wo']; ?></div>
                <div class="stat-label">Scheduled WO</div>
                <div class="stat-icon">📅</div>
            </div>
            <div class="stat-card stat-completed">
                <div class="stat-number"><?php echo $stats['completed_wo']; ?></div>
                <div class="stat-label">Completed WO</div>
                <div class="stat-icon">✅</div>
            </div>
            <div class="stat-card stat-cancelled">
                <div class="stat-number"><?php echo $stats['cancelled_wo']; ?></div>
                <div class="stat-label">Cancelled WO</div>
                <div class="stat-icon">❌</div>
            </div>
        </div>

        <div class="dashboard-dispatch-grid">
            
            <!-- Quick Actions Dispatch (UPDATED!) -->
            <div class="dispatch-action-column">
                <section class="card">
                    <h3>⚡ Quick Actions Dispatch</h3>
                    
                    <div class="quick-actions">
                        <button onclick="showAllWO()" class="btn-quick-action btn-primary">
                            📋 Lihat Semua WO
                        </button>
                        <button onclick="showPendingWO()" class="btn-quick-action btn-warning">
                            ⏳ WO Pending
                        </button>
                        <button onclick="showScheduledWO()" class="btn-quick-action btn-info">
                            📅 WO Scheduled
                        </button>
                        <button onclick="showCompletedWO()" class="btn-quick-action btn-success">
                            ✅ WO Completed
                        </button>
                        <button onclick="showCancelledWO()" class="btn-quick-action btn-danger">
                            ❌ WO Cancelled
                        </button>
                    </div>
                </section>
            </div>

            <!-- Daftar Work Orders -->
            <div class="work-order-list-column">
                <section class="card">
                    <h3>🛠️ Daftar Work Orders</h3>
                    
                    <div class="table-container">
                        <table class="ticket-table" id="workOrderTable">
                            <thead>
                                <tr>
                                    <th>WO Code</th>
                                    <th>Ticket Info</th>
                                    <th>Customer</th>
                                    <th>Status WO</th>
                                    <th>Created</th>
                                    <th>Assigned To</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($work_orders)): ?>
                                    <tr>
                                        <td colspan="7" style="text-align: center; padding: 40px;">
                                            <div class="no-tickets">
                                                <div style="font-size: 48px; margin-bottom: 10px;">🎉</div>
                                                <h4>Tidak ada Work Order!</h4>
                                                <p>Belum ada WO yang dibuat oleh BOR.</p>
                                            </div>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($work_orders as $wo): ?>
                                        <tr data-status="<?php echo strtolower($wo['wo_status']); ?>">
                                            <td>
                                                <strong><?php echo htmlspecialchars($wo['wo_code']); ?></strong>
                                                <br>
                                                <small class="text-muted">
                                                    Ticket: <?php echo htmlspecialchars($wo['ticket_code']); ?>
                                                </small>
                                            </td>
                                            <td>
                                                <div class="ticket-title">
                                                    <strong><?php echo htmlspecialchars($wo['ticket_title']); ?></strong>
                                                </div>
                                                <small class="text-muted">
                                                    <?php echo htmlspecialchars(substr($wo['ticket_description'], 0, 50)); ?>...
                                                </small>
                                                <br>
                                                <small>By: <?php echo htmlspecialchars($wo['created_by_bor']); ?></small>
                                            </td>
                                            <td>
                                                <strong><?php echo htmlspecialchars($wo['customer_name']); ?></strong>
                                                <br>
                                                <small class="text-muted">
                                                    <?php echo htmlspecialchars($wo['customer_phone']); ?>
                                                </small>
                                                <br>
                                                <small class="text-muted">
                                                    <?php echo htmlspecialchars(substr($wo['customer_address'], 0, 30)); ?>...
                                                </small>
                                            </td>
                                            <td>
                                                <?php 
                                                $wo_status_class = strtolower($wo['wo_status']); 
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
                                                <?php if ($wo['scheduled_visit_date']): ?>
                                                    <br>
                                                    <small class="text-info">
                                                        📅 <?php echo date('d/m/Y H:i', strtotime($wo['scheduled_visit_date'])); ?>
                                                    </small>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <small><?php echo date('d/m/Y H:i', strtotime($wo['wo_created'])); ?></small>
                                            </td>
                                            <td>
                                                <?php if ($wo['assigned_vendor']): ?>
                                                    <strong><?php echo htmlspecialchars($wo['assigned_vendor']); ?></strong>
                                                    <br><small class="text-success">✅ Assigned</small>
                                                <?php else: ?>
                                                    <span class="text-warning">⏳ Belum assign</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="dispatch-action-buttons">
                                                <?php if ($wo['wo_status'] == 'Pending'): ?>
                                                    <button onclick="scheduleWO(<?php echo $wo['id']; ?>)" 
                                                            class="btn-dispatch-action btn-schedule" title="Jadwalkan WO">
                                                        📅 Schedule
                                                    </button>
                                                    <button onclick="assignTechnician(<?php echo $wo['id']; ?>)" 
                                                            class="btn-dispatch-action btn-assign" title="Assign Teknisi">
                                                        👨‍🔧 Assign
                                                    </button>
                                                <?php elseif ($wo['wo_status'] == 'Scheduled'): ?>
                                                    <button onclick="editSchedule(<?php echo $wo['id']; ?>)" 
                                                            class="btn-dispatch-action btn-edit" title="Edit Jadwal">
                                                        ✏️ Edit
                                                    </button>
                                                <?php elseif ($wo['wo_status'] == 'Completed'): ?>
                                                    <span class="text-success" style="font-size: 12px;">
                                                        ✅ Selesai
                                                    </span>
                                                    <a href="view_work_report.php?wo_id=<?php echo $wo['id']; ?>" 
                                                       class="btn-dispatch-action btn-report" title="Lihat Laporan Detail">
                                                        📋 Laporan
                                                    </a>
                                                <?php elseif ($wo['wo_status'] == 'Cancelled'): ?>
                                                    <span class="text-danger" style="font-size: 12px;">
                                                        ❌ Dibatalkan
                                                    </span>
                                                <?php endif; ?>
                                                
                                                <a href="view_work_order.php?id=<?php echo $wo['id']; ?>" 
                                                   class="btn-dispatch-action btn-view" title="Lihat Detail WO">
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

    <!-- Modal untuk Schedule WO -->
    <div id="scheduleModal" class="modal" style="display: none;">
        <div class="modal-content">
            <span class="close" onclick="closeModal()">&times;</span>
            <h3>📅 Jadwalkan Work Order</h3>
            <form id="scheduleForm" action="proses_schedule_wo.php" method="POST">
                <input type="hidden" id="woId" name="wo_id">
                
                <div class="form-group">
                    <label for="visit_date">Tanggal Kunjungan</label>
                    <input type="datetime-local" id="visit_date" name="visit_date" required>
                </div>
                
                <div class="form-group">
                    <label for="assigned_vendor">Assign ke Teknisi</label>
                    <select id="assigned_vendor" name="assigned_vendor" required>
                        <option value="">Pilih Teknisi...</option>
                        <?php foreach ($vendors as $vendor): ?>
                            <option value="<?php echo $vendor['id']; ?>">
                                <?php echo htmlspecialchars($vendor['full_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <button type="submit" class="btn">Jadwalkan WO</button>
            </form>
        </div>
    </div>

    <script>
    // Modal functions
    function scheduleWO(woId) {
        document.getElementById('woId').value = woId;
        document.getElementById('scheduleModal').style.display = 'block';
    }

    function assignTechnician(woId) {
        // Untuk sekarang, redirect ke halaman assign (bisa dikembangkan jadi modal juga)
        window.location.href = 'assign_technician.php?wo_id=' + woId;
    }

    function editSchedule(woId) {
        window.location.href = 'edit_schedule.php?wo_id=' + woId;
    }

    function closeModal() {
        document.getElementById('scheduleModal').style.display = 'none';
    }

    // Filter functions (UPDATED WITH NEW FILTERS!)
    function showAllWO() {
        const rows = document.querySelectorAll('#workOrderTable tbody tr');
        rows.forEach(row => row.style.display = '');
    }

    function showPendingWO() {
        const rows = document.querySelectorAll('#workOrderTable tbody tr');
        rows.forEach(row => {
            if (row.dataset.status === 'pending') {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    }

    function showScheduledWO() {
        const rows = document.querySelectorAll('#workOrderTable tbody tr');
        rows.forEach(row => {
            if (row.dataset.status === 'scheduled') {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    }

    // NEW FILTER FUNCTIONS!
    function showCompletedWO() {
        const rows = document.querySelectorAll('#workOrderTable tbody tr');
        rows.forEach(row => {
            if (row.dataset.status === 'completed') {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    }

    function showCancelledWO() {
        const rows = document.querySelectorAll('#workOrderTable tbody tr');
        rows.forEach(row => {
            if (row.dataset.status === 'cancelled') {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    }

    // Close modal when clicking outside
    window.onclick = function(event) {
        const modal = document.getElementById('scheduleModal');
        if (event.target == modal) {
            modal.style.display = 'none';
        }
    }
    </script>

    <style>
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

    .user-role-dispatch {
        background-color: #17a2b8 !important;
    }

    .dashboard-dispatch-grid {
        display: grid;
        grid-template-columns: 1fr 2fr;
        gap: 30px;
    }

    @media (max-width: 992px) {
        .dashboard-dispatch-grid {
            grid-template-columns: 1fr;
        }
    }

    .dispatch-action-buttons {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 6px;
        width: 100%;
    }

    .dispatch-action-buttons .btn-view {
        grid-column: 1 / -1;
    }

    .btn-dispatch-action {
        padding: 5px 8px;
        border: none;
        border-radius: 4px;
        font-size: 10px;
        font-weight: 500;
        cursor: pointer;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 3px;
        transition: all 0.2s ease;
        white-space: nowrap;
        text-align: center;
    }

    .btn-schedule {
        background-color: #17a2b8;
        color: white;
    }

    .btn-assign {
        background-color: #28a745;
        color: white;
    }

    .btn-edit {
        background-color: #ffc107;
        color: #212529;
    }

    .btn-report {
        background-color: #6f42c1;
        color: white;
    }

    .btn-report:hover {
        background-color: #5a32a3;
    }

    /* NEW STYLES FOR NEW BUTTONS */
    .btn-success {
        background-color: #28a745;
        color: white;
    }

    .btn-success:hover {
        background-color: #218838;
        transform: translateX(5px);
    }

    .btn-danger {
        background-color: #dc3545;
        color: white;
    }

    .btn-danger:hover {
        background-color: #c82333;
        transform: translateX(5px);
    }

    /* NEW STAT CARD STYLES */
    .stat-pending {
        background: linear-gradient(135deg, #ffc107 0%, #ff8c00 100%);
    }

    .stat-scheduled {
        background: linear-gradient(135deg, #17a2b8 0%, #138496 100%);
    }

    .stat-completed {
        background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
    }

    .stat-cancelled {
        background: linear-gradient(135deg, #dc3545 0%, #fd7e14 100%);
    }
    </style>

</body>
</html>