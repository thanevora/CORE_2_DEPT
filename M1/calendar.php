<?php
session_start();
include("../main_connection.php");

// Database connection
$db_name = "rest_m1_trs";
if (!isset($connections[$db_name])) {
    die("❌ Connection not found for $db_name");
}
$conn = $connections[$db_name];

// Filter setup
$filter_status = isset($_GET['status']) ? $_GET['status'] : 'all';
$where_clause = $filter_status != 'all' ? "WHERE status = '" . $conn->real_escape_string($filter_status) . "'" : "";

// Pagination setup
$limit = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Count total records
$count_sql = "SELECT COUNT(*) as total FROM reservations $where_clause";
$count_result = $conn->query($count_sql);
$total_records = $count_result->fetch_assoc()['total'];
$total_pages = ceil($total_records / $limit);

// Fetch reservations with filter + pagination
$sql = "SELECT * FROM reservations $where_clause ORDER BY reservation_date DESC, start_time DESC LIMIT $limit OFFSET $offset";
$result_sql = $conn->query($sql);

// Stats
$query = "SELECT 
  (SELECT COUNT(*) FROM reservations) AS total_reservations,
  (SELECT COUNT(*) FROM reservations WHERE status = 'Queued') AS Queued,
  (SELECT COUNT(*) FROM reservations WHERE status = 'Confirmed') AS Confirmed,
  (SELECT COUNT(*) FROM reservations WHERE status = 'Denied') AS Denied,
  (SELECT COUNT(*) FROM reservations WHERE status = 'For Compliance') AS For_compliance";

