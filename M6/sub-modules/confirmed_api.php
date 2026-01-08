<?php
session_start();
include("../../main_connection.php");

$db_name = "rest_m11_event";
if (!isset($connections[$db_name])) {
    die(json_encode(['success' => false, 'message' => "Connection not found"]));
}

$conn = $connections[$db_name];
header('Content-Type: application/json');

// Handle CORS
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

// Get action
$action = $_GET['action'] ?? '';
if (empty($action)) {
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
    case 'get_reservation':
        getConfirmedReservation($conn);
        break;
    case 'request_facility':
        requestFacility($conn);
        break;
    case 'for_compliance':
        forCompliance($conn);
        break;
    case 'deny_reservation':
        denyReservation($conn);
        break;
    case 'update_facility':
        updateFacility($conn);
        break;
    case 'get_facility_requests':
        getFacilityRequests($conn);
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action: ' . $action]);
        exit;
}

function getConfirmedReservation($conn) {
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
        echo json_encode(['success' => true, 'data' => $reservation]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Reservation not found']);
    }
    exit;
}

function requestFacility($conn) {
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
    $requirements = $data['requirements'] ?? '';
    $equipment = $data['equipment'] ?? '';
    $setupTime = $data['setup_time'] ?? '';
    
    if (!$id || !$requirements) {
        echo json_encode(['success' => false, 'message' => 'Reservation ID and requirements are required']);
        exit;
    }
    
    // Check if facility_notes column exists, if not add it
    $checkColumn = $conn->query("SHOW COLUMNS FROM event_reservations LIKE 'facility_notes'");
    if ($checkColumn->num_rows == 0) {
        $conn->query("ALTER TABLE event_reservations ADD COLUMN facility_notes TEXT NULL DEFAULT NULL");
    }
    
    // Check if compliance_notes column exists, if not add it
    $checkColumn2 = $conn->query("SHOW COLUMNS FROM event_reservations LIKE 'compliance_notes'");
    if ($checkColumn2->num_rows == 0) {
        $conn->query("ALTER TABLE event_reservations ADD COLUMN compliance_notes TEXT NULL DEFAULT NULL");
    }
    
    // Create facility notes with timestamp
    $timestamp = date('Y-m-d H:i:s');
    $facilityNotes = "[FACILITY REQUEST - $timestamp]\n";
    $facilityNotes .= "Requirements: $requirements\n";
    if ($equipment) $facilityNotes .= "Equipment: $equipment\n";
    if ($setupTime) $facilityNotes .= "Setup Time: $setupTime\n";
    
    // Update reservation
    $stmt = $conn->prepare("UPDATE event_reservations SET facility_notes = ?, reservation_status = 'Requested' WHERE reservation_id = ?");
    if (!$stmt) {
        echo json_encode(['success' => false, 'message' => 'Prepare failed: ' . $conn->error]);
        exit;
    }
    
    $stmt->bind_param("si", $facilityNotes, $id);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Facility request submitted successfully. Status changed to "Requested"']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to submit facility request: ' . $stmt->error]);
    }
    exit;
}

function forCompliance($conn) {
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
    $notes = $data['notes'] ?? '';
    $action = $data['action'] ?? '';
    
    if (!$id || !$notes) {
        echo json_encode(['success' => false, 'message' => 'Reservation ID and notes are required']);
        exit;
    }
    
    // Create compliance notes with timestamp
    $timestamp = date('Y-m-d H:i:s');
    $complianceNotes = "[COMPLIANCE ISSUE - $timestamp]\n";
    $complianceNotes .= "Action Required: " . ucfirst(str_replace('_', ' ', $action)) . "\n";
    $complianceNotes .= "Notes: $notes\n";
    
    // Update reservation
    $stmt = $conn->prepare("UPDATE event_reservations SET compliance_notes = ? WHERE reservation_id = ?");
    if (!$stmt) {
        echo json_encode(['success' => false, 'message' => 'Prepare failed: ' . $conn->error]);
        exit;
    }
    
    $stmt->bind_param("si", $complianceNotes, $id);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Compliance issue recorded successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to record compliance issue: ' . $stmt->error]);
    }
    exit;
}

function denyReservation($conn) {
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
    
    if (!$id) {
        echo json_encode(['success' => false, 'message' => 'Reservation ID is required']);
        exit;
    }
    
    // Update reservation status to Denied
    $stmt = $conn->prepare("UPDATE event_reservations SET reservation_status = 'Denied' WHERE reservation_id = ?");
    if (!$stmt) {
        echo json_encode(['success' => false, 'message' => 'Prepare failed: ' . $conn->error]);
        exit;
    }
    
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Reservation has been denied']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to deny reservation: ' . $stmt->error]);
    }
    exit;
}

function updateFacility($conn) {
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
        echo json_encode(['success' => false, 'message' => 'Reservation ID and status are required']);
        exit;
    }
    
    $timestamp = date('Y-m-d H:i:s');
    $updateNotes = "[FACILITY UPDATE - $timestamp]\n";
    $updateNotes .= "Status: $status\n";
    if ($notes) $updateNotes .= "Notes: $notes\n";
    
    // Get existing facility notes
    $checkStmt = $conn->prepare("SELECT facility_notes FROM event_reservations WHERE reservation_id = ?");
    $checkStmt->bind_param("i", $id);
    $checkStmt->execute();
    $result = $checkStmt->get_result();
    $reservation = $result->fetch_assoc();
    
    $existingNotes = $reservation['facility_notes'] ?? '';
    $newNotes = $existingNotes . "\n\n" . $updateNotes;
    
    // Update reservation
    $stmt = $conn->prepare("UPDATE event_reservations SET facility_notes = ? WHERE reservation_id = ?");
    if (!$stmt) {
        echo json_encode(['success' => false, 'message' => 'Prepare failed: ' . $conn->error]);
        exit;
    }
    
    $stmt->bind_param("si", $newNotes, $id);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Facility request updated successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update facility request: ' . $stmt->error]);
    }
    exit;
}

function getFacilityRequests($conn) {
    $page = $_GET['page'] ?? 1;
    $limit = $_GET['limit'] ?? 10;
    $offset = ($page - 1) * $limit;
    
    // Get reservations with facility requests
    $sql = "SELECT * FROM event_reservations 
            WHERE (facility_notes IS NOT NULL AND facility_notes != '') 
            OR reservation_status = 'Requested'
            ORDER BY event_date ASC 
            LIMIT ? OFFSET ?";
    
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        echo json_encode(['success' => false, 'message' => 'Prepare failed: ' . $conn->error]);
        exit;
    }
    
    $stmt->bind_param("ii", $limit, $offset);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $reservations = [];
    while ($row = $result->fetch_assoc()) {
        $reservations[] = $row;
    }
    
    // Get total count
    $countSql = "SELECT COUNT(*) as total FROM event_reservations 
                 WHERE (facility_notes IS NOT NULL AND facility_notes != '') 
                 OR reservation_status = 'Requested'";
    
    $countResult = $conn->query($countSql);
    $total = $countResult->fetch_assoc()['total'];
    
    echo json_encode([
        'success' => true,
        'data' => $reservations,
        'total' => $total,
        'page' => (int)$page,
        'limit' => (int)$limit
    ]);
    exit;
}
?>