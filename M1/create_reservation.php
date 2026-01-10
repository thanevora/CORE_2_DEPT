<?php
header('Content-Type: application/json');
session_start();

/**
 * create_reservation.php
 * - Inserts new reservation into rest_m1_trs.reservations
 * - Inserts notification into rest_m1_trs.notification_m1
 * - Inserts audit record into rest_core_2_usm.dept_audit_transc
 * - Uses transactions and UTC+8 timestamps (Beijing/Manila)
 */

/* -----------------------------
   Include main connection
   ----------------------------- */
$connectionPath = __DIR__ . '/../main_connection.php';
if (!file_exists($connectionPath)) {
    echo json_encode(['success' => false, 'error' => 'Connection file not found.']);
    exit;
}
include($connectionPath);

/* DB handles */
$conn_reservation = $connections['rest_m1_trs'] ?? null;    // reservations + notification_m1
$conn_core_audit  = $connections['rest_core_2_usm'] ?? null; // department_accounts + audit

if (!$conn_reservation || !$conn_core_audit) {
    echo json_encode(['success' => false, 'error' => 'Required database connections missing.']);
    exit;
}

/* -----------------------------
   Validate request
   ----------------------------- */
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
    exit;
}

/* Collect POST values */
$name             = trim($_POST['name'] ?? '');
$contact          = trim($_POST['contact'] ?? '');
$reservation_date = trim($_POST['reservation_date'] ?? '');
$start_time       = trim($_POST['start_time'] ?? '');
$end_time         = trim($_POST['end_time'] ?? '');
$size             = (int) ($_POST['size'] ?? 0);
$table_id         = (int) ($_POST['table_id'] ?? 0);
$type             = trim($_POST['type'] ?? '');
$request          = trim($_POST['request'] ?? '');
$note             = trim($_POST['note'] ?? '');
$email            = trim($_POST['email'] ?? '');
$amount           = floatval($_POST['amount'] ?? 0);
$MOP              = trim($_POST['MOP'] ?? '');
$payment_type     = trim($_POST['payment_type'] ?? 'full');

/* -----------------------------
   Validation rules
   ----------------------------- */
if (empty($name) || empty($contact) || empty($reservation_date) || empty($start_time)) {
    echo json_encode(['success' => false, 'error' => 'Missing required fields.']);
    exit;
}

if (!preg_match('/^\d{11}$/', $contact)) {
    echo json_encode(['success' => false, 'error' => 'Contact number must be exactly 11 digits.']);
    exit;
}

if ($size < 1 || $size > 10) {
    echo json_encode(['success' => false, 'error' => 'Party size must be between 1 and 10.']);
    exit;
}

if ($amount <= 0) {
    echo json_encode(['success' => false, 'error' => 'Invalid amount.']);
    exit;
}

$allowed_payment_methods = ['gcash', 'maya', 'card', 'cash'];
if (!in_array(strtolower($MOP), $allowed_payment_methods, true)) {
    echo json_encode(['success' => false, 'error' => 'Invalid payment method.']);
    exit;
}

$allowed_payment_types = ['full', 'downpayment'];
if (!in_array(strtolower($payment_type), $allowed_payment_types, true)) {
    echo json_encode(['success' => false, 'error' => 'Invalid payment type.']);
    exit;
}

$payment_status = ($payment_type === 'full') ? 'paid' : 'partial';

/* -----------------------------
   Fetch employee info
   ----------------------------- */
$employee_id   = $_SESSION['employee_id'] ?? 0;
$employee_name = $_SESSION['employee_name'] ?? ($_SESSION['Name'] ?? '');
$role          = $_SESSION['Role'] ?? '';

if (empty($employee_id) || empty($employee_name)) {
    if (!empty($_SESSION['email'])) {
        $deptFetch = $conn_core_audit->prepare("SELECT employee_id, employee_name, role FROM department_accounts WHERE email = ? LIMIT 1");
        $deptFetch->bind_param("s", $_SESSION['email']);
    } elseif (!empty($employee_name)) {
        $deptFetch = $conn_core_audit->prepare("SELECT employee_id, employee_name, role FROM department_accounts WHERE employee_name = ? LIMIT 1");
        $deptFetch->bind_param("s", $employee_name);
    }

    if (isset($deptFetch) && $deptFetch && $deptFetch->execute()) {
        $dRes = $deptFetch->get_result();
        if ($dRes && $dRes->num_rows > 0) {
            $dRow = $dRes->fetch_assoc();
            $employee_id   = (int)$dRow['employee_id'];
            $employee_name = $dRow['employee_name'];
            $role          = $dRow['role'];
        }
        $deptFetch->close();
    }
}

