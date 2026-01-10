<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: application/json');

// -------------------- Connection Includes --------------------
$connectionPath = __DIR__ . '/../../main_connection.php';
if (!file_exists($connectionPath)) {
    echo json_encode(['success' => false, 'error' => 'Connection file not found.']);
    exit;
}
include($connectionPath);

/* DB handles */
$conn_core_audit = $connections['rest_core_2_usm'] ?? null; // for department_accounts + audit
$conn = $connections['rest_m3_menu'] ?? null;   // for menu module

if (!$conn || !$conn_core_audit) {
    echo json_encode(['success' => false, 'error' => 'Required database connections missing.']);
    exit;
}

session_start();

// -------------------- Validate POST --------------------
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(["status" => "error", "message" => "Invalid request method."]);
    exit;
}

// Image upload functions - UPDATED FOR M3/Menu_uploaded DIRECTORY
function uploadMenuImage($file) {
    // Use relative path from the API file location
    // Assuming add_item.php is in M3/sub-modules/ directory
    $uploadDir = __DIR__ . '/../Menu_uploaded/menu_images/';
    
    // Debug: Log upload directory
    error_log("Upload directory: " . $uploadDir);
    
    $originalDir = $uploadDir . 'original/';
    $optimizedDir = $uploadDir . 'optimized/';
    
    // Create directories if they don't exist
    if (!is_dir($originalDir)) {
        error_log("Creating directory: " . $originalDir);
        if (!mkdir($originalDir, 0755, true)) {
            throw new Exception('Failed to create upload directory: ' . $originalDir . '. Check permissions.');
        }
    }
    if (!is_dir($optimizedDir)) {
        error_log("Creating directory: " . $optimizedDir);
        if (!mkdir($optimizedDir, 0755, true)) {
            throw new Exception('Failed to create optimized directory: ' . $optimizedDir . '. Check permissions.');
        }
    }
    
    // Check if directories are writable
    if (!is_writable($originalDir)) {
        throw new Exception('Original directory is not writable: ' . $originalDir);
    }
    if (!is_writable($optimizedDir)) {
        throw new Exception('Optimized directory is not writable: ' . $optimizedDir);
    }
    
    // Validate file
    $allowedTypes = ['image/jpeg', 'image/png', 'image/webp', 'image/jpg'];
    $maxSize = 10 * 1024 * 1024; // 10MB for HD images
    
    if (!in_array($file['type'], $allowedTypes)) {
        throw new Exception('Invalid file type. Only JPEG, PNG, and WebP are allowed. Got: ' . $file['type']);
    }
    
    if ($file['size'] > $maxSize) {
        throw new Exception('File too large. Maximum size is 10MB. File size: ' . round($file['size'] / 1024 / 1024, 2) . 'MB');
    }
    
    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('Upload error code: ' . $file['error']);
    }
    
    // Generate unique filename
    $fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $filename = 'menu_' . uniqid() . '_' . time() . '.' . $fileExtension;
    $originalPath = $originalDir . $filename;
    $optimizedPath = $optimizedDir . $filename;
    
    error_log("Original path: " . $originalPath);
    error_log("Optimized path: " . $optimizedPath);
    
    // Move uploaded file to original directory
    if (move_uploaded_file($file['tmp_name'], $originalPath)) {
        error_log("File moved successfully to: " . $originalPath);
        
        // Create optimized version for web display
        try {
            $optimized = createOptimizedImage($originalPath, $optimizedPath);
            if ($optimized) {
                error_log("Optimized image created: " . $optimizedPath);
                return $filename;
            } else {
                // If optimization fails, still return the original
                error_log("Optimization failed, but original uploaded successfully");
                return $filename;
            }
        } catch (Exception $e) {
            error_log("Optimization error: " . $e->getMessage());
            // Still return filename if original uploaded
            return $filename;
        }
    } else {
        $lastError = error_get_last();
        throw new Exception('Failed to upload image. Last error: ' . ($lastError['message'] ?? 'Unknown'));
    }
}

