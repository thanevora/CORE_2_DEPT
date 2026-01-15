<?php
session_start();
include("../main_connection.php");

$db_name = "rest_m6_kot";
$conn = $connections[$db_name] ?? die("❌ Connection not found for $db_name");

// Hotel API credentials
$hotel_api_token = "uX8B1QqYJt7XqTf0sM3tKAh5nCjEjR1Xlqk4F8ZdD1mHq5V9y7oUj1QhUzPg5s";
$hotel_api_url = "https://hotel.soliera-hotel-restaurant.com/api/getKOT";

// Check if we're showing hotel orders or local orders
$show_hotel_orders = isset($_GET['hotel']) && $_GET['hotel'] == 'true';

// Handle the PUT request when the form is submitted (for hotel API)
$successMessage = "";
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['order_id'])) {
    $kotID = $_POST['order_id'];
    $url = "https://hotel.soliera-hotel-restaurant.com/api/cookKOT/$kotID";

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST => "PUT",
        CURLOPT_POSTFIELDS => json_encode(["status" => "cooked"]),
        CURLOPT_HTTPHEADER => [
            "Authorization: Bearer $hotel_api_token",
            "Accept: application/json",
            "Content-Type: application/json"
        ]
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $respData = json_decode($response, true);

    if ($httpCode == 200 || $httpCode == 201) {
        $successMessage = "KOT #$kotID successfully set to Cooked!";
    } else {
        $successMessage = "Error updating KOT: " . ($respData['message'] ?? $response);
    }
}

// Pagination settings
$orders_per_page = 20;
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * $orders_per_page;

// Search and filter parameters
$search = isset($_GET['search']) ? $_GET['search'] : '';
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$date_filter = isset($_GET['date']) ? $_GET['date'] : '';

// Initialize arrays for hotel orders
$hotel_orders = [];
$hotel_api_data = [];
$hotel_stats = [
    'pending' => 0,
    'preparing' => 0,
    'cooking' => 0,
    'ready_to_serve' => 0,
    'voided' => 0,
    'delivered' => 0
];

