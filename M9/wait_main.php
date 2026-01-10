<?php
session_start();
include("../main_connection.php");

$db_name = "rest_m9_wait_staff";

if (!isset($connections[$db_name])) {
    die("❌ Connection not found for $db_name");
}

$conn = $connections[$db_name];

// ✅ Optimized single query with conditional aggregation
$query = "
    SELECT 
        COUNT(*) AS total_staff_count,
        SUM(CASE WHEN status = 'On_duty' THEN 1 ELSE 0 END) AS on_duty_count,
        SUM(CASE WHEN status = 'On_leave' THEN 1 ELSE 0 END) AS on_leave_count,
        COALESCE(ROUND(AVG(performance_rating), 1), 0) AS avg_performance_rating
    FROM wait_staff
";

$result = mysqli_query($conn, $query);

if (!$result) {
    die("Count query failed: " . mysqli_error($conn));
}

$row = mysqli_fetch_assoc($result);

$total_staff_count      = (int)$row['total_staff_count'];
$on_duty_count          = (int)$row['on_duty_count'];
$on_leave_count         = (int)$row['on_leave_count'];
$avg_performance_rating = (float)$row['avg_performance_rating'];

// --- Pagination Settings ---
$limit = 10; // Number of staff per page
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($page - 1) * $limit;

// --- Fetch total rows for pagination ---
$totalRowsResult = $connections[$db_name]->query("SELECT COUNT(*) as total FROM wait_staff");
$totalRowsRow = $totalRowsResult->fetch_assoc();
$totalRows = $totalRowsRow['total'];
$totalPages = ceil($totalRows / $limit);

// --- Fetch staff data ---
$result = $connections[$db_name]->query("SELECT * FROM wait_staff ORDER BY hire_date DESC LIMIT $limit OFFSET $offset");
?>



<!DOCTYPE html>
<html lang="en">
        <?php include '../header.php'; ?>

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Restaurant Dashboard - Staff Management</title>


   
</head>
<body class="bg-gray-50 min-h-screen">
  <div class="flex h-screen">
    <!-- Sidebar -->
    <?php include '../sidebarr.php'; ?>

    <!-- Content Area -->
    <div class="flex flex-col flex-1 overflow-auto">
        <!-- Navbar -->
        <?php include '../navbar.php'; ?>

        <!-- Content Area -->
        <div class="flex flex-col flex-1">
            <!-- Navbar -->
            <header class="bg-white shadow-sm py-4 px-6 sticky top-0 z-10">
                <div class="flex justify-between items-center">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-800">Wait Staff & Server</h1>
                    </div>
                  
                </div>
            </header>

            <!-- Main Content -->
            <main class="flex-1 p-6 overflow-auto">
<!-- Stats Cards -->
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">

    <!-- Total Staff -->
    <div class="stat-card bg-white shadow-2xl p-5 rounded-xl transition-all duration-300 hover:shadow-2xl hover:scale-105 hover:bg-gray-50">
        <div class="flex justify-between items-start">
            <div>
                <p class="text-sm font-medium text-[#001f54] hover:drop-shadow-md transition-all">Total Staff</p>
                <h3 class="text-3xl font-bold text-black mt-1" id="total-staff"><?= $total_staff_count ?></h3>
                <p class="text-xs text-gray-500 mt-1">All employees</p>
            </div>
            <div class="p-3 rounded-lg bg-[#001f54] flex items-center justify-center transition-all duration-300 hover:bg-[#002b70]">
                <i data-lucide="users" class="w-6 h-6 text-[#F7B32B]"></i>
            </div>
        </div>
    </div>

    <!-- On Duty -->
    <div class="stat-card bg-white shadow-2xl p-5 rounded-xl transition-all duration-300 hover:shadow-2xl hover:scale-105 hover:bg-gray-50">
        <div class="flex justify-between items-start">
            <div>
                <p class="text-sm font-medium text-[#001f54] hover:drop-shadow-md transition-all">On Duty</p>
                <h3 class="text-3xl font-bold text-black mt-1" id="on-duty"><?= $on_duty_count ?></h3>
                <p class="text-xs text-gray-500 mt-1">Currently active</p>
            </div>
            <div class="p-3 rounded-lg bg-[#001f54] flex items-center justify-center transition-all duration-300 hover:bg-[#002b70]">
                <i data-lucide="user-check" class="w-6 h-6 text-[#F7B32B]"></i>
            </div>
        </div>
    </div>

    <!-- On Leave -->
    <div class="stat-card bg-white shadow-2xl p-5 rounded-xl transition-all duration-300 hover:shadow-2xl hover:scale-105 hover:bg-gray-50">
        <div class="flex justify-between items-start">
            <div>
                <p class="text-sm font-medium text-[#001f54] hover:drop-shadow-md transition-all">On Leave</p>
                <h3 class="text-3xl font-bold text-black mt-1" id="on-leave"><?= $on_leave_count ?></h3>
                <p class="text-xs text-gray-500 mt-1">Currently on leave</p>
            </div>
            <div class="p-3 rounded-lg bg-[#001f54] flex items-center justify-center transition-all duration-300 hover:bg-[#002b70]">
                <i data-lucide="user-x" class="w-6 h-6 text-[#F7B32B]"></i>
            </div>
        </div>
    </div>

    <!-- Performance Rate -->
    <div class="stat-card bg-white shadow-2xl p-5 rounded-xl transition-all duration-300 hover:shadow-2xl hover:scale-105 hover:bg-gray-50">
        <div class="flex justify-between items-start">
            <div>
                <p class="text-sm font-medium text-[#001f54] hover:drop-shadow-md transition-all">Performance</p>
                <h3 class="text-3xl font-bold text-black mt-1" id="performance-rate"><?= $avg_performance_rating ?> ★</h3>
                <p class="text-xs text-gray-500 mt-1">Average rating</p>
            </div>
            <div class="p-3 rounded-lg bg-[#001f54] flex items-center justify-center transition-all duration-300 hover:bg-[#002b70]">
                <i data-lucide="activity" class="w-6 h-6 text-[#F7B32B]"></i>
            </div>
        </div>
    </div>

