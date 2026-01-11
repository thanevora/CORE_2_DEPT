<?php
session_start();

// Database connection and data fetching logic
include("../main_connection.php");

$db_name = "rest_m8_table_turnover";

if (!isset($connections[$db_name])) {
    die("Connection not found for $db_name");
}

$conn = $connections[$db_name];

// Connect to rest_m1_trs database for images
$trs_db_name = "rest_m1_trs";
if (!isset($connections[$trs_db_name])) {
    die("Connection not found for $trs_db_name");
}
$trs_conn = $connections[$trs_db_name];

// Function to calculate time difference in minutes
function timeDiffInMinutes($start, $end = null) {
    if (!$end) $end = date('Y-m-d H:i:s');
    $start = new DateTime($start);
    $end = new DateTime($end);
    $diff = $start->diff($end);
    return ($diff->days * 24 * 60) + ($diff->h * 60) + $diff->i;
}

// Function to get table image URL
function getTableImage($table_id, $trs_conn) {
    $image_filename = null;
    
    // Try to fetch from rest_m1_trs database
    $query = "SELECT `image_url` FROM tables WHERE table_id = ?";
    $stmt = $trs_conn->prepare($query);
    if ($stmt) {
        $stmt->bind_param("s", $table_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $image_filename = basename($row['image_url']);
        }
        $stmt->close();
    }
    
    // If no image in database, construct filename
    if (!$image_filename || empty($image_filename)) {
        // Check for image files in the directory
        $image_dir = "Table_images/";
        $extensions = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
        
        foreach ($extensions as $ext) {
            $test_file = "table_{$table_id}.{$ext}";
            if (file_exists($image_dir . $test_file)) {
                $image_filename = $test_file;
                break;
            }
        }
        
        // If still no file found, return null
        if (!$image_filename) {
            $image_filename = null;
        }
    }
    
    return $image_filename;
}

// Pagination variables
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 12;
$offset = ($page - 1) * $limit;

// Search and filter variables
$search = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';
$status_filter = isset($_GET['status']) ? $_GET['status'] : 'all';

// Fetch waitlist data with pagination, search, and filters
$where_conditions = [];
$params = [];
$types = '';

if ($search) {
    $where_conditions[] = "(party_name LIKE ? OR table_id LIKE ?)";
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
    $types .= 'ss';
}

if ($status_filter !== 'all') {
    if ($status_filter === 'waiting') {
        $where_conditions[] = "last_seated_at IS NULL";
    } elseif ($status_filter === 'seated') {
        $where_conditions[] = "last_seated_at IS NOT NULL";
    } elseif ($status_filter === 'available') {
        $where_conditions[] = "last_cleared_at IS NOT NULL AND last_seated_at IS NULL";
    }
}

$where_clause = '';
if (!empty($where_conditions)) {
    $where_clause = 'WHERE ' . implode(' AND ', $where_conditions);
}

// Get total count for pagination
$count_query = "SELECT COUNT(*) as total FROM table_metrics $where_clause";
$stmt = $conn->prepare($count_query);
if ($types) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$count_result = $stmt->get_result();
$total_rows = $count_result->fetch_assoc()['total'];
$total_pages = ceil($total_rows / $limit);

// Fetch paginated waitlist data with images
$query = "SELECT 
            metric_id as wait_id,
            table_id,
            CONCAT('Table ', table_id) as party_name,
            4 as party_size,
            total_wait_time_minutes as wait_since,
            TIMESTAMPDIFF(MINUTE, total_wait_time_minutes, NOW()) as wait_time,
            last_seated_at,
            last_cleared_at,
            CASE 
                WHEN last_cleared_at IS NOT NULL AND last_seated_at IS NULL THEN 'available'
                WHEN last_seated_at IS NULL THEN 'waiting'
                ELSE 'seated'
            END as status
          FROM table_metrics
          $where_clause
          ORDER BY total_wait_time_minutes ASC
          LIMIT ? OFFSET ?";

$params[] = $limit;
$params[] = $offset;
$types .= 'ii';

$stmt = $conn->prepare($query);
if ($types) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$waitlist_result = $stmt->get_result();
$waitlist = [];
while ($row = $waitlist_result->fetch_assoc()) {
    // Get table image for each row
    $row['image_url'] = getTableImage($row['table_id'], $trs_conn);
    $waitlist[] = $row;
}

// Fetch dashboard data with additional statistics
$dashboard_data = [
    'success' => true,
    'last_updated' => date('H:i:s'),
    'avg_wait_time' => 0,
    'avg_table_turnover' => 0,
    'turnover_rate' => 0,
    'wait_time_rate' => 0,
    'max_turnover' => 0,
    'current_waitlist' => 0,
    'est_wait_time' => 0,
    'occupied_tables' => 0,
    'total_tables' => 0,
    'reserved_tables' => 0,
    'vacant_tables' => 0,
    'available_tables' => 0,
    'waitlist' => [],
    'total_revenue' => 0,
    'avg_table_occupancy' => 0,
    'peak_hour' => 'N/A',
    'avg_party_size' => 0,
    'busiest_table' => 'N/A',
    'completion_rate' => 0,
    'customer_satisfaction' => 0,
    'avg_dining_time' => 45,
    'revenue_per_table' => 0
];

