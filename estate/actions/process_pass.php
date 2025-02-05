<?php
require_once '../../config/config.php';
require_once '../../backend/db.php';

// Check if user is logged in and is an Estate Officer
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Estate Officer') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pass_id = filter_input(INPUT_POST, 'pass_id', FILTER_VALIDATE_INT);
    $action = filter_input(INPUT_POST, 'action', FILTER_SANITIZE_STRING);
    
    try {
        $pdo->beginTransaction();

        if ($action === 'grant') {
            $stmt = $pdo->prepare("
                UPDATE gate_pass 
                SET estate_office_status = 'Granted',
                    final_status = 'Approved'
                WHERE pass_id = ?
            ");
            $stmt->execute([$pass_id]);
        } elseif ($action === 'deny') {
            $reason = filter_input(INPUT_POST, 'reason', FILTER_SANITIZE_STRING);
            $stmt = $pdo->prepare("
                UPDATE gate_pass 
                SET estate_office_status = 'Not Granted',
                    final_status = 'Rejected',
                    estate_remarks = ?
                WHERE pass_id = ?
            ");
            $stmt->execute([$reason, $pass_id]);
        }

        // Log the action
        $stmt = $pdo->prepare("
            INSERT INTO activity_log (user_id, action_type, action_description, related_id)
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([
            $_SESSION['user_id'],
            $action === 'grant' ? 'Granted' : 'Denied',
            "Gate pass #{$pass_id} " . ($action === 'grant' ? 'granted' : 'denied') . " by Estate Officer",
            $pass_id
        ]);

        $pdo->commit();
        echo json_encode(['success' => true]);
    } catch (PDOException $e) {
        $pdo->rollBack();
        error_log("Error in process_pass.php: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Database error occurred']);
    }
}
?> 