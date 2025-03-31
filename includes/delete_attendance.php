<?php
// Start session
session_start();

// Include functions
require_once 'functions.php';

// Check if request is POST and record_id is set
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['record_id'])) {
    $recordId = (int)$_POST['record_id'];
    $response = array();
    
    // Check if user is logged in (either admin or employee)
    if (!isset($_SESSION['admin_id']) && !isset($_SESSION['employee_id'])) {
        $response['success'] = false;
        $response['message'] = 'Unauthorized access';
        echo json_encode($response);
        exit;
    }
    
    // Get the record to check if it exists and belongs to the current employee (if not admin)
    $records = readJsonFile(ATTENDANCE_JSON);
    $recordFound = false;
    $recordIndex = -1;
    $imagesToDelete = array();
    
    foreach ($records as $index => $record) {
        if ($record['id'] == $recordId) {
            $recordFound = true;
            $recordIndex = $index;
            
            // If employee is logged in, check if the record belongs to them
            if (isset($_SESSION['employee_id']) && $record['employee_id'] != $_SESSION['employee_id']) {
                $response['success'] = false;
                $response['message'] = 'You can only delete your own attendance records';
                echo json_encode($response);
                exit;
            }
            
            // Store image paths to delete
            if (!empty($record['check_in_image'])) {
                $imagesToDelete[] = '../uploads/attendance_images/' . $record['check_in_image'];
            }
            if (!empty($record['check_out_image'])) {
                $imagesToDelete[] = '../uploads/attendance_images/' . $record['check_out_image'];
            }
            
            break;
        }
    }
    
    if (!$recordFound) {
        $response['success'] = false;
        $response['message'] = 'Record not found';
        echo json_encode($response);
        exit;
    }
    
    // Remove the record from the array
    array_splice($records, $recordIndex, 1);
    
    // Save the updated records
    if (writeJsonFile(ATTENDANCE_JSON, $records)) {
        // Delete the images
        foreach ($imagesToDelete as $imagePath) {
            if (file_exists($imagePath)) {
                unlink($imagePath);
            }
        }
        
        $response['success'] = true;
        $response['message'] = 'Record deleted successfully';
    } else {
        $response['success'] = false;
        $response['message'] = 'Failed to delete record';
    }
    
    echo json_encode($response);
    exit;
} else {
    // Invalid request
    $response = array(
        'success' => false,
        'message' => 'Invalid request'
    );
    echo json_encode($response);
    exit;
}
?>
