<?php
include 'config/db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'Vendor IKR') {
    header('Location: login.php');
    exit();
}

if (!isset($_GET['wo_id']) || empty($_GET['wo_id'])) {
    header('Location: dashboard_vendor.php');
    exit();
}

$wo_id = (int)$_GET['wo_id'];
$vendor_user_id = $_SESSION['user_id'];

$check_sql = "SELECT 
    wo.id,
    wo.wo_code,
    wo.status as wo_status,
    wo.scheduled_visit_date,
    wo.started_at,
    wo.estimated_duration,
    wo.visit_report,
    t.id as ticket_id,
    t.ticket_code,
    t.jenis_tiket,
    t.title as ticket_title,
    t.description as ticket_description,
    c.full_name as customer_name,
    c.address as customer_address,
    c.phone_number as customer_phone,
    c.email as customer_email
FROM tr_work_orders wo
JOIN tr_tickets t ON wo.ticket_id = t.id
JOIN ms_customers c ON t.customer_id = c.id
WHERE wo.id = '$wo_id' 
AND wo.assigned_to_vendor_id = '$vendor_user_id' 
AND wo.status IN ('In Progress', 'Scheduled')";

$check_result = mysqli_query($conn, $check_sql);

if (mysqli_num_rows($check_result) == 0) {
    header('Location: dashboard_vendor.php?status=error_invalid_wo');
    exit();
}

$wo = mysqli_fetch_assoc($check_result);

$draft_sql = "SELECT * FROM tr_work_reports WHERE work_order_id = '$wo_id'";
$draft_result = mysqli_query($conn, $draft_sql);
$existing_report = mysqli_num_rows($draft_result) > 0 ? mysqli_fetch_assoc($draft_result) : null;

$equipment_data = [];
$materials_data = [];
if ($existing_report) {
    $equipment_data = $existing_report['equipment_replaced'] ? json_decode($existing_report['equipment_replaced'], true) : [];
    $materials_data = $existing_report['materials_used'] ? json_decode($existing_report['materials_used'], true) : [];
}

