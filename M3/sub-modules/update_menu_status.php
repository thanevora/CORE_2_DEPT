<?php
session_start();
include("../../main_connection.php");

// Database configuration
$db_name = "rest_m3_menu";

// Check database connection
if (!isset($connections[$db_name])) {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Database connection not found']);
    exit;
}

$conn = $connections[$db_name];

// ---------------------------------------------------------
// 🔍 Fetch Employee Info from department_accounts
// ---------------------------------------------------------

// Also get core_audit connection for department info
$conn_core_audit = null;
if (isset($connections['core_audit'])) {
    $conn_core_audit = $connections['core_audit'];
} elseif (isset($connections['rest_core_2_usm'])) {
    // Try alternative connection name
    $conn_core_audit = $connections['rest_core_2_usm'];
}

// Initialize employee variables
$employee_id = 0;
$employee_name = 'System';
$role = 'Admin';
$dept_id = 0;
$dept_name = 'Unknown';

// Try to get employee info from session first
if (isset($_SESSION['employee_id'])) {
    $employee_id = (int)$_SESSION['employee_id'];
} elseif (isset($_SESSION['User_ID'])) {
    $employee_id = (int)$_SESSION['User_ID'];
}

if (isset($_SESSION['employee_name'])) {
    $employee_name = trim($_SESSION['employee_name']);
} elseif (isset($_SESSION['Name'])) {
    $employee_name = trim($_SESSION['Name']);
}

if (isset($_SESSION['Role'])) {
    $role = trim($_SESSION['Role']);
} elseif (isset($_SESSION['role'])) {
    $role = trim($_SESSION['role']);
}

if (isset($_SESSION['Dept_id'])) {
    $dept_id = (int)$_SESSION['Dept_id'];
}

if (isset($_SESSION['dept_name'])) {
    $dept_name = trim($_SESSION['dept_name']);
}

// If we have an audit connection, fetch employee details from department_accounts
if ($conn_core_audit) {
    // Try to fetch by employee_id if we have it
    if ($employee_id > 0) {
        $deptFetch = $conn_core_audit->prepare("
            SELECT employee_id, employee_name, role, Dept_id, dept_name 
            FROM department_accounts 
            WHERE employee_id = ? 
            LIMIT 1
        ");
        if ($deptFetch) {
            $deptFetch->bind_param("i", $employee_id);
            if ($deptFetch->execute()) {
                $dRes = $deptFetch->get_result();
                if ($dRes && $dRes->num_rows > 0) {
                    $dRow = $dRes->fetch_assoc();
                    $employee_id   = (int)$dRow['employee_id'];
                    $employee_name = trim($dRow['employee_name']);
                    $role          = trim($dRow['role']);
                    $dept_id       = (int)$dRow['Dept_id'];
                    $dept_name     = trim($dRow['dept_name']);
                }
            }
            $deptFetch->close();
        }
    }
    
    // If still no employee name, try by email
    if (empty($employee_name) || $employee_name === 'System') {
        if (!empty($_SESSION['email'])) {
            $deptFetch = $conn_core_audit->prepare("
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
                        $employee_name = trim($dRow['employee_name']);
                        $role          = trim($dRow['role']);
                        $dept_id       = (int)$dRow['Dept_id'];
                        $dept_name     = trim($dRow['dept_name']);
                    }
                }
                $deptFetch->close();
            }
        }
    }
    
    // If still no employee name, try by username
    if ((empty($employee_name) || $employee_name === 'System') && !empty($_SESSION['username'])) {
        $deptFetch = $conn_core_audit->prepare("
            SELECT employee_id, employee_name, role, Dept_id, dept_name 
            FROM department_accounts 
            WHERE username = ? 
            LIMIT 1
        ");
        if ($deptFetch) {
            $deptFetch->bind_param("s", $_SESSION['username']);
            if ($deptFetch->execute()) {
                $dRes = $deptFetch->get_result();
                if ($dRes && $dRes->num_rows > 0) {
                    $dRow = $dRes->fetch_assoc();
                    $employee_id   = (int)$dRow['employee_id'];
                    $employee_name = trim($dRow['employee_name']);
                    $role          = trim($dRow['role']);
                    $dept_id       = (int)$dRow['Dept_id'];
                    $dept_name     = trim($dRow['dept_name']);
                }
            }
            $deptFetch->close();
        }
    }
}

// Final sanitization
$employee_id   = (int) ($employee_id ?: 0);
$employee_name = trim($employee_name ?: 'System');
$role          = trim($role ?: 'Admin');
$dept_id       = (int) ($dept_id ?: 0);
$dept_name     = trim($dept_name ?: 'Unknown');

// Get POST data
$menu_id = isset($_POST['menu_id']) ? (int)$_POST['menu_id'] : 0;
$status = isset($_POST['status']) ? $conn->real_escape_string($_POST['status']) : '';
$reason = isset($_POST['reason']) ? $conn->real_escape_string($_POST['reason']) : '';

// Validate input
if ($menu_id <= 0) {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Invalid menu item ID']);
    exit;
}

if (empty($status)) {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Status is required']);
    exit;
}

// Valid status values
$valid_statuses = ['Pending for approval', 'For compliance review', 'Approved', 'Rejected', 'For posting'];
if (!in_array($status, $valid_statuses)) {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Invalid status value']);
    exit;
}

