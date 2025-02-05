<?php
session_start();
require_once '../backend/db.php';

// Check if user is logged in and has estate officer role
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Estate Officer') {
    header('Location: ../login.php');
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $applicantId = $_SESSION['user_id'];
    $vehicleRegistration = filter_input(INPUT_POST, 'vehicle_registration', FILTER_SANITIZE_STRING);
    $reasonForMovement = filter_input(INPUT_POST, 'reason_for_movement', FILTER_SANITIZE_NUMBER_INT);
    $otherReason = filter_input(INPUT_POST, 'other_reason', FILTER_SANITIZE_STRING);
    $items = $_POST['items'] ?? [];

    try {
        // Insert gate pass
        $stmt = $pdo->prepare("INSERT INTO gate_pass (applicant_id, vehicle_registration, reason_for_movement, other_reason, is_emergency) VALUES (:applicant_id, :vehicle_registration, :reason_for_movement, :other_reason, 1)");
        $stmt->execute([
            ':applicant_id' => $applicantId,
            ':vehicle_registration' => $vehicleRegistration,
            ':reason_for_movement' => $reasonForMovement,
            ':other_reason' => $otherReason
        ]);
        $passId = $pdo->lastInsertId();

        // Insert goods
        $goodsStmt = $pdo->prepare("INSERT INTO goods (pass_id, item_description, quantity, remarks) VALUES (:pass_id, :item_description, :quantity, :remarks)");
        foreach ($items as $item) {
            $goodsStmt->execute([
                ':pass_id' => $passId,
                ':item_description' => filter_var($item['description'], FILTER_SANITIZE_STRING),
                ':quantity' => filter_var($item['quantity'], FILTER_SANITIZE_NUMBER_INT),
                ':remarks' => filter_var($item['remarks'], FILTER_SANITIZE_STRING)
            ]);
        }

        // Log the action
        $logStmt = $pdo->prepare("INSERT INTO activity_log (user_id, action_type, action_description, related_id) VALUES (:user_id, 'Created', 'Emergency gate pass created', :pass_id)");
        $logStmt->execute([
            ':user_id' => $applicantId,
            ':pass_id' => $passId
        ]);

        $successMessage = "Emergency gate pass created successfully.";
    } catch (PDOException $e) {
        error_log($e->getMessage());
        $errorMessage = "An error occurred while creating the gate pass. Please try again.";
    }
}

// Fetch movement reasons
$reasonsStmt = $pdo->query("SELECT reason_id, reason_name FROM movement_reason");
$reasons = $reasonsStmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Emergency Gate Pass</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100">
    <div class="container mx-auto px-4 py-8">
        <h1 class="text-2xl font-bold mb-4">Emergency Gate Pass Registration</h1>

        <?php if (isset($successMessage)): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                <?php echo htmlspecialchars($successMessage); ?>
            </div>
        <?php elseif (isset($errorMessage)): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                <?php echo htmlspecialchars($errorMessage); ?>
            </div>
        <?php endif; ?>

        <form action="" method="POST" class="space-y-6">
            <h3>APPLICAT FOR EMERGENCY GATE PASS</h3>
            <div>
                <label class="block text-sm font-medium text-gray-700">Vehicle Registration</label>
                <input type="text" name="vehicle_registration" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" required>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">Name of the Person</label>
                <input type="text" name="vehicle_registration" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" required>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">Telephone Number</label>
                <input type="text" name="vehicle_registration" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" required>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Designation</label>
                <input type="text" name="vehicle_registration" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" required>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Date</label>
                <input type="text" name="vehicle_registration" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" required>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">Reason for Movement</label>
                <select name="reason_for_movement" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" required>
                    <?php foreach ($reasons as $reason): ?>
                        <option value="<?php echo $reason['reason_id']; ?>"><?php echo htmlspecialchars($reason['reason_name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">Other Reason (if any)</label>
                <textarea name="other_reason" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm"></textarea>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">Items</label>
                <div id="itemsContainer">
                    <div class="flex space-x-2 mb-2">
                        <input type="text" name="items[0][description]" placeholder="Item Description" class="flex-1 border-gray-300 rounded-md shadow-sm" required>
                        <input type="number" name="items[0][quantity]" placeholder="Quantity" class="w-24 border-gray-300 rounded-md shadow-sm" required>
                        <input type="text" name="items[0][remarks]" placeholder="Remarks" class="flex-1 border-gray-300 rounded-md shadow-sm">
                    </div>
                </div>
                <button type="button" onclick="addItem()" class="mt-2 bg-blue-500 text-white px-4 py-2 rounded-md">Add Item</button>
            </div>

            <div>
                <button type="submit" class="bg-green-500 text-white px-6 py-3 rounded-md">Submit</button>
            </div>
        </form>
    </div>

    <script>
        let itemIndex = 1;
        function addItem() {
            const container = document.getElementById('itemsContainer');
            const newItem = document.createElement('div');
            newItem.className = 'flex space-x-2 mb-2';
            newItem.innerHTML = `
                <input type="text" name="items[${itemIndex}][description]" placeholder="Item Description" class="flex-1 border-gray-300 rounded-md shadow-sm" required>
                <input type="number" name="items[${itemIndex}][quantity]" placeholder="Quantity" class="w-24 border-gray-300 rounded-md shadow-sm" required>
                <input type="text" name="items[${itemIndex}][remarks]" placeholder="Remarks" class="flex-1 border-gray-300 rounded-md shadow-sm">
            `;
            container.appendChild(newItem);
            itemIndex++;
        }
    </script>
</body>
</html> 