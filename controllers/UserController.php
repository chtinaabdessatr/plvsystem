<?php
// 1. START SESSION IMMEDIATELY (Fixes the logout issue)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'models/User.php';
require_once 'models/Log.php'; // 🕵️‍♂️ ENTERPRISE LOGGING ADDED
if(!class_exists('Database')) require_once 'config/Database.php';

class UserController {
    private $userModel;
    private $logModel; // 🕵️‍♂️ Added Log property

    public function __construct() {
        // 2. NOW this check will work correctly because session is loaded
        if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
            header("Location: /plvsystem/auth/login");
            exit;
        }

        $db = (new Database())->getConnection();
        $this->userModel = new User($db);
        $this->logModel = new Log($db); // 🕵️‍♂️ Initialize Logger
    }

    public function index() {
        $users = $this->userModel->getAll();
        require 'views/admin/users.php'; 
    }

    public function create() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
            $this->userModel->create($_POST['name'], $_POST['email'], $password, $_POST['role']);
            
            // 🕵️‍♂️ LOG ACTION: User Created
            $this->logModel->logAction($_SESSION['user_id'], 'CRÉATION UTILISATEUR', "Création du compte pour: {$_POST['name']} (Rôle: {$_POST['role']})");

            header("Location: /plvsystem/user/index");
            exit;
        } else {
            // Fetch users for the side table
            $users = $this->userModel->getAll();
            require 'views/admin/create_user.php';
        }
    }

    public function edit($id) {
        $user = $this->userModel->findById($id);
        if (!$user) {
            die("User not found.");
        }
        require 'views/admin/edit_user.php';
    }

    public function update($id) {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $name = $_POST['name'];
            $email = $_POST['email'];
            $role = $_POST['role'];
            $is_active = isset($_POST['is_active']) ? $_POST['is_active'] : 1;
            
            $password = !empty($_POST['password']) ? password_hash($_POST['password'], PASSWORD_DEFAULT) : null;

            $this->userModel->update($id, $name, $email, $role, $is_active, $password);
            
            // 🕵️‍♂️ LOG ACTION: User Updated
            $this->logModel->logAction($_SESSION['user_id'], 'MODIFICATION UTILISATEUR', "Mise à jour du profil de: $name (ID: $id)");

            header("Location: /plvsystem/user/index");
            exit;
        }
    }

    public function toggleActive($id) {
        $this->userModel->toggleStatus($id);
        
        // 🕵️‍♂️ LOG ACTION: User Status Toggled
        $this->logModel->logAction($_SESSION['user_id'], 'STATUT UTILISATEUR', "Changement d'état (Actif/Inactif) pour l'utilisateur ID: $id");

        header("Location: /plvsystem/user/index");
        exit;
    }
    
    // --- 🗑️ DELETE USER ROUTE ---
    public function delete($id) {
        // 1. Security Check: Only Admins can delete
        if ($_SESSION['role'] !== 'admin') {
            header("Location: /plvsystem/dashboard");
            exit;
        }

        // 2. Safety Check: Don't let the Admin delete themselves!
        if ($id == $_SESSION['user_id']) {
            // Redirect back with an error
            header("Location: /plvsystem/user?error=self_delete");
            exit;
        }

        // 🕵️‍♂️ LOG ACTION: User Deleted (Do this BEFORE deleting so we can record it)
        $this->logModel->logAction($_SESSION['user_id'], 'SUPPRESSION UTILISATEUR', "Suppression définitive du compte utilisateur ID: $id");

        // 3. Delete and redirect with success message
        $this->userModel->deleteUser($id);
        header("Location: /plvsystem/user?msg=deleted");
        exit;
    }
}
?>