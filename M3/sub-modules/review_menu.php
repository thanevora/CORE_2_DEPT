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

// Pagination settings
$limit = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Search and filter parameters
$search = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';
$category_filter = isset($_GET['category']) ? $conn->real_escape_string($_GET['category']) : 'all';
$status_filter = isset($_GET['status']) ? $conn->real_escape_string($_GET['status']) : 'all';

// Build query for menu items
$sql_where = "WHERE 1=1";
if (!empty($search)) {
    $sql_where .= " AND (name LIKE '%$search%' OR description LIKE '%$search%')";
}
if ($category_filter !== 'all') {
    $sql_where .= " AND category = '$category_filter'";
}
if ($status_filter !== 'all') {
    $sql_where .= " AND status = '$status_filter'";
}

// Fetch menu items with pagination
$sql = "SELECT menu_id, name, category, description, variant, price, status, created_at, updated_at, 
               prep_time, availability, image_url 
        FROM menu 
        $sql_where
        ORDER BY created_at DESC 
        LIMIT $limit OFFSET $offset";

$result_sql = $conn->query($sql);
if (!$result_sql) {
    die("SQL Error: " . $conn->error);
}

// Get total count for pagination
$count_sql = "SELECT COUNT(*) as total FROM menu $sql_where";
$count_result = $conn->query($count_sql);
$total_items = $count_result->fetch_assoc()['total'];
$total_pages = ceil($total_items / $limit);

// Fetch comprehensive review statistics
$stats_query = "SELECT 
    (SELECT COUNT(*) FROM menu WHERE status = 'Pending for approval') AS pending_review,
    (SELECT COUNT(*) FROM menu WHERE status = 'For compliance review') AS compliance_review,
    (SELECT COUNT(*) FROM menu WHERE status = 'Approved') AS approved_review,
    (SELECT COUNT(*) FROM menu WHERE status = 'Rejected') AS rejected_review,
    (SELECT COUNT(*) FROM menu WHERE status = 'For posting') AS for_posting,
    (SELECT COUNT(*) FROM menu) AS total_menu_reviews,
    (SELECT COUNT(DISTINCT category) FROM menu WHERE status = 'Pending for approval') AS pending_categories";

$stats_result = $conn->query($stats_query);
if (!$stats_result) {
    die("Count query failed: " . $conn->error);
}

// Get counts
$stats = $stats_result->fetch_assoc();
$pending_review_count = $stats['Under_review'] ?? 0;
$compliance_review_count = $stats['compliance_review'] ?? 0;
$approved_review_count = $stats['approved_review'] ?? 0;
$rejected_review_count = $stats['rejected_review'] ?? 0;
$for_posting_count = $stats['for_posting'] ?? 0;
$total_menu_reviews_count = $stats['total_menu_reviews'] ?? 0;
$pending_categories_count = $stats['pending_categories'] ?? 0;

// Fetch inventory items for reference
$inv_query = "SELECT item_id, item_name FROM inventory_and_stock";
$inv_result = $connections['rest_m2_inventory']->query($inv_query);
$inventory_items = [];
while ($inv = $inv_result->fetch_assoc()) {
    $inventory_items[] = $inv;
}

// Get categories for filter
$categories_query = "SELECT DISTINCT category FROM menu ORDER BY category";
$categories_result = $conn->query($categories_query);
$categories = [];
while ($cat = $categories_result->fetch_assoc()) {
    $categories[] = $cat['category'];
}

