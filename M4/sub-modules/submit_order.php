<?php
session_start();
include("../../main_connection.php");

// Set timezone to Manila/Beijing (UTC+8)
date_default_timezone_set('Asia/Manila');

// Force JSON output
header('Content-Type: application/json');

function respond($status, $message, $extra = []) {
    http_response_code($status === 'success' ? 200 : 400);
    echo json_encode(array_merge(['status' => $status, 'message' => $message], $extra));
    exit;
}

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// =======================
// Database connections
// =======================
$conn_pos = $connections["rest_m4_pos"] ?? respond('error', 'Connection not found for POS');
$conn_billing = $connections["rest_m7_billing_payments"] ?? respond('error', 'Connection not found for Billing');
$conn_kot = $connections["rest_m6_kot"] ?? respond('error', 'Connection not found for KOT');
$conn_tables = $connections["rest_m1_trs"] ?? respond('error', 'Connection not found for Tables');
$conn_core_audit = $connections["rest_core_2_usm"] ?? respond('error', 'Connection not found for Audit');

// =======================
// Collect request data - SUPPORT MULTIPLE METHODS
// =======================
$input_data = [];

// Method 1: Check if JSON was sent
$json_input = file_get_contents('php://input');
if (!empty($json_input)) {
    $json_data = json_decode($json_input, true);
    if (json_last_error() === JSON_ERROR_NONE && is_array($json_data)) {
        $input_data = array_merge($input_data, $json_data);
    }
}

// Method 2: Check POST data
if (!empty($_POST)) {
    $input_data = array_merge($input_data, $_POST);
}

// Method 3: For debugging - log what we received
error_log("Received data: " . print_r($input_data, true));

// Extract values with fallbacks
$order_code     = $input_data['order_code'] ?? $input_data['orderCode'] ?? $input_data['order_id'] ?? null;
$table_id       = $input_data['table_id'] ?? $input_data['tableId'] ?? $input_data['table'] ?? null;
$customer_name  = $input_data['customer_name'] ?? $input_data['customerName'] ?? $input_data['customer'] ?? 'Walk-in Customer';
$order_type     = $input_data['order_type'] ?? $input_data['orderType'] ?? 'dine-in';
$total_amount   = $input_data['total_amount'] ?? $input_data['totalAmount'] ?? $input_data['total'] ?? 0;
$mop            = $input_data['MOP'] ?? $input_data['payment_method'] ?? $input_data['paymentMethod'] ?? 'cash';
$notes          = $input_data['notes'] ?? $input_data['special_instructions'] ?? '';
$placed_by      = $input_data['placed_by'] ?? $input_data['placedBy'] ?? '';
$served_by      = $input_data['served_by'] ?? $input_data['servedBy'] ?? '';

// Handle order items - could be in different formats
$orders_json = '';
if (isset($input_data['order_items_json'])) {
    $orders_json = $input_data['order_items_json'];
} elseif (isset($input_data['order_items'])) {
    $orders_json = json_encode($input_data['order_items']);
} elseif (isset($input_data['items'])) {
    $orders_json = json_encode($input_data['items']);
} elseif (isset($input_data['cartItems'])) {
    $orders_json = json_encode($input_data['cartItems']);
} else {
    $orders_json = '[]';
}

$created_at     = date("Y-m-d H:i:s");

// Debug logging
error_log("Extracted values - order_code: $order_code, table_id: $table_id, customer_name: $customer_name");

// Validate required fields - with better error messages
$missing_fields = [];
if (empty($order_code)) $missing_fields[] = 'order_code';
if (empty($table_id)) $missing_fields[] = 'table_id';
if (empty($customer_name)) $missing_fields[] = 'customer_name';

if (!empty($missing_fields)) {
    respond('error', 'Missing required fields: ' . implode(', ', $missing_fields) . 
           '. Received data: ' . json_encode([
               'order_code' => $order_code,
               'table_id' => $table_id,
               'customer_name' => $customer_name,
               'all_data' => $input_data
           ]));
}

// If table_id is a string but should be integer, convert it
if (is_string($table_id) && is_numeric($table_id)) {
    $table_id = (int)$table_id;
}

// Generate order code if not provided
if (empty($order_code) || $order_code === 'null' || $order_code === 'undefined') {
    $order_code = 'ORD-' . date('YmdHis') . '-' . rand(1000, 9999);
    error_log("Generated order_code: $order_code");
}

// Decode orders JSON safely
$order_items = [];
if (!empty($orders_json) && $orders_json !== '[]') {
    $order_items = json_decode($orders_json, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        // Try to fix common JSON issues
        $orders_json = str_replace("'", '"', $orders_json);
        $orders_json = preg_replace('/\s+/', ' ', $orders_json);
        $order_items = json_decode($orders_json, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            respond('error', 'Invalid order items format. JSON error: ' . json_last_error_msg() . 
                   ' | Data: ' . substr($orders_json, 0, 200));
        }
    }
}