$employee_id   = (int) ($employee_id ?: 0);
$employee_name = trim($employee_name ?: 'System');
$role          = trim($role ?: 'Admin');

/* -----------------------------
   Begin Transactions
   ----------------------------- */
$conn_reservation->begin_transaction();
$conn_core_audit->begin_transaction();

try {
    /* -----------------------------
       Insert reservation
       ----------------------------- */
    $stmt = $conn_reservation->prepare("
        INSERT INTO reservations 
        (name, contact, reservation_date, start_time, end_time, size, table_id, type, request, note, status, created_at, email, amount, MOP, payment_type, payment_status)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Queue', NOW(), ?, ?, ?, ?, ?)
    ");
    
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn_reservation->error);
    }

    $stmt->bind_param(
        "sssssssssssssss",
        $name,
        $contact,
        $reservation_date,
        $start_time,
        $end_time,
        $size,
        $table_id,
        $type,
        $request,
        $note,
        $email,
        $amount,
        $MOP,
        $payment_type,
        $payment_status
    );

    if (!$stmt->execute()) {
        throw new Exception("Reservation insert failed: " . $stmt->error);
    }

    $reservation_id = $stmt->insert_id;
    $stmt->close();

    /* -----------------------------
       Insert notification (CN time)
       ----------------------------- */
    $notification_title   = "New Reservation Created";
    $notification_message = "Reservation #{$reservation_id} created for {$name}.";
    $notification_status  = "Unread";
    $module               = "Table Reservation & Seating";

    $dateCN = new DateTime('now', new DateTimeZone('Asia/Shanghai'));
    $date_sent = $dateCN->format('Y-m-d H:i:s');

    $notifQuery = $conn_reservation->prepare("
        INSERT INTO notification_m1 
        (employee_id, employee_name, role, title, message, status, date_sent, module)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");
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
        throw new Exception("Notification insert failed: " . $notifQuery->error);
    }
    $notifQuery->close();

    /* -----------------------------
       Insert audit log (PH time)
       ----------------------------- */
    $modules_cover = "Table Reservation";
    $action        = "Reservation Created";
    $activity      = "Created new reservation #{$reservation_id} for {$name} (₱" . number_format($amount, 2) . ")";
    $auditDatePH   = new DateTime('now', new DateTimeZone('Asia/Manila'));
    $audit_date    = $auditDatePH->format('Y-m-d H:i:s');

    $dept_id   = $_SESSION['Dept_id'] ?? 0;
    $dept_name = $_SESSION['dept_name'] ?? 'Unknown';

    if ($employee_id && ($dept_id == 0 || $dept_name === 'Unknown')) {
        $dq = $conn_core_audit->prepare("SELECT Dept_id, dept_name FROM department_accounts WHERE employee_id = ? LIMIT 1");
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

    $auditStmt = $conn_core_audit->prepare("
        INSERT INTO dept_audit_transc 
        (dept_id, dept_name, modules_cover, action, activity, employee_name, employee_id, role, date)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
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
        throw new Exception("Audit insert failed: " . $auditStmt->error);
    }
    $auditStmt->close();

    /* -----------------------------
       Commit
       ----------------------------- */
    $conn_reservation->commit();
    $conn_core_audit->commit();

    echo json_encode([
        'success' => true,
        'message' => "Reservation #{$reservation_id} created successfully for {$name} on {$reservation_date}.",
        'reservation_id' => $reservation_id,
        'details' => [
            'name' => $name,
            'date' => $reservation_date,
            'start_time' => $start_time,
            'table_id' => $table_id,
            'size' => $size
        ]
    ]);
    exit;

} catch (Exception $e) {
    $conn_reservation->rollback();
    $conn_core_audit->rollback();
    error_log("Reservation creation error: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Internal error: ' . $e->getMessage()]);
    exit;
}
?>
