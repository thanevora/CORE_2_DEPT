<?php
include("../conn_M1.php");



// Today
$sql = "SELECT COUNT(*) as total FROM reservations WHERE DATE(reservation_date) = CURDATE()";
$todayCount = $connections->query($sql)->fetch_assoc()['total'] ?? 0;

// This Week (Sunday to Saturday)
$sql = "SELECT COUNT(*) as total FROM reservations 
        WHERE YEARWEEK(reservation_date, 1) = YEARWEEK(CURDATE(), 1)";
$weekCount = $connections->query($sql)->fetch_assoc()['total'] ?? 0;

// This Month
$sql = "SELECT COUNT(*) as total FROM reservations 
        WHERE MONTH(reservation_date) = MONTH(CURDATE()) 
        AND YEAR(reservation_date) = YEAR(CURDATE())";
$monthCount = $connections->query($sql)->fetch_assoc()['total'] ?? 0;

// Completion Rate (Approved ÷ Total)
$sql = "SELECT 
            (SELECT COUNT(*) FROM reservations WHERE status = 'Approved') as approved,
            (SELECT COUNT(*) FROM reservations) as total";
$res = $connections->query($sql)->fetch_assoc();
$completionRate = ($res['total'] > 0) ? round(($res['approved'] / $res['total']) * 100) : 0;



$sql = "SELECT reservation_id, name, contact, reservation_date, start_time, end_time, size, status, request, type, created_at, modify_at, note 
        FROM reservations 
        ORDER BY reservation_date DESC, start_time DESC 
        LIMIT 11";

$result_sql = $connections->query($sql);
if (!$result_sql) {
  die("SQL Error: " . $connection->error);
}


$query = "SELECT 
        (SELECT COUNT(*) FROM reservations) AS total_reservations,
        (SELECT COUNT(*) FROM reservations WHERE status = 'Pending') AS Pending,
        (SELECT COUNT(*) FROM reservations WHERE status = 'Confirmed') AS Confirmed,
        (SELECT COUNT(*) FROM reservations WHERE status = 'Cancelled') AS Cancelled
";

$result = mysqli_query($connections, $query);

if (!$result) {
    die("Count query failed: " . mysqli_error($connection));
}

// Fetch the counts
$row = mysqli_fetch_assoc($result);
$total_reservations_count = $row['total_reservations'];
$pending_count = $row['Pending'];
$confirmed_count = $row['Confirmed'];
$cancelled_count = $row['Cancelled'];

// Query to fetch all reservations
$query = "SELECT * FROM `reservations`";
$result = mysqli_query($connections, $query);

if (!$result) {
    die("Fetch query failed: " . mysqli_error($connection));
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cancelled Reservations</title>
    <link href="https://cdn.jsdelivr.net/npm/daisyui@3.9.4/dist/full.css" rel="stylesheet" type="text/css" />
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .animate-fade-in { animation: fadeIn 0.5s ease-out forwards; }
        .card-hover {
            transition: all 0.3s ease;
        }
        .card-hover:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1);
        }
        .status-badge {
            transition: all 0.2s ease;
        }
        .status-badge:hover {
            transform: scale(1.05);
        }
        .reason-chip {
            transition: all 0.2s ease;
        }
        .reason-chip:hover {
            transform: translateY(-1px);
            box-shadow: 0 2px 5px -1px rgba(0, 0, 0, 0.1);
        }
    </style>
