<?php
session_start();
require_once '../backend/db.php';

// Check authentication
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Estate Officer') {
    exit('Unauthorized access');
}

$reportType = $_POST['report_type'] ?? 'summary';
$startDate = $_POST['start_date'] ?? '';
$endDate = $_POST['end_date'] ?? '';
$department = $_POST['department'] ?? '';
$status = $_POST['status'] ?? 'all';

// Base query for all report types
$baseQuery = "SELECT 
    g.pass_id,
    g.vehicle_registration,
    g.date_submitted,
    g.hod_status,
    g.estate_office_status,
    COALESCE(g.final_status, 'Pending') as final_status,
    m.reason_name,
    d.department_name,
    CONCAT(u.fname, ' ', u.l_name) as applicant_name,
    GROUP_CONCAT(DISTINCT gd.item_description SEPARATOR ', ') as items
FROM gate_pass g
JOIN users u ON g.applicant_id = u.user_id
JOIN departments d ON u.department_id = d.department_id
JOIN movement_reason m ON g.reason_for_movement = m.reason_id
LEFT JOIN goods gd ON g.pass_id = gd.pass_id";

// Add filters
$whereConditions = [];
$params = [];

if ($startDate && $endDate) {
    $whereConditions[] = "g.date_submitted BETWEEN :start_date AND :end_date";
    $params[':start_date'] = $startDate . ' 00:00:00';
    $params[':end_date'] = $endDate . ' 23:59:59';
}

if ($department) {
    $whereConditions[] = "d.department_id = :department_id";
    $params[':department_id'] = $department;
}

if ($status !== 'all') {
    $whereConditions[] = "COALESCE(g.final_status, 'Pending') = :status";
    $params[':status'] = $status;
}

// Add WHERE clause if conditions exist
if (!empty($whereConditions)) {
    $baseQuery .= " WHERE " . implode(" AND ", $whereConditions);
}

$baseQuery .= " GROUP BY g.pass_id";

// Prepare specific queries based on report type
switch($reportType) {
    case 'summary':
        $query = $baseQuery . " ORDER BY g.date_submitted DESC";
        break;

    case 'department':
        $query = "SELECT 
            d.department_name,
            COUNT(DISTINCT g.pass_id) as total_passes,
            SUM(CASE WHEN COALESCE(g.final_status, 'Pending') = 'Approved' THEN 1 ELSE 0 END) as approved,
            SUM(CASE WHEN COALESCE(g.final_status, 'Pending') = 'Rejected' THEN 1 ELSE 0 END) as rejected,
            SUM(CASE WHEN COALESCE(g.final_status, 'Pending') = 'Pending' THEN 1 ELSE 0 END) as pending
        FROM departments d
        LEFT JOIN users u ON d.department_id = u.department_id
        LEFT JOIN gate_pass g ON u.user_id = g.applicant_id";
        
        if (!empty($whereConditions)) {
            $query .= " WHERE " . implode(" AND ", $whereConditions);
        }
        
        $query .= " GROUP BY d.department_id, d.department_name";
        break;

    case 'detailed':
        $query = $baseQuery . " ORDER BY g.date_submitted DESC";
        break;
}

// Execute query
try {
    $stmt = $pdo->prepare($query);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->execute();
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Initialize counters
    $totalPasses = count($results);
    $approvedPasses = 0;
    $rejectedPasses = 0;
    $pendingPasses = 0;

    // Calculate summary statistics only for summary and detailed reports
    if ($reportType === 'summary' || $reportType === 'detailed') {
        foreach ($results as $pass) {
            $status = $pass['final_status'] ?? 'Pending';
            switch ($status) {
                case 'Approved':
                    $approvedPasses++;
                    break;
                case 'Rejected':
                    $rejectedPasses++;
                    break;
                default:
                    $pendingPasses++;
                    break;
            }
        }
    }

    // Include appropriate template
    include "report_templates/{$reportType}_template.php";

} catch (PDOException $e) {
    error_log($e->getMessage());
    echo "<div class='bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative'>
            An error occurred while generating the report. Please try again.
          </div>";
}
?>