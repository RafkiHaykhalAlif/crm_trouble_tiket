<?php
include 'config/db_connect.php';

echo "<h2>🔍 Debug Work Order Status</h2>";

// Cek semua WO yang statusnya Completed by Technician
$sql = "SELECT 
    wo.id,
    wo.wo_code,
    wo.status,
    wo.completed_at,
    wo.assigned_to_vendor_id,
    u_tech.full_name as technician_name,
    t.ticket_code
FROM tr_work_orders wo
LEFT JOIN ms_users u_tech ON wo.assigned_to_vendor_id = u_tech.id
LEFT JOIN tr_tickets t ON wo.ticket_id = t.id
WHERE wo.status = 'Completed by Technician'
ORDER BY wo.completed_at DESC";

$result = mysqli_query($conn, $sql);

echo "<h3>📋 WO dengan status 'Completed by Technician':</h3>";

if (mysqli_num_rows($result) == 0) {
    echo "<p style='color: red;'>❌ TIDAK ADA WO dengan status 'Completed by Technician'!</p>";
    
    // Cek status apa aja yang ada
    echo "<h3>🔍 Semua status WO yang ada di database:</h3>";
    $status_sql = "SELECT status, COUNT(*) as jumlah FROM tr_work_orders GROUP BY status ORDER BY jumlah DESC";
    $status_result = mysqli_query($conn, $status_sql);
    
    echo "<ul>";
    while ($status_row = mysqli_fetch_assoc($status_result)) {
        echo "<li><strong>" . $status_row['status'] . "</strong>: " . $status_row['jumlah'] . " WO</li>";
    }
    echo "</ul>";
    
} else {
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr style='background-color: #f0f0f0;'>";
    echo "<th>WO Code</th><th>Status</th><th>Technician</th><th>Completed At</th><th>Ticket</th>";
    echo "</tr>";
    
    while ($row = mysqli_fetch_assoc($result)) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['wo_code']) . "</td>";
        echo "<td style='font-weight: bold; color: green;'>" . htmlspecialchars($row['status']) . "</td>";
        echo "<td>" . htmlspecialchars($row['technician_name'] ?: 'N/A') . "</td>";
        echo "<td>" . ($row['completed_at'] ?: 'N/A') . "</td>";
        echo "<td>" . htmlspecialchars($row['ticket_code']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
}

// Cek juga WO terbaru dari teknisi yang login terakhir
echo "<h3>🔍 WO terbaru dari semua teknisi:</h3>";
$recent_sql = "SELECT 
    wo.id,
    wo.wo_code,
    wo.status,
    wo.completed_at,
    wo.updated_at,
    u_tech.full_name as technician_name
FROM tr_work_orders wo
LEFT JOIN ms_users u_tech ON wo.assigned_to_vendor_id = u_tech.id
WHERE u_tech.role = 'Vendor IKR'
ORDER BY COALESCE(wo.updated_at, wo.created_at) DESC
LIMIT 10";

$recent_result = mysqli_query($conn, $recent_sql);

echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
echo "<tr style='background-color: #f0f0f0;'>";
echo "<th>WO Code</th><th>Status</th><th>Technician</th><th>Last Update</th>";
echo "</tr>";

while ($row = mysqli_fetch_assoc($recent_result)) {
    $status_color = '';
    if ($row['status'] == 'Completed by Technician') {
        $status_color = 'color: green; font-weight: bold;';
    } elseif ($row['status'] == 'Completed') {
        $status_color = 'color: blue; font-weight: bold;';
    }
    
    echo "<tr>";
    echo "<td>" . htmlspecialchars($row['wo_code']) . "</td>";
    echo "<td style='$status_color'>" . htmlspecialchars($row['status']) . "</td>";
    echo "<td>" . htmlspecialchars($row['technician_name'] ?: 'N/A') . "</td>";
    echo "<td>" . ($row['updated_at'] ?: $row['completed_at'] ?: 'N/A') . "</td>";
    echo "</tr>";
}
echo "</table>";

mysqli_close($conn);
?>

<style>
table { margin: 10px 0; }
th, td { padding: 8px; text-align: left; }
th { background-color: #f0f0f0; }
tr:nth-child(even) { background-color: #f9f9f9; }
</style>