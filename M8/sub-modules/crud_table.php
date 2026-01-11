<?php
session_start();
header('Content-Type: application/json');
require_once("../../main_connection.php");

// POS DB (has `tables` + `notification_m1`)
$db_name_pos = "rest_m1_trs"; 
if (!isset($connections[$db_name_pos])) {
    die(json_encode(['status' => 'error', 'message' => "POS DB connection not found"]));
}
$conn_pos = $connections[$db_name_pos];

// Table turnover DB
$db_name_turnover = "rest_m8_table_turnover";
if (!isset($connections[$db_name_turnover])) {
    die(json_encode(['status' => 'error', 'message' => "Table turnover DB connection not found"]));
}
$conn_turnover = $connections[$db_name_turnover];

// Audit DB
$db_name_audit = "rest_core_2_usm";
if (!isset($connections[$db_name_audit])) {
    die(json_encode(['status' => 'error', 'message' => "Audit DB connection not found"]));
}
$conn_audit = $connections[$db_name_audit];

// Only accept POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method Not Allowed']);
    exit;
}

$action = $_POST['action'] ?? '';
$table_id = $_POST['table_id'] ?? 0;

if (!$table_id) {
    echo json_encode(['status' => 'error', 'message' => 'Table ID is required']);
    exit;
}

// Get table details first for logging
$stmt = $conn_pos->prepare("SELECT name, category, capacity, image_url, status FROM tables WHERE table_id = ?");
$stmt->bind_param("i", $table_id);
$stmt->execute();
$stmt->bind_result($table_name, $category, $capacity, $image_url, $current_status);
$stmt->fetch();
$stmt->close();

if (!$table_name) {
    echo json_encode(['status' => 'error', 'message' => 'Table not found']);
    exit;
}

$response = ['status' => 'error', 'message' => 'Invalid action'];

