<?php
class Setting {
    private $conn;

    public function __construct($db) {
        $this->conn = $db;
    }

    // --- 📥 GET ALL SETTINGS ---
    public function getAll() {
        $stmt = $this->conn->query("SELECT * FROM settings");
        $result = [];
        foreach($stmt->fetchAll() as $row) {
            $result[$row['setting_key']] = $row['setting_value'];
        }
        return $result;
    }

    // --- 💾 SAVE A SETTING ---
    public function update($key, $value) {
        // This is a clever SQL trick: If the key exists, update it. If not, insert it!
        $stmt = $this->conn->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = ?");
        return $stmt->execute([$key, $value, $value]);
    }
}
?>