</div>


                <!-- Staff List and Performance -->

              <div class="w-full flex justify-center bg-gray-50 p-6">
    <div class="w-full max-w-7xl lg:col-span-2 ]">
        <!-- Header -->
        <div class="flex justify-between items-center mb-6">
            <div>
                <h3 class="text-lg font-semibold text-gray-800">Staff Members</h3>
                <p class="text-sm text-gray-500"></p>
            </div>
            <div class="flex space-x-3">
                <button id="addStaffButton" class="flex items-center text-sm bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition">
                    <i data-lucide="plus" class="w-4 h-4 mr-2"></i> Add Staff
                </button>
            </div>
        </div>
<!-- Staff Cards Grid -->
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-2 gap-6">
    <?php if ($result->num_rows > 0): ?>
        <?php while($staff = $result->fetch_assoc()): ?>
            <div class="bg-[#001f54] rounded-xl shadow border shadow-2xl border-gray-100 p-5 flex flex-col justify-between hover:shadow-md transition">
                <!-- Name & Position -->
                <div class="mb-3">

                <h2 class="text-md font-semibold text-[#F7B32B] flex items-center">
                        <i data-lucide="user" class="text-[#F7B32B] w-4 h-4 mr-2"></i>
                        Staff information
                    </h2>
                    <h4 class="text-md font-semibold text-white flex items-center">
                        <i data-lucide="user" class="text-[#F7B32B] w-4 h-4 mr-2"></i>
                        <?= htmlspecialchars($staff['full_name']) ?>
                    </h4>
                    <p class="text-sm text-white flex items-center">
                        <i data-lucide="briefcase" class="text-[#F7B32B] w-4 h-4 mr-2"></i>
                        <?= htmlspecialchars($staff['position']) ?> | <?= htmlspecialchars($staff['shift']) ?>
                    </p>
                </div>

                <!-- Staff Details -->
                <div class="mb-3 text-sm text-white space-y-1">
                    <p class="flex items-center">
                        <i data-lucide="layout" class="text-[#F7B32B] w-4 h-4 mr-2"></i>
                        <strong>Area:</strong> <?= htmlspecialchars($staff['tables_assigned'] ?: '-') ?>
                    </p>
                    <p class="flex items-center">
                        <i data-lucide="activity" class="text-[#F7B32B] w-4 h-4 mr-2"></i>
                        <strong>Status:</strong> <?= htmlspecialchars($staff['status']) ?>
                    </p>
                    <p class="flex items-center">
                        <i data-lucide="calendar" class="text-[#F7B32B] w-4 h-4 mr-2"></i>
                        <strong>Last Active:</strong> <?= !empty($staff['last_shift_date']) ? htmlspecialchars($staff['last_shift_date']) : '-' ?>
                    </p>
                    <p class="flex items-center">
                        <i data-lucide="phone" class="text-[#F7B32B] w-4 h-4 mr-2"></i>
                        <strong>Contact:</strong> <?= htmlspecialchars($staff['contact_number']) ?>
                    </p>
                </div>

            </div>
        <?php endwhile; ?>
    <?php else: ?>
        <div class="col-span-full py-6 text-center text-gray-500">No staff found.</div>
    <?php endif; ?>
