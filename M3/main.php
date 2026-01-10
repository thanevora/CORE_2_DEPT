<?php
session_start();
include("../main_connection.php");

// Database configuration
$db_name = "rest_m3_menu";

// Check database connection
if (!isset($connections[$db_name])) {
    die("❌ Connection not found for $db_name");
}

$conn = $connections[$db_name];

// ============================================
// PAGINATION & FILTERING LOGIC
// ============================================

// Get filter parameters
$search = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';
$category = isset($_GET['category']) ? $conn->real_escape_string($_GET['category']) : '';
$status = isset($_GET['status']) ? $conn->real_escape_string($_GET['status']) : '';
$variant = isset($_GET['variant']) ? $conn->real_escape_string($_GET['variant']) : '';

// Build WHERE conditions
$whereConditions = [];
$params = [];

if (!empty($search)) {
    $whereConditions[] = "(name LIKE '%$search%' OR description LIKE '%$search%')";
}

if (!empty($category) && $category !== 'all') {
    $whereConditions[] = "category = '$category'";
}

if (!empty($status) && $status !== 'all') {
    $whereConditions[] = "status = '$status'";
}

if (!empty($variant) && $variant !== 'all') {
    $whereConditions[] = "variant = '$variant'";
}

$whereClause = '';
if (!empty($whereConditions)) {
    $whereClause = 'WHERE ' . implode(' AND ', $whereConditions);
}

// Get total count for pagination
$countQuery = "SELECT COUNT(*) as total FROM menu $whereClause";
$countResult = $conn->query($countQuery);
$totalItems = $countResult ? $countResult->fetch_assoc()['total'] : 0;

// Pagination variables
$itemsPerPage = 9;
$currentPage = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$totalPages = ceil($totalItems / $itemsPerPage);
$offset = ($currentPage - 1) * $itemsPerPage;

// Fetch menu items with pagination and filtering
$sql = "SELECT menu_id, name, category, description, variant, price, status, image_url, created_at, updated_at 
        FROM menu 
        $whereClause 
        ORDER BY updated_at DESC 
        LIMIT $offset, $itemsPerPage";

$result_sql = $conn->query($sql);
if (!$result_sql) {
    die("SQL Error: " . $conn->error);
}

// Fetch menu statistics
$query = "SELECT 
            (SELECT COUNT(*) FROM menu) AS total_menu,
            (SELECT COUNT(DISTINCT category) FROM menu) AS total_categories,
            (SELECT COUNT(*) FROM menu WHERE category = 'seasonal') AS seasonal,
            (SELECT COUNT(*) FROM menu WHERE category = 'popular') AS popular";

$result = $conn->query($query);
if (!$result) {
    die("Count query failed: " . $conn->error);
}

// Get counts
$row = $result->fetch_assoc();
$total_menu_count = $row['total_menu'] ?? 0;
$total_categories_count = $row['total_categories'] ?? 0;
$seasonal_count = $row['seasonal'] ?? 0;   
$popular_count = $row['popular'] ?? 0;

// Fetch inventory items for the form
$inv_query = "SELECT item_id, item_name FROM inventory_and_stock";
$inv_result = $connections['rest_m2_inventory']->query($inv_query);
$inventory_items = [];
while ($inv = $inv_result->fetch_assoc()) {
    $inventory_items[] = $inv;
}

// Get unique categories, statuses, variants for filters
$categoriesQuery = "SELECT DISTINCT category FROM menu WHERE category IS NOT NULL AND category != '' ORDER BY category";
$categoriesResult = $conn->query($categoriesQuery);
$categories = [];
while ($cat = $categoriesResult->fetch_assoc()) {
    $categories[] = $cat['category'];
}

$statusesQuery = "SELECT DISTINCT status FROM menu WHERE status IS NOT NULL AND status != '' ORDER BY status";
$statusesResult = $conn->query($statusesQuery);
$statuses = [];
while ($stat = $statusesResult->fetch_assoc()) {
    $statuses[] = $stat['status'];
}

