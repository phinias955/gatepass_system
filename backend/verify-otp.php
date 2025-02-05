<?php
session_start();
require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $otp = filter_var($_POST['otp'], FILTER_SANITIZE_STRING);
    $email = $_SESSION['reset_email'] ?? '';
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    try {
        // Validate passwords match
        if ($new_password !== $confirm_password) {
            $_SESSION['message'] = "Passwords do not match.";
            $_SESSION['message_type'] = "error";
            header('Location: ../views/verify-otp.php');
            exit;
        }

        // Validate password length
        if (strlen($new_password) < 8) {
            $_SESSION['message'] = "Password must be at least 8 characters long.";
            $_SESSION['message_type'] = "error";
            header('Location: ../views/verify-otp.php');
            exit;
        }

        // Verify OTP
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND otp_code = ? ");
        $stmt->execute([$email, $otp]);
        $user = $stmt->fetch();

        if ($user) {
            // Hash new password
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

            // Update password and clear OTP
            $stmt = $pdo->prepare("UPDATE users SET password = ?, otp_code = NULL, otp_expiry = NULL WHERE user_id = ?");
            $result = $stmt->execute([$hashed_password, $user['user_id']]);

            if ($result) {
                // Log activity
                $log_stmt = $pdo->prepare("INSERT INTO activity_log (user_id, action_type, action_description) VALUES (?, 'PASSWORD_RESET', 'Password reset completed via OTP')");
                $log_stmt->execute([$user['user_id']]);

                unset($_SESSION['reset_email']);
                $_SESSION['message'] = "Password has been reset successfully. You can now login with your new password.";
                $_SESSION['message_type'] = "success";
                header('Location: ../index.php');
                exit;
            }
        } else {
            $_SESSION['message'] = "Invalid or expired OTP. Please try again.";
            $_SESSION['message_type'] = "error";
            header('Location: ../views/verify-otp.php');
            exit;
        }
    } catch (Exception $e) {
        error_log("OTP verification error: " . $e->getMessage());
        $_SESSION['message'] = "An error occurred. Please try again later.";
        $_SESSION['message_type'] = "error";
        header('Location: ../views/verify-otp.php');
        exit;
    }
}

header('Location: ../views/forgot-password.php');
exit;
?> 