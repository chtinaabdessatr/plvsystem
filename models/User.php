<?php
class User {
    private $conn;
    public function __construct($db) { $this->conn = $db; }

    public function login($email) {
        $stmt = $this->conn->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        return $stmt->fetch();
    }

    public function getAll() {
        return $this->conn->query("SELECT * FROM users ORDER BY id DESC")->fetchAll();
    }

    public function create($name, $email, $password, $role) {
        try {
            $stmt = $this->conn->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)");
            return $stmt->execute([$name, $email, $password, $role]);
        } catch (PDOException $e) { return false; }
    }

    public function toggleStatus($id) {
        $stmt = $this->conn->prepare("UPDATE users SET is_active = NOT is_active WHERE id = ?");
        return $stmt->execute([$id]);
    }

    public function getDesigners() {
        return $this->conn->query("SELECT * FROM users WHERE role = 'designer' AND is_active = 1")->fetchAll();
    }
    
    public function getPrinters() {
        return $this->conn->query("SELECT * FROM users WHERE role = 'printer' AND is_active = 1")->fetchAll();
    }

    public function getDelivery() {
        return $this->conn->query("SELECT * FROM users WHERE role = 'delivery' AND is_active = 1")->fetchAll();
    }
}
?>