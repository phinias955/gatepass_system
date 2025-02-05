<?php
$page_title = "Review Gate Pass";
require_once '../config/config.php';
require_once '../backend/db.php';
include 'includes/header.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if user is logged in and has HoD role
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'HoD') {
    header('Location: ../login.php');
    exit;
}

// Initialize variables
$pass_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$action = isset($_GET['action']) ? trim($_GET['action']) : '';
$error = '';
$success = '';
$gate_pass = null;
$items = [];

// Validate pass_id
if ($pass_id <= 0) {
    header('Location: index.php');
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
    $hod_dept_id = $stmt->fetchColumn();

    if (!$hod_dept_id) {
        throw new Exception("Department not found for HoD");
    }

    // Fetch gate pass details with applicant and department info
    $stmt = $pdo->prepare("
        SELECT 
            GP.*,
            CONCAT(u.fname, ' ', COALESCE(u.m_name, ''), ' ', u.l_name) as applicant_name,
            u.department_id,
            d.department_name,
            u.email as applicant_email
        FROM gate_pass GP
        JOIN users u ON GP.applicant_id = u.user_id
        JOIN departments d ON u.department_id = d.department_id
        WHERE GP.pass_id = ? 
        AND u.department_id = ?
    ");
    $stmt->execute([$pass_id, $hod_dept_id]);
    $gate_pass = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$gate_pass) {
        throw new Exception("Gate pass not found or unauthorized access");
    }

    // Fetch items
    $stmt = $pdo->prepare("
    SELECT 
        goods_id,  
        item_description,
        quantity,
        COALESCE(purpose, 'N/A') as purpose
    FROM goods 
    WHERE pass_id = ?
    ORDER BY goods_id ASC  
");
    $stmt->execute([$pass_id]);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Handle form submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $remarks = isset($_POST['remarks']) ? trim($_POST['remarks']) : '';
        $action = isset($_POST['action']) ? trim($_POST['action']) : '';

        // Validate action
        if (!in_array($action, ['approve', 'reject'])) {
            throw new Exception("Invalid action specified");
        }

        // Validate remarks
        if (empty($remarks)) {
            throw new Exception("Remarks are required");
        }

        $pdo->beginTransaction();

        try {
            // Update gate pass status
            $new_status = ($action === 'approve') ? 'Approved' : 'Rejected';
            $date_field = ($action === 'approve') ? 'hod_approval_date' : 'hod_rejection_date';

            $stmt = $pdo->prepare("
                UPDATE gate_pass 
                SET hod_status = ?,
                    hod_remarks = ?,
                    {$date_field} = CURRENT_TIMESTAMP
                WHERE pass_id = ?
            ");
            $stmt->execute([$new_status, $remarks, $pass_id]);

            // Log the action
            $stmt = $pdo->prepare("
                INSERT INTO activity_log (
                    user_id, 
                    action_type, 
                    action_description, 
                    related_id,
                    created_at
                ) VALUES (?, ?, ?, ?, CURRENT_TIMESTAMP)
            ");
            $action_description = "Gate pass #" . $pass_id . " " . strtolower($new_status) . " by HoD";
            $stmt->execute([$_SESSION['user_id'], $new_status, $action_description, $pass_id]);

            // Send email notification to applicant
            if (isset($gate_pass['applicant_email'])) {
                try {
                    $to = $gate_pass['applicant_email'];
                    $subject = "Gate Pass " . ucfirst($new_status);
                    $message = "Your gate pass (ID: GP" . str_pad($pass_id, 3, '0', STR_PAD_LEFT) . ") has been " . 
                              strtolower($new_status) . " by the HoD.\n\nRemarks: " . $remarks;
                    
                    // Use proper email headers with error handling
                    $headers = array(
                        'From: ' . SITE_NAME . ' <' . SITE_EMAIL . '>',
                        'Reply-To: ' . SITE_EMAIL,
                        'X-Mailer: PHP/' . phpversion(),
                        'Content-Type: text/plain; charset=UTF-8'
                    );
            
                    if (!mail($to, $subject, $message, implode("\r\n", $headers))) {
                        error_log("Failed to send email notification to: " . $to);
                        // Don't throw exception as email is not critical
                    }
                } catch (Exception $e) {
                    error_log("Email error: " . $e->getMessage());
                    // Continue processing as email is not critical
                }
            }

            $pdo->commit();
            $success = "Gate pass has been successfully " . strtolower($new_status);

            // Redirect back to dashboard after 2 seconds
            header("refresh:2;url=index.php");
        } catch (Exception $e) {
            $pdo->rollBack();
            throw new Exception("Error processing request: " . $e->getMessage());
        }
    }
} catch (Exception $e) {
    $error = $e->getMessage();
    error_log("Error in review.php: " . $e->getMessage());
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
                <h1 class="text-2xl font-semibold text-gray-900">Review Gate Pass</h1>
                <nav class="flex" aria-label="Breadcrumb">
                    <ol class="inline-flex items-center space-x-1 md:space-x-3">
                        <li><a href="index.php" class="text-gray-500 hover:text-gray-700">Dashboard</a></li>
                        <li class="flex items-center">
                            <svg class="w-6 h-6 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"></path>
                            </svg>
                            <span class="text-gray-500">Review</span>
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
                <div class="bg-white rounded-lg shadow-sm p-6">
                    <!-- Gate Pass Details -->
                    <div class="mb-6">
                        <h2 class="text-xl font-semibold mb-4">Gate Pass Details</h2>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <p class="text-gray-600">Pass ID</p>
                                <p class="font-semibold">GP<?php echo str_pad($gate_pass['pass_id'], 3, '0', STR_PAD_LEFT); ?></p>
                            </div>
                            <div>
                                <p class="text-gray-600">Applicant Name</p>
                                <p class="font-semibold"><?php echo htmlspecialchars($gate_pass['applicant_name']); ?></p>
                            </div>
                            <div>
                                <p class="text-gray-600">Submission Date</p>
                                <p class="font-semibold"><?php echo date('Y-m-d H:i', strtotime($gate_pass['date_submitted'])); ?></p>
                            </div>
                            <div>
                                <p class="text-gray-600">Department</p>
                                <p class="font-semibold"><?php echo htmlspecialchars($gate_pass['department_name']); ?></p>
                            </div>
                            <div>
                                <p class="text-gray-600">Current Status</p>
                                <p class="font-semibold <?php echo $gate_pass['hod_status'] === 'Pending' ? 'text-yellow-600' : ($gate_pass['hod_status'] === 'Approved' ? 'text-green-600' : 'text-red-600'); ?>">
                                    <?php echo htmlspecialchars($gate_pass['hod_status']); ?>
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Items List -->
                    <div class="mb-6">
                        <h3 class="text-lg font-semibold mb-3">Items</h3>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Item Description</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Quantity</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Purpose</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <?php if (!empty($items)): ?>
                                        <?php foreach ($items as $item): ?>
                                            <tr>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                    <?php echo htmlspecialchars($item['item_description']); ?>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                    <?php echo htmlspecialchars($item['quantity']); ?>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                    <?php echo htmlspecialchars($item['purpose']); ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="3" class="px-6 py-4 text-center text-sm text-gray-500">No items found</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Action Form -->
                    <?php if ($gate_pass['hod_status'] === 'Pending'): ?>
                        <form method="POST" class="mt-6" id="reviewForm">
                            <div class="mb-4">
                                <label for="remarks" class="block text-sm font-medium text-gray-700">Remarks <span class="text-red-500">*</span></label>
                                <textarea id="remarks" name="remarks" rows="3" required
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
                                <a href="index.php"
                                    class="bg-gray-500 text-white px-4 py-2 rounded-md hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2">
                                    Cancel
                                </a>
                            </div>
                        </form>
                    <?php else: ?>
                        <div class="mt-6 border-t pt-4">
                            <p class="text-lg font-semibold">
                                Status:
                                <span class="<?php echo $gate_pass['hod_status'] === 'Approved' ? 'text-green-500' : 'text-red-500'; ?>">
                                    <?php echo htmlspecialchars($gate_pass['hod_status']); ?>
                                </span>
                            </p>
                            <?php if (!empty($gate_pass['hod_remarks'])): ?>
                                <p class="mt-2">
                                    <span class="font-semibold">Remarks:</span>
                                    <?php echo htmlspecialchars($gate_pass['hod_remarks']); ?>
                                </p>
                            <?php endif; ?>
                            <div class="mt-4">
                                <a href="index.php"
                                    class="inline-block bg-gray-500 text-white px-4 py-2 rounded-md hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2">
                                    Back to Dashboard
                                </a>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </main>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.getElementById('reviewForm');
        if (form) {
            form.addEventListener('submit', function(e) {
                const remarks = document.getElementById('remarks').value.trim();
                if (!remarks) {
                    e.preventDefault();
                    alert('Please enter remarks before submitting.');
                    return false;
                }

                const action = e.submitter.value;
                if (!confirm(`Are you sure you want to ${action} this gate pass?`)) {
                    e.preventDefault();
                    return false;
                }
            });
        }
    });
</script>