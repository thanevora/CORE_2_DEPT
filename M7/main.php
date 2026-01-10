<?php

session_start();

include("../main_connection.php");

$db_name = "rest_m7_billing_payments";

if (!isset($connections[$db_name])) {
    die("❌ Connection not found for $db_name");
}

$conn = $connections[$db_name];

// Fetch payment data
$total_revenue_query = "SELECT SUM(total_amount) as total FROM billing_payments WHERE status = 'Paid'";
$total_revenue_result = $conn->query($total_revenue_query);
$total_revenue = $total_revenue_result->fetch_assoc()['total'] ?? 0;

$pending_query = "SELECT COUNT(*) as count, SUM(total_amount) as total FROM billing_payments WHERE status = 'Pending'";
$pending_result = $conn->query($pending_query);
$pending_data = $pending_result->fetch_assoc();
$pending_payments = $pending_data['total'] ?? 0;
$pending_invoices = $pending_data['count'] ?? 0;

$overdue_query = "SELECT COUNT(*) as count, SUM(total_amount) as total FROM billing_payments WHERE status = 'Overdue'";
$overdue_result = $conn->query($overdue_query);
$overdue_data = $overdue_result->fetch_assoc();
$overdue_payments = $overdue_data['total'] ?? 0;
$overdue_invoices = $overdue_data['count'] ?? 0;

// Calculate percentages
$total_invoices = $pending_invoices + $overdue_invoices;
$pending_percentage = $total_invoices > 0 ? ($pending_invoices / $total_invoices) * 100 : 0;
$overdue_percentage = $total_invoices > 0 ? ($overdue_invoices / $total_invoices) * 100 : 0;

// Fetch pending and overdue payments
$pending_list_query = "SELECT * FROM billing_payments WHERE status = 'Pending' ORDER BY due_date ASC";
$pending_list_result = $conn->query($pending_list_query);
$pending_payments_list = [];
while ($row = $pending_list_result->fetch_assoc()) {
    $pending_payments_list[] = $row;
}

$overdue_list_query = "SELECT * FROM billing_payments WHERE status = 'Overdue' ORDER BY due_date ASC";
$overdue_list_result = $conn->query($overdue_list_query);
$overdue_payments_list = [];
while ($row = $overdue_list_result->fetch_assoc()) {
    $overdue_payments_list[] = $row;
}

// Secondary stats
$successful_query = "SELECT COUNT(*) as count FROM billing_payments WHERE status = 'Paid'";
$successful_result = $conn->query($successful_query);
$successful_transactions = $successful_result->fetch_assoc()['count'] ?? 0;

$avg_invoice_query = "SELECT AVG(total_amount) as average FROM billing_payments WHERE status = 'Paid'";
$avg_invoice_result = $conn->query($avg_invoice_query);
$average_invoice = $avg_invoice_result->fetch_assoc()['average'] ?? 0;

$payment_methods_query = "SELECT 
    COUNT(*) as total,
    SUM(MOP = 'Credit Card') as credit_card,
    SUM(MOP = 'Bank Transfer') as bank_transfer,
    SUM(MOP = 'Cash') as cash
    FROM billing_payments WHERE status = 'Paid'";
$payment_methods_result = $conn->query($payment_methods_query);
$payment_methods_data = $payment_methods_result->fetch_assoc();
$credit_card_percentage = $payment_methods_data['total'] > 0 ? 
    round(($payment_methods_data['credit_card'] / $payment_methods_data['total']) * 100) : 0;

$refunds_query = "SELECT SUM(total_amount) as total FROM billing_payments";
$refunds_result = $conn->query($refunds_query);
$total_refunds = $refunds_result->fetch_assoc()['total'] ?? 0;
?>

<!DOCTYPE html>
<html lang="en">
        <?php include '../header.php'; ?>

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Billing & Payments Dashboard</title>

        <link rel="stylesheet" href="../CSS/M4/main.css">

    <style>
   
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
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-8">
            <div>
                <h1 class="text-3xl font-bold text-gray-900 mb-1">Billing & Payment Dashboard</h1>
            </div>
            <div class="mt-4 md:mt-0">
               
            </div>
        </div>
        
