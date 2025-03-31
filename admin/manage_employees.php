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

// Handle employee deletion
if (isset($_GET['delete']) && !empty($_GET['delete'])) {
    $employeeId = $_GET['delete'];
    
    // Find the employee index
    $employeeIndex = -1;
    foreach ($employees as $index => $employee) {
        if ($employee['id'] == $employeeId) {
            $employeeIndex = $index;
            break;
        }
    }
    
    // If employee found, remove it
    if ($employeeIndex >= 0) {
        // Delete profile image if exists
        if (!empty($employees[$employeeIndex]['profile_image'])) {
            $imagePath = '../uploads/profile_images/' . $employees[$employeeIndex]['profile_image'];
            if (file_exists($imagePath)) {
                unlink($imagePath);
            }
        }
        
        // Remove employee from array
        array_splice($employees, $employeeIndex, 1);
        
        // Save updated employees
        if (writeJsonFile(EMPLOYEES_JSON, $employees)) {
            $successMessage = "Employee deleted successfully!";
        } else {
            $errorMessage = "Failed to delete employee. Please try again.";
        }
    } else {
        $errorMessage = "Employee not found.";
    }
    
    // Redirect to remove the delete parameter from URL
    header('Location: manage_employees.php' . (isset($errorMessage) ? '?error=' . urlencode($errorMessage) : (isset($successMessage) ? '?success=' . urlencode($successMessage) : '')));
    exit;
}