// If still empty, try to get from other possible keys
if (empty($order_items)) {
    // Check if items are directly in input_data
    if (isset($input_data['items']) && is_array($input_data['items'])) {
        $order_items = $input_data['items'];
    } elseif (isset($input_data['order_items']) && is_array($input_data['order_items'])) {
        $order_items = $input_data['order_items'];
    } elseif (isset($input_data['cartItems']) && is_array($input_data['cartItems'])) {
        $order_items = $input_data['cartItems'];
    }
}

if (empty($order_items)) {
    respond('error', 'No order items provided. orders_json: ' . substr($orders_json, 0, 100));
}

// Validate total amount
$calculated_total = 0;
foreach ($order_items as $item) {
    $price = floatval($item['price'] ?? $item['unit_price'] ?? $item['amount'] ?? 0);
    $quantity = intval($item['quantity'] ?? $item['qty'] ?? 1);
    $calculated_total += ($price * $quantity);
}

// Add tax and service charge
$service_charge = $calculated_total * 0.02;
$vat = ($calculated_total + $service_charge) * 0.12;
$calculated_total_with_tax = $calculated_total + $service_charge + $vat;

// Allow small differences due to rounding
$tolerance = 0.10; // 10 centavos tolerance
if (abs($calculated_total_with_tax - floatval($total_amount)) > $tolerance) {
    error_log("Total mismatch - Calculated: $calculated_total_with_tax, Provided: $total_amount");
    // For now, use calculated total if mismatch is too big
    if ($total_amount == 0) {
        $total_amount = $calculated_total_with_tax;
    }
}

// Get table details
$stmt_table = $conn_tables->prepare("SELECT name, status FROM tables WHERE table_id = ?");
$stmt_table->bind_param("i", $table_id);
$stmt_table->execute();
$result_table = $stmt_table->get_result();
if ($result_table->num_rows === 0) {
    respond('error', 'Table not found with ID: ' . $table_id);
}
$table = $result_table->fetch_assoc();
$table_name = $table['name'];
$current_table_status = $table['status'];
$stmt_table->close();

// Check if table is available
if ($current_table_status !== 'Available' && $current_table_status !== 'Reserved' && $current_table_status !== 'Occupied') {
    respond('error', "Table '{$table_name}' is currently {$current_table_status}. Cannot place order.");
}

// Get employee info for placed_by and served_by
$employee_id   = $_SESSION['employee_id'] ?? ($_SESSION['User_ID'] ?? 0);
$employee_name = $_SESSION['employee_name'] ?? ($_SESSION['Name'] ?? '');

if (empty($placed_by)) {
    $placed_by = !empty($employee_name) ? $employee_name : 'System';
}

if (empty($served_by)) {
    $served_by = !empty($employee_name) ? $employee_name : 'Staff';
}

