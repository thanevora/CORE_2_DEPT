<?php
$role = $_SESSION['role'] ?? 'guest';
$permissions = include 'USM/role_permissions.php';
$allowed_modules = $permissions[$role] ?? [];
$is_supervisor = ($role === 'supervisor' || $role === 'admin');

// Define base path for consistent URL structure
$base_url = '/SOLIERA_RESTAURANT'; // Correct full URL
?>

<div class="bg-[#001f54] pt-5 pb-4 flex flex-col fixed md:relative h-full transition-all duration-300 ease-in-out shadow-xl -translate-x-full md:translate-x-0" id="sidebar">
    <!-- Sidebar Header -->
    <div class="flex items-center justify-between flex-shrink-0 px-4 mb-6 text-center">
        <img src="<?php echo $base_url; ?>/images/tagline_no_bg.png" 
            alt="Full Logo" 
            class="h-25 w-auto "
            id="sidebar-logo">
        <img src="<?php echo $base_url; ?>/images/soliera_S.png" 
            alt="Icon Logo" 
            class="h-25 w-auto"
            id="sonly">
    </div>

    <!-- Navigation Menu -->
    <div class="flex-1 flex flex-col overflow-hidden hover:overflow-y-auto">
        <nav class="flex-1 px-2 space-y-1">
            <!-- ANALYTICS & REPORTING SECTION -->
            <?php if ($is_supervisor || in_array('analytics', $allowed_modules)): ?>
            <div class="px-4 py-2 mt-2">
                <p class="text-xs font-semibold text-blue-300 uppercase tracking-wider sidebar-text">Analytics & Reporting</p>
            </div>
            <a href="<?php echo $base_url; ?>Analytics/Analytics_metabase.php" class="block">
                <div class="flex items-center px-4 py-3 text-sm font-medium rounded-lg transition-all hover:bg-blue-600/50 text-white group">
                    <div class="p-1.5 rounded-lg bg-blue-800/30 group-hover:bg-blue-700/50 transition-colors">
                        <i data-lucide="bar-chart-2" class="w-5 h-5 text-[#F7B32B] group-hover:text-white"></i>
                    </div>
                    <span class="ml-3 sidebar-text">Reports & Analytics</span>
                </div>
            </a>
            <?php endif; ?>

            <!-- RESERVATION MANAGEMENT SECTION -->
            <?php if ($is_supervisor || in_array('table_reservation', $allowed_modules) || in_array('events', $allowed_modules)): ?>
            <div class="px-4 py-2 mt-4">
                <p class="text-xs font-semibold text-blue-300 uppercase tracking-wider sidebar-text">Reservation Management</p>
            </div>
            
            <?php if ($is_supervisor || in_array('table_reservation', $allowed_modules)): ?>
            <!-- Table Reservation Dropdown -->
            <div class="menu-dropdown">
                <button type="button" class="dropdown-toggle flex items-center justify-between w-full px-4 py-3 text-sm font-medium rounded-lg transition-all hover:bg-blue-600/50 text-white group">
                    <div class="flex items-center">
                        <div class="p-1.5 rounded-lg bg-blue-800/30 group-hover:bg-blue-700/50 transition-colors">
                            <i data-lucide="table" class="w-5 h-5 text-[#F7B32B] group-hover:text-white"></i>
                        </div>
                        <span class="ml-3 sidebar-text">Table Reservation</span>
                    </div>
                    <i data-lucide="chevron-down" class="w-4 h-4 ml-auto transition-transform duration-200 dropdown-icon dropdown-arrow"></i>
                </button>
                
                <!-- Dropdown Menu -->
                <div class="dropdown-content overflow-hidden transition-all duration-300 max-h-0">
                    <div class="py-2 space-y-1">
                         <a href="<?php echo $base_url; ?>/M1/calendar.php" class="flex items-center px-4 py-2 text-sm rounded-lg transition-all hover:bg-blue-600/30 text-blue-100 hover:text-white group/item ml-8">
                            <i data-lucide="calendar" class="w-4 h-4 mr-3 text-[#F7B32B] group-hover/item:text-white"></i>
                            <span>Calendar View</span>
                        </a>
                        <!-- Tables Management -->
                        <a href="<?php echo $base_url; ?>/M1/sub-modules/Table.php" class="flex items-center px-4 py-2 text-sm rounded-lg transition-all hover:bg-blue-600/30 text-blue-100 hover:text-white group/item ml-8">
                            <i data-lucide="table-properties" class="w-4 h-4 mr-3 text-[#F7B32B] group-hover/item:text-white"></i>
                            <span class="sidebar-text">Tables</span>
                        </a>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <?php if ($is_supervisor || in_array('events', $allowed_modules)): ?>
            <a href="<?php echo $base_url; ?>/M6/main_reservation.php" class="block">
                <div class="flex items-center px-4 py-3 text-sm font-medium rounded-lg transition-all hover:bg-blue-600/50 text-white group">
                    <div class="p-1.5 rounded-lg bg-blue-800/30 group-hover:bg-blue-700/50 transition-colors">
                        <i data-lucide="calendar-days" class="w-5 h-5 text-[#F7B32B] group-hover:text-white"></i>
                    </div>
                    <span class="ml-3 sidebar-text">Event Reservations</span>
                </div>
            </a>
            <?php endif; ?>
            <?php endif; ?>

            <!-- OPERATIONS & PERFORMANCE SECTION -->
            <?php if ($is_supervisor || in_array('turnover', $allowed_modules) || in_array('feedback', $allowed_modules)): ?>
            <div class="px-4 py-2 mt-4">
                <p class="text-xs font-semibold text-blue-300 uppercase tracking-wider sidebar-text">Operations & Performance</p>
            </div>
            
            <?php if ($is_supervisor || in_array('turnover', $allowed_modules)): ?>
            <a href="<?php echo $base_url; ?>/M8/turnover_main.php" class="block">
                <div class="flex items-center px-4 py-3 text-sm font-medium rounded-lg transition-all hover:bg-blue-600/50 text-white group">
                    <div class="p-1.5 rounded-lg bg-blue-800/30 group-hover:bg-blue-700/50 transition-colors">
                        <i data-lucide="rotate-cw" class="w-5 h-5 text-[#F7B32B] group-hover:text-white"></i>
                    </div>
                    <span class="ml-3 sidebar-text">Turnover & Wait Time</span>
                </div>
            </a>
            <?php endif; ?>
            
            <?php if ($is_supervisor || in_array('feedback', $allowed_modules)): ?>
            <a href="<?php echo $base_url; ?>/M10/comments_main.php" class="block">
                <div class="flex items-center px-4 py-3 text-sm font-medium rounded-lg transition-all hover:bg-blue-600/50 text-white group">
                    <div class="p-1.5 rounded-lg bg-blue-800/30 group-hover:bg-blue-700/50 transition-colors">
                        <i data-lucide="message-square" class="w-5 h-5 text-[#F7B32B] group-hover:text-white"></i>
                    </div>
                    <span class="ml-3 sidebar-text">Reviews & Feedback</span>
                </div>
            </a>
            <?php endif; ?>
            <?php endif; ?>

            <!-- MENU & ORDERING SECTION -->
            <?php if ($is_supervisor || in_array('menu_management', $allowed_modules) || in_array('pos', $allowed_modules) || in_array('kot', $allowed_modules)): ?>
            <div class="px-4 py-2 mt-4">
                <p class="text-xs font-semibold text-blue-300 uppercase tracking-wider sidebar-text">Menu & Ordering</p>
            </div>
            
            <?php if ($is_supervisor || in_array('menu_management', $allowed_modules)): ?>
            <!-- Menu Management Dropdown -->
            <div class="menu-dropdown">
                <button type="button" class="dropdown-toggle flex items-center justify-between w-full px-4 py-3 text-sm font-medium rounded-lg transition-all hover:bg-blue-600/50 text-white group">
                    <div class="flex items-center">
                        <div class="p-1.5 rounded-lg bg-blue-800/30 group-hover:bg-blue-700/50 transition-colors">
                            <i data-lucide="notebook-text" class="w-5 h-5 text-[#F7B32B] group-hover:text-white"></i>
                        </div>
                        <span class="ml-3 sidebar-text">Menu Management</span>
                    </div>
                    <i data-lucide="chevron-down" class="w-4 h-4 ml-auto transition-transform duration-200 dropdown-icon dropdown-arrow"></i>
                </button>
                
                <!-- Dropdown Menu -->
                <div class="dropdown-content overflow-hidden transition-all duration-300 max-h-0">
                    <div class="py-2 space-y-1">
                        <!-- Main Menu Management Link -->
                        <a href="<?php echo $base_url; ?>/M3/main.php" class="flex items-center px-4 py-2 text-sm rounded-lg transition-all hover:bg-blue-600/30 text-blue-100 hover:text-white group/item ml-8">
                            <i data-lucide="layout-dashboard" class="w-4 h-4 mr-3 text-[#F7B32B] group-hover/item:text-white"></i>
                            <span class="sidebar-text">Menu Dashboard</span>
                        </a>
                        
                        <!-- Review New Menu -->
                        <a href="<?php echo $base_url; ?>/M3/sub-modules/review_menu.php" class="flex items-center px-4 py-2 text-sm rounded-lg transition-all hover:bg-blue-600/30 text-blue-100 hover:text-white group/item ml-8">
                            <i data-lucide="clipboard-check" class="w-4 h-4 mr-3 text-[#F7B32B] group-hover/item:text-white"></i>
                            <span class="sidebar-text">Review New Menu</span>
                            <span class="ml-auto px-2 py-1 text-xs bg-amber-500/20 text-amber-300 rounded-full sidebar-text">New</span>
                        </a>
                        
                        <!-- Nutritional Context -->
                        <a href="<?php echo $base_url; ?>/M3/nutritional-context.php" class="flex items-center px-4 py-2 text-sm rounded-lg transition-all hover:bg-blue-600/30 text-blue-100 hover:text-white group/item ml-8">
                            <i data-lucide="apple" class="w-4 h-4 mr-3 text-[#F7B32B] group-hover/item:text-white"></i>
                            <span class="sidebar-text">Nutritional Context</span>
                        </a>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <?php if ($is_supervisor || in_array('pos', $allowed_modules)): ?>
            <!-- POS & Ordering Dropdown -->
            <div class="menu-dropdown">
                <button type="button" class="dropdown-toggle flex items-center justify-between w-full px-4 py-3 text-sm font-medium rounded-lg transition-all hover:bg-blue-600/50 text-white group">
                    <div class="flex items-center">
                        <div class="p-1.5 rounded-lg bg-blue-800/30 group-hover:bg-blue-700/50 transition-colors">
                            <i data-lucide="shopping-cart" class="w-5 h-5 text-[#F7B32B] group-hover:text-white"></i>
                        </div>
                        <span class="ml-3 sidebar-text">POS & Ordering</span>
                    </div>
                    <i data-lucide="chevron-down" class="w-4 h-4 ml-auto transition-transform duration-200 dropdown-icon dropdown-arrow"></i>
                </button>
                
                <!-- Dropdown Menu -->
                <div class="dropdown-content overflow-hidden transition-all duration-300 max-h-0">
                    <div class="py-2 space-y-1">
                        <!-- Main POS System -->
                        <a href="<?php echo $base_url; ?>/M4/employee_main.php" class="flex items-center px-4 py-2 text-sm rounded-lg transition-all hover:bg-blue-600/30 text-blue-100 hover:text-white group/item ml-8">
                            <i data-lucide="monitor" class="w-4 h-4 mr-3 text-[#F7B32B] group-hover/item:text-white"></i>
                            <span class="sidebar-text">POS Terminal</span>
                        </a>
                        
                        <!-- To Be Posted Items -->
                        <a href="<?php echo $base_url; ?>/M4/for_posting.php" class="flex items-center px-4 py-2 text-sm rounded-lg transition-all hover:bg-blue-600/30 text-blue-100 hover:text-white group/item ml-8">
                            <i data-lucide="clock" class="w-4 h-4 mr-3 text-[#F7B32B] group-hover/item:text-white"></i>
                            <span class="sidebar-text">To Be Posted</span>
                            <span class="ml-auto px-2 py-1 text-xs bg-amber-500/20 text-amber-300 rounded-full sidebar-text">Pending</span>
                        </a>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <?php if ($is_supervisor || in_array('kot', $allowed_modules)): ?>
            <a href="<?php echo $base_url; ?>/M5/main.php" class="block">
                <div class="flex items-center px-4 py-3 text-sm font-medium rounded-lg transition-all hover:bg-blue-600/50 text-white group">
                    <div class="p-1.5 rounded-lg bg-blue-800/30 group-hover:bg-blue-700/50 transition-colors">
                        <i data-lucide="printer" class="w-5 h-5 text-[#F7B32B] group-hover:text-white"></i>
                    </div>
                    <span class="ml-3 sidebar-text">Kitchen Orders (KOT)</span>
                </div>
            </a>
            <?php endif; ?>
            <?php endif; ?>

            <!-- BILLING & FINANCE SECTION -->
            <?php if ($is_supervisor || in_array('billing', $allowed_modules)): ?>
            <div class="px-4 py-2 mt-4">
                <p class="text-xs font-semibold text-blue-300 uppercase tracking-wider sidebar-text">Finance</p>
            </div>
            <a href="<?php echo $base_url; ?>/M7/main.php" class="block">
                <div class="flex items-center px-4 py-3 text-sm font-medium rounded-lg transition-all hover:bg-blue-600/50 text-white group">
                    <div class="p-1.5 rounded-lg bg-blue-800/30 group-hover:bg-blue-700/50 transition-colors">
                        <i data-lucide="receipt" class="w-5 h-5 text-[#F7B32B] group-hover:text-white"></i>
                    </div>
                    <span class="ml-3 sidebar-text">Billing & Payments</span>
                </div>
            </a>
            <?php endif; ?>

            <!-- INVENTORY & SUPPLIES SECTION -->
            <?php if ($is_supervisor || in_array('inventory', $allowed_modules)): ?>
            <div class="px-4 py-2 mt-4">
                <p class="text-xs font-semibold text-blue-300 uppercase tracking-wider sidebar-text">Inventory & Supplies</p>
            </div>
            <a href="<?php echo $base_url; ?>/M2/main.php" class="block">
                <div class="flex items-center px-4 py-3 text-sm font-medium rounded-lg transition-all hover:bg-blue-600/50 text-white group">
                    <div class="p-1.5 rounded-lg bg-blue-800/30 group-hover:bg-blue-700/50 transition-colors">
                        <i data-lucide="package" class="w-5 h-5 text-[#F7B32B] group-hover:text-white"></i>
                    </div>
                    <span class="ml-3 sidebar-text">Stock Management</span>
                </div>
            </a>
            <?php endif; ?>

            <!-- STAFF & ADMINISTRATION SECTION -->
            <?php if ($is_supervisor || in_array('staff_management', $allowed_modules)): ?>
            <div class="px-4 py-2 mt-4">
                <p class="text-xs font-semibold text-blue-300 uppercase tracking-wider sidebar-text">Staff & Administration</p>
            </div>
            
            <?php if ($is_supervisor): ?>
            <a href="<?php echo $base_url; ?>/M9/wait_main.php" class="block">
                <div class="flex items-center px-4 py-3 text-sm font-medium rounded-lg transition-all hover:bg-blue-600/50 text-white group">
                    <div class="p-1.5 rounded-lg bg-blue-800/30 group-hover:bg-blue-700/50 transition-colors">
                        <i data-lucide="users" class="w-5 h-5 text-[#F7B32B] group-hover:text-white"></i>
                    </div>
                    <span class="ml-3 sidebar-text">Wait Staff</span>
                </div>
            </a>
            <?php endif; ?>
            
            <?php if ($is_supervisor || in_array('user_management', $allowed_modules)): ?>
            <!-- User Management Dropdown -->
            <div class="menu-dropdown">
                <button type="button" class="dropdown-toggle flex items-center justify-between w-full px-4 py-3 text-sm font-medium rounded-lg transition-all hover:bg-blue-600/50 text-white group">
                    <div class="flex items-center">
                        <div class="p-1.5 rounded-lg bg-blue-800/30 group-hover:bg-blue-700/50 transition-colors">
                            <i data-lucide="users" class="w-5 h-5 text-[#F7B32B] group-hover:text-white"></i>
                        </div>
                        <span class="ml-3 sidebar-text">User Management</span>
                    </div>
                    <i data-lucide="chevron-down" class="w-4 h-4 ml-auto transition-transform duration-200 dropdown-icon dropdown-arrow"></i>
                </button>
                
                <!-- Dropdown Menu -->
                <div class="dropdown-content overflow-hidden transition-all duration-300 max-h-0">
                    <div class="py-2 space-y-1">
                        <a href="<?php echo $base_url; ?>/USM/department_accounts.php" class="flex items-center px-4 py-2 text-sm rounded-lg transition-all hover:bg-blue-600/30 text-blue-100 hover:text-white group/item ml-8">
                            <i data-lucide="user-cog" class="w-4 h-4 mr-3 text-[#F7B32B] group-hover/item:text-white"></i>
                            <span class="sidebar-text">Department Accounts</span>
                        </a>
                        
                        <!-- ADDED: Login Logs -->
                        <a href="<?php echo $base_url; ?>/USM/department_logs.php" class="flex items-center px-4 py-2 text-sm rounded-lg transition-all hover:bg-blue-600/30 text-blue-100 hover:text-white group/item ml-8">
                            <i data-lucide="key" class="w-4 h-4 mr-3 text-[#F7B32B] group-hover/item:text-white"></i>
                            <span class="sidebar-text">Login Logs</span>
                        </a>
                       
                        <a href="<?php echo $base_url; ?>/USM/audit_trail&transaction.php" class="flex items-center px-4 py-2 text-sm rounded-lg transition-all hover:bg-blue-600/30 text-blue-100 hover:text-white group/item ml-8">
                            <i data-lucide="history" class="w-4 h-4 mr-3 text-[#F7B32B] group-hover/item:text-white"></i>
                            <span class="sidebar-text">Audit Trail & Transaction</span>
                        </a>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            <?php endif; ?>

            <!-- Logout -->
            <div class="px-4 py-2 mt-4">
                <p class="text-xs font-semibold text-blue-300 uppercase tracking-wider sidebar-text">Account</p>
            </div>
            <form action="<?php echo $base_url; ?>/USM/logout.php" method="POST" class="px-4 py-3">
                <button type="submit" class="flex items-center w-full text-sm font-medium rounded-lg transition-all hover:bg-blue-600/50 text-white group">
                    <div class="p-1.5 rounded-lg bg-blue-800/30 group-hover:bg-blue-700/50 transition-colors">
                        <i data-lucide="log-out" class="w-5 h-5 text-[#F7B32B] group-hover:text-white"></i>
                    </div>
                    <span class="ml-3 sidebar-text">Logout</span>
                </button>
            </form>
        </nav>
    </div>
