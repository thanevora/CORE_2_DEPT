<?php
session_start();
include("../main_connection.php");

$db_name = "rest_m11_event";
if (!isset($connections[$db_name])) {
    die("❌ Connection not found for $db_name");
}

$conn = $connections[$db_name];

// Fetch event data with pagination
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = 15;
$offset = ($page - 1) * $limit;

// Build query with filters
$where_clause = "1=1";
$params = [];
$types = "";

if (isset($_GET['search']) && !empty($_GET['search'])) {
    $search = "%" . $_GET['search'] . "%";
    $where_clause .= " AND (customer_name LIKE ? OR event_name LIKE ? OR customer_email LIKE ?)";
    $params[] = $search;
    $params[] = $search;
    $params[] = $search;
    $types .= "sss";
}

if (isset($_GET['status']) && !empty($_GET['status'])) {
    $where_clause .= " AND reservation_status = ?";
    $params[] = $_GET['status'];
    $types .= "s";
}

if (isset($_GET['date_from']) && !empty($_GET['date_from'])) {
    $where_clause .= " AND event_date >= ?";
    $params[] = $_GET['date_from'];
    $types .= "s";
}

if (isset($_GET['date_to']) && !empty($_GET['date_to'])) {
    $where_clause .= " AND event_date <= ?";
    $params[] = $_GET['date_to'];
    $types .= "s";
}

// Get total count
$count_sql = "SELECT COUNT(*) as total FROM event_reservations WHERE $where_clause";
$count_stmt = $conn->prepare($count_sql);
if ($types) {
    $count_stmt->bind_param($types, ...$params);
}
$count_stmt->execute();
$count_result = $count_stmt->get_result();
$total_rows = $count_result->fetch_assoc()['total'];
$total_pages = ceil($total_rows / $limit);

// Get data
$sql = "SELECT * FROM event_reservations WHERE $where_clause ORDER BY created_at DESC LIMIT ? OFFSET ?";
$types .= "ii";
$params[] = $limit;
$params[] = $offset;

$stmt = $conn->prepare($sql);
if ($types) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$events_result = $stmt->get_result();

// Count events by status - UPDATED CATEGORIES
$confirmed_count = 0;
$under_review_count = 0;
$denied_count = 0;
$reserved_count = 0;
$cancelled_count = 0;
$total_reservations = 0;

// Get all counts at once for efficiency
$counts_sql = "SELECT 
    COUNT(*) as total_reservations,
    SUM(CASE WHEN reservation_status = 'Confirmed' THEN 1 ELSE 0 END) as confirmed_count,
    SUM(CASE WHEN reservation_status IN ('Under Review', 'Pending') THEN 1 ELSE 0 END) as under_review_count,
    SUM(CASE WHEN reservation_status = 'Denied' THEN 1 ELSE 0 END) as denied_count,
    SUM(CASE WHEN reservation_status = 'Reserved' THEN 1 ELSE 0 END) as reserved_count,
    SUM(CASE WHEN reservation_status = 'Cancelled' THEN 1 ELSE 0 END) as cancelled_count
    FROM event_reservations";

$counts_result = $conn->query($counts_sql);
if ($counts_result && $counts_row = $counts_result->fetch_assoc()) {
    $total_reservations = $counts_row['total_reservations'] ?? 0;
    $confirmed_count = $counts_row['confirmed_count'] ?? 0;
    $under_review_count = $counts_row['under_review_count'] ?? 0;
    $denied_count = $counts_row['denied_count'] ?? 0;
    $reserved_count = $counts_row['reserved_count'] ?? 0;
    $cancelled_count = $counts_row['cancelled_count'] ?? 0;
}

// Get today's date
$today = date('Y-m-d');
$today_count = 0;

// Get today's events count
$today_sql = "SELECT COUNT(*) as today_count FROM event_reservations WHERE event_date = ?";
$today_stmt = $conn->prepare($today_sql);
$today_stmt->bind_param("s", $today);
$today_stmt->execute();
$today_result = $today_stmt->get_result();
if ($today_row = $today_result->fetch_assoc()) {
    $today_count = $today_row['today_count'] ?? 0;
}

// Process events for upcoming events
$upcoming_events = [];
if ($events_result && mysqli_num_rows($events_result) > 0) {
    while ($event = mysqli_fetch_assoc($events_result)) {
        // Get upcoming events (next 7 days) - Confirmed and Reserved only
        if ($event['event_date'] >= $today && 
            ($event['reservation_status'] == 'Confirmed' || 
             $event['reservation_status'] == 'Reserved')) {
            $upcoming_events[] = $event;
        }
    }
    
    // Reset pointer
    mysqli_data_seek($events_result, 0);
}

// Fetch ONLY Confirmed reservations with pagination for the new section
$confirmed_page = isset($_GET['confirmed_page']) ? max(1, intval($_GET['confirmed_page'])) : 1;
$confirmed_limit = 12;
$confirmed_offset = ($confirmed_page - 1) * $confirmed_limit;

// Build query for Confirmed reservations with filters
$confirmed_where = "reservation_status = 'Confirmed'";
$confirmed_params = [];
$confirmed_types = "";

// Search filter for Confirmed section
if (isset($_GET['confirmed_search']) && !empty($_GET['confirmed_search'])) {
    $confirmed_search = "%" . $_GET['confirmed_search'] . "%";
    $confirmed_where .= " AND (customer_name LIKE ? OR event_name LIKE ? OR customer_email LIKE ?)";
    $confirmed_params[] = $confirmed_search;
    $confirmed_params[] = $confirmed_search;
    $confirmed_params[] = $confirmed_search;
    $confirmed_types .= "sss";
}

// Additional status filter for Confirmed section (for Reserved, Denied)
if (isset($_GET['confirmed_status']) && !empty($_GET['confirmed_status'])) {
    if ($_GET['confirmed_status'] === 'All') {
        // Show all including Confirmed
        $confirmed_where = "reservation_status IN ('Confirmed', 'Reserved', 'Denied')";
    } else {
        $confirmed_where = "reservation_status = ?";
        $confirmed_params[] = $_GET['confirmed_status'];
        $confirmed_types .= "s";
    }
}

// Date filters for Confirmed section
if (isset($_GET['confirmed_date_from']) && !empty($_GET['confirmed_date_from'])) {
    $confirmed_where .= " AND event_date >= ?";
    $confirmed_params[] = $_GET['confirmed_date_from'];
    $confirmed_types .= "s";
}

if (isset($_GET['confirmed_date_to']) && !empty($_GET['confirmed_date_to'])) {
    $confirmed_where .= " AND event_date <= ?";
    $confirmed_params[] = $_GET['confirmed_date_to'];
    $confirmed_types .= "s";
}

// Get total count for Confirmed reservations
$confirmed_count_sql = "SELECT COUNT(*) as total FROM event_reservations WHERE $confirmed_where";
$confirmed_count_stmt = $conn->prepare($confirmed_count_sql);
if ($confirmed_types) {
    $confirmed_count_stmt->bind_param($confirmed_types, ...$confirmed_params);
}
$confirmed_count_stmt->execute();
$confirmed_count_result = $confirmed_count_stmt->get_result();
$confirmed_total_rows = $confirmed_count_result->fetch_assoc()['total'];
$confirmed_total_pages = ceil($confirmed_total_rows / $confirmed_limit);

// Get Confirmed reservations data
$confirmed_sql = "SELECT * FROM event_reservations WHERE $confirmed_where ORDER BY event_date ASC, event_time ASC LIMIT ? OFFSET ?";
$confirmed_types .= "ii";
$confirmed_params[] = $confirmed_limit;
$confirmed_params[] = $confirmed_offset;

$confirmed_stmt = $conn->prepare($confirmed_sql);
if ($confirmed_types) {
    $confirmed_stmt->bind_param($confirmed_types, ...$confirmed_params);
}
$confirmed_stmt->execute();
$confirmed_result = $confirmed_stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en" data-theme="light">
<?php include '../header.php'; ?>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Event Reservation Management</title>
    <!-- SweetAlert -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
    
    <style>
        :root {
            --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --secondary-gradient: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            --success-gradient: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            --warning-gradient: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
            --glass-effect: rgba(255, 255, 255, 0.85);
        }

        .glass-effect {
            background: var(--glass-effect);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border-radius: 16px;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .stat-card {
            transition: all 0.3s ease;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1);
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.15);
        }

        .status-confirmed {
            background-color: #10b981;
            color: white;
        }

        .status-pending {
            background-color: #f59e0b;
            color: white;
        }

        .status-cancelled {
            background-color: #ef4444;
            color: white;
        }

        .modal-box {
            border-radius: 20px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
            max-width: 80vw;
        }

        .image-preview {
            max-height: 200px;
            object-fit: contain;
        }
        /* Dynamic clear button */
.search-clear-btn {
    position: absolute;
    right: 10px;
    top: 50%;
    transform: translateY(-50%);
    background: none;
    border: none;
    color: #9ca3af;
    cursor: pointer;
    padding: 2px;
    transition: color 0.2s;
    display: none;
}

.search-clear-btn:hover {
    color: #6b7280;
}

.search-container:has(input:not(:placeholder-shown)) .search-clear-btn {
    display: block;
}

/* Active filter badge */
.active-filters {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    margin-left: 8px;
    padding: 2px 8px;
    background-color: #e5e7eb;
    border-radius: 12px;
    font-size: 12px;
    color: #374151;
}
    </style>
</head>
<body class="bg-gray-50 min-h-screen">

