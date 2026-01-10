<?php
session_start();
include("../../main_connection.php");

$db_name = "rest_m11_event";
if (!isset($connections[$db_name])) {
    die(json_encode(['success' => false, 'message' => "Connection not found"]));
}

$conn = $connections[$db_name];
header('Content-Type: application/json');

// Handle CORS if needed
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

// Get the action from GET or POST
$action = $_GET['action'] ?? '';
if (empty($action)) {
    // Try to get from POST
    $input = file_get_contents('php://input');
    if (!empty($input)) {
        $data = json_decode($input, true);
        $action = $data['action'] ?? '';
    }
}

if (empty($action)) {
    $action = $_POST['action'] ?? '';
}

switch ($action) {
    case 'get_reservations':
        getReservations($conn);
        break;
    case 'get_reservation':
        getReservation($conn);
        break;
    case 'update_status':
        updateStatus($conn);
        break;
    case 'for_compliance':
        forCompliance($conn);
        break;
    case 'delete_reservation':
        deleteReservation($conn);
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action: ' . $action]);
        exit;
}

function getReservations($conn) {
    $page = $_GET['page'] ?? 1;
    $limit = 15;
    $offset = ($page - 1) * $limit;
    
    $search = $_GET['search'] ?? '';
    $status = $_GET['status'] ?? '';
    $date_from = $_GET['date_from'] ?? '';
    $date_to = $_GET['date_to'] ?? '';
    
    // Build query
    $sql = "SELECT * FROM event_reservations WHERE 1=1";
    $count_sql = "SELECT COUNT(*) as total FROM event_reservations WHERE 1=1";
    $params = [];
    $types = "";
    
    if ($search) {
        $sql .= " AND (customer_name LIKE ? OR event_name LIKE ? OR customer_email LIKE ?)";
        $count_sql .= " AND (customer_name LIKE ? OR event_name LIKE ? OR customer_email LIKE ?)";
        $search_term = "%$search%";
        $params[] = $search_term;
        $params[] = $search_term;
        $params[] = $search_term;
        $types .= "sss";
    }
    
    if ($status) {
        $sql .= " AND reservation_status = ?";
        $count_sql .= " AND reservation_status = ?";
        $params[] = $status;
        $types .= "s";
    }
    
    if ($date_from) {
        $sql .= " AND event_date >= ?";
        $count_sql .= " AND event_date >= ?";
        $params[] = $date_from;
        $types .= "s";
    }
    
    if ($date_to) {
        $sql .= " AND event_date <= ?";
        $count_sql .= " AND event_date <= ?";
        $params[] = $date_to;
        $types .= "s";
    }
    
    // For count query
    $count_params = $params;
    $count_types = $types;
    
    // For data query
    $sql .= " ORDER BY created_at DESC LIMIT ? OFFSET ?";
    $params[] = $limit;
    $params[] = $offset;
    $types .= "ii";
    
    // Get total count
    $stmt = $conn->prepare($count_sql);
    if (!empty($count_types)) {
        $stmt->bind_param($count_types, ...$count_params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    $total = $result->fetch_assoc()['total'] ?? 0;
    $stmt->close();
    
    // Get data
    $stmt = $conn->prepare($sql);
    if (!empty($types)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    
    $reservations = [];
    while ($row = $result->fetch_assoc()) {
        // Add flag to show if editable
        $row['is_editable'] = isReservationEditable($row['reservation_status']);
        $reservations[] = $row;
    }
    
    $total_pages = ceil($total / $limit);
    
    echo json_encode([
        'success' => true,
        'data' => $reservations,
        'pagination' => [
            'current_page' => (int)$page,
            'total_pages' => $total_pages,
            'total_items' => $total,
            'limit' => $limit
        ]
    ]);
    exit;
}

function getReservation($conn) {
    $id = $_GET['id'] ?? 0;
    
    if (!$id) {
        echo json_encode(['success' => false, 'message' => 'Reservation ID is required']);
        exit;
    }
    
    $stmt = $conn->prepare("SELECT * FROM event_reservations WHERE reservation_id = ?");
    if (!$stmt) {
        echo json_encode(['success' => false, 'message' => 'Prepare failed: ' . $conn->error]);
        exit;
    }
    
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($reservation = $result->fetch_assoc()) {
        // Add image path if exists
        if (!empty($reservation['image_url'])) {
            $reservation['image_path'] = '../M6/images/' . $reservation['image_url'];
        }
        // Add flag to show if editable
        $reservation['is_editable'] = isReservationEditable($reservation['reservation_status']);
        echo json_encode(['success' => true, 'data' => $reservation]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Reservation not found']);
    }
    exit;
}

function updateStatus($conn) {
    // Get JSON input
    $input = file_get_contents('php://input');
    if (empty($input)) {
        echo json_encode(['success' => false, 'message' => 'No data received']);
        exit;
    }
    
    $data = json_decode($input, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        echo json_encode(['success' => false, 'message' => 'Invalid JSON: ' . json_last_error_msg()]);
        exit;
    }
    
    $id = $data['id'] ?? 0;
    $status = $data['status'] ?? '';
    $notes = $data['notes'] ?? '';
    
    if (!$id || !$status) {
        echo json_encode([
            'success' => false, 
            'message' => 'Invalid data',
            'debug' => ['id' => $id, 'status' => $status, 'notes' => $notes]
        ]);
        exit;
    }
    
    // Check current reservation status first
    $checkStmt = $conn->prepare("SELECT reservation_status FROM event_reservations WHERE reservation_id = ?");
    $checkStmt->bind_param("i", $id);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();
    $currentReservation = $checkResult->fetch_assoc();
    $checkStmt->close();
    
    if (!$currentReservation) {
        echo json_encode(['success' => false, 'message' => 'Reservation not found']);
        exit;
    }
    
    $currentStatus = $currentReservation['reservation_status'];
    
    // Check if reservation can be modified (one-time only rule)
    if (!isReservationEditable($currentStatus)) {
        echo json_encode([
            'success' => false, 
            'message' => 'This reservation can no longer be modified. Status is already set to: ' . $currentStatus,
            'current_status' => $currentStatus
        ]);
        exit;
    }
    
    // Check if "notes" column exists, if not, add it
    $checkColumn = $conn->query("SHOW COLUMNS FROM event_reservations LIKE 'notes'");
    if ($checkColumn->num_rows == 0) {
        // Add notes column if it doesn't exist
        $conn->query("ALTER TABLE event_reservations ADD COLUMN notes TEXT NULL DEFAULT NULL");
    }
    
    // Update reservation status
    $sql = "UPDATE event_reservations SET reservation_status = ?, notes = ? WHERE reservation_id = ?";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        echo json_encode(['success' => false, 'message' => 'Prepare failed: ' . $conn->error]);
        exit;
    }
    
    // If status is changing to "For Compliance", append to notes
    if ($status === 'For Compliance') {
        $timestamp = date('Y-m-d H:i:s');
        $complianceNote = "\n[FOR COMPLIANCE - " . $timestamp . "]: " . $notes;
        $finalNotes = ($currentReservation['notes'] ?? '') . $complianceNote;
    } else {
        $finalNotes = $notes;
    }
    
    $stmt->bind_param("ssi", $status, $finalNotes, $id);
    
    if ($stmt->execute()) {
        // If status is "Confirmed", also update payment status to reflect 20% downpayment
        if ($status === 'Confirmed') {
            $updatePaymentStmt = $conn->prepare("UPDATE event_reservations SET payment_status = 'Partial Payment' WHERE reservation_id = ?");
            $updatePaymentStmt->bind_param("i", $id);
            $updatePaymentStmt->execute();
            $updatePaymentStmt->close();
        }
        
        echo json_encode([
            'success' => true, 
            'message' => 'Status updated successfully',
            'new_status' => $status,
            'is_editable' => isReservationEditable($status)
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update status: ' . $stmt->error]);
    }
    exit;
}

function forCompliance($conn) {
    // Get JSON input
    $input = file_get_contents('php://input');
    if (empty($input)) {
        echo json_encode(['success' => false, 'message' => 'No data received']);
        exit;
    }
    
    $data = json_decode($input, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        echo json_encode(['success' => false, 'message' => 'Invalid JSON: ' . json_last_error_msg()]);
        exit;
    }
    
    $id = $data['id'] ?? 0;
    $complianceNotes = $data['notes'] ?? '';
    $newStatus = $data['new_status'] ?? ''; // Optional: new status after compliance
    
    if (!$id) {
        echo json_encode(['success' => false, 'message' => 'Reservation ID is required']);
        exit;
    }
    
    // Check if "notes" column exists, if not, add it
    $checkColumn = $conn->query("SHOW COLUMNS FROM event_reservations LIKE 'notes'");
    if ($checkColumn->num_rows == 0) {
        $conn->query("ALTER TABLE event_reservations ADD COLUMN notes TEXT NULL DEFAULT NULL");
    }
    
    // Get current notes
    $checkStmt = $conn->prepare("SELECT notes, reservation_status FROM event_reservations WHERE reservation_id = ?");
    $checkStmt->bind_param("i", $id);
    $checkStmt->execute();
    $result = $checkStmt->get_result();
    $reservation = $result->fetch_assoc();
    $checkStmt->close();
    
    if (!$reservation) {
        echo json_encode(['success' => false, 'message' => 'Reservation not found']);
        exit;
    }
    
    // Check if already in "For Compliance" status
    if ($reservation['reservation_status'] !== 'For Compliance') {
        echo json_encode(['success' => false, 'message' => 'Reservation is not marked for compliance']);
        exit;
    }
    
    $timestamp = date('Y-m-d H:i:s');
    $complianceEntry = "\n[COMPLIANCE RESOLVED - " . $timestamp . "]: " . $complianceNotes;
    $updatedNotes = ($reservation['notes'] ?? '') . $complianceEntry;
    
    // Prepare update query
    $sql = "UPDATE event_reservations SET notes = ?";
    $params = [$updatedNotes];
    $types = "s";
    
    // If new status is provided, update it
    if (!empty($newStatus)) {
        $sql .= ", reservation_status = ?";
        $params[] = $newStatus;
        $types .= "s";
    }
    
    $sql .= " WHERE reservation_id = ?";
    $params[] = $id;
    $types .= "i";
    
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        echo json_encode(['success' => false, 'message' => 'Prepare failed: ' . $conn->error]);
        exit;
    }
    
    $stmt->bind_param($types, ...$params);
    
    if ($stmt->execute()) {
        echo json_encode([
            'success' => true, 
            'message' => 'Compliance notes added successfully' . (!empty($newStatus) ? ' and status updated to ' . $newStatus : ''),
            'is_editable' => isReservationEditable($newStatus ?? $reservation['reservation_status'])
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update compliance notes: ' . $stmt->error]);
    }
    exit;
}

function deleteReservation($conn) {
    $id = $_POST['id'] ?? 0;
    
    if (!$id) {
        echo json_encode(['success' => false, 'message' => 'Reservation ID is required']);
        exit;
    }
    
    // Check current reservation status first
    $checkStmt = $conn->prepare("SELECT reservation_status FROM event_reservations WHERE reservation_id = ?");
    $checkStmt->bind_param("i", $id);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();
    $currentReservation = $checkResult->fetch_assoc();
    $checkStmt->close();
    
    if (!$currentReservation) {
        echo json_encode(['success' => false, 'message' => 'Reservation not found']);
        exit;
    }
    
    $currentStatus = $currentReservation['reservation_status'];
    
    // Check if reservation can be deleted (one-time only rule)
    if (!isReservationEditable($currentStatus)) {
        echo json_encode([
            'success' => false, 
            'message' => 'This reservation can no longer be deleted. Status is already set to: ' . $currentStatus,
            'current_status' => $currentStatus
        ]);
        exit;
    }
    
    // Only allow deletion if status is still editable
    if (isReservationEditable($currentStatus)) {
        $stmt = $conn->prepare("DELETE FROM event_reservations WHERE reservation_id = ?");
        if (!$stmt) {
            echo json_encode(['success' => false, 'message' => 'Prepare failed: ' . $conn->error]);
            exit;
        }
        
        $stmt->bind_param("i", $id);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Reservation deleted successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to delete reservation: ' . $stmt->error]);
        }
    } else {
        echo json_encode([
            'success' => false, 
            'message' => 'Cannot delete reservation with status: ' . $currentStatus
        ]);
    }
    exit;
}

// Helper function to determine if a reservation is editable
function isReservationEditable($status) {
    // Reservations are editable only when status is: Pending or For Compliance
    $editableStatuses = ['Under review', 'For Compliance'];
    return in_array($status, $editableStatuses);
}
?>