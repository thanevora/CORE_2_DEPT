<?php
header('Content-Type: application/json');
include("../../main_connection.php");

$db_name = "rest_m1_trs"; // ✅ pick the DB you want

if (!isset($connections[$db_name])) {
    die("❌ Connection not found for $db_name");
}

$conn = $connections[$db_name]; // ✅ get the correct DB connection

$query = "
    SELECT 
        (SELECT COUNT(*) FROM tables) AS total_tables,
        (SELECT COUNT(*) FROM tables WHERE status = 'Available') AS Available,
        (SELECT COUNT(*) FROM tables WHERE status = 'Queued') AS Queued,
        (SELECT COUNT(*) FROM tables WHERE status = 'Occupied') AS Occupied,
        (SELECT COUNT(*) FROM tables WHERE status = 'Reserved') AS Reserved,
        (SELECT COUNT(*) FROM tables WHERE status = 'Maintenance') AS Maintenance
";

$result = mysqli_query($conn, $query);

if (!$result) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Count query failed.']);
    exit;
}

$row = mysqli_fetch_assoc($result);

echo json_encode([
    'status' => 'success',
    'labels' => ['Available', 'Queued','Occupied' ,'Reserved', 'Maintenance'],
    'data' => [
        (int)$row['Available'],
        (int)$row['Queued'],
        (int)$row['Occupied'],
        (int)$row['Reserved'],
        (int)$row['Maintenance']
    ]
]);