$variantsQuery = "SELECT DISTINCT variant FROM menu WHERE variant IS NOT NULL AND variant != '' ORDER BY variant";
$variantsResult = $conn->query($variantsQuery);
$variants = [];
while ($var = $variantsResult->fetch_assoc()) {
    $variants[] = $var['variant'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Menu Management | Soliera Restaurant</title>
    <?php include '../header.php'; ?>
    <style>
        .glass-effect {
            background: rgba(255, 255, 255, 0.7);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.3);
        }
        
        .menu-item-card {
            transition: all 0.3s ease;
        }
        
        .menu-item-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }
        
        .popular-item {
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0% { box-shadow: 0 0 0 0 rgba(59, 130, 246, 0.4); }
            70% { box-shadow: 0 0 0 10px rgba(59, 130, 246, 0); }
            100% { box-shadow: 0 0 0 0 rgba(59, 130, 246, 0); }
        }
        
        /* Responsive adjustments */
        @media (max-width: 768px) {
            .modal-box {
                margin: 1rem;
                padding: 1rem;
                width: calc(100% - 2rem);
            }
            
            .ingredient-row {
                flex-direction: column;
            }
            
            .ingredient-qty {
                width: 100% !important;
            }
        }
        
        /* Form styling */
        .form-control {
            margin-bottom: 1rem;
        }
        
        .label {
            margin-bottom: 0.5rem;
        }
        
        .ingredient-row {
            display: flex;
            gap: 0.5rem;
            margin-bottom: 0.5rem;
            align-items: center;
        }
        
        .ingredient-qty {
            width: 100px;
        }
        
        /* Filter dropdown animation */
        .filter-dropdown {
            transition: all 0.3s ease;
        }
        
        /* Loading spinner */
        .loading-spinner {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid #f3f3f3;
            border-top: 3px solid #001f54;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
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
            <!-- Menu Summary Cards -->
            <section class="glass-effect p-6 rounded-2xl shadow-sm mb-6">
                <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
                    <h2 class="text-2xl font-bold text-gray-800 flex items-center">
                        <span class="p-2 mr-3 rounded-lg bg-blue-100/50 text-blue-600">
                            <i data-lucide="utensils" class="w-5 h-5"></i>
                        </span>
                        Menu Overview
                    </h2>
                </div>

               <!-- Menu Dashboard Cards -->
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 h-full">
    
    <!-- Total Menu Items Card -->
    <div class="bg-white text-black shadow-lg p-5 rounded-xl hover:shadow-xl transition-all duration-300">
        <div class="flex justify-between items-start">
            <div>
                <p class="text-sm font-medium text-[#001f54]">Total Items</p>
                <h3 class="text-3xl font-bold mt-1"><?php echo $total_menu_count; ?></h3>
                <p class="text-xs text-gray-500 mt-1">All menu items</p>
            </div>
            <div class="p-3 rounded-lg bg-[#001f54] flex items-center justify-center">
                <i data-lucide="clipboard-list" class="w-6 h-6 text-[#F7B32B]"></i>
            </div>
        </div>
    </div>

    <!-- Popular Items Card -->
    <div class="bg-white text-black shadow-lg p-5 rounded-xl hover:shadow-xl transition-all duration-300">
        <div class="flex justify-between items-start">
            <div>
                <p class="text-sm font-medium text-[#001f54]">Popular Items</p>
                <h3 class="text-3xl font-bold mt-1"><?php echo $popular_count; ?></h3>
                <p class="text-xs text-gray-500 mt-1">Customer favorites</p>
            </div>
            <div class="p-3 rounded-lg bg-[#001f54] flex items-center justify-center">
                <i data-lucide="star" class="w-6 h-6 text-[#F7B32B]"></i>
            </div>
        </div>
    </div>

    <!-- Categories Card -->
    <div class="bg-white text-black shadow-lg p-5 rounded-xl hover:shadow-xl transition-all duration-300">
        <div class="flex justify-between items-start">
            <div>
                <p class="text-sm font-medium text-[#001f54]">Categories</p>
                <h3 class="text-3xl font-bold mt-1"><?php echo $total_categories_count; ?></h3>
                <p class="text-xs text-gray-500 mt-1">Menu sections</p>
            </div>
            <div class="p-3 rounded-lg bg-[#001f54] flex items-center justify-center">
                <i data-lucide="layers" class="w-6 h-6 text-[#F7B32B]"></i>
            </div>
        </div>
    </div>

    <!-- Seasonal Items Card -->
    <div class="bg-white text-black shadow-lg p-5 rounded-xl hover:shadow-xl transition-all duration-300">
        <div class="flex justify-between items-start">
            <div>
                <p class="text-sm font-medium text-[#001f54]">Seasonal Items</p>
                <h3 class="text-3xl font-bold mt-1"><?php echo $seasonal_count; ?></h3>
                <p class="text-xs text-gray-500 mt-1">Limited time offers</p>
            </div>
            <div class="p-3 rounded-lg bg-[#001f54] flex items-center justify-center">
                <i data-lucide="leaf" class="w-6 h-6 text-[#F7B32B]"></i>
            </div>
        </div>
    </div>
</div>
            </section>

            <!-- Menu Management Section -->
            <section class="glass-effect p-6 rounded-xl shadow-sm">
                <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4">
                    <div>
                        <h2 class="text-xl font-bold flex items-center gap-2 text-gray-800 mb-2">
                            <i data-lucide="clipboard-list" class="w-5 h-5 text-blue-500"></i>
                            <span>Menu Items</span>
                        </h2>
                        <p class="text-sm text-gray-500">Manage your restaurant menu items</p>
                    </div>
                    <div class="flex flex-wrap gap-3">
                        <!-- Add Menu Item Button -->
                        <label for="add-menu-item-modal" class="btn btn-primary px-4 py-2 rounded-lg flex items-center gap-2 shadow-md hover:shadow-lg cursor-pointer text-sm" style="background-color: #001f54; color: white; border: none;">
                            <i data-lucide="plus" class="w-4 h-4"></i>
                            <span>Add Menu Item</span>
                        </label>
                    </div>
                </div>
                
                <!-- Filters & Search Bar -->
                <div class="bg-gray-50 rounded-xl p-4 mb-6 border border-gray-200">
                    <form method="GET" action="" id="filter-form" class="space-y-4">
                        <!-- Search Bar -->
                        <div class="relative">
                            <div class="flex items-center">
                                <i data-lucide="search" class="absolute left-3 top-1/2 transform -translate-y-1/2 w-5 h-5 text-gray-400"></i>
                                <input type="text" 
                                       name="search" 
                                       placeholder="Search menu items..." 
                                       value="<?php echo htmlspecialchars($search); ?>"
                                       class="input input-bordered w-full pl-10 py-3 rounded-lg border-gray-300 focus:border-[#001f54] focus:ring-2 focus:ring-blue-100 transition-colors bg-white">
                                <button type="submit" class="btn bg-[#001f54] text-white ml-2 px-4 py-3 rounded-lg hover:bg-[#001a44]">
                                    <i data-lucide="filter" class="w-4 h-4"></i>
                                    <span class="hidden md:inline ml-1">Filter</span>
                                </button>
                            </div>
                        </div>
                        
                        <!-- Filter Controls -->
                        <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-4 gap-4">
                            <!-- Category Filter -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Category</label>
                                <select name="category" class="select select-bordered w-full bg-white border-gray-300 focus:border-[#001f54] focus:ring-2 focus:ring-blue-100 transition-colors">
                                    <option value="all" <?php echo empty($category) || $category === 'all' ? 'selected' : ''; ?>>All Categories</option>
                                    <?php foreach ($categories as $cat): ?>
                                        <option value="<?php echo htmlspecialchars($cat); ?>" <?php echo $category === $cat ? 'selected' : ''; ?>>
                                            <?php echo ucfirst(htmlspecialchars($cat)); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            
                            
                            <!-- Variant Filter -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Variant</label>
                                <select name="variant" class="select select-bordered w-full bg-white border-gray-300 focus:border-[#001f54] focus:ring-2 focus:ring-blue-100 transition-colors">
                                    <option value="all" <?php echo empty($variant) || $variant === 'all' ? 'selected' : ''; ?>>All Variants</option>
                                    <?php foreach ($variants as $var): ?>
                                        <option value="<?php echo htmlspecialchars($var); ?>" <?php echo $variant === $var ? 'selected' : ''; ?>>
                                            <?php echo ucfirst(htmlspecialchars($var)); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <!-- Reset Button -->
                            <div class="flex items-end">
                                <a href="?" class="btn btn-outline border-gray-300 text-gray-700 hover:bg-gray-50 w-full py-3">
                                    <i data-lucide="refresh-cw" class="w-4 h-4 mr-2"></i>
                                    Reset Filters
                                </a>
                            </div>
                        </div>
                        
                        <!-- Active Filters Badges -->
                        <?php if (!empty($search) || !empty($category) && $category !== 'all' || !empty($status) && $status !== 'all' || !empty($variant) && $variant !== 'all'): ?>
                        <div class="pt-2">
                            <p class="text-sm text-gray-500 mb-2">Active filters:</p>
                            <div class="flex flex-wrap gap-2">
                                <?php if (!empty($search)): ?>
                                    <span class="badge badge-info gap-1">
                                        Search: "<?php echo htmlspecialchars($search); ?>"
                                        <a href="?<?php echo http_build_query(array_merge($_GET, ['search' => ''])); ?>" class="ml-1">
                                            <i data-lucide="x" class="w-3 h-3"></i>
                                        </a>
                                    </span>
                                <?php endif; ?>
                                <?php if (!empty($category) && $category !== 'all'): ?>
                                    <span class="badge badge-primary gap-1">
                                        Category: <?php echo ucfirst(htmlspecialchars($category)); ?>
                                        <a href="?<?php echo http_build_query(array_merge($_GET, ['category' => 'all'])); ?>" class="ml-1">
                                            <i data-lucide="x" class="w-3 h-3"></i>
                                        </a>
                                    </span>
                                <?php endif; ?>
                                <?php if (!empty($status) && $status !== 'all'): ?>
                                    <span class="badge badge-secondary gap-1">
                                        Status: <?php echo ucfirst(htmlspecialchars($status)); ?>
                                        <a href="?<?php echo http_build_query(array_merge($_GET, ['status' => 'all'])); ?>" class="ml-1">
                                            <i data-lucide="x" class="w-3 h-3"></i>
                                        </a>
                                    </span>
                                <?php endif; ?>
                                <?php if (!empty($variant) && $variant !== 'all'): ?>
                                    <span class="badge badge-accent gap-1">
                                        Variant: <?php echo ucfirst(htmlspecialchars($variant)); ?>
                                        <a href="?<?php echo http_build_query(array_merge($_GET, ['variant' => 'all'])); ?>" class="ml-1">
                                            <i data-lucide="x" class="w-3 h-3"></i>
                                        </a>
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endif; ?>
                    </form>
                </div>
                
                <!-- Results Summary -->
                <div class="flex justify-between items-center mb-4">
                    <div class="text-sm text-gray-600">
                        Showing 
                        <span class="font-semibold"><?php echo min(($currentPage - 1) * $itemsPerPage + 1, $totalItems); ?>-<?php echo min($currentPage * $itemsPerPage, $totalItems); ?></span> 
                        of <span class="font-semibold"><?php echo $totalItems; ?></span> items
                        <?php if (!empty($search) || !empty($category) && $category !== 'all' || !empty($status) && $status !== 'all' || !empty($variant) && $variant !== 'all'): ?>
                            <span class="text-gray-500 ml-2">(filtered)</span>
                        <?php endif; ?>
                    </div>
                    <div class="text-sm text-gray-500">
                        Page <?php echo $currentPage; ?> of <?php echo $totalPages; ?>
                    </div>
                </div>
                
                <!-- Menu Items Grid -->
                <?php if ($result_sql->num_rows > 0): ?>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6" id="menu-items-grid">
                    <?php while($row = $result_sql->fetch_assoc()): ?>
                    <div class="menu-item-card bg-white rounded-lg shadow-lg hover:shadow-xl transition-all duration-300 hover:-translate-y-1">
                        
                        <!-- Image Section -->
                        <div class="relative h-56 w-full overflow-hidden rounded-t-lg bg-gray-100">
                            <?php if (!empty($row['image_url'])): ?>
                                <!-- Corrected path for M3/Menu_uploaded directory -->
                                <img src="Menu_uploaded/menu_images/original/<?php echo htmlspecialchars($row['image_url']); ?>" 
                                     alt="<?php echo htmlspecialchars($row['name']); ?>" 
                                     class="w-full h-full object-cover transition-transform duration-500 hover:scale-110"
                                     onerror="this.onerror=null; this.src='data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMjAwIiBoZWlnaHQ9IjIwMCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iMjAwIiBoZWlnaHQ9IjIwMCIgZmlsbD0iI2YwZjBmMCIvPjx0ZXh0IHg9IjEwMCUiIHk9IjUwJSIgZm9udC1mYW1pbHk9IkFyaWFsIiBmb250LXNpemU9IjE0IiB0ZXh0LWFuY2hvcj0ibWlkZGxlIiBmaWxsPSIjOTk5Ij5JbWFnZSBOb3QgRm91bmQ8L3RleHQ+PC9zdmc+'">
                            <?php else: ?>
                                <div class="w-full h-full flex flex-col items-center justify-center bg-gradient-to-br from-gray-100 to-gray-200">
                                    <i data-lucide="utensils" class="w-16 h-16 text-gray-400 mb-3"></i>
                                    <p class="text-sm text-gray-500 font-medium">No image</p>
                                </div>
                            <?php endif; ?>
                            
                            <!-- Status Badge -->
                            <div class="absolute top-4 right-4">
                                <span class="text-xs font-semibold px-3 py-1.5 rounded-full shadow-sm <?php 
                                    echo $row['status'] === 'Available' ? 'bg-green-100 text-green-800 border border-green-200' : 
                                           ($row['status'] === 'Under review' ? 'bg-amber-100 text-amber-800 border border-amber-200' : 
                                           'bg-gray-100 text-gray-800 border border-gray-200');
                                ?>">
                                    <?php echo htmlspecialchars($row['status']); ?>
                                </span>
                            </div>
                            
                            <!-- Category Tag -->
                            <div class="absolute bottom-4 left-4">
                                <span class="text-xs font-medium px-3 py-1.5 rounded-full bg-white/90 backdrop-blur-sm text-gray-800 shadow-sm border border-white/50">
                                    <?php echo htmlspecialchars(ucfirst($row['category'])); ?>
                                </span>
                            </div>
                        </div>
                        
                        <!-- Content Section -->
                        <div class="p-5">
                            <!-- Header with Icon and Name -->
                            <div class="flex items-start justify-between mb-4">
                                <div class="flex items-center gap-3">
                                    <div class="p-2 rounded-lg bg-[#001f54]/10">
                                        <i data-lucide="utensils" class="w-5 h-5 text-[#001f54]"></i>
                                    </div>
                                    <div>
                                        <h3 class="text-lg font-bold text-gray-800 truncate"><?php echo htmlspecialchars($row['name']); ?></h3>
                                        <p class="text-xs text-gray-500 mt-0.5">ID: <?php echo htmlspecialchars($row['menu_id']); ?></p>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Price and Variant Info -->
                            <div class="grid grid-cols-2 gap-4 mb-4">
                                <div class="space-y-1">
                                    <p class="text-xs text-gray-500 font-medium">Price</p>
                                    <div class="flex items-center gap-2">
                                        <i data-lucide="dollar-sign" class="w-4 h-4 text-[#F7B32B]"></i>
                                        <p class="font-bold text-lg text-gray-800">₱ <?php echo number_format($row['price'], 2); ?></p>
                                    </div>
                                </div>
                                <div class="space-y-1">
                                    <p class="text-xs text-gray-500 font-medium">Variant</p>
                                    <div class="flex items-center gap-2">
                                        <i data-lucide="clock" class="w-4 h-4 text-[#001f54]"></i>
                                        <p class="font-medium text-gray-800"><?php echo htmlspecialchars($row['variant']); ?></p>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Description -->
                            <p class="text-sm text-gray-600 line-clamp-2 mb-4"><?php echo htmlspecialchars($row['description']); ?></p>
                            
                            <!-- Prep Time and Actions -->
                            <div class="flex items-center justify-between pt-4 border-t border-gray-100">
                                <div class="flex items-center gap-3">
                                    <button onclick="showMenuItemDetails(<?= (int)$row['menu_id'] ?>)" 
                                        class="btn btn-sm bg-[#001f54] hover:bg-[#001a44] text-white border-0 rounded-lg px-4 py-2 transition-colors flex items-center gap-2">
                                        <i data-lucide="eye" class="w-4 h-4"></i>
                                        View
                                    </button>
                                    
                                    <div class="text-xs text-gray-400">
                                        <i data-lucide="calendar" class="w-3 h-3 inline mr-1"></i>
                                        <?php echo date('M j, Y', strtotime($row['updated_at'])); ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endwhile; ?>
                </div>
                <?php else: ?>
                <!-- No Results -->
                <div class="text-center py-12 bg-gray-50 rounded-xl border border-gray-200">
                    <i data-lucide="search-x" class="w-16 h-16 text-gray-400 mx-auto mb-4"></i>
                    <h3 class="text-lg font-semibold text-gray-700 mb-2">No menu items found</h3>
                    <p class="text-gray-500 mb-4">
                        <?php if (!empty($search) || !empty($category) && $category !== 'all' || !empty($status) && $status !== 'all' || !empty($variant) && $variant !== 'all'): ?>
                            Try adjusting your filters or search terms.
                        <?php else: ?>
                            No menu items have been added yet.
                        <?php endif; ?>
                    </p>
                    <a href="?" class="btn btn-outline border-gray-300 text-gray-700 hover:bg-gray-50">
                        <i data-lucide="refresh-cw" class="w-4 h-4 mr-2"></i>
                        Clear Filters
                    </a>
                </div>
                <?php endif; ?>
                
                <!-- Pagination -->
                <?php if ($totalPages > 1): ?>
                <div class="mt-8 flex flex-col sm:flex-row justify-between items-center gap-4">
                    <div class="text-sm text-gray-600">
                        Showing <?php echo $itemsPerPage; ?> items per page
                    </div>
                    <div class="join">
                        <!-- Previous Button -->
                        <?php if ($currentPage > 1): ?>
                            <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $currentPage - 1])); ?>" 
                               class="join-item btn btn-sm btn-outline border-gray-300">
                                <i data-lucide="chevron-left" class="w-4 h-4"></i>
                                Previous
                            </a>
                        <?php else: ?>
                            <button class="join-item btn btn-sm btn-outline border-gray-300" disabled>
                                <i data-lucide="chevron-left" class="w-4 h-4"></i>
                                Previous
                            </button>
                        <?php endif; ?>
                        
                        <!-- Page Numbers -->
                        <?php 
                        $startPage = max(1, $currentPage - 2);
                        $endPage = min($totalPages, $currentPage + 2);
                        
                        for ($i = $startPage; $i <= $endPage; $i++): 
                        ?>
                            <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>" 
                               class="join-item btn btn-sm btn-outline border-gray-300 <?php echo $i == $currentPage ? 'bg-blue-50 text-blue-600 border-blue-300' : ''; ?>">
                                <?php echo $i; ?>
                            </a>
                        <?php endfor; ?>
                        
                        <!-- Next Button -->
                        <?php if ($currentPage < $totalPages): ?>
                            <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $currentPage + 1])); ?>" 
                               class="join-item btn btn-sm btn-outline border-gray-300">
                                Next
                                <i data-lucide="chevron-right" class="w-4 h-4"></i>
                            </a>
                        <?php else: ?>
                            <button class="join-item btn btn-sm btn-outline border-gray-300" disabled>
                                Next
                                <i data-lucide="chevron-right" class="w-4 h-4"></i>
                            </button>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Page Selector -->
                    <div class="flex items-center gap-2">
                        <span class="text-sm text-gray-600 bg-white">Go to page:</span>
                        <form method="GET" class="flex items-center gap-2">
                            <input type="hidden" name="search" value="<?php echo htmlspecialchars($search); ?>">
                            <input type="hidden" name="category" value="<?php echo htmlspecialchars($category); ?>">
                            <input type="hidden" name="variant" value="<?php echo htmlspecialchars($variant); ?>">
                            <input type="number" 
                                   name="page" 
                                   min="1" 
                                   max="<?php echo $totalPages; ?>" 
                                   value="<?php echo $currentPage; ?>"
                                   class="input input-bordered input-sm w-20 text-center border-gray-300">
                            <button type="submit" class="btn btn-sm btn-ghost">
                                <i data-lucide="arrow-right" class="w-4 h-4"></i>
                            </button>
                        </form>
                    </div>
                </div>
                <?php endif; ?>
            </section>
        </main>
    </div>
