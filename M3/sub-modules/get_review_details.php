<?php
session_start();
include("../../main_connection.php");

// Database configuration
$db_name = "rest_m3_menu";

// Check database connection
if (!isset($connections[$db_name])) {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Database connection not found']);
    exit;
}

$conn = $connections[$db_name];

// Get menu_id from request
$menu_id = isset($_GET['menu_id']) ? (int)$_GET['menu_id'] : 0;

if ($menu_id <= 0) {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Invalid menu item ID']);
    exit;
}

// Fetch menu item details
$sql = "SELECT * FROM menu WHERE menu_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $menu_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Menu item not found']);
    exit;
}

$menu_item = $result->fetch_assoc();

// Prepare response data
$response = [
    'status' => 'success',
    'data' => [
        'menu_id' => $menu_item['menu_id'],
        'name' => $menu_item['name'],
        'category' => $menu_item['category'],
        'description' => $menu_item['description'],
        'variant' => $menu_item['variant'],
        'price' => $menu_item['price'],
        'prep_time' => $menu_item['prep_time'],
        'availability' => $menu_item['availability'],
        'status' => $menu_item['status'],
        'image_url' => $menu_item['image_url'],
        'created_at' => $menu_item['created_at'],
        'updated_at' => $menu_item['updated_at']
    ]
];

header('Content-Type: application/json');
echo json_encode($response);
?>