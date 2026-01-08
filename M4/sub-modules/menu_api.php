<?php
// menu_api.php
session_start();

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 0);

// Function to send JSON response
function sendResponse($success, $message = '', $data = []) {
    // Clear any previous output
    while (ob_get_level()) {
        ob_end_clean();
    }
    
    header('Content-Type: application/json');
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data
    ]);
    exit;
}

try {
    // Include database connection
    $connection_path = __DIR__ . '/../../main_connection.php';
    if (!file_exists($connection_path)) {
        throw new Exception('Database configuration not found');
    }
    
    include($connection_path);

    // Database connections
    $db_name_menu = "rest_m3_menu";
    if (!isset($connections[$db_name_menu])) {
        throw new Exception('Menu database connection not available');
    }
    
    $conn = $connections[$db_name_menu];
    
    // Additional database connections
    $conn_core_audit = null;
    $conn_m4_pos = null;
    
    // Get core_audit connection for department_accounts
    if (isset($connections['core_audit'])) {
        $conn_core_audit = $connections['core_audit'];
    } elseif (isset($connections['rest_core_2_usm'])) {
        $conn_core_audit = $connections['rest_core_2_usm'];
    }
    
    // Get M4 POS connection for notification_m4
    if (isset($connections['rest_m4_pos'])) {
        $conn_m4_pos = $connections['rest_m4_pos'];
    }

    // Check main connection
    if ($conn->connect_error) {
        throw new Exception('Database connection failed: ' . $conn->connect_error);
    }

    // Get POST data
    $action = $_POST['action'] ?? '';
    $menu_id = $_POST['menu_id'] ?? '';

    // Validate input
    if (empty($action)) {
        throw new Exception('No action specified');
    }

    if (empty($menu_id)) {
        throw new Exception('Menu ID is required');
    }

    // Convert menu_id to integer
    $menu_id = intval($menu_id);

    switch ($action) {
        case 'activate_menu':
            activateMenuItem($conn, $menu_id, $conn_core_audit, $conn_m4_pos);
            break;
            
        case 'deactivate_menu':
            deactivateMenuItem($conn, $menu_id, $conn_core_audit, $conn_m4_pos);
            break;
            
        default:
            throw new Exception('Invalid action: ' . $action);
    }

} catch (Exception $e) {
    sendResponse(false, $e->getMessage());
}

function getEmployeeInfo($conn_core_audit) {
    $employee_id   = $_SESSION['employee_id'] ?? ($_SESSION['User_ID'] ?? 0);
    $employee_name = $_SESSION['employee_name'] ?? ($_SESSION['Name'] ?? '');
    $role          = $_SESSION['Role'] ?? ($_SESSION['role'] ?? '');
    $dept_id   = $_SESSION['Dept_id'] ?? 0;
    $dept_name = $_SESSION['dept_name'] ?? 'Unknown';

    if ($conn_core_audit) {
        if (empty($employee_id) || empty($employee_name)) {
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
                            $employee_name = $dRow['employee_name'];
                            $role          = $dRow['role'];
                            $dept_id       = (int)$dRow['Dept_id'];
                            $dept_name     = $dRow['dept_name'];
                        }
                    }
                    $deptFetch->close();
                }
            } elseif (!empty($employee_name)) {
                $deptFetch = $conn_core_audit->prepare("
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
    }

    $employee_id   = (int) ($employee_id ?: 0);
    $employee_name = trim($employee_name ?: 'System');
    $role          = trim($role ?: 'Admin');
    $dept_id       = (int) ($dept_id ?: 0);
    $dept_name     = trim($dept_name ?: 'Unknown');

    return [
        'employee_id' => $employee_id,
        'employee_name' => $employee_name,
        'role' => $role,
        'dept_id' => $dept_id,
        'dept_name' => $dept_name
    ];
}

