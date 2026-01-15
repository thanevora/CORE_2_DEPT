<?php
$token = "uX8B1QqYJt7XqTf0sM3tKAh5nCjEjR1Xlqk4F8ZdD1mHq5V9y7oUj1QhUzPg5s";

// Handle the PUT request when the form is submitted
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
            "Authorization: Bearer $token",
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

// Fetch KOTs
$ch = curl_init("https://hotel.soliera-hotel-restaurant.com/api/getKOT");
$token = "uX8B1QqYJt7XqTf0sM3tKAh5nCjEjR1Xlqk4F8ZdD1mHq5V9y7oUj1QhUzPg5s";
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        "Authorization: Bearer $token",
        "Accept: application/json"
    ]
]);
$response = curl_exec($ch);
curl_close($ch);

$data = json_decode($response, true);
$kots = $data['data'] ?? [];

// Pagination settings
$per_page = 20;
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * $per_page;

// Calculate stats for each status
$totalKots = count($kots);
$stats = [
    'pending' => 0,
    'preparing' => 0,
    'cooking' => 0,
    'ready_to_serve' => 0,
    'delivered' => 0,
    'voided' => 0,
    'completed' => 0,
    'cooked' => 0 // For backward compatibility
];

$today = date('Y-m-d');
$todayCount = 0;

foreach ($kots as $kot) {
    $kotStatus = strtolower($kot['status'] ?? 'pending');
    
    // Map API statuses to our status categories
    if ($kotStatus === 'pending' || $kotStatus === 'ordered') {
        $stats['pending']++;
    } elseif ($kotStatus === 'preparing' || $kotStatus === 'preparation') {
        $stats['preparing']++;
    } elseif ($kotStatus === 'cooking' || $kotStatus === 'cook') {
        $stats['cooking']++;
    } elseif ($kotStatus === 'cooked' || $kotStatus === 'ready_to_serve' || $kotStatus === 'ready') {
        $stats['ready_to_serve']++;
        $stats['cooked']++; // For backward compatibility
    } elseif ($kotStatus === 'delivered' || $kotStatus === 'served') {
        $stats['delivered']++;
    } elseif ($kotStatus === 'voided' || $kotStatus === 'cancelled') {
        $stats['voided']++;
    } elseif ($kotStatus === 'completed') {
        $stats['completed']++;
    } else {
        // Default to pending if status not recognized
        $stats['pending']++;
    }
    
    $kotDate = date('Y-m-d', strtotime($kot['created_at'] ?? 'now'));
    if ($kotDate === $today) {
        $todayCount++;
    }
}

// Filter KOTs based on status filter
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$search_term = isset($_GET['search']) ? strtolower($_GET['search']) : '';

$filtered_kots = [];
foreach ($kots as $kot) {
    $kotStatus = strtolower($kot['status'] ?? 'pending');
    $mappedStatus = 'pending'; // Default
    
    // Map to our status categories
    if ($kotStatus === 'pending' || $kotStatus === 'ordered') {
        $mappedStatus = 'pending';
    } elseif ($kotStatus === 'preparing' || $kotStatus === 'preparation') {
        $mappedStatus = 'preparing';
    } elseif ($kotStatus === 'cooking' || $kotStatus === 'cook') {
        $mappedStatus = 'cooking';
    } elseif ($kotStatus === 'cooked' || $kotStatus === 'ready_to_serve' || $kotStatus === 'ready') {
        $mappedStatus = 'ready_to_serve';
    } elseif ($kotStatus === 'delivered' || $kotStatus === 'served') {
        $mappedStatus = 'delivered';
    } elseif ($kotStatus === 'voided' || $kotStatus === 'cancelled') {
        $mappedStatus = 'voided';
    } elseif ($kotStatus === 'completed') {
        $mappedStatus = 'completed';
    }
    
    // Apply filters
    $status_match = !$status_filter || $mappedStatus === $status_filter;
    
    // Apply search filter
    $search_match = !$search_term || 
                   strpos(strtolower($kot['kot_id'] ?? ''), $search_term) !== false ||
                   strpos(strtolower($kot['order_id'] ?? ''), $search_term) !== false ||
                   strpos(strtolower($kot['item_name'] ?? ''), $search_term) !== false ||
                   strpos(strtolower($kot['table_number'] ?? ''), $search_term) !== false;
    
    if ($status_match && $search_match) {
        $filtered_kots[] = $kot;
    }
}