</div>

<!-- Menu Item Details Modal (Same as before, just included for completeness) -->
<input type="checkbox" id="menu-item-details-modal" class="modal-toggle" />
<div class="modal modal-bottom sm:modal-middle">
  <div class="modal-box max-w-7xl w-full p-0 rounded-xl shadow-2xl overflow-hidden bg-white" style="max-height: 90vh;">
    <!-- Modal Header -->
    <div class="sticky top-0 z-10 bg-white border-b border-gray-200 px-8 py-5">
      <div class="flex justify-between items-center">
        <div>
          <h3 class="text-2xl font-bold text-gray-800 flex items-center gap-3">
            <i data-lucide="clipboard-list" class="w-7 h-7 text-[#001f54]"></i>
            <span>Menu Item Details</span>
          </h3>
          <p class="text-sm text-gray-500 mt-1">Complete information about the menu item</p>
        </div>
      </div>
    </div>
    
    <!-- Modal Content -->
    <div class="p-0 overflow-y-auto" style="max-height: calc(90vh - 140px);">
      <div id="menu-item-details-content" class="space-y-0">
        <!-- Item details will load here -->
      </div>
    </div>
    
    <!-- Modal Footer -->
    <div class="sticky bottom-0 bg-white border-t border-gray-200 px-8 py-4">
      <div class="flex justify-between items-center">
        <div class="text-sm text-gray-500 flex items-center gap-2">
          <i data-lucide="info" class="w-4 h-4"></i>
        </div>
        <div class="flex gap-3">
          <label for="menu-item-details-modal" 
                 class="btn bg-[#001f54] hover:bg-[#001a44] text-white border-0 font-medium">
            <i data-lucide="check-circle" class="w-4 h-4 mr-2"></i>
            Close
          </label>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Add Menu Item Modal (Same as before) -->