</div>

<!-- Mobile Overlay -->
<div class="sidebar-overlay" onclick="toggleSidebar()"></div>

<style>
/* Mobile styles */
@media (max-width: 767px) {
    #sidebar {
        z-index: 40;
        width: 16rem; /* w-64 equivalent */
        left: 0;
        top: 0;
        bottom: 0;
        transition: transform 0.1s ease;
    }
    
    #sidebar.translate-x-0 {
        transform: translateX(0);
    }
    
    #sidebar.-translate-x-full {
        transform: translateX(-100%);
    }
    
    /* Optional overlay */
    .sidebar-overlay {
        position: fixed;
        top: 0;
        right: 0;
        bottom: 0;
        left: 0;
        background: rgba(0,0,0,0.5);
        z-index: 30;
        display: none;
    }
    
    #sidebar.translate-x-0 + .sidebar-overlay {
        display: block;
    }
}

/* Desktop styles */
.w-20 .sidebar-text {
    display: none;
}

.w-20 .flex.items-center {
    justify-content: center;
    padding-left: 0.5rem;
    padding-right: 0.5rem;
}

.w-20 .collapse-title {
    padding-left: 0.5rem;
    padding-right: 0.5rem;
    justify-content: center;
}

.w-20 .collapse-content {
    display: none;
}

