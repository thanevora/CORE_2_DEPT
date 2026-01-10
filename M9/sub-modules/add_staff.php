<?php
include("../../main_connection.php");

$db_name = "rest_m9_wait_staff";
if (!isset($connections[$db_name])) {
    die("Database connection not found");
}

// Check required fields
$required = ['full_name', 'shift', 'contact_number', 'email', 'status', 'hire_date', 'position'];
foreach ($required as $field) {
    if (empty($_POST[$field])) {
        die("Field '$field' is required.");
    }
}

// Collect data safely
$full_name = trim($_POST['full_name']);
$shift = trim($_POST['shift']);
$contact_number = trim($_POST['contact_number']);
$email = trim($_POST['email']);
$status = trim($_POST['status']);
$hire_date = trim($_POST['hire_date']);
$position = trim($_POST['position']);
$last_shift_date = !empty($_POST['last_shift_date']) ? trim($_POST['last_shift_date']) : null;
$notes = !empty($_POST['notes']) ? trim($_POST['notes']) : null;
$tables_assigned = isset($_POST['tables_assigned']) ? implode(',', $_POST['tables_assigned']) : null;

try {
    $stmt = $connections[$db_name]->prepare("
        INSERT INTO wait_staff 
        (full_name, shift, contact_number, email, status, hire_date, position, tables_assigned, last_shift_date, notes)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([$full_name, $shift, $contact_number, $email, $status, $hire_date, $position, $tables_assigned, $last_shift_date, $notes]);

    // Redirect after success
    header("Location: ../wait_main.php");
    exit;

} catch (PDOException $e) {
    // Redirect to an error page or show message
    header("Location: ../wait_main.php?error=" . urlencode($e->getMessage()));
    exit;
}
