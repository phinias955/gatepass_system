<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Applicant') {
    header('Location: ../index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title ?? 'Applicant Dashboard'; ?> - Gate Pass System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="../assets/main.css">
    <style>
        .required:after {
            content: " *";
            color: red;
        }
    </style>
</head>
<body class="font-sans antialiased bg-gray-50">