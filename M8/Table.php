<?php
session_start();
include("../main_connection.php");

$db_name = "rest_m1_trs";
if (!isset($connections[$db_name])) {
    die("❌ Connection not found for $db_name");
}

$conn = $connections[$db_name];

// Handle search and filter
$search = $_GET['search'] ?? '';
$filter = $_GET['filter'] ?? 'all';

// Build query for tables
$sql = "SELECT table_id, name, category, capacity, image_url, status, created_at FROM tables WHERE 1=1";

if (!empty($search)) {
    $sql .= " AND (name LIKE '%" . $conn->real_escape_string($search) . "%' 
                  OR category LIKE '%" . $conn->real_escape_string($search) . "%')";
}

if ($filter !== 'all') {
    $sql .= " AND status = '" . $conn->real_escape_string($filter) . "'";
}

$sql .= " ORDER BY created_at DESC";

$result_sql = $conn->query($sql);
if (!$result_sql) {
    die("SQL Error: " . $conn->error);
}

$tables = [];
while ($row = $result_sql->fetch_assoc()) {
    $tables[] = $row;
}

// Query to get table counts by status
$query = "SELECT 
            (SELECT COUNT(*) FROM tables) AS total_tables,
            (SELECT COUNT(*) FROM tables WHERE status = 'Available') AS Available,
            (SELECT COUNT(*) FROM tables WHERE status = 'Queued') AS Queued,
            (SELECT COUNT(*) FROM tables WHERE status = 'Occupied') AS Occupied,
            (SELECT COUNT(*) FROM tables WHERE status = 'Reserved') AS Reserved,
            (SELECT COUNT(*) FROM tables WHERE status = 'Maintenance') AS maintenance,
            (SELECT COUNT(*) FROM tables WHERE status = 'Hidden') AS hidden";

$result = mysqli_query($conn, $query);
if (!$result) {
    die("Count query failed: " . mysqli_error($conn));
}

// Fetch the counts
$row = mysqli_fetch_assoc($result);
$total_tables_count = $row['total_tables'];
$Queued_count = $row['Queued'];   
$available_count = $row['Available'];
$occupied_count = $row['Occupied'];
$reserved_count = $row['Reserved'];
$maintenance_count = $row['maintenance'];
$hidden_count = $row['hidden'];

