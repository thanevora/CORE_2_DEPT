<?php
header('Content-Type: application/json');
include("../../main_connection.php");

$db_name = "rest_m2_inventory";

if (!isset($connections[$db_name])) {
    die(json_encode(['status' => 'error', 'message' => "❌ Connection not found for $db_name"]));
}

$conn = $connections[$db_name];

// Get the JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['item_ids']) || !is_array($input['item_ids'])) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid item IDs']);
    exit;
}

$item_ids = array_map('intval', $input['item_ids']);
$placeholders = implode(',', array_fill(0, count($item_ids), '?'));

// Prepare the delete statement
$stmt = $conn->prepare("DELETE FROM inventory_and_stock WHERE item_id IN ($placeholders)");

if ($stmt === false) {
    echo json_encode(['status' => 'error', 'message' => 'Failed to prepare statement: ' . $conn->error]);
    exit;
}

// Bind parameters
$types = str_repeat('i', count($item_ids));
$stmt->bind_param($types, ...$item_ids);

// Execute the statement
if ($stmt->execute()) {
    echo json_encode(['status' => 'success', 'message' => 'Items deleted successfully']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Failed to delete items: ' . $stmt->error]);
}

$stmt->close();
?>