<!-- Stats Cards -->
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">

    <!-- Total Revenue Card -->
    <div class="stat-card bg-white shadow-2xl p-5 rounded-xl text-black transition-all duration-300 hover:shadow-2xl hover:scale-105 hover:bg-gray-50">
        <div class="flex justify-between items-start">
            <div>
                <p class="text-sm font-medium text-[#001f54] hover:text-[#001f54] hover:drop-shadow-md transition-all">Total Revenue</p>
                <h3 class="text-3xl font-bold mt-1"><?php echo number_format($total_revenue, 2); ?></h3>
                <p class="text-xs text-gray-500 mt-1">This month</p>
            </div>
            <div class="p-3 rounded-lg bg-[#001f54] flex items-center justify-center transition-all duration-300 hover:bg-[#002b70]">
                <i data-lucide="dollar-sign" class="w-6 h-6 text-[#F7B32B]"></i>
            </div>
        </div>
        <div class="mt-4 pt-4 border-t" style="border-color:#E5E7EB;">
            <div class="flex justify-between items-center text-sm">
                <span>Trend</span>
                <span class="flex items-center gap-1 text-sm">
                    <i data-lucide="trending-up" class="w-4 h-4"></i> +5.2%
                </span>
            </div>
        </div>
    </div>

    <!-- Pending Payments Card -->
    <a href="javascript:void(0);" onclick="showSection('pending-section')" class="cursor-pointer">
        <div class="stat-card bg-white shadow-2xl p-5 rounded-xl text-black transition-all duration-300 hover:shadow-2xl hover:scale-105 hover:bg-gray-50">
            <div class="flex justify-between items-start">
                <div>
                    <p class="text-sm font-medium text-[#001f54] hover:text-[#001f54] hover:drop-shadow-md transition-all">Pending Payments</p>
                    <h3 class="text-3xl font-bold mt-1">
                        ₱<?php echo number_format($pending_payments, 2); ?>
                    </h3>
                    <p class="text-xs text-gray-500 mt-1">
                        <?php echo $pending_invoices; ?> invoices due in 7 days
                    </p>
                </div>
                <div class="p-3 rounded-lg bg-[#001f54] flex items-center justify-center transition-all duration-300 hover:bg-[#002b70]">
                    <i data-lucide="clock" class="w-6 h-6 text-[#F7B32B]"></i>
                </div>
            </div>
            <div class="mt-4">
                <div class="flex justify-between text-xs mb-1" style="color:#001f54;">
                    <span>Progress</span>
                    <span><?php echo round($pending_percentage); ?>%</span>
                </div>
                <div class="w-full bg-gray-200 rounded-full h-2 overflow-hidden">
                    <div class="h-full" style="width: <?php echo $pending_percentage; ?>%; background:#F7B32B;"></div>
                </div>
            </div>
        </div>
    </a>

    <!-- Overdue Payments Card -->
    <a href="javascript:void(0);" onclick="showSection('overdue-section')" class="cursor-pointer">
        <div class="stat-card bg-white shadow-2xl p-5 rounded-xl text-black transition-all duration-300 hover:shadow-2xl hover:scale-105 hover:bg-gray-50">
            <div class="flex justify-between items-start">
                <div>
                    <p class="text-sm font-medium text-[#001f54] hover:text-[#001f54] hover:drop-shadow-md transition-all">Overdue Payments</p>
                    <h3 class="text-3xl font-bold mt-1">
                        ₱<?php echo number_format($overdue_payments, 2); ?>
                    </h3>
                    <p class="text-xs text-gray-500 mt-1">
                        <?php echo $overdue_invoices; ?> invoices past due
                    </p>
                </div>
                <div class="p-3 rounded-lg bg-[#001f54] flex items-center justify-center transition-all duration-300 hover:bg-[#002b70]">
                    <i data-lucide="alert-circle" class="w-6 h-6 text-[#F7B32B]"></i>
                </div>
            </div>
            <div class="mt-4">
                <div class="flex justify-between text-xs mb-1" style="color:#001f54;">
                    <span>Progress</span>
                    <span><?php echo round($overdue_percentage); ?>%</span>
                </div>
                <div class="w-full bg-gray-200 rounded-full h-2 overflow-hidden">
                    <div class="h-full" style="width: <?php echo $overdue_percentage; ?>%; background:#F7B32B;"></div>
                </div>
            </div>
        </div>
    </a>