// Apply pagination
$total_filtered = count($filtered_kots);
$total_pages = ceil($total_filtered / $per_page);
$paginated_kots = array_slice($filtered_kots, $offset, $per_page);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <?php include '../../header.php'; ?>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kitchen Display System | Soliera Hotel</title>
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
    
    <!-- SweetAlert2 -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    
    <style>
        :root {
            --primary: #001f54;
            --secondary: #F7B32B;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
            --dark: #1e293b;
            --light: #f8fafc;
        }
        
        body {
            font-family: 'Inter', system-ui, sans-serif;
            background-color: #f1f5f9;
            color: var(--dark);
        }
        
        .stat-card {
            transition: all 0.3s ease;
            cursor: pointer;
        }
        
        .stat-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
        }
        
        .stat-card.active {
            border: 2px solid var(--primary);
            box-shadow: 0 0 0 3px rgba(0, 31, 84, 0.2);
        }
        
        .kot-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            border-left: 4px solid;
            overflow: hidden;
        }
        
        .kot-card:hover {
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
        }
        
        .kot-card.pending {
            border-left-color: #f59e0b;
        }
        
        .kot-card.preparing {
            border-left-color: #8b5cf6;
        }
        
        .kot-card.cooking {
            border-left-color: #f97316;
        }
        
        .kot-card.ready_to_serve {
            border-left-color: #10b981;
        }
        
        .kot-card.delivered {
            border-left-color: #3b82f6;
        }
        
        .kot-card.voided {
            border-left-color: #ef4444;
        }
        
        .kot-card.completed {
            border-left-color: #059669;
        }
        
        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }
        
        .badge-pending {
            background-color: #fef3c7;
            color: #92400e;
        }
        
        .badge-preparing {
            background-color: #f3e8ff;
            color: #7c3aed;
        }
        
        .badge-cooking {
            background-color: #ffedd5;
            color: #ea580c;
        }
        
        .badge-ready_to_serve {
            background-color: #d1fae5;
            color: #065f46;
        }
        
        .badge-delivered {
            background-color: #dbeafe;
            color: #1d4ed8;
        }
        
        .badge-voided {
            background-color: #fee2e2;
            color: #991b1b;
        }
        
        .badge-completed {
            background-color: #ecfdf5;
            color: #047857;
        }
        
        @keyframes pulse {
            0% { box-shadow: 0 0 0 0 rgba(245, 158, 11, 0.4); }
            70% { box-shadow: 0 0 0 10px rgba(245, 158, 11, 0); }
            100% { box-shadow: 0 0 0 0 rgba(245, 158, 11, 0); }
        }
        
        .urgent-card {
            animation: pulse 1.5s infinite;
            border-left-color: var(--danger);
        }
        
        .search-box {
            transition: all 0.3s ease;
        }
        
        .search-box:focus {
            box-shadow: 0 0 0 3px rgba(0, 31, 84, 0.2);
        }
        
        .btn-primary {
            background: var(--primary);
            color: var(--secondary);
        }
        
        .btn-primary:hover {
            opacity: 0.9;
        }
        
        .btn-cook {
            background: var(--warning);
            color: white;
        }
        
        .btn-cook:hover {
            background: #e48406;
        }
        
        .btn-disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        
        .action-btn {
            transition: all 0.2s ease;
        }
        
        .action-btn:hover {
            transform: scale(1.05);
        }
        
        .modal-box {
            max-width: 90%;
            width: 500px;
        }
        
        .timer {
            font-family: 'Courier New', monospace;
            font-weight: bold;
            background: var(--dark);
            color: white;
            padding: 4px 8px;
            border-radius: 4px;
        }
        
        .pagination-btn {
            transition: all 0.2s ease;
        }
        
        .pagination-btn:hover:not(.active):not(:disabled) {
            background-color: #e5e7eb;
        }
    </style>
</head>
<body class="bg-base-100 min-h-screen bg-white">
  <div class="flex h-screen">
    <!-- Sidebar -->
    <?php include '../../sidebarr.php'; ?>

    <!-- Content Area -->
    <div class="flex flex-col flex-1 overflow-auto">
        <!-- Navbar -->
        <?php include '../../navbar.php'; ?>

        <!-- Main Content -->
        <main class="flex-1 overflow-auto p-4 md:p-6">
            <!-- Header -->
            <div class="flex justify-between items-center mb-6">
                <h1 class="text-2xl font-bold text-gray-800">Kitchen Order Ticket (KOT)</h1>
                <div class="text-sm text-gray-500">
                    Total KOTs: <?= $totalKots ?>
                </div>
            </div>
            
            <!-- Success Message -->
            <?php if($successMessage): ?>
                <div class="mb-6 p-4 rounded-lg bg-green-50 border border-green-200 text-green-700 flex items-center gap-2">
                    <i data-lucide="check-circle" class="w-5 h-5"></i>
                    <?= htmlspecialchars($successMessage) ?>
                </div>
            <?php endif; ?>
            
          <!-- Stats Cards -->
