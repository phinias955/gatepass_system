<?php
session_start();
require_once 'includes/header.php';
require_once '../backend/db.php';

// Check authentication
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Estate Officer') {
    header('Location: ../login.php');
    exit();
}

// Fetch gate pass history
$query = "SELECT 
    g.pass_id,
    CONCAT(u.fname, ' ', u.l_name) as applicant_name,
    g.vehicle_registration,
    g.date_submitted,
    d.department_name,
    d.hod_name,
    m.reason_name,
    g.hod_status,
    g.estate_office_status,
    g.final_status,
    g.hod_remarks,
    g.hod_approval_date,
    g.hod_rejection_date
FROM gate_pass g
JOIN users u ON g.applicant_id = u.user_id
JOIN departments d ON u.department_id = d.department_id
JOIN movement_reason m ON g.reason_for_movement = m.reason_id
WHERE g.estate_office_status IS NOT NULL 
ORDER BY g.date_submitted DESC";

$stmt = $pdo->prepare($query);
$stmt->execute();
$history = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>History - Gate Pass System</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* Your existing styles */
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

        @media (max-width: 768px) {
            .responsive-table {
                display: block;
                overflow-x: auto;
                white-space: nowrap;
            }
        }

        /* Status badge styles */
        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.875rem;
            font-weight: 500;
        }
        .status-approved {
            background-color: #DEF7EC;
            color: #03543F;
        }
        .status-rejected {
            background-color: #FDE8E8;
            color: #9B1C1C;
        }
        .status-pending {
            background-color: #FEF3C7;
            color: #92400E;
        }
        .upline{
            text-decoration: none;
            color: blue;

        }
        .upcase{
            text-transform: uppercase;
        }
        .upline_color{
            color: green;
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
                        <h1 class="text-2xl font-bold text-gray-900">Gate Pass History</h1>
                        <div class="flex space-x-4">
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
                                <div class="p-3 rounded-full bg-green-100 text-green-500">
                                    <i class="fas fa-check-circle text-2xl"></i>
                                </div>
                                <div class="ml-4">
                                    <h2 class="text-gray-600">Approved Passes</h2>
                                    <p class="text-2xl font-semibold">
                                        <?php 
                                        echo count(array_filter($history, function($item) {
                                            return $item['estate_office_status'] === 'Granted';
                                        }));
                                        ?>
                                    </p>
                                </div>
                            </div>
                        </div>
                        <div class="bg-white rounded-lg shadow p-6 card-hover animate-fade-in" style="animation-delay: 0.2s">
                            <div class="flex items-center">
                                <div class="p-3 rounded-full bg-red-100 text-red-500">
                                    <i class="fas fa-times-circle text-2xl"></i>
                                </div>
                                <div class="ml-4">
                                    <h2 class="text-gray-600">Rejected Passes</h2>
                                    <p class="text-2xl font-semibold">
                                        <?php 
                                        echo count(array_filter($history, function($item) {
                                            return $item['estate_office_status'] === 'Not Granted';
                                        }));
                                        ?>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- History Table -->
                    <div class="bg-white rounded-lg shadow animate-fade-in" style="animation-delay: 0.2s">
                        <div class="p-6">
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Pass ID</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Applicant</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Department</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">HoD Name</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Vehicle Reg.</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Reason</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        <?php foreach ($history as $index => $pass): ?>
                                        <tr class="hover:bg-gray-50 animate-fade-in" 
                                            style="animation-delay: <?php echo (0.3 + ($index * 0.1)); ?>s">
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                DARTU/GP<?php echo sprintf('%03d', $pass['pass_id']); ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap upcase upline_color">
                                                <?php echo htmlspecialchars($pass['applicant_name']); ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap upcase">
                                                <?php echo htmlspecialchars($pass['department_name']); ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap upline ">
                                                <?php echo htmlspecialchars($pass['hod_name']); ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap upcase">
                                                <?php echo htmlspecialchars($pass['vehicle_registration']); ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap upcase">
                                                <?php echo htmlspecialchars($pass['reason_name']); ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <span class="status-badge <?php 
                                                    echo $pass['estate_office_status'] === 'Granted' 
                                                        ? 'status-approved' 
                                                        : 'status-rejected'; 
                                                ?>">
                                                    <?php echo htmlspecialchars($pass['estate_office_status']); ?>
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <?php echo date('M d, Y', strtotime($pass['date_submitted'])); ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <a href="view_pass.php?id=<?php echo $pass['pass_id']; ?>" 
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
        // Add loading state to buttons
        document.addEventListener('DOMContentLoaded', function() {
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