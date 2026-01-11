<?php
session_start();
include("../main_connection.php");

$db_name = "rest_m6_kot";
$conn = $connections[$db_name] ?? die("❌ Connection not found for $db_name");

// Pagination settings
$orders_per_page = 20;
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * $orders_per_page;

// Search and filter parameters
$search = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';
$status_filter = isset($_GET['status']) ? $conn->real_escape_string($_GET['status']) : '';
$date_filter = isset($_GET['date']) ? $conn->real_escape_string($_GET['date']) : '';

// Build WHERE conditions
$where_conditions = [];
$params = [];
$types = '';

if (!empty($search)) {
    $where_conditions[] = "(order_id LIKE ? OR item_name LIKE ? OR table_number LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $types .= 'sss';
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

// Build the query
$where_clause = empty($where_conditions) ? '' : 'WHERE ' . implode(' AND ', $where_conditions);
$query = "SELECT * FROM kot_orders $where_clause ORDER BY created_at DESC LIMIT ? OFFSET ?";
$params[] = $orders_per_page;
$params[] = $offset;
$types .= 'ii';

// Execute query
$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$orders_result = $stmt->get_result();

// Get total count for pagination
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
$total_orders = $count_result->fetch_assoc()['total'];
$total_pages = ceil($total_orders / $orders_per_page);

// Get menu database connection for prep_time
$menu_db_name = "rest_m3_menu";
$menu_conn = $connections[$menu_db_name] ?? null;

// Get POS database connection for table_id
$pos_db_name = "rest_m1_pos";
$pos_conn = $connections[$pos_db_name] ?? null;

// Fetch all orders for timer calculations
$all_orders_query = "SELECT kot_id, status, created_at FROM kot_orders WHERE status IN ('cook', 'pending', 'serve', 'preparing')";
$all_orders_result = $conn->query($all_orders_query);
$order_timers = [];
while ($order = $all_orders_result->fetch_assoc()) {
    $order_timers[$order['kot_id']] = $order;
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
        
        .badge-completed {
            background-color: #ecfdf5;
            color: #047857;
        }
        
        .badge-voided {
            background-color: #fee2e2;
            color: #991b1b;
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
            </div>
            
            <!-- Stats Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4 mb-6">
                <?php
                $status_query = "SELECT 
                    SUM(LOWER(status) = 'pending') as pending,
                    SUM(LOWER(status) = 'preparing') as preparing,
                    SUM(LOWER(status) = 'cook') as cooking,
                    SUM(LOWER(status) = 'serve') as ready_to_serve,
                    SUM(LOWER(status) = 'completed') as completed,
                    SUM(LOWER(status) = 'voided') as voided
                    FROM kot_orders";
                
                $status_result = $conn->query($status_query);
                $status_counts = $status_result->fetch_assoc();
                
                $cards = [
                    ['title' => 'Pending', 'count' => $status_counts['pending'] ?? 0, 'icon' => 'clock', 'filter' => 'pending', 'subtitle' => 'Not started'],
                    ['title' => 'Preparing', 'count' => $status_counts['preparing'] ?? 0, 'icon' => 'chef-hat', 'filter' => 'preparing', 'subtitle' => 'Being prepared'],
                    ['title' => 'Cooking', 'count' => $status_counts['cooking'] ?? 0, 'icon' => 'cooking-pot', 'filter' => 'cook', 'subtitle' => 'On the stove'],
                    ['title' => 'Ready to serve', 'count' => $status_counts['ready_to_serve'] ?? 0, 'icon' => 'tray-arrow-up', 'filter' => 'serve', 'subtitle' => 'Waiting for pickup'],
                ];
                
                foreach ($cards as $card) {
                ?>
                <div class="stat-card bg-white text-black shadow-xl p-5 rounded-lg cursor-pointer transition hover:shadow-2xl"
                     onclick="showOrdersModal('<?= $card['filter'] ?>', '<?= $card['title'] ?>')">
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
                    <div>
                        <label class="block text-sm font-medium mb-1">Search</label>
                        <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" 
                               placeholder="Order ID, Item, Table..." 
                               class="w-full px-3 py-2 border rounded-lg">
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Status</label>
                        <select name="status" class="w-full px-3 py-2 border rounded-lg">
                            <option value="all" <?= $status_filter === 'all' || $status_filter === '' ? 'selected' : '' ?>>All Status</option>
                            <option value="pending" <?= strtolower($status_filter) === 'pending' ? 'selected' : '' ?>>Pending</option>
                            <option value="preparing" <?= strtolower($status_filter) === 'preparing' ? 'selected' : '' ?>>Preparing</option>
                            <option value="cook" <?= strtolower($status_filter) === 'cook' ? 'selected' : '' ?>>Cooking</option>
                            <option value="serve" <?= strtolower($status_filter) === 'serve' ? 'selected' : '' ?>>Ready to Serve</option>
                            <option value="completed" <?= strtolower($status_filter) === 'completed' ? 'selected' : '' ?>>Completed</option>
                            <option value="voided" <?= strtolower($status_filter) === 'voided' ? 'selected' : '' ?>>Voided</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Date</label>
                        <input type="date" name="date" value="<?= htmlspecialchars($date_filter) ?>" 
                               class="w-full px-3 py-2 border rounded-lg">
                    </div>
                    <div class="flex items-end gap-2">
                        <button type="submit" class="px-4 py-2 bg-[#001f54] text-[#F7B32B] rounded-lg hover:opacity-90 flex items-center">
                            <i data-lucide="search" class="w-4 h-4 mr-2"></i> Filter
                        </button>
                        <a href="?" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 flex items-center">
                            <i data-lucide="x" class="w-4 h-4 mr-2"></i> Clear
                        </a>
                    </div>
                </form>
            </div>

            <!-- Recent Orders Section -->
            <div class="mb-6">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-lg font-semibold text-[#001f54]">Orders (<?= $total_orders ?> total)</h2>
                    <div class="text-sm text-gray-500">
                        Page <?= $page ?> of <?= $total_pages ?>
                    </div>
                </div>
                <div class="grid grid-cols-1 lg:grid-cols-2 xl:grid-cols-4 gap-5" id="recent-orders">
                    <?php
                    if ($orders_result->num_rows > 0) {
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
                                case 'completed':
                                    $status_class = 'badge-completed';
                                    $status_icon = 'check';
                                    break;
                                case 'voided':
                                    $status_class = 'badge-voided';
                                    $status_icon = 'x-circle';
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
                    <!-- Order Card -->
                    <div class="order-card bg-white shadow-lg rounded-lg p-5 transition hover:shadow-xl" 
                         data-kot-id="<?= $kot_id ?>" 
                         data-status="<?= $status ?>">
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
                        
                       
                        
                        <div class="flex justify-end">
                            <button class="px-4 py-2 rounded-md bg-[#001f54] text-[#F7B32B] text-sm font-semibold hover:opacity-90 transition flex items-center"
                                    onclick="showOrderDetails(<?= $kot_id ?>)">
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
                    ?>
                </div>
                
                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                <div class="flex justify-center items-center gap-2 mt-6">
                    <?php if ($page > 1): ?>
                        <a href="?page=<?= $page-1 ?>&search=<?= urlencode($search) ?>&status=<?= urlencode($status_filter) ?>&date=<?= urlencode($date_filter) ?>" 
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
                            <a href="?page=<?= $p ?>&search=<?= urlencode($search) ?>&status=<?= urlencode($status_filter) ?>&date=<?= urlencode($date_filter) ?>" 
                               class="px-3 py-1 rounded-lg <?= $p == $page ? 'bg-[#001f54] text-[#F7B32B]' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' ?>">
                                <?= $p ?>
                            </a>
                        <?php endfor; ?>
                    </div>
                    
                    <?php if ($page < $total_pages): ?>
                        <a href="?page=<?= $page+1 ?>&search=<?= urlencode($search) ?>&status=<?= urlencode($status_filter) ?>&date=<?= urlencode($date_filter) ?>" 
                           class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 flex items-center">
                            Next <i data-lucide="chevron-right" class="w-4 h-4 ml-1"></i>
                        </a>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
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
    
    // Show order details modal
    async function showOrderDetails(kotId) {
        try {
            document.getElementById('order-details-content').innerHTML = `
                <div class="flex justify-center items-center py-12">
                    <div class="animate-spin rounded-full h-10 w-10 border-t-2 border-b-2 border-[#001f54]"></div>
                </div>
            `;
            
            // Close any open SweetAlert before opening modal
            await Swal.close();
            
            order_details_modal.showModal();
            
            // Fetch order details
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
                case 'completed':
                    statusClass = 'badge-completed';
                    statusIcon = 'check';
                    statusLabel = 'Completed';
                    break;
                case 'voided':
                    statusClass = 'badge-voided';
                    statusIcon = 'x-circle';
                    statusLabel = 'Voided';
                    break;
            }
            
            const orderTime = new Date(order.created_at).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
            const orderDate = new Date(order.created_at).toLocaleDateString();
            
            // Calculate initial timer display
            let initialTimerDisplay = '';
            let isTimerFinished = false;
            
            if (status === 'cook') {
                const startTime = new Date(order.created_at).getTime();
                const currentTime = new Date().getTime();
                const elapsedSeconds = Math.floor((currentTime - startTime) / 1000);
                const totalPrepSeconds = prepTime * 60;
                const remainingSeconds = Math.max(0, totalPrepSeconds - elapsedSeconds);
                
                isTimerFinished = remainingSeconds <= 0;
                
                if (remainingSeconds > 0) {
                    const minutes = Math.floor(remainingSeconds / 60);
                    const seconds = remainingSeconds % 60;
                    initialTimerDisplay = `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
                } else {
                    initialTimerDisplay = "00:00";
                }
            }
            
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
                            
                            ${status === 'cook' && isTimerFinished ? `
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
            
        } catch (error) {
            console.error('Error:', error);
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Failed to load order details'
            });
        }
    }
    
    // Start cooking timer in modal
    function startCookingTimerModal(kotId, startTime, prepTime) {
        const timerEl = document.getElementById(`modal-timer-${kotId}`);
        if (!timerEl) return;
        
        const totalPrepSeconds = prepTime * 60;
        const endTime = startTime + (totalPrepSeconds * 1000);
        
        function updateTimer() {
            const now = new Date().getTime();
            const remaining = endTime - now;
            const remainingSeconds = Math.max(0, Math.floor(remaining / 1000));
            
            if (remainingSeconds <= 0) {
                timerEl.innerHTML = "00:00";
                timerEl.className = 'kot-timer expired-timer';
                
                // Show "Mark as Complete" button
                const completeBtn = document.getElementById(`complete-btn-${kotId}`);
                if (!completeBtn) {
                    const actionButtons = document.getElementById(`action-buttons-${kotId}`);
                    if (actionButtons) {
                        // Create and insert the complete button
                        const btnHtml = `
                            <button onclick="markAsComplete(${kotId})" 
                                    class="action-btn btn-complete" id="complete-btn-${kotId}">
                                <i data-lucide="check" class="w-4 h-4"></i>
                                MARK AS COMPLETE
                            </button>
                        `;
                        
                        // Insert after any existing buttons
                        const existingButtons = actionButtons.querySelectorAll('button');
                        if (existingButtons.length > 0) {
                            existingButtons[0].insertAdjacentHTML('afterend', btnHtml);
                        } else {
                            actionButtons.innerHTML = btnHtml + actionButtons.innerHTML;
                        }
                        
                        lucide.createIcons();
                    }
                }
                
                clearInterval(timers[`modal-${kotId}`]);
                return;
            }
            
            const minutes = Math.floor(remainingSeconds / 60);
            const seconds = remainingSeconds % 60;
            
            timerEl.innerHTML = `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
        }
        
        // Clear any existing timer
        if (timers[`modal-${kotId}`]) {
            clearInterval(timers[`modal-${kotId}`]);
        }
        
        updateTimer(); // Initial update
        timers[`modal-${kotId}`] = setInterval(updateTimer, 1000);
    }
    
    // CRUD Functions
    async function startPreparing(kotId) {
        // Close order modal first
        order_details_modal.close();
        
        const result = await Swal.fire({
            title: 'Start Preparing?',
            text: 'This will change the status to "Preparing".',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#10b981',
            cancelButtonColor: '#6b7280',
            confirmButtonText: 'Yes, start preparing',
            cancelButtonText: 'Cancel',
            allowOutsideClick: false
        });
        
        if (result.isConfirmed) {
            await updateOrderStatus(kotId, 'preparing', 'Order is now being prepared');
        } else {
            // Re-open the order modal if cancelled
            showOrderDetails(kotId);
        }
    }
    
    async function startCooking(kotId, prepTime) {
        // Close order modal first
        order_details_modal.close();
        
        const result = await Swal.fire({
            title: 'Start Cooking?',
            html: `This will start the timer for <b>${prepTime} minutes</b>.<br>Status will change to "Cooking".`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#f59e0b',
            cancelButtonColor: '#6b7280',
            confirmButtonText: 'Start Cooking',
            cancelButtonText: 'Cancel',
            allowOutsideClick: false
        });
        
        if (result.isConfirmed) {
            await updateOrderStatus(kotId, 'cook', 'Cooking started');
        } else {
            // Re-open the order modal if cancelled
            showOrderDetails(kotId);
        }
    }
    
    async function markAsComplete(kotId) {
        // Close order modal first
        order_details_modal.close();
        
        const result = await Swal.fire({
            title: 'Mark as Complete?',
            text: 'This will change status to "Ready to Serve".',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#10b981',
            cancelButtonColor: '#6b7280',
            confirmButtonText: 'Mark as Complete',
            cancelButtonText: 'Cancel',
            allowOutsideClick: false
        });
        
        if (result.isConfirmed) {
            await updateOrderStatus(kotId, 'serve', 'Order marked as complete - Ready to serve');
        } else {
            // Re-open the order modal if cancelled
            showOrderDetails(kotId);
        }
    }
    
    async function markAsServed(kotId) {
        // Close order modal first
        order_details_modal.close();
        
        const result = await Swal.fire({
            title: 'Mark as Served?',
            text: 'This will change status to "Completed".',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#3b82f6',
            cancelButtonColor: '#6b7280',
            confirmButtonText: 'Mark as Served',
            cancelButtonText: 'Cancel',
            allowOutsideClick: false
        });
        
        if (result.isConfirmed) {
            await updateOrderStatus(kotId, 'completed', 'Order marked as served');
        } else {
            // Re-open the order modal if cancelled
            showOrderDetails(kotId);
        }
    }
    
    async function voidOrder(kotId) {
        // Close order modal first
        order_details_modal.close();
        
        const result = await Swal.fire({
            title: 'Void Order?',
            text: 'This action cannot be undone! Only orders with "Pending" status can be voided.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            cancelButtonColor: '#6b7280',
            confirmButtonText: 'Yes, void it!',
            cancelButtonText: 'Cancel',
            allowOutsideClick: false
        });
        
        if (result.isConfirmed) {
            await updateOrderStatus(kotId, 'voided', 'Order has been voided');
        } else {
            // Re-open the order modal if cancelled
            showOrderDetails(kotId);
        }
    }
    
    // Update order status function
    async function updateOrderStatus(kotId, status, successMessage) {
        try {
            Swal.fire({
                title: 'Processing...',
                allowOutsideClick: false,
                showConfirmButton: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });
            
            const response = await fetch("sub-modules/update_order_status.php", {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify({ 
                    kot_id: kotId, 
                    status: status,
                    action_time: new Date().toISOString()
                })
            });
            
            const data = await response.json();
            
            if (data.success) {
                // Clear timers if needed
                if (timers[`modal-${kotId}`]) {
                    clearInterval(timers[`modal-${kotId}`]);
                    delete timers[`modal-${kotId}`];
                }
                
                Swal.fire({
                    icon: 'success',
                    title: 'Success!',
                    text: successMessage,
                    timer: 1500,
                    showConfirmButton: false
                }).then(() => {
                    location.reload();
                });
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: data.msg || 'Failed to update order'
                }).then(() => {
                    // Re-open the order modal on error
                    showOrderDetails(kotId);
                });
            }
        } catch (error) {
            console.error('Error:', error);
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Network error. Please try again.'
            }).then(() => {
                // Re-open the order modal on error
                showOrderDetails(kotId);
            });
        }
    }
    
    // Print KOT function
    function printKOT(kotId) {
        const printWindow = window.open('', '_blank');
        printWindow.document.write(`
            <html>
            <head>
                <title>KOT #${kotId}</title>
                <style>
                    body { font-family: 'Courier New', monospace; padding: 20px; max-width: 400px; margin: 0 auto; }
                    .header { text-align: center; border-bottom: 2px dashed #000; padding-bottom: 15px; margin-bottom: 20px; }
                    .restaurant-name { font-size: 24px; font-weight: bold; margin-bottom: 5px; }
                    .kot-title { font-size: 18px; margin-bottom: 10px; }
                    .order-info { margin-bottom: 20px; }
                    .info-row { display: flex; justify-content: space-between; margin-bottom: 5px; }
                    .items-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
                    .items-table th { border-bottom: 1px solid #000; padding: 8px 0; text-align: left; }
                    .items-table td { padding: 8px 0; }
                    .footer { text-align: center; margin-top: 30px; font-size: 12px; color: #666; border-top: 1px dashed #000; padding-top: 10px; }
                    .timestamp { text-align: center; margin-top: 10px; font-size: 12px; }
                    @media print {
                        body { padding: 0; }
                        .no-print { display: none; }
                    }
                </style>
            </head>
            <body>
                <div class="header">
                    <div class="restaurant-name">SOLIERA RESTAURANT</div>
                    <div class="kot-title">KITCHEN ORDER TICKET</div>
                </div>
                
                <div class="order-info">
                    <div class="info-row">
                        <span>KOT #:</span>
                        <span><strong>${kotId}</strong></span>
                    </div>
                    <div class="info-row">
                        <span>Date:</span>
                        <span>${new Date().toLocaleDateString()}</span>
                    </div>
                    <div class="info-row">
                        <span>Time:</span>
                        <span>${new Date().toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'})}</span>
                    </div>
                </div>
                
                <table class="items-table">
                    <thead>
                        <tr>
                            <th>QTY</th>
                            <th>ITEM</th>
                            <th>NOTES</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>1</td>
                            <td>Order #${kotId}</td>
                            <td>KOT Print</td>
                        </tr>
                    </tbody>
                </table>
                
                <div class="footer">
                    <div>*** KITCHEN COPY ***</div>
                    <div>Printed from Kitchen Display System</div>
                </div>
                
                <div class="timestamp">
                    Printed: ${new Date().toLocaleString()}
                </div>
                
                <div class="no-print" style="margin-top: 20px; text-align: center;">
                    <button onclick="window.print()" style="padding: 10px 20px; background: #001f54; color: white; border: none; border-radius: 5px; cursor: pointer;">
                        Print Now
                    </button>
                    <button onclick="window.close()" style="padding: 10px 20px; background: #666; color: white; border: none; border-radius: 5px; cursor: pointer; margin-left: 10px;">
                        Close
                    </button>
                </div>
                
                <script>
                    window.onload = function() {
                        window.print();
                    };
                <\/script>
            </body>
            </html>
        `);
        printWindow.document.close();
    }
    
    // Show orders modal by status
    function showOrdersModal(status, title) {
        window.location.href = `?status=${status}`;
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
    
    // Update all timers on the main page
    function updateAllTimers() {
        document.querySelectorAll('[id^="timer-"]').forEach(timerEl => {
            const kotId = timerEl.id.replace('timer-', '');
            const orderCard = document.querySelector(`[data-kot-id="${kotId}"]`);
            
            if (orderCard) {
                const status = orderCard.dataset.status;
                if (status === 'cook') {
                    // Check if this is a modal timer or main page timer
                    if (timerEl.id.startsWith('modal-timer-')) {
                        // Modal timer is handled separately
                        return;
                    }
                    
                    // For main page timers
                    const startTime = parseInt(orderCard.dataset.startTime) || Date.now();
                    const prepTime = parseInt(orderCard.dataset.prepTime) || 15;
                    const currentTime = Date.now();
                    const elapsedSeconds = Math.floor((currentTime - startTime) / 1000);
                    const totalPrepSeconds = prepTime * 60;
                    const remainingSeconds = Math.max(0, totalPrepSeconds - elapsedSeconds);
                    
                    if (remainingSeconds <= 0) {
                        timerEl.innerHTML = "00:00";
                        timerEl.className = 'kot-timer expired-timer';
                    } else {
                        const minutes = Math.floor(remainingSeconds / 60);
                        const seconds = remainingSeconds % 60;
                        timerEl.innerHTML = `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
                        timerEl.className = 'kot-timer cooking-timer';
                    }
                }
            }
        });
    }
    
    // Start updating main page timers
    setInterval(updateAllTimers, 1000);
    updateAllTimers(); // Initial call
    
    // Refresh page every 30 seconds to get updated orders
    setInterval(() => {
        const isModalOpen = order_details_modal.open;
        if (!isModalOpen) {
            location.reload();
        }
    }, 30000);
</script>
  <script src="../JavaScript/sidebar.js"></script>
</body>
</html>