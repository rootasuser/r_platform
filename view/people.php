<?php

require_once __DIR__ . '/../config/Database.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header("Location: ../public/index.php");
    exit();
}

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Function to get all users
function getAllUsers($conn) {
    $stmt = $conn->prepare("SELECT * FROM users");
    if (!$stmt) {
        die("Query preparation failed: " . $conn->error);
    }
    $stmt->execute();
    $result = $stmt->get_result();

    $users = [];
    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
    }
    return $users;
}

// Create a database connection
$database = new Database();  
$conn = $database->getConnection();

if (!$conn) {
    die("Connection failed: " . $conn->connect_error);
}


$allUsers = getAllUsers($conn);


?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All Users - R Connect</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
    <style>
        .profile-img {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            object-fit: cover;
        }
    </style>
</head>
<body>
    <?php include('header.php'); ?>

    <div class="container mt-4">
        <div class="row">
            <div class="col-md-12">
                <h2>All Users</h2>
                <div class="row">
                    <?php if (empty($allUsers)): ?>
                        <p>No users found.</p>
                    <?php else: ?>
                        <?php foreach ($allUsers as $user): ?>
                            <div class="col-md-4 mb-4">
                                <div class="card">
                                    <div class="card-body text-center">
                                        <!-- Profile picture -->
                                        <img src="data:image/jpeg;base64,<?= !empty($user['profile_picture']) ? base64_encode($user['profile_picture']) : 'path/to/default/image.jpg' ?>" alt="Profile Picture" class="profile-img mb-3">

                                        
                                        <h5 class="card-title"><?= htmlspecialchars($user['first_name']) ?> <?= htmlspecialchars($user['last_name']) ?></h5>
                                        <p class="card-text"><?= htmlspecialchars($user['email']) ?></p>
                                        <p class="card-text"><?= htmlspecialchars($user['contact_number']) ?></p>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/jquery@3.6.0/dist/jquery.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
