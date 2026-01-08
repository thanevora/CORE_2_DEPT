<?php
// Start session at the very beginning
session_start();

include '../../main_connection.php';

$db_name = "rest_m2_inventory";

if (!isset($connections[$db_name])) {
    die("❌ Connection not found for $db_name");
}

$conn = $connections[$db_name];

// Audit DB connection
$db_name_audit = "rest_core_2_usm";
if (!isset($connections[$db_name_audit])) {
    // Don't die, just log and continue without audit
    error_log("⚠️ Audit DB connection not found: " . $db_name_audit);
    $conn_audit = null;
} else {
    $conn_audit = $connections[$db_name_audit];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Start transactions
    $conn->begin_transaction();
    if ($conn_audit) {
        $conn_audit->begin_transaction();
    }
    
    // Handle file upload
    $image_url = null;
    $upload_path = null;
    
    if (isset($_FILES['item_image']) && $_FILES['item_image']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = __DIR__ . '/../upload_images/'; // Adjust path to your M2 folder
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        // File validation
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $max_size = 2 * 1024 * 1024; // 2MB
        $file_type = $_FILES['item_image']['type'];
        $file_size = $_FILES['item_image']['size'];
        $file_name = $_FILES['item_image']['name'];
        
        // Check file type
        if (!in_array($file_type, $allowed_types)) {
            header("Location: ../main.php?error=Invalid image type. Only JPG, PNG, GIF, and WEBP are allowed.");
            exit();
        }
        
        // Check file size
        if ($file_size > $max_size) {
            header("Location: ../main.php?error=Image size exceeds 2MB limit.");
            exit();
        }
        
        // Generate unique filename
        $file_extension = pathinfo($file_name, PATHINFO_EXTENSION);
        $unique_name = uniqid() . '_' . time() . '.' . $file_extension;
        $upload_path = $upload_dir . $unique_name;
        
        // Move uploaded file
        if (move_uploaded_file($_FILES['item_image']['tmp_name'], $upload_path)) {
            // Store relative path in database
            $image_url = '' . $unique_name;
        } else {
            header("Location: ../main.php?error=Failed to upload image.");
            exit();
        }
    }
    
    // Get form data
    $item_name         = $_POST['item_name'] ?? '';
    $category          = $_POST['category'] ?? '';
    $location          = $_POST['location'] ?? '';
    $quantity          = (int) ($_POST['quantity'] ?? 0);
    $critical_level    = (int) ($_POST['critical_level'] ?? 0);
    $unit_price        = (float) ($_POST['unit_price'] ?? 0);
    $notes             = $_POST['notes'] ?? '';
    $expiry_date       = $_POST['expiry_date'] ?: null;
    $last_restock_date = $_POST['last_restock_date'] ?: null;
    
    $created_at = date('Y-m-d H:i:s');
    $updated_at = $created_at;
    $request_status = 'For procurement approval';

    /* -----------------------------
       🔍 Fetch Employee Info
       ----------------------------- */
    $employee_id   = $_SESSION['employee_id'] ?? ($_SESSION['User_ID'] ?? 0);
    $employee_name = $_SESSION['employee_name'] ?? ($_SESSION['Name'] ?? '');
    $role          = $_SESSION['Role'] ?? ($_SESSION['role'] ?? '');

    // If employee info is empty, try to fetch from audit database using email or employee_name
    if ((empty($employee_id) || empty($employee_name)) && $conn_audit) {
        // Try by email first
        if (!empty($_SESSION['email'])) {
            $deptFetch = $conn_audit->prepare("
                SELECT employee_id, employee_name, role 
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
                        
                        // Update session with fetched values
                        $_SESSION['employee_id'] = $employee_id;
                        $_SESSION['employee_name'] = $employee_name;
                        $_SESSION['Role'] = $role;
                    }
                }
                $deptFetch->close();
            }
        }
        
        // If still empty, try by employee_name
        if ((empty($employee_id) || empty($employee_name)) && !empty($employee_name) && $employee_name !== '') {
            $deptFetch = $conn_audit->prepare("
                SELECT employee_id, employee_name, role 
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
                        
                        // Update session with fetched values
                        $_SESSION['employee_id'] = $employee_id;
                        $_SESSION['employee_name'] = $employee_name;
                        $_SESSION['Role'] = $role;
                    }
                }
                $deptFetch->close();
            }
        }
    }

    $employee_id   = (int) ($employee_id ?: 0);
    $employee_name = trim($employee_name ?: 'System');
    $role          = trim($role ?: 'Admin');

    // Prepare SQL statement with image_url
    $stmt = $conn->prepare("
        INSERT INTO inventory_and_stock (
            item_name, category, location, quantity, critical_level,
            unit_price, notes, expiry_date, last_restock_date,
            image_url, created_at, updated_at, request_status
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");

    if (!$stmt) {
        // If statement preparation fails
        if ($image_url && $upload_path && file_exists($upload_path)) {
            unlink($upload_path);
        }
        header("Location: ../main.php?error=Database preparation failed: " . htmlspecialchars($conn->error));
        exit();
    }

    $stmt->bind_param(
        "sssiidsssssss",
        $item_name, $category, $location, $quantity, $critical_level,
        $unit_price, $notes, $expiry_date, $last_restock_date,
        $image_url, $created_at, $updated_at, $request_status
    );

    if ($stmt->execute()) {
        $item_id = $conn->insert_id;
        
        // ---------------------------------------------------------
        // 🔔 Notification (CN time)
        // ---------------------------------------------------------
        $notification_title   = "New Inventory Item Added";
        $notification_message = "Inventory item '{$item_name}' has been added and is pending procurement approval.";
        $notification_status  = "Unread";
        $module               = "Inventory Management";

        $dateCN = new DateTime('now', new DateTimeZone('Asia/Shanghai'));
        $date_sent = $dateCN->format('Y-m-d H:i:s');

        // Check if notification_m2 table exists, if not create it
        $checkNotifTable = $conn->query("SHOW TABLES LIKE 'notification_m2'");
        if ($checkNotifTable) {
            if ($checkNotifTable->num_rows == 0) {
                $createNotifTable = $conn->query("
                    CREATE TABLE IF NOT EXISTS notification_m2 (
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
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
                ");
                if (!$createNotifTable) {
                    // Don't throw error for notification table creation failure, just log it
                    error_log("Failed to create notification_m2 table: " . $conn->error);
                }
            }
            $checkNotifTable->free();
        }

        $notifQuery = $conn->prepare("
            INSERT INTO notification_m2 
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
        // 🧾 Audit Log (PH time)
        // ---------------------------------------------------------
        if ($conn_audit) {
            $modules_cover = "Inventory Management";
            $action        = "Inventory Item Created";
            $activity      = "Created new inventory item '{$item_name}' under {$category} category (Location: {$location})";
            $auditDatePH   = new DateTime('now', new DateTimeZone('Asia/Manila'));
            $audit_date    = $auditDatePH->format('Y-m-d H:i:s');

            $dept_id   = $_SESSION['Dept_id'] ?? 0;
            $dept_name = $_SESSION['dept_name'] ?? 'Unknown';

            // Try to fetch department info if not in session
            if ($employee_id && ($dept_id == 0 || $dept_name === 'Unknown')) {
                $dq = $conn_audit->prepare("SELECT Dept_id, dept_name FROM department_accounts WHERE employee_id = ? LIMIT 1");
                if ($dq) {
                    $dq->bind_param("i", $employee_id);
                    if ($dq->execute()) {
                        $dr = $dq->get_result();
                        if ($dr && $dr->num_rows > 0) {
                            $drRow = $dr->fetch_assoc();
                            $dept_id   = $drRow['Dept_id'] ?? $dept_id;
                            $dept_name = $drRow['dept_name'] ?? $dept_name;
                            
                            // Update session
                            $_SESSION['Dept_id'] = $dept_id;
                            $_SESSION['dept_name'] = $dept_name;
                        }
                    }
                    $dq->close();
                }
            }

            // Check if dept_audit_transc table exists
            $checkAuditTable = $conn_audit->query("SHOW TABLES LIKE 'dept_audit_transc'");
            if ($checkAuditTable && $checkAuditTable->num_rows > 0) {
                $auditStmt = $conn_audit->prepare("
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
                        // Log audit failure but don't stop the process
                        error_log("Audit insert failed: " . ($auditStmt->error ?: 'Unknown error'));
                    }
                    $auditStmt->close();
                }
                $checkAuditTable->free();
            } else {
                error_log("⚠️ dept_audit_transc table not found in audit database");
            }
        }

        // ---------------------------------------------------------
        // ✅ Commit Transactions
        // ---------------------------------------------------------
        $conn->commit();
        if ($conn_audit) {
            $conn_audit->commit();
        }

        $stmt->close();
        
        header("Location: ../main.php?success=1&item=" . urlencode($item_name));
        exit();
    } else {
        // If insert fails, delete uploaded image
        if ($image_url && $upload_path && file_exists($upload_path)) {
            unlink($upload_path);
        }
        
        // Rollback transactions
        $conn->rollback();
        if ($conn_audit) {
            $conn_audit->rollback();
        }
        
        header("Location: ../main.php?error=1&message=" . urlencode($stmt->error));
        $stmt->close();
        exit();
    }

} else {
    header("Location: ../main.php?invalid=1");
    exit();
}