
<?php
class Model {
    protected $conn;

    public function __construct() {
        $this->conn = new mysqli("localhost", "root", "", "r_platform");
        if ($this->conn->connect_error) {
            die("Conn failed: " . $this->conn->connect_error);
        }
    }
}