.w-20 .text-xs.uppercase {
    display: none;
}

.w-20 .p-1.5.rounded-lg {
    margin-right: 0;
}

#sidebar-logo {
    opacity: 0;
    transition: opacity 0.3s ease;
}

#sidebar.loaded #sidebar-logo {
    opacity: 1;
}

/* Hide scrollbar but keep scrolling */
#sidebar {
    -ms-overflow-style: none;  /* IE and Edge */
    scrollbar-width: none;  /* Firefox */
}

#sidebar::-webkit-scrollbar {
    display: none;  /* Chrome, Safari and Opera */
}

/* Only show scrollbar on hover */
.overflow-hidden {
    overflow: hidden;
}

.hover\:overflow-y-auto:hover {
    overflow-y: auto;
}

/* SweetAlert Fix */
.swal2-styled {
  opacity: 1 !important;
  visibility: visible !important;
  color: #fff !important;
  background-color: #3085d6 !important;
  transition: background-color 0.2s ease-in-out, transform 0.1s ease-in-out;
}

.swal2-styled:hover {
  background-color: #2563eb !important; /* Tailwind's blue-600 */
  transform: scale(1.03);
}

.swal2-cancel {
  background-color: #e5e7eb !important; /* neutral-200 */
  color: #111 !important;
}
.swal2-cancel:hover {
  background-color: #d1d5db !important; /* neutral-300 */
}

