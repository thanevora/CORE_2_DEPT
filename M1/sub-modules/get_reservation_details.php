<?php
// Turn off error display for production, but log them
ini_set('display_errors', 0);
ini_set('log_errors', 1);

include("../../main_connection.php");

// Set JSON header FIRST
header('Content-Type: application/json');

// Check for connection errors
if (!isset($connections['rest_m1_trs'])) {
    echo json_encode(['success' => false, 'error' => 'Database connection not available']);
    exit;
}

if (!isset($_GET['id'])) {
    echo json_encode(['success' => false, 'error' => 'Invalid reservation ID']);
    exit;
}

$id = intval($_GET['id']);
if ($id <= 0) {
    echo json_encode(['success' => false, 'error' => 'Invalid reservation ID']);
    exit;
}

try {
    $sql = "SELECT * FROM reservations WHERE reservation_id = ? LIMIT 1";
    $stmt = $connections['rest_m1_trs']->prepare($sql);
    
    if (!$stmt) {
        throw new Exception('Failed to prepare statement');
    }
    
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        
        $reservationData = [
            'success' => true,
            'data' => [
                'reservation_id' => $row['reservation_id'],
                'customer_name' => $row['name'],
                'customer_phone' => $row['contact'],
                'customer_email' => $row['email'] ?? '',
                'reservation_date' => $row['reservation_date'],
                'reservation_time' => $row['start_time'],
                'end_time' => $row['end_time'],
                'num_guests' => (int)$row['size'],
                'table_number' => $row['table_id'],
                'type' => $row['type'],
                'special_requests' => $row['request'] ?? '',
                'note' => $row['note'] ?? '',
                'status' => $row['status'],
                'create_at' => $row['create_at'],
                'modify_at' => $row['modify_at'] ?? ''
            ]
        ];
        
        echo json_encode($reservationData);
    } else {
        echo json_encode(['success' => false, 'error' => 'Reservation not found']);
    }
    
    if ($stmt) {
        $stmt->close();
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
}
?>