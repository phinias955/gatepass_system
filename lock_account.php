<?php
session_start();
include('../backend/db.php'); // Database connection file

// Admin should be the one locking accounts, so check if the user is an admin
if ($_SESSION['role'] !== 'admin') {
    die("Unauthorized access!");
}

$user_email = $_GET['email'];  // Get email from URL

// Lock the account by setting account_locked to TRUE
$stmt = $pdo->prepare("UPDATE Users SET account_locked = 1 WHERE email = ?");
$stmt->execute([$user_email]);

echo "The account has been locked successfully!";
?>
