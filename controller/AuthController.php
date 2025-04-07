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
            $firstName       = trim($_POST['firstName']);
            $lastName        = trim($_POST['lastName']);
            $email           = trim($_POST['email']);
            $contactNumber   = trim($_POST['contactNumber']);
            $password        = trim($_POST['password']);
            $confirmPassword = trim($_POST['confirmPasword']); 

            if ($password !== $confirmPassword) {
                $_SESSION['message'] = "Passwords do not match.";
            } else {
                $result = $this->userModel->register($firstName, $lastName, $email, $contactNumber, $password);
                $_SESSION['message'] = $result === "success" ? "Registration successful!" : $result;
            }
        }
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

}