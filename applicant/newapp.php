<link rel="stylesheet" href="../assets/main.css">
<?php
$page_title = "New Gate Pass Application";
require_once '../config/config.php';
require_once '../backend/db.php';
include 'includes/header.php';

// Fetch user details and movement reasons from database
try {
    // Fetch user details including department info
    $stmt = $pdo->prepare("
        SELECT 
            u.name, 
            u.phone,
            u.designation,
            d.department_name, 
            d.hod_name, 
            d.hod_id
        FROM users u 
        JOIN departments d ON u.department_id = d.department_id 
        WHERE u.user_id = ?
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $user_info = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user_info) {
        $user_info = [
            'name' => '',
            'phone' => '',
            'department_name' => '',
            'designation' => '',
            'hod_name' => '',
            'hod_id' => ''
        ];
    }

    // Fetch movement reasons
    $stmt = $pdo->query("SELECT * FROM movement_reason ORDER BY reason_name");
    $movement_reasons = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($movement_reasons)) {
        throw new Exception('No movement reasons found in the database');
    }
} catch (PDOException $e) {
    error_log("Database Error: " . $e->getMessage());
    $movement_reasons = [];
    $user_info = [
        'name' => '',
        'phone' => '',
        'department_name' => '',
        'designation' => '',
        'hod_name' => '',
        'hod_id' => ''
    ];
} catch (Exception $e) {
    error_log("General Error: " . $e->getMessage());
    $movement_reasons = [];
}
?>

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
                <header class="text-center mb-6">
                    <div class="flex justify-center items-center space-x-4">
                        <img src="../images/logo.png" alt="University Logo" class="w-20 h-20">
                        <div>
                            <h1 class="text-xl font-bold uppercase"><?php echo APP_NAME; ?></h1>
                            <h2 class="text-lg font-semibold mt-2 uppercase">Goods Outward Gate Pass</h2>
                        </div>
                    </div>
                </header>

                <form action="../backend/process_gatepass.php" method="POST">
                    <!-- Applicant Details -->
                    <section class="mb-6">
                        <h3 class="font-semibold mb-4 uppercase flex items-center">
                            <i class="fas fa-user-circle mr-2"></i>Applicant Details
                        </h3>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium required ">Name:</label>
                                <input type="text" 
                                       value="<?php echo isset($user_info['name']) ? htmlspecialchars($user_info['name']) : ''; ?>" 
                                       class="txt-formart w-full border border-gray-300 rounded p-2 bg-gray-50" readonly>
                            </div>
                            <div>
                                <label class="block text-sm font-medium required">Telephone No:</label>
                                <input type="text" 
                                       value="<?php echo isset($user_info['phone']) ? htmlspecialchars($user_info['phone']) : ''; ?>" 
                                       class=" w-full border border-gray-300 rounded p-2 bg-gray-50" readonly>
                            </div>
                            <div>
                                <label class="block text-sm font-medium required">Department:</label>
                                <input type="text" 
                                       value="<?php echo isset($user_info['department_name']) ? htmlspecialchars($user_info['department_name']) : ''; ?>" 
                                       class="txt-formart w-full border border-gray-300 rounded p-2 bg-gray-50" readonly>
                            </div>
                            <div>
                                <label class="block text-sm font-medium required">Designation:</label>
                                <input type="text" 
                                       value="<?php echo isset($user_info['designation']) ? htmlspecialchars($user_info['designation']) : ''; ?>" 
                                       class="txt-formart w-full border border-gray-300 rounded p-2 bg-gray-50" readonly>
                            </div>
                            <div>
                                <label class="block text-sm font-medium required">Vehicle Registration No:</label>
                                <input type="text" name="vehicle_registration" 
                                       class="txt-formart w-full border border-gray-300 rounded p-2 upcase" 
                                       placeholder="Enter vehicle number" required>
                            </div>
                            <div>
                                <label class="block text-sm font-medium required">Date:</label>
                                <input type="date" name="date" value="<?php echo date('Y-m-d'); ?>" 
                                       class="w-full border border-gray-300 rounded p-2 bg-gray-50" readonly>
                            </div>
                        </div>
                    </section>

                    <!-- Goods Details -->
                    <section class="mb-6">
                        <h3 class="font-semibold mb-4 uppercase flex items-center">
                            <i class="fas fa-box mr-2"></i>Goods Details
                        </h3>
                        <div class="overflow-x-auto">
                            <table class="w-full border-collapse border border-gray-300">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="border border-gray-300 p-2">S/N</th>
                                        <th class="border border-gray-300 p-2">Item Description</th>
                                        <th class="border border-gray-300 p-2">Qty</th>
                                        <th class="border border-gray-300 p-2">Remarks</th>
                                        <th class="border border-gray-300 p-2">Action</th>
                                    </tr>
                                </thead>
                                <tbody id="goodsTableBody">
                                    <tr>
                                        <td class="border border-gray-300 p-2 text-center">1</td>
                                        <td class="border border-gray-300 p-2">
                                            <input type="text" name="goods[0][item_description]" 
                                                   class="w-full border-none upcase" required>
                                        </td>
                                        <td class="border border-gray-300 p-2">
                                            <input type="number" name="goods[0][quantity]" 
                                                   class="w-full border-none text-center upcase" required>
                                        </td>
                                        <td class="border border-gray-300 p-2">
                                            <input type="text" name="goods[0][remarks]" class="upcase w-full border-none">
                                        </td>
                                        <td class="border border-gray-300 p-2 text-center">
                                            <button type="button" class="text-red-500 hover:text-red-700" 
                                                    onclick="removeRow(this)" disabled>
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <button type="button" onclick="addGoodsRow()" 
                                class="mt-3 inline-flex items-center px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600">
                            <i class="fas fa-plus mr-2"></i> Add Row
                        </button>
                    </section>

                    <!-- Movement Reason -->
                    <section class="mb-6">
                        <h3 class="font-semibold mb-4 uppercase flex items-center">
                            <i class="fas fa-truck mr-2"></i>Reason for Movement
                        </h3>
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                            <?php if (!empty($movement_reasons)): ?>
                                <?php foreach ($movement_reasons as $reason): ?>
                                    <label class="flex items-center space-x-2 p-2 border rounded hover:bg-gray-50">
                                        <input type="radio" name="reason_for_movement" 
                                               value="<?php echo htmlspecialchars($reason['reason_id']); ?>" required>
                                        <span><?php echo htmlspecialchars($reason['reason_name']); ?></span>
                                    </label>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <p class="text-red-500 col-span-3">No movement reasons available. Please contact administrator.</p>
                            <?php endif; ?>
                        </div>
                        <div id="otherReasonContainer" class="mt-4 hidden">
                            <textarea name="other_reason" id="otherReason" rows="3" 
                                    class="w-full border border-gray-300 rounded p-2 upcase-2" 
                                    placeholder="Please specify other reason"></textarea>
                        </div>
                    </section>

                    <!-- Form Actions -->
                    <div class="flex justify-end space-x-4">
                        <button type="reset" class="px-6 py-2 bg-gray-500 text-white rounded hover:bg-gray-600">
                            <i class="fas fa-redo mr-2"></i>Reset
                        </button>
                        <button type="submit" class="px-6 py-2 bg-blue-500 text-white rounded hover:bg-blue-600">
                            <i class="fas fa-paper-plane mr-2"></i>Submit
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
let rowCount = 1;

