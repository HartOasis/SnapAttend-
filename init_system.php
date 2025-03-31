<?php
// Include configuration
require_once 'config/config.php';

// Create necessary directories
$directories = [
    'data',
    'uploads',
    'uploads/attendance_images'
];

foreach ($directories as $dir) {
    if (!file_exists($dir)) {
        mkdir($dir, 0755, true);
        echo "Created directory: $dir<br>";
    } else {
        echo "Directory already exists: $dir<br>";
    }
}

// Initialize JSON files if they don't exist
$jsonFiles = [
    EMPLOYEES_JSON => [
        [
            "id" => 1,
            "employee_id" => "EMP001",
            "first_name" => "John",
            "last_name" => "Doe",
            "email" => "john.doe@example.com",
            "password" => "password",
            "position" => "Software Developer",
            "department" => "IT",
            "created_at" => date("Y-m-d H:i:s"),
            "updated_at" => date("Y-m-d H:i:s")
        ]
    ],
    ADMINS_JSON => [
        [
            "id" => 1,
            "username" => "admin",
            "password" => "password",
            "email" => "admin@example.com",
            "name" => "System Administrator",
            "created_at" => date("Y-m-d H:i:s"),
            "updated_at" => date("Y-m-d H:i:s")
        ]
    ],
    ATTENDANCE_JSON => []
];

foreach ($jsonFiles as $file => $defaultData) {
    if (!file_exists($file)) {
        $jsonData = json_encode($defaultData, JSON_PRETTY_PRINT);
        file_put_contents($file, $jsonData);
        echo "Created JSON file: $file<br>";
    } else {
        echo "JSON file already exists: $file<br>";
    }
}

echo "<br>System initialization complete!<br>";
echo "<p>Default login credentials:</p>";
echo "<ul>";
echo "<li>Employee Login: EMP001 / password</li>";
echo "<li>Admin Login: admin / password</li>";
echo "</ul>";
echo "<p><a href='index.php'>Go to Employee Login</a> | <a href='admin/login.php'>Go to Admin Login</a></p>";
?>
