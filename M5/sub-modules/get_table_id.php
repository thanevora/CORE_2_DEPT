<?php
include("../../main_connection.php");
$order_id = $_GET['order_id'];
$pos_conn = $connections["rest_m1_pos"]; // Adjust database name
$stmt = $pos_conn->prepare("SELECT table_id FROM orders WHERE order_id = ?");
$stmt->bind_param("s", $order_id);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
echo json_encode(['table_id' => $row['table_id'] ?? null]);
?>