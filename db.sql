SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

-- 1. Users
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL UNIQUE,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','commercial','designer','printer','delivery') NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`id`)
);

-- 2. Orders
CREATE TABLE `orders` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `client_name` varchar(100) NOT NULL,
  `plv_type` varchar(100) NOT NULL,
  `description` text,
  `deadline` datetime DEFAULT NULL,
  `priority` enum('low','medium','high','urgent') DEFAULT 'medium',
  `current_stage` enum('created','design','printing','delivery','completed') DEFAULT 'created',
  `status` enum('pending','assigned','in_progress','review','approved') DEFAULT 'pending',
  `created_by` int(11) NOT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
);

-- 3. Assignments (Workflow Engine)
CREATE TABLE `assignments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `order_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `stage` enum('design','printing','delivery') NOT NULL,
  `status` enum('pending','accepted','refused','completed') DEFAULT 'pending',
  `refusal_reason` text,
  `start_time` datetime DEFAULT NULL,
  `end_time` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
);

-- 4. Files
CREATE TABLE `order_files` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `order_id` int(11) NOT NULL,
  `uploaded_by` int(11) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `stage` varchar(50) NOT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
);

-- 5. OTP Codes
CREATE TABLE `otp_codes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `email` varchar(100) NOT NULL,
  `code` varchar(6) NOT NULL,
  `expires_at` datetime NOT NULL,
  PRIMARY KEY (`id`)
);

-- Default Admin (Pass: admin123)
INSERT INTO `users` (`name`, `email`, `password`, `role`) VALUES
('Super Admin', 'admin@lap.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin'),
('John Designer', 'design@lap.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'designer'),
('Mike Printer', 'print@lap.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'printer');
COMMIT;