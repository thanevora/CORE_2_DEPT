<?php
// event_reservations_api.php - REST API for Event Reservations
include("../../main_connection.php");

$db_name = "rest_m11_event";
if (!isset($connections[$db_name])) {
    echo json_encode(['success' => false, 'message' => "Database connection failed"]);
    exit;
}

$conn = $connections[$db_name];

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Function to get event reservations with "Requested" status only
function getRequestedReservations($conn, $params = [])
{
    // Default values - PHP 5.x compatible
    $page = max(1, (int)(isset($params['page']) ? $params['page'] : 1));
    $limit = max(1, min(100, (int)(isset($params['limit']) ? $params['limit'] : 20)));
    $offset = ($page - 1) * $limit;
    
    // Build query with optional filters
    $whereConditions = ["reservation_status = 'Requested'"]; // ALWAYS filter by Requested status
    $queryParams = [];
    $types = '';
    
    // Check for additional filters
    if (!empty($params['event_type'])) {
        $whereConditions[] = "event_type = ?";
        $queryParams[] = $params['event_type'];
        $types .= 's';
    }
    
    if (!empty($params['venue'])) {
        $whereConditions[] = "venue LIKE ?";
        $queryParams[] = "%" . $params['venue'] . "%";
        $types .= 's';
    }
    
    if (!empty($params['date_from'])) {
        $whereConditions[] = "event_date >= ?";
        $queryParams[] = $params['date_from'];
        $types .= 's';
    }
    
    if (!empty($params['date_to'])) {
        $whereConditions[] = "event_date <= ?";
        $queryParams[] = $params['date_to'];
        $types .= 's';
    }
    
    if (!empty($params['search'])) {
        $whereConditions[] = "(event_name LIKE ? OR venue LIKE ? OR facility_notes LIKE ?)";
        $queryParams[] = "%" . $params['search'] . "%";
        $queryParams[] = "%" . $params['search'] . "%";
        $queryParams[] = "%" . $params['search'] . "%";
        $types .= 'sss';
    }
    
    // Build WHERE clause
    $whereClause = 'WHERE ' . implode(" AND ", $whereConditions);
    
    // Count total records for pagination
    $countSql = "SELECT COUNT(*) as total FROM event_reservations $whereClause";
    
    if (!empty($queryParams)) {
        $countStmt = $conn->prepare($countSql);
        $countStmt->bind_param($types, ...$queryParams);
        $countStmt->execute();
        $totalResult = $countStmt->get_result();
    } else {
        $totalResult = $conn->query($countSql);
    }
    
    $totalRow = $totalResult->fetch_assoc();
    $totalRecords = isset($totalRow['total']) ? $totalRow['total'] : 0; // PHP 5.x compatible
    $totalPages = ceil($totalRecords / $limit);
    
    // Main query with pagination - ONLY EXPOSE SPECIFIED FIELDS
    $sql = "SELECT 
                reservation_id,
                event_name,
                event_type,
                event_date,
                event_time,
                venue,
                reservation_status,
                facility_notes,
                DATE_FORMAT(event_date, '%Y-%m-%d') as event_date_formatted,
                CASE 
                    WHEN event_time IS NOT NULL AND event_time != '' 
                    THEN TIME_FORMAT(STR_TO_DATE(event_time, '%H:%i'), '%h:%i %p')
                    ELSE NULL 
                END as event_time_formatted,
                'bg-yellow-100 text-yellow-800' as status_class
            FROM event_reservations 
            $whereClause
            ORDER BY event_date ASC, created_at DESC
            LIMIT ? OFFSET ?";
    
    // Add pagination parameters to query
    $queryParams[] = $limit;
    $queryParams[] = $offset;
    $types .= 'ii';
    
    // Prepare and execute query
    $reservations = [];
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$queryParams);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            // Parse facility notes if exists
            if (!empty($row['facility_notes'])) {
                $row['facility_notes_parsed'] = parseFacilityNotes($row['facility_notes']);
            }
            
            // Calculate days until event
            if (!empty($row['event_date'])) {
                $event_date = new DateTime($row['event_date']);
                $today = new DateTime();
                $interval = $today->diff($event_date);
                $row['days_until_event'] = $interval->days;
                $row['is_upcoming'] = $event_date >= $today;
                $row['is_today'] = $event_date->format('Y-m-d') === $today->format('Y-m-d');
                $row['is_past'] = $event_date < $today;
            }
            
            $reservations[] = $row;
        }
    }
    
    // Get available event types for filter info (only from Requested reservations)
    $typeStmt = $conn->prepare("SELECT DISTINCT event_type FROM event_reservations WHERE event_type IS NOT NULL AND reservation_status = 'Requested' ORDER BY event_type");
    $typeStmt->execute();
    $typeResult = $typeStmt->get_result();
    $available_event_types = [];
    while ($typeRow = $typeResult->fetch_assoc()) {
        $available_event_types[] = $typeRow['event_type'];
    }
    
    // Get available venues for filter info (only from Requested reservations)
    $venueStmt = $conn->prepare("SELECT DISTINCT venue FROM event_reservations WHERE venue IS NOT NULL AND reservation_status = 'Requested' ORDER BY venue");
    $venueStmt->execute();
    $venueResult = $venueStmt->get_result();
    $available_venues = [];
    while ($venueRow = $venueResult->fetch_assoc()) {
        $available_venues[] = $venueRow['venue'];
    }
    
    // Get statistics
    $statsSql = "SELECT 
                    COUNT(*) as total_requested,
                    MIN(event_date) as earliest_date,
                    MAX(event_date) as latest_date,
                    SUM(CASE WHEN event_date >= CURDATE() THEN 1 ELSE 0 END) as upcoming_events,
                    SUM(CASE WHEN event_date < CURDATE() THEN 1 ELSE 0 END) as past_events
                FROM event_reservations 
                WHERE reservation_status = 'Requested'";
    
    $statsResult = $conn->query($statsSql);
    $stats = $statsResult->fetch_assoc();
    
    return [
        'success' => true,
        'data' => $reservations,
        'pagination' => [
            'current_page' => $page,
            'total_pages' => $totalPages,
            'total_records' => $totalRecords,
            'limit' => $limit,
            'offset' => $offset,
            'has_next_page' => $page < $totalPages,
            'has_previous_page' => $page > 1
        ],
        'filters' => array_merge(['status' => 'Requested'], $params),
        'available_filters' => [
            'event_types' => $available_event_types,
            'venues' => $available_venues
        ],
        'statistics' => [
            'total_requested_reservations' => (int)$stats['total_requested'],
            'upcoming_events' => (int)$stats['upcoming_events'],
            'past_events' => (int)$stats['past_events'],
            'date_range' => [
                'earliest' => isset($stats['earliest_date']) ? $stats['earliest_date'] : null,
                'latest' => isset($stats['latest_date']) ? $stats['latest_date'] : null
            ]
        ],
        'metadata' => [
            'status' => 'Requested',
            'description' => 'Reservations that have requested facility setup',
            'count' => count($reservations),
            'timestamp' => date('Y-m-d H:i:s'),
            'fields_exposed' => [
                'reservation_id' => 'Unique reservation ID',
                'event_name' => 'Name of the event',
                'event_type' => 'Type of event',
                'event_date' => 'Original date (YYYY-MM-DD)',
                'event_date_formatted' => 'Formatted date',
                'event_time' => 'Original time',
                'event_time_formatted' => 'Formatted time (12-hour format)',
                'venue' => 'Location/venue',
                'reservation_status' => 'Always "Requested"',
                'status_class' => 'CSS classes for status display (yellow for Requested)',
                'facility_notes' => 'Facility requirements',
                'facility_notes_parsed' => 'Parsed facility notes (if available)',
                'days_until_event' => 'Days until event (negative if past)',
                'is_upcoming' => 'True if event is in future or today',
                'is_today' => 'True if event is today',
                'is_past' => 'True if event has passed'
            ]
        ]
    ];
}

