<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Floor Plan Manager</title>
    <!-- Tailwind CSS via CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- DaisyUI -->
    <link href="https://cdn.jsdelivr.net/npm/daisyui@3.9.4/dist/full.css" rel="stylesheet" type="text/css" />
    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');
        
        :root {
            --primary-blue: #2563eb;
            --dark-blue: #1e40af;
            --accent-green: #10b981;
            --accent-yellow: #f59e0b;
        }
        
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f8fafc;
        }
        
        .glass-effect {
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
        }
        
        .table-available {
            background-color: rgba(16, 185, 129, 0.15);
            border-color: var(--accent-green);
        }
        
        .table-reserved {
            background-color: rgba(245, 158, 11, 0.15);
            border-color: var(--accent-yellow);
        }
        
        .table-occupied {
            background-color: rgba(239, 68, 68, 0.15);
            border-color: #ef4444;
        }
        
        .floor-canvas {
            background-image: 
                linear-gradient(rgba(30, 64, 175, 0.05) 1px, transparent 1px),
                linear-gradient(90deg, rgba(30, 64, 175, 0.05) 1px, transparent 1px);
            background-size: 20px 20px;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .animate-fadeIn {
            animation: fadeIn 0.5s ease-out forwards;
        }
    </style>
</head>
<body class="min-h-screen bg-gradient-to-br from-slate-50 to-blue-50">
    <div class="container mx-auto p-4 max-w-7xl">
        <!-- Reservations Dashboard -->
        <div class="mb-10">
            <h2 class="text-xl font-semibold mb-4 text-[#191970]">Reservations Dashboard</h2>
            <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4">
                <!-- Today's Reservations -->
                <a href="#" class="card bg-base-100 hover:bg-base-200 shadow-lg hover:shadow-xl transition-all duration-300 animate-fadeIn group" style="animation-delay: 0.1s">
                    <div class="card-body">
                        <div class="flex items-center">
                            <div class="rounded-lg p-2 bg-blue-100 text-blue-600 shadow-inner group-hover:scale-110 transition-transform">
                                <i data-lucide="calendar-check" class="w-6 h-6"></i>
                            </div>
                            <div class="ml-4">
                                <h3 class="text-sm font-medium text-gray-500">Today's Reservations</h3>
                                <div class="flex items-baseline">
                                    <p class="text-2xl font-semibold">24</p>
                                    <span class="ml-2 text-sm font-semibold text-green-600">+12.5%</span>
                                </div>
                            </div>
                        </div>
                        <div class="mt-4 grid grid-cols-2 gap-2 text-sm">
                            <div class="bg-blue-50 p-2 rounded hover:bg-blue-100 transition-colors">
                                <p class="text-gray-500">Lunch</p>
                                <p class="font-semibold">14</p>
                            </div>
                            <div class="bg-blue-50 p-2 rounded hover:bg-blue-100 transition-colors">
                                <p class="text-gray-500">Dinner</p>
                                <p class="font-semibold">10</p>
                            </div>
                        </div>
                    </div>
                </a>

                <!-- New Reservations -->
                <a href="#" class="card bg-base-100 hover:bg-base-200 shadow-lg hover:shadow-xl transition-all duration-300 animate-fadeIn group" style="animation-delay: 0.2s">
                    <div class="card-body">
                        <div class="flex items-center">
                            <div class="rounded-lg p-2 bg-green-100 text-green-600 shadow-inner group-hover:scale-110 transition-transform">
                                <i data-lucide="user-plus" class="w-6 h-6"></i>
                            </div>
                            <div class="ml-4">
                                <h3 class="text-sm font-medium text-gray-500">New Reservations</h3>
                                <div class="flex items-baseline">
                                    <p class="text-2xl font-semibold">8</p>
                                    <span class="ml-2 text-sm font-semibold text-green-600">+9.3%</span>
                                </div>
                            </div>
                        </div>
                        <div class="mt-4 grid grid-cols-2 gap-2 text-sm">
                            <div class="bg-green-50 p-2 rounded hover:bg-green-100 transition-colors">
                                <p class="text-gray-500">Website</p>
                                <p class="font-semibold">5</p>
                            </div>
                            <div class="bg-green-50 p-2 rounded hover:bg-green-100 transition-colors">
                                <p class="text-gray-500">Phone</p>
                                <p class="font-semibold">3</p>
                            </div>
                        </div>
                    </div>
                </a>

                <!-- Approved Reservations -->
                <a href="#" class="card bg-base-100 hover:bg-base-200 shadow-lg hover:shadow-xl transition-all duration-300 animate-fadeIn group" style="animation-delay: 0.3s">
                    <div class="card-body">
                        <div class="flex items-center">
                            <div class="rounded-lg p-2 bg-teal-100 text-teal-600 shadow-inner group-hover:scale-110 transition-transform">
                                <i data-lucide="check-circle" class="w-6 h-6"></i>
                            </div>
                            <div class="ml-4">
                                <h3 class="text-sm font-medium text-gray-500">Approved</h3>
                                <div class="flex items-baseline">
                                    <p class="text-2xl font-semibold">15</p>
                                    <span class="ml-2 text-sm font-semibold text-green-600">+8.1%</span>
                                </div>
                            </div>
                        </div>
                        <div class="mt-4 grid grid-cols-2 gap-2 text-sm">
                            <div class="bg-teal-50 p-2 rounded hover:bg-teal-100 transition-colors">
                                <p class="text-gray-500">Confirmed</p>
                                <p class="font-semibold">12</p>
                            </div>
                            <div class="bg-teal-50 p-2 rounded hover:bg-teal-100 transition-colors">
                                <p class="text-gray-500">Pre-paid</p>
                                <p class="font-semibold">3</p>
                            </div>
                        </div>
                    </div>
                </a>

                <!-- Pending Reservations -->
                <a href="#" class="card bg-base-100 hover:bg-base-200 shadow-lg hover:shadow-xl transition-all duration-300 animate-fadeIn group" style="animation-delay: 0.4s">
                    <div class="card-body">
                        <div class="flex items-center">
                            <div class="rounded-lg p-2 bg-amber-100 text-amber-600 shadow-inner group-hover:scale-110 transition-transform">
                                <i data-lucide="clock" class="w-6 h-6"></i>
                            </div>
                            <div class="ml-4">
                                <h3 class="text-sm font-medium text-gray-500">Pending</h3>
                                <div class="flex items-baseline">
                                    <p class="text-2xl font-semibold">2</p>
                                    <span class="ml-2 text-sm font-semibold text-red-600">+1.2%</span>
                                </div>
                            </div>
                        </div>
                        <div class="mt-4 grid grid-cols-2 gap-2 text-sm">
                            <div class="bg-amber-50 p-2 rounded hover:bg-amber-100 transition-colors">
                                <p class="text-gray-500">Waiting</p>
                                <p class="font-semibold">1</p>
                            </div>
                            <div class="bg-amber-50 p-2 rounded hover:bg-amber-100 transition-colors">
                                <p class="text-gray-500">Unconfirmed</p>
                                <p class="font-semibold">1</p>
                            </div>
                        </div>
                    </div>
                </a>
            </div>
        </div>

        <!-- Header -->
        <header class="mb-8">
            <h1 class="text-4xl font-bold bg-gradient-to-r from-blue-600 to-blue-800 bg-clip-text text-transparent">Table Reservation System</h1>
            <p class="text-blue-800/70 font-medium">Floor Plan Management</p>
        </header>

        <!-- Control Panel -->
        <div class="glass-effect rounded-xl shadow-lg p-4 mb-6 border border-blue-100">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div class="flex items-center gap-4">
                    <select class="select select-bordered w-48 bg-white border-blue-200 focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
                        <option disabled selected>Select Floor</option>
                        <option>Main Dining Area</option>
                        <option>Private Rooms</option>
                        <option>Outdoor Patio</option>
                    </select>
                    
                    <button class="btn bg-blue-600 hover:bg-blue-700 border-blue-600 hover:border-blue-700 text-white">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd" />
                        </svg>
                        Add Floor
                    </button>
                </div>
                
                <div class="flex items-center gap-4">
                    <div class="btn-group">
                        <button class="btn bg-blue-600 hover:bg-blue-700 border-blue-600 hover:border-blue-700 text-white">View</button>
                        <button class="btn bg-white hover:bg-blue-50 text-blue-600 border-blue-200 hover:border-blue-300">Edit</button>
                    </div>
                    
                    <button class="btn bg-white hover:bg-blue-50 text-blue-600 border-blue-200 hover:border-blue-300">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M3 17a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm3.293-7.707a1 1 0 011.414 0L9 10.586V3a1 1 0 112 0v7.586l1.293-1.293a1 1 0 111.414 1.414l-3 3a1 1 0 01-1.414 0l-3-3a1 1 0 010-1.414z" clip-rule="evenodd" />
                        </svg>
                        Export
                    </button>
                    
                    <button class="btn bg-white hover:bg-blue-50 text-blue-600 border-blue-200 hover:border-blue-300">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M5 4v3H4a2 2 0 00-2 2v3a2 2 0 002 2h1v2a2 2 0 002 2h6a2 2 0 002-2v-2h1a2 2 0 002-2V9a2 2 0 00-2-2h-1V4a2 2 0 00-2-2H7a2 2 0 00-2 2zm8 0H7v3h6V4zm0 8H7v4h6v-4z" clip-rule="evenodd" />
                        </svg>
                        Print
                    </button>
                </div>
            </div>
        </div>

        <!-- Main Content Area -->
        <div class="flex flex-col lg:flex-row gap-6">
            <!-- Tools Panel -->
            <div class="glass-effect rounded-xl shadow-lg p-5 w-full lg:w-72 border border-blue-100">
                <h2 class="text-lg font-semibold mb-4 text-blue-800">Table Tools</h2>
                
                <div class="space-y-4">
                    <button class="btn btn-block bg-green-500 hover:bg-green-600 border-green-500 hover:border-green-600 text-white">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd" />
                        </svg>
                        Add Table
                    </button>
                    
                    <div class="form-control">
                        <label class="label">
                            <span class="label-text text-blue-800/80">Table Shape</span>
                        </label>
                        <select class="select select-bordered bg-white border-blue-200 focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
                            <option>Round</option>
                            <option>Square</option>
                            <option>Rectangle</option>
                            <option>Booth</option>
                        </select>
                    </div>
                    
                    <div class="form-control">
                        <label class="label">
                            <span class="label-text text-blue-800/80">Seat Count</span>
                        </label>
                        <input type="range" min="1" max="12" value="4" class="range range-xs range-primary" />
                        <div class="w-full flex justify-between text-xs px-2 text-blue-800/70">
                            <span>1</span>
                            <span>2</span>
                            <span>3</span>
                            <span>4</span>
                            <span>5</span>
                            <span>6</span>
                            <span>7</span>
                            <span>8</span>
                            <span>9</span>
                            <span>10</span>
                            <span>11</span>
                            <span>12</span>
                        </div>
                    </div>
                    
                    <div class="form-control">
                        <label class="label">
                            <span class="label-text text-blue-800/80">Table Number</span>
                        </label>
                        <input type="text" placeholder="T-1" class="input input-bordered bg-white border-blue-200 focus:border-blue-500 focus:ring-1 focus:ring-blue-500" />
                    </div>
                    
                    <button class="btn btn-block bg-red-500 hover:bg-red-600 border-red-500 hover:border-red-600 text-white">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd" />
                        </svg>
                        Delete Table
                    </button>
                </div>
            </div>
            
            <!-- Floor Plan Canvas -->
            <div class="flex-1 glass-effect rounded-xl shadow-lg p-5 relative border border-blue-100">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-xl font-semibold text-blue-800">Main Dining Area</h2>
                    <div class="flex gap-2">
                        <button class="btn btn-sm btn-square bg-white hover:bg-blue-50 text-blue-600 border-blue-200 hover:border-blue-300">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
                            </svg>
                        </button>
                        <button class="btn btn-sm btn-square bg-white hover:bg-blue-50 text-blue-600 border-blue-200 hover:border-blue-300">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                            </svg>
                        </button>
                        <span class="text-sm text-blue-800/70">100%</span>
                    </div>
                </div>
                
                <div id="floorCanvas" class="floor-canvas border-2 border-dashed border-blue-200 rounded-xl h-[600px] relative overflow-hidden">
                    <!-- Tables will be added here dynamically -->
                    <div class="absolute w-16 h-16 rounded-full table-available flex items-center justify-center cursor-move shadow-md" style="top: 100px; left: 150px;" data-table-id="1">
                        <span class="font-bold text-green-700">T1</span>
                        <div class="absolute bottom-0 text-xs text-green-700 font-medium">4 seats</div>
                    </div>
                    
                    <div class="absolute w-20 h-20 table-reserved flex items-center justify-center cursor-move shadow-md" style="top: 100px; left: 300px;" data-table-id="2">
                        <span class="font-bold text-yellow-700">T2</span>
                        <div class="absolute bottom-0 text-xs text-yellow-700 font-medium">2 seats</div>
                    </div>
                    
                    <div class="absolute w-24 h-16 table-occupied flex items-center justify-center cursor-move shadow-md" style="top: 250px; left: 200px;" data-table-id="3">
                        <span class="font-bold text-red-700">T3</span>
                        <div class="absolute bottom-0 text-xs text-red-700 font-medium">6 seats</div>
                    </div>
                    
                    <div class="absolute w-16 h-16 rounded-full table-available flex items-center justify-center cursor-move shadow-md" style="top: 400px; left: 100px;" data-table-id="4">
                        <span class="font-bold text-green-700">T4</span>
                        <div class="absolute bottom-0 text-xs text-green-700 font-medium">4 seats</div>
                    </div>
                    
                    <!-- Restaurant elements -->
                    <div class="absolute bottom-0 left-0 right-0 h-20 bg-blue-100/50 border-t border-blue-200 flex items-center justify-center">
                        <span class="font-semibold text-blue-800">Entrance</span>
                    </div>
                    
                    <div class="absolute top-20 right-0 w-40 h-40 bg-blue-100/50 border-l border-b border-blue-200 flex items-center justify-center rounded-bl-xl">
                        <span class="font-semibold text-blue-800">Kitchen</span>
                    </div>
                    
                    <div class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 w-32 h-32 bg-blue-100/30 border-2 border-dashed border-blue-300 rounded-full flex items-center justify-center">
                        <span class="text-blue-800/70 font-medium">Service Station</span>
                    </div>
                </div>
            </div>
            
            <!-- Table Details Panel -->
            <div class="glass-effect rounded-xl shadow-lg p-5 w-full lg:w-72 border border-blue-100">
                <h2 class="text-lg font-semibold mb-4 text-blue-800">Table Details</h2>
                
                <div class="space-y-4">
                    <div class="flex flex-col items-center">
                        <div class="w-20 h-20 rounded-full table-available mb-3 flex items-center justify-center shadow-md">
                            <span class="font-bold text-green-700">T1</span>
                        </div>
                        <h3 class="font-bold text-blue-800">Table T1</h3>
                        <p class="text-sm text-blue-800/70">Round Table</p>
                    </div>
                    
                    <div class="stats shadow bg-white">
                        <div class="stat">
                            <div class="stat-title text-blue-800/70">Status</div>
                            <div class="stat-value text-green-500">Available</div>
                            <div class="stat-desc text-blue-800/60">No reservation</div>
                        </div>
                    </div>
                    
                    <div class="stats shadow bg-white">
                        <div class="stat">
                            <div class="stat-title text-blue-800/70">Seats</div>
                            <div class="stat-value text-blue-600">4</div>
                            <div class="stat-desc text-blue-800/60">Standard seating</div>
                        </div>
                    </div>
                    
                    <div class="divider text-blue-800/30 before:bg-blue-200 after:bg-blue-200">Details</div>
                    
                    <div>
                        <h4 class="font-semibold mb-2 text-blue-800">Reservation</h4>
                        <button class="btn btn-block bg-blue-600 hover:bg-blue-700 border-blue-600 hover:border-blue-700 text-white">
                            Create Reservation
                        </button>
                        <button class="btn btn-block bg-white hover:bg-blue-50 text-blue-600 border-blue-200 hover:border-blue-300 mt-2">
                            View Calendar
                        </button>
                    </div>
                    
                    <div class="divider text-blue-800/30 before:bg-blue-200 after:bg-blue-200">Notes</div>
                    
                    <div>
                        <textarea class="textarea textarea-bordered w-full bg-white border-blue-200 focus:border-blue-500 focus:ring-1 focus:ring-blue-500" placeholder="Add notes about this table..."></textarea>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Enhanced interactivity for the floor plan
        document.addEventListener('DOMContentLoaded', function() {
            const floorCanvas = document.getElementById('floorCanvas');
            const tables = document.querySelectorAll('[data-table-id]');
            let isDragging = false;
            let currentTable = null;
            let offsetX, offsetY;
            
            // Make tables draggable
            tables.forEach(table => {
                table.addEventListener('mousedown', startDrag);
                table.addEventListener('mouseenter', () => {
                    if (!isDragging) {
                        table.style.transform = 'scale(1.05)';
                        table.style.transition = 'transform 0.2s ease';
                    }
                });
                table.addEventListener('mouseleave', () => {
                    if (!isDragging) {
                        table.style.transform = '';
                    }
                });
            });
            
            function startDrag(e) {
                if (e.button !== 0) return; // Only left mouse button
                
                isDragging = true;
                currentTable = e.target.closest('[data-table-id]');
                const rect = currentTable.getBoundingClientRect();
                
                offsetX = e.clientX - rect.left;
                offsetY = e.clientY - rect.top;
                
                currentTable.style.zIndex = 1000;
                currentTable.style.boxShadow = '0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04)';
                document.body.style.cursor = 'grabbing';
                
                e.preventDefault();
            }
            
            document.addEventListener('mousemove', dragTable);
            document.addEventListener('mouseup', stopDrag);
            
            function dragTable(e) {
                if (!isDragging) return;
                
                const canvasRect = floorCanvas.getBoundingClientRect();
                let x = e.clientX - canvasRect.left - offsetX;
                let y = e.clientY - canvasRect.top - offsetY;
                
                // Constrain to canvas
                x = Math.max(0, Math.min(x, canvasRect.width - currentTable.offsetWidth));
                y = Math.max(0, Math.min(y, canvasRect.height - currentTable.offsetHeight));
                
                currentTable.style.left = `${x}px`;
                currentTable.style.top = `${y}px`;
            }
            
            function stopDrag() {
                if (!isDragging) return;
                
                isDragging = false;
                currentTable.style.zIndex = '';
                currentTable.style.boxShadow = '0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06)';
                currentTable.style.transform = '';
                document.body.style.cursor = '';
                currentTable = null;
            }
            
            // Table selection
            tables.forEach(table => {
                table.addEventListener('click', function(e) {
                    if (isDragging) {
                        e.stopPropagation();
                        return;
                    }
                    
                    // Update details panel
                    const tableNumber = table.querySelector('span').textContent;
                    const tableStatus = table.classList.contains('table-available') ? 'Available' : 
                                      table.classList.contains('table-reserved') ? 'Reserved' : 'Occupied';
                    const statusColor = tableStatus === 'Available' ? 'text-green-500' : 
                                      tableStatus === 'Reserved' ? 'text-yellow-500' : 'text-red-500';
                    
                    // Update status display
                    const statusElement = document.querySelector('.stat-value');
                    statusElement.textContent = tableStatus;
                    statusElement.className = 'stat-value ' + statusColor;
                    
                    // Update other details
                    document.querySelector('h3').textContent = `Table ${tableNumber}`;
                    document.querySelector('.w-20.h-20 span').textContent = tableNumber;
                    
                    // Add visual feedback
                    tables.forEach(t => t.classList.remove('ring-2', 'ring-blue-500'));
                    table.classList.add('ring-2', 'ring-blue-500');
                });
            });
            
            // Initialize Lucide icons
            lucide.createIcons();
        });
    </script>
</body>
</html>