// Pagination
$itemsPerPage = 9;
$totalItems = count($tables);
$totalPages = ceil($totalItems / $itemsPerPage);
$currentPage = isset($_GET['page']) ? max(1, min($totalPages, intval($_GET['page']))) : 1;
$startIndex = ($currentPage - 1) * $itemsPerPage;
$paginatedTables = array_slice($tables, $startIndex, $itemsPerPage);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Table Management</title>
    <?php include '../header.php'; ?>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/daisyui@4.7.2/dist/full.min.css" rel="stylesheet" type="text/css" />
    <style>
        .side-by-side-container {
            display: flex;
            flex-direction: row;
            gap: 1.5rem;
        }
        
        .chart-container {
            flex: 1;
            min-width: 0;
        }
        
        .status-cards-container {
            flex: 1;
            min-width: 0;
        }
        
        @media (max-width: 1024px) {
            .side-by-side-container {
                flex-direction: column;
            }
        }
        .swal2-popup {
            background: white !important;
            color: #333 !important;
        }
        .table-image {
            width: 100%;
            height: 200px;
            object-fit: cover;
            border-radius: 8px;
        }
        .no-image {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
        }
        .active-filter {
            background-color: #3b82f6 !important;
            color: white !important;
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
        <main class="flex-1 overflow-auto p-4 md:p-6 bg-white">
            <!-- Combined Chart and Status Cards Section -->
            <section class="glass-effect p-6 rounded-2xl shadow-xl border border-gray-100/30 backdrop-blur-sm bg-white/70 mb-6">
                <div class="side-by-side-container">
                    <!-- Status Cards Container -->
                    <div class="status-cards-container">
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3 h-full">

                            <!-- Total Tables Card -->
                            <div class="bg-white p-4 rounded-xl shadow-md border border-gray-100 h-full transition-all duration-300 hover:shadow-2xl hover:scale-105 hover:bg-gray-50">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <p class="text-sm font-medium text-[#001f54] hover:drop-shadow-md transition-all">Total Tables</p>
                                        <h3 class="text-3xl font-bold mt-1 text-[#001f54]"><?php echo $total_tables_count ?? '0'; ?></h3>
                                    </div>
                                    <div class="p-3 rounded-lg bg-[#001f54] flex items-center justify-center transition-all duration-300 hover:bg-[#002b70]">
                                        <i data-lucide="table" class="w-5 h-5 text-[#F7B32B]"></i>
                                    </div>
                                </div>
                                <div class="mt-3">
                                    <div class="h-2 w-full bg-gray-100 rounded-full overflow-hidden">
                                        <div class="h-full bg-[#F7B32B] rounded-full" style="width: 100%"></div>
                                    </div>
                                    <div class="flex justify-between text-xs text-gray-500 mt-1">
                                        <span>Capacity</span>
                                        <span class="font-medium"><?php echo $total_tables_count ?? '0'; ?> tables</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Available Tables Card -->
                            <div class="bg-white p-4 rounded-xl shadow-md border border-gray-100 h-full transition-all duration-300 hover:shadow-2xl hover:scale-105 hover:bg-gray-50">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <p class="text-sm font-medium text-[#001f54] hover:drop-shadow-md transition-all">Available</p>
                                        <h3 class="text-3xl font-bold mt-1 text-[#001f54]"><?php echo $available_count ?? '0'; ?></h3>
                                    </div>
                                    <div class="p-3 rounded-lg bg-[#001f54] flex items-center justify-center transition-all duration-300 hover:bg-[#002b70]">
                                        <i data-lucide="check-circle" class="w-5 h-5 text-[#F7B32B]"></i>
                                    </div>
                                </div>
                                <div class="mt-3">
                                    <div class="h-2 w-full bg-gray-100 rounded-full overflow-hidden">
                                        <div class="h-full bg-[#F7B32B] rounded-full" style="width: <?php echo isset($available_count) ? ($available_count/$total_tables_count)*100 : '0'; ?>%"></div>
                                    </div>
                                    <div class="flex justify-between text-xs text-gray-500 mt-1">
                                        <span>Availability</span>
                                        <span class="font-medium"><?php echo isset($available_count) ? round(($available_count/$total_tables_count)*100) : '0'; ?>%</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Queued Reservations Card -->
                            <div class="bg-white p-4 rounded-xl shadow-md border border-gray-100 h-full transition-all duration-300 hover:shadow-2xl hover:scale-105 hover:bg-gray-50">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <p class="text-sm font-medium text-[#001f54] hover:drop-shadow-md transition-all">Queued</p>
                                        <h3 class="text-3xl font-bold mt-1 text-[#001f54]"><?php echo $Queued_count ?? '0'; ?></h3>
                                    </div>
                                    <div class="p-3 rounded-lg bg-[#001f54] flex items-center justify-center transition-all duration-300 hover:bg-[#002b70]">
                                        <i data-lucide="clock" class="w-5 h-5 text-[#F7B32B]"></i>
                                    </div>
                                </div>
                                <div class="mt-3">
                                    <div class="h-2 w-full bg-gray-100 rounded-full overflow-hidden">
                                        <div class="h-full bg-[#F7B32B] rounded-full" style="width: <?php echo isset($Queued_count) ? ($Queued_count/$total_tables_count)*100 : '0'; ?>%"></div>
                                    </div>
                                    <div class="flex justify-between text-xs text-gray-500 mt-1">
                                        <span>New requests</span>
                                        <span class="font-medium"><?php echo rand(1, 5); ?> today</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Occupied Tables Card -->
                            <div class="bg-white p-4 rounded-xl shadow-md border border-gray-100 h-full transition-all duration-300 hover:shadow-2xl hover:scale-105 hover:bg-gray-50">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <p class="text-sm font-medium text-[#001f54] hover:drop-shadow-md transition-all">Occupied</p>
                                        <h3 class="text-3xl font-bold mt-1 text-[#001f54]"><?php echo $occupied_count ?? '0'; ?></h3>
                                    </div>
                                    <div class="p-3 rounded-lg bg-[#001f54] flex items-center justify-center transition-all duration-300 hover:bg-[#002b70]">
                                        <i data-lucide="users" class="w-5 h-5 text-[#F7B32B]"></i>
                                    </div>
                                </div>
                            </div>

                            <!-- Reserved Tables Card -->
                            <div class="bg-white p-4 rounded-xl shadow-md border border-gray-100 h-full transition-all duration-300 hover:shadow-2xl hover:scale-105 hover:bg-gray-50">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <p class="text-sm font-medium text-[#001f54] hover:drop-shadow-md transition-all">Reserved</p>
                                        <h3 class="text-3xl font-bold mt-1 text-[#001f54]"><?php echo $reserved_count ?? '0'; ?></h3>
                                    </div>
                                    <div class="p-3 rounded-lg bg-[#001f54] flex items-center justify-center transition-all duration-300 hover:bg-[#002b70]">
                                        <i data-lucide="calendar-clock" class="w-5 h-5 text-[#F7B32B]"></i>
                                    </div>
                                </div>
                                <div class="mt-3">
                                    <div class="h-2 w-full bg-gray-100 rounded-full overflow-hidden">
                                        <div class="h-full bg-[#F7B32B] rounded-full" style="width: <?php echo isset($reserved_count) ? ($reserved_count/$total_tables_count)*100 : '0'; ?>%"></div>
                                    </div>
                                    <div class="flex justify-between text-xs text-gray-500 mt-1">
                                        <span>Upcoming</span>
                                        <span class="font-medium"><?php echo rand(1, 8); ?> today</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Maintenance Tables Card -->
                            <div class="bg-white p-4 rounded-xl shadow-md border border-gray-100 h-full transition-all duration-300 hover:shadow-2xl hover:scale-105 hover:bg-gray-50">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <p class="text-sm font-medium text-[#001f54] hover:drop-shadow-md transition-all">Maintenance</p>
                                        <h3 class="text-3xl font-bold mt-1 text-[#001f54]"><?php echo $maintenance_count ?? '0'; ?></h3>
                                    </div>
                                    <div class="p-3 rounded-lg bg-[#001f54] flex items-center justify-center transition-all duration-300 hover:bg-[#002b70]">
                                        <i data-lucide="wrench" class="w-5 h-5 text-[#F7B32B]"></i>
                                    </div>
                                </div>
                                <div class="mt-3">
                                    <div class="h-2 w-full bg-gray-100 rounded-full overflow-hidden">
                                        <div class="h-full bg-[#F7B32B] rounded-full" style="width: <?php echo isset($maintenance_count) ? ($maintenance_count/$total_tables_count)*100 : '0'; ?>%"></div>
                                    </div>
                                    <div class="flex justify-between text-xs text-gray-500 mt-1">
                                        <span>Estimated repair</span>
                                        <span class="font-medium"><?php echo rand(1, 3); ?> days</span>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>
            </section>

            <!-- Table Grid Section -->
            <section class="glass-effect p-6 rounded-xl shadow-xl mt-6">
                <div class="flex flex-col md:flex-row justify-between items-center mb-6 gap-4">
                    <h2 class="text-xl font-bold flex items-center gap-2 text-gray-800">
                        <i data-lucide="layout-grid" class="w-5 h-5 text-blue-500"></i>
                        <span>All Tables</span>
                    </h2>
                    <div class="flex flex-wrap gap-3 w-full md:w-auto">
                        <!-- Add Table Button -->
                        <label for="add-table-modal" class="btn btn-primary px-4 py-2 rounded-xl flex items-center gap-2 shadow-md hover:shadow-lg cursor-pointer text-sm">
                            <i data-lucide="plus" class="w-4 h-4"></i>
                            <span>Add Table</span>
                        </label>

                        <!-- Filter + Search Controls -->
                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-4 gap-2">
                            <!-- Filter Dropdown -->
                            <div class="dropdown dropdown-end">
                                <button class="btn btn-outline border-gray-300 text-gray-700 hover:bg-gray-100 px-4 py-2 rounded-xl flex items-center gap-2 text-sm">
                                    <i data-lucide="filter" class="w-4 h-4"></i> 
                                    <span>Filter</span>
                                </button>
                                <ul id="table-filter" class="dropdown-content menu p-2 shadow bg-white text-black rounded-box w-52 mt-2 border border-gray-200">
                                    <li><a href="?filter=all<?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" class="<?php echo $filter === 'all' ? 'active-filter' : ''; ?>">All Tables</a></li>
                                    <li><a href="?filter=available<?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" class="<?php echo $filter === 'available' ? 'active-filter' : ''; ?>">Available</a></li>
                                    <li><a href="?filter=queued<?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" class="<?php echo $filter === 'queued' ? 'active-filter' : ''; ?>">Queued</a></li>
                                    <li><a href="?filter=occupied<?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" class="<?php echo $filter === 'occupied' ? 'active-filter' : ''; ?>">Occupied</a></li>
                                    <li><a href="?filter=maintenance<?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" class="<?php echo $filter === 'maintenance' ? 'active-filter' : ''; ?>">Maintenance</a></li>
                                    <li><a href="?filter=hidden<?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" class="<?php echo $filter === 'hidden' ? 'active-filter' : ''; ?>">Hidden</a></li>
                                </ul>
                            </div>

                            <!-- Search Bar -->
                            <form method="GET" class="flex gap-2">
                                <input 
                                    type="text" 
                                    name="search" 
                                    value="<?php echo htmlspecialchars($search); ?>"
                                    placeholder="Search tables..." 
                                    class="w-full sm:w-64 px-4 py-2 border border-gray-300 rounded-xl shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm text-black"
                                />
                                <?php if (!empty($filter) && $filter !== 'all'): ?>
                                    <input type="hidden" name="filter" value="<?php echo htmlspecialchars($filter); ?>">
                                <?php endif; ?>
                                <button type="submit" class="btn btn-outline border-gray-300">
                                    <i data-lucide="search" class="w-4 h-4"></i>
                                </button>
                                <?php if (!empty($search)): ?>
                                    <a href="?" class="btn btn-outline border-gray-300">
                                        <i data-lucide="x" class="w-4 h-4"></i>
                                    </a>
                                <?php endif; ?>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Table Grid -->
                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-3 gap-4 p-4 rounded-lg">
                    <?php if (empty($paginatedTables)): ?>
                        <div class="col-span-full text-center py-12">
                            <i data-lucide="table" class="w-16 h-16 mx-auto text-gray-300 mb-4"></i>
                            <p class="text-gray-500 text-lg mb-2">No tables found</p>
                            <p class="text-gray-400 text-sm">Try changing your search or filter criteria</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($paginatedTables as $table): ?>
                            <?php
                            // Get status info
                            $statusInfo = getStatusInfo($table['status']);
                            ?>
                            <div class="rounded-xl p-0 shadow-md hover:shadow-xl transition-all duration-300 hover:-translate-y-1 cursor-pointer flex flex-col gap-0 bg-white"
                                 onclick="openViewModal(<?php echo $table['table_id']; ?>)">
                                
                                <!-- Image Section -->
                                <div class="relative">
                                    <?php if (!empty($table['image_url'])): ?>
                                        <img src="Table_images/<?php echo htmlspecialchars($table['image_url']); ?>" 
                                             alt="<?php echo htmlspecialchars($table['name']); ?>" 
                                             class="w-full h-48 object-cover rounded-t-xl transition-transform duration-500 hover:scale-110"
                                             onerror="this.onerror=null; this.src='data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMjAwIiBoZWlnaHQ9IjIwMCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iMjAwIiBoZWlnaHQ9IjIwMCIgZmlsbD0iI2YwZjBmMCIvPjx0ZXh0IHg9IjEwMCUiIHk9IjUwJSIgZm9udC1mYW1pbHk9IkFyaWFsIiBmb250LXNpemU9IjE0IiB0ZXh0LWFuY2hvcj0ibWlkZGxlIiBmaWxsPSIjOTk5Ij5JbWFnZSBOb3QgRm91bmQ8L3RleHQ+PC9zdmc+'">
                                    <?php else: ?>
                                        <div class="w-full h-48 flex flex-col items-center justify-center bg-gradient-to-br from-gray-100 to-gray-200 rounded-t-xl">
                                            <i data-lucide="table" class="w-16 h-16 text-gray-400 mb-3"></i>
                                            <p class="text-sm text-gray-500 font-medium">No image</p>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <div class="absolute top-2 right-2">
                                        <button class="btn btn-sm btn-circle bg-white/80 hover:bg-white border-0" 
                                                onclick="event.stopPropagation(); openViewModal(<?php echo $table['table_id']; ?>)">
                                            <i data-lucide="eye" class="w-4 h-4"></i>
                                        </button>
                                    </div>
                                </div>

                                <!-- Header -->
                                <div class="flex items-start justify-between gap-3 text-black p-4">
                                    <div class="flex items-center gap-3 min-w-0">
                                        <div class="p-2 rounded-lg <?php echo $statusInfo['badgeColor']; ?> border">
                                            <i data-lucide="<?php echo $statusInfo['statusIcon']; ?>" class="w-5 h-5 <?php echo $statusInfo['iconColor']; ?>"></i>
                                        </div>
                                        <div class="min-w-0">
                                            <h3 class="text-lg font-semibold truncate text-gray-800"><?php echo htmlspecialchars($table['name']); ?></h3>
                                            <p class="text-xs text-gray-500 truncate"><?php echo htmlspecialchars($table['category']); ?> • <?php echo $table['capacity']; ?> seats</p>
                                        </div>
                                    </div>
                                    <span class="text-xs px-3 py-1 rounded-full <?php echo $statusInfo['badgeColor']; ?> border font-medium flex items-center gap-1.5 shrink-0">
                                        <i data-lucide="<?php echo $statusInfo['statusIcon']; ?>" class="w-3 h-3"></i>
                                        <?php echo htmlspecialchars($table['status']); ?>
                                    </span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <!-- Pagination -->
                <div class="mt-6 flex flex-col sm:flex-row justify-between items-center gap-4">
                    <div class="text-sm text-gray-600">
                        Showing <span class="font-semibold"><?php echo min($startIndex + 1, $totalItems); ?>-<?php echo min($startIndex + count($paginatedTables), $totalItems); ?></span> of <span class="font-semibold"><?php echo $totalItems; ?></span> tables
                    </div>
                    <div class="join">
                        <?php if ($totalPages > 1): ?>
                            <!-- Previous button -->
                            <?php if ($currentPage > 1): ?>
                                <a href="?page=<?php echo $currentPage - 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo !empty($filter) && $filter !== 'all' ? '&filter=' . urlencode($filter) : ''; ?>"
                                   class="join-item btn btn-sm btn-outline border-gray-300 text-gray-700 hover:bg-gray-100">
                                    <i data-lucide="chevron-left" class="w-4 h-4"></i>
                                </a>
                            <?php endif; ?>

                            <!-- Page numbers -->
                            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                <?php if ($i === 1 || $i === $totalPages || ($i >= $currentPage - 1 && $i <= $currentPage + 1)): ?>
                                    <a href="?page=<?php echo $i; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo !empty($filter) && $filter !== 'all' ? '&filter=' . urlencode($filter) : ''; ?>"
                                       class="join-item btn btn-sm <?php echo $i === $currentPage ? 'bg-blue-600 text-white' : 'btn-outline border-gray-300 text-gray-700 hover:bg-gray-100'; ?>">
                                        <?php echo $i; ?>
                                    </a>
                                <?php elseif ($i === $currentPage - 2 || $i === $currentPage + 2): ?>
                                    <span class="join-item btn btn-sm btn-disabled border-gray-300">...</span>
                                <?php endif; ?>
                            <?php endfor; ?>

                            <!-- Next button -->
                            <?php if ($currentPage < $totalPages): ?>
                                <a href="?page=<?php echo $currentPage + 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo !empty($filter) && $filter !== 'all' ? '&filter=' . urlencode($filter) : ''; ?>"
                                   class="join-item btn btn-sm btn-outline border-gray-300 text-gray-700 hover:bg-gray-100">
                                    <i data-lucide="chevron-right" class="w-4 h-4"></i>
                                </a>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </section>
        </main>
    </div>
</div>

<!-- Add Table Modal -->
<input type="checkbox" id="add-table-modal" class="modal-toggle" />
<div class="modal modal-bottom sm:modal-middle">
    <div class="modal-box max-w-md p-6 rounded-lg shadow-2xl bg-white">
        <!-- Modal Header -->
        <div class="flex justify-between items-center mb-5">
            <h3 class="text-2xl font-bold flex items-center gap-2 text-[#001f54]">
                <i data-lucide="plus-circle" class="w-6 h-6 text-[#F7B32B]"></i>
                <span>Add New Table</span>
            </h3>
            <label for="add-table-modal" class="btn btn-circle btn-ghost btn-sm text-[#001f54]">
                <i data-lucide="x" class="w-5 h-5"></i>
            </label>
        </div>

        <!-- Add Table Form -->
        <form id="add-table-form" class="space-y-4" enctype="multipart/form-data">
            <!-- Name Field -->
            <div class="form-control">
                <label class="label">
                    <span class="label-text font-medium text-[#001f54]">Table Name</span>
                </label>
                <input
                    type="text"
                    name="name"
                    required
                    placeholder="e.g. Table 5, Booth B"
                    class="input input-bordered bg-white w-full focus:border-[#F7B32B] focus:ring-2 focus:ring-[#F7B32B]/40 text-[#001f54]"
                />
            </div>

            <!-- Category Field -->
            <div class="form-control">
                <label class="label">
                    <span class="label-text font-medium text-[#001f54]">Category</span>
                </label>
                <select
                    name="category"
                    required
                    class="select select-bordered bg-white w-full focus:border-[#F7B32B] focus:ring-2 focus:ring-[#F7B32B]/40 text-[#001f54]"
                >
                    <option value="" disabled selected>Select Category</option>
                    <option value="Regular">Regular</option>
                    <option value="VIP">VIP</option>
                    <option value="Family">Family</option>
                    <option value="Bar">Bar</option>
                </select>
            </div>

            <!-- Capacity Field -->
            <div class="form-control">
                <label class="label">
                    <span class="label-text font-medium text-[#001f54]">Seating Capacity</span>
                </label>
                <input
                    type="number"
                    name="capacity"
                    required
                    min="1"
                    max="20"
                    placeholder="e.g. 4"
                    class="input input-bordered bg-white w-full focus:border-[#F7B32B] focus:ring-2 focus:ring-[#F7B32B]/40 text-[#001f54]"
                />
            </div>

            <!-- Image Upload Field -->
            <div class="form-control">
                <label class="label">
                    <span class="label-text font-medium text-[#001f54]">Table Image (Optional)</span>
                </label>
                <input
                    type="file"
                    name="table_image"
                    accept="image/*"
                    class="file-input file-input-bordered bg-white w-full focus:border-[#F7B32B] focus:ring-2 focus:ring-[#F7B32B]/40 text-[#001f54]"
                />
                <label class="label">
                    <span class="label-text-alt text-gray-500">Supports JPG, PNG, GIF, WebP (Max 5MB)</span>
                </label>
                <div class="mt-2" id="image-preview"></div>
            </div>

            <!-- Action Buttons -->
            <div class="modal-action mt-6 flex justify-end gap-3">
                <label
                    for="add-table-modal"
                    class="btn btn-outline border-[#001f54] text-[#001f54] hover:border-[#F7B32B] hover:text-[#F7B32B]"
                >
                    Cancel
                </label>
                <button
                    type="submit"
                    class="btn bg-[#F7B32B] hover:bg-[#001f54] hover:text-white text-[#001f54] border-none"
                >
                    <i data-lucide="check" class="w-4 h-4 mr-1"></i>
                    Add Table
                </button>
            </div>
        </form>
    </div>
</div>

<!-- View Table Modal -->
<input type="checkbox" id="view-table-modal" class="modal-toggle" />
<div class="modal modal-bottom sm:modal-middle">
    <div class="modal-box max-w-2xl p-0 bg-white rounded-lg shadow-2xl overflow-hidden">
        <!-- Modal Header -->
        <div class="flex justify-between items-center p-6 border-b">
            <h3 class="text-2xl font-bold flex items-center gap-2 text-[#001f54]">
                <i data-lucide="table" class="w-6 h-6 text-[#F7B32B]"></i>
                <span>Table Details</span>
            </h3>
            <label for="view-table-modal" class="btn btn-circle btn-ghost btn-sm">
                <i data-lucide="x" class="w-5 h-5"></i>
            </label>
        </div>

        <!-- Modal Content -->
        <div class="p-6">
            <div id="table-details-content">
                <!-- Table details will be loaded here via AJAX -->
            </div>
        </div>
    </div>
</div>

<!-- JavaScript -->
<script>
// Initialize Lucide icons
lucide.createIcons();

// Function to get status color and icon
function getStatusInfo(status) {
    switch (status.toLowerCase()) {
        case "available":
            return { 
                badgeColor: "bg-green-100 text-green-800 border-green-200",
                iconColor: "text-green-500",
                statusIcon: "check-circle",
                bgColor: "bg-green-50",
                textColor: "text-green-800"
            };
        case "queued":
            return { 
                badgeColor: "bg-blue-100 text-blue-800 border-blue-200",
                iconColor: "text-blue-500",
                statusIcon: "clock",
                bgColor: "bg-blue-50",
                textColor: "text-blue-800"
            };
        case "occupied":
            return { 
                badgeColor: "bg-amber-100 text-amber-800 border-amber-200",
                iconColor: "text-amber-500",
                statusIcon: "users",
                bgColor: "bg-amber-50",
                textColor: "text-amber-800"
            };
        case "reserved":
            return { 
                badgeColor: "bg-purple-100 text-purple-800 border-purple-200",
                iconColor: "text-purple-500",
                statusIcon: "calendar-clock",
                bgColor: "bg-purple-50",
                textColor: "text-purple-800"
            };
        case "maintenance":
            return { 
                badgeColor: "bg-red-100 text-red-800 border-red-200",
                iconColor: "text-red-500",
                statusIcon: "wrench",
                bgColor: "bg-red-50",
                textColor: "text-red-800"
            };
        case "hidden":
            return { 
                badgeColor: "bg-gray-100 text-gray-800 border-gray-200",
                iconColor: "text-gray-500",
                statusIcon: "eye-off",
                bgColor: "bg-gray-50",
                textColor: "text-gray-800"
            };
        default:
            return { 
                badgeColor: "bg-gray-100 text-gray-800 border-gray-200",
                iconColor: "text-gray-500",
                statusIcon: "square",
                bgColor: "bg-gray-50",
                textColor: "text-gray-800"
            };
    }
}

// Image preview for add form
document.addEventListener("DOMContentLoaded", () => {
    const addTableForm = document.getElementById("add-table-form");
    const imagePreview = document.getElementById("image-preview");
    
    if (addTableForm && addTableForm.table_image) {
        addTableForm.table_image.addEventListener("change", function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    imagePreview.innerHTML = `
                        <div class="mt-2">
                            <img src="${e.target.result}" class="w-full h-48 object-cover rounded-lg border" alt="Preview">
                            <p class="text-xs text-gray-500 mt-1">Preview of selected image</p>
                        </div>
                    `;
                };
                reader.readAsDataURL(file);
            }
        });
    }

    // Add table form submission
    if (addTableForm) {
        addTableForm.addEventListener("submit", (e) => {
            e.preventDefault();
            
            const formData = new FormData(addTableForm);
            const tableName = formData.get('name');
            
            Swal.fire({
                title: 'Adding Table...',
                text: `Adding ${tableName} to the system`,
                allowOutsideClick: false,
                showConfirmButton: false,
                willOpen: () => {
                    Swal.showLoading();
                }
            });

            fetch("sub-modules/add_table.php", {
                method: "POST",
                body: formData
            })
            .then(res => res.json())
            .then(response => {
                if (response.status === "success") {
                    Swal.fire({
                        icon: 'success',
                        title: 'Success!',
                        text: response.message,
                        background: 'white',
                        color: '#333',
                        showConfirmButton: false,
                        timer: 1500
                    }).then(() => {
                        // Close modal
                        document.getElementById('add-table-modal').checked = false;
                        // Reset form
                        addTableForm.reset();
                        if (imagePreview) imagePreview.innerHTML = '';
                        // Refresh page
                        window.location.reload();
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: response.message,
                        background: 'white',
                        color: '#333'
                    });
                }
            })
            .catch(error => {
                console.error("[Add Table Error]", error);
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Failed to add table. Please try again.',
                    background: 'white',
                    color: '#333'
                });
            });
        });
    }
});

