<?php

ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);

require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../model/User.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header("Location: ../public/login.php");
    exit();
}

$userModel = new User();
$user = $userModel->getUserById($_SESSION['user_id']);

if (!$user) {
    $_SESSION['error'] = "User not found.";
    header("Location: ../public/login.php");
    exit();
}

function getPostActivities($conn, $userId) {
    try {
        $postQuery = "SELECT p.*, u.first_name, u.last_name, u.profile_picture 
                      FROM posts p
                      JOIN users u ON p.user_id = u.id
                      WHERE p.user_id = ?";
        $postStmt = $conn->prepare($postQuery);
        $postStmt->bind_param("i", $userId);
        $postStmt->execute();
        $postResult = $postStmt->get_result();
        $posts = $postResult->fetch_all(MYSQLI_ASSOC);

        $likeQuery = "SELECT l.*, p.text AS post_text, u.first_name, u.last_name, u.profile_picture 
                      FROM likes l
                      JOIN posts p ON l.post_id = p.id
                      JOIN users u ON l.user_id = u.id
                      WHERE p.user_id = ?";
        $likeStmt = $conn->prepare($likeQuery);
        $likeStmt->bind_param("i", $userId);
        $likeStmt->execute();
        $likeResult = $likeStmt->get_result();
        $likes = $likeResult->fetch_all(MYSQLI_ASSOC);

        $commentQuery = "SELECT c.*, p.text AS post_text, u.first_name, u.last_name, u.profile_picture 
                         FROM comments c
                         JOIN posts p ON c.post_id = p.id
                         JOIN users u ON c.user_id = u.id
                         WHERE p.user_id = ?";
        $commentStmt = $conn->prepare($commentQuery);
        $commentStmt->bind_param("i", $userId);
        $commentStmt->execute();
        $commentResult = $commentStmt->get_result();
        $comments = $commentResult->fetch_all(MYSQLI_ASSOC);

        $shareQuery = "SELECT s.*, p.text AS post_text, u.first_name, u.last_name, u.profile_picture 
                       FROM shares s
                       JOIN posts p ON s.post_id = p.id
                       JOIN users u ON s.user_id = u.id
                       WHERE p.user_id = ?";
        $shareStmt = $conn->prepare($shareQuery);
        $shareStmt->bind_param("i", $userId);
        $shareStmt->execute();
        $shareResult = $shareStmt->get_result();
        $shares = $shareResult->fetch_all(MYSQLI_ASSOC);

        $activities = array_merge(
            array_map(function($post) { return ['type' => 'post', 'data' => $post]; }, $posts),
            array_map(function($like) { return ['type' => 'like', 'data' => $like]; }, $likes),
            array_map(function($comment) { return ['type' => 'comment', 'data' => $comment]; }, $comments),
            array_map(function($share) { return ['type' => 'share', 'data' => $share]; }, $shares)
        );

        return $activities;
    } catch (Exception $e) {
        return ['error' => 'Database error: ' . $e->getMessage()];
    }
}

function blobToBase64($blob) {
    return base64_encode($blob);
}

function timeAgo($datetime) {
    if (is_numeric($datetime) && strlen($datetime) > 10) {
        $datetime = $datetime / 1000;
    } else {
        $datetime = strtotime($datetime);
    }
    
    $diff = time() - $datetime;
    
    if ($diff < 0) {  // Future date
        $absDiff = abs($diff);
        if ($absDiff < 60) {
            return '1 minute from now';
        } else {
            $minutes = round($absDiff / 60);
            return $minutes . ' minutes from now';
        }
    }
    
    if ($diff < 60) {
        return '1 minute ago';
    } elseif ($diff < 3600) {
        return round($diff / 60) . ' minutes ago';
    } elseif ($diff < 86400) {
        return round($diff / 3600) . ' hours ago';
    } else {
        return round($diff / 86400) . ' days ago';
    }
}

$db = new Database();
$conn = $db->getConnection();
$userId = $_SESSION['user_id'];
$activities = getPostActivities($conn, $userId);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Post Activities</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
    <style>
        .activity-item {
            border-bottom: 1px solid #dee2e6;
            padding: 15px 0;
        }

        .activity-item:last-child {
            border-bottom: none;
        }

        .activity-header {
            display: flex;
            align-items: center;
            margin-bottom: 8px;
        }

        .activity-header img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            margin-right: 10px;
        }

        .activity-content {
            margin-left: 50px;
        }

        .activity-time {
            font-size: 0.8rem;
            color: #6c757d;
        }
    </style>
</head>
<body>

    <?php include('header.php'); ?>
    
    <div class="container mt-4">
        <div class="row">
            <div class="col-md-12">
                <h2 class="mb-4">Post Activities</h2>
                <?php if (isset($activities['error'])): ?>
                    <p class="text-danger"><?= $activities['error'] ?></p>
                <?php else: ?>
                    <?php if (empty($activities)): ?>
                        <p>No activities yet.</p>
                    <?php else: ?>
                        <div class="activity-list">
                            <?php foreach ($activities as $activity): ?>
                                <div class="activity-item">
                                    <div class="activity-header">
                                        <img src="data:image/jpeg;base64,<?= blobToBase64($activity['data']['profile_picture']) ?>" alt="User Picture">
                                        <div class="activity-content">
                                            <strong><?= htmlspecialchars($activity['data']['first_name'] . ' ' . $activity['data']['last_name']) ?></strong>
                                            <?php
                                            switch ($activity['type']) {
                                                case 'post':
                                                    echo ' posted: ' . htmlspecialchars($activity['data']['text']);
                                                    break;
                                                case 'like':
                                                    echo ' liked your post: ' . htmlspecialchars($activity['data']['post_text']);
                                                    break;
                                                case 'comment':
                                                    echo ' commented on your post: ' . htmlspecialchars($activity['data']['comment_text']);
                                                    break;
                                                case 'share':
                                                    echo ' shared your post: ' . htmlspecialchars($activity['data']['post_text']);
                                                    break;
                                            }
                                            ?>
                                            <div class="activity-time"><?= timeAgo($activity['data']['created_at']) ?></div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>