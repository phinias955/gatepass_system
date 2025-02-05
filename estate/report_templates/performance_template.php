<?php
// Calculate processing times and efficiency metrics
$processingQuery = "SELECT 
    g.pass_id,
    g.date_submitted,
    g.hod_approval_date,
    g.final_status,
    d.department_name,
    CONCAT(u.fname, ' ', u.l_name) as applicant_name,
    TIMESTAMPDIFF(HOUR, g.date_submitted, g.hod_approval_date) as processing_time
FROM gate_pass g
JOIN users u ON g.applicant_id = u.user_id
JOIN departments d ON u.department_id = d.department_id
WHERE g.hod_approval_date IS NOT NULL
ORDER BY g.date_submitted DESC";

$stmt = $pdo->prepare($processingQuery);
$stmt->execute();
$performanceData = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate averages and metrics
$totalPasses = count($performanceData);
$avgProcessingTime = array_sum(array_column($performanceData, 'processing_time')) / $totalPasses;
$departmentStats = [];

foreach ($performanceData as $pass) {
    $dept = $pass['department_name'];
    if (!isset($departmentStats[$dept])) {
        $departmentStats[$dept] = [
            'total' => 0,
            'processing_time' => 0,
            'approved' => 0
        ];
    }
    $departmentStats[$dept]['total']++;
    $departmentStats[$dept]['processing_time'] += $pass['processing_time'];
    if ($pass['final_status'] === 'Approved') {
        $departmentStats[$dept]['approved']++;
    }
}
?>

<div class="space-y-8 p-6">
    <!-- Performance Overview Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="bg-gradient-to-br from-purple-500 to-indigo-600 rounded-xl p-6 text-white shadow-lg">
            <h3 class="text-lg font-semibold mb-2">Average Processing Time</h3>
            <p class="text-3xl font-bold"><?php echo round($avgProcessingTime, 1); ?> hours</p>
            <p class="text-sm opacity-80 mt-2">Across all departments</p>
        </div>
        
        <div class="bg-gradient-to-br from-blue-500 to-cyan-600 rounded-xl p-6 text-white shadow-lg">
            <h3 class="text-lg font-semibold mb-2">Total Passes Processed</h3>
            <p class="text-3xl font-bold"><?php echo $totalPasses; ?></p>
            <p class="text-sm opacity-80 mt-2">In selected period</p>
        </div>
        
        <div class="bg-gradient-to-br from-emerald-500 to-teal-600 rounded-xl p-6 text-white shadow-lg">
            <h3 class="text-lg font-semibold mb-2">Efficiency Rate</h3>
            <p class="text-3xl font-bold"><?php 
                $efficiency = ($totalPasses > 0) ? 
                    round((array_sum(array_column($departmentStats, 'approved')) / $totalPasses) * 100, 1) : 0;
                echo $efficiency; ?>%</p>
            <p class="text-sm opacity-80 mt-2">Approval rate</p>
        </div>
    </div>

    <!-- Department Performance -->
    <div class="bg-white rounded-xl shadow-lg p-6">
        <h3 class="text-xl font-bold mb-4">Department Performance Analysis</h3>
        <div class="space-y-4">
            <?php foreach ($departmentStats as $dept => $stats): ?>
            <div class="border-b border-gray-200 pb-4">
                <div class="flex justify-between items-center mb-2">
                    <h4 class="font-semibold text-gray-800"><?php echo htmlspecialchars($dept); ?></h4>
                    <span class="text-sm text-gray-500">
                        Avg. Processing: <?php echo round($stats['processing_time'] / $stats['total'], 1); ?> hours
                    </span>
                </div>
                <div class="w-full bg-gray-200 rounded-full h-2.5">
                    <div class="bg-blue-600 h-2.5 rounded-full" style="width: <?php 
                        echo round(($stats['approved'] / $stats['total']) * 100); ?>%"></div>
                </div>
                <div class="flex justify-between text-sm text-gray-600 mt-1">
                    <span>Success Rate: <?php 
                        echo round(($stats['approved'] / $stats['total']) * 100, 1); ?>%</span>
                    <span>Total Passes: <?php echo $stats['total']; ?></span>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Recent Processing Timeline -->
    <div class="bg-white rounded-xl shadow-lg p-6">
        <h3 class="text-xl font-bold mb-4">Recent Processing Timeline</h3>
        <div class="space-y-4">
            <?php 
            $recentPasses = array_slice($performanceData, 0, 5);
            foreach ($recentPasses as $pass): ?>
            <div class="flex items-center space-x-4">
                <div class="flex-shrink-0">
                    <div class="w-12 h-12 rounded-full bg-indigo-100 flex items-center justify-center">
                        <span class="text-indigo-600 font-semibold">GP<?php echo sprintf('%03d', $pass['pass_id']); ?></span>
                    </div>
                </div>
                <div class="flex-grow">
                    <div class="flex justify-between items-center">
                        <h4 class="font-medium text-gray-800"><?php echo htmlspecialchars($pass['applicant_name']); ?></h4>
                        <span class="text-sm text-gray-500"><?php echo date('M d, Y', strtotime($pass['date_submitted'])); ?></span>
                    </div>
                    <div class="text-sm text-gray-600">
                        Processing Time: <?php echo $pass['processing_time']; ?> hours
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php 
                            echo $pass['final_status'] === 'Approved' ? 
                                'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                            <?php echo $pass['final_status']; ?>
                        </span>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- Add Chart.js for visualizations -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    // Add any additional charts or visualizations here
</script> 