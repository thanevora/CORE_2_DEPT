<?php
session_start();
include("../../main_connection.php");

// Database configuration
$db_name = "rest_m3_menu";

// Check database connection
if (!isset($connections[$db_name])) {
    die("❌ Connection not found for $db_name");
}

$conn = $connections[$db_name];

// Fetch real-time review statistics
$stats_query = "SELECT 
    (SELECT COUNT(*) FROM menu WHERE status = 'Pending for approval') AS pending_review,
    (SELECT COUNT(*) FROM menu WHERE status = 'For compliance review') AS compliance_review,
    (SELECT COUNT(*) FROM menu WHERE status = 'Approved') AS approved_review,
    (SELECT COUNT(*) FROM menu WHERE status = 'Rejected') AS rejected_review,
    (SELECT COUNT(*) FROM menu WHERE status = 'For posting') AS for_posting,
    (SELECT COUNT(*) FROM menu) AS total_menu_reviews,
    (SELECT COUNT(DISTINCT category) FROM menu WHERE status = 'Pending for approval') AS pending_categories,
    (SELECT COUNT(*) FROM menu WHERE DATE(created_at) = CURDATE()) AS created_today,
    (SELECT COUNT(*) FROM menu WHERE DATE(updated_at) = CURDATE() AND status != 'Pending for approval') AS updated_today";

$stats_result = $conn->query($stats_query);

if (!$stats_result) {
    echo json_encode(['status' => 'error', 'message' => 'Failed to fetch statistics']);
    exit;
}

$stats = $stats_result->fetch_assoc();

// Calculate additional metrics
$total_pending = $stats['pending_review'] + $stats['compliance_review'];
$approval_rate = $stats['total_menu_reviews'] > 0 ? 
    round(($stats['approved_review'] / $stats['total_menu_reviews']) * 100, 1) : 0;

// Prepare response
$response = [
    'status' => 'success',
    'data' => [
        'pending_review' => (int)$stats['pending_review'],
        'compliance_review' => (int)$stats['compliance_review'],
        'approved_review' => (int)$stats['approved_review'],
        'rejected_review' => (int)$stats['rejected_review'],
        'for_posting' => (int)$stats['for_posting'],
        'total_menu_reviews' => (int)$stats['total_menu_reviews'],
        'pending_categories' => (int)$stats['pending_categories'],
        'created_today' => (int)$stats['created_today'],
        'updated_today' => (int)$stats['updated_today'],
        'total_pending' => $total_pending,
        'approval_rate' => $approval_rate,
        'timestamp' => date('Y-m-d H:i:s')
    ],
    'trends' => [
        'pending_trend' => getTrendData($conn, 'Pending for approval'),
        'approval_trend' => getApprovalTrend($conn),
        'daily_activity' => getDailyActivity($conn)
    ]
];

header('Content-Type: application/json');
echo json_encode($response);

// Helper function to get trend data
function getTrendData($conn, $status) {
    $trend_query = "SELECT 
        COUNT(*) as count,
        DATE(created_at) as date
        FROM menu 
        WHERE status = ? 
        AND created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
        GROUP BY DATE(created_at)
        ORDER BY date DESC
        LIMIT 7";
    
    $stmt = $conn->prepare($trend_query);
    $stmt->bind_param("s", $status);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $trend_data = [];
    while ($row = $result->fetch_assoc()) {
        $trend_data[] = [
            'date' => $row['date'],
            'count' => (int)$row['count']
        ];
    }
    
    return $trend_data;
}

// Helper function to get approval trend
function getApprovalTrend($conn) {
    $trend_query = "SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status = 'Approved' THEN 1 ELSE 0 END) as approved,
        DATE(created_at) as date
        FROM menu 
        WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
        GROUP BY DATE(created_at)
        ORDER BY date DESC
        LIMIT 7";
    
    $result = $conn->query($trend_query);
    
    $approval_trend = [];
    while ($row = $result->fetch_assoc()) {
        $rate = $row['total'] > 0 ? round(($row['approved'] / $row['total']) * 100, 1) : 0;
        $approval_trend[] = [
            'date' => $row['date'],
            'rate' => $rate,
            'total' => (int)$row['total'],
            'approved' => (int)$row['approved']
        ];
    }
    
    return $approval_trend;
}

// Helper function to get daily activity
function getDailyActivity($conn) {
    $activity_query = "SELECT 
        status,
        COUNT(*) as count,
        HOUR(created_at) as hour
        FROM menu 
        WHERE DATE(created_at) = CURDATE()
        GROUP BY status, HOUR(created_at)
        ORDER BY hour ASC";
    
    $result = $conn->query($activity_query);
    
    $daily_activity = [];
    while ($row = $result->fetch_assoc()) {
        $daily_activity[] = [
            'status' => $row['status'],
            'count' => (int)$row['count'],
            'hour' => (int)$row['hour']
        ];
    }
    
    return $daily_activity;
}
?>