</div>

<!-- Initialize Lucide icons -->


        <!-- Pagination -->
        <div class="mt-6 flex justify-between items-center">
            <span class="text-sm text-gray-500">
                Showing <?= $offset + 1 ?> to <?= min($offset + $limit, $totalRows) ?> of <?= $totalRows ?> entries
            </span>
            <div class="flex space-x-2">
                <?php if ($page > 1): ?>
                    <a href="?page=<?= $page - 1 ?>" class="px-3 py-1 text-sm border rounded hover:bg-gray-50">Previous</a>
                <?php endif; ?>

                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <a href="?page=<?= $i ?>" class="px-3 py-1 text-sm <?= $i === $page ? 'bg-blue-600 text-white' : 'border' ?> rounded hover:bg-gray-50"><?= $i ?></a>
                <?php endfor; ?>

                <?php if ($page < $totalPages): ?>
                    <a href="?page=<?= $page + 1 ?>" class="px-3 py-1 text-sm border rounded hover:bg-gray-50">Next</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

            </main>
        </div>
    </div>

<!-- Modern Modal Design -->
<div id="addStaffModal" class="fixed inset-0 bg-black/30 backdrop-blur-sm flex items-center justify-center z-50 hidden transition-opacity duration-300">
    <div class="bg-white rounded-lg shadow-2xl w-full max-w-4xl max-h-[90vh] flex flex-col overflow-hidden border border-gray-100">
        <!-- Modal Header -->
        <div class="p-6 border-b border-gray-100 flex justify-between items-center bg-gradient-to-r from-blue-50 to-indigo-50">
            <div>
                <h5 class="text-2xl font-bold text-gray-800">Add New Staff Member</h5>
                <p class="text-sm text-gray-500 mt-1">Fill in the details to add a new staff member</p>
            </div>
            <button onclick="closeModal()" class="p-2 rounded-full hover:bg-gray-100 transition-colors">
                <i data-lucide="x" class="w-5 h-5 text-gray-500"></i>
            </button>
        </div>
        
        <!-- Modal Content (scrollable but without visible scrollbar) -->
        <div class="overflow-y-auto scrollbar-hide flex-1 p-6">