function createOptimizedImage($originalPath, $optimizedPath) {
    // Check if GD is installed
    if (!extension_loaded('gd')) {
        error_log("GD library not loaded. Skipping optimization.");
        return false;
    }
    
    // Check if file exists
    if (!file_exists($originalPath)) {
        error_log("Original file not found: " . $originalPath);
        return false;
    }
    
    // Get image dimensions
    list($width, $height, $type) = getimagesize($originalPath);
    
    if (!$width || !$height) {
        error_log("Invalid image dimensions");
        return false;
    }
    
    error_log("Image dimensions: {$width}x{$height}, type: {$type}");
    
    // Set maximum dimensions for optimized version
    $maxWidth = 800;
    $maxHeight = 600;
    
    // Calculate new dimensions maintaining aspect ratio
    if ($width > $maxWidth || $height > $maxHeight) {
        $ratio = $width / $height;
        
        if ($ratio > 1) {
            // Landscape
            $newWidth = $maxWidth;
            $newHeight = $maxWidth / $ratio;
        } else {
            // Portrait
            $newHeight = $maxHeight;
            $newWidth = $maxHeight * $ratio;
        }
    } else {
        // Image is smaller than max dimensions, use original
        $newWidth = $width;
        $newHeight = $height;
    }
    
    error_log("New dimensions: {$newWidth}x{$newHeight}");
    
    // Create image resource based on file type
    $image = false;
    switch ($type) {
        case IMAGETYPE_JPEG:
            $image = imagecreatefromjpeg($originalPath);
            break;
        case IMAGETYPE_PNG:
            $image = imagecreatefrompng($originalPath);
            // Preserve transparency
            imagealphablending($image, false);
            imagesavealpha($image, true);
            break;
        case IMAGETYPE_WEBP:
            if (function_exists('imagecreatefromwebp')) {
                $image = imagecreatefromwebp($originalPath);
            } else {
                error_log("WebP not supported by GD");
                return false;
            }
            break;
        default:
            error_log("Unsupported image type: " . $type);
            return false;
    }
    
    if (!$image) {
        error_log("Failed to create image resource");
        return false;
    }
    
    // Create optimized image
    $optimizedImage = imagecreatetruecolor($newWidth, $newHeight);
    
    // Preserve transparency for PNG
    if ($type == IMAGETYPE_PNG) {
        imagealphablending($optimizedImage, false);
        imagesavealpha($optimizedImage, true);
        $transparent = imagecolorallocatealpha($optimizedImage, 255, 255, 255, 127);
        imagefilledrectangle($optimizedImage, 0, 0, $newWidth, $newHeight, $transparent);
    }
    
    // Resize image
    imagecopyresampled($optimizedImage, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
    
    // Save optimized image
    $result = false;
    switch ($type) {
        case IMAGETYPE_JPEG:
            $result = imagejpeg($optimizedImage, $optimizedPath, 85); // 85% quality
            break;
        case IMAGETYPE_PNG:
            $result = imagepng($optimizedImage, $optimizedPath, 8); // Compression level
            break;
        case IMAGETYPE_WEBP:
            if (function_exists('imagewebp')) {
                $result = imagewebp($optimizedImage, $optimizedPath, 85); // 85% quality
            }
            break;
    }
    
    // Free memory
    imagedestroy($image);
    imagedestroy($optimizedImage);
    
    if (!$result) {
        error_log("Failed to save optimized image to: " . $optimizedPath);
    }
    
    return $result;
}

/* -----------------------------
   🔍 Fetch Employee Info
   ----------------------------- */
$employee_id   = $_SESSION['employee_id'] ?? ($_SESSION['User_ID'] ?? 0);
$employee_name = $_SESSION['employee_name'] ?? ($_SESSION['Name'] ?? '');
$role          = $_SESSION['Role'] ?? ($_SESSION['role'] ?? '');

if (empty($employee_id) || empty($employee_name)) {
    if (!empty($_SESSION['email'])) {
        $deptFetch = $conn_core_audit->prepare("
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
                }
            }
            $deptFetch->close();
        }
    } elseif (!empty($employee_name)) {
        $deptFetch = $conn_core_audit->prepare("
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
                }
            }
            $deptFetch->close();
        }
    }
}

$employee_id   = (int) ($employee_id ?: 0);
$employee_name = trim($employee_name ?: 'System');
$role          = trim($role ?: 'Admin');



