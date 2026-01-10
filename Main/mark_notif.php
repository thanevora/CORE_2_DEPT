<?php
session_start();
include("../M1/conn_M1.php");

header('Content-Type: application/json');

if (!isset($_SESSION['User_ID'])) {
    echo json_encode(["error" => "Unauthorized"]);
    exit;
}

$userId = $_SESSION['User_ID'];

try {
    $stmt = $connection->prepare("
        UPDATE notification_m1
        SET status = 'Read'
        WHERE User_ID = ? AND status = 'Unread'
    ");

    if (!$stmt) {
        echo json_encode(["error" => "Prepare failed: " . $connection->error]);
        exit;
    }

    if (!$stmt->bind_param("s", $userId)) {
        echo json_encode(["error" => "Bind param failed: " . $stmt->error]);
        exit;
    }

    if (!$stmt->execute()) {
        echo json_encode(["error" => "Execute failed: " . $stmt->error]);
        exit;
    }

    echo json_encode(["success" => true]);
} catch (Exception $e) {
    echo json_encode(["error" => "Failed to update notifications: " . $e->getMessage()]);
}
?>
