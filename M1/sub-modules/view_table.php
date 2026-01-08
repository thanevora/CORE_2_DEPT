<?php
session_start();
header('Content-Type: application/json');
require_once("../../main_connection.php");

$db_name = "rest_m1_trs";
if (!isset($connections[$db_name])) {
    die(json_encode(['status' => 'error', 'message' => "Connection not found for $db_name"]));
}

$conn = $connections[$db_name];

if (!isset($_GET['id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Table ID is required']);
    exit;
}

$table_id = intval($_GET['id']);

$stmt = $conn->prepare("SELECT * FROM tables WHERE table_id = ?");
$stmt->bind_param("i", $table_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['status' => 'error', 'message' => 'Table not found']);
    exit;
}

$table = $result->fetch_assoc();

echo json_encode([
    'status' => 'success',
    'table' => $table
]);

$stmt->close();
$conn->close();
?>