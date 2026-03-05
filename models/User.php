<?php
class User {
    private $conn;
    public function __construct($db) { $this->conn = $db; }

    public function login($email) {
        $stmt = $this->conn->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getAll() {
        $stmt = $this->conn->query("SELECT * FROM users ORDER BY id DESC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // --- NEW: Find single user by ID ---
    public function findById($id) {
        $stmt = $this->conn->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function create($name, $email, $password, $role) {
        try {
            $stmt = $this->conn->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)");
            return $stmt->execute([$name, $email, $password, $role]);
        } catch (PDOException $e) { return false; }
    }

    // --- NEW: Update User ---
    public function update($id, $name, $email, $role, $is_active, $password = null) {
        if ($password) {
            // Update everything INCLUDING password
            $stmt = $this->conn->prepare("UPDATE users SET name=?, email=?, role=?, is_active=?, password=? WHERE id=?");
            return $stmt->execute([$name, $email, $role, $is_active, $password, $id]);
        } else {
            // Update everything EXCEPT password
            $stmt = $this->conn->prepare("UPDATE users SET name=?, email=?, role=?, is_active=? WHERE id=?");
            return $stmt->execute([$name, $email, $role, $is_active, $id]);
        }
    }

    public function toggleStatus($id) {
        $stmt = $this->conn->prepare("UPDATE users SET is_active = NOT is_active WHERE id = ?");
        return $stmt->execute([$id]);
    }

    public function getDesigners() {
        return $this->conn->query("SELECT * FROM users WHERE role = 'designer' AND is_active = 1")->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function getPrinters() {
        return $this->conn->query("SELECT * FROM users WHERE role = 'printer' AND is_active = 1")->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getDelivery() {
        return $this->conn->query("SELECT * FROM users WHERE role = 'delivery' AND is_active = 1")->fetchAll(PDO::FETCH_ASSOC);
    }
    // --- 🗑️ DELETE USER ---
    public function deleteUser($id) {
        // Hard delete the user from the database
        $stmt = $this->conn->prepare("DELETE FROM users WHERE id = ?");
        return $stmt->execute([$id]);
    }
}
?>