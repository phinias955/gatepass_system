<?php
$page_title = "Department Statistics";
require_once '../config/config.php';
require_once '../backend/db.php';
include 'includes/header.php';

// Check if user is logged in and has HoD role
if (!isset($_SESSION['user_id']) || strtolower($_SESSION['role']) !== 'hod') {
    header('Location: ../login.php');
    exit;
}

try {
    // Get HoD's department_id
    $stmt = $pdo->prepare("SELECT department_id FROM users WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $department_id = $stmt->fetchColumn();

    // Get total applications count
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as total,
            SUM(CASE WHEN hod_status = 'Approved' THEN 1 ELSE 0 END) as approved,
            SUM(CASE WHEN hod_status = 'Rejected' THEN 1 ELSE 0 END) as rejected,
            SUM(CASE WHEN hod_status = 'Pending' THEN 1 ELSE 0 END) as pending
        FROM gate_pass GP
        JOIN users u ON GP.applicant_id = u.user_id
        WHERE u.department_id = ?
    ");
    $stmt->execute([$department_id]);
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);

    // Get monthly statistics for the current year
    $stmt = $pdo->prepare("
        SELECT 
            MONTH(date_submitted) as month,
            COUNT(*) as total,
            SUM(CASE WHEN hod_status = 'Approved' THEN 1 ELSE 0 END) as approved,
            SUM(CASE WHEN hod_status = 'Rejected' THEN 1 ELSE 0 END) as rejected
        FROM gate_pass GP
        JOIN users u ON GP.applicant_id = u.user_id
        WHERE u.department_id = ? 
        AND YEAR(date_submitted) = YEAR(CURRENT_DATE())
        GROUP BY MONTH(date_submitted)
        ORDER BY month
    ");
    $stmt->execute([$department_id]);
    $monthly_stats = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get recent applications
    $stmt = $pdo->prepare("
        SELECT 
            GP.pass_id,
            GP.date_submitted,
            GP.hod_status,
            CONCAT(u.fname, ' ', u.l_name) as applicant_name
        FROM gate_pass GP
        JOIN users u ON GP.applicant_id = u.user_id
        WHERE u.department_id = ?
        ORDER BY GP.date_submitted DESC
        LIMIT 5
    ");
    $stmt->execute([$department_id]);
    $recent_applications = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    error_log("Error in stats.php: " . $e->getMessage());
    $error = "An error occurred while fetching statistics.";
}
?>

<!-- HTML Structure -->
<div class="flex h-screen bg-gray-100">
    <!-- Sidebar -->
    <?php include 'includes/sidebar.php'; ?>

    <!-- Main Content -->
    <div class="flex-1 ml-64">
        <header class="bg-white shadow-sm">
            <div class="flex justify-between items-center px-8 py-4">
                <h1 class="text-2xl font-semibold text-gray-900">Department Statistics</h1>
            </div>
        </header>

        <main class="p-8">
            <!-- Overview Cards -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                <div class="bg-white rounded-lg shadow-sm p-6">
                    <div class="flex items-center">
                        <div class="flex-1">
                            <h3 class="text-lg font-semibold text-gray-900">Total Applications</h3>
                            <p class="text-3xl font-bold text-blue-600"><?php echo $stats['total']; ?></p>
                        </div>
                        <div class="text-blue-500">
                            <i class="fas fa-file-alt text-3xl"></i>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow-sm p-6">
                    <div class="flex items-center">
                        <div class="flex-1">
                            <h3 class="text-lg font-semibold text-gray-900">Approved</h3>
                            <p class="text-3xl font-bold text-green-600"><?php echo $stats['approved']; ?></p>
                        </div>
                        <div class="text-green-500">
                            <i class="fas fa-check-circle text-3xl"></i>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow-sm p-6">
                    <div class="flex items-center">
                        <div class="flex-1">
                            <h3 class="text-lg font-semibold text-gray-900">Rejected</h3>
                            <p class="text-3xl font-bold text-red-600"><?php echo $stats['rejected']; ?></p>
                        </div>
                        <div class="text-red-500">
                            <i class="fas fa-times-circle text-3xl"></i>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow-sm p-6">
                    <div class="flex items-center">
                        <div class="flex-1">
                            <h3 class="text-lg font-semibold text-gray-900">Pending</h3>
                            <p class="text-3xl font-bold text-yellow-600"><?php echo $stats['pending']; ?></p>
                        </div>
                        <div class="text-yellow-500">
                            <i class="fas fa-clock text-3xl"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Monthly Statistics -->
            <div class="bg-white rounded-lg shadow-sm p-6 mb-8">
                <h2 class="text-xl font-semibold mb-4">Monthly Statistics (<?php echo date('Y'); ?>)</h2>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Month</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Total</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Approved</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Rejected</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Approval Rate</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php 
                            $months = [
                                1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
                                5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
                                9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December'
                            ];
                            foreach ($monthly_stats as $stat): 
                                $total = $stat['total'];
                                $approved = $stat['approved'];
                                $approval_rate = $total > 0 ? round(($approved / $total) * 100, 1) : 0;
                            ?>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap"><?php echo $months[$stat['month']]; ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap"><?php echo $stat['total']; ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-green-600"><?php echo $stat['approved']; ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-red-600"><?php echo $stat['rejected']; ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <div class="w-full bg-gray-200 rounded-full h-2.5 mr-2 w-24">
                                                <div class="bg-blue-600 h-2.5 rounded-full" style="width: <?php echo $approval_rate; ?>%"></div>
                                            </div>
                                            <span><?php echo $approval_rate; ?>%</span>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Recent Applications -->
            <div class="bg-white rounded-lg shadow-sm p-6">
                <h2 class="text-xl font-semibold mb-4">Recent Applications</h2>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Pass ID</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Applicant</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Action</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($recent_applications as $app): ?>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        GP<?php echo str_pad($app['pass_id'], 4, '0', STR_PAD_LEFT); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <?php echo htmlspecialchars($app['applicant_name']); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <?php echo date('Y-m-d H:i', strtotime($app['date_submitted'])); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full 
                                            <?php echo $app['hod_status'] === 'Approved' ? 'bg-green-100 text-green-800' : 
                                                ($app['hod_status'] === 'Rejected' ? 'bg-red-100 text-red-800' : 
                                                'bg-yellow-100 text-yellow-800'); ?>">
                                            <?php echo htmlspecialchars($app['hod_status']); ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <a href="view.php?id=<?php echo $app['pass_id']; ?>" 
                                           class="text-blue-600 hover:text-blue-900">View Details</a>
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
