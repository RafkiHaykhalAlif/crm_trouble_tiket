<?php
include 'config/db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

if ($_SESSION['user_role'] !== 'Dispatch') {
    header('Location: dashboard.php');
    exit();
}

$dispatch_user_id = $_SESSION['user_id'];

$sql_get_work_orders = "SELECT 
    wo.id,
    wo.wo_code,
    wo.status as wo_status,
    wo.scheduled_visit_date,
    wo.created_at as wo_created,
    wo.visit_report,
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
LEFT JOIN ms_users u_admin ON wo.scheduled_by_admin_id = u_admin.id
LEFT JOIN ms_users u_vendor ON wo.assigned_to_vendor_id = u_vendor.id
WHERE wo.status IN ('Pending', 'Sent to Dispatch', 'Received by Admin IKR', 'Scheduled by Admin IKR', 'In Progress', 'Completed by Technician', 'Reviewed by Dispatch', 'Waiting For BOR Review', 'Closed by BOR')
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

$sql_stats = "SELECT 
    SUM(CASE WHEN status = 'Sent to Dispatch' THEN 1 ELSE 0 END) as new_from_bor,
    SUM(CASE WHEN status = 'Received by Admin IKR' THEN 1 ELSE 0 END) as with_admin_ikr,
    SUM(CASE WHEN status = 'Scheduled by Admin IKR' THEN 1 ELSE 0 END) as scheduled_wo,
    SUM(CASE WHEN status = 'In Progress' THEN 1 ELSE 0 END) as in_progress_wo,
    SUM(CASE WHEN status = 'Completed by Technician' THEN 1 ELSE 0 END) as need_review,
    SUM(CASE WHEN status = 'Reviewed by Dispatch' THEN 1 ELSE 0 END) as reviewed_wo
FROM tr_work_orders wo";

$result_stats = mysqli_query($conn, $sql_stats);
$stats = mysqli_fetch_assoc($result_stats);