<div class="flex h-screen">
    <!-- Sidebar -->
    <?php include '../sidebarr.php'; ?>

    <!-- Content Area -->
    <div class="flex flex-col flex-1 overflow-auto">
        <!-- Navbar -->
        <?php include '../navbar.php'; ?>

        <div class="container mx-auto px-4 py-8">
            <!-- Stats Cards -->
            <div class="glass-effect p-6 mb-8">
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
        <h2 class="text-2xl font-bold text-gray-800 flex items-center">
            <i data-lucide="chart-line" class="mr-3 text-blue-600"></i>
            Reservations Dashboard
        </h2>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
        <!-- Total Reservations -->
        <div class="stat-card bg-white text-black shadow-xl p-5 rounded-lg">
            <div class="flex justify-between items-start">
                <div>
                    <p class="text-sm font-medium text-[#F7B32B]">Total</p>
                    <h3 class="text-3xl font-bold mt-1"><?php echo $total_reservations; ?></h3>
                    <p class="text-xs opacity-70 mt-1">All reservations</p>
                </div>
                <div class="p-3 rounded-lg bg-[#001f54] text-[#F7B32B]">
                    <i data-lucide="layers" class="w-6 h-6"></i>
                </div>
            </div>
        </div>

        <!-- Confirmed -->
        <div class="stat-card bg-white text-black shadow-xl p-5 rounded-lg">
            <div class="flex justify-between items-start">
                <div>
                    <p class="text-sm font-medium text-[#F7B32B]">Confirmed</p>
                    <h3 class="text-3xl font-bold mt-1"><?php echo $confirmed_count; ?></h3>
                    <p class="text-xs opacity-70 mt-1">Approved events</p>
                </div>
                <div class="p-3 rounded-lg bg-[#001f54] text-[#F7B32B]">
                    <i data-lucide="check-circle" class="w-6 h-6"></i>
                </div>
            </div>
        </div>

        <!-- Under Review -->
        <div class="stat-card bg-white text-black shadow-xl p-5 rounded-lg">
            <div class="flex justify-between items-start">
                <div>
                    <p class="text-sm font-medium text-[#F7B32B]">Under Review</p>
                    <h3 class="text-3xl font-bold mt-1"><?php echo $under_review_count; ?></h3>
                    <p class="text-xs opacity-70 mt-1">Pending approval</p>
                </div>
                <div class="p-3 rounded-lg bg-[#001f54] text-[#F7B32B]">
                    <i data-lucide="clock" class="w-6 h-6"></i>
                </div>
            </div>
        </div>

        <!-- Reserved -->
        <div class="stat-card bg-white text-black shadow-xl p-5 rounded-lg">
            <div class="flex justify-between items-start">
                <div>
                    <p class="text-sm font-medium text-[#F7B32B]">Reserved</p>
                    <h3 class="text-3xl font-bold mt-1"><?php echo $reserved_count; ?></h3>
                    <p class="text-xs opacity-70 mt-1">Tentative bookings</p>
                </div>
                <div class="p-3 rounded-lg bg-[#001f54] text-[#F7B32B]">
                    <i data-lucide="bookmark" class="w-6 h-6"></i>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Second Row of Stats Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mt-6">
        <!-- Denied -->
        <div class="stat-card bg-white text-black shadow-xl p-5 rounded-lg">
            <div class="flex justify-between items-start">
                <div>
                    <p class="text-sm font-medium text-[#F7B32B]">Denied</p>
                    <h3 class="text-3xl font-bold mt-1"><?php echo $denied_count; ?></h3>
                    <p class="text-xs opacity-70 mt-1">Rejected requests</p>
                </div>
                <div class="p-3 rounded-lg bg-[#001f54] text-[#F7B32B]">
                    <i data-lucide="x-circle" class="w-6 h-6"></i>
                </div>
            </div>
        </div>

        <!-- Cancelled -->
        <div class="stat-card bg-white text-black shadow-xl p-5 rounded-lg">
            <div class="flex justify-between items-start">
                <div>
                    <p class="text-sm font-medium text-[#F7B32B]">Cancelled</p>
                    <h3 class="text-3xl font-bold mt-1"><?php echo $cancelled_count; ?></h3>
                    <p class="text-xs opacity-70 mt-1">Cancelled events</p>
                </div>
                <div class="p-3 rounded-lg bg-[#001f54] text-[#F7B32B]">
                    <i data-lucide="calendar-x" class="w-6 h-6"></i>
                </div>
            </div>
        </div>

        <!-- Today's Reservations -->
        <div class="stat-card bg-white text-black shadow-xl p-5 rounded-lg">
            <div class="flex justify-between items-start">
                <div>
                    <p class="text-sm font-medium text-[#F7B32B]">Today's</p>
                    <h3 class="text-3xl font-bold mt-1"><?php echo $today_count; ?></h3>
                    <p class="text-xs opacity-70 mt-1">Events today</p>
                </div>
                <div class="p-3 rounded-lg bg-[#001f54] text-[#F7B32B]">
                    <i data-lucide="calendar-check" class="w-6 h-6"></i>
                </div>
            </div>
        </div>

        <!-- For Compliance (if you want to keep this) -->
        <div class="stat-card bg-white text-black shadow-xl p-5 rounded-lg">
            <div class="flex justify-between items-start">
                <div>
                    <p class="text-sm font-medium text-[#F7B32B]">Requires Action</p>
                    <h3 class="text-3xl font-bold mt-1"><?php echo $under_review_count; ?></h3>
                    <p class="text-xs opacity-70 mt-1">Needs review</p>
                </div>
                <div class="p-3 rounded-lg bg-[#001f54] text-[#F7B32B]">
                    <i data-lucide="alert-circle" class="w-6 h-6"></i>
                </div>
            </div>
        </div>
    </div>
</div>

            <!-- Filter and Search Section -->
            <div class="glass-effect p-6 mb-6">
                <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-6">
    <h2 class="text-xl font-semibold text-gray-800">Event Reservations</h2>
    <div class="flex flex-col sm:flex-row gap-3 w-full md:w-auto">
        <!-- Search Bar with Clear Button -->
        <div class="join">
            <div class="relative">
                <input type="text" id="searchInput" placeholder="Search by name, event, or email..." 
                       class="input input-bordered join-item w-full sm:w-64 pr-10"
                       value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>"
                       onkeypress="if(event.key === 'Enter') applyFilters()">
                <!-- Clear Button (X) - Only shown when there's text -->
                <?php if (!empty($_GET['search'])): ?>
                <button type="button" 
                        onclick="clearSearch()" 
                        class="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-400 hover:text-gray-600 transition-colors">
                    <i data-lucide="x" class="w-4 h-4"></i>
                </button>
                <?php endif; ?>
            </div>
            <button class="btn btn-primary join-item" onclick="applyFilters()">
                <i data-lucide="search" class="w-4 h-4"></i>
            </button>
            <?php if (!empty($_GET['search']) || !empty($_GET['status']) || !empty($_GET['date_from']) || !empty($_GET['date_to'])): ?>
            <button class="btn btn-outline join-item" onclick="clearAllFilters()" title="Clear all filters">
                <i data-lucide="filter-x" class="w-4 h-4"></i>
                Clear
            </button>
            <?php endif; ?>
        </div>
        
        <!-- Filter Button -->
        <div class="dropdown dropdown-end">
            <label tabindex="0" class="btn btn-outline join-item">
                <i data-lucide="filter" class="w-4 h-4 mr-2"></i> Filter
            </label>
            <div tabindex="0" class="dropdown-content z-[1] card card-compact w-64 p-2 shadow bg-base-100">
                <div class="card-body">
                    <h3 class="card-title">Filters</h3>
                    
                    <!-- Status Filter -->
                    <div class="form-control">
                        <label class="label">
                            <span class="label-text">Status</span>
                        </label>
                        <select id="statusFilter" class="select select-bordered w-full">
                            <option value="">All Status</option>
                            <option value="Pending" <?php echo ($_GET['status'] ?? '') == 'Pending' ? 'selected' : ''; ?>>Pending</option>
                            <option value="Confirmed" <?php echo ($_GET['status'] ?? '') == 'Confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                            <option value="Cancelled" <?php echo ($_GET['status'] ?? '') == 'Cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                            <option value="For Compliance" <?php echo ($_GET['status'] ?? '') == 'For Compliance' ? 'selected' : ''; ?>>For Compliance</option>
                        </select>
                    </div>
                    
                    <!-- Date Range -->
                    <div class="form-control">
                        <label class="label">
                            <span class="label-text">Date From</span>
                        </label>
                        <input type="date" id="dateFrom" class="input input-bordered" 
                               value="<?php echo htmlspecialchars($_GET['date_from'] ?? ''); ?>">
                    </div>
                    
                    <div class="form-control">
                        <label class="label">
                            <span class="label-text">Date To</span>
                        </label>
                        <input type="date" id="dateTo" class="input input-bordered"
                               value="<?php echo htmlspecialchars($_GET['date_to'] ?? ''); ?>">
                    </div>
                    
                    <!-- Filter Status -->
                    <div class="mt-3 p-2 bg-base-200 rounded">
                        <div class="text-xs font-semibold mb-1">Active Filters:</div>
                        <div class="text-xs space-y-1">
                            <?php if (!empty($_GET['search'])): ?>
                            <div class="flex justify-between">
                                <span>Search:</span>
                                <span class="font-medium">"<?php echo htmlspecialchars($_GET['search']); ?>"</span>
                            </div>
                            <?php endif; ?>
                            <?php if (!empty($_GET['status'])): ?>
                            <div class="flex justify-between">
                                <span>Status:</span>
                                <span class="badge badge-sm"><?php echo htmlspecialchars($_GET['status']); ?></span>
                            </div>
                            <?php endif; ?>
                            <?php if (!empty($_GET['date_from'])): ?>
                            <div class="flex justify-between">
                                <span>From:</span>
                                <span><?php echo htmlspecialchars($_GET['date_from']); ?></span>
                            </div>
                            <?php endif; ?>
                            <?php if (!empty($_GET['date_to'])): ?>
                            <div class="flex justify-between">
                                <span>To:</span>
                                <span><?php echo htmlspecialchars($_GET['date_to']); ?></span>
                            </div>
                            <?php endif; ?>
                            <?php if (empty($_GET['search']) && empty($_GET['status']) && empty($_GET['date_from']) && empty($_GET['date_to'])): ?>
                            <div class="text-gray-500">No filters active</div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Action Buttons -->
                    <div class="card-actions justify-end mt-4">
                        <button class="btn btn-primary btn-sm" onclick="applyFilters()">Apply</button>
                        <button class="btn btn-ghost btn-sm" onclick="resetFilters()">Reset</button>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- New Event Button -->
        <label for="event-modal" class="btn btn-primary">
            <i data-lucide="plus" class="w-4 h-4 mr-2"></i> New Event
        </label>
    </div>
