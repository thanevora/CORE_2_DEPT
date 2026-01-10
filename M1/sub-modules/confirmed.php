<?php
include("../conn_M1.php");

// ==========================
// RESERVATION COUNT METRICS
// ==========================

// 👉 Today’s Reservations
$sql = "SELECT COUNT(*) AS total FROM reservations WHERE DATE(reservation_date) = CURDATE()";
$todayCount = $connections->query($sql)->fetch_assoc()['total'] ?? 0;

// 👉 This Week’s Reservations (Monday to Sunday — mode 1)
$sql = "SELECT COUNT(*) AS total FROM reservations 
        WHERE YEARWEEK(reservation_date, 1) = YEARWEEK(CURDATE(), 1)";
$weekCount = $connections->query($sql)->fetch_assoc()['total'] ?? 0;

// 👉 This Month’s Reservations
$sql = "SELECT COUNT(*) AS total FROM reservations 
        WHERE MONTH(reservation_date) = MONTH(CURDATE()) 
        AND YEAR(reservation_date) = YEAR(CURDATE())";
$monthCount = $connections->query($sql)->fetch_assoc()['total'] ?? 0;

// 👉 Completion Rate: Approved ÷ Total
$sql = "SELECT 
            (SELECT COUNT(*) FROM reservations WHERE status = 'Approved') AS approved,
            (SELECT COUNT(*) FROM reservations) AS total";
$res = $connections->query($sql)->fetch_assoc();
$completionRate = ($res['total'] > 0) ? round(($res['approved'] / $res['total']) * 100) : 0;

// ==============================
// RESERVATION STATUS DISTRIBUTION
// ==============================

$statusQuery = "
    SELECT 
        (SELECT COUNT(*) FROM reservations) AS total_reservations,
        (SELECT COUNT(*) FROM reservations WHERE status = 'Pending') AS Pending,
        (SELECT COUNT(*) FROM reservations WHERE status = 'Confirmed') AS Confirmed,
        (SELECT COUNT(*) FROM reservations WHERE status = 'Cancelled') AS Cancelled
";

$statusResult = mysqli_query($connections, $statusQuery);
if (!$statusResult) {
    die("Count query failed: " . mysqli_error($connections));
}

$statusCounts = mysqli_fetch_assoc($statusResult);
$totalReservations = $statusCounts['total_reservations'];
$pendingCount     = $statusCounts['Pending'];
$confirmedCount   = $statusCounts['Confirmed'];
$cancelledCount   = $statusCounts['Cancelled'];

// ==========================
// FETCH RECENT RESERVATIONS
// ==========================

$sql = "SELECT 
            reservation_id, name, contact, reservation_date, 
            start_time, end_time, size, status, 
            request, type, created_at, modify_at, note 
        FROM reservations 
        ORDER BY reservation_date DESC, start_time DESC 
        LIMIT 11";

