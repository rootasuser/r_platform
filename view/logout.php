<?php
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../model/User.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (isset($_SESSION['user_id'])) {
    session_unset();
    session_destroy();

    header("Location: ../public/index.php");
    exit();
} else {
    header("Location: ../public/index.php");
    exit();
}
?>