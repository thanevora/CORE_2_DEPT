    <?php
    $role = $_SESSION['role'] ?? 'guest';
    $permissions = include 'USM/role_permissions.php';
    $allowed_modules = $permissions[$role] ?? [];
    $is_supervisor = ($role === 'supervisor' || $role === 'admin');

    // Define base path for consistent URL structure
    $base_url = 'https://restaurant.soliera-hotel-restaurant.com/'; // Correct full URL
    ?>

    <div class="bg-[#001f54] pt-5 pb-4 flex flex-col fixed md:relative h-full transition-all duration-300 ease-in-out shadow-xl transform -translate-x-full md:transform-none md:translate-x-0" id="sidebar">
        <!-- Sidebar Header -->
        <div class="flex items-center justify-between flex-shrink-0 px-4 mb-6 text-center">
            <h1 class="text-xl font-bold text-white flex items-center gap-2">
                <img src="<?php echo $base_url; ?>images/tagline_no_bg.png" 
                    alt="Logo" 
                    class="h-25 w-auto">
            </h1>
        </div>

        <!-- Navigation Menu -->
        <div class="flex-1 flex flex-col overflow-hidden hover:overflow-y-auto">
            <nav class="flex-1 px-2 space-y-1">
                <!-- ANALYTICS & REPORTING SECTION -->
                <?php if ($is_supervisor || in_array('analytics', $allowed_modules)): ?>
                <div class="px-4 py-2 mt-2">
                    <p class="text-xs font-semibold text-blue-300 uppercase tracking-wider">Analytics & Reporting</p>
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
                    <p class="text-xs font-semibold text-blue-300 uppercase tracking-wider">Reservation Management</p>
                </div>
                
                <?php if ($is_supervisor || in_array('table_reservation', $allowed_modules)): ?>
                <a href="<?php echo $base_url; ?>/M1/calendar.php" class="block">
                    <div class="flex items-center px-4 py-3 text-sm font-medium rounded-lg transition-all hover:bg-blue-600/50 text-white group">
                        <div class="p-1.5 rounded-lg bg-blue-800/30 group-hover:bg-blue-700/50 transition-colors">
                            <i data-lucide="table" class="w-5 h-5 text-[#F7B32B] group-hover:text-white"></i>
                        </div>
                        <span class="ml-3 sidebar-text">Table Reservation</span>
                    </div>
                </a>
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
                    <p class="text-xs font-semibold text-blue-300 uppercase tracking-wider">Operations & Performance</p>
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
                    <p class="text-xs font-semibold text-blue-300 uppercase tracking-wider">Menu & Ordering</p>
                </div>
                
                <?php if ($is_supervisor || in_array('menu_management', $allowed_modules)): ?>
                <a href="<?php echo $base_url; ?>/M3/main.php" class="block">
                    <div class="flex items-center px-4 py-3 text-sm font-medium rounded-lg transition-all hover:bg-blue-600/50 text-white group">
                        <div class="p-1.5 rounded-lg bg-blue-800/30 group-hover:bg-blue-700/50 transition-colors">
                            <i data-lucide="notebook-text" class="w-5 h-5 text-[#F7B32B] group-hover:text-white"></i>
                        </div>
                        <span class="ml-3 sidebar-text">Menu Management</span>
                    </div>
                </a>
                <?php endif; ?>
                
                <?php if ($is_supervisor || in_array('pos', $allowed_modules)): ?>
                <a href="<?php echo $base_url; ?>/M4/admin_main.php" class="block">
                    <div class="flex items-center px-4 py-3 text-sm font-medium rounded-lg transition-all hover:bg-blue-600/50 text-white group">
                        <div class="p-1.5 rounded-lg bg-blue-800/30 group-hover:bg-blue-700/50 transition-colors">
                            <i data-lucide="shopping-cart" class="w-5 h-5 text-[#F7B32B] group-hover:text-white"></i>
                        </div>
                        <span class="ml-3 sidebar-text">POS & Ordering</span>
                    </div>
                </a>
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
                    <p class="text-xs font-semibold text-blue-300 uppercase tracking-wider">Finance</p>
                </div>
                <a href="<?php echo $base_url; ?>/M7/main.php" class="block">
                    <div class="flex items-center px-4 py-3 text-sm font-medium rounded-lg transition-all hover:bg-blue-600/50 text-white group">
                        <div class="p-1.5 rounded-lg bg-blue-800/30 group-hover:bg-blue-700/50 transition-colors">
                            <i data-lucide="printer" class="w-5 h-5 text-[#F7B32B] group-hover:text-white"></i>
                        </div>
                        <span class="ml-3 sidebar-text">Billing & Payments</span>
                    </div>
                </a>
                <?php endif; ?>

                <!-- INVENTORY & SUPPLIES SECTION -->
                <?php if ($is_supervisor || in_array('inventory', $allowed_modules)): ?>
                <div class="px-4 py-2 mt-4">
                    <p class="text-xs font-semibold text-blue-300 uppercase tracking-wider">Inventory & Supplies</p>
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
                    <p class="text-xs font-semibold text-blue-300 uppercase tracking-wider">Staff & Administration</p>
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
                <!-- Only User Management keeps its dropdown -->
                <div class="collapse group">
                    <input type="checkbox" class="peer" /> 
                    <div class="collapse-title flex items-center justify-between px-4 py-3 text-sm font-medium rounded-lg transition-all peer-checked:bg-blue-600/50 text-white group">
                        <div class="flex items-center">
                            <div class="p-1.5 rounded-lg bg-blue-800/30 group-hover:bg-blue-700/50 transition-colors">
                                <i data-lucide="users" class="w-5 h-5 text-[#F7B32B] group-hover:text-white"></i>
                            </div>
                            <span class="ml-3 sidebar-text">User Management</span>
                        </div>
                        <i class="w-4 h-4 text-blue-200 transform transition-transform duration-200 peer-checked:rotate-90 dropdown-icon" data-lucide="chevron-down"></i>
                    </div>
                    <div class="collapse-content pl-14 pr-4 py-1 space-y-1"> 
                        <a href="<?php echo $base_url; ?>/USM/department_accounts.php" class="block px-3 py-2 text-sm rounded-lg transition-all hover:bg-blue-600/30 text-blue-100 hover:text-white">
                            <span class="flex items-center gap-2">
                                <i data-lucide="user-cog" class="w-4 h-4 text-[#F7B32B]"></i>
                                Department Accounts
                            </span>
                        </a>
                       
                        <a href="<?php echo $base_url; ?>/USM/audit_trail&transaction.php" class="block px-3 py-2 text-sm rounded-lg transition-all hover:bg-blue-600/30 text-blue-100 hover:text-white">
                            <span class="flex items-center gap-2">
                                <i data-lucide="history" class="w-4 h-4 text-[#F7B32B]"></i>
                                Audit Trail & Transaction
                            </span>
                        </a>
                    </div>
                </div>
                <?php endif; ?>
                <?php endif; ?>

                <!-- Logout -->
                <div class="px-4 py-2 mt-4">
                    <p class="text-xs font-semibold text-blue-300 uppercase tracking-wider">Account</p>
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