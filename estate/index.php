<?php
session_start();
require_once 'includes/header.php';
require_once '../backend/db.php';

// Check if user is logged in and has estate officer role
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Estate Officer') {
    header('Location: ../login.php');
    exit();
}

// Handle Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (isset($_POST['action'])) {
            switch ($_POST['action']) {
                case 'grant':
                    $pass_id = filter_input(INPUT_POST, 'pass_id', FILTER_SANITIZE_NUMBER_INT);
                    $stmt = $pdo->prepare("UPDATE gate_pass SET 
                        estate_office_status = 'Granted',
                        final_status = 'Approved'
                        WHERE pass_id = :pass_id");
                    $stmt->execute(['pass_id' => $pass_id]);
                    
                    // Log the action
                    $log_stmt = $pdo->prepare("INSERT INTO activity_log 
                        (user_id, action_type, action_description, related_id) 
                        VALUES (:user_id, 'Granted', 'Gate pass granted', :pass_id)");
                    $log_stmt->execute([
                        'user_id' => $_SESSION['user_id'],
                        'pass_id' => $pass_id
                    ]);
                    break;

                case 'deny':
                    $pass_id = filter_input(INPUT_POST, 'pass_id', FILTER_SANITIZE_NUMBER_INT);
                    $stmt = $pdo->prepare("UPDATE gate_pass SET 
                        estate_office_status = 'Not Granted',
                        final_status = 'Rejected'
                        WHERE pass_id = :pass_id");
                    $stmt->execute(['pass_id' => $pass_id]);
                    
                    // Log the action
                    $log_stmt = $pdo->prepare("INSERT INTO activity_log 
                        (user_id, action_type, action_description, related_id) 
                        VALUES (:user_id, 'Denied', 'Gate pass denied', :pass_id)");
                    $log_stmt->execute([
                        'user_id' => $_SESSION['user_id'],
                        'pass_id' => $pass_id
                    ]);
                    break;

                case 'approve_all':
                    $stmt = $pdo->prepare("UPDATE gate_pass SET 
                        estate_office_status = 'Granted',
                        final_status = 'Approved'
                        WHERE hod_status = 'Approved' AND final_status = 'Pending'");
                    $stmt->execute();
                    break;
            }
            
            // Redirect to prevent form resubmission
            header('Location: ' . $_SERVER['PHP_SELF']);
            exit();
        }
    } catch(PDOException $e) {
        error_log($e->getMessage());
        $error_message = "Action failed. Please try again.";
    }
}

// Get stats for cards
$today = date('Y-m-d');

try {
    // Pending Finals Count
    $pending_query = "SELECT COUNT(*) as pending_count FROM gate_pass 
                     WHERE hod_status = 'Approved' AND final_status = 'Pending'";
    $stmt = $pdo->query($pending_query);
    $pending_count = $stmt->fetch()['pending_count'];

    // Today's Processed Count
    $processed_query = "SELECT COUNT(*) as processed_count FROM gate_pass 
                       WHERE DATE(date_submitted) = :today";
    $stmt = $pdo->prepare($processed_query);
    $stmt->execute(['today' => $today]);
    $processed_count = $stmt->fetch()['processed_count'];

    // Active Departments Count
    $dept_query = "SELECT COUNT(*) as dept_count FROM departments";
    $stmt = $pdo->query($dept_query);
    $dept_count = $stmt->fetch()['dept_count'];

    // Monthly Gate Passes Count
    $month_query = "SELECT COUNT(*) as month_count FROM gate_pass 
                    WHERE MONTH(date_submitted) = MONTH(CURRENT_DATE())";
    $stmt = $pdo->query($month_query);
    $month_count = $stmt->fetch()['month_count'];

    // Get data for daily activity chart
    $daily_activity_query = "SELECT HOUR(date_submitted) as hour, 
                            COUNT(*) as count FROM gate_pass 
                            WHERE DATE(date_submitted) = CURRENT_DATE()
                            GROUP BY HOUR(date_submitted)";
    $stmt = $pdo->query($daily_activity_query);
    $hours = [];
    $counts = [];
    while($row = $stmt->fetch()) {
        $hours[] = date('ga', strtotime($row['hour'] . ':00'));
        $counts[] = $row['count'];
    }

    // Get department distribution data
    $dept_dist_query = "SELECT d.department_name, COUNT(g.pass_id) as pass_count 
                        FROM departments d 
                        LEFT JOIN users u ON d.department_id = u.department_id 
                        LEFT JOIN gate_pass g ON u.user_id = g.applicant_id 
                        GROUP BY d.department_id 
                        ORDER BY pass_count DESC 
                        LIMIT 5";
    $stmt = $pdo->query($dept_dist_query);
    $dept_labels = [];
    $dept_data = [];
    while($row = $stmt->fetch()) {
        $dept_labels[] = $row['department_name'];
        $dept_data[] = $row['pass_count'];
    }

    // Get pending approvals for table
    $pending_passes_query = "SELECT g.pass_id, d.department_name, 
                            CONCAT(u.fname, ' ', u.l_name) as staff_name,
                            g.hod_status
                            FROM gate_pass g
                            JOIN users u ON g.applicant_id = u.user_id
                            JOIN departments d ON u.department_id = d.department_id
                            WHERE g.hod_status = 'Approved' AND g.final_status = 'Pending'
                            ORDER BY g.date_submitted DESC
                            LIMIT 5";
    $stmt = $pdo->query($pending_passes_query);
    $pending_passes = $stmt->fetchAll();

} catch(PDOException $e) {
    error_log($e->getMessage());
    $error_message = "An error occurred while fetching data. Please try again later.";
}
?>

<div class="flex h-screen">
    <!-- Sidebar -->
    <?php include 'includes/sidebar.php'; ?>

    <!-- Main Content -->
    <div class="flex-1 flex flex-col overflow-hidden">
        <!-- Header -->
        <?php include 'includes/head.php'; ?>

        <!-- Main Content Area -->
        <main class="flex-1 overflow-y-auto p-8">
            <?php if (isset($error_message)): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                    <span class="block sm:inline"><?php echo htmlspecialchars($error_message); ?></span>
                </div>
            <?php endif; ?>

            <!-- Quick Actions -->
            <div class="mb-8 flex space-x-4">
                <button onclick="downloadReport()" class="bg-blue-500 text-white px-6 py-3 rounded-lg hover:bg-blue-600 transition-colors">
                    <i class="fas fa-download mr-2"></i> Download Reports
                </button>
                <button onclick="printSummary()" class="bg-green-500 text-white px-6 py-3 rounded-lg hover:bg-green-600 transition-colors">
                    <i class="fas fa-print mr-2"></i> Print Summary
                </button>
            </div>

            <!-- Stats Cards -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                <!-- Pending Final Approvals -->
                <div class="bg-white rounded-lg shadow-sm p-6">
                    <div class="flex justify-between items-center">
                        <div>
                            <p class="text-gray-500 text-sm">Pending Finals</p>
                            <h3 class="text-2xl font-bold text-gray-800"><?php echo $pending_count; ?></h3>
                            <p class="text-yellow-500 text-sm">Requires Action</p>
                        </div>
                        <div class="bg-yellow-100 p-3 rounded-full">
                            <i class="fas fa-clock text-yellow-500"></i>
                        </div>
                    </div>
                </div>

                <!-- Today's Processed -->
                <div class="bg-white rounded-lg shadow-sm p-6">
                    <div class="flex justify-between items-center">
                        <div>
                            <p class="text-gray-500 text-sm">Today's Processed</p>
                            <h3 class="text-2xl font-bold text-gray-800"><?php echo $processed_count; ?></h3>
                            <p class="text-green-500 text-sm">Completed</p>
                        </div>
                        <div class="bg-green-100 p-3 rounded-full">
                            <i class="fas fa-check-circle text-green-500"></i>
                        </div>
                    </div>
                </div>

                <!-- Active Departments -->
                <div class="bg-white rounded-lg shadow-sm p-6">
                    <div class="flex justify-between items-center">
                        <div>
                            <p class="text-gray-500 text-sm">Active Departments</p>
                            <h3 class="text-2xl font-bold text-gray-800"><?php echo $dept_count; ?></h3>
                            <p class="text-blue-500 text-sm">Departments</p>
                        </div>
                        <div class="bg-blue-100 p-3 rounded-full">
                            <i class="fas fa-building text-blue-500"></i>
                        </div>
                    </div>
                </div>

                <!-- Total Gate Passes -->
                <div class="bg-white rounded-lg shadow-sm p-6">
                    <div class="flex justify-between items-center">
                        <div>
                            <p class="text-gray-500 text-sm">Total Gate Passes</p>
                            <h3 class="text-2xl font-bold text-gray-800"><?php echo $month_count; ?></h3>
                            <p class="text-purple-500 text-sm">This Month</p>
                        </div>
                        <div class="bg-purple-100 p-3 rounded-full">
                            <i class="fas fa-id-card text-purple-500"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Charts Section -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
                <!-- Daily Activity Chart -->
                <div class="bg-white rounded-lg shadow-sm p-6">
                    <h3 class="text-lg font-semibold mb-4">Daily Activity</h3>
                    <canvas id="dailyActivity" height="300"></canvas>
                </div>

                <!-- Department Distribution -->
                <div class="bg-white rounded-lg shadow-sm p-6">
                    <h3 class="text-lg font-semibold mb-4">Department Distribution</h3>
                    <canvas id="deptDistribution" height="300"></canvas>
                </div>
            </div>

            <!-- Pending Final Approvals Table -->
            <div class="bg-white rounded-lg shadow-sm p-6">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-semibold">Pending Final Approvals</h3>
                    <div class="flex space-x-2">
                        <button onclick="handleAction('approve_all')" class="bg-green-500 text-white px-4 py-2 rounded-lg hover:bg-green-600 transition-colors">
                            Approve All
                        </button>
                        <button onclick="window.location.href='view_all.php'" class="bg-blue-500 text-white px-4 py-2 rounded-lg hover:bg-blue-600 transition-colors">
                            View All
                        </button>
                    </div>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Pass ID</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Department</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Staff Name</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">HoD Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach($pending_passes as $pass): ?>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap">GP<?php echo sprintf('%03d', $pass['pass_id']); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap"><?php echo htmlspecialchars($pass['department_name']); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap"><?php echo htmlspecialchars($pass['staff_name']); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                          <?php echo $pass['hod_status'] == 'Approved' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'; ?>">
                                        <?php echo htmlspecialchars($pass['hod_status']); ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap space-x-2">
                                    <button onclick="handleAction('grant', <?php echo $pass['pass_id']; ?>)" 
                                            class="bg-green-500 text-white px-3 py-1 rounded-md hover:bg-green-600">
                                        Grant
                                    </button>
                                    <button onclick="handleAction('deny', <?php echo $pass['pass_id']; ?>)" 
                                            class="bg-red-500 text-white px-3 py-1 rounded-md hover:bg-red-600">
                                        Deny
                                    </button>
                                    <a href="view_pass.php?id=<?php echo $pass['pass_id']; ?>" 
                                       class="bg-blue-500 text-white px-3 py-1 rounded-md hover:bg-blue-600 inline-block">
                                        View
                                    </a>
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

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
function handleAction(action, passId) {
    if (confirm('Are you sure you want to ' + action + ' this gate pass?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '';

        const actionInput = document.createElement('input');
        actionInput.type = 'hidden';
        actionInput.name = 'action';
        actionInput.value = action;

        const passInput = document.createElement('input');
        passInput.type = 'hidden';
        passInput.name = 'pass_id';
        passInput.value = passId;

        form.appendChild(actionInput);
        form.appendChild(passInput);
        document.body.appendChild(form);
        form.submit();
    }
}

function downloadReport() {
    window.location.href = 'reports/generate_report.php';
}

function printSummary() {
    window.print();
}

const activityCtx = document.getElementById('dailyActivity').getContext('2d');
new Chart(activityCtx, {
    type: 'line',
    data: {
        labels: <?php echo json_encode($hours); ?>,
        datasets: [{
            label: 'Gate Passes',
            data: <?php echo json_encode($counts); ?>,
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

const deptCtx = document.getElementById('deptDistribution').getContext('2d');
new Chart(deptCtx, {
    type: 'pie',
    data: {
        labels: <?php echo json_encode($dept_labels); ?>,
        datasets: [{
            data: <?php echo json_encode($dept_data); ?>,
            backgroundColor: [
                'rgba(59, 130, 246, 0.5)',
                'rgba(16, 185, 129, 0.5)',
                'rgba(245, 158, 11, 0.5)',
                'rgba(139, 92, 246, 0.5)',
                'rgba(107, 114, 128, 0.5)'
            ],
            borderColor: [
                'rgb(59, 130, 246)',
                'rgb(16, 185, 129)',
                'rgb(245, 158, 11)',
                'rgb(139, 92, 246)',
                'rgb(107, 114, 128)'
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
</body>
</html>