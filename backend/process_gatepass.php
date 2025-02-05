<?php
session_start();
require_once '../config/config.php';
require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_SESSION['user_id'])) {
    $_SESSION['error'] = "Invalid request method";
    header('Location: ../applicant/newapp.php');
    exit;
}

try {
    $pdo->beginTransaction();

    // Insert into gate_pass table
    $stmt = $pdo->prepare("
        INSERT INTO gate_pass (
            applicant_id, 
            vehicle_registration, 
            date_submitted,
            reason_for_movement,
            other_reason,
            hod_status,
            final_status
        ) VALUES (?, ?, NOW(), ?, ?, 'Pending', 'Pending')
    ");

    $stmt->execute([
        $_SESSION['user_id'],
        $_POST['vehicle_registration'],
        $_POST['reason_for_movement'],
        isset($_POST['other_reason']) ? $_POST['other_reason'] : null
    ]);

    $pass_id = $pdo->lastInsertId();

    // Insert goods items
    if (isset($_POST['goods']) && is_array($_POST['goods'])) {
        $stmt = $pdo->prepare("
            INSERT INTO goods (
                pass_id, 
                item_description, 
                quantity, 
                remarks
            ) VALUES (?, ?, ?, ?)
        ");

        foreach ($_POST['goods'] as $item) {
            $stmt->execute([
                $pass_id,
                $item['item_description'],
                $item['quantity'],
                $item['remarks'] ?? null
            ]);
        }
    }

    // Log the creation
    $stmt = $pdo->prepare("
        INSERT INTO logs (
            pass_id, 
            action, 
            performed_by
        ) VALUES (?, 'Created', ?)
    ");
    $stmt->execute([$pass_id, $_SESSION['user_id']]);

    $pdo->commit();
    
    $_SESSION['success'] = "Gate pass application submitted successfully";
    header('Location: ../applicant/index.php');
    exit;

} catch (PDOException $e) {
    $pdo->rollBack();
    error_log("Error processing gate pass: " . $e->getMessage());
    $_SESSION['error'] = "Error submitting application. Please try again.";
    header('Location: ../applicant/newapp.php');
    exit;
}
?>