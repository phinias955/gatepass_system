<?php
ob_start(); // Start output buffering
session_start();
error_reporting(0);
ini_set('display_errors', 0);

$page_title = "Movement History";
require_once '../config/config.php';
require_once '../backend/db.php';
require_once '../vendor/autoload.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit();
}

// Generate PDF
if (isset($_GET['generate_pdf']) && isset($_GET['order_id'])) {
    try {
        $order_id = $_GET['order_id'];
        $stmt = $pdo->prepare("
            SELECT 
                mo.*,
                sl.location_name AS source_location,
                dl.location_name AS destination_location,
                u.name AS user_name,
                u.designation,
                d.department_name,
                GROUP_CONCAT(
                    CONCAT(
                        moi.item_description, 
                        ' (Qty: ', 
                        moi.quantity, 
                        ')'
                    ) SEPARATOR '\n'
                ) as items
            FROM movement_orders mo
            JOIN locations sl ON mo.source_location_id = sl.location_id
            JOIN locations dl ON mo.destination_location_id = dl.location_id
            JOIN users u ON mo.user_id = u.user_id
            JOIN departments d ON u.department_id = d.department_id
            LEFT JOIN movement_order_items moi ON mo.order_id = moi.order_id
            WHERE mo.order_id = ?
            GROUP BY mo.order_id
        ");
        $stmt->execute([$order_id]);
        $movement = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($movement && $movement['status'] === 'Approved') {
            // Clean output buffer
            ob_end_clean();

            // Create new PDF document
            $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

            // Set document information
            $pdf->SetCreator(PDF_CREATOR);
            $pdf->SetAuthor('Your University');
            $pdf->SetTitle('Movement Order #' . $order_id);

            // Remove header/footer
            $pdf->setPrintHeader(false);
            $pdf->setPrintFooter(false);

            // Set margins
            $pdf->SetMargins(15, 15, 15);

            // Add a page
            $pdf->AddPage();

            // Set font
            $pdf->SetFont('helvetica', '', 12);

            // Create HTML content
            $html = '
            <h1 style="text-align: center;">Movement Order</h1>
            <h2 style="text-align: center;">Order #' . $order_id . '</h2>
            <br>
            <table border="0" cellpadding="5">
                <tr>
                    <td width="30%"><strong>Name:</strong></td>
                    <td width="70%">' . $movement['user_name'] . '</td>
                </tr>
                <tr>
                    <td><strong>Department:</strong></td>
                    <td>' . $movement['department_name'] . '</td>
                </tr>
                <tr>
                    <td><strong>Designation:</strong></td>
                    <td>' . $movement['designation'] . '</td>
                </tr>
                <tr>
                    <td><strong>Date:</strong></td>
                    <td>' . date('F j, Y', strtotime($movement['movement_date'])) . '</td>
                </tr>
                <tr>
                    <td><strong>Source:</strong></td>
                    <td>' . $movement['source_location'] . '</td>
                </tr>
                <tr>
                    <td><strong>Destination:</strong></td>
                    <td>' . $movement['destination_location'] . '</td>
                </tr>
                <tr>
                    <td><strong>Priority:</strong></td>
                    <td>' . $movement['priority_level'] . '</td>
                </tr>
                <tr>
                    <td><strong>Status:</strong></td>
                    <td>' . $movement['status'] . '</td>
                </tr>
            </table>
            <br>
            <h3>Items to be Moved:</h3>
            <table border="1" cellpadding="5">
                <tr style="background-color: #f0f0f0;">
                    <th width="70%">Description</th>
                    <th width="30%">Quantity</th>
                </tr>';

            // Fetch items for this order
            $stmt = $pdo->prepare("
                SELECT * FROM movement_order_items 
                WHERE order_id = ?
            ");
            $stmt->execute([$order_id]);
            $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($items as $item) {
                $html .= '
                <tr>
                    <td>' . $item['item_description'] . '</td>
                    <td>' . $item['quantity'] . '</td>
                </tr>';
            }

            $html .= '</table>';

            // Output HTML content
            $pdf->writeHTML($html, true, false, true, false, '');

            // Close and output PDF document
            $pdf->Output('movement_order_' . $order_id . '.pdf', 'D');
            exit();
        } else {
            $_SESSION['error_message'] = "PDF generation is only available for approved orders.";
            header("Location: " . $_SERVER['PHP_SELF']);
            exit();
        }
    } catch (Exception $e) {
        error_log($e->getMessage());
        $_SESSION['error_message'] = "Failed to generate PDF. Please try again.";
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    }
}

// Fetch movement history
try {
    $stmt = $pdo->prepare("
        SELECT 
            mo.*,
            sl.location_name AS source_location,
            dl.location_name AS destination_location,
            u.name AS user_name,
            COUNT(moi.item_id) as total_items
        FROM movement_orders mo
        JOIN locations sl ON mo.source_location_id = sl.location_id
        JOIN locations dl ON mo.destination_location_id = dl.location_id
        JOIN users u ON mo.user_id = u.user_id
        LEFT JOIN movement_order_items moi ON mo.order_id = moi.order_id
        WHERE mo.user_id = ?
        GROUP BY mo.order_id
        ORDER BY mo.created_at DESC
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $movements = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log($e->getMessage());
    $_SESSION['error_message'] = "Failed to fetch movement history.";
}

include 'includes/header.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .fade-in {
            animation: fadeIn 0.6s ease-in-out;
        }
        
        .slide-in {
            animation: slideIn 0.5s ease-out;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        @keyframes slideIn {
            from { transform: translateY(20px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }

        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.875rem;
            font-weight: 500;
        }

        .status-pending {
            background-color: #FEF3C7;
            color: #92400E;
        }

        .status-approved {
            background-color: #DEF7EC;
            color: #03543F;
        }

        .status-rejected {
            background-color: #FDE8E8;
            color: #9B1C1C;
        }

        .hover-effect {
            transition: all 0.3s ease;
        }

        .hover-effect:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        }

        .table-row {
            transition: background-color 0.3s ease;
        }

        .table-row:hover {
            background-color: #F9FAFB;
        }
    </style>
</head>
<body class="bg-gray-50">
    <div class="flex min-h-screen">
        <!-- Sidebar -->
        <div class="fixed inset-y-0 left-0 z-50">
            <?php include 'includes/sidebar.php'; ?>
        </div>

        <!-- Main Content -->
        <div class="flex-1 ml-64">
            <?php include 'includes/topbar.php'; ?>
            
            <div class="p-8">
                <div class="max-w-7xl mx-auto">
                    <!-- Header Section -->
                    <div class="bg-white p-6 rounded-lg shadow-lg mb-6 fade-in">
                        <div class="flex justify-between items-center">
                            <div>
                                <h1 class="text-2xl font-bold text-gray-900">Movement History</h1>
                                <p class="text-gray-600 mt-1">Track all your movement orders and their status</p>
                            </div>
                            <div class="flex space-x-4">
                                <button onclick="window.print()" class="flex items-center px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors">
                                    <i class="fas fa-print mr-2"></i> Print
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Messages -->
                    <?php if (isset($_SESSION['error_message'])): ?>
                        <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded-lg fade-in" role="alert">
                            <p class="font-medium">Error</p>
                            <p><?php echo htmlspecialchars($_SESSION['error_message']); ?></p>
                        </div>
                        <?php unset($_SESSION['error_message']); ?>
                    <?php endif; ?>

                    <!-- Table Section -->
                    <div class="bg-white rounded-lg shadow-lg overflow-hidden slide-in">
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Order ID</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Source</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Destination</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Items</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Priority</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <?php foreach ($movements as $movement): ?>
                                        <tr class="table-row">
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                                #<?php echo htmlspecialchars($movement['order_id']); ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                <?php echo date('M j, Y', strtotime($movement['movement_date'])); ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                <?php echo htmlspecialchars($movement['source_location']); ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                <?php echo htmlspecialchars($movement['destination_location']); ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                <?php echo htmlspecialchars($movement['total_items']); ?> items
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                                    <?php echo strtolower($movement['priority_level']) === 'urgent' ? 'bg-red-100 text-red-800' : 
                                                        (strtolower($movement['priority_level']) === 'high' ? 'bg-yellow-100 text-yellow-800' : 'bg-green-100 text-green-800'); ?>">
                                                    <?php echo htmlspecialchars($movement['priority_level']); ?>
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <span class="status-badge <?php 
                                                    echo $movement['status'] === 'Approved' ? 'status-approved' : 
                                                        ($movement['status'] === 'Rejected' ? 'status-rejected' : 'status-pending'); 
                                                ?>">
                                                    <?php echo htmlspecialchars($movement['status']); ?>
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-center text-sm font-medium">
                                                <div class="flex justify-center space-x-3">
                                                    <a href="view_movement.php?id=<?php echo $movement['order_id']; ?>" 
                                                       class="text-indigo-600 hover:text-indigo-900 hover-effect">
                                                        <i class="fas fa-eye"></i> View
                                                    </a>
                                                    <?php if ($movement['status'] === 'Approved'): ?>
                                                        <a href="?generate_pdf=1&order_id=<?php echo $movement['order_id']; ?>" 
                                                           class="text-green-600 hover:text-green-900 hover-effect">
                                                            <i class="fas fa-file-pdf"></i> PDF
                                                        </a>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Add animation to table rows on page load
        document.addEventListener('DOMContentLoaded', function() {
            const rows = document.querySelectorAll('.table-row');
            rows.forEach((row, index) => {
                row.style.animation = `slideIn 0.3s ease-out ${index * 0.1}s forwards`;
                row.style.opacity = '0';
            });
        });

        // Auto-hide messages after 5 seconds
        const messages = document.querySelectorAll('[role="alert"]');
        messages.forEach(message => {
            setTimeout(() => {
                message.style.transition = 'opacity 0.5s ease-out';
                message.style.opacity = '0';
                setTimeout(() => message.remove(), 500);
            }, 5000);
        });
    </script>
</body>
</html>