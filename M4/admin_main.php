<?php

session_start();

?>
<!DOCTYPE html>
<html lang="en">
        <?php include '../header.php'; ?>

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>POS Management | Soliera Restaurant</title>

    <style>
        .order-card {
            transition: all 0.2s ease;
        }
        
        .order-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1);
        }
        
        .status-badge.preparing {
            background-color: #fef3c7;
            color: #d97706;
        }
        
        .status-badge.ready {
            background-color: #dcfce7;
            color: #16a34a;
        }
        
        .status-badge.served {
            background-color: #e0f2fe;
            color: #0284c7;
        }
        
        .status-badge.paid {
            background-color: #ede9fe;
            color: #7c3aed;
        }
        
        .tab-active {
            border-bottom: 3px solid #3b82f6;
            color: #3b82f6;
            font-weight: 600;
        }
        
        .drawer-content {
            transition: margin-left 0.3s ease;
        }

        /* New enhancements */
        .stats-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }

        .progress-bar {
            transition: width 0.6s ease;
        }

        #new-order-card {
            transition: all 0.3s ease;
        }

        #new-order-card:hover {
            border-color: #3b82f6;
            background-color: #f8fafc;
        }

        .order-table tr {
            transition: background-color 0.2s ease;
        }

        .order-table tr:hover {
            background-color: #f8fafc;
        }

        .modal-backdrop {
            background-color: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(2px);
        }
    </style>