/* Remove all SweetAlert hover effects */
.swal2-confirm:hover,
.swal2-cancel:hover,
.swal2-deny:hover {
    transform: none !important;
    box-shadow: none !important;
    background-color: inherit !important;
    filter: none !important;
}

/* Remove transitions */
.swal2-confirm,
.swal2-cancel,
.swal2-deny {
    transition: none !important;
}

/* Remove focus effects if desired */
.swal2-confirm:focus,
.swal2-cancel:focus,
.swal2-deny:focus {
    box-shadow: none !important;
}

/* Smooth dropdown animations */
.dropdown-content {
    transition: max-height 0.3s ease-in-out, opacity 0.2s ease-in-out;
    max-height: 0;
    opacity: 0;
}

.menu-dropdown.active .dropdown-content {
    max-height: 500px !important;
    opacity: 1;
}

.menu-dropdown.active .dropdown-arrow {
    transform: rotate(180deg);
}

/* Active state for dropdown parent */
.menu-dropdown.active .dropdown-toggle {
    background: rgba(59, 130, 246, 0.5) !important;
}

/* Mobile responsiveness */
@media (max-width: 768px) {
    .menu-dropdown .dropdown-content {
        max-height: 300px;
        overflow-y: auto;
    }
}

/* Active link styling */
nav a.active .dropdown-toggle,
nav a.active > div {
    background: rgba(59, 130, 246, 0.5) !important;
}

