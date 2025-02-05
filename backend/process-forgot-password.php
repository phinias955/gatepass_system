<?php
session_start();
require_once 'db.php';
require_once '../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);

    try {
       
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user) {
            
            $otp = sprintf("%06d", mt_rand(1, 999999));
            
           
            $expiry = date('Y-m-d H:i:s', strtotime('+15 minutes'));
            
          
            error_log("Generated OTP: " . $otp);
            error_log("Expiry time: " . $expiry);
       
            $update_stmt = $pdo->prepare("UPDATE users SET otp_code = ?, otp_expiry = ? WHERE user_id = ?");
            $result = $update_stmt->execute([$otp, $expiry, $user['user_id']]);

            if ($result) {
              
                $mail = new PHPMailer(true);
                try {
                    $mail->isSMTP();
                    $mail->Host = 'mail.luxurycarrenter.co.tz';
                    $mail->SMTPAuth = true;
                    $mail->Username = 'developer@luxurycarrenter.co.tz';
                    $mail->Password = 'Phini@1234.';
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                    $mail->Port = 587;

                    $mail->setFrom('norepray-email@gmail.com', 'Gate Pass System');
                    $mail->addAddress($email);

                    $mail->isHTML(true);
                    $mail->Subject = 'Password Reset OTP';
                    $mail->Body = "
                        <h2>Password Reset OTP</h2>
                        <p>Hello " . htmlspecialchars($user['name']) . ",</p>
                        <p>Your OTP for password reset is: <strong>{$otp}</strong></p>
                        <p>This OTP will expire in 15 minutes.</p>
                        <p>If you didn't request this, please ignore this email.</p>
                        <br>
                        <p>Best regards,<br>Gate Pass System</p>
                    ";
                    $mail->AltBody = "Your OTP for password reset is: {$otp}";

                    $mail->send();
                    error_log("OTP email sent successfully to: " . $email);

                    $_SESSION['reset_email'] = $email;
                    $_SESSION['message'] = "OTP has been sent to your email.";
                    $_SESSION['message_type'] = "success";
                    header('Location: ../views/verify-otp.php');
                    exit;
                } catch (Exception $e) {
                    error_log("Email sending failed: " . $mail->ErrorInfo);
                    throw new Exception("Failed to send OTP email: " . $mail->ErrorInfo);
                }
            }
        } else {
            // Don't reveal if email exists or not for security
            $_SESSION['message'] = "If your email is registered, you will receive an OTP.";
            $_SESSION['message_type'] = "success";
            header('Location: ../views/forgot-password.php');
            exit;
        }
    } catch (Exception $e) {
        error_log("Password reset error: " . $e->getMessage());
        $_SESSION['message'] = "An error occurred. Please try again later.";
        $_SESSION['message_type'] = "error";
        header('Location: ../views/forgot-password.php');
        exit;
    }
}

header('Location: ../views/forgot-password.php');
exit;
?>