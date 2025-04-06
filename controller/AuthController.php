<?php
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../model/User.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$user = new User();

class AuthController {

    private $userModel;

    public function __construct() {
        $this->userModel = new User();
    }

    // Login Function
    public function login() {
        if (isset($_POST['loginBtn'])) {
            $emailOrContact = trim($_POST['emailOrContactNumber']);
            $password = trim($_POST['password']);

            if (empty($emailOrContact) || empty($password)) {
                $_SESSION['message'] = "All fields are required.";
            } else {
                $result = $this->userModel->login($emailOrContact, $password);
                $_SESSION['message'] = $result === "success" ? "Login successful!" : $result;
            }
        }
    }

    // Register Function
    public function register() {
        if (isset($_POST['registerBtn'])) {
            $firstName      = trim($_POST['firstName']);
            $lastName       = trim($_POST['lastName']);
            $email          = trim($_POST['email']);
            $contactNumber  = trim($_POST['contactNumber']);
            $password       = trim($_POST['password']);
            $confirmPassword = trim($_POST['confirmPasword']);

            if ($password !== $confirmPassword) {
                $_SESSION['message'] = "Passwords do not match.";
            } else {
                $result = $this->userModel->register($firstName, $lastName, $email, $contactNumber, $password);
                $_SESSION['message'] = $result === "success" ? "Registration successful!" : $result;
            }
        }
    }

    // Update Profile Function
    public function updateProfile() {
        if (!isset($_SESSION['user_id'])) {
            $_SESSION['message'] = "You must be logged in to update your profile.";
            return;
        }

        $userId = $_SESSION['user_id'];
        $firstName = $this->sanitizeInput($_POST['firstname']);
        $lastName = $this->sanitizeInput($_POST['lastname']);
        $contact = $this->sanitizeInput($_POST['contact']);
        $email = filter_var($this->sanitizeInput($_POST['email']), FILTER_SANITIZE_EMAIL);
        $password = !empty($_POST['password']) ? password_hash($_POST['password'], PASSWORD_DEFAULT) : null;
        $status = $this->sanitizeInput($_POST['status']);
        $profilePicture = null;

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['message'] = "Invalid email format.";
            return;
        }

        if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
            $profilePicture = $this->handleFileUpload($_FILES['profile_picture']);
            if (!$profilePicture) {
                $_SESSION['message'] = "Error uploading profile picture.";
                return;
            }
        }

        $updateSuccess = $this->userModel->updateProfile($userId, $firstName, $lastName, $contact, $email, $password, $status, $profilePicture);

        if ($updateSuccess) {
            $_SESSION['message'] = "Profile updated successfully!";
        } else {
            $_SESSION['message'] = "There was an error updating your profile.";
        }
    }

    // Handle File Upload Function
    private function handleFileUpload($file) {
        // Ensure the directory exists
        $uploadDir = __DIR__ . '/../../assets/images/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);  // Create directory if it doesn't exist
        }

        $fileName = basename($file['name']);
        $targetFilePath = $uploadDir . $fileName;
        $fileType = mime_content_type($file['tmp_name']);
        
        // Allowed file types
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];  
        if (!in_array($fileType, $allowedTypes)) {
            $_SESSION['message'] = "Sorry, only JPG, JPEG, PNG & GIF files are allowed.";
            return false;
        }

        // Check if file already exists
        if (file_exists($targetFilePath)) {
            $_SESSION['message'] = "Sorry, file already exists.";
            return false;
        }

        // Check file size (5MB limit)
        if ($file['size'] > 5000000) {
            $_SESSION['message'] = "Sorry, your file is too large.";
            return false;
        }

        // Attempt to move the uploaded file
        if (move_uploaded_file($file['tmp_name'], $targetFilePath)) {
            // Return the relative path to the uploaded file
            return 'assets/images/' . $fileName;
        }

        $_SESSION['message'] = "Sorry, there was an error uploading your file.";
        return false;
    }

    // Sanitize input data to prevent XSS and other issues
    private function sanitizeInput($data) {
        return htmlspecialchars(trim($data));
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $authController = new AuthController();

    if (isset($_POST['loginBtn'])) {
        $authController->login();
    }

    if (isset($_POST['registerBtn'])) {
        $authController->register();
    }

    if (isset($_POST['updateProfileBtn'])) {
        $authController->updateProfile();
    }
}
?>
