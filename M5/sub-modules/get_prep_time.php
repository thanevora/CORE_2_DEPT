<?php
include("../../main_connection.php");
$item_name = $_GET['item_name'];
$menu_conn = $connections["rest_m3_menu"];
$stmt = $menu_conn->prepare("SELECT prep_time FROM menu WHERE name = ?");
$stmt->bind_param("s", $item_name);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
echo json_encode(['prep_time' => $row['prep_time'] ?? 15]);
?>