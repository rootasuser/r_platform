<?php

error_reporting(E_ALL);
ini_set('display_errors',0);

header('Content-Type: application/json; charset=utf-8');
session_start();

require_once __DIR__ . '/../config/Database.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(["success" => false, "error" => "User not logged in."]);
    exit();
}

$db = new Database();
$conn = $db->getConnection();

if (isset($_GET['q'])) {
    $q = $_GET['q'];
    $qParam = "%" . $q . "%";
    
    // Prepare query: search by first name, last name, or full name
    $stmt = $conn->prepare("
        SELECT id, first_name, last_name, profile_picture 
        FROM users 
        WHERE first_name LIKE ? 
          OR last_name LIKE ? 
          OR CONCAT(first_name, ' ', last_name) LIKE ?
    ");
    
    if (!$stmt) {
        echo json_encode(["success" => false, "error" => "Error preparing statement: " . $conn->error]);
        exit();
    }
    
    $stmt->bind_param("sss", $qParam, $qParam, $qParam);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $users = [];
    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
    }
    
    echo json_encode(["success" => true, "users" => $users]);
    exit();
} else {
    echo json_encode(["success" => false, "error" => "No search query provided."]);
    exit();
}