// -------------------- Begin Try/Catch --------------------
try {
    // Begin transaction
    $conn->begin_transaction();
    $conn_core_audit->begin_transaction();

    // Collect form data
    $name         = trim($_POST['name'] ?? '');
    $category     = trim($_POST['category'] ?? '');
    $prep_time    = trim($_POST['prep_time'] ?? '');
    $variant      = trim($_POST['variant'] ?? '');
    $availability = trim($_POST['availability'] ?? '');
    $description  = trim($_POST['description'] ?? '');
    $price        = floatval($_POST['price'] ?? 0);
    $status       = trim($_POST['status'] ?? 'Pending for approval');

    // Ingredients data
    $ingredients     = $_POST['ingredients'] ?? [];
    $ingredient_qty  = $_POST['ingredient_qty'] ?? [];
    $ingredient_unit = $_POST['ingredient_unit'] ?? [];

    // Handle image upload with new system
    $image_url = null;
    if (isset($_FILES['image_url']) && $_FILES['image_url']['error'] === UPLOAD_ERR_OK) {
        error_log("Image upload detected, processing...");
        $image_filename = uploadMenuImage($_FILES['image_url']);
        $image_url = $image_filename; // Store just the filename
        error_log("Image uploaded successfully: " . $image_url);
    } elseif (isset($_FILES['image_url'])) {
        error_log("File upload error: " . $_FILES['image_url']['error']);
    } else {
        error_log("No image file uploaded");
    }

    // Validate required fields
    if (empty($name) || empty($category) || empty($prep_time) || empty($variant) || empty($description) || $price <= 0) {
        throw new Exception("Please fill out all required fields and ensure price is greater than 0.");
    }

    // Validate ingredients
    if (empty($ingredients)) {
        throw new Exception("Please add at least one ingredient.");
    }

    // -------------------- Insert Menu Item --------------------
    $stmt = $conn->prepare("
        INSERT INTO menu 
        (name, category, prep_time, variant, description, price, image_url, status, created_at, updated_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
    ");
    
    if (!$stmt) {
        throw new Exception("Failed to prepare menu insert statement: " . $conn->error);
    }
    
    $stmt->bind_param("ssissdss", $name, $category, $prep_time, $variant, $description, $price, $image_url, $status);

    if (!$stmt->execute()) {
        throw new Exception("Failed to add menu item: " . $stmt->error);
    }

    $menu_id = $stmt->insert_id;
    $stmt->close();

    // -------------------- Insert Ingredients --------------------
    if (!empty($ingredients)) {
        // Check if menu_ingredients table exists, if not create it
        $checkTable = $conn->query("SHOW TABLES LIKE 'menu_ingredients'");
        if ($checkTable && $checkTable->num_rows == 0) {
            // Create menu_ingredients table
            $createTable = $conn->query("
                CREATE TABLE IF NOT EXISTS menu_ingredients (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    menu_id INT NOT NULL,
                    item_id INT NOT NULL,
                    quantity DECIMAL(10,2) NOT NULL,
                    unit VARCHAR(20) NOT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (menu_id) REFERENCES menu(menu_id) ON DELETE CASCADE
                )
            ");
            if (!$createTable) {
                throw new Exception("Failed to create menu_ingredients table: " . $conn->error);
            }
        }
        
        if ($checkTable) {
            $checkTable->free();
        }

        $ingredientStmt = $conn->prepare("
            INSERT INTO menu_ingredients 
            (menu_id, item_id, quantity, unit) 
            VALUES (?, ?, ?, ?)
        ");
        
        if (!$ingredientStmt) {
            throw new Exception("Failed to prepare ingredient statement: " . $conn->error);
        }
        
        foreach ($ingredients as $index => $ingredient_id) {
            if (isset($ingredient_qty[$index]) && isset($ingredient_unit[$index])) {
                $quantity = floatval($ingredient_qty[$index]);
                $unit = trim($ingredient_unit[$index]);
                
                if ($quantity > 0 && !empty($unit)) {
                    $ingredientStmt->bind_param("iids", $menu_id, $ingredient_id, $quantity, $unit);
                    
                    if (!$ingredientStmt->execute()) {
                        throw new Exception("Failed to add ingredient: " . $ingredientStmt->error);
                    }
                }
            }
        }
        $ingredientStmt->close();
    }

    // ---------------------------------------------------------
    // 🔔 Notification (CN time)
    // ---------------------------------------------------------
    $notification_title   = "New Menu Item Added";
    $notification_message = "Menu item '{$name}' has been added and is pending approval.";
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
    // 🧾 Audit Log (PH time)
    // ---------------------------------------------------------
    $modules_cover = "Menu Management";
    $action        = "Menu Item Created";
    $activity      = "Created new menu item '{$name}' under {$category} (Variant: {$variant})";
    $auditDatePH   = new DateTime('now', new DateTimeZone('Asia/Manila'));
    $audit_date    = $auditDatePH->format('Y-m-d H:i:s');

    $dept_id   = $_SESSION['Dept_id'] ?? 0;
    $dept_name = $_SESSION['dept_name'] ?? 'Unknown';

    if ($employee_id && ($dept_id == 0 || $dept_name === 'Unknown')) {
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

    // ---------------------------------------------------------
    // ✅ Commit Transactions
    // ---------------------------------------------------------
    $conn->commit();
    $conn_core_audit->commit();

    echo json_encode([
        'status' => 'success',
        'message' => "Menu item '{$name}' added successfully!" . ($image_url ? " Image uploaded." : ""),
        'menu_id' => $menu_id,
        'image_url' => $image_url,
        'details' => [
            'category' => $category,
            'variant' => $variant,
            'price' => $price,
            'status' => $status,
            'ingredients_count' => count($ingredients),
            'created_by' => $employee_name
        ]
    ]);
    exit;

} catch (Exception $e) {
    // Rollback everything if something goes wrong
    if ($conn) $conn->rollback();
    if ($conn_core_audit) $conn_core_audit->rollback();

    error_log("Menu item addition error: " . $e->getMessage());
    
    echo json_encode([
        "status" => "error",
        "message" => $e->getMessage()
    ]);
    exit;
}
?>