<?php
session_start();
require_once 'config/config.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Gate Pass System</title>
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
        <!-- Background Pattern -->
        <div class="absolute inset-0 z-0 opacity-20">
            <!-- <div class="absolute inset-0" style="background-image: url('data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.4'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E');">
            </div> -->
        </div>

        <div class="max-w-md w-full glass-effect rounded-2xl shadow-2xl p-8 relative z-10">
            <!-- Logo -->
            <div class="text-center mb-8">
                <div class="flex justify-center mb-4">
                    <img src="images/logo.png" alt="Logo" class="h-20 w-20 object-contain">
                </div>
                <h2 class="text-3xl font-bold text-gray-800">Gate Pass System</h2>
                <p class="text-gray-600 mt-2">Sign in to your account</p>
            </div>

            <?php if (isset($_SESSION['error'])): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg mb-4">
                <?php 
                echo $_SESSION['error'];
                unset($_SESSION['error']);
                ?>
            </div>
            <?php endif; ?>

            <form method="POST" action="<?php echo APP_URL; ?>/backend/login.php" class="space-y-6">
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

                <div>
                    <label class="block text-gray-700 text-sm font-semibold mb-2" for="password">
                        Password
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
                            placeholder="Enter your password">
                    </div>
                </div>

                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <input type="checkbox" id="remember" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                        <label for="remember" class="ml-2 block text-sm text-gray-700">
                            Remember me
                        </label>
                    </div>
                    <a href="views/forgot-password.php" class="text-sm font-medium text-blue-600 hover:text-blue-500">
                        Forgot Password?
                    </a>
                </div>

                <button 
                    type="submit"
                    class="w-full flex justify-center py-3 px-4 border border-transparent rounded-lg shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors duration-200">
                    Sign In
                </button>
            </form>

            <div class="mt-6 text-center text-sm">
                <p class="text-gray-600">
                    Need help? Contact 
                    <a href="mailto:support@example.com" class="font-medium text-blue-600 hover:text-blue-500">
                        support
                    </a>
                </p>
            </div>
        </div>
    </div>
</body>
</html>