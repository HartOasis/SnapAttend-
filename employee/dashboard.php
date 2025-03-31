<?php
// Start session
session_start();

// Check if employee is logged in
if (!isset($_SESSION['employee_id'])) {
    header('Location: index.php');
    exit;
}

// Include functions
require_once '../includes/functions.php';

// Get employee data
$employeeId = $_SESSION['employee_id'];
$employees = readJsonFile(EMPLOYEES_JSON);
$employee = null;

// Find the employee
foreach ($employees as $emp) {
    if ($emp['id'] == $employeeId) {
        $employee = $emp;
        break;
    }
}

// If employee not found, redirect to login
if (!$employee) {
    header('Location: logout.php');
    exit;
}

// Check if employee has checked in today
$attendanceRecords = readJsonFile(ATTENDANCE_JSON);
$todayAttendance = null;
$hasCheckedIn = false;
$notesSaved = false;

// Get today's date
$today = date('Y-m-d');

// Check if employee has attendance record for today
foreach ($attendanceRecords as $record) {
    if ($record['employee_id'] == $employeeId && date('Y-m-d', strtotime($record['check_in'])) === $today) {
        $todayAttendance = $record;
        $hasCheckedIn = true;
        break;
    }
}

// Handle notes form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['notes']) && $hasCheckedIn) {
    $notes = sanitizeInput($_POST['notes']);
    
    // Update notes in attendance record
    foreach ($attendanceRecords as &$record) {
        if ($record['id'] == $todayAttendance['id']) {
            $record['notes'] = $notes;
            $todayAttendance = $record;
            break;
        }
    }
    
    // Save updated records
    if (writeJsonFile(ATTENDANCE_JSON, $attendanceRecords)) {
        $notesSaved = true;
    }
}

// Get employee attendance records for the table
$employeeAttendance = [];
foreach ($attendanceRecords as $record) {
    if ($record['employee_id'] == $employeeId) {
        $employeeAttendance[] = $record;
    }
}

// Sort records by date (newest first)
usort($employeeAttendance, function($a, $b) {
    return strtotime($b['check_in']) - strtotime($a['check_in']);
});