// Status labels for display
$status_labels = [
    'all' => 'All',
    'Under review' => 'Under review',
    'For compliance review' => 'Compliance', 
    'Approved' => 'Approved',
    'Rejected' => 'Rejected',
    'For posting' => 'For Posting'
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Review New Menu | Soliera Restaurant</title>
    <?php include '../../header.php'; ?>
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
            <!-- Review Summary Cards -->
            <section class="bg-white p-6 rounded-2xl shadow-sm border border-gray-200 mb-6">
                <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
                    <h2 class="text-2xl font-bold text-gray-800 flex items-center">
                        <span class="p-2 mr-3 rounded-lg bg-blue-100 text-blue-600">
                            <i data-lucide="clipboard-check" class="w-5 h-5"></i>
                        </span>
                        Menu Review Dashboard
                    </h2>
                </div>

                <!-- Review Statistics Cards -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                    
                    <!-- Pending Review Card -->
                    <div class="bg-white text-black shadow-lg p-5 rounded-xl">
                        <div class="flex justify-between items-start">
                            <div>
                                <p class="text-sm font-medium text-[#001f54]">Under Review</p>
                                <h3 class="text-3xl font-bold mt-1"><?php echo $pending_review_count; ?></h3>
                                <p class="text-xs text-gray-500 mt-1">Awaiting approval</p>
                            </div>
                            <div class="p-3 rounded-lg bg-[#001f54] flex items-center justify-center">
                                <i data-lucide="clock" class="w-6 h-6 text-[#F7B32B]"></i>
                            </div>
                        </div>
                    </div>

                    <!-- For Compliance Review Card -->
                    <div class="bg-white text-black shadow-lg p-5 rounded-xl">
                        <div class="flex justify-between items-start">
                            <div>
                                <p class="text-sm font-medium text-[#001f54]">For Compliance</p>
                                <h3 class="text-3xl font-bold mt-1"><?php echo $compliance_review_count; ?></h3>
                                <p class="text-xs text-gray-500 mt-1">Compliance review</p>
                            </div>
                            <div class="p-3 rounded-lg bg-[#001f54] flex items-center justify-center">
                                <i data-lucide="shield-alert" class="w-6 h-6 text-[#F7B32B]"></i>
                            </div>
                        </div>
                    </div>

                    <!-- Approved Review Card -->
                    <div class="bg-white text-black shadow-lg p-5 rounded-xl">
                        <div class="flex justify-between items-start">
                            <div>
                                <p class="text-sm font-medium text-[#001f54]">Approved</p>
                                <h3 class="text-3xl font-bold mt-1"><?php echo $approved_review_count; ?></h3>
                                <p class="text-xs text-gray-500 mt-1">Approved items</p>
                            </div>
                            <div class="p-3 rounded-lg bg-[#001f54] flex items-center justify-center">
                                <i data-lucide="check-circle" class="w-6 h-6 text-[#F7B32B]"></i>
                            </div>
                        </div>
                    </div>

                    <!-- Rejected Review Card -->
                    <div class="bg-white text-black shadow-lg p-5 rounded-xl">
                        <div class="flex justify-between items-start">
                            <div>
                                <p class="text-sm font-medium text-[#001f54]">Rejected</p>
                                <h3 class="text-3xl font-bold mt-1"><?php echo $rejected_review_count; ?></h3>
                                <p class="text-xs text-gray-500 mt-1">Rejected items</p>
                            </div>
                            <div class="p-3 rounded-lg bg-[#001f54] flex items-center justify-center">
                                <i data-lucide="x-circle" class="w-6 h-6 text-[#F7B32B]"></i>
                            </div>
                        </div>
                    </div>

                    <!-- For Posting Card -->
                    <div class="bg-white text-black shadow-lg p-5 rounded-xl">
                        <div class="flex justify-between items-start">
                            <div>
                                <p class="text-sm font-medium text-[#001f54]">For Posting</p>
                                <h3 class="text-3xl font-bold mt-1"><?php echo $for_posting_count; ?></h3>
                                <p class="text-xs text-gray-500 mt-1">Ready for posting</p>
                            </div>
                            <div class="p-3 rounded-lg bg-[#001f54] flex items-center justify-center">
                                <i data-lucide="upload" class="w-6 h-6 text-[#F7B32B]"></i>
                            </div>
                        </div>
                    </div>

                    <!-- Total Menu Reviews Card -->
                    <div class="bg-white text-black shadow-lg p-5 rounded-xl">
                        <div class="flex justify-between items-start">
                            <div>
                                <p class="text-sm font-medium text-[#001f54]">Total Reviews</p>
                                <h3 class="text-3xl font-bold mt-1"><?php echo $total_menu_reviews_count; ?></h3>
                                <p class="text-xs text-gray-500 mt-1">All menu items</p>
                            </div>
                            <div class="p-3 rounded-lg bg-[#001f54] flex items-center justify-center">
                                <i data-lucide="bar-chart-3" class="w-6 h-6 text-[#F7B32B]"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Menu Review Section -->
            <section class="bg-white p-6 rounded-xl shadow-sm border border-gray-200">
                <div class="flex flex-col gap-4 mb-6">
                    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                        <h2 class="text-xl font-bold flex items-center gap-2 text-gray-800">
                            <i data-lucide="clipboard-check" class="w-5 h-5 text-amber-500"></i>
                            <span>Menu Items for Review</span>
                            <?php if ($total_items > 0): ?>
                                <span class="bg-amber-500 text-white px-2 py-1 rounded-full text-sm font-medium">
                                    <?php echo $total_items; ?> items
                                </span>
                            <?php endif; ?>
                        </h2>
                    </div>

                    <!-- Filter Bar -->
                    <div class="flex flex-col md:flex-row gap-4 items-start md:items-center flex-wrap">
                        <!-- Search Box -->
                        <div class="relative min-w-[250px]">
                            <i data-lucide="search" class="absolute left-3 top-1/2 transform -translate-y-1/2 w-4 h-4 text-gray-400"></i>
                            <input type="text" 
                                   id="search-input"
                                   placeholder="Search menu items..." 
                                   value="<?php echo htmlspecialchars($search); ?>"
                                   class="input input-bordered w-full pl-10 bg-white border-gray-300">
                        </div>

                        <!-- Category Filter -->
                        <select id="category-filter" class="select select-bordered bg-white border-gray-300 text-sm">
                            <option value="all" <?php echo $category_filter === 'all' ? 'selected' : ''; ?>>All Categories</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?php echo htmlspecialchars($category); ?>" 
                                        <?php echo $category_filter === $category ? 'selected' : ''; ?>>
                                    <?php echo ucfirst(htmlspecialchars($category)); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>

                        <form method="GET" class="flex items-center gap-3">
                            <div class="flex items-center gap-2">
                                <i data-lucide="filter" class="w-4 h-4 text-gray-500"></i>
                                <span class="text-gray-700 font-medium">Status:</span>
                            </div>
                            <select 
                                name="status" 
                                class="bg-white text-gray-800 px-4 py-2.5 rounded-md border border-gray-300 shadow-sm cursor-pointer"
                                onchange="this.form.submit()">
                                
                                <option value="all" <?= ($status_filter === 'all') ? 'selected' : '' ?>>All Status</option>
                                <option value="Pending for approval" <?= ($status_filter === 'Under review') ? 'selected' : '' ?>>Under review</option>
                                <option value="For compliance review" <?= ($status_filter === 'For compliance review') ? 'selected' : '' ?>>For Compliance review</option>
                                <option value="Approved" <?= ($status_filter === 'Approved') ? 'selected' : '' ?>>Approved review</option>
                                <option value="Rejected" <?= ($status_filter === 'Rejected') ? 'selected' : '' ?>>Rejected review</option>
                                <option value="For posting" <?= ($status_filter === 'For posting') ? 'selected' : '' ?>>For Posting</option>
                            </select>
                        </form>
                    </div>
                </div>
                <?php if ($result_sql->num_rows > 0): ?>
<!-- Menu Review Grid -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-3 gap-6" id="menu-review-grid">
    <?php while($row = $result_sql->fetch_assoc()): 
        $status_class = '';
        $border_class = '';
        switch($row['status']) {
            case 'Under review':
                $status_class = 'bg-amber-100 text-amber-800';
                $border_class = 'border-amber-300';
                break;
            case 'For compliance review':
                $status_class = 'bg-purple-100 text-purple-800';
                $border_class = 'border-purple-300';
                break;
            case 'Approved':
                $status_class = 'bg-green-100 text-green-800';
                $border_class = 'border-green-300';
                break;
            case 'Rejected':
                $status_class = 'bg-red-100 text-red-800';
                $border_class = 'border-red-300';
                break;
            case 'For posting':
                $status_class = 'bg-blue-100 text-blue-800';
                $border_class = 'border-blue-300';
                break;
        }
    ?>
    <div class="bg-white rounded-lg shadow-lg hover:shadow-xl transition-all duration-300 hover:-translate-y-1 border-2 <?php echo $border_class; ?>">
        
        <!-- Image Section -->
        <div class="relative h-48 w-full overflow-hidden rounded-t-lg bg-gray-100">
            <?php if (!empty($row['image_url'])): ?>
                <!-- Corrected path for M3/Menu_uploaded directory -->
                <img src="../Menu_uploaded/menu_images/original/<?php echo htmlspecialchars($row['image_url']); ?>" 
                     alt="<?php echo htmlspecialchars($row['name']); ?>" 
                     class="w-full h-full object-cover transition-transform duration-500 hover:scale-110"
                     onerror="this.onerror=null; this.src='data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMjAwIiBoZWlnaHQ9IjIwMCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iMjAwIiBoZWlnaHQ9IjIwMCIgZmlsbD0iI2YwZjBmMCIvPjx0ZXh0IHg9IjEwMCUiIHk9IjUwJSIgZm9udC1mYW1pbHk9IkFyaWFsIiBmb250LXNpemU9IjE0IiB0ZXh0LWFuY2hvcj0ibWlkZGxlIiBmaWxsPSIjOTk5Ij5JbWFnZSBOb3QgRm91bmQ8L3RleHQ+PC9zdmc+'">
            <?php else: ?>
                <div class="w-full h-full flex flex-col items-center justify-center bg-gradient-to-br from-gray-100 to-gray-200">
                    <i data-lucide="utensils" class="w-12 h-12 text-gray-400 mb-2"></i>
                    <p class="text-xs text-gray-500 font-medium">No image</p>
                </div>
            <?php endif; ?>
            
            <!-- Status Badge -->
            <div class="absolute top-4 right-4 px-3 py-1.5 rounded-full text-xs font-semibold shadow-sm <?php echo $status_class; ?>">
                <i data-lucide="<?php 
                    switch($row['status']) {
                        case 'Under review': echo 'clock'; break;
                        case 'For compliance review': echo 'shield-alert'; break;
                        case 'Approved': echo 'check-circle'; break;
                        case 'Rejected': echo 'x-circle'; break;
                        case 'For posting': echo 'upload'; break;
                        default: echo 'file';
                    }
                ?>" class="w-3 h-3 inline mr-1"></i>
                <?php echo htmlspecialchars($row['status']); ?>
            </div>
        </div>
        
        <!-- Content Section -->
        <div class="p-4">
            <!-- Header with Name -->
            <div class="flex items-start justify-between mb-3">
                <div class="flex items-center gap-3">
                    <div class="p-2 rounded-lg bg-[#001f54]/10">
                        <i data-lucide="utensils" class="w-4 h-4 text-[#001f54]"></i>
                    </div>
                    <div class="min-w-0 flex-1">
                        <h3 class="text-base font-bold text-gray-800 truncate"><?php echo htmlspecialchars($row['name']); ?></h3>
                        <p class="text-xs text-gray-500 mt-0.5 truncate">ID: <?php echo htmlspecialchars($row['menu_id']); ?></p>
                    </div>
                </div>
            </div>

            <!-- Details Grid -->
            <div class="grid grid-cols-2 gap-3 mb-3">
                <div class="space-y-1">
                    <p class="text-xs text-gray-500 font-medium">Price</p>
                    <div class="flex items-center gap-2">
                        <p class="font-bold text-gray-800">₱ <?php echo number_format($row['price'], 2); ?></p>
                    </div>
                </div>
                <div class="space-y-1">
                    <p class="text-xs text-gray-500 font-medium">Prep Time</p>
                    <div class="flex items-center gap-2">
                        <i data-lucide="clock" class="w-3 h-3 text-blue-600"></i>
                        <p class="font-semibold text-gray-800"><?php echo htmlspecialchars($row['prep_time']); ?> min</p>
                    </div>
                </div>
                <div class="space-y-1">
                    <p class="text-xs text-gray-500 font-medium">Category</p>
                    <div class="flex items-center gap-2">
                        <i data-lucide="tag" class="w-3 h-3 text-purple-600"></i>
                        <p class="font-semibold text-gray-800 capitalize"><?php echo htmlspecialchars($row['category']); ?></p>
                    </div>
                </div>
                <div class="space-y-1">
                    <p class="text-xs text-gray-500 font-medium">Variant</p>
                    <div class="flex items-center gap-2">
                        <i data-lucide="sun" class="w-3 h-3 text-amber-600"></i>
                        <p class="font-semibold text-gray-800"><?php echo htmlspecialchars($row['variant']); ?></p>
                    </div>
                </div>
            </div>

            <!-- Description -->
            <div class="mb-3">
                <p class="text-xs text-gray-600 line-clamp-2"><?php echo htmlspecialchars($row['description']); ?></p>
            </div>

            <!-- Action Buttons -->
            <div class="flex gap-2">
                <button onclick="showReviewDetails(<?= (int)$row['menu_id'] ?>)" 
                        class="btn btn-sm bg-[#001f54] hover:bg-[#001a44] text-white border-0 flex-1">
                    <i data-lucide="eye" class="w-4 h-4 mr-1"></i>
                    Review Details
                </button>
            </div>

            <!-- Footer -->
            <div class="mt-3 pt-3 border-t border-gray-200 flex justify-between items-center text-xs text-gray-500">
                <div class="flex items-center gap-1">
                    <i data-lucide="calendar" class="w-3 h-3"></i>
                    <span><?php echo date('M j, Y', strtotime($row['created_at'])); ?></span>
                </div>
                <div class="flex items-center gap-1">
                    <i data-lucide="clock" class="w-3 h-3"></i>
                    <span><?php echo date('g:i A', strtotime($row['created_at'])); ?></span>
                </div>
            </div>
        </div>
    </div>
    <?php endwhile; ?>
</div>

                <!-- Pagination -->
                <div class="mt-6 flex flex-col sm:flex-row justify-between items-center gap-4">
                    <div class="text-sm text-gray-600">
                        Showing <span class="font-semibold"><?php echo ($offset + 1); ?>-<?php echo min($offset + $limit, $total_items); ?></span> of 
                        <span class="font-semibold"><?php echo $total_items; ?></span> items
                    </div>
                    <div class="join">
                        <?php if ($page > 1): ?>
                            <a href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&category=<?php echo urlencode($category_filter); ?>&status=<?php echo urlencode($status_filter); ?>" 
                               class="join-item btn btn-sm btn-outline border-gray-300">Previous</a>
                        <?php endif; ?>
                        
                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&category=<?php echo urlencode($category_filter); ?>&status=<?php echo urlencode($status_filter); ?>" 
                               class="join-item btn btn-sm btn-outline border-gray-300 <?php echo $i == $page ? 'bg-blue-50 text-blue-600' : ''; ?>">
                                <?php echo $i; ?>
                            </a>
                        <?php endfor; ?>
                        
                        <?php if ($page < $total_pages): ?>
                            <a href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&category=<?php echo urlencode($category_filter); ?>&status=<?php echo urlencode($status_filter); ?>" 
                               class="join-item btn btn-sm btn-outline border-gray-300">Next</a>
                        <?php endif; ?>
                    </div>
                </div>
                <?php else: ?>
                <!-- Empty State -->
                <div class="text-center py-12">
                    <div class="w-24 h-24 mx-auto mb-4 rounded-full bg-green-100 flex items-center justify-center">
                        <i data-lucide="check-circle" class="w-12 h-12 text-green-600"></i>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-800 mb-2">No Items Found</h3>
                    <p class="text-gray-600 mb-6">No menu items match your current filters.</p>
                    <button onclick="clearFilters()" class="btn bg-[#001f54] text-white">
                        <i data-lucide="refresh-cw" class="w-4 h-4 mr-2"></i>
                        Clear Filters
                    </button>
                </div>
                <?php endif; ?>
            </section>
        </main>
    </div>
</div>

<!-- Review Details Modal -->
<input type="checkbox" id="review-details-modal" class="modal-toggle" />
<div class="modal">
    <div class="modal-box max-w-6xl p-0 rounded-lg overflow-hidden bg-white relative">
        <!-- Header with Close Button -->
        <div class="sticky top-0 bg-white z-10 border-b border-gray-200 px-6 py-4 flex justify-between items-center shadow-sm">
            <h3 class="font-bold text-xl text-gray-800 flex items-center gap-2">
                <i data-lucide="clipboard-list" class="w-6 h-6 text-amber-500"></i>
                Menu Item Review
            </h3>
            <label for="review-details-modal" 
                   class="btn btn-ghost btn-sm btn-circle text-gray-500"
                   title="Close (ESC)">
                <i data-lucide="x" class="w-5 h-5"></i>
            </label>
        </div>
        
        <div class="max-h-[75vh] overflow-y-auto p-6">
            <div id="review-details-content" class="space-y-6">
                <!-- Review content will load here -->
            </div>
        </div>
    </div>
    
    <!-- Backdrop click to close -->
    <label class="modal-backdrop" for="review-details-modal">Close</label>
</div>


<script>
// Show review details with AJAX
function showReviewDetails(menuId) {
    // Show loading state
    document.getElementById('review-details-content').innerHTML = `
        <div class="flex justify-center items-center py-12">
            <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-500"></div>
        </div>
    `;
    
    // Open modal first
    document.getElementById('review-details-modal').checked = true;
    
    // Fetch data via AJAX
    fetch('get_review_details.php?menu_id=' + menuId)
        .then(res => res.json())
        .then(data => {
            if (data.status === 'success') {
                populateModal(data.data);
                // Re-initialize Lucide icons for the new content
                if (window.lucide) {
                    lucide.createIcons();
                }
            } else {
                document.getElementById('review-details-content').innerHTML = `
                    <div class="alert alert-error">
                        <i data-lucide="alert-circle" class="w-6 h-6"></i>
                        <span>${data.message}</span>
                    </div>
                `;
            }
        })
        .catch(err => {
            console.error('Error loading review details:', err);
            document.getElementById('review-details-content').innerHTML = `
                <div class="alert alert-error">
                    <i data-lucide="alert-circle" class="w-6 h-6"></i>
                    <span>Failed to load menu item details</span>
                </div>
            `;
        });
}

// Populate modal with data
function populateModal(menuItem) {
    const statusBadges = {
        'Under review': 'bg-amber-100 text-amber-800 border-amber-200',
        'For compliance review': 'bg-purple-100 text-purple-800 border-purple-200',
        'Approved': 'bg-green-100 text-green-800 border-green-200',
        'Rejected': 'bg-red-100 text-red-800 border-red-200',
        'For posting': 'bg-blue-100 text-blue-800 border-blue-200'
    };

    const statusIcons = {
        'Under review': 'clock',
        'For compliance review': 'shield-alert',
        'Approved': 'check-circle',
        'Rejected': 'x-circle',
        'For posting': 'upload'
    };

    const prepTimeRating = menuItem.prep_time <= 20 ? 'Fast' : (menuItem.prep_time <= 30 ? 'Moderate' : 'Slow');
    const prepTimeColor = menuItem.prep_time <= 20 ? 'text-green-600' : (menuItem.prep_time <= 30 ? 'text-amber-600' : 'text-red-600');

    // Realistic nutritional data based on category
    const nutritionalData = getNutritionalData(menuItem.category, menuItem.name);

    const modalContent = `
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Left Column - Basic Information -->
            <div class="space-y-6">
                <!-- Header Section -->
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-4">
                        ${menuItem.image_url ? `
                       
                        ` : `
                        <div class="w-20 h-20 rounded-xl bg-gray-100 text-gray-400 flex items-center justify-center">
                            <i data-lucide="utensils" class="w-8 h-8"></i>
                        </div>
                        `}
                        <div>
                            <h4 class="text-xl font-bold text-gray-800">${menuItem.name}</h4>
                            <p class="text-sm text-gray-600">ID: ${menuItem.menu_id}</p>
                        </div>
                    </div>
                    <div class="flex flex-col items-end gap-2">
                        <span class="px-3 py-1 rounded-full text-sm font-medium border ${statusBadges[menuItem.status] || 'bg-gray-100 text-gray-800 border-gray-200'}">
                            <i data-lucide="${statusIcons[menuItem.status] || 'file'}" class="w-3 h-3 inline mr-1"></i>
                            ${menuItem.status}
                        </span>
                        <p class="text-2xl font-bold text-green-600">₱${parseFloat(menuItem.price).toFixed(2)}</p>
                    </div>
                </div>

                <!-- Basic Information Card -->
                <div class="bg-gray-50 rounded-xl p-5 border border-gray-200">
                    <h5 class="text-lg font-semibold text-gray-800 mb-4 flex items-center gap-2">
                        <i data-lucide="info" class="w-5 h-5 text-blue-500"></i>
                        Basic Information
                    </h5>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="text-sm font-medium text-gray-600">Category</label>
                            <p class="text-gray-800 font-medium capitalize">${menuItem.category}</p>
                        </div>
                        <div>
                            <label class="text-sm font-medium text-gray-600">Variant</label>
                            <p class="text-gray-800 font-medium">${menuItem.variant}</p>
                        </div>
                        <div>
                            <label class="text-sm font-medium text-gray-600">Prep Time</label>
                            <p class="text-gray-800 font-medium">${menuItem.prep_time} minutes</p>
                        </div>
                        <div>
                            <label class="text-sm font-medium text-gray-600">Availability</label>
                            <p class="text-gray-800 font-medium">${menuItem.availability}</p>
                        </div>
                    </div>
                </div>

                <!-- Description Card -->
                <div class="bg-gray-50 rounded-xl p-5 border border-gray-200">
                    <h5 class="text-lg font-semibold text-gray-800 mb-3 flex items-center gap-2">
                        <i data-lucide="file-text" class="w-5 h-5 text-blue-500"></i>
                        Description
                    </h5>
                    <p class="text-gray-700 leading-relaxed">${menuItem.description}</p>
                </div>

                <!-- Timeline Card -->
                <div class="bg-gray-50 rounded-xl p-5 border border-gray-200">
                    <h5 class="text-lg font-semibold text-gray-800 mb-4 flex items-center gap-2">
                        <i data-lucide="history" class="w-5 h-5 text-amber-500"></i>
                        Activity Timeline
                    </h5>
                    <div class="space-y-4">
                        <div class="flex items-start gap-3">
                            <div class="w-8 h-8 rounded-full bg-green-100 text-green-600 flex items-center justify-center flex-shrink-0">
                                <i data-lucide="plus" class="w-4 h-4"></i>
                            </div>
                            <div>
                                <p class="font-medium text-gray-800">Menu item created</p>
                                <p class="text-sm text-gray-600">${new Date(menuItem.created_at).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric', hour: 'numeric', minute: '2-digit' })}</p>
                            </div>
                        </div>
                        
                        <div class="flex items-start gap-3">
                            <div class="w-8 h-8 rounded-full bg-amber-100 text-amber-600 flex items-center justify-center flex-shrink-0">
                                <i data-lucide="${statusIcons[menuItem.status] || 'file'}" class="w-4 h-4"></i>
                            </div>
                            <div>
                                <p class="font-medium text-gray-800">Current status: ${menuItem.status}</p>
                                <p class="text-sm text-gray-600">Awaiting review action</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Column - Nutrition & Actions -->
            <div class="space-y-6">
                <!-- Nutritional Information Card -->
                <div class="bg-gradient-to-r from-purple-50 to-blue-50 rounded-xl p-5 border border-purple-200">
                    <h5 class="text-lg font-semibold text-gray-800 mb-4 flex items-center gap-2">
                        <i data-lucide="apple" class="w-5 h-5 text-purple-500"></i>
                        Nutritional Facts
                    </h5>
                    <div class="space-y-4">
                        <!-- Serving Size -->
                        <div class="flex justify-between items-center pb-3 border-b border-purple-200">
                            <span class="text-sm font-medium text-gray-700">Serving Size</span>
                            <span class="text-sm text-gray-600">1 serving (${nutritionalData.servingSize})</span>
                        </div>
                        
                        <!-- Calories -->
                        <div class="flex justify-between items-center pb-2">
                            <span class="text-sm font-medium text-gray-700">Calories</span>
                            <span class="text-lg font-bold text-purple-600">${nutritionalData.calories}</span>
                        </div>
                        
                        <!-- Macronutrients -->
                        <div class="space-y-2">
                            <div class="flex justify-between items-center">
                                <span class="text-sm text-gray-600">Total Fat</span>
                                <span class="text-sm font-medium">${nutritionalData.fat}g</span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-sm text-gray-600">Saturated Fat</span>
                                <span class="text-sm font-medium">${nutritionalData.saturatedFat}g</span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-sm text-gray-600">Cholesterol</span>
                                <span class="text-sm font-medium">${nutritionalData.cholesterol}mg</span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-sm text-gray-600">Sodium</span>
                                <span class="text-sm font-medium">${nutritionalData.sodium}mg</span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-sm text-gray-600">Total Carbohydrate</span>
                                <span class="text-sm font-medium">${nutritionalData.carbs}g</span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-sm text-gray-600">Dietary Fiber</span>
                                <span class="text-sm font-medium">${nutritionalData.fiber}g</span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-sm text-gray-600">Sugars</span>
                                <span class="text-sm font-medium">${nutritionalData.sugars}g</span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-sm text-gray-600">Protein</span>
                                <span class="text-sm font-medium">${nutritionalData.protein}g</span>
                            </div>
                        </div>
                        
                        <!-- Vitamins & Minerals -->
                        <div class="grid grid-cols-2 gap-3 pt-3 border-t border-purple-200">
                            <div class="text-center">
                                <div class="text-sm font-medium text-blue-600">Vitamin A</div>
                                <div class="text-xs text-gray-600">${nutritionalData.vitaminA}%</div>
                            </div>
                            <div class="text-center">
                                <div class="text-sm font-medium text-blue-600">Vitamin C</div>
                                <div class="text-xs text-gray-600">${nutritionalData.vitaminC}%</div>
                            </div>
                            <div class="text-center">
                                <div class="text-sm font-medium text-blue-600">Calcium</div>
                                <div class="text-xs text-gray-600">${nutritionalData.calcium}%</div>
                            </div>
                            <div class="text-center">
                                <div class="text-sm font-medium text-blue-600">Iron</div>
                                <div class="text-xs text-gray-600">${nutritionalData.iron}%</div>
                            </div>
                        </div>
                    </div>
                    <div class="mt-4 text-xs text-gray-600 text-center">
                        <i data-lucide="info" class="w-3 h-3 inline mr-1"></i>
                        Percent Daily Values are based on a 2,000 calorie diet
                    </div>
                </div>

                

                <!-- Action Buttons -->
                <div class="bg-white rounded-xl p-5 border border-gray-200 sticky bottom-0">
                    <h5 class="text-lg font-semibold text-gray-800 mb-4 flex items-center gap-2">
                        <i data-lucide="settings" class="w-5 h-5 text-gray-500"></i>
                        Review Actions
                    </h5>
                    
                    <div class="grid grid-cols-2 gap-3">
                        <button onclick="approveMenuItem(${menuItem.menu_id})" 
                                class="btn bg-green-600 text-white border-none">
                            <i data-lucide="check" class="w-4 h-4 mr-2"></i>
                            Approve
                        </button>
                        <button onclick="rejectMenuItem(${menuItem.menu_id})" 
                                class="btn bg-red-600 text-white border-none">
                            <i data-lucide="x" class="w-4 h-4 mr-2"></i>
                            Reject
                        </button>
                    </div>
                    
                    <!-- Additional Actions -->
                    <div class="mt-4 pt-4 border-t border-gray-200">
                        <div class="grid grid-cols-2 gap-2">
                            <button onclick="requestComplianceReview(${menuItem.menu_id})" 
                                    class="btn btn-sm btn-outline border-purple-300 text-purple-600">
                                <i data-lucide="shield-alert" class="w-4 h-4 mr-2"></i>
                                Compliance
                            </button>
                            <button onclick="editMenuItem(${menuItem.menu_id})" 
                                    class="btn btn-sm btn-outline border-gray-300 text-gray-700">
                                <i data-lucide="edit" class="w-4 h-4 mr-2"></i>
                                Edit
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    `;

    document.getElementById('review-details-content').innerHTML = modalContent;
}

// Nutritional data generator
function getNutritionalData(category, name) {
    const baseData = {
        servingSize: '250g',
        calories: 0,
        fat: 0,
        saturatedFat: 0,
        cholesterol: 0,
        sodium: 0,
        carbs: 0,
        fiber: 0,
        sugars: 0,
        protein: 0,
        vitaminA: 0,
        vitaminC: 0,
        calcium: 0,
        iron: 0
    };

    // Set nutritional values based on category
    switch(category.toLowerCase()) {
        case 'appetizers':
            baseData.calories = Math.floor(Math.random() * 200) + 150;
            baseData.fat = (Math.random() * 12 + 8).toFixed(1);
            baseData.carbs = (Math.random() * 20 + 15).toFixed(1);
            baseData.protein = (Math.random() * 8 + 5).toFixed(1);
            break;
        case 'mains':
            baseData.calories = Math.floor(Math.random() * 400) + 300;
            baseData.fat = (Math.random() * 25 + 15).toFixed(1);
            baseData.carbs = (Math.random() * 45 + 30).toFixed(1);
            baseData.protein = (Math.random() * 35 + 20).toFixed(1);
            break;
        case 'desserts':
            baseData.calories = Math.floor(Math.random() * 350) + 200;
            baseData.fat = (Math.random() * 15 + 10).toFixed(1);
            baseData.carbs = (Math.random() * 50 + 35).toFixed(1);
            baseData.protein = (Math.random() * 6 + 3).toFixed(1);
            break;
        case 'drinks':
            baseData.calories = Math.floor(Math.random() * 150) + 50;
            baseData.fat = (Math.random() * 2).toFixed(1);
            baseData.carbs = (Math.random() * 25 + 15).toFixed(1);
            baseData.protein = (Math.random() * 3).toFixed(1);
            baseData.servingSize = '300ml';
            break;
        default:
            baseData.calories = Math.floor(Math.random() * 300) + 200;
            baseData.fat = (Math.random() * 15 + 8).toFixed(1);
            baseData.carbs = (Math.random() * 35 + 20).toFixed(1);
            baseData.protein = (Math.random() * 15 + 8).toFixed(1);
    }

    // Calculate derived values
    baseData.saturatedFat = (baseData.fat * 0.3).toFixed(1);
    baseData.cholesterol = Math.floor(baseData.fat * 10);
    baseData.sodium = Math.floor(Math.random() * 400) + 200;
    baseData.fiber = (Math.random() * 5 + 2).toFixed(1);
    baseData.sugars = (baseData.carbs * 0.4).toFixed(1);
    baseData.vitaminA = Math.floor(Math.random() * 15) + 5;
    baseData.vitaminC = Math.floor(Math.random() * 20) + 10;
    baseData.calcium = Math.floor(Math.random() * 15) + 5;
    baseData.iron = Math.floor(Math.random() * 10) + 5;

    return baseData;
}

// CRUD Actions with SweetAlert
function approveMenuItem(menuId) {
    Swal.fire({
        title: 'Approve Menu Item?',
        text: 'This will approve the menu item for posting.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#10B981',
        cancelButtonColor: '#6B7280',
        confirmButtonText: 'Yes, approve it!',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (result.isConfirmed) {
            fetch('update_menu_status.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'menu_id=' + menuId + '&status=Approved'
            })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    Swal.fire({
                        title: 'Approved!',
                        text: 'Menu item has been approved.',
                        icon: 'success',
                        confirmButtonColor: '#10B981'
                    }).then(() => {
                        document.getElementById('review-details-modal').checked = false;
                        location.reload();
                    });
                } else {
                    Swal.fire({
                        title: 'Error!',
                        text: data.message,
                        icon: 'error',
                        confirmButtonColor: '#EF4444'
                    });
                }
            })
            .catch(err => {
                console.error('Error:', err);
                Swal.fire({
                    title: 'Error!',
                    text: 'An error occurred while approving the menu item.',
                    icon: 'error',
                    confirmButtonColor: '#EF4444'
                });
            });
        }
    });
}