/* Improved hover effects */
.dropdown-content a:hover {
    background: rgba(59, 130, 246, 0.4);
    transform: translateX(2px);
    transition: all 0.2s ease;
}
</style>

<script>
// Initialize lucide icons
lucide.createIcons();

function isMobileView() {
    return window.innerWidth < 768; // Tailwind's md breakpoint
}

function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
    const sidebarLogo = document.getElementById('sidebar-logo');
    const sonlyLogo = document.getElementById('sonly');

    if (isMobileView()) {
        // Mobile toggle
        sidebar.classList.toggle('translate-x-0');
        sidebar.classList.toggle('-translate-x-full');
    } else {
        // Desktop toggle
        const currentlyCollapsed = sidebar.classList.contains('w-20');
        sidebar.classList.toggle('w-20', !currentlyCollapsed);
        sidebar.classList.toggle('w-64', currentlyCollapsed);

        // Save state
        localStorage.setItem('sidebarCollapsed', !currentlyCollapsed);

        // Toggle text & logos
        document.querySelectorAll('.sidebar-text').forEach(text => {
            text.classList.toggle('hidden', !currentlyCollapsed);
        });

        if (!currentlyCollapsed) {
            sidebarLogo.classList.add('hidden');
            sonlyLogo.classList.remove('hidden');
        } else {
            sidebarLogo.classList.remove('hidden');
            sonlyLogo.classList.add('hidden');
        }
    }

    updateDropdownIndicators();
}

