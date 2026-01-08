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

// Inputs
$table_name = trim($_POST['name'] ?? '');
$category   = trim($_POST['category'] ?? '');
$capacity   = isset($_POST['capacity']) ? (int) $_POST['capacity'] : 0;
$status     = 'Available'; // Always set to Available when adding
$image_url  = null;

if ($table_name === '' || $category === '' || $capacity <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid input']);
    exit;
}

// Handle image upload
if (isset($_FILES['table_image']) && $_FILES['table_image']['error'] === UPLOAD_ERR_OK) {
    $uploadDir = '../../M1/Table_images/';
    
    // Create directory if it doesn't exist
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }
    
    $fileExtension = pathinfo($_FILES['table_image']['name'], PATHINFO_EXTENSION);
    $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    
    if (!in_array(strtolower($fileExtension), $allowedExtensions)) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid file type. Only JPG, PNG, GIF, and WebP are allowed.']);
        exit;
    }
    
    // Generate unique filename
    $fileName = 'table_' . time() . '_' . uniqid() . '.' . $fileExtension;
    $filePath = $uploadDir . $fileName;
    
    if (move_uploaded_file($_FILES['table_image']['tmp_name'], $filePath)) {
        $image_url = $fileName; // Store only filename
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to upload image']);
        exit;
    }
}

try {
    // Begin transactions in ALL DBs
    $conn_pos->begin_transaction();
    $conn_turnover->begin_transaction();
    $conn_audit->begin_transaction();

    // 1️⃣ Insert into tables (POS DB)
    if ($image_url) {
        $stmt = $conn_pos->prepare("INSERT INTO tables (name, category, capacity, image_url, status) VALUES (?, ?, ?, ?, ?)");
        if (!$stmt) throw new Exception("Prepare failed for tables");
        $stmt->bind_param("ssiss", $table_name, $category, $capacity, $image_url, $status);
    } else {
        $stmt = $conn_pos->prepare("INSERT INTO tables (name, category, capacity, status) VALUES (?, ?, ?, ?)");
        if (!$stmt) throw new Exception("Prepare failed for tables");
        $stmt->bind_param("ssis", $table_name, $category, $capacity, $status);
    }
    
    if (!$stmt->execute()) throw new Exception("Execution failed for tables");
    $table_id = $stmt->insert_id;
    $stmt->close();

    // 2️⃣ Insert into table_metrics (turnover DB)
    $metricsStmt = $conn_turnover->prepare("
        INSERT INTO table_metrics (table_id, turnover_count, avg_wait_time_minutes, record_date)
        VALUES (?, 0, 0, NOW())
    ");
    if (!$metricsStmt) throw new Exception("Prepare failed for metrics");
    $metricsStmt->bind_param("i", $table_id);
    if (!$metricsStmt->execute()) throw new Exception("Execution failed for metrics");
    $metricsStmt->close();

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
    $notification_title   = "New Table Added";
    $notification_message = "A new table named <strong>$table_name</strong> was added. Category: $category, Capacity: $capacity";
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
    $action        = "Table Added";
    $activity      = "Added new table '$table_name' (Category: $category, Capacity: $capacity, Status: $status" . ($image_url ? ", With Image" : "") . ")";
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
        $action,
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

    echo json_encode([
        'status' => 'success',
        'message' => "Table '$table_name' added successfully!",
        'data' => [
            'table_id' => $table_id,
            'table_name' => $table_name,
            'category' => $category,
            'capacity' => $capacity,
            'image_url' => $image_url,
            'status' => $status,
            'added_by' => $employee_name,
            'employee_id' => $employee_id,
            'timestamp' => date('Y-m-d H:i:s')
        ]
    ]);

} catch (Exception $e) {
    $conn_pos->rollback();
    $conn_turnover->rollback();
    $conn_audit->rollback();
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}

// Close connections
$conn_pos->close();
$conn_turnover->close();
$conn_audit->close();
?>