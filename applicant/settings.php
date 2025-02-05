<?php
$page_title = "Settings";
require_once '../config/config.php';
require_once '../backend/db.php';
include 'includes/header.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit();
}

// Fetch user data
try {
    $stmt = $pdo->prepare("SELECT u.*, d.department_name 
                          FROM users u 
                          LEFT JOIN departments d ON u.department_id = d.department_id 
                          WHERE u.user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // Fetch departments
    $deptStmt = $pdo->query("SELECT * FROM departments ORDER BY department_name");
    $departments = $deptStmt->fetchAll(PDO::FETCH_ASSOC);

    // Get activity stats
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM activity_log WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $activity_count = $stmt->fetchColumn();

    // Get last login
    $last_login = $user['last_login'];

} catch(PDOException $e) {
    error_log($e->getMessage());
    $error_message = "Failed to fetch user data";
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $type = $_POST['form_type'] ?? '';
    
    try {
        switch($type) {
            case 'profile':
                $fname = filter_input(INPUT_POST, 'fname', FILTER_SANITIZE_STRING);
                $lname = filter_input(INPUT_POST, 'lname', FILTER_SANITIZE_STRING);
                $phone = filter_input(INPUT_POST, 'phone', FILTER_SANITIZE_STRING);
                $department_id = filter_input(INPUT_POST, 'department_id', FILTER_SANITIZE_NUMBER_INT);

                $stmt = $pdo->prepare("UPDATE users SET 
                    fname = ?, l_name = ?, phone = ?, department_id = ?
                    WHERE user_id = ?");
                $stmt->execute([$fname, $lname, $phone, $department_id, $_SESSION['user_id']]);
                
                // Log the action
                $log_stmt = $pdo->prepare("INSERT INTO activity_log 
                    (user_id, action_type, action_description) 
                    VALUES (?, 'Profile Update', 'Updated profile information')");
                $log_stmt->execute([$_SESSION['user_id']]);
                
                $success_message = "Profile updated successfully";
                break;

            case 'security':
                $current_password = $_POST['current_password'];
                $new_password = $_POST['new_password'];
                $confirm_password = $_POST['confirm_password'];

                // Verify current password
                if (!password_verify($current_password, $user['password'])) {
                    throw new Exception("Current password is incorrect");
                }

                // Validate new password
                if ($new_password !== $confirm_password) {
                    throw new Exception("New passwords do not match");
                }

                // Update password
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE user_id = ?");
                $stmt->execute([$hashed_password, $_SESSION['user_id']]);
                
                $success_message = "Password updated successfully";
                break;

            case 'preferences':
                $email_notifications = isset($_POST['email_notifications']) ? 1 : 0;
                $stmt = $pdo->prepare("UPDATE users SET email_notifications = ? WHERE user_id = ?");
                $stmt->execute([$email_notifications, $_SESSION['user_id']]);
                $success_message = "Preferences updated successfully";
                break;
        }
    } catch(Exception $e) {
        error_log($e->getMessage());
        $error_message = $e->getMessage();
    }
}
?>
    <link rel="stylesheet" href="../assets/main.css">
<div class="flex min-h-screen bg-gray-50">
    <!-- Sidebar -->
    <div class="fixed inset-y-0 left-0 z-50">
        <?php include 'includes/sidebar.php'; ?>
    </div>

    <!-- Main Content -->
    <div class="flex-1 ml-64">
        <!-- Top Navigation -->
        <header class="sticky top-0 z-40 bg-white shadow-sm">
            <div class="flex justify-between items-center px-8 py-4">
                <div class="flex items-center space-x-4">
                    <span class="text-lg font-semibold text-gray-700">Account Settings</span>
                </div>
                <div class="flex items-center space-x-4">
                    <button class="p-2 hover:bg-gray-100 rounded-full transition-colors">
                        <i class="fas fa-bell text-gray-600"></i>
                    </button>
                    <span class="text-gray-700"><?php echo htmlspecialchars($_SESSION['name']); ?></span>
                    <button class="p-2 hover:bg-gray-100 rounded-full transition-colors">
                        <i class="fas fa-user-circle text-gray-600 text-2xl"></i>
                    </button>
                </div>
            </div>
        </header>

        <!-- Page Content -->
        <div class="p-8">
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

            <!-- Stats Cards -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                <!-- Profile Status -->
                <div class="bg-white rounded-lg shadow-sm p-6">
                    <div class="flex justify-between items-center">
                        <div>
                            <p class="text-gray-500 text-sm">Profile Status</p>
                            <h3 class="text-2xl font-bold text-gray-800 upcase"><?php echo htmlspecialchars($user['l_name']) . " " . htmlspecialchars($user['fname']); ?></h3>
                            <p class="text-blue-500 text-sm">Active User</p>
                        </div>
                        <div class="bg-blue-100 p-3 rounded-full">
                            <i class="fas fa-user text-blue-500"></i>
                        </div>
                    </div>
                </div>

                <!-- Department -->
                <div class="bg-white rounded-lg shadow-sm p-6">
                    <div class="flex justify-between items-center">
                        <div>
                            <p class="text-gray-500 text-sm ">Department</p>
                            <h3 class="text-2xl font-bold text-gray-800 upcase"><?php echo htmlspecialchars($user['department_name']); ?></h3>
                            <p class="text-green-500 text-sm">Current Assignment</p>
                        </div>
                        <div class="bg-green-100 p-3 rounded-full">
                            <i class="fas fa-building text-green-500"></i>
                        </div>
                    </div>
                </div>

                <!-- Activity Count -->
                <div class="bg-white rounded-lg shadow-sm p-6">
                    <div class="flex justify-between items-center">
                        <div>
                            <p class="text-gray-500 text-sm">Activities</p>
                            <h3 class="text-2xl font-bold text-gray-800"><?php echo $activity_count; ?></h3>
                            <p class="text-yellow-500 text-sm">Total Actions</p>
                        </div>
                        <div class="bg-yellow-100 p-3 rounded-full">
                            <i class="fas fa-chart-line text-yellow-500"></i>
                        </div>
                    </div>
                </div>

                <!-- Last Login -->
                <div class="bg-white rounded-lg shadow-sm p-6">
                    <div class="flex justify-between items-center">
                        <div>
                            <p class="text-gray-500 text-sm">Last Login</p>
                            <h3 class="text-2xl font-bold text-gray-800"><?php echo date('d M', strtotime($last_login)); ?></h3>
                            <p class="text-purple-500 text-sm"><?php echo date('h:i A', strtotime($last_login)); ?></p>
                        </div>
                        <div class="bg-purple-100 p-3 rounded-full">
                            <i class="fas fa-clock text-purple-500"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Settings Sections -->
            <div class="grid grid-cols-1 gap-8">
                <!-- Profile Settings -->
                <div class="bg-white rounded-lg shadow-sm p-6">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-semibold">Profile Information</h3>
                    </div>
                    <form action="" method="POST" class="space-y-4">
                        <input type="hidden" name="form_type" value="profile">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">First Name</label>
                                <input type="text" name="fname" value="<?php echo htmlspecialchars($user['fname']); ?>" 
                                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 upcase">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Last Name</label>
                                <input type="text" name="lname" value="<?php echo htmlspecialchars($user['l_name']); ?>" 
                                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 upcase">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Phone Number</label>
                                <input type="text" name="phone" value="<?php echo htmlspecialchars($user['phone']); ?>" 
                                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 upcase ">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Department</label>
                                <select name="department_id" 
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500  upcase">
                                    <?php foreach ($departments as $dept): ?>
                                        <option value="<?php echo $dept['department_id']; ?>" 
                                            <?php echo $user['department_id'] == $dept['department_id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($dept['department_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="flex justify-end">
                            <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded-lg hover:bg-blue-600 transition-colors">
                                <i class="fas fa-save mr-2"></i> Update Profile
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Security Settings -->
                <div class="bg-white rounded-lg shadow-sm p-6">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-semibold">Security Settings</h3>
                    </div>
                    <form action="" method="POST" class="space-y-4">
                        <input type="hidden" name="form_type" value="security">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Current Password</label>
                                <input type="password" name="current_password" required 
                                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">New Password</label>
                                <input type="password" name="new_password" required 
                                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Confirm New Password</label>
                                <input type="password" name="confirm_password" required 
                                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            </div>
                        </div>
                        <div class="flex justify-end">
                            <button type="submit" class="bg-green-500 text-white px-4 py-2 rounded-lg hover:bg-green-600 transition-colors">
                                <i class="fas fa-lock mr-2"></i> Update Password
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Preferences -->
                <div class="bg-white rounded-lg shadow-sm p-6">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-semibold">Preferences</h3>
                    </div>
                    <form action="" method="POST" class="space-y-4">
                        <input type="hidden" name="form_type" value="preferences">
                        <div class="flex items-center">
                            <input type="checkbox" name="email_notifications" id="email_notifications" 
                                   class="rounded border-gray-300 text-blue-500 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                   <?php echo $user['email_notifications'] ? 'checked' : ''; ?>>
                            <label for="email_notifications" class="ml-2 block text-sm text-gray-700">
                                Receive email notifications for gate pass updates
                            </label>
                        </div>
                        <div class="flex justify-end">
                            <button type="submit" class="bg-purple-500 text-white px-4 py-2 rounded-lg hover:bg-purple-600 transition-colors">
                                <i class="fas fa-cog mr-2"></i> Save Preferences
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // Show/hide success message
    const successMessage = document.querySelector('.bg-green-100');
    if (successMessage) {
        setTimeout(() => {
            successMessage.style.display = 'none';
        }, 3000);
    }
</script>

<?php include 'includes/footer.php'; ?>