// Fetch hotel orders if toggled
if ($show_hotel_orders) {
    try {
        $ch = curl_init($hotel_api_url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                "Authorization: Bearer $hotel_api_token",
                "Accept: application/json",
                "Content-Type: application/json"
            ],
            CURLOPT_TIMEOUT => 10,
            CURLOPT_SSL_VERIFYPEER => false
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        if ($response === false) {
            $error = curl_error($ch);
            curl_close($ch);
            throw new Exception("CURL Error: " . $error);
        }
        
        curl_close($ch);
        
        $hotel_api_data = json_decode($response, true);
        
        if ($httpCode != 200) {
            throw new Exception("API Error: HTTP $httpCode - " . ($hotel_api_data['message'] ?? 'Unknown error'));
        }
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception("JSON Parse Error: " . json_last_error_msg());
        }
        
        $hotel_orders_raw = $hotel_api_data['data'] ?? [];
        
        // DEBUG: Log hotel data
        error_log("Hotel API Response: " . print_r($hotel_api_data, true));
        error_log("Raw hotel orders count: " . count($hotel_orders_raw));
        
        // Process hotel orders to match our format
        foreach ($hotel_orders_raw as $hotel_order) {
            if (!is_array($hotel_order)) continue;
            
            $processed_order = [
                'kot_id' => $hotel_order['kot_id'] ?? null,
                'order_id' => $hotel_order['order_id'] ?? null,
                'table_number' => $hotel_order['table_number'] ?? null,
                'item_name' => $hotel_order['item_name'] ?? null,
                'quantity' => $hotel_order['quantity'] ?? null,
                'status' => $hotel_order['status'] ?? 'pending',
                'created_at' => $hotel_order['created_at'] ?? date('Y-m-d H:i:s'),
                'price' => $hotel_order['price'] ?? null,
                'notes' => $hotel_order['notes'] ?? null,
                'special_instructions' => $hotel_order['special_instructions'] ?? null,
                'source' => 'hotel' // Mark as hotel order
            ];
            
            // Calculate hotel stats
            $status = strtolower($processed_order['status']);
            if ($status === 'pending' || $status === 'ordered') {
                $hotel_stats['pending']++;
            } elseif ($status === 'preparing' || $status === 'preparation') {
                $hotel_stats['preparing']++;
            } elseif ($status === 'cooking' || $status === 'cook') {
                $hotel_stats['cooking']++;
            } elseif ($status === 'cooked' || $status === 'ready_to_serve' || $status === 'ready') {
                $hotel_stats['ready_to_serve']++;
            } elseif ($status === 'delivered' || $status === 'served') {
                $hotel_stats['delivered']++;
            } elseif ($status === 'voided' || $status === 'cancelled') {
                $hotel_stats['voided']++;
            }
            
            $hotel_orders[] = $processed_order;
        }
        
        // For hotel orders, apply filters to the array
        $filtered_orders = [];
        foreach ($hotel_orders as $order) {
            $status = strtolower($order['status']);
            $mappedStatus = 'pending';
            
            // Map to our status categories
            if ($status === 'pending' || $status === 'ordered') {
                $mappedStatus = 'pending';
            } elseif ($status === 'preparing' || $status === 'preparation') {
                $mappedStatus = 'preparing';
            } elseif ($status === 'cooking' || $status === 'cook') {
                $mappedStatus = 'cooking';
            } elseif ($status === 'cooked' || $status === 'ready_to_serve' || $status === 'ready') {
                $mappedStatus = 'ready_to_serve';
            } elseif ($status === 'delivered' || $status === 'served') {
                $mappedStatus = 'delivered';
            } elseif ($status === 'voided' || $status === 'cancelled') {
                $mappedStatus = 'voided';
            }
            
            // Apply filters
            $status_match = !$status_filter || $mappedStatus === $status_filter;
            
            // Apply search filter
            $search_match = true;
            if ($search) {
                $search_lower = strtolower($search);
                $search_match = 
                    (isset($order['order_id']) && stripos($order['order_id'], $search_lower) !== false) ||
                    (isset($order['item_name']) && stripos($order['item_name'], $search_lower) !== false) ||
                    (isset($order['table_number']) && stripos($order['table_number'], $search_lower) !== false) ||
                    (isset($order['kot_id']) && stripos($order['kot_id'], $search_lower) !== false);
            }
            
            // Apply date filter
            $date_match = true;
            if ($date_filter) {
                $order_date = date('Y-m-d', strtotime($order['created_at']));
                $date_match = $order_date === $date_filter;
            }
            
            if ($status_match && $search_match && $date_match) {
                $filtered_orders[] = $order;
            }
        }
        
        $total_filtered = count($filtered_orders);
        $total_pages = ceil($total_filtered / $orders_per_page);
        $paginated_orders = array_slice($filtered_orders, $offset, $orders_per_page);
        
    } catch (Exception $e) {
        error_log("Hotel API Error: " . $e->getMessage());
        $hotel_error = "Failed to fetch hotel orders: " . $e->getMessage();
        $total_filtered = 0;
        $total_pages = 1;
        $paginated_orders = [];
    }
    
} else {
    // Local database orders
    // Escape parameters for SQL
    $search_sql = $conn->real_escape_string($search);
    $status_filter_sql = $conn->real_escape_string($status_filter);
    $date_filter_sql = $conn->real_escape_string($date_filter);
    
    // Build WHERE conditions for local database
    $where_conditions = [];
    $params = [];
    $types = '';

    if (!empty($search)) {
        $where_conditions[] = "(order_id LIKE ? OR item_name LIKE ? OR table_number LIKE ? OR kot_id LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
        $params[] = "%$search%";
        $params[] = "%$search%";
        $types .= 'ssss';
    }

    if (!empty($status_filter) && $status_filter !== 'all') {
        $where_conditions[] = "status = ?";
        $params[] = $status_filter;
        $types .= 's';
    }

    if (!empty($date_filter)) {
        $where_conditions[] = "DATE(created_at) = ?";
        $params[] = $date_filter;
        $types .= 's';
    }

    // Build the query for local database
    $where_clause = empty($where_conditions) ? '' : 'WHERE ' . implode(' AND ', $where_conditions);
    $query = "SELECT * FROM kot_orders $where_clause ORDER BY created_at DESC LIMIT ? OFFSET ?";
    $params[] = $orders_per_page;
    $params[] = $offset;
    $types .= 'ii';

    // Execute query for local database
    $stmt = $conn->prepare($query);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $orders_result = $stmt->get_result();

    // Get total count for pagination for local database
    $count_query = "SELECT COUNT(*) as total FROM kot_orders $where_clause";
    if (!empty($where_conditions)) {
        $count_params = array_slice($params, 0, count($params) - 2);
        $count_types = substr($types, 0, -2);
        
        if (empty($count_params)) {
            $count_result = $conn->query($count_query);
        } else {
            $count_stmt = $conn->prepare($count_query);
            $count_stmt->bind_param($count_types, ...$count_params);
            $count_stmt->execute();
            $count_result = $count_stmt->get_result();
        }
    } else {
        $count_result = $conn->query($count_query);
    }
    $total_filtered = $count_result->fetch_assoc()['total'];
    $total_pages = ceil($total_filtered / $orders_per_page);

    // Get menu database connection for prep_time
    $menu_db_name = "rest_m3_menu";
    $menu_conn = $connections[$menu_db_name] ?? null;

    // Get POS database connection for table_id
    $pos_db_name = "rest_m1_pos";
    $pos_conn = $connections[$pos_db_name] ?? null;

    // Calculate stats for local database (excluding completed)
    $stats_query = "SELECT 
        SUM(LOWER(status) = 'pending') as pending,
        SUM(LOWER(status) = 'preparing') as preparing,
        SUM(LOWER(status) = 'cook') as cooking,
        SUM(LOWER(status) = 'serve') as ready_to_serve,
        SUM(LOWER(status) = 'voided') as voided,
        SUM(LOWER(status) = 'delivered') as delivered
        FROM kot_orders";
    
    $stats_result = $conn->query($stats_query);
    $stats = $stats_result->fetch_assoc();
    
    // Fetch all orders for timer calculations (local only)
    $all_orders_query = "SELECT kot_id, status, created_at FROM kot_orders WHERE status IN ('cook', 'pending', 'serve', 'preparing')";
    $all_orders_result = $conn->query($all_orders_query);
    $order_timers = [];
    while ($order = $all_orders_result->fetch_assoc()) {
        $order_timers[$order['kot_id']] = $order;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <?php include '../header.php'; ?>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kitchen Display System | Soliera</title>
    <!-- SweetAlert2 CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    
    <style>
        :root {
            --primary: #4f46e5;
            --primary-light: #6366f1;
            --secondary: #10b981;
            --danger: #ef4444;
            --warning: #f59e0b;
            --dark: #1e293b;
            --light: #f8fafc;
        }
        
        body {
            font-family: 'Inter', system-ui, sans-serif;
            background-color: #f1f5f9;
            color: var(--dark);
        }
        
        .order-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            border-left: 4px solid var(--primary);
            overflow: hidden;
        }
        
        .stat-card {
            transition: all 0.3s ease;
        }
        
        .stat-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
        }
        
        .stat-card.active {
            border: 2px solid var(--primary);
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.2);
        }
        
        .order-urgent {
            border-left-color: var(--danger);
            animation: pulse 1.5s infinite;
        }
        
        @keyframes pulse {
            0% { box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.4); }
            70% { box-shadow: 0 0 0 10px rgba(239, 68, 68, 0); }
            100% { box-shadow: 0 0 0 0 rgba(239, 68, 68, 0); }
        }
        
        .item-category {
            display: inline-flex;
            align-items: center;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 0.7rem;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        
        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
        }
        
        .badge-pending {
            background-color: #fef3c7;
            color: #92400e;
        }
        
        .badge-preparing {
            background-color: #fef3c7;
            color: #92400e;
        }
        
        .badge-cook {
            background-color: #fef3c7;
            color: #92400e;
        }
        
        .badge-serve {
            background-color: #d1fae5;
            color: #065f46;
        }
        
        .badge-voided {
            background-color: #fee2e2;
            color: #991b1b;
        }
        
        .badge-delivered {
            background-color: #dbeafe;
            color: #1d4ed8;
        }
        
        .modal-box {
            max-width: 90%;
            width: 800px;
        }
        
        .timer-display {
            font-family: 'Courier New', monospace;
            font-size: 1.5rem;
            font-weight: bold;
            padding: 0.5rem 1rem;
            background: #1e293b;
            color: #10b981;
            border-radius: 8px;
            display: inline-block;
            margin: 0.5rem 0;
        }
        
        .timer-running {
            animation: pulse 2s infinite;
        }
        
        .timer-finished {
            background: #ef4444;
            color: white;
            animation: pulse 1s infinite;
        }
        
        .timer-expired {
            background: #ef4444;
            color: white;
        }
        
        .btn-disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        
        .action-buttons {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            margin-top: 15px;
        }
        
        .action-btn {
            padding: 8px 16px;
            border-radius: 6px;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 6px;
            cursor: pointer;
            transition: all 0.2s;
            border: none;
            font-size: 14px;
        }
        
        .action-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        
        .btn-void {
            background-color: #ef4444;
            color: white;
        }
        
        .btn-start {
            background-color: #10b981;
            color: white;
        }
        
        .btn-cook {
            background-color: #f59e0b;
            color: white;
        }
        
        .btn-complete {
            background-color: #10b981;
            color: white;
        }
        
        .btn-serve {
            background-color: #3b82f6;
            color: white;
        }
        
        .btn-secondary {
            background-color: #6b7280;
            color: white;
        }
        
        .filtration-section {
            margin-top: 24px;
            margin-bottom: 24px;
        }
        
        .kot-timer {
            font-family: 'Courier New', monospace;
            font-weight: bold;
            padding: 4px 8px;
            border-radius: 4px;
            background: #1e293b;
            color: white;
        }
        
        .cooking-timer {
            background: #f59e0b;
            color: white;
        }
        
        .expired-timer {
            background: #ef4444;
            color: white;
            animation: blink 1s infinite;
        }
        
        @keyframes blink {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }
        
        .hotel-badge {
            position: absolute;
            top: 8px;
            right: 8px;
            background: #001f54;
            color: #F7B32B;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 0.7rem;
            font-weight: 600;
        }
        
        .toggle-container {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-left: auto;
        }
        
        .toggle-label {
            font-size: 14px;
            font-weight: 500;
            color: #4b5563;
        }
        
        .toggle-switch {
            position: relative;
            display: inline-block;
            width: 50px;
            height: 24px;
        }
        
        .toggle-switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }
        
        .toggle-slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #ccc;
            transition: .4s;
            border-radius: 34px;
        }
        
        .toggle-slider:before {
            position: absolute;
            content: "";
            height: 16px;
            width: 16px;
            left: 4px;
            bottom: 4px;
            background-color: white;
            transition: .4s;
            border-radius: 50%;
        }
        
        input:checked + .toggle-slider {
            background-color: #001f54;
        }
        
        input:checked + .toggle-slider:before {
            transform: translateX(26px);
        }
        
        .error-message {
            background-color: #fee2e2;
            border: 1px solid #fecaca;
            color: #991b1b;
            padding: 1rem;
            border-radius: 0.5rem;
            margin-bottom: 1rem;
        }
        
        .hotel-crud-section {
            margin-top: 20px;
            padding: 15px;
            background: #f8fafc;
            border-radius: 8px;
            border: 1px solid #e2e8f0;
        }
        
        .hotel-crud-title {
            font-size: 16px;
            font-weight: 600;
            color: #001f54;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .hotel-crud-buttons {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        
        .hotel-crud-btn {
            padding: 8px 16px;
            border-radius: 6px;
            font-size: 13px;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 6px;
            cursor: pointer;
            transition: all 0.2s;
            border: 1px solid #001f54;
            background: white;
            color: #001f54;
        }
        
        .hotel-crud-btn:hover {
            background: #001f54;
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 4px 6px rgba(0, 31, 84, 0.1);
        }
        
        .hotel-crud-btn.ready {
            background: #10b981;
            color: white;
            border-color: #10b981;
        }
        
        .hotel-crud-btn.complete {
            background: #3b82f6;
            color: white;
            border-color: #3b82f6;
        }
        
        .hotel-crud-btn.void {
            background: #ef4444;
            color: white;
            border-color: #ef4444;
        }
        
        .hotel-crud-btn.preparing {
            background: #f59e0b;
            color: white;
            border-color: #f59e0b;
        }
    </style>
</head>
<body class="bg-base-100 min-h-screen bg-white">
  <div class="flex h-screen">
    <!-- Sidebar -->
    <?php include '../sidebarr.php'; ?>

    <!-- Content Area -->
    <div class="flex flex-col flex-1 overflow-auto">
        <!-- Navbar -->
        <?php include '../navbar.php'; ?>

        <!-- Main Content -->
        <main class="flex-1 overflow-auto p-4 md:p-6">
            <!-- Header -->
            <div class="flex justify-between items-center mb-6">
                <h1 class="text-2xl font-bold text-gray-800">Kitchen Order Ticket (KOT)</h1>
                <div class="toggle-container">
                    <span class="toggle-label">Hotel Orders</span>
                    <label class="toggle-switch">
                        <input type="checkbox" id="hotelToggle" <?= $show_hotel_orders ? 'checked' : '' ?> onchange="toggleHotelOrders()">
                        <span class="toggle-slider"></span>
                    </label>
                </div>
            </div>
            
            <!-- Success Message -->
            <?php if($successMessage): ?>
                <div class="mb-6 p-4 rounded-lg bg-green-50 border border-green-200 text-green-700 flex items-center gap-2">
                    <i data-lucide="check-circle" class="w-5 h-5"></i>
                    <?= htmlspecialchars($successMessage) ?>
                </div>
            <?php endif; ?>
            
            <!-- Error Message for Hotel API -->
            <?php if(isset($hotel_error)): ?>
                <div class="mb-6 p-4 rounded-lg bg-red-50 border border-red-200 text-red-700 flex items-center gap-2">
                    <i data-lucide="alert-circle" class="w-5 h-5"></i>
                    <?= htmlspecialchars($hotel_error) ?>
                </div>
            <?php endif; ?>
            
            <!-- Stats Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-3 gap-4 mb-6">
                <?php
                if ($show_hotel_orders) {
                    $current_stats = $hotel_stats;
                } else {
                    $current_stats = $stats;
                }
                
                $status_cards = [
                    ['title' => 'Pending', 'count' => $current_stats['pending'] ?? 0, 'icon' => 'clock', 'filter' => 'pending', 'subtitle' => 'Not started'],
                    ['title' => 'Preparing', 'count' => $current_stats['preparing'] ?? 0, 'icon' => 'chef-hat', 'filter' => 'preparing', 'subtitle' => 'Being prepared'],
                    ['title' => 'Cooking', 'count' => $current_stats['cooking'] ?? 0, 'icon' => 'cooking-pot', 'filter' => 'cook', 'subtitle' => 'On the stove'],
                    ['title' => 'Ready to serve', 'count' => $current_stats['ready_to_serve'] ?? 0, 'icon' => 'tray-arrow-up', 'filter' => 'serve', 'subtitle' => 'Waiting for pickup'],
                    ['title' => 'Delivered', 'count' => $current_stats['delivered'] ?? 0, 'icon' => 'check-circle', 'filter' => 'delivered', 'subtitle' => 'Served to table'],
                    ['title' => 'Voided', 'count' => $current_stats['voided'] ?? 0, 'icon' => 'x-circle', 'filter' => 'voided', 'subtitle' => 'Cancelled orders'],
                ];
                
                foreach ($status_cards as $card) {
                    $isActive = $status_filter === $card['filter'];
                ?>
                <div class="stat-card bg-white shadow-xl p-5 rounded-lg cursor-pointer transition hover:shadow-2xl"
                     onclick="filterByStatus('<?= $card['filter'] ?>')">
                    <div class="flex justify-between items-start">
                        <div>
                            <p class="text-sm font-medium text-[#F7B32B]"><?= $card['title'] ?></p>
                            <h3 class="text-3xl font-bold mt-1"><?= $card['count'] ?></h3>
                            <p class="text-xs opacity-70 mt-1"><?= $card['subtitle'] ?></p>
                        </div>
                        <div class="p-3 rounded-lg bg-[#001f54] text-[#F7B32B]">
                            <i data-lucide="<?= $card['icon'] ?>" class="w-6 h-6"></i>
                        </div>
                    </div>
                </div>
                <?php } ?>
            </div>

            <!-- Filtration Section -->
            <div class="filtration-section bg-white p-4 rounded-lg shadow mb-6">
                <form method="GET" action="" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <?php if($show_hotel_orders): ?>
                        <input type="hidden" name="hotel" value="true">
                    <?php endif; ?>
                    <div>
                        <label class="block text-sm font-medium mb-1">Search</label>
                        <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" 
                               placeholder="Order ID, Item, Table, KOT ID..." 
                               class="bg-white w-full px-3 py-2 border rounded-lg">
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Status</label>
                        <select name="status" class="bg-white w-full px-3 py-2 border rounded-lg">
                            <option value="all" <?= $status_filter === 'all' || $status_filter === '' ? 'selected' : '' ?>>All Status</option>
                            <option value="pending" <?= strtolower($status_filter) === 'pending' ? 'selected' : '' ?>>Pending</option>
                            <option value="preparing" <?= strtolower($status_filter) === 'preparing' ? 'selected' : '' ?>>Preparing</option>
                            <option value="cook" <?= strtolower($status_filter) === 'cook' ? 'selected' : '' ?>>Cooking</option>
                            <option value="serve" <?= strtolower($status_filter) === 'serve' ? 'selected' : '' ?>>Ready to Serve</option>
                            <option value="delivered" <?= strtolower($status_filter) === 'delivered' ? 'selected' : '' ?>>Delivered</option>
                            <option value="voided" <?= strtolower($status_filter) === 'voided' ? 'selected' : '' ?>>Voided</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Date</label>
                        <input type="date" name="date" value="<?= htmlspecialchars($date_filter) ?>" 
                               class="bg-white w-full px-3 py-2 border rounded-lg">
                    </div>
                    <div class="flex items-end gap-2">
                        <button type="submit" class="px-4 py-2 bg-[#001f54] text-[#F7B32B] rounded-lg hover:opacity-90 flex items-center">
                            <i data-lucide="search" class="w-4 h-4 mr-2"></i> Filter
                        </button>
                        <a href="?<?= $show_hotel_orders ? 'hotel=true' : '' ?>" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 flex items-center">
                            <i data-lucide="x" class="w-4 h-4 mr-2"></i> Clear
                        </a>
                    </div>
                </form>
            </div>

            <!-- Recent Orders Section -->
            <div class="mb-6">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-lg font-semibold text-[#001f54]">
                        <?= $show_hotel_orders ? 'Hotel Orders' : 'Restaurant Orders' ?> (<?= $total_filtered ?> total)
                    </h2>
                    <div class="text-sm text-gray-500">
                        Page <?= $page ?> of <?= max(1, $total_pages) ?>
                    </div>
                </div>
                
                <?php if ($show_hotel_orders && !empty($hotel_orders) && empty($paginated_orders) && ($search || $status_filter || $date_filter)): ?>
                    <div class="text-center py-12 bg-white rounded-lg shadow">
                        <i data-lucide="search" class="w-16 h-16 text-gray-300 mx-auto mb-4"></i>
                        <h3 class="text-lg font-medium text-gray-500 mb-2">No orders match your filters</h3>
                        <p class="text-gray-400">Try adjusting your search criteria</p>
                    </div>
                <?php elseif ($show_hotel_orders && empty($hotel_orders)): ?>
                    <div class="text-center py-12 bg-white rounded-lg shadow">
                        <i data-lucide="utensils-crossed" class="w-16 h-16 text-gray-300 mx-auto mb-4"></i>
                        <h3 class="text-lg font-medium text-gray-500 mb-2">No hotel orders available</h3>
                        <p class="text-gray-400">There are currently no orders from the hotel system</p>
                    </div>
                <?php else: ?>
                    <div class="grid grid-cols-1 lg:grid-cols-2 xl:grid-cols-4 gap-5" id="recent-orders">
                        <?php
                        if ($show_hotel_orders) {
                            // Display hotel orders
                            if (!empty($paginated_orders)) {
                                foreach ($paginated_orders as $order) {
                                    $status = strtolower($order['status']);
                                    $status_class = '';
                                    $status_icon = '';
                                    $status_label = 'Pending';
                                    $kot_id = $order['kot_id'] ?? 'N/A';
                                    $table_id = $order['table_number'] ?? 'N/A';
                                    $order_id = $order['order_id'] ?? 'N/A';
                                    $order_time = isset($order['created_at']) ? date('h:i A', strtotime($order['created_at'])) : 'N/A';

                                    switch ($status) {
                                        case 'pending':
                                        case 'ordered':
                                            $status_class = 'badge-pending';
                                            $status_icon = 'clock';
                                            $status_label = 'Pending';
                                            break;
                                        case 'preparing':
                                        case 'preparation':
                                            $status_class = 'badge-preparing';
                                            $status_icon = 'chef-hat';
                                            $status_label = 'Preparing';
                                            break;
                                        case 'cooking':
                                        case 'cook':
                                            $status_class = 'badge-cook';
                                            $status_icon = 'cooking-pot';
                                            $status_label = 'Cooking';
                                            break;
                                        case 'cooked':
                                        case 'ready_to_serve':
                                        case 'ready':
                                            $status_class = 'badge-serve';
                                            $status_icon = 'check-circle';
                                            $status_label = 'Ready to Serve';
                                            break;
                                        case 'delivered':
                                        case 'served':
                                            $status_class = 'badge-delivered';
                                            $status_icon = 'check-circle';
                                            $status_label = 'Delivered';
                                            break;
                                        case 'voided':
                                        case 'cancelled':
                                            $status_class = 'badge-voided';
                                            $status_icon = 'x-circle';
                                            $status_label = 'Voided';
                                            break;
                                        default:
                                            $status_class = 'badge-pending';
                                            $status_icon = 'clock';
                                            $status_label = 'Pending';
                                    }

                                    $isCooked = ($status === 'cooked' || $status === 'cook' || $status === 'ready_to_serve' || $status === 'ready' || $status === 'delivered' || $status === 'served');
                        ?>
                        <!-- Order Card - Hotel -->
                        <div class="order-card bg-white shadow-lg rounded-lg p-5 transition hover:shadow-xl relative" 
                             data-kot-id="<?= htmlspecialchars($kot_id) ?>" 
                             data-status="<?= htmlspecialchars($status) ?>"
                             data-source="hotel">
                            <div class="hotel-badge">Hotel</div>
                            <div class="flex justify-between items-start mb-3">
                                <div>
                                    <h3 class="text-lg font-bold text-gray-900">Order #<?= htmlspecialchars($order_id) ?></h3>
                                    <div class="flex items-center gap-2 mt-1">
                                        <span class="text-sm font-medium text-gray-500 flex items-center">
                                            <i data-lucide="table" class="w-3 h-3 mr-1"></i>
                                            Table <?= htmlspecialchars($table_id) ?>
                                        </span>
                                        <span class="status-badge <?= $status_class ?>">
                                            <i data-lucide="<?= $status_icon ?>" class="w-3 h-3 mr-1"></i>
                                            <?= $status_label ?>
                                        </span>
                                    </div>
                                </div>
                                <span class="text-sm font-medium text-gray-500 flex items-center">
                                    <i data-lucide="clock" class="w-3 h-3 mr-1"></i>
                                    <?= $order_time ?>
                                </span>
                            </div>
                            
                            <!-- Order Details -->
                            <div class="mb-4">
                                <div class="flex items-center gap-2 mb-2">
                                    <i data-lucide="utensils" class="w-4 h-4 text-gray-500"></i>
                                    <span class="font-medium text-gray-700"><?= htmlspecialchars($order['item_name'] ?? 'N/A') ?></span>
                                </div>
                                <div class="flex justify-between items-center">
                                    <div class="flex items-center gap-4">
                                        <span class="text-sm text-gray-600 flex items-center">
                                            <i data-lucide="hash" class="w-3 h-3 mr-1"></i>
                                            Qty: <?= htmlspecialchars($order['quantity'] ?? '1') ?>
                                        </span>
                                        <span class="text-sm text-gray-600 flex items-center">
                                            <i data-lucide="tag" class="w-3 h-3 mr-1"></i>
                                            KOT #<?= htmlspecialchars($kot_id) ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Action Button -->
                            <div class="flex justify-end">
                                <?php if (!$isCooked && $status !== 'voided' && $status !== 'cancelled'): ?>
                                    <form method="POST" onsubmit="return confirmMarkAsCooked('<?= htmlspecialchars($order_id) ?>')">
                                        <input type="hidden" name="order_id" value="<?= htmlspecialchars($order_id) ?>">
                                        <button type="submit" class="px-4 py-2 rounded-md bg-[#001f54] text-[#F7B32B] text-sm font-semibold hover:opacity-90 transition flex items-center">
                                            <i data-lucide="cooking-pot" class="w-4 h-4 mr-2"></i>
                                            Mark as Cooked
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <button class="px-4 py-2 rounded-md bg-[#001f54] text-[#F7B32B] text-sm font-semibold hover:opacity-90 transition flex items-center"
                                            onclick="showOrderDetails('<?= htmlspecialchars($kot_id) ?>', 'hotel', '<?= htmlspecialchars($order_id) ?>')">
                                        <i data-lucide="eye" class="w-4 h-4 mr-2"></i>
                                        View Details
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php
                                }
                            }
                        } else {
                            // Display local database orders
                            if (isset($orders_result) && $orders_result->num_rows > 0) {
                                while ($order = $orders_result->fetch_assoc()) {
                                    // Get prep_time from menu database if available
                                    $prep_time = 15; // Default 15 minutes
                                    if ($menu_conn) {
                                        $menu_query = "SELECT prep_time FROM menu WHERE name = ? LIMIT 1";
                                        $menu_stmt = $menu_conn->prepare($menu_query);
                                        $menu_stmt->bind_param("s", $order['item_name']);
                                        $menu_stmt->execute();
                                        $menu_result = $menu_stmt->get_result();
                                        if ($menu_row = $menu_result->fetch_assoc()) {
                                            $prep_time = $menu_row['prep_time'] ?? 15;
                                        }
                                    }
                                    
                                    // Get table_id from POS database
                                    $table_id = $order['table_number'];
                                    if ($pos_conn && !empty($order['order_id'])) {
                                        $pos_query = "SELECT table_id FROM orders WHERE order_id = ? LIMIT 1";
                                        $pos_stmt = $pos_conn->prepare($pos_query);
                                        $pos_stmt->bind_param("s", $order['order_id']);
                                        $pos_stmt->execute();
                                        $pos_result = $pos_stmt->get_result();
                                        if ($pos_row = $pos_result->fetch_assoc()) {
                                            $table_id = $pos_row['table_id'] ?? $order['table_number'];
                                        }
                                    }
                                    
                                    // Convert status to lowercase for consistency
                                    $status = strtolower($order['status']);
                                    $status_class = '';
                                    $status_icon = '';
                                    $status_label = ucfirst($status);

                                    switch ($status) {
                                        case 'pending':
                                            $status_class = 'badge-pending';
                                            $status_icon = 'clock';
                                            break;
                                        case 'preparing':
                                            $status_class = 'badge-preparing';
                                            $status_icon = 'chef-hat';
                                            break;
                                        case 'cook':
                                            $status_class = 'badge-cook';
                                            $status_icon = 'cooking-pot';
                                            break;
                                        case 'serve':
                                            $status_class = 'badge-serve';
                                            $status_icon = 'check-circle';
                                            break;
                                        case 'voided':
                                            $status_class = 'badge-voided';
                                            $status_icon = 'x-circle';
                                            break;
                                        case 'delivered':
                                            $status_class = 'badge-delivered';
                                            $status_icon = 'check-circle';
                                            break;
                                    }

                                    $order_time = date('h:i A', strtotime($order['created_at']));
                                    $kot_id = $order['kot_id'];
                                    
                                    // Calculate remaining cooking time if cooking
                                    $remaining_time = '';
                                    $timer_class = '';
                                    if ($status === 'cook') {
                                        $start_time = strtotime($order['created_at']);
                                        $current_time = time();
                                        $elapsed = $current_time - $start_time;
                                        $remaining_seconds = max(0, ($prep_time * 60) - $elapsed);
                                        
                                        if ($remaining_seconds > 0) {
                                            $minutes = floor($remaining_seconds / 60);
                                            $seconds = $remaining_seconds % 60;
                                            $remaining_time = sprintf("%02d:%02d", $minutes, $seconds);
                                            $timer_class = 'cooking-timer';
                                        } else {
                                            $remaining_time = "00:00";
                                            $timer_class = 'expired-timer';
                                        }
                                    }
                        ?>
                        <!-- Order Card - Local -->
                        <div class="order-card bg-white shadow-lg rounded-lg p-5 transition hover:shadow-xl" 
                             data-kot-id="<?= $kot_id ?>" 
                             data-status="<?= $status ?>"
                             data-source="local">
                            <div class="flex justify-between items-start mb-3">
                                <div>
                                    <h3 class="text-lg font-bold text-gray-900">Order #<?= $order['order_id'] ?></h3>
                                    <div class="flex items-center gap-2 mt-1">
                                        <span class="text-sm font-medium text-gray-500 flex items-center">
                                            <i data-lucide="table" class="w-3 h-3 mr-1"></i>
                                            Table <?= htmlspecialchars($table_id) ?>
                                        </span>
                                        <span class="status-badge <?= $status_class ?>">
                                            <i data-lucide="<?= $status_icon ?>" class="w-3 h-3 mr-1"></i>
                                            <?= $status_label ?>
                                        </span>
                                    </div>
                                </div>
                                <span class="text-sm font-medium text-gray-500 flex items-center">
                                    <i data-lucide="clock" class="w-3 h-3 mr-1"></i>
                                    <?= $order_time ?>
                                </span>
                            </div>
                            
                            <!-- Order Details -->
                            <div class="mb-4">
                                <div class="flex items-center gap-2 mb-2">
                                    <i data-lucide="utensils" class="w-4 h-4 text-gray-500"></i>
                                    <span class="font-medium text-gray-700"><?= htmlspecialchars($order['item_name']) ?></span>
                                </div>
                                <div class="flex justify-between items-center">
                                    <div class="flex items-center gap-4">
                                        <span class="text-sm text-gray-600 flex items-center">
                                            <i data-lucide="hash" class="w-3 h-3 mr-1"></i>
                                            Qty: <?= htmlspecialchars($order['quantity']) ?>
                                        </span>
                                        <span class="text-sm text-gray-600 flex items-center">
                                            <i data-lucide="tag" class="w-3 h-3 mr-1"></i>
                                            KOT #<?= htmlspecialchars($kot_id) ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Action Button -->
                            <div class="flex justify-end">
                                <button class="px-4 py-2 rounded-md bg-[#001f54] text-[#F7B32B] text-sm font-semibold hover:opacity-90 transition flex items-center"
                                        onclick="showOrderDetails(<?= $kot_id ?>, 'local', '<?= $order['order_id'] ?>')">
                                    <i data-lucide="eye" class="w-4 h-4 mr-2"></i>
                                    View Details
                                </button>
                            </div>
                        </div>
                        <?php
                                }
                            } else {
                                echo '<div class="col-span-full text-center py-12 text-gray-400">
                                    <i data-lucide="utensils-crossed" class="w-12 h-12 mx-auto mb-4"></i>
                                    <p class="text-lg font-medium">No orders found</p>';
                                if (!empty($search) || !empty($status_filter) || !empty($date_filter)) {
                                    echo '<p class="text-sm">Try clearing your filters</p>';
                                }
                                echo '</div>';
                            }
                        }
                        ?>
                    </div>
                    
                    <!-- Pagination -->
                    <?php if ($total_pages > 1): ?>
                    <div class="flex justify-center items-center gap-2 mt-6">
                        <?php if ($page > 1): ?>
                            <a href="?page=<?= $page-1 ?>&search=<?= urlencode($search) ?>&status=<?= urlencode($status_filter) ?>&date=<?= urlencode($date_filter) ?><?= $show_hotel_orders ? '&hotel=true' : '' ?>" 
                               class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 flex items-center">
                                <i data-lucide="chevron-left" class="w-4 h-4 mr-1"></i> Previous
                            </a>
                        <?php endif; ?>
                        
                        <div class="flex gap-1">
                            <?php
                            $start_page = max(1, $page - 2);
                            $end_page = min($total_pages, $start_page + 4);
                            
                            for ($p = $start_page; $p <= $end_page; $p++):
                            ?>
                                <a href="?page=<?= $p ?>&search=<?= urlencode($search) ?>&status=<?= urlencode($status_filter) ?>&date=<?= urlencode($date_filter) ?><?= $show_hotel_orders ? '&hotel=true' : '' ?>" 
                                   class="px-3 py-1 rounded-lg <?= $p == $page ? 'bg-[#001f54] text-[#F7B32B]' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' ?>">
                                    <?= $p ?>
                                </a>
                            <?php endfor; ?>
                        </div>
                        
                        <?php if ($page < $total_pages): ?>
                            <a href="?page=<?= $page+1 ?>&search=<?= urlencode($search) ?>&status=<?= urlencode($status_filter) ?>&date=<?= urlencode($date_filter) ?><?= $show_hotel_orders ? '&hotel=true' : '' ?>" 
                               class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 flex items-center">
                                Next <i data-lucide="chevron-right" class="w-4 h-4 ml-1"></i>
                            </a>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
            
            <!-- API Data Section (Collapsible - Only for Hotel Orders) -->
            <?php if ($show_hotel_orders && !empty($hotel_api_data)): ?>
            <div class="bg-white rounded-lg shadow mb-6">
                <button id="toggleApiData" class="w-full px-4 py-3 flex justify-between items-center hover:bg-gray-50 rounded-lg">
                    <div class="flex items-center gap-2">
                        <i data-lucide="database" class="w-5 h-5"></i>
                        <span class="font-medium">Hotel API Response Data</span>
                    </div>
                    <i data-lucide="chevron-down" id="apiDataIcon" class="w-5 h-5 transition-transform"></i>
                </button>
                <div id="apiDataContent" class="hidden px-4 pb-4">
                    <textarea class="w-full h-64 p-3 border border-gray-300 rounded-lg font-mono text-sm" readonly><?= htmlspecialchars(json_encode($hotel_api_data, JSON_PRETTY_PRINT)) ?></textarea>
                </div>
            </div>
            <?php endif; ?>
        </main>
    </div>
  </div>

  <!-- Order Details Modal -->
  <dialog id="order_details_modal" class="modal">
    <div class="modal-box bg-white rounded-lg shadow-xl p-5 max-w-3xl">
      <div class="flex justify-between items-center mb-4">
        <h3 class="font-bold text-lg text-[#001f54] flex items-center">
          <i data-lucide="receipt" class="w-5 h-5 mr-2"></i>
          Order Details
        </h3>
        <button onclick="order_details_modal.close()" 
                class="p-2 rounded-full bg-[#F7B32B] text-[#001f54] hover:opacity-90 transition">
          <i data-lucide="x" class="w-5 h-5"></i>
        </button>
      </div>
      <div class="overflow-y-auto max-h-[60vh] space-y-3" id="order-details-content">
        <!-- Dynamic content loaded here -->
      </div>
    </div>
  </dialog>

  <!-- SweetAlert2 JS -->
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <script>
    // Initialize Lucide icons
    lucide.createIcons();
    
    // Global variables
    let timers = {};
    
    // Initialize modals
    const order_details_modal = document.getElementById('order_details_modal');
    
    // Configure SweetAlert2 to appear on top
    const Swal = window.Swal;
    
    // Toggle hotel orders
    function toggleHotelOrders() {
        const isChecked = document.getElementById('hotelToggle').checked;
        const url = new URL(window.location.href);
        
        if (isChecked) {
            url.searchParams.set('hotel', 'true');
        } else {
            url.searchParams.delete('hotel');
        }
        
        // Reset to page 1 when toggling
        url.searchParams.delete('page');
        window.location.href = url.toString();
    }
    
    // Filter by status function
    function filterByStatus(status) {
        const url = new URL(window.location.href);
        if (status) {
            url.searchParams.set('status', status);
        } else {
            url.searchParams.delete('status');
        }
        url.searchParams.delete('page'); // Reset to page 1 when filtering
        window.location.href = url.toString();
    }
    
    // Toggle API data section
    document.getElementById('toggleApiData')?.addEventListener('click', function() {
        const content = document.getElementById('apiDataContent');
        const icon = document.getElementById('apiDataIcon');
        content.classList.toggle('hidden');
        icon.style.transform = content.classList.contains('hidden') ? 'rotate(0deg)' : 'rotate(180deg)';
    });
    
    // Show order details modal
    async function showOrderDetails(kotId, source = 'local', orderId = '') {
        try {
            document.getElementById('order-details-content').innerHTML = `
                <div class="flex justify-center items-center py-12">
                    <div class="animate-spin rounded-full h-10 w-10 border-t-2 border-b-2 border-[#001f54]"></div>
                </div>
            `;
            
            // Close any open SweetAlert before opening modal
            await Swal.close();
            
            order_details_modal.showModal();
            
            if (source === 'hotel') {
                // For hotel orders, we need to fetch from the current hotel orders data
                const orderCard = document.querySelector(`[data-kot-id="${kotId}"][data-source="hotel"]`);
                if (!orderCard) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Order details not found'
                    });
                    return;
                }
                
                // Extract data from the card or fetch from API
                const orderTime = orderCard.querySelector('[data-lucide="clock"]')?.parentElement?.textContent?.trim() || 'N/A';
                const tableNumber = orderCard.querySelector('[data-lucide="table"]')?.parentElement?.textContent?.replace('Table', '').trim() || 'N/A';
                const itemName = orderCard.querySelector('[data-lucide="utensils"]')?.nextElementSibling?.textContent || 'N/A';
                const quantity = orderCard.querySelector('[data-lucide="hash"]')?.parentElement?.textContent?.replace('Qty:', '').trim() || '1';
                const statusBadge = orderCard.querySelector('.status-badge');
                const statusText = statusBadge?.textContent?.trim() || 'Pending';
                const statusClass = statusBadge?.className || '';
                const kotIdValue = kotId || 'N/A';
                const orderIdValue = orderId || kotId;
                
                // Determine available actions based on status
                const status = statusText.toLowerCase();
                let availableActions = [];
                
                if (status.includes('pending') || status.includes('ordered')) {
                    availableActions = ['preparing', 'ready', 'complete', 'void'];
                } else if (status.includes('preparing')) {
                    availableActions = ['ready', 'complete', 'void'];
                } else if (status.includes('cooking') || status.includes('cook')) {
                    availableActions = ['ready', 'complete', 'void'];
                } else if (status.includes('ready')) {
                    availableActions = ['complete'];
                }
                
                const html = `
                    <div class="space-y-4">
                        <!-- Order Header -->
                        <div class="flex justify-between items-start p-4 bg-gray-50 rounded-lg">
                            <div>
                                <h4 class="font-bold text-xl text-[#001f54]">Order #${orderIdValue}</h4>
                                <div class="flex items-center gap-4 mt-2">
                                    <span class="flex items-center text-gray-600">
                                        <i data-lucide="table" class="w-4 h-4 mr-2"></i>
                                        Table ${tableNumber}
                                    </span>
                                    <span class="flex items-center text-gray-600">
                                        <i data-lucide="calendar" class="w-4 h-4 mr-2"></i>
                                        Hotel Order
                                    </span>
                                    <span class="status-badge ${statusClass}">
                                        <i data-lucide="${status.includes('pending') ? 'clock' : status.includes('preparing') ? 'chef-hat' : status.includes('cooking') ? 'cooking-pot' : status.includes('ready') ? 'check-circle' : status.includes('void') ? 'x-circle' : 'check-circle'}" class="w-4 h-4 mr-1"></i>
                                        ${statusText}
                                    </span>
                                </div>
                            </div>
                            <div class="text-right">
                                <div class="text-gray-600">${orderTime}</div>
                                <div class="text-sm text-gray-500">Hotel System</div>
                            </div>
                        </div>
                        
                        <!-- Order Items -->
                        <div class="p-4 border rounded-lg">
                            <h5 class="font-semibold text-lg mb-3 flex items-center">
                                <i data-lucide="utensils" class="w-4 h-4 mr-2"></i>
                                Items
                            </h5>
                            <div class="bg-gray-50 p-4 rounded-lg">
                                <div class="flex justify-between items-center">
                                    <div>
                                        <div class="flex items-center gap-2 mb-1">
                                            <span class="font-bold text-lg">${quantity}x ${itemName}</span>
                                        </div>
                                        <div class="text-sm text-gray-600">
                                            <span class="font-medium">Status:</span> ${statusText}
                                        </div>
                                        <div class="text-sm text-gray-600 mt-1">
                                            <span class="font-medium">KOT ID:</span> ${kotIdValue}
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <div class="font-bold text-lg">N/A</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- CRUD SECTION FOR HOTEL ORDERS -->
                        <div class="hotel-crud-section">
                            <div class="hotel-crud-title">
                                <i data-lucide="settings" class="w-5 h-5"></i>
                                Hotel Order Management
                            </div>
                            <div class="hotel-crud-buttons" id="hotel-crud-buttons">
                                ${availableActions.includes('preparing') ? `
                                <button onclick="updateHotelOrderStatus('${kotIdValue}', 'preparing')" 
                                        class="hotel-crud-btn preparing">
                                    <i data-lucide="chef-hat" class="w-4 h-4"></i>
                                    Mark as Preparing
                                </button>
                                ` : ''}
                                
                                ${availableActions.includes('ready') ? `
                                <button onclick="updateHotelOrderStatus('${kotIdValue}', 'ready')" 
                                        class="hotel-crud-btn ready">
                                    <i data-lucide="check-circle" class="w-4 h-4"></i>
                                    Mark as Ready
                                </button>
                                ` : ''}
                                
                                ${availableActions.includes('complete') ? `
                                <button onclick="updateHotelOrderStatus('${kotIdValue}', 'complete')" 
                                        class="hotel-crud-btn complete">
                                    <i data-lucide="check" class="w-4 h-4"></i>
                                    Mark as Complete
                                </button>
                                ` : ''}
                                
                                ${availableActions.includes('void') ? `
                                <button onclick="updateHotelOrderStatus('${kotIdValue}', 'void')" 
                                        class="hotel-crud-btn void">
                                    <i data-lucide="x-circle" class="w-4 h-4"></i>
                                    Void Order
                                </button>
                                ` : ''}
                            </div>
                        </div>
                        
                        <!-- Note about hotel orders -->
                        <div class="p-4 border rounded-lg bg-blue-50">
                            <div class="flex items-center gap-2 mb-2">
                                <i data-lucide="info" class="w-5 h-5 text-blue-600"></i>
                                <span class="font-medium text-blue-800">Hotel Order Information</span>
                            </div>
                            <p class="text-blue-700 text-sm">
                                This order originates from the hotel system. Changes made here will update the hotel's database directly through their API.
                            </p>
                        </div>
                    </div>
                `;
                
                document.getElementById('order-details-content').innerHTML = html;
                lucide.createIcons();
                
            } else {
                // For local orders, fetch from your existing system
                const response = await fetch('sub-modules/fetch_order_details.php?kot_id=' + kotId);
                const data = await response.json();

                if (!data.success) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: data.msg || 'Failed to load order details'
                    });
                    return;
                }

                const order = data.order;
                
                // Convert status to lowercase for consistency
                const status = order.status.toLowerCase();
                
                // Get prep_time from database or default
                let prepTime = 15;
                try {
                    const prepTimeResponse = await fetch(`sub-modules/get_prep_time.php?item_name=${encodeURIComponent(order.item_name)}`);
                    const prepData = await prepTimeResponse.json();
                    if (prepData.prep_time) {
                        prepTime = parseInt(prepData.prep_time);
                    }
                } catch (e) {
                    console.log('Could not fetch prep time:', e);
                }
                
                // Get table_id from POS
                let tableId = order.table_number;
                try {
                    const tableResponse = await fetch(`sub-modules/get_table_id.php?order_id=${order.order_id}`);
                    const tableData = await tableResponse.json();
                    if (tableData.table_id) {
                        tableId = tableData.table_id;
                    }
                } catch (e) {
                    console.log('Could not fetch table ID:', e);
                }
                
                // Determine status styling
                let statusClass = '';
                let statusIcon = '';
                let statusLabel = order.status.charAt(0).toUpperCase() + order.status.slice(1);
                
                switch(status) {
                    case 'pending':
                        statusClass = 'badge-pending';
                        statusIcon = 'clock';
                        statusLabel = 'Pending';
                        break;
                    case 'preparing':
                        statusClass = 'badge-preparing';
                        statusIcon = 'chef-hat';
                        statusLabel = 'Preparing';
                        break;
                    case 'cook':
                        statusClass = 'badge-cook';
                        statusIcon = 'cooking-pot';
                        statusLabel = 'Cooking';
                        break;
                    case 'serve':
                        statusClass = 'badge-serve';
                        statusIcon = 'check-circle';
                        statusLabel = 'Ready to Serve';
                        break;
                    case 'voided':
                        statusClass = 'badge-voided';
                        statusIcon = 'x-circle';
                        statusLabel = 'Voided';
                        break;
                    case 'delivered':
                        statusClass = 'badge-delivered';
                        statusIcon = 'check-circle';
                        statusLabel = 'Delivered';
                        break;
                }
                
                const orderTime = new Date(order.created_at).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
                const orderDate = new Date(order.created_at).toLocaleDateString();
                
                // Build the order details HTML
                const html = `
                    <div class="space-y-4">
                        <!-- Order Header -->
                        <div class="flex justify-between items-start p-4 bg-gray-50 rounded-lg">
                            <div>
                                <h4 class="font-bold text-xl text-[#001f54]">Order #${order.order_id}</h4>
                                <div class="flex items-center gap-4 mt-2">
                                    <span class="flex items-center text-gray-600">
                                        <i data-lucide="table" class="w-4 h-4 mr-2"></i>
                                        Table ${tableId}
                                    </span>
                                    <span class="flex items-center text-gray-600">
                                        <i data-lucide="calendar" class="w-4 h-4 mr-2"></i>
                                        ${orderDate}
                                    </span>
                                    <span class="status-badge ${statusClass}">
                                        <i data-lucide="${statusIcon}" class="w-4 h-4 mr-1"></i>
                                        ${statusLabel}
                                    </span>
                                </div>
                            </div>
                            <div class="text-right">
                                <div class="text-gray-600">${orderTime}</div>
                                <div class="text-sm text-gray-500">Restaurant System</div>
                            </div>
                        </div>
                        
                        <!-- Order Items -->
                        <div class="p-4 border rounded-lg">
                            <h5 class="font-semibold text-lg mb-3 flex items-center">
                                <i data-lucide="utensils" class="w-4 h-4 mr-2"></i>
                                Items
                            </h5>
                            <div class="bg-gray-50 p-4 rounded-lg">
                                <div class="flex justify-between items-center">
                                    <div>
                                        <div class="flex items-center gap-2 mb-1">
                                            <span class="font-bold text-lg">${order.quantity}x ${order.item_name}</span>
                                        </div>
                                        <div class="text-sm text-gray-600">
                                            <span class="font-medium">Prep Time:</span> ${prepTime} minutes
                                        </div>
                                        <div class="text-sm text-gray-600 mt-1">
                                            <span class="font-medium">KOT ID:</span> ${order.kot_id}
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <div class="font-bold text-lg">${order.price ? '$' + order.price : 'N/A'}</div>
                                    </div>
                                </div>
                                
                                ${order.special_instructions ? `
                                <div class="mt-3 p-3 bg-blue-50 rounded-lg border border-blue-200">
                                    <div class="flex items-center gap-2 mb-1">
                                        <i data-lucide="message-square" class="w-4 h-4 text-blue-600"></i>
                                        <span class="font-medium text-blue-800">Special Instructions:</span>
                                    </div>
                                    <p class="text-blue-700">${order.special_instructions}</p>
                                </div>
                                ` : ''}
                            </div>
                        </div>
                        
                        ${order.notes ? `
                        <div class="p-4 border rounded-lg">
                            <h5 class="font-semibold text-lg mb-2 flex items-center">
                                <i data-lucide="clipboard" class="w-4 h-4 mr-2"></i>
                                Order Notes
                            </h5>
                            <div class="bg-yellow-50 p-3 rounded-lg border border-yellow-200">
                                <p class="text-yellow-800">${order.notes}</p>
                            </div>
                        </div>
                        ` : ''}
                        
                        <!-- CRUD ACTION BUTTONS -->
                        <div class="p-4 border rounded-lg bg-gray-50">
                            <h5 class="font-semibold text-lg mb-3 flex items-center">
                                <i data-lucide="settings" class="w-4 h-4 mr-2"></i>
                                Actions
                            </h5>
                            
                            <div class="action-buttons" id="action-buttons-${kotId}">
                                ${status === 'pending' ? `
                                <button onclick="startPreparing(${kotId})" 
                                        class="action-btn btn-start">
                                    <i data-lucide="play" class="w-4 h-4"></i>
                                    START PREPARING
                                </button>
                                ` : ''}
                                
                                ${status === 'preparing' ? `
                                <button onclick="startCooking(${kotId}, ${prepTime})" 
                                        class="action-btn btn-cook">
                                    <i data-lucide="cooking-pot" class="w-4 h-4"></i>
                                    START COOKING
                                </button>
                                ` : ''}
                                
                                ${status === 'cook' ? `
                                <button onclick="markAsComplete(${kotId})" 
                                        class="action-btn btn-complete" id="complete-btn-${kotId}">
                                    <i data-lucide="check" class="w-4 h-4"></i>
                                    MARK AS COMPLETE
                                </button>
                                ` : ''}
                                
                                ${status === 'serve' ? `
                                <button onclick="markAsServed(${kotId})" 
                                        class="action-btn btn-serve">
                                    <i data-lucide="check-circle" class="w-4 h-4"></i>
                                    MARK AS SERVED
                                </button>
                                ` : ''}
                                
                                ${status === 'pending' ? `
                                <button onclick="voidOrder(${kotId})" 
                                        class="action-btn btn-void">
                                    <i data-lucide="x-circle" class="w-4 h-4"></i>
                                    VOID ORDER
                                </button>
                                ` : ''}
                                
                                <button onclick="printKOT(${kotId})" 
                                        class="action-btn btn-secondary">
                                    <i data-lucide="printer" class="w-4 h-4"></i>
                                    PRINT KOT
                                </button>
                            </div>
                        </div>
                    </div>
                `;
                
                document.getElementById('order-details-content').innerHTML = html;
                lucide.createIcons();
                
                // Start timer if cooking
                if (status === 'cook') {
                    startCookingTimerModal(kotId, new Date(order.created_at).getTime(), prepTime);
                }
            }
            
        } catch (error) {
            console.error('Error:', error);
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Failed to load order details'
            });
        }
    }
    
    // Confirm mark as cooked (for hotel orders)
    function confirmMarkAsCooked(orderId) {
        Swal.fire({
            title: 'Mark as Cooked?',
            text: `Are you sure you want to mark Order #${orderId} as cooked? This will update the hotel system.`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#10b981',
            cancelButtonColor: '#6b7280',
            confirmButtonText: 'Yes, mark as cooked!',
            cancelButtonText: 'Cancel',
            allowOutsideClick: false
        }).then((result) => {
            if (result.isConfirmed) {
                // The form will submit normally
                return true;
            } else {
                return false;
            }
        });
        return false; // Prevent immediate form submission
    }
    
    // Update hotel order status
    async function updateHotelOrderStatus(kotId, action) {
        try {
            const hotelApiToken = "uX8B1QqYJt7XqTf0sM3tKAh5nCjEjR1Xlqk4F8ZdD1mHq5V9y7oUj1QhUzPg5s";
            let apiUrl = '';
            let method = 'PUT';
            let confirmMessage = '';
            let successMessage = '';
            
            switch(action) {
                case 'preparing':
                    apiUrl = `https://hotel.soliera-hotel-restaurant.com/api/preparingKOT/${kotId}`;
                    confirmMessage = `Mark KOT #${kotId} as Preparing?`;
                    successMessage = 'Order marked as preparing!';
                    break;
                case 'ready':
                    apiUrl = `https://hotel.soliera-hotel-restaurant.com/api/readyKOT/${kotId}`;
                    confirmMessage = `Mark KOT #${kotId} as Ready to Serve?`;
                    successMessage = 'Order marked as ready!';
                    break;
                case 'complete':
                    apiUrl = `https://hotel.soliera-hotel-restaurant.com/api/completedKOT/${kotId}`;
                    confirmMessage = `Mark KOT #${kotId} as Completed?`;
                    successMessage = 'Order marked as completed!';
                    break;
                case 'void':
                    apiUrl = `https://hotel.soliera-hotel-restaurant.com/api/voidKOT/${kotId}`;
                    confirmMessage = `Void KOT #${kotId}? This action cannot be undone.`;
                    successMessage = 'Order voided successfully!';
                    break;
                default:
                    throw new Error('Invalid action');
            }
            
            const result = await Swal.fire({
                title: 'Confirm Action',
                text: confirmMessage,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#001f54',
                cancelButtonColor: '#6b7280',
                confirmButtonText: 'Yes, proceed!',
                cancelButtonText: 'Cancel',
                allowOutsideClick: false
            });
            
            if (!result.isConfirmed) return;
            
            // Show loading state
            Swal.fire({
                title: 'Updating...',
                text: 'Please wait while we update the order status',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });
            
            const response = await fetch(apiUrl, {
                method: method,
                headers: {
                    'Authorization': `Bearer ${hotelApiToken}`,
                    'Accept': 'application/json',
                    'Content-Type': 'application/json'
                }
            });
            
            const data = await response.json();
            
            if (response.ok) {
                Swal.fire({
                    icon: 'success',
                    title: 'Success!',
                    text: successMessage,
                    confirmButtonColor: '#001f54'
                }).then(() => {
                    // Close modal and refresh page
                    order_details_modal.close();
                    setTimeout(() => {
                        location.reload();
                    }, 500);
                });
            } else {
                throw new Error(data.message || 'Failed to update order status');
            }
            
        } catch (error) {
            console.error('Error updating hotel order:', error);
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: error.message || 'Failed to update order status. Please try again.',
                confirmButtonColor: '#ef4444'
            });
        }
    }
    
    // Close modal when clicking outside
    window.addEventListener('click', (event) => {
        if (event.target === order_details_modal) {
            order_details_modal.close();
            // Clear all timers when modal closes
            Object.values(timers).forEach(timer => {
                if (timer) clearInterval(timer);
            });
            timers = {};
        }
    });
    
    // Refresh page every 30 seconds
    setInterval(() => {
        const isModalOpen = order_details_modal.open;
        if (!isModalOpen) {
            location.reload();
        }
    }, 30000);
    
    // Existing functions for local orders
    function startPreparing(kotId) {
        // Your existing local order functions
    }
    
    function startCooking(kotId, prepTime) {
        // Your existing local order functions
    }
    
    function markAsComplete(kotId) {
        // Your existing local order functions
    }
    
    function markAsServed(kotId) {
        // Your existing local order functions
    }
    
    function voidOrder(kotId) {
        // Your existing local order functions
    }
    
    function printKOT(kotId) {
        // Your existing local order functions
    }
    
    function startCookingTimerModal(kotId, startTime, prepTime) {
        // Your existing timer function
    }
  </script>
  <script src="../JavaScript/sidebar.js"></script>
</body>
</html>