function updateDropdownIndicators() {
    const sidebar = document.getElementById('sidebar');
    const isCollapsed = sidebar.classList.contains('w-20') && !isMobileView();

    document.querySelectorAll('.dropdown-icon').forEach(icon => {
        const parentDropdown = icon.closest('.menu-dropdown');
        const isOpen = parentDropdown ? parentDropdown.classList.contains('active') : false;
        if (isCollapsed) {
            icon.setAttribute('data-lucide', isOpen ? 'plus' : 'minus');
        } else {
            icon.setAttribute('data-lucide', isOpen ? 'chevron-down' : 'chevron-right');
        }
    });

    // Re-render all icons
    lucide.createIcons();
}

function handleResize() {
    const sidebar = document.getElementById('sidebar');
    const sidebarLogo = document.getElementById('sidebar-logo');
    const sonlyLogo = document.getElementById('sonly');

    if (isMobileView()) {
        // Reset to mobile closed state
        sidebar.classList.remove('w-64', 'w-20');
        sidebar.classList.add('-translate-x-full');
        sidebarLogo.classList.remove('hidden');
        sonlyLogo.classList.add('hidden');
    } else {
        const collapsedState = localStorage.getItem('sidebarCollapsed') === 'true';
        sidebar.classList.remove('-translate-x-full', 'translate-x-0');
        sidebar.classList.toggle('w-20', collapsedState);
        sidebar.classList.toggle('w-64', !collapsedState);

        document.querySelectorAll('.sidebar-text').forEach(text => {
            text.classList.toggle('hidden', collapsedState);
        });

        if (collapsedState) {
            sidebarLogo.classList.add('hidden');
            sonlyLogo.classList.remove('hidden');
        } else {
            sidebarLogo.classList.remove('hidden');
            sonlyLogo.classList.add('hidden');
        }
    }

    updateDropdownIndicators();
}

