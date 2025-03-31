<?php
// Start session
session_start();

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Not authorized']);
    exit;
}

// Include functions
require_once '../includes/functions.php';

// Check if record ID is provided
if (!isset($_GET['record_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Record ID is required']);
    exit;
}

$recordId = $_GET['record_id'];

// Get all attendance records
$attendanceRecords = readJsonFile(ATTENDANCE_JSON);
$record = null;

// Find the requested record
foreach ($attendanceRecords as $r) {
    if ($r['id'] == $recordId) {
        $record = $r;
        break;
    }
}

if (!$record) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Record not found']);
    exit;
}

// Get employee details
$employees = readJsonFile(EMPLOYEES_JSON);
$employee = null;

foreach ($employees as $e) {
    if ($e['id'] == $record['employee_id']) {
        $employee = $e;
        break;
    }
}

// Add employee details to record
if ($employee) {
    $record['employee_name'] = $employee['first_name'] . ' ' . $employee['last_name'];
    $record['employee_id'] = $employee['employee_id'];
    $record['employee_profile_image'] = $employee['profile_image'] ?? '';
}

// Return record as JSON
header('Content-Type: application/json');
echo json_encode($record);
?>
