<?php
session_start();
require_once 'db.php';
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

try {
    // Check if user email exists in session
    if (!isset($_SESSION['email'])) {
        $_SESSION['error'] = "Session expired. Please login again.";
        header('Location: ../index.php');
        exit;
    }

    $email = $_SESSION['email'];

    // Generate new OTP
    $otp = rand(100000, 999999);
    $expiry = date('Y-m-d H:i:s', strtotime('+' . OTP_TIMEOUT . ' minutes'));

    // Update OTP in database
    $stmt = $pdo->prepare("UPDATE Users SET otp_code = ?, otp_expiry = ? WHERE email = ?");
    $stmt->execute([$otp, $expiry, $email]);

    // Prepare email content
    $emailBody = "
    <html>
    <head>
        <title>New OTP Code</title>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .otp-code { 
                font-size: 24px; 
                font-weight: bold; 
                color: #3b82f6;
                letter-spacing: 2px;
            }
        </style>
    </head>
    <body>
        <div class='container'>
            <h2>New OTP Code</h2>
            <p>Your new OTP code is:</p>
            <p class='otp-code'>{$otp}</p>
            <p>This code will expire in " . OTP_TIMEOUT . " minutes.</p>
            <p>If you didn't request this code, please ignore this email.</p>
            <br>
            <p>Best regards,<br>" . APP_NAME . " Team</p>
        </div>
    </body>
    </html>";

    // Send email using PHPMailer
    $mail = new PHPMailer(true);

    // Server settings
    $mail->isSMTP();
    $mail->Host = SMTP_HOST;
    $mail->SMTPAuth = true;
    $mail->Username = SMTP_USERNAME;
    $mail->Password = SMTP_PASSWORD;
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = SMTP_PORT;

    // Recipients
    $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
    $mail->addAddress($email);

    // Content
    $mail->isHTML(true);
    $mail->Subject = APP_NAME . " - New OTP Code";
    $mail->Body = $emailBody;
    $mail->AltBody = "Your new OTP code is: {$otp}. This code will expire in " . OTP_TIMEOUT . " minutes.";

    $mail->send();
    $_SESSION['success'] = "New OTP has been sent to your email.";

    // Log successful OTP resend if in debug mode
    if (APP_DEBUG) {
        error_log("OTP resent successfully to: " . $email);
    }

} catch (Exception $e) {
    $_SESSION['error'] = "Failed to send new OTP. Please try again.";
    if (APP_DEBUG) {
        error_log("Resend OTP error: " . $e->getMessage());
    }
}

// Redirect back to verify OTP page
header('Location: verify_otp.php');
exit;
?>