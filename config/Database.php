<?php
class Database {
    private $host = "localhost";
    private $username = "root";
    private $password = "";
    private $database = "r_platform";
    private $conn;

    public function getConnection() {
        if (!$this->conn) {
            $this->conn = new mysqli($this->host, $this->username, $this->password, $this->database);
            if ($this->conn->connect_error) {
                die("Connection failed: " . $this->conn->connect_error);
            }
        }
        return $this->conn;
    }
}