$message = '';
if (isset($_GET['status'])) {
    switch($_GET['status']) {
        case 'draft_saved':
            $message = '<div class="alert alert-success">Draft laporan berhasil disimpan! Anda bisa melanjutkan nanti.</div>';
            break;
        case 'report_submitted':
            $message = '<div class="alert alert-success">Laporan berhasil disubmit ke BOR untuk review!</div>';
            break;
        case 'error_save':
            $message = '<div class="alert alert-error">Gagal menyimpan laporan. Silakan coba lagi.</div>';
            break;
    }
}

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

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Work Order - <?php echo htmlspecialchars($wo['wo_code']); ?></title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

    <header class="main-header">
        <div class="container">
            <h1>Buat Laporan Work Order</h1>
            <div class="user-info">
                <span>Selamat datang, <strong><?php echo htmlspecialchars($_SESSION['user_full_name']); ?></strong>!</span>
                <span class="user-role user-role-vendor">[<?php echo htmlspecialchars($_SESSION['user_role']); ?>]</span>
                <a href="logout.php" class="btn-logout">Logout</a>
            </div>
        </div>
    </header>

    <main class="container">
        
        <div class="back-button-section">
            <a href="dashboard_vendor.php" class="btn-back">Kembali ke Dashboard</a>
            <a href="view_work_order.php?id=<?php echo $wo['id']; ?>" class="btn-back" style="background-color: #6c757d; margin-left: 10px;">Info WO</a>
        </div>

        <?php echo $message; ?>

        <div class="card wo-header-card">
            <div class="wo-header">
                <div class="wo-title">
                    <h2><?php echo htmlspecialchars($wo['wo_code']); ?></h2>
                    <div class="wo-meta">
                        <span>Ticket: <strong><?php echo htmlspecialchars($wo['ticket_code']); ?></strong></span>
                        <span>Jenis WO: <strong><?php echo htmlspecialchars($wo['jenis_tiket']); ?></strong></span>
                        <span>Customer: <strong><?php echo htmlspecialchars($wo['customer_name']); ?></strong></span>
                        <?php if ($wo['started_at']): ?>
                        <span>Mulai: <strong><?php echo formatTanggalIndonesia($wo['started_at']); ?></strong></span>
                        <?php endif; ?>
                    </div>
                </div>
                
                <?php if ($existing_report): ?>
                <div class="draft-status">
                    <span class="status" style="background-color: #ffc107; color: #212529;">
                        Ada Draft Tersimpan
                    </span>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <form id="reportForm" action="proses_save_work_report.php" method="POST">
            <input type="hidden" name="wo_id" value="<?php echo $wo_id; ?>">
            
            <div class="card">
                <div class="form-section">
                    <h3>Informasi Penyelesaian</h3>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="completion_status">Status Penyelesaian *</label>
                            <select id="completion_status" name="completion_status" required>
                                <option value="">Pilih status...</option>
                                <option value="Solved" <?php echo ($existing_report && strpos($existing_report['equipment_replaced'], 'Solved') !== false) ? 'selected' : ''; ?>>Masalah Berhasil Diperbaiki</option>
                                <option value="Partial">Diperbaiki Sebagian (Perlu Follow-up)</option>
                                <option value="Cannot Fix">Tidak Bisa Diperbaiki</option>
                                <option value="Customer Not Available">Customer Tidak Ada</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="work_duration">Durasi Aktual Pengerjaan (menit)</label>
                            <input type="number" id="work_duration" name="work_duration" 
                                   min="1" max="600" placeholder="Berapa menit yang dibutuhkan?"
                                   value="<?php echo $existing_report ? $existing_report['actual_duration'] ?? '' : ''; ?>">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="work_description">Deskripsi Pekerjaan yang Dilakukan *</label>
                        <textarea id="work_description" name="work_description" rows="5" required
                                  placeholder="Tuliskan detail pekerjaan yang dilakukan:&#10;- Masalah yang ditemukan&#10;- Tindakan yang dilakukan&#10;- Kondisi akhir&#10;- Catatan tambahan"><?php echo $existing_report ? htmlspecialchars($wo['visit_report']) : ''; ?></textarea>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="form-section">
                    <h3>Pergantian Equipment</h3>
                    
                    <div class="form-group">
                        <label for="equipment_replaced">Equipment yang Diganti</label>
                        <textarea id="equipment_replaced" name="equipment_replaced" rows="3"
                                  placeholder="Contoh: Ganti router Huawei HG8245H ke ZTE F609, Serial: ABC123"><?php echo isset($equipment_data['equipment_replaced']) ? htmlspecialchars($equipment_data['equipment_replaced']) : ''; ?></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="cables_replaced">Kabel yang Diganti</label>
                        <textarea id="cables_replaced" name="cables_replaced" rows="3"
                                  placeholder="Contoh: Ganti kabel UTP Cat6 sepanjang 20 meter, Ganti kabel fiber indoor 5 meter"><?php echo isset($equipment_data['cables_replaced']) ? htmlspecialchars($equipment_data['cables_replaced']) : ''; ?></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="new_installations">Instalasi Baru</label>
                        <textarea id="new_installations" name="new_installations" rows="3"
                                  placeholder="Contoh: Pasang splitter 1:2, Tambah socket baru di kamar"><?php echo isset($equipment_data['new_installations']) ? htmlspecialchars($equipment_data['new_installations']) : ''; ?></textarea>
                    </div>

                    <h3>Pencabutan Equipment</h3>

                    <div class="form-group">
                        <label for="equipment_removed">Equipment yang Dicabut</label>
                        <textarea id="equipment_removed" name="equipment_removed" rows="3"
                                  placeholder="Contoh: Cabut ONT lama, Cabut kabel fiber dari tiang"><?php echo isset($equipment_data['equipment_removed']) ? htmlspecialchars($equipment_data['equipment_removed']) : ''; ?></textarea>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="form-section">
                    <h3>Pengukuran Teknis</h3>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="signal_before">Signal Sebelum (dBm)</label>
                            <input type="text" id="signal_before" name="signal_before" 
                                   placeholder="Contoh: -15.2 dBm"
                                   value="<?php echo $existing_report ? htmlspecialchars($existing_report['signal_before']) : ''; ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="signal_after">Signal Sesudah (dBm)</label>
                            <input type="text" id="signal_after" name="signal_after" 
                                   placeholder="Contoh: -12.8 dBm"
                                   value="<?php echo $existing_report ? htmlspecialchars($existing_report['signal_after']) : ''; ?>">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="speed_test_result">Hasil Speed Test</label>
                        <input type="text" id="speed_test_result" name="speed_test_result" 
                               placeholder="Contoh: Download: 95 Mbps, Upload: 45 Mbps, Ping: 12ms"
                               value="<?php echo $existing_report ? htmlspecialchars($existing_report['speed_test_result']) : ''; ?>">
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="form-section">
                    <h3>Material yang Digunakan</h3>
                    <div id="materials-container">
                        <?php if (!empty($materials_data)): ?>
                            <?php foreach ($materials_data as $index => $material): ?>
                            <div class="material-row">
                                <div class="form-row">
                                    <div class="form-group">
                                        <input type="text" name="materials[<?php echo $index; ?>][name]" 
                                               placeholder="Nama material" value="<?php echo htmlspecialchars($material['name']); ?>">
                                    </div>
                                    <div class="form-group" style="width: 100px;">
                                        <input type="number" name="materials[<?php echo $index; ?>][quantity]" 
                                               placeholder="Qty" min="1" value="<?php echo $material['quantity']; ?>">
                                    </div>
                                    <div class="form-group" style="width: 100px;">
                                        <input type="text" name="materials[<?php echo $index; ?>][unit]" 
                                               placeholder="Unit" value="<?php echo htmlspecialchars($material['unit']); ?>">
                                    </div>
                                    <div class="form-group">
                                        <input type="text" name="materials[<?php echo $index; ?>][notes]" 
                                               placeholder="Catatan" value="<?php echo htmlspecialchars($material['notes']); ?>">
                                    </div>
                                    <button type="button" onclick="removeMaterialRow(this)" class="btn-remove">×</button>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                        <div class="material-row">
                            <div class="form-row">
                                <div class="form-group">
                                    <input type="text" name="materials[0][name]" placeholder="Nama material">
                                </div>
                                <div class="form-group" style="width: 100px;">
                                    <input type="number" name="materials[0][quantity]" placeholder="Qty" min="0">
                                </div>
                                <div class="form-group" style="width: 100px;">
                                    <input type="text" name="materials[0][unit]" placeholder="Unit">
                                </div>
                                <div class="form-group">
                                    <input type="text" name="materials[0][notes]" placeholder="Catatan">
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                    <button type="button" onclick="addMaterialRow()" class="btn-small">+ Tambah Material</button>
                </div>
            </div>

            <div class="card">
                <div class="action-buttons-section">
                    <div class="buttons-row">
                        <button type="submit" name="action" value="save_draft" class="btn btn-secondary">
                            Simpan Draft
                        </button>
                        <button type="submit" name="action" value="submit_final" class="btn btn-primary" onclick="return confirmSubmit()">
                            Submit ke Dispatch
                        </button>
                    </div>
                    <small class="help-text">
                        <strong>Tips:</strong> Simpan draft dulu jika belum yakin. Setelah submit ke BOR, laporan tidak bisa diedit lagi.
                    </small>
                </div>
            </div>

        </form>

    </main>

    <script>
        let materialIndex = <?php echo !empty($materials_data) ? count($materials_data) : 1; ?>;
        
        function addMaterialRow() {
            const container = document.getElementById('materials-container');
            const newRow = document.createElement('div');
            newRow.className = 'material-row';
            newRow.innerHTML = `
                <div class="form-row">
                    <div class="form-group">
                        <input type="text" name="materials[${materialIndex}][name]" placeholder="Nama material">
                    </div>
                    <div class="form-group" style="width: 100px;">
                        <input type="number" name="materials[${materialIndex}][quantity]" placeholder="Qty" min="0">
                    </div>
                    <div class="form-group" style="width: 100px;">
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

        function confirmSubmit() {
        return confirm('Yakin ingin submit laporan ke Dispatch?\n\nSetelah disubmit, laporan akan dikirim ke Dispatch untuk review sebelum ke BOR.');
        }

        setInterval(function() {
            const formData = new FormData(document.getElementById('reportForm'));
            formData.set('action', 'auto_save');
            
            fetch('proses_save_work_report.php', {
                method: 'POST',
                body: formData
            }).then(response => {
                if (response.ok) {
                    console.log('Auto-save berhasil');
                }
            }).catch(error => {
                console.log('Auto-save gagal:', error);
            });
        }, 120000); 
    </script>

    <style>
        .user-role-vendor {
            background-color: #28a745 !important;
        }

        .wo-header-card {
            margin-bottom: 25px;
            border-left: 4px solid #28a745;
        }

        .wo-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 20px;
        }

        .wo-title h2 {
            margin: 0 0 10px 0;
            color: #28a745;
        }

        .wo-meta {
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
            font-size: 14px;
            color: #6c757d;
        }

        .draft-status {
            flex-shrink: 0;
        }

        .problem-summary {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 6px;
            border-left: 3px solid #17a2b8;
        }

        .problem-summary h4 {
            margin-top: 0;
            color: #17a2b8;
        }

        .form-section {
            padding: 0;
        }

        .form-section h3 {
            margin-bottom: 20px;
            color: #495057;
            border-bottom: 2px solid #e9ecef;
            padding-bottom: 10px;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 15px;
        }

        @media (max-width: 768px) {
            .form-row {
                grid-template-columns: 1fr;
            }
            
            .wo-meta {
                flex-direction: column;
                gap: 8px;
            }
        }

        .material-row {
            margin-bottom: 15px;
            padding: 15px;
            border: 1px solid #dee2e6;
            border-radius: 6px;
            background-color: white;
        }

        .material-row .form-row {
            grid-template-columns: 2fr 100px 100px 2fr 40px;
            align-items: center;
            margin-bottom: 0;
        }

        .btn-small {
            padding: 8px 15px;
            background-color: #28a745;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
        }

        .btn-small:hover {
            background-color: #218838;
        }

        .btn-remove {
            background-color: #dc3545;
            color: white;
            border: none;
            border-radius: 50%;
            width: 35px;
            height: 35px;
            cursor: pointer;
            font-size: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .btn-remove:hover {
            background-color: #c82333;
        }

        .action-buttons-section {
            text-align: center;
            padding: 20px 0;
        }

        .buttons-row {
            display: flex;
            gap: 15px;
            justify-content: center;
            margin-bottom: 15px;
        }

        @media (max-width: 768px) {
            .buttons-row {
                flex-direction: column;
                align-items: center;
            }
            
            .buttons-row .btn {
                width: 100%;
                max-width: 300px;
            }
        }

        .btn-secondary {
            background-color: #6c757d;
            color: white;
        }

        .btn-secondary:hover {
            background-color: #5a6268;
        }

        .btn-primary {
            background-color: #28a745;
            color: white;
        }

        .btn-primary:hover {
            background-color: #218838;
        }

        .help-text {
            color: #6c757d;
            font-style: italic;
            text-align: center;
        }

        .form-group label {
            font-weight: 600;
            color: #495057;
            margin-bottom: 8px;
            display: block;
        }

        .form-group input,
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ced4da;
            border-radius: 4px;
            font-size: 14px;
            transition: border-color 0.15s ease-in-out;
            box-sizing: border-box;
        }

        .form-group input:focus,
        .form-group textarea:focus,
        .form-group select:focus {
            border-color: #80bdff;
            outline: 0;
            box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
        }
    </style>

</body>
</html>