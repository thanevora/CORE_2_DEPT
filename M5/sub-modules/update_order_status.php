<?php
include("../../main_connection.php");

$db_name = "rest_m6_kot";
$conn = $connections[$db_name] ?? die(json_encode(["success" => false, "msg" => "Connection error"]));

$data = json_decode(file_get_contents("php://input"), true);
$kot_id = intval($data['kot_id'] ?? 0);
$status = $data['status'] ?? '';

if ($kot_id <= 0 || $status === '') {
    echo json_encode(["success" => false, "msg" => "Invalid request"]);
    exit;
}

$query = $conn->prepare("UPDATE kot_orders SET status = ? WHERE kot_id = ?");
$query->bind_param("si", $status, $kot_id);

if ($query->execute()) {
    echo json_encode(["success" => true]);
} else {
    echo json_encode(["success" => false, "msg" => "DB error"]);
}