$result = $conn->query($query);
$row = $result->fetch_assoc();
$total_reservations_count = $row['total_reservations'] ?? 0;
$queued_count = $row['Queued'] ?? 0;
$confirmed_count = $row['Confirmed'] ?? 0;
$denied_count = $row['Denied'] ?? 0;
$for_compliance_count = $row['For_compliance'] ?? 0;
$search_data = [];
$search_name = isset($_GET['name']) ? trim($_GET['name']) : '';
if ($search_name !== '') {
    $sql = "SELECT reservation_id, name, reservation_date, start_time, end_time, status, note, table_id, created_at, modify_at, request
            FROM reservations
            WHERE name LIKE ?
            ORDER BY reservation_date DESC, start_time DESC";
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $like = "%{$search_name}%";
        $stmt->bind_param("s", $like);
        $stmt->execute();

        // Use bind_result so this works even if get_result is not available
        $stmt->bind_result($reservation_id, $name, $reservation_date, $start_time, $end_time, $status, $notes, $table_number, $create_at, $modify_at, $special_requests);
        while ($stmt->fetch()) {
            $search_data[] = [
                'reservation_id' => $reservation_id,
                'name' => $name,
                'reservation_date' => $reservation_date,
                'start_time' => $start_time,
                'end_time' => $end_time,
                'status' => $status,
                'notes' => $notes,
                'table_id' => $table_number,
                'created_at' => $create_at,
                'modify_at' => $modify_at,
                'request' => $special_requests
            ];
        }
        $stmt->close();
    } else {
        // fallback: try simple query (escaped) to avoid breaking everything
        $esc = $conn->real_escape_string($search_name);
        $q = "SELECT reservation_id, name, reservation_date, start_time, end_time, guests, status, notes, table_number, create_at, modify_at, special_requests
              FROM reservations WHERE name LIKE '%$esc%' ORDER BY reservation_date DESC, start_time DESC";
        $res = $conn->query($q);
        if ($res) {
            while ($row = $res->fetch_assoc()) {
                $search_data[] = $row;
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Restaurant Reservation System</title>
  <?php include '../header.php'; ?>
  <style>
    .custom-scrollbar::-webkit-scrollbar {
      width: 6px;
    }
    .custom-scrollbar::-webkit-scrollbar-track {
      background: #1e3a8a;
      border-radius: 10px;
    }
    .custom-scrollbar::-webkit-scrollbar-thumb {
      background: #F7B32B;
      border-radius: 10px;
    }
    .custom-scrollbar::-webkit-scrollbar-thumb:hover {
      background: #e6a123;
    }
    .status-pending { background-color: #fff3cd; color: #856404; border: 1px solid #ffeaa7; }
    .status-approved { background-color: #d1edff; color: #0c5460; border: 1px solid #bee5eb; }
    .status-rejected { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
    .status-confirmed { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
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
          <!-- Dashboard Cards -->
           
          <div class="glass-effect p-6 rounded-2xl shadow-sm border border-gray-100/50 backdrop-blur-sm bg-white/70">
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
              <h2 class="text-2xl font-bold text-gray-800 flex items-center">
                <span class="p-2 mr-3 rounded-lg bg-blue-100/50 text-blue-600">
                  <i data-lucide="activity" class="w-5 h-5"></i>
                </span>
                Dashboard
              </h2>
              
            </div>
            
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
              <!-- Confirmed Reservations -->
              <div class="stat-card bg-white text-black shadow-2xl p-5 rounded-xl transition-all duration-300 hover:shadow-2xl hover:scale-105 hover:bg-gray-50">
                <div class="flex justify-between items-start">
                  <div>
                    <p class="text-sm font-medium text-[#001f54] hover:drop-shadow-md transition-all">Confirmed</p>
                    <h3 class="text-3xl font-bold mt-1"><?php echo $confirmed_count; ?></h3>
                    <p class="text-xs text-gray-500 mt-1">Events confirmed</p>
                  </div>
                  <div class="p-3 rounded-lg bg-[#001f54] flex items-center justify-center transition-all duration-300 hover:bg-[#002b70]">
                    <i class="fas fa-check-circle text-2xl text-[#F7B32B]"></i>
                  </div>
                </div>
              </div>

              <!-- Queued Reservations -->
              <div class="stat-card bg-white text-black shadow-2xl p-5 rounded-xl transition-all duration-300 hover:shadow-2xl hover:scale-105 hover:bg-gray-50">
                <div class="flex justify-between items-start">
                  <div>
                    <p class="text-sm font-medium text-[#001f54] hover:drop-shadow-md transition-all">Queued</p>
                    <h3 class="text-3xl font-bold mt-1"><?php echo $queued_count; ?></h3>
                    <p class="text-xs text-gray-500 mt-1">Pending approval</p>
                  </div>
                  <div class="p-3 rounded-lg bg-[#001f54] flex items-center justify-center transition-all duration-300 hover:bg-[#002b70]">
                    <i class="fas fa-clock text-2xl text-[#F7B32B]"></i>
                  </div>
                </div>
              </div>

              <!-- Cancelled Reservations -->
              <div class="stat-card bg-white text-black shadow-2xl p-5 rounded-xl transition-all duration-300 hover:shadow-2xl hover:scale-105 hover:bg-gray-50">
                <div class="flex justify-between items-start">
                  <div>
                    <p class="text-sm font-medium text-[#001f54] hover:drop-shadow-md transition-all">Cancelled</p>
                    <h3 class="text-3xl font-bold mt-1"><?php echo $denied_count; ?></h3>
                    <p class="text-xs text-gray-500 mt-1">Cancelled events</p>
                  </div>
                  <div class="p-3 rounded-lg bg-[#001f54] flex items-center justify-center transition-all duration-300 hover:bg-[#002b70]">
                    <i class="fas fa-times-circle text-2xl text-[#F7B32B]"></i>
                  </div>
                </div>
              </div>

             <!-- For Compliance -->
<div class="stat-card bg-white text-black shadow-2xl p-5 rounded-xl transition-all duration-300 hover:shadow-2xl hover:scale-105 hover:bg-gray-50">
  <div class="flex justify-between items-start">
    <div>
      <p class="text-sm font-medium text-[#001f54] hover:drop-shadow-md transition-all">For Compliance</p>
      <h3 class="text-3xl font-bold mt-1"><?php echo $for_compliance_count; ?></h3>
      <p class="text-xs text-gray-500 mt-1">Pending action or review</p>
    </div>
    <div class="p-3 rounded-lg bg-[#001f54] flex items-center justify-center transition-all duration-300 hover:bg-[#002b70]">
      <i class="fas fa-clipboard-list text-2xl text-[#F7B32B]"></i>
    </div>
  </div>
</div>

            </div>
          </div>

<div class="bg-white p-4 rounded-lg shadow-sm mt-4 flex flex-col md:flex-row md:items-center md:justify-between gap-3">
  <h3 class="text-lg font-semibold text-gray-800">Filter Reservations</h3>

  <div class="flex items-center gap-3">
    <!-- Filter Dropdown -->
    <form method="GET" class="flex items-center gap-2">
      <select 
        name="status"
        class="bg-white text-black px-4 py-2 rounded-md border border-gray-300 focus:ring focus:ring-blue-200"
        onchange="this.form.submit()">
        <option value="all" <?= ($filter_status == 'all') ? 'selected' : '' ?>>All Reservations</option>
        <option value="Confirmed" <?= ($filter_status == 'Confirmed') ? 'selected' : '' ?>>Confirmed</option>
        <option value="Denied" <?= ($filter_status == 'Denied') ? 'selected' : '' ?>>Denied</option>
        <option value="Queued" <?= ($filter_status == 'Queued') ? 'selected' : '' ?>>Queued</option>
        <option value="Cancelled" <?= ($filter_status == 'Cancelled') ? 'selected' : '' ?>>Cancelled</option>
        <option value="For Compliance" <?= ($filter_status == 'For Compliance') ? 'selected' : '' ?>>For Compliance</option>
      </select>
    </form>

    <!-- Search Button -->
    <button 
      type="button"
      class="btn btn-sm bg-blue-600 text-white hover:bg-blue-700 transition-all duration-200 hover:scale-105"
      onclick="document.getElementById('search-modal').showModal()">
      <i class="bx bx-search mr-1"></i> Search
    </button>

    <!-- New Reservation Button -->
    <button 
      class="btn btn-sm bg-[#F7B32B] text-black hover:bg-[#d99a22] transition-all duration-200 hover:scale-105"
      onclick="document.getElementById('reservations-modal').showModal()">
      <i class="bx bx-plus mr-1"></i> New
    </button>
  </div>
</div>






<!-- Reservation List (Glassy UI) -->
<div class="p-6 grid gap-3 
            sm:grid-cols-2 
            md:grid-cols-3 
            lg:grid-cols-4 
            xl:grid-cols-4 
            2xl:grid-cols-4">

  <?php if ($result_sql && $result_sql->num_rows > 0): ?>
    <?php while ($row = $result_sql->fetch_assoc()): 
      // Glassy background color per status
      $cardClass = match($row['status']) {
        'Confirmed' => 'bg-green-200/30 backdrop-blur-lg border border-green-400/40 shadow-green-200/30',
        'Denied' => 'bg-red-200/30 backdrop-blur-lg border border-red-400/40 shadow-red-200/30',
        'Queued' => 'bg-yellow-200/30 backdrop-blur-lg border border-yellow-400/40 shadow-yellow-200/30',
        'For Compliance' => 'bg-orange-200/30 backdrop-blur-lg border border-orange-400/40 shadow-orange-200/30',
        default => 'bg-gray-200/30 backdrop-blur-lg border border-gray-400/40 shadow-gray-200/30'
      };

      $badgeColor = match($row['status']) {
        'Confirmed' => 'bg-green-500/90 text-white',
        'Denied' => 'bg-red-500/90 text-white',
        'Queued' => 'bg-yellow-500/90 text-white',
        'For Compliance' => 'bg-orange-500/90 text-white',
        default => 'bg-gray-500/90 text-white'
      };
    ?>

      <div class="<?= $cardClass ?> rounded-lg shadow-xl hover:shadow-xl 
                  transition-all duration-300 p-5 flex flex-col justify-between 
                  hover:scale-[1.03]">
        
        <!-- Header -->
        <div class="flex items-center justify-between mb-4">
          <div class="flex items-center gap-2 truncate">
            <i data-lucide="user" class="w-5 h-5 text-[#F7B32B] drop-shadow-sm"></i>
            <h3 class="font-semibold text-gray-900 text-lg truncate">
              <?= htmlspecialchars($row['name']) ?>
            </h3>
          </div>
          <span class="px-3 py-1 text-xs font-semibold rounded-full <?= $badgeColor ?> shadow-md whitespace-nowrap">
            <?= htmlspecialchars($row['status']) ?>
          </span>
        </div>

        <!-- Reservation Info -->
        <div class="space-y-2 text-sm text-gray-800">
          <p class="flex items-center gap-2">
            <i data-lucide="phone" class="w-4 h-4 text-[#F7B32B]"></i>
            <span><?= htmlspecialchars($row['contact']) ?></span>
          </p>
          <p class="flex items-center gap-2">
            <i data-lucide="calendar" class="w-4 h-4 text-[#F7B32B]"></i>
            <span><?= date('M j, Y', strtotime($row['reservation_date'])) ?></span>
          </p>
          <p class="flex items-center gap-2">
            <i data-lucide="clock" class="w-4 h-4 text-[#F7B32B]"></i>
            <span><?= date('g:i A', strtotime($row['start_time'])) ?></span>
          </p>
          <p class="flex items-center gap-2">
            <i data-lucide="users" class="w-4 h-4 text-[#F7B32B]"></i>
            <span><?= (int)$row['size'] ?> guests</span>
          </p>
          <p class="flex items-center gap-2">
            <i data-lucide="utensils-crossed" class="w-4 h-4 text-[#F7B32B]"></i>
            <span><?= htmlspecialchars($row['type']) ?></span>
          </p>
        </div>

        <!-- Footer -->
        <div class="mt-4 flex justify-between items-center">
          <div class="text-xs text-gray-600">
            <i data-lucide="calendar-check" class="inline w-3 h-3 mr-1 text-[#F7B32B]"></i>
            <?= date('M j, Y', strtotime($row['created_at'] ?? $row['reservation_date'])) ?>
          </div>
          <button 
            class="btn btn-sm bg-[#F7B32B] text-black font-semibold hover:bg-[#d99a22] border-none transition-all duration-200 hover:scale-105 view-btn"
            data-id="<?= $row['reservation_id'] ?>">
            <i data-lucide="eye" class="w-4 h-4 mr-1"></i> Details
          </button>
        </div>
      </div>

    <?php endwhile; ?>
  <?php else: ?>
    <div class="col-span-full flex flex-col items-center justify-center py-10 text-gray-500">
      <i data-lucide="calendar-x" class="w-12 h-12 mb-3 text-[#F7B32B]"></i>
      <p class="text-lg font-medium">No reservations found</p>
      <?php if ($filter_status != 'all'): ?>
        <p class="text-sm mt-1">No <?= strtolower($filter_status); ?> reservations</p>
      <?php endif; ?>
    </div>
  <?php endif; ?>
</div>



<!-- View Modal -->
<div id="view-modal" class="fixed inset-0 flex items-center justify-center bg-black/50 z-50 hidden">
  <div class="modal-box w-11/12 max-w-4xl bg-white text-black shadow-2xl border-2 border-[#F7B32B] rounded-xl">

    <!-- Header -->
    <div class="flex justify-between items-center mb-4">
      <h3 class="font-bold text-xl flex items-center text-black">
        <i class="bx bx-detail mr-2 text-[#F7B32B] text-2xl"></i>
        <span>Reservation Details</span>
      </h3>
      <button id="close-modal" class="btn btn-circle btn-ghost btn-sm text-black hover:bg-[#F7B32B] hover:text-black transition-all">
        <i class="bx bx-x text-xl"></i>
      </button>
    </div>

    <!-- Body -->
    <div id="reservation-details" class="py-4 max-h-[60vh] overflow-y-auto custom-scrollbar">
      <!-- Loading -->
      <div id="loading-indicator" class="flex justify-center items-center py-8">
        <div class="animate-spin rounded-full h-10 w-10 border-b-2 border-[#F7B32B]"></div>
      </div>

      <!-- Dynamic Content -->
      <div id="reservation-content" class="hidden text-black"></div>
    </div>

  <!-- Footer -->
<div class="modal-action mt-6 pt-4 border-t border-gray-300 flex justify-between items-center">

  <!-- Status Display -->
  <div id="status-display" class="flex items-center space-x-2">
    <span class="text-black font-medium">Status:</span>
    <span id="current-status" class="px-3 py-1 rounded-full text-sm font-semibold bg-gray-200 text-black"></span>
  </div>

  <!-- CRUD Buttons -->
  <div id="action-buttons" class="flex space-x-2">
    <button class="btn btn-sm bg-green-600 text-white hover:bg-green-700 status-btn" data-status="Confirmed">
      <i class="bx bx-check-circle mr-1"></i> Confirme
    </button>

    <button class="btn btn-sm bg-red-600 text-white hover:bg-red-700 status-btn" data-status="Denied">
      <i class="bx bx-x-circle mr-1"></i> Denie
    </button>

    <button class="btn btn-sm bg-blue-600 text-white hover:bg-blue-700 status-btn" data-status="For Compliance">
      <i class="bx bx-edit-alt mr-1"></i> For Compliance
    </button>
  </div>
</div>


  </div>
</div>






 <!-- Reservations Modal (Create/Edit) -->
<dialog id="reservations-modal" class="modal backdrop-blur-sm">
  <div class="modal-box w-[95vw] max-w-[1100px] h-[90vh] max-h-[800px] p-0 overflow-hidden bg-white border-0 shadow-2xl rounded-2xl transform transition-all duration-300 ease-out">
    <div class="flex flex-col h-full">
      <!-- Header -->
      <div class="flex justify-between items-center p-6 border-b border-gray-100 bg-gradient-to-r from-[#001f54] to-[#0a2a6a] text-white rounded-t-2xl">
        <div class="flex items-center gap-3">
          <div class="p-2 rounded-xl bg-white/10 text-[#F7B32B]">
            <i data-lucide="calendar-plus" class="w-6 h-6"></i>
          </div>
          <div>
            <h5 id="modal-date-title" class="text-2xl font-bold text-white">New Reservation</h5>
            <p class="text-sm text-blue-100 mt-1">Fill in the details to create a new booking</p>
          </div>
        </div>
        <button id="close-modal" class="btn btn-sm btn-circle bg-white/10 hover:bg-white/20 text-white border-0 transition-all duration-200"
          onclick="document.getElementById('reservations-modal').close()">
          <i data-lucide="x" class="w-5 h-5"></i>
        </button>
      </div>

      <!-- Form Content -->
      <div class="flex-1 overflow-y-auto p-6 bg-gray-50 scroll-smooth [scrollbar-width:none] [-ms-overflow-style:none] [&::-webkit-scrollbar]:hidden">
        <form id="reservation-form" class="space-y-6" novalidate>
          <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Left Column -->
            <div class="space-y-6">
              <!-- Guest Section -->
              <div class="bg-white p-5 rounded-xl border border-gray-200 shadow-sm transition-all duration-300 hover:shadow-md">
                <h6 class="text-lg font-semibold text-gray-800 mb-4 flex items-center gap-2">
                  <div class="p-1.5 rounded-lg bg-[#001f54]/10 text-[#001f54]">
                    <i data-lucide="user" class="w-4 h-4"></i>
                  </div>
                  Guest Information
                </h6>
                
                <!-- Guest Name -->
                <div class="form-control mb-4">
                  <label class="label mb-2" for="name">
                    <span class="label-text font-medium text-gray-700">Full Name*</span>
                  </label>
                  <div class="relative">
                    <i data-lucide="user" class="absolute left-3 top-1/2 transform -translate-y-1/2 w-4 h-4 text-gray-400"></i>
                    <input type="text" id="name" name="name"
                      class="input input-bordered w-full bg-white border-gray-300 focus:border-[#001f54] focus:ring-2 focus:ring-[#001f54]/20 transition-all duration-200 hover:border-gray-400 pl-10 py-3 rounded-lg"
                      placeholder="John Doe"
                      required>
                  </div>
                </div>

                <!-- Contact -->
                <div class="form-control mb-4">
                  <label class="label mb-2" for="contact">
                    <span class="label-text font-medium text-gray-700">Contact Information*</span>
                  </label>
                  <div class="relative">
                    <i data-lucide="phone" class="absolute left-3 top-1/2 transform -translate-y-1/2 w-4 h-4 text-gray-400"></i>
                    <input 
                      type="tel" 
                      id="contact" 
                      name="contact"
                      maxlength="11"
                      pattern="[0-9]{11}"
                      inputmode="numeric"
                      class="input input-bordered w-full bg-white border-gray-300 
                             focus:border-[#001f54] focus:ring-2 focus:ring-[#001f54]/20 
                             transition-all duration-200 hover:border-gray-400 
                             pl-10 py-3 rounded-lg"
                      placeholder="09123456789"
                      required
                    >
                  </div>
                  <small class="text-xs text-gray-500 mt-1">
                    Must be 11 digits (e.g. 09123456789)
                  </small>
                </div>

                <div class="form-control">
                  <label class="label mb-2" for="email">
                    <span class="label-text font-medium text-gray-700">Email*</span>
                  </label>
                  <div class="relative">
                    <i data-lucide="mail" class="absolute left-3 top-1/2 transform -translate-y-1/2 w-4 h-4 text-gray-400"></i>
                    <input type="email" id="email" name="email"
                      class="input input-bordered w-full bg-white border-gray-300 focus:border-[#001f54] focus:ring-2 focus:ring-[#001f54]/20 transition-all duration-200 hover:border-gray-400 pl-10 py-3 rounded-lg"
                      placeholder="Email address"
                      required>
                  </div>
                </div>
              </div>

              <!-- Reservation Details Section -->
              <div class="bg-white p-5 rounded-xl border border-gray-200 shadow-sm transition-all duration-300 hover:shadow-md">
                <h6 class="text-lg font-semibold text-gray-800 mb-4 flex items-center gap-2">
                  <div class="p-1.5 rounded-lg bg-[#001f54]/10 text-[#001f54]">
                    <i data-lucide="calendar-clock" class="w-4 h-4"></i>
                  </div>
                  Reservation Details
                </h6>
                
                <div class="grid grid-cols-2 gap-4">
                  <!-- Date -->
                  <div class="form-control">
                    <label class="label mb-2" for="reservation_date">
                      <span class="label-text font-medium text-gray-700">Date*</span>
                    </label>
                    <div class="relative">
                      <i data-lucide="calendar" class="absolute left-3 top-1/2 transform -translate-y-1/2 w-4 h-4 text-gray-400"></i>
                      <input 
                        type="date" 
                        id="reservation_date" 
                        name="reservation_date"
                        class="bg-white text-black input input-bordered w-full border-gray-300 
                               focus:border-[#001f54] focus:ring-2 focus:ring-[#001f54]/20 
                               transition-all duration-200 hover:border-gray-400 pl-10 pr-3 py-3 rounded-lg 
                               appearance-auto cursor-pointer"
                        required
                        min="<?= date('Y-m-d') ?>"
                      >
                    </div>
                  </div>

                  <!-- Party Size -->
                  <div class="form-control">
                    <label class="label mb-2" for="size">
                      <span class="label-text font-medium text-gray-700">Party Size*</span>
                    </label>
                    <div class="relative">
                      <i data-lucide="users" class="absolute left-3 top-1/2 transform -translate-y-1/2 w-4 h-4 text-gray-400"></i>
                      <input 
                        type="number" 
                        id="size" 
                        name="size" 
                        min="1" 
                        max="10" 
                        class="input input-bordered w-full bg-white border-gray-300 
                               focus:border-[#001f54] focus:ring-2 focus:ring-[#001f54]/20 
                               transition-all duration-200 hover:border-gray-400 
                               pl-10 pr-3 py-3 rounded-lg appearance-none"
                        placeholder="2" 
                        required
                      >
                    </div>
                    <small class="text-xs text-gray-500 mt-1">
                      Maximum of 10 guests per reservation.
                    </small>
                  </div>
                </div>

                <!-- Time -->
                <div class="grid grid-cols-2 gap-4 mt-4">
                  <div class="form-control">
                    <label class="label mb-2" for="start_time">
                      <span class="label-text font-medium text-gray-700">Start Time*</span>
                    </label>
                    <div class="relative">
                      <i data-lucide="clock" class="absolute left-3 top-1/2 transform -translate-y-1/2 w-4 h-4 text-gray-400"></i>
                      <input 
                        type="time" 
                        id="start_time" 
                        name="start_time"
                        class="input input-bordered w-full bg-white border-gray-300 
                               focus:border-[#001f54] focus:ring-2 focus:ring-[#001f54]/20 
                               transition-all duration-200 hover:border-gray-400 
                               pl-10 pr-3 py-3 rounded-lg appearance-auto cursor-pointer"
                        required
                      >
                    </div>
                  </div>
                </div>
              </div>

              <!-- Payment Section -->
              <div class="bg-white p-5 rounded-xl border border-gray-200 shadow-sm transition-all duration-300 hover:shadow-md">
                <h6 class="text-lg font-semibold text-gray-800 mb-4 flex items-center gap-2">
                  <div class="p-1.5 rounded-lg bg-[#001f54]/10 text-[#001f54]">
                    <i data-lucide="credit-card" class="w-4 h-4"></i>
                  </div>
                  Payment Details
                </h6>
                
                <!-- Amount -->
                <div class="form-control mb-4">
                  <label class="label mb-2" for="amount">
                    <span class="label-text font-medium text-gray-700">Amount (₱)*</span>
                  </label>
                  <div class="relative">
                    <i data-lucide="dollar-sign" class="absolute left-3 top-1/2 transform -translate-y-1/2 w-4 h-4 text-gray-400"></i>
                    <input 
                      type="number" 
                      id="amount" 
                      name="amount" 
                      min="1" 
                      max="100000"
                      step="0.01"
                      class="input input-bordered w-full bg-white border-gray-300 
                             focus:border-[#001f54] focus:ring-2 focus:ring-[#001f54]/20 
                             transition-all duration-200 hover:border-gray-400 
                             pl-10 pr-3 py-3 rounded-lg amount-input"
                      placeholder="0.00"
                      required
                      oninput="validateAmount(this)"
                    >
                  </div>
                  <div class="flex justify-between items-center mt-1">
                    <small class="text-xs text-gray-500">
                      Amount must be between ₱1.00 and ₱100,000.00
                    </small>
                    <small id="amount-warning" class="text-xs text-red-500 hidden">
                      ⚠ Amount exceeds ₱100,000 limit
                    </small>
                  </div>
                </div>

                <!-- Mode of Payment -->
                <div class="form-control mb-4">
                  <label class="label mb-2">
                    <span class="label-text font-medium text-gray-700">Mode of Payment*</span>
                  </label>
                  <div class="grid grid-cols-2 gap-3">
                    <!-- GCash -->
                    <label class="flex items-center gap-3 p-3 border-2 border-gray-200 rounded-xl hover:border-[#001f54]/40 hover:bg-[#001f54]/5 cursor-pointer transition-all duration-200 has-[:checked]:bg-blue-50 has-[:checked]:border-[#001f54]">
                      <input type="radio" name="MOP" value="gcash" class="radio radio-sm checked:bg-[#001f54]" required>
                      <span class="flex items-center gap-2 text-gray-700">
                        <div class="w-8 h-8 bg-gradient-to-r from-[#00a94f] to-[#00c853] rounded-lg flex items-center justify-center">
                          <i data-lucide="smartphone" class="w-4 h-4 text-white"></i>
                        </div>
                        <span class="font-medium">GCash</span>
                      </span>
                    </label>

                    <!-- Maya -->
                    <label class="flex items-center gap-3 p-3 border-2 border-gray-200 rounded-xl hover:border-[#001f54]/40 hover:bg-[#001f54]/5 cursor-pointer transition-all duration-200 has-[:checked]:bg-blue-50 has-[:checked]:border-[#001f54]">
                      <input type="radio" name="MOP" value="maya" class="radio radio-sm checked:bg-[#001f54]">
                      <span class="flex items-center gap-2 text-gray-700">
                        <div class="w-8 h-8 bg-gradient-to-r from-[#6c35b9] to-[#8a4dff] rounded-lg flex items-center justify-center">
                          <i data-lucide="credit-card" class="w-4 h-4 text-white"></i>
                        </div>
                        <span class="font-medium">Maya</span>
                      </span>
                    </label>

                    <!-- Credit/Debit Card -->
                    <label class="flex items-center gap-3 p-3 border-2 border-gray-200 rounded-xl hover:border-[#001f54]/40 hover:bg-[#001f54]/5 cursor-pointer transition-all duration-200 has-[:checked]:bg-blue-50 has-[:checked]:border-[#001f54]">
                      <input type="radio" name="MOP" value="card" class="radio radio-sm checked:bg-[#001f54]">
                      <span class="flex items-center gap-2 text-gray-700">
                        <div class="w-8 h-8 bg-gradient-to-r from-[#ff6b35] to-[#ff8c42] rounded-lg flex items-center justify-center">
                          <i data-lucide="card" class="w-4 h-4 text-white"></i>
                        </div>
                        <span class="font-medium">Card</span>
                      </span>
                    </label>

                    <!-- Cash -->
                    <label class="flex items-center gap-3 p-3 border-2 border-gray-200 rounded-xl hover:border-[#001f54]/40 hover:bg-[#001f54]/5 cursor-pointer transition-all duration-200 has-[:checked]:bg-blue-50 has-[:checked]:border-[#001f54]">
                      <input type="radio" name="MOP" value="cash" class="radio radio-sm checked:bg-[#001f54]">
                      <span class="flex items-center gap-2 text-gray-700">
                        <div class="w-8 h-8 bg-gradient-to-r from-[#28a745] to-[#34ce57] rounded-lg flex items-center justify-center">
                          <i data-lucide="banknote" class="w-4 h-4 text-white"></i>
                        </div>
                        <span class="font-medium">Cash</span>
                      </span>
                    </label>
                  </div>
                </div>

                <!-- Payment Type -->
                <div class="form-control">
                  <label class="label mb-2">
                    <span class="label-text font-medium text-gray-700">Payment Type*</span>
                  </label>
                  <div class="grid grid-cols-2 gap-3">
                    <label class="flex items-center gap-3 p-3 border-2 border-gray-200 rounded-xl hover:border-[#001f54]/40 hover:bg-[#001f54]/5 cursor-pointer transition-all duration-200 has-[:checked]:bg-blue-50 has-[:checked]:border-[#001f54]">
                      <input type="radio" name="payment_type" value="full" class="radio radio-sm checked:bg-[#001f54]" required checked>
                      <span class="flex items-center gap-2 text-gray-700">
                        <i data-lucide="circle-dollar-sign" class="w-5 h-5 text-green-600"></i>
                        <span class="font-medium">Full Payment</span>
                      </span>
                    </label>

                    <label class="flex items-center gap-3 p-3 border-2 border-gray-200 rounded-xl hover:border-[#001f54]/40 hover:bg-[#001f54]/5 cursor-pointer transition-all duration-200 has-[:checked]:bg-blue-50 has-[:checked]:border-[#001f54]">
                      <input type="radio" name="payment_type" value="downpayment" class="radio radio-sm checked:bg-[#001f54]">
                      <span class="flex items-center gap-2 text-gray-700">
                        <i data-lucide="percent" class="w-5 h-5 text-amber-600"></i>
                        <span class="font-medium">Downpayment</span>
                      </span>
                    </label>
                  </div>
                </div>
              </div>
            </div>

            <!-- Right Column -->
            <div class="space-y-6">
              <!-- Table Selection -->
              <div class="bg-white p-5 rounded-xl border border-gray-200 shadow-sm transition-all duration-300 hover:shadow-md">
                <h6 class="text-lg font-semibold text-gray-800 mb-4 flex items-center gap-2">
                  <div class="p-1.5 rounded-lg bg-[#001f54]/10 text-[#001f54]">
                    <i data-lucide="table" class="w-4 h-4"></i>
                  </div>
                  Table Selection
                </h6>
                
                <div class="form-control relative">
                  <div class="relative">
                    <i data-lucide="table" class="absolute left-3 top-1/2 transform -translate-y-1/2 w-5 h-5 text-gray-400"></i>
                    <select id="table_id" name="table_id"
                      class="pl-10 pr-10 py-3 w-full bg-white border border-gray-300 rounded-lg shadow-sm focus:border-[#001f54] focus:ring-2 focus:ring-[#001f54]/20 transition-all duration-200 appearance-none hover:border-gray-400 cursor-pointer"
                      required>
                      <option value="" disabled selected class="text-gray-400">-- Choose a Table --</option>
                      <?php
                        include('../conn_M1.php');
                        $query = "SELECT table_id, name, category, capacity, status FROM tables ORDER BY status, name";
                        $result = $conn->query($query);
                        while ($row = $result->fetch_assoc()):
                          $disabled = ($row['status'] !== 'Available') ? 'disabled' : '';
                          $class = ($row['status'] !== 'Available') 
                              ? 'bg-gray-50 text-gray-400 cursor-not-allowed' 
                              : 'hover:bg-blue-50 text-gray-700';
                      ?>
                        <option value="<?= $row['table_id']; ?>" class="<?= $class; ?>" <?= $disabled; ?> data-status="<?= $row['status']; ?>">
                          <?= htmlspecialchars($row['name']) ?> • <?= $row['category'] ?> • <?= $row['capacity'] ?> pax • 
                          <span class="<?= $row['status'] === 'Available' ? 'text-green-600 font-medium' : 'text-gray-500' ?>">
                            <?= $row['status'] ?>
                          </span>
                        </option>
                      <?php endwhile; ?>
                    </select>
                    
                    <!-- Dropdown Arrow -->
                    <i data-lucide="chevron-down" class="absolute right-3 top-1/2 transform -translate-y-1/2 w-5 h-5 text-gray-400 pointer-events-none"></i>
                  </div>
                  
                  <!-- Status Legend -->
                  <div class="flex flex-wrap gap-4 mt-3 text-xs">
                    <div class="flex items-center gap-2">
                      <span class="w-3 h-3 rounded-full bg-green-500"></span>
                      <span class="text-gray-500 font-medium">Available</span>
                    </div>
                    <div class="flex items-center gap-2">
                      <span class="w-3 h-3 rounded-full bg-amber-500"></span>
                      <span class="text-gray-500 font-medium">Occupied</span>
                    </div>
                    <div class="flex items-center gap-2">
                      <span class="w-3 h-3 rounded-full bg-gray-400"></span>
                      <span class="text-gray-500 font-medium">Unavailable</span>
                    </div>
                  </div>
                </div>

                <div class="mt-4">
                  <label class="label mb-2">
                    <span class="label-text font-medium text-gray-700">Reservation Type*</span>
                  </label>
                  <div class="grid grid-cols-3 gap-3">
                    <label class="flex items-center gap-2 p-3 border-2 border-gray-200 rounded-xl hover:border-[#001f54]/40 hover:bg-[#001f54]/5 cursor-pointer transition-all duration-200 has-[:checked]:bg-blue-50 has-[:checked]:border-[#001f54]">
                      <input type="radio" name="type" value="breakfast" class="radio radio-sm checked:bg-[#001f54]">
                      <span class="flex items-center gap-2 text-gray-700">
                        <i data-lucide="sun" class="w-4 h-4 text-amber-500"></i>
                        <span class="font-medium">Breakfast</span>
                      </span>
                    </label>
                    <label class="flex items-center gap-2 p-3 border-2 border-gray-200 rounded-xl hover:border-[#001f54]/40 hover:bg-[#001f54]/5 cursor-pointer transition-all duration-200 has-[:checked]:bg-blue-50 has-[:checked]:border-[#001f54]">
                      <input type="radio" name="type" value="lunch" class="radio radio-sm checked:bg-[#001f54]">
                      <span class="flex items-center gap-2 text-gray-700">
                        <i data-lucide="sunrise" class="w-4 h-4 text-orange-500"></i>
                        <span class="font-medium">Lunch</span>
                      </span>
                    </label>
                    <label class="flex items-center gap-2 p-3 border-2 border-gray-200 rounded-xl hover:border-[#001f54]/40 hover:bg-[#001f54]/5 cursor-pointer transition-all duration-200 has-[:checked]:bg-blue-50 has-[:checked]:border-[#001f54]">
                      <input type="radio" name="type" value="dinner" class="radio radio-sm checked:bg-[#001f54]">
                      <span class="flex items-center gap-2 text-gray-700">
                        <i data-lucide="moon" class="w-4 h-4 text-indigo-500"></i>
                        <span class="font-medium">Dinner</span>
                      </span>
                    </label>
                  </div>
                </div>
              </div>

              <!-- Special Requests -->
              <div class="bg-white p-5 rounded-xl border border-gray-200 shadow-sm transition-all duration-300 hover:shadow-md">
                <h6 class="text-lg font-semibold text-gray-800 mb-4 flex items-center gap-2">
                  <div class="p-1.5 rounded-lg bg-[#001f54]/10 text-[#001f54]">
                    <i data-lucide="message-square" class="w-4 h-4"></i>
                  </div>
                  Special Requests
                </h6>
                <div class="relative">
                  <i data-lucide="message-square" class="absolute left-3 top-3 w-4 h-4 text-gray-400"></i>
                  <textarea id="request" name="request"
                    class="textarea textarea-bordered w-full h-32 bg-white border-gray-300 focus:border-[#001f54] focus:ring-2 focus:ring-[#001f54]/20 transition-all duration-200 hover:border-gray-400 rounded-lg py-3 pl-10"
                    placeholder="Any dietary restrictions or special arrangements..."></textarea>
                </div>
              </div>

              <!-- Additional Notes -->
              <div class="bg-white p-5 rounded-xl border border-gray-200 shadow-sm transition-all duration-300 hover:shadow-md">
                <h6 class="text-lg font-semibold text-gray-800 mb-4 flex items-center gap-2">
                  <div class="p-1.5 rounded-lg bg-[#001f54]/10 text-[#001f54]">
                    <i data-lucide="file-text" class="w-4 h-4"></i>
                  </div>
                  Additional Notes
                </h6>
                <div class="relative">
                  <i data-lucide="file-text" class="absolute left-3 top-3 w-4 h-4 text-gray-400"></i>
                  <textarea id="note" name="note"
                    class="textarea textarea-bordered w-full h-32 bg-white border-gray-300 focus:border-[#001f54] focus:ring-2 focus:ring-[#001f54]/20 transition-all duration-200 hover:border-gray-400 rounded-lg py-3 pl-10"
                    placeholder="Internal notes or comments..."></textarea>
                </div>
              </div>
            </div>
          </div>

          <!-- Footer -->
          <div class="py-4 px-6 border-t border-gray-200 bg-white flex justify-between items-center rounded-b-2xl">
            <div class="text-sm text-gray-500 flex items-center gap-2">
              <i data-lucide="info" class="w-4 h-4 text-[#F7B32B]"></i>
              <span>Fields marked with * are required</span>
            </div>
            <div class="flex gap-3">
              <button type="button" class="btn btn-ghost text-gray-700 hover:bg-gray-100 transition-all duration-200 border border-gray-300 rounded-lg"
                  onclick="document.getElementById('reservations-modal').close()">
                  <i data-lucide="x" class="w-4 h-4 mr-2"></i> Cancel
              </button>
              <button type="submit" class="btn bg-[#001f54] hover:bg-[#001a44] text-white transition-all duration-200 shadow hover:shadow-md border-0 rounded-lg flex items-center gap-2">
                  <i data-lucide="plus" class="w-4 h-4"></i>
                  Create Reservation
              </button>
            </div>
          </div>
        </form>
      </div>
    </div>
  </div>

  <form method="dialog" class="modal-backdrop">
    <button>close</button>
  </form>
</dialog>











          <!-- Pagination -->
          <div class="text-black flex justify-center items-center gap-2 mt-6">
            <?php if ($page > 1): ?>
              <a href="?status=<?php echo $filter_status; ?>&page=<?php echo $page-1; ?>" 
                 class="px-3 py-1 bg-gray-200 rounded hover:bg-gray-300">Prev</a>
            <?php endif; ?>

            <span class="px-3 py-1">Page <?php echo $page; ?> of <?php echo $total_pages; ?></span>

            <?php if ($page < $total_pages): ?>
              <a href="?status=<?php echo $filter_status; ?>&page=<?php echo $page+1; ?>" 
                 class="px-3 py-1 bg-gray-200 rounded hover:bg-gray-300">Next</a>
            <?php endif; ?>
          </div>

<!-- Search Modal -->
<dialog id="search-modal" class="modal modal-bottom sm:modal-middle">
  <div class="modal-box bg-white text-black rounded-lg shadow-2xl border border-gray-200">
    <h3 class="font-semibold text-lg mb-4 flex items-center gap-2">
      <i class="bx bx-search text-blue-600"></i> Search Reservation by Customer
    </h3>

    <form id="search-form" class="space-y-3" method="GET" action="<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>">
      <input 
        type="text" 
        name="name" 
        id="search-input"
        value="<?= isset($_GET['name']) ? htmlspecialchars($_GET['name']) : '' ?>"
        placeholder="Enter customer name..."
        class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring focus:ring-blue-200 focus:border-blue-400 bg-white text-black"
        required>

      <div class="flex justify-end gap-2 mt-4">
        <button type="button" class="btn bg-gray-300 hover:bg-gray-400 text-black" onclick="document.getElementById('search-modal').close()">Cancel</button>
        <button type="submit" class="btn bg-blue-600 hover:bg-blue-700 text-white">Search</button>
      </div>
    </form>
  </div>
</dialog>

<!-- Results Modal -->
<div id="results-modal" class="fixed inset-0 flex items-center justify-center bg-black/60 backdrop-blur-sm z-[1000] hidden">
  <div class="modal-box w-11/12 max-w-6xl bg-white/70 backdrop-blur-md text-black shadow-2xl rounded-xl overflow-y-auto max-h-[90vh]">
    <div class="flex justify-between items-center mb-4 p-4 border-b">
      <h3 class="font-bold text-xl flex items-center gap-2">
        <i class="bx bx-list-ul text-[#F7B32B]"></i> Reservations for "<span id="results-name"></span>"
      </h3>
      <button id="close-results" class="btn btn-sm bg-gray-300 hover:bg-gray-400 text-black">Close</button>
    </div>

    <div id="results-grid" class="p-4 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4"></div>
  </div>
</div>

<!-- Details Modal -->
<div id="details-modal" class="fixed inset-0 flex items-center justify-center bg-black/60 z-[2000] hidden">
  <div class="modal-box w-11/12 max-w-4xl bg-white text-black shadow-2xl border-2 border-[#F7B32B] rounded-xl relative">
    <div class="flex justify-between items-center mb-4">
      <h3 class="font-bold text-xl flex items-center text-black">
        <i class="bx bx-detail mr-2 text-[#F7B32B] text-2xl"></i>
        <span>Reservation Details</span>
      </h3>
      <button id="close-details-modal" class="btn btn-circle btn-ghost btn-sm text-black hover:bg-[#F7B32B] transition-all">
        <i class="bx bx-x text-xl"></i>
      </button>
    </div>

    <div id="reservation-details" class="py-4 max-h-[60vh] overflow-y-auto custom-scrollbar">
      <div id="loading-indicator" class="flex justify-center items-center py-8 hidden">
        <div class="animate-spin rounded-full h-10 w-10 border-b-2 border-[#F7B32B]"></div>
      </div>
      <div id="details-content" class="text-black"></div>
    </div>

    <div class="modal-action mt-6 pt-4 border-t border-gray-300 flex justify-between items-center">
      <div class="flex items-center space-x-2">
        <span class="text-black font-medium">Status:</span>
        <span id="current-status" class="px-3 py-1 rounded-full text-sm font-semibold bg-gray-200 text-black"></span>
      </div>
      <div id="action-buttons" class="flex space-x-2">
        <button class="btn btn-sm bg-green-600 text-white hover:bg-green-700 status-btn" data-status="Confirmed">
          <i class="bx bx-check-circle mr-1"></i> Confirm
        </button>
        <button class="btn btn-sm bg-red-600 text-white hover:bg-red-700 status-btn" data-status="Denied">
          <i class="bx bx-x-circle mr-1"></i> Deny
        </button>
        <button class="btn btn-sm bg-blue-600 text-white hover:bg-blue-700 status-btn" data-status="For Compliance">
          <i class="bx bx-edit-alt mr-1"></i> For Compliance
        </button>
      </div>
    </div>
  </div>
</div>

<script>
  // Amount validation function
  function validateAmount(input) {
    const warning = document.getElementById('amount-warning');
    const value = parseFloat(input.value);
    
    if (value > 100000) {
      warning.classList.remove('hidden');
      input.classList.add('border-red-500');
    } else {
      warning.classList.add('hidden');
      input.classList.remove('border-red-500');
    }
  }

  // Initialize Lucide icons when modal opens
  document.getElementById('reservations-modal').addEventListener('click', function(e) {
    if (e.target === this) {
      lucide.createIcons();
    }
  });
</script>


<script>
document.addEventListener('DOMContentLoaded', () => {
  const searchForm = document.getElementById('search-form');

  // 🔍 Searching feedback
  searchForm.addEventListener('submit', () => {
    Swal.fire({
      title: 'Searching...',
      allowOutsideClick: false,
      didOpen: () => Swal.showLoading(),
      customClass: { popup: 'z-[100000]' } // 🔥 ensure front layer
    });
  });

  <?php if (!empty($search_data)): ?>
    Swal.close();

    const resultsModal = document.getElementById('results-modal');
    const resultsGrid = document.getElementById('results-grid');
    const resultsNameEl = document.getElementById('results-name');
    const reservations = <?= json_encode($search_data, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_AMP) ?>;
    resultsNameEl.textContent = <?= json_encode($search_name) ?>;

    // 🟨 Generate reservation cards
    resultsGrid.innerHTML = reservations.map(res => {
      const statusLower = (res.status || '').toLowerCase();
      const colorMap = {
        'confirmed': 'bg-green-100/70',
        'denied': 'bg-red-100/70',
        'queued': 'bg-yellow-100/70',
        'for compliance': 'bg-orange-100/70'
      };
      const color = colorMap[statusLower] || 'bg-white/70';
      const locked = ['confirmed','denied'].includes(statusLower);

      return `
        <div class="rounded-xl p-4 shadow-md ${color} border border-white/20">
          <h4 class="font-semibold text-sm mb-2">${escapeHtml(res.name)}</h4>
          <p class="text-sm"><strong>ID:</strong> ${escapeHtml(res.reservation_id)}</p>
          <p class="text-sm"><strong>Date:</strong> ${escapeHtml(res.reservation_date)}</p>
          <p class="text-sm"><strong>Time:</strong> ${escapeHtml(res.start_time)} - ${escapeHtml(res.end_time || '')}</p>
          <p class="text-sm"><strong>Guests:</strong> ${escapeHtml(res.guests)}</p>
          <p class="text-sm"><strong>Table:</strong> ${escapeHtml(res.table_number || 'N/A')}</p>
          <p class="text-sm"><strong>Status:</strong> <span class="font-medium">${escapeHtml(res.status)}</span></p>
          <p class="text-sm mt-2"><strong>Notes:</strong> ${escapeHtml(res.notes || 'None')}</p>

          <div class="flex gap-2 mt-3">
            <button class="btn btn-sm bg-[#F7B32B] text-black font-semibold border-none hover:bg-[#d99a22] view-btn" data-id="${escapeAttr(res.reservation_id)}">
              <i data-lucide="eye" class="w-4 h-4 mr-1"></i> Details
            </button>

            ${locked
              ? `<button class="btn btn-sm bg-gray-300 text-white cursor-not-allowed opacity-70">Locked</button>`
              : `<button class="btn btn-sm bg-blue-600 text-white edit-btn" data-id="${escapeAttr(res.reservation_id)}">Edit</button>
                 <button class="btn btn-sm bg-red-600 text-white cancel-btn" data-id="${escapeAttr(res.reservation_id)}">Cancel</button>`}
          </div>
        </div>
      `;
    }).join('');

    // 🔹 Show results modal
    resultsModal.classList.remove('hidden');
    document.getElementById('close-results').addEventListener('click', () => resultsModal.classList.add('hidden'));

    // 🧠 Details Button logic
    document.querySelectorAll('.view-btn').forEach(btn => {
      btn.addEventListener('click', (ev) => {
        ev.stopPropagation();
        const id = ev.currentTarget.dataset.id;
        const reservation = reservations.find(r => String(r.reservation_id) === String(id));
        if (!reservation) return;

        const detailsModal = document.getElementById('details-modal');
        const content = document.getElementById('details-content');
        const loading = document.getElementById('loading-indicator');
        const currentStatusEl = document.getElementById('current-status');
        const actionButtons = document.getElementById('action-buttons');

        loading.classList.add('hidden');
        content.classList.remove('hidden');

        // 🧾 Fill in reservation info
        content.innerHTML = `
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <p><strong>Name:</strong> ${escapeHtml(reservation.name)}</p>
              <p><strong>ID:</strong> ${escapeHtml(reservation.reservation_id)}</p>
              <p><strong>Date:</strong> ${escapeHtml(reservation.reservation_date)}</p>
              <p><strong>Time:</strong> ${escapeHtml(reservation.start_time)} - ${escapeHtml(reservation.end_time || '')}</p>
              <p><strong>Guests:</strong> ${escapeHtml(reservation.guests)}</p>
              <p><strong>Table:</strong> ${escapeHtml(reservation.table_number || 'N/A')}</p>
            </div>
            <div>
              <p><strong>Created:</strong> ${escapeHtml(reservation.create_at || '')}</p>
              <p><strong>Last Updated:</strong> ${escapeHtml(reservation.modify_at || '')}</p>
              <p><strong>Special Requests:</strong> ${escapeHtml(reservation.special_requests || 'None')}</p>
              <p class="mt-2"><strong>Notes:</strong> ${escapeHtml(reservation.notes || 'None')}</p>
            </div>
          </div>
        `;

        currentStatusEl.textContent = reservation.status;
        if (['Confirmed','Denied'].includes(reservation.status)) {
          actionButtons.classList.add('hidden');
        } else {
          actionButtons.classList.remove('hidden');
        }

        // 🟢 Open modal
        detailsModal.classList.remove('hidden');
        document.body.classList.add('overflow-hidden');

        // ❌ Close button & ESC key
        const closeDetailsBtn = document.getElementById('close-details-modal');
        closeDetailsBtn.onclick = () => closeDetailsModal();

        document.addEventListener('keydown', (e) => {
          if (e.key === 'Escape' && !detailsModal.classList.contains('hidden')) {
            closeDetailsModal();
          }
        });

        // 🖱️ Click outside closes modal
        detailsModal.addEventListener('click', (e) => {
          if (e.target === detailsModal) closeDetailsModal();
        });

        function closeDetailsModal() {
          detailsModal.classList.add('hidden');
          document.body.classList.remove('overflow-hidden');
        }

        // ✅ SweetAlert fix - always on top
        document.querySelectorAll('.status-btn').forEach(button => {
          button.addEventListener('click', () => {
            const newStatus = button.dataset.status;

            Swal.fire({
              title: `Mark as ${newStatus}?`,
              text: "Are you sure you want to change this status?",
              icon: "warning",
              showCancelButton: true,
              confirmButtonText: "Yes, proceed",
              cancelButtonText: "Cancel",
              didOpen: (popup) => {
                popup.parentElement.style.zIndex = 999999; // Always above modal
              }
            }).then((result) => {
              if (result.isConfirmed) {
                Swal.fire({
                  icon: "success",
                  title: `Reservation ${newStatus}!`,
                  text: `Status updated to ${newStatus}.`,
                  didOpen: (popup) => {
                    popup.parentElement.style.zIndex = 999999; // keep above modal
                  }
                }).then(() => location.reload());
              }
            });
          });
        });
      });
    });

    if (window.lucide) lucide.createIcons();
  <?php endif; ?>

  // 🧱 Utility sanitizers
  function escapeHtml(s) {
    if (s === null || s === undefined) return '';
    return String(s)
      .replaceAll('&', '&amp;')
      .replaceAll('<', '&lt;')
      .replaceAll('>', '&gt;')
      .replaceAll('"', '&quot;')
      .replaceAll("'", '&#39;');
  }
  function escapeAttr(s) { return escapeHtml(s); }
});
</script>



<script>
let currentReservationId = null;
let currentReservationStatus = null;

document.addEventListener('DOMContentLoaded', () => {

  // ===========================
  // 🔹 VIEW BUTTON HANDLER
  // ===========================
  document.querySelectorAll('.view-btn').forEach(btn => {
    btn.addEventListener('click', () => {
      const reservationId = btn.dataset.id;
      currentReservationId = reservationId;
      showReservationDetails(reservationId);
    });
  });

  // ===========================
  // 🔹 FETCH + SHOW DETAILS
  // ===========================
  async function showReservationDetails(reservationId) {
    const modal = document.getElementById('view-modal');
    const loadingIndicator = document.getElementById('loading-indicator');
    const reservationContent = document.getElementById('reservation-content');

    modal.classList.remove('hidden');
    loadingIndicator.classList.remove('hidden');
    reservationContent.classList.add('hidden');

    try {
      const response = await fetch(`sub-modules/get_reservation_details.php?id=${reservationId}`);
      const data = await response.json();

      if (!data.success) throw new Error(data.error || 'Failed to load details');

      displayReservationContent(data.data);
      updateActionButtons(data.data.status);
    } catch (error) {
      Swal.fire({ icon: 'error', title: 'Error', text: error.message });
    }
  }

  // ===========================
  // 🔹 DISPLAY RESERVATION CONTENT
  // ===========================
  function displayReservationContent(reservation) {
    const contentDiv = document.getElementById('reservation-content');
    const loadingIndicator = document.getElementById('loading-indicator');

    const formattedDate = new Date(reservation.reservation_date).toLocaleDateString('en-US', {
      year: 'numeric', month: 'long', day: 'numeric'
    });
    const formattedStartTime = new Date('1970-01-01T' + reservation.reservation_time)
      .toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' });
    const formattedEndTime = new Date('1970-01-01T' + reservation.end_time)
      .toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' });

    contentDiv.innerHTML = `
      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div class="space-y-4">
          <div class="bg-gray-50 rounded-lg p-4 border border-gray-200">
            <h4 class="text-[#F7B32B] font-semibold mb-3 flex items-center">
              <i class="bx bx-user mr-2"></i> Customer Information
            </h4>
            <div class="space-y-2 text-gray-800">
              <div class="flex justify-between"><span>Name:</span><span>${reservation.customer_name}</span></div>
              <div class="flex justify-between"><span>Email:</span><span>${reservation.customer_email || 'N/A'}</span></div>
              <div class="flex justify-between"><span>Phone:</span><span>${reservation.customer_phone || 'N/A'}</span></div>
            </div>
          </div>

          <div class="bg-gray-50 rounded-lg p-4 border border-gray-200">
            <h4 class="text-[#F7B32B] font-semibold mb-3 flex items-center">
              <i class="bx bx-calendar mr-2"></i> Reservation Details
            </h4>
            <div class="space-y-2 text-gray-800">
              <div class="flex justify-between"><span>Date:</span><span>${formattedDate}</span></div>
              <div class="flex justify-between"><span>Time:</span><span>${formattedStartTime} - ${formattedEndTime}</span></div>
              <div class="flex justify-between"><span>Guests:</span><span>${reservation.num_guests} people</span></div>
              <div class="flex justify-between"><span>Table:</span><span>${reservation.table_number || 'N/A'}</span></div>
              <div class="flex justify-between"><span>Type:</span><span>${reservation.type || 'N/A'}</span></div>
            </div>
          </div>
        </div>

        <div class="space-y-4">
          <div class="bg-gray-50 rounded-lg p-4 border border-gray-200">
            <h4 class="text-[#F7B32B] font-semibold mb-3 flex items-center">
              <i class="bx bx-note mr-2"></i> Additional Information
            </h4>
            <div class="space-y-2 text-gray-800">
              <div class="flex justify-between"><span>Reservation ID:</span><span>#${reservation.reservation_id}</span></div>
              <div class="flex justify-between"><span>Created:</span><span>${new Date(reservation.create_at).toLocaleString()}</span></div>
              ${reservation.modify_at ? `<div class="flex justify-between"><span>Updated:</span><span>${new Date(reservation.modify_at).toLocaleString()}</span></div>` : ''}
            </div>
          </div>

          ${reservation.special_requests ? `<div class="bg-gray-50 rounded-lg p-4 border border-gray-200"><h4 class="text-[#F7B32B] font-semibold mb-3 flex items-center"><i class="bx bx-message-detail mr-2"></i> Special Requests</h4><p class="text-sm italic">"${reservation.special_requests}"</p></div>` : ''}
          ${reservation.note ? `<div class="bg-gray-50 rounded-lg p-4 border border-gray-200"><h4 class="text-[#F7B32B] font-semibold mb-3 flex items-center"><i class="bx bx-note mr-2"></i> Internal Notes</h4><p class="text-sm">${reservation.note}</p></div>` : ''}
        </div>
      </div>
    `;

    updateStatusDisplay(reservation.status);
    loadingIndicator.classList.add('hidden');
    contentDiv.classList.remove('hidden');
  }

  // ===========================
  // 🔹 STATUS BADGE COLOR
  // ===========================
  function updateStatusDisplay(status) {
    const el = document.getElementById('current-status');
    currentReservationStatus = status;
    el.className = 'px-3 py-1 rounded-full text-sm font-semibold';
    el.textContent = status;

    const colors = {
      Confirmed: ['bg-green-100', 'text-green-800'],
      Denied: ['bg-red-100', 'text-red-800'],
      'For Compliance': ['bg-orange-100', 'text-orange-800'],
      Queued: ['bg-yellow-100', 'text-yellow-800'],
      default: ['bg-gray-200', 'text-gray-800']
    };
    el.classList.add(...(colors[status] || colors.default));
  }

  // ===========================
  // 🔹 ACTION BUTTON CONTROL
  // ===========================
  function updateActionButtons(status) {
    const actionButtons = document.getElementById('action-buttons');
    const buttons = document.querySelectorAll('.status-btn');

    // Lock finalized statuses
    if (['Confirmed', 'Denied'].includes(status)) {
      actionButtons.classList.add('hidden');
      buttons.forEach(btn => btn.disabled = true);
    }
    // For Compliance - only allow Confirmed or Denied
    else if (status === 'For Compliance') {
      actionButtons.classList.remove('hidden');
      buttons.forEach(btn => {
        const s = btn.dataset.status;
        btn.disabled = !['Confirmed', 'Denied'].includes(s);
      });
    }
    // Otherwise allow all
    else {
      actionButtons.classList.remove('hidden');
      buttons.forEach(btn => btn.disabled = false);
    }
  }

  // ===========================
  // 🔹 STATUS CHANGE HANDLER
  // ===========================
  document.querySelectorAll('.status-btn').forEach(button => {
    button.addEventListener('click', async () => {
      if (button.disabled) return;

      const status = button.dataset.status;
      const currentStatus = currentReservationStatus;

      // Prevent altering confirmed/denied
      if (['Confirmed', 'Denied'].includes(currentStatus)) {
        Swal.fire({ icon: 'warning', title: 'Action Not Allowed', text: `This reservation has already been ${currentStatus.toLowerCase()}.` });
        return;
      }

      // From "For Compliance" can only move to confirmed/denied
      if (currentStatus === 'For Compliance' && !['Confirmed', 'Denied'].includes(status)) {
        Swal.fire({ icon: 'warning', title: 'Invalid Transition', text: 'Only Confirmed or Denied actions are allowed from "For Compliance".' });
        return;
      }

      let complianceNote = '';
      if (status === 'For Compliance') {
        const { value: note } = await Swal.fire({
          title: 'Compliance Note',
          input: 'textarea',
          inputPlaceholder: 'Enter compliance details...',
          showCancelButton: true
        });
        if (!note) return Swal.fire({ icon: 'info', title: 'Cancelled', text: 'Compliance note required.' });
        complianceNote = note;
      }

      const confirmAction = await Swal.fire({
        title: `Change status to "${status}"?`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Yes, update it!'
      });
      if (!confirmAction.isConfirmed) return;

      const formData = new FormData();
      formData.append('reservation_id', currentReservationId);
      formData.append('status', status);
      if (complianceNote) formData.append('compliance', complianceNote);

      try {
        const response = await fetch('sub-modules/update_reservation_status.php', {
          method: 'POST',
          body: formData
        });
        const data = await response.json();
        if (data.success) {
          Swal.fire({ icon: 'success', title: 'Updated!', text: data.message || 'Reservation updated.', timer: 1800, showConfirmButton: false })
            .then(() => location.reload());
        } else {
          Swal.fire({ icon: 'error', title: 'Error', text: data.error });
        }
      } catch {
        Swal.fire({ icon: 'error', title: 'Connection Error', text: 'Could not reach server.' });
      }
    });
  });

  // ===========================
  // 🔹 CLOSE MODAL
  // ===========================
  document.querySelectorAll('#close-modal, #close-modal-footer').forEach(el => {
    el.addEventListener('click', closeModal);
  });

  document.getElementById('view-modal').addEventListener('click', e => {
    if (e.target === e.currentTarget) closeModal();
  });

  function closeModal() {
    document.getElementById('view-modal').classList.add('hidden');
    currentReservationId = null;
    currentReservationStatus = null;
  }

});
</script>


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


<script>
document.getElementById('size').addEventListener('input', function() {
  if (this.value > 10) this.value = 10;
  if (this.value < 1) this.value = 1;
});
document.getElementById('contact').addEventListener('input', function() {
  this.value = this.value.replace(/\D/g, ''); // only digits
  if (this.value.length > 11) {
    this.value = this.value.slice(0, 11); // trim to 11 digits
  }
});
</script>

<script>
function validateAmount(input) {
  const value = parseFloat(input.value || 0);
  const maxAmount = 100000;
  const warningElement = document.getElementById('amount-warning');
  
  if (value > maxAmount) {
    input.classList.add('border-red-500', 'focus:border-red-500');
    warningElement.classList.remove('hidden');
    input.setCustomValidity(`Amount cannot exceed ₱${maxAmount.toLocaleString()}`);
  } else {
    input.classList.remove('border-red-500', 'focus:border-red-500');
    warningElement.classList.add('hidden');
    input.setCustomValidity('');
  }
}

// Form submission validation
document.getElementById('reservation-form').addEventListener('submit', function(e) {
  const amountInput = document.getElementById('amount');
  const amount = parseFloat(amountInput.value);
  
  if (amount > 100000) {
    e.preventDefault();
    alert('❌ Amount cannot exceed ₱100,000.00. Please adjust the amount.');
    amountInput.focus();
    amountInput.select();
  }
});
</script>


<script>
document.addEventListener('DOMContentLoaded', () => {
  const form = document.getElementById('reservation-form');

  form.addEventListener('submit', async (e) => {
    e.preventDefault();

    const formData = new FormData(form);

    Swal.fire({
      title: 'Processing...',
      text: 'Creating your reservation, please wait.',
      allowOutsideClick: false,
      didOpen: () => Swal.showLoading(),
    });

    try {
      const response = await fetch('create_reservation.php', {
        method: 'POST',
        body: formData
      });
      const result = await response.json();

      if (result.success) {
        Swal.fire({
          icon: 'success',
          title: 'Reservation Created!',
          html: `
            <b>Name:</b> ${result.details.name}<br>
            <b>Date:</b> ${result.details.date}<br>
            <b>Time:</b> ${result.details.start_time}<br>
            <b>Table:</b> #${result.details.table_id}<br>
            <b>Party Size:</b> ${result.details.size}
          `,
          confirmButtonColor: '#001f54',
        }).then(() => {
          document.getElementById('reservations-modal').close();
          form.reset();
          location.reload();
        });
      } else {
        Swal.fire({
          icon: 'error',
          title: 'Failed to Create Reservation',
          text: result.error || 'Something went wrong. Please try again.',
          confirmButtonColor: '#d33',
        });
      }
    } catch (error) {
      Swal.fire({
        icon: 'error',
        title: 'Network Error',
        text: 'Unable to connect to the server. Please try again later.',
        confirmButtonColor: '#d33',
      });
      console.error(error);
    }
  });
});

</script>

  <script src="../JavaScript/calendar_crude.js"></script>
  <script src="../JavaScript/sidebar.js"></script>
  <script src="../JavaScript/soliera.js"></script>




</body>
</html>