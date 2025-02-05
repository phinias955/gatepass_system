<?php
session_start();
require_once '../config/config.php';
require_once '../backend/db.php';
require_once __DIR__ . '/../vendor/autoload.php';

use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    $_SESSION['error'] = "Please login to continue";
    header('Location: ../login.php');
    exit;
}

// Initialize variables
$pass = null;
$items = [];
$error = null;

// Validate gate pass ID
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if (!$id) {
    $_SESSION['error'] = "Invalid gate pass ID";
    header('Location: index.php');
    exit;
}

try {
    // Fetch gate pass details
    $stmt = $pdo->prepare("
    SELECT 
        GP.*,
        u.fname,
        u.m_name,
        u.l_name,
        u.phone,
        u.designation,
        d.department_name,
        mr.reason_name,
        CONCAT(u.fname, ' ', COALESCE(u.m_name, ''), ' ', u.l_name) as full_name
    FROM gate_pass GP
    JOIN users u ON GP.applicant_id = u.user_id
    JOIN departments d ON u.department_id = d.department_id
    LEFT JOIN movement_reason mr ON GP.reason_for_movement = mr.reason_id
    WHERE GP.pass_id = ?
");
    $stmt->execute([$id]);
    $pass = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$pass) {
        throw new Exception("Gate pass not found");
        
    }

    // Fetch items
    $stmt = $pdo->prepare("
        SELECT * FROM goods 
        WHERE pass_id = ? 
        ORDER BY goods_id ASC
    ");
    $stmt->execute([$id]);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    $error = $e->getMessage();
    error_log("Error: " . $e->getMessage());
}

// PDF Generation Logic
if (isset($_GET['generate_pdf']) && $pass && $pass['hod_status'] === 'Approved' && $pass['estate_office_status'] === 'Granted') {
    try {
        // Create new PDF document
        $pdf = new \TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

        // Set document information
        $pdf->SetCreator('Gate Pass System');
        $pdf->SetAuthor('Your Organization');
        $pdf->SetTitle('Gate Pass #' . str_pad($pass['pass_id'], 4, '0', STR_PAD_LEFT));

        // Remove default header/footer
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);

        // Set margins
        $pdf->SetMargins(15, 15, 15);

        // Add a page
        $pdf->AddPage();

        // Set font
        $pdf->SetFont('helvetica', '', 10);

        // QR Code generation
        $qrOptions = new QROptions([
            'outputType' => QRCode::OUTPUT_IMAGE_PNG,
            'eccLevel' => QRCode::ECC_L,
            'scale' => 10,
            'imageBase64' => true,
        ]);

        $qrCode = new QRCode($qrOptions);
        $qrData = "GATE PASS #" . str_pad($pass['pass_id'], 4, '0', STR_PAD_LEFT);
        $qrImage = $qrCode->render($qrData);

        // Create HTML content for PDF
        $html = '
        <style>
            table { width: 100%; border-collapse: collapse; margin-bottom: 15px; }
            th, td { border: 1px solid #4a5568; padding: 12px; }
            th { background-color: #2d3748; color: white; font-weight: bold; }
            .header { font-size: 24px; font-weight: bold; text-align: center; margin-bottom: 20px; color: #2d3748; }
            .section { 
                font-size: 16px; 
                font-weight: bold; 
                margin: 20px 0 15px 0; 
                background-color: #edf2f7; 
                padding: 10px;
                color: #2d3748;
                border-left: 5px solid #4299e1;
            }
            .logo-container {
                text-align: center;
                margin-bottom: 20px;
                border-bottom: 2px solid #4a5568;
                padding-bottom: 20px;
            }
            .pass-number {
                font-size: 14px;
                color: #4a5568;
                text-align: center;
                margin-bottom: 10px;
            }
            .date-time {
                font-size: 12px;
                color: #718096;
                text-align: right;
                margin-bottom: 20px; 
            }
            .info-box {
                background-color: #f7fafc;
                border: 1px solid #e2e8f0;
                border-radius: 5px;
                padding: 15px;
                margin-bottom: 20px;
            }
            .signature-section {
                margin-top: 60px;
                border-top: 1px dashed #4a5568;
                padding-top: 20px;
            }
            .signature-image {
                width: 100px;
                height: auto;
                margin-bottom: 5px;
            }
            .watermark {
                position: absolute;
                top: 50%;
                left: 50%;
                transform: translate(-50%, -50%) rotate(-45deg);
                font-size: 10px;
                color: green;
            }
            .signature-table {
                width: 100%;
                border: none;
            }
            .signature-table td {
                width: 33.33%;
                padding: 8px;
                text-align: center;
                border: none;
            }
            .upper-text{
                text-transform: uppercase;
            }
        </style>
        <div class="logo-container">
            <img src="logo.png" width="60" height="60"> 
            <div class="header">DAR ES SALAAM TUMAINI UNIVERSITY</div>
            <div class="pass-number">Reference No: DARTU/GP/' . str_pad($pass['pass_id'], 4, '0', STR_PAD_LEFT) . '</div>
        </div>
        <div class="pass-number">GOODS OUTWARD GATE PASS</div>




        <div class="date-time">
            Generated on: ' . date('F j, Y h:i A') . '<br>
            Valid until: ' . date('F j, Y', strtotime('+1 day')) . '
        </div>
           <div class="watermark">APPROVED</div> 
        <div class="info-box">
            <div class="section">APPLICANT DETAILS</div>
            <table>
                <tr>
                    <td width="25%" style="background-color: #f7fafc;  text-transform: uppercase;"><strong>Full Name:</strong></td>
                    <td width="25%" style=" text-transform: uppercase !important;">' . htmlspecialchars($pass['full_name']) . '</td>
                    <td width="25%" style="background-color: #f7fafc; text-transform: uppercase;"><strong>Department:</strong></td>
                    <td width="25%" style=" text-transform: uppercase     b`;">' . htmlspecialchars($pass['department_name']) . '</td>
                    
                </tr>
                <tr>
                    <td style="background-color: #f7fafc; text-transform: uppercase;"><strong>Phone:</strong></td>
                    <td>' . htmlspecialchars($pass['phone']) . '</td>
                    <td style="background-color: #f7fafc; text-transform: uppercase;"><strong>Designation:</strong></td>
                    <td>' . htmlspecialchars($pass['designation']) . '</td>
                </tr>
                <tr>
                    <td style="background-color: #f7fafc; text-transform: uppercase;"><strong>Reason for Movement:</strong></td>
                    <td colspan="3">' . htmlspecialchars($pass['reason_name']) . '</td>
                </tr>
                <tr>
                    <td style="background-color: #f7fafc; text-transform: uppercase;"><strong>Vehicle Reg. No:</strong></td>
                    <td colspan="3">' . htmlspecialchars($pass['vehicle_registration'] ?? 'N/A') . '</td>
                </tr>
            </table>
        </div>'; 

        // Add items section if items exist
        if (!empty($items)) {
            $html .= '
            <div class="info-box">
                <div class="section">ITEMS DETAILS</div>
                <table>
                    <tr>
                        <th width="40%">Item Description</th>
                        <th width="30%">Quantity</th>
                        <th width="30%">Remarks</th>
                    </tr>';

            foreach ($items as $item) {
                $html .= '<tr>
                    <td>' . htmlspecialchars($item['item_description']) . '</td>
                    <td style="text-align: center;">' . htmlspecialchars($item['quantity']) . '</td>
                    <td>' . htmlspecialchars($item['remarks'] ?? '-') . '</td>
                </tr>';
            }

            $html .= '</table>
            </div>';
        }

        // Approval status section
        $html .= '
        <div class="info-box">
            <div class="section">APPROVAL STATUS</div>
            <table>
                <tr>
                    <td width="25%" style="background-color: #f7fafc;"><strong>HOD Status:</strong></td>
                    <td width="25%">' . htmlspecialchars($pass['hod_status']) . '</td>
                    <td width="25%" style="background-color: #f7fafc;"><strong>Estate Office Status:</strong></td>
                    <td width="25%">' . htmlspecialchars($pass['estate_office_status']) . '</td>
                </tr>
            </table>
        </div>
    
        <div class="signature-section">
            <table class="signature-table">
                <tr>
                    <td>
                        <div class="signature-line"></div>
                        Applicant Signature<br>
                        <span style="font-size: 12px; color: #718096; text-transform: uppercase;">
                            ' . htmlspecialchars($pass['full_name']) . '
                        </span>
                    </td>
                    <td>
                        <div class="signature-line"></div>
                        Security Officer Signature<br>
                        <span style="font-size: 12px; color: #718096;">
                            officer on duty
                        </span>
                    </td>
                    <td>
                        <div class="signature-line"></div>
                       Estate Office Signature<br>
                        <span style="font-size: 12px; color: #718096;">
                            Estate Officer
                        </span>
                    </td>
                </tr>
            </table>
        </div>

        <div class="qr-code" style="text-align: center; margin-top: 20px;">
            <img src="' . $qrImage . '" width="100" height="100">
        </div>

        <div style="text-align: center; margin-top: 20px; font-size: 12px; color: #718096;">
            This document is electronically generated and is valid without a stamp.<br>
            Verify this gate pass at: https://gtps.dartu.ac.tz/verify.php' . $pass['pass_id'] . '
        </div>';
        // Output HTML content
        $pdf->writeHTML($html, true, false, true, false, '');

        // Close and output PDF document
        $pdf->Output('gate_pass_' . sprintf("%04d", $pass['pass_id']) . '.pdf', 'D');
        exit();

    } catch (Exception $e) {
        $error = "Failed to generate PDF: " . $e->getMessage();
        error_log($error);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Gate Pass</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        .fade-in {
            animation: fadeIn 0.6s ease-in;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .hover-effect {
            transition: all 0.3s ease;
        }

        .hover-effect:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }

        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(255, 255, 255, 0.9);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 1000;
        }

        .spinner {
            width: 40px;
            height: 40px;
            border: 4px solid #f3f3f3;
            border-top: 4px solid #3498db;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .status-pill {
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.875rem;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 0.375rem;
        }
    </style>
</head>
<body class="bg-gray-100">

<!-- Loading Overlay -->
<div id="loadingOverlay" class="loading-overlay">
    <div class="spinner"></div>
</div>

<?php if ($error): ?>
    <script>
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: '<?php echo htmlspecialchars($error); ?>',
            confirmButtonColor: '#3085d6'
        });
    </script>
<?php endif; ?>

<div class="flex min-h-screen">
    <!-- Sidebar -->
    <?php include 'includes/sidebar.php'; ?>

    <!-- Main Content -->
    <div class="flex-1 ml-64">
        <?php include 'includes/topbar.php'; ?>

        <div class="p-6 fade-in">
            <div class="max-w-5xl mx-auto">
                <!-- Header Card -->
                <div class="bg-white rounded-lg shadow-lg mb-6 overflow-hidden">
                    <div class="bg-gradient-to-r from-blue-600 to-blue-700 px-6 py-4">
                        <div class="flex justify-between items-center">
                            <div class="text-white">
                                <h1 class="text-2xl font-bold">Gate Pass Details</h1>
                                <p class="text-blue-100">Reference: DARTU/GP/<?php echo str_pad($pass['pass_id'], 4, '0', STR_PAD_LEFT); ?></p>
                            </div>
                            <div class="text-right text-blue-100">
                                <p>Created: <?php echo date('F j, Y', strtotime($pass['date_submitted'])); ?></p>
                                <p>Time: <?php echo date('h:i A', strtotime($pass['date_submitted'])); ?></p>
                            </div>
                        </div>
                    </div>

                    <!-- Status Summary -->
                    <div class="px-6 py-4 bg-gray-50 border-b">
                        <div class="flex justify-between items-center">
                            <div class="flex space-x-4">
                                <!-- HOD Status -->
                                <div>
                                    <span class="text-sm text-gray-600">HOD Status:</span>
                                    <span class="status-pill ml-2 <?php 
                                        echo match($pass['hod_status']) {
                                            'Approved' => 'bg-green-100 text-green-800',
                                            'Rejected' => 'bg-red-100 text-red-800',
                                            default => 'bg-yellow-100 text-yellow-800'
                                        };
                                    ?>">
                                        <i class="fas fa-circle text-xs"></i>
                                        <?php echo htmlspecialchars($pass['hod_status']); ?>
                                    </span>
                                </div>
                                <!-- Estate Office Status -->
                                <div>
                                    <span class="text-sm text-gray-600">Estate Office:</span>
                                    <span class="status-pill ml-2 <?php 
                                        echo match($pass['estate_office_status']) {
                                            'Granted' => 'bg-green-100 text-green-800',
                                            'Not Granted' => 'bg-red-100 text-red-800',
                                            default => 'bg-yellow-100 text-yellow-800'
                                        };
                                    ?>">
                                        <i class="fas fa-circle text-xs"></i>
                                        <?php echo htmlspecialchars($pass['estate_office_status'] ?? 'Pending'); ?>
                                    </span>
                                </div>
                            </div>
                            
                            <!-- Download Button -->
                            <?php if ($pass['hod_status'] === 'Approved' && $pass['estate_office_status'] === 'Granted'): ?>
                                <button onclick="downloadPDF(<?php echo $pass['pass_id']; ?>)" 
                                        class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 hover-effect">
                                    <i class="fas fa-file-pdf mr-2"></i>
                                    Download Gate Pass
                                </button>
                            <?php else: ?>
                                <button disabled 
                                        class="inline-flex items-center px-4 py-2 bg-gray-300 text-gray-500 rounded-lg cursor-not-allowed"
                                        title="Gate pass must be approved by HOD and granted by Estate Office">
                                    <i class="fas fa-file-pdf mr-2"></i>
                                    Download Gate Pass
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Main Content -->
                    <div class="p-6">
                        <!-- Applicant Details -->
                        <div class="mb-8">
                            <h2 class="text-lg font-semibold mb-4 text-gray-800 flex items-center">
                                <i class="fas fa-user-circle mr-2 text-blue-600"></i>
                                Applicant Information
                            </h2>
                            <div class="grid grid-cols-2 gap-6">
                                <div class="bg-gray-50 p-4 rounded-lg">
                                    <p class="text-sm text-gray-600">Full Name</p>
                                    <p class="font-medium"><?php echo htmlspecialchars($pass['full_name']); ?></p>
                                </div>
                                <div class="bg-gray-50 p-4 rounded-lg">
                                    <p class="text-sm text-gray-600">Department</p>
                                    <p class="font-medium"><?php echo htmlspecialchars($pass['department_name']); ?></p>
                                </div>
                                <div class="bg-gray-50 p-4 rounded-lg">
                                    <p class="text-sm text-gray-600">Phone Number</p>
                                    <p class="font-medium"><?php echo htmlspecialchars($pass['phone']); ?></p>
                                </div>
                                <div class="bg-gray-50 p-4 rounded-lg">
                                    <p class="text-sm text-gray-600">Designation</p>
                                    <p class="font-medium"><?php echo htmlspecialchars($pass['designation']); ?></p>
                                </div>
                            </div>
                        </div>

                        <!-- Items List -->
                        <div class="mb-8">
                            <h2 class="text-lg font-semibold mb-4 text-gray-800 flex items-center">
                                <i class="fas fa-boxes mr-2 text-blue-600"></i>
                                Items Details
                            </h2>
                            <div class="bg-white rounded-lg overflow-hidden border">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Item Description</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Quantity</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Remarks</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        <?php foreach ($items as $item): ?>
                                        <tr class="hover:bg-gray-50">
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                <?php echo htmlspecialchars($item['item_description']); ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                <?php echo htmlspecialchars($item['quantity']); ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                <?php echo htmlspecialchars($item['remarks'] ?? '-'); ?>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Back Button -->
                <div class="flex justify-start">
                    <a href="history.php" class="inline-flex items-center px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 hover-effect">
                        <i class="fas fa-arrow-left mr-2"></i>
                        Back to History
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function downloadPDF(passId) {
    // Show loading overlay
    document.getElementById('loadingOverlay').style.display = 'flex';
    
    // Create form and submit
    const form = document.createElement('form');
    form.method = 'GET';
    form.action = window.location.href;
    
    const idInput = document.createElement('input');
    idInput.type = 'hidden';
    idInput.name = 'id';
    idInput.value = passId;
    
    const pdfInput = document.createElement('input');
    pdfInput.type = 'hidden';
    pdfInput.name = 'generate_pdf';
    pdfInput.value = '1';
    
    form.appendChild(idInput);
    form.appendChild(pdfInput);
    document.body.appendChild(form);
    
    form.submit();
    
    // Hide loading overlay after a short delay
    setTimeout(() => {
        document.getElementById('loadingOverlay').style.display = 'none';
    }, 2000);
}
</script>

</body>
</html>