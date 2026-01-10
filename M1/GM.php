<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Management</title>
    <link href="https://cdn.jsdelivr.net/npm/daisyui@3.9.4/dist/full.css" rel="stylesheet" type="text/css" />
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .animate-fadeIn {
            animation: fadeIn 0.5s ease-out forwards;
        }
        .guest-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }
        .favorite-icon {
            transition: all 0.3s ease;
        }
        .favorite-icon:hover {
            transform: scale(1.2);
        }
        .timeline-item:not(:last-child)::after {
            content: '';
            position: absolute;
            left: 19px;
            top: 30px;
            height: calc(100% - 30px);
            width: 2px;
            background-color: #e5e7eb;
        }
        .preference-badge {
            transition: all 0.2s ease;
        }
        .preference-badge:hover {
            transform: translateY(-2px);
        }
    </style>
</head>
<body class="bg-gray-50">
    <div class="container mx-auto px-4 py-8">
        <!-- Customer Metrics Dashboard -->
        <div class="mb-10">
            <h2 class="text-xl font-semibold mb-4 text-[#191970]">Customer Insights Dashboard</h2>
            <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4">
                <!-- Total Customers -->
                <a href="#" class="card bg-base-100 hover:bg-base-200 shadow-lg hover:shadow-xl transition-all duration-300 animate-fadeIn group" style="animation-delay: 0.1s">
                    <div class="card-body">
                        <div class="flex items-center">
                            <div class="rounded-lg p-2 bg-blue-100 text-blue-600 shadow-inner group-hover:scale-110 transition-transform">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" viewBox="0 0 20 20" fill="currentColor">
                                    <path d="M9 6a3 3 0 11-6 0 3 3 0 016 0zM17 6a3 3 0 11-6 0 3 3 0 016 0zM12.93 17c.046-.327.07-.66.07-1a6.97 6.97 0 00-1.5-4.33A5 5 0 0119 16v1h-6.07zM6 11a5 5 0 015 5v1H1v-1a5 5 0 015-5z" />
                                </svg>
                            </div>
                            <div class="ml-4">
                                <h3 class="text-sm font-medium text-gray-500">Total Customers</h3>
                                <div class="flex items-baseline">
                                    <p class="text-2xl font-semibold">1,245</p>
                                    <span class="ml-2 text-sm font-semibold text-green-600">+12.5%</span>
                                </div>
                            </div>
                        </div>
                        <div class="mt-4 grid grid-cols-2 gap-2 text-sm">
                            <div class="bg-blue-50 p-2 rounded hover:bg-blue-100 transition-colors">
                                <p class="text-gray-500">VIP</p>
                                <p class="font-semibold">248</p>
                            </div>
                            <div class="bg-blue-50 p-2 rounded hover:bg-blue-100 transition-colors">
                                <p class="text-gray-500">Regular</p>
                                <p class="font-semibold">997</p>
                            </div>
                        </div>
                    </div>
                </a>

                <!-- New Customers -->
                <a href="#" class="card bg-base-100 hover:bg-base-200 shadow-lg hover:shadow-xl transition-all duration-300 animate-fadeIn group" style="animation-delay: 0.2s">
                    <div class="card-body">
                        <div class="flex items-center">
                            <div class="rounded-lg p-2 bg-green-100 text-green-600 shadow-inner group-hover:scale-110 transition-transform">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd" />
                                </svg>
                            </div>
                            <div class="ml-4">
                                <h3 class="text-sm font-medium text-gray-500">New Customers</h3>
                                <div class="flex items-baseline">
                                    <p class="text-2xl font-semibold">84</p>
                                    <span class="ml-2 text-sm font-semibold text-green-600">+9.3%</span>
                                </div>
                            </div>
                        </div>
                        <div class="mt-4 grid grid-cols-2 gap-2 text-sm">
                            <div class="bg-green-50 p-2 rounded hover:bg-green-100 transition-colors">
                                <p class="text-gray-500">This Month</p>
                                <p class="font-semibold">32</p>
                            </div>
                            <div class="bg-green-50 p-2 rounded hover:bg-green-100 transition-colors">
                                <p class="text-gray-500">Last Month</p>
                                <p class="font-semibold">52</p>
                            </div>
                        </div>
                    </div>
                </a>

                <!-- Repeat Customers -->
                <a href="#" class="card bg-base-100 hover:bg-base-200 shadow-lg hover:shadow-xl transition-all duration-300 animate-fadeIn group" style="animation-delay: 0.3s">
                    <div class="card-body">
                        <div class="flex items-center">
                            <div class="rounded-lg p-2 bg-teal-100 text-teal-600 shadow-inner group-hover:scale-110 transition-transform">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                </svg>
                            </div>
                            <div class="ml-4">
                                <h3 class="text-sm font-medium text-gray-500">Repeat Customers</h3>
                                <div class="flex items-baseline">
                                    <p class="text-2xl font-semibold">721</p>
                                    <span class="ml-2 text-sm font-semibold text-green-600">+8.1%</span>
                                </div>
                            </div>
                        </div>
                        <div class="mt-4 grid grid-cols-2 gap-2 text-sm">
                            <div class="bg-teal-50 p-2 rounded hover:bg-teal-100 transition-colors">
                                <p class="text-gray-500">3+ Visits</p>
                                <p class="font-semibold">412</p>
                            </div>
                            <div class="bg-teal-50 p-2 rounded hover:bg-teal-100 transition-colors">
                                <p class="text-gray-500">5+ Visits</p>
                                <p class="font-semibold">309</p>
                            </div>
                        </div>
                    </div>
                </a>

                <!-- Customer Satisfaction -->
                <a href="#" class="card bg-base-100 hover:bg-base-200 shadow-lg hover:shadow-xl transition-all duration-300 animate-fadeIn group" style="animation-delay: 0.4s">
                    <div class="card-body">
                        <div class="flex items-center">
                            <div class="rounded-lg p-2 bg-amber-100 text-amber-600 shadow-inner group-hover:scale-110 transition-transform">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M3.172 5.172a4 4 0 015.656 0L10 6.343l1.172-1.171a4 4 0 115.656 5.656L10 17.657l-6.828-6.829a4 4 0 010-5.656z" clip-rule="evenodd" />
                                </svg>
                            </div>
                            <div class="ml-4">
                                <h3 class="text-sm font-medium text-gray-500">Satisfaction</h3>
                                <div class="flex items-baseline">
                                    <p class="text-2xl font-semibold">92%</p>
                                    <span class="ml-2 text-sm font-semibold text-green-600">+1.2%</span>
                                </div>
                            </div>
                        </div>
                        <div class="mt-4 grid grid-cols-2 gap-2 text-sm">
                            <div class="bg-amber-50 p-2 rounded hover:bg-amber-100 transition-colors">
                                <p class="text-gray-500">Positive</p>
                                <p class="font-semibold">89%</p>
                            </div>
                            <div class="bg-amber-50 p-2 rounded hover:bg-amber-100 transition-colors">
                                <p class="text-gray-500">Negative</p>
                                <p class="font-semibold">8%</p>
                            </div>
                        </div>
                    </div>
                </a>
            </div>
        </div>

        <!-- Customer Recommendations -->
        <div class="mb-10 bg-white p-6 rounded-lg shadow">
            <h2 class="text-xl font-semibold mb-4 text-[#191970]">Customer Engagement Recommendations</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                <!-- VIP Program -->
                <div class="card bg-gradient-to-br from-purple-50 to-blue-50 border border-purple-100">
                    <div class="card-body">
                        <div class="flex items-start">
                            <div class="bg-purple-100 text-purple-600 p-2 rounded-lg mr-3">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" viewBox="0 0 20 20" fill="currentColor">
                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                                </svg>
                            </div>
                            <div>
                                <h3 class="font-medium text-gray-800">VIP Program</h3>
                                <p class="text-sm text-gray-600 mt-1">Enroll 15 high-value customers in VIP program</p>
                                <button class="btn btn-sm btn-outline btn-primary mt-2">View List</button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Re-engagement -->
                <div class="card bg-gradient-to-br from-green-50 to-teal-50 border border-green-100">
                    <div class="card-body">
                        <div class="flex items-start">
                            <div class="bg-green-100 text-green-600 p-2 rounded-lg mr-3">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M12.586 4.586a2 2 0 112.828 2.828l-3 3a2 2 0 01-2.828 0 1 1 0 00-1.414 1.414 4 4 0 005.656 0l3-3a4 4 0 00-5.656-5.656l-1.5 1.5a1 1 0 101.414 1.414l1.5-1.5zm-5 5a2 2 0 012.828 0 1 1 0 101.414-1.414 4 4 0 00-5.656 0l-3 3a4 4 0 105.656 5.656l1.5-1.5a1 1 0 10-1.414-1.414l-1.5 1.5a2 2 0 11-2.828-2.828l3-3z" clip-rule="evenodd" />
                                </svg>
                            </div>
                            <div>
                                <h3 class="font-medium text-gray-800">Re-engagement</h3>
                                <p class="text-sm text-gray-600 mt-1">28 customers haven't visited in 3 months</p>
                                <button class="btn btn-sm btn-outline btn-success mt-2">Send Offers</button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Birthday -->
                <div class="card bg-gradient-to-br from-pink-50 to-red-50 border border-pink-100">
                    <div class="card-body">
                        <div class="flex items-start">
                            <div class="bg-pink-100 text-pink-600 p-2 rounded-lg mr-3">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M6 3a1 1 0 011-1h.01a1 1 0 010 2H7a1 1 0 01-1-1zm2 3a1 1 0 00-2 0v1a2 2 0 00-2 2v1H6a1 1 0 000 2h1v1a2 2 0 002 2h1a1 1 0 100-2H9a1 1 0 01-1-1v-1h1a1 1 0 100-2H8V8a1 1 0 011-1h1a1 1 0 100-2H9a1 1 0 01-1-1V6zm5-3a1 1 0 011 1v.01a1 1 0 010 2H13a1 1 0 01-1-1V6zm-1 5a1 1 0 00-1 1v.01a1 1 0 000 2h.01a1 1 0 000-2H12zm2 1a1 1 0 011-1h.01a1 1 0 010 2H15a1 1 0 01-1-1zm1 3h-.01a1 1 0 010-2H16a1 1 0 010 2z" clip-rule="evenodd" />
                                </svg>
                            </div>
                            <div>
                                <h3 class="font-medium text-gray-800">Birthdays</h3>
                                <p class="text-sm text-gray-600 mt-1">7 customers have birthdays this week</p>
                                <button class="btn btn-sm btn-outline btn-secondary mt-2">Send Wishes</button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Feedback -->
                <div class="card bg-gradient-to-br from-yellow-50 to-amber-50 border border-yellow-100">
                    <div class="card-body">
                        <div class="flex items-start">
                            <div class="bg-yellow-100 text-yellow-600 p-2 rounded-lg mr-3">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-8-3a1 1 0 00-.867.5 1 1 0 11-1.731-1A3 3 0 0113 8a3.001 3.001 0 01-2 2.83V11a1 1 0 11-2 0v-1a1 1 0 011-1 1 1 0 100-2zm0 8a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd" />
                                </svg>
                            </div>
                            <div>
                                <h3 class="font-medium text-gray-800">Feedback</h3>
                                <p class="text-sm text-gray-600 mt-1">Request feedback from 42 recent customers</p>
                                <button class="btn btn-sm btn-outline btn-warning mt-2">Send Survey</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Header -->
        <div class="flex justify-between items-center mb-8">
            <h1 class="text-3xl font-bold text-gray-800">Customer Management</h1>
            <div class="flex space-x-4">
                <button class="btn btn-primary" onclick="openAddGuestModal()">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd" />
                    </svg>
                    Add Customer
                </button>
                <div class="form-control">
                    <input type="text" placeholder="Search customers..." class="input input-bordered w-full max-w-xs" id="guestSearch" />
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="bg-white p-4 rounded-lg shadow mb-6">
            <div class="flex flex-wrap items-center gap-4">
                <div class="form-control">
                    <label class="label">
                        <span class="label-text">Status</span>
                    </label>
                    <select class="select select-bordered w-full max-w-xs">
                        <option disabled selected>Select status</option>
                        <option>All</option>
                        <option>VIP</option>
                        <option>Regular</option>
                        <option>Blacklisted</option>
                    </select>
                </div>
                <div class="form-control">
                    <label class="label">
                        <span class="label-text">Visits</span>
                    </label>
                    <select class="select select-bordered w-full max-w-xs">
                        <option disabled selected>Filter by visits</option>
                        <option>All</option>
                        <option>New (0 visits)</option>
                        <option>1-5 visits</option>
                        <option>5+ visits</option>
                    </select>
                </div>
                <div class="form-control">
                    <label class="label">
                        <span class="label-text">Last Visit</span>
                    </label>
                    <select class="select select-bordered w-full max-w-xs">
                        <option disabled selected>Filter by last visit</option>
                        <option>All</option>
                        <option>Last 7 days</option>
                        <option>Last 30 days</option>
                        <option>Last 6 months</option>
                    </select>
                </div>
                <button class="btn btn-ghost mt-7">Reset Filters</button>
            </div>
        </div>

        <!-- Customer List -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <!-- Customer Card 1 -->
            <div class="guest-card bg-white rounded-lg shadow-md overflow-hidden transition-all duration-300">
                <div class="p-5">
                    <div class="flex justify-between items-start">
                        <div>
                            <h3 class="text-xl font-semibold text-gray-800">John Doe</h3>
                            <p class="text-gray-600">VIP Customer</p>
                        </div>
                        <button class="favorite-icon text-yellow-400 hover:text-yellow-500">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" viewBox="0 0 20 20" fill="currentColor">
                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                            </svg>
                        </button>
                    </div>
                    <div class="mt-4 space-y-2">
                        <div class="flex items-center text-gray-600">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" viewBox="0 0 20 20" fill="currentColor">
                                <path d="M2.003 5.884L10 9.882l7.997-3.998A2 2 0 0016 4H4a2 2 0 00-1.997 1.884z" />
                                <path d="M18 8.118l-8 4-8-4V14a2 2 0 002 2h12a2 2 0 002-2V8.118z" />
                            </svg>
                            john.doe@example.com
                        </div>
                        <div class="flex items-center text-gray-600">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M7 2a2 2 0 00-2 2v12a2 2 0 002 2h6a2 2 0 002-2V4a2 2 0 00-2-2H7zm3 14a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd" />
                            </svg>
                            (555) 123-4567
                        </div>
                        <div class="flex items-center text-gray-600">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd" />
                            </svg>
                            Last visit: 2 days ago
                        </div>
                        <div class="flex items-center text-gray-600">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M3 17a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm3.293-7.707a1 1 0 011.414 0L9 10.586V3a1 1 0 112 0v7.586l1.293-1.293a1 1 0 111.414 1.414l-3 3a1 1 0 01-1.414 0l-3-3a1 1 0 010-1.414z" clip-rule="evenodd" />
                            </svg>
                            Total visits: 12
                        </div>
                    </div>
                    <div class="mt-4 flex space-x-2">
                        <button class="btn btn-sm btn-outline" onclick="openEditGuestModal()">Edit</button>
                        <button class="btn btn-sm btn-outline btn-error">Blacklist</button>
                        <button class="btn btn-sm btn-primary">View History</button>
                    </div>
                </div>
            </div>

            <!-- Customer Card 2 -->
            <div class="guest-card bg-white rounded-lg shadow-md overflow-hidden transition-all duration-300">
                <div class="p-5">
                    <div class="flex justify-between items-start">
                        <div>
                            <h3 class="text-xl font-semibold text-gray-800">Jane Smith</h3>
                            <p class="text-gray-600">Regular Customer</p>
                        </div>
                        <button class="favorite-icon text-gray-300 hover:text-yellow-400">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" viewBox="0 0 20 20" fill="currentColor">
                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                            </svg>
                        </button>
                    </div>
                    <div class="mt-4 space-y-2">
                        <div class="flex items-center text-gray-600">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" viewBox="0 0 20 20" fill="currentColor">
                                <path d="M2.003 5.884L10 9.882l7.997-3.998A2 2 0 0016 4H4a2 2 0 00-1.997 1.884z" />
                                <path d="M18 8.118l-8 4-8-4V14a2 2 0 002 2h12a2 2 0 002-2V8.118z" />
                            </svg>
                            jane.smith@example.com
                        </div>
                        <div class="flex items-center text-gray-600">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M7 2a2 2 0 00-2 2v12a2 2 0 002 2h6a2 2 0 002-2V4a2 2 0 00-2-2H7zm3 14a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd" />
                            </svg>
                            (555) 987-6543
                        </div>
                        <div class="flex items-center text-gray-600">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd" />
                            </svg>
                            Last visit: 1 week ago
                        </div>
                        <div class="flex items-center text-gray-600">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M3 17a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm3.293-7.707a1 1 0 011.414 0L9 10.586V3a1 1 0 112 0v7.586l1.293-1.293a1 1 0 111.414 1.414l-3 3a1 1 0 01-1.414 0l-3-3a1 1 0 010-1.414z" clip-rule="evenodd" />
                            </svg>
                            Total visits: 5
                        </div>
                    </div>
                    <div class="mt-4 flex space-x-2">
                        <button class="btn btn-sm btn-outline">Edit</button>
                        <button class="btn btn-sm btn-outline btn-error">Blacklist</button>
                        <button class="btn btn-sm btn-primary">View History</button>
                    </div>
                </div>
            </div>

            <!-- Customer Card 3 -->
            <div class="guest-card bg-white rounded-lg shadow-md overflow-hidden transition-all duration-300">
                <div class="p-5">
                    <div class="flex justify-between items-start">
                        <div>
                            <h3 class="text-xl font-semibold text-gray-800">Michael Johnson</h3>
                            <p class="text-gray-600">Blacklisted</p>
                        </div>
                        <button class="favorite-icon text-gray-300 hover:text-yellow-400">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" viewBox="0 0 20 20" fill="currentColor">
                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                            </svg>
                        </button>
                    </div>
                    <div class="mt-4 space-y-2">
                        <div class="flex items-center text-gray-600">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" viewBox="0 0 20 20" fill="currentColor">
                                <path d="M2.003 5.884L10 9.882l7.997-3.998A2 2 0 0016 4H4a2 2 0 00-1.997 1.884z" />
                                <path d="M18 8.118l-8 4-8-4V14a2 2 0 002 2h12a2 2 0 002-2V8.118z" />
                            </svg>
                            michael.j@example.com
                        </div>
                        <div class="flex items-center text-gray-600">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M7 2a2 2 0 00-2 2v12a2 2 0 002 2h6a2 2 0 002-2V4a2 2 0 00-2-2H7zm3 14a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd" />
                            </svg>
                            (555) 456-7890
                        </div>
                        <div class="flex items-center text-gray-600">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd" />
                            </svg>
                            Last visit: 3 months ago
                        </div>
                        <div class="flex items-center text-gray-600">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M3 17a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm3.293-7.707a1 1 0 011.414 0L9 10.586V3a1 1 0 112 0v7.586l1.293-1.293a1 1 0 111.414 1.414l-3 3a1 1 0 01-1.414 0l-3-3a1 1 0 010-1.414z" clip-rule="evenodd" />
                            </svg>
                            Total visits: 2
                        </div>
                    </div>
                    <div class="mt-4 flex space-x-2">
                        <button class="btn btn-sm btn-outline">Edit</button>
                        <button class="btn btn-sm btn-outline btn-success">Remove Blacklist</button>
                        <button class="btn btn-sm btn-primary">View History</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Pagination -->
        <div class="flex justify-center mt-8">
            <div class="btn-group">
                <button class="btn">«</button>
                <button class="btn btn-active">1</button>
                <button class="btn">2</button>
                <button class="btn">3</button>
                <button class="btn">4</button>
                <button class="btn">»</button>
            </div>
        </div>
    </div>

    <!-- Add Customer Modal -->
    <div id="addGuestModal" class="modal">
        <div class="modal-box w-11/12 max-w-5xl">
            <h3 class="font-bold text-lg">Add New Customer</h3>
            <div class="py-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="form-control">
                        <label class="label">
                            <span class="label-text">First Name</span>
                        </label>
                        <input type="text" placeholder="First name" class="input input-bordered w-full" />
                    </div>
                    <div class="form-control">
                        <label class="label">
                            <span class="label-text">Last Name</span>
                        </label>
                        <input type="text" placeholder="Last name" class="input input-bordered w-full" />
                    </div>
                    <div class="form-control">
                        <label class="label">
                            <span class="label-text">Email</span>
                        </label>
                        <input type="email" placeholder="Email" class="input input-bordered w-full" />
                    </div>
                    <div class="form-control">
                        <label class="label">
                            <span class="label-text">Phone Number</span>
                        </label>
                        <input type="tel" placeholder="Phone number" class="input input-bordered w-full" />
                    </div>
                    <div class="form-control">
                        <label class="label">
                            <span class="label-text">Customer Type</span>
                        </label>
                        <select class="select select-bordered w-full">
                            <option disabled selected>Select customer type</option>
                            <option>VIP</option>
                            <option>Regular</option>
                            <option>Staff</option>
                        </select>
                    </div>
                    <div class="form-control">
                        <label class="label">
                            <span class="label-text">Special Notes</span>
                        </label>
                        <textarea class="textarea textarea-bordered h-24" placeholder="Allergies, preferences, etc."></textarea>
                    </div>
                </div>
            </div>
            <div class="modal-action">
                <button class="btn btn-ghost" onclick="closeAddGuestModal()">Cancel</button>
                <button class="btn btn-primary">Add Customer</button>
            </div>
        </div>
    </div>

    <!-- Edit Customer Modal -->
    <div id="editGuestModal" class="modal">
        <div class="modal-box w-11/12 max-w-5xl">
            <h3 class="font-bold text-lg">Edit Customer Information</h3>
            <div class="py-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="form-control">
                        <label class="label">
                            <span class="label-text">First Name</span>
                        </label>
                        <input type="text" placeholder="First name" class="input input-bordered w-full" value="John" />
                    </div>
                    <div class="form-control">
                        <label class="label">
                            <span class="label-text">Last Name</span>
                        </label>
                        <input type="text" placeholder="Last name" class="input input-bordered w-full" value="Doe" />
                    </div>
                    <div class="form-control">
                        <label class="label">
                            <span class="label-text">Email</span>
                        </label>
                        <input type="email" placeholder="Email" class="input input-bordered w-full" value="john.doe@example.com" />
                    </div>
                    <div class="form-control">
                        <label class="label">
                            <span class="label-text">Phone Number</span>
                        </label>
                        <input type="tel" placeholder="Phone number" class="input input-bordered w-full" value="(555) 123-4567" />
                    </div>
                    <div class="form-control">
                        <label class="label">
                            <span class="label-text">Customer Type</span>
                        </label>
                        <select class="select select-bordered w-full">
                            <option>VIP</option>
                            <option>Regular</option>
                            <option>Staff</option>
                        </select>
                    </div>
                    <div class="form-control">
                        <label class="label">
                            <span class="label-text">Special Notes</span>
                        </label>
                        <textarea class="textarea textarea-bordered h-24" placeholder="Allergies, preferences, etc.">Prefers window seating. Allergic to peanuts.</textarea>
                    </div>
                </div>
            </div>
            <div class="modal-action">
                <button class="btn btn-ghost" onclick="closeEditGuestModal()">Cancel</button>
                <button class="btn btn-primary">Save Changes</button>
            </div>
        </div>
    </div>

    <script>
        // Search functionality
        document.getElementById('guestSearch').addEventListener('input', function(e) {
            const searchTerm = e.target.value.toLowerCase();
            const guestCards = document.querySelectorAll('.guest-card');
            
            guestCards.forEach(card => {
                const name = card.querySelector('h3').textContent.toLowerCase();
                if (name.includes(searchTerm)) {
                    card.style.display = 'block';
                } else {
                    card.style.display = 'none';
                }
            });
        });

        // Modal functions
        function openAddGuestModal() {
            document.getElementById('addGuestModal').classList.add('modal-open');
        }

        function closeAddGuestModal() {
            document.getElementById('addGuestModal').classList.remove('modal-open');
        }

        function openEditGuestModal() {
            document.getElementById('editGuestModal').classList.add('modal-open');
        }

        function closeEditGuestModal() {
            document.getElementById('editGuestModal').classList.remove('modal-open');
        }

        // Toggle favorite
        document.querySelectorAll('.favorite-icon').forEach(icon => {
            icon.addEventListener('click', function() {
                if (this.classList.contains('text-yellow-400')) {
                    this.classList.remove('text-yellow-400');
                    this.classList.add('text-gray-300');
                } else {
                    this.classList.remove('text-gray-300');
                    this.classList.add('text-yellow-400');
                }
            });
        });
    </script>
</body>
</html>