</head>
<body class="bg-base-100 min-h-screen bg-white">

  <div class="flex h-screen">
    <!-- Sidebar -->
    <div class="bg-[#1A2C5B] border-r border-blue-600 pt-5 pb-4 flex flex-col w-64 transition-all duration-300 ease-in-out shadow-xl relative overflow-hidden h-screen" id="sidebar">
        <!-- Sidebar content -->
        <?php include '../sidebarr.php'; ?>
    </div>

    <!-- Content Area -->
    <div class="flex flex-col flex-1 overflow-auto">
        <!-- Navbar -->
        <?php include '../navbar.php'; ?>
       
        <!-- Main Content -->
        <main class="flex-1 overflow-auto p-4 md:p-6">
            <!-- Stats Cards -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                <div class="stats-card card bg-white border border-gray-200 rounded-lg p-4 transition-all hover:shadow-sm">
                    <div class="flex justify-between items-center">
                        <div>
                            <p class="text-sm text-gray-500">Today's Orders</p>
                            <h3 class="text-2xl font-bold mt-1" id="today-orders">0</h3>
                        </div>
                        <div class="p-2 rounded-lg bg-blue-100 text-blue-600">
                            <i data-lucide="shopping-basket" class="w-5 h-5"></i>
                        </div>
                    </div>
                    <div class="mt-3">
                        <div class="h-2 w-full bg-gray-100 rounded-full overflow-hidden">
                            <div class="h-full bg-blue-500 rounded-full progress-bar" id="orders-progress" style="width: 0%"></div>
                        </div>
                        <p class="text-xs text-gray-500 mt-1" id="orders-comparison">Loading data...</p>
                    </div>
                </div>
                
                <div class="stats-card card bg-white border border-gray-200 rounded-lg p-4 transition-all hover:shadow-sm">
                    <div class="flex justify-between items-center">
                        <div>
                            <p class="text-sm text-gray-500">Today's Revenue</p>
                            <h3 class="text-2xl font-bold mt-1" id="today-revenue">₱ 0.00</h3>
                        </div>
                        <div class="p-2 rounded-lg bg-green-100 text-green-600">
                            <i data-lucide="dollar-sign" class="w-5 h-5"></i>
                        </div>
                    </div>
                    <div class="mt-3">
                        <div class="h-2 w-full bg-gray-100 rounded-full overflow-hidden">
                            <div class="h-full bg-green-500 rounded-full progress-bar" id="revenue-progress" style="width: 0%"></div>
                        </div>
                        <p class="text-xs text-gray-500 mt-1" id="revenue-comparison">Loading data...</p>
                    </div>
                </div>
                
                <div class="stats-card card bg-white border border-gray-200 rounded-lg p-4 transition-all hover:shadow-sm">
                    <div class="flex justify-between items-center">
                        <div>
                            <p class="text-sm text-gray-500">Active Tables</p>
                            <h3 class="text-2xl font-bold mt-1" id="active-tables">0/0</h3>
                        </div>
                        <div class="p-2 rounded-lg bg-orange-100 text-orange-600">
                            <i data-lucide="table" class="w-5 h-5"></i>
                        </div>
                    </div>
                    <div class="mt-3">
                        <div class="h-2 w-full bg-gray-100 rounded-full overflow-hidden">
                            <div class="h-full bg-orange-500 rounded-full progress-bar" id="tables-progress" style="width: 0%"></div>
                        </div>
                        <p class="text-xs text-gray-500 mt-1" id="tables-available">Loading data...</p>
                    </div>
                </div>
                
                <div class="stats-card card bg-white border border-gray-200 rounded-lg p-4 transition-all hover:shadow-sm">
                    <div class="flex justify-between items-center">
                        <div>
                            <p class="text-sm text-gray-500">Avg. Order Value</p>
                            <h3 class="text-2xl font-bold mt-1" id="avg-order-value">₱ 0.00</h3>
                        </div>
                        <div class="p-2 rounded-lg bg-purple-100 text-purple-600">
                            <i data-lucide="trending-up" class="w-5 h-5"></i>
                        </div>
                    </div>
                    <div class="mt-3">
                        <div class="h-2 w-full bg-gray-100 rounded-full overflow-hidden">
                            <div class="h-full bg-purple-500 rounded-full progress-bar" id="avg-order-progress" style="width: 0%"></div>
                        </div>
                        <p class="text-xs text-gray-500 mt-1" id="order-value-comparison">Loading data...</p>
                    </div>
                </div>
            </div>
            
            <!-- Order Management -->
            <div class="bg-white rounded-xl shadow-sm p-6 mb-6">
                <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4">
                    <h2 class="text-xl font-bold text-gray-800">Order Management</h2>
                    <div class="flex flex-wrap gap-3 w-full md:w-auto">
                        <!-- Order Status Tabs -->
                        <div class="tabs tabs-boxed bg-gray-100 p-1">
                            <a class="tab tab-active" data-status="all">All Orders</a> 
                            <a class="tab" data-status="pending">Pending</a> 
                            <a class="tab" data-status="preparing">Preparing</a> 
                            <a class="tab" data-status="ready">Ready</a>
                            <a class="tab" data-status="completed">Completed</a>
                        </div>
                        
                        <!-- Filter Dropdown -->
                        <div class="dropdown dropdown-end">
                            <button class="btn btn-outline border-gray-300 text-gray-700 hover:bg-gray-100 px-4 py-2 rounded-xl flex items-center gap-2 text-sm">
                                <i data-lucide="filter" class="w-4 h-4"></i> 
                                <span>Filter</span>
                            </button>
                            <ul class="dropdown-content menu p-2 shadow bg-white rounded-box w-52 mt-2 border border-gray-200">
                                <li><a data-filter="today">Today</a></li>
                                <li><a data-filter="week">This Week</a></li>
                                <li><a data-filter="month">This Month</a></li>
                                <li><a data-filter="custom">Custom Range</a></li>
                            </ul>
                        </div>
                    </div>
                </div>
                
                <!-- Orders Grid -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4" id="orders-container">
                    <!-- Dynamic order cards will be inserted here -->
                    
                    <!-- New Order Card -->
                    <div id="new-order-card" class="order-card bg-white border-2 border-dashed border-gray-300 rounded-lg p-4 cursor-pointer hover:border-blue-400 flex items-center justify-center">
                        <div class="text-center">
                            <i data-lucide="plus" class="w-8 h-8 mx-auto text-gray-400 mb-2"></i>
                            <p class="text-gray-600 font-medium">Create New Order</p>
                        </div>
                    </div>
                </div>
                
                <!-- Pagination -->
                <div class="mt-6 flex justify-center">
                    <div class="join" id="pagination-controls">
                        <!-- Dynamic pagination will be inserted here -->
                    </div>
                </div>
            </div>
            
            <!-- Recent Transactions -->
            <div class="bg-white rounded-xl shadow-sm p-6">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-xl font-bold text-gray-800">Recent Transactions</h2>
                    <button class="btn btn-sm btn-outline" id="print-report-btn">
                        <i data-lucide="printer" class="w-4 h-4 mr-1"></i> Print Report
                    </button>
                </div>
                
                <div class="overflow-x-auto">
                    <table class="table order-table">
                        <thead>
                            <tr>
                                <th>Order ID</th>
                                <th>Customer</th>
                                <th>Type</th>
                                <th>Items</th>
                                <th>Total</th>
                                <th>Payment</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="transactions-table-body">
                            <!-- Dynamic transaction rows will be inserted here -->
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
</div>