$recentReservations = $connections->query($sql);
if (!$recentReservations) {
    die("SQL Error: " . $connections->error);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reservations Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://unpkg.com/lucide@latest"></script>
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.js"></script>
  <!-- SweetAlert2 CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
<!-- SweetAlert2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../CSS/calendar.css">

    
</head>
<body class="bg-gray-50">
<div class="p-6 max-w-7xl mx-auto animate-fade-in">
    <!-- Header Section -->
    <div class="flex justify-between items-center mb-8">
        <div>
            <h1 class="text-3xl font-bold text-slate-800 flex items-center gap-3">
                <i data-lucide="check-circle" class="w-8 h-8 text-emerald-500"></i>
                <span class="bg-gradient-to-r from-blue-600 to-blue-800 bg-clip-text text-transparent">
                    Confirmed Reservations
                </span>
            </h1>
        </div>
        <div class="flex gap-3">
            
            <button class="btn btn-sm bg-gradient-to-r from-slate-600 to-slate-700 text-white hover:from-slate-700 hover:to-slate-800 shadow hover:shadow-md transition-all">
                <i data-lucide="download" class="w-4 h-4 mr-2"></i> Export
            </button>
        </div>
    </div>

<!-- Stats Cards Grid -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-5 mb-8">
    <!-- Total Confirmed -->
    <div class="relative group">
        <!-- Glow effect on hover -->
        <div class="absolute inset-0 bg-blue-500 rounded-xl opacity-0 group-hover:opacity-10 blur-md transition-all duration-500"></div>
        
        <!-- Main card -->
        <div class="card-hover bg-gradient-to-br from-blue-600 to-blue-800 p-5 rounded-xl shadow-lg border border-blue-200/50 hover:border-blue-300 transition-all duration-300 hover:-translate-y-1 hover:shadow-xl relative z-10 overflow-hidden">
            <!-- Shimmer overlay -->
            <div class="absolute inset-0 bg-gradient-to-br from-white/10 via-white/5 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-500"></div>
            
            <div class="flex items-start justify-between relative">
                <div>
                    <p class="text-sm font-medium text-blue-100/90 group-hover:text-blue-50 flex items-center gap-1 transition-colors">
                        <i data-lucide="check-circle" class="w-4 h-4 transition-transform group-hover:scale-110"></i>
                        Total Confirmed
                    </p>
                    <h3 class="text-3xl font-bold mt-1 text-white transition-all duration-300 group-hover:scale-[1.02] origin-left"><?= $confirmedCount ?></h3>
                    <div class="mt-2 pt-2 border-t border-blue-400/20 group-hover:border-blue-400/40 transition-all">
                        <p class="text-xs text-blue-200/80 group-hover:text-blue-200 flex items-center transition-all">
                            <span class="inline-block w-2 h-2 rounded-full bg-green-300 mr-1 group-hover:animate-pulse"></span>
                            All-time reservations
                        </p>
                    </div>
                </div>
                <div class="p-3 rounded-lg bg-blue-100/20 text-white group-hover:bg-blue-100/30 group-hover:rotate-12 transition-all duration-300">
                    <i data-lucide="check-circle" class="w-6 h-6 group-hover:scale-110 transition-transform"></i>
                </div>
            </div>
            
            <!-- Animated underline -->
            <div class="absolute bottom-0 left-0 right-0 h-1 bg-blue-400/30 group-hover:bg-blue-400/60 transition-all duration-500 origin-left scale-x-0 group-hover:scale-x-100"></div>
        </div>
    </div>

    <!-- Today's Confirmed -->
    <div class="relative group">
        <div class="absolute inset-0 bg-emerald-500 rounded-xl opacity-0 group-hover:opacity-10 blur-md transition-all duration-500"></div>
        <div class="card-hover bg-gradient-to-br from-emerald-500 to-emerald-700 p-5 rounded-xl shadow-lg border border-emerald-200/50 hover:border-emerald-300 transition-all duration-300 hover:-translate-y-1 hover:shadow-xl relative z-10 overflow-hidden">
            <div class="absolute inset-0 bg-gradient-to-br from-white/10 via-white/5 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-500"></div>
            <div class="flex items-start justify-between relative">
                <div>
                    <p class="text-sm font-medium text-emerald-100/90 group-hover:text-emerald-50 flex items-center gap-1 transition-colors">
                        <i data-lucide="calendar" class="w-4 h-4 transition-transform group-hover:scale-110"></i>
                        Today
                    </p>
                    <h3 class="text-3xl font-bold mt-1 text-white transition-all duration-300 group-hover:scale-[1.02] origin-left"><?= $todayCount ?></h3>
                    <div class="mt-2 pt-2 border-t border-emerald-400/20 group-hover:border-emerald-400/40 transition-all">
                        <p class="text-xs text-emerald-200/80 group-hover:text-emerald-200 flex items-center transition-all">
                            <span class="inline-block w-2 h-2 rounded-full bg-white mr-1 group-hover:animate-pulse"></span>
                            <?= date('M j, Y') ?>
                        </p>
                    </div>
                </div>
                <div class="p-3 rounded-lg bg-emerald-100/20 text-white group-hover:bg-emerald-100/30 group-hover:rotate-12 transition-all duration-300">
                    <i data-lucide="calendar" class="w-6 h-6 group-hover:scale-110 transition-transform"></i>
                </div>
            </div>
            <div class="absolute bottom-0 left-0 right-0 h-1 bg-emerald-400/30 group-hover:bg-emerald-400/60 transition-all duration-500 origin-left scale-x-0 group-hover:scale-x-100"></div>
        </div>
    </div>

    <!-- This Week -->
    <div class="relative group">
        <div class="absolute inset-0 bg-amber-500 rounded-xl opacity-0 group-hover:opacity-10 blur-md transition-all duration-500"></div>
        <div class="card-hover bg-gradient-to-br from-amber-500 to-amber-700 p-5 rounded-xl shadow-lg border border-amber-200/50 hover:border-amber-300 transition-all duration-300 hover:-translate-y-1 hover:shadow-xl relative z-10 overflow-hidden">
            <div class="absolute inset-0 bg-gradient-to-br from-white/10 via-white/5 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-500"></div>
            <div class="flex items-start justify-between relative">
                <div>
                    <p class="text-sm font-medium text-amber-100/90 group-hover:text-amber-50 flex items-center gap-1 transition-colors">
                        <i data-lucide="calendar-days" class="w-4 h-4 transition-transform group-hover:scale-110"></i>
                        This Week
                    </p>
                    <h3 class="text-3xl font-bold mt-1 text-white transition-all duration-300 group-hover:scale-[1.02] origin-left"><?= $weekCount ?></h3>
                    <div class="mt-2 pt-2 border-t border-amber-400/20 group-hover:border-amber-400/40 transition-all">
                        <p class="text-xs text-amber-200/80 group-hover:text-amber-200 flex items-center transition-all">
                            <span class="inline-block w-2 h-2 rounded-full bg-white mr-1 group-hover:animate-pulse"></span>
                            Week <?= date('W') ?>
                        </p>
                    </div>
                </div>
                <div class="p-3 rounded-lg bg-amber-100/20 text-white group-hover:bg-amber-100/30 group-hover:rotate-12 transition-all duration-300">
                    <i data-lucide="calendar-days" class="w-6 h-6 group-hover:scale-110 transition-transform"></i>
                </div>
            </div>
            <div class="absolute bottom-0 left-0 right-0 h-1 bg-amber-400/30 group-hover:bg-amber-400/60 transition-all duration-500 origin-left scale-x-0 group-hover:scale-x-100"></div>
        </div>
    </div>

    <!-- This Month -->
    <div class="relative group">
        <div class="absolute inset-0 bg-cyan-500 rounded-xl opacity-0 group-hover:opacity-10 blur-md transition-all duration-500"></div>
        <div class="card-hover bg-gradient-to-br from-cyan-500 to-cyan-700 p-5 rounded-xl shadow-lg border border-cyan-200/50 hover:border-cyan-300 transition-all duration-300 hover:-translate-y-1 hover:shadow-xl relative z-10 overflow-hidden">
            <div class="absolute inset-0 bg-gradient-to-br from-white/10 via-white/5 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-500"></div>
            <div class="flex items-start justify-between relative">
                <div>
                    <p class="text-sm font-medium text-cyan-100/90 group-hover:text-cyan-50 flex items-center gap-1 transition-colors">
                        <i data-lucide="calendar-check-2" class="w-4 h-4 transition-transform group-hover:scale-110"></i>
                        This Month
                    </p>
                    <h3 class="text-3xl font-bold mt-1 text-white transition-all duration-300 group-hover:scale-[1.02] origin-left"><?= $monthCount ?></h3>
                    <div class="mt-2 pt-2 border-t border-cyan-400/20 group-hover:border-cyan-400/40 transition-all">
                        <p class="text-xs text-cyan-200/80 group-hover:text-cyan-200 flex items-center transition-all">
                            <span class="inline-block w-2 h-2 rounded-full bg-white mr-1 group-hover:animate-pulse"></span>
                            <?= date('F Y') ?>
                        </p>
                    </div>
                </div>
                <div class="p-3 rounded-lg bg-cyan-100/20 text-white group-hover:bg-cyan-100/30 group-hover:rotate-12 transition-all duration-300">
                    <i data-lucide="calendar-check-2" class="w-6 h-6 group-hover:scale-110 transition-transform"></i>
                </div>
            </div>
            <div class="absolute bottom-0 left-0 right-0 h-1 bg-cyan-400/30 group-hover:bg-cyan-400/60 transition-all duration-500 origin-left scale-x-0 group-hover:scale-x-100"></div>
        </div>
    </div>

    <!-- Completion Rate -->
    <div class="relative group">
        <div class="absolute inset-0 bg-indigo-500 rounded-xl opacity-0 group-hover:opacity-10 blur-md transition-all duration-500"></div>
        <div class="card-hover bg-gradient-to-br from-indigo-500 to-indigo-700 p-5 rounded-xl shadow-lg border border-indigo-200/50 hover:border-indigo-300 transition-all duration-300 hover:-translate-y-1 hover:shadow-xl relative z-10 overflow-hidden">
            <div class="absolute inset-0 bg-gradient-to-br from-white/10 via-white/5 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-500"></div>
            <div class="flex items-start justify-between relative">
                <div>
                    <p class="text-sm font-medium text-indigo-100/90 group-hover:text-indigo-50 flex items-center gap-1 transition-colors">
                        <i data-lucide="trending-up" class="w-4 h-4 transition-transform group-hover:scale-110"></i>
                        Completion Rate
                    </p>
                    <h3 class="text-3xl font-bold mt-1 text-white transition-all duration-300 group-hover:scale-[1.02] origin-left"><?= $completionRate ?>%</h3>
                    <div class="mt-2 pt-2 border-t border-indigo-400/20 group-hover:border-indigo-400/40 transition-all">
                        <p class="text-xs text-indigo-200/80 group-hover:text-indigo-200 flex items-center transition-all">
                            <span class="inline-block w-2 h-2 rounded-full <?= $completionRate > 90 ? 'bg-green-300' : ($completionRate > 75 ? 'bg-amber-300' : 'bg-rose-300') ?> mr-1 group-hover:animate-pulse"></span>
                            <?= $completionRate > 90 ? 'Excellent' : ($completionRate > 75 ? 'Good' : 'Needs improvement') ?>
                        </p>
                    </div>
                </div>
                <div class="p-3 rounded-lg bg-indigo-100/20 text-white group-hover:bg-indigo-100/30 group-hover:rotate-12 transition-all duration-300">
                    <i data-lucide="trending-up" class="w-6 h-6 group-hover:scale-110 transition-transform"></i>
                </div>
            </div>
            <div class="absolute bottom-0 left-0 right-0 h-1 bg-indigo-400/30 group-hover:bg-indigo-400/60 transition-all duration-500 origin-left scale-x-0 group-hover:scale-x-100"></div>
        </div>
    </div>
</div>

    <!-- Main Table -->
    <div class="bg-white rounded-xl shadow-lg overflow-hidden border border-gray-100 transition-all duration-300 hover:shadow-xl">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gradient-to-r from-blue-50 to-blue-100">
                    <tr>
                        <th class="px-6 py-4 text-left text-sm font-semibold text-blue-800 uppercase tracking-wider">
                            <div class="flex items-center gap-2">
                                <i data-lucide="user" class="w-4 h-4 text-blue-600"></i>
                                Guest
                            </div>
                        </th>
                        <th class="px-6 py-4 text-left text-sm font-semibold text-blue-800 uppercase tracking-wider">
                            <div class="flex items-center gap-2">
                                <i data-lucide="calendar" class="w-4 h-4 text-blue-600"></i>
                                Date/Time
                            </div>
                        </th>
                        <th class="px-6 py-4 text-left text-sm font-semibold text-blue-800 uppercase tracking-wider">
                            <div class="flex items-center gap-2">
                                <i data-lucide="users" class="w-4 h-4 text-blue-600"></i>
                                Party Size
                            </div>
                        </th>
                        <th class="px-6 py-4 text-left text-sm font-semibold text-blue-800 uppercase tracking-wider">
                            <div class="flex items-center gap-2">
                                <i data-lucide="utensils" class="w-4 h-4 text-blue-600"></i>
                                Type
                            </div>
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-blue-700 uppercase tracking-wider">
                            <div class="flex items-center gap-2">
                                <i data-lucide="utensils" class="w-4 h-4 text-blue-500"></i>
                                <span>Status</span>
                            </div>
                        </th>
                        <th class="px-6 py-4 text-left text-sm font-semibold text-blue-800 uppercase tracking-wider">
                            <div class="flex items-center gap-2">
                                <i data-lucide="badge-check" class="w-4 h-4 text-blue-600"></i>
                                Actions
                            </div>
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                <?php while ($reservation = mysqli_fetch_assoc($recentReservations)) : ?>
                    <tr class="hover:bg-blue-50/50 transition-colors duration-200">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <div class="text-sm font-medium text-slate-800"><?= $reservation['name'] ?></div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-medium text-slate-800"><?= date('M j, Y', strtotime($reservation['reservation_date'])) ?></div>
                            <div class="text-sm text-slate-500"><?= $reservation['start_time'] ?> - <?= $reservation['end_time'] ?></div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-3 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800 status-badge">
                                <?= $reservation['size'] ?> people
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-3 py-1 text-xs font-semibold rounded-full bg-indigo-100 text-indigo-800 status-badge">
                                <?= ucfirst($reservation['type']) ?>
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                                    <?= $reservation['status'] === 'confirmed' ? 'bg-green-100 text-green-800' : '' ?>
                                    <?= $reservation['status'] === 'pending' ? 'bg-yellow-100 text-yellow-800' : '' ?>
                                    <?= $reservation['status'] === 'cancelled' ? 'bg-red-100 text-red-800' : '' ?>
                                    <?= $reservation['status'] === 'completed' ? 'bg-blue-100 text-blue-800' : '' ?>">
                            <i data-lucide="<?= $reservation['status'] === 'confirmed' ? 'check-circle' : 
                                            ($reservation['status'] === 'pending' ? 'clock' : 
                                            ($reservation['status'] === 'cancelled' ? 'x-circle' : 'calendar-check')) ?>" 
                            class="w-3 h-3 mr-1"></i>
                            <?= ucfirst($reservation['status']) ?>
                        </span>
                    </td>
                        <td class="px-6 py-4 whitespace-nowrap">
    <div class="flex gap-2">
    <!-- View Button -->
<div class="tooltip" data-tip="View Details">
    <button onclick="handleView(this)" data-id="<?= $row['reservation_id'] ?>" class="btn btn-xs btn-square btn-ghost bg-blue-50 text-blue-600 hover:bg-blue-100 hover:text-blue-700 transition-all duration-200 group relative overflow-hidden">
        <i data-lucide="eye" class="w-4 h-4 transition-transform group-hover:scale-110"></i>
        <span class="absolute inset-0 bg-blue-500 opacity-0 group-hover:opacity-10 transition-opacity duration-300"></span>
    </button>
</div>


    <!-- Edit Button -->
    <div class="tooltip" data-tip="Edit Reservation">
        <button onclick="handleEdit(this)" data-id="<?= $row['reservation_id'] ?>" class="btn btn-xs btn-square btn-ghost bg-amber-50 text-amber-600 hover:bg-amber-100 hover:text-amber-700 transition-all duration-200 group relative overflow-hidden">
            <i data-lucide="edit" class="w-4 h-4 transition-transform group-hover:scale-110"></i>
            <span class="absolute inset-0 bg-amber-500 opacity-0 group-hover:opacity-10 transition-opacity duration-300"></span>
        </button>
    </div>

    <!-- Cancel Button -->
    <div class="tooltip" data-tip="Cancel Reservation">
        <button onclick="handleCancel(this)" data-id="<?= $row['reservation_id'] ?>" class="btn btn-xs btn-square btn-ghost bg-rose-50 text-rose-600 hover:bg-rose-100 hover:text-rose-700 transition-all duration-200 group relative overflow-hidden">
            <i data-lucide="x" class="w-4 h-4 transition-transform group-hover:scale-110"></i>
            <span class="absolute inset-0 bg-rose-500 opacity-0 group-hover:opacity-10 transition-opacity duration-300"></span>
        </button>
    </div>
</div>

</td>


   

                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>


<!-- Native dialog modal styled with Tailwind -->
<dialog id="view-modal" class="modal">
  <div class="modal-box">
    <h3 class="font-bold text-lg mb-4">Reservation Details</h3>
    <div id="view-content" class="space-y-2 text-sm text-gray-700"></div>
    <div class="modal-action">
      <form method="dialog">
        <button class="btn btn-sm">Close</button>
      </form>
    </div>
  </div>
</dialog>


    <!-- Pagination -->
    <div class="mt-6 flex justify-center">
        <div class="join">
            <button class="join-item btn btn-sm bg-white border border-gray-200 hover:bg-gray-50">«</button>
            <button class="join-item btn btn-sm bg-blue-600 text-white border-blue-600">1</button>
            <button class="join-item btn btn-sm bg-white border border-gray-200 hover:bg-gray-50">2</button>
            <button class="join-item btn btn-sm bg-white border border-gray-200 hover:bg-gray-50">3</button>
            <button class="join-item btn btn-sm bg-white border border-gray-200 hover:bg-gray-50">»</button>
        </div>
    </div>
</div>

<script>
    lucide.createIcons();
    
    // Animation for stats cards on load
    document.querySelectorAll('.card-hover').forEach((card, index) => {
        card.style.animationDelay = `${index * 100}ms`;
        card.classList.add('animate-fade-in');
    });
</script>

<script src="../../JavaScript/calendar_crude.js"></script>
</body>
</html>