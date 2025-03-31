<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include database configuration
require_once __DIR__ . '/../config/config.php';

/**
 * Sanitize user input
 * @param string $data
 * @return string
 */
function sanitize($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

/**
 * Validate email format
 * @param string $email
 * @return bool
 */
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

/**
 * Check if user is logged in
 * @return bool
 */
function isLoggedIn() {
    return isset($_SESSION['employee_id']);
}

/**
 * Check if admin is logged in
 * @return bool
 */
function isAdminLoggedIn() {
    return isset($_SESSION['admin_id']);
}

/**
 * Redirect to a specific page
 * @param string $location
 */
function redirect($location) {
    header("Location: $location");
    exit;
}

/**
 * Read JSON data from file
 * @param string $file
 * @return array
 */
function readJsonFile($file) {
    if (!file_exists($file)) {
        return [];
    }
    
    $jsonData = file_get_contents($file);
    return json_decode($jsonData, true) ?: [];
}

/**
 * Write data to JSON file
 * @param string $file
 * @param array $data
 * @return bool
 */
function writeJsonFile($file, $data) {
    $jsonData = json_encode($data, JSON_PRETTY_PRINT);
    return file_put_contents($file, $jsonData) !== false;
}

/**
 * Get employee by ID
 * @param int $id
 * @return array|null
 */
function getEmployeeById($id) {
    $employees = readJsonFile(EMPLOYEES_JSON);
    
    foreach ($employees as $employee) {
        if ($employee['id'] == $id) {
            return $employee;
        }
    }
    
    return null;
}

/**
 * Get employee by employee_id and password
 * @param string $employeeId
 * @param string $password
 * @return array|null
 */
function getEmployeeByCode($employeeId, $password = null) {
    $employees = readJsonFile(EMPLOYEES_JSON);
    
    foreach ($employees as $employee) {
        // If password is provided, check both employee_id and password
        if ($password !== null) {
            if ($employee['employee_id'] == $employeeId && $employee['password'] == $password) {
                return $employee;
            }
        } else {
            // If no password provided, just check employee_id
            if ($employee['employee_id'] == $employeeId) {
                return $employee;
            }
        }
    }
    
    return null;
}

/**
 * Get admin by username
 * @param string $username
 * @param string $password
 * @return array|null
 */
function getAdminByUsername($username, $password) {
    $admins = readJsonFile(ADMINS_JSON);
    
    foreach ($admins as $admin) {
        if ($admin['username'] == $username && $admin['password'] == $password) {
            return $admin;
        }
    }
    
    return null;
}

/**
 * Get current employee information
 * @return array|null
 */
function getCurrentEmployee() {
    if (!isLoggedIn()) {
        return null;
    }
    
    $employeeId = $_SESSION['employee_id'];
    return getEmployeeById($employeeId);
}

/**
 * Check if employee has already checked in today
 * @param int $employeeId
 * @return bool
 */
function hasCheckedInToday($employeeId) {
    $records = readJsonFile(ATTENDANCE_JSON);
    $today = date('Y-m-d');
    
    foreach ($records as $record) {
        if ($record['employee_id'] == $employeeId && 
            date('Y-m-d', strtotime($record['check_in'])) == $today && 
            empty($record['check_out'])) {
            return true;
        }
    }
    
    return false;
}

/**
 * Get today's attendance record for an employee
 * @param int $employeeId
 * @return array|null
 */
function getTodayAttendance($employeeId) {
    $records = readJsonFile(ATTENDANCE_JSON);
    $today = date('Y-m-d');
    
    foreach ($records as $record) {
        if ($record['employee_id'] == $employeeId && 
            date('Y-m-d', strtotime($record['check_in'])) == $today) {
            return $record;
        }
    }
    
    return null;
}

/**
 * Save image with timestamp
 * @param string $base64Image
 * @param string $employeeId
 * @param string $type (check_in or check_out)
 * @return string|false
 */
function saveAttendanceImage($base64Image, $employeeId, $type) {
    // Create directory if it doesn't exist
    $uploadDir = '../uploads/attendance_images/';
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }
    
    // Extract the base64 encoded image
    $image_parts = explode(";base64,", $base64Image);
    $image_type_aux = explode("image/", $image_parts[0]);
    $image_type = $image_type_aux[1];
    $image_base64 = base64_decode($image_parts[1]);
    
    // Generate filename
    $filename = $employeeId . '_' . $type . '_' . date('Y-m-d_H-i-s') . '.' . $image_type;
    $file = $uploadDir . $filename;
    
    // Create image from base64
    $image = imagecreatefromstring($image_base64);
    
    if (!$image) {
        return false;
    }
    
    // Get image dimensions
    $width = imagesx($image);
    $height = imagesy($image);
    
    // Add timestamp to the image
    $timestamp = date('Y-m-d H:i:s');
    $textColor = imagecolorallocate($image, 255, 255, 255); // White text
    $shadowColor = imagecolorallocate($image, 0, 0, 0); // Black shadow
    $fontSize = 3;
    $fontX = $width - 200; // Position in bottom right
    $fontY = $height - 10;
    
    // Add shadow effect for better readability
    imagestring($image, $fontSize, $fontX + 1, $fontY + 1, $timestamp, $shadowColor);
    imagestring($image, $fontSize, $fontX, $fontY, $timestamp, $textColor);
    
    // Save the image with timestamp
    switch ($image_type) {
        case 'jpeg':
        case 'jpg':
            imagejpeg($image, $file, 90);
            break;
        case 'png':
            imagepng($image, $file, 9);
            break;
        case 'gif':
            imagegif($image, $file);
            break;
        default:
            imagejpeg($image, $file, 90);
    }
    
    // Free memory
    imagedestroy($image);
    
    if (file_exists($file)) {
        return $filename;
    }
    
    return false;
}