</div>

                <!-- Events Cards -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6" id="reservationsCards">
    <?php if ($events_result && mysqli_num_rows($events_result) > 0): ?>
        <?php while ($event = mysqli_fetch_assoc($events_result)): ?>
            <?php 
            // Format date and time
            $event_date = date('M j, Y', strtotime($event['event_date']));
            $event_time = date('g:i A', strtotime($event['event_time']));
            $created_date = date('M j, Y', strtotime($event['created_at']));
            
            // Determine status class and color
            $status_class = '';
            $status_color = '';
            switch ($event['reservation_status']) {
                case 'Confirmed':
                    $status_class = 'status-confirmed';
                    $status_color = 'bg-green-100 text-green-800 border-green-200';
                    $status_icon = 'check-circle';
                    break;
                case 'Under Review':
                case 'Pending':
                    $status_class = 'status-pending';
                    $status_color = 'bg-yellow-100 text-yellow-800 border-yellow-200';
                    $status_icon = 'clock';
                    break;
                case 'Denied':
                    $status_class = 'status-denied';
                    $status_color = 'bg-red-100 text-red-800 border-red-200';
                    $status_icon = 'x-circle';
                    break;
                case 'Reserved':
                    $status_class = 'status-reserved';
                    $status_color = 'bg-purple-100 text-purple-800 border-purple-200';
                    $status_icon = 'bookmark';
                    break;
                case 'Cancelled':
                    $status_class = 'status-cancelled';
                    $status_color = 'bg-gray-100 text-gray-800 border-gray-200';
                    $status_icon = 'calendar-x';
                    break;
                default:
                    $status_class = 'status-pending';
                    $status_color = 'bg-gray-100 text-gray-800 border-gray-200';
                    $status_icon = 'help-circle';
            }
            
            // Check if editable
            $is_editable = in_array($event['reservation_status'], ['Under Review', 'Pending', 'For Compliance']);
            ?>
            
            <div class="card bg-base-100 shadow-lg border border-gray-200 rounded-xl hover:shadow-xl transition-shadow duration-300" 
                 data-id="<?php echo $event['reservation_id']; ?>">
                <!-- Card Header -->
                <div class="card-body p-5">
                    <!-- Header with ID and Status -->
                    <div class="flex justify-between items-start mb-4">
                        <div>
                            <span class="font-mono text-sm text-gray-500">#<?php echo str_pad($event['reservation_id'], 5, '0', STR_PAD_LEFT); ?></span>
                            <h3 class="font-bold text-lg text-gray-800 mt-1"><?php echo htmlspecialchars($event['event_name']); ?></h3>
                        </div>
                        <div class="flex flex-col items-end">
                            <span class="badge <?php echo $status_color; ?> border px-3 py-1 rounded-full text-xs font-semibold flex items-center gap-1 mb-2">
                                <i data-lucide="<?php echo $status_icon; ?>" class="w-3 h-3"></i>
                                <?php echo $event['reservation_status']; ?>
                            </span>
                            <span class="text-xs text-gray-500">Created: <?php echo $created_date; ?></span>
                        </div>
                    </div>
                    
                    <!-- Customer Info -->
                    <div class="mb-4">
                        <div class="flex items-center gap-2 mb-2">
                            <div class="p-2 bg-blue-50 rounded-lg">
                                <i data-lucide="user" class="w-4 h-4 text-blue-600"></i>
                            </div>
                            <div>
                                <p class="font-medium text-gray-800"><?php echo htmlspecialchars($event['customer_name']); ?></p>
                                <p class="text-sm text-gray-500"><?php echo htmlspecialchars($event['customer_email']); ?></p>
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            <div class="p-2 bg-green-50 rounded-lg">
                                <i data-lucide="phone" class="w-4 h-4 text-green-600"></i>
                            </div>
                            <div>
                                <p class="text-sm text-gray-600"><?php echo htmlspecialchars($event['customer_phone']); ?></p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Event Details -->
                    <div class="grid grid-cols-2 gap-4 mb-4">
                        <div class="space-y-2">
                            <div class="flex items-center gap-2">
                                <i data-lucide="calendar" class="w-4 h-4 text-gray-400"></i>
                                <span class="text-sm font-medium text-gray-700">Date</span>
                            </div>
                            <p class="text-sm text-gray-600 pl-6"><?php echo $event_date; ?></p>
                        </div>
                        <div class="space-y-2">
                            <div class="flex items-center gap-2">
                                <i data-lucide="clock" class="w-4 h-4 text-gray-400"></i>
                                <span class="text-sm font-medium text-gray-700">Time</span>
                            </div>
                            <p class="text-sm text-gray-600 pl-6"><?php echo $event_time; ?></p>
                        </div>
                        <div class="space-y-2">
                            <div class="flex items-center gap-2">
                                <i data-lucide="users" class="w-4 h-4 text-gray-400"></i>
                                <span class="text-sm font-medium text-gray-700">Guests</span>
                            </div>
                            <p class="text-sm text-gray-600 pl-6"><?php echo $event['num_guests']; ?></p>
                        </div>
                        <div class="space-y-2">
                            <div class="flex items-center gap-2">
                                <i data-lucide="map-pin" class="w-4 h-4 text-gray-400"></i>
                                <span class="text-sm font-medium text-gray-700">Venue</span>
                            </div>
                            <p class="text-sm text-gray-600 pl-6 truncate"><?php echo htmlspecialchars($event['venue']); ?></p>
                        </div>
                    </div>
                    
                    <!-- Financial Info -->
                    <div class="bg-gray-50 rounded-lg p-3 mb-4">
                        <div class="flex justify-between items-center">
                            <div>
                                <p class="text-xs text-gray-500">Total Amount</p>
                                <p class="text-lg font-bold text-gray-800">₱<?php echo number_format($event['total_amount'], 2); ?></p>
                            </div>
                            <div class="text-right">
                                <p class="text-xs text-gray-500">Downpayment</p>
                                <p class="text-sm font-semibold text-green-600">₱<?php echo number_format($event['amount_paid'], 2); ?></p>
                            </div>
                        </div>
                        <div class="mt-2 flex items-center gap-2">
                            <i data-lucide="credit-card" class="w-3 h-3 text-gray-400"></i>
                            <span class="text-xs text-gray-500">Payment: </span>
                            <span class="text-xs font-medium <?php echo $event['payment_status'] === 'Paid' ? 'text-green-600' : 'text-yellow-600'; ?>">
                                <?php echo $event['payment_status']; ?>
                            </span>
                        </div>
                    </div>
                    
                    <!-- Actions Footer -->
                    <div class="card-actions flex justify-between items-center pt-4 border-t border-gray-100">
                        <div class="flex items-center gap-2">
                            <span class="text-xs text-gray-500">
                                <i data-lucide="package" class="w-3 h-3 inline mr-1"></i>
                                <?php echo htmlspecialchars($event['event_package']); ?>
                            </span>
                        </div>
                        <div class="flex gap-2">
                            <button class="btn btn-sm btn-outline view-btn" 
                                    data-id="<?php echo $event['reservation_id']; ?>">
                                <i data-lucide="eye" class="w-3 h-3 mr-1"></i> View
                            </button>
                            
                        </div>
                    </div>
                </div>
            </div>
        <?php endwhile; ?>
    <?php else: ?>
        <div class="col-span-full">
            <div class="empty-state text-center py-12 bg-base-100 rounded-xl border-2 border-dashed border-gray-200">
                <i data-lucide="calendar-plus" class="w-16 h-16 mx-auto mb-4 text-gray-300"></i>
                <h3 class="text-lg font-semibold text-gray-700 mb-2">No events found</h3>
                <p class="text-gray-500 mb-6">Create your first event reservation to get started</p>
                <label for="event-modal" class="btn btn-primary">
                    <i data-lucide="plus" class="w-4 h-4 mr-2"></i> Create New Event
                </label>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- Pagination -->
<?php if ($total_pages > 1): ?>
<div class="flex justify-center mt-8">
    <div class="join">
        <?php if ($page > 1): ?>
            <a href="?page=<?php echo $page-1; ?><?php 
                echo isset($_GET['search']) ? '&search='.urlencode($_GET['search']) : '';
                echo isset($_GET['status']) ? '&status='.urlencode($_GET['status']) : '';
                echo isset($_GET['date_from']) ? '&date_from='.urlencode($_GET['date_from']) : '';
                echo isset($_GET['date_to']) ? '&date_to='.urlencode($_GET['date_to']) : '';
            ?>" class="btn btn-outline join-item">
                <i data-lucide="chevron-left" class="w-4 h-4"></i> Previous
            </a>
        <?php endif; ?>
        
        <?php 
        // Show limited pagination
        $start_page = max(1, $page - 2);
        $end_page = min($total_pages, $page + 2);
        
        for ($i = $start_page; $i <= $end_page; $i++): ?>
            <?php if ($i == $page): ?>
                <button class="btn btn-primary join-item"><?php echo $i; ?></button>
            <?php else: ?>
                <a href="?page=<?php echo $i; ?><?php 
                    echo isset($_GET['search']) ? '&search='.urlencode($_GET['search']) : '';
                    echo isset($_GET['status']) ? '&status='.urlencode($_GET['status']) : '';
                    echo isset($_GET['date_from']) ? '&date_from='.urlencode($_GET['date_from']) : '';
                    echo isset($_GET['date_to']) ? '&date_to='.urlencode($_GET['date_to']) : '';
                ?>" class="btn btn-outline join-item"><?php echo $i; ?></a>
            <?php endif; ?>
        <?php endfor; ?>
        
        <?php if ($page < $total_pages): ?>
            <a href="?page=<?php echo $page+1; ?><?php 
                echo isset($_GET['search']) ? '&search='.urlencode($_GET['search']) : '';
                echo isset($_GET['status']) ? '&status='.urlencode($_GET['status']) : '';
                echo isset($_GET['date_from']) ? '&date_from='.urlencode($_GET['date_from']) : '';
                echo isset($_GET['date_to']) ? '&date_to='.urlencode($_GET['date_to']) : '';
            ?>" class="btn btn-outline join-item">
                Next <i data-lucide="chevron-right" class="w-4 h-4"></i>
            </a>
        <?php endif; ?>
    </div>
    
    <!-- Page info -->
    <div class="ml-4 flex items-center text-sm text-gray-500">
        <span>Page <?php echo $page; ?> of <?php echo $total_pages; ?></span>
        <span class="mx-2">•</span>
        <span><?php echo $total_rows; ?> total reservations</span>
    </div>
</div>
<?php endif; ?>
            </div>

            <!-- Upcoming Events -->
            <div class="glass-effect p-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                    <i data-lucide="calendar-day" class="mr-2 text-primary"></i>
                    Upcoming Events
                </h3>
                
                <?php if (!empty($upcoming_events)): ?>
                    <div class="event-list space-y-4">
                        <?php foreach ($upcoming_events as $event): ?>
                            <div class="event-item p-4 rounded-lg bg-base-100 border border-base-300">
                                <div class="flex justify-between items-start">
                                    <div>
                                        <h4 class="font-semibold"><?php echo htmlspecialchars($event['event_name']); ?></h4>
                                        <p class="text-sm text-gray-500">
                                            <?php 
                                            $event_date = date('M j, Y', strtotime($event['event_date']));
                                            $event_time = date('g:i A', strtotime($event['event_time']));
                                            echo "$event_date at $event_time";
                                            ?>
                                        </p>
                                        <p class="text-sm mt-1"><?php echo $event['num_guests']; ?> guests</p>
                                    </div>
                                    <?php 
                                    $status_class = '';
                                    switch ($event['reservation_status']) {
                                        case 'Confirmed':
                                            $status_class = 'status-confirmed';
                                            break;
                                        case 'Pending':
                                            $status_class = 'status-pending';
                                            break;
                                        case 'Cancelled':
                                            $status_class = 'status-cancelled';
                                            break;
                                    }
                                    ?>
                                    <span class="badge <?php echo $status_class; ?> text-xs">
                                        <?php echo $event['reservation_status']; ?>
                                    </span>
                                </div>
                                <div class="mt-2 flex justify-between items-center">
                                    <span class="text-xs text-gray-500"><?php echo htmlspecialchars($event['venue']); ?></span>
                                    <button class="btn btn-xs btn-outline view-btn" 
                                            data-id="<?php echo $event['reservation_id']; ?>">
                                        <i data-lucide="eye" class="w-3 h-3"></i> Details
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="empty-state text-center py-8">
                        <i data-lucide="calendar-day" class="w-12 h-12 mx-auto mb-3 opacity-30"></i>
                        <p class="text-gray-500">No upcoming events</p>
                    </div>
                <?php endif; ?>
            </div>

            
