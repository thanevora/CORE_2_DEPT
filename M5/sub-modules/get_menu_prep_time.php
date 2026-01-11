<?php
include("../../main_connection.php");

$db_name = "rest_m3_menu";
$conn = $connections[$db_name] ?? die(json_encode(["success" => false, "msg" => "Menu connection error"]));

$item_name = $_GET['item_name'] ?? '';

if (empty($item_name)) {
    echo json_encode(["success" => false, "msg" => "Item name required"]);
    exit;
}

$query = $conn->prepare("SELECT prep_time FROM menu_items WHERE item_name = ? LIMIT 1");
$query->bind_param("s", $item_name);
$query->execute();
$result = $query->get_result();

if ($row = $result->fetch_assoc()) {
    echo json_encode(["success" => true, "prep_time" => $row['prep_time'] ?? 15]);
} else {
    echo json_encode(["success" => true, "prep_time" => 15]); // Default
}
?>