try {
    // 1. Get total tables and occupancy stats
    $total_tables_query = "SELECT COUNT(DISTINCT table_id) as total FROM table_metrics";
    $total_tables_result = $conn->query($total_tables_query);
    $total_tables = $total_tables_result->fetch_assoc()['total'] ?? 0;
    
    $occupied_tables_query = "SELECT COUNT(DISTINCT table_id) as occupied FROM table_metrics WHERE last_seated_at IS NOT NULL AND last_cleared_at IS NULL";
    $occupied_tables_result = $conn->query($occupied_tables_query);
    $occupied_tables = $occupied_tables_result->fetch_assoc()['occupied'] ?? 0;
    
    // Get reserved tables (tables that are booked/reserved)
    $reserved_tables_query = "SELECT COUNT(DISTINCT table_id) as reserved FROM table_metrics 
                             WHERE last_seated_at IS NULL AND total_wait_time_minutes IS NOT NULL";
    $reserved_tables_result = $conn->query($reserved_tables_query);
    $reserved_tables = $reserved_tables_result->fetch_assoc()['reserved'] ?? 0;
    
    // Get available tables
    $available_tables_query = "SELECT COUNT(DISTINCT table_id) as available FROM table_metrics 
                              WHERE last_cleared_at IS NOT NULL AND last_seated_at IS NULL";
    $available_tables_result = $conn->query($available_tables_query);
    $available_tables = $available_tables_result->fetch_assoc()['available'] ?? 0;
    
    $dashboard_data['occupied_tables'] = $occupied_tables;
    $dashboard_data['total_tables'] = $total_tables;
    $dashboard_data['reserved_tables'] = $reserved_tables;
    $dashboard_data['available_tables'] = $available_tables;
    $dashboard_data['vacant_tables'] = $total_tables - $occupied_tables - $reserved_tables;
    
    // 2. Get average wait time (today)
    $query = "SELECT AVG(TIMESTAMPDIFF(MINUTE, total_wait_time_minutes, last_seated_at)) as avg_wait 
              FROM table_metrics 
              WHERE last_seated_at IS NOT NULL 
              AND DATE(total_wait_time_minutes) = CURDATE()";
    $result = $conn->query($query);
    $row = $result->fetch_assoc();
    $dashboard_data['avg_wait_time'] = round($row['avg_wait'] ?? 0);
    
    // 3. Get average table turnover and calculate turnover rate (today)
    $query = "SELECT 
                COALESCE(AVG(turnover_count), 0) as avg_turnover, 
                COALESCE(MAX(turnover_count), 0) as max_turnover,
                COALESCE((COUNT(DISTINCT CASE WHEN turnover_count > 0 THEN table_id END) / NULLIF(COUNT(DISTINCT table_id), 0) * 100), 0) as turnover_rate
              FROM (
                  SELECT table_id, COUNT(*) as turnover_count
                  FROM table_metrics
                  WHERE DATE(last_cleared_at) = CURDATE()
                  GROUP BY table_id
              ) as turnovers";
    $result = $conn->query($query);
    $row = $result->fetch_assoc();
    $dashboard_data['avg_table_turnover'] = round($row['avg_turnover'] ?? 0, 1);
    $dashboard_data['max_turnover'] = $row['max_turnover'] ?? 0;
    $dashboard_data['turnover_rate'] = round($row['turnover_rate'] ?? 0);
    
    // 4. Calculate wait time rate (percentage of tables with wait time < 30 mins)
    $query = "SELECT 
                COALESCE((COUNT(CASE WHEN TIMESTAMPDIFF(MINUTE, total_wait_time_minutes, NOW()) < 30 THEN 1 END) / 
                NULLIF(COUNT(*), 0) * 100), 0) as wait_time_rate
              FROM table_metrics
              WHERE last_seated_at IS NULL
              AND total_wait_time_minutes IS NOT NULL";
    $result = $conn->query($query);
    $row = $result->fetch_assoc();
    $dashboard_data['wait_time_rate'] = round($row['wait_time_rate'] ?? 0);
    
    // 5. Get current waitlist count
    $query = "SELECT COUNT(*) as waitlist_count FROM table_metrics WHERE last_seated_at IS NULL";
    $result = $conn->query($query);
    $row = $result->fetch_assoc();
    $dashboard_data['current_waitlist'] = $row['waitlist_count'] ?? 0;
    
    // Estimate wait time
    if ($dashboard_data['avg_table_turnover'] > 0) {
        $dashboard_data['est_wait_time'] = $dashboard_data['current_waitlist'] * ($dashboard_data['avg_wait_time'] / $dashboard_data['avg_table_turnover']);
        $dashboard_data['est_wait_time'] = round(min($dashboard_data['est_wait_time'], 120));
    } else {
        $dashboard_data['est_wait_time'] = 0;
    }
    
    // 6. Average table occupancy rate (today)
    $query = "SELECT 
                COALESCE((COUNT(DISTINCT CASE WHEN last_seated_at IS NOT NULL AND DATE(last_seated_at) = CURDATE() THEN table_id END) / 
                NULLIF(COUNT(DISTINCT table_id), 0) * 100), 0) as avg_occupancy
              FROM table_metrics
              WHERE DATE(last_seated_at) = CURDATE() OR DATE(last_cleared_at) = CURDATE()";
    $result = $conn->query($query);
    $row = $result->fetch_assoc();
    $dashboard_data['avg_table_occupancy'] = round($row['avg_occupancy'] ?? 0);
    
    // 7. Peak hour (today)
    $query = "SELECT 
                HOUR(last_seated_at) as hour,
                COUNT(*) as count
              FROM table_metrics
              WHERE DATE(last_seated_at) = CURDATE()
              AND last_seated_at IS NOT NULL
              GROUP BY HOUR(last_seated_at)
              ORDER BY count DESC
              LIMIT 1";
    $result = $conn->query($query);
    if ($row = $result->fetch_assoc()) {
        $hour = $row['hour'] ?? 0;
        $dashboard_data['peak_hour'] = sprintf('%02d:00 - %02d:00', $hour, $hour + 1);
    } else {
        $dashboard_data['peak_hour'] = 'N/A';
    }
    
    // 8. Average party size (today)
    $query = "SELECT COALESCE(AVG(party_size), 4) as avg_party_size FROM table_metrics WHERE party_size > 0 AND DATE(last_seated_at) = CURDATE()";
    $result = $conn->query($query);
    $row = $result->fetch_assoc();
    $dashboard_data['avg_party_size'] = round($row['avg_party_size'] ?? 4, 1);
    
    // 9. Busiest table (most turnovers today)
    $query = "SELECT 
                table_id,
                COUNT(*) as turnover_count
              FROM table_metrics
              WHERE DATE(last_cleared_at) = CURDATE()
              GROUP BY table_id
              ORDER BY turnover_count DESC
              LIMIT 1";
    $result = $conn->query($query);
    if ($row = $result->fetch_assoc()) {
        $dashboard_data['busiest_table'] = 'Table ' . $row['table_id'] . ' (' . $row['turnover_count'] . ' turns)';
    } else {
        $dashboard_data['busiest_table'] = 'N/A';
    }
    
    // 10. Completion rate (tables that went from waiting to seated today)
    $query = "SELECT 
                COALESCE((COUNT(CASE WHEN last_seated_at IS NOT NULL AND DATE(total_wait_time_minutes) = CURDATE() THEN 1 END) / 
                NULLIF(COUNT(CASE WHEN total_wait_time_minutes IS NOT NULL AND DATE(total_wait_time_minutes) = CURDATE() THEN 1 END), 0) * 100), 100) as completion_rate
              FROM table_metrics
              WHERE DATE(total_wait_time_minutes) = CURDATE()";
    $result = $conn->query($query);
    $row = $result->fetch_assoc();
    $dashboard_data['completion_rate'] = round($row['completion_rate'] ?? 100);
    
    // 11. Customer satisfaction (based on wait time vs seated time today)
    $query = "SELECT 
                COALESCE(AVG(CASE 
                    WHEN TIMESTAMPDIFF(MINUTE, total_wait_time_minutes, last_seated_at) <= 30 THEN 100
                    WHEN TIMESTAMPDIFF(MINUTE, total_wait_time_minutes, last_seated_at) <= 60 THEN 80
                    WHEN TIMESTAMPDIFF(MINUTE, total_wait_time_minutes, last_seated_at) <= 90 THEN 60
                    ELSE 40
                END), 85) as satisfaction_rate
              FROM table_metrics
              WHERE last_seated_at IS NOT NULL
              AND DATE(last_seated_at) = CURDATE()
              AND total_wait_time_minutes IS NOT NULL";
    $result = $conn->query($query);
    $row = $result->fetch_assoc();
    $dashboard_data['customer_satisfaction'] = round($row['satisfaction_rate'] ?? 85);
    
    // 12. Average dining time (today)
    $query = "SELECT COALESCE(AVG(TIMESTAMPDIFF(MINUTE, last_seated_at, last_cleared_at)), 45) as avg_dining_time 
              FROM table_metrics 
              WHERE last_seated_at IS NOT NULL 
              AND last_cleared_at IS NOT NULL
              AND DATE(last_seated_at) = CURDATE()";
    $result = $conn->query($query);
    $row = $result->fetch_assoc();
    $dashboard_data['avg_dining_time'] = round($row['avg_dining_time'] ?? 45);
    
    // 13. Get waitlist details with images
    $dashboard_data['waitlist'] = $waitlist;
    
} catch (Exception $e) {
    $dashboard_data = [
        'success' => false,
        'error' => $e->getMessage()
    ];
}