</div>


        
        <!-- Pending Payments Section -->
        <div id="pending-section" class="payment-section glass-card rounded-xl border border-gray-200 overflow-hidden mb-8">
            <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center bg-white">
                <h2 class="font-semibold text-gray-800 flex items-center">
                    <i data-lucide="clock" class="w-5 h-5 mr-2 text-amber-500"></i>
                    Pending Payments (<?php echo count($pending_payments_list); ?>)
                </h2>
                <button onclick="hideSections()" class="text-sm text-gray-500 hover:text-gray-700 font-medium flex items-center">
                    <i data-lucide="x" class="w-4 h-4 mr-1"></i> Close
                </button>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Invoice #</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Client</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Due Date</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Action</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php if (!empty($pending_payments_list)): ?>
                            <?php foreach ($pending_payments_list as $payment): 
                                $due_date = new DateTime($payment['due_date']);
                                $today = new DateTime();
                                $interval = $today->diff($due_date);
                                $days_left = $interval->format('%r%a');
                            ?>
                                <tr class="table-row-hover">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">#<?php echo htmlspecialchars($payment['invoice_number']); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo htmlspecialchars($payment['client_name']); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo htmlspecialchars($payment['due_date']); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-gray-900">₱<?php echo number_format($payment['total_amount'], 2); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="status-badge badge-pending">
                                            <i data-lucide="clock" class="w-3 h-3"></i>
                                            <?php echo $days_left > 0 ? $days_left . ' days' : 'Due today'; ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <a href="#" class="text-indigo-600 hover:text-indigo-900 mr-3 highlight">Remind</a>
                                        <a href="#" class="text-green-600 hover:text-green-900 highlight">Mark Paid</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="px-6 py-4 text-center text-sm text-gray-500">No pending payments found</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- Overdue Payments Section -->
        <div id="overdue-section" class="payment-section glass-card rounded-xl border border-gray-200 overflow-hidden mb-8">
            <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center bg-white">
                <h2 class="font-semibold text-gray-800 flex items-center">
                    <i data-lucide="alert-circle" class="w-5 h-5 mr-2 text-red-500"></i>
                    Overdue Payments (<?php echo count($overdue_payments_list); ?>)
                </h2>
                <button onclick="hideSections()" class="text-sm text-gray-500 hover:text-gray-700 font-medium flex items-center">
                    <i data-lucide="x" class="w-4 h-4 mr-1"></i> Close
                </button>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Invoice #</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Client</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Due Date</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Action</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php if (!empty($overdue_payments_list)): ?>
                            <?php foreach ($overdue_payments_list as $payment): 
                                $due_date = new DateTime($payment['due_date']);
                                $today = new DateTime();
                                $interval = $today->diff($due_date);
                                $days_overdue = abs($interval->format('%a'));
                            ?>
                                <tr class="table-row-hover">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">#<?php echo htmlspecialchars($payment['invoice_number']); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo htmlspecialchars($payment['client_name']); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo htmlspecialchars($payment['due_date']); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-gray-900">₱<?php echo number_format($payment['amount'], 2); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="status-badge badge-overdue">
                                            <i data-lucide="alert-triangle" class="w-3 h-3"></i>
                                            <?php echo $days_overdue . ' days'; ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <a href="#" class="text-indigo-600 hover:text-indigo-900 mr-3 highlight">Remind</a>
                                        <a href="#" class="text-green-600 hover:text-green-900 highlight">Mark Paid</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="px-6 py-4 text-center text-sm text-gray-500">No overdue payments found</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- Recent Transactions -->
