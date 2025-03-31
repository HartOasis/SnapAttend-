<?php
// Start session
session_start();

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header('Location: index.php');
    exit;
}

// Include functions
require_once '../includes/functions.php';

// Check if it's a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: manage_employees.php?error=' . urlencode('Invalid request method'));
    exit;
}

// Get all employees
$employees = readJsonFile(EMPLOYEES_JSON);

// Check if we're editing an existing employee or adding a new one
$isEditing = !empty($_POST['employee_id_hidden']);
$employeeId = $isEditing ? $_POST['employee_id_hidden'] : null;

// Validate required fields
$requiredFields = ['first_name', 'last_name', 'employee_id', 'email', 'position', 'department'];
foreach ($requiredFields as $field) {
    if (empty($_POST[$field])) {
        header('Location: manage_employees.php?error=' . urlencode('All fields are required'));
        exit;
    }
}

// Check if employee ID already exists (for new employees or when changing ID)
$employeeIdExists = false;
foreach ($employees as $employee) {
    if ($employee['employee_id'] === $_POST['employee_id'] && (!$isEditing || $employee['id'] != $employeeId)) {
        $employeeIdExists = true;
        break;
    }
}

if ($employeeIdExists) {
    header('Location: manage_employees.php?error=' . urlencode('Employee ID already exists'));
    exit;
}

// Check if email already exists (for new employees or when changing email)
$emailExists = false;
foreach ($employees as $employee) {
    if ($employee['email'] === $_POST['email'] && (!$isEditing || $employee['id'] != $employeeId)) {
        $emailExists = true;
        break;
    }
}

if ($emailExists) {
    header('Location: manage_employees.php?error=' . urlencode('Email already exists'));
    exit;
}

// Process profile image upload
$profileImage = null;
if (!empty($_FILES['profile_image']['name'])) {
    // Create upload directory if it doesn't exist
    $uploadDir = '../uploads/profile_images/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    // Generate unique filename
    $fileExtension = pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION);
    $newFileName = uniqid('profile_') . '_' . date('Ymd') . '.' . $fileExtension;
    $targetFile = $uploadDir . $newFileName;
    
    // Check file type
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/jpg'];
    if (!in_array($_FILES['profile_image']['type'], $allowedTypes)) {
        header('Location: manage_employees.php?error=' . urlencode('Only JPG, JPEG, PNG & GIF files are allowed'));
        exit;
    }
    
    // Move uploaded file
    if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $targetFile)) {
        $profileImage = $newFileName;
    } else {
        header('Location: manage_employees.php?error=' . urlencode('Failed to upload image'));
        exit;
    }
}

// If editing, find the employee
$employeeIndex = -1;
if ($isEditing) {
    foreach ($employees as $index => $employee) {
        if ($employee['id'] == $employeeId) {
            $employeeIndex = $index;
            break;
        }
    }
    
    if ($employeeIndex < 0) {
        header('Location: manage_employees.php?error=' . urlencode('Employee not found'));
        exit;
    }
}

// Prepare employee data
$employeeData = [
    'first_name' => $_POST['first_name'],
    'last_name' => $_POST['last_name'],
    'employee_id' => $_POST['employee_id'],
    'email' => $_POST['email'],
    'position' => $_POST['position'],
    'department' => $_POST['department'],
    'updated_at' => date('Y-m-d H:i:s')
];

if ($isEditing) {
    // Update existing employee
    
    // Only update password if provided
    if (!empty($_POST['password'])) {
        $employeeData['password'] = $_POST['password']; // Storing password as plain text as per user preference
    } else {
        // Keep existing password
        $employeeData['password'] = $employees[$employeeIndex]['password'];
    }
    
    // Handle profile image
    if ($profileImage) {
        // Delete old image if exists
        if (!empty($employees[$employeeIndex]['profile_image'])) {
            $oldImagePath = '../uploads/profile_images/' . $employees[$employeeIndex]['profile_image'];
            if (file_exists($oldImagePath)) {
                unlink($oldImagePath);
            }
        }
        $employeeData['profile_image'] = $profileImage;
    } else {
        // Keep existing profile image
        $employeeData['profile_image'] = $employees[$employeeIndex]['profile_image'];
    }
    
    // Preserve ID and created_at
    $employeeData['id'] = $employees[$employeeIndex]['id'];
    $employeeData['created_at'] = $employees[$employeeIndex]['created_at'];
    
    // Update employee in array
    $employees[$employeeIndex] = $employeeData;
    
    $successMessage = "Employee updated successfully!";
} else {
    // Add new employee
    
    // Generate new ID
    $maxId = 0;
    foreach ($employees as $employee) {
        if ($employee['id'] > $maxId) {
            $maxId = $employee['id'];
        }
    }
    $newId = $maxId + 1;
    
    // Set required fields for new employee
    $employeeData['id'] = $newId;
    $employeeData['password'] = !empty($_POST['password']) ? $_POST['password'] : 'password'; // Default password if not provided
    $employeeData['profile_image'] = $profileImage;
    $employeeData['created_at'] = date('Y-m-d H:i:s');
    
    // Add new employee to array
    $employees[] = $employeeData;
    
    $successMessage = "Employee added successfully!";
}

// Save updated employees
if (writeJsonFile(EMPLOYEES_JSON, $employees)) {
    header('Location: manage_employees.php?success=' . urlencode($successMessage));
} else {
    header('Location: manage_employees.php?error=' . urlencode('Failed to save employee data'));
}
exit;
?>