<!-- =========================================== -->
<!-- CONFIRMED RESERVATIONS MANAGEMENT SECTION -->
<!-- =========================================== -->
<div class="glass-effect p-6 mt-8">
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4">
        <div>
            <h2 class="text-xl font-semibold text-gray-800 flex items-center">
                <i data-lucide="check-circle" class="mr-2 text-green-600"></i>
                Confirmed Reservations
            </h2>
            <p class="text-sm text-gray-500 mt-1">Manage confirmed events, request facilities, and handle compliance</p>
        </div>
        
        <div class="flex flex-col sm:flex-row gap-3 w-full md:w-auto">
            <!-- Search Bar for Confirmed -->
            <div class="join">
                <div class="relative">
                    <input type="text" id="confirmedSearchInput" placeholder="Search confirmed events..." 
                           class="input input-bordered join-item w-full sm:w-64 pr-10"
                           value="<?php echo htmlspecialchars($_GET['confirmed_search'] ?? ''); ?>"
                           onkeypress="if(event.key === 'Enter') applyConfirmedFilters()">
                    <?php if (!empty($_GET['confirmed_search'])): ?>
                    <button type="button" 
                            onclick="clearConfirmedSearch()" 
                            class="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-400 hover:text-gray-600 transition-colors">
                        <i data-lucide="x" class="w-4 h-4"></i>
                    </button>
                    <?php endif; ?>
                </div>
                <button class="btn btn-primary join-item" onclick="applyConfirmedFilters()">
                    <i data-lucide="search" class="w-4 h-4"></i>
                </button>
            </div>
            
            <!-- Filter for Confirmed -->
            <div class="dropdown dropdown-end">
                <label tabindex="0" class="btn btn-outline join-item">
                    <i data-lucide="filter" class="w-4 h-4 mr-2"></i> Filter
                </label>
                <div tabindex="0" class="dropdown-content z-[1] card card-compact w-64 p-2 shadow bg-base-100">
                    <div class="card-body">
                        <h3 class="card-title">Confirmed Filters</h3>
                        
                        <!-- Status Filter -->
                        <div class="form-control">
                            <label class="label">
                                <span class="label-text">Show</span>
                            </label>
                            <select id="confirmedStatusFilter" class="select select-bordered w-full">
                                <option value="Confirmed" <?php echo ($_GET['confirmed_status'] ?? 'Confirmed') == 'Confirmed' ? 'selected' : ''; ?>>Confirmed Only</option>
                                <option value="All" <?php echo ($_GET['confirmed_status'] ?? '') == 'All' ? 'selected' : ''; ?>>All (Confirmed + Reserved + Denied)</option>
                                <option value="Reserved" <?php echo ($_GET['confirmed_status'] ?? '') == 'Reserved' ? 'selected' : ''; ?>>Reserved Only</option>
                                <option value="Denied" <?php echo ($_GET['confirmed_status'] ?? '') == 'Denied' ? 'selected' : ''; ?>>Denied Only</option>
                            </select>
                        </div>
                        
                        <!-- Date Range -->
                        <div class="form-control">
                            <label class="label">
                                <span class="label-text">Date From</span>
                            </label>
                            <input type="date" id="confirmedDateFrom" class="input input-bordered" 
                                   value="<?php echo htmlspecialchars($_GET['confirmed_date_from'] ?? ''); ?>">
                        </div>
                        
                        <div class="form-control">
                            <label class="label">
                                <span class="label-text">Date To</span>
                            </label>
                            <input type="date" id="confirmedDateTo" class="input input-bordered"
                                   value="<?php echo htmlspecialchars($_GET['confirmed_date_to'] ?? ''); ?>">
                        </div>
                        
                        <!-- Action Buttons -->
                        <div class="card-actions justify-end mt-4">
                            <button class="btn btn-primary btn-sm" onclick="applyConfirmedFilters()">Apply</button>
                            <button class="btn btn-ghost btn-sm" onclick="resetConfirmedFilters()">Reset</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Confirmed Reservations Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4 mb-6" id="confirmedReservationsCards">
        <?php if ($confirmed_result && mysqli_num_rows($confirmed_result) > 0): ?>
            <?php while ($confirmed = mysqli_fetch_assoc($confirmed_result)): ?>
                <?php 
                // Format date and time
                $event_date = date('M j, Y', strtotime($confirmed['event_date']));
                $event_time = date('g:i A', strtotime($confirmed['event_time']));
                $days_remaining = floor((strtotime($confirmed['event_date']) - time()) / (60 * 60 * 24));
                
                // Status colors for confirmed section
                $status_bg = [
                    'Confirmed' => 'bg-green-50 border-green-200',
                    'Reserved' => 'bg-purple-50 border-purple-200',
                    'Denied' => 'bg-red-50 border-red-200',
                ];
                
                $status_icon = [
                    'Confirmed' => 'check-circle',
                    'Reserved' => 'bookmark',
                    'Denied' => 'x-circle',
                ];
                
                $status_color = $status_bg[$confirmed['reservation_status']] ?? 'bg-gray-50 border-gray-200';
                $status_ic = $status_icon[$confirmed['reservation_status']] ?? 'help-circle';
                ?>
                
                <div class="card <?php echo $status_color; ?> border rounded-lg p-4 hover:shadow-md transition-shadow duration-300" 
                     data-id="<?php echo $confirmed['reservation_id']; ?>">
                    <!-- Card Header -->
                    <div class="flex justify-between items-start mb-3">
                        <div>
                            <h4 class="font-bold text-gray-800 truncate"><?php echo htmlspecialchars($confirmed['event_name']); ?></h4>
                            <p class="text-sm text-gray-500"><?php echo htmlspecialchars($confirmed['customer_name']); ?></p>
                        </div>
                        <span class="text-xs font-semibold px-2 py-1 rounded-full flex items-center gap-1">
                            <i data-lucide="<?php echo $status_ic; ?>" class="w-3 h-3"></i>
                            <?php echo $confirmed['reservation_status']; ?>
                        </span>
                    </div>
                    
                    <!-- Event Details -->
                    <div class="space-y-2 mb-3">
                        <div class="flex items-center text-sm">
                            <i data-lucide="calendar" class="w-3 h-3 text-gray-400 mr-2"></i>
                            <span class="text-gray-600"><?php echo $event_date; ?></span>
                            <span class="mx-2">•</span>
                            <span class="text-gray-600"><?php echo $event_time; ?></span>
                        </div>
                        <div class="flex items-center text-sm">
                            <i data-lucide="users" class="w-3 h-3 text-gray-400 mr-2"></i>
                            <span class="text-gray-600"><?php echo $confirmed['num_guests']; ?> guests</span>
                        </div>
                        <div class="flex items-center text-sm">
                            <i data-lucide="map-pin" class="w-3 h-3 text-gray-400 mr-2"></i>
                            <span class="text-gray-600 truncate"><?php echo htmlspecialchars($confirmed['venue']); ?></span>
                        </div>
                    </div>
                    
                    <!-- Days Remaining & Amount -->
                    <div class="flex justify-between items-center mb-3">
                        <div>
                            <span class="text-xs font-medium <?php echo $days_remaining < 7 ? 'text-red-600' : 'text-green-600'; ?>">
                                <?php echo $days_remaining > 0 ? $days_remaining . ' days left' : 'Today'; ?>
                            </span>
                        </div>
                        <div class="text-right">
                            <p class="text-xs text-gray-500">Total</p>
                            <p class="text-sm font-bold text-gray-800">₱<?php echo number_format($confirmed['total_amount'], 2); ?></p>
                        </div>
                    </div>
                    
                    <!-- Actions -->
                    <div class="flex justify-between items-center pt-3 border-t border-gray-200">
                        <span class="text-xs text-gray-500">#<?php echo str_pad($confirmed['reservation_id'], 5, '0', STR_PAD_LEFT); ?></span>
                        <div class="flex gap-2">
                            <button class="btn btn-xs btn-outline confirmed-view-btn" 
                                    data-id="<?php echo $confirmed['reservation_id']; ?>">
                                <i data-lucide="eye" class="w-3 h-3"></i>
                            </button>
                            <?php if ($confirmed['reservation_status'] === 'Confirmed'): ?>
                            <button class="btn btn-xs btn-primary request-facility-btn" 
                                    data-id="<?php echo $confirmed['reservation_id']; ?>">
                                <i data-lucide="package" class="w-3 h-3"></i>
                            </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="col-span-full">
                <div class="empty-state text-center py-12 bg-base-100 rounded-xl border-2 border-dashed border-gray-200">
                    <i data-lucide="calendar-check" class="w-16 h-16 mx-auto mb-4 text-gray-300"></i>
                    <h3 class="text-lg font-semibold text-gray-700 mb-2">No confirmed reservations found</h3>
                    <p class="text-gray-500 mb-6">
                        <?php if (!empty($_GET['confirmed_search']) || !empty($_GET['confirmed_status']) || !empty($_GET['confirmed_date_from']) || !empty($_GET['confirmed_date_to'])): ?>
                        Try changing your filters
                        <?php else: ?>
                        All confirmed events will appear here
                        <?php endif; ?>
                    </p>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Pagination for Confirmed -->
    <?php if ($confirmed_total_pages > 1): ?>
    <div class="flex justify-center mt-6">
        <div class="join">
            <?php if ($confirmed_page > 1): ?>
                <a href="?confirmed_page=<?php echo $confirmed_page-1; ?><?php 
                    echo isset($_GET['confirmed_search']) ? '&confirmed_search='.urlencode($_GET['confirmed_search']) : '';
                    echo isset($_GET['confirmed_status']) ? '&confirmed_status='.urlencode($_GET['confirmed_status']) : '';
                    echo isset($_GET['confirmed_date_from']) ? '&confirmed_date_from='.urlencode($_GET['confirmed_date_from']) : '';
                    echo isset($_GET['confirmed_date_to']) ? '&confirmed_date_to='.urlencode($_GET['confirmed_date_to']) : '';
                    // Keep main filters
                    echo isset($_GET['page']) ? '&page='.urlencode($_GET['page']) : '';
                    echo isset($_GET['search']) ? '&search='.urlencode($_GET['search']) : '';
                    echo isset($_GET['status']) ? '&status='.urlencode($_GET['status']) : '';
                    echo isset($_GET['date_from']) ? '&date_from='.urlencode($_GET['date_from']) : '';
                    echo isset($_GET['date_to']) ? '&date_to='.urlencode($_GET['date_to']) : '';
                ?>" class="btn btn-outline join-item">
                    <i data-lucide="chevron-left" class="w-4 h-4"></i>
                </a>
            <?php endif; ?>
            
            <?php 
            $confirmed_start_page = max(1, $confirmed_page - 2);
            $confirmed_end_page = min($confirmed_total_pages, $confirmed_page + 2);
            
            for ($i = $confirmed_start_page; $i <= $confirmed_end_page; $i++): ?>
                <?php if ($i == $confirmed_page): ?>
                    <button class="btn btn-primary join-item"><?php echo $i; ?></button>
                <?php else: ?>
                    <a href="?confirmed_page=<?php echo $i; ?><?php 
                        echo isset($_GET['confirmed_search']) ? '&confirmed_search='.urlencode($_GET['confirmed_search']) : '';
                        echo isset($_GET['confirmed_status']) ? '&confirmed_status='.urlencode($_GET['confirmed_status']) : '';
                        echo isset($_GET['confirmed_date_from']) ? '&confirmed_date_from='.urlencode($_GET['confirmed_date_from']) : '';
                        echo isset($_GET['confirmed_date_to']) ? '&confirmed_date_to='.urlencode($_GET['confirmed_date_to']) : '';
                        // Keep main filters
                        echo isset($_GET['page']) ? '&page='.urlencode($_GET['page']) : '';
                        echo isset($_GET['search']) ? '&search='.urlencode($_GET['search']) : '';
                        echo isset($_GET['status']) ? '&status='.urlencode($_GET['status']) : '';
                        echo isset($_GET['date_from']) ? '&date_from='.urlencode($_GET['date_from']) : '';
                        echo isset($_GET['date_to']) ? '&date_to='.urlencode($_GET['date_to']) : '';
                    ?>" class="btn btn-outline join-item"><?php echo $i; ?></a>
                <?php endif; ?>
            <?php endfor; ?>
            
            <?php if ($confirmed_page < $confirmed_total_pages): ?>
                <a href="?confirmed_page=<?php echo $confirmed_page+1; ?><?php 
                    echo isset($_GET['confirmed_search']) ? '&confirmed_search='.urlencode($_GET['confirmed_search']) : '';
                    echo isset($_GET['confirmed_status']) ? '&confirmed_status='.urlencode($_GET['confirmed_status']) : '';
                    echo isset($_GET['confirmed_date_from']) ? '&confirmed_date_from='.urlencode($_GET['confirmed_date_from']) : '';
                    echo isset($_GET['confirmed_date_to']) ? '&confirmed_date_to='.urlencode($_GET['confirmed_date_to']) : '';
                    // Keep main filters
                    echo isset($_GET['page']) ? '&page='.urlencode($_GET['page']) : '';
                    echo isset($_GET['search']) ? '&search='.urlencode($_GET['search']) : '';
                    echo isset($_GET['status']) ? '&status='.urlencode($_GET['status']) : '';
                    echo isset($_GET['date_from']) ? '&date_from='.urlencode($_GET['date_from']) : '';
                    echo isset($_GET['date_to']) ? '&date_to='.urlencode($_GET['date_to']) : '';
                ?>" class="btn btn-outline join-item">
                    <i data-lucide="chevron-right" class="w-4 h-4"></i>
                </a>
            <?php endif; ?>
        </div>
        
        <!-- Page info -->
        <div class="ml-4 flex items-center text-sm text-gray-500">
            <span>Page <?php echo $confirmed_page; ?> of <?php echo $confirmed_total_pages; ?></span>
            <span class="mx-2">•</span>
            <span><?php echo $confirmed_total_rows; ?> confirmed reservations</span>
        </div>
    </div>
    <?php endif; ?>
