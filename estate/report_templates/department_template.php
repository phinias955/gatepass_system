<div class="bg-white rounded-lg shadow-lg p-6">
    <h3 class="text-xl font-semibold mb-4">Department-wise Report</h3>
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Department</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Total Passes</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Approved</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Rejected</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Pending</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Success Rate</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php foreach ($results as $dept): ?>
                <tr>
                    <td class="px-6 py-4"><?php echo htmlspecialchars($dept['department_name']); ?></td>
                    <td class="px-6 py-4"><?php echo $dept['total_passes']; ?></td>
                    <td class="px-6 py-4 text-green-600"><?php echo $dept['approved']; ?></td>
                    <td class="px-6 py-4 text-red-600"><?php echo $dept['rejected']; ?></td>
                    <td class="px-6 py-4 text-yellow-600"><?php echo $dept['pending']; ?></td>
                    <td class="px-6 py-4">
                        <?php 
                        $successRate = $dept['total_passes'] > 0 ? 
                            round(($dept['approved'] / $dept['total_passes']) * 100, 1) : 0;
                        echo $successRate . '%';
                        ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div> 