// Dropdown functionality
document.addEventListener('DOMContentLoaded', function() {
    // Initialize Lucide icons
    lucide.createIcons();
    
    // Handle dropdown click events
    const dropdownToggles = document.querySelectorAll('.dropdown-toggle');
    
    dropdownToggles.forEach(toggle => {
        toggle.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            const parentDropdown = this.closest('.menu-dropdown');
            const content = parentDropdown.querySelector('.dropdown-content');
            const arrow = this.querySelector('.dropdown-arrow');
            
            // Check if this dropdown is currently open
            const isCurrentlyOpen = parentDropdown.classList.contains('active');
            
            // Close all other dropdowns first
            document.querySelectorAll('.menu-dropdown.active').forEach(otherDropdown => {
                if (otherDropdown !== parentDropdown) {
                    otherDropdown.classList.remove('active');
                    const otherContent = otherDropdown.querySelector('.dropdown-content');
                    const otherArrow = otherDropdown.querySelector('.dropdown-arrow');
                    otherContent.style.maxHeight = '0';
                    otherContent.style.opacity = '0';
                    if (otherArrow) otherArrow.style.transform = '';
                }
            });
            
            // Toggle current dropdown
            if (!isCurrentlyOpen) {
                parentDropdown.classList.add('active');
                content.style.maxHeight = content.scrollHeight + 'px';
                content.style.opacity = '1';
                if (arrow) arrow.style.transform = 'rotate(180deg)';
            } else {
                parentDropdown.classList.remove('active');
                content.style.maxHeight = '0';
                content.style.opacity = '0';
                if (arrow) arrow.style.transform = '';
            }
            
            updateDropdownIndicators();
        });
    });
    
    // Close dropdowns when clicking outside
    document.addEventListener('click', function(e) {
        if (!e.target.closest('.menu-dropdown')) {
            document.querySelectorAll('.menu-dropdown.active').forEach(dropdown => {
                dropdown.classList.remove('active');
                const content = dropdown.querySelector('.dropdown-content');
                const arrow = dropdown.querySelector('.dropdown-arrow');
                content.style.maxHeight = '0';
                content.style.opacity = '0';
                if (arrow) arrow.style.transform = '';
            });
            updateDropdownIndicators();
        }
    });
    
    // Highlight current page in sidebar
    const currentPath = window.location.pathname;
    const sidebarLinks = document.querySelectorAll('nav a[href]');
    
    sidebarLinks.forEach(link => {
        const linkPath = link.getAttribute('href');
        if (currentPath.includes(linkPath) && linkPath !== '/') {
            link.classList.add('active');
            
            // If it's in a dropdown, expand the parent dropdown
            const dropdown = link.closest('.menu-dropdown');
            if (dropdown) {
                dropdown.classList.add('active');
                const content = dropdown.querySelector('.dropdown-content');
                const arrow = dropdown.querySelector('.dropdown-arrow');
                content.style.maxHeight = content.scrollHeight + 'px';
                content.style.opacity = '1';
                if (arrow) arrow.style.transform = 'rotate(180deg)';
            }
        }
    });
    
    // Apply initial state
    handleResize();
    window.addEventListener('resize', handleResize);
    
    // Mark sidebar as loaded for fade-in effect
    setTimeout(() => {
        document.getElementById('sidebar').classList.add('loaded');
    }, 100);
});
</script>