try {
    // Begin transactions in ALL DBs
    $conn_pos->begin_transaction();
    $conn_turnover->begin_transaction();
    $conn_audit->begin_transaction();
    
    $new_status = '';
    $action_message = '';
    
    switch ($action) {
        case 'display':
            $new_status = 'Available';
            $action_message = 'Displayed as Available';
            $stmt = $conn_pos->prepare("UPDATE tables SET status = ? WHERE table_id = ?");
            $stmt->bind_param("si", $new_status, $table_id);
            if ($stmt->execute()) {
                $response = ['status' => 'success', 'message' => 'Table is now displayed as Available'];
            } else {
                throw new Exception("Failed to update table status");
            }
            $stmt->close();
            break;

        case 'hide':
            $new_status = 'Hidden';
            $action_message = 'Hidden';
            $stmt = $conn_pos->prepare("UPDATE tables SET status = ? WHERE table_id = ?");
            $stmt->bind_param("si", $new_status, $table_id);
            if ($stmt->execute()) {
                $response = ['status' => 'success', 'message' => 'Table is now hidden'];
            } else {
                throw new Exception("Failed to hide table");
            }
            $stmt->close();
            break;

        case 'maintenance':
            $new_status = 'Maintenance';
            $action_message = 'Set to Maintenance';
            $stmt = $conn_pos->prepare("UPDATE tables SET status = ? WHERE table_id = ?");
            $stmt->bind_param("si", $new_status, $table_id);
            if ($stmt->execute()) {
                $response = ['status' => 'success', 'message' => 'Table set to Maintenance'];
            } else {
                throw new Exception("Failed to set maintenance status");
            }
            $stmt->close();
            break;

        case 'delete':
            $action_message = 'Deleted';
            // Delete the table
            $stmt = $conn_pos->prepare("DELETE FROM tables WHERE table_id = ?");
            $stmt->bind_param("i", $table_id);
            if ($stmt->execute()) {
                // Delete associated image file if exists
                if ($image_url && file_exists('../../M1/Table_images/' . $image_url)) {
                    unlink('../../M1/Table_images/' . $image_url);
                }
                $response = ['status' => 'success', 'message' => 'Table deleted successfully'];
            } else {
                throw new Exception("Failed to delete table");
            }
            $stmt->close();
            break;

        default:
            throw new Exception("Invalid action specified");
    }
    
    // ---------------------------------------------------------
    // 🔍 Fetch Employee Info
    // ---------------------------------------------------------
    $employee_id   = $_SESSION['employee_id'] ?? ($_SESSION['User_ID'] ?? 0);
    $employee_name = $_SESSION['employee_name'] ?? ($_SESSION['Name'] ?? '');
    $role          = $_SESSION['Role'] ?? ($_SESSION['role'] ?? '');
    $dept_id   = $_SESSION['Dept_id'] ?? 0;
    $dept_name = $_SESSION['dept_name'] ?? 'Unknown';

    if (empty($employee_id) || empty($employee_name)) {
        if (!empty($_SESSION['email'])) {
            $deptFetch = $conn_audit->prepare("
                SELECT employee_id, employee_name, role, Dept_id, dept_name 
                FROM department_accounts 
                WHERE email = ? 
                LIMIT 1
            ");
            if ($deptFetch) {
                $deptFetch->bind_param("s", $_SESSION['email']);
                if ($deptFetch->execute()) {
                    $dRes = $deptFetch->get_result();
                    if ($dRes && $dRes->num_rows > 0) {
                        $dRow = $dRes->fetch_assoc();
                        $employee_id   = (int)$dRow['employee_id'];
                        $employee_name = $dRow['employee_name'];
                        $role          = $dRow['role'];
                        $dept_id       = (int)$dRow['Dept_id'];
                        $dept_name     = $dRow['dept_name'];
                    }
                }
                $deptFetch->close();
            }
        } elseif (!empty($employee_name)) {
            $deptFetch = $conn_audit->prepare("
                SELECT employee_id, employee_name, role, Dept_id, dept_name 
                FROM department_accounts 
                WHERE employee_name = ? 
                LIMIT 1
            ");
            if ($deptFetch) {
                $deptFetch->bind_param("s", $employee_name);
                if ($deptFetch->execute()) {
                    $dRes = $deptFetch->get_result();
                    if ($dRes && $dRes->num_rows > 0) {
                        $dRow = $dRes->fetch_assoc();
                        $employee_id   = (int)$dRow['employee_id'];
                        $employee_name = $dRow['employee_name'];
                        $role          = $dRow['role'];
                        $dept_id       = (int)$dRow['Dept_id'];
                        $dept_name     = $dRow['dept_name'];
                    }
                }
                $deptFetch->close();
            }
        }
    }

    $employee_id   = (int) ($employee_id ?: 0);
    $employee_name = trim($employee_name ?: 'System');
    $role          = trim($role ?: 'Admin');
    $dept_id       = (int) ($dept_id ?: 0);
    $dept_name     = trim($dept_name ?: 'Unknown');

    // If still missing department info, try to fetch by employee_id
    if (($dept_id == 0 || $dept_name === 'Unknown') && $employee_id > 0) {
        $dq = $conn_audit->prepare("SELECT Dept_id, dept_name FROM department_accounts WHERE employee_id = ? LIMIT 1");
        if ($dq) {
            $dq->bind_param("i", $employee_id);
            if ($dq->execute()) {
                $dr = $dq->get_result();
                if ($dr && $dr->num_rows > 0) {
                    $drRow = $dr->fetch_assoc();
                    $dept_id   = $drRow['Dept_id'] ?? $dept_id;
                    $dept_name = $drRow['dept_name'] ?? $dept_name;
                }
            }
            $dq->close();
        }
    }
    
    // ---------------------------------------------------------
    // 🔔 Notification (CN time)
    // ---------------------------------------------------------
    $notification_title   = "Table " . ucfirst($action);
    $notification_message = "Table <strong>$table_name</strong> was $action_message. ";
    if ($action !== 'delete') {
        $notification_message .= "Status changed from $current_status to $new_status.";
    } else {
        $notification_message .= "Table was permanently removed from the system.";
    }
    $notification_status  = "Unread";
    $module               = "Table Management";

    $dateCN = new DateTime('now', new DateTimeZone('Asia/Shanghai'));
    $date_sent = $dateCN->format('Y-m-d H:i:s');

    // Check if notification_m1 table exists, if not create it
    $checkNotifTable = $conn_pos->query("SHOW TABLES LIKE 'notification_m1'");
    if ($checkNotifTable && $checkNotifTable->num_rows == 0) {
        $createNotifTable = $conn_pos->query("
            CREATE TABLE IF NOT EXISTS notification_m1 (
                notification_id INT AUTO_INCREMENT PRIMARY KEY,
                employee_id INT NOT NULL,
                employee_name VARCHAR(255) NOT NULL,
                role VARCHAR(100) NOT NULL,
                title VARCHAR(255) NOT NULL,
                message TEXT NOT NULL,
                status VARCHAR(50) DEFAULT 'Unread',
                date_sent DATETIME NOT NULL,
                module VARCHAR(100) NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        ");
        if (!$createNotifTable) {
            error_log("Failed to create notification_m1 table: " . $conn_pos->error);
        }
    }
    
    if ($checkNotifTable) {
        $checkNotifTable->free();
    }

    // Insert notification
    $notifQuery = $conn_pos->prepare("
        INSERT INTO notification_m1 
        (employee_id, employee_name, role, title, message, status, date_sent, module)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    if (!$notifQuery) {
        throw new Exception("Prepare failed for notification_m1");
    }
    
    $notifQuery->bind_param(
        "isssssss",
        $employee_id,
        $employee_name,
        $role,
        $notification_title,
        $notification_message,
        $notification_status,
        $date_sent,
        $module
    );

    if (!$notifQuery->execute()) {
        error_log("Notification insert failed: " . $notifQuery->error);
    }
    $notifQuery->close();

    // ---------------------------------------------------------
    // 🧾 Audit Log (PH time)
    // ---------------------------------------------------------
    $modules_cover = "Table Management";
    $action_audit = "Table " . ucfirst($action);
    $activity = "Table '$table_name' was $action_message";
    if ($action !== 'delete') {
        $activity .= " (Status: $current_status → $new_status)";
    } else {
        $activity .= " (Category: $category, Capacity: $capacity)";
    }
    
    $auditDatePH   = new DateTime('now', new DateTimeZone('Asia/Manila'));
    $audit_date    = $auditDatePH->format('Y-m-d H:i:s');

    $auditStmt = $conn_audit->prepare("
        INSERT INTO dept_audit_transc 
        (dept_id, dept_name, modules_cover, action, activity, employee_name, employee_id, role, date)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    if (!$auditStmt) {
        throw new Exception("Prepare failed for dept_audit_transc");
    }
    
    $auditStmt->bind_param(
        "isssssiss",
        $dept_id,
        $dept_name,
        $modules_cover,
        $action_audit,
        $activity,
        $employee_name,
        $employee_id,
        $role,
        $audit_date
    );

    if (!$auditStmt->execute()) {
        error_log("Audit insert failed: " . $auditStmt->error);
    }
    $auditStmt->close();

    // ---------------------------------------------------------
    // ✅ Commit Transactions
    // ---------------------------------------------------------
    $conn_pos->commit();
    $conn_turnover->commit();
    $conn_audit->commit();

} catch (Exception $e) {
    $conn_pos->rollback();
    $conn_turnover->rollback();
    $conn_audit->rollback();
    $response = ['status' => 'error', 'message' => $e->getMessage()];
}

echo json_encode($response);

// Close connections
$conn_pos->close();
$conn_turnover->close();
$conn_audit->close();
?>