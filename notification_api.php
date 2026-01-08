<?php
session_start();
header('Content-Type: application/json');
include("main_connection.php"); // contains $connections

$mapping = [
    'module_1' => ['db' => 'rest_m1_trs', 'table' => 'notification_m1'],
    'module_2' => ['db' => 'rest_m3_menu', 'table' => 'notification_m3'],
    'module_3' => ['db' => 'rest_m4_pos', 'table' => 'notification_m4'],
    'module_4' => ['db' => 'rest_m2_inventory', 'table' => 'notification_m2'],

];

// ================= POST actions =================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Add a new notification (example)
    if (isset($_POST['message'], $_POST['module'])) {
        $target = $_POST['module'];
        if (!isset($mapping[$target])) exit(json_encode(['status'=>'error','message'=>"Module not found"]));

        $conn = $connections[$mapping[$target]['db']] ?? null;
        if (!$conn) exit(json_encode(['status'=>'error','message'=>"No DB connection"]));

        $employee_name = $_SESSION['User_Name'] ?? 'System';
        $message = trim($_POST['message']);

        // Set Philippine time
        $dt = new DateTime('now', new DateTimeZone('Asia/Manila'));
        $date_sent = $dt->format('Y-m-d H:i:s');

        $stmt = $conn->prepare("INSERT INTO `{$mapping[$target]['table']}` (employee_name, message, date_sent, status) VALUES (?, ?, ?, 'Unread')");
        $stmt->bind_param("sss", $employee_name, $message, $date_sent);
        $success = $stmt->execute();
        $stmt->close();

        exit(json_encode(['status'=>$success?'success':'error','message'=>$success?'Notification sent':'Failed']));
    }

    // Mark single notification as read
    if (isset($_POST['notif_id'], $_POST['module'])) {
        $target = $_POST['module'];
        if (!isset($mapping[$target])) exit(json_encode(['status'=>'error','message'=>"Module not found"]));

        $conn = $connections[$mapping[$target]['db']] ?? null;
        if (!$conn) exit(json_encode(['status'=>'error','message'=>"No DB connection"]));

        $notif_id = intval($_POST['notif_id']);
        $stmt = $conn->prepare("UPDATE `{$mapping[$target]['table']}` SET status='Read' WHERE notification_id=?");
        $stmt->bind_param("i", $notif_id);
        $success = $stmt->execute();
        $stmt->close();

        exit(json_encode(['status'=>$success?'success':'error','message'=>$success?'Marked as read':'Failed']));
    }

    // Clear all notifications
    if (isset($_POST['clear_all'])) {
        foreach ($mapping as $mod => $info) {
            $conn = $connections[$info['db']] ?? null;
            if (!$conn) continue;

            $stmt = $conn->prepare("UPDATE `{$info['table']}` SET status='Read' WHERE status='Unread'");
            $stmt->execute();
            $stmt->close();
        }
        exit(json_encode(['status'=>'success','message'=>'All marked as read']));
    }

    exit;
}

// ================= GET: fetch all unread notifications =================
$allNotifications = [];
foreach ($mapping as $mod => $info) {
    $conn = $connections[$info['db']] ?? null;
    if (!$conn) continue;

    $stmt = $conn->prepare("
        SELECT notification_id, employee_name, message, date_sent, status
        FROM `{$info['table']}`
        WHERE status='Unread'
        ORDER BY date_sent 
        LIMIT 50
    ");
    $stmt->execute();
    $result = $stmt->get_result();
    $notifications = $result->fetch_all(MYSQLI_ASSOC);

  foreach ($notifications as &$notif) {
    $dt = new DateTime($notif['date_sent']); // use DB time as-is
    $notif['date_sent'] = $dt->format('Y-m-d H:i:s'); // optional: always format
    $notif['module'] = $mod;
}



    $allNotifications = array_merge($allNotifications, $notifications);
    $stmt->close();
}

// Sort by newest first
usort($allNotifications, fn($a,$b) => strtotime($b['date_sent']) - strtotime($a['date_sent']));

echo json_encode(['status'=>'success','notifications'=>$allNotifications]);
