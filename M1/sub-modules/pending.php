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
    <title>Pending Reservations</title>
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
        .action-btn {
            transition: all 0.2s ease;
        }
        .action-btn:hover {
            transform: scale(1.05);
            box-shadow: 0 2px 10px -2px rgba(0, 0, 0, 0.2);
        }
    </style>
</head>
<body class="bg-gray-50">
<div class="p-6 max-w-7xl mx-auto animate-fade-in">
    <!-- Header Section -->
    <div class="flex justify-between items-center mb-8">
        <div>
            <h1 class="text-3xl font-bold text-slate-800 flex items-center gap-3">
                <i data-lucide="clock" class="w-8 h-8 text-amber-500"></i>
                <span class="bg-gradient-to-r from-amber-600 to-amber-800 bg-clip-text text-transparent">
                    Pending Reservations
                </span>
            </h1>
            <p class="text-sm text-slate-500 mt-1">Review and manage pending booking requests</p>
        </div>
        <div class="flex gap-3">
            <button class="btn btn-sm bg-gradient-to-r from-blue-600 to-blue-700 text-white hover:from-blue-700 hover:to-blue-800 shadow-md hover:shadow-lg transition-all">
                <i data-lucide="download" class="w-4 h-4 mr-2"></i> Export
            </button>
        </div>
    </div>

    <!-- Stats Cards Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-5 mb-8">
        <!-- Total Pending -->
        <div class="card-hover bg-gradient-to-br from-amber-500 to-amber-700 p-5 rounded-xl shadow-lg border border-amber-200 hover:border-amber-300">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-sm font-medium text-amber-100 flex items-center gap-1">
                        <i data-lucide="clock" class="w-4 h-4"></i>
                        Total Pending
                    </p>
                    <h3 class="text-3xl font-bold mt-1 text-white"><?= $pending_count ?></h3>
                    <p class="text-xs text-amber-200 mt-2 flex items-center">
                        <span class="inline-block w-2 h-2 rounded-full bg-white mr-1"></span>
                        Awaiting confirmation
                    </p>
                </div>
                <div class="p-3 rounded-lg bg-amber-100/20 text-white">
                    <i data-lucide="clock" class="w-6 h-6"></i>
                </div>
            </div>
        </div>

        <!-- New Today -->
        <div class="card-hover bg-gradient-to-br from-blue-600 to-blue-800 p-5 rounded-xl shadow-lg border border-blue-200 hover:border-blue-300">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-sm font-medium text-blue-100 flex items-center gap-1">
                        <i data-lucide="calendar" class="w-4 h-4"></i>
                        New Today
                    </p>
                    <h3 class="text-3xl font-bold mt-1 text-white">5</h3>
                    <p class="text-xs text-blue-200 mt-2 flex items-center">
                        <span class="inline-block w-2 h-2 rounded-full bg-green-300 mr-1"></span>
                        <?= date('M j, Y') ?>
                    </p>
                </div>
                <div class="p-3 rounded-lg bg-blue-100/20 text-white">
                    <i data-lucide="calendar" class="w-6 h-6"></i>
                </div>
            </div>
        </div>

        <!-- Waiting >24h -->
        <div class="card-hover bg-gradient-to-br from-purple-600 to-purple-800 p-5 rounded-xl shadow-lg border border-purple-200 hover:border-purple-300">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-sm font-medium text-purple-100 flex items-center gap-1">
                        <i data-lucide="hourglass" class="w-4 h-4"></i>
                        Waiting >24h
                    </p>
                    <h3 class="text-3xl font-bold mt-1 text-white">3</h3>
                    <p class="text-xs text-purple-200 mt-2 flex items-center">
                        <span class="inline-block w-2 h-2 rounded-full bg-amber-300 mr-1"></span>
                        Needs attention
                    </p>
                </div>
                <div class="p-3 rounded-lg bg-purple-100/20 text-white">
                    <i data-lucide="hourglass" class="w-6 h-6"></i>
                </div>
            </div>
        </div>

        <!-- Expiring Soon -->
        <div class="card-hover bg-gradient-to-br from-rose-600 to-rose-800 p-5 rounded-xl shadow-lg border border-rose-200 hover:border-rose-300">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-sm font-medium text-rose-100 flex items-center gap-1">
                        <i data-lucide="alert-circle" class="w-4 h-4"></i>
                        Expiring Soon
                    </p>
                    <h3 class="text-3xl font-bold mt-1 text-white">2</h3>
                    <p class="text-xs text-rose-200 mt-2 flex items-center">
                        <span class="inline-block w-2 h-2 rounded-full bg-red-300 mr-1"></span>
                        Urgent action needed
                    </p>
                </div>
                <div class="p-3 rounded-lg bg-rose-100/20 text-white">
                    <i data-lucide="alert-circle" class="w-6 h-6"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Table -->
    <div class="bg-white rounded-xl shadow-lg overflow-hidden border border-gray-100 transition-all duration-300 hover:shadow-xl">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gradient-to-r from-amber-50 to-amber-100">
                    <tr>
                        <th class="px-6 py-4 text-left text-sm font-semibold text-amber-800 uppercase tracking-wider">
                            <div class="flex items-center gap-2">
                                <i data-lucide="user" class="w-4 h-4 text-amber-600"></i>
                                Guest
                            </div>
                        </th>
                        <th class="px-6 py-4 text-left text-sm font-semibold text-amber-800 uppercase tracking-wider">
                            <div class="flex items-center gap-2">
                                <i data-lucide="calendar" class="w-4 h-4 text-amber-600"></i>
                                Date/Time
                            </div>
                        </th>
                        <th class="px-6 py-4 text-left text-sm font-semibold text-amber-800 uppercase tracking-wider">
                            <div class="flex items-center gap-2">
                                <i data-lucide="clock" class="w-4 h-4 text-amber-600"></i>
                                Requested On
                            </div>
                        </th>
                        <th class="px-6 py-4 text-left text-sm font-semibold text-amber-800 uppercase tracking-wider">
                            <div class="flex items-center gap-2">
                                <i data-lucide="settings" class="w-4 h-4 text-amber-600"></i>
                                Actions
                            </div>
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <?php while ($reservation = mysqli_fetch_assoc($result)): ?>
                    <tr class="hover:bg-amber-50/50 transition-colors duration-200">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <div class="text-sm font-medium text-slate-800"><?= $reservation['name'] ?></div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-medium text-slate-800"><?= date('M j, Y', strtotime($reservation['reservation_date'])) ?></div>
                            <div class="text-sm text-slate-500"><?= $reservation['start_time'] . ' - ' . $reservation['end_time'] ?></div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-slate-500">
                                <?= date('M j, g:i a', strtotime($reservation['created_at'])) ?>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex gap-2">
                                <button onclick="confirmReservation(<?= $reservation['reservation_id'] ?>)" 
                                        class="action-btn btn btn-xs bg-gradient-to-r from-green-500 to-green-600 text-white hover:from-green-600 hover:to-green-700 shadow hover:shadow-md">
                                    <i data-lucide="check" class="w-4 h-4 mr-1"></i> Confirm
                                </button>
                                <button onclick="rejectReservation(<?= $reservation['reservation_id'] ?>)" 
                                        class="action-btn btn btn-xs bg-gradient-to-r from-red-500 to-red-600 text-white hover:from-red-600 hover:to-red-700 shadow hover:shadow-md">
                                    <i data-lucide="x" class="w-4 h-4 mr-1"></i> Reject
                                </button>
                                <button class="action-btn btn btn-xs btn-ghost bg-blue-50 text-blue-600 hover:bg-blue-100">
                                    <i data-lucide="eye" class="w-4 h-4"></i>
                                </button>
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
            <button class="join-item btn btn-sm bg-amber-600 text-white border-amber-600">1</button>
            <button class="join-item btn btn-sm bg-white border border-gray-200 hover:bg-gray-50">2</button>
            <button class="join-item btn btn-sm bg-white border border-gray-200 hover:bg-gray-50">3</button>
            <button class="join-item btn btn-sm bg-white border border-gray-200 hover:bg-gray-50">»</button>
        </div>
    </div>
</div>

<script>
    lucide.createIcons();
    
    function confirmReservation(id) {
        Swal.fire({
            title: 'Confirm Reservation',
            text: 'Are you sure you want to confirm this reservation?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#10B981',
            cancelButtonColor: '#EF4444',
            confirmButtonText: 'Yes, confirm!'
        }).then((result) => {
            if (result.isConfirmed) {
                // AJAX call to confirm reservation
                Swal.fire('Confirmed!', 'The reservation has been confirmed.', 'success');
            }
        });
    }
    
    function rejectReservation(id) {
        Swal.fire({
            title: 'Reject Reservation',
            text: 'Are you sure you want to reject this reservation?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#EF4444',
            cancelButtonColor: '#6B7280',
            confirmButtonText: 'Yes, reject!'
        }).then((result) => {
            if (result.isConfirmed) {
                // AJAX call to reject reservation
                Swal.fire('Rejected!', 'The reservation has been rejected.', 'success');
            }
        });
    }
</script>
</body>
</html>