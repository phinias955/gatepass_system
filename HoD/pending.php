<?php
$page_title = "Pending Approvals";
require_once '../config/config.php';
require_once '../backend/db.php';
include 'includes/header.php';

// Check if user is logged in and has HoD role
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'HoD') {
    header('Location: ../login.php');
    exit;
}

try {
    // Get HoD's department_id
    $stmt = $pdo->prepare("
        SELECT department_id 
        FROM users 
        WHERE user_id = ? 
        AND role = 'HoD'
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $department_id = $stmt->fetchColumn();

    if (!$department_id) {
        throw new Exception("Department not found for HoD");
    }

    // Fetch all pending applications with details
    $stmt = $pdo->prepare("
        SELECT 
            GP.pass_id,
            GP.date_submitted,
            CONCAT(u.fname, ' ', COALESCE(u.m_name, ''), ' ', u.l_name) as applicant_name,
            u.email as applicant_email,
            d.department_name,
            (
                SELECT COUNT(*)
                FROM goods g
                WHERE g.pass_id = GP.pass_id
            ) as items_count,
            (
                SELECT GROUP_CONCAT(g.item_description SEPARATOR ', ')
                FROM goods g
                WHERE g.pass_id = GP.pass_id
                LIMIT 3
            ) as items_preview
        FROM gate_pass GP
        JOIN users u ON GP.applicant_id = u.user_id
        JOIN departments d ON u.department_id = d.department_id
        WHERE u.department_id = ? 
        AND GP.hod_status = 'Pending'
        ORDER BY GP.date_submitted DESC
    ");
    $stmt->execute([$department_id]);
    $pending_applications = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    error_log("Error in pending.php: " . $e->getMessage());
    $error = "An error occurred while fetching pending applications.";
}
?>

<div class="flex h-screen bg-gray-100">
    <!-- Sidebar -->
    <?php include 'includes/sidebar.php'; ?>

    <!-- Main Content -->
    <div class="flex-1 ml-64">
        <!-- Header -->
        <header class="bg-white shadow-sm">
            <div class="flex justify-between items-center px-8 py-4">
                <h1 class="text-2xl font-semibold text-gray-900">Pending Approvals</h1>
                <nav class="flex" aria-label="Breadcrumb">
                    <ol class="inline-flex items-center space-x-1 md:space-x-3">
                        <li><a href="index.php" class="text-gray-500 hover:text-gray-700">Dashboard</a></li>
                        <li class="flex items-center">
                            <svg class="w-6 h-6 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"></path>
                            </svg>
                            <span class="text-gray-500">Pending Approvals</span>
                        </li>
                    </ol>
                </nav>
            </div>
        </header>

        <!-- Main Content Area -->
        <main class="p-8">
            <?php if (isset($error)): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                    <strong class="font-bold">Error!</strong>
                    <span class="block sm:inline"><?php echo htmlspecialchars($error); ?></span>
                </div>
            <?php endif; ?>

            <!-- Pending Applications Table -->
            <div class="bg-white rounded-lg shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-lg font-semibold text-gray-800">
                        Pending Gate Pass Applications
                        <?php if (!empty($pending_applications)): ?>
                            <span class="ml-2 px-2 py-1 text-sm bg-yellow-100 text-yellow-800 rounded-full">
                                <?php echo count($pending_applications); ?> pending
                            </span>
                        <?php endif; ?>
                    </h2>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Pass ID</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Applicant</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Submission Date</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Items</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php if (!empty($pending_applications)): ?>
                                <?php foreach ($pending_applications as $app): ?>
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">
                                                DARTU/GP<?php echo str_pad($app['pass_id'], 3, '0', STR_PAD_LEFT); ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm font-medium text-gray-900">
                                                <?php echo htmlspecialchars($app['applicant_name']); ?>
                                            </div>
                                            <div class="text-sm text-gray-500">
                                                <?php echo htmlspecialchars($app['applicant_email']); ?>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm text-gray-900">
                                                <?php echo date('Y-m-d', strtotime($app['date_submitted'])); ?>
                                            </div>
                                            <div class="text-sm text-gray-500">
                                                <?php echo date('H:i', strtotime($app['date_submitted'])); ?>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="text-sm text-gray-900">
                                                <?php echo $app['items_count']; ?> items
                                            </div>
                                            <div class="text-sm text-gray-500 truncate max-w-xs">
                                                <?php echo htmlspecialchars($app['items_preview'] ?? 'No items'); ?>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                            <a href="review.php?id=<?php echo $app['pass_id']; ?>" 
                                               class="text-blue-600 hover:text-blue-900 mr-3">Review</a>
                                            <button onclick="quickApprove(<?php echo $app['pass_id']; ?>)" 
                                                    class="text-green-600 hover:text-green-900 mr-3">
                                                Quick Approve
                                            </button>
                                            <button onclick="quickReject(<?php echo $app['pass_id']; ?>)" 
                                                    class="text-red-600 hover:text-red-900">
                                                Quick Reject
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="px-6 py-4 text-center text-gray-500">
                                        No pending applications found
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
function quickApprove(passId) {
    if (confirm('Are you sure you want to approve this gate pass?')) {
        window.location.href = `review.php?id=${passId}&action=approve`;
    }
}

function quickReject(passId) {
    if (confirm('Are you sure you want to reject this gate pass?')) {
        window.location.href = `review.php?id=${passId}&action=reject`;
    }
}

// Add sorting functionality if needed
document.addEventListener('DOMContentLoaded', function() {
    // Add any additional JavaScript functionality here
});
</script>