</head>
<body class="bg-gray-50">
<div class="p-6 max-w-7xl mx-auto animate-fade-in">
    <!-- Header Section -->
    <div class="flex justify-between items-center mb-8">
        <div>
            <h1 class="text-3xl font-bold text-slate-800 flex items-center gap-3">
                <i data-lucide="x-circle" class="w-8 h-8 text-rose-500"></i>
                <span class="bg-gradient-to-r from-rose-600 to-rose-800 bg-clip-text text-transparent">
                    Cancelled Reservations
                </span>
            </h1>
            <p class="text-sm text-slate-500 mt-1">Review cancelled bookings and no-shows</p>
        </div>
        <div class="flex gap-3">
            <button class="btn btn-sm bg-gradient-to-r from-rose-600 to-rose-700 text-white hover:from-rose-700 hover:to-rose-800 shadow-md hover:shadow-lg transition-all">
                <i data-lucide="download" class="w-4 h-4 mr-2"></i> Export
            </button>
        </div>
    </div>

    <!-- Stats Cards Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-5 mb-8">
        <!-- Total Cancelled -->
        <div class="card-hover bg-gradient-to-br from-rose-600 to-rose-800 p-5 rounded-xl shadow-lg border border-rose-200 hover:border-rose-300">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-sm font-medium text-rose-100 flex items-center gap-1">
                        <i data-lucide="x-circle" class="w-4 h-4"></i>
                        Total Cancelled
                    </p>
                    <h3 class="text-3xl font-bold mt-1 text-white"><?= $cancelled_count ?></h3>
                    <p class="text-xs text-rose-200 mt-2 flex items-center">
                        <span class="inline-block w-2 h-2 rounded-full bg-white mr-1"></span>
                        All-time cancellations
                    </p>
                </div>
                <div class="p-3 rounded-lg bg-rose-100/20 text-white">
                    <i data-lucide="x-circle" class="w-6 h-6"></i>
                </div>
            </div>
        </div>

        <!-- This Month -->
        <div class="card-hover bg-gradient-to-br from-amber-500 to-amber-700 p-5 rounded-xl shadow-lg border border-amber-200 hover:border-amber-300">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-sm font-medium text-amber-100 flex items-center gap-1">
                        <i data-lucide="calendar" class="w-4 h-4"></i>
                        This Month
                    </p>
                    <h3 class="text-3xl font-bold mt-1 text-white">15</h3>
                    <p class="text-xs text-amber-200 mt-2 flex items-center">
                        <span class="inline-block w-2 h-2 rounded-full bg-white mr-1"></span>
                        <?= date('F Y') ?>
                    </p>
                </div>
                <div class="p-3 rounded-lg bg-amber-100/20 text-white">
                    <i data-lucide="calendar" class="w-6 h-6"></i>
                </div>
            </div>
        </div>

        <!-- No-Shows -->
        <div class="card-hover bg-gradient-to-br from-blue-600 to-blue-800 p-5 rounded-xl shadow-lg border border-blue-200 hover:border-blue-300">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-sm font-medium text-blue-100 flex items-center gap-1">
                        <i data-lucide="user-x" class="w-4 h-4"></i>
                        No-Shows
                    </p>
                    <h3 class="text-3xl font-bold mt-1 text-white">3</h3>
                    <p class="text-xs text-blue-200 mt-2 flex items-center">
                        <span class="inline-block w-2 h-2 rounded-full bg-rose-300 mr-1"></span>
                        Missed appointments
                    </p>
                </div>
                <div class="p-3 rounded-lg bg-blue-100/20 text-white">
                    <i data-lucide="user-x" class="w-6 h-6"></i>
                </div>
            </div>
        </div>

        <!-- Cancellation Rate -->
        <div class="card-hover bg-gradient-to-br from-gray-600 to-gray-800 p-5 rounded-xl shadow-lg border border-gray-200 hover:border-gray-300">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-100 flex items-center gap-1">
                        <i data-lucide="trending-down" class="w-4 h-4"></i>
                        Cancellation Rate
                    </p>
                    <h3 class="text-3xl font-bold mt-1 text-white">8%</h3>
                    <p class="text-xs text-gray-200 mt-2 flex items-center">
                        <span class="inline-block w-2 h-2 rounded-full <?= $completionRate > 10 ? 'bg-amber-300' : 'bg-green-300' ?> mr-1"></span>
                        <?= $completionRate > 10 ? 'High' : 'Low' ?> rate
                    </p>
                </div>
                <div class="p-3 rounded-lg bg-gray-100/20 text-white">
                    <i data-lucide="trending-down" class="w-6 h-6"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Table -->
    <div class="bg-white rounded-xl shadow-lg overflow-hidden border border-gray-100 transition-all duration-300 hover:shadow-xl">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gradient-to-r from-rose-50 to-rose-100">
                    <tr>
                        <th class="px-6 py-4 text-left text-sm font-semibold text-rose-800 uppercase tracking-wider">
                            <div class="flex items-center gap-2">
                                <i data-lucide="user" class="w-4 h-4 text-rose-600"></i>
                                Guest
                            </div>
                        </th>
                        <th class="px-6 py-4 text-left text-sm font-semibold text-rose-800 uppercase tracking-wider">
                            <div class="flex items-center gap-2">
                                <i data-lucide="calendar" class="w-4 h-4 text-rose-600"></i>
                                Original Date
                            </div>
                        </th>
                        <th class="px-6 py-4 text-left text-sm font-semibold text-rose-800 uppercase tracking-wider">
                            <div class="flex items-center gap-2">
                                <i data-lucide="clock" class="w-4 h-4 text-rose-600"></i>
                                Cancelled On
                            </div>
                        </th>
                        <th class="px-6 py-4 text-left text-sm font-semibold text-rose-800 uppercase tracking-wider">
                            <div class="flex items-center gap-2">
                                <i data-lucide="message-circle" class="w-4 h-4 text-rose-600"></i>
                                Reason
                            </div>
                        </th>
                        <th class="px-6 py-4 text-left text-sm font-semibold text-rose-800 uppercase tracking-wider">
                            <div class="flex items-center gap-2">
                                <i data-lucide="message-circle" class="w-4 h-4 text-rose-600"></i>
                                Action
                            </div>
                        </th>
                    </tr>
                </thead>
                 <tbody class="divide-y divide-gray-100">
                    <?php while ($reservation = mysqli_fetch_assoc($result)) : ?>
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
    <div class="flex gap-2">
        <!-- View Button -->
        <div class="tooltip" data-tip="View Details">
            <button class="btn btn-xs btn-square btn-ghost bg-blue-50 text-blue-600 hover:bg-blue-100 hover:text-blue-700 
                        transition-all duration-200 group relative overflow-hidden">
                <i data-lucide="eye" class="w-4 h-4 transition-transform group-hover:scale-110"></i>
                <span class="absolute inset-0 bg-blue-500 opacity-0 group-hover:opacity-10 transition-opacity duration-300"></span>
            </button>
        </div>

        <!-- Edit Button -->
        <div class="tooltip" data-tip="Edit Reservation">
            <button class="btn btn-xs btn-square btn-ghost bg-amber-50 text-amber-600 hover:bg-amber-100 hover:text-amber-700 
                        transition-all duration-200 group relative overflow-hidden">
                <i data-lucide="edit" class="w-4 h-4 transition-transform group-hover:scale-110"></i>
                <span class="absolute inset-0 bg-amber-500 opacity-0 group-hover:opacity-10 transition-opacity duration-300"></span>
            </button>
        </div>

       
        <!-- Cancel Button -->
        <div class="tooltip" data-tip="Cancel Reservation">
            <button class="btn btn-xs btn-square btn-ghost bg-rose-50 text-rose-600 hover:bg-rose-100 hover:text-rose-700 
                        transition-all duration-200 group relative overflow-hidden">
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

    <!-- Pagination -->
    <div class="mt-6 flex justify-center">
        <div class="join">
            <button class="join-item btn btn-sm bg-white border border-gray-200 hover:bg-gray-50">«</button>
            <button class="join-item btn btn-sm bg-rose-600 text-white border-rose-600">1</button>
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
</body>
</html>