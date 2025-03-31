<?php
// Start session
session_start();

// Check if employee is logged in
if (!isset($_SESSION['employee_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

// Include functions
require_once 'functions.php';

// Get employee ID
$employeeId = $_SESSION['employee_id'];

// Check if it's a POST request with required parameters
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['record_id']) && isset($_POST['notes'])) {
    try {
        $recordId = $_POST['record_id'];
        $notes = $_POST['notes'];
        
        // Get all attendance records
        $records = readJsonFile(ATTENDANCE_JSON);
        $updated = false;
        $isEmployeeRecord = false;
        
        // Find the record and update notes
        foreach ($records as &$record) {
            if ($record['id'] == $recordId) {
                // Check if this record belongs to the logged-in employee
                if ($record['employee_id'] == $employeeId) {
                    $isEmployeeRecord = true;
                    $record['notes'] = $notes;
                    $record['updated_at'] = date('Y-m-d H:i:s');
                    $updated = true;
                }
                break;
            }
        }
        
        if (!$isEmployeeRecord) {
            echo json_encode(['success' => false, 'message' => 'You can only update your own attendance records']);
            exit;
        }
        
        if ($updated) {
            // Save the updated records
            if (writeJsonFile(ATTENDANCE_JSON, $records)) {
                echo json_encode(['success' => true, 'message' => 'Notes updated successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to save notes. Please try again.']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Record not found']);
        }
    } catch (Exception $e) {
        // Log the error
        error_log('Update notes error: ' . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'An error occurred: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
}
?>