<form id="staffForm" class="space-y-6" action="sub-modules/add_staff.php" method="POST">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Full Name -->
                    <div class="space-y-2">
                        <label for="full_name" class="block text-sm font-medium text-gray-700">Full Name</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i data-lucide="user" class="w-4 h-4 text-gray-400"></i>
                            </div>
                            <input type="text" id="full_name" name="full_name" class="bg-white text-black pl-10 block w-full border-gray-300 rounded-lg shadow-sm py-2.5 px-4 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200" placeholder="Evora, Jonathan E.">
                        </div>
                    </div>
                    
                 
                    
                    <!-- Shift -->
                    <div class="space-y-2">
                        <label for="shift" class="block text-sm font-medium text-gray-700">Shift</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i data-lucide="clock" class="w-4 h-4 text-gray-400"></i>
                            </div>
                            <select id="shift" name="shift" class="bg-white text-black pl-10 block w-full border-gray-300 rounded-lg shadow-sm py-2.5 px-4 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 appearance-none bg-[url('data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSIyNCIgaGVpZ2h0PSIyNCIgdmlld0JveD0iMCAwIDI0IDI0IiBmaWxsPSJub25lIiBzdHJva2U9IiAjdjR2NXY3IiBzdHJva2Utd2lkdGg9IjIiIHN0cm9rZS1saW5lY2FwPSJyb3VuZCIgc3Ryb2tlLWxpbmVqb2luPSJyb3VuZCI+PHBvbHlsaW5lIHBvaW50cz0iNiA5IDEyIDE1IDE4IDkiPjwvcG9seWxpbmU+PC9zdmc+')] bg-no-repeat bg-[center_right_1rem]">
                                <option value="">Select Shift</option>
                                <option value="Morning (6AM-2PM)">Morning (6AM-2PM)</option>
                                <option value="Afternoon (2PM-10PM)">Afternoon (2PM-10PM)</option>
                                <option value="Night (10PM-6AM)">Night (10PM-6AM)</option>
                            </select>
                        </div>
                    </div>
                    
                    <!-- Contact Number -->
                    <div class="space-y-2">
                        <label for="contact_number" class="block text-sm font-medium text-gray-700">Contact Number</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i data-lucide="phone" class="w-4 h-4 text-gray-400"></i>
                            </div>
                            <input type="tel" id="contact_number" name="contact_number" class="bg-white text-black pl-10 block w-full border-gray-300 rounded-lg shadow-sm py-2.5 px-4 focus:ring-2 focus:ring-blue-500 focus:border-blue-500" placeholder="+63 912 345 6789">
                        </div>
                    </div>
                    
                    <!-- Email -->
                    <div class="space-y-2">
                        <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i data-lucide="mail" class="w-4 h-4 text-gray-400"></i>
                            </div>
                            <input type="email" id="email" name="email" class="bg-white text-black pl-10 block w-full border-gray-300 rounded-lg shadow-sm py-2.5 px-4 focus:ring-2 focus:ring-blue-500 focus:border-blue-500" placeholder="john@restaurant.com">
                        </div>
                    </div>
                    
                    <!-- Status -->
                    <div class="space-y-2">
                        <label for="status" class="block text-sm font-medium text-gray-700">Status</label>
                        <select id="status" name="status" class="bg-white text-black block w-full border-gray-300 rounded-lg shadow-sm py-2.5 px-4 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            <option value="Active">Active</option>
                            <option value="On Leave">On Leave</option>
                            <option value="Terminated">Terminated</option>
                        </select>
                    </div>
                    
                    <!-- Hire Date -->
                    <div class="space-y-2">
                        <label for="hire_date" class="block text-sm font-medium text-gray-700">Hire Date</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i data-lucide="calendar" class="w-4 h-4 text-gray-400"></i>
                            </div>
                            <input type="date" id="hire_date" name="hire_date" class="bg-white text-black pl-10 block w-full border-gray-300 rounded-lg shadow-sm py-2.5 px-4 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        </div>
                    </div>
                    
<!-- Position Dropdown -->
<div class="md:col-span-2 space-y-2">
    <label for="position" class="block text-sm font-medium text-gray-700">Position</label>
    <select id="position" name="position" class="bg-white text-black block w-full border-gray-300 rounded-lg shadow-sm py-2.5 px-4 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
        <option value="">Select position</option>
        <option value="Chef">Chef</option>
        <option value="Manager">Manager</option>
        <option value="Supervisor">Supervisor</option>
        <option value="Waiter">Waiter</option>
        <option value="Cashier">Cashier</option>
        <option value="Washier">Washier</option>
    </select>
</div>

<!-- Tables Assigned Dropdown (initially hidden) -->
<div id="tablesAssignedContainer" class="md:col-span-2 space-y-2 hidden">
    <label for="tables_assigned" class="block text-sm font-medium text-gray-700">Tables Assigned</label>
    <select id="tables_assigned" name="tables_assigned[]" multiple class="block w-full border-gray-300 rounded-lg shadow-sm py-2.5 px-4 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
        <option value="Table 1">Table 1</option>
        <option value="Table 2">Table 2</option>
        <option value="Table 3">Table 3</option>
        <option value="Table 4">Table 4</option>
    </select>
</div>


        
                  
                    
                    <!-- Last Shift Date -->
                    <div class="space-y-2">
                        <label for="last_shift_date" class="block text-sm font-medium text-gray-700">Last Shift Date</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i data-lucide="calendar-clock" class="w-4 h-4 text-gray-400"></i>
                            </div>
                            <input type="date" id="last_shift_date" name="last_shift_date" class="bg-white text-black pl-10 block w-full border-gray-300 rounded-lg shadow-sm py-2.5 px-4 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        </div>
                    </div>
                    
                    <!-- Notes -->
                    <div class="md:col-span-2 space-y-2">
                        <label for="notes" class="block text-sm font-medium text-gray-700">Notes</label>
                        <div class="relative">
                            <div class="absolute top-3 left-3">
                                <i data-lucide="file-text" class="w-4 h-4 text-gray-400"></i>
                            </div>
                            <textarea id="notes" name="notes" rows="3" class="bg-white text-black pl-10 block w-full border-gray-300 rounded-lg shadow-sm py-2 px-4 focus:ring-2 focus:ring-blue-500 focus:border-blue-500" placeholder="Additional notes..."></textarea>
                        </div>
                    </div>
                </div>
                
               <div class="flex justify-end space-x-3 pt-2">
        <button type="reset" class="px-5 py-2.5 rounded-lg border border-gray-300 text-gray-700 hover:bg-gray-50 transition-colors duration-200">
            Cancel
        </button>
        <button type="submit" class="px-5 py-2.5 rounded-lg bg-gradient-to-r from-blue-600 to-indigo-600 text-white hover:from-blue-700 hover:to-indigo-700 transition-all duration-200 shadow-sm hover:shadow-md flex items-center">
            <i data-lucide="save" class="w-4 h-4 mr-2"></i>
            Save Staff
        </button>
    </div>
