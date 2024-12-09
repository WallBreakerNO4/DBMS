<?php
class Database {
    private $host = "localhost";
    private $db_name = "inventory_system";
    private $username = "inventory_user";
    private $password = "your_password";
    public $conn;

    public function getConnection() {
        $this->conn = null;
        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name,
                $this->username,
                $this->password
            );
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->exec("set names utf8");
        } catch(PDOException $e) {
            echo "连接错误: " . $e->getMessage();
        }
        return $this->conn;
    }
}
?> 