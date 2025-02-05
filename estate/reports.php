<?php
session_start();
require_once 'includes/header.php';
require_once '../backend/db.php';

// Check if user is logged in and has estate officer role
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Estate Officer') {
    header('Location: ../login.php');
    exit();
}

// Fetch departments for filter
$deptQuery = "SELECT department_id, department_name FROM departments ORDER BY department_name";
$deptStmt = $pdo->query($deptQuery);
$departments = $deptStmt->fetchAll(PDO::FETCH_ASSOC);
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

            <!-- Report Generation Section -->
            <div class="bg-white rounded-lg shadow-sm p-6 mb-8">
                <div class="flex justify-between items-center mb-6">
                    <h3 class="text-lg font-semibold">Generate Reports</h3>
                </div>

                <form id="reportForm" action="generate_report.php" method="POST" class="space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <!-- Report Type -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Report Type</label>
                            <select name="report_type" class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                <option value="summary">Summary Report</option>
                                <option value="department">Department-wise Report</option>
                                <option value="detailed">Detailed Report</option>
                            </select>
                        </div>

                        <!-- Date Range Preset -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Date Range</label>
                            <select id="datePreset" class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                <option value="custom">Custom Range</option>
                                <option value="today">Today</option>
                                <option value="week">This Week</option>
                                <option value="month">This Month</option>
                                <option value="sixmonths">Last 6 Months</option>
                                <option value="year">This Year</option>
                            </select>
                        </div>

                        <!-- Status -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                            <select name="status" class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                <option value="all">All Status</option>
                                <option value="Approved">Approved</option>
                                <option value="Rejected">Rejected</option>
                                <option value="Pending">Pending</option>
                            </select>
                        </div>

                        <!-- Date Range Inputs -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Start Date</label>
                            <input type="date" name="start_date" id="start_date" 
                                   class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">End Date</label>
                            <input type="date" name="end_date" id="end_date" 
                                   class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                        </div>

                        <!-- Department -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Department</label>
                            <select name="department" class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                <option value="">All Departments</option>
                                <?php foreach ($departments as $dept): ?>
                                    <option value="<?php echo $dept['department_id']; ?>">
                                        <?php echo htmlspecialchars($dept['department_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="flex justify-end space-x-4 mt-6">
                        <button type="submit" class="bg-blue-500 text-white px-6 py-3 rounded-lg hover:bg-blue-600 transition-colors">
                            <i class="fas fa-sync-alt mr-2"></i> Generate Report
                        </button>
                        <button type="button" onclick="downloadPDF()" class="bg-red-500 text-white px-6 py-3 rounded-lg hover:bg-red-600 transition-colors">
                            <i class="fas fa-file-pdf mr-2"></i> Download PDF
                        </button>
                        <button type="button" onclick="window.print()" class="bg-gray-500 text-white px-6 py-3 rounded-lg hover:bg-gray-600 transition-colors">
                            <i class="fas fa-print mr-2"></i> Print Report
                        </button>
                    </div>
                </form>
            </div>

            <!-- Report Content -->
            <div class="bg-white rounded-lg shadow-sm p-6">
                <div id="reportContent">
                    <!-- Report will be loaded here -->
                </div>
            </div>
        </main>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Date preset handling
    document.getElementById('datePreset').addEventListener('change', function() {
        const today = new Date();
        const startDate = document.getElementById('start_date');
        const endDate = document.getElementById('end_date');

        switch(this.value) {
            case 'today':
                startDate.value = today.toISOString().split('T')[0];
                endDate.value = today.toISOString().split('T')[0];
                break;
            case 'week':
                const weekStart = new Date(today.setDate(today.getDate() - today.getDay()));
                startDate.value = weekStart.toISOString().split('T')[0];
                endDate.value = new Date().toISOString().split('T')[0];
                break;
            case 'month':
                startDate.value = new Date(today.getFullYear(), today.getMonth(), 1).toISOString().split('T')[0];
                endDate.value = new Date().toISOString().split('T')[0];
                break;
            case 'sixmonths':
                const sixMonthsAgo = new Date();
                sixMonthsAgo.setMonth(sixMonthsAgo.getMonth() - 6);
                startDate.value = sixMonthsAgo.toISOString().split('T')[0];
                endDate.value = new Date().toISOString().split('T')[0];
                break;
            case 'year':
                startDate.value = new Date(today.getFullYear(), 0, 1).toISOString().split('T')[0];
                endDate.value = new Date().toISOString().split('T')[0];
                break;
        }
    });

    // Form submission handling
    document.getElementById('reportForm').addEventListener('submit', async function(e) {
        e.preventDefault();
        const formData = new FormData(this);

        try {
            const response = await fetch('generate_report.php', {
                method: 'POST',
                body: formData
            });
            const data = await response.text();
            document.getElementById('reportContent').innerHTML = data;
        } catch (error) {
            console.error('Error:', error);
            alert('Error generating report. Please try again.');
        }
    });
});

function downloadPDF() {
    const form = document.getElementById('reportForm');
    const formData = new FormData(form);
    formData.append('download_pdf', 'true');

    const tempForm = document.createElement('form');
    tempForm.method = 'POST';
    tempForm.action = 'generate_pdf.php';
    tempForm.style.display = 'none';

    for (let [key, value] of formData.entries()) {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = key;
        input.value = value;
        tempForm.appendChild(input);
    }

    document.body.appendChild(tempForm);
    tempForm.submit();
    document.body.removeChild(tempForm);
}
</script>