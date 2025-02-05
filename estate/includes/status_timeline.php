<div class="status-timeline mt-6 p-4 bg-gray-50 rounded-lg">
    <h3 class="text-lg font-semibold mb-4">Status Timeline</h3>
    <div class="flex items-center space-x-4">
        <!-- Submission -->
        <div class="flex flex-col items-center">
            <div class="w-8 h-8 rounded-full bg-green-500 text-white flex items-center justify-center">
                <i class="fas fa-check"></i>
            </div>
            <p class="text-sm mt-2">Submitted</p>
            <p class="text-xs text-gray-500"><?php echo date('M d, Y', strtotime($pass['date_submitted'])); ?></p>
        </div>

        <!-- Line -->
        <div class="flex-1 h-0.5 bg-gray-300"></div>

        <!-- HoD Approval -->
        <div class="flex flex-col items-center">
            <div class="w-8 h-8 rounded-full <?php echo getStatusClass($pass['hod_status']); ?> text-white flex items-center justify-center">
                <?php echo getStatusIcon($pass['hod_status']); ?>
            </div>
            <p class="text-sm mt-2">HoD</p>
            <p class="text-xs text-gray-500"><?php echo getStatusDate($pass['hod_approval_date'], $pass['hod_rejection_date']); ?></p>
        </div>

        <!-- Line -->
        <div class="flex-1 h-0.5 bg-gray-300"></div>

        <!-- Estate Office -->
        <div class="flex flex-col items-center">
            <div class="w-8 h-8 rounded-full <?php echo getStatusClass($pass['estate_office_status']); ?> text-white flex items-center justify-center">
                <?php echo getStatusIcon($pass['estate_office_status']); ?>
            </div>
            <p class="text-sm mt-2">Estate</p>
        </div>
    </div>
</div>

<?php
function getStatusClass($status) {
    return match($status) {
        'Approved', 'Granted' => 'bg-green-500',
        'Rejected', 'Not Granted' => 'bg-red-500',
        default => 'bg-gray-300'
    };
}

function getStatusIcon($status) {
    return match($status) {
        'Approved', 'Granted' => '<i class="fas fa-check"></i>',
        'Rejected', 'Not Granted' => '<i class="fas fa-times"></i>',
        default => '<i class="fas fa-clock"></i>'
    };
}

function getStatusDate($approval_date, $rejection_date) {
    if ($approval_date) {
        return date('M d, Y', strtotime($approval_date));
    }
    if ($rejection_date) {
        return date('M d, Y', strtotime($rejection_date));
    }
    return 'Pending';
}
?>