</div>
        </div>
    </div>
</div>



<!-- Create Event Modal -->
<input type="checkbox" id="event-modal" class="modal-toggle" />
<div class="modal">
  <div class="modal-box max-w-5xl rounded-lg shadow-xl p-8 bg-base-100">
    <!-- Modal Header -->
    <div class="flex justify-between items-center mb-6">
      <h3 class="font-bold text-2xl text-primary">✨ New Event Reservation</h3>
      <div class="flex gap-2">
        <label for="event-modal" class="btn btn-sm btn-circle btn-ghost">✕</label>
      </div>
    </div>
    
    <!-- Form -->
    <form id="eventForm" action="sub-modules/add_event.php" method="POST" enctype="multipart/form-data" class="space-y-6">
      
      <!-- Customer Info -->
      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div>
          <label class="label"><span class="label-text font-medium">Customer Name *</span></label>
          <input type="text" name="customer_name" class="input input-bordered w-full rounded-lg" required>
        </div>
        <div>
          <label class="label"><span class="label-text font-medium">Customer Email *</span></label>
          <input type="email" name="customer_email" class="input input-bordered w-full rounded-lg" required>
        </div>
      </div>
      
      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div>
          <label class="label"><span class="label-text font-medium">Customer Phone *</span></label>
          <input type="text" name="customer_phone" class="input input-bordered w-full rounded-lg" required>
        </div>
        <div>
          <label class="label"><span class="label-text font-medium">Event Name *</span></label>
          <input type="text" name="event_name" class="input input-bordered w-full rounded-lg" required>
        </div>
      </div>
      
      <!-- Event Info with Prices -->
      <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div>
          <label class="label"><span class="label-text font-medium">Event Type *</span></label>
          <select name="event_type" id="event_type" class="select select-bordered w-full rounded-lg" required>
            <option value="" disabled selected>Select event type</option>
            <option value="Wedding" data-price="500">Wedding (₱500)</option>
            <option value="Birthday" data-price="300">Birthday Party (₱300)</option>
            <option value="Corporate" data-price="400">Corporate Event (₱400)</option>
            <option value="Conference" data-price="600">Conference (₱600)</option>
            <option value="Seminar" data-price="350">Seminar (₱350)</option>
            <option value="Anniversary" data-price="450">Anniversary (₱450)</option>
            <option value="Baby Shower" data-price="250">Baby Shower (₱250)</option>
            <option value="Reunion" data-price="200">Family Reunion (₱200)</option>
            <option value="Gala" data-price="800">Gala Dinner (₱800)</option>
            <option value="Product Launch" data-price="700">Product Launch (₱700)</option>
            <option value="Other" data-price="350">Other (₱350)</option>
          </select>
        </div>
        <div>
          <label class="label"><span class="label-text font-medium">Venue *</span></label>
          <select name="venue" id="venue" class="select select-bordered w-full rounded-lg" required>
            <option value="" disabled selected>Select venue</option>
            <option value="Conference Hall A" data-price="600">Conference Hall A (₱600)</option>
            <option value="Conference Hall B" data-price="500">Conference Hall B (₱500)</option>
            <option value="Executive Room" data-price="400">Executive Room (₱400)</option>
            <option value="Main Restaurant" data-price="550">Main Restaurant (₱550)</option>
            <option value="Private Dining" data-price="450">Private Dining Room (₱450)</option>
          </select>
        </div>
        <div>
          <label class="label"><span class="label-text font-medium">Event Package *</span></label>
          <select name="event_package" id="event_package" class="select select-bordered w-full rounded-lg" required>
            <option value="" disabled selected>Select package</option>
            <option value="Basic" data-price="50">Basic Package (₱50/guest)</option>
            <option value="Standard" data-price="75">Standard Package (₱75/guest)</option>
            <option value="Premium" data-price="100">Premium Package (₱100/guest)</option>
            <option value="Platinum" data-price="150">Platinum Package (₱150/guest)</option>
            <option value="Corporate Basic" data-price="60">Corporate Basic (₱60/guest)</option>
            <option value="Corporate Premium" data-price="90">Corporate Premium (₱90/guest)</option>
            <option value="Wedding Essential" data-price="80">Wedding Essential (₱80/guest)</option>
            <option value="Wedding Deluxe" data-price="120">Wedding Deluxe (₱120/guest)</option>
            <option value="Birthday Basic" data-price="40">Birthday Basic (₱40/guest)</option>
            <option value="Birthday Celebration" data-price="65">Birthday Celebration (₱65/guest)</option>
            <option value="Custom" data-price="0">Custom Package (₱0/guest)</option>
          </select>
        </div>
      </div>
      
      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div class="space-y-6">
          <div>
            <label class="label"><span class="label-text font-medium">Number of Guests *</span></label>
            <input type="number" name="num_guests" id="num_guests" class="input input-bordered w-full rounded-lg" required min="1">
          </div>
          
          <div>
            <label class="label"><span class="label-text font-medium">Mode of Payment</span></label>
            <select name="MOP" class="select select-bordered w-full rounded-lg">
              <option value="Cash" selected>Cash</option>
              <option value="Credit Card">Credit Card</option>
              <option value="Bank Transfer">Bank Transfer</option>
              <option value="GCash">GCash</option>
              <option value="PayPal">PayPal</option>
            </select>
          </div>
          
          <div>
            <label class="label"><span class="label-text font-medium">Valid ID Upload</span></label>
            <input type="file" name="valid_id" class="file-input file-input-bordered w-full rounded-lg" accept="image/*">
            <div class="text-xs text-gray-500 mt-2">Upload a clear image of valid ID (JPG, PNG, GIF)</div>
          </div>
        </div>
        
        <div class="bg-blue-50 p-4 rounded-lg">
          <h4 class="font-bold mb-4">Pricing Summary</h4>
          <div class="flex justify-between items-center mb-2">
            <span class="font-medium">Event Type:</span>
            <span id="event_type_price">₱0.00</span>
          </div>
          <div class="flex justify-between items-center mb-2">
            <span class="font-medium">Venue:</span>
            <span id="venue_price">₱0.00</span>
          </div>
          <div class="flex justify-between items-center mb-2">
            <span class="font-medium">Package (<span id="guest_count">0</span> guests):</span>
            <span id="package_price">₱0.00</span>
          </div>
          <div class="border-t pt-2 mt-2 flex justify-between items-center font-bold text-lg">
            <span>Total Amount:</span>
            <span id="total_amount">₱0.00</span>
          </div>
          <div class="flex justify-between items-center mt-2 text-sm">
            <span>20% Downpayment:</span>
            <span id="downpayment">₱0.00</span>
          </div>
        </div>
      </div>
      
      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div>
          <label class="label"><span class="label-text font-medium">Event Date *</span></label>
          <input type="date" name="event_date" class="input input-bordered w-full rounded-lg" required>
        </div>
        <div>
          <label class="label"><span class="label-text font-medium">Event Time *</span></label>
          <input type="time" name="event_time" class="input input-bordered w-full rounded-lg" required>
        </div>
      </div>
      
      <div>
        <label class="label"><span class="label-text font-medium">Special Requests</span></label>
        <textarea name="special_requests" class="textarea textarea-bordered w-full rounded-lg" rows="3" placeholder="Any dietary restrictions, setup preferences, or additional notes..."></textarea>
      </div>
      
      <!-- Hidden fields to store calculated values -->
      <input type="hidden" name="calculated_total" id="calculated_total" value="0">
      
      <div class="modal-action flex justify-end gap-4 pt-4">
        <button type="submit" class="btn btn-primary px-6 rounded-lg">
          <i data-lucide="save" class="w-4 h-4 mr-2"></i> Save Reservation
        </button>
        <label for="event-modal" class="btn btn-ghost rounded-lg">Cancel</label>
      </div>
    </form>
  </div>
</div>

<!-- View Modal -->
<input type="checkbox" id="view-modal" class="modal-toggle" />
<div class="modal">
  <div class="modal-box max-w-6xl">
    <div class="flex justify-between items-center mb-6">
      <h3 class="text-2xl font-bold">Reservation Details</h3>
      <label for="view-modal" class="btn btn-sm btn-circle btn-ghost">✕</label>
    </div>
    
    <div id="reservationDetails">
      <!-- Details will be loaded here -->
    </div>
    
    <div class="modal-action flex justify-between">
      <div class="flex gap-2">
        <button class="btn btn-success" onclick="updateStatus('Confirmed')">
          <i data-lucide="check" class="w-4 h-4 mr-2"></i> Approve
        </button>
        <button class="btn btn-error" onclick="updateStatus('Cancelled')">
          <i data-lucide="x" class="w-4 h-4 mr-2"></i> Reject
        </button>
        <button class="btn btn-warning" onclick="showComplianceModal()">
          <i data-lucide="file-text" class="w-4 h-4 mr-2"></i> For Compliance
        </button>
      </div>
      <label for="view-modal" class="btn btn-ghost">Close</label>
    </div>
  </div>
</div>

<!-- Compliance Notes Modal -->
<input type="checkbox" id="compliance-modal" class="modal-toggle" />
<div class="modal">
  <div class="modal-box">
    <h3 class="font-bold text-lg">Add Compliance Notes</h3>
    <div class="form-control mt-4">
      <label class="label">
        <span class="label-text">Notes</span>
      </label>
      <textarea id="complianceNotes" class="textarea textarea-bordered w-full" rows="4" 
                placeholder="Enter compliance notes..."></textarea>
    </div>
    <div class="modal-action">
      <button class="btn btn-primary" onclick="saveComplianceNotes()">Save Notes</button>
      <label for="compliance-modal" class="btn btn-ghost">Cancel</label>
    </div>
  </div>
</div>

