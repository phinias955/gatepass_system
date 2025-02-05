<?php
$page_title = "Gate Pass History";
require_once '../config/config.php';
require_once '../backend/db.php';
require_once '../vendor/autoload.php'; // For TCPDF
include 'includes/header.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    $_SESSION['error'] = "Please login to continue";
    header('Location: ../login.php');
    exit;
}

// Get user ID from session
$user_id = $_SESSION['user_id']; 

try {
    // Fetch gate passes with items
    $query = "
        SELECT 
            GP.pass_id,
            GP.date_submitted,
            GP.vehicle_registration,
            GP.hod_status,
            GP.final_status,
            mr.reason_name
        FROM gate_pass GP
        LEFT JOIN movement_reason mr ON GP.reason_for_movement = mr.reason_id
        WHERE GP.applicant_id = ?
        ORDER BY GP.date_submitted DESC
    ";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute([$user_id]);
    $gate_passes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Fetch items for each gate pass
    $items_query = "
        SELECT item_description, quantity
        FROM goods
        WHERE pass_id = ?
    ";
    $items_stmt = $pdo->prepare($items_query);

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gate Pass History</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <style>
        .hover-effect {
            transition: all 0.3s ease;
        }

        .hover-effect:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }

        .fade-in {
            animation: fadeIn 0.6s ease-in;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        .table-row {
            transition: all 0.2s ease;
        }

        .table-row:hover {
            background-color: #f8fafc;
            transform: scale(1.01);
        }

        .status-badge {
            transition: all 0.3s ease;
        }

        .status-badge:hover {
            transform: translateY(-1px);
        }
    </style>
</head>
<body class="bg-gray-100">

<div class="flex min-h-screen">
    <!-- Sidebar -->
    <?php include 'includes/sidebar.php'; ?>

    <!-- Main Content -->
    <div class="flex-1 ml-64">
        <!-- Top Navigation -->
        <?php include 'includes/topbar.php'; ?>

        <!-- Main Content Area -->
        <div class="p-6 fade-in">
            <div class="max-w-7xl mx-auto">
                <div class="bg-white rounded-lg shadow-lg overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h1 class="text-2xl font-bold text-gray-900">Gate Pass History</h1>
                    </div>

                    <?php if (empty($gate_passes)): ?>
                    <div class="p-6 text-center text-gray-500">
                        No gate passes found.
                    </div>
                    <?php else: ?>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Pass ID</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Vehicle</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Items</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php foreach ($gate_passes as $pass): 
                                    // Fetch items for this pass
                                    $items_stmt->execute([$pass['pass_id']]);
                                    $items = $items_stmt->fetchAll(PDO::FETCH_ASSOC);
                                ?>
                                <tr class="table-row">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        DARTU/GP<?php echo str_pad($pass['pass_id'], 4, '0', STR_PAD_LEFT); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <?php echo date('d/m/Y H:i', strtotime($pass['date_submitted'])); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <?php echo htmlspecialchars($pass['vehicle_registration'] ?? 'N/A'); ?>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-900">
                                        <?php foreach ($items as $item): ?>
                                            <div class="mb-1">
                                                <?php echo htmlspecialchars($item['item_description']); ?> 
                                                (<?php echo htmlspecialchars($item['quantity']); ?>)
                                            </div>
                                        <?php endforeach; ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="status-badge px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                            <?php 
                                            echo match($pass['hod_status']) {
                                                'Approved' => 'bg-green-100 text-green-800',
                                                'Rejected' => 'bg-red-100 text-red-800',
                                                default => 'bg-yellow-100 text-yellow-800'
                                            };
                                            ?>">
                                            <?php echo htmlspecialchars($pass['hod_status']); ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <a href="view_application.php?id=<?php echo $pass['pass_id']; ?>" 
                                           class="text-blue-600 hover:text-blue-900 hover-effect mr-3">
                                           <i class="fas fa-eye"></i> View
                                        </a>
                                        <?php if ($pass['hod_status'] === 'Approved'): ?>
                                            <a href="view_application.php?id=<?php echo $pass['pass_id']; ?>&generate_pdf=1" 
                                               class="text-green-600 hover:text-green-900 hover-effect">
                                               <i class="fas fa-file-pdf"></i> Download PDF
                                            </a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // Add animation to table rows
    document.addEventListener('DOMContentLoaded', function() {
        const rows = document.querySelectorAll('.table-row');
        rows.forEach((row, index) => {
            row.style.animation = `fadeIn 0.3s ease-out ${index * 0.1}s forwards`;
            row.style.opacity = '0';
        });
    });
</script>

</body>
</html>