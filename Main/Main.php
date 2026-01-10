<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Restaurant</title>
  <link href="https://cdn.jsdelivr.net/npm/daisyui@3.9.4/dist/full.css" rel="stylesheet" type="text/css" />
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://unpkg.com/lucide@latest"></script>
  <link rel="stylesheet" href="../CSS/soliera.css">
  <link rel="stylesheet" href="../CSS/sidebar.css">
  
</head>
<body class="bg-base-100">
  <div class="flex h-screen overflow-hidden">
    <!-- Sidebar -->

<?php include '../sidebarr.php'; ?>
    <!-- Main content -->
    <div class="flex flex-col flex-1 overflow-hidden">
    
    <?php include '../navbar.php'; ?>

      <!-- Dashboard Content -->
      <main class="flex-1 overflow-y-auto p-4 sm:p-6 lg:p-8 transition-slow">
        <div class="pb-5 border-b border-base-300 animate-fadeIn">
          <h1 class="text-2xl font-semibold bg-white bg-clip-text text-[#191970]">Dashboard</h1>
         
        </div>

<?php include 'cards.php'; ?>

        <!-- Charts Section -->
        <div class="mt-8 grid grid-cols-1 gap-6 lg:grid-cols-2">
          <div class="card bg-base-100 shadow-lg hover:shadow-xl transition-all duration-300 animate-fadeIn" style="animation-delay: 0.5s">
            <div class="card-body">
              <div class="flex items-center justify-between">
                <h2 class="card-title bg-gradient-to-r from-primary to-secondary bg-clip-text text-transparent">Revenue Overview</h2>
                <div class="tooltip tooltip-left" data-tip="View detailed report">
                  <i data-lucide="bar-chart-2" class="w-5 h-5 text-gray-400 hover:text-primary transition-colors cursor-pointer"></i>
                </div>
              </div>
              <div class="h-64 flex items-center justify-center bg-base-200 rounded-box mt-4 hover:bg-gradient-to-br from-base-200 to-base-300 transition-all duration-500">
                <div class="text-center p-4 animate-pulse">
                  <i data-lucide="bar-chart-2" class="w-12 h-12 mx-auto text-primary/30 mb-2"></i>
                  <p class="text-gray-500">Revenue chart visualization would appear here</p>
                </div>
              </div>
            </div>
          </div>

          <div class="card bg-base-100 shadow-lg hover:shadow-xl transition-all duration-300 animate-fadeIn" style="animation-delay: 0.6s">
            <div class="card-body">
              <div class="flex items-center justify-between">
                <h2 class="card-title bg-gradient-to-r from-primary to-secondary bg-clip-text text-transparent">User Activity</h2>
                <div class="tooltip tooltip-left" data-tip="View detailed report">
                  <i data-lucide="line-chart" class="w-5 h-5 text-gray-400 hover:text-primary transition-colors cursor-pointer"></i>
                </div>
              </div>
              <div class="h-64 flex items-center justify-center bg-base-200 rounded-box mt-4 hover:bg-gradient-to-br from-base-200 to-base-300 transition-all duration-500">
                <div class="text-center p-4 animate-pulse">
                  <i data-lucide="activity" class="w-12 h-12 mx-auto text-primary/30 mb-2"></i>
                  <p class="text-gray-500">User activity chart would appear here</p>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Recent Activity -->
        <div class="card bg-base-100 shadow-lg hover:shadow-xl mt-8 transition-all duration-300 animate-fadeIn" style="animation-delay: 0.7s">
          <div class="card-body p-0">
            <div class="px-6 py-5 border-b border-base-300 flex items-center justify-between bg-gradient-to-r from-base-100 to-base-200">
              <h2 class="card-title bg-gradient-to-r from-primary to-secondary bg-clip-text text-transparent">Recent Activity</h2>
              <button class="btn btn-ghost btn-sm hover:bg-base-300 transition-all hover:scale-105">
                View All <i data-lucide="chevron-right" class="w-4 h-4 ml-1"></i>
              </button>
            </div>
            <div class="p-6">
              <ul class="space-y-4">
                <li class="flex items-center gap-4 pb-4 border-b border-base-300 hover:bg-base-200/50 px-2 py-1 rounded-lg transition-colors">
                  <div class="avatar placeholder">
                    <div class="bg-base-300 text-gray-500 rounded-full w-8 hover:ring-2 hover:ring-primary transition-all">
                      <i data-lucide="user" class="w-4 h-4"></i>
                    </div>
                  </div>
                  <div class="flex-1">
                    <p class="font-medium">New user registered</p>
                    <p class="text-sm text-gray-500">user1@example.com</p>
                  </div>
                  <span class="badge badge-success gap-1 animate-pulse">
                    <i data-lucide="sparkles" class="w-3 h-3"></i> New
                  </span>
                </li>
                <li class="flex items-center gap-4 pb-4 border-b border-base-300 hover:bg-base-200/50 px-2 py-1 rounded-lg transition-colors">
                  <div class="avatar placeholder">
                    <div class="bg-base-300 text-gray-500 rounded-full w-8 hover:ring-2 hover:ring-primary transition-all">
                      <i data-lucide="user" class="w-4 h-4"></i>
                    </div>
                  </div>
                  <div class="flex-1">
                    <p class="font-medium">New user registered</p>
                    <p class="text-sm text-gray-500">user2@example.com</p>
                  </div>
                  <span class="badge badge-success gap-1 animate-pulse">
                    <i data-lucide="sparkles" class="w-3 h-3"></i> New
                  </span>
                </li>
                <li class="flex items-center gap-4 pb-4 border-b border-base-300 hover:bg-base-200/50 px-2 py-1 rounded-lg transition-colors">
                  <div class="avatar placeholder">
                    <div class="bg-base-300 text-gray-500 rounded-full w-8 hover:ring-2 hover:ring-primary transition-all">
                      <i data-lucide="user" class="w-4 h-4"></i>
                    </div>
                  </div>
                  <div class="flex-1">
                    <p class="font-medium">New user registered</p>
                    <p class="text-sm text-gray-500">user3@example.com</p>
                  </div>
                  <span class="badge badge-success gap-1 animate-pulse">
                    <i data-lucide="sparkles" class="w-3 h-3"></i> New
                  </span>
                </li>
              </ul>
            </div>
          </div>
        </div>
      </main>
    </div>
  </div>
</body>

<script src="../JavaScript/soliera.js"></script>
<script src="../JavaScript/sidebar.js"></script>

</html>