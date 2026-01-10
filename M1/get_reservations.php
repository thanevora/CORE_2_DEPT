<?php
include '../main_connection.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *'); // For dev use only — remove or restrict in production

try {
    $date = $_GET['date'] ?? null;

    if (!$date) {
        throw new Exception('Missing date parameter');
    }

    // Validate YYYY-MM-DD format
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
        throw new Exception('Invalid date format. Use YYYY-MM-DD.');
    }

    $sql = "SELECT * FROM reservations WHERE reservation_date = ? ORDER BY start_time ASC";
    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        throw new Exception('Prepare failed: ' . $conn->error);
    }

    $stmt->bind_param("s", $date);
    $stmt->execute();

    $result = $stmt->get_result();

    $reservations = [];

    while ($row = $result->fetch_assoc()) {
        $reservations[] = [
            'id' => $row['id'],
            'name' => $row['name'],
            'contact' => $row['contact'],
            'reservation_date' => $row['reservation_date'],
            'start_time' => $row['start_time'],
            'end_time' => $row['end_time'],
            'size' => $row['size'],
            'status' => $row['status'],
            'type' => $row['type'],
            'request' => $row['request'],
            'note' => $row['note'],
            'color' => getStatusColor($row['status'])
        ];
    }

    echo json_encode($reservations);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
}

function getStatusColor($status) {
    return match (strtolower($status)) {
        'confirmed' => '#10B981', // emerald
        'pending' => '#FBBF24',   // amber
        'cancelled' => '#EF4444', // red
        'completed' => '#3B82F6', // blue
        default => '#9CA3AF',     // gray
    };
}
?>