</form>
        </div>
    </div>
</div>

<style>
    /* Hide scrollbar but allow scrolling */
    .scrollbar-hide {
        -ms-overflow-style: none;  /* IE and Edge */
        scrollbar-width: none;  /* Firefox */
    }
    .scrollbar-hide::-webkit-scrollbar {
        display: none;  /* Chrome, Safari and Opera */
    }
    
    /* Custom select styling */
    select {
        background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%236b7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='M6 8l4 4 4-4'/%3e%3c/svg%3e");
        background-position: right 0.5rem center;
        background-repeat: no-repeat;
        background-size: 1.5em 1.5em;
        padding-right: 2.5rem;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }
    
    /* Smooth transitions */
    .transition-all {
        transition-property: all;
        transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
        transition-duration: 150ms;
    }
    
    /* Focus rings */
    .focus\:ring-2:focus {
        --tw-ring-offset-shadow: var(--tw-ring-inset) 0 0 0 var(--tw-ring-offset-width) var(--tw-ring-offset-color);
        --tw-ring-shadow: var(--tw-ring-inset) 0 0 0 calc(2px + var(--tw-ring-offset-width)) var(--tw-ring-color);
        box-shadow: var(--tw-ring-offset-shadow), var(--tw-ring-shadow), var(--tw-shadow, 0 0 #0000);
    }
</style>
<!-- Lucide Icons & Staff Modals & Table Selection Scripts -->
<!-- <script>
const staffForm = document.getElementById('staffForm');

staffForm.addEventListener('submit', async (e) => {
    e.preventDefault(); // Prevent default form submission

    const formData = new FormData(staffForm);

    try {
        const response = await fetch('sub-modules/add_staff.php', {
            method: 'POST',
            body: formData
        });

        const result = await response.json();

        if (result.success) {
            Swal.fire({
                icon: 'success',
                title: 'Staff Added',
                text: result.message,
                timer: 2000,
                showConfirmButton: false
            });

            staffForm.reset(); // Clear the form
            closeModal(); // Close modal if needed
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: result.message
            });
        }
    } catch (err) {
        console.error(err);
        Swal.fire({
            icon: 'error',
            title: 'Oops!',
            text: 'Something went wrong while submitting the form.'
        });
    }
});
</script> -->
<script>

        const positionSelect = document.getElementById('position');
    const tablesContainer = document.getElementById('tablesAssignedContainer');

    positionSelect.addEventListener('change', () => {
        if(positionSelect.value === 'Waiter'){
            tablesContainer.classList.remove('hidden');
        } else {
            tablesContainer.classList.add('hidden');
        }
    });
    // ========================
    // Initialize Lucide icons
    // ========================
    lucide.createIcons();

    // ========================
    // Modal Functions
    // ========================
    function openAddStaffModal() {
        const modal = document.getElementById('addStaffModal');
        modal.classList.remove('hidden');
        document.body.classList.add('overflow-hidden');
        loadTablesForDropdown(); // Load tables when modal opens
    }

    function closeModal() {
        const modal = document.getElementById('addStaffModal');
        modal.classList.add('hidden');
        document.body.classList.remove('overflow-hidden');
    }

    // ========================
    // Load tables for select dropdown
    // ========================
    function loadTablesForDropdown() {
        fetch('sub-modules/get_tables.php')
            .then(response => response.json())
            .then(data => {
                const select = document.getElementById('tables_assigned');
                select.innerHTML = '';

                if (data.error) {
                    console.error('Error loading tables:', data.error);
                    showAlert('error', 'Failed to load tables', data.error);
                    return;
                }

                data.forEach(table => {
                    const option = document.createElement('option');
                    option.value = table.table_id;
                    option.textContent = `${table.name} (${table.category}, ${table.capacity} seats) - ${table.status}`;
                    select.appendChild(option);
                });
            })
            .catch(error => {
                console.error('Error loading tables:', error);
                showAlert('error', 'Connection Error', 'Failed to load tables data');
            });
    }

    // ========================
    // Staff Form Submission
    // ===
    // ========================
    // SweetAlert Notification
    // ========================
    function showAlert(icon, title, text, timer = 1000) {
        const Toast = Swal.mixin({
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: timer,
            timerProgressBar: true,
            didOpen: (toast) => {
                toast.addEventListener('mouseenter', Swal.stopTimer);
                toast.addEventListener('mouseleave', Swal.resumeTimer);
            }
        });

        Toast.fire({
            icon: icon,
            title: title,
            text: text
        });
    }

    // ========================
    // Initialize Buttons & Modals
    // ========================
    document.addEventListener('DOMContentLoaded', function() {
        const addStaffBtn = document.getElementById('addStaffButton');
        if (addStaffBtn) addStaffBtn.addEventListener('click', openAddStaffModal);

        // Close modal when clicking outside content
        const modal = document.getElementById('addStaffModal');
        if (modal) {
            modal.addEventListener('click', function(e) {
                if (e.target === modal) closeModal();
            });
        }

        // Close modal with Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && !modal.classList.contains('hidden')) closeModal();
        });
    });

    // ========================
    // Staff Stats Initialization (optional placeholders)
    // ========================

    // ========================
    // Multi-Select Tables Dropdown
    // ========================
    document.addEventListener('DOMContentLoaded', function() {
        const dropdownButton = document.getElementById('tablesDropdownButton');
        const dropdownMenu = document.getElementById('tablesDropdownMenu');
        const tableSearchInput = document.getElementById('tableSearchInput');
        const tablesOptionsContainer = document.getElementById('tablesOptionsContainer');
        const selectedTablesText = document.getElementById('selectedTablesText');
        const hiddenSelect = document.getElementById('tables_assigned');

        let allTables = [];
        let selectedTables = [];

        // Toggle dropdown
        if (dropdownButton && dropdownMenu) {
            dropdownButton.addEventListener('click', function() {
                const isOpen = dropdownMenu.classList.contains('hidden');
                dropdownMenu.classList.toggle('hidden', !isOpen);
                dropdownButton.querySelector('i')?.classList.toggle('rotate-180', isOpen);
                if (isOpen) tableSearchInput?.focus();
            });
        }

     

        function renderTableOptions(tables) {
            tablesOptionsContainer.innerHTML = '';
            if (!tables.length) {
                tablesOptionsContainer.innerHTML = '<div class="px-4 py-2 text-sm text-gray-500">No tables found</div>';
                return;
            }

            tables.forEach(table => {
                const optionDiv = document.createElement('div');
                optionDiv.className = 'table-option px-4 py-2 hover:bg-gray-50';
                if (selectedTables.some(t => t.table_id === table.table_id)) optionDiv.classList.add('selected');

                optionDiv.innerHTML = `
                    <label class="flex items-center">
                        <input type="checkbox" value="${table.table_id}" class="table-checkbox" ${selectedTables.some(t => t.table_id === table.table_id) ? 'checked' : ''}>
                        <span class="ml-2">
                            ${table.name} (${table.category}, ${table.capacity} seats)
                            <span class="text-xs text-gray-500 ml-1">${table.status}</span>
                        </span>
                    </label>
                `;

                const checkbox = optionDiv.querySelector('input');
                checkbox.addEventListener('change', function() {
                    if (this.checked) selectedTables.push(table);
                    else selectedTables = selectedTables.filter(t => t.table_id !== table.table_id);
                    updateSelectedTablesUI();
                });

                tablesOptionsContainer.appendChild(optionDiv);
            });
        }

        function updateSelectedTablesUI() {
            hiddenSelect.innerHTML = '';
            selectedTables.forEach(table => {
                const option = document.createElement('option');
                option.value = table.table_id;
                option.selected = true;
                hiddenSelect.appendChild(option);
            });

            if (!selectedTables.length) selectedTablesText.textContent = 'Select tables...';
            else if (selectedTables.length === 1) selectedTablesText.textContent = selectedTables[0].name;
            else selectedTablesText.textContent = `${selectedTables.length} tables selected`;
        }

        // Initial load
        loadTablesForDropdown();
    });
</script>

<!-- Sidebar Script -->
<script src="../JavaScript/sidebar.js"></script>

</body>
</html>