// Start transaction
$conn->begin_transaction();
if ($conn_core_audit) {
    $conn_core_audit->begin_transaction();
}

try {
    // First, get the menu item details before update
    $menu_sql = "SELECT name, category FROM menu WHERE menu_id = ?";
    $menu_stmt = $conn->prepare($menu_sql);
    $menu_stmt->bind_param("i", $menu_id);
    $menu_stmt->execute();
    $menu_result = $menu_stmt->get_result();
    
    if ($menu_result->num_rows === 0) {
        throw new Exception("Menu item not found");
    }
    
    $menu_item = $menu_result->fetch_assoc();
    $menu_name = $menu_item['name'];
    $menu_category = $menu_item['category'];
    
    $menu_stmt->close();

    // Update menu status
    $update_sql = "UPDATE menu SET status = ? WHERE menu_id = ?";
    $update_stmt = $conn->prepare($update_sql);
    $update_stmt->bind_param("si", $status, $menu_id);
    
    if (!$update_stmt->execute()) {
        throw new Exception("Failed to update menu status");
    }
    
    $update_stmt->close();

    // ---------------------------------------------------------
    // 🔔 Notification (CN time)
    // ---------------------------------------------------------
    $notification_title   = "Menu Item Status Updated";
    $notification_message = "Menu item '{$menu_name}' status changed to '{$status}'" . 
                          ($reason ? " - Reason: {$reason}" : "");
    $notification_status  = "Unread";
    $module               = "Menu Management";

    $dateCN = new DateTime('now', new DateTimeZone('Asia/Shanghai'));
    $date_sent = $dateCN->format('Y-m-d H:i:s');

    // Check if notification_m3 table exists, if not create it
    $checkNotifTable = $conn->query("SHOW TABLES LIKE 'notification_m3'");
    if ($checkNotifTable && $checkNotifTable->num_rows == 0) {
        $createNotifTable = $conn->query("
            CREATE TABLE IF NOT EXISTS notification_m3 (
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
            // Don't throw error for notification table creation failure, just log it
            error_log("Failed to create notification_m3 table: " . $conn->error);
        }
    }
    
    if ($checkNotifTable) {
        $checkNotifTable->free();
    }

    // Insert notification
    $notifQuery = $conn->prepare("
        INSERT INTO notification_m3 
        (employee_id, employee_name, role, title, message, status, date_sent, module)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    if ($notifQuery) {
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
            // Don't throw error for notification failure, just log it
            error_log("Notification insert failed: " . $notifQuery->error);
        }
        $notifQuery->close();
    }

    // ---------------------------------------------------------
    // 🧾 Audit Log (PH time) - Only if we have audit connection
    // ---------------------------------------------------------
    if ($conn_core_audit) {
        $modules_cover = "Menu Management";
        $action        = "Menu Status Updated";
        $activity      = "Updated menu item '{$menu_name}' status to '{$status}' under {$menu_category}" . 
                        ($reason ? " (Reason: {$reason})" : "");
        $auditDatePH   = new DateTime('now', new DateTimeZone('Asia/Manila'));
        $audit_date    = $auditDatePH->format('Y-m-d H:i:s');

        // Fetch department info again if not already fetched
        if ($dept_id == 0 || $dept_name === 'Unknown') {
            if ($employee_id > 0) {
                $dq = $conn_core_audit->prepare("SELECT Dept_id, dept_name FROM department_accounts WHERE employee_id = ? LIMIT 1");
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
        }

        $auditStmt = $conn_core_audit->prepare("
            INSERT INTO dept_audit_transc 
            (dept_id, dept_name, modules_cover, action, activity, employee_name, employee_id, role, date)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        if ($auditStmt) {
            $auditStmt->bind_param(
                "isssssiss",
                $dept_id,
                $dept_name,
                $modules_cover,
                $action,
                $activity,
                $employee_name,
                $employee_id,
                $role,
                $audit_date
            );

            if (!$auditStmt->execute()) {
                // Don't throw error for audit failure, just log it
                error_log("Audit insert failed: " . $auditStmt->error);
            }
            $auditStmt->close();
        }
    }

    // ---------------------------------------------------------
    // ✅ Commit Transactions
    // ---------------------------------------------------------
    $conn->commit();
    if ($conn_core_audit) {
        $conn_core_audit->commit();
    }

    // Return success response
    header('Content-Type: application/json');
    echo json_encode([
        'status' => 'success', 
        'message' => "Menu item '{$menu_name}' status updated to '{$status}' successfully!",
        'data' => [
            'menu_id' => $menu_id,
            'menu_name' => $menu_name,
            'new_status' => $status,
            'updated_by' => $employee_name,
            'employee_id' => $employee_id,
            'role' => $role,
            'timestamp' => date('Y-m-d H:i:s')
        ]
    ]);
    
} catch (Exception $e) {
    // Rollback on error
    $conn->rollback();
    if ($conn_core_audit) {
        $conn_core_audit->rollback();
    }
    
    header('Content-Type: application/json');
    echo json_encode([
        'status' => 'error', 
        'message' => $e->getMessage()
    ]);
}

// Close connections
if (isset($conn)) {
    $conn->close();
}
?>