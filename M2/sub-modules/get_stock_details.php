<?php
session_start();
include("../../main_connection.php");

$db_name = "rest_m2_inventory";

if (!isset($connections[$db_name])) {
    die(json_encode(['success' => false, 'error' => 'Database connection not found']));
}

$conn = $connections[$db_name];

// Get item ID from request
$itemId = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($itemId <= 0) {
    die(json_encode(['success' => false, 'error' => 'Invalid item ID']));
}

// Fetch stock item details
$sql = "SELECT item_id, item_name, category, quantity, critical_level, created_at, updated_at, unit_price, notes, request_status, expiry_date, last_restock_date, location, image_url
        FROM inventory_and_stock 
        WHERE item_id = ?";
        
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $itemId);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    echo json_encode(['success' => true, 'data' => $row]);
} else {
    echo json_encode(['success' => false, 'error' => 'Item not found']);
}

$stmt->close();
?>