<input type="checkbox" id="add-menu-item-modal" class="modal-toggle" />
<div class="modal modal-bottom sm:modal-middle">
  <div class="bg-white modal-box max-w-6xl p-8 rounded-lg shadow-xl border border-gray-100">
    <!-- ... (Same add menu item form content as before) ... -->
    <div class="flex justify-between items-center mb-6 pb-4 border-b border-gray-100">
      <h3 class="text-2xl font-bold text-gray-800 flex items-center gap-3">
        <i data-lucide="plus-circle" class="w-6 h-6 text-[#F7B32B]"></i>
        <span>Add Menu Item</span>
      </h3>
      <label for="add-menu-item-modal" class="btn btn-circle btn-ghost btn-sm hover:bg-gray-100 transition-colors">
        <i data-lucide="x" class="w-5 h-5 text-gray-500"></i>
      </label>
    </div>

    <!-- Add Menu Item Form -->
    <form id="add-menu-item-form" method="POST" enctype="multipart/form-data" class="space-y-6">
      <!-- ... (Same form fields as before) ... -->
      <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
        <!-- Item Name -->
        <div class="form-control">
          <label class="label mb-2"><span class="label-text font-medium text-gray-700">Item Name</span></label>
          <div class="relative">
            <i data-lucide="utensils" class="absolute left-3 top-1/2 transform -translate-y-1/2 w-4 h-4 text-gray-400"></i>
            <input type="text" name="name" required placeholder="e.g. Truffle Pasta"
              class="input input-bordered bg-white w-full pl-10 py-3 rounded-lg border-gray-300 focus:border-[#001f54] focus:ring-2 focus:ring-blue-100 transition-colors" />
          </div>
        </div>

        <!-- Category -->
        <div class="form-control">
          <label class="label mb-2"><span class="label-text font-medium text-gray-700">Category</span></label>
          <div class="relative">
            <i data-lucide="folder" class="absolute left-3 top-1/2 transform -translate-y-1/2 w-4 h-4 text-gray-400"></i>
            <select name="category" id="category" required class="select select-bordered bg-white w-full pl-10 py-3 rounded-lg border-gray-300 focus:border-[#001f54] focus:ring-2 focus:ring-blue-100 transition-colors appearance-none">
              <option value="" disabled selected>Select Category</option>
              <option value="appetizers">Appetizers</option>
              <option value="mains">Main Courses</option>
              <option value="desserts">Desserts</option>
              <option value="drinks">Drinks</option>
              <option value="specials">Specials</option>
              <option value="sides">Sides</option>
              <option value="bundle">Bundle</option>
            </select>
            <i data-lucide="chevron-down" class="absolute right-3 top-1/2 transform -translate-y-1/2 w-4 h-4 text-gray-400 pointer-events-none"></i>
          </div>
        </div>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
        <!-- Price -->
        <div class="form-control">
          <label class="label mb-2"><span class="label-text font-medium text-gray-700">Price</span></label>
          <div class="relative">
            <i data-lucide="dollar-sign" class="absolute left-3 top-1/2 transform -translate-y-1/2 w-4 h-4 text-gray-400"></i>
            <input type="number" name="price" min="0" step="0.01" required placeholder="0.00" 
              class="input input-bordered bg-white w-full pl-10 py-3 rounded-lg border-gray-300 focus:border-[#001f54] focus:ring-2 focus:ring-blue-100 transition-colors" />
          </div>
        </div>

        <!-- Preparation Time -->
        <div class="form-control">
          <label class="label mb-2"><span class="label-text font-medium text-gray-700">Prep Time</span></label>
          <div class="relative">
            <i data-lucide="clock" class="absolute left-3 top-1/2 transform -translate-y-1/2 w-4 h-4 text-gray-400"></i>
            <input type="number" name="prep_time" min="1" required placeholder="0" 
              class="input input-bordered bg-white w-full pl-10 py-3 rounded-lg border-gray-300 focus:border-[#001f54] focus:ring-2 focus:ring-blue-100 transition-colors" />
            <span class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-500 font-medium">min</span>
          </div>
        </div>

        <!-- Variant -->
        <div class="form-control">
          <label class="label mb-2"><span class="label-text font-medium text-gray-700">Variant</span></label>
          <div class="relative">
            <i data-lucide="sun" class="absolute left-3 top-1/2 transform -translate-y-1/2 w-4 h-4 text-gray-400"></i>
            <select name="variant" class="select select-bordered bg-white w-full pl-10 py-3 rounded-lg border-gray-300 focus:border-[#001f54] focus:ring-2 focus:ring-blue-100 transition-colors appearance-none" required>
              <option value="" disabled selected>Select Variant</option>
              <option value="Breakfast">Breakfast</option>
              <option value="Lunch">Lunch</option>
              <option value="Dinner">Dinner</option>
            </select>
            <i data-lucide="chevron-down" class="absolute right-3 top-1/2 transform -translate-y-1/2 w-4 h-4 text-gray-400 pointer-events-none"></i>
          </div>
        </div>
      </div>

      <!-- Description -->
      <div class="form-control">
        <label class="label mb-2"><span class="label-text font-medium text-gray-700">Description</span></label>
        <div class="relative">
          <i data-lucide="file-text" class="absolute left-3 top-3 w-4 h-4 text-gray-400"></i>
          <textarea name="description" rows="3" required placeholder="Describe the menu item..."
            class="textarea textarea-bordered bg-white w-full pl-10 py-3 rounded-lg border-gray-300 focus:border-[#001f54] focus:ring-2 focus:ring-blue-100 transition-colors"></textarea>
        </div>
      </div>

      <!-- Ingredients Section -->
      <div class="form-control">
        <label class="label mb-2"><span class="label-text font-medium text-gray-700">Ingredients</span></label>
        <div id="ingredients-container" class="space-y-3">
          <div class="ingredient-row flex flex-wrap gap-3 p-4 bg-gray-50 rounded-lg">
            <!-- Ingredient Dropdown -->
            <div class="relative flex-grow">
              <i data-lucide="list" class="absolute left-3 top-1/2 transform -translate-y-1/2 w-4 h-4 text-gray-400"></i>
              <select name="ingredients[]" class="ingredient-select select select-bordered bg-white w-full pl-10 py-3 rounded-lg border-gray-300 focus:border-[#001f54] focus:ring-2 focus:ring-blue-100 transition-colors appearance-none" required>
                <option value="" disabled selected>Select Ingredient</option>
                <?php foreach ($inventory_items as $inv): ?>
                  <option value="<?= $inv['item_id'] ?>">
                    <?= htmlspecialchars($inv['item_name']) ?>
                  </option>
                <?php endforeach; ?>
              </select>
              <i data-lucide="chevron-down" class="absolute right-3 top-1/2 transform -translate-y-1/2 w-4 h-4 text-gray-400 pointer-events-none"></i>
            </div>

            <!-- Quantity Input -->
            <div class="relative">
              <i data-lucide="hash" class="absolute left-3 top-1/2 transform -translate-y-1/2 w-4 h-4 text-gray-400"></i>
              <input type="number" name="ingredient_qty[]" placeholder="Qty" min="0.01" step="0.01"
                class="ingredient-qty input input-bordered bg-white w-28 pl-10 py-3 rounded-lg border-gray-300 focus:border-[#001f54] focus:ring-2 focus:ring-blue-100 transition-colors" required />
            </div>

            <!-- Measurement Dropdown -->
            <div class="relative">
              <i data-lucide="ruler" class="absolute left-3 top-1/2 transform -translate-y-1/2 w-4 h-4 text-gray-400"></i>
              <select name="ingredient_unit[]" class="unit-select select select-bordered bg-white w-36 pl-10 py-3 rounded-lg border-gray-300 focus:border-[#001f54] focus:ring-2 focus:ring-blue-100 transition-colors appearance-none" required>
                <option value="" disabled selected>Unit</option>
                <option value="kg">Kilogram (kg)</option>
                <option value="g">Gram (g)</option>
                <option value="L">Liter (L)</option>
                <option value="mL">Milliliter (mL)</option>
                <option value="pcs">Pieces (pcs)</option>
              </select>
              <i data-lucide="chevron-down" class="absolute right-3 top-1/2 transform -translate-y-1/2 w-4 h-4 text-gray-400 pointer-events-none"></i>
            </div>
          </div>
        </div>
        <button type="button" onclick="addIngredientRow()"
          class="btn btn-sm mt-3 bg-[#001f54] text-white hover:bg-[#001a44] border-none flex items-center gap-2 transition-colors">
          <i data-lucide="plus" class="w-4 h-4"></i>
          Add Ingredient
        </button>
      </div>

      <!-- Image Upload - Enhanced -->
      <div class="form-control">
        <label class="label mb-2">
          <span class="label-text font-medium text-gray-700">Upload HD Image</span>
          <span class="label-text-alt text-gray-500">Max 10MB • JPEG, PNG, WebP</span>
        </label>
        
        <!-- Image Preview -->
        <div id="image-preview" class="mb-3 hidden">
          <div class="relative inline-block">
            <img id="preview-image" class="w-32 h-32 object-cover rounded-lg border border-gray-300" />
            <button type="button" onclick="removeImagePreview()" class="absolute -top-2 -right-2 bg-red-500 text-white rounded-full w-6 h-6 flex items-center justify-center text-xs hover:bg-red-600">
              <i data-lucide="x" class="w-3 h-3"></i>
            </button>
          </div>
        </div>
        
        <!-- Upload Area -->
        <div class="relative border-2 border-dashed border-gray-300 rounded-lg p-6 text-center hover:border-blue-400 transition-colors bg-gray-50">
          <i data-lucide="upload" class="w-8 h-8 text-gray-400 mx-auto mb-2"></i>
          <p class="text-sm text-gray-600 mb-1">Click to upload or drag and drop</p>
          <p class="text-xs text-gray-500">HD Images up to 10MB</p>
          <input type="file" 
                 name="image_url" 
                 id="image-upload"
                 accept="image/jpeg, image/png, image/webp, image/jpg"
                 class="absolute inset-0 w-full h-full opacity-0 cursor-pointer" 
                 onchange="previewImage(this)" />
        </div>
        
        <!-- Upload Progress -->
        <div id="upload-progress" class="hidden mt-2">
          <div class="flex items-center gap-2 text-sm text-gray-600">
            <i data-lucide="loader" class="w-4 h-4 animate-spin"></i>
            <span>Uploading...</span>
            <div class="flex-1 bg-gray-200 rounded-full h-2">
              <div id="progress-bar" class="bg-blue-500 h-2 rounded-full transition-all duration-300" style="width: 0%"></div>
            </div>
          </div>
        </div>
      </div>

      <!-- Hidden Status Field -->
      <input type="hidden" name="status" value="Under review" />

      <!-- Action Buttons -->
      <div class="modal-action mt-8 flex flex-col-reverse sm:flex-row justify-end gap-3 pt-5 border-t border-gray-100">
        <label for="add-menu-item-modal" class="btn btn-outline border-gray-300 text-gray-700 hover:bg-gray-50 text-center sm:text-left transition-colors">
          Cancel
        </label>
        <button type="submit" class="btn bg-[#001f54] hover:bg-[#001a44] text-white border-none flex items-center gap-2 transition-colors">
          <i data-lucide="check" class="w-4 h-4"></i>
          Add Item
        </button>
      </div>
    </form>
  </div>
