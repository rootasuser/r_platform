
<?php
class Model {
    protected $conn;

    public function __construct() {
        $this->conn = new mysqli("localhost", "root", "", "your_database_name");
        if ($this->conn->connect_error) {
            die("Connection failed: " . $this->conn->connect_error);
        }
    }
}
