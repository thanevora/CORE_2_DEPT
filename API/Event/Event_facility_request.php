<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-API-Key');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Include database connection
include("../../main_connection.php");

$db_name = "rest_m11_event";
if (!isset($connections[$db_name])) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => "Database connection not found"]);
    exit;
}

$conn = $connections[$db_name];

// API key authentication - ENABLED
$api_key = $_SERVER['HTTP_X_API_KEY'] ?? $_GET['api_key'] ?? '';
$valid_api_key = 'Core_2_dept@4562526090892302633450908923026336';

// Check API key
if ($api_key !== $valid_api_key) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Invalid API key']);
    exit;
}

// Get action from URL parameter
$action = $_GET['action'] ?? '';

// Available endpoints
switch ($action) {
    case 'get_requested':
        getRequestedReservations($conn);
        break;
    case 'get_approved':
        getApprovedReservations($conn);
        break;
    case 'get_by_status':
        getReservationsByStatus($conn);
        break;
    case 'get_all':
        getAllReservations($conn);
        break;
    case 'update_status':
        updateReservationStatus($conn);
        break;
    case 'get_reservation':
        getSingleReservation($conn);
        break;
    default:
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Invalid action parameter',
            'available_actions' => [
                'get_requested' => 'Get all reservations with Requested status',
                'get_approved' => 'Get all reservations with Approved/Confirmed status',
                'get_by_status' => 'Get reservations by specific status (pass ?status=STATUS)',
                'get_all' => 'Get all reservations with pagination',
                'update_status' => 'Update reservation status (POST only)',
                'get_reservation' => 'Get single reservation by ID'
            ],
            'usage' => [
                'get_requested' => 'GET /external_api.php?action=get_requested&api_key=YOUR_KEY',
                'get_approved' => 'GET /external_api.php?action=get_approved&api_key=YOUR_KEY',
                'get_by_status' => 'GET /external_api.php?action=get_by_status&status=Confirmed&api_key=YOUR_KEY',
                'get_all' => 'GET /external_api.php?action=get_all&page=1&limit=20&api_key=YOUR_KEY',
                'update_status' => 'POST /external_api.php?action=update_status (with JSON body and X-API-Key header)',
                'get_reservation' => 'GET /external_api.php?action=get_reservation&id=123&api_key=YOUR_KEY'
            ]
        ]);
        exit;
}

/**
 * Get all reservations with "Requested" status
 * URL: /external_api.php?action=get_requested
 */
function getRequestedReservations($conn) {
    $page = max(1, intval($_GET['page'] ?? 1));
    $limit = min(100, max(1, intval($_GET['limit'] ?? 20)));
    $offset = ($page - 1) * $limit;
    
    // Get total count
    $countSql = "SELECT COUNT(*) as total FROM event_reservations WHERE reservation_status = 'Requested'";
    $countResult = $conn->query($countSql);
    $total = $countResult->fetch_assoc()['total'];
    
    // Get data
    $sql = "SELECT 
                reservation_id,
                customer_name,
                customer_email,
                customer_phone,
                event_name,
                event_type,
                event_date,
                event_time,
                venue,
                num_guests,
                total_amount,
                amount_paid,
                payment_status,
                reservation_status,
                facility_notes,
                created_at
            FROM event_reservations 
            WHERE reservation_status = 'Requested'
            ORDER BY event_date ASC, created_at DESC 
            LIMIT ? OFFSET ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $limit, $offset);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $reservations = [];
    while ($row = $result->fetch_assoc()) {
        // Format dates for better readability
        $row['event_date_formatted'] = date('Y-m-d', strtotime($row['event_date']));
        $row['event_time_formatted'] = date('H:i', strtotime($row['event_time']));
        $row['created_at_formatted'] = date('Y-m-d H:i:s', strtotime($row['created_at']));
        
        // Parse facility notes if exists
        if (!empty($row['facility_notes'])) {
            $row['facility_notes_parsed'] = parseFacilityNotes($row['facility_notes']);
        }
        
        $reservations[] = $row;
    }
    
    echo json_encode([
        'success' => true,
        'data' => $reservations,
        'pagination' => [
            'current_page' => $page,
            'total_pages' => ceil($total / $limit),
            'total_items' => $total,
            'limit' => $limit
        ],
        'metadata' => [
            'status' => 'Requested',
            'description' => 'Reservations that have requested facility setup',
            'count' => count($reservations)
        ]
    ], JSON_PRETTY_PRINT);
    exit;
}

