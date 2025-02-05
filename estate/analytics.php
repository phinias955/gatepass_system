<?php
session_start();
require_once 'includes/header.php';
require_once '../backend/db.php';

// Check authentication
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Estate Officer') {
    header('Location: ../login.php');
    exit();
}

// Fetch analytics data for different time periods
function getAnalyticsData($pdo, $period = 'all') {
    $timeConstraint = "";
    switch($period) {
        case 'today':
            $timeConstraint = "WHERE DATE(g.date_submitted) = CURDATE()";
            break;
        case 'week':
            $timeConstraint = "WHERE g.date_submitted >= DATE_SUB(CURDATE(), INTERVAL 1 WEEK)";
            break;
        case 'month':
            $timeConstraint = "WHERE g.date_submitted >= DATE_SUB(CURDATE(), INTERVAL 1 MONTH)";
            break;
    }

    $query = "SELECT 
        COUNT(*) as total_passes,
        SUM(CASE WHEN g.final_status = 'Approved' THEN 1 ELSE 0 END) AS approved,
        SUM(CASE WHEN g.final_status = 'Rejected' THEN 1 ELSE 0 END) AS rejected,
        SUM(CASE WHEN g.final_status = 'Pending' THEN 1 ELSE 0 END) AS pending,
        COUNT(DISTINCT g.applicant_id) as unique_users,
        COUNT(DISTINCT u.department_id) as active_departments
    FROM gate_pass g
    JOIN users u ON g.applicant_id = u.user_id
    $timeConstraint";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute();
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// Get department-wise statistics
$deptQuery = "SELECT 
    d.department_name,
    COUNT(g.pass_id) as total_requests,
    SUM(CASE WHEN g.final_status = 'Approved' THEN 1 ELSE 0 END) as approved,
    SUM(CASE WHEN g.final_status = 'Rejected' THEN 1 ELSE 0 END) as rejected
FROM departments d
JOIN users u ON d.department_id = u.department_id
LEFT JOIN gate_pass g ON u.user_id = g.applicant_id
GROUP BY d.department_id, d.department_name
ORDER BY total_requests DESC";

$deptStmt = $pdo->prepare($deptQuery);
$deptStmt->execute();
$departmentStats = $deptStmt->fetchAll(PDO::FETCH_ASSOC);

// Get monthly trends
$trendQuery = "SELECT 
    DATE_FORMAT(g.date_submitted, '%Y-%m') as month,
    COUNT(*) as total_requests,
    SUM(CASE WHEN g.final_status = 'Approved' THEN 1 ELSE 0 END) as approved,
    SUM(CASE WHEN g.final_status = 'Rejected' THEN 1 ELSE 0 END) as rejected
FROM gate_pass g
GROUP BY DATE_FORMAT(g.date_submitted, '%Y-%m')
ORDER BY month DESC
LIMIT 12";

$trendStmt = $pdo->prepare($trendQuery);
$trendStmt->execute();
$monthlyTrends = $trendStmt->fetchAll(PDO::FETCH_ASSOC);

// Get current statistics
$currentStats = getAnalyticsData($pdo);
$todayStats = getAnalyticsData($pdo, 'today');
$weekStats = getAnalyticsData($pdo, 'week');
$monthStats = getAnalyticsData($pdo, 'month');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analytics Dashboard - Gate Pass System</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Add Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <!-- Add AOS library -->
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <!-- Add ApexCharts -->
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
    
    <style>
        .neo-card {
            background: white;
            border-radius: 20px;
            box-shadow: 8px 8px 15px #e2e8f0, 
                       -8px -8px 15px #ffffff;
            transition: all 0.3s ease;
        }

        .neo-card:hover {
            transform: translateY(-5px);
            box-shadow: 12px 12px 20px #d1d9e6, 
                       -12px -12px 20px #ffffff;
        }

        .gradient-bg {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }

        .glass-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .chart-container {
            position: relative;
            height: 400px;
            width: 100%;
        }

        .sparkline {
            height: 50px;
            width: 200px;
        }

        @keyframes pulse {
            0% {
                transform: scale(1);
            }
            50% {
                transform: scale(1.05);
            }
            100% {
                transform: scale(1);
            }
        }
    </style>
</head>
<body class="bg-gray-100">
    <div class="flex min-h-screen">
        <!-- Sidebar -->
        <?php require_once 'includes/sidebar.php'; ?>

        <!-- Main Content -->
        <div class="flex-1 flex flex-col overflow-hidden">
            <!-- Top Navigation -->
            <header class="bg-white shadow">
                <div class="px-4 py-6">
                    <div class="flex justify-between items-center">
                        <h1 class="text-2xl font-bold text-gray-900">Analytics Dashboard</h1>
                        <div class="flex items-center space-x-4">
                            <button class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg transition-colors duration-200">
                                <i class="fas fa-sync-alt mr-2"></i>Refresh
                            </button>
                            <button onclick="window.print()" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg transition-colors duration-200">
                                <i class="fas fa-print mr-2"></i>Print
                            </button>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Page Content -->
            <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-100">
                <div class="container mx-auto px-4 py-6">
                    <!-- Quick Stats -->
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                        <!-- Today's Stats -->
                        <div class="neo-card p-6" data-aos="fade-up" data-aos-delay="100">
                            <div class="flex justify-between items-start">
                                <div>
                                    <p class="text-sm text-gray-600">Today's Passes</p>
                                    <h3 class="text-2xl font-bold text-gray-800"><?php echo $todayStats['total_passes']; ?></h3>
                                    <p class="text-xs text-green-600 mt-2">
                                        <i class="fas fa-arrow-up mr-1"></i>
                                        +<?php echo round(($todayStats['approved'] / max(1, $todayStats['total_passes'])) * 100); ?>%
                                    </p>
                                </div>
                                <div class="p-3 bg-indigo-100 rounded-full">
                                    <i class="fas fa-ticket-alt text-indigo-600 text-xl"></i>
                                </div>
                            </div>
                        </div>

                        <!-- Weekly Stats -->
                        <div class="neo-card p-6" data-aos="fade-up" data-aos-delay="200">
                            <div class="flex justify-between items-start">
                                <div>
                                    <p class="text-sm text-gray-600">Weekly Passes</p>
                                    <h3 class="text-2xl font-bold text-gray-800"><?php echo $weekStats['total_passes']; ?></h3>
                                    <p class="text-xs text-green-600 mt-2">
                                        <i class="fas fa-chart-line mr-1"></i>
                                        <?php echo $weekStats['approved']; ?> Approved
                                    </p>
                                </div>
                                <div class="p-3 bg-green-100 rounded-full">
                                    <i class="fas fa-calendar-week text-green-600 text-xl"></i>
                                </div>
                            </div>
                        </div>

                        <!-- Monthly Stats -->
                        <div class="neo-card p-6" data-aos="fade-up" data-aos-delay="300">
                            <div class="flex justify-between items-start">
                                <div>
                                    <p class="text-sm text-gray-600">Monthly Passes</p>
                                    <h3 class="text-2xl font-bold text-gray-800"><?php echo $monthStats['total_passes']; ?></h3>
                                    <p class="text-xs text-blue-600 mt-2">
                                        <i class="fas fa-users mr-1"></i>
                                        <?php echo $monthStats['unique_users']; ?> Unique Users
                                    </p>
                                </div>
                                <div class="p-3 bg-blue-100 rounded-full">
                                    <i class="fas fa-calendar-alt text-blue-600 text-xl"></i>
                                </div>
                            </div>
                        </div>

                        <!-- Overall Stats -->
                        <div class="neo-card p-6" data-aos="fade-up" data-aos-delay="400">
                            <div class="flex justify-between items-start">
                                <div>
                                    <p class="text-sm text-gray-600">Success Rate</p>
                                    <h3 class="text-2xl font-bold text-gray-800">
                                        <?php 
                                        $successRate = $currentStats['total_passes'] > 0 
                                            ? round(($currentStats['approved'] / $currentStats['total_passes']) * 100) 
                                            : 0;
                                        echo $successRate . '%';
                                        ?>
                                    </h3>
                                    <p class="text-xs text-purple-600 mt-2">
                                        <i class="fas fa-check-circle mr-1"></i>
                                        Overall Performance
                                    </p>
                                </div>
                                <div class="p-3 bg-purple-100 rounded-full">
                                    <i class="fas fa-chart-pie text-purple-600 text-xl"></i>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Charts Section -->
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
                        <!-- Monthly Trends Chart -->
                        <div class="neo-card p-6" data-aos="fade-right">
                            <h3 class="text-lg font-semibold mb-4">Monthly Trends</h3>
                            <div class="chart-container">
                                <canvas id="monthlyTrendsChart"></canvas>
                            </div>
                        </div>

                        <!-- Department Performance Chart -->
                        <div class="neo-card p-6" data-aos="fade-left">
                            <h3 class="text-lg font-semibold mb-4">Department Performance</h3>
                            <div class="chart-container">
                                <canvas id="departmentChart"></canvas>
                            </div>
                        </div>
                    </div>

                    <!-- Department Stats Table -->
                    <div class="neo-card p-6" data-aos="fade-up">
                        <h3 class="text-lg font-semibold mb-4">Department Statistics</h3>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Department</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total Requests</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Approved</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Rejected</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Success Rate</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <?php foreach ($departmentStats as $dept): ?>
                                    <tr class="hover:bg-gray-50 transition-colors duration-200">
                                        <td class="px-6 py-4 whitespace-nowrap"><?php echo htmlspecialchars($dept['department_name']); ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap"><?php echo $dept['total_requests']; ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-green-600"><?php echo $dept['approved']; ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-red-600"><?php echo $dept['rejected']; ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <?php 
                                            $success_rate = $dept['total_requests'] > 0 
                                                ? round(($dept['approved'] / $dept['total_requests']) * 100) 
                                                : 0;
                                            echo $success_rate . '%';
                                            ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script>
        // Initialize AOS
        AOS.init({
            duration: 800,
            easing: 'ease-out',
            once: true
        });

        // Monthly Trends Chart
        const monthlyData = <?php echo json_encode($monthlyTrends); ?>;
        new Chart(document.getElementById('monthlyTrendsChart'), {
            type: 'line',
            data: {
                labels: monthlyData.map(item => item.month),
                datasets: [{
                    label: 'Total Requests',
                    data: monthlyData.map(item => item.total_requests),
                    borderColor: '#4F46E5',
                    tension: 0.4,
                    fill: false
                }, {
                    label: 'Approved',
                    data: monthlyData.map(item => item.approved),
                    borderColor: '#10B981',
                    tension: 0.4,
                    fill: false
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top'
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });

        // Department Performance Chart
        const deptData = <?php echo json_encode($departmentStats); ?>;
        new Chart(document.getElementById('departmentChart'), {
            type: 'bar',
            data: {
                labels: deptData.map(item => item.department_name),
                datasets: [{
                    label: 'Approved',
                    data: deptData.map(item => item.approved),
                    backgroundColor: '#10B981'
                }, {
                    label: 'Rejected',
                    data: deptData.map(item => item.rejected),
                    backgroundColor: '#EF4444'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top'
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        stacked: true
                    },
                    x: {
                        stacked: true
                    }
                }
            }
        });
    </script>
</body>
</html>