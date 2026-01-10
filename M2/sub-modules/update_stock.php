<?php
session_start();
include("../../main_connection.php");

$db_name = "rest_m2_inventory";

if (!isset($connections[$db_name])) {
    die(json_encode(['status' => 'error', 'message' => 'Database connection not found']));
}

$conn = $connections[$db_name];

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid JSON data']);
    exit;
}

$item_id = $input['item_id'] ?? null;
$item_name = $input['item_name'] ?? null;
$category = $input['category'] ?? null;
$quantity = $input['quantity'] ?? null;
$critical_level = $input['critical_level'] ?? null;
$unit_price = $input['unit_price'] ?? null;
$notes = $input['notes'] ?? null;
$expiry_date = $input['expiry_date'] ?? null;
$location = $input['location'] ?? null;
$last_restock_date = $input['last_restock_date'] ?? null;

if (!$item_id) {
    echo json_encode(['status' => 'error', 'message' => 'Item ID is required']);
    exit;
}

try {
    $stmt = $conn->prepare("UPDATE inventory_and_stock SET 
        item_name = COALESCE(?, item_name),
        category = COALESCE(?, category),
        quantity = COALESCE(?, quantity),
        critical_level = COALESCE(?, critical_level),
        unit_price = COALESCE(?, unit_price),
        notes = COALESCE(?, notes),
        expiry_date = COALESCE(?, expiry_date),
        location = COALESCE(?, location),
        last_restock_date = COALESCE(?, last_restock_date),
        updated_at = NOW()
        WHERE item_id = ?");
    
    $stmt->bind_param(
        "ssiisssssi",
        $item_name,
        $category,
        $quantity,
        $critical_level,
        $unit_price,
        $notes,
        $expiry_date,
        $location,
        $last_restock_date,
        $item_id
    );
    
    if ($stmt->execute()) {
        echo json_encode([
            'status' => 'success', 
            'message' => 'Stock updated successfully'
        ]);
    } else {
        echo json_encode([
            'status' => 'error', 
            'message' => 'Failed to update stock: ' . $stmt->error
        ]);
    }
    
    $stmt->close();
} catch (Exception $e) {
    echo json_encode([
        'status' => 'error', 
        'message' => 'Error: ' . $e->getMessage()
    ]);
}

$conn->close();
?>