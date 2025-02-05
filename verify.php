<?php
session_start();
require_once 'config/config.php';
require_once 'backend/db.php';

// Initialize variables
$pass = null;
$order = null;
$items = [];
$error = null;
$success = null;
$verified = false;

// Process verification form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
    $type = isset($_POST['type']) ? $_POST['type'] : 'gatepass';
    $phone = isset($_POST['phone']) ? trim($_POST['phone']) : '';
    $lastname = isset($_POST['lastname']) ? trim($_POST['lastname']) : '';

    try {
        if ($type === 'gatepass') {
            // Verify gate pass with user details
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
                LEFT JOIN departments d ON u.department_id = d.department_id
                LEFT JOIN movement_reason mr ON GP.reason_for_movement = mr.reason_id
                WHERE GP.pass_id = ? AND u.phone = ? AND u.l_name = ?
            ");
            $stmt->execute([$id, $phone, $lastname]);
            $pass = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($pass) {
                // Check if pass is expired (more than 1 day from approval)
                if ($pass['estate_office_status'] === 'Granted') {
                    $approvalDate = new DateTime($pass['hod_approval_date']);
                    $currentDate = new DateTime();
                    $interval = $currentDate->diff($approvalDate);
                    
                    if ($interval->days > 1) {
                        $error = "This gate pass has expired.";
                        $verified = false;
                    } else {
                        // Fetch items
                        $stmt = $pdo->prepare("SELECT * FROM goods WHERE pass_id = ?");
                        $stmt->execute([$id]);
                        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
                        $verified = true;
                        $success = "Gate pass verified successfully!";
                    }
                } else {
                    $verified = true;
                    $success = "Gate pass verified successfully!";
                }
            } else {
                $error = "Invalid verification details. Please check and try again.";
            }
        } elseif ($type === 'movementorder') {
            // Verify movement order with user details
            $stmt = $pdo->prepare("
                SELECT 
                    MO.*,
                    u.fname,
                    u.m_name,
                    u.l_name,
                    u.phone,
                    u.designation,
                    d.department_name,
                    l1.location_name as source_location,
                    l2.location_name as destination_location,
                    CONCAT(u.fname, ' ', COALESCE(u.m_name, ''), ' ', u.l_name) as full_name
                FROM movement_orders MO
                JOIN users u ON MO.user_id = u.user_id
                LEFT JOIN departments d ON u.department_id = d.department_id
                JOIN locations l1 ON MO.source_location_id = l1.location_id
                JOIN locations l2 ON MO.destination_location_id = l2.location_id
                WHERE MO.order_id = ? AND u.phone = ? AND u.l_name = ?
            ");
            $stmt->execute([$id, $phone, $lastname]);
            $order = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($order) {
                // Check if order is expired (more than 1 day from creation)
                $creationDate = new DateTime($order['created_at']);
                $currentDate = new DateTime();
                $interval = $currentDate->diff($creationDate);
                
                if ($interval->days > 1) {
                    $error = "This movement order has expired.";
                    $verified = false;
                } else {
                    // Fetch items
                    $stmt = $pdo->prepare("SELECT * FROM movement_order_items WHERE order_id = ?");
                    $stmt->execute([$id]);
                    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    $verified = true;
                    $success = "Movement order verified successfully!";
                }
            } else {
                $error = "Invalid verification details. Please check and try again.";
            }
        }
    } catch (PDOException $e) {
        $error = "Error verifying document: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document Verification System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        @media print {
            .no-print {
                display: none;
            }
            .print-only {
                display: block;
            }
        }
        
        /* Custom scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }
        ::-webkit-scrollbar-track {
            background: #f1f1f1;
        }
        ::-webkit-scrollbar-thumb {
            background: #888;
            border-radius: 4px;
        }
        ::-webkit-scrollbar-thumb:hover {
            background: #555;
        }
    </style>
</head>
<body class="bg-gradient-to-br from-gray-100 to-gray-200 min-h-screen">
    <div class="container mx-auto px-4 py-8 sm:px-6 lg:px-8">
        <div class="max-w-4xl mx-auto">
            <!-- Verification Form -->
            <?php if (!$verified): ?>
                <div class="bg-white rounded-2xl shadow-xl overflow-hidden">
                    <div class="bg-gradient-to-r from-blue-600 to-blue-700 p-6 sm:p-8">
                        <div class="text-center">
                            <i class="fas fa-shield-alt text-white text-4xl mb-4"></i>
                            <h2 class="text-2xl sm:text-3xl font-bold text-white">Document Verification</h2>
                            <p class="mt-2 text-blue-100">Secure verification system for gate passes and movement orders</p>
                        </div>
                    </div>

                    <div class="p-6 sm:p-8">
                        <?php if ($error): ?>
                            <div class="bg-red-50 border-l-4 border-red-500 p-4 mb-6" role="alert">
                                <div class="flex">
                                    <div class="flex-shrink-0">
                                        <i class="fas fa-exclamation-circle text-red-500"></i>
                                    </div>
                                    <div class="ml-3">
                                        <p class="text-sm text-red-700"><?php echo htmlspecialchars($error); ?></p>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>

                        <form method="POST" class="space-y-6">
                            <!-- Form fields with enhanced styling -->
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                                <div class="col-span-1 sm:col-span-2">
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        <i class="fas fa-file-alt mr-2"></i>Document Type
                                    </label>
                                    <select name="type" 
                                            class="w-full px-4 py-3 rounded-lg border-2 border-gray-200 focus:border-blue-500 focus:ring focus:ring-blue-200 transition-all duration-200"
                                            required>
                                        <option value="gatepass">Gate Pass</option>
                                        <option value="movementorder">Movement Order</option>
                                    </select>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        <i class="fas fa-id-card mr-2"></i>Document ID
                                    </label>
                                    <input type="number" name="id" 
                                           class="w-full px-4 py-3 rounded-lg border-2 border-gray-200 focus:border-blue-500 focus:ring focus:ring-blue-200 transition-all duration-200"
                                           placeholder="Enter Document ID" required>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        <i class="fas fa-phone mr-2"></i>Phone Number
                                    </label>
                                    <input type="text" name="phone" 
                                           class="w-full px-4 py-3 rounded-lg border-2 border-gray-200 focus:border-blue-500 focus:ring focus:ring-blue-200 transition-all duration-200"
                                           placeholder="Enter Phone Number" required>
                                </div>

                                <div class="col-span-1 sm:col-span-2">
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        <i class="fas fa-user mr-2"></i>Last Name
                                    </label>
                                    <input type="text" name="lastname" 
                                           class="w-full px-4 py-3 rounded-lg border-2 border-gray-200 focus:border-blue-500 focus:ring focus:ring-blue-200 transition-all duration-200"
                                           placeholder="Enter Last Name" required>
                                </div>
                            </div>

                            <button type="submit" 
                                    class="w-full bg-gradient-to-r from-blue-600 to-blue-700 text-white rounded-lg px-6 py-3 font-medium hover:from-blue-700 hover:to-blue-800 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transform transition-all duration-200 hover:scale-[1.02]">
                                <i class="fas fa-search mr-2"></i>Verify Document
                            </button>
                        </form>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Verified Document Display -->
            <?php if ($verified): ?>
                <div class="bg-white rounded-2xl shadow-xl overflow-hidden mt-8">
                    <div class="bg-gradient-to-r from-green-600 to-green-700 p-6 sm:p-8">
                        <div class="text-center">
                            <i class="fas fa-check-circle text-white text-4xl mb-4"></i>
                            <h2 class="text-2xl sm:text-3xl font-bold text-white">Verification Successful</h2>
                            <p class="mt-2 text-green-100">The document has been verified successfully.</p>
                        </div>
                    </div>

                    <div class="p-6 sm:p-8">
                        <h3 class="text-lg font-semibold mb-4">Document Details</h3>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                            <div>
                                <span class="text-gray-600">Name:</span>
                                <p class="font-medium upcase-2"><?php echo htmlspecialchars($pass ? $pass['full_name'] : $order['full_name']); ?></p>
                            </div>
                            <div>
                                <span class="text-gray-600">Department:</span>
                                <p class="font-medium upcase-2"><?php echo htmlspecialchars($pass ? $pass['department_name'] : $order['department_name']); ?></p>
                            </div>
                            <div>
                                <span class="text-gray-600">Designation:</span>
                                <p class="font-medium upcase-2"><?php echo htmlspecialchars($pass ? $pass['designation'] : $order['designation']); ?></p>
                            </div>
                            <div>
                                <span class="text-gray-600">Phone:</span>
                                <p class="font-medium upcase-2"><?php echo htmlspecialchars($pass ? $pass['phone'] : $order['phone']); ?></p>
                            </div>
                        </div>

                        <h3 class="text-lg font-semibold mt-8 mb-4">Items List</h3>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Item Description
                                        </th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Quantity
                                        </th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Remarks
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <?php foreach ($items as $item): ?>
                                        <tr>
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
            <?php endif; ?>
        </div>
    </div>

    <?php if ($success): ?>
    <script>
        Swal.fire({
            icon: 'success',
            title: 'Verification Successful',
            text: '<?php echo $success; ?>',
            confirmButtonColor: '#3085d6',
            showConfirmButton: true,
            timer: 3000
        });
    </script>
    <?php endif; ?>

    <!-- Print Button -->
    <?php if ($verified): ?>
    <div class="fixed bottom-6 right-6 print:hidden">
        <button onclick="window.print()" 
                class="bg-blue-600 text-white rounded-full p-4 shadow-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transform transition-all duration-200 hover:scale-110">
            <i class="fas fa-print text-xl"></i>
        </button>
    </div>
    <?php endif; ?>
</body>
</html>