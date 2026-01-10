<?php
header('Content-Type: application/json');
include("../../main_connection.php");

$db_name = "rest_m2_inventory";

if (!isset($connections[$db_name])) {
    die(json_encode(['status' => 'error', 'message' => "❌ Connection not found for $db_name"]));
}

$conn = $connections[$db_name];

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die(json_encode(['status' => 'error', 'message' => 'Invalid item ID']));
}

$item_id = mysqli_real_escape_string($conn, $_GET['id']);

// Get all details for a specific item
$query = "SELECT 
            item_id, 
            item_name, 
            category, 
            quantity, 
            unit, 
            unit_price, 
            critical_level, 
            expiry_date,
            supplier,
            last_updated,
            status
          FROM inventory_and_stock 
          WHERE item_id = $item_id";
          
$result = mysqli_query($conn, $query);

if ($result && mysqli_num_rows($result) > 0) {
    $stock = mysqli_fetch_assoc($result);
    echo json_encode(['status' => 'success', 'stock' => $stock]);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Item not found']);
}
?>