<?php
function getDashboardStats($pdo) {
    try {
        // Pending Finals Count
        $stmt = $pdo->prepare("
            SELECT COUNT(*) FROM gate_pass 
            WHERE hod_status = 'Approved' 
            AND estate_office_status IS NULL
        ");
        $stmt->execute();
        $pending_finals = $stmt->fetchColumn();

        // Today's Processed
        $stmt = $pdo->prepare("
            SELECT COUNT(*) FROM gate_pass 
            WHERE DATE(date_submitted) = CURDATE() 
            AND estate_office_status IS NOT NULL
        ");
        $stmt->execute();
        $today_processed = $stmt->fetchColumn();

        // Active Departments
        $stmt = $pdo->prepare("
            SELECT COUNT(DISTINCT department_id) 
            FROM users WHERE status = 'Active'
        ");
        $stmt->execute();
        $active_departments = $stmt->fetchColumn();

        // Monthly Total
        $stmt = $pdo->prepare("
            SELECT COUNT(*) FROM gate_pass 
            WHERE MONTH(date_submitted) = MONTH(CURRENT_DATE())
            AND YEAR(date_submitted) = YEAR(CURRENT_DATE())
        ");
        $stmt->execute();
        $monthly_total = $stmt->fetchColumn();

        return [
            'pending_finals' => $pending_finals,
            'today_processed' => $today_processed,
            'active_departments' => $active_departments,
            'monthly_total' => $monthly_total
        ];
    } catch (PDOException $e) {
        error_log("Error getting dashboard stats: " . $e->getMessage());
        return false;
    }
}

function getPendingApprovals($pdo) {
    try {
        $stmt = $pdo->prepare("
            SELECT 
                gp.pass_id,
                d.department_name,
                CONCAT(u.fname, ' ', u.m_name, ' ', u.l_name) as staff_name,
                gp.hod_status,
                gp.date_submitted,
                gp.vehicle_registration
            FROM gate_pass gp
            JOIN users u ON gp.applicant_id = u.user_id
            JOIN departments d ON u.department_id = d.department_id
            WHERE gp.hod_status = 'Approved' 
            AND gp.estate_office_status IS NULL
            ORDER BY gp.date_submitted DESC
        ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error getting pending approvals: " . $e->getMessage());
        return false;
    }
}