/**
 * Record check-in
 * @param int $employeeId
 * @param string $imagePath
 * @return bool
 */
function recordCheckIn($employeeId, $imagePath) {
    $records = readJsonFile(ATTENDANCE_JSON);
    
    $newRecord = [
        'id' => generateNewId($records),
        'employee_id' => $employeeId,
        'check_in' => date('Y-m-d H:i:s'),
        'check_out' => null,
        'check_in_image' => $imagePath,
        'check_out_image' => null,
        'status' => 'present',
        'notes' => '',
        'created_at' => date('Y-m-d H:i:s'),
        'updated_at' => date('Y-m-d H:i:s')
    ];
    
    $records[] = $newRecord;
    
    return writeJsonFile(ATTENDANCE_JSON, $records);
}

/**
 * Record check-out
 * @param int $employeeId
 * @param string $imagePath
 * @return bool
 */
function recordCheckOut($employeeId, $imagePath) {
    $records = readJsonFile(ATTENDANCE_JSON);
    $today = date('Y-m-d');
    $updated = false;
    
    foreach ($records as &$record) {
        if ($record['employee_id'] == $employeeId && 
            date('Y-m-d', strtotime($record['check_in'])) == $today) {
            $record['check_out'] = date('Y-m-d H:i:s');
            $record['check_out_image'] = $imagePath;
            $record['updated_at'] = date('Y-m-d H:i:s');
            $updated = true;
            break;
        }
    }
    
    if ($updated) {
        return writeJsonFile(ATTENDANCE_JSON, $records);
    }
    
    return false;
}

/**
 * Add or update notes for today's attendance
 * @param int $employeeId
 * @param string $notes
 * @return bool
 */
function updateAttendanceNotes($employeeId, $notes) {
    $records = readJsonFile(ATTENDANCE_JSON);
    $today = date('Y-m-d');
    $updated = false;
    
    foreach ($records as &$record) {
        if ($record['employee_id'] == $employeeId && 
            date('Y-m-d', strtotime($record['check_in'])) == $today) {
            $record['notes'] = $notes;
            $record['updated_at'] = date('Y-m-d H:i:s');
            $updated = true;
            break;
        }
    }
    
    if ($updated) {
        return writeJsonFile(ATTENDANCE_JSON, $records);
    }
    
    return false;
}

/**
 * Get formatted date and time in Pakistan timezone
 * @return string
 */
function getPakistanDateTime() {
    $timezone = new DateTimeZone('Asia/Karachi');
    $date = new DateTime('now', $timezone);
    return $date->format('d-m-Y h:i:s A');
}

/**
 * Get all employees
 * 
 * @return array Array of all employees
 */
function getAllEmployees() {
    return readJsonFile(EMPLOYEES_JSON);
}

/**
 * Get attendance records within a date range and optionally for a specific employee
 * 
 * @param string $startDate Start date (Y-m-d)
 * @param string $endDate End date (Y-m-d)
 * @param int|null $employeeId Optional employee ID
 * @return array Array of attendance records
 */
