<div class="space-y-6">
    <!-- Detailed Statistics -->
    <div class="bg-white rounded-lg shadow-lg p-6">
        <h3 class="text-xl font-semibold mb-4">Detailed Gate Pass Report</h3>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Pass ID</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Vehicle Reg</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Applicant</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Department</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Reason</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Items</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">HoD Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Estate Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Final Status</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($results as $pass): ?>
                    <tr>
                        <td class="px-6 py-4">GP<?php echo sprintf('%03d', $pass['pass_id']); ?></td>
                        <td class="px-6 py-4"><?php echo htmlspecialchars($pass['vehicle_registration']); ?></td>
                        <td class="px-6 py-4"><?php echo htmlspecialchars($pass['applicant_name']); ?></td>
                        <td class="px-6 py-4"><?php echo htmlspecialchars($pass['department_name']); ?></td>
                        <td class="px-6 py-4"><?php echo htmlspecialchars($pass['reason_name']); ?></td>
                        <td class="px-6 py-4"><?php echo htmlspecialchars($pass['items']); ?></td>
                        <td class="px-6 py-4"><?php echo date('M d, Y', strtotime($pass['date_submitted'])); ?></td>
                        <td class="px-6 py-4">
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                <?php echo getStatusColor($pass['hod_status']); ?>">
                                <?php echo $pass['hod_status']; ?>
                            </span>
                        </td>
                        <td class="px-6 py-4">
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                <?php echo getEstateStatusColor($pass['estate_office_status']); ?>">
                                <?php echo $pass['estate_office_status'] ?? 'Pending'; ?>
                            </span>
                        </td>
                        <td class="px-6 py-4">
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                <?php echo getStatusColor($pass['final_status']); ?>">
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

<?php
function getStatusColor($status) {
    switch ($status) {
        case 'Approved':
            return 'bg-green-100 text-green-800';
        case 'Rejected':
            return 'bg-red-100 text-red-800';
        default:
            return 'bg-yellow-100 text-yellow-800';
    }
}

function getEstateStatusColor($status) {
    switch ($status) {
        case 'Granted':
            return 'bg-green-100 text-green-800';
        case 'Not Granted':
            return 'bg-red-100 text-red-800';
        default:
            return 'bg-yellow-100 text-yellow-800';
    }
}
?>