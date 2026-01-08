<?php
session_start();
header('Content-Type: application/json');
require_once("../../main_connection.php");

$db_name = "rest_m1_trs";
if (!isset($connections[$db_name])) {
    die(json_encode(['status' => 'error', 'message' => "Connection not found for $db_name"]));
}

$conn = $connections[$db_name];

$sql = "SELECT table_id, name, category, capacity, image_url, status FROM tables ORDER BY name";
$result = $conn->query($sql);

if (!$result) {
    echo json_encode(['status' => 'error', 'message' => 'Query failed: ' . $conn->error]);
    exit;
}

$tables = [];
while ($row = $result->fetch_assoc()) {
    $tables[] = [
        'id' => $row['table_id'],
        'name' => $row['name'],
        'category' => $row['category'],
        'capacity' => $row['capacity'],
        'image_url' => $row['image_url'] ? '../M1/Table_images/' . $row['image_url'] : '../assets/default-table.jpg',
        'status' => $row['status']
    ];
}

echo json_encode([
    'status' => 'success',
    'tables' => $tables,
    'count' => count($tables)
]);

$conn->close();
?>