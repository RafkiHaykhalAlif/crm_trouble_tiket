<?php
include 'config/db_connect.php';

// --- PENJAGA HALAMAN ---
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Cek apakah ada parameter ID ticket
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: dashboard.php');
    exit();
}

$ticket_id = (int)$_GET['id'];

// Query untuk ambil detail ticket lengkap dengan data customer dan user
$sql_ticket_detail = "SELECT 
    t.id,
    t.ticket_code,
    t.title,
    t.description,
    t.status,
    t.created_at,
    t.closed_at,
    c.customer_id_number,
    c.full_name as customer_name,
    c.address,
    c.phone_number,
    c.email,
    u_creator.full_name as created_by_name,
    u_owner.full_name as current_owner_name,
    u_owner.role as current_owner_role
FROM tr_tickets t
JOIN ms_customers c ON t.customer_id = c.id
JOIN ms_users u_creator ON t.created_by_user_id = u_creator.id
JOIN ms_users u_owner ON t.current_owner_user_id = u_owner.id
WHERE t.id = '$ticket_id'";

$result_ticket = mysqli_query($conn, $sql_ticket_detail);

if (mysqli_num_rows($result_ticket) == 0) {
    // Ticket tidak ditemukan
    header('Location: dashboard.php?status=ticket_not_found');
    exit();
}

$ticket = mysqli_fetch_assoc($result_ticket);

// Query untuk ambil history/update ticket
$sql_updates = "SELECT 
    tu.update_type,
    tu.description,
    tu.created_at,
    u.full_name as user_name,
    u.role as user_role
FROM tr_ticket_updates tu
JOIN ms_users u ON tu.user_id = u.id
WHERE tu.ticket_id = '$ticket_id'
ORDER BY tu.created_at DESC";

$result_updates = mysqli_query($conn, $sql_updates);
$updates = mysqli_fetch_all($result_updates, MYSQLI_ASSOC);