<div class="glass-card rounded-xl border border-gray-200 overflow-hidden mb-8">
    <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center bg-white">
        <h2 class="font-semibold text-gray-800 flex items-center">
            <i data-lucide="list" class="w-5 h-5 mr-2 text-indigo-500"></i>
            Recent Transactions
        </h2>
        <button class="text-sm text-indigo-600 hover:text-indigo-800 font-medium flex items-center">
            View All <i data-lucide="chevron-right" class="w-4 h-4 ml-1"></i>
        </button>
    </div>
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Invoice</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Client</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                    <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Action</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php
                // Fetch recent transactions (last 10 paid transactions)
                $recent_query = "SELECT * FROM billing_payments 
                                WHERE status = 'Paid' 
                                ORDER BY payment_date DESC 
                                LIMIT 10";
                $recent_result = $conn->query($recent_query);
                
                if ($recent_result->num_rows > 0) {
                    while ($transaction = $recent_result->fetch_assoc()) {
                        $payment_date = new DateTime($transaction['payment_date']);
                        $formatted_date = $payment_date->format('Y-m-d');
                        
                        // Determine status badge class
                        $status_class = '';
                        $status_icon = '';
                        if ($transaction['status'] == 'Paid') {
                            $status_class = 'badge-paid';
                            $status_icon = 'check-circle';
                        } elseif ($transaction['status'] == 'Pending') {
                            $status_class = 'badge-pending';
                            $status_icon = 'clock';
                        } else {
                            $status_class = 'badge-overdue';
                            $status_icon = 'alert-triangle';
                        }
                        ?>
                        <tr class="table-row-hover">
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                <?php echo htmlspecialchars($transaction['invoice_number']); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <?php echo htmlspecialchars($transaction['client_name']); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <?php echo $formatted_date; ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-gray-900">
                                ₱<?php echo number_format($transaction['total_amount'], 2); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="status-badge <?php echo $status_class; ?>">
                                    <i data-lucide="<?php echo $status_icon; ?>" class="w-3 h-3"></i>
                                    <?php echo htmlspecialchars($transaction['status']); ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <a href="invoice_details.php?id=<?php echo $transaction['BP_id']; ?>" 
                                   class="text-indigo-600 hover:text-indigo-900 highlight">View</a>
                                <?php if ($transaction['status'] != 'Paid'): ?>
                                    <a href="mark_paid.php?id=<?php echo $transaction['id']; ?>" 
                                       class="text-green-600 hover:text-green-900 ml-3 highlight">Mark Paid</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php
                    }
                } else {
                    ?>
                    <tr>
                        <td colspan="6" class="px-6 py-4 text-center text-sm text-gray-500">
                            No recent transactions found
                        </td>
                    </tr>
                    <?php
                }
                ?>
            </tbody>
        </table>
    </div>
</div>
      </div>
    </div>
  </div>

  <script>
    lucide.createIcons();
    
    // Animate elements on page load
    document.addEventListener('DOMContentLoaded', function() {
        // Progress bars animation
        const progressBars = document.querySelectorAll('.progress-bar');
        progressBars.forEach(bar => {
            const width = bar.style.width;
            bar.style.width = '0';
            setTimeout(() => {
                bar.style.width = width;
            }, 100);
        });
        
        // Cards animation
        const cards = document.querySelectorAll('.card-hover');
        cards.forEach((card, index) => {
            card.style.opacity = '0';
            card.style.transform = 'translateY(20px)';
            setTimeout(() => {
                card.style.transition = 'opacity 0.3s ease-out, transform 0.3s ease-out';
                card.style.opacity = '1';
                card.style.transform = 'translateY(0)';
            }, 100 * index);
        });
    });
    
    // Show specific payment section
    function showSection(sectionId) {
        // Hide all payment sections first
        document.querySelectorAll('.payment-section').forEach(section => {
            section.classList.remove('active');
        });
        
        // Show the selected section
        const section = document.getElementById(sectionId);
        section.classList.add('active');
        
        // Scroll to the section
        section.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
    
    // Hide all payment sections
    function hideSections() {
        document.querySelectorAll('.payment-section').forEach(section => {
            section.classList.remove('active');
        });
    }
  </script>
  <script src="../JavaScript/sidebar.js"></script>
</body>
</html>