// Function to get single requested reservation by ID
function getSingleRequestedReservation($conn, $id)
{
    $sql = "SELECT 
                reservation_id,
                event_name,
                event_type,
                event_date,
                event_time,
                venue,
                reservation_status,
                facility_notes,
                DATE_FORMAT(event_date, '%Y-%m-d') as event_date_formatted,
                CASE 
                    WHEN event_time IS NOT NULL AND event_time != '' 
                    THEN TIME_FORMAT(STR_TO_DATE(event_time, '%H:%i'), '%h:%i %p')
                    ELSE NULL 
                END as event_time_formatted,
                'bg-yellow-100 text-yellow-800' as status_class
            FROM event_reservations 
            WHERE reservation_id = ? AND reservation_status = 'Requested'";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        // Parse facility notes if exists
        if (!empty($row['facility_notes'])) {
            $row['facility_notes_parsed'] = parseFacilityNotes($row['facility_notes']);
        }
        
        // Calculate days until event
        if (!empty($row['event_date'])) {
            $event_date = new DateTime($row['event_date']);
            $today = new DateTime();
            $interval = $today->diff($event_date);
            $row['days_until_event'] = $interval->days;
            $row['is_upcoming'] = $event_date >= $today;
            $row['is_today'] = $event_date->format('Y-m-d') === $today->format('Y-m-d');
            $row['is_past'] = $event_date < $today;
        }
        
        return [
            'success' => true,
            'data' => $row
        ];
    }
    
    return [
        'success' => false,
        'message' => 'Requested reservation not found'
    ];
}

