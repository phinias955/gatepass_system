<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gate Pass System Dashboard</title>
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
                    <i class="fas fa-id-card text-2xl text-blue-400"></i>
                    <h2 class="text-xl font-bold">Gate Pass System</h2>
                </div>
                <div class="mt-2 text-sm text-gray-400">
                    Admin Dashboard
                </div>
            </div>
            
            <nav class="flex-1 p-4">
                <ul class="space-y-2">
                    <!-- Menu Items -->
                    <li>
                        <a href="#" class="flex items-center space-x-3 px-4 py-3 rounded-lg hover:bg-slate-700 transition-colors">
                            <i class="fas fa-home w-5"></i>
                            <span>Dashboard</span>
                        </a>
                    </li>
                    <li>
                        <a href="#" class="flex items-center space-x-3 px-4 py-3 rounded-lg hover:bg-slate-700 transition-colors">
                            <i class="fas fa-file-alt w-5"></i>
                            <span>New Gate Pass</span>
                        </a>
                    </li>
                    <li>
                        <a href="#" class="flex items-center space-x-3 px-4 py-3 rounded-lg hover:bg-slate-700 transition-colors">
                            <i class="fas fa-list w-5"></i>
                            <span>Applications</span>
                        </a>
                    </li>
                    <li>
                        <a href="#" class="flex items-center space-x-3 px-4 py-3 rounded-lg hover:bg-slate-700 transition-colors">
                            <i class="fas fa-check-circle w-5"></i>
                            <span>Approvals</span>
                        </a>
                    </li>
                    <li>
                        <a href="#" class="flex items-center space-x-3 px-4 py-3 rounded-lg hover:bg-slate-700 transition-colors">
                            <i class="fas fa-chart-bar w-5"></i>
                            <span>Reports</span>
                        </a>
                    </li>
                    <li>
                        <a href="#" class="flex items-center space-x-3 px-4 py-3 rounded-lg hover:bg-slate-700 transition-colors">
                            <i class="fas fa-cog w-5"></i>
                            <span>Settings</span>
                        </a>
                    </li>
                </ul>
            </nav>
            
            <div class="p-4 border-t border-slate-700">
                <a href="../logout.php" class="flex items-center space-x-3 px-4 py-3 text-red-400 rounded-lg hover:bg-slate-700 transition-colors">
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
                        <button class="p-2 hover:bg-gray-100 rounded-full transition-colors">
                            <i class="fas fa-bell text-gray-600"></i>
                            <span class="absolute top-4 right-4 h-2 w-2 bg-red-500 rounded-full"></span>
                        </button>
                    </div>
                    <div class="flex items-center space-x-4">
                        <span class="text-gray-700">John Doe</span>
                        <button class="p-2 hover:bg-gray-100 rounded-full transition-colors">
                            <i class="fas fa-user-circle text-gray-600 text-2xl"></i>
                        </button>
                    </div>
                </div>
            </header>

            <!-- Main Content Area -->
            <main class="flex-1 overflow-y-auto p-8">
                <!-- Stats Cards -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                    <!-- Total Applications -->
                    <div class="bg-white rounded-lg shadow-sm p-6">
                        <div class="flex justify-between items-center">
                            <div>
                                <p class="text-gray-500 text-sm">Total Applications</p>
                                <h3 class="text-2xl font-bold text-gray-800">1,234</h3>
                                <p class="text-green-500 text-sm">+12.5%</p>
                            </div>
                            <div class="bg-blue-100 p-3 rounded-full">
                                <i class="fas fa-file-alt text-blue-500"></i>
                            </div>
                        </div>
                    </div>

                    <!-- Pending Approvals -->
                    <div class="bg-white rounded-lg shadow-sm p-6">
                        <div class="flex justify-between items-center">
                            <div>
                                <p class="text-gray-500 text-sm">Pending Approvals</p>
                                <h3 class="text-2xl font-bold text-gray-800">45</h3>
                                <p class="text-yellow-500 text-sm">Active</p>
                            </div>
                            <div class="bg-yellow-100 p-3 rounded-full">
                                <i class="fas fa-clock text-yellow-500"></i>
                            </div>
                        </div>
                    </div>

                    <!-- Approved Passes -->
                    <div class="bg-white rounded-lg shadow-sm p-6">
                        <div class="flex justify-between items-center">
                            <div>
                                <p class="text-gray-500 text-sm">Approved Passes</p>
                                <h3 class="text-2xl font-bold text-gray-800">892</h3>
                                <p class="text-green-500 text-sm">+8.3%</p>
                            </div>
                            <div class="bg-green-100 p-3 rounded-full">
                                <i class="fas fa-check-circle text-green-500"></i>
                            </div>
                        </div>
                    </div>

                    <!-- Active Passes -->
                    <div class="bg-white rounded-lg shadow-sm p-6">
                        <div class="flex justify-between items-center">
                            <div>
                                <p class="text-gray-500 text-sm">Active Passes</p>
                                <h3 class="text-2xl font-bold text-gray-800">156</h3>
                                <p class="text-blue-500 text-sm">Current</p>
                            </div>
                            <div class="bg-purple-100 p-3 rounded-full">
                                <i class="fas fa-id-card text-purple-500"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Charts Section -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
                    <!-- Line Chart -->
                    <div class="bg-white rounded-lg shadow-sm p-6">
                        <h3 class="text-lg font-semibold mb-4">Gate Pass Trends</h3>
                        <canvas id="gatePassTrend" height="300"></canvas>
                    </div>

                    <!-- Bar Chart -->
                    <div class="bg-white rounded-lg shadow-sm p-6">
                        <h3 class="text-lg font-semibold mb-4">Department-wise Distribution</h3>
                        <canvas id="departmentChart" height="300"></canvas>
                    </div>
                </div>

                <!-- Recent Gate Passes Table -->
                <div class="bg-white rounded-lg shadow-sm p-6">
                    <h3 class="text-lg font-semibold mb-4">Recent Gate Passes</h3>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Pass ID</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Applicant</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Department</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap">GP001</td>
                                    <td class="px-6 py-4 whitespace-nowrap">John Doe</td>
                                    <td class="px-6 py-4 whitespace-nowrap">IT Department</td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                            Approved
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap space-x-2">
                                        <button class="text-blue-600 hover:text-blue-900">View</button>
                                        <button class="text-green-600 hover:text-green-900">Approve</button>
                                        <button class="text-red-600 hover:text-red-900">Reject</button>
                                    </td>
                                </tr>
                                <!-- Add more rows as needed -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script>
        // Gate Pass Trend Chart
        const trendCtx = document.getElementById('gatePassTrend').getContext('2d');
        new Chart(trendCtx, {
            type: 'line',
            data: {
                labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
                datasets: [{
                    label: 'Gate Passes',
                    data: [65, 59, 80, 81, 56, 55],
                    borderColor: 'rgb(59, 130, 246)',
                    tension: 0.4,
                    fill: false
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        display: false
                    }
                }
            }
        });

        // Department Distribution Chart
        const deptCtx = document.getElementById('departmentChart').getContext('2d');
        new Chart(deptCtx, {
            type: 'bar',
            data: {
                labels: ['IT', 'HR', 'Finance', 'Operations', 'Marketing'],
                datasets: [{
                    label: 'Gate Passes',
                    data: [12, 19, 3, 5, 2],
                    backgroundColor: 'rgba(59, 130, 246, 0.5)',
                    borderColor: 'rgb(59, 130, 246)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        display: false
                    }
                }
            }
        });
    </script>
</body>
</html>