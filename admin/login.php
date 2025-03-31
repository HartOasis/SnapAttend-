<?php
// Start session
session_start();

// Redirect to dashboard if already logged in
if (isset($_SESSION['admin_id'])) {
    header("Location: dashboard.php");
    exit;
}

// Include functions
require_once '../includes/functions.php';

// Check for login attempt
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get login credentials
    $username = sanitize($_POST['username']);
    $password = $_POST['password'];
    
    // Validate input
    if (empty($username) || empty($password)) {
        $error = "Please enter both username and password";
    } else {
        // Get admin by username
        $admin = getAdminByUsername($username, $password);
        
        if ($admin) {
            // Password is correct, start a new session
            session_start();
            
            // Store data in session variables
            $_SESSION['admin_id'] = $admin['id'];
            $_SESSION['admin_username'] = $admin['username'];
            
            // Redirect to dashboard
            header("Location: dashboard.php");
            exit;
        } else {
            $error = "Invalid username or password";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - Attendance System</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Animate.css -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #4e54c8, #8f94fb);
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .login-container {
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.2);
            overflow: hidden;
            width: 400px;
            max-width: 100%;
            animation: fadeIn 1s ease-in-out;
        }
        .login-header {
            background-color: #4e54c8;
            color: white;
            padding: 30px;
            text-align: center;
            border-radius: 10px 10px 0 0;
        }
        .login-header h2 {
            margin: 0;
            font-weight: 600;
        }
        .login-header p {
            margin: 10px 0 0;
            opacity: 0.8;
        }
        .login-form {
            padding: 30px;
        }
        .form-group {
            margin-bottom: 20px;
            position: relative;
        }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #333;
        }
        .form-group input {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
            transition: all 0.3s ease;
        }
        .form-group input:focus {
            border-color: #4e54c8;
            box-shadow: 0 0 10px rgba(78, 84, 200, 0.3);
            outline: none;
        }
        .form-group i {
            position: absolute;
            top: 42px;
            left: 15px;
            color: #6c757d;
        }
        .form-group input[type="text"],
        .form-group input[type="password"] {
            padding-left: 40px;
        }
        .btn-login {
            background-color: #4e54c8;
            border: none;
            color: white;
            padding: 12px;
            width: 100%;
            font-size: 16px;
            font-weight: 600;
            border-radius: 5px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .btn-login:hover {
            background-color: #3f44a9;
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(78, 84, 200, 0.3);
        }
        .employee-link {
            text-align: center;
            margin-top: 20px;
        }
        .employee-link a {
            color: #4e54c8;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        .employee-link a:hover {
            color: #3f44a9;
            text-decoration: underline;
        }
        .error-message {
            background-color: #f8d7da;
            color: #721c24;
            padding: 10px 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            animation: shake 0.5s ease-in-out;
        }
        .logo {
            width: 80px;
            height: 80px;
            background-color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
            animation: bounce 2s infinite;
        }
        .logo i {
            font-size: 40px;
            color: #4e54c8;
        }
        
        /* Loading overlay */
        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.7);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 9999;
            opacity: 0;
            visibility: hidden;
            transition: opacity 0.3s, visibility 0.3s;
        }
        .loading-overlay.active {
            opacity: 1;
            visibility: visible;
        }
        .loading-spinner {
            width: 60px;
            height: 60px;
            border: 5px solid rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            border-top-color: #fff;
            animation: spin 1s ease-in-out infinite;
        }
        .loading-text {
            color: white;
            margin-top: 15px;
            font-size: 18px;
            font-weight: 500;
        }
        
        /* Animation keyframes */
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        @keyframes shake {
            0%, 100% {
                transform: translateX(0);
            }
            10%, 30%, 50%, 70%, 90% {
                transform: translateX(-5px);
            }
            20%, 40%, 60%, 80% {
                transform: translateX(5px);
            }
        }
        
        @keyframes bounce {
            0%, 20%, 50%, 80%, 100% {
                transform: translateY(0);
            }
            40% {
                transform: translateY(-20px);
            }
            60% {
                transform: translateY(-10px);
            }
        }
        
        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }
    </style>
</head>
<body>
    <!-- Loading Overlay -->
    <div class="loading-overlay" id="loadingOverlay">
        <div class="text-center">
            <div class="loading-spinner"></div>
            <div class="loading-text animate__animated animate__fadeIn animate__infinite">Authenticating...</div>
        </div>
    </div>

    <div class="login-container">
        <div class="login-header">
            <div class="logo">
                <i class="fas fa-user-shield"></i>
            </div>
            <h2 class="animate__animated animate__fadeInDown">Admin Login</h2>
            <p class="animate__animated animate__fadeInUp">Access the admin dashboard</p>
        </div>
        
        <div class="login-form">
            <?php if (isset($error)): ?>
                <div class="error-message">
                    <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" id="loginForm">
                <div class="form-group animate__animated animate__fadeInLeft">
                    <label for="username">Username</label>
                    <i class="fas fa-user"></i>
                    <input type="text" id="username" name="username" placeholder="Enter your username" required>
                </div>
                
                <div class="form-group animate__animated animate__fadeInRight">
                    <label for="password">Password</label>
                    <i class="fas fa-lock"></i>
                    <input type="password" id="password" name="password" placeholder="Enter your password" required>
                </div>
                
                <button type="submit" class="btn-login animate__animated animate__fadeInUp">
                    <i class="fas fa-sign-in-alt"></i> Login
                </button>
            </form>
            
            <div class="employee-link animate__animated animate__fadeIn" style="animation-delay: 0.5s;">
                <a href="../index.php">Login as Employee</a>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Add animation to form elements on load
        document.addEventListener('DOMContentLoaded', function() {
            const loginForm = document.getElementById('loginForm');
            const loadingOverlay = document.getElementById('loadingOverlay');
            
            // Add loading overlay when form is submitted
            loginForm.addEventListener('submit', function(e) {
                // Only show loading if form is valid
                if (loginForm.checkValidity()) {
                    loadingOverlay.classList.add('active');
                }
            });
            
            // Add animation to the login button on hover
            const loginBtn = document.querySelector('.btn-login');
            loginBtn.addEventListener('mouseenter', function() {
                this.classList.add('animate__animated', 'animate__pulse');
            });
            
            loginBtn.addEventListener('mouseleave', function() {
                this.classList.remove('animate__animated', 'animate__pulse');
            });
            
            // Add animation to input fields on focus
            const inputs = document.querySelectorAll('input');
            inputs.forEach(input => {
                input.addEventListener('focus', function() {
                    this.parentElement.classList.add('animate__animated', 'animate__pulse');
                });
                
                input.addEventListener('blur', function() {
                    this.parentElement.classList.remove('animate__animated', 'animate__pulse');
                });
            });
        });
    </script>
</body>
</html>
