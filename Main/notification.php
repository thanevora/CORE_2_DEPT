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
        SELECT notification_id, message, date_sent AS timestamp, sent_by
        FROM notification_m1
        WHERE User_ID = ? AND status = 'Unread'
        ORDER BY date_sent DESC
        LIMIT 10
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

    $result = $stmt->get_result();

    $notifications = [];
    while ($row = $result->fetch_assoc()) {
        $notifications[] = [
            'id' => $row['notification_id'],
            'message' => $row['message'],
            'date' => date("M j, Y ", strtotime($row['timestamp'])),
            'sender_name' => $row['sent_by'] ?? 'System'
        ];
    }

    echo json_encode($notifications);
} catch (Exception $e) {
    echo json_encode(["error" => "Failed to fetch notifications: " . $e->getMessage()]);
}
?>
