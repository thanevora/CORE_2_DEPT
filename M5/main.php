<?php
session_start();
include("../main_connection.php");

$db_name = "rest_m6_kot";
$conn = $connections[$db_name] ?? die("❌ Connection not found for $db_name");
?>

<!DOCTYPE html>
<html lang="en">
        <?php include '../header.php'; ?>

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kitchen Display System | Soliera</title>
 
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
        
        .badge-new {
            background-color: #dbeafe;
            color: #1e40af;
        }
        
        .badge-preparing {
            background-color: #fef3c7;
            color: #92400e;
        }
        
        .badge-ready {
            background-color: #d1fae5;
            color: #065f46;
        }
        
        .badge-completed {
            background-color: #ecfdf5;
            color: #047857;
        }
        
        .badge-urgent {
            background-color: #fee2e2;
            color: #991b1b;
        }
        
        .modal-box {
            max-width: 90%;
            width: 800px;
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
        SUM(status = 'new') as new_orders,
        SUM(status = 'completed') as completed,
        SUM(status = 'voided') as voided,   
        SUM(status = 'delayed') as delayed_orders,
        SUM(status = 'pending') as pending,
        SUM(status = 'preparing') as preparing,
        SUM(status = 'cook') as cooking,
        SUM(status = 'serve') as ready_to_serve
        FROM kot_orders";
    
    $status_result = $conn->query($status_query);
    $status_counts = $status_result->fetch_assoc();
    
    $cards = [
      
        ['title' => 'Pending', 'count' => $status_counts['pending'] ?? 0, 'icon' => 'clock', 'filter' => 'pending', 'subtitle' => 'Not started'],
        ['title' => 'Preparing', 'count' => $status_counts['preparing'] ?? 0, 'icon' => 'chef-hat', 'filter' => 'preparing', 'subtitle' => 'Being prepared'],
        ['title' => 'Cooking', 'count' => $status_counts['cooking'] ?? 0, 'icon' => 'cooking-pot', 'filter' => 'cooking', 'subtitle' => 'On the stove'],
        ['title' => 'Ready to serve', 'count' => $status_counts['ready_to_serve'] ?? 0, 'icon' => 'tray-arrow-up', 'filter' => 'ready_to_serve', 'subtitle' => 'Waiting for pickup'],
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


            <!-- Recent Orders Section -->
<div class="mb-6">
    <h2 class="text-lg font-semibold text-[#001f54] mb-4">Orders</h2>
    <div class="grid grid-cols-1 lg:grid-cols-2 xl:grid-cols-3 gap-5" id="recent-orders">
        <?php
        $recent_orders_query = "SELECT * FROM kot_orders ORDER BY created_at DESC LIMIT 6";
        $recent_orders_result = $conn->query($recent_orders_query);
        
        if ($recent_orders_result->num_rows > 0) {
            while ($order = $recent_orders_result->fetch_assoc()) {
                $status_class = '';
                $status_icon = '';
                $status_label = ucfirst($order['status']);

                switch ($order['status']) {
                    case 'new':
                        $status_class = 'bg-[#001f54] text-[#F7B32B]';
                        $status_icon = 'inbox';
                        break;
                    case 'preparing':
                        $status_class = 'bg-[#001f54] text-[#F7B32B]';
                        $status_icon = 'chef-hat';
                        break;
                    case 'ready':
                        $status_class = 'bg-[#001f54] text-[#F7B32B]';
                        $status_icon = 'check-circle';
                        $status_label = "Ready to Serve";
                        break;
                    case 'urgent':
                        $status_class = 'bg-red-600 text-white';
                        $status_icon = 'alert-triangle';
                        break;
                }

                $order_time = date('h:i A', strtotime($order['created_at']));
        ?>
        <!-- Order Card -->
        <div class="order-card bg-white shadow-lg rounded-lg p-5 transition hover:shadow-xl">
            <div class="flex justify-between items-start mb-3">
                <div>
                    <h3 class="text-lg font-bold text-gray-900">Order #<?= $order['order_id'] ?></h3>
                    <div class="flex items-center gap-2 mt-1">
                        <span class="text-sm font-medium text-gray-500">
                            <?= $order['table_number'] ? 'Table ' . $order['table_number'] : 'Takeaway' ?>
                        </span>
                        <span class="inline-flex items-center text-xs font-semibold px-2 py-1 rounded <?= $status_class ?>">
                            <i data-lucide="<?= $status_icon ?>" class="w-3 h-3 mr-1"></i>
                            <?= $status_label ?>
                        </span>
                    </div>
                </div>
                <span class="text-sm font-medium text-gray-500"><?= $order_time ?></span>
            </div>
            
            <div class="space-y-3 mb-4">
                <div class="flex justify-between items-start p-3 bg-gray-50 rounded-lg">
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="font-medium"><?= $order['quantity'] ?>x <?= $order['item_name'] ?></span>
                            <span class="text-xs px-2 py-1 rounded bg-[#F7B32B] text-[#001f54] font-semibold">
                                <?= ucfirst($order['status']) ?>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="flex justify-end">
                <button class="px-4 py-2 rounded-md bg-[#001f54] text-[#F7B32B] text-sm font-semibold hover:opacity-90 transition"
                        onclick="showOrderDetails(<?= $order['kot_id'] ?>)">
                    View Details
                </button>
            </div>
        </div>
        <?php
            }
        } else {
            echo '<div class="col-span-full text-center py-12 text-gray-400">
                <i data-lucide="utensils-crossed" class="w-12 h-12 mx-auto mb-4"></i>
                <p class="text-lg font-medium">No recent orders</p>
            </div>';
        }
        ?>
    </div>
</div>

        </main>
    </div>
  </div>
<!-- Order Details Modal -->
<dialog id="orderDetailsModal" class="modal">
  <div class="modal-box max-w-2xl">
    <h3 class="text-xl font-bold mb-4">Order Details</h3>
    <div id="order-details-content" class="space-y-3">
      <!-- Filled dynamically with JS -->
    </div>
    <div class="modal-action">
      <button class="btn" onclick="document.getElementById('orderDetailsModal').close()">Close</button>
    </div>
  </div>
</dialog>

  <!-- Orders Modal -->
  <dialog id="orders_modal" class="modal">
    <div class="modal-box">
        <div class="flex justify-between items-center mb-4">
            <h3 class="font-bold text-lg" id="modal-title">Orders</h3>
            <button onclick="orders_modal.close()" class="btn btn-sm btn-circle btn-ghost">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>
        <div class="overflow-y-auto max-h-[60vh]" id="modal-content">
            <!-- Content will be loaded here -->
        </div>
        <div class="modal-action">
            <button onclick="orders_modal.close()" class="btn">Close</button>
        </div>
    </div>
  </dialog>

<dialog id="order_details_modal" class="modal">
  <div class="modal-box bg-white rounded-lg shadow-xl p-5 max-w-3xl">
    <div class="flex justify-between items-center mb-4">
      <h3 class="font-bold text-lg text-[#001f54]">Order Details</h3>
      <button onclick="order_details_modal.close()" 
              class="p-2 rounded-full bg-[#F7B32B] text-[#001f54] hover:opacity-90 transition">
        <i data-lucide="x" class="w-5 h-5"></i>
      </button>
    </div>
    <div class="overflow-y-auto max-h-[60vh] space-y-3" id="order-details-content">
      <!-- Dynamic content loaded here -->
    </div>
    <div class="modal-action mt-4">
      <button onclick="order_details_modal.close()" 
              class="px-4 py-2 rounded-md bg-[#001f54] text-[#F7B32B] font-semibold hover:opacity-90 transition">
        Close
      </button>
    </div>
  </div>
</dialog>



  <script>
    // Initialize Lucide icons
    lucide.createIcons();
    
    // Initialize modals
    const orders_modal = document.getElementById('orders_modal');
    const order_details_modal = document.getElementById('order_details_modal');
    
    // Show orders modal with filtered orders
    function showOrdersModal(status, title) {
        document.getElementById('modal-title').textContent = title + ' Orders';
        document.getElementById('modal-content').innerHTML = `
            <div class="flex justify-center py-8">
                <div class="animate-spin rounded-full h-8 w-8 border-t-2 border-b-2 border-primary"></div>
            </div>
        `;
        
        orders_modal.showModal();
        
        fetch('sub-modules/get_filtered_orders.php?status=' + status)
            .then(response => response.text())
            .then(html => {
                document.getElementById('modal-content').innerHTML = html;
                lucide.createIcons();
            })
            .catch(error => {
                document.getElementById('modal-content').innerHTML = `
                    <div class="alert alert-error">
                        <i data-lucide="alert-circle" class="w-6 h-6"></i>
                        <span>Error loading orders. Please try again.</span>
                    </div>
                `;
                lucide.createIcons();
            });
    }
    
    // Show order details modal
    function showOrderDetails(orderId) {
        document.getElementById('order-details-content').innerHTML = `
            <div class="flex justify-center py-8">
                <div class="animate-spin rounded-full h-8 w-8 border-t-2 border-b-2 border-primary"></div>
            </div>
        `;
        
        order_details_modal.showModal();
        
        fetch('sub-modules/get_order_details.php?id=' + orderId)
            .then(response => response.text())
            .then(html => {
                document.getElementById('order-details-content').innerHTML = html;
                lucide.createIcons();
            })
            .catch(error => {
                document.getElementById('order-details-content').innerHTML = `
                    <div class="alert alert-error">
                        <i data-lucide="alert-circle" class="w-6 h-6"></i>
                        <span>Error loading order details. Please try again.</span>
                    </div>
                `;
                lucide.createIcons();
            });
    }
    
    // Close modal when clicking outside
    window.addEventListener('click', (event) => {
        if (event.target === orders_modal) {
            orders_modal.close();
        }
        if (event.target === order_details_modal) {
            order_details_modal.close();
        }
    });
  </script>
  <script>
async function showOrderDetails(kotId) {
  try {
    const response = await fetch(`sub-modules/fetch_order_details.php?kot_id=${kotId}`);
    const data = await response.json();

    if (!data.success) {
      alert("Failed to load order details.");
      return;
    }

    const order = data.order;
    const content = document.getElementById('order-details-content');
    content.innerHTML = `
      <div class="p-4 border rounded-lg bg-gray-50">
        <p><strong>Order #:</strong> ${order.order_id}</p>
        <p><strong>Table:</strong> ${order.table_number || "Takeaway"}</p>
        <p><strong>Item:</strong> ${order.quantity}x ${order.item_name}</p>
        <p><strong>Instructions:</strong> ${order.special_instructions || "None"}</p>
        <p><strong>Status:</strong> <span class="font-semibold">${order.status}</span></p>
      </div>

      <div class="flex gap-2 mt-4">
        <button class="btn btn-sm bg-yellow-500 text-white" onclick="updateStatus(${kotId}, 'cook')">Cook It</button>
        <button class="btn btn-sm bg-orange-500 text-white" onclick="updateStatus(${kotId}, 'Preparing')">Preparing</button>
        <button class="btn btn-sm bg-green-600 text-white" onclick="updateStatus(${kotId}, 'serve')">Ready to Serve</button>
      </div>
    `;

    document.getElementById('orderDetailsModal').showModal();
  } catch (err) {
    console.error(err);
    alert("Error loading order details.");
  }
}

async function updateStatus(kotId, status) {
  try {
    const response = await fetch("sub-modules/update_order_status.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ kot_id: kotId, status })
    });

    const data = await response.json();
    if (data.success) {
      alert("Status updated to " + status);
      showOrderDetails(kotId); // refresh modal with new status
    } else {
      alert("Failed to update status.");
    }
  } catch (err) {
    console.error(err);
    alert("Error updating status.");
  }
}
</script>

  <script src="../JavaScript/sidebar.js"></script>
</body>
</html>