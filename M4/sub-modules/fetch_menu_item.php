<?php
include("../../main_connection.php");

// ✅ POS connection
$conn_pos = $connections["rest_m4_pos"] ?? die(json_encode(["error" => "POS connection not found"]));

header('Content-Type: application/json');

// Get menu_id from GET
$menu_id = isset($_GET['menu_id']) ? (int)$_GET['menu_id'] : 0;

if ($menu_id <= 0) {
    echo json_encode(["error" => "Menu ID not provided"]);
    exit;
}

// Fetch menu item details
$sql = "SELECT menu_id, name, price, category, status, description, image_url, updated_at 
        FROM menu_items 
        WHERE menu_id = ?";
$stmt = $conn_pos->prepare($sql);

if (!$stmt) {
    echo json_encode(["error" => "SQL prepare failed: " . $conn_pos->error]);
    exit;
}

$stmt->bind_param("i", $menu_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(["error" => "Menu item not found"]);
    exit;
}

$item = $result->fetch_assoc();

// Format for frontend
$item['status_text'] = strtolower($item['status']) === 'available' ? 'Available' : 'Unavailable';
$item['updated_at']  = date("F d, Y", strtotime($item['updated_at']));
$item['image_url']   = $item['image_url'] ?: '../images/default.png';

// Return JSON
echo json_encode($item);

$stmt->close();
$conn_pos->close();
?>
