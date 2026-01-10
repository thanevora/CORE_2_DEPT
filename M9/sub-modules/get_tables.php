<?php
include("../../main_connection.php");

$db_name = "rest_m1_trs";
if (!isset($connections[$db_name])) {
    die(json_encode(['error' => 'Database connection not found']));
}

$conn = $connections[$db_name];
header('Content-Type: application/json');

try {
    $query = "SELECT table_id, name, category, capacity, status FROM tables";
    $result = $conn->query($query);
    
    $tables = [];
    while ($row = $result->fetch_assoc()) {
        $tables[] = $row;
    }
    
    echo json_encode($tables);
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>