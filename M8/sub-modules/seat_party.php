<?php
include("../../main_connection.php");

$db_name = "rest_m8_table_turnover";
$conn = $connections[$db_name] ?? null;

$response = ['success' => false];

if ($conn && isset($_GET['wait_id'])) {
    $wait_id = intval($_GET['wait_id']);
    $query = "UPDATE waitlist SET seated_at = NOW() WHERE wait_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $wait_id);
    
    if ($stmt->execute()) {
        $response['success'] = true;
    } else {
        $response['error'] = $conn->error;
    }
} else {
    $response['error'] = 'Invalid request';
}

header('Content-Type: application/json');
echo json_encode($response);
?>