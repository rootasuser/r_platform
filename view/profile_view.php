<?php
if (session_status() === PHP_SESSION_NONE) session_start();

require_once __DIR__ . '/../config/Database.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$db = new Database();
$conn = $db->getConnection();

// 获取请求的用户ID
$requestedUserId = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;

// 获取当前登录用户ID
$loggedInUserId = $_SESSION['user_id'];

// 查询用户信息
try {
    $query = "SELECT * FROM users WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $requestedUserId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result === false) {
        throw new Exception("Error executing query: " . $conn->error);
    }

    $user = $result->fetch_assoc();

    if (!$user) {
        throw new Exception("User not found.");
    }
} catch (Exception $e) {
    die("Error: " . $e->getMessage());
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>User Profile</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../assets/css/bootstrap.min.css">
    <style>
        .profile-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        .profile-header {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
        }
        .profile-avatar {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            object-fit: cover;
        }
        .profile-info {
            margin-left: 20px;
        }
        .profile-status {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
            margin-top: 10px;
        }
        .status-active {
            background-color: #28a745;
            color: white;
        }
        .status-offline {
            background-color: #6c757d;
            color: white;
        }
    </style>
</head>
<body>
    <div class="profile-container">
        <div class="profile-header">
            <img src="<?= !empty($user['profile_picture']) ? 'data:image/jpeg;base64,' . base64_encode($user['profile_picture']) : '../assets/default_img/def.png' ?>" alt="Profile" class="profile-avatar">
            <div class="profile-info">
                <h2><?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?></h2>
                <p>Email: <?= htmlspecialchars($user['email']) ?></p>
                <p>Status: <span class="profile-status <?= $user['status'] === 'Active' ? 'status-active' : 'status-offline' ?>"><?= $user['status'] ?></span></p>
            </div>
        </div>
        <div class="profile-content">
            <h3>About</h3>
            <p>Bio: <?= htmlspecialchars($user['bio'] ?? 'No bio available.') ?></p>
            <p>Joined: <?= htmlspecialchars($user['created_at']) ?></p>
        </div>
    </div>
</body>
</html>