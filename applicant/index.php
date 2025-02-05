<?php
$page_title = "Dashboard";
require_once '../config/config.php';
require_once '../backend/db.php';
include 'includes/header.php';

// Check if user is logged in and is an applicant
if (!isset($_SESSION['user_id']) || strtolower($_SESSION['role']) !== 'applicant') {
    header('Location: ../login.php');
    exit;
}

// Initialize variables
$total_applications = 0;
$pending_applications = 0;
$approved_applications = 0;
$recent_applications = [];
$monthly_stats = [];

try {
    $user_id = $_SESSION['user_id'];

    // Total applications
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM gate_pass WHERE applicant_id = ?");
    $stmt->execute([$user_id]);
    $total_applications = $stmt->fetchColumn();

    // Pending applications
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM gate_pass WHERE applicant_id = ? AND hod_status = 'Pending'");
    $stmt->execute([$user_id]);
    $pending_applications = $stmt->fetchColumn();

    // Approved applications
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM gate_pass WHERE applicant_id = ? AND hod_status = 'Approved'");
    $stmt->execute([$user_id]);
    $approved_applications = $stmt->fetchColumn();

    // Recent applications with items
    $stmt = $pdo->prepare("
        SELECT 
            GP.pass_id,
            GP.date_submitted,
            GP.hod_status as final_status,
            GROUP_CONCAT(CONCAT(g.item_description, ' (', g.quantity, ')') SEPARATOR ', ') as items
        FROM gate_pass GP
        LEFT JOIN goods g ON GP.pass_id = g.pass_id
        WHERE GP.applicant_id = ?
        GROUP BY GP.pass_id
        ORDER BY GP.date_submitted DESC
        LIMIT 5
    ");
    $stmt->execute([$user_id]);
    $recent_applications = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Monthly statistics for chart
    $stmt = $pdo->prepare("
        SELECT 
            DATE_FORMAT(date_submitted, '%b') as month,
            COUNT(*) as total
        FROM gate_pass 
        WHERE applicant_id = ? 
        AND date_submitted >= DATE_SUB(CURRENT_DATE(), INTERVAL 6 MONTH)
        GROUP BY month
        ORDER BY MIN(date_submitted) ASC
    ");
    $stmt->execute([$user_id]);
    $monthly_stats = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log("Error fetching dashboard data: " . $e->getMessage());
    $_SESSION['error'] = "Error loading dashboard data";
}

?>

<div class="flex min-h-screen bg-gray-50">
    <!-- Sidebar -->
    <div class="fixed inset-y-0 left-0 z-50">
        <?php include 'includes/sidebar.php'; ?>
    </div>

    <!-- Main Content -->
    <div class="flex-1 ml-64">
        <!-- Top Navigation -->
        <header class="sticky top-0 z-40 bg-white shadow-sm">
            <div class="flex justify-between items-center px-8 py-4">
                <div class="flex items-center space-x-4">
                    <span class="text-lg font-semibold text-gray-700">Applicant Dashboard</span>
                </div>
                <div class="flex items-center space-x-4">
                    <button class="p-2 hover:bg-gray-100 rounded-full transition-colors">
                        <i class="fas fa-bell text-gray-600"></i>
                        <?php if ($pending_applications > 0): ?>
                            <span class="absolute top-2 right-2 h-2 w-2 bg-red-500 rounded-full"></span>
                        <?php endif; ?>
                    </button>
                    <span class="text-gray-700"><?php echo htmlspecialchars($_SESSION['name']); ?></span>
                    <button class="p-2 hover:bg-gray-100 rounded-full transition-colors">
                        <i class="fas fa-user-circle text-gray-600 text-2xl"></i>
                    </button>
                </div>
            </div>
        </header>

        <!-- Page Content -->
        <div class="p-8">
            <!-- Quick Actions -->
            <div class="mb-8">
                <a href="newapp.php" class="bg-blue-500 text-white px-6 py-3 rounded-lg hover:bg-blue-600 transition-colors inline-flex items-center">
                    <i class="fas fa-plus mr-2"></i> New Gate Pass
                </a>
            </div>

            <!-- Stats Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                <!-- Total Applications -->
                <div class="bg-white rounded-lg shadow-sm p-6">
                    <div class="flex justify-between items-center">
                        <div>
                            <p class="text-gray-500 text-sm">Total Applications</p>
                            <h3 class="text-2xl font-bold text-gray-800"><?php echo $total_applications; ?></h3>
                            <p class="text-blue-500 text-sm mt-1">All Time</p>
                        </div>
                        <div class="bg-blue-100 p-3 rounded-full">
                            <i class="fas fa-file-alt text-blue-500"></i>
                        </div>
                    </div>
                </div>

                <!-- Pending Applications -->
                <div class="bg-white rounded-lg shadow-sm p-6">
                    <div class="flex justify-between items-center">
                        <div>
                            <p class="text-gray-500 text-sm">Pending</p>
                            <h3 class="text-2xl font-bold text-gray-800"><?php echo $pending_applications; ?></h3>
                            <p class="text-yellow-500 text-sm mt-1">Awaiting Approval</p>
                        </div>
                        <div class="bg-yellow-100 p-3 rounded-full">
                            <i class="fas fa-clock text-yellow-500"></i>
                        </div>
                    </div>
                </div>

                <!-- Approved Applications -->
                <div class="bg-white rounded-lg shadow-sm p-6">
                    <div class="flex justify-between items-center">
                        <div>
                            <p class="text-gray-500 text-sm">Approved</p>
                            <h3 class="text-2xl font-bold text-gray-800"><?php echo $approved_applications; ?></h3>
                            <p class="text-green-500 text-sm mt-1">Successfully Processed</p>
                        </div>
                        <div class="bg-green-100 p-3 rounded-full">
                            <i class="fas fa-check-circle text-green-500"></i>
                        </div>
                    </div>
                </div>

                <!-- Rejected Applications -->
                <div class="bg-white rounded-lg shadow-sm p-6">
                    <div class="flex justify-between items-center">
                        <div>
                            <p class="text-gray-500 text-sm">Rejected</p>
                            <h3 class="text-2xl font-bold text-gray-800">
                                <?php echo $total_applications - ($pending_applications + $approved_applications); ?>
                            </h3>
                            <p class="text-red-500 text-sm mt-1">Not Approved</p>
                        </div>
                        <div class="bg-red-100 p-3 rounded-full">
                            <i class="fas fa-times-circle text-red-500"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Charts Section -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
                <div class="bg-white rounded-lg shadow-sm p-6">
                    <h3 class="text-lg font-semibold mb-4">Application Trend</h3>
                    <canvas id="applicationTrend"></canvas>
                </div>
                <div class="bg-white rounded-lg shadow-sm p-6">
                    <h3 class="text-lg font-semibold mb-4">Status Distribution</h3>
                    <canvas id="statusDistribution"></canvas>
                </div>
            </div>

            <!-- Recent Applications -->
            <div class="bg-white rounded-lg shadow-sm p-6">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-semibold">Recent Applications</h3>
                    <a href="history.php" class="text-blue-500 hover:text-blue-700">View All</a>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Pass ID</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Items</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php if (!empty($recent_applications)): ?>
                                <?php foreach ($recent_applications as $app): ?>
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap">GP<?php echo str_pad($app['pass_id'], 3, '0', STR_PAD_LEFT); ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap"><?php echo date('Y-m-d', strtotime($app['date_submitted'])); ?></td>
                                        <td class="px-6 py-4"><?php echo htmlspecialchars($app['items']); ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <?php
                                            $status_colors = [
                                                'Pending' => 'yellow',
                                                'Approved' => 'green',
                                                'Rejected' => 'red'
                                            ];
                                            $color = $status_colors[$app['final_status']] ?? 'gray';
                                            ?>
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-<?php echo $color; ?>-100 text-<?php echo $color; ?>-800">
                                                <?php echo $app['final_status']; ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4">
                                            <a href="view_application.php?id=<?php echo $app['pass_id']; ?>"
                                                class="text-blue-600 hover:text-blue-800">
                                                <i class="fas fa-eye"></i> View
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="px-6 py-4 text-center text-gray-500">No applications found</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // Application Trend Chart
    const trendCtx = document.getElementById('applicationTrend').getContext('2d');
    new Chart(trendCtx, {
        type: 'line',
        data: {
            labels: <?php echo json_encode(array_column($monthly_stats, 'month')); ?>,
            datasets: [{
                label: 'Applications',
                data: <?php echo json_encode(array_column($monthly_stats, 'total')); ?>,
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
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        stepSize: 1
                    }
                }
            }
        }
    });

    // Status Distribution Chart
    const distCtx = document.getElementById('statusDistribution').getContext('2d');
    new Chart(distCtx, {
        type: 'doughnut',
        data: {
            labels: ['Pending', 'Approved', 'Rejected'],
            datasets: [{
                data: [
                    <?php echo $pending_applications; ?>,
                    <?php echo $approved_applications; ?>,
                    <?php echo $total_applications - ($pending_applications + $approved_applications); ?>
                ],
                backgroundColor: [
                    'rgba(245, 158, 11, 0.5)',
                    'rgba(16, 185, 129, 0.5)',
                    'rgba(239, 68, 68, 0.5)'
                ],
                borderColor: [
                    'rgb(245, 158, 11)',
                    'rgb(16, 185, 129)',
                    'rgb(239, 68, 68)'
                ],
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            }
        }
    });
</script>

<?php include 'includes/footer.php'; ?>