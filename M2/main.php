<?php
session_start();
include("../main_connection.php");

$db_name = "rest_m2_inventory";

if (!isset($connections[$db_name])) {
    die("❌ Connection not found for $db_name");
}

$conn = $connections[$db_name];

// Search functionality
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$whereClause = '';

if (!empty($search)) {
    $searchTerm = "%" . $conn->real_escape_string($search) . "%";
    $whereClause = "WHERE item_name LIKE '$searchTerm' OR category LIKE '$searchTerm' OR location LIKE '$searchTerm' OR notes LIKE '$searchTerm'";
}

// Pagination variables
$limit = 15; // Number of records per page
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Get total records count for pagination
$countQuery = "SELECT COUNT(*) as total FROM inventory_and_stock $whereClause";
$countResult = $conn->query($countQuery);
$totalRecords = $countResult->fetch_assoc()['total'];
$totalPages = ceil($totalRecords / $limit);

// Ensure page is within valid range
if ($page > $totalPages && $totalPages > 0) {
    $page = $totalPages;
    $offset = ($page - 1) * $limit;
}

// Fetch stock items with pagination and search
$sql = "SELECT item_id, item_name, category, quantity, critical_level, created_at, updated_at, unit_price, notes, request_status, expiry_date, last_restock_date, location, image_url
        FROM inventory_and_stock 
        $whereClause 
        ORDER BY created_at DESC 
        LIMIT $limit OFFSET $offset";
        
$result_sql = $conn->query($sql);
if (!$result_sql) {
    die("SQL Error: " . $conn->error);
}

$stocks = [];
while ($row = $result_sql->fetch_assoc()) {
    $stocks[] = $row;
}

// Count queries (with search filter if applicable)
$countWhere = str_replace('WHERE', 'AND', $whereClause);
$countWhere = $countWhere ? $countWhere : '';
$query = "SELECT 
        (SELECT COUNT(*) FROM inventory_and_stock $whereClause) AS total_items_count,
        (SELECT COUNT(*) FROM inventory_and_stock WHERE critical_level = 'In_stock' $countWhere) AS In_stock,
        (SELECT COUNT(*) FROM inventory_and_stock WHERE critical_level = 'low_stock' $countWhere) AS low,
        (SELECT COUNT(*) FROM inventory_and_stock WHERE critical_level = 'out_of_stock' $countWhere) AS out_of_stock,
        (SELECT COUNT(*) FROM inventory_and_stock WHERE critical_level = 'expire' $countWhere) AS expire,
        (SELECT COUNT(*) FROM inventory_and_stock WHERE critical_level = 'recently_added' $countWhere) AS recently_added";

$result = mysqli_query($conn, $query);
if (!$result) {
    die("Count query failed: " . mysqli_error($conn));
}

$row = mysqli_fetch_assoc($result);
$total_items_count = $row['total_items_count'];
$in_stock_count = $row['In_stock'];   
$low_stock_count = $row['low'];
$out_of_stock_count = $row['out_of_stock'];
$expired_count = $row['expire'];
$recently_added_count = $row['recently_added'];

// PHP Helper Functions
function getStockStatus($quantity, $criticalLevel, $expiryDate) {
    $today = new DateTime();
    $expiry = $expiryDate ? new DateTime($expiryDate) : null;
    
    if ($expiry && $expiry < $today) {
        return ['status' => 'Expired', 'class' => 'bg-purple-100 text-purple-700'];
    }
    if ($quantity <= 0) {
        return ['status' => 'Out of Stock', 'class' => 'bg-red-100 text-red-700'];
    }
    if ($quantity <= $criticalLevel) {
        return ['status' => 'Low Stock', 'class' => 'bg-amber-100 text-amber-700'];
    }
    return ['status' => 'In Stock', 'class' => 'bg-green-100 text-green-700'];
}

function getStatusIcon($status) {
    $icons = [
        'Out of Stock' => '<i data-lucide="x-circle" class="w-3 h-3"></i>',
        'Low Stock' => '<i data-lucide="alert-circle" class="w-3 h-3"></i>',
        'In Stock' => '<i data-lucide="check-circle" class="w-3 h-3"></i>',
        'Expired' => '<i data-lucide="calendar-x" class="w-3 h-3"></i>'
    ];
    return $icons[$status] ?? '<i data-lucide="package" class="w-3 h-3"></i>';
}

function getQuantityColorClass($quantity, $criticalLevel) {
    if ($quantity <= 0) return 'text-red-600';
    if ($quantity <= $criticalLevel) return 'text-amber-600';
    if ($quantity <= $criticalLevel * 2) return 'text-blue-600';
    return 'text-green-600';
}

function getProgressBarColor($quantity, $criticalLevel) {
    if ($quantity <= 0) return 'bg-red-500';
    if ($quantity <= $criticalLevel) return 'bg-amber-500';
    if ($quantity <= $criticalLevel * 2) return 'bg-blue-500';
    return 'bg-green-500';
}

function calculatePercentage($quantity, $criticalLevel) {
    $max = $criticalLevel * 3;
    return $max > 0 ? min(($quantity / $max) * 100, 100) : 0;
}

function isExpiringSoon($expiryDate) {
    if (!$expiryDate) return false;
    $today = new DateTime();
    $expiry = new DateTime($expiryDate);
    $interval = $today->diff($expiry);
    return $interval->days <= 7 && $interval->invert == 0;
}

function formatExpiryDate($date) {
    if (!$date) return 'Not set';
    
    $expiry = new DateTime($date);
    $today = new DateTime();
    $tomorrow = new DateTime('tomorrow');
    
    if ($expiry->format('Y-m-d') === $today->format('Y-m-d')) return 'Today';
    if ($expiry->format('Y-m-d') === $tomorrow->format('Y-m-d')) return 'Tomorrow';
    
    $format = 'M j';
    if ($expiry->format('Y') !== $today->format('Y')) {
        $format .= ', Y';
    }
    return $expiry->format($format);
}

function formatDateTime($dateString) {
    $date = new DateTime($dateString);
    return $date->format('M j, Y g:i A');
}

