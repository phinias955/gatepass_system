<?php
session_start();
require_once 'includes/header.php';
require_once '../backend/db.php';

// Check authentication
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Estate Officer') {
    header('Location: ../login.php');
    exit();
}

// Fetch department statistics
$query = "SELECT 
    d.department_name,
    COUNT(g.pass_id) AS total_passes,
    SUM(CASE WHEN g.estate_office_status = 'Granted' THEN 1 ELSE 0 END) as approved_passes,
    SUM(CASE WHEN g.estate_office_status = 'Not Granted' THEN 1 ELSE 0 END) as rejected_passes,
    COUNT(DISTINCT u.user_id) as total_users
FROM departments d
LEFT JOIN users u ON d.department_id = u.department_id
LEFT JOIN gate_pass g ON u.user_id = g.applicant_id
GROUP BY d.department_id, d.department_name
ORDER BY total_passes DESC";

$stmt = $pdo->prepare($query);
$stmt->execute();
$departments = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate totals for charts
$total_passes = array_sum(array_column($departments, 'total_passes'));
$total_approved = array_sum(array_column($departments, 'approved_passes'));
$total_rejected = array_sum(array_column($departments, 'rejected_passes'));
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Department Overview - Gate Pass System</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Add Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <!-- Add AOS library for animations -->
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    
    <style>
        .card {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 12px rgba(0, 0, 0, 0.15);
        }

        .gradient-bg {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }

        .chart-container {
            position: relative;
            height: 300px;
            width: 100%;
        }

        .progress-ring {
            transition: stroke-dashoffset 0.35s;
            transform: rotate(-90deg);
            transform-origin: 50% 50%;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .animate-slide-in {
            animation: slideIn 0.5s ease-out forwards;
        }
        .fixtop{
            position: fixed;
            top: 0;
            width: 100%;
        }
    </style>
</head>
<body class="bg-gray-100">
    <div class="flex min-h-screen">
        <!-- Sidebar -->
        <?php require_once 'includes/sidebar.php'; ?>

        <!-- Main Content -->
        <div class="flex-1 flex flex-col overflow-hidden">
            <!-- Header -->
            <header class="bg-white shadow-sm">
                <div class="px-6 py-4">
                    <h1 class="text-2xl font-bold text-gray-800">Department Overview</h1>
                </div>
            </header>

            <!-- Main Content -->
            <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-100 p-6">
                <!-- Summary Cards -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                    <!-- Total Departments -->
                    <div class="card p-6" data-aos="fade-up" data-aos-delay="100">
                        <div class="flex items-center">
                            <div class="p-3 rounded-full bg-blue-100 text-blue-500">
                                <i class="fas fa-building text-2xl"></i>
                            </div>
                            <div class="ml-4">
                                <h2 class="text-gray-600">Total Departments</h2>
                                <p class="text-2xl font-semibold"><?php echo count($departments); ?></p>
                            </div>
                        </div>
                    </div>

                    <!-- Total Passes -->
                    <div class="card p-6" data-aos="fade-up" data-aos-delay="200">
                        <div class="flex items-center">
                            <div class="p-3 rounded-full bg-green-100 text-green-500">
                                <i class="fas fa-passport text-2xl"></i>
                            </div>
                            <div class="ml-4">
                                <h2 class="text-gray-600">Total Passes</h2>
                                <p class="text-2xl font-semibold"><?php echo $total_passes; ?></p>
                            </div>
                        </div>
                    </div>

                    <!-- Success Rate -->
                    <div class="card p-6" data-aos="fade-up" data-aos-delay="300">
                        <div class="flex items-center">
                            <div class="p-3 rounded-full bg-purple-100 text-purple-500">
                                <i class="fas fa-chart-line text-2xl"></i>
                            </div>
                            <div class="ml-4">
                                <h2 class="text-gray-600">Success Rate</h2>
                                <p class="text-2xl font-semibold">
                                    <?php echo $total_passes > 0 ? round(($total_approved / $total_passes) * 100) : 0; ?>%
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Charts Section -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                    <!-- Pie Chart -->
                    <div class="card p-6" data-aos="fade-right">
                        <h2 class="text-xl font-semibold mb-4">Department Distribution</h2>
                        <div class="chart-container">
                            <canvas id="departmentPieChart"></canvas>
                        </div>
                    </div>

                    <!-- Bar Chart -->
                    <div class="card p-6" data-aos="fade-left">
                        <h2 class="text-xl font-semibold mb-4">Pass Status by Department</h2>
                        <div class="chart-container">
                            <canvas id="departmentBarChart"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Department Table -->
                <div class="card p-6" data-aos="fade-up">
                    <h2 class="text-xl font-semibold mb-4">Department Details</h2>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Department</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total Passes</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Approved</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Rejected</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Success Rate</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php foreach ($departments as $index => $dept): ?>
                                <tr class="hover:bg-gray-50" data-aos="fade-up" data-aos-delay="<?php echo $index * 50; ?>">
                                    <td class="px-6 py-4 whitespace-nowrap"><?php echo htmlspecialchars($dept['department_name']); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap"><?php echo $dept['total_passes']; ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-green-600"><?php echo $dept['approved_passes']; ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-red-600"><?php echo $dept['rejected_passes']; ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <?php 
                                        $success_rate = $dept['total_passes'] > 0 
                                            ? round(($dept['approved_passes'] / $dept['total_passes']) * 100) 
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

        // Prepare data for charts
        const departments = <?php echo json_encode(array_column($departments, 'department_name')); ?>;
        const totalPasses = <?php echo json_encode(array_column($departments, 'total_passes')); ?>;
        const approvedPasses = <?php echo json_encode(array_column($departments, 'approved_passes')); ?>;
        const rejectedPasses = <?php echo json_encode(array_column($departments, 'rejected_passes')); ?>;

        // Pie Chart
        new Chart(document.getElementById('departmentPieChart'), {
            type: 'pie',
            data: {
                labels: departments,
                datasets: [{
                    data: totalPasses,
                    backgroundColor: [
                        '#4F46E5', '#7C3AED', '#EC4899', '#F59E0B', '#10B981',
                        '#3B82F6', '#6366F1', '#8B5CF6', '#D946EF', '#14B8A6'
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'right'
                    }
                }
            }
        });

        // Bar Chart
        new Chart(document.getElementById('departmentBarChart'), {
            type: 'bar',
            data: {
                labels: departments,
                datasets: [{
                    label: 'Approved',
                    data: approvedPasses,
                    backgroundColor: '#10B981'
                }, {
                    label: 'Rejected',
                    data: rejectedPasses,
                    backgroundColor: '#EF4444'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    x: {
                        stacked: true
                    },
                    y: {
                        stacked: true
                    }
                }
            }
        });
    </script>
</body>
</html>