// Function to get available filters for Requested reservations
function getRequestedReservationFilters($conn)
{
    // Get distinct event types (only from Requested)
    $typeSql = "SELECT DISTINCT event_type FROM event_reservations WHERE event_type IS NOT NULL AND reservation_status = 'Requested' ORDER BY event_type";
    $typeResult = $conn->query($typeSql);
    
    $event_types = [];
    while ($row = $typeResult->fetch_assoc()) {
        $event_types[] = $row['event_type'];
    }
    
    // Get distinct venues (only from Requested)
    $venueSql = "SELECT DISTINCT venue FROM event_reservations WHERE venue IS NOT NULL AND reservation_status = 'Requested' ORDER BY venue";
    $venueResult = $conn->query($venueSql);
    
    $venues = [];
    while ($row = $venueResult->fetch_assoc()) {
        $venues[] = $row['venue'];
    }
    
    return [
        'success' => true,
        'data' => [
            'status' => 'Requested (fixed)',
            'event_types' => $event_types,
            'venues' => $venues,
            'note' => 'Only reservations with "Requested" status are available'
        ]
    ];
}

// Function to create a new reservation (POST)
function createReservation($conn, $data)
{
    // Validate required fields
    $required_fields = ['event_name', 'event_type', 'event_date', 'event_time', 'venue'];
    
    // Debug: Check what data we received
    if (empty($data)) {
        return [
            'success' => false,
            'message' => 'No data received. Please send JSON data.',
            'debug' => 'Received empty data array'
        ];
    }
    
    // Check if data is an array
    if (!is_array($data)) {
        return [
            'success' => false,
            'message' => 'Invalid data format. Expected JSON object.',
            'debug' => 'Data is not an array: ' . gettype($data)
        ];
    }
    
    foreach ($required_fields as $field) {
        if (!isset($data[$field]) || empty(trim($data[$field]))) {
            return [
                'success' => false,
                'message' => "Missing required field: $field",
                'debug' => 'Received fields: ' . implode(', ', array_keys($data))
            ];
        }
    }
    
    // Set default values
    $reservation_status = 'Requested'; // Always set to Requested for new reservations
    $created_at = date('Y-m-d H:i:s');
    
    // Prepare SQL
    $sql = "INSERT INTO event_reservations 
            (event_name, event_type, event_date, event_time, venue, 
             reservation_status, facility_notes, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        return [
            'success' => false,
            'message' => 'Database prepare failed: ' . $conn->error
        ];
    }
    
    $facility_notes = isset($data['facility_notes']) ? $data['facility_notes'] : '';
    
    // Bind parameters
    $stmt->bind_param(
        "ssssssss",
        $data['event_name'],
        $data['event_type'],
        $data['event_date'],
        $data['event_time'],
        $data['venue'],
        $reservation_status,
        $facility_notes,
        $created_at
    );
    
    if ($stmt->execute()) {
        $reservation_id = $stmt->insert_id;
        return [
            'success' => true,
            'message' => 'Reservation created successfully',
            'reservation_id' => $reservation_id,
            'data' => [
                'reservation_id' => $reservation_id,
                'event_name' => $data['event_name'],
                'event_type' => $data['event_type'],
                'event_date' => $data['event_date'],
                'event_time' => $data['event_time'],
                'venue' => $data['venue'],
                'reservation_status' => $reservation_status,
                'facility_notes' => $facility_notes,
                'created_at' => $created_at
            ]
        ];
    } else {
        return [
            'success' => false,
            'message' => 'Failed to create reservation: ' . $stmt->error,
            'debug' => 'SQL error occurred'
        ];
    }
}