// Global functions for table actions
window.openViewModal = function(tableId) {
    // Fetch table details via AJAX
    fetch(`sub-modules/view_table.php?id=${tableId}`)
        .then((res) => res.json())
        .then((response) => {
            if (response.status !== "success") {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Failed to fetch table details',
                    background: 'white',
                    color: '#333'
                });
                return;
            }

            const table = response.table;
            const statusInfo = getStatusInfo(table.status);
            
            const content = `
                <div class="space-y-6">
                    <!-- Image Section -->
                    <div class="rounded-lg overflow-hidden">
                        <?php if (!empty($table['image_url'])): ?>
                            <img src="Table_images/${table.image_url}" 
                                 alt="${table.name}" 
                                 class="w-full h-64 object-cover transition-transform duration-500 hover:scale-110"
                                 onerror="this.onerror=null; this.src='data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMjAwIiBoZWlnaHQ9IjIwMCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iMjAwIiBoZWlnaHQ9IjIwMCIgZmlsbD0iI2YwZjBmMCIvPjx0ZXh0IHg9IjEwMCUiIHk9IjUwJSIgZm9udC1mYW1pbHk9IkFyaWFsIiBmb250LXNpemU9IjE0IiB0ZXh0LWFuY2hvcj0ibWlkZGxlIiBmaWxsPSIjOTk5Ij5JbWFnZSBOb3QgRm91bmQ8L3RleHQ+PC9zdmc+'">
                        <?php else: ?>
                            <div class="w-full h-64 flex flex-col items-center justify-center bg-gradient-to-br from-gray-100 to-gray-200">
                                <i data-lucide="table" class="w-16 h-16 text-gray-400 mb-3"></i>
                                <p class="text-sm text-gray-500 font-medium">No image</p>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Details Section -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="space-y-2">
                            <h4 class="text-lg font-semibold text-gray-800">${table.name}</h4>
                            <div class="flex items-center gap-2">
                                <span class="text-xs font-semibold px-3 py-1 rounded-full ${statusInfo.badgeColor}">
                                    ${table.status}
                                </span>
                                <span class="text-gray-500">•</span>
                                <span class="text-gray-600">ID: ${table.table_id}</span>
                            </div>
                        </div>
                        
                        <div class="space-y-3">
                            <div class="flex items-center gap-3">
                                <div class="p-2 rounded-lg bg-gray-100">
                                    <i data-lucide="tag" class="w-4 h-4 text-gray-600"></i>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-500">Category</p>
                                    <p class="font-medium text-gray-800">${table.category}</p>
                                </div>
                            </div>
                            
                            <div class="flex items-center gap-3">
                                <div class="p-2 rounded-lg bg-gray-100">
                                    <i data-lucide="users" class="w-4 h-4 text-gray-600"></i>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-500">Capacity</p>
                                    <p class="font-medium text-gray-800">${table.capacity} people</p>
                                </div>
                            </div>
                            
                            <div class="flex items-center gap-3">
                                <div class="p-2 rounded-lg bg-gray-100">
                                    <i data-lucide="calendar" class="w-4 h-4 text-gray-600"></i>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-500">Created</p>
                                    <p class="font-medium text-gray-800">${new Date(table.created_at).toLocaleDateString('en-US', {
                                        year: 'numeric',
                                        month: 'short',
                                        day: 'numeric',
                                        hour: '2-digit',
                                        minute: '2-digit'
                                    })}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- CRUD Buttons Section -->
                    <div class="pt-4 border-t border-gray-200">
                        <h5 class="text-sm font-semibold text-gray-700 mb-3">Table Actions</h5>
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                            <button onclick="performTableAction(${table.table_id}, 'display')" 
                                    class="btn btn-outline border-green-500 text-green-600 hover:bg-green-50 flex items-center gap-2">
                                <i data-lucide="eye" class="w-4 h-4"></i>
                                Display
                            </button>
                            
                            <button onclick="performTableAction(${table.table_id}, 'hide')" 
                                    class="btn btn-outline border-gray-500 text-gray-600 hover:bg-gray-50 flex items-center gap-2">
                                <i data-lucide="eye-off" class="w-4 h-4"></i>
                                Hide
                            </button>
                            
                            <button onclick="performTableAction(${table.table_id}, 'maintenance')" 
                                    class="btn btn-outline border-red-500 text-red-600 hover:bg-red-50 flex items-center gap-2">
                                <i data-lucide="wrench" class="w-4 h-4"></i>
                                Maintenance
                            </button>
                            
                            <button onclick="editTable(${table.table_id})" 
                                    class="btn btn-outline border-blue-500 text-blue-600 hover:bg-blue-50 flex items-center gap-2">
                                <i data-lucide="edit" class="w-4 h-4"></i>
                                Edit
                            </button>
                            
                            <button onclick="deleteTable(${table.table_id})" 
                                    class="btn btn-outline border-red-500 text-red-600 hover:bg-red-50 flex items-center gap-2">
                                <i data-lucide="trash-2" class="w-4 h-4"></i>
                                Delete
                            </button>
                            
                           
                        </div>
                    </div>
                </div>
            `;

            document.getElementById("table-details-content").innerHTML = content;
            document.getElementById("view-table-modal").checked = true;
            lucide.createIcons();
        })
        .catch((err) => {
            console.error("[Table Detail Error]", err);
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Failed to load table details',
                background: 'white',
                color: '#333'
            });
        });
};

