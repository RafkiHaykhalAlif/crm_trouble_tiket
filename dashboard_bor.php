<?php
include 'config/db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

if ($_SESSION['user_role'] !== 'BOR') {
    header('Location: dashboard.php');
    exit();
}

$bor_user_id = $_SESSION['user_id'];

$sql_get_bor_tickets = "SELECT 
    t.id, 
    t.ticket_code, 
    c.full_name, 
    t.title, 
    t.status, 
    t.created_at,
    t.jenis_tiket,
    u_creator.full_name as created_by_name,
    
    wo.id as wo_id,
    wo.wo_code,
    wo.status as wo_status,
    wo.work_quality_rating,
    wo.ticket_resolution_status,
    wo.reviewed_by_dispatch_id,
    u_vendor.full_name as technician_name
FROM tr_tickets t
JOIN ms_customers c ON t.customer_id = c.id
JOIN ms_users u_creator ON t.created_by_user_id = u_creator.id
LEFT JOIN tr_work_orders wo ON t.id = wo.ticket_id
LEFT JOIN ms_users u_vendor ON wo.assigned_to_vendor_id = u_vendor.id
WHERE (
    (t.status = 'On Progress - BOR' AND t.current_owner_user_id = '$bor_user_id')
    OR
    (t.status = 'Waiting For BOR Review' AND wo.reviewed_by_dispatch_id IS NOT NULL)
    OR
    (t.status IN ('Closed - Solved', 'Closed - Unsolved'))
    OR
    (t.status = 'Waiting for Dispatch' AND t.current_owner_user_id = '$bor_user_id')
)
ORDER BY 
    CASE 
        WHEN t.status = 'Waiting For BOR Review' AND wo.reviewed_by_dispatch_id IS NOT NULL THEN 1
        WHEN t.status = 'On Progress - BOR' THEN 2
        WHEN t.status = 'Waiting for Dispatch' THEN 3
        WHEN t.status = 'Closed - Solved' THEN 4
        ELSE 5
    END,
    t.created_at DESC";

$result_tickets = mysqli_query($conn, $sql_get_bor_tickets);
$tickets = mysqli_fetch_all($result_tickets, MYSQLI_ASSOC);

$sql_stats = "SELECT 
    SUM(CASE WHEN status = 'On Progress - BOR' THEN 1 ELSE 0 END) as in_progress,
    SUM(CASE WHEN status = 'Waiting for Dispatch' THEN 1 ELSE 0 END) as waiting_dispatch,
    SUM(CASE WHEN status = 'Waiting For BOR Review' THEN 1 ELSE 0 END) as waiting_review,
    SUM(CASE WHEN status LIKE 'Closed%' AND current_owner_user_id = '$bor_user_id' AND DATE(closed_at) = CURDATE() THEN 1 ELSE 0 END) as closed_today
FROM tr_tickets 
WHERE current_owner_user_id = '$bor_user_id' 
   OR status IN ('On Progress - BOR', 'Waiting for Dispatch', 'Waiting For BOR Review')";

$result_stats = mysqli_query($conn, $sql_stats);
$stats = mysqli_fetch_assoc($result_stats);

