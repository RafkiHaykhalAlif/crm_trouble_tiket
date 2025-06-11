<?php
include 'config/db_connect.php';

// --- PENJAGA HALAMAN ---
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Cek apakah user adalah BOR, kalau bukan redirect ke dashboard biasa
if ($_SESSION['user_role'] !== 'BOR') {
    header('Location: dashboard.php');
    exit();
}
// --- AKHIR DARI PENJAGA HALAMAN ---

// --- AMBIL DATA TIKET YANG DITUGASKAN KE BOR ---
$bor_user_id = $_SESSION['user_id'];

// Query untuk ambil ticket yang sedang di-handle BOR atau yang udah di-handle BOR (termasuk solved)
$sql_get_bor_tickets = "SELECT 
    t.id, 
    t.ticket_code, 
    c.full_name, 
    t.title, 
    t.status, 
    t.created_at,
    u_creator.full_name as created_by_name
FROM tr_tickets t
JOIN ms_customers c ON t.customer_id = c.id
JOIN ms_users u_creator ON t.created_by_user_id = u_creator.id
WHERE t.status IN ('On Progress - BOR', 'Waiting for Dispatch', 'Closed - Solved', 'Closed - Unsolved') 
   OR (t.current_owner_user_id = '$bor_user_id')
ORDER BY 
    CASE 
        WHEN t.status = 'On Progress - BOR' THEN 1
        WHEN t.status = 'Waiting for Dispatch' THEN 2
        WHEN t.status = 'Closed - Solved' THEN 3
        WHEN t.status = 'Closed - Unsolved' THEN 4
        ELSE 5
    END,
    t.created_at DESC"; // Urutkan berdasarkan prioritas dan tanggal terbaru

$result_tickets = mysqli_query($conn, $sql_get_bor_tickets);
$tickets = mysqli_fetch_all($result_tickets, MYSQLI_ASSOC);

// --- STATISTIK DASHBOARD BOR ---
// Hitung jumlah ticket berdasarkan status
$sql_stats = "SELECT 
    SUM(CASE WHEN status = 'On Progress - BOR' THEN 1 ELSE 0 END) as in_progress,
    SUM(CASE WHEN status = 'Waiting for Dispatch' THEN 1 ELSE 0 END) as waiting_dispatch,
    SUM(CASE WHEN status LIKE 'Closed%' AND current_owner_user_id = '$bor_user_id' AND DATE(closed_at) = CURDATE() THEN 1 ELSE 0 END) as closed_today
FROM tr_tickets 
WHERE current_owner_user_id = '$bor_user_id' 
   OR status IN ('On Progress - BOR', 'Waiting for Dispatch')";

$result_stats = mysqli_query($conn, $sql_stats);
$stats = mysqli_fetch_assoc($result_stats);