<div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-3 gap-4 mb-6">
    <?php
    $status_cards = [
        ['title' => 'Pending', 'count' => $stats['pending'] ?? 0, 'icon' => 'clock', 'filter' => 'pending', 'subtitle' => 'Not started'],
        ['title' => 'Preparing', 'count' => $stats['preparing'] ?? 0, 'icon' => 'chef-hat', 'filter' => 'preparing', 'subtitle' => 'Being prepared'],
        ['title' => 'Cooking', 'count' => $stats['cooking'] ?? 0, 'icon' => 'cooking-pot', 'filter' => 'cooking', 'subtitle' => 'On the stove'],
        ['title' => 'Ready to serve', 'count' => $stats['ready_to_serve'] ?? 0, 'icon' => 'tray-arrow-up', 'filter' => 'ready_to_serve', 'subtitle' => 'Waiting for pickup'],
        ['title' => 'Delivered', 'count' => $stats['delivered'] ?? 0, 'icon' => 'check-circle', 'filter' => 'delivered', 'subtitle' => 'Served to table'],
        ['title' => 'Voided', 'count' => $stats['voided'] ?? 0, 'icon' => 'x-circle', 'filter' => 'voided', 'subtitle' => 'Cancelled orders'],
    ];
    
    foreach ($status_cards as $card) {
        $isActive = $status_filter === $card['filter'];
    ?>
    <div class="stat-card bg-white shadow-xl p-5 rounded-lg cursor-pointer <?= $isActive ? 'active' : '' ?>"
         onclick="filterByStatus('<?= $card['filter'] ?>')">
        <div class="flex justify-between items-start">
            <div>
                <p class="text-sm font-medium text-gray-600"><?= $card['title'] ?></p>
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
            
            <!-- Controls and Filters -->
            <div class="bg-white rounded-lg shadow p-4 mb-6">
                <form method="GET" action="" class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                    <div class="flex-1">
                        <div class="relative">
                            <i data-lucide="search" class="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-400 w-5 h-5"></i>
                            <input 
                                type="text" 
                                name="search" 
                                value="<?= htmlspecialchars($search_term) ?>" 
                                placeholder="Search by KOT ID, Order ID, Item, Table..." 
                                class="bg-white w-lg pl-10 pr-4 py-2 border border-gray-300 rounded-lg search-box focus:outline-none focus:ring-2 focus:ring-[#001f54] focus:border-transparent"
                            >
                        </div>
                    </div>
                    <div class="flex gap-2">
                        <button type="submit" class="px-4 py-2 rounded-lg bg-[#001f54] text-[#F7B32B] hover:opacity-90 transition flex items-center">
                            <i data-lucide="search" class="w-4 h-4 mr-2"></i> Search
                        </button>
                        <a href="?" class="px-4 py-2 rounded-lg bg-gray-200 text-gray-700 hover:bg-gray-300 transition flex items-center">
                            <i data-lucide="x" class="w-4 h-4 mr-2"></i> Clear
                        </a>
                    </div>
                </form>
            </div>
            
            <!-- KOT Grid -->
            <div class="mb-8">
                <div class="mb-6">
    <div class="flex justify-between items-center mb-4">
        <h2 class="text-lg font-semibold text-[#001f54]">Orders (<?= $total_filtered ?> total)</h2>
        <div class="text-sm text-gray-500">
            Page <?= $page ?> of <?= max(1, $total_pages) ?>
        </div>
    </div>
    <div class="grid grid-cols-1 lg:grid-cols-2 xl:grid-cols-4 gap-5" id="recent-orders">
        <?php
        if (!empty($paginated_kots)) {
            foreach ($paginated_kots as $kot): 
                $kotStatus = strtolower($kot['status'] ?? 'pending');
                
                // Map API status to our status categories
                $status = 'pending';
                $status_label = 'Pending';
                $status_icon = 'clock';
                
                if ($kotStatus === 'pending' || $kotStatus === 'ordered') {
                    $status = 'pending';
                    $status_label = 'Pending';
                    $status_icon = 'clock';
                } elseif ($kotStatus === 'preparing' || $kotStatus === 'preparation') {
                    $status = 'preparing';
                    $status_label = 'Preparing';
                    $status_icon = 'chef-hat';
                } elseif ($kotStatus === 'cooking' || $kotStatus === 'cook') {
                    $status = 'cook';
                    $status_label = 'Cooking';
                    $status_icon = 'cooking-pot';
                } elseif ($kotStatus === 'cooked' || $kotStatus === 'ready_to_serve' || $kotStatus === 'ready') {
                    $status = 'serve';
                    $status_label = 'Ready to Serve';
                    $status_icon = 'check-circle';
                } elseif ($kotStatus === 'delivered' || $kotStatus === 'served') {
                    $status = 'completed';
                    $status_label = 'Delivered';
                    $status_icon = 'check-circle';
                } elseif ($kotStatus === 'voided' || $kotStatus === 'cancelled') {
                    $status = 'voided';
                    $status_label = 'Voided';
                    $status_icon = 'x-circle';
                } elseif ($kotStatus === 'completed') {
                    $status = 'completed';
                    $status_label = 'Completed';
                    $status_icon = 'check';
                }
                
                $isCooked = ($kotStatus === 'cooked' || $kotStatus === 'cook' || $kotStatus === 'ready_to_serve' || $kotStatus === 'ready' || $kotStatus === 'delivered' || $kotStatus === 'served' || $kotStatus === 'completed');
                $order_time = isset($kot['created_at']) ? date('h:i A', strtotime($kot['created_at'])) : 'N/A';
                $kot_id = $kot['kot_id'] ?? 'N/A';
                $table_id = $kot['table_number'] ?? 'N/A';
                $order_id = $kot['order_id'] ?? 'N/A';
                
                // Status badge class
                $status_class = '';
                switch($status) {
                    case 'pending':
                        $status_class = 'badge-pending';
                        break;
                    case 'preparing':
                        $status_class = 'badge-preparing';
                        break;
                    case 'cook':
                        $status_class = 'badge-cook';
                        break;
                    case 'serve':
                        $status_class = 'badge-serve';
                        break;
                    case 'completed':
                        $status_class = 'badge-completed';
                        break;
                    case 'voided':
                        $status_class = 'badge-voided';
                        break;
                    default:
                        $status_class = 'badge-pending';
                }
        ?>
        <!-- Order Card -->
        <div class="order-card bg-white shadow-lg rounded-lg p-5 transition hover:shadow-xl" 
             data-kot-id="<?= $kot_id ?>" 
             data-status="<?= $status ?>">
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
                    <span class="font-medium text-gray-700"><?= htmlspecialchars($kot['item_name'] ?? 'N/A') ?></span>
                </div>
                <div class="flex justify-between items-center">
                    <div class="flex items-center gap-4">
                        <span class="text-sm text-gray-600 flex items-center">
                            <i data-lucide="hash" class="w-3 h-3 mr-1"></i>
                            Qty: <?= htmlspecialchars($kot['quantity'] ?? '1') ?>
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
                <?php if (!$isCooked && $status !== 'voided'): ?>
                    <form method="POST" onsubmit="return confirmMarkAsCooked(<?= htmlspecialchars($order_id) ?>)">
                        <input type="hidden" name="order_id" value="<?= htmlspecialchars($order_id) ?>">
                        <button type="submit" class="px-4 py-2 rounded-md bg-[#001f54] text-[#F7B32B] text-sm font-semibold hover:opacity-90 transition flex items-center">
                            <i data-lucide="cooking-pot" class="w-4 h-4 mr-2"></i>
                            Mark as Cooked
                        </button>
                    </form>
                <?php else: ?>
                    <button class="px-4 py-2 rounded-md bg-[#001f54] text-[#F7B32B] text-sm font-semibold hover:opacity-90 transition flex items-center">
                        <i data-lucide="eye" class="w-4 h-4 mr-2"></i>
                        View Details
                    </button>
                <?php endif; ?>
            </div>
        </div>
        <?php
            endforeach;
        } else {
            echo '<div class="col-span-full text-center py-12 text-gray-400">
                <i data-lucide="utensils-crossed" class="w-12 h-12 mx-auto mb-4"></i>
                <p class="text-lg font-medium">No orders found</p>';
            if (!empty($search_term) || !empty($status_filter)) {
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
            <a href="?page=<?= $page-1 ?><?= $status_filter ? '&status=' . urlencode($status_filter) : '' ?><?= $search_term ? '&search=' . urlencode($search_term) : '' ?>" 
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
                <a href="?page=<?= $p ?><?= $status_filter ? '&status=' . urlencode($status_filter) : '' ?><?= $search_term ? '&search=' . urlencode($search_term) : '' ?>" 
                   class="px-3 py-1 rounded-lg <?= $p == $page ? 'bg-[#001f54] text-[#F7B32B]' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' ?>">
                    <?= $p ?>
                </a>
            <?php endfor; ?>
        </div>
        
        <?php if ($page < $total_pages): ?>
            <a href="?page=<?= $page+1 ?><?= $status_filter ? '&status=' . urlencode($status_filter) : '' ?><?= $search_term ? '&search=' . urlencode($search_term) : '' ?>" 
               class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 flex items-center">
                Next <i data-lucide="chevron-right" class="w-4 h-4 ml-1"></i>
            </a>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</div>
            
            <!-- API Data Section (Collapsible) -->
            <div class="bg-white rounded-lg shadow mb-6">
                <button id="toggleApiData" class="w-full px-4 py-3 flex justify-between items-center hover:bg-gray-50 rounded-lg">
                    <div class="flex items-center gap-2">
                        <i data-lucide="database" class="w-5 h-5"></i>
                        <span class="font-medium">API Response Data</span>
                    </div>
                    <i data-lucide="chevron-down" id="apiDataIcon" class="w-5 h-5 transition-transform"></i>
                </button>
                <div id="apiDataContent" class="hidden px-4 pb-4">
                    <textarea class="w-full h-64 p-3 border border-gray-300 rounded-lg font-mono text-sm" readonly><?= htmlspecialchars(json_encode($data, JSON_PRETTY_PRINT)) ?></textarea>
                </div>
            </div>
        </main>
    </div>
  </div>
    
    <!-- Quick Actions Footer -->
    <div class="fixed bottom-6 right-6 flex gap-2">
        <button onclick="refreshOrders()" class="bg-[#001f54] text-[#F7B32B] p-3 rounded-full shadow-lg hover:opacity-90 transition action-btn">
            <i data-lucide="refresh-cw" class="w-5 h-5"></i>
        </button>
        <button onclick="filterByStatus('')" class="bg-white text-[#001f54] p-3 rounded-full shadow-lg hover:bg-gray-50 transition action-btn">
            <i data-lucide="list" class="w-5 h-5"></i>
        </button>
        <button onclick="filterByStatus('pending')" class="bg-yellow-500 text-white p-3 rounded-full shadow-lg hover:bg-yellow-600 transition action-btn">
            <i data-lucide="alert-circle" class="w-5 h-5"></i>
        </button>
    </div>
    
    <!-- SweetAlert2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <script>
        // Initialize Lucide icons
        lucide.createIcons();
        
        // Initialize SweetAlert2
        const Swal = window.Swal;
        
        // Toggle API data section
        document.getElementById('toggleApiData').addEventListener('click', function() {
            const content = document.getElementById('apiDataContent');
            const icon = document.getElementById('apiDataIcon');
            content.classList.toggle('hidden');
            icon.style.transform = content.classList.contains('hidden') ? 'rotate(0deg)' : 'rotate(180deg)';
        });
        
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
        
        // Search functionality
        document.getElementById('searchInput')?.addEventListener('input', function(e) {
            // This will be handled by form submission
        });
        
        // Confirm mark as cooked
        function confirmMarkAsCooked(orderId) {
            Swal.fire({
                title: 'Mark as Cooked?',
                text: `Are you sure you want to mark Order #${orderId} as cooked?`,
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
        
        // Quick action functions
        function refreshOrders() {
            Swal.fire({
                title: 'Refreshing...',
                allowOutsideClick: false,
                showConfirmButton: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });
            
            setTimeout(() => {
                window.location.reload();
            }, 500);
        }
        
        // Auto-refresh every 30 seconds
        setInterval(refreshOrders, 30000);
        
        // Highlight active stat card
        function highlightActiveCard() {
            const status = '<?= $status_filter ?>';
            const cards = document.querySelectorAll('.stat-card');
            cards.forEach(card => {
                card.classList.remove('active');
                const filter = card.getAttribute('onclick')?.match(/'([^']+)'/)?.[1];
                if (filter === status) {
                    card.classList.add('active');
                }
            });
        }
        
        // Initialize on page load
        document.addEventListener('DOMContentLoaded', function() {
            highlightActiveCard();
            lucide.createIcons();
        });
    </script>
</body>
</html>