<?php
session_start();
require_once '../backend/db.php';

// Enable error logging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Log the received token
$received_token = $_GET['token'] ?? 'no token';
error_log("Reset Password - Received token: " . $received_token);

// Verify token in database with detailed logging
try {
    // First, let's see what's in the database for this token
    $check_sql = "SELECT user_id, email, reset_token, reset_token_expiry, 
                  CASE 
                    WHEN reset_token_expiry > NOW() THEN 'Valid'
                    ELSE 'Expired'
                  END as token_status,
                  NOW() as current_time
                  FROM users 
                  WHERE reset_token = ?";
    
    $check_stmt = $pdo->prepare($check_sql);
    $check_stmt->execute([$received_token]);
    $token_info = $check_stmt->fetch(PDO::FETCH_ASSOC);

    // Log the detailed information
    error_log("Token check results:");
    error_log("Token found in database: " . ($token_info ? "Yes" : "No"));
    if ($token_info) {
        error_log("User ID: " . $token_info['user_id']);
        error_log("Email: " . $token_info['email']);
        error_log("Token Status: " . $token_info['token_status']);
        error_log("Expiry Time: " . $token_info['reset_token_expiry']);
        error_log("Current Time: " . $token_info['current_time']);
    }

    // Continue with the rest of your existing code...
    if (!$token_info || $token_info['token_status'] !== 'Valid') {
        $_SESSION['message'] = "This password reset link has expired or is invalid.";
        $_SESSION['message_type'] = "error";
        header('Location: forgot-password.php');
        exit;
    }

} catch (Exception $e) {
    error_log("Reset Password Error - " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    $_SESSION['message'] = "An error occurred. Please try again.";
    $_SESSION['message_type'] = "error";
    header('Location: ../index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - Gate Pass System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .bg-gradient {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .glass-effect {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
        }
    </style>
</head>
<body class="bg-gradient">
    <div class="min-h-screen flex items-center justify-center p-4">
        <div class="max-w-md w-full glass-effect rounded-2xl shadow-2xl p-8">
            <div class="text-center mb-8">
                <div class="flex justify-center mb-4">
                    <img src="../images/logo.png" alt="Logo" class="h-20 w-20 object-contain">
                </div>
                <h2 class="text-3xl font-bold text-gray-800">Reset Password</h2>
                <p class="text-gray-600 mt-2">Enter your new password</p>
            </div>

            <?php if (isset($_SESSION['message'])): ?>
            <div class="<?php echo ($_SESSION['message_type'] == 'success') ? 'bg-green-100 border-green-400 text-green-700' : 'bg-red-100 border-red-400 text-red-700'; ?> px-4 py-3 rounded-lg mb-4 border">
                <?php 
                echo htmlspecialchars($_SESSION['message']);
                unset($_SESSION['message']);
                unset($_SESSION['message_type']);
                ?>
            </div>
            <?php endif; ?>

            <form method="POST" action="../backend/process-reset-password.php" class="space-y-6" id="resetForm">
                <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
                
                <div>
                    <label class="block text-gray-700 text-sm font-semibold mb-2" for="password">
                        New Password
                    </label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                            </svg>
                        </div>
                        <input 
                            class="pl-10 w-full px-4 py-2 bg-gray-50 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            id="password"
                            type="password"
                            name="password"
                            required
                            minlength="8"
                            placeholder="Enter new password">
                    </div>
                    <p class="mt-1 text-sm text-gray-500">Minimum 8 characters</p>
                </div>

                <div>
                    <label class="block text-gray-700 text-sm font-semibold mb-2" for="confirm_password">
                        Confirm Password
                    </label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                            </svg>
                        </div>
                        <input 
                            class="pl-10 w-full px-4 py-2 bg-gray-50 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            id="confirm_password"
                            type="password"
                            name="confirm_password"
                            required
                            minlength="8"
                            placeholder="Confirm new password">
                    </div>
                </div>

                <button 
                    type="submit"
                    id="submitButton"
                    class="w-full flex justify-center py-3 px-4 border border-transparent rounded-lg shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors duration-200">
                    Reset Password
                </button>

                <div class="text-center">
                    <a href="../index.php" class="text-sm font-medium text-blue-600 hover:text-blue-500">
                        Back to Login
                    </a>
                </div>
            </form>
        </div>
    </div>

    <script>
        document.getElementById('resetForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            const submitButton = document.getElementById('submitButton');
            
            // Clear any previous error messages
            const errorDiv = document.querySelector('.text-red-700');
            if (errorDiv) {
                errorDiv.remove();
            }

            // Validate password length
            if (password.length < 8) {
                showError('Password must be at least 8 characters long.');
                return;
            }

            // Validate password match
            if (password !== confirmPassword) {
                showError('Passwords do not match.');
                return;
            }

            // Disable submit button to prevent double submission
            submitButton.disabled = true;
            submitButton.innerHTML = 'Processing...';

            // Submit the form
            this.submit();
        });

        function showError(message) {
            const errorDiv = document.createElement('div');
            errorDiv.className = 'bg-red-100 border-red-400 text-red-700 px-4 py-3 rounded-lg mb-4 border';
            errorDiv.textContent = message;
            
            const form = document.getElementById('resetForm');
            form.insertBefore(errorDiv, form.firstChild);
        }
    </script>
</body>
</html>