-- Create database
CREATE DATABASE IF NOT EXISTS attendance_db;
USE attendance_db;

-- Create employees table
CREATE TABLE IF NOT EXISTS employees (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id VARCHAR(20) UNIQUE NOT NULL,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    position VARCHAR(100),
    department VARCHAR(100),
    profile_image VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Create admin table
CREATE TABLE IF NOT EXISTS admins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Create attendance records table
CREATE TABLE IF NOT EXISTS attendance_records (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT NOT NULL,
    check_in DATETIME,
    check_out DATETIME,
    check_in_image VARCHAR(255),
    check_out_image VARCHAR(255),
    status ENUM('present', 'late', 'half-day', 'absent') DEFAULT 'present',
    note TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE
);

-- Insert default admin
INSERT INTO admins (username, email, password) VALUES 
('admin', 'admin@example.com', '$2y$10$8tPRwX0jAEwQmGm0jVm.5OcN1ykQECjUEwC9.vnhNbVl2qqM0sKOO'); -- Default password: admin123

-- Insert sample employees
INSERT INTO employees (employee_id, first_name, last_name, email, password, position, department) VALUES
('EMP001', 'John', 'Doe', 'john.doe@example.com', '$2y$10$8tPRwX0jAEwQmGm0jVm.5OcN1ykQECjUEwC9.vnhNbVl2qqM0sKOO', 'Software Engineer', 'IT'),
('EMP002', 'Jane', 'Smith', 'jane.smith@example.com', '$2y$10$8tPRwX0jAEwQmGm0jVm.5OcN1ykQECjUEwC9.vnhNbVl2qqM0sKOO', 'HR Manager', 'Human Resources'),
('EMP003', 'Ahmed', 'Khan', 'ahmed.khan@example.com', '$2y$10$8tPRwX0jAEwQmGm0jVm.5OcN1ykQECjUEwC9.vnhNbVl2qqM0sKOO', 'Accountant', 'Finance');
-- Note: Default password for all users is 'admin123'
