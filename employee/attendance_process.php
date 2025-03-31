<?php
// Start session
session_start();

// Check if employee is logged in
if (!isset($_SESSION['employee_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

// Include functions
require_once '../includes/functions.php';

// Get employee ID
$employeeId = $_SESSION['employee_id'];

// Check if it's a POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get action type (check_in or check_out)
    $action = isset($_POST['action']) ? $_POST['action'] : '';
    
    // Get image data
    $imageData = isset($_POST['image_data']) ? $_POST['image_data'] : '';
    
    if (empty($action) || empty($imageData)) {
        echo json_encode(['success' => false, 'message' => 'Missing required data']);
        exit;
    }
    
    // Process based on action type
    if ($action === 'check_in') {
        // Check if already checked in today
        if (hasCheckedInToday($employeeId)) {
            echo json_encode(['success' => false, 'message' => 'You have already checked in today']);
            exit;
        }
        
        // Save the image
        $imagePath = saveAttendanceImage($imageData, $employeeId, 'check_in');
        
        if ($imagePath) {
            // Record check-in
            if (recordCheckIn($employeeId, $imagePath)) {
                echo json_encode(['success' => true, 'message' => 'Check-in recorded successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to record check-in']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to save image']);
        }
    } elseif ($action === 'check_out') {
        // Check if already checked in today
        if (!hasCheckedInToday($employeeId)) {
            echo json_encode(['success' => false, 'message' => 'You need to check in first']);
            exit;
        }
        
        // Get today's attendance record
        $todayAttendance = getTodayAttendance($employeeId);
        
        // Check if already checked out
        if ($todayAttendance && !empty($todayAttendance['check_out'])) {
            echo json_encode(['success' => false, 'message' => 'You have already checked out today']);
            exit;
        }
        
        // Save the image
        $imagePath = saveAttendanceImage($imageData, $employeeId, 'check_out');
        
        if ($imagePath) {
            // Record check-out
            if (recordCheckOut($employeeId, $imagePath)) {
                echo json_encode(['success' => true, 'message' => 'Check-out recorded successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to record check-out']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to save image']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>
