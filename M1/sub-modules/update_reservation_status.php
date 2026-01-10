<?php
header('Content-Type: application/json');
session_start();

/**
 * update_reservation_status.php
 * - Updates reservation status (Confirmed / Denied / For Compliance)
 * - Sends email to customer
 * - Inserts notification into rest_m1_trs.notification_m1
 * - Inserts audit record into rest_core_2_usm.dept_audit_transc
 * - Uses transactions and rolls back on failure
 */

/* -----------------------------
   include main connection
   ----------------------------- */
$connectionPath = __DIR__ . '/../../main_connection.php';
if (!file_exists($connectionPath)) {
    echo json_encode(['success' => false, 'error' => 'Connection file not found: ' . $connectionPath]);
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
   validate request
   ----------------------------- */
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
    exit;
}

$reservation_id  = isset($_POST['reservation_id']) ? (int) $_POST['reservation_id'] : null;
$new_status      = isset($_POST['status']) ? trim($_POST['status']) : null;
$compliance_note = isset($_POST['compliance']) ? trim($_POST['compliance']) : null;

if (!$reservation_id || !$new_status) {
    echo json_encode(['success' => false, 'error' => 'Missing parameters']);
    exit;
}

$allowed_statuses = ['Confirmed', 'Denied', 'For Compliance'];
if (!in_array($new_status, $allowed_statuses, true)) {
    echo json_encode(['success' => false, 'error' => 'Invalid status value']);
    exit;
}

/* -----------------------------
   fetch reservation details
   ----------------------------- */
