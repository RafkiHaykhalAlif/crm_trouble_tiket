<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include 'config/db_connect.php';

// --- PENJAGA HALAMAN ---
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Cek apakah user adalah Admin IKR, kalau bukan redirect ke dashboard biasa
if ($_SESSION['user_role'] !== 'Admin IKR') {
    header('Location: dashboard.php');
    exit();
}

$admin_user_id = $_SESSION['user_id'];

// --- AMBIL DATA WORK ORDER YANG PERLU DI-HANDLE ADMIN IKR ---
$sql_get_work_orders = "SELECT 
    wo.id,
    wo.wo_code,
    wo.status as wo_status,
    wo.scheduled_visit_date,
    wo.assigned_to_vendor_id,
    wo.created_at as wo_created,
    t.id as ticket_id,
    t.ticket_code,
    t.jenis_tiket,
    t.title as ticket_title,
    t.description as ticket_description,
    t.status as ticket_status,
    c.full_name as customer_name,
    c.address as customer_address,
    c.phone_number as customer_phone,
    c.email as customer_email,
    u_dispatch.full_name as from_dispatch,
    u_vendor.full_name as assigned_vendor,
    u_vendor.id as vendor_id
FROM tr_work_orders wo
JOIN tr_tickets t ON wo.ticket_id = t.id
JOIN ms_customers c ON t.customer_id = c.id
JOIN ms_users u_dispatch ON wo.created_by_dispatch_id = u_dispatch.id
LEFT JOIN ms_users u_vendor ON wo.assigned_to_vendor_id = u_vendor.id
WHERE wo.status IN (
    'Pending',
    'Sent to Dispatch',
    'Received by Admin IKR',
    'Scheduled by Admin IKR',
    'In Progress',
    'Completed by Technician',
    'Reviewed by Dispatch',
    'Waiting For BOR Review',
    'Closed by BOR'
)
ORDER BY 
    CASE 
        WHEN wo.status = 'Sent to Dispatch' THEN 1
        WHEN wo.status = 'Completed by Technician' THEN 2
        WHEN wo.status = 'In Progress' THEN 3
        WHEN wo.status = 'Scheduled by Admin IKR' THEN 4
        WHEN wo.status = 'Received by Admin IKR' THEN 5
        WHEN wo.status = 'Reviewed by Dispatch' THEN 6
        WHEN wo.status = 'Waiting For BOR Review' THEN 7 
        WHEN wo.status = 'Closed by BOR' THEN 9
        ELSE 10 
    END,
    wo.created_at ASC";

$result_work_orders = mysqli_query($conn, $sql_get_work_orders);
$work_orders = mysqli_fetch_all($result_work_orders, MYSQLI_ASSOC);

// --- STATISTIK DASHBOARD ADMIN IKR ---
$sql_stats = "SELECT 
    SUM(CASE WHEN status = 'Received by Admin IKR' THEN 1 ELSE 0 END) as new_wo,
    SUM(CASE WHEN status = 'Scheduled by Admin IKR' THEN 1 ELSE 0 END) as scheduled_wo,
    SUM(CASE WHEN status = 'In Progress' THEN 1 ELSE 0 END) as inprogress_wo,
    SUM(CASE WHEN status IN ('Completed by Technician', 'Closed by BOR') THEN 1 ELSE 0 END) as completed_wo,
    SUM(CASE WHEN DATE(scheduled_visit_date) = CURDATE() AND status = 'Scheduled by Admin IKR' THEN 1 ELSE 0 END) as today_visits
FROM tr_work_orders wo
WHERE status IN ('Received by Admin IKR', 'Scheduled by Admin IKR', 'In Progress', 'Completed by Technician')";

$result_stats = mysqli_query($conn, $sql_stats);
$stats = mysqli_fetch_assoc($result_stats);

// --- AMBIL DAFTAR TEKNISI IKR YANG AVAILABLE ---
$sql_vendors = "SELECT 
    u.id, 
    u.full_name,
    COUNT(wo.id) as active_wo_count
FROM ms_users u
LEFT JOIN tr_work_orders wo ON u.id = wo.assigned_to_vendor_id AND wo.status IN ('Scheduled by Admin IKR', 'In Progress')
WHERE u.role = 'Vendor IKR' 
GROUP BY u.id, u.full_name
ORDER BY active_wo_count ASC, u.full_name";
$result_vendors = mysqli_query($conn, $sql_vendors);
$vendors = mysqli_fetch_all($result_vendors, MYSQLI_ASSOC);

