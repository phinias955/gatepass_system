<?php
// Calculate statistics
$totalPasses = count($results);
$statusCounts = [
    'Approved' => 0,
    'Rejected' => 0,
    'Pending' => 0
];
$departmentStats = [];

// foreach ($results as $pass) {
//     $statusCounts[$pass['final_status']]++;
    
//     $dept = $pass['department_name'];
//     if (!isset($departmentStats[$dept])) {
//         $departmentStats[$dept] = [
//             'total' => 0,
//             'approved' => 0,
//             'rejected' => 0,
//             'pending' => 0
//         ];
//     }
//     $departmentStats[$dept]['total']++;
//     $departmentStats[$dept][$pass['final_status']]++;
// }
// ?>

<div class="space-y-6">
    <!-- Recent Gate Passes -->
    <div class="bg-white rounded-lg shadow-lg p-6">
        <h3 class="text-xl font-semibold mb-4">Recent Gate Passes</h3>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Pass ID</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Applicant</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Department</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Items</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($results as $pass): ?>
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap">GP<?php echo sprintf('%03d', $pass['pass_id']); ?></td>
                        <td class="px-6 py-4"><?php echo htmlspecialchars($pass['applicant_name']); ?></td>
                        <td class="px-6 py-4"><?php echo htmlspecialchars($pass['department_name']); ?></td>
                        <td class="px-6 py-4"><?php echo date('M d, Y', strtotime($pass['date_submitted'])); ?></td>
                        <td class="px-6 py-4"><?php echo htmlspecialchars($pass['items']); ?></td>
                        <td class="px-6 py-4">
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                <?php echo $pass['final_status'] === 'Approved' ? 'bg-green-100 text-green-800' : 
                                    ($pass['final_status'] === 'Rejected' ? 'bg-red-100 text-red-800' : 
                                    'bg-yellow-100 text-yellow-800'); ?>">
                                <?php echo $pass['final_status']; ?>
                            </span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>