<?php
session_start();
include("../main_connection.php");

// Database connection
$db_name = "rest_m3_menu";
if (!isset($connections[$db_name])) {
    die("❌ Connection not found for $db_name");
}
$conn = $connections[$db_name];

// Fetch only ACTIVE menus from database
$active_menus = [];
try {
    $sql = "SELECT * FROM menu WHERE status = 'active' ORDER BY category, name";
    $result = $conn->query($sql);
    
    if ($result) {
        $active_menus = $result->fetch_all(MYSQLI_ASSOC);
    } else {
        error_log("Database error: " . $conn->error);
        $active_menus = [];
    }
} catch (Exception $e) {
    error_log("Database error: " . $e->getMessage());
    $active_menus = [];
}

// Get unique categories for filter dropdown
$categories = [];
try {
    $category_sql = "SELECT DISTINCT category FROM menu WHERE status = 'active' ORDER BY category";
    $category_result = $conn->query($category_sql);
    if ($category_result) {
        while ($row = $category_result->fetch_assoc()) {
            $categories[] = $row['category'];
        }
    }
} catch (Exception $e) {
    error_log("Category fetch error: " . $e->getMessage());
}

// Convert PHP data to JSON for JavaScript
$active_menus_json = json_encode($active_menus);
$categories_json = json_encode($categories);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Luxury Grand Hotel - POS System</title>
    <?php include '../header.php'; ?>
    
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
    <style>
        .menu-card:hover {
            transform: translateY(-5px);
            transition: transform 0.3s ease;
        }
        .table-available {
            background: linear-gradient(135deg, #10B981 0%, #059669 100%);
            color: white;
        }
        .table-occupied {
            background: linear-gradient(135deg, #EF4444 0%, #DC2626 100%);
            color: white;
        }
        .table-maintenance {
            background: linear-gradient(135deg, #F59E0B 0%, #D97706 100%);
            color: white;
        }
        .table-reserved {
            background: linear-gradient(135deg, #6366F1 0%, #4F46E5 100%);
            color: white;
        }
        .table-hidden {
            background: linear-gradient(135deg, #6B7280 0%, #4B5563 100%);
            color: white;
            opacity: 0.7;
        }
        .glassy-card {
            background: rgba(255, 255, 255, 0.7);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.3);
        }
        .primary-button {
            background-color: #F7B32B;
            color: white;
        }
        .primary-button:hover {
            background-color: #e6a117;
        }
        /* SweetAlert white background */
        .swal2-popup {
            background: white !important;
            color: #333 !important;
        }
        /* Scrollbar styling */
        .overflow-y-auto::-webkit-scrollbar {
            width: 6px;
        }
        .overflow-y-auto::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 3px;
        }
        .overflow-y-auto::-webkit-scrollbar-thumb {
            background: #888;
            border-radius: 3px;
        }
        .overflow-y-auto::-webkit-scrollbar-thumb:hover {
            background: #555;
        }
        /* Full screen modal */
        .fullscreen-modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 9999;
            background: white;
            overflow-y: auto;
        }
        .table-grid {
            display: grid;
            gap: 1rem;
            padding: 1rem;
        }
        @media (min-width: 640px) {
            .table-grid { grid-template-columns: repeat(2, 1fr); }
        }
        @media (min-width: 768px) {
            .table-grid { grid-template-columns: repeat(3, 1fr); }
        }
        @media (min-width: 1024px) {
            .table-grid { grid-template-columns: repeat(4, 1fr); }
        }
        @media (min-width: 1280px) {
            .table-grid { grid-template-columns: repeat(5, 1fr); }
        }
        .table-card {
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            transition: all 0.3s ease;
            cursor: pointer;
        }
        .table-card:hover:not(.disabled) {
            transform: translateY(-4px);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        }
        .table-card.selected {
            box-shadow: 0 0 0 3px #F7B32B;
        }
        .table-card.disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }
        .table-image {
            width: 100%;
            height: 120px;
            object-fit: cover;
        }
        .no-image {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
        }
        /* Receipt modal */
        .receipt-modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 10000;
            background: white;
            overflow-y: auto;
        }
        .receipt-container {
            width: 100%;
            max-width: 400px;
            margin: 0 auto;
            padding: 20px;
        }
        /* Amount input styling */
        .amount-input {
            border: 2px solid #e5e7eb;
            transition: all 0.3s;
        }
        .amount-input:focus {
            border-color: #F7B32B;
            box-shadow: 0 0 0 3px rgba(247, 179, 43, 0.1);
        }
        .amount-input.valid {
            border-color: #10B981;
            background-color: #f0fdf4;
        }
        .amount-input.invalid {
            border-color: #EF4444;
            background-color: #fef2f2;
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
            <!-- Search and Filter Section -->
            <div class="mb-6">
                <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-4">
                    <!-- Search Bar -->
                    <div class="flex-1">
                        <div class="relative">
                            <i data-lucide="search" class="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 w-5 h-5"></i>
                            <input type="text" 
                                   id="searchInput" 
                                   placeholder="Search menu items..." 
                                   class="bg-white w-full pl-10 pr-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#F7B32B] focus:border-transparent">
                        </div>
                    </div>
                    
                    <!-- Filter Dropdowns -->
                    <div class="flex flex-col sm:flex-row gap-3">
                        <!-- Category Filter -->
                        <div class="relative">
                            <select id="categoryFilter" class="w-full sm:w-48 px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#F7B32B] appearance-none bg-white">
                                <option value="all">All Categories</option>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?php echo htmlspecialchars(strtolower($category)); ?>">
                                        <?php echo htmlspecialchars($category); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <i data-lucide="chevron-down" class="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-400 w-5 h-5 pointer-events-none"></i>
                        </div>
                        
                        <!-- Price Sort -->
                        <div class="relative">
                            <select id="priceSort" class="w-full sm:w-48 px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#F7B32B] appearance-none bg-white">
                                <option value="default">Sort by Price</option>
                                <option value="low-high">Price: Low to High</option>
                                <option value="high-low">Price: High to Low</option>
                            </select>
                            <i data-lucide="filter" class="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-400 w-5 h-5 pointer-events-none"></i>
                        </div>
                    </div>
                </div>
                
                <!-- Active Filters Display -->
                <div id="activeFilters" class="flex flex-wrap gap-2 hidden">
                    <!-- Active filters will appear here -->
                </div>
            </div>

            <!-- Customer Info & Table Selection -->
            <div class="bg-white rounded-lg shadow-md p-4 mb-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Customer Name</label>
                        <input type="text" id="customerName" class="bg-white w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-[#F7B32B]" placeholder="Enter customer name">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Table</label>
                        <button id="tableSelectBtn" class="w-full px-3 py-2 border border-gray-300 rounded-md text-left flex justify-between items-center focus:outline-none focus:ring-2 focus:ring-[#F7B32B] hover:bg-gray-50 transition duration-200">
                            <span id="selectedTableText">Select Table</span>
                            <div class="flex items-center gap-2">
                                <span id="tableStatusBadge" class="hidden text-xs font-medium px-2 py-1 rounded-full"></span>
                                <i data-lucide="chevron-down"></i>
                            </div>
                        </button>
                    </div>
                </div>
                <div class="mt-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Order Notes (Optional)</label>
                    <textarea id="orderNotes" class="bg-white w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-[#F7B32B]" placeholder="Special instructions or notes..." rows="2"></textarea>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Order Section -->
                <div class="lg:col-span-2">
                    <!-- Menu Items Grid -->
                    <div class="bg-white rounded-lg shadow-md p-4">
                        <div id="menuItems" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                            <!-- Menu items will be populated here -->
                        </div>
                        
                        <!-- Pagination -->
                        <div class="flex flex-col sm:flex-row items-center justify-between mt-6 pt-4 border-t border-gray-200">
                            <div class="text-sm text-gray-600 mb-2 sm:mb-0">
                                Showing <span id="showingStart">0</span>-<span id="showingEnd">0</span> of <span id="totalItems">0</span> items
                            </div>
                            <nav class="inline-flex rounded-md shadow">
                                <button id="prevPage" class="py-2 px-4 border border-gray-300 bg-white rounded-l-md text-sm font-medium text-gray-500 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed">
                                    <i data-lucide="chevron-left" class="w-4 h-4"></i>
                                </button>
                                <div id="pageNumbers" class="flex border-t border-b border-gray-300">
                                    <!-- Page numbers will be populated here -->
                                </div>
                                <button id="nextPage" class="py-2 px-4 border border-gray-300 bg-white rounded-r-md text-sm font-medium text-gray-500 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed">
                                    <i data-lucide="chevron-right" class="w-4 h-4"></i>
                                </button>
                            </nav>
                        </div>
                    </div>
                </div>

                <!-- Order Summary -->
                <div class="lg:col-span-1">
                    <div class="bg-white rounded-lg shadow-md p-4 sticky top-4">
                        <div class="flex items-center justify-between mb-4">
                            <h2 class="text-xl font-bold text-gray-800">Order Summary</h2>
                            <div class="flex items-center gap-2">
                                <span id="itemCount" class="bg-[#F7B32B] text-white text-xs font-bold px-2 py-1 rounded-full">0</span>
                                <i data-lucide="shopping-cart" class="w-5 h-5 text-gray-600"></i>
                            </div>
                        </div>
                        
                        <!-- Order Items -->
                        <div id="orderItems" class="mb-4 max-h-64 overflow-y-auto pr-2">
                            <!-- Order items will be populated here -->
                            <div class="text-center py-8">
                                <i data-lucide="shopping-bag" class="w-12 h-12 mx-auto text-gray-300 mb-3"></i>
                                <p class="text-gray-500">No items added yet</p>
                                <p class="text-gray-400 text-sm mt-1">Add items from the menu</p>
                            </div>
                        </div>
                        
                        <!-- Bill Calculation -->
                        <div class="border-t border-gray-200 pt-4 space-y-2">
                            <div class="flex justify-between">
                                <span class="text-gray-600">Subtotal:</span>
                                <span id="subtotal">₱0.00</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Service Charge (2%):</span>
                                <span id="serviceCharge">₱0.00</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">VAT (12%):</span>
                                <span id="vat">₱0.00</span>
                            </div>
                            <div class="flex justify-between font-bold text-lg pt-2 border-t border-gray-200">
                                <span>Total:</span>
                                <span id="totalBill">₱0.00</span>
                            </div>
                        </div>
                        
                        <!-- Customer Payment Amount -->
                        <div class="mt-6 border-t border-gray-200 pt-4">
                            <h3 class="text-lg font-medium text-gray-700 mb-2">Customer Payment</h3>
                            <div class="space-y-3">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Amount Received</label>
                                    <div class="relative">
                                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                            <span class="text-gray-500 sm:text-sm">₱</span>
                                        </div>
                                        <input type="number" 
                                               id="amountReceived" 
                                               min="0" 
                                               step="0.01"
                                               class="amount-input pl-10 bg-white w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-[#F7B32B]"
                                               placeholder="0.00">
                                    </div>
                                </div>
                                <div class="bg-gray-50 p-3 rounded-md">
                                    <div class="flex justify-between items-center">
                                        <span class="text-gray-700 font-medium">Change:</span>
                                        <span id="changeAmount" class="text-lg font-bold text-green-600">₱0.00</span>
                                    </div>
                                    <div id="changeStatus" class="text-xs text-gray-500 mt-1">
                                        Enter amount to calculate change
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Payment Method -->
                        <div class="mt-6">
                            <h3 class="text-lg font-medium text-gray-700 mb-2">Payment Method</h3>
                            <div class="grid grid-cols-2 gap-2">
                                <button 
                                    class="mop-btn py-2 px-3 border border-gray-300 rounded-md text-center hover:bg-gray-50"
                                    data-mop="cash"
                                    >
                                        <i data-lucide="banknote" class="w-8 h-8 mx-auto mb-1"></i>
                                        <span class="text-sm">Cash</span>
                                    </button>

                                <button class="mop-btn py-2 px-3 border border-gray-300 rounded-md text-center hover:bg-gray-50" data-mop="gcash">
                                    <img src="../images/Gcash.png" alt="GCash" class="w-8 h-8 mx-auto mb-1 object-contain">
                                    <span class="text-sm">GCash</span>
                                </button>
                                <button class="mop-btn py-2 px-3 border border-gray-300 rounded-md text-center hover:bg-gray-50" data-mop="maya">
                                    <img src="../images/Maya.png" alt="Maya" class="w-10 h-15 mx-auto mb-1 object-contain">
                                    <span class="text-sm">Maya</span>
                                </button>
                               <button 
                                        class="mop-btn py-2 px-3 border border-gray-300 rounded-md text-center hover:bg-gray-50"
                                        data-mop="card"
                                        >
                                            <i data-lucide="credit-card" class="w-8 h-8 mx-auto mb-1"></i>
                                            <span class="text-sm">Card</span>
                                        </button>

                            </div>
                        </div>
                        
                        <!-- Action Buttons -->
                        <div class="mt-6 space-y-3">
                            <button id="checkoutBtn" class="w-full primary-button py-3 rounded-md font-medium transition duration-200 flex items-center justify-center gap-2">
                                <i data-lucide="credit-card" class="w-5 h-5"></i>
                                Process Payment
                            </button>
                            <button id="clearOrderBtn" class="w-full bg-gray-200 text-gray-700 py-3 rounded-md font-medium hover:bg-gray-300 transition duration-200 flex items-center justify-center gap-2">
                                <i data-lucide="trash-2" class="w-5 h-5"></i>
                                Clear Order
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
  </div>

    <!-- Table Selection Modal (Full Screen) -->
<div id="tableModal" class="fullscreen-modal hidden">
    <div class="min-h-screen bg-gray-50">
        <!-- Modal Header -->
        <div class="sticky top-0 z-10 bg-white border-b border-gray-200 px-6 py-4 shadow-sm">
            <div class="flex justify-between items-center">
                <div>
                    <h2 class="text-2xl font-bold text-gray-800 flex items-center gap-3">
                        <i data-lucide="table" class="w-8 h-8 text-[#F7B32B]"></i>
                        Select Table
                    </h2>
                    <p class="text-gray-600 mt-1">Choose a table for your order. Available tables are highlighted.</p>
                </div>
                <button id="closeTableModal" class="p-2 rounded-lg hover:bg-gray-100 transition duration-200">
                    <i data-lucide="x" class="w-6 h-6 text-gray-600"></i>
                </button>
            </div>
            
            <!-- Search and Filter Controls -->
            <div class="mt-4 grid grid-cols-1 md:grid-cols-4 gap-4">
                <!-- Search Bar -->
                <div class="md:col-span-2">
                    <div class="relative">
                        <i data-lucide="search" class="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 w-5 h-5"></i>
                        <input type="text" 
                               id="tableSearchInput" 
                               placeholder="Search tables by name, capacity, or location..." 
                               class="w-full pl-10 pr-4 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#F7B32B] focus:border-transparent bg-white">
                    </div>
                </div>
                
                <!-- Status Filter -->
                <div class="relative">
                    <select id="tableStatusFilter" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#F7B32B] appearance-none bg-white">
                        <option value="all">All Status</option>
                        <option value="available">Available</option>
                        <option value="occupied">Occupied</option>
                        <option value="reserved">Reserved</option>
                        <option value="maintenance">Maintenance</option>
                        <option value="hidden">Hidden</option>
                    </select>
                    <i data-lucide="chevron-down" class="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-400 w-5 h-5 pointer-events-none"></i>
                </div>
                
                <!-- Category Filter -->
                <div class="relative">
                    <select id="tableCategoryFilter" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#F7B32B] appearance-none bg-white">
                        <option value="all">All Categories</option>
                        <option value="standard">Standard</option>
                        <option value="premium">Premium</option>
                        <option value="vip">VIP</option>
                        <option value="outdoor">Outdoor</option>
                        <option value="private">Private</option>
                    </select>
                    <i data-lucide="filter" class="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-400 w-5 h-5 pointer-events-none"></i>
                </div>
            </div>
            
            <!-- Active Filters -->
            <div id="tableActiveFilters" class="flex flex-wrap gap-2 mt-3 hidden">
                <!-- Active filters will appear here -->
            </div>
            
            <!-- Status Legend -->
            <div class="flex flex-wrap gap-3 mt-4">
                <div class="flex items-center gap-2">
                    <div class="w-3 h-3 rounded-full bg-green-500"></div>
                    <span class="text-sm text-gray-600">Available</span>
                </div>
                <div class="flex items-center gap-2">
                    <div class="w-3 h-3 rounded-full bg-blue-500"></div>
                    <span class="text-sm text-gray-600">Reserved</span>
                </div>
                <div class="flex items-center gap-2">
                    <div class="w-3 h-3 rounded-full bg-red-500"></div>
                    <span class="text-sm text-gray-600">Occupied</span>
                </div>
                <div class="flex items-center gap-2">
                    <div class="w-3 h-3 rounded-full bg-yellow-500"></div>
                    <span class="text-sm text-gray-600">Maintenance</span>
                </div>
                <div class="flex items-center gap-2">
                    <div class="w-3 h-3 rounded-full bg-gray-500"></div>
                    <span class="text-sm text-gray-600">Hidden</span>
                </div>
            </div>
        </div>

        <!-- Main Content Area -->
        <div class="p-6">
            <!-- Table Display Controls -->
            <div class="flex justify-between items-center mb-4">
                <div class="text-sm text-gray-600">
                    Showing <span id="tableShowingStart">0</span>-<span id="tableShowingEnd">0</span> of <span id="tableTotalItems">0</span> tables
                </div>
                <div class="flex items-center gap-4">
                    <!-- View Toggle -->
                    <div class="flex items-center gap-2 bg-gray-100 rounded-lg p-1">
                        <button id="tableViewToggle" class="p-2 rounded-lg bg-white text-gray-700 shadow-sm hover:bg-gray-50 transition duration-200">
                            <i data-lucide="grid" class="w-5 h-5"></i>
                        </button>
                        <button id="tableListViewBtn" class="p-2 rounded-lg text-gray-500 hover:bg-gray-50 hover:text-gray-700 transition duration-200">
                            <i data-lucide="list" class="w-5 h-5"></i>
                        </button>
                    </div>
                    
                    <!-- Sort Options -->
                    <div class="relative">
                        <select id="tableSort" class="pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#F7B32B] appearance-none bg-white text-sm">
                            <option value="name-asc">Sort by: Name (A-Z)</option>
                            <option value="name-desc">Sort by: Name (Z-A)</option>
                            <option value="capacity-asc">Sort by: Capacity (Low-High)</option>
                            <option value="capacity-desc">Sort by: Capacity (High-Low)</option>
                            <option value="status-asc">Sort by: Status</option>
                        </select>
                        <i data-lucide="arrow-up-down" class="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 w-4 h-4 pointer-events-none"></i>
                    </div>
                </div>
            </div>
            
            <!-- Tables Grid View -->
            <div id="tableGridView" class="table-grid">
                <!-- Tables will be populated here -->
            </div>
            
            <!-- Tables List View (Hidden by default) -->
            <div id="tableListView" class="hidden">
                <div class="bg-white rounded-lg shadow overflow-hidden">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Table
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Category
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Capacity
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Status
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Location
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Actions
                                </th>
                            </tr>
                        </thead>
                        <tbody id="tableListBody" class="bg-white divide-y divide-gray-200">
                            <!-- Table rows will be populated here -->
                        </tbody>
                    </table>
                </div>
            </div>
            
            <!-- Pagination -->
            <div class="flex flex-col sm:flex-row items-center justify-between mt-6 pt-4 border-t border-gray-200">
                <div class="text-sm text-gray-600 mb-2 sm:mb-0">
                    Page <span id="tableCurrentPage">1</span> of <span id="tableTotalPages">1</span>
                </div>
                <nav class="inline-flex rounded-md shadow">
                    <button id="tablePrevPage" class="py-2 px-4 border border-gray-300 bg-white rounded-l-md text-sm font-medium text-gray-500 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed">
                        <i data-lucide="chevron-left" class="w-4 h-4"></i>
                        Previous
                    </button>
                    <div id="tablePageNumbers" class="flex border-t border-b border-gray-300">
                        <!-- Page numbers will be populated here -->
                    </div>
                    <button id="tableNextPage" class="py-2 px-4 border border-gray-300 bg-white rounded-r-md text-sm font-medium text-gray-500 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed">
                        Next
                        <i data-lucide="chevron-right" class="w-4 h-4"></i>
                    </button>
                </nav>
                <div class="flex items-center gap-2 mt-2 sm:mt-0">
                    <span class="text-sm text-gray-600">Items per page:</span>
                    <select id="tableItemsPerPage" class="px-2 py-1 border border-gray-300 rounded text-sm focus:outline-none focus:ring-2 focus:ring-[#F7B32B] bg-white">
                        <option value="12">12</option>
                        <option value="24">24</option>
                        <option value="48">48</option>
                        <option value="96">96</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="sticky bottom-0 bg-white border-t border-gray-200 px-6 py-4 shadow-lg">
            <div class="flex justify-between items-center">
                <div>
                    <span class="text-gray-600">Selected:</span>
                    <span id="selectedTableInfo" class="ml-2 font-medium text-gray-800">No table selected</span>
                </div>
                <div class="flex gap-3">
                    <button id="cancelTableBtn" class="px-5 py-2.5 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition duration-200 flex items-center gap-2 font-medium">
                        <i data-lucide="x" class="w-5 h-5"></i>
                        Cancel
                    </button>
                    <button id="confirmTableBtn" class="px-5 py-2.5 bg-[#F7B32B] text-white rounded-lg hover:bg-[#e6a117] transition duration-200 flex items-center gap-2 font-medium disabled:opacity-50 disabled:cursor-not-allowed">
                        <i data-lucide="check" class="w-5 h-5"></i>
                        Confirm Selection
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

    <!-- Receipt Modal -->
    <div id="receiptModal" class="receipt-modal hidden">
        <div class="min-h-screen bg-gray-50">
            <div class="sticky top-0 z-10 bg-white border-b border-gray-200 px-6 py-4 shadow-sm">
                <div class="flex justify-between items-center">
                    <h2 class="text-2xl font-bold text-gray-800 flex items-center gap-3">
                        <i data-lucide="receipt" class="w-8 h-8 text-[#F7B32B]"></i>
                        Order Receipt
                    </h2>
                    <div class="flex gap-3">
                        <button id="printReceiptBtn" class="px-4 py-2 bg-[#F7B32B] text-white rounded-lg hover:bg-[#e6a117] transition duration-200 flex items-center gap-2">
                            <i data-lucide="printer" class="w-5 h-5"></i>
                            Print
                        </button>
                        <button id="closeReceiptModal" class="p-2 rounded-lg hover:bg-gray-100 transition duration-200">
                            <i data-lucide="x" class="w-6 h-6 text-gray-600"></i>
                        </button>
                    </div>
                </div>
            </div>
            <div id="receiptContent" class="receipt-container">
                <!-- Receipt content will be loaded here -->
            </div>
        </div>
    </div>

    <!-- Nutritional Information Modal -->
    <div id="nutritionModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
        <div class="bg-white rounded-lg shadow-xl w-11/12 max-w-2xl max-h-[90vh] overflow-auto">
            <div class="p-6">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-xl font-bold text-gray-800" id="nutritionModalTitle">Nutritional Information</h3>
                    <button id="closeNutritionModal" class="text-gray-500 hover:text-gray-700">
                        <i data-lucide="x" class="w-6 h-6"></i>
                    </button>
                </div>
                
                <div id="nutritionContent" class="space-y-4">
                    <!-- Nutritional content will be populated here -->
                </div>
                
                <div class="flex justify-end mt-6">
                    <button id="closeNutritionBtn" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300 flex items-center gap-2">
                        <i data-lucide="x" class="w-4 h-4"></i>
                        Close
                    </button>
                </div>
            </div>
        </div>
    </div>
<script>
    // Initialize Lucide icons
    lucide.createIcons();

    // Active menu data - populated from PHP
    const menuData = {
        appetizers: [],
        mains: [],
        sides: [],
        desserts: [],
        drinks: [],
        specials: []
    };

    // Categories from PHP
    const categories = <?php echo isset($categories_json) ? $categories_json : '[]'; ?>;

    // Table data
    let tableData = [];
    let selectedTable = null;
    let selectedTableDetails = null;

    // State variables
    let currentPage = 1;
    const itemsPerPage = 15;
    let orderItems = [];
    let selectedPaymentMethod = null;
    let amountReceived = 0;
    let changeAmount = 0;
    
    // Filter state
    let currentCategory = 'all';
    let currentSearch = '';
    let currentPriceSort = 'default';
    let filteredItems = [];

    // Table Modal State Variables
    let tableCurrentPage = 1;
    let tableItemsPerPage = 12;
    let tableCurrentSearch = '';
    let tableCurrentStatus = 'all';
    let tableCurrentCategory = 'all';
    let tableCurrentSort = 'name-asc';
    let filteredTables = [];
    let isGridView = true;

    // DOM elements
    const menuItemsContainer = document.getElementById('menuItems');
    const orderItemsContainer = document.getElementById('orderItems');
    const pageNumbersContainer = document.getElementById('pageNumbers');
    const prevPageButton = document.getElementById('prevPage');
    const nextPageButton = document.getElementById('nextPage');
    const tableSelectBtn = document.getElementById('tableSelectBtn');
    const tableModal = document.getElementById('tableModal');
    const closeTableModal = document.getElementById('closeTableModal');
    const cancelTableBtn = document.getElementById('cancelTableBtn');
    const confirmTableBtn = document.getElementById('confirmTableBtn');
    const selectedTableText = document.getElementById('selectedTableText');
    const tableStatusBadge = document.getElementById('tableStatusBadge');
    const selectedTableInfo = document.getElementById('selectedTableInfo');
    const subtotalElement = document.getElementById('subtotal');
    const serviceChargeElement = document.getElementById('serviceCharge');
    const vatElement = document.getElementById('vat');
    const totalBillElement = document.getElementById('totalBill');
    const mopButtons = document.querySelectorAll('.mop-btn');
    const checkoutBtn = document.getElementById('checkoutBtn');
    const clearOrderBtn = document.getElementById('clearOrderBtn');
    const customerNameInput = document.getElementById('customerName');
    const orderNotesInput = document.getElementById('orderNotes');
    const nutritionModal = document.getElementById('nutritionModal');
    const closeNutritionModal = document.getElementById('closeNutritionModal');
    const closeNutritionBtn = document.getElementById('closeNutritionBtn');
    const nutritionModalTitle = document.getElementById('nutritionModalTitle');
    const nutritionContent = document.getElementById('nutritionContent');
    const receiptModal = document.getElementById('receiptModal');
    const closeReceiptModal = document.getElementById('closeReceiptModal');
    const printReceiptBtn = document.getElementById('printReceiptBtn');
    const receiptContent = document.getElementById('receiptContent');
    const amountReceivedInput = document.getElementById('amountReceived');
    const changeAmountElement = document.getElementById('changeAmount');
    const changeStatusElement = document.getElementById('changeStatus');
    
    // Filter elements
    const searchInput = document.getElementById('searchInput');
    const categoryFilter = document.getElementById('categoryFilter');
    const priceSort = document.getElementById('priceSort');
    const activeFiltersContainer = document.getElementById('activeFilters');
    const showingStartElement = document.getElementById('showingStart');
    const showingEndElement = document.getElementById('showingEnd');
    const totalItemsElement = document.getElementById('totalItems');
    const itemCountElement = document.getElementById('itemCount');

    // Table Modal DOM Elements
    const tableGrid = document.getElementById('tableGridView');
    const tableListBody = document.getElementById('tableListBody');
    const tableSearchInput = document.getElementById('tableSearchInput');
    const tableStatusFilter = document.getElementById('tableStatusFilter');
    const tableCategoryFilter = document.getElementById('tableCategoryFilter');
    const tableActiveFilters = document.getElementById('tableActiveFilters');
    const tableGridViewElement = document.getElementById('tableGridView');
    const tableListViewElement = document.getElementById('tableListView');
    const tableViewToggle = document.getElementById('tableViewToggle');
    const tableListViewBtn = document.getElementById('tableListView');
    const tableSort = document.getElementById('tableSort');
    const tablePrevPage = document.getElementById('tablePrevPage');
    const tableNextPage = document.getElementById('tableNextPage');
    const tablePageNumbers = document.getElementById('tablePageNumbers');
    const tableItemsPerPageSelect = document.getElementById('tableItemsPerPage');
    const tableCurrentPageElement = document.getElementById('tableCurrentPage');
    const tableTotalPagesElement = document.getElementById('tableTotalPages');
    const tableShowingStartElement = document.getElementById('tableShowingStart');
    const tableShowingEndElement = document.getElementById('tableShowingEnd');
    const tableTotalItemsElement = document.getElementById('tableTotalItems');

    // ============================================
    // AMOUNT & CHANGE CALCULATION FUNCTIONS
    // ============================================
    function calculateChange() {
        const totalBillText = totalBillElement.textContent.replace('₱', '').replace(/,/g, '');
        const totalBill = parseFloat(totalBillText) || 0;
        
        // Get amount received value
        amountReceived = parseFloat(amountReceivedInput.value) || 0;
        
        // Calculate change
        changeAmount = amountReceived - totalBill;
        
        // Update UI
        if (amountReceived > 0) {
            changeAmountElement.textContent = `₱${changeAmount.toLocaleString(undefined, { minimumFractionDigits: 2 })}`;
            
            // Style based on change status
            if (changeAmount >= 0) {
                changeAmountElement.classList.remove('text-red-600');
                changeAmountElement.classList.add('text-green-600');
                changeStatusElement.textContent = 'Sufficient payment';
                amountReceivedInput.classList.remove('invalid');
                amountReceivedInput.classList.add('valid');
            } else {
                changeAmountElement.classList.remove('text-green-600');
                changeAmountElement.classList.add('text-red-600');
                changeStatusElement.textContent = `Insufficient payment. Need ₱${(-changeAmount).toLocaleString(undefined, { minimumFractionDigits: 2 })} more`;
                amountReceivedInput.classList.remove('valid');
                amountReceivedInput.classList.add('invalid');
            }
        } else {
            changeAmountElement.textContent = '₱0.00';
            changeAmountElement.classList.remove('text-green-600', 'text-red-600');
            changeStatusElement.textContent = 'Enter amount to calculate change';
            amountReceivedInput.classList.remove('valid', 'invalid');
            changeAmount = 0;
        }
        
        return changeAmount;
    }

    // ============================================
    // AUTO-DOWNLOAD RECEIPT FUNCTION
    // ============================================
    function autoDownloadReceipt(apiResponse) {
        if (apiResponse.status === 'success' && apiResponse.receipt_jpeg) {
            console.log('Auto-downloading receipt...');
            
            // Create invisible download link
            const link = document.createElement('a');
            link.style.display = 'none';
            link.href = apiResponse.receipt_jpeg;
            link.download = apiResponse.receipt_filename || 'receipt.jpg';
            
            // Trigger download
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
            
            // Optional: Show the receipt in a new tab
            setTimeout(() => {
                window.open(apiResponse.receipt_jpeg, '_blank');
            }, 500);
            
            return true;
        }
        return false;
    }

    // Initialize the POS system
    async function init() {
        // Parse active menus from PHP
        const activeMenus = <?php echo isset($active_menus_json) ? $active_menus_json : '[]'; ?>;
        
        console.log('Active menus loaded:', activeMenus);
        
        // Organize menus by category
        activeMenus.forEach(menu => {
            const category = menu.category?.toLowerCase() || 'specials';
            
            // Determine the image source
            let imageSrc = '';
            let hasImage = false;
            
            // Check if image_url exists and is not empty
            if (menu.image_url && menu.image_url.trim() !== '') {
                hasImage = true;
                // Use the correct path structure
                imageSrc = '../M3/Menu_uploaded/menu_images/original/' + encodeURIComponent(menu.image_url);
            }
            // Check if 'image' field exists as fallback
            else if (menu.image && menu.image.trim() !== '') {
                hasImage = true;
                imageSrc = menu.image;
            }
            
            if (menuData[category]) {
                menuData[category].push({
                    id: menu.menu_id || menu.id,
                    name: menu.name || 'Unnamed Item',
                    category: category,
                    price: parseFloat(menu.price) || 0,
                    status: menu.status || 'inactive',
                    image: hasImage ? imageSrc : '',
                    hasImage: hasImage,
                    description: menu.description || '',
                    prepTime: menu.prep_time || 0,
                    spiceLevel: menu.spice_level || 0,
                    nutrition: menu.nutrition || null
                });
            }
        });
        
        // Fetch tables from server
        await fetchTables();
        
        applyFilters();
        setupEventListeners();
        updateBill();
        updateItemCount();
        
        console.log(`Loaded ${activeMenus.length} active menu items`);
        console.log(`Loaded ${tableData.length} tables`);
    }

    // Fetch tables from server
    async function fetchTables() {
        try {
            const response = await fetch('sub-modules/fetch_tables.php');
            const data = await response.json();
            
            if (data.status === 'success') {
                tableData = data.tables;
                console.log('Tables loaded:', tableData);
            } else {
                console.error('Failed to load tables:', data.message);
                tableData = [];
            }
        } catch (error) {
            console.error('Error fetching tables:', error);
            tableData = [];
        }
    }

    // Function to handle image loading errors
    function handleImageError(img, itemName) {
        const fallbackSvg = `data:image/svg+xml;base64,${btoa(`
            <svg width="200" height="200" xmlns="http://www.w3.org/2000/svg">
                <rect width="200" height="200" fill="#f0f0f0"/>
                <text x="50%" y="45%" font-family="Arial" font-size="14" text-anchor="middle" fill="#999">${itemName}</text>
                <text x="50%" y="55%" font-family="Arial" font-size="12" text-anchor="middle" fill="#999">Image Not Available</text>
            </svg>
        `)}`;
        
        img.src = fallbackSvg;
        img.classList.remove('hover:scale-110');
        img.classList.add('object-contain', 'p-4');
        img.onerror = null;
    }

    // Set up event listeners
    function setupEventListeners() {
        // Search input
        searchInput.addEventListener('input', (e) => {
            currentSearch = e.target.value.toLowerCase();
            currentPage = 1;
            applyFilters();
        });
        
        // Category filter
        categoryFilter.addEventListener('change', (e) => {
            currentCategory = e.target.value;
            currentPage = 1;
            applyFilters();
            updateActiveFilters();
        });
        
        // Price sort
        priceSort.addEventListener('change', (e) => {
            currentPriceSort = e.target.value;
            currentPage = 1;
            applyFilters();
            updateActiveFilters();
        });

        // Pagination
        prevPageButton.addEventListener('click', () => {
            if (currentPage > 1) {
                currentPage--;
                renderMenuItems();
            }
        });

        nextPageButton.addEventListener('click', () => {
            const totalPages = Math.ceil(filteredItems.length / itemsPerPage);
            if (currentPage < totalPages) {
                currentPage++;
                renderMenuItems();
            }
        });

        // Table selection
        tableSelectBtn.addEventListener('click', () => {
            renderTablesInModal();
        });

        closeTableModal.addEventListener('click', () => {
            tableModal.classList.add('hidden');
            document.body.style.overflow = 'auto';
        });

        cancelTableBtn.addEventListener('click', () => {
            tableModal.classList.add('hidden');
            document.body.style.overflow = 'auto';
        });

        confirmTableBtn.addEventListener('click', () => {
            if (selectedTable) {
                selectedTableDetails = tableData.find(t => t.id === selectedTable);
                selectedTableText.textContent = `Table ${selectedTableDetails.name}`;
                
                // Update status badge
                tableStatusBadge.classList.remove('hidden');
                tableStatusBadge.textContent = selectedTableDetails.status;
                tableStatusBadge.className = `text-xs font-medium px-2 py-1 rounded-full ${getStatusBadgeClass(selectedTableDetails.status)}`;
                
                // Update selected table info
                selectedTableInfo.textContent = `Table ${selectedTableDetails.name} (${selectedTableDetails.capacity} persons, ${selectedTableDetails.category})`;
                
                tableModal.classList.add('hidden');
                document.body.style.overflow = 'auto';
            } else {
                Swal.fire({
                    icon: 'warning',
                    title: 'No Table Selected',
                    text: 'Please select a table before confirming.',
                    background: 'white',
                    color: '#333'
                });
            }
        });

        // Payment method selection
        mopButtons.forEach(button => {
            button.addEventListener('click', () => {
                mopButtons.forEach(btn => {
                    btn.classList.remove('border-[#F7B32B]', 'bg-yellow-50');
                });
                button.classList.add('border-[#F7B32B]', 'bg-yellow-50');
                selectedPaymentMethod = button.dataset.mop;
                
                // Auto-select amount for cash payment
                if (selectedPaymentMethod === 'cash') {
                    amountReceivedInput.focus();
                }
            });
        });

        // Amount received input
        amountReceivedInput.addEventListener('input', () => {
            calculateChange();
        });

        amountReceivedInput.addEventListener('blur', () => {
            const value = parseFloat(amountReceivedInput.value);
            if (value >= 0) {
                amountReceivedInput.value = value.toFixed(2);
            }
            calculateChange();
        });

        // Checkout button
        checkoutBtn.addEventListener('click', processCheckout);

        // Clear order button
        clearOrderBtn.addEventListener('click', clearOrder);

        // Nutrition modal
        closeNutritionModal.addEventListener('click', () => {
            nutritionModal.classList.add('hidden');
        });

        closeNutritionBtn.addEventListener('click', () => {
            nutritionModal.classList.add('hidden');
        });

        // Receipt modal
        closeReceiptModal.addEventListener('click', () => {
            receiptModal.classList.add('hidden');
            document.body.style.overflow = 'auto';
        });

        printReceiptBtn.addEventListener('click', () => {
            window.print();
        });

        // ============================================
        // TABLE MODAL EVENT LISTENERS
        // ============================================
        // Table search input
        tableSearchInput.addEventListener('input', (e) => {
            tableCurrentSearch = e.target.value.toLowerCase();
            tableCurrentPage = 1;
            applyTableFilters();
        });
        
        // Table status filter
        tableStatusFilter.addEventListener('change', (e) => {
            tableCurrentStatus = e.target.value;
            tableCurrentPage = 1;
            applyTableFilters();
            updateTableActiveFilters();
        });
        
        // Table category filter
        tableCategoryFilter.addEventListener('change', (e) => {
            tableCurrentCategory = e.target.value;
            tableCurrentPage = 1;
            applyTableFilters();
            updateTableActiveFilters();
        });
        
        // Table sort options
        tableSort.addEventListener('change', (e) => {
            tableCurrentSort = e.target.value;
            tableCurrentPage = 1;
            applyTableFilters();
        });
        
        // Table view toggle
        tableViewToggle.addEventListener('click', () => {
            if (!isGridView) {
                isGridView = true;
                tableGridViewElement.classList.remove('hidden');
                tableListViewElement.classList.add('hidden');
                tableViewToggle.classList.add('bg-gray-100', 'text-gray-600');
                tableViewToggle.classList.remove('text-gray-400');
                tableListViewBtn.classList.remove('bg-gray-100', 'text-gray-600');
                tableListViewBtn.classList.add('text-gray-400');
            }
        });
        
        tableListViewBtn.addEventListener('click', () => {
            if (isGridView) {
                isGridView = false;
                tableGridViewElement.classList.add('hidden');
                tableListViewElement.classList.remove('hidden');
                tableListViewBtn.classList.add('bg-gray-100', 'text-gray-600');
                tableListViewBtn.classList.remove('text-gray-400');
                tableViewToggle.classList.remove('bg-gray-100', 'text-gray-600');
                tableViewToggle.classList.add('text-gray-400');
            }
        });
        
        // Table pagination
        tablePrevPage.addEventListener('click', () => {
            if (tableCurrentPage > 1) {
                tableCurrentPage--;
                renderTables();
            }
        });
        
        tableNextPage.addEventListener('click', () => {
            const totalPages = Math.ceil(filteredTables.length / tableItemsPerPage);
            if (tableCurrentPage < totalPages) {
                tableCurrentPage++;
                renderTables();
            }
        });
        
        // Table items per page
        tableItemsPerPageSelect.addEventListener('change', (e) => {
            tableItemsPerPage = parseInt(e.target.value);
            tableCurrentPage = 1;
            applyTableFilters();
        });
    }

    // Apply all filters and sorting
    function applyFilters() {
        let items = [];
        if (currentCategory === 'all') {
            items = Object.values(menuData).flat();
        } else {
            items = menuData[currentCategory] || [];
        }
        
        if (currentSearch) {
            items = items.filter(item => 
                item.name.toLowerCase().includes(currentSearch) ||
                item.description.toLowerCase().includes(currentSearch) ||
                item.id.toString().includes(currentSearch)
            );
        }
        
        if (currentPriceSort === 'low-high') {
            items.sort((a, b) => a.price - b.price);
        } else if (currentPriceSort === 'high-low') {
            items.sort((a, b) => b.price - a.price);
        }
        
        filteredItems = items;
        renderMenuItems();
        updateActiveFilters();
    }

    // Apply table filters and sorting
    function applyTableFilters() {
        let filtered = [...tableData];
        
        // Apply search filter
        if (tableCurrentSearch) {
            filtered = filtered.filter(table => 
                table.name.toLowerCase().includes(tableCurrentSearch) ||
                (table.location && table.location.toLowerCase().includes(tableCurrentSearch)) ||
                table.capacity.toString().includes(tableCurrentSearch) ||
                table.category.toLowerCase().includes(tableCurrentSearch)
            );
        }
        
        // Apply status filter
        if (tableCurrentStatus !== 'all') {
            filtered = filtered.filter(table => 
                table.status.toLowerCase() === tableCurrentStatus.toLowerCase()
            );
        }
        
        // Apply category filter
        if (tableCurrentCategory !== 'all') {
            filtered = filtered.filter(table => 
                table.category.toLowerCase() === tableCurrentCategory.toLowerCase()
            );
        }
        
        // Apply sorting
        filtered.sort((a, b) => {
            switch (tableCurrentSort) {
                case 'name-asc':
                    return a.name.localeCompare(b.name, undefined, { numeric: true });
                case 'name-desc':
                    return b.name.localeCompare(a.name, undefined, { numeric: true });
                case 'capacity-asc':
                    return a.capacity - b.capacity;
                case 'capacity-desc':
                    return b.capacity - a.capacity;
                case 'status-asc':
                    return a.status.localeCompare(b.status);
                default:
                    return a.name.localeCompare(b.name, undefined, { numeric: true });
            }
        });
        
        filteredTables = filtered;
        renderTables();
        updateTablePaginationInfo();
    }

    // Update active filters display
    function updateActiveFilters() {
        activeFiltersContainer.innerHTML = '';
        let hasActiveFilters = false;
        
        if (currentCategory !== 'all') {
            hasActiveFilters = true;
            const categoryName = currentCategory.charAt(0).toUpperCase() + currentCategory.slice(1);
            const filterBadge = createFilterBadge('Category', categoryName, () => {
                categoryFilter.value = 'all';
                currentCategory = 'all';
                currentPage = 1;
                applyFilters();
            });
            activeFiltersContainer.appendChild(filterBadge);
        }
        
        if (currentPriceSort !== 'default') {
            hasActiveFilters = true;
            const sortText = currentPriceSort === 'low-high' ? 'Price: Low to High' : 'Price: High to Low';
            const filterBadge = createFilterBadge('Sort', sortText, () => {
                priceSort.value = 'default';
                currentPriceSort = 'default';
                currentPage = 1;
                applyFilters();
            });
            activeFiltersContainer.appendChild(filterBadge);
        }
        
        if (currentSearch) {
            hasActiveFilters = true;
            const filterBadge = createFilterBadge('Search', currentSearch, () => {
                searchInput.value = '';
                currentSearch = '';
                currentPage = 1;
                applyFilters();
            });
            activeFiltersContainer.appendChild(filterBadge);
        }
        
        if (hasActiveFilters) {
            activeFiltersContainer.classList.remove('hidden');
        } else {
            activeFiltersContainer.classList.add('hidden');
        }
    }

    // Update table active filters display
    function updateTableActiveFilters() {
        tableActiveFilters.innerHTML = '';
        let hasActiveFilters = false;
        
        if (tableCurrentStatus !== 'all') {
            hasActiveFilters = true;
            const statusText = tableCurrentStatus.charAt(0).toUpperCase() + tableCurrentStatus.slice(1);
            const filterBadge = createTableFilterBadge('Status', statusText, () => {
                tableStatusFilter.value = 'all';
                tableCurrentStatus = 'all';
                tableCurrentPage = 1;
                applyTableFilters();
            });
            tableActiveFilters.appendChild(filterBadge);
        }
        
        if (tableCurrentCategory !== 'all') {
            hasActiveFilters = true;
            const categoryText = tableCurrentCategory.charAt(0).toUpperCase() + tableCurrentCategory.slice(1);
            const filterBadge = createTableFilterBadge('Category', categoryText, () => {
                tableCategoryFilter.value = 'all';
                tableCurrentCategory = 'all';
                tableCurrentPage = 1;
                applyTableFilters();
            });
            tableActiveFilters.appendChild(filterBadge);
        }
        
        if (hasActiveFilters) {
            tableActiveFilters.classList.remove('hidden');
        } else {
            tableActiveFilters.classList.add('hidden');
        }
    }

    // Create filter badge element
    function createFilterBadge(type, value, onClick) {
        const badge = document.createElement('div');
        badge.className = 'flex items-center gap-1 bg-[#F7B32B] text-white text-xs font-medium px-3 py-1 rounded-full';
        badge.innerHTML = `
            <span>${type}: ${value}</span>
            <button type="button" class="hover:text-gray-200">
                <i data-lucide="x" class="w-3 h-3"></i>
            </button>
        `;
        
        const removeBtn = badge.querySelector('button');
        removeBtn.addEventListener('click', onClick);
        
        return badge;
    }

    // Create table filter badge element
    function createTableFilterBadge(type, value, onClick) {
        const badge = document.createElement('div');
        badge.className = 'flex items-center gap-1 bg-[#F7B32B] text-white text-xs font-medium px-3 py-1 rounded-full';
        badge.innerHTML = `
            <span>${type}: ${value}</span>
            <button type="button" class="hover:text-gray-200">
                <i data-lucide="x" class="w-3 h-3"></i>
            </button>
        `;
        
        const removeBtn = badge.querySelector('button');
        removeBtn.addEventListener('click', onClick);
        
        return badge;
    }

    // Render menu items
    function renderMenuItems() {
        const startIndex = (currentPage - 1) * itemsPerPage;
        const endIndex = startIndex + itemsPerPage;
        const itemsToShow = filteredItems.slice(startIndex, endIndex);

        menuItemsContainer.innerHTML = '';

        if (itemsToShow.length === 0) {
            menuItemsContainer.innerHTML = `
                <div class="col-span-full text-center py-12">
                    <i data-lucide="utensils-crossed" class="w-16 h-16 mx-auto text-gray-300 mb-4"></i>
                    <p class="text-gray-500 text-lg mb-2">No menu items found</p>
                    <p class="text-gray-400 text-sm">${currentSearch ? 'Try a different search term' : 'No items match your filters'}</p>
                </div>
            `;
            lucide.createIcons();
            updatePaginationInfo();
            return;
        }

        itemsToShow.forEach(item => {
            const menuCard = document.createElement('div');
            menuCard.className = 'menu-card glassy-card rounded-lg shadow-md overflow-hidden border border-gray-200';
            
            let imageHTML = '';
            if (item.hasImage) {
                imageHTML = `
                    <img src="${item.image}" 
                         alt="${item.name}" 
                         class="w-full h-full object-cover transition-transform duration-500 hover:scale-110"
                         onerror="handleImageError(this, '${item.name.replace(/'/g, "\\'")}')">
                `;
            } else {
                imageHTML = `
                    <div class="w-full h-full flex flex-col items-center justify-center bg-gradient-to-br from-gray-100 to-gray-200">
                        <i data-lucide="utensils" class="w-12 h-12 text-gray-400 mb-2"></i>
                        <p class="text-xs text-gray-500 font-medium">No image</p>
                    </div>
                `;
            }
            
            menuCard.innerHTML = `
                <div class="h-40 bg-gray-200 overflow-hidden relative">
                    ${imageHTML}
                    <button class="nutrition-btn absolute top-2 right-2 bg-white/80 hover:bg-white p-1.5 rounded-full transition duration-200" data-id="${item.id}">
                        <i data-lucide="eye" class="w-4 h-4 text-gray-700"></i>
                    </button>
                </div>
                <div class="p-3">
                    <div class="flex justify-between items-start mb-2">
                        <span class="text-xs font-semibold bg-gray-100 text-gray-700 px-2 py-1 rounded">${item.id}</span>
                        <span class="text-lg font-bold text-[#F7B32B]">₱${item.price.toLocaleString()}</span>
                    </div>
                    <h3 class="font-medium text-gray-800 mb-1 text-sm line-clamp-1">${item.name}</h3>
                    <p class="text-xs text-gray-600 mb-3 line-clamp-2">${item.description}</p>
                    <button class="add-to-order w-full primary-button py-2 rounded-md text-sm font-medium transition duration-200 flex items-center justify-center"
                            data-id="${item.id}">
                        <i data-lucide="plus" class="w-4 h-4 mr-1"></i>
                        Add to Order
                    </button>
                </div>
            `;
            menuItemsContainer.appendChild(menuCard);
        });

        lucide.createIcons();

        document.querySelectorAll('.add-to-order').forEach(button => {
            button.addEventListener('click', (e) => {
                const itemId = e.currentTarget.dataset.id;
                addToOrder(itemId);
            });
        });

        document.querySelectorAll('.nutrition-btn').forEach(button => {
            button.addEventListener('click', (e) => {
                const itemId = e.currentTarget.dataset.id;
                showNutritionInfo(itemId);
            });
        });

        updatePagination();
        updatePaginationInfo();
    }

    // Render tables based on current view
    function renderTables() {
        const startIndex = (tableCurrentPage - 1) * tableItemsPerPage;
        const endIndex = startIndex + tableItemsPerPage;
        const tablesToShow = filteredTables.slice(startIndex, endIndex);
        
        if (isGridView) {
            renderGridView(tablesToShow);
        } else {
            renderListView(tablesToShow);
        }
        
        updateTablePagination();
        updateTablePaginationInfo();
        lucide.createIcons();
    }

    // Render grid view
    function renderGridView(tables) {
        tableGrid.innerHTML = '';
        
        if (tables.length === 0) {
            tableGrid.innerHTML = `
                <div class="col-span-full text-center py-12">
                    <i data-lucide="table" class="w-16 h-16 mx-auto text-gray-300 mb-4"></i>
                    <p class="text-gray-500 text-lg mb-2">No tables found</p>
                    <p class="text-gray-400 text-sm">${tableCurrentSearch ? 'Try a different search term' : 'No tables match your filters'}</p>
                </div>
            `;
            return;
        }
        
        tables.forEach(table => {
            const tableCard = document.createElement('div');
            const isSelectable = table.status === 'Available' || table.status === 'Reserved';
            const isDisabled = table.status === 'Maintenance' || table.status === 'Hidden';
            
            tableCard.className = `table-card ${getTableCardClass(table.status)} ${isDisabled ? 'disabled' : ''} ${selectedTable === table.id ? 'selected' : ''}`;
            
            let imageHTML = '';
            if (table.image_url && !table.image_url.includes('default-table.jpg')) {
                imageHTML = `
                    <img src="${table.image_url}" 
                         alt="Table ${table.name}" 
                         class="table-image"
                         onerror="this.onerror=null; this.src='data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMjAwIiBoZWlnaHQ9IjIwMCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iMjAwIiBoZWlnaHQ9IjIwMCIgZmlsbD0iI2YwZjBmMCIvPjx0ZXh0IHg9IjEwMCUiIHk9IjUwJSIgZm9udC1mYW1pbHk9IkFyaWFsIiBmb250LXNpemU9IjE0IiB0ZXh0LWFuY2hvcj0ibWlkZGxlIiBmaWxsPSIjOTk5Ij5JbWFnZSBOb3QgRm91bmQ8L3RleHQ+PC9zdmc+'">
                `;
            } else {
                imageHTML = `
                    <div class="table-image no-image flex flex-col items-center justify-center">
                        <i data-lucide="table" class="w-8 h-8 mb-1"></i>
                        <span class="text-xs">No Image</span>
                    </div>
                `;
            }
            
            tableCard.innerHTML = `
                <div class="relative">
                    ${imageHTML}
                    <div class="absolute top-2 right-2">
                        <span class="text-xs font-semibold px-2 py-1 rounded-full bg-white/90 text-gray-800">
                            ${table.category}
                        </span>
                    </div>
                </div>
                <div class="p-4">
                    <div class="flex justify-between items-center mb-2">
                        <h3 class="text-lg font-bold">Table ${table.name}</h3>
                        <span class="text-xs font-medium px-2 py-1 rounded-full ${getStatusBadgeClass(table.status)}">
                            ${table.status}
                        </span>
                    </div>
                    <div class="flex items-center gap-2 text-sm text-gray-600 mb-1">
                        <i data-lucide="users" class="w-4 h-4"></i>
                        <span>Capacity: ${table.capacity} persons</span>
                    </div>
                    <div class="flex items-center gap-2 text-sm text-gray-600 mb-3">
                        <i data-lucide="map-pin" class="w-4 h-4"></i>
                        <span>${table.location || 'Main Hall'}</span>
                    </div>
                    <button class="table-select-btn w-full px-4 py-2 bg-[#F7B32B] text-white rounded-lg hover:bg-[#e6a117] transition duration-200 text-sm font-medium disabled:opacity-50 disabled:cursor-not-allowed"
                            data-id="${table.id}"
                            ${!isSelectable ? 'disabled' : ''}>
                        ${selectedTable === table.id ? 'Selected' : 'Select Table'}
                    </button>
                </div>
            `;
            
            tableCard.addEventListener('click', (e) => {
                if (isSelectable && !e.target.closest('.table-select-btn')) {
                    selectTableInModal(table.id);
                }
            });
            
            const selectBtn = tableCard.querySelector('.table-select-btn');
            selectBtn.addEventListener('click', (e) => {
                e.stopPropagation();
                selectTableInModal(table.id);
            });
            
            tableGrid.appendChild(tableCard);
        });
    }

    // Render list view
    function renderListView(tables) {
        tableListBody.innerHTML = '';
        
        if (tables.length === 0) {
            tableListBody.innerHTML = `
                <tr>
                    <td colspan="6" class="px-6 py-12 text-center">
                        <i data-lucide="table" class="w-16 h-16 mx-auto text-gray-300 mb-4"></i>
                        <p class="text-gray-500 text-lg mb-2">No tables found</p>
                        <p class="text-gray-400 text-sm">${tableCurrentSearch ? 'Try a different search term' : 'No tables match your filters'}</p>
                    </td>
                </tr>
            `;
            return;
        }
        
        tables.forEach(table => {
            const isSelectable = table.status === 'Available' || table.status === 'Reserved';
            const isSelected = selectedTable === table.id;
            
            const row = document.createElement('tr');
            row.className = `hover:bg-gray-50 ${isSelected ? 'bg-yellow-50' : ''}`;
            row.innerHTML = `
                <td class="px-6 py-4 whitespace-nowrap">
                    <div class="flex items-center">
                        <div class="ml-4">
                            <div class="text-sm font-medium text-gray-900">Table ${table.name}</div>
                        </div>
                    </div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                    <div class="text-sm text-gray-900">${table.category}</div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                    <div class="text-sm text-gray-900">${table.capacity} persons</div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full ${getStatusBadgeClass(table.status)}">
                        ${table.status}
                    </span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                    ${table.location || 'Main Hall'}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                    <button class="table-select-btn-list text-[#F7B32B] hover:text-[#e6a117] disabled:text-gray-400 disabled:cursor-not-allowed"
                            data-id="${table.id}"
                            ${!isSelectable ? 'disabled' : ''}>
                        ${isSelected ? 'Selected ✓' : 'Select'}
                    </button>
                </td>
            `;
            
            row.addEventListener('click', (e) => {
                if (isSelectable && !e.target.closest('.table-select-btn-list')) {
                    selectTableInModal(table.id);
                }
            });
            
            const selectBtn = row.querySelector('.table-select-btn-list');
            selectBtn.addEventListener('click', (e) => {
                e.stopPropagation();
                selectTableInModal(table.id);
            });
            
            tableListBody.appendChild(row);
        });
    }

    // Select table in modal
    function selectTableInModal(tableId) {
        const table = tableData.find(t => t.id === tableId);
        if (!table) return;
        
        if (table.status.toLowerCase() !== 'available' && table.status.toLowerCase() !== 'reserved') {
            Swal.fire({
                icon: 'warning',
                title: 'Table Not Available',
                text: `Table ${table.name} is currently ${table.status.toLowerCase()}. Please select an available table.`,
                background: 'white',
                color: '#333'
            });
            return;
        }
        
        selectedTable = tableId;
        
        // Update selected table info
        selectedTableInfo.textContent = `Table ${table.name} (${table.capacity} persons, ${table.category})`;
        
        // Re-render to update selection state
        renderTables();
        
        // Enable confirm button
        confirmTableBtn.disabled = false;
    }

    // Update pagination info text
    function updatePaginationInfo() {
        const totalItems = filteredItems.length;
        const startIndex = Math.min((currentPage - 1) * itemsPerPage + 1, totalItems);
        const endIndex = Math.min(currentPage * itemsPerPage, totalItems);
        
        showingStartElement.textContent = startIndex;
        showingEndElement.textContent = endIndex;
        totalItemsElement.textContent = totalItems;
    }

    // Update table pagination info
    function updateTablePaginationInfo() {
        const totalItems = filteredTables.length;
        const startIndex = Math.min((tableCurrentPage - 1) * tableItemsPerPage + 1, totalItems);
        const endIndex = Math.min(tableCurrentPage * tableItemsPerPage, totalItems);
        const totalPages = Math.ceil(totalItems / tableItemsPerPage);
        
        tableShowingStartElement.textContent = startIndex;
        tableShowingEndElement.textContent = endIndex;
        tableTotalItemsElement.textContent = totalItems;
        tableCurrentPageElement.textContent = tableCurrentPage;
        tableTotalPagesElement.textContent = totalPages || 1;
    }

    // Update pagination controls
    function updatePagination() {
        const totalItems = filteredItems.length;
        const totalPages = Math.ceil(totalItems / itemsPerPage);
        
        pageNumbersContainer.innerHTML = '';
        
        if (totalPages > 0) {
            const firstButton = createPageButton(1);
            pageNumbersContainer.appendChild(firstButton);
        }
        
        if (currentPage > 3) {
            const ellipsis = document.createElement('span');
            ellipsis.className = 'py-2 px-4 border border-gray-300 bg-white text-sm font-medium text-gray-500';
            ellipsis.textContent = '...';
            pageNumbersContainer.appendChild(ellipsis);
        }
        
        for (let i = Math.max(2, currentPage - 1); i <= Math.min(totalPages - 1, currentPage + 1); i++) {
            const pageButton = createPageButton(i);
            pageNumbersContainer.appendChild(pageButton);
        }
        
        if (currentPage < totalPages - 2) {
            const ellipsis = document.createElement('span');
            ellipsis.className = 'py-2 px-4 border border-gray-300 bg-white text-sm font-medium text-gray-500';
            ellipsis.textContent = '...';
            pageNumbersContainer.appendChild(ellipsis);
        }
        
        if (totalPages > 1) {
            const lastButton = createPageButton(totalPages);
            pageNumbersContainer.appendChild(lastButton);
        }
        
        prevPageButton.disabled = currentPage === 1;
        nextPageButton.disabled = currentPage === totalPages || totalPages === 0;
    }

    // Update table pagination controls
    function updateTablePagination() {
        const totalItems = filteredTables.length;
        const totalPages = Math.ceil(totalItems / tableItemsPerPage);
        
        tablePageNumbers.innerHTML = '';
        
        if (totalPages <= 1) {
            tablePrevPage.disabled = true;
            tableNextPage.disabled = true;
            return;
        }
        
        if (totalPages > 0) {
            const firstButton = createTablePageButton(1);
            tablePageNumbers.appendChild(firstButton);
        }
        
        if (tableCurrentPage > 3) {
            const ellipsis = document.createElement('span');
            ellipsis.className = 'py-2 px-4 border border-gray-300 bg-white text-sm font-medium text-gray-500';
            ellipsis.textContent = '...';
            tablePageNumbers.appendChild(ellipsis);
        }
        
        for (let i = Math.max(2, tableCurrentPage - 1); i <= Math.min(totalPages - 1, tableCurrentPage + 1); i++) {
            const pageButton = createTablePageButton(i);
            tablePageNumbers.appendChild(pageButton);
        }
        
        if (tableCurrentPage < totalPages - 2) {
            const ellipsis = document.createElement('span');
            ellipsis.className = 'py-2 px-4 border border-gray-300 bg-white text-sm font-medium text-gray-500';
            ellipsis.textContent = '...';
            tablePageNumbers.appendChild(ellipsis);
        }
        
        if (totalPages > 1) {
            const lastButton = createTablePageButton(totalPages);
            tablePageNumbers.appendChild(lastButton);
        }
        
        tablePrevPage.disabled = tableCurrentPage === 1;
        tableNextPage.disabled = tableCurrentPage === totalPages || totalPages === 0;
    }

    // Create page button
    function createPageButton(pageNumber) {
        const pageButton = document.createElement('button');
        pageButton.className = `py-2 px-4 border border-gray-300 bg-white text-sm font-medium ${
            pageNumber === currentPage ? 'text-[#F7B32B] bg-yellow-50' : 'text-gray-500 hover:bg-gray-50'
        }`;
        pageButton.textContent = pageNumber;
        pageButton.addEventListener('click', () => {
            currentPage = pageNumber;
            renderMenuItems();
        });
        return pageButton;
    }

    // Create table page button
    function createTablePageButton(pageNumber) {
        const pageButton = document.createElement('button');
        pageButton.className = `py-2 px-4 border border-gray-300 bg-white text-sm font-medium ${
            pageNumber === tableCurrentPage ? 'text-[#F7B32B] bg-yellow-50' : 'text-gray-500 hover:bg-gray-50'
        }`;
        pageButton.textContent = pageNumber;
        pageButton.addEventListener('click', () => {
            tableCurrentPage = pageNumber;
            renderTables();
        });
        return pageButton;
    }

    // Render tables in modal
    function renderTablesInModal() {
        // Reset filters
        tableCurrentPage = 1;
        tableCurrentSearch = '';
        tableCurrentStatus = 'all';
        tableCurrentCategory = 'all';
        tableCurrentSort = 'name-asc';
        
        // Reset UI elements
        tableSearchInput.value = '';
        tableStatusFilter.value = 'all';
        tableCategoryFilter.value = 'all';
        tableSort.value = 'name-asc';
        
        // Apply filters and render
        applyTableFilters();
        updateTableActiveFilters();
        
        // Reset selection
        selectedTable = null;
        selectedTableInfo.textContent = 'No table selected';
        confirmTableBtn.disabled = true;
        
        // Show modal
        tableModal.classList.remove('hidden');
        document.body.style.overflow = 'hidden';
    }

    // Get table card CSS class based on status
    function getTableCardClass(status) {
        switch(status.toLowerCase()) {
            case 'available': return 'table-available';
            case 'occupied': return 'table-occupied';
            case 'maintenance': return 'table-maintenance';
            case 'reserved': return 'table-reserved';
            case 'hidden': return 'table-hidden';
            default: return 'bg-gray-200';
        }
    }

    // Get status badge CSS class
    function getStatusBadgeClass(status) {
        switch(status.toLowerCase()) {
            case 'available': return 'bg-green-100 text-green-800';
            case 'occupied': return 'bg-red-100 text-red-800';
            case 'maintenance': return 'bg-yellow-100 text-yellow-800';
            case 'reserved': return 'bg-blue-100 text-blue-800';
            case 'hidden': return 'bg-gray-100 text-gray-800';
            default: return 'bg-gray-100 text-gray-800';
        }
    }

    // Show nutritional information modal
    function showNutritionInfo(itemId) {
        let item = null;
        for (const category in menuData) {
            item = menuData[category].find(menuItem => menuItem.id === itemId);
            if (item) break;
        }
        
        if (!item) {
            Swal.fire({
                icon: 'error',
                title: 'Item Not Found',
                text: 'Nutritional information not available for this item.',
                background: 'white',
                color: '#333'
            });
            return;
        }
        
        nutritionModalTitle.textContent = `Nutritional Information - ${item.name}`;
        
        let nutritionData = {
            calories: 'Not available',
            protein: 'Not available',
            carbs: 'Not available',
            fat: 'Not available',
            allergens: 'Not specified',
            ingredients: 'Information not available'
        };
        
        if (item.nutrition) {
            try {
                if (typeof item.nutrition === 'string') {
                    nutritionData = JSON.parse(item.nutrition);
                } else if (typeof item.nutrition === 'object') {
                    nutritionData = item.nutrition;
                }
            } catch (e) {
                console.error('Error parsing nutrition data:', e);
            }
        }
        
        nutritionContent.innerHTML = `
            <div class="grid grid-cols-2 gap-4">
                <div class="bg-gray-50 p-4 rounded-lg">
                    <h4 class="font-medium text-gray-700 mb-2">Calories</h4>
                    <p class="text-lg font-semibold text-[#F7B32B]">${nutritionData.calories}</p>
                </div>
                <div class="bg-gray-50 p-4 rounded-lg">
                    <h4 class="font-medium text-gray-700 mb-2">Protein</h4>
                    <p class="text-lg font-semibold text-[#F7B32B]">${nutritionData.protein}</p>
                </div>
                <div class="bg-gray-50 p-4 rounded-lg">
                    <h4 class="font-medium text-gray-700 mb-2">Carbohydrates</h4>
                    <p class="text-lg font-semibold text-[#F7B32B]">${nutritionData.carbs}</p>
                </div>
                <div class="bg-gray-50 p-4 rounded-lg">
                    <h4 class="font-medium text-gray-700 mb-2">Fat</h4>
                    <p class="text-lg font-semibold text-[#F7B32B]">${nutritionData.fat}</p>
                </div>
            </div>
            <div class="bg-gray-50 p-4 rounded-lg">
                <h4 class="font-medium text-gray-700 mb-2">Allergens</h4>
                <p class="text-gray-600">${nutritionData.allergens}</p>
            </div>
            <div class="bg-gray-50 p-4 rounded-lg">
                <h4 class="font-medium text-gray-700 mb-2">Ingredients</h4>
                <p class="text-gray-600">${nutritionData.ingredients}</p>
            </div>
        `;
        
        nutritionModal.classList.remove('hidden');
    }

    // Add item to order
    function addToOrder(itemId) {
        let item = null;
        for (const category in menuData) {
            item = menuData[category].find(menuItem => menuItem.id === itemId);
            if (item) break;
        }
        
        if (!item) {
            Swal.fire({
                icon: 'error',
                title: 'Item Not Found',
                text: 'Could not add item to order.',
                background: 'white',
                color: '#333'
            });
            return;
        }
        
        const existingItemIndex = orderItems.findIndex(orderItem => orderItem.id === itemId);
        
        if (existingItemIndex !== -1) {
            orderItems[existingItemIndex].quantity++;
        } else {
            orderItems.push({
                id: item.id,
                name: item.name,
                price: item.price,
                quantity: 1
            });
        }
        
        updateOrderDisplay();
        updateBill();
        updateItemCount();
        
        Swal.fire({
            icon: 'success',
            title: 'Item Added',
            text: `${item.name} has been added to your order.`,
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 1500,
            timerProgressBar: true,
            background: 'white',
            color: '#333'
        });
    }

    // Update item count badge
    function updateItemCount() {
        const totalItems = orderItems.reduce((sum, item) => sum + item.quantity, 0);
        itemCountElement.textContent = totalItems;
    }

    // Update order display
    function updateOrderDisplay() {
        orderItemsContainer.innerHTML = '';
        
        if (orderItems.length === 0) {
            orderItemsContainer.innerHTML = `
                <div class="text-center py-8">
                    <i data-lucide="shopping-bag" class="w-12 h-12 mx-auto text-gray-300 mb-3"></i>
                    <p class="text-gray-500">No items added yet</p>
                    <p class="text-gray-400 text-sm mt-1">Add items from the menu</p>
                </div>
            `;
            lucide.createIcons();
            return;
        }
        
        orderItems.forEach((item, index) => {
            const orderItemElement = document.createElement('div');
            orderItemElement.className = 'flex justify-between items-center py-3 border-b border-gray-200';
            orderItemElement.innerHTML = `
                <div class="flex-1">
                    <div class="font-medium text-gray-800 text-sm">${item.name}</div>
                    <div class="text-xs text-gray-500 mt-1">₱${item.price.toLocaleString()} x ${item.quantity}</div>
                </div>
                <div class="flex items-center gap-2">
                    <span class="font-bold text-gray-800">₱${(item.price * item.quantity).toLocaleString()}</span>
                    <div class="flex items-center bg-gray-100 rounded-lg">
                        <button class="decrease-quantity text-gray-600 hover:text-red-600 p-1" data-index="${index}">
                            <i data-lucide="minus" class="w-3 h-3"></i>
                        </button>
                        <span class="text-xs font-medium px-2">${item.quantity}</span>
                        <button class="increase-quantity text-gray-600 hover:text-green-600 p-1" data-index="${index}">
                            <i data-lucide="plus" class="w-3 h-3"></i>
                        </button>
                    </div>
                    <button class="remove-item text-gray-500 hover:text-red-500 p-1" data-index="${index}">
                        <i data-lucide="trash-2" class="w-4 h-4"></i>
                    </button>
                </div>
            `;
            orderItemsContainer.appendChild(orderItemElement);
        });
        
        lucide.createIcons();
        
        document.querySelectorAll('.decrease-quantity').forEach(button => {
            button.addEventListener('click', (e) => {
                const index = parseInt(e.currentTarget.dataset.index);
                decreaseQuantity(index);
            });
        });
        
        document.querySelectorAll('.increase-quantity').forEach(button => {
            button.addEventListener('click', (e) => {
                const index = parseInt(e.currentTarget.dataset.index);
                increaseQuantity(index);
            });
        });
        
        document.querySelectorAll('.remove-item').forEach(button => {
            button.addEventListener('click', (e) => {
                const index = parseInt(e.currentTarget.dataset.index);
                removeItem(index);
            });
        });
    }

    // Decrease item quantity
    function decreaseQuantity(index) {
        if (orderItems[index].quantity > 1) {
            orderItems[index].quantity--;
        } else {
            orderItems.splice(index, 1);
        }
        updateOrderDisplay();
        updateBill();
        updateItemCount();
    }

    // Increase item quantity
    function increaseQuantity(index) {
        orderItems[index].quantity++;
        updateOrderDisplay();
        updateBill();
        updateItemCount();
    }

    // Remove item from order
    function removeItem(index) {
        orderItems.splice(index, 1);
        updateOrderDisplay();
        updateBill();
        updateItemCount();
    }

    // Update bill calculation
    function updateBill() {
        const subtotal = orderItems.reduce((sum, item) => sum + (item.price * item.quantity), 0);
        const serviceCharge = subtotal * 0.02;
        const vat = (subtotal + serviceCharge) * 0.12;
        const total = subtotal + serviceCharge + vat;
        
        subtotalElement.textContent = `₱${subtotal.toLocaleString(undefined, { minimumFractionDigits: 2 })}`;
        serviceChargeElement.textContent = `₱${serviceCharge.toLocaleString(undefined, { minimumFractionDigits: 2 })}`;
        vatElement.textContent = `₱${vat.toLocaleString(undefined, { minimumFractionDigits: 2 })}`;
        totalBillElement.textContent = `₱${total.toLocaleString(undefined, { minimumFractionDigits: 2 })}`;
        
        // Recalculate change when bill updates
        calculateChange();
    }

    // Generate order code
    function generateOrderCode() {
        const timestamp = Date.now();
        const random = Math.floor(Math.random() * 1000);
        return `ORD-${timestamp}-${random}`;
    }

    // Submit order to API
    async function submitOrder() {
        if (!selectedTableDetails) {
            Swal.fire({
                icon: 'warning',
                title: 'No Table Selected',
                text: 'Please select a table before submitting the order.',
                background: 'white',
                color: '#333'
            });
            return null;
        }

        const totalBillText = totalBillElement.textContent.replace('₱', '').replace(/,/g, '');
        const totalAmount = parseFloat(totalBillText) || 0;
        
        // Validate amount received for cash payments
        if (selectedPaymentMethod === 'cash') {
            const change = calculateChange();
            if (change < 0) {
                throw new Error('Insufficient payment. Please enter a sufficient amount.');
            }
            
            if (amountReceived <= 0) {
                throw new Error('Please enter the amount received from customer.');
            }
        }

        const orderData = {
            order_code: generateOrderCode(),
            table_id: selectedTableDetails.id,
            customer_name: customerNameInput.value.trim() || 'Walk-in Customer',
            order_type: 'dine-in',
            total_amount: totalAmount,
            amount_received: amountReceived,
            change_amount: changeAmount,
            payment_method: selectedPaymentMethod || 'cash',
            notes: orderNotesInput.value.trim(),
            order_items: orderItems
        };

        try {
            const response = await fetch('sub-modules/submit_order.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(orderData)
            });

            const result = await response.json();
            
            if (result.status === 'success') {
                return result;
            } else {
                throw new Error(result.message || 'Failed to submit order');
            }
        } catch (error) {
            console.error('Order submission error:', error);
            throw error;
        }
    }

    // Show receipt
    function showReceipt(receiptHtml) {
        receiptContent.innerHTML = receiptHtml;
        receiptModal.classList.remove('hidden');
        document.body.style.overflow = 'hidden';
    }

    // Process checkout
    async function processCheckout() {
        if (orderItems.length === 0) {
            Swal.fire({
                icon: 'warning',
                title: 'Empty Order',
                text: 'Please add items to your order before checkout.',
                background: 'white',
                color: '#333'
            });
            return;
        }
        
        if (!selectedTableDetails) {
            Swal.fire({
                icon: 'warning',
                title: 'No Table Selected',
                text: 'Please select a table before checkout.',
                background: 'white',
                color: '#333'
            });
            return;
        }
        
        if (!selectedPaymentMethod) {
            Swal.fire({
                icon: 'warning',
                title: 'No Payment Method',
                text: 'Please select a payment method before checkout.',
                background: 'white',
                color: '#333'
            });
            return;
        }
        
        const customerName = customerNameInput.value.trim() || 'Walk-in Customer';
        const total = totalBillElement.textContent;
        const amount = amountReceivedInput.value ? `₱${parseFloat(amountReceivedInput.value).toLocaleString(undefined, { minimumFractionDigits: 2 })}` : '₱0.00';
        const change = changeAmountElement.textContent;
        
        // Validate cash payment
        if (selectedPaymentMethod === 'cash') {
            if (!amountReceivedInput.value || parseFloat(amountReceivedInput.value) <= 0) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Amount Required',
                    text: 'Please enter the amount received from customer for cash payment.',
                    background: 'white',
                    color: '#333'
                });
                amountReceivedInput.focus();
                return;
            }
            
            if (changeAmount < 0) {
                Swal.fire({
                    icon: 'error',
                    title: 'Insufficient Payment',
                    text: `Customer paid ${amount} but total is ${total}. Please collect ₱${(-changeAmount).toLocaleString(undefined, { minimumFractionDigits: 2 })} more.`,
                    background: 'white',
                    color: '#333'
                });
                return;
            }
        }

        Swal.fire({
            title: 'Confirm Payment',
            html: `
                <div class="text-left space-y-3">
                    <p><strong>Customer:</strong> ${customerName}</p>
                    <p><strong>Table:</strong> ${selectedTableText.textContent}</p>
                    <p><strong>Payment Method:</strong> ${selectedPaymentMethod.toUpperCase()}</p>
                    <p><strong>Total Amount:</strong> ${total}</p>
                    ${selectedPaymentMethod === 'cash' ? `
                        <p><strong>Amount Received:</strong> ${amount}</p>
                        <p><strong>Change:</strong> ${change}</p>
                    ` : ''}
                </div>
            `,
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Confirm Payment',
            background: 'white',
            color: '#333',
            cancelButtonText: 'Cancel'
        }).then(async (result) => {
            if (result.isConfirmed) {
                Swal.fire({
                    title: 'Processing Order...',
                    text: 'Please wait while we process your order.',
                    allowOutsideClick: false,
                    allowEscapeKey: false,
                    showConfirmButton: false,
                    willOpen: () => {
                        Swal.showLoading();
                    }
                });
                
                try {
                    const result = await submitOrder();
                    
                    if (result) {
                        // AUTO-DOWNLOAD RECEIPT HERE
                        const downloadSuccess = autoDownloadReceipt(result);
                        
                        Swal.fire({
                            icon: 'success',
                            title: 'Order Submitted Successfully!',
                            html: `
                                <div class="text-left space-y-3">
                                    <p><strong>Order #:</strong> ${result.order_code}</p>
                                    <p><strong>Table:</strong> ${result.table_name}</p>
                                    <p><strong>Total:</strong> ₱${parseFloat(result.total_amount).toLocaleString()}</p>
                                    ${selectedPaymentMethod === 'cash' ? `
                                        <p><strong>Amount Received:</strong> ₱${parseFloat(result.amount_received).toLocaleString()}</p>
                                        <p><strong>Change:</strong> ₱${parseFloat(result.change_amount).toLocaleString()}</p>
                                    ` : ''}
                                    <p class="text-green-600 font-medium">✓ Receipt has been automatically downloaded</p>
                                    ${downloadSuccess ? '' : '<p class="text-yellow-600">Note: If receipt didn\'t download, click "View Receipt" button</p>'}
                                </div>
                            `,
                            background: 'white',
                            color: '#333',
                            confirmButtonText: 'View Receipt',
                            showCancelButton: true,
                            cancelButtonText: 'Close'
                        }).then((receiptResult) => {
                            if (receiptResult.isConfirmed) {
                                // Show receipt
                                showReceipt(result.receipt_html);
                            }
                            
                            // Reset order
                            clearOrder();
                        });
                    }
                } catch (error) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Order Failed',
                        text: error.message || 'Failed to process order. Please try again.',
                        background: 'white',
                        color: '#333'
                    });
                }
            }
        });
    }

    // Clear order
    function clearOrder() {
        Swal.fire({
            title: 'Clear Order?',
            text: 'This will remove all items from the current order.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes, Clear Order',
            background: 'white',
            color: '#333',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                orderItems = [];
                amountReceivedInput.value = '';
                amountReceived = 0;
                changeAmount = 0;
                selectedPaymentMethod = null;
                
                // Reset payment method buttons
                mopButtons.forEach(btn => {
                    btn.classList.remove('border-[#F7B32B]', 'bg-yellow-50');
                });
                
                updateOrderDisplay();
                updateBill();
                updateItemCount();
                calculateChange();
                
                Swal.fire({
                    icon: 'success',
                    title: 'Order Cleared',
                    text: 'The order has been cleared successfully.',
                    toast: true,
                    position: 'top-end',
                    showConfirmButton: false,
                    timer: 1500,
                    background: 'white',
                    color: '#333'
                });
            }
        });
    }

    // Initialize the POS system when the page loads
    document.addEventListener('DOMContentLoaded', init);
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