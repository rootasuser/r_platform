<?php
require_once __DIR__ . '/../config/Database.php';

class User {
    private $conn;

    public function __construct() {
        $db = new Database();
        $this->conn = $db->getConnection();
    }

    /**
     * LOGIN USER
     */
    public function login($emailOrContact, $password)
    {
        try {
            $stmt = $this->conn->prepare("SELECT * FROM users WHERE email = ? OR contact_number = ?");
            if (!$stmt) {
                throw new Exception("Db Err: " . $this->conn->error);
            }
    
            $stmt->bind_param("ss", $emailOrContact, $emailOrContact);
            $stmt->execute();
            $result = $stmt->get_result();
    
            if ($result->num_rows === 1) {
                $user = $result->fetch_assoc();
    
                if (password_verify($password, $user['password'])) {
                    if (session_status() === PHP_SESSION_NONE) {
                        session_start();
                    }
    
                    $updateStatusStmt = $this->conn->prepare("UPDATE users SET status = ? WHERE id = ?");
                    if (!$updateStatusStmt) {
                        throw new Exception("Db Err: " . $this->conn->error);
                    }
                    $status = 'Active';
                    $updateStatusStmt->bind_param("si", $status, $user['id']);
                    $updateStatusStmt->execute();
    
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['first_name'] = $user['first_name'];
                    $_SESSION['last_name'] = $user['last_name'];
                    $_SESSION['email'] = $user['email'];
                    $_SESSION['contact_number'] = $user['contact_number'];
    
                    header("Location: ../view/dashboard.php");
                    exit();
                } else {
                    return "Incorrect password.";
                }
            } else {
                return "No account found with provided credentials.";
            }
        } catch (Exception $e) {
            return "Login failed: " . $e->getMessage();
        }
    }
    
    
    /**
     * REGISTER USER
     */
    public function register($firstName, $lastName, $email, $contactNumber, $password) {
        $stmt = $this->conn->prepare("SELECT * FROM users WHERE email = ? OR contact_number = ?");
        $stmt->bind_param("ss", $email, $contactNumber);
        $stmt->execute();
        $result = $stmt->get_result();
    
        if ($result->num_rows > 0) {
            return "Email or contact number already registered.";
        }
    
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    
        $stmt = $this->conn->prepare("INSERT INTO users (first_name, last_name, email, contact_number, password, status) VALUES (?, ?, ?, ?, ?, ?)");
        $status = 'Offline'; 
        $stmt->bind_param("ssssss", $firstName, $lastName, $email, $contactNumber, $hashedPassword, $status);
    
        if ($stmt->execute()) {
            return "success";
        } else {
            return "Registration failed. Please try again.";
        }
    }
    
    /**
     * GET USER BY ID INTO DASHBOARD
     */
    public function getUserById($id) {
        try {
            $stmt = $this->conn->prepare("SELECT * FROM users WHERE id = ?");
    
            if (!$stmt) {
                throw new Exception("DB Err: " . $this->conn->error);
            }
    
            $stmt->bind_param("i", $id);
            $stmt->execute();
    
            $result = $stmt->get_result();
    
            if ($result->num_rows === 1) {
                $user = $result->fetch_assoc();
    
                $user['profile_picture'] = $user['profile_picture'];  
                $user['cover_photo'] = $user['cover_photo'];  
                
                return $user;
            } else {
                return null;
            }
        } catch (Exception $e) {
            return ["error" => "Failed to fetch user: " . $e->getMessage()]; 
        }
    }
    
    
    
 /**
 * UPDATE OR INSERT USER PROFILE
 */
public function updateOrInsertProfile($userId, $firstName, $lastName, $contactNumber, $email, $password, $profilePictureBlob, $coverPhotoBlob) {
    $query = "UPDATE users SET 
                 first_name = ?, 
                 last_name = ?, 
                 contact_number = ?, 
                 email = ?, 
                 password = ?, 
                 profile_picture = ?, 
                 cover_photo = ?, 
                 updated_at = CURRENT_TIMESTAMP 
               WHERE id = ?";
    
    $stmt = $this->conn->prepare($query);
    $stmt->bind_param('sssssssi', $firstName, $lastName, $contactNumber, $email, $password, $profilePictureBlob, $coverPhotoBlob, $userId);
    
    return $stmt->execute();
    
    }


    
}