function getPaginationUrl($page, $search = '', $filter = '') {
    $params = [];
    if ($page > 1) $params['page'] = $page;
    if (!empty($search)) $params['search'] = $search;
    if (!empty($filter) && $filter != 'all') $params['filter'] = $filter;
    
    return empty($params) ? '?' : '?' . http_build_query($params);
}
?>
<!DOCTYPE html>
<html lang="en">
<?php include '../header.php'; ?>
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Inventory Management</title>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    
    <style>
        .inventory-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 1.5rem;
        }
        
        .glass-effect {
            background: rgba(255, 255, 255, 0.7);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.3);
        }
        
        .stock-low {
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0% { box-shadow: 0 0 0 0 rgba(245, 158, 11, 0.4); }
            70% { box-shadow: 0 0 0 10px rgba(245, 158, 11, 0); }
            100% { box-shadow: 0 0 0 0 rgba(245, 158, 11, 0); }
        }
        
        .stock-out {
            animation: pulse-red 2s infinite;
        }
        
        @keyframes pulse-red {
            0% { box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.4); }
            70% { box-shadow: 0 0 0 10px rgba(239, 68, 68, 0); }
            100% { box-shadow: 0 0 0 0 rgba(239, 68, 68, 0); }
        }
        
        .dropdown-content {
            display: none;
            position: absolute;
            z-index: 50;
        }

        .dropdown:hover .dropdown-content,
        .dropdown:focus-within .dropdown-content {
            display: block;
        }

        #stock-filter a.active {
            background-color: #3b82f6;
            color: white;
        }

        .glass-effect {
            background: rgba(255, 255, 255, 0.7);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .loading {
            display: inline-block;
            border: 2px solid #f3f3f3;
            border-radius: 50%;
            border-top: 2px solid #3498db;
            width: 20px;
            height: 20px;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .alert {
            display: flex;
            align-items: center;
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 16px;
        }

        .alert-error {
            background-color: #fee2e2;
            color: #dc2626;
            border: 1px solid #fecaca;
        }

        .alert-success {
            background-color: #d1fae5;
            color: #065f46;
            border: 1px solid #a7f3d0;
        }
        
        .toast {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1000;
        }

        /* Modern View Modal Styles */
        .modal-backdrop {
            background: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(4px);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .modal-content {
            background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
            border: none;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 31, 84, 0.3);
            width: 95%;
            max-width: 1200px;
            max-height: 90vh;
            overflow: hidden;
        }

        .modal-header {
            background: linear-gradient(135deg, #001f54 0%, #00308f 100%);
            color: #F7B32B;
            border-top-left-radius: 20px;
            border-top-right-radius: 20px;
            padding: 1.5rem 2rem;
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            padding: 1.5rem;
        }

        .info-card {
            background: white;
            border-radius: 12px;
            padding: 1.25rem;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
            border: 1px solid #e2e8f0;
            transition: all 0.3s ease;
        }

        .info-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
        }

        .info-card-title {
            color: #64748b;
            font-size: 0.875rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 0.75rem;
        }

        .info-card-value {
            color: #1e293b;
            font-size: 1.125rem;
            font-weight: 600;
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            padding: 0.375rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.875rem;
            font-weight: 600;
        }

        .progress-container {
            margin-top: 1rem;
        }

        .progress-bar {
            height: 8px;
            border-radius: 4px;
            background: #e2e8f0;
            overflow: hidden;
        }

        .progress-fill {
            height: 100%;
            border-radius: 4px;
            transition: width 0.5s ease;
        }

        .edit-form-input {
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            padding: 0.75rem 1rem;
            font-size: 0.95rem;
            transition: all 0.2s ease;
            width: 100%;
        }

        .edit-form-input:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }

        .select2-container--default .select2-selection--single {
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            height: 46px;
            padding: 0.5rem;
        }

        .select2-container--default .select2-selection--single:focus {
            border-color: #3b82f6;
            outline: none;
        }

        .select2-container--default .select2-selection--single .select2-selection__rendered {
            line-height: 2;
        }

        .modal-footer {
            padding: 1.5rem 2rem;
            background: #f8fafc;
            border-bottom-left-radius: 20px;
            border-bottom-right-radius: 20px;
            border-top: 1px solid #e2e8f0;
        }

        .action-btn {
            padding: 0.75rem 1.5rem;
            border-radius: 10px;
            font-weight: 600;
            font-size: 0.95rem;
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .action-btn i {
            width: 18px;
            height: 18px;
        }

        .swal2-confirm-destructive {
            background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%) !important;
            color: white !important;
            font-weight: 600 !important;
            border: none !important;
            padding: 0.75rem 1.5rem !important;
            border-radius: 10px !important;
        }

        .swal2-cancel-safe {
            background: linear-gradient(135deg, #6b7280 0%, #4b5563 100%) !important;
            color: white !important;
            font-weight: 600 !important;
            border: none !important;
            padding: 0.75rem 1.5rem !important;
            border-radius: 10px !important;
        }

        .swal2-popup {
            border-radius: 20px !important;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3) !important;
            border: 1px solid #e2e8f0 !important;
        }

        .swal2-title {
            color: #001f54 !important;
            font-size: 1.5rem !important;
            font-weight: 700 !important;
        }

        .swal2-html-container {
            color: #4b5563 !important;
            font-size: 1rem !important;
        }

        .animate__animated {
            animation-duration: 0.3s;
        }

        .animate__fadeIn {
            animation-name: fadeIn;
        }

        .animate__fadeOut {
            animation-name: fadeOut;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @keyframes fadeOut {
            from { opacity: 1; transform: translateY(0); }
            to { opacity: 0; transform: translateY(-20px); }
        }

        /* Pagination styles */
        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 0.5rem;
            margin-top: 2rem;
            padding: 1rem;
        }

        .pagination .btn {
            padding: 0.5rem 1rem;
            border-radius: 8px;
            font-size: 0.875rem;
            min-width: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .pagination .btn-active {
            background: linear-gradient(135deg, #001f54 0%, #00308f 100%);
            color: #F7B32B;
            border: none;
        }

        .pagination .btn-disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        /* Search bar styles */
        .search-container {
            position: relative;
            flex: 1;
            max-width: 400px;
        }

        .search-input {
            width: 100%;
            padding: 0.75rem 1rem 0.75rem 3rem;
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            font-size: 0.95rem;
            transition: all 0.2s ease;
            background: white;
        }

        .search-input:focus {
            outline: none;
            border-color: #001f54;
            box-shadow: 0 0 0 3px rgba(0, 31, 84, 0.1);
        }

        .search-icon {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: #6b7280;
        }
    </style>
</head>
<body class="bg-base-100 min-h-screen bg-white">
    <div class="flex h-screen">
        <!-- Sidebar -->
        <div class="bg-[#1A2C5B] border-r border-blue-600 pt-5 pb-4 flex flex-col w-64 transition-all duration-300 ease-in-out shadow-xl relative overflow-hidden h-screen" id="sidebar">
            <?php include '../sidebarr.php'; ?>
        </div>

        <!-- Content Area -->
        <div class="flex flex-col flex-1 overflow-auto">
            <!-- Navbar -->
            <?php include '../navbar.php'; ?>

            <!-- Main Content -->
            <main class="flex-1 overflow-auto p-4 md:p-6">
                <!-- Inventory Summary Cards -->
                <section class="glass-effect p-6 rounded-2xl shadow-xl border border-gray-100/30 backdrop-blur-sm bg-white/70 mb-6">
                    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
                        <h2 class="text-2xl font-bold flex items-center" style="color:#F7B32B;">
                            <span class="p-2 mr-3 rounded-lg" style="background:#F7B32B; color:#001f54;">
                                <i data-lucide="package" class="w-5 h-5"></i>
                            </span>
                            Inventory Overview
                        </h2>
                        
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 h-full">
                        <!-- Total Items Card -->
                        <div class="stat-card bg-white shadow-2xl p-5 rounded-xl transition-all duration-300 hover:shadow-2xl hover:scale-105 hover:bg-gray-50">
                            <div class="flex justify-between items-start">
                                <div>
                                    <p class="text-sm font-medium text-[#001f54] hover:drop-shadow-md transition-all">Total Items</p>
                                    <h3 class="text-3xl font-bold text-black mt-1"><?= $total_items_count ?? '0' ?></h3>
                                    <p class="text-xs text-gray-500 mt-1">Inventory count</p>
                                </div>
                                <div class="p-3 rounded-lg bg-[#001f54] flex items-center justify-center transition-all duration-300 hover:bg-[#002b70]">
                                    <i data-lucide="boxes" class="w-6 h-6 text-[#F7B32B]"></i>
                                </div>
                            </div>
                            <div class="mt-4">
                                <div class="h-2 w-full bg-gray-200 rounded overflow-hidden">
                                    <div class="h-full" style="width:100%; background:#F7B32B;"></div>
                                </div>
                                <div class="flex justify-between text-xs mt-1 text-gray-600">
                                    <span>Inventory</span>
                                    <span class="font-medium"><?= $total_items_count ?? '0' ?> items</span>
                                </div>
                            </div>
                        </div>

                        <!-- In Stock Card -->
                        <div class="stat-card bg-white shadow-2xl p-5 rounded-xl transition-all duration-300 hover:shadow-2xl hover:scale-105 hover:bg-gray-50">
                            <div class="flex justify-between items-start">
                                <div>
                                    <p class="text-sm font-medium text-[#001f54] hover:drop-shadow-md transition-all">In Stock</p>
                                    <h3 class="text-3xl font-bold text-black mt-1"><?= $in_stock_count ?? '0' ?></h3>
                                    <p class="text-xs text-gray-500 mt-1">Currently available</p>
                                </div>
                                <div class="p-3 rounded-lg bg-[#001f54] flex items-center justify-center transition-all duration-300 hover:bg-[#002b70]">
                                    <i data-lucide="check-circle" class="w-6 h-6 text-[#F7B32B]"></i>
                                </div>
                            </div>
                            <div class="mt-4">
                                <div class="h-2 w-full bg-gray-200 rounded overflow-hidden">
                                    <div class="h-full" style="width: <?= isset($in_stock_count) && $total_items_count ? ($in_stock_count/$total_items_count)*100 : 0 ?>%; background:#F7B32B;"></div>
                                </div>
                                <div class="flex justify-between text-xs mt-1 text-gray-600">
                                    <span>Availability</span>
                                    <span class="font-medium"><?= isset($in_stock_count) && $total_items_count ? round(($in_stock_count/$total_items_count)*100) : '0' ?>%</span>
                                </div>
                            </div>
                        </div>

                        <!-- Low Stock Card -->
                        <div class="stat-card bg-white shadow-2xl p-5 rounded-xl transition-all duration-300 hover:shadow-2xl hover:scale-105 hover:bg-gray-50">
                            <div class="flex justify-between items-start">
                                <div>
                                    <p class="text-sm font-medium text-[#001f54] hover:drop-shadow-md transition-all">Low Stock</p>
                                    <h3 class="text-3xl font-bold text-black mt-1"><?= $low_stock_count ?? '0' ?></h3>
                                    <p class="text-xs text-gray-500 mt-1">Needs attention</p>
                                </div>
                                <div class="p-3 rounded-lg bg-[#001f54] flex items-center justify-center transition-all duration-300 hover:bg-[#002b70]">
                                    <i data-lucide="alert-triangle" class="w-6 h-6 text-[#F7B32B]"></i>
                                </div>
                            </div>
                            <div class="mt-4">
                                <div class="h-2 w-full bg-gray-200 rounded overflow-hidden">
                                    <div class="h-full" style="width: <?= isset($low_stock_count) && $total_items_count ? ($low_stock_count/$total_items_count)*100 : 0 ?>%; background:#F7B32B;"></div>
                                </div>
                                <div class="flex justify-between text-xs mt-1 text-gray-600">
                                    <span>Low Stock</span>
                                    <span class="font-medium"><?= isset($low_stock_count) && $total_items_count ? round(($low_stock_count/$total_items_count)*100) : '0' ?>%</span>
                                </div>
                            </div>
                        </div>

                        <!-- Out of Stock Card -->
                        <div class="stat-card bg-white shadow-2xl p-5 rounded-xl transition-all duration-300 hover:shadow-2xl hover:scale-105 hover:bg-gray-50">
                            <div class="flex justify-between items-start">
                                <div>
                                    <p class="text-sm font-medium text-[#001f54] hover:drop-shadow-md transition-all">Out of Stock</p>
                                    <h3 class="text-3xl font-bold text-black mt-1"><?= $out_of_stock_count ?? '0' ?></h3>
                                    <p class="text-xs text-gray-500 mt-1">Urgent restock</p>
                                </div>
                                <div class="p-3 rounded-lg bg-[#001f54] flex items-center justify-center transition-all duration-300 hover:bg-[#002b70]">
                                    <i data-lucide="x-octagon" class="w-6 h-6 text-[#F7B32B]"></i>
                                </div>
                            </div>
                            <div class="mt-4">
                                <div class="h-2 w-full bg-gray-200 rounded overflow-hidden">
                                    <div class="h-full" style="width: <?= isset($out_of_stock_count) && $total_items_count ? ($out_of_stock_count/$total_items_count)*100 : 0 ?>%; background:#F7B32B;"></div>
                                </div>
                                <div class="flex justify-between text-xs mt-1 text-gray-600">
                                    <span>Out of Stock</span>
                                    <span class="font-medium"><?= isset($out_of_stock_count) && $total_items_count ? round(($out_of_stock_count/$total_items_count)*100) : '0' ?>%</span>
                                </div>
                            </div>
                        </div>

                        <!-- Expired Items Card -->
                        <div class="stat-card bg-white shadow-2xl p-5 rounded-xl transition-all duration-300 hover:shadow-2xl hover:scale-105 hover:bg-gray-50">
                            <div class="flex justify-between items-start">
                                <div>
                                    <p class="text-sm font-medium text-[#001f54] hover:drop-shadow-md transition-all">Expired</p>
                                    <h3 class="text-3xl font-bold text-black mt-1"><?= $expired_count ?? '0' ?></h3>
                                    <p class="text-xs text-gray-500 mt-1">Needs disposal</p>
                                </div>
                                <div class="p-3 rounded-lg bg-[#001f54] flex items-center justify-center transition-all duration-300 hover:bg-[#002b70]">
                                    <i data-lucide="calendar-x" class="w-6 h-6 text-[#F7B32B]"></i>
                                </div>
                            </div>
                            <div class="mt-4">
                                <div class="h-2 w-full bg-gray-200 rounded overflow-hidden">
                                    <div class="h-full" style="width: <?= isset($expired_count) && $total_items_count ? ($expired_count/$total_items_count)*100 : 0 ?>%; background:#F7B32B;"></div>
                                </div>
                                <div class="flex justify-between text-xs mt-1 text-gray-600">
                                    <span>Expired</span>
                                    <span class="font-medium"><?= isset($expired_count) && $total_items_count ? round(($expired_count/$total_items_count)*100) : '0' ?>%</span>
                                </div>
                            </div>
                        </div>

                        <!-- Recently Added Card -->
                        <div class="stat-card bg-white shadow-2xl p-5 rounded-xl transition-all duration-300 hover:shadow-2xl hover:scale-105 hover:bg-gray-50">
                            <div class="flex justify-between items-start">
                                <div>
                                    <p class="text-sm font-medium text-[#001f54] hover:drop-shadow-md transition-all">Recently Added</p>
                                    <h3 class="text-3xl font-bold text-black mt-1"><?= $recently_added_count ?? '0' ?></h3>
                                    <p class="text-xs text-gray-500 mt-1">This week</p>
                                </div>
                                <div class="p-3 rounded-lg bg-[#001f54] flex items-center justify-center transition-all duration-300 hover:bg-[#002b70]">
                                    <i data-lucide="package-plus" class="w-6 h-6 text-[#F7B32B]"></i>
                                </div>
                            </div>
                            <div class="mt-4">
                                <div class="h-2 w-full bg-gray-200 rounded overflow-hidden">
                                    <div class="h-full" style="width: <?= isset($recently_added_count) && $total_items_count ? ($recently_added_count/$total_items_count)*100 : 0 ?>%; background:#F7B32B;"></div>
                                </div>
                                <div class="flex justify-between text-xs mt-1 text-gray-600">
                                    <span>Increase</span>
                                    <span class="font-medium"><?= isset($recently_added_count) && $total_items_count ? round(($recently_added_count/$total_items_count)*100) : '0' ?>%</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Stock Management Section -->
                <section class="glass-effect p-6 rounded-xl shadow-sm mb-6">
                    <div class="flex flex-col md:flex-row justify-between items-center mb-6 gap-4">
                        <h2 class="text-xl font-bold flex items-center gap-2 text-gray-800">
                            <i data-lucide="package" class="w-5 h-5 text-blue-500"></i>
                            <span>Stock Items</span>
                        </h2>
                        <div class="flex flex-wrap gap-3 w-full md:w-auto">

                        <div class="flex items-center gap-3 w-full sm:w-auto">
                            <!-- Search Bar -->
                            <div class="search-container">
                                <form method="GET" action="" class="relative">
                                    <i data-lucide="search" class="w-4 h-4 search-icon"></i>
                                    <input type="text" 
                                           name="search" 
                                           placeholder="Search items..." 
                                           value="<?= htmlspecialchars($search) ?>"
                                           class="search-input">
                                </form>
                            </div>
                            
                            <!-- Clear Search Button -->
                            <?php if (!empty($search)): ?>
                                <a href="?" class="btn btn-outline border-gray-300 text-gray-700 hover:bg-gray-100 px-4 py-2 rounded-xl flex items-center gap-2 text-sm">
                                    <i data-lucide="x" class="w-4 h-4"></i>
                                    Clear
                                </a>
                            <?php endif; ?>
                        </div>
                            <!-- Add Stock Button -->
                            <label for="add-stock-modal" 
                                   class="btn px-4 py-2 rounded-xl flex items-center gap-2 shadow-md hover:shadow-lg cursor-pointer text-sm
                                          bg-[#001f54] text-[#F7B32B] hover:bg-[#F7B32B] hover:text-[#001f54] border border-[#F7B32B]">
                                <i data-lucide="plus" class="w-4 h-4"></i>
                                <span>Request new stock</span>
                            </label>
                            
                            <!-- Filter Dropdown -->
                            <div class="dropdown dropdown-end">
                                <button class="btn btn-outline border-gray-300 text-gray-700 hover:bg-gray-100 px-4 py-2 rounded-xl flex items-center gap-2 text-sm">
                                    <i data-lucide="filter" class="w-4 h-4"></i> 
                                    <span>Filter</span>
                                </button>
                                <ul id="stock-filter" class="dropdown-content menu p-2 shadow bg-white text-black rounded-box w-52 mt-2 border border-gray-200">
                                    <?php 
                                    $filter = $_GET['filter'] ?? 'all';
                                    ?>
                                    <li><a href="<?= getPaginationUrl($page, $search, 'all') ?>" class="<?= $filter == 'all' ? 'active' : '' ?>">All Stock</a></li>
                                    <li><a href="<?= getPaginationUrl($page, $search, 'food') ?>" class="<?= $filter == 'food' ? 'active' : '' ?>">Food Items</a></li>
                                    <li><a href="<?= getPaginationUrl($page, $search, 'beverage') ?>" class="<?= $filter == 'beverage' ? 'active' : '' ?>">Beverages</a></li>
                                    <li><a href="<?= getPaginationUrl($page, $search, 'supplies') ?>" class="<?= $filter == 'supplies' ? 'active' : '' ?>">Supplies</a></li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Search Results Info -->
                    <?php if (!empty($search)): ?>
                        <div class="mb-4 p-3 bg-blue-50 border border-blue-200 rounded-lg">
                            <div class="flex items-center gap-2">
                                <i data-lucide="search" class="w-4 h-4 text-blue-600"></i>
                                <span class="text-sm text-blue-700">
                                    Showing results for "<strong><?= htmlspecialchars($search) ?></strong>"
                                    <?php if ($total_items_count > 0): ?>
                                        (<?= $total_items_count ?> item<?= $total_items_count != 1 ? 's' : '' ?> found)
                                    <?php endif; ?>
                                </span>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4 p-4 rounded-lg" id="stock-grid">
                        <!-- PHP Stock Cards -->
                        <?php
                        // Apply filter if set
                        $filter = $_GET['filter'] ?? 'all';
                        $filteredStocks = $stocks;
                        
                        if ($filter != 'all') {
                            $filteredStocks = array_filter($stocks, function($stock) use ($filter) {
                                return strtolower($stock['category'] ?? '') == $filter;
                            });
                        }
                        
                        if (empty($filteredStocks)): ?>
                            <div class="col-span-full text-center py-10">
                                <i data-lucide="package-x" class="w-12 h-12 text-gray-400 mx-auto mb-3"></i>
                                <h3 class="text-lg font-medium text-gray-600">No items found</h3>
                                <p class="text-gray-500"><?= empty($search) ? 'Try adding some stock items' : 'Try changing your search or filter criteria' ?></p>
                            </div>
                        <?php else: ?>
                            <?php foreach($filteredStocks as $stock): 
                                $status = getStockStatus($stock['quantity'], $stock['critical_level'], $stock['expiry_date']);
                                $quantityColor = getQuantityColorClass($stock['quantity'], $stock['critical_level']);
                                $progressBarColor = getProgressBarColor($stock['quantity'], $stock['critical_level']);
                                $percentage = calculatePercentage($stock['quantity'], $stock['critical_level']);
                                $expiringSoon = isExpiringSoon($stock['expiry_date']);
                            ?>
                                <div class="bg-white border border-gray-200 rounded-xl shadow-sm hover:shadow-md transition-all duration-300 overflow-hidden group">
                                    <div class="relative">
                                        <!-- Image Section -->
                                        <div class="w-full h-48 overflow-hidden bg-gradient-to-br from-gray-50 to-gray-100">
                                            <?php if (!empty($stock['image_url'])): ?>
                                                <img src="upload_images/<?= htmlspecialchars($stock['image_url']) ?>" 
                                                     alt="<?= htmlspecialchars($stock['item_name']) ?>" 
                                                     class="w-full h-full object-cover transition-transform duration-500 hover:scale-110"
                                                     onerror="this.onerror=null; this.src='data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMjAwIiBoZWlnaHQ9IjIwMCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iMjAwIiBoZWlnaHQ9IjIwMCIgZmlsbD0iI2YwZjBmMCIvPjx0ZXh0IHg9IjEwMCUiIHk9IjUwJSIgZm9udC1mYW1pbHk9IkFyaWFsIiBmb250LXNpemU9IjE0IiB0ZXh0LWFuY2hvcj0ibWlkZGxlIiBmaWxsPSIjOTk5Ij5JbWFnZSBOb3QgRm91bmQ8L3RleHQ+PC9zdmc+'">
                                            <?php else: ?>
                                                <div class="w-full h-full flex flex-col items-center justify-center">
                                                    <i data-lucide="image" class="w-16 h-16 text-gray-300 mb-3"></i>
                                                    <p class="text-sm text-gray-400 font-medium">No image</p>
                                                </div>
                                            <?php endif; ?>
                                        </div>

                                        <!-- Card Header with gradient -->
                                        <div class="bg-gradient-to-r from-blue-50 to-indigo-50 p-4 border-b border-gray-100">
                                            <div class="flex items-center justify-between mb-2">
                                                <h3 class="text-lg font-bold text-gray-800 truncate pr-2"><?= htmlspecialchars($stock['item_name']) ?></h3>
                                                <!-- Status badge with icon -->
                                                <div class="flex items-center gap-2">
                                                    <span class="text-xs font-semibold px-3 py-1.5 rounded-full <?= $status['class'] ?> shadow-sm flex items-center gap-1">
                                                        <?= getStatusIcon($status['status']) ?>
                                                        <?= $status['status'] ?>
                                                    </span>
                                                </div>
                                            </div>
                                            
                                            <!-- Quantity display with progress bar -->
                                            <div class="space-y-2">
                                                <div class="flex items-center justify-between text-sm">
                                                    <span class="text-gray-600 font-medium">Quantity</span>
                                                    <span class="font-bold <?= $quantityColor ?>">
                                                        <?= $stock['quantity'] ?> pcs
                                                    </span>
                                                </div>
                                                <div class="w-full bg-gray-200 rounded-full h-1.5">
                                                    <div class="h-1.5 rounded-full <?= $progressBarColor ?>" 
                                                         style="width: <?= $percentage ?>%"></div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Card Body -->
                                        <div class="p-4 bg-white">
                                            <div class="grid grid-cols-2 gap-3 mb-4">
                                                <!-- Category with icon -->
                                                <div class="flex items-center gap-2">
                                                    <div class="w-8 h-8 rounded-lg bg-blue-100 flex items-center justify-center">
                                                        <i data-lucide="tag" class="w-4 h-4 text-blue-600"></i>
                                                    </div>
                                                    <div>
                                                        <p class="text-xs text-gray-500">Category</p>
                                                        <p class="text-sm font-medium text-gray-800 truncate"><?= htmlspecialchars($stock['category']) ?></p>
                                                    </div>
                                                </div>

                                                <!-- Critical Level with warning indicator -->
                                                <div class="flex items-center gap-2">
                                                    <div class="w-8 h-8 rounded-lg <?= $stock['quantity'] <= $stock['critical_level'] * 2 ? 'bg-amber-100' : 'bg-green-100' ?> flex items-center justify-center">
                                                        <i data-lucide="alert-circle" class="w-4 h-4 <?= $stock['quantity'] <= $stock['critical_level'] * 2 ? 'text-amber-600' : 'text-green-600' ?>"></i>
                                                    </div>
                                                    <div>
                                                        <p class="text-xs text-gray-500">Critical Level</p>
                                                        <p class="text-sm font-medium <?= $stock['quantity'] <= $stock['critical_level'] ? 'text-red-600' : 'text-gray-800' ?>">
                                                            <?= $stock['critical_level'] ?>
                                                        </p>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Expiry Date (if exists) -->
                                            <?php if ($stock['expiry_date']): ?>
                                                <div class="mb-4 p-3 rounded-lg <?= $expiringSoon ? 'bg-red-50 border border-red-100' : 'bg-gray-50' ?>">
                                                    <div class="flex items-center gap-2 mb-1">
                                                        <i data-lucide="calendar" class="w-4 h-4 <?= $expiringSoon ? 'text-red-500' : 'text-gray-500' ?>"></i>
                                                        <span class="text-xs font-medium <?= $expiringSoon ? 'text-red-600' : 'text-gray-600' ?>">Expiry Date</span>
                                                    </div>
                                                    <div class="flex items-center justify-between">
                                                        <span class="text-sm font-semibold <?= $expiringSoon ? 'text-red-700' : 'text-gray-800' ?>">
                                                            <?= formatExpiryDate($stock['expiry_date']) ?>
                                                        </span>
                                                        <?php if ($expiringSoon): ?>
                                                            <span class="text-xs font-medium text-red-500 animate-pulse">Expiring soon!</span>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            <?php endif; ?>

                                            <!-- Action Button -->
                                            <div class="pt-3 border-t border-gray-100">
                                                <button onclick="showStockDetails(<?= $stock['item_id'] ?>)" 
                                                        class="btn btn-sm w-full bg-blue-50 text-blue-600 hover:text-blue-700 hover:bg-blue-100 
                                                               font-medium flex items-center justify-center gap-2 px-3 py-2 rounded-lg transition-all duration-200">
                                                    <i data-lucide="eye" class="w-4 h-4"></i>
                                                    <span>View Details</span>
                                                </button>
                                            </div>
                                        </div>

                                        <!-- Hover effect overlay -->
                                        <div class="absolute inset-0 rounded-xl border-2 border-transparent group-hover:border-blue-200 
                                                    transition-all duration-300 pointer-events-none"></div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Pagination -->
                    <?php if ($totalPages > 1): ?>
                        <div class="pagination">
                            <!-- Previous Button -->
                            <a href="<?= getPaginationUrl($page - 1, $search, $filter) ?>" 
                               class="btn btn-outline border-gray-300 text-gray-700 hover:bg-gray-100 <?= $page <= 1 ? 'btn-disabled' : '' ?>">
                                <i data-lucide="chevron-left" class="w-4 h-4"></i>
                            </a>
                            
                            <!-- Page Numbers -->
                            <?php
                            $startPage = max(1, $page - 2);
                            $endPage = min($totalPages, $startPage + 4);
                            
                            if ($endPage - $startPage < 4 && $startPage > 1) {
                                $startPage = max(1, $endPage - 4);
                            }
                            
                            for ($i = $startPage; $i <= $endPage; $i++): ?>
                                <a href="<?= getPaginationUrl($i, $search, $filter) ?>" 
                                   class="btn <?= $i == $page ? 'btn-active' : 'btn-outline border-gray-300 text-gray-700 hover:bg-gray-100' ?>">
                                    <?= $i ?>
                                </a>
                            <?php endfor; ?>
                            
                            <!-- Next Button -->
                            <a href="<?= getPaginationUrl($page + 1, $search, $filter) ?>" 
                               class="btn btn-outline border-gray-300 text-gray-700 hover:bg-gray-100 <?= $page >= $totalPages ? 'btn-disabled' : '' ?>">
                                <i data-lucide="chevron-right" class="w-4 h-4"></i>
                            </a>
                            
                            <!-- Page Info -->
                            <div class="ml-4 text-sm text-gray-600">
                                Showing <?= ($offset + 1) ?> - <?= min($offset + $limit, $totalRecords) ?> of <?= $totalRecords ?> items
                            </div>
                        </div>
                    <?php endif; ?>
                </section>
            </main>
        </div>
    </div>

    <!-- Add Stock Modal (PHP version) -->
    <input type="checkbox" id="add-stock-modal" class="modal-toggle" />
    <div class="modal modal-bottom sm:modal-middle">
        <div class="bg-white modal-box max-w-2xl p-6 rounded-lg shadow-2xl">
            <div class="flex justify-between items-center mb-5">
                <h3 class="text-2xl font-bold text-[#001f54] flex items-center gap-2">
                    <i data-lucide="package-plus" class="w-5 h-5 text-[#F7B32B]"></i>
                    <span>Request New Stock</span>
                </h3>
                <label for="add-stock-modal" 
                       class="btn btn-circle btn-ghost btn-sm text-[#001f54] hover:text-[#F7B32B] border-[#F7B32B]/30 hover:border-[#F7B32B]">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </label>
            </div>

            <div class="overflow-y-auto max-h-[70vh] 
                        [scrollbar-width:none] 
                        [-ms-overflow-style:none] 
                        [&::-webkit-scrollbar]:hidden">
                <form id="add-stock-form" action="sub-modules/add_stock_request.php" method="POST" enctype="multipart/form-data" class="space-y-4" autocomplete="off">
                    <div class="form-control">
                        <label class="label">
                            <span class="label-text text-gray-700 font-medium">Item Image (Optional)</span>
                            <span class="label-text-alt text-gray-400">Max 2MB</span>
                        </label>
                        <div class="flex items-center gap-4">
                            <!-- Image preview -->
                            <div class="relative w-24 h-24 border-2 border-dashed border-gray-300 rounded-lg overflow-hidden bg-gray-50 flex items-center justify-center">
                                <img id="image-preview" src="" alt="Preview" class="hidden w-full h-full object-cover">
                                <div id="placeholder-icon" class="text-gray-400">
                                    <i data-lucide="image" class="w-8 h-8"></i>
                                </div>
                            </div>
                            
                            <div class="flex-1">
                                <input type="file" id="image-upload" name="item_image" accept="image/*"
                                       class="file-input file-input-bordered bg-white w-full"
                                       onchange="previewImage(this)" />
                                <p class="text-xs text-gray-500 mt-1">Accepted: JPG, PNG, GIF, WEBP (max 2MB)</p>
                            </div>
                        </div>
                    </div>

                    <div class="form-control">
                        <label class="label">
                            <span class="label-text text-gray-700 font-medium">Item Name</span>
                        </label>
                        <input type="text" name="item_name" required placeholder="Enter item name"
                            class="input input-bordered bg-white w-full" />
                    </div>

                    <div class="form-control">
                        <label class="label">
                            <span class="label-text text-gray-700 font-medium">Category</span>
                        </label>
                        <select name="category" required
                            class="select select-bordered bg-white w-full">
                            <option value="" disabled selected>Select Category</option>
                            <option value="food">Food Items</option>
                            <option value="beverage">Beverages</option>
                            <option value="supplies">Supplies</option>
                            <option value="other">Other</option>
                        </select>
                    </div>

                    <div class="form-control">
                        <label class="label">
                            <span class="label-text text-gray-700 font-medium">Location</span>
                        </label>
                        <input type="text" name="location" required placeholder="Enter storage or branch location"
                            class="input input-bordered bg-white w-full" />
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div class="form-control">
                            <label class="label">
                                <span class="label-text text-gray-700 font-medium">Quantity</span>
                            </label>
                            <input type="number" name="quantity" min="1" required placeholder="0"
                                class="input input-bordered bg-white w-full" />
                        </div>

                        <div class="form-control">
                            <label class="label">
                                <span class="label-text text-gray-700 font-medium">Critical Level</span>
                            </label>
                            <input type="number" name="critical_level" min="1" required placeholder="1"
                                class="input input-bordered bg-white w-full" />
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div class="form-control">
                            <label class="label">
                                <span class="label-text text-gray-700 font-medium">Unit Price (₱)</span>
                            </label>
                            <div class="relative">
                                <span class="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-500">₱</span>
                                <input type="number" name="unit_price" min="0" step="0.01" required placeholder="0.00"
                                    class="input input-bordered bg-white w-full pl-8" />
                            </div>
                        </div>

                        <div class="form-control">
                            <label class="label">
                                <span class="label-text text-gray-700 font-medium">Expiry Date</span>
                            </label>
                            <input type="date" name="expiry_date" class="input input-bordered bg-white w-full" />
                        </div>
                    </div>

                    <div class="form-control">
                        <label class="label">
                            <span class="label-text text-gray-700 font-medium">Last Restock Date</span>
                        </label>
                        <input type="date" name="last_restock_date" class="input input-bordered bg-white w-full" />
                    </div>

                    <div class="form-control">
                        <label class="label">
                            <span class="label-text text-gray-700 font-medium">Notes (Optional)</span>
                        </label>
                        <textarea name="notes" rows="2" placeholder="Any additional information..."
                            class="textarea textarea-bordered bg-white w-full"></textarea>
                    </div>

                    <input type="hidden" name="request_status" value="pending" />

                    <div class="modal-action mt-6 flex justify-end gap-3">
                        <label for="add-stock-modal"
                            class="btn btn-outline border-[#F7B32B] text-[#F7B32B] hover:bg-[#F7B32B]/10 hover:border-[#F7B32B] hover:text-[#001f54]">
                            Cancel
                        </label>
                        <button type="submit" class="btn bg-[#001f54] hover:bg-[#001a48] text-[#F7B32B] flex items-center">
                            <i data-lucide="check" class="w-4 h-4 mr-1"></i>
                            Add Stock
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Single Stock Details Modal (Dynamic) -->
    <div id="stock-modal" class="modal-backdrop fixed inset-0 bg-black bg-opacity-50 hidden z-50">
        <div class="modal-content w-full max-w-6xl mx-4 overflow-hidden animate__animated animate__fadeIn">
            <div class="modal-header">
                <div class="flex justify-between items-center">
                    <div class="flex items-center gap-3">
                        <div class="p-2 bg-[#F7B32B] rounded-lg">
                            <i data-lucide="package" class="w-6 h-6 text-[#001f54]"></i>
                        </div>
                        <h3 id="modal-title" class="text-xl font-bold">Stock Details</h3>
                    </div>
                    <button onclick="closeModal()" class="text-[#F7B32B] hover:text-white p-2 rounded-lg hover:bg-white/10 transition-colors">
                        <i data-lucide="x" class="w-6 h-6"></i>
                    </button>
                </div>
            </div>

            <div class="overflow-y-auto max-h-[70vh]">
                <div class="info-grid p-6" id="modal-content">
                    <!-- Content will be loaded via AJAX -->
                </div>
            </div>

            <div class="modal-footer">
                <div class="flex justify-between items-center">
                   
                    
                  
                </div>
            </div>
        </div>
    </div>

    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="../JavaScript/sidebar.js"></script>
    <script src="../JavaScript/soliera.js"></script>
    
    <script>
    // Initialize Lucide icons
    lucide.createIcons();

    // Global variables for modal
    let currentItemId = null;
    let currentItemName = null;

    // Show stock details via AJAX
    async function showStockDetails(itemId) {
        currentItemId = itemId;
        
        try {
            // Show loading state
            const modal = document.getElementById('stock-modal');
            modal.classList.remove('hidden');
            document.body.style.overflow = 'hidden';
            
            const contentDiv = document.getElementById('modal-content');
            contentDiv.innerHTML = `
                <div class="flex items-center justify-center h-64">
                    <div class="loading loading-spinner loading-lg"></div>
                </div>
            `;
            
            // Fetch stock details
            const response = await fetch(`sub-modules/get_stock_details.php?id=${itemId}`);
            if (!response.ok) throw new Error('Network response was not ok');
            
            const data = await response.json();
            
            if (data.success) {
                const stock = data.data;
                currentItemName = stock.item_name;
                
                // Update modal title
                document.getElementById('modal-title').textContent = stock.item_name;
                
                
                
                // Format data
                const status = getStockStatus(stock.quantity, stock.critical_level, stock.expiry_date);
                const quantityColor = getQuantityColorClass(stock.quantity, stock.critical_level);
                const progressBarColor = getProgressBarColor(stock.quantity, stock.critical_level);
                const percentage = calculatePercentage(stock.quantity, stock.critical_level);
                const expiringSoon = isExpiringSoon(stock.expiry_date);
                
                // Create modal content
                contentDiv.innerHTML = `
<div class="space-y-6">
    <!-- Image Section -->
 <div class="rounded-xl overflow-hidden border border-gray-200 aspect-video bg-gray-100">
    ${stock.image_url ? 
        `<img src="upload_images/${escapeHtml(stock.image_url)}" 
             alt="${escapeHtml(stock.item_name)}" 
             class="w-full h-full object-cover transition-transform duration-500 hover:scale-110"
             onerror="this.onerror=null; this.src='data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMjAwIiBoZWlnaHQ9IjIwMCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iMjAwIiBoZWlnaHQ9IjIwMCIgZmlsbD0iI2YwZjBmMCIvPjx0ZXh0IHg9IjUwJSIgeT0iNTAlIiBmb250LWZhbWlseT0iQXJpYWwiIGZvbnQtc2l6ZT0iMTQiIHRleHQtYW5jaG9yPSJtaWRkbGUiIGZpbGw9IiM5OTkiPkltYWdlIE5vdCBGb3VuZDwvdGV4dD48L3N2Zz4='">` : 
        `<div class="w-full h-full flex flex-col items-center justify-center">
            <i data-lucide="image" class="w-16 h-16 text-gray-400 mb-4"></i>
            <p class="text-gray-500 font-medium">No image available</p>
            <p class="text-gray-400 text-sm mt-2">Image will appear here</p>
        </div>`}
</div>
    <!-- Main Details Section -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div class="space-y-4">
            <div>
                <h4 class="text-xl font-semibold text-gray-800">${escapeHtml(stock.item_name)}</h4>
                <div class="flex items-center gap-2 mt-2">
                    <span class="status-badge ${status.class} inline-flex items-center gap-2 px-3 py-1.5 rounded-full text-sm font-medium">
                        ${getStatusIcon(status.status)}
                        ${status.status}
                    </span>
                    <span class="text-gray-500">•</span>
                    <span class="text-sm text-gray-600">ID: #${stock.item_id}</span>
                </div>
            </div>
            
            <!-- Stock Level Progress -->
            <div class="space-y-3 pt-3 border-t border-gray-100">
                <div class="flex justify-between items-center">
                    <span class="text-sm font-medium text-gray-700">Current Stock</span>
                    <span class="text-lg font-bold ${quantityColor}">${stock.quantity} pcs</span>
                </div>
                <div class="w-full bg-gray-200 rounded-full h-2">
                    <div class="h-2 rounded-full ${progressBarColor} transition-all duration-700" 
                         style="width: ${percentage}%"></div>
                </div>
                <div class="flex justify-between text-xs text-gray-500">
                    <span>0</span>
                    <span class="font-medium">Critical: ${stock.critical_level}</span>
                    <span>${stock.critical_level * 3}</span>
                </div>
            </div>
        </div>
        
        <div class="space-y-4">
            <!-- Category -->
            <div class="flex items-center gap-3">
                <div class="p-2.5 rounded-lg bg-blue-100">
                    <i data-lucide="tag" class="w-4 h-4 text-blue-600"></i>
                </div>
                <div class="flex-1">
                    <p class="text-sm text-gray-500">Category</p>
                    <p class="font-medium text-gray-800">${escapeHtml(stock.category)}</p>
                </div>
            </div>
            
            <!-- Location -->
            <div class="flex items-center gap-3">
                <div class="p-2.5 rounded-lg bg-green-100">
                    <i data-lucide="map-pin" class="w-4 h-4 text-green-600"></i>
                </div>
                <div class="flex-1">
                    <p class="text-sm text-gray-500">Location</p>
                    <p class="font-medium text-gray-800">${escapeHtml(stock.location)}</p>
                </div>
            </div>
            
            <!-- Unit Price -->
            <div class="flex items-center gap-3">
                <div class="p-2.5 rounded-lg bg-purple-100">
                    <i data-lucide="dollar-sign" class="w-4 h-4 text-purple-600"></i>
                </div>
                <div class="flex-1">
                    <p class="text-sm text-gray-500">Unit Price</p>
                    <p class="font-medium text-gray-800">₱${formatPrice(stock.unit_price)}</p>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Dates Section -->
    <div class="pt-4 border-t border-gray-200">
        <h5 class="text-sm font-semibold text-gray-700 mb-3">Important Dates</h5>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <!-- Expiry Date -->
            <div class="flex items-center gap-3">
                <div class="p-2.5 rounded-lg ${expiringSoon ? 'bg-red-100' : 'bg-purple-100'}">
                    <i data-lucide="calendar" class="w-4 h-4 ${expiringSoon ? 'text-red-600' : 'text-purple-600'}"></i>
                </div>
                <div class="flex-1">
                    <p class="text-sm text-gray-500">Expiry Date</p>
                    <div class="flex items-center gap-2">
                        <p class="font-medium ${expiringSoon ? 'text-red-600' : 'text-gray-800'}">
                            ${formatExpiryDate(stock.expiry_date)}
                        </p>
                        ${expiringSoon ? 
                            '<span class="px-2 py-0.5 text-xs font-semibold bg-red-100 text-red-700 rounded-full animate-pulse">Expiring Soon!</span>' : 
                            ''}
                    </div>
                </div>
            </div>
            
            <!-- Last Restock -->
            <div class="flex items-center gap-3">
                <div class="p-2.5 rounded-lg bg-indigo-100">
                    <i data-lucide="refresh-cw" class="w-4 h-4 text-indigo-600"></i>
                </div>
                <div class="flex-1">
                    <p class="text-sm text-gray-500">Last Restock</p>
                    <p class="font-medium text-gray-800">${formatExpiryDate(stock.last_restock_date)}</p>
                </div>
            </div>
            
            <!-- Created Date -->
            <div class="flex items-center gap-3">
                <div class="p-2.5 rounded-lg bg-gray-100">
                    <i data-lucide="clock" class="w-4 h-4 text-gray-600"></i>
                </div>
                <div class="flex-1">
                    <p class="text-sm text-gray-500">Created On</p>
                    <p class="font-medium text-gray-800">${formatDateTime(stock.created_at)}</p>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Critical Level Section -->
    <div class="pt-4 border-t border-gray-200">
        <h5 class="text-sm font-semibold text-gray-700 mb-3">Stock Level Information</h5>
        <div class="flex items-center gap-3">
            <div class="p-2.5 rounded-lg ${stock.quantity <= stock.critical_level ? 'bg-red-100' : 'bg-amber-100'}">
                <i data-lucide="alert-triangle" class="w-4 h-4 ${stock.quantity <= stock.critical_level ? 'text-red-600' : 'text-amber-600'}"></i>
            </div>
            <div class="flex-1">
                <p class="text-sm text-gray-500">Critical Level</p>
                <div class="flex items-center gap-4">
                    <p class="font-medium ${stock.quantity <= stock.critical_level ? 'text-red-600' : 'text-gray-800'}">
                        ${stock.critical_level} units
                    </p>
                    ${stock.quantity <= stock.critical_level ? 
                        '<span class="px-2 py-0.5 text-xs font-semibold bg-red-100 text-red-700 rounded-full">Critical!</span>' : 
                        stock.quantity <= stock.critical_level * 2 ? 
                        '<span class="px-2 py-0.5 text-xs font-semibold bg-amber-100 text-amber-700 rounded-full">Low Stock</span>' : 
                        '<span class="px-2 py-0.5 text-xs font-semibold bg-green-100 text-green-700 rounded-full">Safe</span>'}
                </div>
            </div>
        </div>
    </div>
    
    <!-- Notes Section (if exists) -->
    ${stock.notes ? `
        <div class="pt-4 border-t border-gray-200">
            <h5 class="text-sm font-semibold text-gray-700 mb-3">Additional Notes</h5>
            <div class="flex items-start gap-3">
                <div class="p-2.5 rounded-lg bg-amber-100 mt-1">
                    <i data-lucide="file-text" class="w-4 h-4 text-amber-600"></i>
                </div>
                <div class="flex-1">
                    <div class="text-gray-700 whitespace-pre-wrap bg-gray-50 p-4 rounded-lg border border-gray-200 text-sm">
                        ${escapeHtml(stock.notes).replace(/\n/g, '<br>')}
                    </div>
                </div>
            </div>
        </div>
    ` : ''}
    
  
    
    <!-- Warning Alerts Section -->
    <div id="stock-warnings" class="space-y-3">
        ${stock.quantity <= stock.critical_level ? `
            <div class="flex items-start gap-3 p-4 bg-red-50 border border-red-200 rounded-lg">
                <i data-lucide="alert-triangle" class="w-5 h-5 text-red-600 mt-0.5 flex-shrink-0"></i>
                <div class="flex-1">
                    <h3 class="font-bold text-red-800 mb-1">Critical Stock Alert!</h3>
                    <p class="text-sm text-red-600">
                        Stock level is at or below critical level (${stock.critical_level}). Immediate restock required.
                    </p>
                </div>
            </div>
        ` : ''}
        
        ${expiringSoon ? `
            <div class="flex items-start gap-3 p-4 bg-amber-50 border border-amber-200 rounded-lg">
                <i data-lucide="calendar-x" class="w-5 h-5 text-amber-600 mt-0.5 flex-shrink-0"></i>
                <div class="flex-1">
                    <h3 class="font-bold text-amber-800 mb-1">Expiry Alert!</h3>
                    <p class="text-sm text-amber-600">
                        Item is expiring soon (${formatExpiryDate(stock.expiry_date)}). Consider using or disposing before expiry.
                    </p>
                </div>
            </div>
        ` : ''}
    </div>
</div>   `;
                
                // Reinitialize icons in modal
                lucide.createIcons();
                
            } else {
                throw new Error(data.error || 'Failed to load stock details');
            }
        } catch (error) {
            console.error('Error loading stock details:', error);
            const contentDiv = document.getElementById('modal-content');
            contentDiv.innerHTML = `
                <div class="flex flex-col items-center justify-center h-64">
                    <i data-lucide="alert-circle" class="w-16 h-16 text-red-500 mb-4"></i>
                    <h3 class="text-lg font-semibold text-gray-800 mb-2">Error Loading Details</h3>
                    <p class="text-gray-600">${error.message}</p>
                </div>
            `;
            lucide.createIcons();
        }
    }

    function closeModal() {
        const modal = document.getElementById('stock-modal');
        modal.classList.add('hidden');
        document.body.style.overflow = 'auto';
    }

    function deleteStock(itemId, itemName) {
        Swal.fire({
            title: 'Delete Confirmation',
            html: `
                <div class="text-center">
                    <div class="mx-auto w-16 h-16 bg-red-100 rounded-full flex items-center justify-center mb-4">
                        <i data-lucide="trash-2" class="w-8 h-8 text-red-600"></i>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-800 mb-2">Delete "${escapeHtml(itemName)}"?</h3>
                    <p class="text-gray-600 mb-4">This action cannot be undone. All data associated with this item will be permanently removed.</p>
                </div>
            `,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc2626',
            cancelButtonColor: '#6b7280',
            confirmButtonText: '<i data-lucide="trash-2" class="w-4 h-4 mr-2"></i> Yes, Delete',
            cancelButtonText: '<i data-lucide="x" class="w-4 h-4 mr-2"></i> Cancel',
            reverseButtons: true,
            customClass: {
                confirmButton: 'swal2-confirm-destructive',
                cancelButton: 'swal2-cancel-safe'
            },
            didOpen: () => {
                lucide.createIcons();
            }
        }).then((result) => {
            if (result.isConfirmed) {
                // Redirect to delete script
                window.location.href = `sub-modules/delete_stock.php?id=${itemId}`;
            }
        });
    }

    function editStock(itemId) {
        // Redirect to edit page
        window.location.href = `edit_stock.php?id=${itemId}`;
    }

    // Helper functions for JavaScript
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    function formatPrice(price) {
        return parseFloat(price).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
    }

    function getStockStatus(quantity, criticalLevel, expiryDate) {
        const today = new Date();
        const expiry = expiryDate ? new Date(expiryDate) : null;
        
        if (expiry && expiry < today) {
            return { status: 'Expired', class: 'bg-purple-100 text-purple-700' };
        }
        if (quantity <= 0) {
            return { status: 'Out of Stock', class: 'bg-red-100 text-red-700' };
        }
        if (quantity <= criticalLevel) {
            return { status: 'Low Stock', class: 'bg-amber-100 text-amber-700' };
        }
        return { status: 'In Stock', class: 'bg-green-100 text-green-700' };
    }

    function getStatusIcon(status) {
        const icons = {
            'Out of Stock': '<i data-lucide="x-circle" class="w-3 h-3"></i>',
            'Low Stock': '<i data-lucide="alert-circle" class="w-3 h-3"></i>',
            'In Stock': '<i data-lucide="check-circle" class="w-3 h-3"></i>',
            'Expired': '<i data-lucide="calendar-x" class="w-3 h-3"></i>'
        };
        return icons[status] || '<i data-lucide="package" class="w-3 h-3"></i>';
    }

    function getQuantityColorClass(quantity, criticalLevel) {
        if (quantity <= 0) return 'text-red-600';
        if (quantity <= criticalLevel) return 'text-amber-600';
        if (quantity <= criticalLevel * 2) return 'text-blue-600';
        return 'text-green-600';
    }

    function getProgressBarColor(quantity, criticalLevel) {
        if (quantity <= 0) return 'bg-red-500';
        if (quantity <= criticalLevel) return 'bg-amber-500';
        if (quantity <= criticalLevel * 2) return 'bg-blue-500';
        return 'bg-green-500';
    }

    function calculatePercentage(quantity, criticalLevel) {
        const max = criticalLevel * 3;
        return max > 0 ? Math.min((quantity / max) * 100, 100) : 0;
    }

    function isExpiringSoon(expiryDate) {
        if (!expiryDate) return false;
        const today = new Date();
        const expiry = new Date(expiryDate);
        const timeDiff = expiry.getTime() - today.getTime();
        const daysDiff = Math.ceil(timeDiff / (1000 * 3600 * 24));
        return daysDiff <= 7 && daysDiff >= 0;
    }

    function formatExpiryDate(date) {
        if (!date) return 'Not set';
        
        const expiry = new Date(date);
        const today = new Date();
        const tomorrow = new Date();
        tomorrow.setDate(today.getDate() + 1);
        
        if (expiry.toDateString() === today.toDateString()) return 'Today';
        if (expiry.toDateString() === tomorrow.toDateString()) return 'Tomorrow';
        
        let format = expiry.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
        if (expiry.getFullYear() !== today.getFullYear()) {
            format += ', ' + expiry.getFullYear();
        }
        return format;
    }

    function formatDateTime(dateString) {
        const date = new Date(dateString);
        return date.toLocaleDateString('en-US', { 
            month: 'short', 
            day: 'numeric', 
            year: 'numeric',
            hour: 'numeric',
            minute: '2-digit',
            hour12: true
        });
    }

    // Image preview function for add stock form
    function previewImage(input) {
        const preview = document.getElementById('image-preview');
        const placeholder = document.getElementById('placeholder-icon');
        
        if (input.files && input.files[0]) {
            const reader = new FileReader();
            
            reader.onload = function(e) {
                preview.src = e.target.result;
                preview.classList.remove('hidden');
                placeholder.classList.add('hidden');
            }
            
            reader.readAsDataURL(input.files[0]);
        } else {
            preview.src = '';
            preview.classList.add('hidden');
            placeholder.classList.remove('hidden');
        }
    }

    // Form submission handling
    document.getElementById('add-stock-form').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        const submitBtn = this.querySelector('button[type="submit"]');
        const originalBtnText = submitBtn.innerHTML;
        
        // Show loading state
        submitBtn.innerHTML = '<span class="loading loading-spinner loading-sm"></span> Adding...';
        submitBtn.disabled = true;
        
        // Submit form
        fetch(this.action, {
            method: 'POST',
            body: formData
        })
        .then(response => {
            if (response.redirected) {
                window.location.href = response.url;
            } else {
                return response.text();
            }
        })
        .then(data => {
            // Handle response if not redirected
            console.log(data);
            window.location.reload();
        })
        .catch(err => {
            console.error('Error:', err);
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Error adding stock request'
            });
            submitBtn.innerHTML = originalBtnText;
            submitBtn.disabled = false;
        });
    });

    // Close modal when clicking outside
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('modal-backdrop')) {
            e.target.classList.add('hidden');
            document.body.style.overflow = 'auto';
        }
    });

    // Auto-submit search on enter
    document.querySelector('.search-input').addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            this.form.submit();
        }
    });

    // Check for success/error messages in URL
    document.addEventListener('DOMContentLoaded', function() {
        const urlParams = new URLSearchParams(window.location.search);
        
        if (urlParams.has('success')) {
            Swal.fire({
                icon: 'success',
                title: 'Success!',
                text: 'Operation completed successfully',
                timer: 2000,
                showConfirmButton: false,
                willClose: () => {
                    // Remove success parameter from URL
                    window.history.replaceState({}, document.title, window.location.pathname);
                }
            });
        }
        
        if (urlParams.has('error')) {
            const errorMsg = urlParams.get('error');
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: errorMsg || 'Operation failed'
            });
            // Remove error parameter from URL
            window.history.replaceState({}, document.title, window.location.pathname);
        }
    });
    </script>