/**
 * Get all reservations with "Approved" or "Confirmed" status
 * URL: /external_api.php?action=get_approved
 */
function getApprovedReservations($conn) {
    $page = max(1, intval($_GET['page'] ?? 1));
    $limit = min(100, max(1, intval($_GET['limit'] ?? 20)));
    $offset = ($page - 1) * $limit;
    
    $status_filter = $_GET['status_type'] ?? 'both'; // 'approved', 'confirmed', or 'both'
    
    // Build status condition
    $status_condition = "reservation_status IN ('Confirmed', 'Approved')";
    if ($status_filter === 'approved') {
        $status_condition = "reservation_status = 'Approved'";
    } elseif ($status_filter === 'confirmed') {
        $status_condition = "reservation_status = 'Confirmed'";
    }
    
    // Get total count
    $countSql = "SELECT COUNT(*) as total FROM event_reservations WHERE $status_condition";
    $countResult = $conn->query($countSql);
    $total = $countResult->fetch_assoc()['total'];
    
    // Get data
    $sql = "SELECT 
                reservation_id,
                customer_name,
                customer_email,
                customer_phone,
                event_name,
                event_type,
                event_date,
                event_time,
                venue,
                num_guests,
                total_amount,
                amount_paid,
                payment_status,
                reservation_status,
                special_requests,
                event_package,
                MOP,
                image_url,
                created_at
            FROM event_reservations 
            WHERE $status_condition
            ORDER BY event_date ASC, created_at DESC 
            LIMIT ? OFFSET ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $limit, $offset);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $reservations = [];
    while ($row = $result->fetch_assoc()) {
        // Format dates
        $row['event_date_formatted'] = date('Y-m-d', strtotime($row['event_date']));
        $row['event_time_formatted'] = date('H:i', strtotime($row['event_time']));
        $row['created_at_formatted'] = date('Y-m-d H:i:s', strtotime($row['created_at']));
        
        // Calculate days until event
        $event_date = new DateTime($row['event_date']);
        $today = new DateTime();
        $interval = $today->diff($event_date);
        $row['days_until_event'] = $interval->days;
        $row['is_upcoming'] = $event_date >= $today;
        
        // Add image URL if exists
        if (!empty($row['image_url'])) {
            $row['image_full_url'] = getBaseUrl() . "/M6/images/" . $row['image_url'];
        }
        
        $reservations[] = $row;
    }
    
    echo json_encode([
        'success' => true,
        'data' => $reservations,
        'pagination' => [
            'current_page' => $page,
            'total_pages' => ceil($total / $limit),
            'total_items' => $total,
            'limit' => $limit
        ],
        'metadata' => [
            'status_filter' => $status_filter,
            'description' => 'Approved/Confirmed event reservations',
            'count' => count($reservations)
        ]
    ], JSON_PRETTY_PRINT);
    exit;
}

/**
 * Get reservations by specific status
 * URL: /external_api.php?action=get_by_status&status=STATUS
 */
function getReservationsByStatus($conn) {
    $status = $_GET['status'] ?? '';
    if (empty($status)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Status parameter is required']);
        exit;
    }
    
    // Validate status
    $valid_statuses = ['Pending', 'Confirmed', 'Approved', 'Requested', 'Reserved', 'Denied', 'Cancelled', 'Under Review'];
    if (!in_array($status, $valid_statuses)) {
        http_response_code(400);
        echo json_encode([
            'success' => false, 
            'message' => 'Invalid status value',
            'valid_statuses' => $valid_statuses
        ]);
        exit;
    }
    
    $page = max(1, intval($_GET['page'] ?? 1));
    $limit = min(100, max(1, intval($_GET['limit'] ?? 20)));
    $offset = ($page - 1) * $limit;
    
    // Get total count
    $countSql = "SELECT COUNT(*) as total FROM event_reservations WHERE reservation_status = ?";
    $countStmt = $conn->prepare($countSql);
    $countStmt->bind_param("s", $status);
    $countStmt->execute();
    $countResult = $countStmt->get_result();
    $total = $countResult->fetch_assoc()['total'];
    
    // Get data
    $sql = "SELECT * FROM event_reservations 
            WHERE reservation_status = ?
            ORDER BY event_date ASC, created_at DESC 
            LIMIT ? OFFSET ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sii", $status, $limit, $offset);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $reservations = [];
    while ($row = $result->fetch_assoc()) {
        $reservations[] = $row;
    }
    
    echo json_encode([
        'success' => true,
        'data' => $reservations,
        'pagination' => [
            'current_page' => $page,
            'total_pages' => ceil($total / $limit),
            'total_items' => $total,
            'limit' => $limit
        ],
        'metadata' => [
            'status' => $status,
            'count' => count($reservations)
        ]
    ], JSON_PRETTY_PRINT);
    exit;
}