<!-- Order Details Modal -->
<input type="checkbox" id="order-details-modal" class="modal-toggle" />
<div class="modal modal-backdrop">
  <div class="modal-box max-w-4xl">
    <h3 class="font-bold text-lg mb-4" id="modal-order-title">Order Details</h3>
    
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <!-- Order Items -->
        <div class="md:col-span-2">
            <div class="bg-gray-50 p-4 rounded-lg mb-4">
                <div class="flex justify-between items-center mb-2">
                    <h4 class="font-medium" id="order-items-count">Items (0)</h4>
                    <span class="badge badge-sm" id="order-status-badge"></span>
                </div>
                
                <div class="space-y-3" id="order-items-list">
                    <!-- Dynamic order items will be inserted here -->
                </div>
                
                <div class="mt-4 pt-3 border-t border-gray-200">
                    <div class="flex justify-between">
                        <span class="text-gray-600">Subtotal</span>
                        <span class="font-medium" id="order-subtotal">₱ 0.00</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Service Charge (10%)</span>
                        <span class="font-medium" id="order-service-charge">₱ 0.00</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Tax (12%)</span>
                        <span class="font-medium" id="order-tax">₱ 0.00</span>
                    </div>
                    <div class="flex justify-between font-bold mt-2">
                        <span>Total</span>
                        <span class="text-blue-600" id="order-total">₱ 0.00</span>
                    </div>
                </div>
            </div>
            
            <div class="bg-gray-50 p-4 rounded-lg">
                <h4 class="font-medium mb-3">Order Notes</h4>
                <p class="text-sm text-gray-700" id="order-notes">No special requests</p>
            </div>
        </div>
        
        <!-- Order Actions -->
        <div>
            <div class="bg-gray-50 p-4 rounded-lg mb-4">
                <h4 class="font-medium mb-3">Customer Information</h4>
                <div class="space-y-2" id="customer-info">
                    <!-- Dynamic customer info will be inserted here -->
                </div>
            </div>
            
            <div class="bg-gray-50 p-4 rounded-lg mb-4">
                <h4 class="font-medium mb-3">Order Status</h4>
                <div class="steps steps-vertical" id="order-status-steps">
                    <!-- Dynamic status steps will be inserted here -->
                </div>
            </div>
            
            <div class="space-y-2" id="order-actions">
                <!-- Dynamic action buttons will be inserted here -->
            </div>
        </div>
    </div>
    
    <div class="modal-action">
      <label for="order-details-modal" class="btn">Close</label>
    </div>
  </div>
</div>

