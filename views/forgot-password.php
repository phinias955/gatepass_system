<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - Gate Pass System</title>
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
        <div class="max-w-md w-full glass-effect rounded-2xl shadow-2xl p-8 relative z-10">
            <!-- Logo -->
            <div class="text-center mb-8">
                <div class="flex justify-center mb-4">
                    <img src="../images/logo.png" alt="Logo" class="h-20 w-20 object-contain">
                </div>
                <h2 class="text-3xl font-bold text-gray-800">Forgot Password</h2>
                <p class="text-gray-600 mt-2">Enter your email to reset your password</p>
            </div>

            <?php if (isset($_SESSION['message'])): ?>
            <div class="<?php echo ($_SESSION['message_type'] == 'success') ? 'bg-green-100 border-green-400 text-green-700' : 'bg-red-100 border-red-400 text-red-700'; ?> px-4 py-3 rounded-lg mb-4">
                <?php 
                echo $_SESSION['message'];
                unset($_SESSION['message']);
                unset($_SESSION['message_type']);
                ?>
            </div>
            <?php endif; ?>

            <form method="POST" action="../backend/process-forgot-password.php" class="space-y-6">
                <div>
                    <label class="block text-gray-700 text-sm font-semibold mb-2" for="email">
                        Email Address
                    </label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207" />
                            </svg>
                        </div>
                        <input 
                            class="pl-10 w-full px-4 py-2 bg-gray-50 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            id="email"
                            type="email"
                            name="email"
                            required
                            placeholder="Enter your email">
                    </div>
                </div>

                <button 
                    type="submit"
                    class="w-full flex justify-center py-3 px-4 border border-transparent rounded-lg shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors duration-200">
                    Send Reset Link
                </button>

                <div class="text-center">
                    <a href="../index.php" class="text-sm font-medium text-blue-600 hover:text-blue-500">
                        Back to Login
                    </a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>