<!-- Your existing notification script -->
<script>
document.addEventListener("DOMContentLoaded", () => {
  const notifButton = document.getElementById("notification-button");
  const notifContainer = document.querySelector(".dropdown-content .max-h-96");
  const notifBadge = document.getElementById("notif-badge");
  const clearAllBtn = document.querySelector(".dropdown-content button.text-blue-300");
  const apiURL = "../notification_api.php";

  let currentNotifIds = new Set();
  let lastFetch = 0;

  // 📨 Fetch notifications smartly
  async function fetchNotifications() {
    try {
      const res = await fetch(apiURL);
      const data = await res.json();

      if (data.status !== "success") return;

      const notifications = data.notifications || [];
      const unreadCount = notifications.filter(n => n.status!=='Read').length;

      notifBadge.classList.toggle("hidden", unreadCount === 0);
      notifBadge.textContent = unreadCount;

      notifications.forEach(n => {
        if (!currentNotifIds.has(n.notification_id) && n.status!=='Read') {
          currentNotifIds.add(n.notification_id);
          displayNotification(n);
        }
      });

    } catch (err) {
      console.error("Fetch error:", err);
    }
  }

  function displayNotification(n) {
    const item = document.createElement("li");
    item.className = "notif-item border border-blue-900/40 rounded-xl bg-blue-950/30 px-4 py-3 flex flex-col transition-all duration-300 opacity-0";
    item.dataset.id = n.notification_id;
    item.innerHTML = `
      <div class="flex justify-between items-center">
        <span class="font-medium text-white">${n.employee_name || "System"}</span>
        <span class="text-xs text-gray-400">${formatDatePH(n.date_sent)}</span>
      </div>
      <p class="text-sm text-gray-300 mt-1">${n.message}</p>
      <span class="text-xs text-blue-300 mt-1">(Unread)</span>
    `;

    item.addEventListener("mouseenter", () => item.style.backgroundColor = "#1e40af66");
    item.addEventListener("mouseleave", () => item.style.backgroundColor = "#1e3a8a33");

    notifContainer.prepend(item);
    requestAnimationFrame(() => item.style.opacity = 1);

    item.addEventListener("click", () => markAsRead(n.notification_id, n.module, item));
  }

  function updateBadgeCount() {
    const unread = notifContainer.querySelectorAll("span.text-blue-300").length;
    notifBadge.textContent = unread;
    notifBadge.classList.toggle("hidden", unread===0);
  }

  async function markAsRead(id, module, item) {
    const formData = new FormData();
    formData.append("notif_id", id);
    formData.append("module", module);

    try {
      const res = await fetch(apiURL, { method: "POST", body: formData });
      const data = await res.json();
      if (data.status !== "success") console.warn(data.message);

      item.style.transition = "all 0.4s ease";
      item.style.opacity = 0;
      item.style.transform = "translateX(50px)";
      setTimeout(() => {
        item.remove();
        currentNotifIds.delete(id);
        updateBadgeCount();
      }, 400);
    } catch (err) {
      console.error("Mark read error:", err);
    }
  }

  // Clear all
  clearAllBtn.addEventListener("click", async () => {
    document.querySelectorAll(".notif-item").forEach(i => i.remove());
    notifBadge.classList.add("hidden");
    currentNotifIds.clear();

    const formData = new FormData();
    formData.append("clear_all", "1");
    try { await fetch(apiURL,{method:"POST",body:formData}); } catch(e){console.error(e);}
  });

  // PH-time formatter
  function formatDatePH(dateStr) {
    const d = new Date(dateStr);
    return d.toLocaleString("en-PH", { month:"short", day:"numeric", hour:"2-digit", minute:"2-digit" });
  }

  // ===== Real-time using requestAnimationFrame loop (smart polling) =====
  function realTimeLoop() {
    const now = Date.now();
    // Fetch every 5s
    if (now - lastFetch > 5000) {
      fetchNotifications();
      lastFetch = now;
    }
    requestAnimationFrame(realTimeLoop);
  }
  realTimeLoop();

  // Manual refresh on button click
  notifButton.addEventListener("click", fetchNotifications);
});
</script>   

</body>
</html>