// Limit to last 10 records
$employeeAttendance = array_slice($employeeAttendance, 0, 10);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee Dashboard - Attendance System</title>
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- Animation Library -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>
    <!-- Custom Tailwind Config -->
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#4f46e5',
                        secondary: '#6366f1',
                        success: '#10b981',
                        danger: '#ef4444',
                        warning: '#f59e0b',
                        info: '#3b82f6',
                    },
                    animation: {
                        'bounce-slow': 'bounce 3s linear infinite',
                        'pulse-slow': 'pulse 4s cubic-bezier(0.4, 0, 0.6, 1) infinite',
                        'fade-in': 'fadeIn 0.5s ease-in-out',
                        'slide-in': 'slideIn 0.5s ease-in-out',
                    },
                    keyframes: {
                        fadeIn: {
                            '0%': { opacity: '0' },
                            '100%': { opacity: '1' },
                        },
                        slideIn: {
                            '0%': { transform: 'translateY(20px)', opacity: '0' },
                            '100%': { transform: 'translateY(0)', opacity: '1' },
                        }
                    }
                }
            }
        }
    </script>
    <style>
        .fade-in {
            animation: fadeIn 0.5s ease-in-out;
        }
        
        .slide-in {
            animation: slideIn 0.5s ease-in-out;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        @keyframes slideIn {
            from { transform: translateY(20px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
        
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
        
        .profile-image-container {
            transition: all 0.3s ease;
        }
        
        .profile-image-container:hover {
            transform: scale(1.05);
        }
        
        .floating {
            animation: floating 3s ease-in-out infinite;
        }
        
        @keyframes floating {
            0% { transform: translateY(0px); }
            50% { transform: translateY(-10px); }
            100% { transform: translateY(0px); }
        }
    </style>
</head>
<body class="bg-gray-50">
    <div class="container mx-auto px-4 py-8">
        <div class="max-w-6xl mx-auto bg-white rounded-lg shadow-md p-6">
            <div class="flex justify-between items-center border-b border-gray-200 pb-4 mb-6">
                <div class="flex items-center space-x-4">
                    <div id="profileImageContainer" class="profile-image-container w-16 h-16 rounded-full bg-gray-200 flex items-center justify-center overflow-hidden border-2 border-primary cursor-pointer animate__animated animate__fadeIn" data-bs-toggle="modal" data-bs-target="#profileModal">
                        <?php if (!empty($employee['profile_image']) && file_exists('../uploads/profile_images/' . $employee['profile_image'])): ?>
                            <img src="../uploads/profile_images/<?php echo $employee['profile_image']; ?>" alt="Profile" class="w-full h-full object-cover">
                        <?php else: ?>
                            <span class="text-2xl font-bold text-gray-600"><?php echo strtoupper(substr($employee['first_name'], 0, 1) . substr($employee['last_name'], 0, 1)); ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="animate__animated animate__fadeIn" style="animation-delay: 0.2s;">
                        <h2 class="text-2xl font-bold text-gray-800"><?php echo htmlspecialchars($employee['first_name'] . ' ' . $employee['last_name']); ?></h2>
                        <p class="text-gray-600"><?php echo htmlspecialchars($employee['employee_id']); ?></p>
                    </div>
                </div>
                <div>
                    <a href="logout.php" class="btn-hover px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-lg transition-colors duration-200 flex items-center animate__animated animate__fadeIn" style="animation-delay: 0.3s;">
                        <i class="fas fa-sign-out-alt mr-2"></i> Logout
                    </a>
                </div>
            </div>

            <?php if (isset($_SESSION['success_message'])): ?>
                <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6" role="alert">
                    <p><?php echo $_SESSION['success_message']; ?></p>
                </div>
                <?php unset($_SESSION['success_message']); ?>
            <?php endif; ?>

            <?php if (isset($_SESSION['error_message'])): ?>
                <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6" role="alert">
                    <p><?php echo $_SESSION['error_message']; ?></p>
                </div>
                <?php unset($_SESSION['error_message']); ?>
            <?php endif; ?>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                <div class="bg-gradient-to-r from-blue-500 to-indigo-600 rounded-lg shadow-md p-6 text-white card-hover animate__animated animate__fadeInLeft">
                    <h3 class="text-xl font-bold mb-4">Attendance Status</h3>
                    <?php if (!$hasCheckedIn): ?>
                        <p class="mb-4 animate__animated animate__pulse animate__infinite">You haven't checked in today.</p>
                        <button id="checkInBtn" class="btn-hover px-4 py-2 bg-white text-blue-600 rounded-lg hover:bg-blue-50 transition-colors duration-200 font-medium animate__animated animate__heartBeat animate__delay-1s">
                            <i class="fas fa-sign-in-alt mr-2"></i> Check In
                        </button>
                    <?php else: ?>
                        <p class="mb-2 animate__animated animate__fadeIn"><i class="fas fa-sign-in-alt mr-2"></i> Check In: <?php echo date('h:i A', strtotime($todayAttendance['check_in'])); ?></p>
                        <?php if (isset($todayAttendance['check_out'])): ?>
                            <p class="mb-4 animate__animated animate__fadeIn" style="animation-delay: 0.2s;"><i class="fas fa-sign-out-alt mr-2"></i> Check Out: <?php echo date('h:i A', strtotime($todayAttendance['check_out'])); ?></p>
                            <div class="px-4 py-2 bg-green-500 text-white rounded-lg inline-block animate__animated animate__bounceIn" style="animation-delay: 0.4s;">
                                <i class="fas fa-check-circle mr-2"></i> Completed
                            </div>
                        <?php else: ?>
                            <p class="mb-4 animate__animated animate__pulse animate__infinite">You haven't checked out yet.</p>
                            <button id="checkOutBtn" class="btn-hover px-4 py-2 bg-white text-blue-600 rounded-lg hover:bg-blue-50 transition-colors duration-200 font-medium animate__animated animate__heartBeat animate__delay-1s">
                                <i class="fas fa-sign-out-alt mr-2"></i> Check Out
                            </button>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>

                <div class="bg-white rounded-lg shadow-md p-6 border border-gray-200 card-hover animate__animated animate__fadeInRight">
                    <h3 class="text-xl font-bold mb-4 text-gray-800">Camera</h3>
                    <div class="mb-4 bg-black rounded-lg overflow-hidden">
                        <video id="video" class="w-full h-48 object-cover" autoplay playsinline></video>
                        <canvas id="canvas" class="hidden"></canvas>
                    </div>
                    <div class="flex space-x-2">
                        <button id="startCamera" class="btn-hover px-4 py-2 bg-primary text-white rounded-lg hover:bg-indigo-600 transition-colors duration-200 animate__animated animate__fadeIn" style="animation-delay: 0.6s;">
                            <i class="fas fa-camera mr-2 floating"></i> Start Camera
                        </button>
                        <button id="stopCamera" class="btn-hover px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-colors duration-200 animate__animated animate__fadeIn" style="animation-delay: 0.8s;">
                            <i class="fas fa-stop-circle mr-2"></i> Stop Camera
                        </button>
                    </div>
                </div>
            </div>

            <?php if ($hasCheckedIn): ?>
                <div class="mb-8">
                    <h3 class="text-xl font-bold mb-4 text-gray-800">Today's Attendance</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <h4 class="text-lg font-semibold mb-2 text-gray-700">Check-in Image</h4>
                            <div class="border border-gray-200 rounded-lg p-4 bg-gray-50 min-h-[200px] flex items-center justify-center">
                                <?php if (isset($todayAttendance['check_in_image']) && !empty($todayAttendance['check_in_image'])): ?>
                                    <img id="checkInImage" src="../uploads/attendance_images/<?php echo $todayAttendance['check_in_image']; ?>" alt="Check-in Image" class="max-h-[180px] rounded">
                                <?php else: ?>
                                    <div class="text-gray-500 italic">No image available</div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php if (isset($todayAttendance['check_out_image']) && !empty($todayAttendance['check_out_image'])): ?>
                        <div>
                            <h4 class="text-lg font-semibold mb-2 text-gray-700">Check-out Image</h4>
                            <div class="border border-gray-200 rounded-lg p-4 bg-gray-50 min-h-[200px] flex items-center justify-center">
                                <img id="checkOutImage" src="../uploads/attendance_images/<?php echo $todayAttendance['check_out_image']; ?>" alt="Check-out Image" class="max-h-[180px] rounded">
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>

            <div class="mb-8">
                <h3 class="text-xl font-bold mb-4 text-gray-800">Daily Notes</h3>
                <form method="post" action="" class="mb-4">
                    <div class="mb-4">
                        <textarea name="notes" id="notes" rows="4" class="w-full px-3 py-2 text-gray-700 border rounded-lg focus:outline-none focus:border-primary" placeholder="Enter your notes for today..."><?php echo isset($todayAttendance['notes']) ? htmlspecialchars($todayAttendance['notes']) : ''; ?></textarea>
                    </div>
                    <button type="submit" class="px-4 py-2 bg-primary text-white rounded-lg hover:bg-indigo-600 transition-colors duration-200">
                        <i class="fas fa-save mr-2"></i> Save Notes
                    </button>
                </form>
                <?php if ($notesSaved): ?>
                    <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4" role="alert">
                        <p>Notes saved successfully!</p>
                    </div>
                <?php endif; ?>
            </div>

            <div>
                <h3 class="text-xl font-bold mb-4 text-gray-800">Recent Attendance</h3>
                <div class="overflow-x-auto">
                    <table class="min-w-full bg-white rounded-lg overflow-hidden">
                        <thead class="bg-gray-100">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Check In</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Check Out</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <?php
                            // Use the employeeAttendance array we created in the PHP initialization
                            foreach ($employeeAttendance as $record):
                            ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap"><?php echo date('Y-m-d', strtotime($record['check_in'])); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap"><?php echo date('h:i A', strtotime($record['check_in'])); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <?php echo isset($record['check_out']) ? date('h:i A', strtotime($record['check_out'])) : 'Not checked out'; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <?php
                                    $checkInHour = (int)date('H', strtotime($record['check_in']));
                                    if ($checkInHour <= 9) {
                                        echo '<span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">Present</span>';
                                    } elseif ($checkInHour <= 10) {
                                        echo '<span class="px-2 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800">Late</span>';
                                    } else {
                                        echo '<span class="px-2 py-1 text-xs font-semibold rounded-full bg-orange-100 text-orange-800">Half-day</span>';
                                    }
                                    ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <div class="flex space-x-2">
                                        <button class="edit-notes px-3 py-1 bg-green-500 text-white rounded hover:bg-green-600 transition-colors duration-200 animate__animated animate__fadeIn" 
                                                data-record-id="<?php echo $record['id']; ?>"
                                                data-notes="<?php echo htmlspecialchars($record['notes'] ?? ''); ?>"
                                                data-date="<?php echo date('Y-m-d', strtotime($record['check_in'])); ?>">
                                            <i class="fas fa-edit"></i> Edit Notes
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

    <!-- Profile Image Modal -->
    <div class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center" id="profileModal">
        <div class="bg-white rounded-lg shadow-xl max-w-md w-full mx-4">
            <div class="border-b border-gray-200 px-6 py-4">
                <div class="flex justify-between items-center">
                    <h5 class="text-xl font-bold text-gray-800">Update Profile Image</h5>
                    <button type="button" class="text-gray-400 hover:text-gray-500" id="closeProfileModal">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>
            <div class="p-6">
                <div class="mb-4">
                    <div class="bg-black rounded-lg overflow-hidden mb-4">
                        <video id="profileVideo" class="w-full h-48 object-cover" autoplay playsinline></video>
                        <canvas id="profileCanvas" class="hidden"></canvas>
                    </div>
                    <div class="flex space-x-2">
                        <button id="startProfileCamera" class="px-4 py-2 bg-primary text-white rounded-lg hover:bg-indigo-600 transition-colors duration-200">
                            <i class="fas fa-camera mr-2"></i> Start Camera
                        </button>
                        <button id="captureProfileImage" class="px-4 py-2 bg-success text-white rounded-lg hover:bg-green-600 transition-colors duration-200">
                            <i class="fas fa-camera-retro mr-2"></i> Capture
                        </button>
                    </div>
                </div>
                <form id="profileImageForm" method="post" action="">
                    <input type="hidden" name="profile_image" id="profileImageData">
                    <button type="submit" class="w-full px-4 py-2 bg-primary text-white rounded-lg hover:bg-indigo-600 transition-colors duration-200">
                        <i class="fas fa-save mr-2"></i> Save Profile Image
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Notes Modal -->
    <div class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center" id="editNotesModal">
        <div class="bg-white rounded-lg shadow-xl max-w-md w-full mx-4 animate__animated animate__fadeInDown">
            <div class="border-b border-gray-200 px-6 py-4">
                <div class="flex justify-between items-center">
                    <h5 class="text-xl font-bold text-gray-800">Edit Notes for <span id="editNotesDate"></span></h5>
                    <button type="button" class="text-gray-400 hover:text-gray-500" id="closeEditNotesModal">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>
            <div class="p-6">
                <form id="editNotesForm">
                    <input type="hidden" id="editRecordId" name="record_id">
                    <div class="mb-4">
                        <label for="editNotes" class="block text-gray-700 text-sm font-bold mb-2">Notes:</label>
                        <textarea id="editNotes" name="notes" rows="4" class="w-full px-3 py-2 text-gray-700 border rounded-lg focus:outline-none focus:border-primary" placeholder="Enter your notes for this day..."></textarea>
                    </div>
                    <div class="flex justify-end space-x-2">
                        <button type="button" id="cancelEditNotes" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-colors duration-200">
                            Cancel
                        </button>
                        <button type="submit" id="saveEditNotes" class="px-4 py-2 bg-primary text-white rounded-lg hover:bg-indigo-600 transition-colors duration-200">
                            <i class="fas fa-save mr-2"></i> Save Notes
                        </button>
                    </div>
                </form>
                <div id="editNotesMessage" class="mt-4 hidden"></div>
            </div>
        </div>
    </div>

    <script>
        // DOM elements
        const video = document.getElementById('video');
        const canvas = document.getElementById('canvas');
        const startCameraBtn = document.getElementById('startCamera');
        const stopCameraBtn = document.getElementById('stopCamera');
        const checkInBtn = document.getElementById('checkInBtn');
        const checkOutBtn = document.getElementById('checkOutBtn');
        const profileModal = document.getElementById('profileModal');
        const closeProfileModal = document.getElementById('closeProfileModal');
        const profileVideo = document.getElementById('profileVideo');
        const profileCanvas = document.getElementById('profileCanvas');
        const startProfileCameraBtn = document.getElementById('startProfileCamera');
        const captureProfileImageBtn = document.getElementById('captureProfileImage');
        const profileImageForm = document.getElementById('profileImageForm');
        const profileImageData = document.getElementById('profileImageData');
        const editNotesModal = document.getElementById('editNotesModal');
        const closeEditNotesModal = document.getElementById('closeEditNotesModal');
        const editNotesForm = document.getElementById('editNotesForm');
        const editRecordId = document.getElementById('editRecordId');
        const editNotes = document.getElementById('editNotes');
        const editNotesDate = document.getElementById('editNotesDate');
        const cancelEditNotes = document.getElementById('cancelEditNotes');
        const editNotesMessage = document.getElementById('editNotesMessage');
        
        let stream = null;
        let profileStream = null;

        // Profile modal functionality
        document.getElementById('profileImageContainer').addEventListener('click', () => {
            profileModal.classList.remove('hidden');
        });

        closeProfileModal.addEventListener('click', () => {
            profileModal.classList.add('hidden');
            if (profileStream) {
                profileStream.getTracks().forEach(track => track.stop());
                profileStream = null;
            }
        });

        // Edit notes functionality
        document.querySelectorAll('.edit-notes').forEach(btn => {
            btn.addEventListener('click', () => {
                const recordId = btn.dataset.recordId;
                const notes = btn.dataset.notes;
                const date = btn.dataset.date;
                
                editRecordId.value = recordId;
                editNotes.value = notes;
                editNotesDate.textContent = date;
                
                // Show the modal with animation
                editNotesModal.classList.remove('hidden');
                setTimeout(() => {
                    editNotesModal.querySelector('.animate__fadeInDown').classList.add('animate__animated');
                }, 10);
            });
        });
        
        closeEditNotesModal.addEventListener('click', () => {
            // Hide the modal with animation
            const modalContent = editNotesModal.querySelector('.animate__fadeInDown');
            modalContent.classList.remove('animate__fadeInDown');
            modalContent.classList.add('animate__fadeOutUp');
            
            setTimeout(() => {
                editNotesModal.classList.add('hidden');
                modalContent.classList.remove('animate__fadeOutUp');
                modalContent.classList.add('animate__fadeInDown');
            }, 500);
        });
        
        cancelEditNotes.addEventListener('click', () => {
            closeEditNotesModal.click();
        });
        
        editNotesForm.addEventListener('submit', (e) => {
            e.preventDefault();
            
            // Show loading indicator
            const saveBtn = document.getElementById('saveEditNotes');
            const originalBtnHtml = saveBtn.innerHTML;
            saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Saving...';
            saveBtn.disabled = true;
            
            // Get form data
            const recordId = editRecordId.value;
            const notes = editNotes.value;
            
            // Send AJAX request to update notes
            fetch('../includes/update_notes.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'record_id=' + encodeURIComponent(recordId) + '&notes=' + encodeURIComponent(notes)
            })
            .then(response => response.json())
            .then(data => {
                // Reset button
                saveBtn.innerHTML = originalBtnHtml;
                saveBtn.disabled = false;
                
                // Show message
                editNotesMessage.classList.remove('hidden');
                
                if (data.success) {
                    // Update the data attribute on the button
                    const editBtn = document.querySelector(`.edit-notes[data-record-id="${recordId}"]`);
                    if (editBtn) {
                        editBtn.dataset.notes = notes;
                    }
                    
                    // Show success message
                    editNotesMessage.className = 'mt-4 bg-green-100 border-l-4 border-green-500 text-green-700 p-4 animate__animated animate__fadeIn';
                    editNotesMessage.innerHTML = '<p>' + data.message + '</p>';
                    
                    // Close modal after 2 seconds
                    setTimeout(() => {
                        closeEditNotesModal.click();
                    }, 2000);
                } else {
                    // Show error message
                    editNotesMessage.className = 'mt-4 bg-red-100 border-l-4 border-red-500 text-red-700 p-4 animate__animated animate__fadeIn';
                    editNotesMessage.innerHTML = '<p>' + data.message + '</p>';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                
                // Reset button
                saveBtn.innerHTML = originalBtnHtml;
                saveBtn.disabled = false;
                
                // Show error message
                editNotesMessage.classList.remove('hidden');
                editNotesMessage.className = 'mt-4 bg-red-100 border-l-4 border-red-500 text-red-700 p-4 animate__animated animate__fadeIn';
                editNotesMessage.innerHTML = '<p>An error occurred while updating notes. Please try again.</p>';
            });
        });

        // Start camera function
        async function startCamera(videoElement) {
            try {
                const constraints = {
                    video: {
                        width: { ideal: 1280 },
                        height: { ideal: 720 },
                        facingMode: 'user'
                    }
                };
                
                const mediaStream = await navigator.mediaDevices.getUserMedia(constraints);
                videoElement.srcObject = mediaStream;
                return mediaStream;
            } catch (err) {
                console.error('Error starting camera:', err);
                alert('Failed to start camera: ' + err.message);
                return null;
            }
        }

        // Start main camera
        startCameraBtn.addEventListener('click', async () => {
            if (!stream) {
                stream = await startCamera(video);
            }
        });

        // Stop main camera
        stopCameraBtn.addEventListener('click', () => {
            if (stream) {
                stream.getTracks().forEach(track => track.stop());
                stream = null;
                video.srcObject = null;
            }
        });

        // Start profile camera
        startProfileCameraBtn.addEventListener('click', async () => {
            if (!profileStream) {
                profileStream = await startCamera(profileVideo);
            }
        });

        // Capture profile image
        captureProfileImageBtn.addEventListener('click', () => {
            if (!profileStream) {
                alert('Please start the camera first');
                return;
            }
            
            profileCanvas.width = profileVideo.videoWidth;
            profileCanvas.height = profileVideo.videoHeight;
            const ctx = profileCanvas.getContext('2d');
            ctx.drawImage(profileVideo, 0, 0, profileCanvas.width, profileCanvas.height);
            
            // Convert canvas to base64 image data
            profileImageData.value = profileCanvas.toDataURL('image/png');
            
            // Show success message
            alert('Image captured! Click Save to update your profile image.');
        });

        // Check In functionality
        if (checkInBtn) {
            checkInBtn.addEventListener('click', async () => {
                if (!stream) {
                    // Show animated error alert
                    showAlert('Please start the camera first.', 'error');
                    return;
                }
                
                // Add animation to button
                $(checkInBtn).addClass('animate__animated animate__rubberBand');
                
                // Show loading indicator
                const loadingIndicator = $(`
                    <div id="loadingIndicator" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 animate__animated animate__fadeIn">
                        <div class="bg-white rounded-lg shadow-xl p-6 max-w-md w-full mx-4 animate__animated animate__zoomIn text-center">
                            <div class="animate__animated animate__pulse animate__infinite">
                                <i class="fas fa-circle-notch fa-spin text-4xl text-indigo-600 mb-4"></i>
                            </div>
                            <h3 class="text-xl font-bold text-gray-800 mb-2">Processing Check-in</h3>
                            <p class="text-gray-600">Please wait while we process your check-in...</p>
                        </div>
                    </div>
                `);
                $('body').append(loadingIndicator);
                
                const imageData = captureImage(video, canvas);
                
                // Create form data
                const formData = new FormData();
                formData.append('image', imageData);
                
                try {
                    const response = await fetch('check_in.php', {
                        method: 'POST',
                        body: formData
                    });
                    
                    // Remove loading indicator
                    $('#loadingIndicator').addClass('animate__fadeOut');
                    setTimeout(() => {
                        $('#loadingIndicator').remove();
                    }, 500);
                    
                    const result = await response.json();
                    
                    if (result.success) {
                        // Show success message with animation
                        showAlert('Check-in successful!', 'success');
                        
                        setTimeout(() => {
                            location.reload();
                        }, 1500);
                    } else {
                        // Show error message with animation
                        showAlert('Error checking in: ' + result.message, 'error');
                    }
                } catch (err) {
                    // Remove loading indicator
                    $('#loadingIndicator').addClass('animate__fadeOut');
                    setTimeout(() => {
                        $('#loadingIndicator').remove();
                    }, 500);
                    
                    console.error('Error:', err);
                    showAlert('An error occurred during check-in. Please try again.', 'error');
                }
            });
        }
        
        // Check Out functionality
        if (checkOutBtn) {
            checkOutBtn.addEventListener('click', async () => {
                if (!stream) {
                    // Show animated error alert
                    showAlert('Please start the camera first.', 'error');
                    return;
                }
                
                // Add animation to button
                $(checkOutBtn).addClass('animate__animated animate__rubberBand');
                
                // Show loading indicator
                const loadingIndicator = $(`
                    <div id="loadingIndicator" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 animate__animated animate__fadeIn">
                        <div class="bg-white rounded-lg shadow-xl p-6 max-w-md w-full mx-4 animate__animated animate__zoomIn text-center">
                            <div class="animate__animated animate__pulse animate__infinite">
                                <i class="fas fa-circle-notch fa-spin text-4xl text-indigo-600 mb-4"></i>
                            </div>
                            <h3 class="text-xl font-bold text-gray-800 mb-2">Processing Check-out</h3>
                            <p class="text-gray-600">Please wait while we process your check-out...</p>
                        </div>
                    </div>
                `);
                $('body').append(loadingIndicator);
                
                const imageData = captureImage(video, canvas);
                
                // Create form data
                const formData = new FormData();
                formData.append('image', imageData);
                
                try {
                    const response = await fetch('check_out.php', {
                        method: 'POST',
                        body: formData
                    });
                    
                    // Remove loading indicator
                    $('#loadingIndicator').addClass('animate__fadeOut');
                    setTimeout(() => {
                        $('#loadingIndicator').remove();
                    }, 500);
                    
                    const result = await response.json();
                    
                    if (result.success) {
                        // Show success message with animation
                        showAlert('Check-out successful!', 'success');
                        
                        setTimeout(() => {
                            location.reload();
                        }, 1500);
                    } else {
                        // Show error message with animation
                        showAlert('Error checking out: ' + result.message, 'error');
                    }
                } catch (err) {
                    // Remove loading indicator
                    $('#loadingIndicator').addClass('animate__fadeOut');
                    setTimeout(() => {
                        $('#loadingIndicator').remove();
                    }, 500);
                    
                    console.error('Error:', err);
                    showAlert('An error occurred during check-out. Please try again.', 'error');
                }
            });
        }
        
        // Function to show custom alerts
        function showAlert(message, type) {
            const alertClass = type === 'success' ? 'alert-success' : 'alert-error';
            const bgColor = type === 'success' ? 'bg-green-100 border-green-500 text-green-700' : 'bg-red-100 border-red-500 text-red-700';
            const icon = type === 'success' ? 'check-circle' : 'exclamation-circle';
            
            const alert = $(`
                <div class="fixed top-4 right-4 max-w-md w-full animate__animated animate__fadeInRight ${bgColor} border-l-4 p-4 rounded shadow-md z-50">
                    <div class="flex items-center">
                        <i class="fas fa-${icon} mr-3"></i>
                        <span>${message}</span>
                    </div>
                </div>
            `);
            
            $('body').append(alert);
            
            // Automatically remove the alert after 3 seconds
            setTimeout(function() {
                alert.removeClass('animate__fadeInRight').addClass('animate__fadeOutRight');
                setTimeout(function() {
                    alert.remove();
                }, 500);
            }, 3000);
        }
    </script>

    <script>
        $(document).ready(function() {
            // Add hover effects to all buttons
            $('button:not(.animate__animated), a:not(.animate__animated)').addClass('btn-hover');
            
            // DOM elements
            const video = document.getElementById('video');
            const canvas = document.getElementById('canvas');
            const startCameraBtn = document.getElementById('startCamera');
            const stopCameraBtn = document.getElementById('stopCamera');
            const checkInBtn = document.getElementById('checkInBtn');
            const checkOutBtn = document.getElementById('checkOutBtn');
            
            let stream = null;
            
            // Function to start camera with animation
            async function startCamera(videoElement) {
                try {
                    const constraints = {
                        video: {
                            width: { ideal: 1280 },
                            height: { ideal: 720 },
                            facingMode: 'user'
                        }
                    };
                    
                    const mediaStream = await navigator.mediaDevices.getUserMedia(constraints);
                    videoElement.srcObject = mediaStream;
                    
                    // Add animation to video element
                    $(videoElement).parent().addClass('animate__animated animate__fadeIn');
                    
                    return mediaStream;
                } catch (err) {
                    console.error('Error accessing camera:', err);
                    alert('Error accessing camera: ' + err.message);
                    return null;
                }
            }
            
            // Function to stop camera
            function stopCamera(videoElement, mediaStream) {
                if (mediaStream) {
                    mediaStream.getTracks().forEach(track => track.stop());
                    videoElement.srcObject = null;
                }
            }
            
            // Function to capture image
            function captureImage(videoElement, canvasElement) {
                const context = canvasElement.getContext('2d');
                canvasElement.width = videoElement.videoWidth;
                canvasElement.height = videoElement.videoHeight;
                context.drawImage(videoElement, 0, 0, canvasElement.width, canvasElement.height);
                
                return canvasElement.toDataURL('image/jpeg');
            }
            
            // Start camera button click
            startCameraBtn.addEventListener('click', async () => {
                if (!stream) {
                    $(startCameraBtn).addClass('animate__animated animate__pulse');
                    stream = await startCamera(video);
                    if (stream) {
                        $(startCameraBtn).addClass('bg-gray-400').removeClass('bg-primary hover:bg-indigo-600');
                        $(stopCameraBtn).addClass('bg-danger text-white').removeClass('bg-gray-200 text-gray-700');
                    }
                }
            });

            // Stop camera button click
            stopCameraBtn.addEventListener('click', () => {
                $(stopCameraBtn).addClass('animate__animated animate__pulse');
                stopCamera(video, stream);
                stream = null;
                $(startCameraBtn).removeClass('bg-gray-400').addClass('bg-primary hover:bg-indigo-600');
                $(stopCameraBtn).removeClass('bg-danger text-white').addClass('bg-gray-200 text-gray-700');
            });

            // Check in button click
            if (checkInBtn) {
                checkInBtn.addEventListener('click', async () => {
                    if (!stream) {
                        alert('Please start the camera first');
                        return;
                    }
                    
                    // Add animation to button
                    $(checkInBtn).addClass('animate__animated animate__rubberBand');
                    
                    const imageData = captureImage(video, canvas);
                    
                    // Create form data
                    const formData = new FormData();
                    formData.append('image', imageData);
                    
                    try {
                        const response = await fetch('check_in.php', {
                            method: 'POST',
                            body: formData
                        });
                        
                        const result = await response.text();
                        
                        if (result.includes('success')) {
                            // Show success message with animation
                            const successAlert = $('<div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 animate__animated animate__fadeInDown" role="alert"><p>Check-in successful!</p></div>');
                            $('.container').prepend(successAlert);
                            
                            setTimeout(() => {
                                location.reload();
                            }, 1500);
                        } else {
                            alert('Error checking in: ' + result);
                        }
                    } catch (err) {
                        console.error('Error:', err);
                        alert('Error checking in: ' + err.message);
                    }
                });
            }
            
            // Check out button click
            if (checkOutBtn) {
                checkOutBtn.addEventListener('click', async () => {
                    if (!stream) {
                        alert('Please start the camera first');
                        return;
                    }
                    
                    // Add animation to button
                    $(checkOutBtn).addClass('animate__animated animate__rubberBand');
                    
                    const imageData = captureImage(video, canvas);
                    
                    // Create form data
                    const formData = new FormData();
                    formData.append('image', imageData);
                    
                    try {
                        const response = await fetch('check_out.php', {
                            method: 'POST',
                            body: formData
                        });
                        
                        const result = await response.text();
                        
                        if (result.includes('success')) {
                            // Show success message with animation
                            const successAlert = $('<div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 animate__animated animate__fadeInDown" role="alert"><p>Check-out successful!</p></div>');
                            $('.container').prepend(successAlert);
                            
                            setTimeout(() => {
                                location.reload();
                            }, 1500);
                        } else {
                            alert('Error checking out: ' + result);
                        }
                    } catch (err) {
                        console.error('Error:', err);
                        alert('Error checking out: ' + err.message);
                    }
                });
            }
            
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
