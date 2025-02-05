<?php
session_start();
require_once '../backend/db.php';
require_once '../vendor/autoload.php';

// Check authentication
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Estate Officer') {
    exit('Unauthorized access');
}



class MYPDF extends TCPDF {
    public function Header() {
        // Logo
        $image_file = K_PATH_IMAGES.'dartu_logo.jpg'; // Update with your logo path
        $this->Image($image_file, 10, 10, 30, '', 'JPG', '', 'T', false, 300, '', false, false, 0, false, false, false);
        
        // Title
        $this->SetFont('helvetica', 'B', 20);
        $this->Cell(0, 15, 'DARTU Gate Pass System - Report', 0, false, 'C', 0, '', 0, false, 'M', 'M');
        
        // Subtitle with date
        $this->Ln(15);
        $this->SetFont('helvetica', '', 12);
        $this->Cell(0, 10, 'Generated on: ' . date('F d, Y'), 0, false, 'C', 0, '', 0, false, 'M', 'M');
    }

    public function Footer() {
        $this->SetY(-15);
        $this->SetFont('helvetica', 'I', 8);
        $this->Cell(0, 10, 'Page '.$this->getAliasNumPage().'/'.$this->getAliasNbPages(), 0, false, 'C', 0, '', 0, false, 'T', 'M');
    }
}

// Create new PDF document
$pdf = new MYPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

// Set document information
$pdf->SetCreator('DARTU Gate Pass System');
$pdf->SetAuthor('Estate Officer');
$pdf->SetTitle('Gate Pass Report');

// Set default header data
$pdf->SetHeaderData(PDF_HEADER_LOGO, PDF_HEADER_LOGO_WIDTH, PDF_HEADER_TITLE, PDF_HEADER_STRING);

// Set header and footer fonts
$pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
$pdf->setFooterFont(Array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));

// Set margins
$pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
$pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
$pdf->SetFooterMargin(PDF_MARGIN_FOOTER);

// Set auto page breaks
$pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);

// Add a page
$pdf->AddPage();

// Fetch report data
$query = "SELECT 
    g.pass_id,
    g.vehicle_registration,
    g.date_submitted,
    g.hod_status,
    g.estate_office_status,
    g.final_status,
    m.reason_name,
    d.department_name,
    CONCAT(u.fname, ' ', u.l_name) as applicant_name,
    GROUP_CONCAT(gd.item_description SEPARATOR ', ') as items
FROM gate_pass g
JOIN users u ON g.applicant_id = u.user_id
JOIN departments d ON u.department_id = d.department_id
JOIN movement_reason m ON g.reason_for_movement = m.reason_id
LEFT JOIN goods gd ON g.pass_id = gd.pass_id
GROUP BY g.pass_id
ORDER BY g.date_submitted DESC";

$stmt = $pdo->prepare($query);
$stmt->execute();
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate summary statistics
$totalPasses = count($results);
$approvedPasses = count(array_filter($results, function($pass) {
    return $pass['final_status'] === 'Approved';
}));
$rejectedPasses = count(array_filter($results, function($pass) {
    return $pass['final_status'] === 'Rejected';
}));
$pendingPasses = count(array_filter($results, function($pass) {
    return $pass['final_status'] === 'Pending';
}));

// Add Summary Section
$pdf->SetFont('helvetica', 'B', 16);
$pdf->Cell(0, 10, 'Summary Statistics', 0, 1, 'L');
$pdf->SetFont('helvetica', '', 12);

$summaryHTML = <<<EOD
<table border="0" cellpadding="5">
    <tr>
        <td width="200"><strong>Total Passes:</strong></td>
        <td>$totalPasses</td>
    </tr>
    <tr>
        <td><strong>Approved Passes:</strong></td>
        <td>$approvedPasses</td>
    </tr>
    <tr>
        <td><strong>Rejected Passes:</strong></td>
        <td>$rejectedPasses</td>
    </tr>
    <tr>
        <td><strong>Pending Passes:</strong></td>
        <td>$pendingPasses</td>
    </tr>
</table>
EOD;

$pdf->writeHTML($summaryHTML, true, false, true, false, '');

// Add Detailed Report Section
$pdf->AddPage();
$pdf->SetFont('helvetica', 'B', 16);
$pdf->Cell(0, 10, 'Detailed Report', 0, 1, 'L');

// Create the table header
$tableHeader = <<<EOD
<table border="1" cellpadding="5">
    <thead>
        <tr style="background-color: #f0f0f0;">
            <th><strong>Pass ID</strong></th>
            <th><strong>Applicant</strong></th>
            <th><strong>Department</strong></th>
            <th><strong>Items</strong></th>
            <th><strong>Date</strong></th>
            <th><strong>Status</strong></th>
        </tr>
    </thead>
    <tbody>
EOD;

// Add table rows
$tableRows = '';
foreach ($results as $row) {
    $passId = 'GP' . sprintf('%03d', $row['pass_id']);
    $applicant = htmlspecialchars($row['applicant_name']);
    $department = htmlspecialchars($row['department_name']);
    $items = htmlspecialchars($row['items']);
    $date = date('M d, Y', strtotime($row['date_submitted']));
    $status = htmlspecialchars($row['final_status']);
    
    $tableRows .= <<<EOD
        <tr>
            <td>$passId</td>
            <td>$applicant</td>
            <td>$department</td>
            <td>$items</td>
            <td>$date</td>
            <td>$status</td>
        </tr>
EOD;
}

$tableFooter = <<<EOD
    </tbody>
</table>
EOD;

// Write the complete table to PDF
$pdf->writeHTML($tableHeader . $tableRows . $tableFooter, true, false, true, false, '');

// Close and output PDF document
$pdf->Output('gate_pass_report.pdf', 'D');
?>