<?php
$page_title = "Internal Movement Order";
require_once '../config/config.php';
require_once '../backend/db.php';
include 'includes/header.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit();
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Start transaction
        $pdo->beginTransaction();

        // Validate inputs
        if (empty($_POST['source_location']) || empty($_POST['destination_location']) || 
            empty($_POST['movement_date']) || empty($_POST['items'])) {
            throw new Exception("Please fill in all required fields.");
        }

        // Insert movement order
        $stmt = $pdo->prepare("
            INSERT INTO movement_orders (
                user_id, 
                source_location_id, 
                destination_location_id, 
                movement_date, 
                priority_level, 
                remarks
            ) VALUES (?, ?, ?, ?, ?, ?)
        ");

        $stmt->execute([
            $_SESSION['user_id'],
            $_POST['source_location'],
            $_POST['destination_location'],
            $_POST['movement_date'],
            $_POST['priority'],
            $_POST['remarks']
        ]);

        $order_id = $pdo->lastInsertId();

        // Insert items
        $stmt = $pdo->prepare("
            INSERT INTO movement_order_items (
                order_id, 
                item_description, 
                quantity, 
                remarks
            ) VALUES (?, ?, ?, ?)
        ");

        foreach ($_POST['items'] as $item) {
            if (empty($item['description']) || empty($item['quantity'])) {
                throw new Exception("Please fill in all item details.");
            }
            $stmt->execute([
                $order_id,
                $item['description'],
                $item['quantity'],
                $item['remarks'] ?? ''
            ]);
        }

        // Log the activity
        $stmt = $pdo->prepare("
            INSERT INTO activity_log (
                user_id, 
                action_type, 
                action_description, 
                related_id
            ) VALUES (?, ?, ?, ?)
        ");

        $stmt->execute([
            $_SESSION['user_id'],
            'Created',
            'Created new movement order #' . $order_id,
            $order_id
        ]);

        // Commit transaction
        $pdo->commit();

        // Store success message in session
        $_SESSION['success_message'] = "Movement order #$order_id created successfully!";
        
        // Redirect to prevent form resubmission
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();

    } catch (Exception $e) {
        // Rollback transaction on error
        $pdo->rollBack();
        error_log($e->getMessage());
        $_SESSION['error_message'] = $e->getMessage();
        
        // Redirect to prevent form resubmission
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    }
}

// Check for messages in session
if (isset($_SESSION['success_message'])) {
    $success_message = $_SESSION['success_message'];
    unset($_SESSION['success_message']); // Clear the message
}

if (isset($_SESSION['error_message'])) {
    $error_message = $_SESSION['error_message'];
    unset($_SESSION['error_message']); // Clear the message
}

// Fetch user details and locations
try {
    $stmt = $pdo->prepare("
        SELECT 
            u.name, 
            u.phone,
            u.designation,
            d.department_name
        FROM users u 
        JOIN departments d ON u.department_id = d.department_id 
        WHERE u.user_id = ?
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $user_info = $stmt->fetch(PDO::FETCH_ASSOC);

    // Fetch locations
    $locationStmt = $pdo->query("SELECT * FROM locations ORDER BY location_name");
    $locations = $locationStmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log($e->getMessage());
    $error_message = "Failed to fetch data.";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    <!-- Prevent form resubmission -->
    <script>
        if (window.history.replaceState) {
            window.history.replaceState(null, null, window.location.href);
        }
    </script>
</head>
<body>
    <div class="flex min-h-screen bg-gray-50">
        <!-- Sidebar -->
        <div class="fixed inset-y-0 left-0 z-50">
            <?php include 'includes/sidebar.php'; ?>
        </div>

        <!-- Main Content -->
        <div class="flex-1 ml-64">
            <?php include 'includes/topbar.php'; ?>
            
            <div class="p-8">
                <div class="max-w-5xl mx-auto bg-white p-6 border border-gray-300 rounded-lg shadow-lg">
                    <!-- Success/Error Messages -->
                    <?php if (isset($error_message)): ?>
                        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                            <span class="block sm:inline"><?php echo htmlspecialchars($error_message); ?></span>
                        </div>
                    <?php endif; ?>

                    <?php if (isset($success_message)): ?>
                        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
                            <span class="block sm:inline"><?php echo htmlspecialchars($success_message); ?></span>
                        </div>
                    <?php endif; ?>

                    <!-- Rest of your form HTML remains the same -->
                    <!-- ... (Previous form HTML code) ... -->

                    <!-- Header -->
                    <header class="text-center mb-6">
                        <div class="flex justify-center items-center space-x-4">
                            <img src="../images/logo.png" alt="University Logo" class="w-20 h-20">
                            <div>
                                <h1 class="text-xl font-bold uppercase"><?php echo APP_NAME; ?></h1>
                                <h2 class="text-lg font-semibold mt-2 uppercase">Internal Movement Order</h2>
                            </div>
                        </div>
                    </header>
                    <form action="" method="POST" class="space-y-6">
                        <!-- Applicant Details Section -->
                        <section class="mb-6">
                            <h3 class="font-semibold mb-4 uppercase flex items-center text-gray-700">
                                <i class="fas fa-user-circle mr-2"></i>Applicant Details
                            </h3>
                            <table class="min-w-full divide-y divide-gray-300">
                                <tbody class="bg-white divide-y divide-gray-300">
                                    <tr>
                                        <td class="px-6 py-4 font-medium text-gray-700">Name:</td>
                                        <td class="px-6 py-4 uppercase"><?php echo htmlspecialchars($user_info['name'] ?? ''); ?></td>
                                    </tr>
                                    <tr>
                                        <td class="px-6 py-4 font-medium text-gray-700">Department:</td>
                                        <td class="px-6 py-4 uppercase"><?php echo htmlspecialchars($user_info['department_name'] ?? ''); ?></td>
                                    </tr>
                                    <tr>
                                        <td class="px-6 py-4 font-medium text-gray-700">Phone Number:</td>
                                        <td class="px-6 py-4"><?php echo htmlspecialchars($user_info['phone'] ?? ''); ?></td>
                                    </tr>
                                    <tr>
                                        <td class="px-6 py-4 font-medium text-gray-700">Designation:</td>
                                        <td class="px-6 py-4 uppercase"><?php echo htmlspecialchars($user_info['designation'] ?? ''); ?></td>
                                    </tr>
                                </tbody>
                            </table>
                        </section>

                        <!-- Movement Details Section -->
                        <section class="mb-6">
                            <h3 class="font-semibold mb-4 uppercase flex items-center text-gray-700">
                                <i class="fas fa-exchange-alt mr-2"></i>Movement Details
                            </h3>
                            <table class="min-w-full divide-y divide-gray-300">
                                <tbody class="bg-white divide-y divide-gray-300">
                                    <tr>
                                        <td class="px-6 py-4 font-medium text-gray-700">Source Location:</td>
                                        <td class="px-6 py-4">
                                            <select name="source_location" required class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                                <option value="">Select Source Location</option>
                                                <?php foreach ($locations as $location): ?>
                                                    <option value="<?php echo $location['location_id']; ?>" class="uppercase">
                                                        <?php echo htmlspecialchars($location['location_name']); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="px-6 py-4 font-medium text-gray-700">Destination Location:</td>
                                        <td class="px-6 py-4">
                                            <select name="destination_location" required class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                                <option value="">Select Destination Location</option>
                                                <?php foreach ($locations as $location): ?>
                                                    <option value="<?php echo $location['location_id']; ?>" class="uppercase">
                                                        <?php echo htmlspecialchars($location['location_name']); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="px-6 py-4 font-medium text-gray-700">Movement Date:</td>
                                        <td class="px-6 py-4">
                                            <input type="date" name="movement_date" required 
                                                   class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="px-6 py-4 font-medium text-gray-700">Priority Level:</td>
                                        <td class="px-6 py-4">
                                            <select name="priority" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                                <option value="Normal">Normal</option>
                                                <option value="Urgent">Urgent</option>
                                                <option value="Emergency">Emergency</option>
                                            </select>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </section>

                        <!-- Items Section -->
                        <section class="mb-6">
                            <h3 class="font-semibold mb-4 uppercase flex items-center text-gray-700">
                                <i class="fas fa-boxes mr-2"></i>Items to be Moved
                            </h3>
                            <div class="bg-white rounded-lg border border-gray-300">
                                <table class="min-w-full divide-y divide-gray-300">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">No.</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Item Name</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Quantity</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Remarks</th>
                                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody id="itemsTableBody" class="bg-white divide-y divide-gray-300">
                                        <tr>
                                            <td class="px-6 py-4 whitespace-nowrap text-center">1</td>
                                            <td class="px-6 py-4">
                                                <input type="text" name="items[0][description]" required
                                                       class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500 uppercase">
                                            </td>
                                            <td class="px-6 py-4">
                                                <input type="number" name="items[0][quantity]" required
                                                       class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                            </td>
                                            <td class="px-6 py-4">
                                                <input type="text" name="items[0][remarks]"
                                                       class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500 uppercase">
                                            </td>
                                            <td class="px-6 py-4 text-center">
                                                <button type="button" class="text-red-500 hover:text-red-700" onclick="removeItem(this)">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                                <div class="p-4 border-t border-gray-300">
                                    <button type="button" onclick="addItem()" 
                                            class="text-blue-500 hover:text-blue-700 font-medium flex items-center">
                                        <i class="fas fa-plus-circle mr-2"></i> Add Another Item
                                    </button>
                                </div>
                            </div>
                        </section>

                        <!-- Additional Notes -->
                        <section class="mb-6">
                            <h3 class="font-semibold mb-4 uppercase flex items-center text-gray-700">
                                <i class="fas fa-notes-medical mr-2"></i>Additional Notes
                            </h3>
                            <div class="bg-gray-50 p-4 rounded-lg">
                                <textarea name="remarks" rows="3" 
                                        class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 uppercase"
                                        placeholder="Any additional information or special instructions..."></textarea>
                            </div>
                        </section>

                        <!-- Form Actions -->
                        <div class="flex justify-end space-x-4 pt-4 border-t">
                            <button type="reset" class="px-6 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600 transition-colors flex items-center">
                                <i class="fas fa-redo mr-2"></i> Reset
                            </button>
                            <button type="submit" class="px-6 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition-colors flex items-center">
                                <i class="fas fa-paper-plane mr-2"></i> Submit Order
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
    let itemCount = 1;

    function addItem() {
        itemCount++;
        const tbody = document.getElementById('itemsTableBody');
        const newRow = document.createElement('tr');
        
        newRow.innerHTML = `
            <td class="px-6 py-4 whitespace-nowrap text-center">${itemCount}</td>
            <td class="px-6 py-4">
                <input type="text" name="items[${itemCount-1}][description]" required
                       class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500 uppercase">
            </td>
            <td class="px-6 py-4">
                <input type="number" name="items[${itemCount-1}][quantity]" required
                       class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500">
            </td>
            <td class="px-6 py-4">
                <input type="text" name="items[${itemCount-1}][remarks]"
                       class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500 uppercase">
            </td>
            <td class="px-6 py-4 text-center">
                <button type="button" class="text-red-500 hover:text-red-700" onclick="removeItem(this)">
                    <i class="fas fa-trash"></i>
                </button>
            </td>
        `;
        tbody.appendChild(newRow);
    }

    function removeItem(button) {
        const tbody = document.getElementById('itemsTableBody');
        if (tbody.children.length > 1) {
            button.closest('tr').remove();
            updateItemNumbers();
        } else {
            alert('At least one item is required.');
        }
    }

    function updateItemNumbers() {
        const rows = document.getElementById('itemsTableBody').getElementsByTagName('tr');
        for (let i = 0; i < rows.length; i++) {
            rows[i].cells[0].textContent = i + 1;
        }
        itemCount = rows.length;
    }

    // Auto-hide success message
    document.addEventListener('DOMContentLoaded', function() {
        const successMessage = document.querySelector('.bg-green-100');
        if (successMessage) {
            setTimeout(() => {
                successMessage.style.display = 'none';
            }, 3000);
        }
    });
    </script>

    <?php include 'includes/footer.php'; ?>
</body>
</html>