<?php
session_start();
require_once '../../backend/db.php';

// Check authentication
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Estate Officer') {
    header('Location: ../../login.php');
    exit();
}

try {
    // Get date range from request or default to current month
    $start_date = $_GET['start_date'] ?? date('Y-m-01');
    $end_date = $_GET['end_date'] ?? date('Y-m-t');

    // Prepare the report query
    $report_query = "SELECT 
        g.pass_id,
        g.date_submitted,
        d.department_name,
        CONCAT(u.fname, ' ', u.l_name) as applicant_name,
        m.reason_description,
        g.vehicle_registration,
        g.hod_status,
        g.estate_office_status,
        g.final_status
    FROM gate_pass g
    JOIN users u ON g.applicant_id = u.user_id
    JOIN departments d ON u.department_id = d.department_id
    JOIN movement_reason m ON g.reason_for_movement = m.reason_id
    WHERE g.date_submitted BETWEEN :start_date AND :end_date
    ORDER BY g.date_submitted DESC";

    $stmt = $pdo->prepare($report_query);
    $stmt->execute([
        'start_date' => $start_date,
        'end_date' => $end_date
    ]);
    $passes = $stmt->fetchAll();

    // Set headers for CSV download
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="gate_passes_report_' . date('Y-m-d') . '.csv"');

    // Create output stream
    $output = fopen('php://output', 'w');

    // Add CSV headers
    fputcsv($output, [
        'Pass ID',
        'Date Submitted',
        'Department',
        'Applicant Name',
        'Movement Reason',
        'Vehicle Registration',
        'HoD Status',
        'Estate Office Status',
        'Final Status'
    ]);

    // Add data rows
    foreach ($passes as $pass) {
        fputcsv($output, [
            'GP' . sprintf('%03d', $pass['pass_id']),
            date('Y-m-d H:i', strtotime($pass['date_submitted'])),
            $pass['department_name'],
            $pass['applicant_name'],
            $pass['reason_description'],
            $pass['vehicle_registration'] ?: 'N/A',
            $pass['hod_status'],
            $pass['estate_office_status'],
            $pass['final_status']
        ]);
    }

    fclose($output);

} catch(PDOException $e) {
    error_log($e->getMessage());
    header('Location: ../index.php?error=report_generation_failed');
    exit();
}
?>