window.performTableAction = function(tableId, action) {
    const actionText = {
        'display': 'display as Available',
        'hide': 'hide',
        'maintenance': 'set to Maintenance'
    }[action] || action;

    Swal.fire({
        title: 'Confirm Action',
        text: `Are you sure you want to ${actionText} this table?`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Yes, proceed',
        cancelButtonText: 'Cancel',
        background: 'white',
        color: '#333'
    }).then((result) => {
        if (result.isConfirmed) {
            const formData = new FormData();
            formData.append('action', action);
            formData.append('table_id', tableId);

            fetch("sub-modules/crud_table.php", {
                method: "POST",
                body: formData
            })
            .then(res => res.json())
            .then(response => {
                if (response.status === "success") {
                    Swal.fire({
                        icon: 'success',
                        title: 'Success!',
                        text: response.message,
                        background: 'white',
                        color: '#333',
                        showConfirmButton: false,
                        timer: 1500
                    }).then(() => {
                        // Close modal and refresh page
                        document.getElementById('view-table-modal').checked = false;
                        window.location.reload();
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: response.message,
                        background: 'white',
                        color: '#333'
                    });
                }
            })
            .catch(error => {
                console.error("[Table Action Error]", error);
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Failed to perform action',
                    background: 'white',
                    color: '#333'
                });
            });
        }
    });
};

