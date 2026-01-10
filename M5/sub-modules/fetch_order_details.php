<?php
include("../../main_connection.php");

// Fetch from POS (order details)
$db_name_pos = "rest_m4_pos"; 
$conn_pos = $connections[$db_name_pos] ?? die(json_encode(["success" => false, "msg" => "POS connection error"]));

// Fetch from KOT (status tracking)
$db_name_kot = "rest_m6_kot";
$conn_kot = $connections[$db_name_kot] ?? die(json_encode(["success" => false, "msg" => "KOT connection error"]));

$kot_id = intval($_GET['kot_id'] ?? 0);

if ($kot_id <= 0) {
    echo json_encode(["success" => false, "msg" => "Invalid ID"]);
    exit;
}

// Get order from KOT
$query = $conn_kot->prepare("SELECT * FROM kot_orders WHERE kot_id = ?");
$query->bind_param("i", $kot_id);
$query->execute();
$kot_result = $query->get_result()->fetch_assoc();

// Also fetch POS order details (if needed)
$order_id = $kot_result['order_id'];
$pos_query = $conn_pos->prepare("SELECT * FROM orders WHERE order_id = ?");
$pos_query->bind_param("i", $order_id);
$pos_query->execute();
$pos_result = $pos_query->get_result()->fetch_assoc();

// Merge results
$order = array_merge($pos_result ?? [], $kot_result ?? []);

echo json_encode(["success" => true, "order" => $order]);