// --- Cek pesan dari proses ---
$message = '';
if (isset($_GET['status'])) {
    switch($_GET['status']) {
        case 'resolved':
            $message = '<div class="alert alert-success">✅ Ticket berhasil diselesaikan!</div>';
            break;
        case 'dispatched':
            $message = '<div class="alert alert-success">🚚 Ticket berhasil dikirim ke Dispatch untuk kunjungan lapangan!</div>';
            break;
        case 'updated':
            $message = '<div class="alert alert-success">📝 Status ticket berhasil diupdate!</div>';
            break;
    }
}

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
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
        
        <!-- Statistik Dashboard -->
        <div class="stats-grid">
            <div class="stat-card stat-progress">
                <div class="stat-number"><?php echo $stats['in_progress']; ?></div>
                <div class="stat-label">Sedang Dikerjakan</div>
                <div class="stat-icon">⚡</div>
            </div>
            <div class="stat-card stat-dispatch">
                <div class="stat-number"><?php echo $stats['waiting_dispatch']; ?></div>
                <div class="stat-label">Menunggu Dispatch</div>
                <div class="stat-icon">📋</div>
            </div>
            <div class="stat-card stat-closed">
                <div class="stat-number"><?php echo $stats['closed_today']; ?></div>
                <div class="stat-label">Selesai Hari Ini</div>
                <div class="stat-icon">✅</div>
            </div>
        </div>

        <div class="dashboard-bor-grid">
            
            <!-- Form Quick Action BOR -->
            <div class="bor-action-column">
                <section class="card">
                    <h3>Quick Actions BOR</h3>
                    
                    <div class="quick-actions">
                        <button onclick="showAllTickets()" class="btn-quick-action btn-primary">
                            📊 Lihat Semua Ticket
                        </button>
                        <button onclick="showOnProgressOnly()" class="btn-quick-action btn-warning">
                            ⚡ Ticket On Progress
                        </button>
                        <button onclick="showCompletedTickets()" class="btn-quick-action btn-info">
                            ✅ Ticket Diselesaikan
                        </button>
                    </div>
                </section>
            </div>

            <!-- Daftar Ticket BOR -->
            <div class="ticket-list-bor-column">
                <section class="card">
                    <h3>🎫 Daftar Trouble Ticket untuk BOR</h3>
                    
                    <div class="table-container">
                        <table class="ticket-table" id="borTicketTable">
                            <thead>
                                <tr>
                                    <th>ID Ticket</th>
                                    <th>Customer</th>
                                    <th>Masalah</th>
                                    <th>Status</th>
                                    <th>Dibuat</th>
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
                                        <tr data-status="<?php echo strtolower(str_replace(' ', '-', $ticket['status'])); ?>">
                                            <td>
                                                <strong><?php echo htmlspecialchars($ticket['ticket_code']); ?></strong>
                                            </td>
                                            <td><?php echo htmlspecialchars($ticket['full_name']); ?></td>
                                            <td>
                                                <div class="ticket-title">
                                                    <?php echo htmlspecialchars($ticket['title']); ?>
                                                </div>
                                                <small class="text-muted">
                                                    Dibuat oleh: <?php echo htmlspecialchars($ticket['created_by_name']); ?>
                                                </small>
                                            </td>
                                            <td>
                                                <?php $status_class = strtolower(str_replace(' ', '-', $ticket['status'])); ?>
                                                <span class="status <?php echo $status_class; ?>">
                                                    <?php echo htmlspecialchars($ticket['status']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <small><?php echo date('d/m/Y H:i', strtotime($ticket['created_at'])); ?></small>
                                            </td>
                                            <td class="bor-action-buttons">
                                                <?php if ($ticket['status'] == 'On Progress - BOR'): ?>
                                                    <button onclick="resolveTicket(<?php echo $ticket['id']; ?>)" 
                                                            class="btn-bor-action btn-resolve" title="Selesaikan Ticket">
                                                        ✅ Resolve
                                                    </button>
                                                    <button onclick="dispatchTicket(<?php echo $ticket['id']; ?>)" 
                                                            class="btn-bor-action btn-dispatch" title="Kirim ke Dispatch">
                                                        🚚 Dispatch
                                                    </button>
                                                <?php elseif ($ticket['status'] == 'Waiting for Dispatch'): ?>
                                                    <span class="text-info">Menunggu teknisi...</span>
                                                <?php elseif ($ticket['status'] == 'Closed - Solved'): ?>
                                                    <span class="text-success">✅ Selesai</span>
                                                <?php elseif ($ticket['status'] == 'Closed - Unsolved'): ?>
                                                    <span class="text-danger">❌ Tidak Selesai</span>
                                                <?php endif; ?>
                                                
                                                <a href="view_ticket.php?id=<?php echo $ticket['id']; ?>" 
                                                   class="btn-bor-action btn-view" title="Lihat Detail">
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

    <script>
    // Fungsi untuk resolve ticket oleh BOR
    function resolveTicket(ticketId) {
        if (confirm('Apakah masalah ticket ini sudah berhasil diselesaikan secara remote oleh BOR?')) {
            window.location.href = 'proses_bor_resolve.php?ticket_id=' + ticketId;
        }
    }

    // Fungsi untuk dispatch ticket ke teknisi lapangan
    function dispatchTicket(ticketId) {
        if (confirm('Apakah ticket ini perlu ditangani oleh teknisi lapangan?\nTicket akan dikirim ke tim Dispatch.')) {
            window.location.href = 'proses_bor_dispatch.php?ticket_id=' + ticketId;
        }
    }

    // Fungsi filter quick actions - YANG UDAH DIPERBAIKI
    function showAllTickets() {
        const rows = document.querySelectorAll('#borTicketTable tbody tr');
        rows.forEach(row => row.style.display = '');
    }

    function showOnProgressOnly() {
        const rows = document.querySelectorAll('#borTicketTable tbody tr');
        rows.forEach(row => {
            if (row.dataset.status === 'on-progress---bor') {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    }

    function showCompletedTickets() {
        const rows = document.querySelectorAll('#borTicketTable tbody tr');
        rows.forEach(row => {
            if (row.dataset.status === 'closed---solved' || row.dataset.status === 'closed---unsolved') {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    }
    </script>

</body>
</html>