window.editTable = function(tableId) {
    Swal.fire({
        title: 'Edit Table',
        html: `
            <div class="text-left">
                <p class="mb-4">Edit table #${tableId}</p>
                <div class="space-y-3">
                    <input type="text" id="edit-table-name" class="input input-bordered w-full" placeholder="Table Name">
                    <input type="number" id="edit-table-capacity" class="input input-bordered w-full" placeholder="Capacity">
                    <select id="edit-table-status" class="select select-bordered w-full">
                        <option value="Available">Available</option>
                        <option value="Queued">Queued</option>
                        <option value="Occupied">Occupied</option>
                        <option value="Reserved">Reserved</option>
                        <option value="Maintenance">Maintenance</option>
                        <option value="Hidden">Hidden</option>
                    </select>
                </div>
            </div>
        `,
        showCancelButton: true,
        confirmButtonText: 'Save Changes',
        background: 'white',
        color: '#333',
        preConfirm: () => {
            const name = document.getElementById('edit-table-name').value;
            const capacity = document.getElementById('edit-table-capacity').value;
            const status = document.getElementById('edit-table-status').value;
            
            if (!name || !capacity) {
                Swal.showValidationMessage('Please fill in all fields');
                return false;
            }
            
            return { name, capacity, status };
        }
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire({
                icon: 'info',
                title: 'Coming Soon',
                text: 'Edit functionality will be implemented soon!',
                background: 'white',
                color: '#333'
            });
        }
    });
};

