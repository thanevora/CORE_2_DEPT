<?php

session_start();

?>
<!DOCTYPE html>
<html lang="en">
        <?php include '../header.php'; ?>

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <style>
        :root { --primary: #6366f1; --primary-hover: #4f46e5; }
        .glass-card { backdrop-filter: blur(8px); background: rgba(255, 255, 255, 0.8); }
        .card-hover { transition: all 0.3s ease; }
        .card-hover:hover { transform: translateY(-4px); box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1); }
        .floating { animation: float 3s ease-in-out infinite; }
        @keyframes float { 0%, 100% { transform: translateY(0); } 50% { transform: translateY(-5px); } }
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

        <div class="container mx-auto px-4 py-8">
<!-- Stats Cards (Dashboard Style) -->
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">

    <!-- Average Wait Time -->
    <div class="p-6 rounded-xl bg-white shadow-md transition-all duration-300 hover:shadow-2xl hover:scale-105 hover:bg-gray-50">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm font-medium text-[#001f54] mb-2 hover:drop-shadow-md transition-all">Avg. Wait Time</p>
                <h3 class="text-2xl font-bold text-[#001f54]" id="avg-wait-time">Loading...</h3>
            </div>
            <div class="p-3 rounded-lg bg-[#F7B32B] flex items-center justify-center transition-all duration-300 hover:bg-[#e6b024]">
                <i data-lucide="clock" class="w-6 h-6 text-[#001f54]"></i>
            </div>
        </div>
        <div class="mt-6 pt-4 border-t border-gray-200">
            <div class="flex justify-between items-center text-sm">
                <span class="text-gray-500">Today's trend</span>
                <span class="font-medium text-[#001f54]" id="wait-time-trend">-</span>
            </div>
        </div>
    </div>

    <!-- Table Turnover -->
    <div class="p-6 rounded-xl bg-white shadow-md transition-all duration-300 hover:shadow-2xl hover:scale-105 hover:bg-gray-50">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm font-medium text-[#001f54] mb-2 hover:drop-shadow-md transition-all">Avg. Table Turnover</p>
                <h3 class="text-2xl font-bold text-[#001f54]" id="table-turnover">Loading...</h3>
            </div>
            <div class="p-3 rounded-lg bg-[#F7B32B] flex items-center justify-center transition-all duration-300 hover:bg-[#e6b024]">
                <i data-lucide="repeat" class="w-6 h-6 text-[#001f54]"></i>
            </div>
        </div>
        <div class="mt-6 pt-4 border-t border-gray-200">
            <div class="flex justify-between items-center text-sm">
                <span class="text-gray-500">Per table today</span>
                <span class="text-gray-500">Max: <span id="max-turnover">-</span></span>
            </div>
        </div>
    </div>

    <!-- Current Waitlist -->
    <div class="p-6 rounded-xl bg-white shadow-md transition-all duration-300 hover:shadow-2xl hover:scale-105 hover:bg-gray-50">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm font-medium text-[#001f54] mb-2 hover:drop-shadow-md transition-all">Current Waitlist</p>
                <h3 class="text-2xl font-bold text-[#001f54]" id="current-waitlist">0</h3>
            </div>
            <div class="p-3 rounded-lg bg-[#F7B32B] flex items-center justify-center transition-all duration-300 hover:bg-[#e6b024]">
                <i data-lucide="users" class="w-6 h-6 text-[#001f54]"></i>
            </div>
        </div>
        <div class="mt-6 pt-4 border-t border-gray-200">
            <div class="flex justify-between items-center text-sm">
                <span class="text-gray-500">Parties waiting</span>
                <span class="text-gray-500">Est. wait: <span id="est-wait-time">-</span></span>
            </div>
        </div>
    </div>

    <!-- Occupancy Rate -->
    <div class="p-6 rounded-xl bg-white shadow-md transition-all duration-300 hover:shadow-2xl hover:scale-105 hover:bg-gray-50">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm font-medium text-[#001f54] mb-2 hover:drop-shadow-md transition-all">Occupancy Rate</p>
                <h3 class="text-2xl font-bold text-[#001f54]" id="occupancy-rate">0%</h3>
            </div>
            <div class="p-3 rounded-lg bg-[#F7B32B] flex items-center justify-center transition-all duration-300 hover:bg-[#e6b024]">
                <i data-lucide="home" class="w-6 h-6 text-[#001f54]"></i>
            </div>
        </div>
        <div class="mt-6 pt-4 border-t border-gray-200">
            <div class="flex justify-between items-center text-sm">
                <span class="text-gray-500">Tables occupied</span>
                <span class="text-gray-500"><span id="occupied-tables">0</span>/<span id="total-tables">0</span></span>
            </div>
        </div>
    </div>

</div>


            
            <!-- Current Waitlist Table -->
            <div class="card-hover glass-card p-6 rounded-xl border border-gray-100 mb-8">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-semibold text-gray-800">Current Waitlist</h3>
                 
                </div>
                <div class="overflow-x-auto">
                    <table class="table divide-y divide-gray-200 w-full">
                        <thead>
                            <tr class="bg-gray-50">
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Party</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Size</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Waiting Since</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Wait Time</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200" id="waitlist-body">
                            <tr>
                                <td colspan="5" class="text-center py-8 text-gray-500">Loading waitlist data...</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script>
        lucide.createIcons();
        
        // Function to fetch data from the server
        async function fetchData() {
            try {
                const response = await fetch('sub-modules/fetch_dashboard_data.php');
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                const data = await response.json();
                console.log('Data received:', data); // Debugging
                return data;
            } catch (error) {
                console.error('Error fetching data:', error);
                return {success: false, error: error.message};
            }
        }

        // Function to update the dashboard with data
        function updateDashboard(data) {
            if (!data || !data.success) {
                console.error('Error in data:', data?.error);
                return;
            }

            // Update cards
            if (data.avg_wait_time !== undefined) {
                document.getElementById('avg-wait-time').textContent = `${data.avg_wait_time} min`;
                document.getElementById('wait-time-trend').textContent = 
                    data.avg_wait_time > 15 ? 'High' : 'Low';
            }

            if (data.avg_table_turnover !== undefined) {
                document.getElementById('table-turnover').textContent = `${data.avg_table_turnover}x`;
                document.getElementById('max-turnover').textContent = `${data.max_turnover}x`;
            }

            if (data.current_waitlist !== undefined) {
                document.getElementById('current-waitlist').textContent = data.current_waitlist;
                document.getElementById('est-wait-time').textContent = `${data.est_wait_time} min`;
            }

            if (data.occupancy_rate !== undefined) {
                document.getElementById('occupancy-rate').textContent = `${data.occupancy_rate}%`;
                document.getElementById('occupied-tables').textContent = data.occupied_tables;
                document.getElementById('total-tables').textContent = data.total_tables;
            }

            // Update waitlist table
            const waitlistBody = document.getElementById('waitlist-body');
            if (data.waitlist && data.waitlist.length > 0) {
                waitlistBody.innerHTML = data.waitlist.map(party => `
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                            ${party.party_name}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            ${party.party_size}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            ${new Date(party.wait_since).toLocaleTimeString()}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            ${party.wait_time} min
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2 py-1 text-xs rounded-full ${
                                party.status === 'seated' ? 'bg-green-100 text-green-800' : 'bg-amber-100 text-amber-800'
                            }">
                                ${party.status === 'seated' ? 'Seated' : 'Waiting'}
                            </span>
                        </td>
                    </tr>
                `).join('');
            } else {
                waitlistBody.innerHTML = '<tr><td colspan="5" class="text-center py-8 text-gray-500">No parties currently waiting</td></tr>';
            }
            
            // Update last updated time
            document.getElementById('waitlist-updated').textContent = data.last_updated;
        }

        // Initialize and refresh data
        async function loadData() {
            const data = await fetchData();
            updateDashboard(data);
        }

        // Set up event listeners
        document.addEventListener('DOMContentLoaded', function() {
            loadData();
            setInterval(loadData, 30000); // Refresh every 30 seconds
            document.getElementById('refresh-waitlist-btn').addEventListener('click', loadData);
        });
    </script>
    <script src="../JavaScript/sidebar.js"></script>
</body>
</html>