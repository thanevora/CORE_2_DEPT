<?php
session_start();
header('Content-Type: application/json');
require_once("../../main_connection.php");

$db_name = "rest_m1_trs";
if (!isset($connections[$db_name])) {
    die(json_encode(['status' => 'error', 'message' => "Connection not found for $db_name"]));
}

$conn = $connections[$db_name];

// Get search and filter parameters
$search = $_GET['search'] ?? '';
$filter = $_GET['filter'] ?? 'all';

// Build query
$sql = "SELECT table_id, name, category, capacity, image_url, status, created_at FROM tables WHERE 1=1";

if (!empty($search)) {
    $sql .= " AND (name LIKE '%" . $conn->real_escape_string($search) . "%' 
                  OR category LIKE '%" . $conn->real_escape_string($search) . "%')";
}

if ($filter !== 'all') {
    $sql .= " AND status = '" . $conn->real_escape_string($filter) . "'";
}

$sql .= " ORDER BY created_at DESC";

$result = $conn->query($sql);

if (!$result) {
    echo json_encode(['status' => 'error', 'message' => 'Query failed: ' . $conn->error]);
    exit;
}

$tables = [];
while ($row = $result->fetch_assoc()) {
    $tables[] = [
        'table_id' => $row['table_id'],
        'name' => $row['name'],
        'category' => $row['category'],
        'capacity' => $row['capacity'],
        'image_url' => $row['image_url'] ? '../../' . $row['image_url'] : '../../assets/default-table.jpg',
        'status' => $row['status'],
        'created_at' => $row['created_at']
    ];
}

echo json_encode([
    'status' => 'success',
    'tables' => $tables,
    'count' => count($tables)
]);

$conn->close();
?>