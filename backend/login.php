<?php
session_start();
require_once __DIR__ . '/../config/config.php'; // Load environment variables
require_once 'db.php';

// Add error reporting if in development mode
if (APP_DEBUG) {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
}

// Check if the user is submitting the login form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'];

    try {
        // Check if user exists
        $stmt = $pdo->prepare("SELECT * FROM Users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user) {
            if (password_verify($password, $user['password'])) {
                // Check if account is locked
                if ($user['account_locked'] == 1) {
                    $_SESSION['error'] = "Your account is locked. Please contact admin.";
                    header('Location: ' . APP_URL . '/index.php');
                    exit;
                }

                // Store common session variables
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['name'] = $user['name'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['last_login'] = time();

                // Role-based redirection
                switch ($user['role']) {
                    case 'Admin':
                        // Generate OTP for admin
                        $otp = rand(100000, 999999);
                        $expiry = date("Y-m-d H:i:s", strtotime("+" . OTP_TIMEOUT . " minutes"));

                        // Update OTP and expiry in DB
                        $stmt = $pdo->prepare("UPDATE Users SET otp_code = ?, otp_expiry = ? WHERE email = ?");
                        $stmt->execute([$otp, $expiry, $email]);

                        // Send OTP via email using PHPMailer
                        $mail = new PHPMailer\PHPMailer\PHPMailer(true);
                        try {
                            $mail->isSMTP();
                            $mail->Host = SMTP_HOST;
                            $mail->SMTPAuth = true;
                            $mail->Username = SMTP_USERNAME;
                            $mail->Password = SMTP_PASSWORD;
                            $mail->SMTPSecure = 'tls'; // Changed from PHPMailer::ENCRYPTION_STARTTLS to 'tls' for compatibility
                            $mail->Port = SMTP_PORT;

                            $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
                            $mail->addAddress($email);

                            $mail->isHTML(true);
                            $mail->Subject = APP_NAME . ' - Admin Login OTP';
                            $mail->Body = "Your OTP code is: <strong>{$otp}</strong>";

                            $mail->send();
                        } catch (Exception $e) {
                            error_log("Email error: " . $mail->ErrorInfo);
                        }

                        header('Location: ' . APP_URL . '/backend/verify_otp.php');
                        break;

                    case 'HoD':
                        header('Location: ' . APP_URL . '/hod/index.php');
                        break;

                    case 'Estate Officer':
                        header('Location: ' . APP_URL . '/estate/index.php');
                        break;

                    case 'Gate Inspector':
                        header('Location: ' . APP_URL . '/gateInspector/index.php');
                        break;

                    case 'Applicant':
                        header('Location: ' . APP_URL . '/applicant/index.php');
                        break;

                    default:
                        $_SESSION['error'] = "Invalid user role!";
                        header('Location: ' . APP_URL . '/index.php');
                        break;
                }
                exit;
            } else {
                $_SESSION['error'] = "Invalid email or password!";
            }
        } else {
            $_SESSION['error'] = "Invalid email or password!";
        }
    } catch (PDOException $e) {
        if (APP_DEBUG) {
            error_log("Login error: " . $e->getMessage());
        }
        $_SESSION['error'] = "System error. Please try again later.";
    }
    
    header('Location: ' . APP_URL . '/index.php');
    exit;
}

// If accessed directly without POST
header('Location: ' . APP_URL . '/index.php');
exit;
?>