<?php
// Application configuration
define('APP_NAME', 'Modern Attendance System');
define('APP_URL', 'http://localhost/punch');
define('UPLOAD_DIR', '../uploads/attendance_images/');

// JSON Database paths
define('EMPLOYEES_JSON', __DIR__ . '/../data/employees.json');
define('ADMINS_JSON', __DIR__ . '/../data/admins.json');
define('ATTENDANCE_JSON', __DIR__ . '/../data/attendance_records.json');

// Ensure data directory is writable
$dataDir = __DIR__ . '/../data';
if (!is_writable($dataDir)) {
    die("Error: Data directory is not writable. Please check permissions.");
}
?>