</div>

<script>
  // Initialize Lucide icons
  lucide.createIcons();

  // Function to add ingredient row
  function addIngredientRow() {
    const container = document.getElementById('ingredients-container');
    const newRow = document.createElement('div');
    newRow.className = 'ingredient-row flex flex-wrap gap-3 p-4 bg-gray-50 rounded-lg';
    newRow.innerHTML = `
      <div class="relative flex-grow">
        <i data-lucide="list" class="absolute left-3 top-1/2 transform -translate-y-1/2 w-4 h-4 text-gray-400"></i>
        <select name="ingredients[]" class="ingredient-select select select-bordered bg-white w-full pl-10 py-3 rounded-lg border-gray-300 focus:border-[#001f54] focus:ring-2 focus:ring-blue-100 transition-colors appearance-none" required>
          <option value="" disabled selected>Select Ingredient</option>
          <?php foreach ($inventory_items as $inv): ?>
            <option value="<?= $inv['item_id'] ?>">
              <?= htmlspecialchars($inv['item_name']) ?>
            </option>
          <?php endforeach; ?>
        </select>
        <i data-lucide="chevron-down" class="absolute right-3 top-1/2 transform -translate-y-1/2 w-4 h-4 text-gray-400 pointer-events-none"></i>
      </div>
      <div class="relative">
        <i data-lucide="hash" class="absolute left-3 top-1/2 transform -translate-y-1/2 w-4 h-4 text-gray-400"></i>
        <input type="number" name="ingredient_qty[]" placeholder="Qty" min="0.01" step="0.01"
          class="ingredient-qty input input-bordered bg-white w-28 pl-10 py-3 rounded-lg border-gray-300 focus:border-[#001f54] focus:ring-2 focus:ring-blue-100 transition-colors" required />
      </div>
      <div class="relative">
        <i data-lucide="ruler" class="absolute left-3 top-1/2 transform -translate-y-1/2 w-4 h-4 text-gray-400"></i>
        <select name="ingredient_unit[]" class="unit-select select select-bordered bg-white w-36 pl-10 py-3 rounded-lg border-gray-300 focus:border-[#001f54] focus:ring-2 focus:ring-blue-100 transition-colors appearance-none" required>
          <option value="" disabled selected>Unit</option>
          <option value="kg">Kilogram (kg)</option>
          <option value="g">Gram (g)</option>
          <option value="L">Liter (L)</option>
          <option value="mL">Milliliter (mL)</option>
          <option value="pcs">Pieces (pcs)</option>
        </select>
        <i data-lucide="chevron-down" class="absolute right-3 top-1/2 transform -translate-y-1/2 w-4 h-4 text-gray-400 pointer-events-none"></i>
      </div>
      <button type="button" onclick="this.parentElement.remove()" class="btn btn-sm btn-ghost text-gray-500 hover:text-red-500 hover:bg-red-50 transition-colors">
        <i data-lucide="trash-2" class="w-4 h-4"></i>
      </button>
    `;
    container.appendChild(newRow);
    
    // Initialize Lucide icons for the new row
    lucide.createIcons();
  }

  // Image preview functionality
  function previewImage(input) {
    const preview = document.getElementById('image-preview');
    const previewImg = document.getElementById('preview-image');
    const progress = document.getElementById('upload-progress');
    
    if (input.files && input.files[0]) {
      const file = input.files[0];
      
      // Validate file size (10MB max)
      if (file.size > 10 * 1024 * 1024) {
        Swal.fire({
          title: 'File Too Large',
          text: 'Please select an image smaller than 10MB',
          icon: 'error',
          confirmButtonColor: '#F7B32B',
          confirmButtonText: 'OK',
          buttonsStyling: true,
          customClass: {
            confirmButton: 'swal-confirm-button'
          }
        });
        input.value = '';
        return;
      }
      
      // Show upload progress
      progress.classList.remove('hidden');
      simulateUploadProgress();
      
      const reader = new FileReader();
      
      reader.onload = function(e) {
        previewImg.src = e.target.result;
        preview.classList.remove('hidden');
        progress.classList.add('hidden');
      }
      
      reader.readAsDataURL(file);
    }
  }

  function removeImagePreview() {
    const input = document.getElementById('image-upload');
    const preview = document.getElementById('image-preview');
    const progress = document.getElementById('upload-progress');
    
    input.value = '';
    preview.classList.add('hidden');
    progress.classList.add('hidden');
  }

  function simulateUploadProgress() {
    const progressBar = document.getElementById('progress-bar');
    let width = 0;
    
    const interval = setInterval(() => {
      if (width >= 90) {
        clearInterval(interval);
      } else {
        width += 10;
        progressBar.style.width = width + '%';
      }
    }, 100);
  }

  // Form submission
  document.getElementById("add-menu-item-form").addEventListener("submit", async (e) => {
    e.preventDefault();
    console.log("Form submission started");

    const form = e.target;
    const formData = new FormData(form);
    
    // Show loading state
    const submitBtn = form.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;
    submitBtn.innerHTML = '<i data-lucide="loader-2" class="w-4 h-4 animate-spin"></i> Adding...';
    submitBtn.disabled = true;

    try {
      console.log("Sending request to add_item.php");
      const response = await fetch("sub-modules/add_item.php", {
        method: "POST",
        body: formData,
      });
      
      console.log("Response status:", response.status);
      
      const responseText = await response.text();
      console.log("Response text:", responseText);
      
      let result;
      try {
        result = JSON.parse(responseText);
        console.log("Parsed result:", result);
      } catch (parseError) {
        console.error("Failed to parse JSON:", parseError);
        console.error("Response was:", responseText);
        throw new Error("Server returned invalid JSON. Check PHP syntax errors.");
      }

      if (result.status === "success") {
        Swal.fire({
          title: "Added!",
          text: result.message,
          icon: "success",
          confirmButtonColor: "#001f54",
          confirmButtonText: "OK",
        }).then(() => {
          form.reset();
          removeImagePreview();
          document.getElementById("add-menu-item-modal").checked = false;
          location.reload();
        });
      } else {
        Swal.fire({
          title: "Error!",
          text: result.message || "An error occurred while adding the menu item.",
          icon: "error",
          confirmButtonColor: "#ef4444",
          confirmButtonText: "OK",
        });
      }
    } catch (error) {
      console.error('Fetch error:', error);
      Swal.fire({
        title: "Network Error!",
        text: error.message || "Unable to connect to server. Please check your connection.",
        icon: "error",
        confirmButtonColor: "#ef4444",
        confirmButtonText: "OK",
      });
    } finally {
      // Restore button state
      submitBtn.innerHTML = originalText;
      submitBtn.disabled = false;
      lucide.createIcons();
    }
  });

  function showMenuItemDetails(menuId) {
    fetch('sub-modules/get_menu_item.php?menu_id=' + menuId)
      .then(res => res.text())
      .then(html => {
        document.getElementById('menu-item-details-content').innerHTML = html;
        document.getElementById('menu-item-details-modal').checked = true;
        lucide.createIcons();
      })
      .catch(err => console.error(err));
  }
  
  // Auto-submit filter form when dropdowns change
  document.querySelectorAll('#filter-form select').forEach(select => {
    select.addEventListener('change', function() {
      document.getElementById('filter-form').submit();
    });
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

<script src="../JavaScript/sidebar.js"></script>
<script src="../JavaScript/soliera.js"></script>

</body>
</html>