<?php
require_once 'models/User.php';

class UserController {
    private $userModel;

    public function __construct() {
        if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') die("Access Denied");
        $db = (new Database())->getConnection();
        $this->userModel = new User($db);
    }

    public function index() {
        $users = $this->userModel->getAll();
        require 'views/admin/users.php';
    }

    public function create() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
            $this->userModel->create($_POST['name'], $_POST['email'], $password, $_POST['role']);
            header("Location: /plvsystem/user/index");
        } else {
            require 'views/admin/create_user.php';
        }
    }

    public function toggleActive($id) {
        $this->userModel->toggleStatus($id);
        header("Location: /plvsystem/user/index");
    }
}
?>