// Handle CRUD actions via AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $response = ['success' => false, 'message' => ''];
    
    try {
        switch ($_POST['action']) {
            case 'set_available':
                if (isset($_POST['metric_id'])) {
                    $query = "UPDATE table_metrics SET last_cleared_at = NOW(), last_seated_at = NULL WHERE metric_id = ?";
                    $stmt = $conn->prepare($query);
                    $stmt->bind_param("i", $_POST['metric_id']);
                    if ($stmt->execute()) {
                        $response['success'] = true;
                        $response['message'] = 'Table marked as available successfully';
                    }
                    $stmt->close();
                }
                break;
                
            case 'disable_table':
                if (isset($_POST['table_id'])) {
                    $query = "UPDATE table_metrics SET last_seated_at = NOW(), last_cleared_at = NULL WHERE table_id = ?";
                    $stmt = $conn->prepare($query);
                    $stmt->bind_param("s", $_POST['table_id']);
                    if ($stmt->execute()) {
                        $response['success'] = true;
                        $response['message'] = 'Table disabled successfully';
                    }
                    $stmt->close();
                }
                break;
                
            case 'update_table':
                if (isset($_POST['metric_id'], $_POST['party_name'], $_POST['party_size'])) {
                    $table_id = $_POST['table_id'];
                    if (isset($_POST['party_name']) && strpos($_POST['party_name'], 'Table ') === 0) {
                        $table_id = str_replace('Table ', '', $_POST['party_name']);
                    }
                    
                    $query = "UPDATE table_metrics SET table_id = ?, party_name = ?, party_size = ? WHERE metric_id = ?";
                    $stmt = $conn->prepare($query);
                    $stmt->bind_param("ssii", $table_id, $_POST['party_name'], $_POST['party_size'], $_POST['metric_id']);
                    if ($stmt->execute()) {
                        $response['success'] = true;
                        $response['message'] = 'Table updated successfully';
                    }
                    $stmt->close();
                }
                break;
                
            case 'delete_table':
                if (isset($_POST['metric_id'])) {
                    $query = "DELETE FROM table_metrics WHERE metric_id = ?";
                    $stmt = $conn->prepare($query);
                    $stmt->bind_param("i", $_POST['metric_id']);
                    if ($stmt->execute()) {
                        $response['success'] = true;
                        $response['message'] = 'Table deleted successfully';
                    }
                    $stmt->close();
                }
                break;
        }
    } catch (Exception $e) {
        $response['error'] = $e->getMessage();
    }
    
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}