/**
 * Get all reservations with pagination
 * URL: /external_api.php?action=get_all
 */
function getAllReservations($conn) {
    $page = max(1, intval($_GET['page'] ?? 1));
    $limit = min(100, max(1, intval($_GET['limit'] ?? 20)));
    $offset = ($page - 1) * $limit;
    
    // Get filters
    $status = $_GET['filter_status'] ?? '';
    $date_from = $_GET['date_from'] ?? '';
    $date_to = $_GET['date_to'] ?? '';
    $search = $_GET['search'] ?? '';
    
    // Build WHERE clause
    $where = "1=1";
    $params = [];
    $types = "";
    
    if (!empty($status)) {
        $where .= " AND reservation_status = ?";
        $params[] = $status;
        $types .= "s";
    }
    
    if (!empty($date_from)) {
        $where .= " AND event_date >= ?";
        $params[] = $date_from;
        $types .= "s";
    }
    
    if (!empty($date_to)) {
        $where .= " AND event_date <= ?";
        $params[] = $date_to;
        $types .= "s";
    }
    
    if (!empty($search)) {
        $where .= " AND (customer_name LIKE ? OR event_name LIKE ? OR customer_email LIKE ?)";
        $search_term = "%$search%";
        $params[] = $search_term;
        $params[] = $search_term;
        $params[] = $search_term;
        $types .= "sss";
    }
    
    // Get total count
    $countSql = "SELECT COUNT(*) as total FROM event_reservations WHERE $where";
    $countStmt = $conn->prepare($countSql);
    if (!empty($types)) {
        $countStmt->bind_param($types, ...$params);
    }
    $countStmt->execute();
    $countResult = $countStmt->get_result();
    $total = $countResult->fetch_assoc()['total'];
    
    // Get data
    $sql = "SELECT * FROM event_reservations 
            WHERE $where
            ORDER BY created_at DESC 
            LIMIT ? OFFSET ?";
    
    $params[] = $limit;
    $params[] = $offset;
    $types .= "ii";
    
    $stmt = $conn->prepare($sql);
    if (!empty($types)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    
    $reservations = [];
    while ($row = $result->fetch_assoc()) {
        $reservations[] = $row;
    }
    
    echo json_encode([
        'success' => true,
        'data' => $reservations,
        'pagination' => [
            'current_page' => $page,
            'total_pages' => ceil($total / $limit),
            'total_items' => $total,
            'limit' => $limit
        ],
        'filters_applied' => [
            'status' => $status,
            'date_from' => $date_from,
            'date_to' => $date_to,
            'search' => $search
        ]
    ], JSON_PRETTY_PRINT);
    exit;
}

/**
 * Update reservation status (POST only)
 * URL: POST /external_api.php?action=update_status
 * Body: {"id": 123, "status": "Approved", "notes": "Optional notes"}
 */
function updateReservationStatus($conn) {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method not allowed. Use POST']);
        exit;
    }
    
    $input = file_get_contents('php://input');
    if (empty($input)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'No data received']);
        exit;
    }
    
    $data = json_decode($input, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid JSON: ' . json_last_error_msg()]);
        exit;
    }
    
    $id = $data['id'] ?? 0;
    $status = $data['status'] ?? '';
    $notes = $data['notes'] ?? '';
    
    if (!$id || !$status) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Reservation ID and status are required']);
        exit;
    }
    
    // Validate status
    $valid_statuses = ['Pending', 'Confirmed', 'Approved', 'Requested', 'Reserved', 'Denied', 'Cancelled', 'Under Review'];
    if (!in_array($status, $valid_statuses)) {
        http_response_code(400);
        echo json_encode([
            'success' => false, 
            'message' => 'Invalid status value',
            'valid_statuses' => $valid_statuses
        ]);
        exit;
    }
    
    // Check if reservation exists
    $checkStmt = $conn->prepare("SELECT reservation_status FROM event_reservations WHERE reservation_id = ?");
    $checkStmt->bind_param("i", $id);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();
    
    if ($checkResult->num_rows === 0) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Reservation not found']);
        exit;
    }
    
    // Update reservation
    $timestamp = date('Y-m-d H:i:s');
    $updateNotes = "[EXTERNAL API UPDATE - $timestamp]\n";
    $updateNotes .= "Status changed to: $status\n";
    if ($notes) $updateNotes .= "Notes: $notes\n";
    
    // Get existing notes
    $existingStmt = $conn->prepare("SELECT notes FROM event_reservations WHERE reservation_id = ?");
    $existingStmt->bind_param("i", $id);
    $existingStmt->execute();
    $existingResult = $existingStmt->get_result();
    $existingData = $existingResult->fetch_assoc();
    
    $existingNotes = $existingData['notes'] ?? '';
    $newNotes = $existingNotes . "\n\n" . $updateNotes;
    
    // Update
    $updateStmt = $conn->prepare("UPDATE event_reservations SET reservation_status = ?, notes = ? WHERE reservation_id = ?");
    $updateStmt->bind_param("ssi", $status, $newNotes, $id);
    
    if ($updateStmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => 'Reservation status updated successfully',
            'data' => [
                'id' => $id,
                'new_status' => $status,
                'updated_at' => $timestamp
            ]
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to update status: ' . $updateStmt->error]);
    }
    exit;
}