$check_stmt = $conn_reservation->prepare("
    SELECT reservation_id, name, contact, reservation_date, start_time, end_time, size, status, type, email, compliance, mop
    FROM reservations
    WHERE reservation_id = ?
    LIMIT 1
");
if (!$check_stmt) {
    echo json_encode(['success' => false, 'error' => 'Prepare failed: ' . $conn_reservation->error]);
    exit;
}
$check_stmt->bind_param("i", $reservation_id);
$check_stmt->execute();
$result = $check_stmt->get_result();
$current = $result->fetch_assoc();
$check_stmt->close();

if (!$current) {
    echo json_encode(['success' => false, 'error' => 'Reservation not found']);
    exit;
}

/* Prevent modification if already finalized */
if (in_array($current['status'], ['Confirmed', 'Denied'], true) && $new_status !== 'For Compliance') {
    echo json_encode(['success' => false, 'error' => 'This reservation is already finalized and cannot be changed.']);
    exit;
}

/* -----------------------------
   Fetch employee info (from session or dept table)
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

/* Sanity defaults */
$employee_id   = (int) ($employee_id ?: 0);
$employee_name = trim($employee_name ?: 'System');
$role          = trim($role ?: 'Admin');

/* -----------------------------
   Begin transactions
   ----------------------------- */
$conn_reservation->begin_transaction();
$conn_core_audit->begin_transaction();

try {
    /* -----------------------------
       Update reservation status
       ----------------------------- */
    if ($new_status === 'For Compliance') {
        $stmt = $conn_reservation->prepare("UPDATE reservations SET status = ?, compliance = ? WHERE reservation_id = ?");
        $stmt->bind_param("ssi", $new_status, $compliance_note, $reservation_id);
    } else {
        $stmt = $conn_reservation->prepare("UPDATE reservations SET status = ? WHERE reservation_id = ?");
        $stmt->bind_param("si", $new_status, $reservation_id);
    }
    if (!$stmt->execute()) throw new Exception("Database error (update): " . $stmt->error);
    $stmt->close();

    /* -----------------------------
       Send email (non-fatal)
       ----------------------------- */
    $email = trim($current['email'] ?? '');
    $recipientName = trim($current['name'] ?? 'Customer');
    $emailSent = false;
    if (!empty($email) && filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $emailSent = sendStatusEmail($email, $recipientName, $new_status, $compliance_note, $current);
    }

    /* -----------------------------
       Insert notification (TRS DB) - PH time
       ----------------------------- */
    $notification_title   = "Reservation Status Updated";
    $notification_message = "Reservation #{$reservation_id} was updated to {$new_status}.";
    $notification_status  = "Unread";
    $module               = "Table Reservation & Seating";

   $dateCN = new DateTime('now', new DateTimeZone('Asia/Shanghai'));
$date_sent = $dateCN->format('Y-m-d H:i:s');


    $notifQuery = $conn_reservation->prepare("
        INSERT INTO notification_m1 (employee_id, employee_name, role, title, message, status, date_sent, module)
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
    if (!$notifQuery->execute()) throw new Exception("Execution failed for notification: " . $notifQuery->error);
    $notifQuery->close();

    /* -----------------------------
       Insert audit log (USM DB) - PH time
       ----------------------------- */
    $modules_cover = "Table reservation";
    $action        = "Status Updated";
    $activity      = "Updated reservation #{$reservation_id} to {$new_status}";
    if (!empty($compliance_note)) $activity .= " (Note: {$compliance_note})";

    $auditDatePH = new DateTime('now', new DateTimeZone('Asia/Manila'));
    $audit_date  = $auditDatePH->format('Y-m-d H:i:s');

    $dept_id   = $_SESSION['Dept_id'] ?? 0;
    $dept_name = $_SESSION['dept_name'] ?? 'Unknown';

    if ($employee_id && ($dept_id == 0 || $dept_name === 'Unknown')) {
        $dq = $conn_core_audit->prepare("SELECT Dept_id, dept_name FROM department_accounts WHERE employee_id = ? LIMIT 1");
        $dq->bind_param("i", $employee_id);
        if ($dq->execute()) {
            $dr = $dq->get_result();
            if ($dr && $dr->num_rows > 0) {
                $drRow = $dr->fetch_assoc();
                $dept_id = $drRow['Dept_id'] ?? $dept_id;
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
    if (!$auditStmt->execute()) throw new Exception("Execution failed for audit: " . $auditStmt->error);
    $auditStmt->close();

    /* -----------------------------
       Commit transactions
       ----------------------------- */
    $conn_reservation->commit();
    $conn_core_audit->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Reservation status updated successfully',
        'email_sent' => $emailSent
    ]);
    exit;

} catch (Exception $e) {
    $conn_reservation->rollback();
    $conn_core_audit->rollback();
    error_log("Reservation update error: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Internal error: ' . $e->getMessage()]);
    exit;
}

/* -----------------------------
   Helper: sendStatusEmail
   ----------------------------- */
function sendStatusEmail($email, $name, $status, $compliance_note, $details) {
    require_once __DIR__ . '/../../PHPMailer/PHPMailerAutoload.php';
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'soliera.restaurant@gmail.com';
        $mail->Password = 'rpyo ncni ulhv lhpx'; // app password
        $mail->SMTPSecure = 'tls';
        $mail->Port = 587;

        $mail->setFrom('soliera.restaurant@gmail.com', 'Soliera Hotel & Restaurant');
        $mail->addAddress($email, $name);
        $mail->isHTML(true);

        $detailsHTML = "
            <table style='border-collapse:collapse;width:100%;font-size:14px;'>
                <tr><td><strong>Reservation ID:</strong></td><td>{$details['reservation_id']}</td></tr>
                <tr><td><strong>Name:</strong></td><td>{$details['name']}</td></tr>
                <tr><td><strong>Contact:</strong></td><td>{$details['contact']}</td></tr>
                <tr><td><strong>Date:</strong></td><td>{$details['reservation_date']}</td></tr>
                <tr><td><strong>Start Time:</strong></td><td>{$details['start_time']}</td></tr>
                <tr><td><strong>End Time:</strong></td><td>{$details['end_time']}</td></tr>
                <tr><td><strong>Size:</strong></td><td>{$details['size']}</td></tr>
                <tr><td><strong>Type:</strong></td><td>{$details['type']}</td></tr>
                <tr><td><strong>MOP:</strong></td><td>{$details['mop']}</td></tr>
                <tr><td><strong>Status:</strong></td><td><strong>{$status}</strong></td></tr>" .
                ($status === 'For Compliance' ? "<tr><td><strong>Compliance Note:</strong></td><td>{$compliance_note}</td></tr>" : "")
            . "</table>";

        $mail->Subject = "Reservation Status: $status";
        $mail->Body = "
            <div style='font-family:Arial,sans-serif;color:#333;'>
                <h2 style='color:#F7B32B;'>Soliera Hotel & Restaurant</h2>
                <p>Dear <strong>{$name}</strong>,</p>
                <p>Your reservation status has been updated to <strong>{$status}</strong>.</p>
                {$detailsHTML}
                <br><hr>
                <p style='font-size:12px;color:#555;'>📞 Hotline: +63-900-123-4567 | 📧 support@soliera.com<br>
                <em>This is an automated message. Please do not reply directly to this email.</em></p>
            </div>";

        return $mail->send();
    } catch (Exception $e) {
        error_log("Mail error: " . $mail->ErrorInfo);
        return false;
    }
}
?>