<!-- New Order Modal -->
<input type="checkbox" id="new-order-modal" class="modal-toggle" />
<div class="modal modal-backdrop">
  <div class="modal-box max-w-3xl">
    <h3 class="font-bold text-lg mb-4">Create New Order</h3>
    
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <!-- Customer Selection -->
        <div class="md:col-span-1">
            <div class="form-control mb-4">
                <label class="label">
                    <span class="label-text">Order Type</span>
                </label>
                <select class="select select-bordered w-full" id="order-type">
                    <option value="dine-in">Dine-in</option>
                    <option value="takeout">Takeout</option>
                    <option value="delivery">Delivery</option>
                </select>
            </div>
            
            <div class="form-control mb-4" id="table-selection">
                <label class="label">
                    <span class="label-text">Select Table</span>
                </label>
                <select class="select select-bordered w-full" id="table-number">
                    <!-- Tables will be loaded dynamically -->
                </select>
            </div>
            
            <div class="form-control mb-4" id="customer-selection">
                <label class="label">
                    <span class="label-text">Customer</span>
                </label>
                <select class="select select-bordered w-full" id="customer-id">
                    <!-- Customers will be loaded dynamically -->
                </select>
            </div>
            
            <div class="form-control">
                <label class="label">
                    <span class="label-text">Special Requests</span>
                </label>
                <textarea class="textarea textarea-bordered" placeholder="Any special requests?" id="order-requests"></textarea>
            </div>
        </div>
        
        <!-- Menu Selection -->
        <div class="md:col-span-2">
            <div class="flex justify-between items-center mb-4">
                <h4 class="font-medium">Menu Items</h4>
                <div class="relative w-64">
                    <input type="text" placeholder="Search menu..." class="input input-bordered w-full pl-10" id="menu-search" />
                    <i data-lucide="search" class="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                </div>
            </div>
            
            <div class="bg-gray-50 p-4 rounded-lg max-h-96 overflow-y-auto">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4" id="menu-items-container">
                    <!-- Menu items will be loaded dynamically -->
                </div>
            </div>
            
            <div class="mt-4 bg-white border border-gray-200 rounded-lg p-4">
                <h4 class="font-medium mb-3">Current Order</h4>
                <div class="space-y-2 max-h-40 overflow-y-auto mb-3" id="current-order-items">
                    <!-- Selected items will appear here -->
                </div>
                <div class="flex justify-between items-center pt-3 border-t border-gray-200">
                    <span class="text-sm font-medium">Subtotal: <span id="current-order-total">₱ 0.00</span></span>
                    <button class="btn btn-primary btn-sm" id="confirm-order-btn">
                        <i data-lucide="check" class="w-4 h-4 mr-1"></i> Confirm Order
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <div class="modal-action">
      <label for="new-order-modal" class="btn">Cancel</label>
    </div>
  </div>
</div>

<script>
    // Initialize Lucide icons
    lucide.createIcons();
    
    // Tab switching functionality
    document.querySelectorAll('.tab').forEach(tab => {
        tab.addEventListener('click', function() {
            document.querySelectorAll('.tab').forEach(t => t.classList.remove('tab-active'));
            this.classList.add('tab-active');
            const status = this.dataset.status;
            // Here you would fetch orders filtered by status
            console.log('Filtering orders by status:', status);
        });
    });

    // Filter dropdown functionality
    document.querySelectorAll('[data-filter]').forEach(filter => {
        filter.addEventListener('click', function() {
            const filterType = this.dataset.filter;
            console.log('Applying filter:', filterType);
            // Here you would apply the date filter
        });
    });

    // New order card click handler
    document.getElementById('new-order-card').addEventListener('click', function() {
        document.getElementById('new-order-modal').checked = true;
        // Here you would load tables, customers, and menu items
    });

    // Print report button
    document.getElementById('print-report-btn').addEventListener('click', function() {
        // Here you would implement print functionality
        console.log('Printing report...');
    });

    // Example of dynamic content loading
    function loadStatsData() {
        // In a real app, you would fetch this from your backend
        setTimeout(() => {
            document.getElementById('today-orders').textContent = '24';
            document.getElementById('orders-progress').style.width = '65%';
            document.getElementById('orders-comparison').textContent = '+12% from yesterday';
            
            document.getElementById('today-revenue').textContent = '₱ 32,450';
            document.getElementById('revenue-progress').style.width = '78%';
            document.getElementById('revenue-comparison').textContent = '+18% from yesterday';
            
            document.getElementById('active-tables').textContent = '8/12';
            document.getElementById('tables-progress').style.width = '67%';
            document.getElementById('tables-available').textContent = '4 tables available';
            
            document.getElementById('avg-order-value').textContent = '₱ 1,352';
            document.getElementById('avg-order-progress').style.width = '82%';
            document.getElementById('order-value-comparison').textContent = '+5% from last week';
        }, 1000);
    }

    // Initialize the page
    document.addEventListener('DOMContentLoaded', function() {
        loadStatsData();
        
        // Here you would also:
        // 1. Load orders
        // 2. Set up event listeners
        // 3. Initialize any other needed functionality
    });
</script>

<script src="../JavaScript/sidebar.js"></script>
<script src="../JavaScript/soliera.js"></script>
</body>
</html>