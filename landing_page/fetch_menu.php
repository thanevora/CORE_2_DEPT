<?php
// fetch_menu.php
include("../main_connection.php");

$db_name = "m3_menu";

// Validate connection
if (!isset($connections) || !isset($connections[$db_name])) {
    header('Content-Type: application/json');
    echo json_encode(["error" => "Connection not found for $db_name"]);
    exit();
}

$conn = $connections[$db_name];

// Check if connection is valid
if (!$conn || $conn->connect_error) {
    header('Content-Type: application/json');
    echo json_encode(["error" => "Database connection failed"]);
    exit();
}

// Fetch menu items
$sql = "SELECT menu_id, name, description, category, variant, price, image_url 
        FROM menu 
        WHERE status = 'available' 
        ORDER BY category, name";
$result = $conn->query($sql);

$menuItems = [];
if ($result && $result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $menuItems[$row['category']][] = $row;
    }
}

header('Content-Type: application/json');
echo json_encode($menuItems);
exit();
?>
