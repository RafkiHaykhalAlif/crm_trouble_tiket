<?php
// Panggil file koneksi, karena kita butuh session dan koneksi DB
include 'config/db_connect.php';

// --- PENJAGA HALAMAN ---
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// --- ROUTING BERDASARKAN ROLE ---
// Kalau user adalah BOR, redirect ke dashboard BOR
if ($_SESSION['user_role'] === 'BOR') {
    header('Location: dashboard_bor.php');
    exit();
}

// Kalau user adalah Dispatch, redirect ke dashboard Dispatch (nanti dibuat)
if ($_SESSION['user_role'] === 'Dispatch') {
    header('Location: dashboard_dispatch.php');
    exit();
}

// Kalau user adalah Vendor IKR, redirect ke dashboard Vendor (nanti dibuat)  
if ($_SESSION['user_role'] === 'Vendor IKR') {
    header('Location: dashboard_vendor.php');
    exit();
}

// Sisanya (Customer Care, Admin) tetap di dashboard ini
// --- AKHIR DARI PENJAGA HALAMAN ---

// --- AMBIL DATA TIKET DARI DATABASE ---
// Query ini mengambil data tiket dan menggabungkannya (JOIN) dengan tabel customer untuk mendapatkan nama pelanggan
$sql_get_tickets = "SELECT t.id, t.ticket_code, c.full_name, t.status, t.title 
                    FROM tr_tickets t
                    JOIN ms_customers c ON t.customer_id = c.id
                    ORDER BY t.created_at DESC"; // Urutkan berdasarkan tiket terbaru

$result_tickets = mysqli_query($conn, $sql_get_tickets);
$tickets = mysqli_fetch_all($result_tickets, MYSQLI_ASSOC);

// --- Cek apakah ada pesan sukses dari proses solve/escalate ---
$message = '';
if (isset($_GET['status'])) {
    if ($_GET['status'] == 'solved') {
        $message = '<div class="alert alert-success">Ticket berhasil diselesaikan!</div>';
    } elseif ($_GET['status'] == 'escalated') {
        $message = '<div class="alert alert-success">Ticket berhasil di-escalate ke BOR!</div>';
    } elseif ($_GET['status'] == 'sukses') {
        $message = '<div class="alert alert-success">Ticket baru berhasil dibuat!</div>';
    }
}

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>CRM-Retailing-Trouble-Ticket</title> 
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

    <header class="main-header">
        <div class="container">
            <h1>CRM Retailing - Trouble Ticket</h1>
            <div class="user-info">
                <span>Selamat datang, <strong><?php echo htmlspecialchars($_SESSION['user_full_name']); ?></strong>!</span>
                <span class="user-role">[<?php echo htmlspecialchars($_SESSION['user_role']); ?>]</span>
                <a href="logout.php" class="btn-logout">Logout</a>
            </div>
        </div>
    </header>

    <main class="container">
        <?php echo $message; ?>
        
        <div class="dashboard-grid">
            
            <div class="main-action-column">
                <section class="card">
                    <form action="proses_buat_tiket.php" method="POST">
                        
                        <fieldset>
                            <legend>Input Data Customer</legend>
                            <div class="form-group">
                                <label for="full_name">Nama Lengkap</label>
                                <input type="text" id="full_name" name="full_name" placeholder="Masukkan Nama Lengkap Pelanggan" required>
                            </div>
                            <div class="form-group">
                                <label for="address">Alamat</label>
                                <textarea id="address" name="address" rows="3" placeholder="Masukkan Alamat Lengkap" required></textarea>
                            </div>
                            <div class="form-group">
                                <label for="phone_number">No. Telepon</label>
                                <input type="text" id="phone_number" name="phone_number" placeholder="Masukkan No. Telepon Aktif" required>
                            </div>
                             <div class="form-group">
                                <label for="email">Email</label>
                                <input type="email" id="email" name="email" placeholder="Masukkan Alamat Email" required>
                            </div>
                        </fieldset>

                        <fieldset>
                            <legend>Buat Trouble Tiket</legend>
                             <div class="form-group">
                                <label for="jenis_gangguan">Jenis Gangguan</label>
                                <input type="text" id="jenis_gangguan" name="jenis_gangguan" placeholder="Contoh: Internet Lambat, Modem Mati Total" required>
                            </div>
                            <div class="form-group">
                                <label for="deskripsi_gangguan">Deskripsi Gangguan</label>
                                <textarea id="deskripsi_gangguan" name="deskripsi_gangguan" rows="5" placeholder="Tuliskan keluhan detail dari pelanggan di sini..." required></textarea>
                            </div>
                        </fieldset>

                        <button type="submit" class="btn" style="margin-top: 20px;">Buat Trouble Tiket</button>
                    </form>
                </section>
            </div>

            <div class="ticket-list-column">
                <section class="card">
                    <h3>Dashboard Trouble Tiket</h3> 
                    <table class="ticket-table">
                        <thead>
                            <tr>
                                <th>ID Tiket</th>
                                <th>Pelanggan</th>
                                <th>Jenis Masalah</th>
                                <th>Status</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($tickets)): ?>
                                <tr>
                                    <td colspan="5" style="text-align: center;">Belum ada tiket yang dibuat.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($tickets as $ticket): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($ticket['ticket_code']); ?></td>
                                        <td><?php echo htmlspecialchars($ticket['full_name']); ?></td>
                                        <td><?php echo htmlspecialchars($ticket['title']); ?></td>
                                        <td>
                                            <?php $status_class = strtolower(str_replace(' ', '-', $ticket['status'])); ?>
                                            <span class="status <?php echo $status_class; ?>">
                                                <?php echo htmlspecialchars($ticket['status']); ?>
                                            </span>
                                        </td>
                                        <td class="action-buttons">
                                            <?php if ($ticket['status'] == 'Open' || $ticket['status'] == 'On Progress - Customer Care'): ?>
                                                <!-- Tombol Solve - Hijau -->
                                                <button onclick="solveTicket(<?php echo $ticket['id']; ?>)" class="btn-action btn-solve" title="Selesaikan Ticket">
                                                    ✓ Solve
                                                </button>
                                                
                                                <!-- Tombol Escalate - Orange -->
                                                <button onclick="escalateTicket(<?php echo $ticket['id']; ?>)" class="btn-action btn-escalate" title="Escalate ke BOR">
                                                    ↗ BOR
                                                </button>
                                            <?php elseif ($ticket['status'] == 'On Progress - BOR'): ?>
                                                <span class="text-info">Sedang ditangani BOR</span>
                                            <?php else: ?>
                                                <span class="text-muted">Ticket Closed</span>
                                            <?php endif; ?>
                                            
                                            <!-- Tombol View selalu ada -->
                                            <a href="view_ticket.php?id=<?php echo $ticket['id']; ?>" class="btn-action btn-view" title="Lihat Detail">
                                                👁 View
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </section>
            </div>

        </div>
    </main>

    <script>
    // Fungsi JavaScript untuk handle tombol Solve
    function solveTicket(ticketId) {
        if (confirm('Apakah Anda yakin ticket ini sudah terselesaikan?')) {
            // Redirect ke file proses dengan method GET (bisa juga pakai POST dengan form hidden)
            window.location.href = 'proses_solve_ticket.php?ticket_id=' + ticketId;
        }
    }

    // Fungsi JavaScript untuk handle tombol Escalate
    function escalateTicket(ticketId) {
        if (confirm('Apakah Anda yakin ingin meng-escalate ticket ini ke BOR?')) {
            window.location.href = 'proses_escalate_ticket.php?ticket_id=' + ticketId;
        }
    }
    </script>

</body>
</html>