// Function to update reservation status (PUT)
function updateReservationStatus($conn, $id, $data)
{
    // Validate required fields
    if (empty($data['reservation_status'])) {
        return [
            'success' => false,
            'message' => 'Missing required field: reservation_status',
            'debug' => 'Data received: ' . print_r($data, true)
        ];
    }
    
    // Validate status value
    $valid_statuses = ['Requested', 'Confirmed', 'Approved', 'Denied', 'Cancelled'];
    if (!in_array($data['reservation_status'], $valid_statuses)) {
        return [
            'success' => false,
            'message' => 'Invalid status value. Valid values: ' . implode(', ', $valid_statuses),
            'received' => $data['reservation_status']
        ];
    }
    
    // First, check if reservation exists and is Requested
    $checkSql = "SELECT reservation_status FROM event_reservations WHERE reservation_id = ?";
    $checkStmt = $conn->prepare($checkSql);
    if (!$checkStmt) {
        return [
            'success' => false,
            'message' => 'Database prepare failed: ' . $conn->error
        ];
    }
    
    $checkStmt->bind_param("i", $id);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();
    
    if ($checkResult->num_rows === 0) {
        return [
            'success' => false,
            'message' => 'Reservation not found',
            'id_requested' => $id
        ];
    }
    
    $reservation = $checkResult->fetch_assoc();
    if ($reservation['reservation_status'] !== 'Requested') {
        return [
            'success' => false,
            'message' => 'Only reservations with "Requested" status can be updated. Current status: ' . $reservation['reservation_status']
        ];
    }
    
    // Update reservation status
    $updated_at = date('Y-m-d H:i:s');
    $notes = isset($data['notes']) ? $data['notes'] : 'No additional notes';
    
    // Build update SQL
    $updateSql = "UPDATE event_reservations 
                  SET reservation_status = ?, 
                      updated_at = ?,
                      facility_notes = CONCAT(COALESCE(facility_notes, ''), 
                                  '\n[STATUS UPDATE - $updated_at]\nStatus changed to: {$data['reservation_status']}\nNotes: $notes')
                  WHERE reservation_id = ?";
    
    $updateStmt = $conn->prepare($updateSql);
    if (!$updateStmt) {
        return [
            'success' => false,
            'message' => 'Database prepare failed: ' . $conn->error
        ];
    }
    
    $updateStmt->bind_param(
        "ssi",
        $data['reservation_status'],
        $updated_at,
        $id
    );
    
    if ($updateStmt->execute()) {
        return [
            'success' => true,
            'message' => 'Reservation status updated successfully',
            'data' => [
                'reservation_id' => $id,
                'new_status' => $data['reservation_status'],
                'updated_at' => $updated_at,
                'notes' => isset($data['notes']) ? $data['notes'] : null
            ]
        ];
    } else {
        return [
            'success' => false,
            'message' => 'Failed to update reservation: ' . $updateStmt->error
        ];
    }
}

