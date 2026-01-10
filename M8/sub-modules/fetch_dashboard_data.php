<?php
include("../../main_connection.php");

$db_name = "rest_m8_table_turnover";

if (!isset($connections[$db_name])) {
    die(json_encode(['success' => false, 'error' => "Connection not found for $db_name"]));
}

$conn = $connections[$db_name];

// Function to calculate time difference in minutes
function timeDiffInMinutes($start, $end = null) {
    if (!$end) $end = date('Y-m-d H:i:s');
    $start = new DateTime($start);
    $end = new DateTime($end);
    $diff = $start->diff($end);
    return ($diff->days * 24 * 60) + ($diff->h * 60) + $diff->i;
}

$response = [
    'success' => true,
    'last_updated' => date('H:i:s')
];

try {
    // 1. Get average wait time
    $query = "SELECT AVG(TIMESTAMPDIFF(MINUTE, total_wait_time_minutes, last_seated_at)) as avg_wait 
              FROM table_metrics 
              WHERE last_seated_at IS NOT NULL 
              AND DATE(total_wait_time_minutes) = CURDATE()";
    $result = $conn->query($query);
    $row = $result->fetch_assoc();
    $response['avg_wait_time'] = round($row['avg_wait'] ?? 0);
    
    // 2. Get average table turnover
    $query = "SELECT AVG(turnover_count) as avg_turnover, MAX(turnover_count) as max_turnover
              FROM (
                  SELECT table_id, COUNT(*) as turnover_count
                  FROM table_metrics
                  WHERE DATE(last_cleared_at) = CURDATE()
                  GROUP BY table_id
              ) as turnovers";
    $result = $conn->query($query);
    $row = $result->fetch_assoc();
    $response['avg_table_turnover'] = round($row['avg_turnover'] ?? 0);
    $response['max_turnover'] = $row['max_turnover'] ?? 0;
    
    // 3. Get current waitlist count
    $query = "SELECT COUNT(*) as waitlist_count FROM table_metrics WHERE last_seated_at IS NULL";
    $result = $conn->query($query);
    $row = $result->fetch_assoc();
    $response['current_waitlist'] = $row['waitlist_count'] ?? 0;
    
    // Estimate wait time
    $response['est_wait_time'] = $response['current_waitlist'] * ($response['avg_wait_time'] / max(1, $response['avg_table_turnover']));
    $response['est_wait_time'] = round(min($response['est_wait_time'], 120));
    
    // 4. Get waitlist details (modified to match frontend expectations)
    $query = "SELECT 
                metric_id as wait_id,
                table_id,
                CONCAT('Table ', table_id) as party_name,
                4 as party_size,
                total_wait_time_minutes as wait_since,
                TIMESTAMPDIFF(MINUTE, total_wait_time_minutes, NOW()) as wait_time,
                IF(last_seated_at IS NULL, 'waiting', 'seated') as status
              FROM table_metrics
              WHERE last_seated_at IS NULL
              ORDER BY total_wait_time_minutes ASC";
    $result = $conn->query($query);
    $response['waitlist'] = [];
    while ($row = $result->fetch_assoc()) {
        $response['waitlist'][] = $row;
    }
    
    // 5. Get table occupancy
    $total_tables_query = "SELECT COUNT(DISTINCT table_id) as total FROM table_metrics";
    $total_tables_result = $conn->query($total_tables_query);
    $total_tables = $total_tables_result->fetch_assoc()['total'] ?? 0;

    $occupied_tables_query = "SELECT COUNT(DISTINCT table_id) as occupied FROM table_metrics WHERE last_cleared_at IS NULL";
    $occupied_tables_result = $conn->query($occupied_tables_query);
    $occupied_tables = $occupied_tables_result->fetch_assoc()['occupied'] ?? 0;
    
    $response['occupancy_rate'] = round(($occupied_tables / max(1, $total_tables)) * 100);
    $response['occupied_tables'] = $occupied_tables;
    $response['total_tables'] = $total_tables;
    
} catch (Exception $e) {
    $response = [
        'success' => false,
        'error' => $e->getMessage()
    ];
}

header('Content-Type: application/json');
echo json_encode($response);
?>