<?php
$page_title = "Gate Pass History";
require_once '../config/config.php';
require_once '../backend/db.php';
include 'includes/header.php';

// Check if user is logged in and has HoD role
if (!isset($_SESSION['user_id']) || strtolower($_SESSION['role']) !== 'hod') {
    header('Location: ../login.php');
    exit;
}

// Initialize variables
$department_id = null;
$applications = [];
$error = '';
$success = '';
$limit = isset($_GET['limit']) ? max(1, (int)$_GET['limit']) : 10; // Ensure positive limit
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1; // Ensure positive page
$offset = ($page - 1) * $limit;
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : '';
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : '';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

try {
    // Get HoD's department_id
    $stmt = $pdo->prepare("
        SELECT department_id 
        FROM users 
        WHERE user_id = ?
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $department_id = $stmt->fetchColumn();

    if (!$department_id) {
        throw new Exception("Unable to determine the department.");
    }

    // Build base query

$query = "
    SELECT 
        GP.*,
        u.fname, 
        u.m_name, 
        u.l_name, 
        u.email as applicant_email,
        d.department_name,
        CASE 
            WHEN GP.hod_status = 'Approved' THEN GP.hod_approval_date
            WHEN GP.hod_status = 'Rejected' THEN GP.hod_rejection_date
            ELSE NULL 
        END as decision_date,
        (SELECT COUNT(*) FROM goods g WHERE g.pass_id = GP.pass_id) as items_count,
        (SELECT GROUP_CONCAT(CONCAT(g.item_description, ' (', g.quantity, ')') SEPARATOR ', ') 
         FROM goods g WHERE g.pass_id = GP.pass_id LIMIT 3) as items_preview
    FROM gate_pass GP
    JOIN users u ON GP.applicant_id = u.user_id
    JOIN departments d ON u.department_id = d.department_id
    WHERE d.department_id = ?
";

    $params = [$department_id];

    if ($status_filter) {
        $query .= " AND GP.hod_status = ?";
        $params[] = $status_filter;
    }
    if ($date_from) {
        $query .= " AND DATE(GP.date_submitted) >= ?";
        $params[] = $date_from;
    }
    if ($date_to) {
        $query .= " AND DATE(GP.date_submitted) <= ?";
        $params[] = $date_to;
    }
    if ($search) {
        $query .= " AND (
            CONCAT(u.fname, ' ', COALESCE(u.m_name, ''), ' ', u.l_name) LIKE ? 
            OR GP.pass_id LIKE ?
            OR EXISTS (
                SELECT 1 
                FROM goods g 
                WHERE g.pass_id = GP.pass_id 
                AND g.item_description LIKE ?
            )
        )";
        $search_term = "%$search%";
        $params[] = $search_term;
        $params[] = $search_term;
        $params[] = $search_term;
    }

    // Get total records
    $count_query = "
        SELECT COUNT(*) 
        FROM gate_pass GP
        JOIN users u ON GP.applicant_id = u.user_id
        JOIN departments d ON u.department_id = d.department_id
        WHERE d.department_id = ?
    ";
    $count_stmt = $pdo->prepare($count_query);
    $count_stmt->execute($params);
    $total_records = $count_stmt->fetchColumn();
    $total_pages = ceil($total_records / $limit);

    // Add sorting and pagination
    $query .= " ORDER BY GP.date_submitted DESC LIMIT $limit OFFSET $offset";

    // Execute final query
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $applications = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    error_log("Error in history.php: " . $e->getMessage());
    $error = "An error occurred while fetching the history. Please contact support.";
    $applications = [];
    $total_pages = 0;
    $total_records = 0;
}
?>

    
    <!-- HTML Structure -->
    <div class="flex h-screen bg-gray-100">
        <!-- Sidebar -->
        <?php include 'includes/sidebar.php'; ?>
    
        <!-- Main Content -->
        <div class="flex-1 ml-64">
            <!-- Header -->
            <header class="bg-white shadow-sm">
                <div class="flex justify-between items-center px-8 py-4">
                    <h1 class="text-2xl font-semibold text-gray-900">Gate Pass History</h1>
                    <nav class="flex" aria-label="Breadcrumb">
                        <ol class="inline-flex items-center space-x-1 md:space-x-3">
                            <li><a href="index.php" class="text-gray-500 hover:text-gray-700">Dashboard</a></li>
                            <li class="flex items-center">
                                <svg class="w-6 h-6 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"></path>
                                </svg>
                                <span class="text-gray-500">History</span>
                            </li>
                        </ol>
                    </nav>
                </div>
            </header>
                    <!-- Main Content Area -->
        <main class="p-8">
            <?php if ($error): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                    <strong class="font-bold">Error!</strong>
                    <span class="block sm:inline"><?php echo htmlspecialchars($error); ?></span>
                </div>
            <?php endif; ?>

            <!-- Filters -->
            <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
                <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4" id="filterForm">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Status</label>
                        <select name="status" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            <option value="">All Status</option>
                            <option value="Approved" <?php echo $status_filter === 'Approved' ? 'selected' : ''; ?>>Approved</option>
                            <option value="Rejected" <?php echo $status_filter === 'Rejected' ? 'selected' : ''; ?>>Rejected</option>
                            <option value="Pending" <?php echo $status_filter === 'Pending' ? 'selected' : ''; ?>>Pending</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">From Date</label>
                        <input type="date" name="date_from" value="<?php echo $date_from; ?>" 
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">To Date</label>
                        <input type="date" name="date_to" value="<?php echo $date_to; ?>" 
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Search</label>
                        <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" 
                               placeholder="Search by name, ID or items"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>
                    <div class="md:col-span-4 flex justify-between items-center">
                        <div>
                            <button type="button" onclick="exportToCSV()" 
                                    class="bg-green-500 text-white px-4 py-2 rounded-md hover:bg-green-600 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2">
                                Export to CSV
                            </button>
                        </div>
                        <div class="flex space-x-2">
                            <button type="submit" 
                                    class="bg-blue-500 text-white px-4 py-2 rounded-md hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                                Apply Filters
                            </button>
                            <a href="history.php" 
                               class="bg-gray-500 text-white px-4 py-2 rounded-md hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2">
                                Clear Filters
                            </a>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Applications Table -->
            <div class="bg-white rounded-lg shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Pass ID</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Applicant</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Submission Date</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Items</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php if (!empty($applications)): ?>
                                <?php foreach ($applications as $app): ?>
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">
                                                GP<?php echo str_pad($app['pass_id'], 3, '0', STR_PAD_LEFT); ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap txt-formart">
                                            <div class="text-sm font-medium text-gray-900">
                                                <?php 
                                                    echo htmlspecialchars(trim($app['fname'] . ' ' . 
                                                         ($app['m_name'] ? $app['m_name'] . ' ' : '') . 
                                                         $app['l_name'])); 
                                                ?>
                                            </div>
                                            <div class="text-sm text-gray-500 txt-formart">
                                                <?php echo htmlspecialchars($app['applicant_email']); ?>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap txt-formart">
                                            <div class="text-sm text-gray-900">
                                                <?php echo date('Y-m-d', strtotime($app['date_submitted'])); ?>
                                            </div>
                                            <div class="text-sm text-gray-500">
                                                <?php echo date('H:i', strtotime($app['date_submitted'])); ?>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap txt-formart">
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                                <?php echo $app['hod_status'] === 'Approved' ? 'bg-green-100 text-green-800' : 
                                                    ($app['hod_status'] === 'Rejected' ? 'bg-red-100 text-red-800' : 
                                                    'bg-yellow-100 text-yellow-800'); ?>">
                                                <?php echo htmlspecialchars($app['hod_status']); ?>
                                            </span>
                                            <?php if ($app['decision_date']): ?>
                                                <div class="text-xs text-gray-500 mt-1">
                                                    <?php echo date('Y-m-d H:i', strtotime($app['decision_date'])); ?>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="text-sm text-gray-900 txt-formart">
                                                <?php echo $app['items_count']; ?> items
                                            </div>
                                            <div class="text-sm text-gray-500 truncate max-w-xs" title="<?php echo htmlspecialchars($app['items_preview']); ?>">
                                                <?php echo htmlspecialchars($app['items_preview'] ?? 'No items'); ?>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                            <a href="view.php?id=<?php echo $app['pass_id']; ?>" 
                                               class="text-blue-600 hover:text-blue-900">View Details</a>
                                        </td>
                                        
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="px-6 py-4 text-center text-gray-500">
                                        No applications found
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                    <div class="px-6 py-4 bg-gray-50 border-t border-gray-200">
                        <div class="flex justify-between items-center">
                            <div class="text-sm text-gray-700">
                                Showing <?php echo $offset + 1; ?> to <?php echo min($offset + $limit, $total_records); ?> 
                                of <?php echo $total_records; ?> entries
                            </div>
                            <div class="flex space-x-2">
                                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                    <a href="?page=<?php echo $i; ?>&status=<?php echo urlencode($status_filter); ?>&date_from=<?php echo urlencode($date_from); ?>&date_to=<?php echo urlencode($date_to); ?>&search=<?php echo urlencode($search); ?>" 
                                       class="px-3 py-1 rounded-md <?php echo $page === $i ? 'bg-blue-500 text-white' : 'bg-white text-gray-500 hover:bg-gray-50'; ?>">
                                        <?php echo $i; ?>
                                    </a>
                                <?php endfor; ?>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Date range validation
    const dateFrom = document.querySelector('input[name="date_from"]');
    const dateTo = document.querySelector('input[name="date_to"]');

    if (dateFrom && dateTo) {
        dateFrom.addEventListener('change', function() {
            if (dateTo.value && this.value > dateTo.value) {
                alert('From date cannot be later than To date');
                this.value = '';
            }
        });

        dateTo.addEventListener('change', function() {
            if (dateFrom.value && this.value < dateFrom.value) {
                alert('To date cannot be earlier than From date');
                this.value = '';
            }
        });
    }

    // Export to CSV functionality
    window.exportToCSV = function() {
        const currentUrl = new URL(window.location.href);
        currentUrl.searchParams.set('export', 'excel');
        window.location.href = currentUrl.toString();
    };
});
</script>