window.deleteTable = function(tableId) {
    Swal.fire({
        title: 'Delete Table',
        text: 'Are you sure you want to delete this table? This action cannot be undone.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Yes, delete it',
        cancelButtonText: 'Cancel',
        background: 'white',
        color: '#333'
    }).then((result) => {
        if (result.isConfirmed) {
            const formData = new FormData();
            formData.append('action', 'delete');
            formData.append('table_id', tableId);

            fetch("crud_table.php", {
                method: "POST",
                body: formData
            })
            .then(res => res.json())
            .then(response => {
                if (response.status === "success") {
                    Swal.fire({
                        icon: 'success',
                        title: 'Deleted!',
                        text: response.message,
                        background: 'white',
                        color: '#333',
                        showConfirmButton: false,
                        timer: 1500
                    }).then(() => {
                        // Close modal and refresh page
                        document.getElementById('view-table-modal').checked = false;
                        window.location.reload();
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: response.message,
                        background: 'white',
                        color: '#333'
                    });
                }
            })
            .catch(error => {
                console.error("[Delete Table Error]", error);
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Failed to delete table',
                    background: 'white',
                    color: '#333'
                });
            });
        }
    });
};

window.reserveTable = function(tableId) {
    Swal.fire({
        title: 'Reserve Table',
        html: `
            <div class="text-left">
                <p class="mb-4">Reserve table #${tableId}</p>
                <div class="space-y-3">
                    <input type="text" id="reserve-customer-name" class="input input-bordered w-full" placeholder="Customer Name">
                    <input type="number" id="reserve-party-size" class="input input-bordered w-full" placeholder="Party Size">
                    <input type="datetime-local" id="reserve-time" class="input input-bordered w-full">
                    <textarea id="reserve-notes" class="textarea textarea-bordered w-full" placeholder="Special requests or notes" rows="3"></textarea>
                </div>
            </div>
        `,
        showCancelButton: true,
        confirmButtonText: 'Reserve',
        background: 'white',
        color: '#333',
        preConfirm: () => {
            const customerName = document.getElementById('reserve-customer-name').value;
            const partySize = document.getElementById('reserve-party-size').value;
            const time = document.getElementById('reserve-time').value;
            
            if (!customerName || !partySize || !time) {
                Swal.showValidationMessage('Please fill in all required fields');
                return false;
            }
            
            return { customerName, partySize, time };
        }
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire({
                icon: 'success',
                title: 'Reserved!',
                text: 'Table has been reserved successfully!',
                background: 'white',
                color: '#333'
            });
        }
    });
};