<!-- =========================================== -->
<!-- CONFIRMED RESERVATION VIEW MODAL -->
<!-- =========================================== -->
<input type="checkbox" id="confirmed-view-modal" class="modal-toggle" />
<div class="modal">
  <div class="modal-box max-w-6xl">
    <div class="flex justify-between items-center mb-6">
      <h3 class="text-2xl font-bold">Confirmed Reservation Details</h3>
      <label for="confirmed-view-modal" class="btn btn-sm btn-circle btn-ghost">✕</label>
    </div>
    
    <div id="confirmedReservationDetails">
      <!-- Details will be loaded here -->
    </div>
    
    <div class="modal-action flex justify-between">
      <div class="flex gap-2">
        <button class="btn btn-success" onclick="requestFacility()">
          <i data-lucide="package" class="w-4 h-4 mr-2"></i> Request Facility
        </button>
      
      </div>
      <label for="confirmed-view-modal" class="btn btn-ghost">Close</label>
    </div>
  </div>
</div>

<!-- =========================================== -->
<!-- REQUEST FACILITY MODAL -->
<!-- =========================================== -->
<input type="checkbox" id="request-facility-modal" class="modal-toggle" />
<div class="modal">
  <div class="modal-box">
    <h3 class="font-bold text-lg">Request Facility Setup</h3>
    <div class="form-control mt-4">
      <label class="label">
        <span class="label-text">Facility Requirements</span>
      </label>
      <textarea id="facilityRequirements" class="textarea textarea-bordered w-full" rows="4" 
                placeholder="Describe the facility setup requirements..."></textarea>
    </div>
    <div class="form-control mt-4">
      <label class="label">
        <span class="label-text">Special Equipment Needed</span>
      </label>
      <textarea id="specialEquipment" class="textarea textarea-bordered w-full" rows="3" 
                placeholder="List any special equipment or setup needed..."></textarea>
    </div>
    <div class="form-control mt-4">
      <label class="label">
        <span class="label-text">Setup Time Required</span>
      </label>
      <input type="text" id="setupTime" class="input input-bordered w-full" 
             placeholder="e.g., 2 hours before event">
    </div>
    <div class="modal-action">
      <button class="btn btn-primary" onclick="submitFacilityRequest()">Submit Request</button>
      <label for="request-facility-modal" class="btn btn-ghost">Cancel</label>
    </div>
  </div>
</div>

<!-- =========================================== -->
<!-- CONFIRMED COMPLIANCE MODAL -->
<!-- =========================================== -->
<input type="checkbox" id="confirmed-compliance-modal" class="modal-toggle" />
<div class="modal">
  <div class="modal-box">
    <h3 class="font-bold text-lg">Compliance Notes for Confirmed Reservation</h3>
    <div class="form-control mt-4">
      <label class="label">
        <span class="label-text">Compliance Issue</span>
      </label>
      <textarea id="confirmedComplianceNotes" class="textarea textarea-bordered w-full" rows="4" 
                placeholder="Describe the compliance issue..."></textarea>
    </div>
    <div class="form-control mt-4">
      <label class="label">
        <span class="label-text">Action Required</span>
      </label>
      <select id="complianceAction" class="select select-bordered w-full">
        <option value="customer_followup">Customer Follow-up Required</option>
        <option value="documentation">Additional Documentation Needed</option>
        <option value="payment">Payment Issue</option>
        <option value="venue">Venue Conflict</option>
        <option value="other">Other</option>
      </select>
    </div>
    <div class="modal-action">
      <button class="btn btn-primary" onclick="submitConfirmedCompliance()">Submit</button>
      <label for="confirmed-compliance-modal" class="btn btn-ghost">Cancel</label>
    </div>
  </div>
</div>
<script>
// Initialize Lucide icons
lucide.createIcons();

let currentReservationId = null;
let currentReservationStatus = null;
let currentReservationEditable = false;

// Apply filters
function applyFilters() {
    const search = document.getElementById('searchInput').value;
    const status = document.getElementById('statusFilter').value;
    const dateFrom = document.getElementById('dateFrom').value;
    const dateTo = document.getElementById('dateTo').value;
    
    let url = '?';
    if (search) url += `&search=${encodeURIComponent(search)}`;
    if (status) url += `&status=${encodeURIComponent(status)}`;
    if (dateFrom) url += `&date_from=${encodeURIComponent(dateFrom)}`;
    if (dateTo) url += `&date_to=${encodeURIComponent(dateTo)}`;
    
    window.location.href = url;
}

// Reset filters
function resetFilters() {
    window.location.href = window.location.pathname;
}

// View reservation details
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.view-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            currentReservationId = this.dataset.id;
            loadReservationDetails(currentReservationId);
        });
    });
    
    // Delete reservation
    document.querySelectorAll('.delete-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const id = this.dataset.id;
            deleteReservation(id);
        });
    });
    
    // Price calculation for new event form
    setupPriceCalculation();
    
    // Form submission
    const eventForm = document.getElementById('eventForm');
    if (eventForm) {
        eventForm.addEventListener('submit', function(e) {
            e.preventDefault();
            submitEventForm(this);
        });
    }
});

function loadReservationDetails(id) {
    if (!id) {
        console.error('No reservation ID provided');
        Swal.fire('Error', 'No reservation ID provided', 'error');
        return;
    }
    
    fetch(`sub-modules/reservations.php?action=get_reservation&id=${id}`)
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! Status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                currentReservationStatus = data.data.reservation_status;
                currentReservationEditable = data.data.is_editable || false;
                displayReservationDetails(data.data);
                updateActionButtons(data.data.is_editable);
                document.getElementById('view-modal').checked = true;
            } else {
                Swal.fire('Error', data.message || 'Failed to load reservation details', 'error');
            }
        })
        .catch(error => {
            console.error('Error loading reservation:', error);
            Swal.fire('Error', 'Failed to load reservation details. Please try again.', 'error');
        });
}

function displayReservationDetails(reservation) {
    const container = document.getElementById('reservationDetails');
    if (!container) {
        console.error('Reservation details container not found');
        return;
    }
    
    // Format dates
    let eventDate = 'Not set';
    if (reservation.event_date) {
        const dateObj = new Date(reservation.event_date);
        if (!isNaN(dateObj)) {
            eventDate = dateObj.toLocaleDateString('en-US', {
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            });
        }
    }
    
    let createdDate = 'Not set';
    if (reservation.created_at) {
        const dateObj = new Date(reservation.created_at);
        if (!isNaN(dateObj)) {
            createdDate = dateObj.toLocaleDateString('en-US', {
                year: 'numeric',
                month: 'long',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
        }
    }
    
    // Calculate balance
    const totalAmount = parseFloat(reservation.total_amount) || 0;
    const amountPaid = parseFloat(reservation.amount_paid) || 0;
    const balance = totalAmount - amountPaid;
    
     let imageHtml = '';
    if (reservation.image_url) {
        // Fix the image path - use relative path from your current location
        // Assuming your PHP is in sub-modules/ and images are in ../../M6/images/
        // But the HTML is being served from the parent directory
        const imagePath = `../M6/images/${reservation.image_url}`;
        console.log('Image path:', imagePath); // Debug log
        
        imageHtml = `
            <div class="col-span-2">
                <div class="card bg-base-100 shadow">
                    <div class="card-body">
                        <h3 class="card-title">Valid ID</h3>
                        <div class="relative">
                            <img src="${imagePath}" 
                                 alt="Valid ID Image" 
                                 class="w-full h-64 object-contain rounded-lg border border-gray-300"
                                 onerror="handleImageError(this)">
                            <div class="mt-2 text-center text-sm text-gray-500">
                                ${reservation.image_url}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;
    }
    
    container.innerHTML = `
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Customer Information -->
            <div class="card bg-base-100 shadow">
                <div class="card-body">
                    <h3 class="card-title">Customer Information</h3>
                    <div class="space-y-2 mt-4">
                        <div class="flex justify-between">
                            <span class="font-medium">Name:</span>
                            <span>${escapeHtml(reservation.customer_name || 'Not provided')}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="font-medium">Email:</span>
                            <span>${escapeHtml(reservation.customer_email || 'Not provided')}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="font-medium">Phone:</span>
                            <span>${escapeHtml(reservation.customer_phone || 'Not provided')}</span>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Event Information -->
            <div class="card bg-base-100 shadow">
                <div class="card-body">
                    <h3 class="card-title">Event Information</h3>
                    <div class="space-y-2 mt-4">
                        <div class="flex justify-between">
                            <span class="font-medium">Event Name:</span>
                            <span>${escapeHtml(reservation.event_name || 'Not provided')}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="font-medium">Event Type:</span>
                            <span>${escapeHtml(reservation.event_type || 'Not provided')}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="font-medium">Venue:</span>
                            <span>${escapeHtml(reservation.venue || 'Not provided')}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="font-medium">Package:</span>
                            <span>${escapeHtml(reservation.event_package || 'Not provided')}</span>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Date & Time -->
            <div class="card bg-base-100 shadow">
                <div class="card-body">
                    <h3 class="card-title">Date & Time</h3>
                    <div class="space-y-2 mt-4">
                        <div class="flex justify-between">
                            <span class="font-medium">Event Date:</span>
                            <span>${eventDate}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="font-medium">Event Time:</span>
                            <span>${reservation.event_time || 'Not set'}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="font-medium">Number of Guests:</span>
                            <span>${reservation.num_guests || '0'}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="font-medium">Created:</span>
                            <span>${createdDate}</span>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Financial Information -->
            <div class="card bg-base-100 shadow">
                <div class="card-body">
                    <h3 class="card-title">Financial Information</h3>
                    <div class="space-y-2 mt-4">
                        <div class="flex justify-between">
                            <span class="font-medium">Total Amount:</span>
                            <span class="font-bold">₱${totalAmount.toFixed(2)}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="font-medium">Amount Paid:</span>
                            <span class="text-success">₱${amountPaid.toFixed(2)}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="font-medium">Balance:</span>
                            <span class="font-bold">₱${balance.toFixed(2)}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="font-medium">Payment Status:</span>
                            <span class="badge ${(reservation.payment_status === 'Paid') ? 'badge-success' : 'badge-warning'}">
                                ${reservation.payment_status || 'Pending'}
                            </span>
                        </div>
                        <div class="flex justify-between">
                            <span class="font-medium">Mode of Payment:</span>
                            <span>${reservation.MOP || 'Cash'}</span>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Status & Requests -->
            <div class="card bg-base-100 shadow">
                <div class="card-body">
                    <h3 class="card-title">Status & Notes</h3>
                    <div class="space-y-2 mt-4">
                        <div class="flex justify-between items-center">
                            <span class="font-medium">Reservation Status:</span>
                            <span class="badge ${getStatusClass(reservation.reservation_status)}">
                                ${reservation.reservation_status || 'Pending'}
                            </span>
                        </div>
                        <div class="mt-2">
                            <p class="font-medium mb-1">Special Requests:</p>
                            <p class="text-sm p-3 bg-base-200 rounded">${escapeHtml(reservation.special_requests || 'No special requests')}</p>
                        </div>
                        ${reservation.notes ? `
                        <div class="mt-2">
                            <p class="font-medium mb-1">Admin Notes:</p>
                            <div class="text-sm p-3 bg-yellow-50 rounded max-h-40 overflow-y-auto">
                                ${formatNotes(reservation.notes)}
                            </div>
                        </div>
                        ` : ''}
                    </div>
                </div>
            </div>
            
            ${imageHtml}
        </div>
    `;
}

// Update action buttons based on editability
function updateActionButtons(isEditable) {
    const actionButtons = document.querySelector('.modal-action .flex');
    if (!actionButtons) return;
    
    // Clear existing buttons
    actionButtons.innerHTML = '';
    
    if (isEditable) {
        // Show all action buttons
        actionButtons.innerHTML = `
            <div class="flex gap-2">
                <button class="btn btn-success" onclick="updateStatus('Confirmed')">
                    <i data-lucide="check" class="w-4 h-4 mr-2"></i> Approve
                </button>
                <button class="btn btn-error" onclick="updateStatus('Cancelled')">
                    <i data-lucide="x" class="w-4 h-4 mr-2"></i> Reject
                </button>
                <button class="btn btn-warning" onclick="showComplianceModal()">
                    <i data-lucide="file-text" class="w-4 h-4 mr-2"></i> For Compliance
                </button>
            </div>
        `;
    } else {
        // Show only view button or disabled buttons
        actionButtons.innerHTML = `
            <div class="flex gap-2">
                <button class="btn btn-disabled" title="Cannot modify - Status is ${currentReservationStatus}">
                    <i data-lucide="check" class="w-4 h-4 mr-2"></i> Approve
                </button>
                <button class="btn btn-disabled" title="Cannot modify - Status is ${currentReservationStatus}">
                    <i data-lucide="x" class="w-4 h-4 mr-2"></i> Reject
                </button>
                ${currentReservationStatus === 'For Compliance' ? `
                <button class="btn btn-info" onclick="showResolveComplianceModal()">
                    <i data-lucide="check-circle" class="w-4 h-4 mr-2"></i> Resolve Compliance
                </button>
                ` : `
                <button class="btn btn-disabled" title="Cannot modify - Status is ${currentReservationStatus}">
                    <i data-lucide="file-text" class="w-4 h-4 mr-2"></i> For Compliance
                </button>
                `}
            </div>
        `;
    }
    
    // Reinitialize icons
    lucide.createIcons();
}

// Helper function to format notes with line breaks
function formatNotes(notes) {
    return escapeHtml(notes || '').replace(/\n/g, '<br>');
}

// Helper function to escape HTML
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function getStatusClass(status) {
    switch(status) {
        case 'Confirmed': return 'badge-success';
        case 'Pending': return 'badge-warning';
        case 'For Compliance': return 'badge-info';
        case 'Cancelled': return 'badge-error';
        default: return 'badge-neutral';
    }
}

function updateStatus(status) {
    if (!currentReservationId) {
        Swal.fire('Error', 'No reservation selected', 'error');
        return;
    }
    
    if (!currentReservationEditable) {
        Swal.fire('Error', `This reservation can no longer be modified. Current status: ${currentReservationStatus}`, 'error');
        return;
    }
    
    const notes = document.getElementById('complianceNotes')?.value || '';
    
    // Show confirmation for certain actions
    if (status === 'Cancelled') {
        Swal.fire({
            title: 'Confirm Cancellation',
            text: 'Are you sure you want to cancel this reservation? This action cannot be undone!',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, cancel it!'
        }).then((result) => {
            if (result.isConfirmed) {
                sendStatusUpdate(status, notes);
            }
        });
    } else if (status === 'Confirmed') {
        Swal.fire({
            title: 'Confirm Approval',
            text: 'Are you sure you want to approve this reservation? This will mark it as confirmed.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#10b981',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, approve it!'
        }).then((result) => {
            if (result.isConfirmed) {
                sendStatusUpdate(status, notes);
            }
        });
    } else {
        sendStatusUpdate(status, notes);
    }
}

function sendStatusUpdate(status, notes) {
    fetch('sub-modules/reservations.php?action=update_status', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            id: currentReservationId,
            status: status,
            notes: notes
        })
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP error! Status: ${response.status}`);
        }
        return response.json();
    })
    .then(data => {
        console.log('Update response:', data);
        if (data.success) {
            Swal.fire({
                icon: 'success',
                title: 'Success!',
                text: data.message || 'Status updated successfully',
                showConfirmButton: false,
                timer: 1500
            }).then(() => {
                document.getElementById('compliance-modal').checked = false;
                document.getElementById('view-modal').checked = false;
                window.location.reload();
            });
        } else {
            Swal.fire('Error', data.message || 'Failed to update status', 'error');
        }
    })
    .catch(error => {
        console.error('Error updating status:', error);
        Swal.fire('Error', 'Failed to update status. Please check your connection and try again.', 'error');
    });
}

