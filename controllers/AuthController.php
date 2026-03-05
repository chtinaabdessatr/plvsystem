<?php
require_once 'models/User.php';
require_once 'models/Log.php'; // 🕵️‍♂️ ENTERPRISE LOGGING ADDED

class AuthController {
    private $logModel; // 🕵️‍♂️ Added Log property

    public function __construct() {
        // Initialize the logger for all auth actions
        $db = (new Database())->getConnection();
        $this->logModel = new Log($db);
    }

    public function login() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $db = (new Database())->getConnection();
            $userModel = new User($db);
            $email = trim($_POST['email']);
            $user = $userModel->login($email);

            if ($user && password_verify($_POST['password'], $user['password'])) {
                
                if ($user['is_active'] == 0) {
                    // 🕵️‍♂️ LOG ACTION: Blocked deactivated account
                    $this->logModel->logAction($user['id'], 'CONNEXION REFUSÉE', "Tentative de connexion bloquée : Compte inactif.");
                    
                    header("Location: /plvsystem/auth/login?error=deactivated");
                    exit;
                }
                
                // Store temp session for OTP
                $_SESSION['temp_user'] = $user;
                // Generate simple OTP (In real life, save to DB and Email it)
                $_SESSION['temp_otp'] = "123456"; 
                
                header("Location: /plvsystem/auth/otp");
                exit; 
                
            } else {
                // 🕵️‍♂️ LOG ACTION: Failed password or invalid email
                // If user doesn't exist, we log 'null' for user_id but record the email they tried
                $userId = $user ? $user['id'] : null;
                $this->logModel->logAction($userId, 'TENTATIVE ÉCHOUÉE', "Échec d'authentification pour l'email : $email");
                
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
            
            // 🕵️‍♂️ LOG ACTION: Successful Login
            $this->logModel->logAction($_SESSION['user_id'], 'CONNEXION RÉUSSIE', "L'utilisateur s'est connecté au système avec succès.");
            
            header("Location: /plvsystem/dashboard");
            exit;
        } else {
            // 🕵️‍♂️ LOG ACTION: Failed OTP
            if (isset($_SESSION['temp_user'])) {
                $this->logModel->logAction($_SESSION['temp_user']['id'], 'ÉCHEC OTP', "Code de sécurité à 6 chiffres incorrect.");
            }
            
            header("Location: /plvsystem/auth/otp?error=otp");
            exit;
        }
    }

    public function logout() {
        // 🕵️‍♂️ LOG ACTION: Logout (Must happen BEFORE destroying the session!)
        if (isset($_SESSION['user_id'])) {
            $this->logModel->logAction($_SESSION['user_id'], 'DÉCONNEXION', "L'utilisateur s'est déconnecté manuellement.");
        }
        
        session_destroy();
        header("Location: /plvsystem/auth/login");
        exit;
    }
}
?>