$message = '';
if (isset($_GET['status'])) {
    switch($_GET['status']) {
        case 'resolved':
            $message = '<div class="alert alert-success">Ticket berhasil diselesaikan!</div>';
            break;
        case 'dispatched':
            $message = '<div class="alert alert-success">Ticket berhasil dikirim ke Dispatch untuk kunjungan lapangan!</div>';
            break;
        case 'wo_approved_closed':
            $message = '<div class="alert alert-success">Work Order berhasil di-approve dan ticket ditutup sebagai SOLVED!</div>';
            break;
        case 'updated':
            $message = '<div class="alert alert-success">Status ticket berhasil diupdate!</div>';
            break;
    }
}

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Dashboard BOR - CRM Ticketing</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

    <header class="main-header">
        <div class="container">
            <h1>Dashboard BOR - Back Office Representative</h1>
            <div class="user-info">
                <span>Selamat datang, <strong><?php echo htmlspecialchars($_SESSION['user_full_name']); ?></strong>!</span>
                <span class="user-role user-role-bor">[<?php echo htmlspecialchars($_SESSION['user_role']); ?>]</span>
                <a href="logout.php" class="btn-logout">Logout</a>
            </div>
        </div>
    </header>

    <main class="container">
        <?php echo $message; ?>
        
        <div class="stats-grid">
            <div class="stat-card stat-review">
                <div class="stat-number"><?php echo $stats['waiting_review']; ?></div>
                <div class="stat-label">Close Tiket</div>
            </div>
            <div class="stat-card stat-progress">
                <div class="stat-number"><?php echo $stats['in_progress']; ?></div>
                <div class="stat-label">Sedang Dikerjakan</div>
            </div>
            <div class="stat-card stat-dispatch">
                <div class="stat-number"><?php echo $stats['waiting_dispatch']; ?></div>
                <div class="stat-label">Menunggu Dispatch</div>
            </div>
            <div class="stat-card stat-closed">
                <div class="stat-number"><?php echo $stats['closed_today']; ?></div>
                <div class="stat-label">Selesai Hari Ini</div>
            </div>
        </div>

        <div class="dashboard-bor-grid">
            
            <div class="bor-action-column">
                <section class="card">
                    <h3>Quick Actions BOR</h3>
                    
                    <div class="quick-actions">
                        <button id="showAllTickets" class="btn-quick-action btn-primary">
                            Lihat Semua Ticket
                        </button>
                        <button id="showReviewBtn" class="btn-quick-action btn-warning">
                            Close Tiket (<?php echo $stats['waiting_review']; ?>)
                        </button>
                        <button id="showProgressBtn" class="btn-quick-action btn-info">
                            Ticket On Progress
                        </button>
                        <button id="showCompletedBtn" class="btn-quick-action btn-success">
                            Ticket Diselesaikan
                        </button>
                    </div>

                </section>
            </div>

            <div class="ticket-list-bor-column">
                <section class="card">
                    <h3>Daftar Trouble Ticket untuk BOR</h3>
                    <form id="searchTicketForm" style="margin-bottom: 12px; display: flex; gap: 8px; max-width: 400px;">
                        <input type="text" id="searchTicketInput" placeholder="Cari Nama Tiket..." 
                            style="padding: 6px 12px; border-radius: 6px; border: 1px solid #ccc; flex: 1; max-width: 220px;">
                        <button type="submit" style="padding: 6px 18px; border-radius: 6px; background: #fd7e14; color: #fff; border: none; font-weight: 500; cursor: pointer;">Cari</button>
                    </form>

                    <div class="table-container">
                        <div style="max-height: 590px; overflow-y: auto;">
                            <table id="borTicketTable" class="ticket-table">
                                <thead>
                                    <tr>
                                        <th>ID Ticket</th>
                                        <th>Customer</th>
                                        <th>Jenis Ticket</th>
                                        <th>Masalah</th>
                                        <th>Status</th>
                                        <th>WO Info</th>
                                        <th>Aksi BOR</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($tickets)): ?>
                                        <tr>
                                            <td colspan="6" style="text-align: center; padding: 40px;">
                                                <div class="no-tickets">
                                                    <div style="font-size: 48px; margin-bottom: 10px;">🎉</div>
                                                    <h4>Tidak ada ticket yang perlu ditangani!</h4>
                                                    <p>Semua ticket sudah selesai atau belum ada yang di-escalate.</p>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($tickets as $ticket): ?>
                                            <tr 
                                                data-status="<?php echo htmlspecialchars(strtolower($ticket['status'])); ?>"
                                                data-title="<?php echo htmlspecialchars(strtolower($ticket['title'])); ?>"
                                            >
                                                <td>
                                                    <strong><?php echo htmlspecialchars($ticket['ticket_code']); ?></strong>
                                                </td>
                                                <td><?php echo htmlspecialchars($ticket['full_name']); ?></td>
                                                <td><?php echo htmlspecialchars($ticket['jenis_tiket'] ?? ''); ?></td>
                                                <td>
                                                    <div class="ticket-title">
                                                        <?php echo htmlspecialchars($ticket['title']); ?>
                                                    </div>
                                                    <small class="text-muted">
                                                        Dibuat oleh: <?php echo htmlspecialchars($ticket['created_by_name']); ?>
                                                    </small>
                                                </td>
                                                <td>
                                                    <?php 
                                                    $status_class = strtolower(str_replace(' ', '-', $ticket['status'])); 
                                                    $status_color = '';
                                                    switch($ticket['status']) {
                                                        case 'Waiting For BOR Review':
                                                            $status_color = 'background-color: #ffc107; color: #212529; animation: blink 1.5s infinite;';
                                                            break;
                                                        default:
                                                            break;
                                                    }
                                                    ?>
                                                    <span class="status <?php echo $status_class; ?>" style="<?php echo $status_color; ?>">
                                                        <?php echo htmlspecialchars($ticket['status']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <?php if ($ticket['wo_id']): ?>
                                                        <strong><?php echo htmlspecialchars($ticket['wo_code']); ?></strong>
                                                        <br>
                                                        <small class="text-muted">
                                                            Status: <?php echo htmlspecialchars($ticket['wo_status']); ?>
                                                        </small>
                                                        <?php if ($ticket['technician_name']): ?>
                                                        <br>
                                                        <small class="text-info">
                                                            By: <?php echo htmlspecialchars($ticket['technician_name']); ?>
                                                        </small>
                                                        <?php endif; ?>
                                                        
                                                        <?php if ($ticket['status'] == 'Waiting For BOR Review' && $ticket['work_quality_rating']): ?>
                                                        <br>
                                                        <small style="color: #28a745; font-weight: 600;">
                                                            Quality: <?php echo $ticket['work_quality_rating']; ?>
                                                        </small>
                                                        <br>
                                                        <small style="color: #17a2b8; font-weight: 600;">
                                                            Resolution: <?php echo $ticket['ticket_resolution_status']; ?>
                                                        </small>
                                                        <?php endif; ?>
                                                    <?php else: ?>
                                                        <span class="text-muted">Tidak ada WO</span>
                                                    <?php endif; ?>
                                                </td>
                                                
                                                <td class="bor-action-buttons">
                                                    <?php if ($ticket['status'] == 'On Progress - BOR'): ?>
                                                        <button onclick="resolveTicket(<?php echo $ticket['id']; ?>)" 
                                                                class="btn-bor-action btn-resolve" title="Selesaikan Ticket">
                                                            Resolve Tiket
                                                        </button>
                                                        <button onclick="dispatchTicket(<?php echo $ticket['id']; ?>)" 
                                                                class="btn-bor-action btn-dispatch" title="Kirim ke Dispatch">
                                                            Escalate to Dispatch
                                                        </button>
                                                    
                                                    <?php elseif ($ticket['status'] == 'Waiting For BOR Review' && $ticket['wo_id'] && $ticket['reviewed_by_dispatch_id']): ?>
                                                        <button onclick="approveWO(<?php echo $ticket['wo_id']; ?>)" 
                                                                class="btn-bor-action btn-approve" title="Approve & Close as Solved">
                                                            Close Tiket
                                                        </button>
                                                        <?php if (!empty($ticket['wo_id'])): ?>
                                                            <a href="view_work_report.php?wo_id=<?php echo $ticket['wo_id']; ?>" 
                                                               class="btn-bor-action btn-report" title="Lihat Report WO">
                                                                View Report IKR
                                                            </a>
                                                        <?php endif; ?>

                                                    <?php elseif ($ticket['status'] == 'Waiting For BOR Review' && $ticket['wo_id'] && !$ticket['reviewed_by_dispatch_id']): ?>
                                                        <span class="text-warning">Waiting Dispatch Review</span>
                                                        <?php if (!empty($ticket['wo_id'])): ?>
                                                            <a href="view_work_report.php?wo_id=<?php echo $ticket['wo_id']; ?>" 
                                                               class="btn-bor-action btn-report" title="Lihat Report WO">
                                                                View Report IKR
                                                            </a>
                                                        <?php endif; ?>

                                                    <?php elseif ($ticket['status'] == 'Waiting for Dispatch'): ?>
                                                        <span class="text-info">Menunggu teknisi...</span>
                                                    
                                                    <?php elseif ($ticket['status'] == 'Closed - Solved'): ?>                                                        <?php if (!empty($ticket['wo_id'])): ?>
                                                            <a href="view_work_report.php?wo_id=<?php echo $ticket['wo_id']; ?>" 
                                                               class="btn-bor-action btn-report" title="Lihat Report WO">
                                                                View Report IKR
                                                            </a>
                                                        <?php endif; ?>

                                                    <?php else: ?>
                                                        <span class="text-muted">No Action</span>
                                                    <?php endif; ?>
                                                    
                                                    <a href="view_ticket.php?id=<?php echo $ticket['id']; ?>" 
                                                       class="btn-bor-action btn-view" title="Lihat Detail">
                                                        Detail Tiket
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
    </main>

    <script>
        function resolveTicket(ticketId) {
            if (confirm('Apakah masalah ticket ini sudah berhasil diselesaikan secara remote oleh BOR?')) {
                window.location.href = 'proses_bor_resolve.php?ticket_id=' + ticketId;
            }
        }

        function dispatchTicket(ticketId) {
            if (confirm('Apakah ticket ini perlu ditangani oleh teknisi lapangan?\nTicket akan dikirim ke tim Dispatch.')) {
                window.location.href = 'proses_bor_dispatch.php?ticket_id=' + ticketId;
            }
        }

        function approveWO(woId) {
            if(confirm('Yakin ingin menutup WO ini?')) {
                window.location.href = 'proses_bor_close_wo.php?wo_id=' + woId + '&action=approve';
            }
        }

        function showAllTickets() {
            const rows = document.querySelectorAll('#borTicketTable tbody tr');
            rows.forEach(row => row.style.display = '');
        }

        function showReviewTickets() {
            const rows = document.querySelectorAll('#borTicketTable tbody tr');
            rows.forEach(row => {
                const status = (row.dataset.status || '').toLowerCase();
                if (status === 'waiting for bor review') {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        }

        function showOnProgressOnly() {
            const rows = document.querySelectorAll('#borTicketTable tbody tr');
            rows.forEach(row => {
                const status = (row.dataset.status || '').toLowerCase();
                if (status === 'on progress - bor') {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        }

        function showCompletedTickets() {
            const rows = document.querySelectorAll('#borTicketTable tbody tr');
            rows.forEach(row => {
                const status = (row.dataset.status || '').toLowerCase();
                if (status === 'closed - solved' || status === 'closed - unsolved') {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        }

        document.getElementById('searchTicketForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const searchTerm = document.getElementById('searchTicketInput').value.toLowerCase();
            const rows = document.querySelectorAll('#borTicketTable tbody tr');
            rows.forEach(row => {
                const ticketCode = row.cells[0].textContent.toLowerCase();
                const customerName = row.cells[1].textContent.toLowerCase();
                const problemDescription = row.cells[2].textContent.toLowerCase();
                
                if (ticketCode.includes(searchTerm) || customerName.includes(searchTerm) || problemDescription.includes(searchTerm)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });

        document.addEventListener('DOMContentLoaded', function() {
            document.querySelector('#showAllTickets').addEventListener('click', showAllTickets);
            document.querySelector('#showReviewBtn').addEventListener('click', showReviewTickets);
            document.querySelector('#showProgressBtn').addEventListener('click', showOnProgressOnly);
            document.querySelector('#showCompletedBtn').addEventListener('click', showCompletedTickets);
        });document.addEventListener('DOMContentLoaded', function() {
        const tbody = document.querySelector('#borTicketTable tbody');
        if (!tbody) return;
        const originalRows = Array.from(tbody.querySelectorAll('tr'));
        const searchForm = document.getElementById('searchTicketForm');
        const searchInput = document.getElementById('searchTicketInput');

        if (!searchForm || !searchInput) return;

        searchForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const searchValue = searchInput.value.trim().toLowerCase();
            const rows = Array.from(tbody.querySelectorAll('tr'));
            rows.forEach(row => {
                row.classList.remove('search-highlight');
                row.style.display = '';
            });

            if (searchValue === '') {
                while (tbody.firstChild) {
                    tbody.removeChild(tbody.firstChild);
                }
                originalRows.forEach(row => {
                    tbody.appendChild(row);
                });
                return;
            }

            let foundRows = [];
            rows.forEach(row => {
                const ticketId = row.querySelector('td:nth-child(1)').textContent.toLowerCase();
                const customer = row.querySelector('td:nth-child(2)').textContent.toLowerCase();
                const issue = row.querySelector('td:nth-child(3)').textContent.toLowerCase();
                if (ticketId.includes(searchValue) || 
                    customer.includes(searchValue) || 
                    issue.includes(searchValue)) {
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
        
        .user-role-bor {
            background-color: #fd7e14 !important;
        }

        .stat-review {
            background: linear-gradient(135deg, #ffc107 0%, #fd7e14 100%);
            position: relative;
            overflow: hidden;
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

        @keyframes blink {
            0% { opacity: 1; }
            50% { opacity: 0.7; }
            100% { opacity: 1; }
        }

        .alert-box {
            background-color: #fff3cd;
            border: 1px solid #ffeaa7;
            border-left: 4px solid #ffc107;
            border-radius: 6px;
            padding: 15px;
            margin-top: 20px;
        }

        .alert-box h4 {
            margin-top: 0;
            color: #856404;
            font-size: 1rem;
        }

        .alert-box p {
            margin: 8px 0;
            color: #856404;
            font-size: 14px;
        }

        .btn-approve {
            background-color: #28a745;
            color: white;
        }

        .btn-approve:hover {
            background-color: #218838;
            transform: scale(1.05);
        }

        .btn-unsolved {
            background-color: #dc3545;
            color: white;
        }

        .btn-unsolved:hover {
            background-color: #c82333;
            transform: scale(1.05);
        }

        .bor-action-buttons {
            display: flex;
            gap: 6px;
            flex-wrap: wrap;
            flex-direction: column;
            align-items: stretch;
            width: 150px;
        }

        .bor-action-buttons .btn-bor-action {
            display: block;
            width: 100%;
            min-width: 0;
            max-width: none;
            padding: 7px 0;
            text-align: center;
            border: none;
            cursor: pointer;
            white-space: nowrap;
            margin: 0;
            border-radius: 8px;
            box-sizing: border-box;
        }

        tr[data-status="waiting-for-bor-review"] {
            background-color: #fff8e1 !important;
            border-left: 4px solid #ffc107;
        }

        tr[data-status="waiting-for-bor-review"]:hover {
            background-color: #fff3c4 !important;
        }

        .table-container {
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            overflow: hidden;
            background: #fff;
            margin-top: 12px;
        }

        .ticket-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 14px;
        }

        .ticket-table th, 
        .ticket-table td {
            border: 1px solid #e0e0e0;
            padding: 12px;
            text-align: left;
        }

        .ticket-table th {
            background: #f5f7fa;
            font-weight: 600;
            position: sticky;
            top: 0;
            z-index: 1;
        }

        .ticket-table tbody tr:nth-child(even) {
            background: #f9fbfd;
        }

        .ticket-table tbody tr:hover {
            background: #e3f2fd;
        }

        @media (max-width: 768px) {
            .bor-action-buttons {
                max-width: none;
            }
            
            .bor-action-buttons .btn-bor-action {
                font-size: 10px;
                padding: 4px 8px;
            }
        }

        @media (max-width: 768px) {
            .container, .main-header .container {
                max-width: 100vw;
                width: 100%;
                padding: 0 4px;
                box-sizing: border-box;
            }
            .stats-grid {
                display: flex;
                flex-direction: column;
                gap: 12px;
            }
            .dashboard-bor-grid {
                flex-direction: column;
                gap: 16px;
            }
            .bor-action-column,
            .ticket-list-bor-column {
                width: 100%;
                min-width: 0;
                padding: 0;
            }
            .card {
                padding: 10px 4px;
                border-radius: 0;
                box-shadow: none;
                margin: 0;
            }
            .quick-actions {
                flex-direction: column;
                gap: 8px;
            }
            .btn-quick-action {
                width: 100%;
                font-size: 1rem;
                margin-bottom: 6px;
            }
            .table-container {
                overflow-x: auto;
                width: 100vw !important;
                max-width: 100vw !important;
                margin: 0 !important;
                padding: 0 !important;
                box-sizing: border-box;
                border-radius: 0;
            }
            .ticket-table {
                min-width: 700px;
                width: 700px;
                max-width: none;
            }
            .bor-action-buttons {
                flex-direction: row;
                flex-wrap: wrap;
                gap: 4px;
                max-width: none;
            }
            .bor-action-buttons .btn-bor-action {
                font-size: 11px;
                padding: 4px 8px;
                width: auto;
            }
            .main-header h1 {
                font-size: 1.1rem;
                padding: 4px 0;
            }
            .user-info {
                flex-direction: column;
                align-items: flex-start;
                gap: 4px;
            }
            .stat-card .stat-number {
                font-size: 1.5rem;
            }
            .stat-card .stat-label {
                font-size: 1rem;
            }


            .search-highlight, .search-highlight td {
                background: #e3f2fd !important;
                }

            .search-highlight td {
                background-color: #e3f2fd !important;
            }
        }
</style>

</body>
</html>