function showComplianceModal() {
    if (!currentReservationId) {
        Swal.fire('Error', 'No reservation selected', 'error');
        return;
    }
    
    if (!currentReservationEditable) {
        Swal.fire('Error', `This reservation can no longer be modified. Current status: ${currentReservationStatus}`, 'error');
        return;
    }
    
    // Clear the compliance notes field
    const complianceNotesField = document.getElementById('complianceNotes');
    if (complianceNotesField) {
        complianceNotesField.value = '';
    }
    
    document.getElementById('compliance-modal').checked = true;
}

function showResolveComplianceModal() {
    if (!currentReservationId) {
        Swal.fire('Error', 'No reservation selected', 'error');
        return;
    }
    
    Swal.fire({
        title: 'Resolve Compliance Issue',
        html: `
            <div class="text-left">
                <p class="mb-4">Please provide resolution notes:</p>
                <textarea id="resolveNotes" class="textarea textarea-bordered w-full" rows="4" 
                          placeholder="Enter resolution notes..."></textarea>
                <div class="mt-4">
                    <label class="block text-sm font-medium mb-2">Set new status:</label>
                    <select id="newStatus" class="select select-bordered w-full">
                        <option value="Confirmed">Confirmed</option>
                        <option value="Pending">Pending</option>
                        <option value="Cancelled">Cancelled</option>
                    </select>
                </div>
            </div>
        `,
        showCancelButton: true,
        confirmButtonText: 'Resolve',
        confirmButtonColor: '#10b981',
        preConfirm: () => {
            const notes = document.getElementById('resolveNotes').value;
            const newStatus = document.getElementById('newStatus').value;
            return { notes, newStatus };
        }
    }).then((result) => {
        if (result.isConfirmed) {
            resolveCompliance(result.value.notes, result.value.newStatus);
        }
    });
}

function resolveCompliance(notes, newStatus) {
    fetch('sub-modules/reservations.php?action=for_compliance', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            id: currentReservationId,
            notes: notes,
            new_status: newStatus
        })
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP error! Status: ${response.status}`);
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            Swal.fire({
                icon: 'success',
                title: 'Resolved!',
                text: data.message || 'Compliance issue resolved',
                showConfirmButton: false,
                timer: 1500
            }).then(() => {
                document.getElementById('view-modal').checked = false;
                window.location.reload();
            });
        } else {
            Swal.fire('Error', data.message || 'Failed to resolve compliance', 'error');
        }
    })
    .catch(error => {
        console.error('Error resolving compliance:', error);
        Swal.fire('Error', 'Failed to resolve compliance. Please try again.', 'error');
    });
}

function saveComplianceNotes() {
    const notes = document.getElementById('complianceNotes')?.value || '';
    if (!notes.trim()) {
        Swal.fire('Warning', 'Please enter compliance notes', 'warning');
        return;
    }
    
    // Update status to "For Compliance"
    updateStatus('For Compliance');
}

function deleteReservation(id) {
    if (!id) {
        Swal.fire('Error', 'No reservation ID provided', 'error');
        return;
    }
    
    // First check if reservation is editable
    fetch(`sub-modules/reservations.php?action=get_reservation&id=${id}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const isEditable = data.data.is_editable || false;
                const currentStatus = data.data.reservation_status;
                
                if (!isEditable) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Cannot Delete',
                        text: `This reservation can no longer be deleted. Current status: ${currentStatus}`,
                        confirmButtonText: 'OK'
                    });
                    return;
                }
                
                // If editable, show delete confirmation
                Swal.fire({
                    title: 'Are you sure?',
                    text: "This action cannot be undone!",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'Yes, delete it!'
                }).then((result) => {
                    if (result.isConfirmed) {
                        const formData = new FormData();
                        formData.append('id', id);
                        formData.append('action', 'delete_reservation');
                        
                        fetch('sub-modules/reservations.php', {
                            method: 'POST',
                            body: formData
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                Swal.fire('Deleted!', data.message || 'Reservation deleted successfully', 'success').then(() => {
                                    window.location.reload();
                                });
                            } else {
                                Swal.fire('Error', data.message || 'Failed to delete reservation', 'error');
                            }
                        })
                        .catch(error => {
                            console.error('Error deleting reservation:', error);
                            Swal.fire('Error', 'Failed to delete reservation. Please try again.', 'error');
                        });
                    }
                });
            } else {
                Swal.fire('Error', data.message || 'Failed to check reservation status', 'error');
            }
        })
        .catch(error => {
            console.error('Error checking reservation:', error);
            Swal.fire('Error', 'Failed to check reservation status. Please try again.', 'error');
        });
}

function setupPriceCalculation() {
    const eventTypeSelect = document.getElementById('event_type');
    const venueSelect = document.getElementById('venue');
    const packageSelect = document.getElementById('event_package');
    const numGuestsInput = document.getElementById('num_guests');
    
    if (!eventTypeSelect || !venueSelect || !packageSelect || !numGuestsInput) {
        return; // Elements not found
    }
    
    function updatePriceDisplay() {
        const eventTypePrice = parseFloat(eventTypeSelect.options[eventTypeSelect.selectedIndex]?.dataset.price || 0);
        const venuePrice = parseFloat(venueSelect.options[venueSelect.selectedIndex]?.dataset.price || 0);
        const packagePrice = parseFloat(packageSelect.options[packageSelect.selectedIndex]?.dataset.price || 0);
        const numGuests = parseInt(numGuestsInput.value) || 0;
        
        const packageTotal = packagePrice * numGuests;
        const totalAmount = eventTypePrice + venuePrice + packageTotal;
        const downpayment = totalAmount * 0.20;
        
        document.getElementById('event_type_price').textContent = `₱${eventTypePrice.toFixed(2)}`;
        document.getElementById('venue_price').textContent = `₱${venuePrice.toFixed(2)}`;
        document.getElementById('package_price').textContent = `₱${packageTotal.toFixed(2)}`;
        document.getElementById('guest_count').textContent = numGuests;
        document.getElementById('total_amount').textContent = `₱${totalAmount.toFixed(2)}`;
        document.getElementById('downpayment').textContent = `₱${downpayment.toFixed(2)}`;
        document.getElementById('calculated_total').value = totalAmount.toFixed(2);
    }
    
    [eventTypeSelect, venueSelect, packageSelect, numGuestsInput].forEach(element => {
        element.addEventListener('change', updatePriceDisplay);
        element.addEventListener('input', updatePriceDisplay);
    });
    
    // Initialize
    updatePriceDisplay();
    
    // Set min date to today
    const today = new Date().toISOString().split('T')[0];
    const dateInput = document.querySelector('input[name="event_date"]');
    if (dateInput) {
        dateInput.min = today;
    }
}