/**
 * Get single reservation by ID
 * URL: /external_api.php?action=get_reservation&id=123
 */
function getSingleReservation($conn) {
    $id = $_GET['id'] ?? 0;
    
    if (!$id) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Reservation ID is required']);
        exit;
    }
    
    $stmt = $conn->prepare("SELECT * FROM event_reservations WHERE reservation_id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Reservation not found']);
        exit;
    }
    
    $reservation = $result->fetch_assoc();
    
    // Add formatted dates
    $reservation['event_date_formatted'] = date('Y-m-d', strtotime($reservation['event_date']));
    $reservation['event_time_formatted'] = date('H:i', strtotime($reservation['event_time']));
    $reservation['created_at_formatted'] = date('Y-m-d H:i:s', strtotime($reservation['created_at']));
    
    // Add image URL if exists
    if (!empty($reservation['image_url'])) {
        $reservation['image_full_url'] = getBaseUrl() . "/M6/images/" . $reservation['image_url'];
    }
    
    // Calculate days until event
    $event_date = new DateTime($reservation['event_date']);
    $today = new DateTime();
    $interval = $today->diff($event_date);
    $reservation['days_until_event'] = $interval->days;
    $reservation['is_upcoming'] = $event_date >= $today;
    
    echo json_encode([
        'success' => true,
        'data' => $reservation,
        'metadata' => [
            'id' => $id,
            'retrieved_at' => date('Y-m-d H:i:s')
        ]
    ], JSON_PRETTY_PRINT);
    exit;
}

/**
 * Helper function to parse facility notes into structured format
 */
function parseFacilityNotes($notes) {
    $lines = explode("\n", $notes);
    $parsed = [
        'requests' => [],
        'timestamps' => []
    ];
    
    foreach ($lines as $line) {
        $line = trim($line);
        if (empty($line)) continue;
        
        // Check for timestamp
        if (preg_match('/\[(.*?)\]/', $line, $matches)) {
            $parsed['timestamps'][] = $matches[1];
        }
        
        // Check for key-value pairs
        if (strpos($line, ':') !== false) {
            list($key, $value) = explode(':', $line, 2);
            $parsed['requests'][trim($key)] = trim($value);
        }
    }
    
    return $parsed;
}

/**
 * Helper function to get base URL
 */
function getBaseUrl() {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
    $host = $_SERVER['HTTP_HOST'];
    return $protocol . "://" . $host;
}
?>