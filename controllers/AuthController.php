<?php
require_once 'models/User.php';

class AuthController {
    public function login() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $db = (new Database())->getConnection();
            $userModel = new User($db);
            $user = $userModel->login($_POST['email']);

            if ($user && password_verify($_POST['password'], $user['password'])) {
                
                // 🔴 FIX: Replace die() with a proper redirect
                if ($user['is_active'] == 0) {
                    header("Location: /plvsystem/auth/login?error=deactivated");
                    exit;
                }
                
                // Store temp session for OTP
                $_SESSION['temp_user'] = $user;
                // Generate simple OTP (In real life, save to DB and Email it)
                $_SESSION['temp_otp'] = "123456"; 
                
                header("Location: /plvsystem/auth/otp");
                exit; // Always add exit after a header redirect
                
            } else {
                // 🔴 FIX: Replace echo with a proper redirect
                header("Location: /plvsystem/auth/login?error=credentials");
                exit;
            }
        } else {
            require 'views/auth/login.php';
        }
    }

    public function otp() {
        require 'views/auth/otp.php';
    }

    public function verifyOtp() {
        if ($_POST['otp_code'] === $_SESSION['temp_otp']) {
            
            $user = $_SESSION['temp_user'];
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['name'] = $user['name'];
            $_SESSION['lang'] = $user['language'] ?? 'fr';
            
            unset($_SESSION['temp_user']);
            unset($_SESSION['temp_otp']);
            
            header("Location: /plvsystem/dashboard");
            exit;
        } else {
            // 🔴 FIX: Replace echo with a redirect back to the OTP page
            header("Location: /plvsystem/auth/otp?error=otp");
            exit;
        }
    }

    public function logout() {
        session_destroy();
        header("Location: /plvsystem/auth/login");
        exit;
    }
}
?>