function getAttendanceRecords($startDate, $endDate, $employeeId = null) {
    $records = readJsonFile(ATTENDANCE_JSON);
    $employees = readJsonFile(EMPLOYEES_JSON);
    $result = [];
    
    // Create a lookup array for employee names
    $employeeNames = [];
    foreach ($employees as $employee) {
        $employeeNames[$employee['id']] = $employee['first_name'] . ' ' . $employee['last_name'];
        $employeeIds[$employee['id']] = $employee['employee_id'];
    }
    
    foreach ($records as $record) {
        $recordDate = date('Y-m-d', strtotime($record['check_in']));
        
        // Check if the record is within the date range
        if ($recordDate >= $startDate && $recordDate <= $endDate) {
            // If employee ID is specified, only include records for that employee
            if ($employeeId === null || $record['employee_id'] == $employeeId) {
                // Add employee name to the record
                $record['employee_name'] = isset($employeeNames[$record['employee_id']]) ? 
                    $employeeNames[$record['employee_id']] : 'Unknown';
                
                // Add employee ID (code) to the record
                $record['emp_id'] = isset($employeeIds[$record['employee_id']]) ? 
                    $employeeIds[$record['employee_id']] : 'Unknown';
                
                $result[] = $record;
            }
        }
    }
    
    return $result;
}

/**
 * Delete an attendance image
 * 
 * @param int $recordId Record ID
 * @param string $type Image type (check_in or check_out)
 * @return bool Success status
 */
function deleteAttendanceImage($recordId, $type) {
    $records = readJsonFile(ATTENDANCE_JSON);
    $updated = false;
    $imagePath = '';
    
    foreach ($records as &$record) {
        if ($record['id'] == $recordId) {
            // Store the image path before removing it
            if ($type === 'check_in') {
                $imagePath = $record['check_in_image'];
                $record['check_in_image'] = null;
            } else if ($type === 'check_out') {
                $imagePath = $record['check_out_image'];
                $record['check_out_image'] = null;
            }
            
            $record['updated_at'] = date('Y-m-d H:i:s');
            $updated = true;
            break;
        }
    }
    
    if ($updated) {
        // Update the records file
        if (writeJsonFile(ATTENDANCE_JSON, $records)) {
            // Delete the actual image file if it exists
            if (!empty($imagePath)) {
                $fullPath = __DIR__ . '/../uploads/attendance_images/' . $imagePath;
                if (file_exists($fullPath)) {
                    unlink($fullPath);
                }
            }
            return true;
        }
    }
    
    return false;
}

/**
 * Generate a new ID for records
 * @param array $records
 * @return int
 */
function generateNewId($records) {
    $maxId = 0;
    
    foreach ($records as $record) {
        if (isset($record['id']) && $record['id'] > $maxId) {
            $maxId = $record['id'];
        }
    }
    
    return $maxId + 1;
}

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    
    switch ($action) {
        case 'delete_image':
            // Check if record_id and type are set
            if (isset($_POST['record_id']) && isset($_POST['type'])) {
                $recordId = (int)$_POST['record_id'];
                $type = $_POST['type']; // 'check_in' or 'check_out'
                
                // Get attendance records
                $records = readJsonFile(ATTENDANCE_JSON);
                $recordFound = false;
                
                foreach ($records as &$record) {
                    if ($record['id'] == $recordId) {
                        $recordFound = true;
                        
                        // Get image path
                        $imageField = $type . '_image';
                        $imagePath = isset($record[$imageField]) ? $record[$imageField] : '';
                        
                        if (!empty($imagePath)) {
                            // Delete the image file
                            $fullPath = '../uploads/attendance_images/' . $imagePath;
                            if (file_exists($fullPath)) {
                                unlink($fullPath);
                            }
                            
                            // Remove image path from record
                            $record[$imageField] = null;
                            
                            // Save updated records
                            if (writeJsonFile(ATTENDANCE_JSON, $records)) {
                                echo 'success';
                                exit;
                            }
                        }
                        
                        break;
                    }
                }
                
                if (!$recordFound) {
                    echo 'Record not found';
                    exit;
                }
            }
            
            echo 'Invalid request';
            exit;
            break;
            
        // Add other actions here as needed
    }
}
?>
