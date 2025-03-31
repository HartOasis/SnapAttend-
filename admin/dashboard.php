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

// Get all employees
$employees = readJsonFile(EMPLOYEES_JSON);

// Set default filter values
$startDate = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d', strtotime('-30 days'));
$endDate = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');
$selectedEmployee = isset($_GET['employee_id']) ? $_GET['employee_id'] : '';

// Get all attendance records
$allAttendanceRecords = readJsonFile(ATTENDANCE_JSON);
$attendanceRecords = [];

// Filter records based on date range and employee
foreach ($allAttendanceRecords as $record) {
    $recordDate = date('Y-m-d', strtotime($record['check_in']));
    
    // Check if record is within date range
    if ($recordDate >= $startDate && $recordDate <= $endDate) {
        // Check if employee filter is applied
        if (empty($selectedEmployee) || $record['employee_id'] == $selectedEmployee) {
            // Add employee name to record
            foreach ($employees as $employee) {
                if ($employee['id'] == $record['employee_id']) {
                    $record['employee_name'] = $employee['first_name'] . ' ' . $employee['last_name'];
                    $record['emp_id'] = $employee['employee_id'];
                    break;
                }
            }
            
            // Add record to filtered records
            $attendanceRecords[] = $record;
        }
    }
}

// Sort records by date (newest first)
usort($attendanceRecords, function($a, $b) {
    return strtotime($b['check_in']) - strtotime($a['check_in']);
});
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Attendance System</title>
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- DataTables -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/jquery.dataTables.min.css">
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <!-- Animation Library -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>
    <style>
        .card-hover {
            transition: all 0.3s ease;
        }
        
        .card-hover:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }
        
        .btn-hover {
            transition: all 0.3s ease;
        }
        
        .btn-hover:hover {
            transform: translateY(-2px);
        }
        
        .floating {
            animation: floating 3s ease-in-out infinite;
        }
        
        @keyframes floating {
            0% { transform: translateY(0px); }
            50% { transform: translateY(-10px); }
            100% { transform: translateY(0px); }
        }
        
        /* DataTables Custom Styling */
        .dataTables_wrapper .dataTables_paginate .paginate_button {
            padding: 0.5em 1em;
            margin: 0 0.2em;
            border: 1px solid #ddd;
            border-radius: 0.375rem;
            background: white;
            transition: all 0.3s ease;
        }
        
        .dataTables_wrapper .dataTables_paginate .paginate_button:hover {
            background: #f3f4f6;
            border-color: #d1d5db;
            transform: translateY(-2px);
        }
        
        .dataTables_wrapper .dataTables_paginate .paginate_button.current {
            background: #4f46e5;
            color: white !important;
            border-color: #4f46e5;
            font-weight: bold;
        }
        
        /* Custom alert styles */
        .custom-alert {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 15px 20px;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            z-index: 1000;
            max-width: 350px;
        }
        
        .alert-success {
            background-color: #10b981;
            color: white;
            border-left: 5px solid #059669;
        }
        
        .alert-error {
            background-color: #ef4444;
            color: white;
            border-left: 5px solid #dc2626;
        }
    </style>