function rejectMenuItem(menuId) {
    Swal.fire({
        title: 'Reject Menu Item?',
        input: 'text',
        inputLabel: 'Reason for rejection',
        inputPlaceholder: 'Enter the reason for rejection...',
        inputAttributes: {
            maxlength: 200
        },
        showCancelButton: true,
        confirmButtonColor: '#EF4444',
        cancelButtonColor: '#6B7280',
        confirmButtonText: 'Reject',
        cancelButtonText: 'Cancel',
        inputValidator: (value) => {
            if (!value) {
                return 'Please provide a reason for rejection!';
            }
        }
    }).then((result) => {
        if (result.isConfirmed) {
            fetch('update_menu_status.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'menu_id=' + menuId + '&status=Rejected&reason=' + encodeURIComponent(result.value)
            })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    Swal.fire({
                        title: 'Rejected!',
                        text: 'Menu item has been rejected.',
                        icon: 'success',
                        confirmButtonColor: '#EF4444'
                    }).then(() => {
                        document.getElementById('review-details-modal').checked = false;
                        location.reload();
                    });
                } else {
                    Swal.fire({
                        title: 'Error!',
                        text: data.message,
                        icon: 'error',
                        confirmButtonColor: '#EF4444'
                    });
                }
            })
            .catch(err => {
                console.error('Error:', err);
                Swal.fire({
                    title: 'Error!',
                    text: 'An error occurred while rejecting the menu item.',
                    icon: 'error',
                    confirmButtonColor: '#EF4444'
                });
            });
        }
    });
}

