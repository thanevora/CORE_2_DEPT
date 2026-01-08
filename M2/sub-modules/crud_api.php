<?php
session_start();
include("../main_connection.php");

$db_name = "rest_m2_inventory";

if (!isset($connections[$db_name])) {
    die(json_encode(['status' => 'error', 'message' => "❌ Connection not found for $db_name"]));
}

$conn = $connections[$db_name];
header('Content-Type: application/json');

// Determine action based on request
$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {
    case 'fetch':
        fetchStocks($conn);
        break;
    case 'update':
        updateStock($conn);
        break;
    case 'delete':
        deleteStocks($conn);
        break;
    case 'add':
        addStock($conn);
        break;
    case 'search':
        searchStocks($conn);
        break;
    default:
        echo json_encode(['status' => 'error', 'message' => 'Invalid action']);
        break;
}

function fetchStocks($conn) {
    $page = $_GET['page'] ?? 1;
    $limit = $_GET['limit'] ?? 15;
    $offset = ($page - 1) * $limit;
    $search = $_GET['search'] ?? '';
    
    // Build query with search
    $whereClause = '';
    $params = [];
    $types = '';
    
    if (!empty($search)) {
        $whereClause = "WHERE item_name LIKE ? OR category LIKE ? OR location LIKE ?";
        $searchTerm = "%$search%";
        $params = [$searchTerm, $searchTerm, $searchTerm];
        $types = 'sss';
    }
    
    // Get total count
    $countQuery = "SELECT COUNT(*) as total FROM inventory_and_stock $whereClause";
    $countStmt = $conn->prepare($countQuery);
    
    if (!empty($params)) {
        $countStmt->bind_param($types, ...$params);
    }
    
    $countStmt->execute();
    $countResult = $countStmt->get_result();
    $totalCount = $countResult->fetch_assoc()['total'];
    $countStmt->close();
    
    // Get paginated data
    $query = "SELECT item_id, item_name, category, quantity, critical_level, created_at, updated_at, 
                     unit_price, notes, request_status, expiry_date, last_restock_date, location
              FROM inventory_and_stock $whereClause 
              ORDER BY created_at DESC 
              LIMIT ? OFFSET ?";
    
    $stmt = $conn->prepare($query);
    
    if (!empty($params)) {
        $params[] = $limit;
        $params[] = $offset;
        $types .= 'ii';
        $stmt->bind_param($types, ...$params);
    } else {
        $stmt->bind_param('ii', $limit, $offset);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    $stocks = [];
    
    while ($row = $result->fetch_assoc()) {
        $stocks[] = $row;
    }
    
    echo json_encode([
        'status' => 'success',
        'data' => $stocks,
        'total' => $totalCount,
        'page' => $page,
        'limit' => $limit,
        'total_pages' => ceil($totalCount / $limit)
    ]);
}

function updateStock($conn) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input || !isset($input['item_id'])) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid data']);
        return;
    }
    
    $allowedFields = ['item_name', 'category', 'quantity', 'critical_level', 'unit_price', 
                     'notes', 'expiry_date', 'location', 'last_restock_date'];
    
    $updates = [];
    $params = [];
    $types = '';
    
    foreach ($allowedFields as $field) {
        if (isset($input[$field])) {
            $updates[] = "$field = ?";
            $params[] = $input[$field];
            $types .= 's';
        }
    }
    
    if (empty($updates)) {
        echo json_encode(['status' => 'error', 'message' => 'No fields to update']);
        return;
    }
    
    $params[] = $input['item_id'];
    $types .= 'i';
    
    $query = "UPDATE inventory_and_stock SET " . implode(', ', $updates) . ", updated_at = NOW() WHERE item_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param($types, ...$params);
    
    if ($stmt->execute()) {
        echo json_encode(['status' => 'success', 'message' => 'Stock updated successfully']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to update: ' . $stmt->error]);
    }
}

function deleteStocks($conn) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['item_ids']) || !is_array($input['item_ids'])) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid item IDs']);
        return;
    }
    
    $item_ids = array_map('intval', $input['item_ids']);
    $placeholders = implode(',', array_fill(0, count($item_ids), '?'));
    
    $stmt = $conn->prepare("DELETE FROM inventory_and_stock WHERE item_id IN ($placeholders)");
    
    if ($stmt === false) {
        echo json_encode(['status' => 'error', 'message' => 'Failed to prepare statement']);
        return;
    }
    
    $types = str_repeat('i', count($item_ids));
    $stmt->bind_param($types, ...$item_ids);
    
    if ($stmt->execute()) {
        echo json_encode(['status' => 'success', 'message' => 'Items deleted successfully']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to delete items']);
    }
}

function addStock($conn) {
    $required = ['item_name', 'category', 'quantity', 'critical_level', 'unit_price', 'location'];
    
    foreach ($required as $field) {
        if (empty($_POST[$field])) {
            echo json_encode(['status' => 'error', 'message' => "$field is required"]);
            return;
        }
    }
    
    $query = "INSERT INTO inventory_and_stock (item_name, category, quantity, critical_level, unit_price, 
              notes, expiry_date, location, last_restock_date, request_status, created_at, updated_at) 
              VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', NOW(), NOW())";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param('ssiisssss', 
        $_POST['item_name'],
        $_POST['category'],
        $_POST['quantity'],
        $_POST['critical_level'],
        $_POST['unit_price'],
        $_POST['notes'] ?? null,
        $_POST['expiry_date'] ?? null,
        $_POST['location'],
        $_POST['last_restock_date'] ?? null
    );
    
    if ($stmt->execute()) {
        echo json_encode(['status' => 'success', 'message' => 'Stock added successfully']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to add stock']);
    }
}

function searchStocks($conn) {
    $search = $_GET['q'] ?? '';
    $limit = 10;
    
    if (empty($search)) {
        echo json_encode(['status' => 'success', 'data' => []]);
        return;
    }
    
    $searchTerm = "%$search%";
    $query = "SELECT item_id, item_name, category, quantity, critical_level 
              FROM inventory_and_stock 
              WHERE item_name LIKE ? OR category LIKE ? 
              LIMIT ?";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param('ssi', $searchTerm, $searchTerm, $limit);
    $stmt->execute();
    $result = $stmt->get_result();
    $items = [];
    
    while ($row = $result->fetch_assoc()) {
        $items[] = $row;
    }
    
    echo json_encode(['status' => 'success', 'data' => $items]);
}
?>