try {
    // Start transaction across all databases
    $conn_pos->begin_transaction();
    $conn_billing->begin_transaction();
    $conn_kot->begin_transaction();
    $conn_tables->begin_transaction();
    $conn_core_audit->begin_transaction();

    // =======================
    // Step 1: Update table status to Occupied
    // =======================
    $new_table_status = 'Occupied';
    $stmt_update_table = $conn_tables->prepare("UPDATE tables SET status = ? WHERE table_id = ?");
    $stmt_update_table->bind_param("si", $new_table_status, $table_id);
    
    if (!$stmt_update_table->execute()) {
        throw new Exception('Table status update error: ' . $stmt_update_table->error);
    }
    $stmt_update_table->close();

    // =======================
    // Step 2: Insert into POS.orders
    // =======================
    $status_pos = "Pending";
    
    $sql_pos = "INSERT INTO orders 
        (order_code, table_id, customer_name, order_type, status, total_amount, MOP, placed_by, served_by, notes, created_at) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt_pos = $conn_pos->prepare($sql_pos);
    $stmt_pos->bind_param(
        "sisssdsssss",
        $order_code,
        $table_id,
        $customer_name,
        $order_type,
        $status_pos,
        $total_amount,
        $mop,
        $placed_by,
        $served_by,
        $notes,
        $created_at
    );
    
    if (!$stmt_pos->execute()) {
        throw new Exception('POS insert error: ' . $stmt_pos->error);
    }
    $order_id = $stmt_pos->insert_id;
    $stmt_pos->close();

    // =======================
    // Step 3: Insert into Billing
    // =======================
    $invoice_number = $order_code;
    $invoice_date   = date("Y-m-d");
    $status_billing = "Paid";
    $description    = "Order #{$order_code} for Table {$table_name} - {$order_type}";
    $quantity       = 1;
    $unit_price     = $total_amount;
    $payment_date   = date("Y-m-d");
    $client_email   = $input_data['customer_email'] ?? $input_data['email'] ?? '';
    $client_contact = $input_data['customer_contact'] ?? $input_data['contact'] ?? $input_data['phone'] ?? '';

    $sql_billing = "INSERT INTO billing_payments 
        (client_name, client_email, client_contact, invoice_number, invoice_date, status, 
         description, quantity, unit_price, total_amount, payment_date, MOP, created_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt_billing = $conn_billing->prepare($sql_billing);
    $stmt_billing->bind_param(
        "ssssssssddsss", 
        $customer_name,
        $client_email,
        $client_contact,
        $invoice_number,
        $invoice_date,
        $status_billing,
        $description,
        $quantity,
        $unit_price,
        $total_amount,
        $payment_date,
        $mop,
        $created_at
    );
    
    if (!$stmt_billing->execute()) {
        throw new Exception('Billing insert error: ' . $stmt_billing->error);
    }
    $billing_id = $stmt_billing->insert_id;
    $stmt_billing->close();

    // =======================
    // Step 4: Insert into KOT
    // =======================
    $kot_status   = "Pending";

    $sql_kot = "INSERT INTO kot_orders 
        (order_id, table_number, item_name, quantity, special_instructions, status, created_at)
        VALUES (?, ?, ?, ?, ?, ?, ?)";
    
    $stmt_kot = $conn_kot->prepare($sql_kot);

    foreach ($order_items as $item) {
        $item_name    = $item['name'] ?? $item['item_name'] ?? 'Unknown Item';
        $kot_quantity = $item['quantity'] ?? $item['qty'] ?? 1;
        $special_instructions = $item['special_instructions'] ?? $item['notes'] ?? $notes;

        $stmt_kot->bind_param(
            "issssss",
            $order_id,
            $table_name,
            $item_name,
            $kot_quantity,
            $special_instructions,
            $kot_status,
            $created_at
        );

        if (!$stmt_kot->execute()) {
            throw new Exception('KOT insert error: ' . $stmt_kot->error);
        }
    }
    $stmt_kot->close();

    // =======================
    // Step 5: Get Employee Info for Audit
    // =======================
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

    // Get department info
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

    // =======================
    // Step 6: Insert Notification for POS Order (CN time)
    // =======================
    $notification_title   = "New Order Placed";
    $notification_message = "Order #{$order_code} has been placed for Table {$table_name} by {$customer_name}. Total: ₱" . number_format($total_amount, 2);
    $notification_status  = "Unread";
    $module               = "POS Order Management";

    $dateCN = new DateTime('now', new DateTimeZone('Asia/Shanghai'));
    $date_sent = $dateCN->format('Y-m-d H:i:s');

    // Check if notification_m4 table exists
    $checkNotifTable = $conn_pos->query("SHOW TABLES LIKE 'notification_m4'");
    if ($checkNotifTable && $checkNotifTable->num_rows > 0) {
        $notifQuery = $conn_pos->prepare("
            INSERT INTO notification_m4 
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
    }
    
    if ($checkNotifTable) {
        $checkNotifTable->free();
    }

    // =======================
    // Step 7: Insert Audit Log for POS Order (PH time)
    // =======================
    $modules_cover = "POS Order Management";
    $action        = "Order Placed";
    $activity      = "Order #{$order_code} placed for Table {$table_name} by {$customer_name}. Total: ₱" . number_format($total_amount, 2) . " (Items: " . count($order_items) . ")";
    
    $auditDatePH   = new DateTime('now', new DateTimeZone('Asia/Manila'));
    $audit_date    = $auditDatePH->format('Y-m-d H:i:s');

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

    // =======================
    // Step 8: Generate Receipt Data and JPEG Image
    // =======================
    
    // Get current Manila time for receipt
    $manilaDateTime = new DateTime('now', new DateTimeZone('Asia/Manila'));
    $receipt_date = $manilaDateTime->format('F j, Y'); // e.g., "March 15, 2024"
    $receipt_time = $manilaDateTime->format('h:i A');   // e.g., "02:30 PM"
    $receipt_timestamp = $manilaDateTime->format('Y-m-d H:i:s');
    
    $receipt_data = [
        'restaurant_name' => 'SOLIERA HOTEL & RESTAURANT',
        'restaurant_address' => '123 Jorge City, Valenzuela, Philippines',
        'restaurant_contact' => '(02) 8123-4567',
        'restaurant_logo' => '../../images/soliera_S.png',
        'order_id' => $order_code,
        'invoice_number' => $invoice_number,
        'date' => $receipt_date,
        'time' => $receipt_time,
        'timestamp' => $receipt_timestamp,
        'table' => "Table {$table_name}",
        'customer' => $customer_name,
        'server' => $employee_name,
        'placed_by' => $placed_by,
        'served_by' => $served_by,
        'items' => $order_items,
        'subtotal' => number_format($calculated_total, 2),
        'service_charge' => number_format($service_charge, 2),
        'vat' => number_format($vat, 2),
        'total' => number_format($total_amount, 2),
        'payment_method' => strtoupper($mop),
        'payment_status' => $status_billing,
        'notes' => $notes,
        'items_count' => count($order_items),
        'thank_you_message' => 'Thank you for dining with us!',
        'watermark_text' => 'SOLIERA HOTEL & RESTAURANT',
        'background_image' => '../../images/receipt-bg.jpg'
    ];

    // Generate receipt JPEG
    $jpeg_data = generateReceiptJPEG($receipt_data);
    
    // Generate receipt HTML for preview
    $receipt_html = generateReceiptHTML($receipt_data);

    // =======================
    // Step 9: Commit all transactions
    // =======================
    $conn_pos->commit();
    $conn_billing->commit();
    $conn_kot->commit();
    $conn_tables->commit();
    $conn_core_audit->commit();

    // =======================
    // Step 10: Return success response with download link
    // =======================
    $download_url = "data:image/jpeg;base64," . base64_encode($jpeg_data);
    
    respond('success', 'Order submitted successfully! Table status updated to Occupied.', [
        'order_id' => $order_id,
        'billing_id' => $billing_id,
        'order_code' => $order_code,
        'table_status' => $new_table_status,
        'table_name' => $table_name,
        'customer_name' => $customer_name,
        'total_amount' => $total_amount,
        'placed_by' => $placed_by,
        'served_by' => $served_by,
        'receipt_data' => $receipt_data,
        'receipt_html' => $receipt_html,
        'receipt_jpeg' => $download_url,
        'receipt_filename' => "receipt_{$order_code}.jpg",
        'auto_download' => true,
        'download_instructions' => 'The receipt JPEG has been generated and will auto-download.',
        'items_count' => count($order_items),
        'current_time_manila' => $receipt_timestamp
    ]);

} catch (Exception $e) {
    // Rollback all transactions
    $conn_pos->rollback();
    $conn_billing->rollback();
    $conn_kot->rollback();
    $conn_tables->rollback();
    $conn_core_audit->rollback();
    
    respond('error', $e->getMessage());
} finally {
    // Close connections
    if (isset($conn_pos)) $conn_pos->close();
    if (isset($conn_billing)) $conn_billing->close();
    if (isset($conn_kot)) $conn_kot->close();
    if (isset($conn_tables)) $conn_tables->close();
    if (isset($conn_core_audit)) $conn_core_audit->close();
}

// =======================
// Function to generate receipt JPEG with TrueType fonts for Peso sign
// =======================
function generateReceiptJPEG($data) {
    // Check if GD library is available
    if (!function_exists('imagecreatetruecolor')) {
        error_log("GD library not available. Cannot generate JPEG receipt.");
        return '';
    }
    
    // Create image dimensions
    $width = 500;
    $height = 750;
    $image = imagecreatetruecolor($width, $height);
    
    if (!$image) {
        error_log("ERROR: Failed to create image resource.");
        return '';
    }
    
    // Define colors
    $white = imagecolorallocate($image, 255, 255, 255);
    $black = imagecolorallocate($image, 0, 0, 0);
    $gray = imagecolorallocate($image, 100, 100, 100);
    $dark_gray = imagecolorallocate($image, 50, 50, 50);
    $red = imagecolorallocate($image, 255, 0, 0);
    $green = imagecolorallocate($image, 0, 128, 0);
    $blue = imagecolorallocate($image, 0, 0, 255);
    $gold = imagecolorallocate($image, 255, 215, 0);
    $dark_red = imagecolorallocate($image, 178, 34, 34);
    
    // Fill background with white
    imagefilledrectangle($image, 0, 0, $width, $height, $white);
    
    // Try to load background image if exists
    if (isset($data['background_image']) && file_exists($data['background_image'])) {
        $bg_image = @imagecreatefromjpeg($data['background_image']);
        if ($bg_image) {
            imagecopyresized($image, $bg_image, 0, 0, 0, 0, $width, $height, imagesx($bg_image), imagesy($bg_image));
            imagedestroy($bg_image);
            
            // Create a semi-transparent white overlay for readability
            $overlay = imagecolorallocatealpha($image, 255, 255, 255, 80);
            imagefilledrectangle($image, 10, 10, $width-10, $height-10, $overlay);
        }
    }
    
    // Add border
    imagerectangle($image, 5, 5, $width-5, $height-5, $black);
    
    // Find a TrueType font
    $font_path = findTrueTypeFont();
    $use_ttf = !empty($font_path);
    
    if (!$use_ttf) {
        error_log("WARNING: No TrueType font found. Using basic GD font.");
    }
    
    // Start Y position
    $y = 30;
    
    // Restaurant Name (Large text)
    addTextToImage($image, 100, $y, "SOLIERA HOTEL & RESTAURANT", $black, 16, $font_path, $use_ttf);
    $y += 40;
    
    // Address
    addTextToImage($image, 120, $y, $data['restaurant_address'], $dark_gray, 10, $font_path, $use_ttf);
    $y += 25;
    
    // Contact
    addTextToImage($image, 150, $y, "Tel: " . $data['restaurant_contact'], $dark_gray, 10, $font_path, $use_ttf);
    $y += 35;
    
    // Separator line
    imageline($image, 20, $y, $width-20, $y, $black);
    $y += 25;
    
    // Receipt Title
    addTextToImage($image, 180, $y, "ORDER RECEIPT", $black, 14, $font_path, $use_ttf);
    $y += 35;
    
    // Order Details
    addTextToImage($image, 30, $y, "Order #:", $black, 11, $font_path, $use_ttf);
    addTextToImage($image, 120, $y, $data['order_id'], $blue, 11, $font_path, $use_ttf);
    $y += 25;
    
    addTextToImage($image, 30, $y, "Date:", $black, 11, $font_path, $use_ttf);
    addTextToImage($image, 120, $y, $data['date'], $black, 11, $font_path, $use_ttf);
    $y += 25;
    
    addTextToImage($image, 30, $y, "Time:", $black, 11, $font_path, $use_ttf);
    addTextToImage($image, 120, $y, $data['time'], $black, 11, $font_path, $use_ttf);
    $y += 25;
    
    addTextToImage($image, 30, $y, "Table:", $black, 11, $font_path, $use_ttf);
    addTextToImage($image, 120, $y, $data['table'], $black, 11, $font_path, $use_ttf);
    $y += 25;
    
    addTextToImage($image, 30, $y, "Customer:", $black, 11, $font_path, $use_ttf);
    addTextToImage($image, 120, $y, $data['customer'], $black, 11, $font_path, $use_ttf);
    $y += 35;
    
    // Items header
    imageline($image, 20, $y, $width-20, $y, $gray);
    $y += 15;
    addTextToImage($image, 30, $y, "ITEMS ORDERED", $black, 12, $font_path, $use_ttf);
    $y += 30;
    
    // Column headers
    addTextToImage($image, 30, $y, "Item", $black, 11, $font_path, $use_ttf);
    addTextToImage($image, 300, $y, "Qty", $black, 11, $font_path, $use_ttf);
    addTextToImage($image, 370, $y, "Amount", $black, 11, $font_path, $use_ttf);
    $y += 25;
    
    imageline($image, 20, $y, $width-20, $y, $gray);
    $y += 15;
    
    // List items (max 12)
    $item_count = 0;
    $max_items = 12;
    foreach ($data['items'] as $item) {
        if ($item_count >= $max_items) {
            $remaining = count($data['items']) - $max_items;
            addTextToImage($image, 30, $y, "... and {$remaining} more items", $gray, 9, $font_path, $use_ttf);
            $y += 20;
            break;
        }
        
        $name = substr($item['name'] ?? 'Item', 0, 28);
        $qty = $item['quantity'] ?? 1;
        $price = $item['price'] ?? 0;
        $total = $qty * $price;
        
        addTextToImage($image, 30, $y, $name, $black, 10, $font_path, $use_ttf);
        addTextToImage($image, 300, $y, $qty . 'x', $black, 10, $font_path, $use_ttf);
        
        // Use Peso sign with proper TrueType font
        if ($use_ttf) {
            addTextToImage($image, 370, $y, '₱' . number_format($total, 2), $black, 10, $font_path, $use_ttf);
        } else {
            // Fallback to PHP if no TTF font
            addTextToImage($image, 370, $y, 'PHP ' . number_format($total, 2), $black, 10, $font_path, $use_ttf);
        }
        $y += 22;
        $item_count++;
    }
    
    $y += 15;
    imageline($image, 20, $y, $width-20, $y, $gray);
    $y += 25;
    
    // Totals section
    addTextToImage($image, 30, $y, "Subtotal:", $black, 11, $font_path, $use_ttf);
    if ($use_ttf) {
        addTextToImage($image, 370, $y, '₱' . $data['subtotal'], $black, 11, $font_path, $use_ttf);
    } else {
        addTextToImage($image, 370, $y, 'PHP ' . $data['subtotal'], $black, 11, $font_path, $use_ttf);
    }
    $y += 25;
    
    addTextToImage($image, 30, $y, "Service Charge (2%):", $black, 11, $font_path, $use_ttf);
    if ($use_ttf) {
        addTextToImage($image, 370, $y, '₱' . $data['service_charge'], $black, 11, $font_path, $use_ttf);
    } else {
        addTextToImage($image, 370, $y, 'PHP ' . $data['service_charge'], $black, 11, $font_path, $use_ttf);
    }
    $y += 25;
    
    addTextToImage($image, 30, $y, "VAT (12%):", $black, 11, $font_path, $use_ttf);
    if ($use_ttf) {
        addTextToImage($image, 370, $y, '₱' . $data['vat'], $black, 11, $font_path, $use_ttf);
    } else {
        addTextToImage($image, 370, $y, 'PHP ' . $data['vat'], $black, 11, $font_path, $use_ttf);
    }
    $y += 25;
    
    imageline($image, 20, $y, $width-20, $y, $black);
    $y += 25;
    
    // Total
    addTextToImage($image, 30, $y, "TOTAL:", $black, 13, $font_path, $use_ttf);
    if ($use_ttf) {
        addTextToImage($image, 370, $y, '₱' . $data['total'], $dark_red, 13, $font_path, $use_ttf);
    } else {
        addTextToImage($image, 370, $y, 'PHP ' . $data['total'], $dark_red, 13, $font_path, $use_ttf);
    }
    $y += 35;
    
    // Payment info
    addTextToImage($image, 30, $y, "Payment Method:", $black, 11, $font_path, $use_ttf);
    addTextToImage($image, 370, $y, strtoupper($data['payment_method']), $green, 11, $font_path, $use_ttf);
    $y += 25;
    
    addTextToImage($image, 30, $y, "Status:", $black, 11, $font_path, $use_ttf);
    addTextToImage($image, 370, $y, "PAID", $green, 11, $font_path, $use_ttf);
    $y += 35;
    
    // Footer
    addTextToImage($image, 150, $y, "THANK YOU!", $gold, 14, $font_path, $use_ttf);
    $y += 30;
    addTextToImage($image, 170, $y, "Please come again", $dark_gray, 9, $font_path, $use_ttf);
    $y += 25;
    
    // Current timestamp - Use Manila time
    $manila_time = date('Y-m-d h:i A', strtotime($data['timestamp']));
    addTextToImage($image, 160, $y, "Printed: " . $manila_time, $gray, 9, $font_path, $use_ttf);
    
    // Add watermark at bottom
    $watermark_color = imagecolorallocatealpha($image, 200, 200, 200, 60);
    addTextToImage($image, 80, $height-40, "SOLIERA HOTEL & RESTAURANT", $watermark_color, 14, $font_path, $use_ttf);
    
    // Output image to buffer
    ob_start();
    $result = imagejpeg($image, null, 90);
    if (!$result) {
        ob_end_clean();
        imagedestroy($image);
        error_log("Failed to output JPEG");
        return '';
    }
    
    $image_data = ob_get_clean();
    imagedestroy($image);
    
    if (empty($image_data)) {
        error_log("Empty image data");
        return '';
    }
    
    error_log("SUCCESS: JPEG generated - Size: " . strlen($image_data) . " bytes");
    error_log("Font used: " . ($use_ttf ? "TrueType" : "Basic GD"));
    return $image_data;
}

// =======================
// Helper function to find TrueType font
// =======================
function findTrueTypeFont() {
    $possible_fonts = [
        '/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf',
        '/usr/share/fonts/truetype/liberation/LiberationSans-Regular.ttf',
        '/usr/share/fonts/truetype/msttcorefonts/arial.ttf',
        'C:/Windows/Fonts/arial.ttf',
        'C:/Windows/Fonts/tahoma.ttf',
        '/Library/Fonts/Arial.ttf',
        '/System/Library/Fonts/Helvetica.ttf',
        'arial.ttf',
        'fonts/arial.ttf',
        '../../fonts/arial.ttf',
        'Arial.ttf'
    ];
    
    foreach ($possible_fonts as $font) {
        if (file_exists($font)) {
            return $font;
        }
    }
    
    return '';
}

// =======================
// Helper function to add text with or without TrueType font
// =======================
function addTextToImage($image, $x, $y, $text, $color, $size, $font_path = null, $use_ttf = false) {
    if ($use_ttf && $font_path) {
        // Adjust y position for TrueType fonts (they use baseline)
        $adjusted_y = $y + $size;
        imagettftext($image, $size, 0, $x, $adjusted_y, $color, $font_path, $text);
    } else {
        // Convert size from TTF to GD font size (approximation)
        $gd_size = max(1, min(5, floor($size / 3)));
        imagestring($image, $gd_size, $x, $y - 8, $text, $color);
    }
}

// =======================
// Fallback function if main function fails
// =======================
function generateSimpleTestImage() {
    $image = imagecreatetruecolor(400, 200);
    $white = imagecolorallocate($image, 255, 255, 255);
    $black = imagecolorallocate($image, 0, 0, 0);
    $red = imagecolorallocate($image, 255, 0, 0);
    $gold = imagecolorallocate($image, 255, 215, 0);
    
    imagefilledrectangle($image, 0, 0, 400, 200, $white);
    imagerectangle($image, 0, 0, 399, 199, $black);
    
    imagestring($image, 5, 120, 20, "SOLIERA RECEIPT", $red);
    imagestring($image, 3, 100, 60, "Order processed successfully", $black);
    
    // Try to find font for peso sign
    $font_path = findTrueTypeFont();
    if ($font_path) {
        $ttf_size = 12;
        $adjusted_y = 100 + $ttf_size;
        imagettftext($image, $ttf_size, 0, 120, $adjusted_y, $black, $font_path, "Total: ₱" . number_format(0, 2));
    } else {
        imagestring($image, 3, 120, 90, "Total: PHP " . number_format(0, 2), $black);
    }
    
    // Use Manila time
    $manila_time = date('Y-m-d h:i A');
    imagestring($image, 2, 140, 120, $manila_time, $black);
    imagestring($image, 4, 130, 150, "THANK YOU!", $gold);
    
    ob_start();
    imagejpeg($image, null, 90);
    $data = ob_get_clean();
    imagedestroy($image);
    
    return $data;
}

// =======================
// Function to generate receipt HTML (for preview)
// =======================
function generateReceiptHTML($data) {
    ob_start();
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <title>Receipt - <?php echo $data['order_id']; ?></title>
        <style>
            * { margin: 0; padding: 0; box-sizing: border-box; }
            body { 
                font-family: 'Courier New', monospace; 
                font-size: 12px; 
                line-height: 1.4; 
                padding: 10px; 
                background: #f5f5f5; 
            }
            .receipt { 
                width: 320px; 
                margin: 0 auto; 
                background: white; 
                padding: 15px; 
                border: 1px solid #ddd; 
                box-shadow: 0 0 10px rgba(0,0,0,0.1); 
            }
            .header { 
                text-align: center; 
                margin-bottom: 15px; 
                border-bottom: 1px dashed #000; 
                padding-bottom: 10px; 
            }
            .restaurant-name { 
                font-size: 16px; 
                font-weight: bold; 
                margin-bottom: 5px; 
                color: #333;
            }
            .restaurant-details { 
                font-size: 10px; 
                margin-bottom: 5px; 
                color: #666;
            }
            .order-info { 
                margin-bottom: 15px; 
            }
            .info-row { 
                display: flex; 
                justify-content: space-between; 
                margin-bottom: 3px; 
            }
            .info-label { 
                font-weight: bold; 
            }
            .items-table { 
                width: 100%; 
                border-collapse: collapse; 
                margin-bottom: 15px; 
            }
            .items-table th { 
                text-align: left; 
                border-bottom: 1px dashed #000; 
                padding: 5px 0; 
            }
            .items-table td { 
                padding: 3px 0; 
            }
            .items-table .item-name { 
                width: 60%; 
            }
            .items-table .item-qty { 
                width: 15%; 
                text-align: center; 
            }
            .items-table .item-price { 
                width: 25%; 
                text-align: right; 
            }
            .totals { 
                margin-bottom: 15px; 
            }
            .total-row { 
                display: flex; 
                justify-content: space-between; 
                margin-bottom: 3px; 
            }
            .total-row.total { 
                font-weight: bold; 
                border-top: 1px dashed #000; 
                padding-top: 5px; 
                margin-top: 5px; 
                font-size: 14px;
            }
            .payment-info { 
                margin-bottom: 15px; 
            }
            .footer { 
                text-align: center; 
                border-top: 1px dashed #000; 
                padding-top: 10px; 
                font-size: 10px; 
            }
            .thank-you { 
                font-weight: bold; 
                margin-bottom: 5px; 
                color: #D4AF37;
                font-size: 14px;
            }
            .cut-line { 
                text-align: center; 
                margin: 10px 0; 
            }
            .cut-line span { 
                display: inline-block; 
                border-top: 1px dashed #000; 
                width: 100%; 
            }
            .auto-download-notice { 
                background: #d4edda; 
                color: #155724; 
                padding: 10px; 
                border-radius: 4px; 
                margin-bottom: 15px; 
                text-align: center; 
                font-weight: bold; 
            }
            .timestamp {
                font-size: 9px;
                color: #666;
                text-align: center;
                margin-top: 5px;
            }
            .timezone-note {
                font-size: 8px;
                color: #999;
                text-align: center;
                margin-top: 2px;
            }
        </style>
    </head>
    <body>
        <div class="receipt">
            <div class="auto-download-notice">
                ✓ Receipt auto-downloaded
            </div>
            
            <div class="header">
                <div class="restaurant-name"><?php echo htmlspecialchars($data['restaurant_name']); ?></div>
                <div class="restaurant-details"><?php echo htmlspecialchars($data['restaurant_address']); ?></div>
                <div class="restaurant-details"><?php echo htmlspecialchars($data['restaurant_contact']); ?></div>
                <div class="timezone-note">(Manila/Beijing Time UTC+8)</div>
            </div>
            
            <div class="order-info">
                <div class="info-row">
                    <span class="info-label">Order #:</span>
                    <span><?php echo htmlspecialchars($data['order_id']); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Invoice #:</span>
                    <span><?php echo htmlspecialchars($data['invoice_number']); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Date:</span>
                    <span><?php echo htmlspecialchars($data['date']); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Time:</span>
                    <span><?php echo htmlspecialchars($data['time']); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Table:</span>
                    <span><?php echo htmlspecialchars($data['table']); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Customer:</span>
                    <span><?php echo htmlspecialchars($data['customer']); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Placed By:</span>
                    <span><?php echo htmlspecialchars($data['placed_by']); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Served By:</span>
                    <span><?php echo htmlspecialchars($data['served_by']); ?></span>
                </div>
            </div>
            
            <table class="items-table">
                <thead>
                    <tr>
                        <th class="item-name">Item</th>
                        <th class="item-qty">Qty</th>
                        <th class="item-price">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($data['items'] as $item): ?>
                    <tr>
                        <td class="item-name"><?php echo htmlspecialchars(substr($item['name'] ?? $item['item_name'] ?? 'Item', 0, 25)); ?></td>
                        <td class="item-qty"><?php echo intval($item['quantity'] ?? $item['qty'] ?? 1); ?></td>
                        <td class="item-price">₱<?php echo number_format(floatval($item['price'] ?? $item['unit_price'] ?? 0) * intval($item['quantity'] ?? $item['qty'] ?? 1), 2); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
            <div class="totals">
                <div class="total-row">
                    <span>Subtotal:</span>
                    <span>₱<?php echo $data['subtotal']; ?></span>
                </div>
                <div class="total-row">
                    <span>Service Charge (2%):</span>
                    <span>₱<?php echo $data['service_charge']; ?></span>
                </div>
                <div class="total-row">
                    <span>VAT (12%):</span>
                    <span>₱<?php echo $data['vat']; ?></span>
                </div>
                <div class="total-row total">
                    <span>TOTAL:</span>
                    <span>₱<?php echo $data['total']; ?></span>
                </div>
            </div>
            
            <div class="payment-info">
                <div class="info-row">
                    <span class="info-label">Payment Method:</span>
                    <span><?php echo htmlspecialchars($data['payment_method']); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Payment Status:</span>
                    <span><?php echo htmlspecialchars($data['payment_status']); ?></span>
                </div>
            </div>
            
            <?php if (!empty($data['notes'])): ?>
            <div class="order-info">
                <div class="info-row">
                    <span class="info-label">Notes:</span>
                    <span><?php echo htmlspecialchars($data['notes']); ?></span>
                </div>
            </div>
            <?php endif; ?>
            
            <div class="cut-line"><span></span></div>
            
            <div class="footer">
                <div class="thank-you"><?php echo htmlspecialchars($data['thank_you_message']); ?></div>
                <div>Items: <?php echo $data['items_count']; ?></div>
                <div class="timestamp">Printed: <?php echo $data['timestamp']; ?> (UTC+8)</div>
                <div>Powered by SOLIERA HOTEL & RESTAURANT POS</div>
            </div>
            
        </div>
        
        <script>
        function downloadReceipt() {
            // This would be handled by the frontend using the receipt_jpeg URL from API response
            alert('Use the receipt_jpeg URL from the API response to download the JPEG image.');
        }
        </script>
    </body>
    </html>
    <?php
    return ob_get_clean();
}