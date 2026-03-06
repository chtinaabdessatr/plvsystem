<?php
require_once 'models/User.php';
require_once 'models/Log.php';

class AccountController {
    private $userModel;
    private $logModel;

    public function __construct() {
        // Kick them out if not logged in
        if (!isset($_SESSION['user_id'])) {
            header("Location: /plvsystem/auth/login");
            exit;
        }
        $db = (new Database())->getConnection();
        $this->userModel = new User($db);
        $this->logModel = new Log($db);
    }

    // --- 👤 DISPLAY ACCOUNT PAGE ---
    public function index() {
        // Fetch fresh data for the logged-in user
        $user = $this->userModel->findById($_SESSION['user_id']);
        require 'views/account/index.php';
    }

    // --- 💾 SAVE PROFILE UPDATES ---
    public function update() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $id = $_SESSION['user_id'];
            $name = trim($_POST['name']);
            $email = trim($_POST['email']);
            
            // Only hash the password if they actually typed a new one
            $password = !empty($_POST['password']) ? password_hash($_POST['password'], PASSWORD_DEFAULT) : null;

            // We need to fetch their current role/status so we don't accidentally overwrite it
            $currentUser = $this->userModel->findById($id);

            // Update user in DB
            $this->userModel->update($id, $name, $email, $currentUser['role'], $currentUser['is_active'], $password);

            // Update the Session so the name in the top right corner changes instantly!
            $_SESSION['name'] = $name;

            // 🕵️‍♂️ LOG ACTION: Profile Updated
            $this->logModel->logAction($id, 'MISE À JOUR PROFIL', "L'utilisateur a mis à jour ses informations personnelles.");

            // Redirect back with a success message
            header("Location: /plvsystem/account?msg=success");
            exit;
        }
    }
}
?>