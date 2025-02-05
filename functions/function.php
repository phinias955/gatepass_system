<?php
define('BASEPATH', true);
session_start();
require_once 'includes/header.php';
require_once '../backend/db.php';
require_once 'includes/functions.php';

// Security checks
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Estate Officer') {
    logAttempt($_SESSION['user_id'] ?? 'unknown', 'Unauthorized access attempt to view_pass.php');
    header('Location: ../login.php');
    exit();
}

// Generate CSRF token if not exists
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Initialize variables
$error_message = null;
$success_message = null;
$pass = null;
$goods = null;

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        logAttempt($_SESSION['user_id'], 'Invalid CSRF token in view_pass.php');
        die('Invalid CSRF token');
    }

    try {
        handlePostAction($pdo, $_POST, $_SESSION['user_id']);
    } catch (Exception $e) {
        $error_message = "Action failed: " . $e->getMessage();
        logError('Action handling failed: ' . $e->getMessage());
    }
}

// Get pass ID from URL and validate
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $error_message = "Invalid gate pass ID.";
} else {
    $pass_id = filter_input(INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT);

    try {
        // Get gate pass details with all related information
        $pass = getGatePassDetails($pdo, $pass_id);
        
        if ($pass) {
            // Get goods associated with this pass
            $goods = getGatePassGoods($pdo, $pass_id);
            
            // Log view action
            logAction($pdo, $_SESSION['user_id'], $pass_id, 'Viewed');
            
        } else {
            $error_message = "Gate pass not found or not accessible.";
        }

    } catch(PDOException $e) {
        logError("Database Error in view_pass.php: " . $e->getMessage());
        $error_message = "Failed to retrieve gate pass details. Please try again later.";
    }
}

// Include the HTML template
include 'templates/view_pass_template.php';

/**
 * Helper Functions
 */