// Helper function for PHP
<?php
function getStatusInfo($status) {
    switch (strtolower($status)) {
        case "available":
            return [ 
                'badgeColor' => "bg-green-100 text-green-800 border-green-200",
                'iconColor' => "text-green-500",
                'statusIcon' => "check-circle",
                'bgColor' => "bg-green-50",
                'textColor' => "text-green-800"
            ];
        case "queued":
            return [ 
                'badgeColor' => "bg-blue-100 text-blue-800 border-blue-200",
                'iconColor' => "text-blue-500",
                'statusIcon' => "clock",
                'bgColor' => "bg-blue-50",
                'textColor' => "text-blue-800"
            ];
        case "occupied":
            return [ 
                'badgeColor' => "bg-amber-100 text-amber-800 border-amber-200",
                'iconColor' => "text-amber-500",
                'statusIcon' => "users",
                'bgColor' => "bg-amber-50",
                'textColor' => "text-amber-800"
            ];
        case "reserved":
            return [ 
                'badgeColor' => "bg-purple-100 text-purple-800 border-purple-200",
                'iconColor' => "text-purple-500",
                'statusIcon' => "calendar-clock",
                'bgColor' => "bg-purple-50",
                'textColor' => "text-purple-800"
            ];
        case "maintenance":
            return [ 
                'badgeColor' => "bg-red-100 text-red-800 border-red-200",
                'iconColor' => "text-red-500",
                'statusIcon' => "wrench",
                'bgColor' => "bg-red-50",
                'textColor' => "text-red-800"
            ];
        case "hidden":
            return [ 
                'badgeColor' => "bg-gray-100 text-gray-800 border-gray-200",
                'iconColor' => "text-gray-500",
                'statusIcon' => "eye-off",
                'bgColor' => "bg-gray-50",
                'textColor' => "text-gray-800"
            ];
        default:
            return [ 
                'badgeColor' => "bg-gray-100 text-gray-800 border-gray-200",
                'iconColor' => "text-gray-500",
                'statusIcon' => "square",
                'bgColor' => "bg-gray-50",
                'textColor' => "text-gray-800"
            ];
    }
}
?>
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