function requestComplianceReview(menuId) {
    Swal.fire({
        title: 'Send for Compliance Review?',
        input: 'text',
        inputLabel: 'Compliance requirements',
        inputPlaceholder: 'Specify compliance requirements...',
        inputAttributes: {
            maxlength: 200
        },
        showCancelButton: true,
        confirmButtonColor: '#8B5CF6',
        cancelButtonColor: '#6B7280',
        confirmButtonText: 'Send for Review',
        cancelButtonText: 'Cancel',
        inputValidator: (value) => {
            if (!value) {
                return 'Please specify compliance requirements!';
            }
        }
    }).then((result) => {
        if (result.isConfirmed) {
            fetch('update_menu_status.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'menu_id=' + menuId + '&status=For compliance review&reason=' + encodeURIComponent(result.value)
            })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    Swal.fire({
                        title: 'Sent for Review!',
                        text: 'Menu item has been sent for compliance review.',
                        icon: 'success',
                        confirmButtonColor: '#8B5CF6'
                    }).then(() => {
                        document.getElementById('review-details-modal').checked = false;
                        location.reload();
                    });
                } else {
                    Swal.fire({
                        title: 'Error!',
                        text: data.message,
                        icon: 'error',
                        confirmButtonColor: '#EF4444'
                    });
                }
            })
            .catch(err => {
                console.error('Error:', err);
                Swal.fire({
                    title: 'Error!',
                    text: 'An error occurred while sending for compliance review.',
                    icon: 'error',
                    confirmButtonColor: '#EF4444'
                });
            });
        }
    });
}

function editMenuItem(menuId) {
    Swal.fire({
        title: 'Edit Menu Item',
        text: 'Redirecting to edit page...',
        icon: 'info',
        showCancelButton: true,
        confirmButtonColor: '#3B82F6',
        cancelButtonColor: '#6B7280',
        confirmButtonText: 'Continue',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = 'edit_menu_item.php?id=' + menuId;
        }
    });
}
</script>


<script>
// Enhanced modal interactions
document.addEventListener('DOMContentLoaded', function() {
    // ESC key support
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            const modal = document.getElementById('review-details-modal');
            if (modal && modal.checked) {
                modal.checked = false;
            }
        }
    });

    // Re-initialize Lucide icons when modal opens
    const modal = document.getElementById('review-details-modal');
    if (modal) {
        modal.addEventListener('change', function() {
            if (this.checked && window.lucide) {
                // Small delay to ensure content is loaded
                setTimeout(() => {
                    lucide.createIcons();
                }, 100);
            }
        });
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
  const apiURL = "../../notification_api.php";

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

<!-- Include your existing scripts -->
<script src="../../JavaScript/sidebar.js"></script>
<script src="../../JavaScript/soliera.js"></script>

</body>
</html>