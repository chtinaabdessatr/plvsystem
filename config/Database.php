<?php
class Database {
    private $host = "localhost";
    private $db_name = "lap_plv";
    private $username = "root";
    private $password = ""; // CHANGE THIS TO YOUR DB PASSWORD

    public function getConnection() {
        $conn = null;
        try {
            $conn = new PDO("mysql:host=" . $this->host . ";dbname=" . $this->db_name, $this->username, $this->password);
            $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        } catch(PDOException $exception) {
            echo "Connection error: " . $exception->getMessage();
        }
        return $conn;
    }
}
?>