<?php
ob_start();
session_start();
error_reporting(0);
ini_set('display_errors', 0);

$page_title = "View Movement Order";
require_once '../config/config.php';
require_once '../backend/db.php';
require_once '../vendor/autoload.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit();
}

// Check if order ID is provided
if (!isset($_GET['id'])) {
    header('Location: movement_history.php');
    exit();
}

// Fetch movement order details
try {
    $stmt = $pdo->prepare("
        SELECT 
            mo.*,
            sl.location_name AS source_location,
            dl.location_name AS destination_location,
            u.name AS user_name,
            u.designation,
            d.department_name,
            u.phone
        FROM movement_orders mo
        JOIN locations sl ON mo.source_location_id = sl.location_id
        JOIN locations dl ON mo.destination_location_id = dl.location_id
        JOIN users u ON mo.user_id = u.user_id
        JOIN departments d ON u.department_id = d.department_id
        WHERE mo.order_id = ? AND mo.user_id = ?
    ");
    $stmt->execute([$_GET['id'], $_SESSION['user_id']]);
    $movement = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$movement) {
        $_SESSION['error_message'] = "Movement order not found.";
        header('Location: movement_history.php');
        exit();
    }

    // Fetch items
    $stmt = $pdo->prepare("
        SELECT * FROM movement_order_items 
        WHERE order_id = ?
        ORDER BY item_id
    ");
    $stmt->execute([$_GET['id']]);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log($e->getMessage());
    $_SESSION['error_message'] = "Failed to fetch movement order details.";
    header('Location: movement_history.php');
    exit();
}

// Generate PDF if requested
if (isset($_GET['generate_pdf']) && isset($_GET['id'])) {
    try {
        // Clean output buffer
        ob_clean();
        
        // Create new PDF document
        $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
        
        // Set document information
        $pdf->SetCreator(PDF_CREATOR);
        $pdf->SetAuthor('Your Organization Name');
        $pdf->SetTitle('Movement Order #' . $movement['order_id']);
        
        // Remove default header/footer
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        
        // Add a page
        $pdf->AddPage();
        
        // Set font
        $pdf->SetFont('helvetica', '', 12);
        
        // Set some header styling
$pdf->setHeaderData('', 0, '', '', array(0,0,0), array(255,255,255));
$pdf->setFooterData(array(0,0,0), array(255,255,255));

// Set header and footer fonts
$pdf->setHeaderFont(Array('helvetica', '', 10));
$pdf->setFooterFont(Array('helvetica', '', 8));

// Create HTML content for PDF
$html = '
<style>
    .header { text-align: center; color: #1a237e; }
    .logo-container { text-align: center; margin-bottom: 20px; }
    .document-title { font-size: 24px; font-weight: bold; color: #1a237e; margin-bottom: 5px; }
    .document-subtitle { font-size: 16px; color: #555; margin-bottom: 20px; }
    .section-title { background-color: #1a237e; color: white; padding: 5px 10px; margin-top: 15px; }
    .info-table { width: 100%; border-collapse: collapse; margin-top: 10px; }
    .info-table td { padding: 8px; border: 1px solid #ddd; }
    .info-table .label { background-color: #f5f5f5; font-weight: bold; width: 30%; }
    .items-table { width: 100%; border-collapse: collapse; margin-top: 15px; }
    .items-table th { background-color: #1a237e; color: white; padding: 10px; }
    .items-table td { padding: 8px; border: 1px solid #ddd; }
    .items-table tr:nth-child(even) { background-color: #f9f9f9; }
    .remarks-section { margin-top: 20px; padding: 10px; background-color: #f5f5f5; }
    .footer { text-align: center; font-size: 12px; color: #666; margin-top: 30px; }
</style>

<div class="logo-container">
    <img src="logo.png" width="150" height="150"> 
</div>

<div class="header">

    <div class="document-title">DAR ES SALAAM TUMAINI UNIVERSITY</div>
    <div class="document-title">MOVEMENT ORDER</div>
    <div class="document-subtitle">Reference Number: MO-' . sprintf("%06d", $movement['order_id']) . '</div>
    <div class="document-subtitle">Date Issued: ' . date('F j, Y', strtotime($movement['created_at'])) . '</div>
</div>

<div class="section-title">APPLICANT INFORMATION</div>
<table class="info-table">
    <tr>
        <td class="label">Applicant Name</td>
        <td>' . strtoupper($movement['user_name']) . '</td>
    </tr>
    <tr>
        <td class="label">Department</td>
        <td>' . $movement['department_name'] . '</td>
    </tr>
    <tr>
        <td class="label">Designation</td>
        <td>' . $movement['designation'] . '</td>
    </tr>
    <tr>
        <td class="label">Contact Number</td>
        <td>' . $movement['phone'] . '</td>
    </tr>
</table>

<div class="section-title">MOVEMENT DETAILS</div>
<table class="info-table">
    <tr>
        <td class="label">Movement Date</td>
        <td>' . date('F j, Y', strtotime($movement['movement_date'])) . '</td>
    </tr>
    <tr>
        <td class="label">Source Location</td>
        <td>' . $movement['source_location'] . '</td>
    </tr>
    <tr>
        <td class="label">Destination</td>
        <td>' . $movement['destination_location'] . '</td>
    </tr>
    <tr>
        <td class="label">Priority Level</td>
        <td><span style="color: ' . 
            (strtolower($movement['priority_level']) === 'urgent' ? '#dc2626' : 
            (strtolower($movement['priority_level']) === 'high' ? '#d97706' : '#059669')) . 
            ';">' . strtoupper($movement['priority_level']) . '</span></td>
    </tr>
    <tr>
        <td class="label">Status</td>
        <td><span style="color: ' . 
            ($movement['status'] === 'Approved' ? '#059669' : 
            ($movement['status'] === 'Rejected' ? '#dc2626' : '#d97706')) . 
            ';">' . strtoupper($movement['status']) . '</span></td>
    </tr>
</table>

<div class="section-title">ITEMS TO BE MOVED</div>
<table class="items-table">
    <tr>
        <th width="10%">No.</th>
        <th width="45%">ITEM</th>
        <th width="25%">Quantity</th>
        <th width="20%">Remarks</th>
    </tr>';

foreach ($items as $index => $item) {
    $html .= '
    <tr>
        <td style="text-align: center;">' . ($index + 1) . '</td>
        <td>' . htmlspecialchars($item['item_description']) . '</td>
        <td style="text-align: center;">' . htmlspecialchars($item['quantity']) . '</td>
        <td>' . htmlspecialchars($item['remarks'] ?? '-') . '</td>
    </tr>';
}

$html .= '</table>';

if (!empty($movement['remarks'])) {
    $html .= '
    <div class="section-title">REMARKS</div>
    <div class="remarks-section">
        ' . nl2br(htmlspecialchars($movement['remarks'])) . '
    </div>';
}

$html .= '
<div class="footer">
    <p>This document is computer-generated and requires no signature.</p>
    <p>Generated on ' . date('F j, Y \a\t h:i A') . '</p>
    <p> ' . date('Y') . ' All right Reserved, DarTU</p>
</div>';
        
        if (!empty($movement['remarks'])) {
            $html .= '
            <br><br>
            <h3>Remarks:</h3>
            <p>' . nl2br(htmlspecialchars($movement['remarks'])) . '</p>';
        }
        
        // Output HTML content
        $pdf->writeHTML($html, true, false, true, false, '');
        
        // Close and output PDF document
        $pdf->Output('movement_order_' . $movement['order_id'] . '.pdf', 'D');
        exit();
    } catch (Exception $e) {
        error_log($e->getMessage());
        $_SESSION['error_message'] = "Failed to generate PDF. Please try again.";
    }
}

include 'includes/header.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .fade-in {
            animation: fadeIn 0.6s ease-in-out;
        }
        
        .slide-in {
            animation: slideIn 0.5s ease-out;
        }
        
        .slide-in-delay {
            opacity: 0;
            animation: slideIn 0.5s ease-out forwards;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        @keyframes slideIn {
            from { 
                transform: translateY(20px); 
                opacity: 0; 
            }
            to { 
                transform: translateY(0); 
                opacity: 1; 
            }
        }

        .status-badge {
            padding: 0.5rem 1rem;
            border-radius: 9999px;
            font-size: 0.875rem;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .priority-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.875rem;
            font-weight: 500;
        }

        .hover-effect {
            transition: all 0.3s ease;
        }

        .hover-effect:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        }

        .detail-card {
            background: white;
            border-radius: 0.5rem;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            border: 1px solid #e5e7eb;
        }

        .detail-card:hover {
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
        }

        .grid-cols-2 {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1.5rem;
        }

        @media (max-width: 768px) {
            .grid-cols-2 {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body class="bg-gray-50">
    <div class="flex min-h-screen">
        <!-- Sidebar -->
        <div class="fixed inset-y-0 left-0 z-50">
            <?php include 'includes/sidebar.php'; ?>
        </div>

        <!-- Main Content -->
        <div class="flex-1 ml-64">
            <?php include 'includes/topbar.php'; ?>
            
            <div class="p-8">
                <div class="max-w-7xl mx-auto">
                    <!-- Header Section -->
                    <div class="bg-white p-6 rounded-lg shadow-lg mb-6 fade-in">
                        <div class="flex justify-between items-center">
                            <div>
                                <h1 class="text-2xl font-bold text-gray-900">
                                    Movement Order #<?php echo htmlspecialchars($movement['order_id']); ?>
                                </h1>
                                <p class="text-gray-600 mt-1">
                                    Created on <?php echo date('F j, Y', strtotime($movement['created_at'])); ?>
                                </p>
                            </div>
                            <div class="flex space-x-4">
                                <?php if ($movement['status'] === 'Approved'): ?>
                                    <a href="?id=<?php echo $movement['order_id']; ?>&generate_pdf=1" 
                                       class="flex items-center px-4 py-2 bg-green-500 text-white rounded-lg hover:bg-green-600 transition-colors hover-effect">
                                        <i class="fas fa-file-pdf mr-2"></i> Download PDF
                                    </a>
                                <?php endif; ?>
                                <a href="movement_history.php" 
                                   class="flex items-center px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors hover-effect">
                                    <i class="fas fa-arrow-left mr-2"></i> Back
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Status and Priority Section -->
                    <div class="grid grid-cols-2 gap-6 mb-6">
                        <div class="bg-white p-6 rounded-lg shadow-lg slide-in" style="animation-delay: 0.1s">
                            <h2 class="text-lg font-semibold mb-4">Status</h2>
                            <span class="status-badge <?php 
                                echo $movement['status'] === 'Approved' ? 'bg-green-100 text-green-800' : 
                                    ($movement['status'] === 'Rejected' ? 'bg-red-100 text-red-800' : 'bg-yellow-100 text-yellow-800'); 
                            ?>">
                                <i class="fas <?php 
                                    echo $movement['status'] === 'Approved' ? 'fa-check-circle' : 
                                        ($movement['status'] === 'Rejected' ? 'fa-times-circle' : 'fa-clock'); 
                                ?>"></i>
                                <?php echo htmlspecialchars($movement['status']); ?>
                            </span>
                        </div>
                        <div class="bg-white p-6 rounded-lg shadow-lg slide-in" style="animation-delay: 0.2s">
                            <h2 class="text-lg font-semibold mb-4">Priority Level</h2>
                            <span class="priority-badge <?php 
                                echo strtolower($movement['priority_level']) === 'urgent' ? 'bg-red-100 text-red-800' : 
                                    (strtolower($movement['priority_level']) === 'high' ? 'bg-yellow-100 text-yellow-800' : 'bg-green-100 text-green-800'); 
                            ?>">
                                <?php echo htmlspecialchars($movement['priority_level']); ?>
                            </span>
                        </div>
                    </div>

                    <!-- Details Section -->
                    <div class="grid grid-cols-2 gap-6 mb-6">
                        <!-- Applicant Details -->
                        <div class="detail-card slide-in" style="animation-delay: 0.3s">
                            <h2 class="text-lg font-semibold mb-4">
                                <i class="fas fa-user-circle mr-2"></i>
                                Applicant Details
                            </h2>
                            <div class="space-y-3">
                                <div class="flex justify-between">
                                    <span class="text-gray-600 ">Name:</span>
                                    <span class="font-medium upcase"><?php echo htmlspecialchars($movement['user_name']); ?></span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Department:</span>
                                    <span class="font-medium upcase"><?php echo htmlspecialchars($movement['department_name']); ?></span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Designation:</span>
                                    <span class="font-medium upcase"><?php echo htmlspecialchars($movement['designation']); ?></span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Phone:</span>
                                    <span class="font-medium upcase"><?php echo htmlspecialchars($movement['phone']); ?></span>
                                </div>
                            </div>
                        </div>

                        <!-- Movement Details -->
                        <div class="detail-card slide-in" style="animation-delay: 0.4s">
                            <h2 class="text-lg font-semibold mb-4">
                                <i class="fas fa-exchange-alt mr-2"></i>
                                Movement Details
                            </h2>
                            <div class="space-y-3">
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Date:</span>
                                    <span class="font-medium">
                                        <?php echo date('F j, Y', strtotime($movement['movement_date'])); ?>
                                    </span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Source:</span>
                                    <span class="font-medium">
                                        <?php echo htmlspecialchars($movement['source_location']); ?>
                                    </span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Destination:</span>
                                    <span class="font-medium">
                                        <?php echo htmlspecialchars($movement['destination_location']); ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Items Section -->
                    <div class="bg-white rounded-lg shadow-lg overflow-hidden slide-in" style="animation-delay: 0.5s">
                        <div class="p-6">
                            <h2 class="text-lg font-semibold mb-4">
                                <i class="fas fa-boxes mr-2"></i>
                                Items to be Moved
                            </h2>
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">No.</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Description</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Quantity</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Remarks</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        <?php foreach ($items as $index => $item): ?>
                                            <tr class="hover:bg-gray-50">
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                    <?php echo $index + 1; ?>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                                    <?php echo htmlspecialchars($item['item_description']); ?>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                    <?php echo htmlspecialchars($item['quantity']); ?>
                                                </td>
                                                <td class="px-6 py-4 text-sm text-gray-500">
                                                    <?php echo htmlspecialchars($item['remarks'] ?? '-'); ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Remarks Section -->
                    <?php if (!empty($movement['remarks'])): ?>
                        <div class="bg-white p-6 rounded-lg shadow-lg mt-6 slide-in" style="animation-delay: 0.6s">
                            <h2 class="text-lg font-semibold mb-4">
                                <i class="fas fa-comments mr-2"></i>
                                Remarks
                            </h2>
                            <p class="text-gray-700">
                                <?php echo nl2br(htmlspecialchars($movement['remarks'])); ?>
                            </p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Add animation delays to elements
            const elements = document.querySelectorAll('.slide-in-delay');
            elements.forEach((element, index) => {
                element.style.animationDelay = `${(index + 1) * 0.1}s`;
            });
        });
    </script>
</body>
</html>