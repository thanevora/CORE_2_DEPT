<?php
session_start();
include("../../main_connection.php");

$db_name = "rest_m2_inventory";

if (!isset($connections[$db_name])) {
    die(json_encode(['status' => 'error', 'message' => "Connection not found"]));
}

$conn = $connections[$db_name];

$sql = "SELECT item_id, item_name, category, quantity, critical_level, 
               created_at, updated_at, unit_price, notes, request_status, 
               expiry_date, last_restock_date, location, image_url
        FROM inventory_and_stock 
        ORDER BY created_at DESC";

$result = $conn->query($sql);

if (!$result) {
    echo json_encode([
        'status' => 'error',
        'message' => 'SQL Error: ' . $conn->error
    ]);
    exit();
}

$stocks = [];
while ($row = $result->fetch_assoc()) {
    $stocks[] = $row;
}

echo json_encode([
    'status' => 'success',
    'stocks' => $stocks,
    'total' => count($stocks)
]);

$conn->close();
?>