<?php
$page_title = "HoD Dashboard";
require_once '../config/config.php';
require_once '../backend/db.php';
include 'includes/header.php';

// Debug information
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if user is logged in and has HoD role
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'HoD') {
    header('Location: ../login.php');
    exit;
}

try {
    $user_id = $_SESSION['user_id'];

    // Get department_id and name for the HoD
    $stmt = $pdo->prepare("
        SELECT u.department_id, d.department_name 
        FROM users u 
        JOIN departments d ON u.department_id = d.department_id 
        WHERE u.user_id = ? AND u.role = 'HoD'
    ");
    $stmt->execute([$user_id]);
    $dept_info = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$dept_info) {
        throw new Exception("Department not found for HoD");
    }

    $department_id = $dept_info['department_id'];
    $department_name = $dept_info['department_name'];

    // Initialize variables
    $pending_approvals = 0;
    $today_approved = 0;
    $total_staff = 0;
    $monthly_passes = 0;
    $pending_applications = [];
    $monthly_stats = [];

    // Pending approvals count
    $stmt = $pdo->prepare("
        SELECT COUNT(*) 
        FROM gate_pass GP
        JOIN users u ON GP.applicant_id = u.user_id
        WHERE u.department_id = ? 
        AND GP.hod_status = 'Pending'
    ");
    $stmt->execute([$department_id]);
    $pending_approvals = $stmt->fetchColumn();

    // Today's approved applications - Modified query
    $stmt = $pdo->prepare("
    SELECT COUNT(*) 
    FROM gate_pass GP
    JOIN users u ON GP.applicant_id = u.user_id
    WHERE u.department_id = ? 
    AND GP.hod_status = 'Approved'
    AND DATE(GP.date_submitted ) = CURDATE()  -- Changed from hod_approval_date to date_submitted
");
    $stmt->execute([$department_id]);
    $today_approved = $stmt->fetchColumn();

    // Total staff count
    $stmt = $pdo->prepare("
        SELECT COUNT(*) 
        FROM users 
        WHERE department_id = ? 
        AND role = 'Applicant'
    ");
    $stmt->execute([$department_id]);
    $total_staff = $stmt->fetchColumn();

    // Monthly passes count
    $stmt = $pdo->prepare("
        SELECT COUNT(*) 
        FROM gate_pass GP
        JOIN users u ON GP.applicant_id = u.user_id
        WHERE u.department_id = ?
        AND MONTH(GP.date_submitted) = MONTH(CURRENT_DATE())
        AND YEAR(GP.date_submitted) = YEAR(CURRENT_DATE())
    ");
    $stmt->execute([$department_id]);
    $monthly_passes = $stmt->fetchColumn();

    // Recent pending applications with items
    $stmt = $pdo->prepare("
        SELECT 
            GP.pass_id,
            GP.date_submitted,
            CONCAT(u.fname, ' ', COALESCE(u.m_name, ''), ' ', u.l_name) as staff_name,
            (
                SELECT GROUP_CONCAT(g.item_description SEPARATOR ', ')
                FROM goods g
                WHERE g.pass_id = GP.pass_id
            ) as items
        FROM gate_pass GP
        JOIN users u ON GP.applicant_id = u.user_id
        WHERE u.department_id = ? 
        AND GP.hod_status = 'Pending'
        ORDER BY GP.date_submitted DESC
        LIMIT 5
    ");
    $stmt->execute([$department_id]);
    $pending_applications = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Monthly statistics for trend chart
    $stmt = $pdo->prepare("
        SELECT 
            DATE_FORMAT(date_submitted, '%Y-%m') as yearMonth,
            DATE_FORMAT(date_submitted, '%b') as month,
            COUNT(*) as total,
            SUM(CASE WHEN hod_status = 'Approved' THEN 1 ELSE 0 END) as approved,
            SUM(CASE WHEN hod_status = 'Rejected' THEN 1 ELSE 0 END) as rejected,
            SUM(CASE WHEN hod_status = 'Pending' THEN 1 ELSE 0 END) as pending
        FROM gate_pass GP
        JOIN users u ON GP.applicant_id = u.user_id
        WHERE u.department_id = ?
        AND date_submitted >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
        GROUP BY yearMonth, month
        ORDER BY yearMonth ASC
    ");
    $stmt->execute([$department_id]);
    $monthly_stats = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Debug output
    echo "<!-- Debug: Department ID: $department_id -->";
    echo "<!-- Debug: Pending Applications: " . json_encode($pending_applications) . " -->";
    echo "<!-- Debug: Monthly Stats: " . json_encode($monthly_stats) . " -->";
} catch (Exception $e) {
    error_log("Error in HoD dashboard: " . $e->getMessage());
    $_SESSION['error'] = "Error loading dashboard data: " . $e->getMessage();
}
?>

<div class="flex h-screen">
    <!-- Sidebar -->
    <?php include 'includes/sidebar.php'; ?>

    <!-- Main Content -->
    <div class="flex-1 ml-64">
        <!-- Header -->
        <header class="bg-white shadow-sm">
            <div class="flex justify-between items-center px-8 py-4">
                <div class="flex items-center space-x-4">
                    <span class="text-lg font-semibold text-gray-700">
                        <?php echo htmlspecialchars($department_name); ?>
                    </span>
                </div>
                <div class="flex items-center space-x-4">
                    <button class="p-2 hover:bg-gray-100 rounded-full transition-colors">
                        <i class="fas fa-bell text-gray-600"></i>
                        <?php if ($pending_approvals > 0): ?>
                            <span class="absolute top-4 right-4 h-2 w-2 bg-red-500 rounded-full"></span>
                        <?php endif; ?>
                    </button>
                    <span class="text-gray-700"><?php echo htmlspecialchars($_SESSION['name']); ?></span>
                    <button class="p-2 hover:bg-gray-100 rounded-full transition-colors">
                        <i class="fas fa-user-circle text-gray-600 text-2xl"></i>
                    </button>
                </div>
            </div>
        </header>

        <!-- Main Content Area -->
        <main class="p-8">
            <!-- Stats Cards -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                <!-- Pending Approvals -->
                <div class="bg-white rounded-lg shadow-sm p-6">
                    <div class="flex justify-between items-center">
                        <div>
                            <p class="text-gray-500 text-sm">Pending Approvals</p>
                            <h3 class="text-2xl font-bold text-gray-800"><?php echo $pending_approvals; ?></h3>
                            <p class="text-yellow-500 text-sm">Requires Action</p>
                        </div>
                        <div class="bg-yellow-100 p-3 rounded-full">
                            <i class="fas fa-clock text-yellow-500"></i>
                        </div>
                    </div>
                </div>

                <!-- Today's Approved -->
                <div class="bg-white rounded-lg shadow-sm p-6">
                    <div class="flex justify-between items-center">
                        <div>
                            <p class="text-gray-500 text-sm">Today's Approved</p>
                            <h3 class="text-2xl font-bold text-gray-800"><?php echo $today_approved; ?></h3>
                            <p class="text-green-500 text-sm">Processed Today</p>
                        </div>
                        <div class="bg-green-100 p-3 rounded-full">
                            <i class="fas fa-check text-green-500"></i>
                        </div>
                    </div>
                </div>

                <!-- Total Staff -->
                <div class="bg-white rounded-lg shadow-sm p-6">
                    <div class="flex justify-between items-center">
                        <div>
                            <p class="text-gray-500 text-sm">Total Staff</p>
                            <h3 class="text-2xl font-bold text-gray-800"><?php echo $total_staff; ?></h3>
                            <p class="text-blue-500 text-sm">Department Members</p>
                        </div>
                        <div class="bg-blue-100 p-3 rounded-full">
                            <i class="fas fa-users text-blue-500"></i>
                        </div>
                    </div>
                </div>

                <!-- Monthly Passes -->
                <div class="bg-white rounded-lg shadow-sm p-6">
                    <div class="flex justify-between items-center">
                        <div>
                            <p class="text-gray-500 text-sm">Monthly Passes</p>
                            <h3 class="text-2xl font-bold text-gray-800"><?php echo $monthly_passes; ?></h3>
                            <p class="text-purple-500 text-sm">This Month</p>
                        </div>
                        <div class="bg-purple-100 p-3 rounded-full">
                            <i class="fas fa-calendar text-purple-500"></i>
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

            <!-- Pending Approvals Table -->
            <div class="bg-white rounded-lg shadow-sm p-6">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-semibold">Pending Approvals</h3>
                    <a href="pending.php" class="text-blue-500 hover:text-blue-700">View All</a>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Staff Name</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Pass ID</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Request Date</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Items</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php if (!empty($pending_applications)): ?>
                                <?php foreach ($pending_applications as $app): ?>
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap txt-formart">
                                            <?php echo htmlspecialchars($app['staff_name'] ?? 'N/A'); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            DARTU/GP<?php echo str_pad($app['pass_id'], 3, '0', STR_PAD_LEFT); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <?php echo date('Y-m-d', strtotime($app['date_submitted'])); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap txt-formart">
                                            <?php echo htmlspecialchars($app['items'] ?? 'No items'); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap space-x-2">
                                            <a href="review.php?id=<?php echo $app['pass_id']; ?>&action=approve"
                                                class="inline-block bg-green-500 text-white px-3 py-1 rounded-md hover:bg-green-600">
                                                Approve
                                            </a>
                                            <a href="review.php?id=<?php echo $app['pass_id']; ?>&action=reject"
                                                class="inline-block bg-red-500 text-white px-3 py-1 rounded-md hover:bg-red-600">
                                                Reject
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="px-6 py-4 text-center text-gray-500">
                                        No pending applications
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
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
                    label: 'Total Applications',
                    data: <?php echo json_encode(array_column($monthly_stats, 'total')); ?>,
                    borderColor: 'rgb(59, 130, 246)',
                    backgroundColor: 'rgba(59, 130, 246, 0.1)',
                    tension: 0.4,
                    fill: true
                },
                {
                    label: 'Approved',
                    data: <?php echo json_encode(array_column($monthly_stats, 'approved')); ?>,
                    borderColor: 'rgb(16, 185, 129)',
                    backgroundColor: 'rgba(16, 185, 129, 0.1)',
                    tension: 0.4,
                    fill: true
                },
                {
                    label: 'Pending',
                    data: <?php echo json_encode(array_column($monthly_stats, 'pending')); ?>,
                    borderColor: 'rgb(245, 158, 11)',
                    backgroundColor: 'rgba(245, 158, 11, 0.1)',
                    tension: 0.4,
                    fill: true
                }
            ]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        usePointStyle: true,
                        padding: 20
                    }
                },
                tooltip: {
                    mode: 'index',
                    intersect: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        stepSize: 1
                    },
                    grid: {
                        drawBorder: false
                    }
                },
                x: {
                    grid: {
                        display: false
                    }
                }
            },
            interaction: {
                mode: 'nearest',
                axis: 'x',
                intersect: false
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
                    <?php echo $pending_approvals; ?>,
                    <?php echo $today_approved; ?>,
                    <?php echo $monthly_passes - ($pending_approvals + $today_approved); ?>
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
                    position: 'bottom',
                    labels: {
                        usePointStyle: true,
                        padding: 20
                    }
                }
            },
            cutout: '65%'
        }
    });
</script>

<?php
// Display any errors
if (isset($_SESSION['error'])) {
    echo '<div class="fixed bottom-4 right-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">';
    echo '<strong class="font-bold">Error!</strong>';
    echo '<span class="block sm:inline">' . htmlspecialchars($_SESSION['error']) . '</span>';
    echo '</div>';
    unset($_SESSION['error']);
}
?>