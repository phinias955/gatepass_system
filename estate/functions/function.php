<?php
/**
 * Logs user actions in the system
 * 
 * @param PDO $pdo Database connection
 * @param int $user_id ID of the user performing the action
 * @param int $pass_id ID of the gate pass being acted upon
 * @param string $action_type Type of action (e.g., 'Viewed', 'Granted', 'Denied')
 * @return bool Returns true on success, false on failure
 */
function logAction($pdo, $user_id, $pass_id, $action_type) {
    try {
        // Generate description based on action type
        $description = "Gate pass #" . sprintf('%03d', $pass_id) . " was " . strtolower($action_type);
        
        // Insert into activity_log
        $query = "INSERT INTO activity_log (
            user_id, 
            action_type, 
            action_description, 
            related_id, 
            created_at
        ) VALUES (
            :user_id,
            :action_type,
            :description,
            :pass_id,
            CURRENT_TIMESTAMP
        )";

        $stmt = $pdo->prepare($query);
        $result = $stmt->execute([
            'user_id' => $user_id,
            'action_type' => $action_type,
            'description' => $description,
            'pass_id' => $pass_id
        ]);

        return $result;

    } catch (PDOException $e) {
        error_log("Error in logAction: " . $e->getMessage());
        return false;
    }
}

function getApplicantDetails($pass) {
    return [
        'Name' => $pass['applicant_name'],
        'Email' => $pass['applicant_email'],
        'Phone' => $pass['applicant_phone'],
        'Department' => $pass['department_name']
    ];
}

function getPassDetails($pass) {
    return [
        'Pass ID' => 'GP' . sprintf('%03d', $pass['pass_id']),
        'Submission Date' => date('M d, Y', strtotime($pass['date_submitted'])),
        'Vehicle Reg.' => $pass['vehicle_registration'],
        'Movement Reason' => $pass['movement_reason'],
        'Status' => $pass['final_status']
    ];
}

function logError($message) {
    // Log to a file
    $logFile = __DIR__ . '/error_log.txt';
    $timestamp = date('Y-m-d H:i:s');
    $formattedMessage = "[$timestamp] $message" . PHP_EOL;
    
    // Append the error message to the log file
    file_put_contents($logFile, $formattedMessage, FILE_APPEND);

    // Alternatively, log to PHP's error log
    // error_log($formattedMessage);
}


function updatePassStatus($pdo, $pass_id, $estate_status, $final_status) {
    $update_query = "UPDATE gate_pass SET 
        estate_office_status = :estate_status,
        final_status = :final_status
    WHERE pass_id = :pass_id";
    
    $stmt = $pdo->prepare($update_query);
    return $stmt->execute([
        'estate_status' => $estate_status,
        'final_status' => $final_status,
        'pass_id' => $pass_id
    ]);
}

function handlePostAction($pdo, $post_data, $user_id) {
    if (!isset($post_data['action']) || !isset($post_data['pass_id'])) {
        throw new Exception('Invalid action parameters');
    }

    $action = $post_data['action'];
    $pass_id = filter_var($post_data['pass_id'], FILTER_SANITIZE_NUMBER_INT);
    
    // Start transaction
    $pdo->beginTransaction();
    
    try {
        switch ($action) {
            case 'grant':
                $status = updatePassStatus($pdo, $pass_id, 'Granted', 'Approved');
                $action_type = 'Granted';
                break;
            case 'deny':
                $status = updatePassStatus($pdo, $pass_id, 'Not Granted', 'Rejected');
                $action_type = 'Denied';
                break;
            default:
                throw new Exception('Invalid action type');
        }

        // Log the action
        logAction($pdo, $user_id, $pass_id, $action_type);
        
        // Commit transaction
        $pdo->commit();
        
        $_SESSION['success_message'] = "Gate pass successfully {$action_type}.";
        
    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
}






/**
 * Helper Functions
 */

 function getGatePassDetails($pdo, $pass_id) {
    $pass_query = "SELECT 
        g.*,
        CONCAT(u.fname, ' ', u.l_name) as applicant_name,
        u.email as applicant_email,
        u.phone as applicant_phone,
        d.department_name,
        m.reason_name as movement_reason,
        (SELECT CONCAT(fname, ' ', l_name) FROM users WHERE user_id = g.applicant_id) as hod_name,
        (
            SELECT COUNT(*) 
            FROM activity_log 
            WHERE related_id = g.pass_id AND action_type = 'View'
        ) as view_count
    FROM gate_pass g
    JOIN users u ON g.applicant_id = u.user_id
    JOIN departments d ON u.department_id = d.department_id
    JOIN movement_reason m ON g.reason_for_movement = m.reason_id
    WHERE g.pass_id = :pass_id";

    $stmt = $pdo->prepare($pass_query);
    $stmt->execute(['pass_id' => $pass_id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function getGatePassGoods($pdo, $pass_id) {
    $goods_query = "SELECT 
        goods_id,
        pass_id,
        item_description as item_name,
        quantity,
        remarks as description,
        purpose
    FROM goods 
    WHERE pass_id = :pass_id
    ORDER BY goods_id ASC";
    
    $stmt = $pdo->prepare($goods_query);
    $stmt->execute(['pass_id' => $pass_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

?>