// --- Cek pesan dari proses ---
$message = '';
if (isset($_GET['status'])) {
    switch($_GET['status']) {
        case 'scheduled':
            $message = '<div class="alert alert-success"> Work Order berhasil dijadwalkan dan di-assign ke teknisi!</div>';
            break;
        case 'reassigned':
            $message = '<div class="alert alert-success"> Teknisi berhasil di-reassign!</div>';
            break;
        case 'forwarded_to_dispatch':
            $message = '<div class="alert alert-success"> Work Order berhasil dikirim kembali ke Dispatch untuk review!</div>';
            break;
    }
}

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Dashboard Admin Back Office IKR</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

    <header class="main-header">
        <div class="container">
            <h1>Admin Back Office IKR - Work Order Scheduler</h1>
            <div class="user-info">
                <span>Selamat datang, <strong><?php echo htmlspecialchars($_SESSION['user_full_name']); ?></strong>!</span>
                <span class="user-role user-role-admin-ikr">[<?php echo htmlspecialchars($_SESSION['user_role']); ?>]</span>
                <a href="logout.php" class="btn-logout">Logout</a>
            </div>
        </div>
    </header>

    <main class="container">
        <?php echo $message; ?>
        
        <!-- Statistik Dashboard Admin IKR -->
        <div class="stats-grid">
            <div class="stat-card stat-new">
                <div class="stat-number"><?php echo $stats['new_wo']; ?></div>
                <div class="stat-label">New Work Orders</div>
                <div class="stat-icon"></div>
            </div>
            <div class="stat-card stat-scheduled">
                <div class="stat-number"><?php echo $stats['scheduled_wo']; ?></div>
                <div class="stat-label">Scheduled WO</div>
                <div class="stat-icon"></div>
            </div>
            <div class="stat-card stat-progress">
                <div class="stat-number"><?php echo $stats['inprogress_wo']; ?></div>
                <div class="stat-label">In Progress</div>
                <div class="stat-icon"></div>
            </div>
            <div class="stat-card stat-completed">
                <div class="stat-number"><?php echo $stats['completed_wo']; ?></div>
                <div class="stat-label">Completed by Tech</div>
                <div class="stat-icon"></div>
            </div>
            <div class="stat-card stat-today">
                <div class="stat-number"><?php echo $stats['today_visits']; ?></div>
                <div class="stat-label">Visits Today</div>
                <div class="stat-icon"></div>
            </div>
        </div>

        <div class="dashboard-admin-ikr-grid"> 
            <div class="admin-action-column">
                <!-- Quick Actions Sidebar -->
                <div class="quick-actions-box">
                    <div class="quick-actions-title">
                        <span style="font-size:1.3em; margin-right:6px;"></span>
                        Quick Actions
                    </div>
                    <hr class="quick-actions-divider">
                    <button class="quick-action-btn all" onclick="filterWO('all')">
                         All Work Orders
                    </button>
                    <button class="quick-action-btn new" onclick="filterWO('new')">
                        New WO (Need Schedule)
                    </button>
                    <button class="quick-action-btn scheduled" onclick="filterWO('scheduled')">
                        Scheduled WO
                    </button>
                    <button class="quick-action-btn inprogress" onclick="filterWO('inprogress')">
                        In Progress
                    </button>
                    <button class="quick-action-btn completed" onclick="filterWO('completed')">
                        Completed
                    </button>
                </div>
            </div>
            <!-- Kolom Work Orders -->
            <div class="work-order-list-column">
                <div class="work-orders-management">
                    <section class="card">
                        <h3> Work Orders Management</h3>

                        <form id="search-wo-form" style="margin-bottom:12px;">
                            <input type="text" id="search-wo-input" placeholder="Cari ID WO..." style="padding:8px 12px; border-radius:6px; border:1px solid #ccc; width:180px;">
                            <button type="submit" style="padding:8px 16px; border-radius:6px; border:none; background:#007bff; color:#fff;">Cari</button>
                        </form>
                        <div id="search-wo-message" style="margin-bottom:10px; color:#d9534f; font-weight:bold;"></div>
                        
                        <div class="table-container">
                            <div class="table-container-scroll">
                                <table class="table-wo" id="adminWorkOrderTable">
                                    <thead>
                                        <tr>
                                            <th>WO Code</th>
                                            <th>Jenis Tiket</th>
                                            <th>Customer & Issue</th>
                                            <th>Status</th>
                                            <th>Schedule</th>
                                            <th>Assigned Tech</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($work_orders)): ?>
                                            <tr>
                                                <td colspan="7" style="text-align: center; padding: 40px;">
                                                    <div class="no-tickets">
                                                        <div style="font-size: 48px; margin-bottom: 10px;"></div>
                                                        <h4>No Work Orders to manage!</h4>
                                                        <p>All work orders are handled or no new ones from Dispatch.</p>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php else: ?>
                                            <?php foreach ($work_orders as $wo): ?>
                                                <tr class="wo-row" data-id="<?php echo htmlspecialchars($wo['wo_code']); ?>"
                                                  data-wo-id="<?php echo $wo['id']; ?>"
                                                  data-status="<?php echo strtolower(htmlspecialchars($wo['wo_status'])); ?>"
                                                  data-visit-date="<?php echo htmlspecialchars($wo['scheduled_visit_date'] ?? ''); ?>"
                                                  data-vendor-id="<?php echo htmlspecialchars($wo['vendor_id'] ?? ''); ?>"
                                                >
                                                    <td>
                                                        <strong style="color: #6f42c1;"><?php echo htmlspecialchars($wo['wo_code']); ?></strong>
                                                        <br>
                                                        <small class="text-muted">
                                                            Ticket: <?php echo htmlspecialchars($wo['ticket_code']); ?>
                                                        </small>
                                                        <br>
                                                        <small class="text-info">
                                                            From: <?php echo htmlspecialchars($wo['from_dispatch']); ?>
                                                        </small>
                                                    </td>
                                                    <td>
                                                        <span class="badge badge-info" style="background-color:rgb(255, 255, 255); color: black;">
                                                            <?php echo htmlspecialchars($wo['jenis_tiket']); ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <div class="customer-info">
                                                            <strong><?php echo htmlspecialchars($wo['customer_name']); ?></strong>
                                                            <br>
                                                            <small style="color: #28a745;"> <?php echo htmlspecialchars($wo['customer_phone']); ?></small>
                                                            <br>
                                                            <small class="text-muted"> <?php echo htmlspecialchars(substr($wo['customer_address'], 0, 30)); ?>...</small>
                                                        </div>
                                                        <div class="issue-info" style="margin-top: 8px; padding: 6px; background-color: #f8f9fa; border-radius: 4px;">
                                                            <strong style="font-size: 12px; color: #495057;"><?php echo htmlspecialchars($wo['ticket_title']); ?></strong>
                                                            <br>
                                                            <small style="color: #666;"><?php echo htmlspecialchars(substr($wo['ticket_description'], 0, 50)); ?>...</small>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <?php 
                                                        $status_color = '';
                                                        switch($wo['wo_status']) {
                                                            case 'Received by Admin IKR': 
                                                                $status_color = 'background-color: #ffc107; color: #212529;'; 
                                                                break;
                                                            case 'Scheduled by Admin IKR': 
                                                                $status_color = 'background-color: #17a2b8; color: white;';
                                                                break;
                                                            case 'In Progress': 
                                                                $status_color = 'background-color: #fd7e14; color: white;';
                                                                break;
                                                            case 'Pending': 
                                                                $status_color = 'background-color: #ffc107; color: #212529;'; 
                                                                break;
                                                            case 'Completed by Technician': 
                                                                $status_color = 'background-color: #28a745; color: white;'; 
                                                                break;
                                                            case 'Reviewed by Dispatch': 
                                                                $status_color = 'background-color: #6c757d; color: white;'; 
                                                                break;
                                                            case 'Waiting For BOR Review': 
                                                                $status_color = 'background-color: #ffc107; color: #212529;'; 
                                                                break;
                                                            case 'Closed by BOR': 
                                                                $status_color = 'background-color: #6f42c1; color: white;'; 
                                                                break;
                                                        }
                                                        ?>
                                                        <span class="status" style="<?php echo $status_color; ?>">
                                                            <?php echo str_replace('by Admin IKR', '', $wo['wo_status']); ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <?php if ($wo['scheduled_visit_date']): ?>
                                                            <div style="color: #17a2b8; font-weight: 500;">
                                                                 <?php echo date('d/m/Y', strtotime($wo['scheduled_visit_date'])); ?>
                                                            </div>
                                                            <small style="color: #6c757d;">
                                                                 <?php echo date('H:i', strtotime($wo['scheduled_visit_date'])); ?>
                                                            </small>
                                                        <?php else: ?>
                                                            <span class="text-warning"> Not scheduled</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <?php if ($wo['assigned_vendor'] && $wo['wo_status'] != 'Received by Admin IKR'): ?>
                                                            <strong style="color: #28a745;"><?php echo htmlspecialchars($wo['assigned_vendor']); ?></strong>
                                                            <br><small class="text-success"> Assigned</small>
                                                        <?php else: ?>
                                                            <span class="text-warning"> Not assigned</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td class="admin-action-buttons">
                                                        <?php if ($wo['wo_status'] == 'Received by Admin IKR'): ?>
                                                            <button onclick="scheduleWO(<?php echo $wo['id']; ?>)" 
                                                                    class="btn-admin-action btn-schedule" title="Schedule & Assign">
                                                                 Schedule
                                                        </button>
                                                        <?php elseif ($wo['wo_status'] == 'Pending'): ?>
                                                            <button onclick="scheduleWO(<?php echo $wo['id']; ?>)" 
                                                                    class="btn-admin-action btn-schedule" title="Schedule & Assign">
                                                                Reschedule WO
                                                        </button>
                                                        <?php elseif ($wo['wo_status'] == 'Scheduled by Admin IKR'): ?>
                                                            <button onclick="editSchedule(<?php echo $wo['id']; ?>)" 
                                                                    class="btn-admin-action btn-edit" title="Edit Schedule & Technician">
                                                                 Edit
                                                            </button>
                                                        <?php elseif ($wo['wo_status'] == 'In Progress'): ?>
                                                            <span class="text-info" style="font-size: 12px;">
                                                                 Technician Working
                                                            </span>
                                                            <button onclick="contactTech(<?php echo $wo['vendor_id']; ?>)" 
                                                                    class="btn-admin-action btn-contact" title="Contact Technician">
                                                                 Contact
                                                            </button>
                                                        <?php elseif ($wo['wo_status'] == 'Completed by Technician'): ?>
                                                            <a href="view_work_report.php?wo_id=<?php echo $wo['id']; ?>" 
                                                               class="btn-admin-action btn-report" title="View Report">
                                                                 View Report
                                                            </a>
                                                        <?php endif; ?>
                                                        
                                                        <a href="view_work_order.php?id=<?php echo $wo['id']; ?>" 
                                                           class="btn-admin-action btn-view" title="View Details">
                                                             Details
                                                        </a>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </section>
                </div>
            </div>
        </div>
    </main>

    <!-- Modal untuk Schedule WO -->
    <div id="scheduleModal" class="modal" style="display: none;">
        <div class="modal-content">
            <span class="close" onclick="closeScheduleModal()">&times;</span>
            <h3> Schedule Work Order</h3>
            <form id="scheduleForm" action="proses_admin_schedule_wo.php" method="POST">
                <input type="hidden" id="scheduleWoId" name="wo_id">
                
                <div class="form-group">
                    <label for="visit_date">Scheduled Visit Date & Time</label>
                    <input type="datetime-local" id="visit_date" name="visit_date" required>
                </div>
                
                <div class="form-group">
                    <label for="assigned_vendor">Assign Technician</label>
                    <select id="assigned_vendor" name="assigned_vendor" required>
                        <option value="">Choose technician...</option>
                        <?php foreach ($vendors as $vendor): ?>
                            <option value="<?php echo $vendor['id']; ?>" data-workload="<?php echo $vendor['active_wo_count']; ?>">
                                <?php echo htmlspecialchars($vendor['full_name']); ?> 
                                (<?php echo $vendor['active_wo_count']; ?> active WO)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="special_notes">Special Instructions for Technician</label>
                    <textarea id="special_notes" name="special_notes" rows="3" 
                              placeholder="Any special instructions, tools needed, or customer notes..."></textarea>
                </div>
                
                <button type="submit" class="btn"> Schedule Work Order</button>
            </form>
        </div>
    </div>

    <!-- Modal untuk Edit Schedule & Technician -->
    <div id="editScheduleModal" class="modal" style="display: none;">
        <div class="modal-content">
            <span class="close" onclick="closeEditScheduleModal()">&times;</span>
            <h3>Edit Schedule & Technician</h3>
            <form id="editScheduleForm" action="proses_admin_edit_schedule.php" method="POST">
                <input type="hidden" id="editWoId" name="wo_id">
                
                <div class="form-group">
                    <label for="edit_visit_date">Scheduled Visit Date & Time</label>
                    <input type="datetime-local" id="edit_visit_date" name="visit_date" required>
                </div>
                
                <div class="form-group">
                    <label for="edit_assigned_vendor">Assign Technician</label>
                    <select id="edit_assigned_vendor" name="assigned_vendor" required>
                        <option value="">Choose technician...</option>
                        <?php foreach ($vendors as $vendor): ?>
                            <option value="<?php echo $vendor['id']; ?>">
                                <?php echo htmlspecialchars($vendor['full_name']); ?> 
                                (<?php echo $vendor['active_wo_count']; ?> active WO)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="edit_special_notes">Special Instructions for Technician</label>
                    <textarea id="edit_special_notes" name="special_notes" rows="3"></textarea>
                </div>
                
                <button type="submit" class="btn"> Save Changes</button>
            </form>
        </div>
    </div>

    <script>
    // Modal functions untuk schedule
    function scheduleWO(woId) {
        document.getElementById('scheduleWoId').value = woId;
        document.getElementById('scheduleModal').style.display = 'block';
        
        // Set minimum date to today
        const now = new Date();
        const tomorrow = new Date(now.getTime() + 24 * 60 * 60 * 1000);
        document.getElementById('visit_date').min = tomorrow.toISOString().slice(0, 16);
    }

    function closeScheduleModal() {
        document.getElementById('scheduleModal').style.display = 'none';
    }

    function editSchedule(woId) {
        const row = document.querySelector('tr[data-wo-id="' + woId + '"]');
        if (row) {
            // Prefill jadwal
            document.getElementById('edit_visit_date').value = row.dataset.visitDate || '';
            // Prefill teknisi
            document.getElementById('edit_assigned_vendor').value = row.dataset.vendorId || '';
        }
        document.getElementById('editWoId').value = woId;
        document.getElementById('editScheduleModal').style.display = 'block';
    }

    function closeEditScheduleModal() {
        document.getElementById('editScheduleModal').style.display = 'none';
    }

    // Tutup modal jika klik di luar
    window.onclick = function(event) {
        const modal = document.getElementById('editScheduleModal');
        if (event.target == modal) {
            modal.style.display = 'none';
        }
    }

    function reassignTech(woId) {
        window.location.href = 'reassign_technician.php?wo_id=' + woId;
    }

    function contactTech(vendorId) {
        // This could open a modal with technician contact info
        alert('Feature: Contact Technician - Will show technician contact details');
    }

    function forwardToDispatch(woId) {
        if (confirm('Forward this completed Work Order to Dispatch for final review?')) {
            window.location.href = 'forward_wo_to_dispatch.php?wo_id=' + woId;
        }
    }

    // Filter functions
    function showAllWO() {
        const rows = document.querySelectorAll('#adminWorkOrderTable tbody tr');
        rows.forEach(row => row.style.display = '');
    }

    function showNewWO() {
        const rows = document.querySelectorAll('#adminWorkOrderTable tbody tr');
        rows.forEach(row => {
            if (row.dataset.status === 'received-by-admin-ikr') {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    }

    function showScheduledWO() {
        const rows = document.querySelectorAll('#adminWorkOrderTable tbody tr');
        rows.forEach(row => {
            if (row.dataset.status === 'scheduled-by-admin-ikr') {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    }

    function showInProgressWO() {
        const rows = document.querySelectorAll('#adminWorkOrderTable tbody tr');
        rows.forEach(row => {
            if (row.dataset.status === 'in-progress') {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    }

    function showCompletedWO() {
        const rows = document.querySelectorAll('#adminWorkOrderTable tbody tr');
        rows.forEach(row => {
            const status = (row.dataset.status || '').trim().toLowerCase();
            if (
                status === 'scheduled by admin ikr' ||
                status === 'completed by technician' ||
                status === 'Closed by BOR'
            ) {
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

    // Modal functions untuk edit schedule & technician
    function closeEditScheduleModal() {
        document.getElementById('editScheduleModal').style.display = 'none';
    }

    // Pre-fill and open edit modal
    function openEditModal(woId, visitDate, assignedVendor, specialNotes) {
        document.getElementById('editWoId').value = woId;
        document.getElementById('edit_visit_date').value = visitDate;
        document.getElementById('edit_assigned_vendor').value = assignedVendor;
        document.getElementById('edit_special_notes').value = specialNotes;
        document.getElementById('editScheduleModal').style.display = 'block';
    }

    function filterWO(filter) {
        const rows = document.querySelectorAll('#adminWorkOrderTable tbody tr');
        rows.forEach(row => {
            row.style.display = ''; // Reset display
            const status = (row.dataset.status || '').trim().toLowerCase();
            
            if (filter === 'new' && status !== 'received by admin ikr') {
                row.style.display = 'none';
            } else if (filter === 'scheduled' && status !== 'scheduled by admin ikr') {
                row.style.display = 'none';
            } else if (filter === 'inprogress' && status !== 'in progress') {
                row.style.display = 'none';
            } else if (filter === 'completed' && status !== 'completed by technician' && status !== 'closed by bor') {
                row.style.display = 'none';
            }
        });
    }

const tableBody = document.querySelector('.table-wo tbody');
const originalRows = Array.from(document.querySelectorAll('.wo-row'));

document.getElementById('search-wo-form').addEventListener('submit', function(e) {
    e.preventDefault();
    const input = document.getElementById('search-wo-input').value.trim().toLowerCase();
    const messageDiv = document.getElementById('search-wo-message');
    messageDiv.textContent = '';

    // Reset tabel jika input kosong
    if (!input) {
        while (tableBody.firstChild) tableBody.removeChild(tableBody.firstChild);
        originalRows.forEach(row => tableBody.appendChild(row));
        return;
    }

    // Temukan baris yang cocok
    const found = originalRows.find(row => row.getAttribute('data-id').toLowerCase().includes(input));
    if (found) {
        while (tableBody.firstChild) tableBody.removeChild(tableBody.firstChild);
        tableBody.appendChild(found);
        originalRows.filter(row => row !== found).forEach(row => tableBody.appendChild(row));
        found.style.background = "#fffbe6";
        setTimeout(() => { found.style.background = ""; }, 1200);
    } else {
        while (tableBody.firstChild) tableBody.removeChild(tableBody.firstChild);
        originalRows.forEach(row => tableBody.appendChild(row));
        messageDiv.textContent = "WO tidak ditemukan.";
    }
});

// Reset pesan jika user mengetik ulang
document.getElementById('search-wo-input').addEventListener('input', function() {
    if (!this.value.trim()) {
        document.getElementById('search-wo-message').textContent = '';
        while (tableBody.firstChild) tableBody.removeChild(tableBody.firstChild);
        originalRows.forEach(row => tableBody.appendChild(row));
    }
});
    </script>

    <style>
    
    body {
    background: linear-gradient(135deg, #0d47a1 0%, #1976d2 100%);
    min-height: 100vh;
    margin: 0;
    font-family: 'Segoe UI', Arial, sans-serif;
    }
    
    /* Admin IKR specific styles */
    .user-role-admin-ikr {
        background-color: #6f42c1 !important;
    }

    .dashboard-admin-ikr-grid {
        display: grid;
        grid-template-columns: 320px 1fr; /* kiri 320px, kanan fleksibel */
        gap: 30px;
        align-items: flex-start;
    }

    .admin-action-column {
        /* Optional: agar sticky saat scroll */
        position: sticky;
        top: 20px;
    }

    @media (max-width: 992px) {
        .dashboard-admin-ikr-grid {
            grid-template-columns: 1fr;
        }
        .admin-action-column {
            position: static;
        }
    }

    /* New stat card colors for admin IKR */
    .stat-new {
        background: linear-gradient(135deg, #ffc107 0%, #fd7e14 100%);
    }

    .stat-scheduled {
        background: linear-gradient(135deg, #17a2b8 0%, #20c997 100%);
    }

    .stat-progress {
        background: linear-gradient(135deg, #fd7e14 0%, #dc3545 100%);
    }

    .stat-completed {
        background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
    }

    .stat-today {
        background: linear-gradient(135deg, #6f42c1 0%, #e83e8c 100%);
    }

    /* Technician Summary */
    .technician-summary {
        margin-top: 25px;
        padding: 20px;
        background-color: #f8f9fa;
        border-radius: 8px;
        border-left: 4px solid #6f42c1;
    }

    .technician-summary h4 {
        margin-top: 0;
        color: #6f42c1;
        font-size: 1rem;
        margin-bottom: 15px;
    }

    .workload-list {
        display: flex;
        flex-direction: column;
        gap: 8px;
    }

    .workload-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 8px 12px;
        background-color: white;
        border-radius: 6px;
        border: 1px solid #dee2e6;
    }

    .tech-name {
        font-weight: 500;
        color: #495057;
        font-size: 14px;
    }

    .workload-count {
        font-size: 12px;
        font-weight: bold;
        padding: 4px 8px;
        border-radius: 12px;
    }

    .low-load {
        background-color: #d1edff;
        color: #0c5460;
    }

    .medium-load {
        background-color: #fff3cd;
        color: #856404;
    }

    .high-load {
        background-color: #f8d7da;
        color: #721c24;
    }

    /* Admin action buttons */
    .admin-action-buttons {
        display: flex;
        flex-direction: column;
        gap: 4px;
        width: 100%;
    }

    .btn-admin-action {
        padding: 4px 8px;
        border: none;
        border-radius: 4px;
        font-size: 10px;
        font-weight: 500;
        cursor: pointer;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 3px;
        transition: all 0.2s ease;
        white-space: nowrap;
    }

    .btn-schedule {
        background-color: #ffc107;
        color: #212529;
    }

    .btn-edit {
        background-color: #17a2b8;
        color: white;
    }

    .btn-reassign {
        background-color: #6c757d;
        color: white;
    }

    .btn-contact {
        background-color: #28a745;
        color: white;
    }

    .btn-forward {
        background-color: #6f42c1;
        color: white;
    }

    .btn-report {
        background-color: #fd7e14;
        color: white;
    }

    .btn-admin-action:hover {
        transform: scale(1.05);
        opacity: 0.9;
    }

    /* Modal styling */
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

    /* Table enhancements */
    .customer-info {
        margin-bottom: 6px;
    }

    .issue-info {
        font-size: 12px;
    }

    /* Tambahkan di file CSS Anda */
.quick-actions-box {
    background: #fff;
    border-radius: 14px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.04);
    padding: 24px 18px 18px 18px;
    margin-bottom: 30px;
    min-width: 240px;
}

.quick-actions-title {
    font-size: 1.2em;
    font-weight: bold;
    margin-bottom: 8px;
    display: flex;
    align-items: center;
}

.quick-actions-divider {
    border: none;
    border-top: 1.5px solid #eee;
    margin: 0 0 18px 0;
}

.quick-action-btn {
    display: block;
    width: 100%;
    text-align: left;
    font-size: 1em;
    font-weight: 500;
    border: none;
    border-radius: 8px;
    padding: 12px 16px;
    margin-bottom: 12px;
    cursor: pointer;
    transition: background 0.15s;
    color: #fff;
}

.quick-action-btn.all { background: #007bff; }
.quick-action-btn.new { background: #ffc107; color: #222; }
.quick-action-btn.scheduled { background: #17a2b8; }
.quick-action-btn.inprogress { background:rgb(233, 36, 5); }
.quick-action-btn.completed { background:rgb(63, 253, 0); color: #222; border: 1px solid #e0e0e0; }

.quick-action-btn:hover {
    filter: brightness(0.95);
}

/* Tabel dengan garis */
.table-wo {
    width: 100%;
    border-collapse: collapse;
    background: #fff;
}

.table-wo th, .table-wo td {
    border: 1px solid #e0e0e0;
    padding: 10px 14px;
    text-align: left;
    vertical-align: middle;
}

/* Header lebih tebal */
.table-wo th {
    background: #fafbfc;
    font-weight: bold;
}

/* Scrollable container */
.table-container-scroll {
    max-height: 540px;
    overflow-y: auto;
    border-radius: 10px;
    box-shadow: 0 1px 8px rgba(0,0,0,0.03);
    background: #fff;
    margin-bottom: 20px;
}

/* Responsive design */
    @media (max-width: 768px) {
        .admin-action-buttons {
            gap: 2px;
        }
        
        .btn-admin-action {
            font-size: 9px;
            padding: 3px 6px;
        }

        .workload-item {
            padding: 6px 10px;
        }

        .tech-name {
            font-size: 13px;
        }

        .workload-count {
            font-size: 11px;
            padding: 3px 6px;
        }
    }
    </style>

</body>
</html>