function addGoodsRow() {
    rowCount++;
    const tableBody = document.getElementById('goodsTableBody');
    const newRow = document.createElement('tr');
    
    newRow.innerHTML = `
        <td class="border border-gray-300 p-2 text-center">${rowCount}</td>
        <td class="border border-gray-300 p-2">
            <input type="text" name="goods[${rowCount-1}][item_description]" class="w-full border-none" required>
        </td>
        <td class="border border-gray-300 p-2">
            <input type="number" name="goods[${rowCount-1}][quantity]" class="w-full border-none text-center" required>
        </td>
        <td class="border border-gray-300 p-2">
            <input type="text" name="goods[${rowCount-1}][remarks]" class="w-full border-none">
        </td>
        <td class="border border-gray-300 p-2 text-center">
            <button type="button" class="text-red-500 hover:text-red-700" onclick="removeRow(this)">
                <i class="fas fa-trash"></i>
            </button>
        </td>
    `;
    tableBody.appendChild(newRow);
}

function removeRow(button) {
    if (document.getElementById('goodsTableBody').children.length > 1) {
        const row = button.closest('tr');
        row.remove();
        updateRowNumbers();
    } else {
        alert('At least one item is required.');
    }
}

function updateRowNumbers() {
    const rows = document.getElementById('goodsTableBody').getElementsByTagName('tr');
    for (let i = 0; i < rows.length; i++) {
        rows[i].cells[0].textContent = i + 1;
    }
    rowCount = rows.length;
}

// Handle other reason toggle
document.querySelectorAll('input[name="reason_for_movement"]').forEach(radio => {
    radio.addEventListener('change', function() {
        const otherContainer = document.getElementById('otherReasonContainer');
        const otherTextarea = document.getElementById('otherReason');
        
        if (this.value === '5') {
            otherContainer.classList.remove('hidden');
            otherTextarea.required = true;
        } else {
            otherContainer.classList.add('hidden');
            otherTextarea.required = false;
            otherTextarea.value = '';
        }
    });
});
</script>

<?php include 'includes/footer.php'; ?>