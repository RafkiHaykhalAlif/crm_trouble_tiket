<?php

include 'config/db_connect.php';
if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit(); }

if ($_SESSION['user_role'] === 'BOR') { header('Location: dashboard_bor.php'); exit(); }
if ($_SESSION['user_role'] === 'Dispatch') { header('Location: dashboard_dispatch.php'); exit(); }
if ($_SESSION['user_role'] === 'Vendor IKR') { header('Location: dashboard_vendor.php'); exit(); }
if ($_SESSION['user_role'] === 'Admin IKR') { header('Location: dashboard_admin_ikr.php'); exit(); }

$message = '';
if (isset($_GET['status']) && $_GET['status'] == 'sukses') {
    $message = '<div class="alert alert-success">Ticket baru berhasil dibuat!</div>';
}

$sql_get_tickets = "SELECT t.id, t.ticket_code, c.full_name, t.status, t.title, t.created_at, t.jenis_tiket
                    FROM tr_tickets t
                    JOIN ms_customers c ON t.customer_id = c.id
                    ORDER BY t.created_at DESC";
$result = mysqli_query($conn, $sql_get_tickets);

$tickets = [];
while ($row = mysqli_fetch_assoc($result)) {
    $tickets[] = $row;
}

$search_ticket = isset($_GET['search_ticket']) ? trim($_GET['search_ticket']) : '';
$searched_ticket = null;
if ($search_ticket !== '') {
    foreach ($tickets as $key => $ticket) {
        if (stripos($ticket['ticket_code'], $search_ticket) !== false) {
            $searched_ticket = $ticket;
            unset($tickets[$key]); 
            break;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Customer Care Dashboard - Network Operations Center</title>    
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <header class="main-header">
        <div class="container">
            <h1>Customer Care - Network Operations Center</h1>
            <div class="user-info">
                <span>Selamat datang, <strong><?php echo htmlspecialchars($_SESSION['user_full_name']); ?></strong>!</span>
                <span class="user-role user-role-cc">[<?php echo htmlspecialchars($_SESSION['user_role']); ?>]</span>
                <a href="logout.php" class="btn-logout">Logout</a>
            </div>
        </div>
    </header>

    <main class="container">
        <?php echo $message; ?> 
    
        <div class="statistik-tiket-wrapper">
            <a href="dashboard_statistik_tiket.php" class="btn btn-gradient-statistik btn-lg shadow px-5 py-2">
                <i class="fas fa-chart-bar me-2"></i>
                <span style="font-weight:600; letter-spacing:0.5px;">Statistik Tiket</span>
            </a>
        </div>

        <div class="dashboard-grid">
            <div class="main-action-column">
                <section class="card">
                    <h3>Buat Trouble Ticket Baru</h3>
                    <form action="proses_buat_tiket.php" method="POST">
                        <fieldset>
                            <legend>Input Data Customer</legend>
                            <div class="form-group">
                                <label for="full_name">Nama Lengkap</label>
                                <input type="text" id="full_name" name="full_name" placeholder="Masukkan Nama Lengkap Customer" required>
                            </div>
                            <div class="form-group">
                                <label for="provinsi">Provinsi</label>
                                <select id="provinsi" name="provinsi_id" required>
                                    <option value="">Pilih Provinsi...</option>
                                </select>
                                <input type="hidden" id="provinsi_nama" name="provinsi" />
                            </div>
                            <div class="form-group">
                                <label for="kota">Kabupaten/Kota</label>
                                <select id="kota" name="kota" required>
                                    <option value="">Pilih Kabupaten/Kota...</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="address">Alamat Lengkap</label>
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
                            <label for="jenis_tiket">Jenis Tiket</label>
                                <select id="jenis_tiket" name="jenis_tiket" required>
                                    <option value="">Pilih Jenis Tiket...</option>
                                    <option value="Maintenance">Maintenance</option>
                                    <option value="Dismantle">Dismantle</option>
                                </select>    
                            <label for="jenis_gangguan">Jenis Gangguan</label>
                                <select id="jenis_gangguan_select" name="jenis_gangguan_select" onchange="updateJenisGangguan()" required>
                                </select>
                                <input type="text" id="jenis_gangguan" name="jenis_gangguan" placeholder="Atau ketik manual jenis gangguan" required style="margin-top: 10px;">
                            </div>
                            <div class="form-group"><label for="deskripsi_gangguan">Deskripsi Gangguan</label><textarea id="deskripsi_gangguan" name="deskripsi_gangguan" rows="5" placeholder="Tuliskan keluhan detail dari pelanggan di sini..." required></textarea></div>
                            <div class="form-group">
                                <label for="complain_channel">Complain Lewat</label>
                                <select name="complain_channel" id="complain_channel" required style="margin-bottom: 12px;">
                                    <option value="">Pilih Channel...</option>
                                    <option value="Hotline">Hotline</option>
                                    <option value="Sosmed">Sosmed</option>
                                    <option value="Oxygen Self Care">Oxygen Self Care</option>
                                    <option value="Email">Email</option>
                                    <option value="Walk-in">Walk-in</option>
                                    <option value="Lainnya">Lainnya</option>
                                </select>
                            </div>
                        </fieldset>
                        <button type="submit" class="btn" style="margin-top: 20px;">Buat Trouble Tiket</button>
                    </form>
                </section>
            </div>
            
            <div class="ticket-list-column">
                <section class="card">
                    <h3>Recent Tickets & Activity</h3>
                    <div style="max-height: 1280px; overflow-y: auto;">
                        <form method="get" style="margin-bottom: 12px; display: flex; gap: 8px;">
                            <input type="text" name="search_ticket" placeholder="Cari ID Tiket..." value="<?php echo isset($_GET['search_ticket']) ? htmlspecialchars($_GET['search_ticket']) : ''; ?>" style="padding: 6px 12px; border-radius: 6px; border: 1px solid #ccc;">
                            <button type="submit" style="padding: 6px 18px; border-radius: 6px; background: #007bff; color: #fff; border: none;">Cari</button>
                        </form>
                        <table class="table-ticket">
                            <thead>
                                <tr><th>ID Tiket</th><th>Pelanggan</th><th>Jenis Tiket</th><th>Jenis Masalah</th><th>Status</th><th>Age</th><th>Aksi</th></tr>
                            </thead>
                            <tbody>
                                <?php if (empty($tickets)): ?>
                                    <tr><td colspan="7" style="text-align: center;">Belum ada tiket.</td></tr>
                                <?php else: ?>
                                    <?php if ($searched_ticket): ?>
                                        <tr style="background: #e3f2fd;">
                                            <td><strong><?php echo htmlspecialchars($searched_ticket['ticket_code']); ?></strong></td>
                                            <td><?php echo htmlspecialchars($searched_ticket['full_name']); ?></td>
                                            <td><?php echo htmlspecialchars($searched_ticket['jenis_tiket'] ?? ''); ?></td>
                                            <td><?php echo htmlspecialchars($searched_ticket['title']); ?></td>
                                            <td>
                                                <?php $status_class = strtolower(str_replace(' ', '-', $ticket['status'])); ?>
                                                <span class="status <?php echo $status_class; ?>"><?php echo htmlspecialchars($ticket['status']); ?></span>
                                            </td>
                                            <td>
                                                <?php
                                                    $created_at = new DateTime($searched_ticket['created_at']);
                                                    $now = new DateTime();
                                                    $age = $now->diff($created_at);
                                                    if ($age->d > 0) {
                                                        $age_text = $age->d . 'd';
                                                    } elseif ($age->h > 0) {
                                                        $age_text = $age->h . 'h';
                                                    } else {
                                                        $age_text = $age->i . 'm';
                                                    }
                                                    ?>
                                                    <span class="ticket-age <?php echo ($age->d > 1) ? 'age-warning' : ''; ?>">
                                                <?php echo $age_text; ?>
                                                    </span>
                                            </td>
                                            <td class="action-buttons">
                                                <?php if ($searched_ticket['status'] == 'Open' || $searched_ticket['status'] == 'On Progress - Customer Care'): ?>
                                                    <button onclick="solveTicket(<?php echo $searched_ticket['id']; ?>)" class="btn-action btn-solve" title="Selesaikan Ticket">✓ Solve</button>
                                                    <button onclick="escalateTicket(<?php echo $searched_ticket['id']; ?>)" class="btn-action btn-escalate" title="Escalate ke BOR">↗ BOR</button>
                                                <?php endif; ?>
                                                <a href="view_ticket.php?id=<?php echo $searched_ticket['id']; ?>" class="btn-action btn-view" title="Lihat Detail">👁 View</a>
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                    <?php foreach ($tickets as $ticket): 
                                        $created_time = new DateTime($ticket['created_at']);
                                        $now = new DateTime();
                                        $age = $created_time->diff($now);
                                        $age_text = '';
                                        if ($age->days > 0) {
                                            $age_text = $age->days . 'd';
                                        } elseif ($age->h > 0) {
                                            $age_text = $age->h . 'h';
                                        } else {
                                            $age_text = $age->i . 'm';
                                        }
                                    ?>
                                        <tr>
                                            <td><strong><?php echo htmlspecialchars($ticket['ticket_code']); ?></strong></td>
                                            <td><?php echo htmlspecialchars($ticket['full_name']); ?></td>
                                            <td><?php echo htmlspecialchars($ticket['jenis_tiket'] ?? ''); ?></td>
                                            <td><?php echo htmlspecialchars($ticket['title']); ?></td>
                                            <td>
                                                <?php $status_class = strtolower(str_replace(' ', '-', $ticket['status'])); ?>
                                                <span class="status <?php echo $status_class; ?>"><?php echo htmlspecialchars($ticket['status']); ?></span>
                                            </td>
                                            <td>
                                                <?php
                                                    $created_at = new DateTime($ticket['created_at']);
                                                    $now = new DateTime();
                                                    $age = $now->diff($created_at);
                                                    if ($age->d > 0) {
                                                        $age_text = $age->d . 'd';
                                                    } elseif ($age->h > 0) {
                                                        $age_text = $age->h . 'h';
                                                    } else {
                                                        $age_text = $age->i . 'm';
                                                    }
                                                    ?>
                                                    <span class="ticket-age <?php echo ($age->d > 1) ? 'age-warning' : ''; ?>">
                                                        <?php echo $age_text; ?>
                                                    </span>
                                            </td>
                                            <td class="action-buttons">
                                                <?php if ($ticket['status'] == 'Open' || $ticket['status'] == 'On Progress - Customer Care'): ?>
                                                    <button onclick="solveTicket(<?php echo $ticket['id']; ?>)" class="btn-action btn-solve" title="Selesaikan Ticket">✓ Solve</button>
                                                    <button onclick="escalateTicket(<?php echo $ticket['id']; ?>)" class="btn-action btn-escalate" title="Escalate ke BOR">↗ BOR</button>
                                                <?php endif; ?>
                                                <a href="view_ticket.php?id=<?php echo $ticket['id']; ?>" class="btn-action btn-view" title="Lihat Detail">View</a>
                                            </td>
                                        </tr>
                                    <?php endforeach; endif; ?>
                            </tbody>
                        </table>
                    </div>
                </section>
            </div>
        </div>
    </main>

    <script>
        const gangguanOptions = {
            Maintenance: [
                { value: "Internet Mati Total", label: "Internet Mati Total" },
                { value: "Internet Lambat", label: "Internet Lambat" },
                { value: "WiFi Tidak Bisa Connect", label: "WiFi Tidak Bisa Connect" },
                { value: "Modem/Router Bermasalah", label: "Modem/Router Bermasalah" },
                { value: "Custom", label: "Lainnya (ketik manual)" }
            ],
            Dismantle: [
                { value: "Permintaan Cabut Layanan", label: "Permintaan Cabut Layanan" },
                { value: "Pindah Alamat (Dismantle)", label: "Pindah Alamat (Dismantle)" },
                { value: "Cabut Sementara", label: "Cabut Sementara" },
                { value: "Custom", label: "Lainnya (ketik manual)" }
            ]
        };

        function populateGangguanOptions(jenisTiket) {
            const select = document.getElementById('jenis_gangguan_select');
            select.innerHTML = '<option value="">Pilih jenis gangguan...</option>';
            if (gangguanOptions[jenisTiket]) {
                gangguanOptions[jenisTiket].forEach(opt => {
                    const option = document.createElement('option');
                    option.value = opt.value;
                    option.textContent = opt.label;
                    select.appendChild(option);
                });
            }
            document.getElementById('jenis_gangguan').value = '';
        }

        document.getElementById('jenis_tiket').addEventListener('change', function() {
            populateGangguanOptions(this.value);
        });

        function updateJenisGangguan() {
            const select = document.getElementById('jenis_gangguan_select');
            const input = document.getElementById('jenis_gangguan');
            if (select.value && select.value !== 'Custom') {
                input.value = select.value;
            } else {
                input.value = '';
            }
            if (select.value === 'Custom') {
                input.focus();
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            populateGangguanOptions(document.getElementById('jenis_tiket').value);
        });

        function updateJenisGangguan() {
            const select = document.getElementById('jenis_gangguan_select');
            const input = document.getElementById('jenis_gangguan');
            
            if (select.value && select.value !== 'Custom') {
                input.value = select.value;
            } else {
                input.value = '';
            }
            
            if (select.value === 'Custom') {
                input.focus();
            }
        }

        function solveTicket(ticketId) {
            if (confirm('Yakin ticket ini selesai?')) { window.location.href = 'proses_solve_ticket.php?ticket_id=' + ticketId; }
        }
        function escalateTicket(ticketId) {
            if (confirm('Yakin escalate ticket ini ke BOR?')) { window.location.href = 'proses_escalate_ticket.php?ticket_id=' + ticketId; }
        }

        document.addEventListener('DOMContentLoaded', function() {
        const provinsiSelect = document.getElementById('provinsi');
        const kotaSelect = document.getElementById('kota');
        if (!provinsiSelect || !kotaSelect) return;

        fetch('wilayah_proxy.php?type=provinces')
            .then(res => res.json())
            .then(provinces => {
                provinces.forEach(prov => {
                    const opt = document.createElement('option');
                    opt.value = prov.id;
                    opt.setAttribute('data-nama', prov.name); 
                    opt.textContent = prov.name;
                    provinsiSelect.appendChild(opt);
                });
            });

        provinsiSelect.addEventListener('change', function() {
            const selectedOption = provinsiSelect.options[provinsiSelect.selectedIndex];
            document.getElementById('provinsi_nama').value = selectedOption.getAttribute('data-nama') || '';
            const provId = this.value;
            kotaSelect.innerHTML = '<option value="">Pilih Kabupaten/Kota...</option>';
            if (provId) {
                fetch('wilayah_proxy.php?type=regencies&prov=' + provId)
                    .then(res => res.json())
                    .then(kabkota => {
                        kabkota.forEach(kota => {
                            const opt = document.createElement('option');
                            opt.value = kota.name;
                            opt.textContent = kota.name;
                            kotaSelect.appendChild(opt);
                        });
                    });
                }
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
        /* Customer Care specific styling */
        .user-role-cc {
            background-color: #007bff !important;
        }

        .dashboard-grid {
            display: flex;
            flex-direction: row;
            gap: 24px;
            margin-top: 40px; 
        }

        /* Field Operations Section */
        .field-ops-section {
            margin-bottom: 30px;
        }

        .field-ops-section h3 {
            margin-bottom: 20px;
            color:rgb(255, 255, 255);
            font-size: 1.4rem;
        }

        .field-ops-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
        }

        .field-card {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            padding: 20px;
            border-radius: 8px;
            text-align: center;
            border: 1px solid #dee2e6;
            transition: all 0.3s ease;
        }

        .field-card:hover {
            box-shadow: 0 8px 24px rgba(0,0,0,0.10);
            transform: translateY(-2px) scale(1.03);
        }

        .field-icon {
            font-size: 1.8rem;
            margin-bottom: 8px;
        }

        .field-number {
            font-size: 1.8rem;
            font-weight: bold;
            color: #495057;
            margin-bottom: 5px;
        }

        .field-label {
            font-size: 0.9rem;
            color: #6c757d;
            font-weight: 500;
        }

        .quick-stats {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 15px;
            margin-bottom: 20px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 8px;
        }

        .quick-stat {
            text-align: center;
            padding: 10px;
        }

        .stat-value {
            display: block;
            font-size: 1.5rem;
            font-weight: bold;
            color: #495057;
            margin-bottom: 5px;
        }

        .stat-label {
            font-size: 0.8rem;
            color: #6c757d;
            font-weight: 500;
        }

        /* Ticket age styling */
        .ticket-age {
            font-size: 0.85rem;
            padding: 2px 6px;
            border-radius: 12px;
            background-color: #e9ecef;
            color: #495057;
            font-weight: 500;
        }

        .age-warning {
            background-color: #fff3cd !important;
            color: #856404 !important;
            border: 1px solid #ffeaa7;
        }

        /* Enhanced form styling */
        #jenis_gangguan_select {
            margin-bottom: 0;
        }

        /* Responsive Design */
        @media (max-width: 1200px) {
            .kpi-grid {
                grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            }
            
            .workflow-pipeline {
                height: 150px;
            }
            
            .stage-bar {
                width: 30px;
            }
        }

        @media (max-width: 768px) {
            .status-banner {
                flex-direction: column;
                gap: 20px;
                text-align: center;
            }
            
            .kpi-grid {
                grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
                gap: 15px;
            }
            
            .kpi-card {
                padding: 20px 15px;
            }
            
            .workflow-pipeline {
                flex-wrap: wrap;
                height: auto;
                gap: 20px;
                padding: 30px 20px;
            }
            
            .workflow-stage {
                min-width: 100px;
            }
            
            .stage-bar {
                height: 80px !important;
                margin-top: 10px;
            }
            
            .field-ops-grid {
                grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
            }
            
            .quick-stats {
                grid-template-columns: 1fr;
                gap: 10px;
            }
        }

        @media (max-width: 576px) {
            .network-overview {
                padding: 20px 15px;
            }
            
            .network-overview h2 {
                font-size: 1.5rem;
            }
            
            .uptime-value {
                font-size: 1.5rem;
            }
            
            .kpi-number {
                font-size: 1.8rem;
            }
            
            .kpi-icon {
                font-size: 2rem;
            }
            
            .workflow-section,
            .field-ops-section,
            .kpi-section {
                padding: 20px 15px;
            }
        }

        .ticket-table {
            font-size: 0.9rem;
        }

        .ticket-table th {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            color: #495057;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.8rem;
            letter-spacing: 0.5px;
        }

        .ticket-table td {
            vertical-align: middle;
        }

        .ticket-table tr:hover {
            background-color: #f8f9fa;
        }

        .status {
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            position: relative;
        }

        .status.open::before,
        .status.on-progress---customer-care::before {
            content: '';
            position: absolute;
            left: -10px;
            top: 50%;
            transform: translateY(-50%);
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background-color: currentColor;
            animation: pulse 2s infinite;
        }

        @media print {
            .main-header,
            .btn-logout,
            .action-buttons {
                display: none;
            }
            
            .kpi-card,
            .workflow-section,
            .field-ops-section {
                break-inside: avoid;
            }
            
            .network-overview {
                background: #f8f9fa !important;
                color: #000 !important;
            }
        }

        .table-ticket {
            width: 100%;
            border-collapse: collapse;
        }
        .table-ticket th, .table-ticket td {
            padding: 12px 10px;
            font-size: 14px;
            border-bottom: 1px solid #eee;
        }

        .dashboard-grid {
            display: flex;
            flex-direction: row;
            gap: 24px;
        }

        .main-action-column {
            flex: 1;
        }

        .ticket-list-column {
            flex: 1;
            display: flex;
            flex-direction: column;
        }

        .ticket-list-column > div {
            flex: 1;
            display: flex;
            flex-direction: column;
        }

        .ticket-list-column > div > div {
            flex: 1;
            overflow-y: auto;
        }

        .table-ticket th,
        .table-ticket td {
            text-align: center;
            vertical-align: middle;
        }

        .table-ticket th:first-child,
        .table-ticket td:first-child {
            text-align: left;
        }
        .table-ticket {
        width: 100%;
        border-collapse: collapse;
        border: 1px solid #bbb; 
        }

        .table-ticket th, .table-ticket td {
            padding: 12px 10px;
            font-size: 14px;
            border: 1px solid #bbb; 
            text-align: center;
            vertical-align: middle;
        }

        .table-ticket th:first-child,
        .table-ticket td:first-child {
            text-align: left;
        }

        .btn-gradient-statistik {
            background: linear-gradient(90deg, #1976d2 0%, #00c6ff 100%);
            color: #fff !important;
            border: none;
            border-radius: 30px;
            font-size: 1.2rem;
            transition: box-shadow 0.2s, transform 0.2s, background 0.2s;
            box-shadow: 0 4px 18px rgba(25, 118, 210, 0.12);
            display: inline-flex;
            align-items: center;
            gap: 10px;
        }
        .btn-gradient-statistik:hover, .btn-gradient-statistik:focus {
            background: linear-gradient(90deg, #00c6ff 0%, #1976d2 100%);
            color: #fff !important;
            transform: translateY(-2px) scale(1.04);
            box-shadow: 0 8px 32px rgba(25, 118, 210, 0.18);
            text-decoration: none;
        }
        .field-card {
        color: #fff;
        border: none;
        border-radius: 12px;
        text-align: center;
        box-shadow: 0 4px 15px rgba(162, 62, 62, 0.07);
        transition: all 0.3s ease;
        padding: 20px;
        }

        .field-pending {
            background: linear-gradient(135deg, #fd7e14 0%, #ffc107 100%);
        }
        .field-scheduled {
            background: linear-gradient(135deg, #007bff 0%, #764ba2 100%);
        }
        .field-progress {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        }
        .field-completed {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
        }
        .field-technician {
            background: linear-gradient(135deg, #28a745 0%, #43e97b 100%);
        }

        .field-card .field-number,
        .field-card .field-label {
            color: #fff !important;
        }

        .statistik-tiket-wrapper {
            display: flex;
            justify-content: center;
            align-items: center;
            margin: 32px 0 0 0;
            width: 100%;
        }

        .btn-gradient-statistik {
            display: block;
            width: 100%;
            text-align: left;
            font-size: 1.2rem;
            padding: 14px 0;
            padding-left: 32px;      
            border-radius: 30px;
            box-sizing: border-box;
            background: linear-gradient(90deg, #1976d2 0%, #00c6ff 100%);
            color: #fff !important;
            border: none;
            transition: box-shadow 0.2s, transform 0.2s, background 0.2s;
            box-shadow: 0 4px 18px rgba(25, 118, 210, 0.12);
            align-items: center;
            gap: 10px;
            margin-bottom: 24px;
        }
</style>

</body>
</html>