// Process messages
$successMessage = isset($_GET['success']) ? $_GET['success'] : '';
$errorMessage = isset($_GET['error']) ? $_GET['error'] : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Employees - Admin Dashboard</title>
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
                <h1 class="text-3xl font-bold text-gray-800 animate__animated animate__fadeInDown">Manage Employees</h1>
                <div class="flex space-x-4">
                    <a href="dashboard.php" class="btn-hover px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-lg transition-colors duration-200 flex items-center animate__animated animate__fadeIn">
                        <i class="fas fa-arrow-left mr-2"></i> Back to Dashboard
                    </a>
                    <a href="logout.php" class="btn-hover px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-lg transition-colors duration-200 flex items-center animate__animated animate__fadeIn">
                        <i class="fas fa-sign-out-alt mr-2"></i> Logout
                    </a>
                </div>
            </div>
            
            <?php if (!empty($successMessage)): ?>
            <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 animate__animated animate__fadeInDown" role="alert">
                <p><?php echo htmlspecialchars($successMessage); ?></p>
            </div>
            <?php endif; ?>
            
            <?php if (!empty($errorMessage)): ?>
            <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 animate__animated animate__fadeInDown" role="alert">
                <p><?php echo htmlspecialchars($errorMessage); ?></p>
            </div>
            <?php endif; ?>
            
            <div class="bg-white rounded-lg shadow-md p-6 mb-8 animate__animated animate__fadeIn">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-2xl font-bold text-gray-800">Employee List</h2>
                    <button id="addEmployeeBtn" class="btn-hover px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors duration-200 flex items-center">
                        <i class="fas fa-plus mr-2"></i> Add New Employee
                    </button>
                </div>
                
                <div class="overflow-x-auto">
                    <table id="employeesTable" class="w-full table-auto">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-2 text-left text-sm font-medium text-gray-500 uppercase tracking-wider">Employee</th>
                                <th class="px-4 py-2 text-left text-sm font-medium text-gray-500 uppercase tracking-wider">ID</th>
                                <th class="px-4 py-2 text-left text-sm font-medium text-gray-500 uppercase tracking-wider">Email</th>
                                <th class="px-4 py-2 text-left text-sm font-medium text-gray-500 uppercase tracking-wider">Position</th>
                                <th class="px-4 py-2 text-left text-sm font-medium text-gray-500 uppercase tracking-wider">Department</th>
                                <th class="px-4 py-2 text-left text-sm font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($employees as $employee): ?>
                                <tr class="border-b border-gray-200 hover:bg-gray-50 animate__animated animate__fadeIn" style="animation-delay: <?php echo array_search($employee, $employees) * 0.05; ?>s">
                                    <td class="px-4 py-3">
                                        <div class="flex items-center">
                                            <div class="flex-shrink-0 h-10 w-10 rounded-full bg-gray-200 flex items-center justify-center">
                                                <?php if (!empty($employee['profile_image']) && file_exists('../uploads/profile_images/' . $employee['profile_image'])): ?>
                                                    <img src="../uploads/profile_images/<?php echo $employee['profile_image']; ?>" alt="Profile" class="h-10 w-10 rounded-full object-cover">
                                                <?php else: ?>
                                                    <span class="text-sm font-medium text-gray-600">
                                                        <?php echo strtoupper(substr($employee['first_name'], 0, 1) . substr($employee['last_name'], 0, 1)); ?>
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                            <div class="ml-4">
                                                <div class="text-sm font-medium text-gray-900">
                                                    <?php echo htmlspecialchars($employee['first_name'] . ' ' . $employee['last_name']); ?>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-500">
                                        <?php echo htmlspecialchars($employee['employee_id']); ?>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-500">
                                        <?php echo htmlspecialchars($employee['email']); ?>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-500">
                                        <?php echo htmlspecialchars($employee['position']); ?>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-500">
                                        <?php echo htmlspecialchars($employee['department']); ?>
                                    </td>
                                    <td class="px-4 py-3 text-sm">
                                        <div class="flex space-x-2">
                                            <button class="edit-employee btn-hover text-blue-600 hover:text-blue-800" 
                                                    data-id="<?php echo $employee['id']; ?>"
                                                    data-employee-id="<?php echo htmlspecialchars($employee['employee_id']); ?>"
                                                    data-first-name="<?php echo htmlspecialchars($employee['first_name']); ?>"
                                                    data-last-name="<?php echo htmlspecialchars($employee['last_name']); ?>"
                                                    data-email="<?php echo htmlspecialchars($employee['email']); ?>"
                                                    data-position="<?php echo htmlspecialchars($employee['position']); ?>"
                                                    data-department="<?php echo htmlspecialchars($employee['department']); ?>"
                                                    data-profile-image="<?php echo htmlspecialchars($employee['profile_image'] ?? ''); ?>">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <a href="manage_employees.php?delete=<?php echo $employee['id']; ?>" class="delete-employee btn-hover text-red-600 hover:text-red-800" onclick="return confirm('Are you sure you want to delete this employee? This action cannot be undone.');">
                                                <i class="fas fa-trash-alt"></i>
                                            </a>
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
    
    <!-- Add/Edit Employee Modal -->
    <div id="employeeModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center animate__animated animate__fadeIn">
        <div class="bg-white rounded-lg shadow-xl max-w-2xl w-full mx-4 overflow-hidden animate__animated animate__zoomIn">
            <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
                <h3 class="text-lg font-semibold text-gray-800" id="modalTitle">Add New Employee</h3>
                <button id="closeModal" class="text-gray-400 hover:text-gray-500 focus:outline-none">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form id="employeeForm" action="process_employee.php" method="post" enctype="multipart/form-data">
                <input type="hidden" name="employee_id_hidden" id="employeeIdHidden">
                <div class="p-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                        <div>
                            <label for="firstName" class="block text-sm font-medium text-gray-700 mb-1">First Name</label>
                            <input type="text" id="firstName" name="first_name" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500" required>
                        </div>
                        <div>
                            <label for="lastName" class="block text-sm font-medium text-gray-700 mb-1">Last Name</label>
                            <input type="text" id="lastName" name="last_name" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500" required>
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                        <div>
                            <label for="employeeId" class="block text-sm font-medium text-gray-700 mb-1">Employee ID</label>
                            <input type="text" id="employeeId" name="employee_id" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500" required>
                        </div>
                        <div>
                            <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                            <input type="email" id="email" name="email" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500" required>
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                        <div>
                            <label for="position" class="block text-sm font-medium text-gray-700 mb-1">Position</label>
                            <input type="text" id="position" name="position" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500" required>
                        </div>
                        <div>
                            <label for="department" class="block text-sm font-medium text-gray-700 mb-1">Department</label>
                            <input type="text" id="department" name="department" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500" required>
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                        <div>
                            <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                            <input type="password" id="password" name="password" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500">
                            <p class="text-xs text-gray-500 mt-1">Leave blank to keep current password when editing.</p>
                        </div>
                        <div>
                            <label for="profileImage" class="block text-sm font-medium text-gray-700 mb-1">Profile Image</label>
                            <input type="file" id="profileImage" name="profile_image" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500" accept="image/*">
                            <p class="text-xs text-gray-500 mt-1">Upload a profile picture (optional).</p>
                        </div>
                    </div>
                    
                    <div id="currentImageContainer" class="mb-6 hidden">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Current Profile Image</label>
                        <div class="flex items-center">
                            <div id="currentImagePreview" class="h-16 w-16 rounded-full bg-gray-200 flex items-center justify-center mr-4">
                                <!-- Image will be inserted here -->
                            </div>
                            <div>
                                <p id="currentImageName" class="text-sm text-gray-600">No image</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="flex justify-end space-x-4 mt-6">
                        <button type="button" id="cancelBtn" class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-lg transition-colors duration-200">
                            Cancel
                        </button>
                        <button type="submit" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg transition-colors duration-200">
                            <i class="fas fa-save mr-2"></i> Save Employee
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        $(document).ready(function() {
            // Initialize DataTable
            var employeesTable = $('#employeesTable').DataTable({
                responsive: true,
                pageLength: 10,
                language: {
                    search: "_INPUT_",
                    searchPlaceholder: "Search employees...",
                    lengthMenu: "Show _MENU_ employees per page",
                    info: "Showing _START_ to _END_ of _TOTAL_ employees",
                    infoEmpty: "Showing 0 to 0 of 0 employees",
                    infoFiltered: "(filtered from _MAX_ total employees)"
                }
            });
            
            // Modal elements
            const employeeModal = document.getElementById('employeeModal');
            const closeModal = document.getElementById('closeModal');
            const cancelBtn = document.getElementById('cancelBtn');
            const modalTitle = document.getElementById('modalTitle');
            const employeeForm = document.getElementById('employeeForm');
            const employeeIdHidden = document.getElementById('employeeIdHidden');
            
            // Add employee button
            $('#addEmployeeBtn').on('click', function() {
                modalTitle.textContent = 'Add New Employee';
                employeeForm.reset();
                employeeIdHidden.value = '';
                $('#currentImageContainer').addClass('hidden');
                
                // Show modal with animation
                employeeModal.classList.remove('hidden');
                $(employeeModal).addClass('animate__fadeIn');
                $(employeeModal.firstElementChild).addClass('animate__zoomIn');
            });
            
            // Edit employee buttons
            $('.edit-employee').on('click', function() {
                const id = $(this).data('id');
                const employeeId = $(this).data('employee-id');
                const firstName = $(this).data('first-name');
                const lastName = $(this).data('last-name');
                const email = $(this).data('email');
                const position = $(this).data('position');
                const department = $(this).data('department');
                const profileImage = $(this).data('profile-image');
                
                modalTitle.textContent = 'Edit Employee';
                employeeIdHidden.value = id;
                
                // Fill form fields
                $('#firstName').val(firstName);
                $('#lastName').val(lastName);
                $('#employeeId').val(employeeId);
                $('#email').val(email);
                $('#position').val(position);
                $('#department').val(department);
                $('#password').val(''); // Clear password field
                
                // Show current image if exists
                if (profileImage) {
                    $('#currentImageContainer').removeClass('hidden');
                    $('#currentImagePreview').html(`<img src="../uploads/profile_images/${profileImage}" alt="Profile" class="h-16 w-16 rounded-full object-cover">`);
                    $('#currentImageName').text(profileImage);
                } else {
                    $('#currentImageContainer').addClass('hidden');
                    $('#currentImagePreview').html(`<span class="text-sm font-medium text-gray-600">${firstName.charAt(0)}${lastName.charAt(0)}</span>`);
                    $('#currentImageName').text('No image');
                }
                
                // Show modal with animation
                employeeModal.classList.remove('hidden');
                $(employeeModal).addClass('animate__fadeIn');
                $(employeeModal.firstElementChild).addClass('animate__zoomIn');
            });
            
            // Close modal functions
            const closeModalWithAnimation = () => {
                $(employeeModal).addClass('animate__fadeOut');
                $(employeeModal.firstElementChild).addClass('animate__zoomOut');
                
                setTimeout(() => {
                    employeeModal.classList.add('hidden');
                    $(employeeModal).removeClass('animate__fadeIn animate__fadeOut');
                    $(employeeModal.firstElementChild).removeClass('animate__zoomIn animate__zoomOut');
                }, 300);
            };
            
            closeModal.addEventListener('click', closeModalWithAnimation);
            cancelBtn.addEventListener('click', closeModalWithAnimation);
            
            // Add animation to table rows on hover
            $('tbody tr').hover(
                function() {
                    $(this).addClass('animate__animated animate__pulse');
                },
                function() {
                    $(this).removeClass('animate__animated animate__pulse');
                }
            );
        });
    </script>
</body>
</html>