function getGatePassDetails($pdo, $pass_id) {
    $pass_query = "SELECT 
        g.*,
        CONCAT(u.fname, ' ', u.l_name) as applicant_name,
        u.email as applicant_email,
        u.phone as applicant_phone,
        d.department_name,
        m.reason_name as movement_reason,
        (SELECT CONCAT(fname, ' ', l_name) FROM users WHERE user_id = g.applicant_id) as hod_name,
        (
            SELECT COUNT(*) 
            FROM activity_log 
            WHERE related_id = g.pass_id AND action_type = 'View'
        ) as view_count
    FROM gate_pass g
    JOIN users u ON g.applicant_id = u.user_id
    JOIN departments d ON u.department_id = d.department_id
    JOIN movement_reason m ON g.reason_for_movement = m.reason_id
    WHERE g.pass_id = :pass_id";

    $stmt = $pdo->prepare($pass_query);
    $stmt->execute(['pass_id' => $pass_id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function getGatePassGoods($pdo, $pass_id) {
    $goods_query = "SELECT 
        goods_id,
        pass_id,
        item_description as item_name,
        quantity,
        remarks as description,
        purpose
    FROM goods 
    WHERE pass_id = :pass_id
    ORDER BY goods_id ASC";
    
    $stmt = $pdo->prepare($goods_query);
    $stmt->execute(['pass_id' => $pass_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function handlePostAction($pdo, $post_data, $user_id) {
    if (!isset($post_data['action']) || !isset($post_data['pass_id'])) {
        throw new Exception('Invalid action parameters');
    }

    $action = $post_data['action'];
    $pass_id = filter_var($post_data['pass_id'], FILTER_SANITIZE_NUMBER_INT);
    
    // Start transaction
    $pdo->beginTransaction();
    
    try {
        switch ($action) {
            case 'grant':
                $status = updatePassStatus($pdo, $pass_id, 'Granted', 'Approved');
                $action_type = 'Granted';
                break;
            case 'deny':
                $status = updatePassStatus($pdo, $pass_id, 'Not Granted', 'Rejected');
                $action_type = 'Denied';
                break;
            default:
                throw new Exception('Invalid action type');
        }

        // Log the action
        logAction($pdo, $user_id, $pass_id, $action_type);
        
        // Commit transaction
        $pdo->commit();
        
        $_SESSION['success_message'] = "Gate pass successfully {$action_type}.";
        
    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
}

function updatePassStatus($pdo, $pass_id, $estate_status, $final_status) {
    $update_query = "UPDATE gate_pass SET 
        estate_office_status = :estate_status,
        final_status = :final_status,
        updated_at = CURRENT_TIMESTAMP
    WHERE pass_id = :pass_id";
    
    $stmt = $pdo->prepare($update_query);
    return $stmt->execute([
        'estate_status' => $estate_status,
        'final_status' => $final_status,
        'pass_id' => $pass_id
    ]);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gate Pass #<?php echo sprintf('%03d', $pass['pass_id'] ?? ''); ?> - Details</title>
    
    <!-- CSS Styles -->
    <style>
        /* Print Styles */
        @media print {
            .no-print { display: none !important; }
            .print-only { display: block !important; }
            @page {
                size: A4;
                margin: 2cm;
            }
            body { font-size: 12pt; }
            .status-timeline, .items-table { break-inside: avoid; }
        }

        /* Custom Animations */
        .fade-in {
            animation: fadeIn 0.3s ease-in;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .grid-cols-1 { grid-template-columns: 1fr; }
            .space-x-4 { gap: 0.5rem; }
            .px-6 { 
                padding-left: 1rem;
                padding-right: 1rem;
            }
        }

        /* Custom Scrollbar */
        .custom-scrollbar::-webkit-scrollbar {
            width: 8px;
        }
        .custom-scrollbar::-webkit-scrollbar-track {
            background: #f1f1f1;
        }
        .custom-scrollbar::-webkit-scrollbar-thumb {
            background: #888;
            border-radius: 4px;
        }
    </style>
</head>
<body class="bg-gray-100">
    <div class="flex h-screen">
        <!-- Sidebar -->
        <?php include 'includes/sidebar.php'; ?>

        <!-- Main Content -->
        <div class="flex-1 flex flex-col overflow-hidden">
            <!-- Header -->
            <?php include 'includes/head.php'; ?>

            <!-- Main Content Area -->
            <main class="flex-1 overflow-y-auto p-8 custom-scrollbar">
                <!-- Notifications -->
                <?php include 'includes/notifications.php'; ?>

                <!-- Back Button -->
                <div class="mb-6 no-print">
                    <a href="index.php" class="bg-gray-500 text-white px-4 py-2 rounded-lg hover:bg-gray-600 transition-colors">
                        <i class="fas fa-arrow-left mr-2"></i> Back to Dashboard
                    </a>
                </div>

                <?php if (isset($pass) && $pass): ?>
                    <!-- Gate Pass Details Card -->
                    <div class="bg-white rounded-lg shadow-sm p-6 mb-6 fade-in">
                        <!-- Header with Status -->
                        <div class="flex justify-between items-center mb-6">
                            <div>
                                <h2 class="text-2xl font-bold text-gray-800">
                                    Gate Pass #GP<?php echo sprintf('%03d', $pass['pass_id']); ?>
                                </h2>
                                <p class="text-sm text-gray-500">
                                    Viewed <?php echo $pass['view_count']; ?> times
                                </p>
                            </div>
                            <div class="flex space-x-4">
                                <!-- Status Badge -->
                                <span class="px-4 py-2 rounded-full <?php 
                                    echo match($pass['final_status']) {
                                        'Approved' => 'bg-green-100 text-green-800',
                                        'Rejected' => 'bg-red-100 text-red-800',
                                        default => 'bg-yellow-100 text-yellow-800'
                                    };
                                ?>">
                                    <?php echo htmlspecialchars($pass['final_status']); ?>
                                </span>
                                
                                <!-- Action Buttons -->
                                <div class="no-print">
                                    <button onclick="printPass()" 
                                            class="bg-blue-500 text-white px-4 py-2 rounded-lg hover:bg-blue-600">
                                        <i class="fas fa-print mr-2"></i> Print
                                    </button>
                                    <?php if ($pass['final_status'] === 'Pending'): ?>
                                        <button onclick="handleAction('deny', <?php echo $pass['pass_id']; ?>)" 
                                                class="bg-red-500 text-white px-4 py-2 rounded-lg hover:bg-red-600 ml-2">
                                            <i class="fas fa-times mr-2"></i> Deny
                                        </button>
                                        <button onclick="handleAction('grant', <?php echo $pass['pass_id']; ?>)" 
                                                class="bg-green-500 text-white px-4 py-2 rounded-lg hover:bg-green-600 ml-2">
                                            <i class="fas fa-check mr-2"></i> Grant
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <!-- Status Timeline -->
                        <?php include 'includes/status_timeline.php'; ?>

                        <!-- Details Grid -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-6">
                            <!-- Applicant Details -->
                            <div class="bg-gray-50 p-4 rounded-lg">
                                <h3 class="text-lg font-semibold mb-4">Applicant Details</h3>
                                <div class="space-y-3">
                                    <?php foreach (getApplicantDetails($pass) as $label => $value): ?>
                                        <p><span class="font-medium"><?php echo $label; ?>:</span> 
                                           <?php echo htmlspecialchars($value); ?></p>
                                    <?php endforeach; ?>
                                </div>
                            </div>

                            <!-- Pass Details -->
                            <div class="bg-gray-50 p-4 rounded-lg">
                                <h3 class="text-lg font-semibold mb-4">Pass Details</h3>
                                <div class="space-y-3">
                                    <?php foreach (getPassDetails($pass) as $label => $value): ?>
                                        <p><span class="font-medium"><?php echo $label; ?>:</span> 
                                           <?php echo htmlspecialchars($value); ?></p>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>

                        <!-- Goods List -->
                        <?php if (isset($goods) && $goods): ?>
                            <?php include 'includes/goods_table.php'; ?>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <div class="bg-yellow-100 border border-yellow-400 text-yellow-700 px-4 py-3 rounded relative">
                        <?php echo $error_message ?? 'Gate pass not found.'; ?>
                    </div>
                <?php endif; ?>
            </main>
        </div>
    </div>

    <!-- JavaScript -->
    <script>
    // Constants
    const REFRESH_INTERVAL = 30000; // 30 seconds
    const ANIMATION_DURATION = 300; // 300ms

    // Event Listeners
    document.addEventListener('DOMContentLoaded', function() {
        setupKeyboardShortcuts();
        setupAutoRefresh();
        setupTooltips();
    });

    // Keyboard Shortcuts
    function setupKeyboardShortcuts() {
        document.addEventListener('keydown', function(e) {
            // Print shortcut (Ctrl/Cmd + P)
            if (e.key === 'p' && (e.ctrlKey || e.metaKey)) {
                e.preventDefault();
                printPass();
            }
            // Close modal (Escape)
            if (e.key === 'Escape') {
                const modal = document.querySelector('.modal');
                if (modal) modal.remove();
            }
        });
    }

    // Auto Refresh
    function setupAutoRefresh() {
        setInterval(async () => {
            try {
                const response = await fetch(window.location.href);
                const html = await response.text();
                const parser = new DOMParser();
                const newDoc = parser.parseFromString(html, 'text/html');
                
                updateContent('.main-content', newDoc);
            } catch (error) {
                console.error('Auto-refresh failed:', error);
            }
        }, REFRESH_INTERVAL);
    }

    // Action Handlers
    function handleAction(action, passId) {
        showModal({
            title: `Confirm ${action.charAt(0).toUpperCase() + action.slice(1)}`,
            message: `Are you sure you want to ${action} this gate pass?`,
            onConfirm: () => submitAction(action, passId)
        });
    }

    function submitAction(action, passId) {
        showLoading();
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = window.location.href;

        // Add CSRF token
        const csrfToken = document.createElement('input');
        csrfToken.type = 'hidden';
        csrfToken.name = 'csrf_token';
        csrfToken.value = '<?php echo $_SESSION['csrf_token']; ?>';
        
        const actionInput = document.createElement('input');
        actionInput.type = 'hidden';
        actionInput.name = 'action';
        actionInput.value = action;

        const passInput = document.createElement('input');
        passInput.type = 'hidden';
        passInput.name = 'pass_id';
        passInput.value = passId;

        form.appendChild(csrfToken);
        form.appendChild(actionInput);
        form.appendChild(passInput);
        document.body.appendChild(form);
        form.submit();
    }

    // UI Components
    function showModal({ title, message, onConfirm }) {
        const modal = document.createElement('div');
        modal.className = 'fixed inset-0 bg-gray-600 bg-opacity-50 flex items-center justify-center z-50 fade-in';
        modal.innerHTML = `
            <div class="bg-white p-6 rounded-lg shadow-xl max-w-md w-full mx-4">
                <h3 class="text-lg font-bold mb-4">${title}</h3>
                <p class="mb-6">${message}</p>
                <div class="flex justify-end space-x-2">
                    <button onclick="this.closest('.fixed').remove()" 
                            class="px-4 py-2 bg-gray-200 rounded hover:bg-gray-300 transition-colors">
                        Cancel
                    </button>
                    <button onclick="onConfirmClick(this)" 
                            class="px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600 transition-colors">
                        Confirm
                    </button>
                </div>
            </div>
        `;
        document.body.appendChild(modal);

        // Store the callback
        modal.querySelector('button:last-child').onclick = () => {
            modal.remove();
            onConfirm();
        };
    }

    function showLoading() {
        const loading = document.createElement('div');
        loading.id = 'loadingOverlay';
        loading.className = 'fixed inset-0 bg-gray-600 bg-opacity-50 flex items-center justify-center z-50 fade-in';
        loading.innerHTML = `
            <div class="bg-white p-6 rounded-lg shadow-xl text-center">
                <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-500 mx-auto"></div>
                <p class="mt-2">Processing...</p>
            </div>
        `;
        document.body.appendChild(loading);
    }

    function hideLoading() {
        const loading = document.getElementById('loadingOverlay');
        if (loading) {
            loading.classList.add('fade-out');
            setTimeout(() => loading.remove(), ANIMATION_DURATION);
        }
    }

    // Utility Functions
    function updateContent(selector, newDoc) {
        const currentElement = document.querySelector(selector);
        const newElement = newDoc.querySelector(selector);
        if (currentElement && newElement && currentElement.innerHTML !== newElement.innerHTML) {
            currentElement.innerHTML = newElement.innerHTML;
        }
    }

    function printPass() {
        window.print();
    }

    function setupTooltips() {
        const tooltips = document.querySelectorAll('[data-tooltip]');
        tooltips.forEach(element => {
            element.addEventListener('mouseenter', showTooltip);
            element.addEventListener('mouseleave', hideTooltip);
        });
    }
    </script>
</body>
</html>