</head>
<body class="bg-gray-50">
    <div class="container mx-auto px-4 py-8">
        <div class="max-w-7xl mx-auto">
            <div class="flex justify-between items-center border-b border-gray-200 pb-4 mb-6">
                <h1 class="text-3xl font-bold text-gray-800 animate__animated animate__fadeInDown">Admin Dashboard</h1>
                <div class="flex space-x-4">
                    <a href="manage_employees.php" class="btn-hover px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg transition-colors duration-200 flex items-center animate__animated animate__fadeIn">
                        <i class="fas fa-users mr-2"></i> Manage Employees
                    </a>
                    <a href="logout.php" class="btn-hover px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-lg transition-colors duration-200 flex items-center animate__animated animate__fadeIn">
                        <i class="fas fa-sign-out-alt mr-2"></i> Logout
                    </a>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                <div class="bg-gradient-to-r from-purple-500 to-indigo-600 rounded-lg shadow-md p-6 text-white card-hover animate__animated animate__fadeInLeft">
                    <div class="flex justify-between items-start">
                        <div>
                            <h3 class="text-xl font-bold mb-2">Total Employees</h3>
                            <p class="text-3xl font-bold"><?php echo count($employees); ?></p>
                        </div>
                        <div class="text-4xl opacity-80 floating">
                            <i class="fas fa-users"></i>
                        </div>
                    </div>
                </div>
                
                <div class="bg-gradient-to-r from-blue-500 to-cyan-500 rounded-lg shadow-md p-6 text-white card-hover animate__animated animate__fadeInUp" style="animation-delay: 0.1s;">
                    <div class="flex justify-between items-start">
                        <div>
                            <h3 class="text-xl font-bold mb-2">Today's Attendance</h3>
                            <p class="text-3xl font-bold"><?php
                                $todayAttendanceCount = 0;
                                foreach ($attendanceRecords as $record) {
                                    if (date('Y-m-d', strtotime($record['check_in'])) === date('Y-m-d')) {
                                        $todayAttendanceCount++;
                                    }
                                }
                                echo $todayAttendanceCount;
                            ?></p>
                        </div>
                        <div class="text-4xl opacity-80 floating">
                            <i class="fas fa-clipboard-check"></i>
                        </div>
                    </div>
                </div>
                
                <div class="bg-gradient-to-r from-green-500 to-emerald-500 rounded-lg shadow-md p-6 text-white card-hover animate__animated animate__fadeInRight" style="animation-delay: 0.2s;">
                    <div class="flex justify-between items-start">
                        <div>
                            <h3 class="text-xl font-bold mb-2">Attendance Rate</h3>
                            <p class="text-3xl font-bold"><?php
                                $attendanceRate = 0;
                                if (count($employees) > 0) {
                                    $attendanceRate = ($todayAttendanceCount / count($employees)) * 100;
                                }
                                echo number_format($attendanceRate, 2);
                            ?>%</p>
                        </div>
                        <div class="text-4xl opacity-80 floating">
                            <i class="fas fa-chart-line"></i>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow-md p-6 mb-8 animate__animated animate__fadeIn" style="animation-delay: 0.3s;">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-2xl font-bold text-gray-800">Employee Attendance</h2>
                    <div>
                        <button id="refreshTable" class="btn-hover px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors duration-200 flex items-center">
                            <i class="fas fa-sync-alt mr-2"></i> Refresh
                        </button>
                    </div>
                </div>
                
                <div class="overflow-x-auto">
                    <table id="attendanceTable" class="w-full table-auto">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-2 text-left text-sm font-medium text-gray-500 uppercase tracking-wider">Employee</th>
                                <th class="px-4 py-2 text-left text-sm font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                <th class="px-4 py-2 text-left text-sm font-medium text-gray-500 uppercase tracking-wider">Check In</th>
                                <th class="px-4 py-2 text-left text-sm font-medium text-gray-500 uppercase tracking-wider">Check Out</th>
                                <th class="px-4 py-2 text-left text-sm font-medium text-gray-500 uppercase tracking-wider">Duration</th>
                                <th class="px-4 py-2 text-left text-sm font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="px-4 py-2 text-left text-sm font-medium text-gray-500 uppercase tracking-wider">Notes</th>
                                <th class="px-4 py-2 text-left text-sm font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($attendanceRecords as $record): ?>
                                <tr class="border-b border-gray-200 hover:bg-gray-50 animate__animated animate__fadeIn" style="animation-delay: <?php echo array_search($record, $attendanceRecords) * 0.05; ?>s">
                                    <td class="px-4 py-3">
                                        <div class="flex items-center">
                                            <div class="flex-shrink-0 h-10 w-10 rounded-full bg-gray-200 flex items-center justify-center">
                                                <?php 
                                                $employee = getEmployeeById($record['employee_id']);
                                                if ($employee && !empty($employee['profile_image']) && file_exists('../uploads/profile_images/' . $employee['profile_image'])): 
                                                ?>
                                                    <img src="../uploads/profile_images/<?php echo $employee['profile_image']; ?>" alt="Profile" class="h-10 w-10 rounded-full object-cover">
                                                <?php else: ?>
                                                    <span class="text-sm font-medium text-gray-600">
                                                        <?php echo $employee ? strtoupper(substr($employee['first_name'], 0, 1) . substr($employee['last_name'], 0, 1)) : 'NA'; ?>
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                            <div class="ml-4">
                                                <div class="text-sm font-medium text-gray-900">
                                                    <?php echo $employee ? htmlspecialchars($employee['first_name'] . ' ' . $employee['last_name']) : 'Unknown'; ?>
                                                </div>
                                                <div class="text-sm text-gray-500">
                                                    <?php echo htmlspecialchars($record['employee_id']); ?>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-500">
                                        <?php echo date('M d, Y', strtotime($record['check_in'])); ?>
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="flex items-center">
                                            <span class="text-sm text-gray-500 mr-2">
                                                <?php echo date('h:i A', strtotime($record['check_in'])); ?>
                                            </span>
                                            <?php if (!empty($record['check_in_image'])): ?>
                                                <button class="view-image btn-hover text-indigo-600 hover:text-indigo-800 animate__animated animate__fadeIn" 
                                                        data-image="../uploads/attendance_images/<?php echo $record['check_in_image']; ?>"
                                                        data-type="Check-in"
                                                        data-time="<?php echo date('h:i A', strtotime($record['check_in'])); ?>">
                                                    <i class="fas fa-camera"></i>
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3">
                                        <?php if (isset($record['check_out'])): ?>
                                            <div class="flex items-center">
                                                <span class="text-sm text-gray-500 mr-2">
                                                    <?php echo date('h:i A', strtotime($record['check_out'])); ?>
                                                </span>
                                                <?php if (!empty($record['check_out_image'])): ?>
                                                    <button class="view-image btn-hover text-indigo-600 hover:text-indigo-800 animate__animated animate__fadeIn" 
                                                            data-image="../uploads/attendance_images/<?php echo $record['check_out_image']; ?>"
                                                            data-type="Check-out"
                                                            data-time="<?php echo date('h:i A', strtotime($record['check_out'])); ?>">
                                                        <i class="fas fa-camera"></i>
                                                    </button>
                                                <?php endif; ?>
                                            </div>
                                        <?php else: ?>
                                            <span class="text-sm text-gray-500">Not checked out</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-500">
                                        <?php
                                        if (isset($record['check_out'])) {
                                            $checkIn = new DateTime($record['check_in']);
                                            $checkOut = new DateTime($record['check_out']);
                                            $duration = $checkOut->diff($checkIn);
                                            echo $duration->format('%h hrs %i mins');
                                        } else {
                                            echo 'N/A';
                                        }
                                        ?>
                                    </td>
                                    <td class="px-4 py-3">
                                        <?php if (isset($record['check_out'])): ?>
                                            <span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">
                                                Completed
                                            </span>
                                        <?php else: ?>
                                            <span class="px-2 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800">
                                                In Progress
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-4 py-3">
                                        <?php echo $record['notes'] ?? 'No notes available'; ?>
                                    </td>
                                    <td class="px-4 py-3 text-sm">
                                        <div class="flex space-x-2">
                                            <button class="view-record btn-hover text-blue-600 hover:text-blue-800" data-record-id="<?php echo $record['id']; ?>">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <button class="delete-record btn-hover text-red-600 hover:text-red-800" data-record-id="<?php echo $record['id']; ?>">
                                                <i class="fas fa-trash-alt"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- View Image Modal -->
    <div id="imageModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center animate__animated animate__fadeIn">
        <div class="bg-white rounded-lg shadow-xl max-w-2xl w-full mx-4 overflow-hidden animate__animated animate__zoomIn">
            <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
                <h3 class="text-lg font-semibold text-gray-800" id="imageModalTitle">View Image</h3>
                <button id="closeImageModal" class="text-gray-400 hover:text-gray-500 focus:outline-none">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="p-6">
                <div class="mb-4 text-center">
                    <img id="modalImage" src="" alt="Attendance Image" class="max-w-full h-auto mx-auto rounded-lg shadow-md">
                </div>
                <div class="text-center text-gray-600 mb-4" id="imageDetails">
                    <!-- Image details will be inserted here -->
                </div>
            </div>
        </div>
    </div>

    <!-- View Record Modal -->
    <div id="recordModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center animate__animated animate__fadeIn">
        <div class="bg-white rounded-lg shadow-xl max-w-4xl w-full mx-4 overflow-hidden animate__animated animate__zoomIn">
            <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
                <h3 class="text-lg font-semibold text-gray-800">Attendance Details</h3>
                <button id="closeRecordModal" class="text-gray-400 hover:text-gray-500 focus:outline-none">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <h4 class="text-md font-semibold text-gray-700 mb-4">Employee Information</h4>
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <div class="flex items-center mb-4">
                                <div id="recordEmployeeAvatar" class="h-16 w-16 rounded-full bg-gray-200 flex items-center justify-center mr-4">
                                    <!-- Employee avatar will be inserted here -->
                                </div>
                                <div>
                                    <p class="text-lg font-medium text-gray-900" id="recordEmployeeName"><!-- Employee name --></p>
                                    <p class="text-sm text-gray-500" id="recordEmployeeId"><!-- Employee ID --></p>
                                </div>
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <p class="text-sm text-gray-500">Date</p>
                                    <p class="text-md font-medium" id="recordDate"><!-- Date --></p>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-500">Status</p>
                                    <p class="text-md font-medium" id="recordStatus"><!-- Status --></p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div>
                        <h4 class="text-md font-semibold text-gray-700 mb-4">Attendance Details</h4>
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <div class="grid grid-cols-2 gap-4 mb-4">
                                <div>
                                    <p class="text-sm text-gray-500">Check In</p>
                                    <p class="text-md font-medium" id="recordCheckIn"><!-- Check in time --></p>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-500">Check Out</p>
                                    <p class="text-md font-medium" id="recordCheckOut"><!-- Check out time --></p>
                                </div>
                            </div>
                            <div class="mb-4">
                                <p class="text-sm text-gray-500">Duration</p>
                                <p class="text-md font-medium" id="recordDuration"><!-- Duration --></p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500">Notes</p>
                                <p class="text-md font-medium bg-white p-2 rounded border border-gray-200 min-h-[60px]" id="recordNotes"><!-- Notes --></p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="mt-6">
                    <h4 class="text-md font-semibold text-gray-700 mb-4">Attendance Images</h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <p class="text-sm text-gray-500 mb-2">Check In Image</p>
                            <div id="recordCheckInImageContainer" class="h-48 flex items-center justify-center bg-gray-100 rounded-lg overflow-hidden">
                                <img id="recordCheckInImage" src="" alt="Check In" class="max-h-full max-w-full object-contain hidden">
                                <div class="no-image text-gray-400">
                                    <i class="fas fa-image text-4xl mb-2"></i>
                                    <p>No image available</p>
                                </div>
                            </div>
                        </div>
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <p class="text-sm text-gray-500 mb-2">Check Out Image</p>
                            <div id="recordCheckOutImageContainer" class="h-48 flex items-center justify-center bg-gray-100 rounded-lg overflow-hidden">
                                <img id="recordCheckOutImage" src="" alt="Check Out" class="max-h-full max-w-full object-contain hidden">
                                <div class="no-image text-gray-400">
                                    <i class="fas fa-image text-4xl mb-2"></i>
                                    <p>No image available</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div id="deleteModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center">
        <div class="bg-white rounded-lg shadow-md p-6 max-w-md w-full mx-4 overflow-hidden animate__animated animate__zoomIn">
            <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
                <h3 class="text-lg font-semibold text-gray-800">Delete Attendance Record</h3>
                <button id="closeDeleteModal" class="text-gray-400 hover:text-gray-500 focus:outline-none">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="p-6">
                <p class="text-lg font-medium text-gray-800 mb-4">Are you sure you want to delete this attendance record?</p>
                <p class="text-sm text-gray-500 mb-6">This action cannot be undone.</p>
                <div class="flex justify-between items-center">
                    <button id="cancelDelete" class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-lg transition-colors duration-200 flex items-center">
                        Cancel
                    </button>
                    <button id="confirmDelete" class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg transition-colors duration-200 flex items-center">
                        Delete
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        $(document).ready(function() {
            // Initialize DataTable
            var attendanceTable = $('#attendanceTable').DataTable({
                responsive: true,
                pageLength: 25,
                order: [[1, 'desc']], // Sort by date column descending
                language: {
                    search: "_INPUT_",
                    searchPlaceholder: "Search records...",
                    lengthMenu: "Show _MENU_ records per page",
                    info: "Showing _START_ to _END_ of _TOTAL_ records",
                    infoEmpty: "Showing 0 to 0 of 0 records",
                    infoFiltered: "(filtered from _MAX_ total records)"
                }
            });
            
            // Image modal functionality
            const imageModal = document.getElementById('imageModal');
            const closeImageModal = document.getElementById('closeImageModal');
            const modalImage = document.getElementById('modalImage');
            const imageModalTitle = document.getElementById('imageModalTitle');
            const imageDetails = document.getElementById('imageDetails');
            
            $('.view-image').on('click', function() {
                const imageSrc = $(this).data('image');
                const imageType = $(this).data('type');
                const imageTime = $(this).data('time');
                
                modalImage.src = imageSrc;
                imageModalTitle.textContent = imageType + ' Image';
                imageDetails.innerHTML = `<p><strong>${imageType}</strong> at ${imageTime}</p>`;
                
                // Show modal with animation
                imageModal.classList.remove('hidden');
                $(imageModal).addClass('animate__fadeIn');
                $(imageModal.firstElementChild).addClass('animate__zoomIn');
            });
            
            closeImageModal.addEventListener('click', () => {
                // Hide modal with animation
                $(imageModal).addClass('animate__fadeOut');
                $(imageModal.firstElementChild).addClass('animate__zoomOut');
                
                setTimeout(() => {
                    imageModal.classList.add('hidden');
                    $(imageModal).removeClass('animate__fadeIn animate__fadeOut');
                    $(imageModal.firstElementChild).removeClass('animate__zoomIn animate__zoomOut');
                }, 300);
            });
            
            // View record functionality
            const recordModal = document.getElementById('recordModal');
            const closeRecordModal = document.getElementById('closeRecordModal');
            
            $('.view-record').on('click', function() {
                const recordId = $(this).data('record-id');
                
                // Show loading state
                $('#recordModal .p-6').html('<div class="flex justify-center items-center h-64"><i class="fas fa-spinner fa-spin text-4xl text-indigo-500"></i></div>');
                recordModal.classList.remove('hidden');
                
                // Fetch record data
                $.ajax({
                    url: 'get_attendance_record.php',
                    type: 'GET',
                    data: { record_id: recordId },
                    dataType: 'json',
                    success: function(record) {
                        if (record) {
                            // Employee information
                            if (record.employee_profile_image && record.employee_profile_image !== '') {
                                $('#recordEmployeeAvatar').html(`<img src="../uploads/profile_images/${record.employee_profile_image}" alt="Profile" class="h-16 w-16 rounded-full object-cover">`);
                            } else {
                                const initials = record.employee_name ? record.employee_name.split(' ').map(n => n[0]).join('').toUpperCase() : 'NA';
                                $('#recordEmployeeAvatar').html(`<span class="text-xl font-medium text-gray-600">${initials}</span>`);
                            }
                            
                            $('#recordEmployeeName').text(record.employee_name || 'Unknown');
                            $('#recordEmployeeId').text(record.employee_id || 'N/A');
                            
                            // Attendance details
                            $('#recordDate').text(new Date(record.check_in).toLocaleDateString('en-US', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' }));
                            $('#recordStatus').html(record.status === 'present' ? 
                                '<span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">Present</span>' : 
                                '<span class="px-2 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800">In Progress</span>');
                            
                            $('#recordCheckIn').text(new Date(record.check_in).toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' }));
                            $('#recordCheckOut').text(record.check_out ? new Date(record.check_out).toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' }) : 'Not checked out');
                            
                            // Calculate duration
                            if (record.check_out) {
                                const checkIn = new Date(record.check_in);
                                const checkOut = new Date(record.check_out);
                                const durationMs = checkOut - checkIn;
                                const hours = Math.floor(durationMs / (1000 * 60 * 60));
                                const minutes = Math.floor((durationMs % (1000 * 60 * 60)) / (1000 * 60));
                                $('#recordDuration').text(`${hours} hrs ${minutes} mins`);
                            } else {
                                $('#recordDuration').text('N/A');
                            }
                            
                            // Notes
                            $('#recordNotes').text(record.notes || 'No notes available');
                            
                            // Images
                            if (record.check_in_image && record.check_in_image !== '') {
                                $('#recordCheckInImage').attr('src', '../uploads/attendance_images/' + record.check_in_image).removeClass('hidden');
                                $('#recordCheckInImageContainer .no-image').addClass('hidden');
                            } else {
                                $('#recordCheckInImage').addClass('hidden');
                                $('#recordCheckInImageContainer .no-image').removeClass('hidden');
                            }
                            
                            if (record.check_out_image && record.check_out_image !== '') {
                                $('#recordCheckOutImage').attr('src', '../uploads/attendance_images/' + record.check_out_image).removeClass('hidden');
                                $('#recordCheckOutImageContainer .no-image').addClass('hidden');
                            } else {
                                $('#recordCheckOutImage').addClass('hidden');
                                $('#recordCheckOutImageContainer .no-image').removeClass('hidden');
                            }
                        } else {
                            // Show error message
                            $('#recordModal .p-6').html('<div class="flex justify-center items-center h-64"><p class="text-red-500">Record not found</p></div>');
                        }
                    },
                    error: function() {
                        // Show error message
                        $('#recordModal .p-6').html('<div class="flex justify-center items-center h-64"><p class="text-red-500">Failed to load record</p></div>');
                    }
                });
            });
            
            closeRecordModal.addEventListener('click', () => {
                // Hide modal with animation
                $(recordModal).addClass('animate__fadeOut');
                $(recordModal.firstElementChild).addClass('animate__zoomOut');
                
                setTimeout(() => {
                    recordModal.classList.add('hidden');
                    $(recordModal).removeClass('animate__fadeIn animate__fadeOut');
                    $(recordModal.firstElementChild).removeClass('animate__zoomIn animate__zoomOut');
                }, 300);
            });
            
            // Delete record functionality
            const deleteModal = document.getElementById('deleteModal');
            const closeDeleteModal = document.getElementById('closeDeleteModal');
            const confirmDeleteBtn = document.getElementById('confirmDelete');
            const cancelDeleteBtn = document.getElementById('cancelDelete');
            let recordIdToDelete = null;
            
            $('.delete-record').on('click', function() {
                recordIdToDelete = $(this).data('record-id');
                deleteModal.classList.remove('hidden');
            });
            
            closeDeleteModal.addEventListener('click', () => {
                deleteModal.classList.add('hidden');
                recordIdToDelete = null;
            });
            
            cancelDeleteBtn.addEventListener('click', () => {
                deleteModal.classList.add('hidden');
                recordIdToDelete = null;
            });
            
            confirmDeleteBtn.addEventListener('click', () => {
                if (recordIdToDelete) {
                    // Send AJAX request to delete the record
                    $.ajax({
                        url: '../includes/delete_attendance.php',
                        type: 'POST',
                        data: { record_id: recordIdToDelete },
                        dataType: 'json',
                        success: function(response) {
                            if (response.success) {
                                // Reload the page to reflect changes
                                window.location.reload();
                            } else {
                                alert('Failed to delete record: ' + response.message);
                            }
                        },
                        error: function() {
                            alert('An error occurred while deleting the record');
                        }
                    });
                }
                deleteModal.classList.add('hidden');
            });
            
            // Refresh table button functionality
            $('#refreshTable').on('click', function() {
                // Show loading spinner
                $(this).html('<i class="fas fa-spinner fa-spin mr-2"></i> Refreshing...');
                
                // Reload the page
                window.location.reload();
            });
            
            // Export to CSV
            $('#exportCSV').on('click', function() {
                exportTableToCSV('attendance_report.csv');
            });
            
            // Function to export table to CSV
            function exportTableToCSV(filename) {
                const csv = [];
                const rows = document.querySelectorAll('#attendanceTable tr');
                
                for (let i = 0; i < rows.length; i++) {
                    const row = [];
                    const cols = rows[i].querySelectorAll('td, th');
                    
                    for (let j = 0; j < cols.length; j++) {
                        // Skip the images column and actions column
                        if (j !== 6 && j !== 7) {
                            const text = cols[j].innerText;
                            row.push('"' + text.replace(/"/g, '""') + '"');
                        }
                    }
                    
                    csv.push(row.join(','));
                }
                
                // Download CSV file
                downloadCSV(csv.join('\n'), filename);
                
                // Show success message
                const successMsg = $('<div class="fixed top-5 right-5 bg-green-100 border-l-4 border-green-500 text-green-700 p-4 rounded shadow-md z-50">Report exported successfully!</div>');
                $('body').append(successMsg);
                
                setTimeout(() => {
                    successMsg.remove();
                }, 3000);
            }
            
            // Function to download CSV
            function downloadCSV(csv, filename) {
                const csvFile = new Blob([csv], {type: 'text/csv'});
                const downloadLink = document.createElement('a');
                
                downloadLink.download = filename;
                downloadLink.href = window.URL.createObjectURL(csvFile);
                downloadLink.style.display = 'none';
                document.body.appendChild(downloadLink);
                
                downloadLink.click();
                document.body.removeChild(downloadLink);
            }
            
            // Initialize DataTables with animation
            attendanceTable.draw();
            
            // Add animation to table rows on hover
            $('tbody tr').hover(
                function() {
                    $(this).addClass('animate__animated animate__pulse');
                },
                function() {
                    $(this).removeClass('animate__animated animate__pulse');
                }
            );
            
            // Add floating animation to icons
            setInterval(function() {
                $('.icon-float').animate({
                    marginTop: '-=10'
                }, 1000, function() {
                    $(this).animate({
                        marginTop: '+=10'
                    }, 1000);
                });
            }, 2000);
        });
    </script>
</body>
</html>
