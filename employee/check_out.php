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
    try {
        // Check if already checked in today
        if (!hasCheckedInToday($employeeId)) {
            echo json_encode(['success' => false, 'message' => 'You need to check in before you can check out']);
            exit;
        }
        
        // Get today's attendance
        $todayAttendance = getTodayAttendance($employeeId);
        
        // Check if already checked out
        if (isset($todayAttendance['check_out']) && !empty($todayAttendance['check_out'])) {
            echo json_encode(['success' => false, 'message' => 'You have already checked out today']);
            exit;
        }
        
        // Get image data
        $imageData = isset($_POST['image']) ? $_POST['image'] : '';
        
        if (empty($imageData)) {
            echo json_encode(['success' => false, 'message' => 'No image data provided']);
            exit;
        }
        
        // Save the image
        $imagePath = saveAttendanceImage($imageData, $employeeId, 'check_out');
        
        if (!$imagePath) {
            echo json_encode(['success' => false, 'message' => 'Failed to save image. Please try again.']);
            exit;
        }
        
        // Record check-out
        if (recordCheckOut($employeeId, $imagePath)) {
            echo json_encode(['success' => true, 'message' => 'Check-out recorded successfully']);
        } else {
            // If check-out fails, try to delete the saved image
            $fullPath = __DIR__ . '/../uploads/attendance_images/' . $imagePath;
            if (file_exists($fullPath)) {
                unlink($fullPath);
            }
            echo json_encode(['success' => false, 'message' => 'Failed to record check-out. Please try again.']);
        }
    } catch (Exception $e) {
        // Log the error
        error_log('Check-out error: ' . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'An error occurred: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>