function submitEventForm(form) {
    const formData = new FormData(form);
    
    // Validate required fields
    const requiredFields = ['customer_name', 'customer_email', 'customer_phone', 'event_name', 
                           'event_type', 'venue', 'event_package', 'num_guests', 'event_date', 'event_time'];
    for (const field of requiredFields) {
        if (!formData.get(field)) {
            Swal.fire('Error', `Please fill in ${field.replace('_', ' ')}`, 'error');
            return;
        }
    }
    
    // Show loading
    Swal.fire({
        title: 'Saving Reservation...',
        allowOutsideClick: false,
        showConfirmButton: false,
        willOpen: () => {
            Swal.showLoading();
        }
    });
    
    fetch(form.action, {
        method: 'POST',
        body: formData
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP error! Status: ${response.status}`);
        }
        return response.json();
    })
    .then(data => {
        Swal.close();
        
        if (data.success) {
            Swal.fire({
                icon: 'success',
                title: 'Success!',
                text: data.message || 'Reservation saved successfully',
                showConfirmButton: false,
                timer: 1500
            }).then(() => {
                document.getElementById('event-modal').checked = false;
                form.reset();
                window.location.reload();
            });
        } else {
            Swal.fire('Error', data.message || 'Failed to save reservation', 'error');
        }
    })
    .catch(error => {
        Swal.close();
        console.error('Error submitting form:', error);
        Swal.fire('Error', 'Failed to save reservation. Please try again.', 'error');
    });
}
</script>

<script>
  // Clear only the search input
function clearSearch() {
    document.getElementById('searchInput').value = '';
    // Apply filters immediately after clearing
    applyFilters();
}

// Clear all filters including search, status, and date filters
function clearAllFilters() {
    // Clear search input
    document.getElementById('searchInput').value = '';
    // Clear status filter
    document.getElementById('statusFilter').value = '';
    // Clear date filters
    document.getElementById('dateFrom').value = '';
    document.getElementById('dateTo').value = '';
    // Apply empty filters (reload page without any filter parameters)
    window.location.href = window.location.pathname;
}

// Reset filters in dropdown (without applying)
function resetFilters() {
    document.getElementById('statusFilter').value = '';
    document.getElementById('dateFrom').value = '';
    document.getElementById('dateTo').value = '';
    // Close the dropdown
    document.activeElement.blur();
}

// Apply filters function (updated to handle Enter key)
function applyFilters() {
    const search = document.getElementById('searchInput').value;
    const status = document.getElementById('statusFilter').value;
    const dateFrom = document.getElementById('dateFrom').value;
    const dateTo = document.getElementById('dateTo').value;
    
    let url = '?';
    if (search) url += `&search=${encodeURIComponent(search)}`;
    if (status) url += `&status=${encodeURIComponent(status)}`;
    if (dateFrom) url += `&date_from=${encodeURIComponent(dateFrom)}`;
    if (dateTo) url += `&date_to=${encodeURIComponent(dateTo)}`;
    
    window.location.href = url;
}

</script>

<script>
  // ============================================
// CONFIRMED RESERVATIONS FUNCTIONS
// ============================================

let confirmedReservationId = null;

// Event listeners for confirmed section
document.addEventListener('DOMContentLoaded', function() {
    // View button for confirmed reservations
    document.querySelectorAll('.confirmed-view-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            confirmedReservationId = this.dataset.id;
            loadConfirmedReservationDetails(confirmedReservationId);
        });
    });
    
    // Request facility button
    document.querySelectorAll('.request-facility-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            confirmedReservationId = this.dataset.id;
            document.getElementById('request-facility-modal').checked = true;
        });
    });
});

// Load confirmed reservation details
function loadConfirmedReservationDetails(id) {
    fetch(`sub-modules/confirmed_api.php?action=get_reservation&id=${id}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displayConfirmedReservationDetails(data.data);
                document.getElementById('confirmed-view-modal').checked = true;
            } else {
                Swal.fire('Error', data.message, 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            Swal.fire('Error', 'Failed to load reservation details', 'error');
        });
}

// Display confirmed reservation details
function displayConfirmedReservationDetails(reservation) {
    const container = document.getElementById('confirmedReservationDetails');
    
    // Format dates
    const eventDate = new Date(reservation.event_date).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    });
    
    // Calculate balance
    const balance = reservation.total_amount - reservation.amount_paid;
    
    container.innerHTML = `
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Customer & Event Info -->
            <div class="space-y-6">
                <div class="card bg-base-100 shadow">
                    <div class="card-body">
                        <h3 class="card-title">Customer Information</h3>
                        <div class="space-y-2 mt-4">
                            <div class="flex justify-between">
                                <span class="font-medium">Name:</span>
                                <span>${escapeHtml(reservation.customer_name)}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="font-medium">Email:</span>
                                <span>${escapeHtml(reservation.customer_email)}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="font-medium">Phone:</span>
                                <span>${escapeHtml(reservation.customer_phone)}</span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="card bg-base-100 shadow">
                    <div class="card-body">
                        <h3 class="card-title">Event Details</h3>
                        <div class="space-y-2 mt-4">
                            <div class="flex justify-between">
                                <span class="font-medium">Event:</span>
                                <span>${escapeHtml(reservation.event_name)}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="font-medium">Type:</span>
                                <span>${escapeHtml(reservation.event_type)}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="font-medium">Date:</span>
                                <span>${eventDate}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="font-medium">Time:</span>
                                <span>${reservation.event_time}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="font-medium">Guests:</span>
                                <span>${reservation.num_guests}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="font-medium">Venue:</span>
                                <span>${escapeHtml(reservation.venue)}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Financial & Status Info -->
            <div class="space-y-6">
                <div class="card bg-base-100 shadow">
                    <div class="card-body">
                        <h3 class="card-title">Financial Information</h3>
                        <div class="space-y-2 mt-4">
                            <div class="flex justify-between">
                                <span class="font-medium">Total Amount:</span>
                                <span class="font-bold">₱${parseFloat(reservation.total_amount).toFixed(2)}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="font-medium">Amount Paid:</span>
                                <span class="text-success">₱${parseFloat(reservation.amount_paid).toFixed(2)}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="font-medium">Balance:</span>
                                <span class="font-bold">₱${balance.toFixed(2)}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="font-medium">Payment Status:</span>
                                <span class="badge ${reservation.payment_status === 'Paid' ? 'badge-success' : 'badge-warning'}">
                                    ${reservation.payment_status}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="card bg-base-100 shadow">
                    <div class="card-body">
                        <h3 class="card-title">Status & Notes</h3>
                        <div class="space-y-2 mt-4">
                            <div class="flex justify-between items-center">
                                <span class="font-medium">Reservation Status:</span>
                                <span class="badge badge-success">${reservation.reservation_status}</span>
                            </div>
                            <div class="mt-2">
                                <p class="font-medium mb-1">Special Requests:</p>
                                <p class="text-sm p-3 bg-base-200 rounded">${escapeHtml(reservation.special_requests || 'No special requests')}</p>
                            </div>
                            ${reservation.facility_notes ? `
                            <div class="mt-2">
                                <p class="font-medium mb-1">Facility Notes:</p>
                                <p class="text-sm p-3 bg-blue-50 rounded">${escapeHtml(reservation.facility_notes)}</p>
                            </div>
                            ` : ''}
                            ${reservation.compliance_notes ? `
                            <div class="mt-2">
                                <p class="font-medium mb-1">Compliance Notes:</p>
                                <p class="text-sm p-3 bg-yellow-50 rounded">${escapeHtml(reservation.compliance_notes)}</p>
                            </div>
                            ` : ''}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    `;
}

// Request facility function
function requestFacility() {
    document.getElementById('request-facility-modal').checked = true;
}

// Submit facility request
function submitFacilityRequest() {
    const requirements = document.getElementById('facilityRequirements').value;
    const equipment = document.getElementById('specialEquipment').value;
    const setupTime = document.getElementById('setupTime').value;
    
    if (!requirements.trim()) {
        Swal.fire('Warning', 'Please describe facility requirements', 'warning');
        return;
    }
    
    fetch('sub-modules/confirmed_api.php?action=request_facility', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            id: confirmedReservationId,
            requirements: requirements,
            equipment: equipment,
            setup_time: setupTime
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            Swal.fire('Success', 'Facility request submitted! Status changed to "Requested"', 'success').then(() => {
                document.getElementById('request-facility-modal').checked = false;
                document.getElementById('confirmed-view-modal').checked = false;
                window.location.reload();
            });
        } else {
            Swal.fire('Error', data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        Swal.fire('Error', 'Failed to submit facility request', 'error');
    });
}

// Show compliance modal for confirmed
function showConfirmedComplianceModal() {
    document.getElementById('confirmed-compliance-modal').checked = true;
}

// Submit compliance for confirmed
function submitConfirmedCompliance() {
    const notes = document.getElementById('confirmedComplianceNotes').value;
    const action = document.getElementById('complianceAction').value;
    
    if (!notes.trim()) {
        Swal.fire('Warning', 'Please describe the compliance issue', 'warning');
        return;
    }
    
    fetch('sub-modules/confirmed_api.php?action=for_compliance', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            id: confirmedReservationId,
            notes: notes,
            action: action
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            Swal.fire('Success', data.message, 'success').then(() => {
                document.getElementById('confirmed-compliance-modal').checked = false;
                window.location.reload();
            });
        } else {
            Swal.fire('Error', data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        Swal.fire('Error', 'Failed to submit compliance issue', 'error');
    });
}

// Deny reservation
function denyReservation() {
    Swal.fire({
        title: 'Deny Reservation',
        text: 'Are you sure you want to deny this confirmed reservation?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Yes, deny it!'
    }).then((result) => {
        if (result.isConfirmed) {
            fetch('sub-modules/confirmed_api.php?action=deny_reservation', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    id: confirmedReservationId
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire('Denied!', 'Reservation has been denied', 'success').then(() => {
                        document.getElementById('confirmed-view-modal').checked = false;
                        window.location.reload();
                    });
                } else {
                    Swal.fire('Error', data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire('Error', 'Failed to deny reservation', 'error');
            });
        }
    });
}

// Filter functions for confirmed section
function applyConfirmedFilters() {
    const search = document.getElementById('confirmedSearchInput').value;
    const status = document.getElementById('confirmedStatusFilter').value;
    const dateFrom = document.getElementById('confirmedDateFrom').value;
    const dateTo = document.getElementById('confirmedDateTo').value;
    
    let url = '?confirmed_page=1';
    
    // Add confirmed filters
    if (search) url += `&confirmed_search=${encodeURIComponent(search)}`;
    if (status && status !== 'Confirmed') url += `&confirmed_status=${encodeURIComponent(status)}`;
    if (dateFrom) url += `&confirmed_date_from=${encodeURIComponent(dateFrom)}`;
    if (dateTo) url += `&confirmed_date_to=${encodeURIComponent(dateTo)}`;
    
    // Keep main filters
    <?php 
    $main_filters = ['page', 'search', 'status', 'date_from', 'date_to'];
    foreach ($main_filters as $filter) {
        echo "if (new URLSearchParams(window.location.search).get('$filter')) url += '&$filter=' + encodeURIComponent(new URLSearchParams(window.location.search).get('$filter'));\n";
    }
    ?>
    
    window.location.href = url;
}

function clearConfirmedSearch() {
    document.getElementById('confirmedSearchInput').value = '';
    applyConfirmedFilters();
}

function resetConfirmedFilters() {
    document.getElementById('confirmedSearchInput').value = '';
    document.getElementById('confirmedStatusFilter').value = 'Confirmed';
    document.getElementById('confirmedDateFrom').value = '';
    document.getElementById('confirmedDateTo').value = '';
    applyConfirmedFilters();
}
</script>
</body>
</html>