function createNotificationTable($conn, $table_name) {
    $checkTable = $conn->query("SHOW TABLES LIKE '$table_name'");
    if ($checkTable && $checkTable->num_rows == 0) {
        $createTable = $conn->query("
            CREATE TABLE IF NOT EXISTS $table_name (
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
        if (!$createTable) {
            error_log("Failed to create $table_name table: " . $conn->error);
            return false;
        }
    }
    
    if ($checkTable) {
        $checkTable->free();
    }
    
    return true;
}

function insertNotification($conn, $table_name, $employee_info, $title, $message, $module) {
    $notification_status = "Unread";
    $dateCN = new DateTime('now', new DateTimeZone('Asia/Shanghai'));
    $date_sent = $dateCN->format('Y-m-d H:i:s');
    
    // Create table if it doesn't exist
    createNotificationTable($conn, $table_name);
    
    $notifQuery = $conn->prepare("
        INSERT INTO $table_name 
        (employee_id, employee_name, role, title, message, status, date_sent, module)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    if ($notifQuery) {
        $notifQuery->bind_param(
            "isssssss",
            $employee_info['employee_id'],
            $employee_info['employee_name'],
            $employee_info['role'],
            $title,
            $message,
            $notification_status,
            $date_sent,
            $module
        );

        if (!$notifQuery->execute()) {
            error_log("Notification insert failed in $table_name: " . $notifQuery->error);
            return false;
        }
        $notifQuery->close();
        return true;
    }
    
    return false;
}

function insertAuditLog($conn_core_audit, $employee_info, $modules_cover, $action, $activity) {
    if (!$conn_core_audit) {
        return false;
    }
    
    $auditDatePH = new DateTime('now', new DateTimeZone('Asia/Manila'));
    $audit_date = $auditDatePH->format('Y-m-d H:i:s');
    
    // If department info is missing, try to fetch it
    if (($employee_info['dept_id'] == 0 || $employee_info['dept_name'] === 'Unknown') && $employee_info['employee_id'] > 0) {
        $dq = $conn_core_audit->prepare("SELECT Dept_id, dept_name FROM department_accounts WHERE employee_id = ? LIMIT 1");
        if ($dq) {
            $dq->bind_param("i", $employee_info['employee_id']);
            if ($dq->execute()) {
                $dr = $dq->get_result();
                if ($dr && $dr->num_rows > 0) {
                    $drRow = $dr->fetch_assoc();
                    $employee_info['dept_id'] = $drRow['Dept_id'] ?? $employee_info['dept_id'];
                    $employee_info['dept_name'] = $drRow['dept_name'] ?? $employee_info['dept_name'];
                }
            }
            $dq->close();
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
            $employee_info['dept_id'],
            $employee_info['dept_name'],
            $modules_cover,
            $action,
            $activity,
            $employee_info['employee_name'],
            $employee_info['employee_id'],
            $employee_info['role'],
            $audit_date
        );

        if (!$auditStmt->execute()) {
            error_log("Audit insert failed: " . $auditStmt->error);
            return false;
        }
        $auditStmt->close();
        return true;
    }
    
    return false;
}

function activateMenuItem($conn, $menu_id, $conn_core_audit = null, $conn_m4_pos = null) {
    // Begin transaction
    $conn->begin_transaction();
    if ($conn_core_audit) {
        $conn_core_audit->begin_transaction();
    }
    if ($conn_m4_pos) {
        $conn_m4_pos->begin_transaction();
    }
    
    try {
        // First check if menu exists
        $check_sql = "SELECT menu_id, name, status FROM menu WHERE menu_id = ?";
        $check_stmt = $conn->prepare($check_sql);
        
        if (!$check_stmt) {
            throw new Exception('Database error: ' . $conn->error);
        }
        
        $check_stmt->bind_param("i", $menu_id);
        
        if (!$check_stmt->execute()) {
            throw new Exception('Failed to check menu: ' . $check_stmt->error);
        }
        
        $result = $check_stmt->get_result();
        $menu = $result->fetch_assoc();
        $check_stmt->close();
        
        if (!$menu) {
            throw new Exception('Menu item not found with ID: ' . $menu_id);
        }
        
        // Update status to active
        $update_sql = "UPDATE menu SET status = 'active' WHERE menu_id = ?";
        $update_stmt = $conn->prepare($update_sql);
        
        if (!$update_stmt) {
            throw new Exception('Database error: ' . $conn->error);
        }
        
        $update_stmt->bind_param("i", $menu_id);
        
        if (!$update_stmt->execute()) {
            throw new Exception('Failed to activate menu: ' . $update_stmt->error);
        }
        
        $update_stmt->close();
        
        // Get employee info
        $employee_info = getEmployeeInfo($conn_core_audit);
        
        // ---------------------------------------------------------
        // 🔔 Notification (CN time) - Insert to both databases
        // ---------------------------------------------------------
        $notification_title = "Menu Item Activated";
        $notification_message = "Menu item '{$menu['name']}' has been activated and is now available for orders.";
        $module = "Menu Management";
        
        // Insert to notification_m3 (rest_m3_menu)
        insertNotification($conn, 'notification_m3', $employee_info, $notification_title, $notification_message, $module);
        
        // Insert to notification_m4 (rest_m4_pos)
        if ($conn_m4_pos) {
            insertNotification($conn_m4_pos, 'notification_m4', $employee_info, $notification_title, $notification_message, $module);
        }
        
        // ---------------------------------------------------------
        // 🧾 Audit Log (PH time)
        // ---------------------------------------------------------
        $modules_cover = "Menu Management";
        $action = "Menu Item Activated";
        $activity = "Activated menu item '{$menu['name']}' (ID: {$menu_id})";
        
        insertAuditLog($conn_core_audit, $employee_info, $modules_cover, $action, $activity);
        
        // ---------------------------------------------------------
        // ✅ Commit Transactions
        // ---------------------------------------------------------
        $conn->commit();
        if ($conn_core_audit) {
            $conn_core_audit->commit();
        }
        if ($conn_m4_pos) {
            $conn_m4_pos->commit();
        }
        
        sendResponse(true, 'Menu "' . $menu['name'] . '" activated successfully', [
            'menu_id' => $menu_id,
            'menu_name' => $menu['name'],
            'new_status' => 'active',
            'updated_by' => $employee_info['employee_name'],
            'employee_id' => $employee_info['employee_id'],
            'timestamp' => date('Y-m-d H:i:s')
        ]);
        
    } catch (Exception $e) {
        // Rollback everything on error
        $conn->rollback();
        if ($conn_core_audit) {
            $conn_core_audit->rollback();
        }
        if ($conn_m4_pos) {
            $conn_m4_pos->rollback();
        }
        throw $e;
    }
}

function deactivateMenuItem($conn, $menu_id, $conn_core_audit = null, $conn_m4_pos = null) {
    // Begin transaction
    $conn->begin_transaction();
    if ($conn_core_audit) {
        $conn_core_audit->begin_transaction();
    }
    if ($conn_m4_pos) {
        $conn_m4_pos->begin_transaction();
    }
    
    try {
        // First check if menu exists
        $check_sql = "SELECT menu_id, name, status FROM menu WHERE menu_id = ?";
        $check_stmt = $conn->prepare($check_sql);
        
        if (!$check_stmt) {
            throw new Exception('Database error: ' . $conn->error);
        }
        
        $check_stmt->bind_param("i", $menu_id);
        
        if (!$check_stmt->execute()) {
            throw new Exception('Failed to check menu: ' . $check_stmt->error);
        }
        
        $result = $check_stmt->get_result();
        $menu = $result->fetch_assoc();
        $check_stmt->close();
        
        if (!$menu) {
            throw new Exception('Menu item not found with ID: ' . $menu_id);
        }
        
        // Update status to inactive
        $update_sql = "UPDATE menu SET status = 'inactive' WHERE menu_id = ?";
        $update_stmt = $conn->prepare($update_sql);
        
        if (!$update_stmt) {
            throw new Exception('Database error: ' . $conn->error);
        }
        
        $update_stmt->bind_param("i", $menu_id);
        
        if (!$update_stmt->execute()) {
            throw new Exception('Failed to deactivate menu: ' . $update_stmt->error);
        }
        
        $update_stmt->close();
        
        // Get employee info
        $employee_info = getEmployeeInfo($conn_core_audit);
        
        // ---------------------------------------------------------
        // 🔔 Notification (CN time) - Insert to both databases
        // ---------------------------------------------------------
        $notification_title = "Menu Item Deactivated";
        $notification_message = "Menu item '{$menu['name']}' has been deactivated and is no longer available for orders.";
        $module = "Menu Management";
        
        // Insert to notification_m3 (rest_m3_menu)
        insertNotification($conn, 'notification_m3', $employee_info, $notification_title, $notification_message, $module);
        
        // Insert to notification_m4 (rest_m4_pos)
        if ($conn_m4_pos) {
            insertNotification($conn_m4_pos, 'notification_m4', $employee_info, $notification_title, $notification_message, $module);
        }
        
        // ---------------------------------------------------------
        // 🧾 Audit Log (PH time)
        // ---------------------------------------------------------
        $modules_cover = "Menu Management";
        $action = "Menu Item Deactivated";
        $activity = "Deactivated menu item '{$menu['name']}' (ID: {$menu_id})";
        
        insertAuditLog($conn_core_audit, $employee_info, $modules_cover, $action, $activity);
        
        // ---------------------------------------------------------
        // ✅ Commit Transactions
        // ---------------------------------------------------------
        $conn->commit();
        if ($conn_core_audit) {
            $conn_core_audit->commit();
        }
        if ($conn_m4_pos) {
            $conn_m4_pos->commit();
        }
        
        sendResponse(true, 'Menu "' . $menu['name'] . '" deactivated successfully', [
            'menu_id' => $menu_id,
            'menu_name' => $menu['name'],
            'new_status' => 'inactive',
            'updated_by' => $employee_info['employee_name'],
            'employee_id' => $employee_info['employee_id'],
            'timestamp' => date('Y-m-d H:i:s')
        ]);
        
    } catch (Exception $e) {
        // Rollback everything on error
        $conn->rollback();
        if ($conn_core_audit) {
            $conn_core_audit->rollback();
        }
        if ($conn_m4_pos) {
            $conn_m4_pos->rollback();
        }
        throw $e;
    }
}
?>