// Fungsi helper untuk format tanggal Indonesia
function formatTanggalIndonesia($datetime) {
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
    <title>Detail Ticket - <?php echo htmlspecialchars($ticket['ticket_code']); ?></title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

    <header class="main-header">
        <div class="container">
            <h1>Detail Trouble Ticket</h1>
            <div class="user-info">
                <span>Selamat datang, <strong><?php echo htmlspecialchars($_SESSION['user_full_name']); ?></strong>!</span>
                <span class="user-role">[<?php echo htmlspecialchars($_SESSION['user_role']); ?>]</span>
                <a href="logout.php" class="btn-logout">Logout</a>
            </div>
        </div>
    </header>

    <main class="container">
        
        <!-- Tombol Kembali -->
        <div class="back-button-section">
            <a href="dashboard.php" class="btn-back">← Kembali ke Dashboard</a>
        </div>

        <div class="ticket-detail-grid">
            
            <!-- Info Ticket -->
            <section class="card ticket-info-card">
                <div class="card-header">
                    <h3>Informasi Ticket</h3>
                    <div class="ticket-status-badge">
                        <?php $status_class = strtolower(str_replace(' ', '-', $ticket['status'])); ?>
                        <span class="status <?php echo $status_class; ?>">
                            <?php echo htmlspecialchars($ticket['status']); ?>
                        </span>
                    </div>
                </div>
                
                <div class="ticket-info">
                    <div class="info-row">
                        <label>ID Ticket:</label>
                        <strong><?php echo htmlspecialchars($ticket['ticket_code']); ?></strong>
                    </div>
                    <div class="info-row">
                        <label>Jenis Masalah:</label>
                        <span><?php echo htmlspecialchars($ticket['title']); ?></span>
                    </div>
                    <div class="info-row">
                        <label>Deskripsi:</label>
                        <div class="description-box">
                            <?php echo nl2br(htmlspecialchars($ticket['description'])); ?>
                        </div>
                    </div>
                    <div class="info-row">
                        <label>Dibuat Tanggal:</label>
                        <span><?php echo formatTanggalIndonesia($ticket['created_at']); ?></span>
                    </div>
                    <div class="info-row">
                        <label>Dibuat Oleh:</label>
                        <span><?php echo htmlspecialchars($ticket['created_by_name']); ?></span>
                    </div>
                    <div class="info-row">
                        <label>Pemilik Saat Ini:</label>
                        <span><?php echo htmlspecialchars($ticket['current_owner_name']); ?> 
                              <small>(<?php echo htmlspecialchars($ticket['current_owner_role']); ?>)</small>
                        </span>
                    </div>
                    <?php if ($ticket['closed_at']): ?>
                    <div class="info-row">
                        <label>Ditutup Tanggal:</label>
                        <span><?php echo formatTanggalIndonesia($ticket['closed_at']); ?></span>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Action Buttons untuk Ticket -->
                <div class="ticket-actions">
                    <?php if ($ticket['status'] == 'Open' || $ticket['status'] == 'On Progress - Customer Care'): ?>
                        <button onclick="solveTicket(<?php echo $ticket['id']; ?>)" class="btn-action btn-solve">
                            ✓ Solve Ticket
                        </button>
                        <button onclick="escalateTicket(<?php echo $ticket['id']; ?>)" class="btn-action btn-escalate">
                            ↗ Escalate ke BOR
                        </button>
                    <?php elseif ($ticket['status'] == 'On Progress - BOR'): ?>
                        <div class="ticket-status-info">
                            <span class="text-info">📋 Ticket sedang ditangani oleh tim BOR</span>
                        </div>
                    <?php else: ?>
                        <div class="ticket-status-info">
                            <span class="text-muted">🔒 Ticket sudah ditutup</span>
                        </div>
                    <?php endif; ?>
                </div>
            </section>

            <!-- Info Customer -->
            <section class="card customer-info-card">
                <h3>Informasi Customer</h3>
                <div class="customer-info">
                    <div class="info-row">
                        <label>ID Customer:</label>
                        <strong><?php echo htmlspecialchars($ticket['customer_id_number']); ?></strong>
                    </div>
                    <div class="info-row">
                        <label>Nama Lengkap:</label>
                        <span><?php echo htmlspecialchars($ticket['customer_name']); ?></span>
                    </div>
                    <div class="info-row">
                        <label>Email:</label>
                        <span><?php echo htmlspecialchars($ticket['email']); ?></span>
                    </div>
                    <div class="info-row">
                        <label>No. Telepon:</label>
                        <span><?php echo htmlspecialchars($ticket['phone_number']); ?></span>
                    </div>
                    <div class="info-row">
                        <label>Alamat:</label>
                        <div class="address-box">
                            <?php echo nl2br(htmlspecialchars($ticket['address'])); ?>
                        </div>
                    </div>
                </div>
            </section>

        </div>

        <!-- History/Timeline Ticket -->
        <section class="card timeline-card">
            <h3>📝 Riwayat Aktivitas Ticket</h3>
            
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
                                <div class="timeline-description">
                                    <?php echo nl2br(htmlspecialchars($update['description'])); ?>
                                </div>
                                <div class="timeline-user">
                                    Oleh: <strong><?php echo htmlspecialchars($update['user_name']); ?></strong> 
                                    <small>(<?php echo htmlspecialchars($update['user_role']); ?>)</small>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>

    </main>

    <script>
    // Fungsi yang sama kayak di dashboard
    function solveTicket(ticketId) {
        if (confirm('Apakah Anda yakin ticket ini sudah terselesaikan?')) {
            window.location.href = 'proses_solve_ticket.php?ticket_id=' + ticketId;
        }
    }

    function escalateTicket(ticketId) {
        if (confirm('Apakah Anda yakin ingin meng-escalate ticket ini ke BOR?')) {
            window.location.href = 'proses_escalate_ticket.php?ticket_id=' + ticketId;
        }
    }
    </script>

</body>
</html>