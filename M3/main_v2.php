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

// Fetch menu items with image
$sql = "SELECT m.*, mi.image_data, mi.mime_type 
        FROM menu m 
        LEFT JOIN menu_images mi ON m.menu_id = mi.menu_image_id
        ORDER BY m.created_at DESC";
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

// Handle search and filter
$search = $_GET['search'] ?? '';
$category = $_GET['category'] ?? '';
$status = $_GET['status'] ?? '';

// Build filtered query
$filter_conditions = [];
$params = [];
$types = '';

if (!empty($search)) {
    $filter_conditions[] = "(m.name LIKE ? OR m.description LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $types .= 'ss';
}

if (!empty($category) && $category !== 'all') {
    $filter_conditions[] = "m.category = ?";
    $params[] = $category;
    $types .= 's';
}

if (!empty($status) && $status !== 'all') {
    $filter_conditions[] = "m.status = ?";
    $params[] = $status;
    $types .= 's';
}

// Get distinct categories for filter dropdown
$category_query = "SELECT DISTINCT category FROM menu WHERE category IS NOT NULL AND category != ''";
$category_result = $conn->query($category_query);
$categories = [];
while ($cat = $category_result->fetch_assoc()) {
    $categories[] = $cat['category'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Menu Management Dashboard</title>
        <?php include '../header.php'; ?>

    <style>
        .gradient-bg {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .card-hover:hover {
            transform: translateY(-5px);
            transition: transform 0.3s ease;
        }
        .image-preview {
            max-width: 200px;
            max-height: 200px;
            object-fit: cover;
            border-radius: 8px;
        }
        .menu-card {
            transition: all 0.3s ease;
            border-left: 4px solid transparent;
        }
        .menu-card:hover {
            border-left-color: #667eea;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }
        .status-active {
            background-color: #10b981;
            color: white;
        }
        .status-inactive {
            background-color: #ef4444;
            color: white;
        }
        .filter-section {
            backdrop-filter: blur(10px);
            background: rgba(255, 255, 255, 0.9);
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

            <!-- Stats Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                <!-- Total Menu Items Card -->
                <div class="p-5 rounded-xl shadow-lg border border-gray-100 bg-white hover:shadow-xl transition-all duration-300 card-hover">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium" style="color:#001f54;">Total Items</p>
                            <h3 class="text-3xl font-bold mt-1 text-gray-800">
                                <?php echo $total_menu_count; ?>
                            </h3>
                        </div>
                        <div class="p-3 rounded-full" style="background:#F7B32B1A; color:#F7B32B;">
                            <i data-lucide="list" class="w-6 h-6"></i>
                        </div>
                    </div>
                    <div class="mt-4">
                        <div class="h-2 w-full bg-gray-100 rounded-full overflow-hidden">
                            <div class="h-full rounded-full" style="background:#F7B32B; width: 100%"></div>
                        </div>
                        <div class="flex justify-between text-xs text-gray-500 mt-1">
                            <span>Active items</span>
                            <span class="font-medium"><?php echo $total_menu_count; ?> items</span>
                        </div>
                    </div>
                </div>

                <!-- Popular Items Card -->
                <div class="p-5 rounded-xl shadow-lg border border-gray-100 bg-white hover:shadow-xl transition-all duration-300 card-hover">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium" style="color:#001f54;">Popular Items</p>
                            <h3 class="text-3xl font-bold mt-1 text-gray-800">
                                <?php echo $popular_count; ?>
                            </h3>
                        </div>
                        <div class="p-3 rounded-full bg-green-100 text-green-600">
                            <i data-lucide="star" class="w-6 h-6"></i>
                        </div>
                    </div>
                    <div class="mt-4">
                        <div class="h-2 w-full bg-gray-100 rounded-full overflow-hidden">
                            <div class="h-full bg-green-500 rounded-full" style="width: <?php echo $total_menu_count > 0 ? round(($popular_count/$total_menu_count)*100) : 0; ?>%"></div>
                        </div>
                        <div class="flex justify-between text-xs text-gray-500 mt-1">
                            <span>Top sellers</span>
                            <span class="font-medium"><?php echo $total_menu_count > 0 ? round(($popular_count/$total_menu_count)*100) : 0; ?>% of menu</span>
                        </div>
                    </div>
                </div>

                <!-- Categories Card -->
                <div class="p-5 rounded-xl shadow-lg border border-gray-100 bg-white hover:shadow-xl transition-all duration-300 card-hover">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium" style="color:#001f54;">Categories</p>
                            <h3 class="text-3xl font-bold mt-1 text-gray-800">
                                <?php echo $total_categories_count; ?>
                            </h3>
                        </div>
                        <div class="p-3 rounded-full bg-purple-100 text-purple-600">
                            <i data-lucide="tags" class="w-6 h-6"></i>
                        </div>
                    </div>
                    <div class="mt-4">
                        <div class="h-2 w-full bg-gray-100 rounded-full overflow-hidden">
                            <div class="h-full bg-purple-500 rounded-full" style="width: <?php echo $total_categories_count > 0 ? round(($total_categories_count/10)*100) : 0; ?>%"></div>
                        </div>
                        <div class="flex justify-between text-xs text-gray-500 mt-1">
                            <span>Menu sections</span>
                            <span class="font-medium"><?php echo $total_categories_count; ?> categories</span>
                        </div>
                    </div>
                </div>

                <!-- Seasonal Items Card -->
                <div class="p-5 rounded-xl shadow-lg border border-gray-100 bg-white hover:shadow-xl transition-all duration-300 card-hover">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium" style="color:#001f54;">Seasonal Items</p>
                            <h3 class="text-3xl font-bold mt-1 text-gray-800">
                                <?php echo $seasonal_count; ?>
                            </h3>
                        </div>
                        <div class="p-3 rounded-full bg-amber-100 text-amber-600">
                            <i data-lucide="sun" class="w-6 h-6"></i>
                        </div>
                    </div>
                    <div class="mt-4">
                        <div class="h-2 w-full bg-gray-100 rounded-full overflow-hidden">
                            <div class="h-full bg-amber-500 rounded-full" style="width: <?php echo $total_menu_count > 0 ? round(($seasonal_count/$total_menu_count)*100) : 0; ?>%"></div>
                        </div>
                        <div class="flex justify-between text-xs text-gray-500 mt-1">
                            <span>Limited time</span>
                            <span class="font-medium"><?php echo $total_menu_count > 0 ? round(($seasonal_count/$total_menu_count)*100) : 0; ?>% of menu</span>
                        </div>
                    </div>
                </div>
            </div>

            

            <!-- Search and Filter Section -->
            <div class="filter-section rounded-xl shadow-lg border border-gray-200 p-6 mb-8">
                <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Search</label>
                        <div class="relative">
                            <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" 
                                   placeholder="Search menu items..." 
                                   class="w-full px-4 py-2 pl-10 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                            <i data-lucide="search" class="absolute left-3 top-2.5 w-5 h-5 text-gray-400"></i>
                        </div>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Category</label>
                        <select name="category" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                            <option value="all">All Categories</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo htmlspecialchars($cat); ?>" 
                                        <?php echo $category == $cat ? 'selected' : ''; ?>>
                                    <?php echo ucfirst(htmlspecialchars($cat)); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                        <select name="status" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                            <option value="all">All Status</option>
                            <option value="active" <?php echo $status == 'active' ? 'selected' : ''; ?>>Active</option>
                            <option value="inactive" <?php echo $status == 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                        </select>
                    </div>
                    
                    <div class="flex items-end">
                        <button type="submit" class="w-full bg-purple-600 text-white px-6 py-2 rounded-lg hover:bg-purple-700 transition duration-300 flex items-center justify-center gap-2">
                            <i data-lucide="filter"></i> Apply Filters
                        </button>
                        <?php if ($search || $category !== 'all' || $status !== 'all'): ?>
                            <a href="?" class="ml-2 bg-gray-200 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-300 transition duration-300">
                                Clear
                            </a>
                        <?php endif; ?>
                    </div>

                     <button onclick="openUploadModal()" class="bg-white text-purple-700 px-6 py-3 rounded-lg font-semibold hover:bg-gray-100 transition duration-300 shadow-lg flex items-center gap-2">
                        <i data-lucide="plus"></i> Add New Menu Item
                    </button>
                </form>

                
            </div>

            <!-- Menu Items Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                <?php while ($item = $result_sql->fetch_assoc()): ?>
                    <div class="menu-card bg-white rounded-xl shadow-lg border border-gray-200 overflow-hidden">
                        <!-- Image Section -->
                        <div class="relative h-48 overflow-hidden bg-gray-100">
                            <?php if (!empty($item['image_data'])): ?>
                                <img src="data:<?php echo $item['mime_type']; ?>;base64,<?php echo base64_encode($item['image_data']); ?>" 
                                     alt="<?php echo htmlspecialchars($item['name']); ?>" 
                                     class="w-full h-full object-cover hover:scale-110 transition-transform duration-500">
                            <?php else: ?>
                                <div class="w-full h-full flex items-center justify-center bg-gradient-to-br from-gray-200 to-gray-300">
                                    <i data-lucide="image" class="w-16 h-16 text-gray-400"></i>
                                </div>
                            <?php endif; ?>
                            <div class="absolute top-3 right-3">
                                <span class="px-3 py-1 rounded-full text-xs font-semibold 
                                    <?php echo $item['status'] == 'active' ? 'bg-green-500 text-white' : 'bg-red-500 text-white'; ?>">
                                    <?php echo ucfirst($item['status']); ?>
                                </span>
                            </div>
                        </div>
                        
                        <!-- Content Section -->
                        <div class="p-5">
                            <div class="flex justify-between items-start mb-2">
                                <h3 class="text-lg font-bold text-gray-800 truncate"><?php echo htmlspecialchars($item['name']); ?></h3>
                                <span class="text-xl font-bold text-purple-600">₱<?php echo number_format($item['price'], 2); ?></span>
                            </div>
                            
                            <div class="flex items-center gap-2 mb-3">
                                <span class="px-2 py-1 bg-gray-100 text-gray-700 text-xs rounded-full">
                                    <?php echo htmlspecialchars($item['category']); ?>
                                </span>
                                <?php if ($item['variant']): ?>
                                    <span class="px-2 py-1 bg-blue-100 text-blue-700 text-xs rounded-full">
                                        <?php echo htmlspecialchars($item['variant']); ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                            
                            <p class="text-gray-600 text-sm mb-4 line-clamp-2">
                                <?php echo htmlspecialchars($item['description']); ?>
                            </p>
                            
                            <div class="grid grid-cols-2 gap-3 text-sm text-gray-500 mb-4">
                                <div class="flex items-center gap-2">
                                    <i data-lucide="clock" class="w-4 h-4"></i>
                                    <span><?php echo $item['prep_time'] ?? 0; ?> mins</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <i data-lucide="calendar" class="w-4 h-4"></i>
                                    <span><?php echo date('M d, Y', strtotime($item['created_at'])); ?></span>
                                </div>
                            </div>
                            
                            <div class="flex gap-2">
                                <button onclick="viewItemDetails(<?php echo $item['menu_id']; ?>)" 
                                        class="flex-1 bg-purple-600 text-white px-4 py-2 rounded-lg hover:bg-purple-700 transition duration-300 flex items-center justify-center gap-2">
                                    <i data-lucide="eye" class="w-4 h-4"></i> View
                                </button>
                                <button onclick="editMenuItem(<?php echo $item['menu_id']; ?>)" 
                                        class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 transition duration-300">
                                    <i data-lucide="edit" class="w-4 h-4"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
                
                <?php if ($result_sql->num_rows === 0): ?>
                    <div class="col-span-full text-center py-12">
                        <i data-lucide="inbox" class="w-16 h-16 text-gray-400 mx-auto mb-4"></i>
                        <h3 class="text-xl font-semibold text-gray-600 mb-2">No menu items found</h3>
                        <p class="text-gray-500 mb-6">Try adjusting your search or add a new menu item</p>
                        <button onclick="openUploadModal()" class="bg-purple-600 text-white px-6 py-3 rounded-lg hover:bg-purple-700 transition duration-300">
                            <i data-lucide="plus"></i> Add First Item
                        </button>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <!-- Upload Modal -->
    <div id="uploadModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50 p-4">
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-2xl max-h-[90vh] overflow-y-auto">
            <div class="sticky top-0 bg-white border-b px-6 py-4 flex justify-between items-center">
                <h2 class="text-2xl font-bold text-gray-800">Add New Menu Item</h2>
                <button onclick="closeUploadModal()" class="text-gray-500 hover:text-gray-700">
                    <i data-lucide="x" class="w-6 h-6"></i>
                </button>
            </div>
            
            <form id="uploadForm" action="upload_menu_api.php" method="POST" enctype="multipart/form-data" class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Left Column -->
                    <div class="space-y-4">
                        <!-- Image Upload -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Menu Image</label>
                            <div class="border-2 border-dashed border-gray-300 rounded-lg p-4 text-center hover:border-purple-500 transition duration-300">
                                <input type="file" id="menuImage" name="menu_image" accept="image/*" 
                                       class="hidden" onchange="previewImage(event)">
                                <div id="imagePreview" class="mb-4 hidden">
                                    <img id="preview" class="image-preview mx-auto">
                                </div>
                                <div id="uploadPlaceholder" class="py-8">
                                    <i data-lucide="upload" class="w-12 h-12 text-gray-400 mx-auto mb-4"></i>
                                    <p class="text-gray-600 mb-2">Click to upload or drag and drop</p>
                                    <p class="text-sm text-gray-500">PNG, JPG, GIF up to 5MB</p>
                                </div>
                                <button type="button" onclick="document.getElementById('menuImage').click()" 
                                        class="mt-4 bg-purple-100 text-purple-700 px-4 py-2 rounded-lg hover:bg-purple-200 transition duration-300">
                                    Choose Image
                                </button>
                            </div>
                        </div>
                        
                        <!-- Basic Information -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Item Name *</label>
                            <input type="text" name="name" required 
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Description</label>
                            <textarea name="description" rows="3" 
                                      class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"></textarea>
                        </div>
                    </div>
                    
                    <!-- Right Column -->
                    <div class="space-y-4">
                        <!-- Category and Variant -->
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Category *</label>
                                <select name="category" required 
                                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                                    <option value="">Select Category</option>
                                    <option value="appetizer">Appetizer</option>
                                    <option value="main">Main Course</option>
                                    <option value="dessert">Dessert</option>
                                    <option value="beverage">Beverage</option>
                                    <option value="popular">Popular</option>
                                    <option value="seasonal">Seasonal</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Variant</label>
                                <input type="text" name="variant" 
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                            </div>
                        </div>
                        
                        <!-- Price and Prep Time -->
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Price *</label>
                                <input type="number" name="price" step="0.01" required 
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Prep Time (mins)</label>
                                <input type="number" name="prep_time" 
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                            </div>
                        </div>
                        
                        <!-- Ingredients -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Primary Ingredient</label>
                            <select name="ingredient1_id" 
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                                <option value="">Select Ingredient</option>
                                <?php foreach ($inventory_items as $inv): ?>
                                    <option value="<?php echo $inv['item_id']; ?>">
                                        <?php echo htmlspecialchars($inv['item_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Ingredient Quantity</label>
                            <input type="text" name="ingredient1_qty" 
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                                   placeholder="e.g., 200g, 1 cup">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Ingredient Names</label>
                            <input type="text" name="ingredient1_names" 
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                                   placeholder="e.g., Flour, Sugar, Eggs">
                        </div>
                        
                        <!-- Status and Availability -->
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                                <select name="status" 
                                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                                    <option value="active">Active</option>
                                    <option value="inactive">Inactive</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Availability</label>
                                <select name="availability" 
                                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                                    <option value="available">Available</option>
                                    <option value="out_of_stock">Out of Stock</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Submit Button -->
                <div class="mt-8 pt-6 border-t">
                    <button type="submit" 
                            class="w-full bg-gradient-to-r from-purple-600 to-blue-600 text-white px-6 py-3 rounded-lg hover:opacity-90 transition duration-300 font-semibold flex items-center justify-center gap-2">
                        <i data-lucide="upload" class="w-5 h-5"></i> Upload Menu Item
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- View Details Modal -->
    <div id="viewModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50 p-4">
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-2xl max-h-[90vh] overflow-y-auto">
            <div class="sticky top-0 bg-white border-b px-6 py-4 flex justify-between items-center">
                <h2 id="viewTitle" class="text-2xl font-bold text-gray-800"></h2>
                <button onclick="closeViewModal()" class="text-gray-500 hover:text-gray-700">
                    <i data-lucide="x" class="w-6 h-6"></i>
                </button>
            </div>
            <div id="viewContent" class="p-6">
                <!-- Content loaded via AJAX -->
            </div>
        </div>
    </div>

    <script>
        // Initialize Lucide icons
        lucide.createIcons();
        
        // Modal functions
        function openUploadModal() {
            document.getElementById('uploadModal').classList.remove('hidden');
            document.getElementById('uploadModal').classList.add('flex');
            document.body.style.overflow = 'hidden';
        }
        
        function closeUploadModal() {
            document.getElementById('uploadModal').classList.add('hidden');
            document.getElementById('uploadModal').classList.remove('flex');
            document.body.style.overflow = 'auto';
        }
        
        function previewImage(event) {
            const input = event.target;
            const preview = document.getElementById('preview');
            const imagePreview = document.getElementById('imagePreview');
            const uploadPlaceholder = document.getElementById('uploadPlaceholder');
            
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                
                reader.onload = function(e) {
                    preview.src = e.target.result;
                    imagePreview.classList.remove('hidden');
                    uploadPlaceholder.classList.add('hidden');
                }
                
                reader.readAsDataURL(input.files[0]);
            }
        }
        
        // View item details
        function viewItemDetails(menuId) {
            fetch(`get_menu_details.php?id=${menuId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        document.getElementById('viewTitle').textContent = data.item.name;
                        
                        const content = `
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    ${data.item.image_data ? 
                                        `<img src="data:${data.item.mime_type};base64,${data.item.image_data}" 
                                              alt="${data.item.name}" 
                                              class="w-full h-64 object-cover rounded-lg mb-4">` :
                                        `<div class="w-full h-64 bg-gray-100 rounded-lg flex items-center justify-center mb-4">
                                            <i data-lucide="image" class="w-16 h-16 text-gray-400"></i>
                                        </div>`
                                    }
                                    
                                    <div class="space-y-4">
                                        <div>
                                            <h3 class="text-sm font-medium text-gray-500">Description</h3>
                                            <p class="text-gray-800">${data.item.description || 'No description'}</p>
                                        </div>
                                        
                                        <div>
                                            <h3 class="text-sm font-medium text-gray-500">Ingredients</h3>
                                            <p class="text-gray-800">${data.item.ingredient1_names || 'No ingredients listed'}</p>
                                            ${data.item.ingredient1_qty ? 
                                                `<p class="text-sm text-gray-600 mt-1">Quantity: ${data.item.ingredient1_qty}</p>` : ''}
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="space-y-4">
                                    <div class="grid grid-cols-2 gap-4">
                                        <div class="bg-gray-50 p-4 rounded-lg">
                                            <h3 class="text-sm font-medium text-gray-500">Price</h3>
                                            <p class="text-2xl font-bold text-purple-600">₱${parseFloat(data.item.price).toFixed(2)}</p>
                                        </div>
                                        <div class="bg-gray-50 p-4 rounded-lg">
                                            <h3 class="text-sm font-medium text-gray-500">Prep Time</h3>
                                            <p class="text-xl font-semibold text-gray-800">${data.item.prep_time || 0} mins</p>
                                        </div>
                                    </div>
                                    
                                    <div class="bg-gray-50 p-4 rounded-lg">
                                        <h3 class="text-sm font-medium text-gray-500">Category & Variant</h3>
                                        <div class="flex gap-2 mt-2">
                                            <span class="px-3 py-1 bg-blue-100 text-blue-700 text-sm rounded-full">
                                                ${data.item.category}
                                            </span>
                                            ${data.item.variant ? 
                                                `<span class="px-3 py-1 bg-green-100 text-green-700 text-sm rounded-full">
                                                    ${data.item.variant}
                                                </span>` : ''}
                                        </div>
                                    </div>
                                    
                                    <div class="bg-gray-50 p-4 rounded-lg">
                                        <h3 class="text-sm font-medium text-gray-500">Status</h3>
                                        <div class="flex gap-2 mt-2">
                                            <span class="px-3 py-1 ${data.item.status === 'active' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'} text-sm rounded-full">
                                                ${data.item.status}
                                            </span>
                                            <span class="px-3 py-1 ${data.item.availability === 'available' ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700'} text-sm rounded-full">
                                                ${data.item.availability}
                                            </span>
                                        </div>
                                    </div>
                                    
                                    <div class="bg-gray-50 p-4 rounded-lg">
                                        <h3 class="text-sm font-medium text-gray-500">Dates</h3>
                                        <div class="grid grid-cols-2 gap-4 mt-2 text-sm">
                                            <div>
                                                <p class="text-gray-500">Created</p>
                                                <p class="font-medium">${new Date(data.item.created_at).toLocaleDateString()}</p>
                                            </div>
                                            <div>
                                                <p class="text-gray-500">Updated</p>
                                                <p class="font-medium">${new Date(data.item.updated_at).toLocaleDateString()}</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        `;
                        
                        document.getElementById('viewContent').innerHTML = content;
                        lucide.createIcons(); // Reinitialize icons
                        openViewModal();
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error loading item details');
                });
        }
        
        function openViewModal() {
            document.getElementById('viewModal').classList.remove('hidden');
            document.getElementById('viewModal').classList.add('flex');
            document.body.style.overflow = 'hidden';
        }
        
        function closeViewModal() {
            document.getElementById('viewModal').classList.add('hidden');
            document.getElementById('viewModal').classList.remove('flex');
            document.body.style.overflow = 'auto';
        }
        
        function editMenuItem(menuId) {
            // Implementation for edit functionality
            alert('Edit functionality for item ID: ' + menuId);
        }
        
        // Drag and drop functionality
        const dropArea = document.querySelector('.border-dashed');
        if (dropArea) {
            ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
                dropArea.addEventListener(eventName, preventDefaults, false);
            });
            
            function preventDefaults(e) {
                e.preventDefault();
                e.stopPropagation();
            }
            
            ['dragenter', 'dragover'].forEach(eventName => {
                dropArea.addEventListener(eventName, highlight, false);
            });
            
            ['dragleave', 'drop'].forEach(eventName => {
                dropArea.addEventListener(eventName, unhighlight, false);
            });
            
            function highlight() {
                dropArea.classList.add('border-purple-500', 'bg-purple-50');
            }
            
            function unhighlight() {
                dropArea.classList.remove('border-purple-500', 'bg-purple-50');
            }
            
            dropArea.addEventListener('drop', handleDrop, false);
            
            function handleDrop(e) {
                const dt = e.dataTransfer;
                const files = dt.files;
                document.getElementById('menuImage').files = files;
                previewImage({ target: document.getElementById('menuImage') });
            }
        }
        
        // Form submission with feedback
        document.getElementById('uploadForm').addEventListener('submit', function(e) {
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<i data-lucide="loader" class="w-5 h-5 animate-spin"></i> Uploading...';
            submitBtn.disabled = true;
            
            // Continue with form submission
        });
    </script>
</body>
</html>