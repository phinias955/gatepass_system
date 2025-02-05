<?php
session_start();
require_once 'db.php';

// Enable error logging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = filter_var($_POST['token'], FILTER_SANITIZE_STRING);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    try {
        // Log the received token
        error_log("Processing password reset for token: " . $token);

        // Validate passwords match
        if ($password !== $confirm_password) {
            $_SESSION['message'] = "Passwords do not match.";
            $_SESSION['message_type'] = "error";
            header('Location: ../views/reset-password.php?token=' . $token);
            exit;
        }

        // Validate password length
        if (strlen($password) < 8) {
            $_SESSION['message'] = "Password must be at least 8 characters long.";
            $_SESSION['message_type'] = "error";
            header('Location: ../views/reset-password.php?token=' . $token);
            exit;
        }

        // Check token validity
        $stmt = $pdo->prepare("SELECT * FROM users WHERE reset_token = ? AND reset_token_expiry > NOW()");
        $stmt->execute([$token]);
        $user = $stmt->fetch();

        // Log token check results
        error_log("Token check - User found: " . ($user ? "Yes" : "No"));
        if ($user) {
            error_log("Token expiry: " . $user['reset_token_expiry']);
            error_log("Current time: " . date('Y-m-d H:i:s'));
        }

        if ($user) {
            // Hash new password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            // Begin transaction
            $pdo->beginTransaction();

            try {
                // Update password and clear reset token
                $stmt = $pdo->prepare("UPDATE users SET password = ?, reset_token = NULL, reset_token_expiry = NULL WHERE user_id = ?");
                $result = $stmt->execute([$hashed_password, $user['user_id']]);

                if (!$result) {
                    throw new Exception("Failed to update password");
                }

                // Log activity
                $log_stmt = $pdo->prepare("INSERT INTO activity_log (user_id, action_type, action_description) VALUES (?, 'PASSWORD_RESET', 'Password reset completed')");
                $log_stmt->execute([$user['user_id']]);

                // Commit transaction
                $pdo->commit();

                $_SESSION['message'] = "Password has been reset successfully. You can now login with your new password.";
                $_SESSION['message_type'] = "success";
                header('Location: ../index.php');
                exit;
            } catch (Exception $e) {
                // Rollback transaction on error
                $pdo->rollBack();
                error_log("Transaction failed: " . $e->getMessage());
                throw $e;
            }
        } else {
            error_log("Invalid token or expired: " . $token);
            $_SESSION['message'] = "Invalid or expired reset token. Please request a new password reset.";
            $_SESSION['message_type'] = "error";
            header('Location: ../views/forgot-password.php');
            exit;
        }
    } catch (Exception $e) {
        error_log("Password Reset Error: " . $e->getMessage());
        error_log("Stack trace: " . $e->getTraceAsString());
        $_SESSION['message'] = "An error occurred. Please try again later.";
        $_SESSION['message_type'] = "error";
        header('Location: ../views/forgot-password.php');
        exit;
    }
}

// If accessed directly without POST
header('Location: ../views/forgot-password.php');
exit;
?>