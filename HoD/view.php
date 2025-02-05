<?php
$page_title = "View Gate Pass";
require_once '../config/config.php';
require_once '../backend/db.php';
include 'includes/header.php';

if (!isset($_SESSION['user_id']) || strtolower($_SESSION['role']) !== 'hod') {
    header('Location: ../login.php');
    exit;
}

$error = '';
$success = '';
$gate_pass = null;
$items = [];

$pass_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$pass_id || $pass_id <= 0) {
    header('Location: history.php?error=InvalidPassID');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    $remarks = trim($_POST['remarks'] ?? '');
    $current_time = date('Y-m-d H:i:s');

    if (empty($remarks)) {
        $error = "Remarks are required.";
    } else {
        try {
            if ($action === 'approve') {
                $stmt = $pdo->prepare("
                    UPDATE gate_pass 
                    SET hod_status = 'Approved',
                        hod_approval_date = ?,
                        hod_remarks = ?
                    WHERE pass_id = ?
                ");
                $stmt->execute([$current_time, $remarks, $pass_id]);
                $success = "Gate pass has been approved successfully.";
            } elseif ($action === 'reject') {
                $stmt = $pdo->prepare("
                    UPDATE gate_pass 
                    SET hod_status = 'Rejected',
                        hod_rejection_date = ?,
                        hod_remarks = ?
                    WHERE pass_id = ?
                ");
                $stmt->execute([$current_time, $remarks, $pass_id]);
                $success = "Gate pass has been rejected.";
            } else {
                $error = "Invalid action.";
            }
        } catch (Exception $e) {
            error_log("Error in view.php action processing: " . $e->getMessage());
            $error = "An error occurred while processing your request.";
        }
    }
}

try {
    $stmt = $pdo->prepare("
        SELECT 
            GP.*, u.fname, u.m_name, u.l_name, u.email as applicant_email,
            u.phone, d.department_name, hod.fname as hod_fname, hod.l_name as hod_lname
        FROM gate_pass GP
        JOIN users u ON GP.applicant_id = u.user_id
        JOIN departments d ON u.department_id = d.department_id
        LEFT JOIN users hod ON d.hod_id = hod.user_id
        WHERE GP.pass_id = ?
    ");
    $stmt->execute([$pass_id]);
    $gate_pass = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$gate_pass) {
        error_log("No gate pass found for pass_id: $pass_id");
        header('Location: history.php?error=GatePassNotFound');
        exit;
    }

    $stmt = $pdo->prepare("SELECT * FROM goods WHERE pass_id = ? ORDER BY goods_id");
    $stmt->execute([$pass_id]);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if (empty($items)) {
        error_log("No items found for pass_id: $pass_id");
    }
} catch (Exception $e) {
    error_log("Error in view.php: " . $e->getMessage());
    $error = "An error occurred while fetching the gate pass details.";
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
                <h1 class="text-2xl font-semibold text-gray-900">View Gate Pass</h1>
                <nav class="flex" aria-label="Breadcrumb">
                    <ol class="inline-flex items-center space-x-1 md:space-x-3">
                        <li><a href="index.php" class="text-gray-500 hover:text-gray-700">Dashboard</a></li>
                        <li class="flex items-center">
                            <svg class="w-6 h-6 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"></path>
                            </svg>
                            <a href="history.php" class="text-gray-500 hover:text-gray-700">History</a>
                        </li>
                        <li class="flex items-center">
                            <svg class="w-6 h-6 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"></path>
                            </svg>
                            <span class="text-gray-500">View</span>
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

            <?php if ($success): ?>
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
                    <strong class="font-bold">Success!</strong>
                    <span class="block sm:inline"><?php echo htmlspecialchars($success); ?></span>
                </div>
            <?php endif; ?>

            <?php if ($gate_pass): ?>
                <!-- Gate Pass Details -->
                <div class="bg-white rounded-lg shadow-sm overflow-hidden mb-6">
                    <div class="p-6">
                        <h2 class="text-xl font-semibold mb-4">Gate Pass Details</h2>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <p class="text-sm text-gray-600">Pass ID</p>
                                <p class="font-medium">GP<?php echo str_pad($gate_pass['pass_id'], 4, '0', STR_PAD_LEFT); ?></p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-600">Status</p>
                                <p class="font-medium">
                                    <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full 
                                        <?php echo $gate_pass['hod_status'] === 'Approved' ? 'bg-green-100 text-green-800' : ($gate_pass['hod_status'] === 'Rejected' ? 'bg-red-100 text-red-800' :
                                                'bg-yellow-100 text-yellow-800'); ?>">
                                        <?php echo htmlspecialchars($gate_pass['hod_status']); ?>
                                    </span>
                                </p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-600">Applicant Name</p>
                                <p class="font-medium">
                                    <?php
                                    echo htmlspecialchars(trim($gate_pass['fname'] . ' ' .
                                        ($gate_pass['m_name'] ? $gate_pass['m_name'] . ' ' : '') .
                                        $gate_pass['l_name']));
                                    ?>
                                </p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-600">Department</p>
                                <p class="font-medium"><?php echo htmlspecialchars($gate_pass['department_name']); ?></p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-600">Email</p>
                                <p class="font-medium"><?php echo htmlspecialchars($gate_pass['applicant_email']); ?></p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-600">Phone</p>
                                <p class="font-medium"><?php echo htmlspecialchars($gate_pass['phone']); ?></p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-600">Submission Date</p>
                                <p class="font-medium"><?php echo date('Y-m-d H:i', strtotime($gate_pass['date_submitted'])); ?></p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-600">Purpose</p>
                                <p class="font-medium"><?php echo htmlspecialchars($gate_pass['purpose'] ?? 'No purpose specified'); ?></p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Items List -->
                <div class="bg-white rounded-lg shadow-sm overflow-hidden mb-6">
                    <div class="p-6">
                        <h2 class="text-xl font-semibold mb-4">Items</h2>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Item Description</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Quantity</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Remarks</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <?php foreach ($items as $item): ?>
                                        <tr>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <?php echo htmlspecialchars($item['item_description']); ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <?php echo htmlspecialchars($item['quantity']); ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <?php echo htmlspecialchars($item['remarks'] ?? ''); ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Action Buttons -->
                <?php if ($gate_pass['hod_status'] === 'Pending'): ?>
                    <div class="bg-white rounded-lg shadow-sm overflow-hidden">
                        <div class="p-6">
                            <h2 class="text-xl font-semibold mb-4">Take Action</h2>
                            <form method="POST" class="space-y-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Remarks</label>
                                    <textarea name="remarks" rows="3"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                        placeholder="Enter your remarks here..."></textarea>
                                </div>
                                <div class="flex space-x-4">
                                    <button type="submit" name="action" value="approve"
                                        class="bg-green-500 text-white px-4 py-2 rounded-md hover:bg-green-600 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2">
                                        Approve
                                    </button>
                                    <button type="submit" name="action" value="reject"
                                        class="bg-red-500 text-white px-4 py-2 rounded-md hover:bg-red-600 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2">
                                        Reject
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="bg-white rounded-lg shadow-sm overflow-hidden">
                        <div class="p-6">
                            <h2 class="text-xl font-semibold mb-4">HoD Remarks</h2>
                            <p class="text-gray-700">
                                <?php echo htmlspecialchars($gate_pass['hod_remarks'] ?? 'No remarks provided.'); ?>
                            </p>
                        </div>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </main>
    </div>
</div>