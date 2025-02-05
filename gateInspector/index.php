<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gate Inspector Dashboard - Gate Pass System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="font-sans antialiased bg-gray-50">
    <div class="flex h-screen">
        <!-- Sidebar -->
        <div class="w-64 bg-slate-800 text-white flex flex-col">
            <div class="p-6 border-b border-slate-700">
                <div class="flex items-center space-x-3">
                    <i class="fas fa-shield-alt text-2xl text-blue-400"></i>
                    <h2 class="text-xl font-bold">Gate Pass System</h2>
                </div>
                <div class="mt-2 text-sm text-gray-400">
                    Gate Inspector Dashboard
                </div>
            </div>
            
            <nav class="flex-1 p-4">
                <ul class="space-y-2">
                    <li>
                        <a href="#" class="flex items-center space-x-3 px-4 py-3 bg-slate-700 rounded-lg">
                            <i class="fas fa-home w-5"></i>
                            <span>Dashboard</span>
                        </a>
                    </li>
                    <li>
                        <a href="#" class="flex items-center space-x-3 px-4 py-3 rounded-lg hover:bg-slate-700 transition-colors">
                            <i class="fas fa-qrcode w-5"></i>
                            <span>Scan Gate Pass</span>
                        </a>
                    </li>
                    <li>
                        <a href="#" class="flex items-center space-x-3 px-4 py-3 rounded-lg hover:bg-slate-700 transition-colors">
                            <i class="fas fa-history w-5"></i>
                            <span>Recent Verifications</span>
                        </a>
                    </li>
                    <li>
                        <a href="#" class="flex items-center space-x-3 px-4 py-3 rounded-lg hover:bg-slate-700 transition-colors">
                            <i class="fas fa-exclamation-triangle w-5"></i>
                            <span>Report Issues</span>
                        </a>
                    </li>
                </ul>
            </nav>
            
            <div class="p-4 border-t border-slate-700">
                <a href="#" class="flex items-center space-x-3 px-4 py-3 text-red-400 rounded-lg hover:bg-slate-700 transition-colors">
                    <i class="fas fa-sign-out-alt w-5"></i>
                    <span>Logout</span>
                </a>
            </div>
        </div>

        <!-- Main Content -->
        <div class="flex-1 flex flex-col overflow-hidden">
            <!-- Header -->
            <header class="bg-white shadow-sm">
                <div class="flex justify-between items-center px-8 py-4">
                    <div class="flex items-center space-x-4">
                        <span class="text-lg font-semibold text-gray-700">Main Gate - North Campus</span>
                    </div>
                    <div class="flex items-center space-x-4">
                        <span class="text-gray-700">Inspector Johnson</span>
                        <button class="p-2 hover:bg-gray-100 rounded-full transition-colors">
                            <i class="fas fa-user-circle text-gray-600 text-2xl"></i>
                        </button>
                    </div>
                </div>
            </header>

            <!-- Main Content Area -->
            <main class="flex-1 overflow-y-auto p-8">
                <!-- Quick Actions -->
                <div class="mb-8">
                    <button class="bg-blue-500 text-white px-8 py-4 rounded-lg hover:bg-blue-600 transition-colors text-lg">
                        <i class="fas fa-qrcode mr-2"></i> Scan New Gate Pass
                    </button>
                </div>

                <!-- Stats Cards -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                    <!-- Today's Verifications -->
                    <div class="bg-white rounded-lg shadow-sm p-6">
                        <div class="flex justify-between items-center">
                            <div>
                                <p class="text-gray-500 text-sm">Today's Verifications</p>
                                <h3 class="text-2xl font-bold text-gray-800">45</h3>
                                <p class="text-green-500 text-sm">Processed</p>
                            </div>
                            <div class="bg-green-100 p-3 rounded-full">
                                <i class="fas fa-check-circle text-green-500"></i>
                            </div>
                        </div>
                    </div>

                    <!-- Pending Exits -->
                    <div class="bg-white rounded-lg shadow-sm p-6">
                        <div class="flex justify-between items-center">
                            <div>
                                <p class="text-gray-500 text-sm">Pending Exits</p>
                                <h3 class="text-2xl font-bold text-gray-800">12</h3>
                                <p class="text-yellow-500 text-sm">Awaiting</p>
                            </div>
                            <div class="bg-yellow-100 p-3 rounded-full">
                                <i class="fas fa-clock text-yellow-500"></i>
                            </div>
                        </div>
                    </div>

                    <!-- Issues Reported -->
                    <div class="bg-white rounded-lg shadow-sm p-6">
                        <div class="flex justify-between items-center">
                            <div>
                                <p class="text-gray-500 text-sm">Issues Reported</p>
                                <h3 class="text-2xl font-bold text-gray-800">3</h3>
                                <p class="text-red-500 text-sm">Today</p>
                            </div>
                            <div class="bg-red-100 p-3 rounded-full">
                                <i class="fas fa-exclamation-circle text-red-500"></i>
                            </div>
                        </div>
                    </div>

                    <!-- Active Hours -->
                    <div class="bg-white rounded-lg shadow-sm p-6">
                        <div class="flex justify-between items-center">
                            <div>
                                <p class="text-gray-500 text-sm">Active Hours</p>
                                <h3 class="text-2xl font-bold text-gray-800">7.5</h3>
                                <p class="text-blue-500 text-sm">Hours</p>
                            </div>
                            <div class="bg-blue-100 p-3 rounded-full">
                                <i class="fas fa-clock text-blue-500"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Gate Pass Verification Section -->
                <div class="bg-white rounded-lg shadow-sm p-6 mb-8">
                    <h3 class="text-lg font-semibold mb-4">Quick Pass Verification</h3>
                    <div class="flex gap-4">
                        <input type="text" 
                               placeholder="Enter Gate Pass ID" 
                               class="flex-1 p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <button class="bg-blue-500 text-white px-6 py-3 rounded-lg hover:bg-blue-600 transition-colors">
                            Verify
                        </button>
                    </div>
                </div>

                <!-- Recent Verifications -->
                <div class="bg-white rounded-lg shadow-sm p-6">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-semibold">Recent Verifications</h3>
                        <button class="text-blue-500 hover:text-blue-700">View All</button>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Time</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Pass ID</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Department</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap">14:30</td>
                                    <td class="px-6 py-4 whitespace-nowrap">GP0123</td>
                                    <td class="px-6 py-4 whitespace-nowrap">John Smith</td>
                                    <td class="px-6 py-4 whitespace-nowrap">Computer Science</td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                            Verified
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <button class="text-blue-600 hover:text-blue-900">Details</button>
                                    </td>
                                </tr>
                                <!-- Add more verification records -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- QR Scanner Modal (Hidden by default) -->
    <div class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center">
        <div class="bg-white p-6 rounded-lg w-96">
            <h3 class="text-lg font-semibold mb-4">Scan QR Code</h3>
            <div class="bg-gray-100 h-64 rounded-lg mb-4 flex items-center justify-center">
                <!-- QR Scanner View -->
                <i class="fas fa-qrcode text-6xl text-gray-400"></i>
            </div>
            <div class="flex justify-end space-x-2">
                <button class="px-4 py-2 bg-gray-200 text-gray-700 rounded hover:bg-gray-300">Cancel</button>
                <button class="px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600">Verify</button>
            </div>
        </div>
    </div> 
</body>
</html>