// Convert data to JSON for JavaScript usage
$dashboard_json = json_encode($dashboard_data);
?>
<!DOCTYPE html>
<html lang="en">
<?php include '../header.php'; ?>

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Table Management Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/daisyui@4.4.20/dist/full.css" rel="stylesheet" type="text/css" />
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
    <style>
        :root {
            --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --secondary-gradient: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            --success-gradient: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            --warning-gradient: linear-gradient(135deg, #f6d365 0%, #fda085 100%);
            --info-gradient: linear-gradient(135deg, #a8edea 0%, #fed6e3 100%);
        }
        
        .glass-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
        }
        
        .stat-card {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
        }
        
        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: 0.5s;
        }
        
        .stat-card:hover::before {
            left: 100%;
        }
        
        .gradient-bg-primary {
            background: var(--primary-gradient);
        }
        
        .gradient-bg-secondary {
            background: var(--secondary-gradient);
        }
        
        .gradient-bg-success {
            background: var(--success-gradient);
        }
        
        .gradient-bg-warning {
            background: var(--warning-gradient);
        }
        
        .gradient-bg-info {
            background: var(--info-gradient);
        }
        
        .hover-lift {
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .hover-lift:hover {
            transform: translateY(-4px);
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }
        
        .animate-pulse-slow {
            animation: pulse 3s cubic-bezier(0.4, 0, 0.6, 1) infinite;
        }
        
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }
        
        .table-card {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        .table-card:hover {
            transform: translateY(-2px) scale(1.01);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.12);
        }
        
        .status-badge {
            transition: all 0.3s ease;
        }
        
        .image-container {
            position: relative;
            overflow: hidden;
        }
        
        .image-container img {
            transition: transform 0.5s ease;
        }
        
        .image-container:hover img {
            transform: scale(1.05);
        }
        
        .progress-ring {
            transform: rotate(-90deg);
        }
        
        .progress-ring-circle {
            transition: stroke-dashoffset 0.5s ease;
        }
        
        .modal-backdrop {
            background: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(4px);
        }
        
        .modal-container {
            animation: modalSlideIn 0.3s ease-out;
        }
        
        @keyframes modalSlideIn {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>
</head>
<body class="bg-gradient-to-br from-gray-50 to-gray-100 min-h-screen">
  <div class="flex h-screen">
    <!-- Sidebar -->
    <?php include '../sidebarr.php'; ?>

    <!-- Content Area -->
    <div class="flex flex-col flex-1 overflow-hidden">
        <!-- Navbar -->
        <?php include '../navbar.php'; ?>

        <main class="flex-1 overflow-y-auto p-6">
            <!-- Header -->
            <div class="mb-8">
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <div>
                        <h1 class="text-3xl font-bold text-gray-800">Table Management Dashboard</h1>
                        <p class="text-gray-600 mt-2">Monitor and manage table turnover, wait times, and occupancy</p>
                    </div>
                    <div class="flex items-center gap-3">
                        <div class="badge badge-lg badge-primary gap-2">
                            <i data-lucide="clock" class="w-4 h-4"></i>
                            <span id="live-time"><?php echo date('H:i:s'); ?></span>
                        </div>
                        <button onclick="window.location.reload()" class="btn btn-sm btn-outline gap-2 hover-lift">
                            <i data-lucide="refresh-ccw" class="w-4 h-4"></i>
                            Refresh
                        </button>
                    </div>
                </div>
            </div>
            
            <!-- Stats Grid (9 Cards) -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6 mb-8">
                <!-- Total Tables -->
                <div class="stat-card glass-card rounded-2xl p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-500 mb-2">Total Tables</p>
                            <h3 class="text-3xl font-bold text-gray-800"><?php echo $dashboard_data['total_tables']; ?></h3>
                            <div class="flex items-center gap-3 mt-3">
                                <div class="flex items-center gap-1">
                                    <div class="w-2 h-2 rounded-full bg-green-500"></div>
                                    <span class="text-xs text-gray-600">Available: <?php echo $dashboard_data['available_tables']; ?></span>
                                </div>
                                <div class="flex items-center gap-1">
                                    <div class="w-2 h-2 rounded-full bg-blue-500"></div>
                                    <span class="text-xs text-gray-600">In use: <?php echo $dashboard_data['occupied_tables']; ?></span>
                                </div>
                            </div>
                        </div>
                        <div class="p-4 rounded-full gradient-bg-primary">
                            <i data-lucide="table" class="w-8 h-8 text-white"></i>
                        </div>
                    </div>
                    <div class="mt-4">
                        <div class="h-2 bg-gray-200 rounded-full overflow-hidden">
                            <div class="h-full bg-gradient-to-r from-blue-500 to-purple-500 rounded-full" 
                                 style="width: <?php echo min(100, ($dashboard_data['occupied_tables'] / max(1, $dashboard_data['total_tables'])) * 100); ?>%"></div>
                        </div>
                    </div>
                </div>

                <!-- Occupancy Rate -->
                <div class="stat-card glass-card rounded-2xl p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-500 mb-2">Occupancy Rate</p>
                            <h3 class="text-3xl font-bold text-gray-800"><?php echo $dashboard_data['avg_table_occupancy']; ?>%</h3>
                            <p class="text-xs text-gray-600 mt-2">Peak: <?php echo $dashboard_data['peak_hour']; ?></p>
                        </div>
                        <div class="p-4 rounded-full gradient-bg-secondary">
                            <i data-lucide="users" class="w-8 h-8 text-white"></i>
                        </div>
                    </div>
                    <div class="mt-4">
                        <div class="flex items-center gap-2">
                            <div class="flex-1 h-2 bg-gray-200 rounded-full overflow-hidden">
                                <div class="h-full bg-gradient-to-r from-pink-500 to-red-500 rounded-full" 
                                     style="width: <?php echo $dashboard_data['avg_table_occupancy']; ?>%"></div>
                            </div>
                            <span class="text-xs font-medium text-gray-600"><?php echo $dashboard_data['avg_table_occupancy']; ?>%</span>
                        </div>
                    </div>
                </div>

                <!-- Turnover Rate -->
                <div class="stat-card glass-card rounded-2xl p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-500 mb-2">Turnover Rate</p>
                            <h3 class="text-3xl font-bold text-gray-800"><?php echo $dashboard_data['turnover_rate']; ?>%</h3>
                            <div class="flex items-center gap-3 mt-3">
                                <div class="flex items-center gap-1">
                                    <i data-lucide="repeat" class="w-3 h-3 text-gray-500"></i>
                                    <span class="text-xs text-gray-600">Avg: <?php echo $dashboard_data['avg_table_turnover']; ?>x</span>
                                </div>
                                <div class="flex items-center gap-1">
                                    <i data-lucide="trending-up" class="w-3 h-3 text-gray-500"></i>
                                    <span class="text-xs text-gray-600">Max: <?php echo $dashboard_data['max_turnover']; ?>x</span>
                                </div>
                            </div>
                        </div>
                        <div class="p-4 rounded-full gradient-bg-success">
                            <i data-lucide="repeat" class="w-8 h-8 text-white"></i>
                        </div>
                    </div>
                </div>

                <!-- Wait Time -->
                <div class="stat-card glass-card rounded-2xl p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-500 mb-2">Average Wait Time</p>
                            <h3 class="text-3xl font-bold text-gray-800"><?php echo $dashboard_data['avg_wait_time']; ?> min</h3>
                            <p class="text-xs text-gray-600 mt-2">Current waitlist: <?php echo $dashboard_data['current_waitlist']; ?> parties</p>
                        </div>
                        <div class="p-4 rounded-full gradient-bg-warning">
                            <i data-lucide="clock" class="w-8 h-8 text-white"></i>
                        </div>
                    </div>
                    <div class="mt-4">
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-gray-600">Est. wait: <?php echo $dashboard_data['est_wait_time']; ?> min</span>
                            <span class="text-gray-600">Rate: <?php echo $dashboard_data['wait_time_rate']; ?>%</span>
                        </div>
                    </div>
                </div>

                <!-- Customer Satisfaction -->
                <div class="stat-card glass-card rounded-2xl p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-500 mb-2">Customer Satisfaction</p>
                            <h3 class="text-3xl font-bold text-gray-800"><?php echo $dashboard_data['customer_satisfaction']; ?>%</h3>
                            <div class="flex items-center gap-1 mt-2">
                                <?php for($i = 1; $i <= 5; $i++): ?>
                                    <i data-lucide="star" class="w-4 h-4 <?php echo $i <= round($dashboard_data['customer_satisfaction'] / 20) ? 'text-yellow-400 fill-yellow-400' : 'text-gray-300'; ?>"></i>
                                <?php endfor; ?>
                            </div>
                        </div>
                        <div class="p-4 rounded-full gradient-bg-info">
                            <i data-lucide="heart" class="w-8 h-8 text-white"></i>
                        </div>
                    </div>
                </div>

                <!-- Average Party Size -->
                <div class="stat-card glass-card rounded-2xl p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-500 mb-2">Avg Party Size</p>
                            <h3 class="text-3xl font-bold text-gray-800"><?php echo $dashboard_data['avg_party_size']; ?></h3>
                            <p class="text-xs text-gray-600 mt-2">Today's average</p>
                        </div>
                        <div class="p-4 rounded-full bg-gradient-to-br from-orange-400 to-pink-500">
                            <i data-lucide="users" class="w-8 h-8 text-white"></i>
                        </div>
                    </div>
                </div>

                <!-- Busiest Table -->
                <div class="stat-card glass-card rounded-2xl p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-500 mb-2">Busiest Table</p>
                            <h3 class="text-xl font-bold text-gray-800 truncate"><?php echo $dashboard_data['busiest_table']; ?></h3>
                            <p class="text-xs text-gray-600 mt-2">Most turnovers today</p>
                        </div>
                        <div class="p-4 rounded-full bg-gradient-to-br from-red-400 to-yellow-500">
                            <i data-lucide="zap" class="w-8 h-8 text-white"></i>
                        </div>
                    </div>
                </div>

                <!-- Completion Rate -->
                <div class="stat-card glass-card rounded-2xl p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-500 mb-2">Completion Rate</p>
                            <h3 class="text-3xl font-bold text-gray-800"><?php echo $dashboard_data['completion_rate']; ?>%</h3>
                            <p class="text-xs text-gray-600 mt-2">Wait → Seated success</p>
                        </div>
                        <div class="p-4 rounded-full bg-gradient-to-br from-green-400 to-blue-500">
                            <i data-lucide="check-circle" class="w-8 h-8 text-white"></i>
                        </div>
                    </div>
                    <div class="mt-4">
                        <div class="h-2 bg-gray-200 rounded-full overflow-hidden">
                            <div class="h-full bg-gradient-to-r from-green-400 to-blue-500 rounded-full" 
                                 style="width: <?php echo $dashboard_data['completion_rate']; ?>%"></div>
                        </div>
                    </div>
                </div>

                <!-- Available Tables -->
                <div class="stat-card glass-card rounded-2xl p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-500 mb-2">Available Now</p>
                            <h3 class="text-3xl font-bold text-gray-800"><?php echo $dashboard_data['available_tables']; ?></h3>
                            <div class="flex items-center gap-3 mt-3">
                                <div class="flex items-center gap-1">
                                    <i data-lucide="shield" class="w-3 h-3 text-gray-500"></i>
                                    <span class="text-xs text-gray-600">Reserved: <?php echo $dashboard_data['reserved_tables']; ?></span>
                                </div>
                                <div class="flex items-center gap-1">
                                    <i data-lucide="home" class="w-3 h-3 text-gray-500"></i>
                                    <span class="text-xs text-gray-600">Vacant: <?php echo $dashboard_data['vacant_tables']; ?></span>
                                </div>
                            </div>
                        </div>
                        <div class="p-4 rounded-full bg-gradient-to-br from-teal-400 to-emerald-500">
                            <i data-lucide="check" class="w-8 h-8 text-white"></i>
                        </div>
                    </div>
                    <div class="mt-4">
                        <div class="text-xs text-gray-600">Ready for immediate seating</div>
                    </div>
                </div>
            </div>
            
            <!-- Search and Filter Controls -->
            <div class="mb-6 glass-card rounded-2xl p-4">
                <div class="flex flex-col md:flex-row gap-4">
                    <!-- Search Bar -->
                    <div class="flex-1">
                        <div class="relative">
                            <i data-lucide="search" class="absolute left-3 top-1/2 transform -translate-y-1/2 w-5 h-5 text-gray-400"></i>
                            <form method="GET" class="flex gap-2">
                                <input type="text" 
                                       name="search" 
                                       value="<?php echo htmlspecialchars($search); ?>" 
                                       placeholder="Search tables by ID or party name..." 
                                       class="w-full pl-10 pr-4 py-3 rounded-xl border border-gray-300 bg-white focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent shadow-sm">
                                <button type="submit" class="btn btn-primary gap-2 hover-lift">
                                    <i data-lucide="search" class="w-4 h-4"></i>
                                    Search
                                </button>
                            </form>
                        </div>
                    </div>
                    
                    <!-- Status Filter -->
                    <div class="flex items-center gap-4">
                        <div class="form-control">
                            <select name="status" 
                                    onchange="this.form.submit()" 
                                    class="select select-bordered w-full bg-white">
                                <option value="all" <?php echo $status_filter === 'all' ? 'selected' : ''; ?>>All Status</option>
                                <option value="available" <?php echo $status_filter === 'available' ? 'selected' : ''; ?>>Available</option>
                                <option value="waiting" <?php echo $status_filter === 'waiting' ? 'selected' : ''; ?>>Waiting</option>
                                <option value="seated" <?php echo $status_filter === 'seated' ? 'selected' : ''; ?>>Seated</option>
                            </select>
                        </div>
                        
                        <!-- View Toggle -->
                        <div class="flex items-center gap-2">
                            <div class="text-sm text-gray-500">
                                <?php echo $total_rows; ?> total tables
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Current Waitlist Cards -->
            <div class="glass-card rounded-2xl p-6 mb-8">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
                    <div>
                        <h2 class="text-xl font-bold text-gray-800">Table Overview</h2>
                        <p class="text-gray-600 text-sm">Updated: <?php echo $dashboard_data['last_updated']; ?></p>
                    </div>
                    <div class="flex items-center gap-2">
                        <div class="badge badge-primary gap-1">
                            <i data-lucide="grid" class="w-3 h-3"></i>
                            Card View
                        </div>
                        <div class="text-sm text-gray-500">
                            Page <?php echo $page; ?> of <?php echo $total_pages; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Waitlist Cards Grid -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                    <?php if (!empty($waitlist)): ?>
                        <?php foreach ($waitlist as $index => $party): ?>
                            <?php
                            // Determine status colors and icons
                            $statusColors = [
                                'available' => ['bg' => 'bg-gradient-to-br from-green-100 to-green-50', 'text' => 'text-green-700', 'border' => 'border-green-200', 'icon' => 'check-circle', 'ring' => 'ring-green-100'],
                                'seated' => ['bg' => 'bg-gradient-to-br from-blue-100 to-blue-50', 'text' => 'text-blue-700', 'border' => 'border-blue-200', 'icon' => 'user-check', 'ring' => 'ring-blue-100'],
                                'waiting' => ['bg' => 'bg-gradient-to-br from-orange-100 to-orange-50', 'text' => 'text-orange-700', 'border' => 'border-orange-200', 'icon' => 'clock', 'ring' => 'ring-orange-100']
                            ];
                            $status = $party['status'];
                            $statusConfig = $statusColors[$status] ?? $statusColors['waiting'];
                            
                            // Format times
                            $waitSince = date('H:i', strtotime($party['wait_since']));
                            $waitTime = $party['wait_time'] . ' min';
                            $lastSeated = $party['last_seated_at'] ? date('H:i', strtotime($party['last_seated_at'])) : 'N/A';
                            $lastCleared = $party['last_cleared_at'] ? date('H:i', strtotime($party['last_cleared_at'])) : 'N/A';
                            
                            // Get image path
                            $imagePath = !empty($party['image_url']) ? "Table_images/" . htmlspecialchars($party['image_url']) : '';
                            $placeholderSVG = 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMzAwIiBoZWlnaHQ9IjIwMCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iMzAwIiBoZWlnaHQ9IjIwMCIgZmlsbD0iI2YwZjBmMCIvPjx0ZXh0IHg9IjUwJSIgeT0iNTAlIiBmb250LWZhbWlseT0iQXJpYWwiIGZvbnQtc2l6ZT0iMTQiIHRleHQtYW5jaG9yPSJtaWRkbGUiIGRvbWluYW50LWJhc2VsaW5lPSJtaWRkbGUiIGZpbGw9IiM5OTkiPlRhYmxlICc8L3RleHQ+PC9zdmc+';
                            ?>
                            
                            <div class="table-card bg-white rounded-xl shadow-md hover:shadow-xl border <?php echo $statusConfig['border']; ?> overflow-hidden <?php echo $statusConfig['ring']; ?> ring-2">
                                <!-- Card Image -->
                                <div class="image-container h-48 overflow-hidden">
                                    <?php if (!empty($party['image_url'])): ?>
                                        <img src="<?php echo $imagePath; ?>" 
                                             alt="Table <?php echo htmlspecialchars($party['table_id']); ?>" 
                                             class="w-full h-full object-cover"
                                             onerror="this.onerror=null; this.src='<?php echo $placeholderSVG; ?>'">
                                    <?php else: ?>
                                        <!-- Fallback when no image URL -->
                                        <div class="w-full h-full flex flex-col items-center justify-center bg-gradient-to-br from-gray-50 to-gray-100">
                                            <i data-lucide="table" class="w-16 h-16 text-gray-300 mb-3"></i>
                                            <p class="text-sm text-gray-400 font-medium">Table <?php echo htmlspecialchars($party['table_id']); ?></p>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <!-- Quick Status Badge -->
                                    <div class="absolute top-3 right-3">
                                        <span class="badge badge-sm <?php echo $statusConfig['bg']; ?> <?php echo $statusConfig['text']; ?> border-0 gap-1 shadow-md">
                                            <i data-lucide="<?php echo $statusConfig['icon']; ?>" class="w-3 h-3"></i>
                                            <?php echo ucfirst($status); ?>
                                        </span>
                                    </div>
                                    
                                    <!-- Queue Position for Waiting -->
                                    <?php if ($status === 'waiting'): ?>
                                    <div class="absolute top-3 left-3">
                                        <span class="badge badge-sm bg-black/70 text-white border-0 shadow-md">
                                            #<?php echo $index + 1 + $offset; ?>
                                        </span>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <!-- View Button Overlay -->
                                    <div class="absolute bottom-3 right-3">
                                        <button class="btn btn-sm btn-circle glass-card shadow-lg hover-lift"
                                                onclick="openViewModal(<?php echo htmlspecialchars(json_encode($party)); ?>)">
                                            <i data-lucide="eye" class="w-4 h-4 text-gray-700"></i>
                                        </button>
                                    </div>
                                </div>
                                
                                <!-- Card Body -->
                                <div class="p-5">
                                    <!-- Table Info Header -->
                                    <div class="flex items-start justify-between mb-4">
                                        <div class="flex-1 min-w-0">
                                            <h3 class="text-lg font-bold truncate text-gray-800 mb-1">
                                                <?php echo htmlspecialchars($party['party_name']); ?>
                                            </h3>
                                            <div class="flex items-center gap-2 text-sm text-gray-500">
                                                <span class="flex items-center gap-1">
                                                    <i data-lucide="hash" class="w-3 h-3"></i>
                                                    ID: <?php echo htmlspecialchars($party['table_id']); ?>
                                                </span>
                                                <span class="flex items-center gap-1">
                                                    <i data-lucide="users" class="w-3 h-3"></i>
                                                    <?php echo htmlspecialchars($party['party_size']); ?>p
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Wait Time Information -->
                                    <div class="space-y-3 mb-5">
                                        <div class="flex justify-between items-center">
                                            <span class="text-sm text-gray-600">Waiting Since:</span>
                                            <span class="font-medium text-gray-800"><?php echo $waitSince; ?></span>
                                        </div>
                                        <div class="flex justify-between items-center">
                                            <span class="text-sm text-gray-600">Wait Time:</span>
                                            <span class="font-bold text-lg <?php echo $statusConfig['text']; ?>"><?php echo $waitTime; ?></span>
                                        </div>
                                        <?php if ($status === 'seated'): ?>
                                        <div class="flex justify-between items-center">
                                            <span class="text-sm text-gray-600">Seated At:</span>
                                            <span class="font-medium text-gray-800"><?php echo $lastSeated; ?></span>
                                        </div>
                                        <?php elseif ($status === 'available'): ?>
                                        <div class="flex justify-between items-center">
                                            <span class="text-sm text-gray-600">Cleared At:</span>
                                            <span class="font-medium text-gray-800"><?php echo $lastCleared; ?></span>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <!-- Progress Bar -->
                                    <div class="mb-5">
                                        <div class="flex justify-between text-xs text-gray-500 mb-1">
                                            <span>Status Progress</span>
                                            <span>
                                                <?php echo match($status) {
                                                    'available' => '100%',
                                                    'seated' => '75%',
                                                    'waiting' => '25%',
                                                    default => '50%'
                                                }; ?>
                                            </span>
                                        </div>
                                        <div class="h-2 bg-gray-200 rounded-full overflow-hidden">
                                            <div class="h-full <?php echo $statusConfig['bg']; ?> rounded-full" 
                                                 style="width: <?php echo match($status) {
                                                    'available' => '100',
                                                    'seated' => '75',
                                                    'waiting' => '25',
                                                    default => '50'
                                                 }; ?>%"></div>
                                        </div>
                                    </div>
                                    
                                    <!-- Action Button -->
                                    <div class="card-actions justify-center">
                                        <button class="btn btn-sm btn-primary w-full gap-2 hover-lift"
                                                onclick="openViewModal(<?php echo htmlspecialchars(json_encode($party)); ?>)">
                                            <i data-lucide="settings" class="w-4 h-4"></i>
                                            Manage Table
                                        </button>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <!-- Empty State -->
                        <div class="col-span-full">
                            <div class="card bg-white shadow-sm border-dashed border-2 border-gray-300">
                                <div class="card-body">
                                    <div class="flex flex-col items-center justify-center py-16">
                                        <div class="w-24 h-24 rounded-full bg-gray-100 flex items-center justify-center mb-6">
                                            <i data-lucide="table" class="w-12 h-12 text-gray-400"></i>
                                        </div>
                                        <h3 class="text-xl font-semibold text-gray-700 mb-3">
                                            <?php echo $search || $status_filter !== 'all' ? 'No matching tables found' : 'All tables are available'; ?>
                                        </h3>
                                        <p class="text-gray-500 text-center max-w-md mb-6">
                                            <?php if ($search || $status_filter !== 'all'): ?>
                                                Try adjusting your search or filter criteria
                                            <?php else: ?>
                                                Great news! All tables are currently available for seating
                                            <?php endif; ?>
                                        </p>
                                        <?php if ($search || $status_filter !== 'all'): ?>
                                            <a href="?" class="btn btn-outline gap-2 hover-lift">
                                                <i data-lucide="x" class="w-4 h-4"></i>
                                                Clear filters
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                <div class="mt-8 pt-6 border-t border-gray-200">
                    <div class="flex flex-col sm:flex-row justify-between items-center gap-4">
                        <div class="text-sm text-gray-500">
                            Showing <span class="font-semibold"><?php echo min(($offset + 1), $total_rows); ?></span>-<span class="font-semibold"><?php echo min(($offset + $limit), $total_rows); ?></span> of <span class="font-semibold"><?php echo $total_rows; ?></span> tables
                        </div>
                        <div class="join">
                            <!-- Previous Page -->
                            <a href="?page=<?php echo max(1, $page - 1); ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status_filter); ?>"
                               class="join-item btn btn-sm <?php echo $page <= 1 ? 'btn-disabled' : 'btn-outline border-gray-300 text-gray-700 hover:bg-gray-100'; ?>">
                                <i data-lucide="chevron-left" class="w-4 h-4"></i>
                            </a>
                            
                            <!-- Page Numbers -->
                            <?php 
                            $start_page = max(1, $page - 2);
                            $end_page = min($total_pages, $page + 2);
                            
                            if ($start_page > 1): ?>
                                <a href="?page=1&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status_filter); ?>"
                                   class="join-item btn btn-sm btn-outline border-gray-300 text-gray-700 hover:bg-gray-100">
                                    1
                                </a>
                                <?php if ($start_page > 2): ?>
                                    <span class="join-item btn btn-sm btn-disabled border-gray-300">...</span>
                                <?php endif; ?>
                            <?php endif; ?>
                            
                            <?php for ($i = $start_page; $i <= $end_page; $i++): ?>
                                <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status_filter); ?>"
                                   class="join-item btn btn-sm <?php echo $i == $page ? 'bg-primary text-white border-primary hover:bg-primary-focus' : 'btn-outline border-gray-300 text-gray-700 hover:bg-gray-100'; ?>">
                                    <?php echo $i; ?>
                                </a>
                            <?php endfor; ?>
                            
                            <?php if ($end_page < $total_pages): ?>
                                <?php if ($end_page < $total_pages - 1): ?>
                                    <span class="join-item btn btn-sm btn-disabled border-gray-300">...</span>
                                <?php endif; ?>
                                <a href="?page=<?php echo $total_pages; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status_filter); ?>"
                                   class="join-item btn btn-sm btn-outline border-gray-300 text-gray-700 hover:bg-gray-100">
                                    <?php echo $total_pages; ?>
                                </a>
                            <?php endif; ?>
                            
                            <!-- Next Page -->
                            <a href="?page=<?php echo min($total_pages, $page + 1); ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status_filter); ?>"
                               class="join-item btn btn-sm <?php echo $page >= $total_pages ? 'btn-disabled' : 'btn-outline border-gray-300 text-gray-700 hover:bg-gray-100'; ?>">
                                <i data-lucide="chevron-right" class="w-4 h-4"></i>
                            </a>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <!-- Enhanced View Modal -->
    <dialog id="viewModal" class="modal modal-bottom sm:modal-middle">
        <div class="modal-backdrop" onclick="document.getElementById('viewModal').close()"></div>
        <div class="modal-box max-w-6xl p-0 modal-container">
            <div class="relative">
                <!-- Close Button -->
                <button class="btn btn-sm btn-circle btn-ghost absolute right-6 top-6 z-10 bg-white/90 shadow-lg hover:bg-white transition-all"
                        onclick="document.getElementById('viewModal').close()">
                    ✕
                </button>
                
                <!-- Modal Content -->
                <div class="p-8">
                    <div class="flex items-center justify-between mb-6">
                        <div>
                            <h3 class="text-2xl font-bold text-gray-800">Table Details</h3>
                            <p class="text-gray-600">View and manage table information</p>
                        </div>
                        <div id="modal-status-badge" class="badge badge-lg gap-2"></div>
                    </div>
                    
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                        <!-- Left Column: Image -->
                        <div class="space-y-6">
                            <div class="card bg-gradient-to-br from-gray-50 to-white shadow-xl overflow-hidden rounded-2xl border border-gray-100">
                                <figure class="relative h-80">
                                    <img id="modal-table-image" 
                                         src="" 
                                         alt="Table Image" 
                                         class="w-full h-full object-contain"
                                         onerror="this.onerror=null; this.src='data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNDAwIiBoZWlnaHQ9IjMwMCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iNDAwIiBoZWlnaHQ9IjMwMCIgZmlsbD0iI2YwZjBmMCIvPjx0ZXh0IHg9IjUwJSIgeT0iNTAlIiBmb250LWZhbWlseT0iQXJpYWwiIGZvbnQtc2l6ZT0iMTYiIHRleHQtYW5jaG9yPSJtaWRkbGUiIGRvbWluYW50LWJhc2VsaW5lPSJtaWRkbGUiIGZpbGw9IiM5OTkiPlRhYmxlIEltYWdlPC90ZXh0Pjwvc3ZnPg=='">
                                    <div class="absolute inset-0 bg-gradient-to-t from-black/10 to-transparent"></div>
                                </figure>
                                <div class="card-body p-6">
                                    <h2 id="modal-image-title" class="card-title text-xl font-bold text-gray-800"></h2>
                                    <p class="text-gray-500">Table Reference Image</p>
                                </div>
                            </div>
                            
                            <!-- Quick Stats -->
                            <div class="grid grid-cols-2 gap-4">
                                <div class="stat bg-gradient-to-br from-blue-50 to-white rounded-xl p-5 shadow-sm border border-blue-100">
                                    <div class="stat-title text-sm text-gray-500 flex items-center gap-2">
                                        <i data-lucide="hash" class="w-4 h-4"></i>
                                        Table ID
                                    </div>
                                    <div id="modal-quick-table-id" class="stat-value text-lg font-bold text-gray-800"></div>
                                </div>
                                <div class="stat bg-gradient-to-br from-green-50 to-white rounded-xl p-5 shadow-sm border border-green-100">
                                    <div class="stat-title text-sm text-gray-500 flex items-center gap-2">
                                        <i data-lucide="users" class="w-4 h-4"></i>
                                        Party Size
                                    </div>
                                    <div id="modal-quick-party-size" class="stat-value text-lg font-bold text-gray-800"></div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Right Column: Information & Actions -->
                        <div class="space-y-6">
                            <!-- Information Card -->
                            <div class="card bg-gradient-to-br from-gray-50 to-white shadow-sm rounded-2xl p-6 border border-gray-100">
                                <h4 class="text-lg font-semibold text-gray-800 mb-4 flex items-center gap-2">
                                    <i data-lucide="info" class="w-5 h-5 text-primary"></i>
                                    Table Information
                                </h4>
                                
                                <div class="space-y-4">
                                    <div class="form-control">
                                        <label class="label">
                                            <span class="label-text font-medium text-gray-700 flex items-center gap-2">
                                                <i data-lucide="hash" class="w-4 h-4"></i>
                                                Table ID
                                            </span>
                                        </label>
                                        <input type="text" id="modal-table-id" class="input input-bordered w-full bg-white focus:bg-white">
                                    </div>
                                    
                                    <div class="form-control">
                                        <label class="label">
                                            <span class="label-text font-medium text-gray-700 flex items-center gap-2">
                                                <i data-lucide="user" class="w-4 h-4"></i>
                                                Party Name
                                            </span>
                                        </label>
                                        <input type="text" id="modal-party-name" class="input input-bordered w-full bg-white focus:bg-white">
                                    </div>
                                    
                                    <div class="form-control">
                                        <label class="label">
                                            <span class="label-text font-medium text-gray-700 flex items-center gap-2">
                                                <i data-lucide="users" class="w-4 h-4"></i>
                                                Party Size
                                            </span>
                                        </label>
                                        <input type="number" id="modal-party-size" class="input input-bordered w-full bg-white focus:bg-white" min="1" max="20">
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Timing Card -->
                            <div class="card bg-gradient-to-br from-gray-50 to-white shadow-sm rounded-2xl p-6 border border-gray-100">
                                <h4 class="text-lg font-semibold text-gray-800 mb-4 flex items-center gap-2">
                                    <i data-lucide="clock" class="w-5 h-5 text-primary"></i>
                                    Timing Information
                                </h4>
                                
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div class="form-control">
                                        <label class="label">
                                            <span class="label-text font-medium text-gray-700">Waiting Since</span>
                                        </label>
                                        <input type="text" id="modal-wait-since" class="input input-bordered w-full bg-gray-50" readonly>
                                    </div>
                                    
                                    <div class="form-control">
                                        <label class="label">
                                            <span class="label-text font-medium text-gray-700">Wait Time</span>
                                        </label>
                                        <input type="text" id="modal-wait-time" class="input input-bordered w-full bg-gray-50" readonly>
                                    </div>
                                    
                                    <div class="form-control">
                                        <label class="label">
                                            <span class="label-text font-medium text-gray-700">Last Seated At</span>
                                        </label>
                                        <input type="text" id="modal-last-seated" class="input input-bordered w-full bg-gray-50" readonly>
                                    </div>
                                    
                                    <div class="form-control">
                                        <label class="label">
                                            <span class="label-text font-medium text-gray-700">Last Cleared At</span>
                                        </label>
                                        <input type="text" id="modal-last-cleared" class="input input-bordered w-full bg-gray-50" readonly>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Actions Card -->
                            <div class="card bg-gradient-to-br from-gray-50 to-white shadow-sm rounded-2xl p-6 border border-gray-100">
                                <h4 class="text-lg font-semibold text-gray-800 mb-4 flex items-center gap-2">
                                    <i data-lucide="settings" class="w-5 h-5 text-primary"></i>
                                    Table Actions
                                </h4>
                                
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                    <button class="btn btn-success gap-2 hover-lift" onclick="markAsAvailable()" id="available-btn">
                                        <i data-lucide="check-circle" class="w-5 h-5"></i>
                                        Set as Available
                                    </button>
                                    <button class="btn btn-warning gap-2 hover-lift" onclick="disableTable()" id="disable-btn">
                                        <i data-lucide="ban" class="w-5 h-5"></i>
                                        Disable Table
                                    </button>
                                    <button class="btn btn-primary gap-2 hover-lift" onclick="updateTable()" id="update-btn">
                                        <i data-lucide="save" class="w-5 h-5"></i>
                                        Save Changes
                                    </button>
                                    <button class="btn btn-error gap-2 hover-lift" onclick="deleteTable()" id="delete-btn">
                                        <i data-lucide="trash-2" class="w-5 h-5"></i>
                                        Delete Table
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </dialog>

    <script>
        // Initialize Lucide icons
        lucide.createIcons();
        
        // Store the initial dashboard data and current table
        const dashboardData = <?php echo $dashboard_json; ?>;
        let currentTableData = null;
        
        // Update live time
        function updateLiveTime() {
            const now = new Date();
            const timeString = now.toLocaleTimeString('en-US', {hour12: false});
            document.getElementById('live-time').textContent = timeString;
        }
        setInterval(updateLiveTime, 1000);
        updateLiveTime();
        
        function openViewModal(tableData) {
            currentTableData = tableData;
            const modal = document.getElementById('viewModal');
            
            // Populate modal fields
            document.getElementById('modal-table-id').value = tableData.table_id;
            document.getElementById('modal-party-name').value = tableData.party_name;
            document.getElementById('modal-party-size').value = tableData.party_size;
            document.getElementById('modal-wait-since').value = new Date(tableData.wait_since).toLocaleString();
            document.getElementById('modal-wait-time').value = tableData.wait_time + ' minutes';
            document.getElementById('modal-last-seated').value = tableData.last_seated_at ? 
                new Date(tableData.last_seated_at).toLocaleString() : 'Not seated yet';
            document.getElementById('modal-last-cleared').value = tableData.last_cleared_at ? 
                new Date(tableData.last_cleared_at).toLocaleString() : 'Not cleared yet';
            
            // Set quick stats
            document.getElementById('modal-quick-table-id').textContent = tableData.table_id;
            document.getElementById('modal-quick-party-size').textContent = tableData.party_size + ' people';
            
            // Set table image with proper path
            const tableImage = document.getElementById('modal-table-image');
            const imagePath = tableData.image_url ? "Table_images/" + tableData.image_url : '';
            tableImage.src = imagePath || 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNDAwIiBoZWlnaHQ9IjMwMCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iNDAwIiBoZWlnaHQ9IjMwMCIgZmlsbD0iI2YwZjBmMCIvPjx0ZXh0IHg9IjUwJSIgeT0iNTAlIiBmb250LWZhbWlseT0iQXJpYWwiIGZvbnQtc2l6ZT0iMTYiIHRleHQtYW5jaG9yPSJtaWRkbGUiIGRvbWluYW50LWJhc2VsaW5lPSJtaWRkbGUiIGZpbGw9IiM5OTkiPlRhYmxlIEltYWdlPC90ZXh0Pjwvc3ZnPg==';
            document.getElementById('modal-image-title').textContent = tableData.party_name;
            
            // Set status badge
            const statusElement = document.getElementById('modal-status-badge');
            statusElement.innerHTML = '';
            
            let badgeClass = '';
            let badgeIcon = '';
            let badgeText = '';
            
            switch(tableData.status) {
                case 'available':
                    badgeClass = 'badge-success';
                    badgeIcon = '<i data-lucide="check-circle" class="w-4 h-4"></i>';
                    badgeText = 'Available';
                    break;
                case 'seated':
                    badgeClass = 'badge-primary';
                    badgeIcon = '<i data-lucide="user-check" class="w-4 h-4"></i>';
                    badgeText = 'Seated';
                    break;
                case 'waiting':
                    badgeClass = 'badge-warning';
                    badgeIcon = '<i data-lucide="clock" class="w-4 h-4"></i>';
                    badgeText = 'Waiting';
                    break;
            }
            
            const badge = document.createElement('span');
            badge.className = 'badge ' + badgeClass + ' gap-2';
            badge.innerHTML = badgeIcon + badgeText;
            statusElement.appendChild(badge);
            
            // Update button states based on status
            updateButtonStates(tableData.status);
            
            // Re-initialize icons inside modal
            setTimeout(() => lucide.createIcons(), 100);
            
            modal.showModal();
        }
        
        function updateButtonStates(status) {
            const availableBtn = document.getElementById('available-btn');
            const disableBtn = document.getElementById('disable-btn');
            
            if (status === 'available') {
                availableBtn.disabled = true;
                availableBtn.classList.add('btn-disabled');
                disableBtn.disabled = false;
                disableBtn.classList.remove('btn-disabled');
            } else if (status === 'seated') {
                availableBtn.disabled = false;
                availableBtn.classList.remove('btn-disabled');
                disableBtn.disabled = true;
                disableBtn.classList.add('btn-disabled');
            } else {
                availableBtn.disabled = false;
                availableBtn.classList.remove('btn-disabled');
                disableBtn.disabled = false;
                disableBtn.classList.remove('btn-disabled');
            }
        }
        
        // CRUD Actions with better error handling
        async function markAsAvailable() {
            if (!currentTableData) return;
            
            if (confirm(`Are you sure you want to mark "${currentTableData.party_name}" as available?`)) {
                try {
                    const response = await fetch('', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: new URLSearchParams({
                            action: 'set_available',
                            metric_id: currentTableData.wait_id
                        })
                    });
                    
                    const result = await response.json();
                    
                    if (result.success) {
                        showNotification(result.message, 'success');
                        setTimeout(() => window.location.reload(), 1500);
                    } else {
                        showNotification(result.message || 'Failed to update table', 'error');
                    }
                } catch (error) {
                    console.error('Error:', error);
                    showNotification('An error occurred while updating the table', 'error');
                }
            }
        }
        
        async function disableTable() {
            if (!currentTableData) return;
            
            if (confirm(`Are you sure you want to disable "${currentTableData.party_name}"? This will mark it as occupied.`)) {
                try {
                    const response = await fetch('', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: new URLSearchParams({
                            action: 'disable_table',
                            table_id: currentTableData.table_id
                        })
                    });
                    
                    const result = await response.json();
                    
                    if (result.success) {
                        showNotification(result.message, 'success');
                        setTimeout(() => window.location.reload(), 1500);
                    } else {
                        showNotification(result.message || 'Failed to disable table', 'error');
                    }
                } catch (error) {
                    console.error('Error:', error);
                    showNotification('An error occurred while disabling the table', 'error');
                }
            }
        }
        
        async function updateTable() {
            if (!currentTableData) return;
            
            const partyName = document.getElementById('modal-party-name').value;
            const partySize = document.getElementById('modal-party-size').value;
            
            if (!partyName || !partySize) {
                showNotification('Please fill in all fields', 'warning');
                return;
            }
            
            try {
                const response = await fetch('', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: new URLSearchParams({
                        action: 'update_table',
                        metric_id: currentTableData.wait_id,
                        table_id: currentTableData.table_id,
                        party_name: partyName,
                        party_size: partySize
                    })
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showNotification(result.message, 'success');
                    setTimeout(() => window.location.reload(), 1500);
                } else {
                    showNotification(result.message || 'Failed to update table', 'error');
                }
            } catch (error) {
                console.error('Error:', error);
                showNotification('An error occurred while updating the table', 'error');
            }
        }
        
        async function deleteTable() {
            if (!currentTableData) return;
            
            if (confirm(`Are you sure you want to delete "${currentTableData.party_name}"? This action cannot be undone.`)) {
                try {
                    const response = await fetch('', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: new URLSearchParams({
                            action: 'delete_table',
                            metric_id: currentTableData.wait_id
                        })
                    });
                    
                    const result = await response.json();
                    
                    if (result.success) {
                        showNotification(result.message, 'success');
                        setTimeout(() => window.location.reload(), 1500);
                    } else {
                        showNotification(result.message || 'Failed to delete table', 'error');
                    }
                } catch (error) {
                    console.error('Error:', error);
                    showNotification('An error occurred while deleting the table', 'error');
                }
            }
        }

        // Notification system
        function showNotification(message, type = 'info') {
            const notification = document.createElement('div');
            notification.className = `toast toast-top toast-end z-50`;
            notification.innerHTML = `
                <div class="alert alert-${type} shadow-lg">
                    <div>
                        <i data-lucide="${type === 'success' ? 'check-circle' : type === 'error' ? 'x-circle' : type === 'warning' ? 'alert-circle' : 'info'}" 
                           class="w-6 h-6"></i>
                        <span>${message}</span>
                    </div>
                </div>
            `;
            
            document.body.appendChild(notification);
            lucide.createIcons();
            
            setTimeout(() => {
                notification.remove();
            }, 3000);
        }

        // Set up event listeners
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize modal close button
            const modal = document.getElementById('viewModal');
            modal.addEventListener('click', (event) => {
                if (event.target === modal || event.target.classList.contains('modal-backdrop')) {
                    modal.close();
                }
            });
            
            // Add keyboard shortcuts
            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape') {
                    document.getElementById('viewModal').close();
                }
            });
        });
    </script>
    <script src="../JavaScript/sidebar.js"></script>
</body>
</html>