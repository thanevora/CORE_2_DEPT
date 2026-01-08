<?php
session_start();
include("../main_connection.php");

// Database connection
$db_name = "rest_m3_menu";
if (!isset($connections[$db_name])) {
    die("❌ Connection not found for $db_name");
}
$conn = $connections[$db_name];

// Fetch approved menus from database
$approved_menus = [];
try {
    $sql = "SELECT * FROM menu WHERE status = 'Approved' OR status = 'pending' OR status = 'active' OR status = 'inactive' ORDER BY created_at DESC";
    $result = $conn->query($sql);
    
    if ($result) {
        $approved_menus = $result->fetch_all(MYSQLI_ASSOC);
    } else {
        error_log("Database error: " . $conn->error);
        $approved_menus = [];
    }
} catch (Exception $e) {
    error_log("Database error: " . $e->getMessage());
    $approved_menus = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Luxury Grand Hotel - Menu Management</title>
    <?php include '../header.php'; ?>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
    <style>
        .menu-card:hover {
            transform: translateY(-5px);
            transition: transform 0.3s ease;
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
        .status-active {
            background-color: #10B981;
            color: white;
        }
        .status-inactive {
            background-color: #EF4444;
            color: white;
        }
        .status-pending {
            background-color: #F59E0B;
            color: white;
        }
        .status-approved {
            background-color: #6366F1;
            color: white;
        }
        .swal2-popup {
            background: white !important;
            color: #333 !important;
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
            <!-- Stats Overview -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
                <!-- Total Approved -->
                <div class="stat-card bg-white text-black shadow-2xl p-5 rounded-xl transition-all duration-300 hover:shadow-2xl hover:scale-105 hover:bg-gray-50">
                    <div class="flex justify-between items-start">
                        <div>
                            <p class="text-sm font-medium text-[#001f54] hover:drop-shadow-md transition-all">Total Approved</p>
                            <h3 class="text-3xl font-bold mt-1"><?php echo count($approved_menus); ?></h3>
                            <p class="text-xs text-gray-500 mt-1">Approved menus</p>
                        </div>
                        <div class="p-3 rounded-lg bg-[#001f54] flex items-center justify-center transition-all duration-300 hover:bg-[#002b70]">
                            <i data-lucide="clipboard-check" class="w-6 h-6 text-[#F7B32B]"></i>
                        </div>
                    </div>
                </div>

                <!-- To Be Posted -->
                <div class="stat-card bg-white text-black shadow-2xl p-5 rounded-xl transition-all duration-300 hover:shadow-2xl hover:scale-105 hover:bg-gray-50">
                    <div class="flex justify-between items-start">
                        <div>
                            <p class="text-sm font-medium text-[#001f54] hover:drop-shadow-md transition-all">To Be Posted</p>
                            <h3 class="text-3xl font-bold mt-1"><?php echo count(array_filter($approved_menus, function($item) { return $item['status'] === 'pending'; })); ?></h3>
                            <p class="text-xs text-gray-500 mt-1">Pending activation</p>
                        </div>
                        <div class="p-3 rounded-lg bg-[#001f54] flex items-center justify-center transition-all duration-300 hover:bg-[#002b70]">
                            <i data-lucide="clock" class="w-6 h-6 text-[#F7B32B]"></i>
                        </div>
                    </div>
                </div>

                <!-- Active -->
                <div class="stat-card bg-white text-black shadow-2xl p-5 rounded-xl transition-all duration-300 hover:shadow-2xl hover:scale-105 hover:bg-gray-50">
                    <div class="flex justify-between items-start">
                        <div>
                            <p class="text-sm font-medium text-[#001f54] hover:drop-shadow-md transition-all">Active</p>
                            <h3 class="text-3xl font-bold mt-1"><?php echo count(array_filter($approved_menus, function($item) { return $item['status'] === 'active'; })); ?></h3>
                            <p class="text-xs text-gray-500 mt-1">Available in POS</p>
                        </div>
                        <div class="p-3 rounded-lg bg-[#001f54] flex items-center justify-center transition-all duration-300 hover:bg-[#002b70]">
                            <i data-lucide="check-circle" class="w-6 h-6 text-[#F7B32B]"></i>
                        </div>
                    </div>
                </div>

                <!-- Inactive -->
                <div class="stat-card bg-white text-black shadow-2xl p-5 rounded-xl transition-all duration-300 hover:shadow-2xl hover:scale-105 hover:bg-gray-50">
                    <div class="flex justify-between items-start">
                        <div>
                            <p class="text-sm font-medium text-[#001f54] hover:drop-shadow-md transition-all">Inactive</p>
                            <h3 class="text-3xl font-bold mt-1"><?php echo count(array_filter($approved_menus, function($item) { return $item['status'] === 'inactive'; })); ?></h3>
                            <p class="text-xs text-gray-500 mt-1">Not in POS</p>
                        </div>
                        <div class="p-3 rounded-lg bg-[#001f54] flex items-center justify-center transition-all duration-300 hover:bg-[#002b70]">
                            <i data-lucide="pause-circle" class="w-6 h-6 text-[#F7B32B]"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Action Bar -->
            <div class="bg-white rounded-lg shadow-md p-4 mb-6">
                <div class="flex flex-col md:flex-row justify-between items-start md:items-center space-y-4 md:space-y-0">
                    <div class="flex items-center space-x-4">
                        <div class="relative">
                            <input type="text" id="searchMenu" placeholder="Search menu items..." class="bg-white pl-10 pr-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-[#F7B32B] w-64">
                            <i data-lucide="search" class="w-4 h-4 absolute left-3 top-3 text-gray-400"></i>
                        </div>
                        <select id="categoryFilter" class="bg-white px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-[#F7B32B]">
                            <option value="all">All Categories</option>
                            <option value="appetizers">Appetizers</option>
                            <option value="mains">Main Courses</option>
                            <option value="sides">Sides</option>
                            <option value="desserts">Desserts</option>
                            <option value="drinks">Drinks</option>
                            <option value="specials">Specials</option>
                        </select>
                        <select id="statusFilter" class="bg-white px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-[#F7B32B]">
                            <option value="all">All Status</option>
                            <option value="Approved">Approved</option>
                            <option value="pending">To Be Posted</option>
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                    <button id="refreshDataBtn" class="bg-gray-200 text-gray-700 px-4 py-2 rounded-md font-medium transition duration-200 flex items-center hover:bg-gray-300">
                        <i data-lucide="refresh-cw" class="w-4 h-4 mr-2"></i>
                        Refresh Data
                    </button>
                </div>
            </div>

            <!-- Menu Items Grid -->
            <div class="bg-white rounded-lg shadow-md p-4">
                <div id="menuItems" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
    <?php if (empty($approved_menus)): ?>
        <div class="col-span-full text-center py-12">
            <i data-lucide="utensils" class="w-16 h-16 mx-auto text-gray-300 mb-4"></i>
            <p class="text-gray-500 text-lg mb-2">No approved menu items found</p>
            <p class="text-gray-400 text-sm">Approved menus will appear here for activation</p>
        </div>
    <?php else: ?>
        <?php foreach ($approved_menus as $item): ?>
        <?php 
        $itemId = $item['menu_id'] ?? $item['id'];
        $status = $item['status'];
        $price = isset($item['price']) ? number_format($item['price'], 2) : '0.00';
        
        // Determine the image source
        $imageSrc = '';
        $hasImage = false;
        
        // Check if image_url exists and is not empty
        if (!empty($item['image_url'])) {
            $hasImage = true;
            // Use the correct path structure like in your example
            $imageSrc = '../M3/Menu_uploaded/menu_images/original/' . htmlspecialchars($item['image_url']);
        }
        // Check if 'image' field exists as fallback
        elseif (!empty($item['image'])) {
            $hasImage = true;
            $imageSrc = htmlspecialchars($item['image']);
        }
        ?>
        
        <div class="menu-card glassy-card rounded-lg shadow-md overflow-hidden border border-gray-200" data-id="<?php echo $itemId; ?>">
            <div class="h-40 bg-gray-200 overflow-hidden relative">
                <?php if ($hasImage): ?>
                    <img src="<?php echo $imageSrc; ?>" 
                         alt="<?php echo htmlspecialchars($item['name']); ?>" 
                         class="w-full h-full object-cover transition-transform duration-500 hover:scale-110"
                         onerror="handleImageError(this, '<?php echo htmlspecialchars($item['name']); ?>')">
                <?php else: ?>
                    <div class="w-full h-full flex flex-col items-center justify-center bg-gradient-to-br from-gray-100 to-gray-200">
                        <i data-lucide="utensils" class="w-12 h-12 text-gray-400 mb-2"></i>
                        <p class="text-xs text-gray-500 font-medium">No image</p>
                    </div>
                <?php endif; ?>
                
                <div class="absolute top-2 right-2">
                    <span class="status-<?php echo strtolower($status); ?> text-xs font-semibold px-2 py-1 rounded-full">
                        <?php 
                        switch($status) {
                            case 'active': echo 'Active'; break;
                            case 'inactive': echo 'Inactive'; break;
                            case 'pending': echo 'To Be Posted'; break;
                            case 'Approved': echo 'Approved'; break;
                            default: echo $status;
                        }
                        ?>
                    </span>
                </div>
            </div>
            <div class="p-4">
                <div class="flex justify-between items-start mb-2">
                    <span class="text-xs font-semibold bg-gray-100 text-gray-700 px-2 py-1 rounded"><?php echo htmlspecialchars($itemId); ?></span>
                    <span class="text-lg font-bold text-[#F7B32B]">₱<?php echo $price; ?></span>
                </div>
                <h3 class="font-medium text-gray-800 mb-2"><?php echo htmlspecialchars($item['name']); ?></h3>
                <p class="text-sm text-gray-600 mb-3 line-clamp-2"><?php echo htmlspecialchars($item['description'] ?? 'No description available'); ?></p>
                <div class="flex justify-between text-xs text-gray-500 mb-4">
                    <span class="flex items-center">
                        <i data-lucide="clock" class="w-3 h-3 mr-1"></i>
                        <?php echo ($item['prep_time'] ?? 0); ?> min
                    </span>
                    <span class="flex items-center">
                        <i data-lucide="flame" class="w-3 h-3 mr-1"></i>
                        <?php 
                        switch($item['spice_level'] ?? 0) {
                            case 0: echo 'None'; break;
                            case 1: echo 'Mild'; break;
                            case 2: echo 'Medium'; break;
                            case 3: echo 'Spicy'; break;
                            case 4: echo 'Very Spicy'; break;
                            default: echo 'Unknown';
                        }
                        ?>
                    </span>
                </div>
                <div class="flex justify-between space-x-2">
                    <?php if ($status === 'pending' || $status === 'Approved' || $status === 'inactive'): ?>
                        <button class="activate-btn flex-1 primary-button py-2 rounded-md text-sm font-medium transition duration-200 flex items-center justify-center" data-id="<?php echo $itemId; ?>">
                            <i data-lucide="check-circle" class="w-4 h-4 mr-1"></i>
                            Activate
                        </button>
                    <?php elseif ($status === 'active'): ?>
                        <button class="deactivate-btn flex-1 bg-gray-200 text-gray-700 py-2 rounded-md text-sm font-medium transition duration-200 flex items-center justify-center" data-id="<?php echo $itemId; ?>">
                            <i data-lucide="pause-circle" class="w-4 h-4 mr-1"></i>
                            Deactivate
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<script>
// Function to handle image loading errors
function handleImageError(img, itemName) {
    // Create fallback with item name
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
    img.onerror = null; // Prevent infinite loop
}

// Initialize Lucide icons after page load
document.addEventListener('DOMContentLoaded', function() {
    if (window.lucide) {
        lucide.createIcons();
    }
});
</script>
            </div>
        </main>
    </div>
</div>

<script>
    // Initialize Lucide icons when DOM is loaded
    document.addEventListener('DOMContentLoaded', function() {
        lucide.createIcons();
        
        // Set current date
        const currentDateElement = document.getElementById('currentDate');
        if (currentDateElement) {
            currentDateElement.textContent = new Date().toLocaleDateString('en-US', {
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            });
        }
        
        console.log('DOM loaded - initializing event listeners');
        initializeEventListeners();
    });

    // Simple event delegation that works reliably
    function initializeEventListeners() {
        console.log('Initializing event listeners...');
        
        // Refresh button
        const refreshBtn = document.getElementById('refreshDataBtn');
        if (refreshBtn) {
            refreshBtn.addEventListener('click', function() {
                window.location.reload();
            });
        }
        
        // Use event delegation for activate/deactivate buttons
        document.addEventListener('click', function(e) {
            // Check if activate button was clicked
            if (e.target.closest('.activate-btn')) {
                const button = e.target.closest('.activate-btn');
                const itemId = button.getAttribute('data-id');
                console.log('Activate button clicked for ID:', itemId);
                e.preventDefault();
                e.stopPropagation();
                activateMenuItem(itemId);
            }
            
            // Check if deactivate button was clicked
            if (e.target.closest('.deactivate-btn')) {
                const button = e.target.closest('.deactivate-btn');
                const itemId = button.getAttribute('data-id');
                console.log('Deactivate button clicked for ID:', itemId);
                e.preventDefault();
                e.stopPropagation();
                deactivateMenuItem(itemId);
            }
        });
        
        console.log('Event listeners initialized');
    }

    // Simple API call function using FormData
    async function callAPI(action, menu_id) {
        try {
            console.log(`Calling API: ${action} for menu ID: ${menu_id}`);
            
            const formData = new FormData();
            formData.append('action', action);
            formData.append('menu_id', menu_id);

            const response = await fetch('sub-modules/menu_api.php', {
                method: 'POST',
                body: formData
            });

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const result = await response.json();
            console.log('API response:', result);
            return result;
            
        } catch (error) {
            console.error('API call failed:', error);
            throw error;
        }
    }

    // Activate menu item
    async function activateMenuItem(itemId) {
        try {
            console.log('Starting activation for menu item:', itemId);
            
            const result = await Swal.fire({
                title: 'Activate Menu Item?',
                text: 'This will make the menu item available in the POS system.',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Yes, Activate',
                cancelButtonText: 'Cancel',
                background: 'white',
                color: '#333',
                confirmButtonColor: '#F7B32B',
                cancelButtonColor: '#6B7280'
            });

            if (!result.isConfirmed) {
                console.log('Activation cancelled by user');
                return;
            }

            const apiResult = await callAPI('activate_menu', itemId);
            
            if (apiResult && apiResult.success) {
                await Swal.fire({
                    icon: 'success',
                    title: 'Menu Item Activated',
                    text: apiResult.message || 'Menu is now active and available in POS.',
                    background: 'white',
                    color: '#333',
                    confirmButtonColor: '#F7B32B'
                });
                window.location.reload();
            } else {
                throw new Error(apiResult?.message || 'Unknown error occurred during activation');
            }
        } catch (error) {
            console.error('Error in activateMenuItem:', error);
            Swal.fire({
                icon: 'error',
                title: 'Activation Failed',
                text: error.message || 'Failed to activate menu item. Please try again.',
                background: 'white',
                color: '#333',
                confirmButtonColor: '#F7B32B'
            });
        }
    }

    // Deactivate menu item
    async function deactivateMenuItem(itemId) {
        try {
            console.log('Starting deactivation for menu item:', itemId);
            
            const result = await Swal.fire({
                title: 'Deactivate Menu Item?',
                text: 'This will remove the menu item from the POS system.',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Yes, Deactivate',
                cancelButtonText: 'Cancel',
                background: 'white',
                color: '#333',
                confirmButtonColor: '#F7B32B',
                cancelButtonColor: '#6B7280'
            });

            if (!result.isConfirmed) {
                console.log('Deactivation cancelled by user');
                return;
            }

            const apiResult = await callAPI('deactivate_menu', itemId);
            
            if (apiResult && apiResult.success) {
                await Swal.fire({
                    icon: 'success',
                    title: 'Menu Item Deactivated',
                    text: apiResult.message || 'Menu is now inactive and not available in POS.',
                    background: 'white',
                    color: '#333',
                    confirmButtonColor: '#F7B32B'
                });
                window.location.reload();
            } else {
                throw new Error(apiResult?.message || 'Unknown error occurred during deactivation');
            }
        } catch (error) {
            console.error('Error in deactivateMenuItem:', error);
            Swal.fire({
                icon: 'error',
                title: 'Deactivation Failed',
                text: error.message || 'Failed to deactivate menu item. Please try again.',
                background: 'white',
                color: '#333',
                confirmButtonColor: '#F7B32B'
            });
        }
    }

    // Log that script is loaded
    console.log('Menu management JavaScript loaded');
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