$message = '';
if (isset($_GET['status'])) {
    switch($_GET['status']) {
        case 'forwarded_to_admin':
            $message = '<div class="alert alert-success">Work Order berhasil diteruskan ke Admin Back Office IKR!</div>';
            break;
        case 'reviewed_and_sent_to_bor':
            $message = '<div class="alert alert-success">Work Order berhasil di-review dan dikirim ke BOR untuk penutupan ticket!</div>';
            break;
        case 'additional_notes_added':
            $message = '<div class="alert alert-success">Catatan tambahan berhasil ditambahkan!</div>';
            break;
    }
}

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Dashboard Dispatch - Work Order Coordinator</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

    <header class="main-header">
        <div class="container">
            <h1>Dashboard Dispatch - Work Order Coordinator</h1>
            <div class="user-info">
                <span>Selamat datang, <strong><?php echo htmlspecialchars($_SESSION['user_full_name']); ?></strong>!</span>
                <span class="user-role user-role-dispatch">[<?php echo htmlspecialchars($_SESSION['user_role']); ?>]</span>
                <a href="logout.php" class="btn-logout">Logout</a>
            </div>
        </div>
    </header>

    <main class="container">
        <?php echo $message; ?>
        
        <div class="stats-grid">
            <div class="stat-card stat-new">
                <div class="stat-number"><?php echo $stats['new_from_bor']; ?></div>
                <div class="stat-label">New from BOR</div>
            </div>
            <div class="stat-card stat-admin">
                <div class="stat-number"><?php echo $stats['with_admin_ikr']; ?></div>
                <div class="stat-label">With Admin IKR</div>
            </div>
            <div class="stat-card stat-scheduled">
                <div class="stat-number"><?php echo $stats['scheduled_wo']; ?></div>
                <div class="stat-label">Scheduled</div>
            </div>
            <div class="stat-card stat-progress">
                <div class="stat-number"><?php echo $stats['in_progress_wo']; ?></div>
                <div class="stat-label">In Progress</div>
            </div>
            <div class="stat-card stat-review">
                <div class="stat-number"><?php echo $stats['need_review']; ?></div>
                <div class="stat-label">Need Review</div>
            </div>
        </div>

        <div class="dashboard-row" style="display: flex; gap: 24px; align-items: stretch;">
            <section class="card" style="flex: 1;">
                <h3>Work Order Flow Control</h3>
                
                <div class="quick-actions">
                    <button onclick="showAllWO()" class="btn-quick-action btn-primary">
                        All Work Orders
                    </button>
                    <button onclick="showNewFromBOR()" class="btn-quick-action btn-warning">
                        New from BOR
                    </button>
                    <button onclick="showWithAdminIKR()" class="btn-quick-action btn-info">
                        With Admin IKR
                    </button>
                    <button onclick="showInProgress()" class="btn-quick-action btn-success">
                        In Progress
                    </button>
                    <button onclick="showNeedReview()" class="btn-quick-action btn-danger">
                        Need Review
                    </button>
                </div>

            </section>
            <section class="card" style="flex: 2; display: flex; flex-direction: column;">
                <h3>Work Orders Pipeline</h3>
                
                <form id="searchWOForm" style="margin-bottom: 12px; display: flex; gap: 8px; max-width: 400px;">
                    <input type="text" id="searchWOInput" placeholder="Cari ID WO..." 
                        style="padding: 6px 12px; border-radius: 6px; border: 1px solid #ccc; flex: 1; max-width: 220px;">
                    <button type="submit" style="padding: 6px 18px; border-radius: 6px; background: #1976d2; color: #fff; border: none; font-weight: 500; cursor: pointer;">Cari</button>
                </form>

                <div class="table-container" style="max-width: 100%; overflow-x: auto;">
                    <div style="max-height: 570px; overflow-y: auto;">
                        <table id="dispatchWorkOrderTable" class="ticket-table">
                            <thead>
                                <tr>
                                    <th>WO Code</th>
                                    <th>Customer & Issue</th>
                                    <th>Status</th>
                                    <th>Progress Info</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($work_orders)): ?>
                                    <tr>
                                        <td colspan="6" style="text-align: center; padding: 40px;">
                                            <div class="no-tickets">
                                                <div style="font-size: 48px; margin-bottom: 10px;">🎉</div>
                                                <h4>No Work Orders in pipeline!</h4>
                                                <p>All work orders are completed or no new ones from BOR.</p>
                                            </div>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($work_orders as $wo): ?>
                                        <tr data-woid="<?php echo htmlspecialchars($wo['wo_code']); ?>">
                                            <td>
                                                <strong style="color: #17a2b8;"><?php echo htmlspecialchars($wo['wo_code']); ?></strong>
                                                <br>
                                                <small class="text-muted">
                                                    Ticket: <?php echo htmlspecialchars($wo['ticket_code']); ?>
                                                </small>
                                                <br>
                                                <small class="text-info">
                                                    From: <?php echo htmlspecialchars($wo['created_by_bor']); ?>
                                                </small>
                                            </td>
                                
                                            <td>
                                                <div class="customer-info">
                                                    <strong><?php echo htmlspecialchars($wo['customer_name']); ?></strong>
                                                    <br>
                                                    <small style="color: #28a745;"> <?php echo htmlspecialchars($wo['customer_phone']); ?></small>
                                                </div>
                                                <div class="issue-info" style="margin-top: 6px; padding: 6px; background-color: #f8f9fa; border-radius: 4px;">
                                                    <strong style="font-size: 12px; color: #495057;"><?php echo htmlspecialchars($wo['ticket_title']); ?></strong>
                                                    <br>
                                                    <small style="color: #666;"><?php echo htmlspecialchars(substr($wo['ticket_description'], 0, 40)); ?>...</small>
                                                </div>
                                            </td>
                                            <td>
                                                <?php 
                                                $status_color = '';
                                                switch($wo['wo_status']) {
                                                    case 'Sent to Dispatch': 
                                                        $status_color = 'background-color: #ffc107; color: #212529;'; 
                                                        break;
                                                    case 'Received by Admin IKR': 
                                                        $status_color = 'background-color: #6f42c1; color: white;';
                                                        break;
                                                    case 'Scheduled by Admin IKR': 
                                                        $status_color = 'background-color: #17a2b8; color: white;';
                                                        break;
                                                    case 'In Progress': 
                                                        $status_color = 'background-color: #fd7e14; color: white;';
                                                        break;
                                                    case 'Completed by Technician': 
                                                        $status_color = 'background-color: #28a745; color: white;'; 
                                                        break;
                                                    case 'Reviewed by Dispatch': 
                                                        $status_color = 'background-color: #dc3545; color: white;'; 
                                                        break;
                                                    case 'Waiting For BOR Review': 
                                                        $status_color = 'background-color: #6f42c1; color: white;'; 
                                                        break;
                                                    case 'Closed':
                                                        $status_color = 'background-color: #adb5bd; color: #343a40;';
                                                        break;
                                                    default:
                                                        $status_color = 'background-color: #dee2e6; color: #495057;';
                                                        break;
                                                }
                                                ?>
                                                <span style="display: inline-block; min-width: 120px; text-align: center; padding: 6px 16px; border-radius: 16px; font-weight: 500; <?php echo $status_color; ?>">
                                                    <?php echo htmlspecialchars($wo['wo_status']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php if ($wo['scheduled_visit_date']): ?>
                                                    <div style="color: #17a2b8; font-weight: 500; font-size: 12px;">
                                                         <?php echo date('d/m/Y H:i', strtotime($wo['scheduled_visit_date'])); ?>
                                                    </div>
                                                <?php endif; ?>
                                                
                                                <?php if ($wo['assigned_vendor']): ?>
                                                    <div style="color: #28a745; font-weight: 500; font-size: 12px;">
                                                         <?php echo htmlspecialchars($wo['assigned_vendor']); ?>
                                                    </div>
                                                    <div style="color: #6f42c1; font-size: 11px;">
                                                        Assigned Tech: <?php echo htmlspecialchars($wo['assigned_vendor']); ?>
                                                    </div>
                                                <?php else: ?>
                                                    <div style="color: #fd7e14; font-weight: 500; font-size: 12px;">
                                                        Created by BOR
                                                    </div>
                                                    <div style="color: #6c757d; font-size: 11px;">
                                                        <?php echo htmlspecialchars($wo['created_by_bor']); ?>
                                                    </div>
                                                    <div style="color: #6c757d; font-size: 11px;">
                                                         Awaiting technician assignment
                                                    </div>
                                                <?php endif; ?>
                                                
                                                <?php if ($wo['wo_status'] == 'Sent to Dispatch'): ?>
                                                    <div style="color: #ffc107; font-size: 11px; margin-top: 4px;">
                                                         Ready for Admin IKR assignment
                                                    </div>
                                                <?php endif; ?>
                                            </td>
                                            <td class="dispatch-action-buttons">
                                                <?php if ($wo['wo_status'] == 'Sent to Dispatch'): ?>
                                                    <button onclick="forwardToAdminIKR(<?php echo $wo['id']; ?>)" 
                                                            class="btn-dispatch-action btn-forward" title="Forward to Admin IKR">
                                                         Forward
                                                    </button>
                                                    
                                                <?php elseif ($wo['wo_status'] == 'Completed by Technician'): ?>
                                                    <button onclick="openCloseWoModal(<?php echo $wo['id']; ?>)" 
                                                            class="btn-dispatch-action btn-danger" title="Close Work Order">
                                                         Close WO
                                                    </button>
                                                    <a href="view_work_report.php?wo_id=<?php echo $wo['id']; ?>" 
                                                       class="btn-dispatch-action btn-report" title="View Tech Report">
                                                         View Report IKR
                                                    </a>

                                                <?php elseif ($wo['wo_status'] == 'Closed by BOR'): ?>
                                                    <a href="view_work_report.php?wo_id=<?php echo $wo['id']; ?>" 
                                                       class="btn-dispatch-action btn-report" title="View Tech Report">
                                                        View Report IKR
                                                    </a>

                                                <?php elseif ($wo['wo_status'] == 'Waiting For BOR Review'): ?>
                                                    <span class="text-purple" style="font-size: 11px;">
                                                        Sent to BOR - Waiting Review
                                                    </span>
                                                    <a href="view_work_report.php?wo_id=<?php echo $wo['id']; ?>" 
                                                       class="btn-dispatch-action btn-report" title="View Tech Report">
                                                        Report
                                                    </a>
                                             
                                                <?php else: ?>
                                                    <span class="text-info" style="font-size: 11px;">
                                                        Waiting for progress
                                                    </span>
                                                <?php endif; ?>
                                                
                                                <a href="view_work_order.php?id=<?php echo $wo['id']; ?>" 
                                                   class="btn-dispatch-action btn-view" title="View Details">
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
    </main>

    <div id="closeWoModal" class="modal" style="display: none;">
        <div class="modal-content modal-large">
            <span class="close" onclick="closeCloseWoModal()">&times;</span>
            <h3>Close Work Order & Send Report to BOR</h3>
            <form id="closeWoForm" action="proses_dispatch_close_wo.php" method="POST">
                <input type="hidden" id="closeWoId" name="wo_id">
                <div class="form-section">
                    <h4>Technician Report</h4>
                    <div class="form-group">
                        <label for="dispatch_notes">Dispatch Report Notes</label>
                        <textarea id="dispatch_notes" name="dispatch_notes" rows="4" required
                            placeholder="Summary of technician's work, solution, and any notes for BOR"></textarea>
                    </div>
                </div>
                <button type="submit" class="btn btn-large btn-danger">Close WO & Send to BOR</button>
            </form>
        </div>
    </div>

    <script>
        function forwardToAdminIKR(woId) {
            if (confirm('Forward this Work Order to Admin Back Office IKR for scheduling?')) {
                window.location.href = 'forward_wo_to_admin_ikr.php?wo_id=' + woId;
            }
        }

        function sendToBOR(woId) {
            if (confirm('Send work completion proof to BOR for final ticket closure?')) {
                window.location.href = 'send_proof_to_bor.php?wo_id=' + woId;
            }
        }

        function openCloseWoModal(woId) {
            document.getElementById('closeWoId').value = woId;
            document.getElementById('closeWoModal').style.display = 'block';
        }

        function closeCloseWoModal() {
            document.getElementById('closeWoModal').style.display = 'none';
        }

        function showAllWO() {
            const rows = document.querySelectorAll('#dispatchWorkOrderTable tbody tr');
            rows.forEach(row => row.style.display = '');
        }

        function showNewFromBOR() {
            const rows = document.querySelectorAll('#dispatchWorkOrderTable tbody tr');
            rows.forEach(row => {
                if (row.dataset.status === 'Sent to Dispatch') {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        }

        function showWithAdminIKR() {
            const rows = document.querySelectorAll('#dispatchWorkOrderTable tbody tr');
            rows.forEach(row => {
                if (row.dataset.status === 'received-by-admin-ikr' || row.dataset.status === 'scheduled-by-admin-ikr') {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        }

        function showInProgress() {
            const rows = document.querySelectorAll('#dispatchWorkOrderTable tbody tr');
            rows.forEach(row => {
                if (row.dataset.status === 'in-progress') {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        }

        function showNeedReview() {
            const rows = document.querySelectorAll('#dispatchWorkOrderTable tbody tr');
            rows.forEach(row => {
                if (row.dataset.status === 'completed-by-technician') {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        }
        
        document.addEventListener('DOMContentLoaded', function() {
            const tbody = document.querySelector('#dispatchWorkOrderTable tbody');
            if (!tbody) return;

            const originalRows = Array.from(tbody.querySelectorAll('tr'));
            const searchForm = document.getElementById('searchWOForm');
            const searchInput = document.getElementById('searchWOInput');

            if (!searchForm || !searchInput) return;

            searchForm.addEventListener('submit', function(e) {
                e.preventDefault();
                const searchValue = searchInput.value.trim().toLowerCase();
                const rows = Array.from(tbody.querySelectorAll('tr'));

                rows.forEach(row => row.classList.remove('search-highlight'));
                originalRows.forEach(row => tbody.appendChild(row));

                if (searchValue === '') return;

                let foundRows = [];
                rows.forEach(row => {
                    const woId = (row.dataset.woid || '').toLowerCase();
                    if (woId.includes(searchValue)) {
                        foundRows.push(row);
                    }
                });

                foundRows.reverse().forEach(row => {
                    row.classList.add('search-highlight');
                    tbody.insertBefore(row, tbody.firstChild);
                });
            });
        });

    </script>

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

        .stat-new {
            background: linear-gradient(135deg, #ffc107 0%, #fd7e14 100%);
        }

        .stat-admin {
            background: linear-gradient(135deg, #6f42c1 0%, #e83e8c 100%);
        }

        .stat-scheduled {
            background: linear-gradient(135deg, #17a2b8 0%, #20c997 100%);
        }

        .stat-progress {
            background: linear-gradient(135deg, #fd7e14 0%, #dc3545 100%);
        }

        .stat-review {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            position: relative;
        }

        .stat-review::after {
            content: '!';
            position: absolute;
            top: 10px;
            right: 15px;
            font-size: 24px;
            font-weight: bold;
            color: rgba(255,255,255,0.8);
            animation: pulse-icon 2s infinite;
        }

        @keyframes pulse-icon {
            0% { transform: scale(1); }
            50% { transform: scale(1.2); }
            100% { transform: scale(1); }
        }

        .dispatch-workflow-info {
            margin-top: 25px;
            padding: 20px;
            background-color: #f8f9fa;
            border-radius: 8px;
            border-left: 4px solid #17a2b8;
        }

        .dispatch-workflow-info h4 {
            margin-top: 0;
            color: #17a2b8;
            font-size: 1rem;
            margin-bottom: 15px;
        }

        .workflow-steps {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .step {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 8px 12px;
            background-color: white;
            border-radius: 6px;
            border: 1px solid #dee2e6;
        }

        .step-number {
            width: 24px;
            height: 24px;
            background-color: #17a2b8;
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            font-weight: bold;
            flex-shrink: 0;
        }

        .step-text {
            font-size: 13px;
            color: #495057;
            font-weight: 500;
        }

        .dispatch-action-buttons {
            display: flex;
            flex-direction: column;
            gap: 4px;
            width: 100%;
        }

        .btn-dispatch-action {
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

        .btn-forward {
            background-color: #6f42c1;
            color: white;
        }

        .btn-review {
            background-color: #28a745;
            color: white;
        }

        .btn-report {
            background-color: #fd7e14;
            color: white;
        }

        .btn-send-bor {
            background-color: #17a2b8;
            color: white;
        }

        .btn-dispatch-action:hover {
            transform: scale(1.05);
            opacity: 0.9;
        }

        .modal {
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
            overflow-y: auto;
        }

        .modal-content {
            background-color: white;
            margin: 2% auto;
            padding: 30px;
            border-radius: 10px;
            width: 90%;
            max-width: 500px;
            position: relative;
        }

        .modal-large {
            max-width: 700px;
            max-height: 90vh;
            overflow-y: auto;
        }

        .close {
            position: absolute;
            right: 15px;
            top: 15px;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
            z-index: 1001;
        }

        .close:hover {
            color: #dc3545;
        }

        .form-section {
            margin-bottom: 25px;
            padding: 20px;
            border: 1px solid #e9ecef;
            border-radius: 8px;
            background-color: #f8f9fa;
        }

        .form-section h4 {
            margin-top: 0;
            margin-bottom: 15px;
            color: #495057;
            font-size: 1.1rem;
            border-bottom: 2px solid #dee2e6;
            padding-bottom: 8px;
        }

        .btn-large {
            padding: 15px 30px;
            font-size: 16px;
            font-weight: 600;
            margin-top: 20px;
            width: 100%;
        }

        .ticket-table {
            width: 100%;
            table-layout: fixed; 
            border-collapse: collapse;
            background: #fff;
            font-size: 15px;
        }

        .ticket-table th, .ticket-table td {
            border: 1px solid #e0e0e0;
            padding: 10px 8px;
            text-align: left;
            vertical-align: top;
            word-break: break-word; 
        }

        .ticket-table th {
            background: #f5f7fa;
            font-weight: bold;
        }

        .ticket-table tr:nth-child(even) {
            background: #f9fbfd;
        }

        .ticket-table tr:hover {
            background: #e3f2fd;
        }

        .table-container {
            width: 100%;
            max-width: 100%;
            overflow-x: auto;
        }

        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
        }

        .modal-content {
            background-color: #fff;
            margin: 15% auto;
            padding: 20px;
            border-radius: 8px;
            width: 90%;
            max-width: 400px;
        }

        .form-group {
            margin-bottom: 16px;
        }

        .form-control {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            margin-top: 8px;
        }

        .modal-buttons {
            display: flex;
            gap: 8px;
            justify-content: flex-end;
            margin-top: 20px;
        }

        @media (max-width: 768px) {
            .dispatch-action-buttons {
                gap: 2px;
            }
            
            .btn-dispatch-action {
                font-size: 9px;
                padding: 3px 6px;
            }

            .workflow-steps {
                gap: 6px;
            }

            .step {
                padding: 6px 10px;
            }

            .step-text {
                font-size: 12px;
            }

            .modal-content {
                width: 95%;
                margin: 1% auto;
                padding: 20px;
            }

            .form-section {
                padding: 15px;
            }
        }
        
        .card {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 2px 8px #e0e0e0;
            padding: 18px 24px;
            display: flex;
            flex-direction: column;
            height: 100%;
        }

        .dashboard-row {
            display: flex;
            gap: 24px;
            align-items: stretch;
        }

        .search-highlight, .search-highlight td {
            background: #e3f2fd !important;
        }

    </style>

</body>
</html>