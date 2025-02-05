<?php
session_start();
require_once 'includes/header.php';
require_once '../backend/db.php';

// Check authentication
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Estate Officer') {
    header('Location: ../login.php');
    exit();
}

// Fetch pending approvals
$query = "SELECT 
    g.pass_id,
    CONCAT(u.fname, ' ', u.l_name) as applicant_name,
    g.vehicle_registration,
    g.date_submitted,
    d.department_name,
    m.reason_name,
    g.hod_status,
    g.estate_office_status,
    g.final_status
FROM gate_pass g
JOIN users u ON g.applicant_id = u.user_id
JOIN departments d ON u.department_id = d.department_id
JOIN movement_reason m ON g.reason_for_movement = m.reason_id
WHERE g.estate_office_status IS NULL 
AND g.hod_status = 'Approved'
ORDER BY g.date_submitted DESC";

$stmt = $pdo->prepare($query);
$stmt->execute();
$pending_approvals = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Final Approvals - Gate Pass System</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* Animations */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .animate-fade-in {
            animation: fadeIn 0.5s ease-out forwards;
        }

        .card-hover {
            transition: all 0.3s ease;
        }

        .card-hover:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        }

        /* Loading animation */
        .loading-spinner {
            border: 4px solid #f3f3f3;
            border-top: 4px solid #3498db;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        /* Responsive table */
        @media (max-width: 768px) {
            .responsive-table {
                display: block;
                overflow-x: auto;
                white-space: nowrap;
            }
        }
    </style>
</head>
<body class="bg-gray-100">
    <div class="flex min-h-screen">
        <!-- Sidebar -->
        <?php require_once 'includes/sidebar.php'; ?>

        <!-- Main Content -->
        <div class="flex-1 flex flex-col overflow-hidden">
            <!-- Top Navigation -->
            <header class="bg-white shadow">
                <div class="px-4 py-6">
                    <div class="flex justify-between items-center">
                        <h1 class="text-2xl font-bold text-gray-900">Final Approvals</h1>
                        <div class="flex items-center space-x-4">
                            <button class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg transition-colors duration-200">
                                <i class="fas fa-filter mr-2"></i>Filter
                            </button>
                            <button onclick="window.print()" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg transition-colors duration-200">
                                <i class="fas fa-print mr-2"></i>Print
                            </button>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Page Content -->
            <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-100">
                <div class="container mx-auto px-4 py-6">
                    <!-- Statistics Cards -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                        <div class="bg-white rounded-lg shadow p-6 card-hover animate-fade-in" style="animation-delay: 0.1s">
                            <div class="flex items-center">
                                <div class="p-3 rounded-full bg-blue-100 text-blue-500">
                                    <i class="fas fa-clock text-2xl"></i>
                                </div>
                                <div class="ml-4">
                                    <h2 class="text-gray-600">Pending Approvals</h2>
                                    <p class="text-2xl font-semibold"><?php echo count($pending_approvals); ?></p>
                                </div>
                            </div>
                        </div>
                        <!-- Add more statistics cards as needed -->
                    </div>

                    <!-- Approvals Table -->
                    <div class="bg-white rounded-lg shadow animate-fade-in" style="animation-delay: 0.2s">
                        <div class="p-6">
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Pass ID</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Applicant</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Department</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Vehicle Reg.</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        <?php foreach ($pending_approvals as $index => $approval): ?>
                                        <tr class="hover:bg-gray-50 animate-fade-in" style="animation-delay: <?php echo (0.3 + ($index * 0.1)); ?>s">
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                GP<?php echo sprintf('%03d', $approval['pass_id']); ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <?php echo htmlspecialchars($approval['applicant_name']); ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <?php echo htmlspecialchars($approval['department_name']); ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <?php echo htmlspecialchars($approval['vehicle_registration']); ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <?php echo date('M d, Y', strtotime($approval['date_submitted'])); ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <a href="view_pass.php?id=<?php echo $approval['pass_id']; ?>" 
                                                   class="text-blue-600 hover:text-blue-900 mr-3 transition-colors duration-200">
                                                    <i class="fas fa-eye"></i> View
                                                </a>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script>
        // this javascript is for loading state to buttons if you want to add loading state to other elements, you can do so by adding the class "loading-spinner" to the element and the animation "spin" to the element
        document.addEventListener('DOMContentLoaded', function() {
            // Example: Add loading state to buttons
            const buttons = document.querySelectorAll('button');
            buttons.forEach(button => {
                button.addEventListener('click', function() {
                    button.classList.add('opacity-75', 'cursor-wait');
                    setTimeout(() => {
                        button.classList.remove('opacity-75', 'cursor-wait');
                    }, 1000);
                });
            });
        });
    </script>
</body>
</html>