// Helper function to parse facility notes
function parseFacilityNotes($notes)
{
    $lines = explode("\n", $notes);
    $parsed = [
        'requests' => [],
        'timestamps' => []
    ];
    
    foreach ($lines as $line) {
        $line = trim($line);
        if (empty($line)) continue;
        
        // Check for timestamp
        if (preg_match('/\[([^\]]+)\]/', $line, $matches)) {
            $parsed['timestamps'][] = $matches[1];
            // Remove timestamp from line for further processing
            $line = trim(str_replace($matches[0], '', $line));
        }
        
        // Check for key-value pairs
        if (strpos($line, ':') !== false) {
            $parts = explode(':', $line, 2);
            if (count($parts) === 2) {
                $key = trim($parts[0]);
                $value = trim($parts[1]);
                if (!empty($key)) {
                    $parsed['requests'][$key] = $value;
                }
            }
        } elseif (!empty($line)) {
            // Add as general note if no colon found
            $parsed['requests']['note'] = $line;
        }
    }
    
    return $parsed;
}

// Get request data based on method
function getRequestData()
{
    $method = $_SERVER['REQUEST_METHOD'];
    
    switch ($method) {
        case 'GET':
            return $_GET;
        case 'POST':
        case 'PUT':
            // Get raw input
            $input = file_get_contents('php://input');
            
            // Debug: Log what we received
            error_log("Raw input received: " . $input);
            error_log("Input length: " . strlen($input));
            
            // Try to decode JSON
            $data = json_decode($input, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                // Try form data as fallback
                if (!empty($_POST)) {
                    error_log("Using POST data instead of JSON");
                    $data = $_POST;
                } else {
                    error_log("JSON decode error: " . json_last_error_msg());
                    // Try to parse as form-urlencoded
                    parse_str($input, $data);
                }
            }
            
            // Debug: Log what we parsed
            error_log("Parsed data: " . print_r($data, true));
            
            return $data;
        default:
            return [];
    }
}

// Main request handler
$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        // Handle GET requests
        if (isset($_GET['action']) && $_GET['action'] === 'filters') {
            $response = getRequestedReservationFilters($conn);
        } elseif (isset($_GET['id'])) {
            // Get single requested reservation by ID
            $id = $_GET['id'];
            $response = getSingleRequestedReservation($conn, $id);
        } else {
            // Get all requested reservations with filters (default action)
            $filters = [
                'page' => isset($_GET['page']) ? $_GET['page'] : 1,
                'limit' => isset($_GET['limit']) ? $_GET['limit'] : 20,
                'event_type' => isset($_GET['event_type']) ? $_GET['event_type'] : null,
                'venue' => isset($_GET['venue']) ? $_GET['venue'] : null,
                'date_from' => isset($_GET['date_from']) ? $_GET['date_from'] : null,
                'date_to' => isset($_GET['date_to']) ? $_GET['date_to'] : null,
                'search' => isset($_GET['search']) ? $_GET['search'] : null
            ];
            $response = getRequestedReservations($conn, $filters);
        }
        break;
        
    case 'POST':
        // Get data for POST
        $requestData = getRequestData();
        // Create new reservation
        $response = createReservation($conn, $requestData);
        break;
        
    case 'PUT':
        // Get data for PUT
        $requestData = getRequestData();
        // Update reservation status
        if (isset($_GET['id'])) {
            $id = $_GET['id'];
            $response = updateReservationStatus($conn, $id, $requestData);
        } else {
            $response = [
                'success' => false,
                'message' => 'Reservation ID is required for PUT request',
                'usage' => 'Use: PUT /event_reservations_api.php?id=123',
                'example' => 'URL: https://restaurant.soliera-hotel-restaurant.com//API/Event/Event_facility_request.php?id=123',
                'note' => 'The ID should be in the URL, not in the JSON body'
            ];
        }
        break;
        
    default:
        $response = [
            'success' => false,
            'message' => 'Method not allowed. Supported methods: GET, POST, PUT.',
            'usage' => [
                'GET' => 'Get reservations: /event_reservations_api.php?page=1&limit=20',
                'GET single' => 'Get single: /event_reservations_api.php?id=123',
                'POST' => 'Create new: Send JSON with event_name, event_type, event_date, event_time, venue',
                'PUT' => 'Update status: /event_reservations_api.php?id=123 with JSON containing reservation_status'
            ]
        ];
        http_response_code(405);
        break;
}

// Send response
echo json_encode($response, JSON_PRETTY_PRINT);

$conn->close();
?>