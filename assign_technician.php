<?php
include 'config/db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'Dispatch') {
    header('Location: login.php');
    exit();
}

if (!isset($_GET['wo_id']) || empty($_GET['wo_id'])) {
    header('Location: dashboard_dispatch.php');
    exit();
}

$wo_id = (int)$_GET['wo_id'];

$sql_wo = "SELECT 
    wo.id,
    wo.wo_code,
    wo.status,
    wo.assigned_to_vendor_id,
    t.ticket_code,
    t.title as ticket_title,
    t.description,
    c.full_name as customer_name
FROM tr_work_orders wo
JOIN tr_tickets t ON wo.ticket_id = t.id
JOIN ms_customers c ON t.customer_id = c.id
WHERE wo.id = '$wo_id'";

$result_wo = mysqli_query($conn, $sql_wo);

if (mysqli_num_rows($result_wo) == 0) {
    header('Location: dashboard_dispatch.php?status=wo_not_found');
    exit();
}

$wo = mysqli_fetch_assoc($result_wo);

$sql_vendors = "SELECT id, full_name FROM ms_users WHERE role = 'Vendor IKR' ORDER BY full_name";
$result_vendors = mysqli_query($conn, $sql_vendors);
$vendors = mysqli_fetch_all($result_vendors, MYSQLI_ASSOC);

$message = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $assigned_vendor = (int)$_POST['assigned_vendor'];
    
    if (!empty($assigned_vendor)) {
        $vendor_check = "SELECT full_name FROM ms_users WHERE id = '$assigned_vendor' AND role = 'Vendor IKR'";
        $vendor_result = mysqli_query($conn, $vendor_check);
        
        if (mysqli_num_rows($vendor_result) == 1) {
            $vendor_data = mysqli_fetch_assoc($vendor_result);
            $update_sql = "UPDATE tr_work_orders SET assigned_to_vendor_id = '$assigned_vendor' WHERE id = '$wo_id'";
            
            if (mysqli_query($conn, $update_sql)) {
                $message = '<div class="alert alert-success">Teknisi berhasil di-assign ke Work Order!</div>';
                
                $wo['assigned_to_vendor_id'] = $assigned_vendor;
            } else {
                $message = '<div class="alert alert-error">Gagal assign teknisi: ' . mysqli_error($conn) . '</div>';
            }
        } else {
            $message = '<div class="alert alert-error">Teknisi tidak valid!</div>';
        }
    } else {
        $message = '<div class="alert alert-error">Pilih teknisi terlebih dahulu!</div>';
    }
}

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Assign Technician - Work Order <?php echo htmlspecialchars($wo['wo_code']); ?></title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

    <header class="main-header">
        <div class="container">
            <h1>Assign Technician - Work Order Management</h1>
            <div class="user-info">
                <span>Selamat datang, <strong><?php echo htmlspecialchars($_SESSION['user_full_name']); ?></strong>!</span>
                <span class="user-role user-role-dispatch">[<?php echo htmlspecialchars($_SESSION['user_role']); ?>]</span>
                <a href="logout.php" class="btn-logout">Logout</a>
            </div>
        </div>
    </header>

    <main class="container">
        
        <div class="back-button-section">
            <a href="dashboard_dispatch.php" class="btn-back">← Kembali ke Dashboard</a>
        </div>

        <?php echo $message; ?>

        <div class="dashboard-grid">
            
            <div class="main-action-column">
                <section class="card">
                    <h3>📋 Informasi Work Order</h3>
                    
                    <div class="info-row">
                        <label>WO Code:</label>
                        <strong><?php echo htmlspecialchars($wo['wo_code']); ?></strong>
                    </div>
                    
                    <div class="info-row">
                        <label>Ticket:</label>
                        <span><?php echo htmlspecialchars($wo['ticket_code']); ?></span>
                    </div>
                    
                    <div class="info-row">
                        <label>Customer:</label>
                        <span><?php echo htmlspecialchars($wo['customer_name']); ?></span>
                    </div>
                    
                    <div class="info-row">
                        <label>Masalah:</label>
                        <span><?php echo htmlspecialchars($wo['ticket_title']); ?></span>
                    </div>
                    
                    <div class="info-row">
                        <label>Deskripsi:</label>
                        <div class="description-box">
                            <?php echo nl2br(htmlspecialchars($wo['description'])); ?>
                        </div>
                    </div>
                    
                    <div class="info-row">
                        <label>Status WO:</label>
                        <span class="status" style="background-color: <?php echo $wo['status'] == 'Pending' ? '#ffc107' : '#17a2b8'; ?>; color: <?php echo $wo['status'] == 'Pending' ? '#212529' : 'white'; ?>;">
                            <?php echo htmlspecialchars($wo['status']); ?>
                        </span>
                    </div>
                </section>
            </div>

            <div class="ticket-list-column">
                <section class="card">
                    <h3>👨‍🔧 Assign Technician</h3>
                    
                    <?php if ($wo['assigned_to_vendor_id']): ?>
                        <?php
                        $current_vendor_sql = "SELECT full_name FROM ms_users WHERE id = '{$wo['assigned_to_vendor_id']}'";
                        $current_vendor_result = mysqli_query($conn, $current_vendor_sql);
                        $current_vendor = mysqli_fetch_assoc($current_vendor_result);
                        ?>
                        <div class="alert alert-success">
                            <strong>Teknisi Saat Ini:</strong><br>
                            <?php echo htmlspecialchars($current_vendor['full_name']); ?>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST">
                        <div class="form-group">
                            <label for="assigned_vendor">Pilih Teknisi IKR</label>
                            <select id="assigned_vendor" name="assigned_vendor" required>
                                <option value="">-- Pilih Teknisi --</option>
                                <?php foreach ($vendors as $vendor): ?>
                                    <option value="<?php echo $vendor['id']; ?>" 
                                            <?php echo ($wo['assigned_to_vendor_id'] == $vendor['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($vendor['full_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <button type="submit" class="btn">
                            <?php echo $wo['assigned_to_vendor_id'] ? 'Update Assignment' : 'Assign Technician'; ?>
                        </button>
                    </form>
                    
                    <div style="margin-top: 20px; padding: 15px; background-color: #f8f9fa; border-radius: 5px; border-left: 4px solid #17a2b8;">
                        <h4 style="margin-top: 0; color: #17a2b8;">Teknisi Available</h4>
                        <ul style="margin: 10px 0; padding-left: 20px;">
                            <?php foreach ($vendors as $vendor): ?>
                                <li style="margin-bottom: 5px;">
                                    <strong><?php echo htmlspecialchars($vendor['full_name']); ?></strong>
                                    <?php if ($wo['assigned_to_vendor_id'] == $vendor['id']): ?>
                                        <span class="status" style="background-color: #28a745; margin-left: 10px;">Current</span>
                                    <?php endif; ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </section>
            </div>

        </div>
    </main>

</body>
</html>