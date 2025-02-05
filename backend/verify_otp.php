<?php
session_start();
include('../backend/db.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $otp = $_POST['otp'];

    // Retrieve user info from DB
    $stmt = $pdo->prepare("SELECT * FROM Users WHERE email = ?");
    $stmt->execute([$_SESSION['email']]);
    $user = $stmt->fetch();

    if ($user && $user['otp_code'] == $otp && $user['otp_expiry'] > date("Y-m-d H:i:s")) {
        // OTP is valid
        unset($_SESSION['email']); // Clear the email session variable
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['last_login'] = time(); // Set the last login time
        header('Location: ../admin/index.php'); // Redirect to admin dashboard
    } else {
        $_SESSION['error'] = "Invalid or expired OTP!";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify OTP - Gate Pass System</title>
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
            <!-- Logo -->
            <div class="text-center mb-8">
                <div class="flex justify-center mb-4">
                    <img src="../images/logo.png" alt="Logo" class="h-20 w-20 object-contain">
                </div>
                <h2 class="text-3xl font-bold text-gray-800">Verify OTP</h2>
                <p class="text-gray-600 mt-2">Enter the OTP sent to your email</p>
            </div>

            <?php if (isset($_SESSION['error'])): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg mb-4">
                <?php 
                echo $_SESSION['error'];
                unset($_SESSION['error']);
                ?>
            </div>
            <?php endif; ?>

            <form method="POST" class="space-y-6">
                <div>
                    <label class="block text-gray-700 text-sm font-semibold mb-2" for="otp">
                        One-Time Password
                    </label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                            </svg>
                        </div>
                        <input 
                            class="pl-10 w-full px-4 py-2 bg-gray-50 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            id="otp"
                            type="text"
                            name="otp"
                            required
                            minlength="6"
                            maxlength="6"
                            pattern="\d{6}"
                            placeholder="Enter 6-digit OTP"
                            autocomplete="one-time-code">
                    </div>
                    <p class="mt-1 text-sm text-gray-500">Check your email for the OTP code</p>
                </div>

                <button 
                    type="submit"
                    class="w-full flex justify-center py-3 px-4 border border-transparent rounded-lg shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors duration-200">
                    Verify OTP
                </button>

                <div class="flex items-center justify-between">
                    <button 
                        type="button" 
                        onclick="window.location.href='resend_otp.php'"
                        class="text-sm font-medium text-blue-600 hover:text-blue-500">
                        Resend OTP
                    </button>
                    <a href="../index.php" class="text-sm font-medium text-blue-600 hover:text-blue-500">
                        Back to Login
                    </a>
                </div>
            </form>

            <div class="mt-6 text-center text-sm">
                <p class="text-gray-600">
                    OTP will expire in <span class="font-medium text-gray-800" id="timer">10:00</span>
                </p>
            </div>
        </div>
    </div>

    <!-- Timer Script -->
    <script>
        function startTimer(duration, display) {
            let timer = duration, minutes, seconds;
            const interval = setInterval(function () {
                minutes = parseInt(timer / 60, 10);
                seconds = parseInt(timer % 60, 10);

                minutes = minutes < 10 ? "0" + minutes : minutes;
                seconds = seconds < 10 ? "0" + seconds : seconds;

                display.textContent = minutes + ":" + seconds;

                if (--timer < 0) {
                    clearInterval(interval);
                    display.textContent = "Expired";
                    // Redirect to login after expiry
                    window.location.href = '../index.php';
                }
            }, 1000);
        }

        window.onload = function () {
            const tenMinutes = 60 * 10,
                display = document.querySelector('#timer');
            startTimer(tenMinutes, display);
        };
    </script>
</body>
</html>