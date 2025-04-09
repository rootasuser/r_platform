<?php
require_once __DIR__ . '/../../config/Database.php';
require_once __DIR__ . '/../../model/User.php';

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

function getAllUserPosts($conn, $userId) {
    try {
        $query = "SELECT p.*, u.first_name, u.last_name, u.profile_picture 
                  FROM posts p
                  JOIN users u ON p.user_id = u.id
                  WHERE p.user_id = ?
                  ORDER BY p.created_at DESC";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();

        $posts = [];
        while ($row = $result->fetch_assoc()) {
            $posts[] = $row;
        }

        return $posts;
    } catch (Exception $e) {
        return ['error' => 'Database error: ' . $e->getMessage()];
    }
}

function deletePost($conn, $postId, $userId) {
    try {

        $conn->begin_transaction();
        
        $commentQuery = "DELETE FROM comments WHERE post_id = ?";
        $commentStmt = $conn->prepare($commentQuery);
        $commentStmt->bind_param("i", $postId);
        $commentStmt->execute();
        
        $likeQuery = "DELETE FROM likes WHERE post_id = ?";
        $likeStmt = $conn->prepare($likeQuery);
        $likeStmt->bind_param("i", $postId);
        $likeStmt->execute();
        
        $shareQuery = "DELETE FROM shares WHERE post_id = ?";
        $shareStmt = $conn->prepare($shareQuery);
        $shareStmt->bind_param("i", $postId);
        $shareStmt->execute();
        
        $postQuery = "DELETE FROM posts WHERE id = ? AND user_id = ?";
        $postStmt = $conn->prepare($postQuery);
        $postStmt->bind_param("ii", $postId, $userId);
        $postStmt->execute();
        
        if ($postStmt->affected_rows > 0) {
            $conn->commit();
            return ['success' => true, 'message' => 'Post deleted successfully.'];
        } else {
            $conn->rollback();
            return ['success' => false, 'error' => 'Failed to delete post.'];
        }
    } catch (Exception $e) {
        $conn->rollback();
        return ['success' => false, 'error' => 'Error deleting post: ' . $e->getMessage()];
    }
}

$db = new Database();
$conn = $db->getConnection();
$userId = $_SESSION['user_id'];
$posts = getAllUserPosts($conn, $userId);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_post'])) {
    $postId = $_POST['post_id'];
    $result = deletePost($conn, $postId, $userId);
    
    if ($result['success']) {
        $_SESSION['success'] = $result['message'];
    } else {
        $_SESSION['error'] = $result['error'];
    }
    
 
}

function timeAgo($timestamp) {
    $time = strtotime($timestamp);
    $now = time();
    $diff = $now - $time;

    $seconds = $diff;
    $minutes = round($diff / 60);
    $hours = round($diff / 3600);
    $days = round($diff / 86400);
    $weeks = round($diff / 604800);
    $months = round($diff / 2600640);
    $years = round($diff / 31207680);

    if ($seconds < 60) {
        return "<i class='fas fa-globe text-primary text-sm'></i> just now";
    } elseif ($minutes < 60) {
        return "{$minutes} <i class='fas fa-globe text-primary text-sm'></i> minutes ago";
    } elseif ($hours < 24) {
        return "{$hours} <i class='fas fa-globe text-primary text-sm'></i> hours ago";
    } elseif ($days < 7) {
        return "{$days} <i class='fas fa-globe text-primary text-sm'></i> days ago";
    } elseif ($weeks < 4) {
        return "{$weeks} <i class='fas fa-globe text-primary text-sm'></i> weeks ago";
    } elseif ($months < 12) {
        return "{$months} <i class='fas fa-globe text-primary text-sm'></i> months ago";
    } else {
        return "{$years} <i class='fas fa-globe text-primary text-sm'></i> years ago";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Posts</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
    <link rel="stylesheet" href="../../assets/css/style.posts.css">
</head>
<body>
    <div class="container mt-4">
        <div class="row">
            <div class="col-md-12">
                <h2 class="mb-4">My Posts</h2>
                
                <?php if (isset($_SESSION['success'])): ?>
                    <div class="alert alert-success"><?= $_SESSION['success'] ?></div>
                    <?php unset($_SESSION['success']); ?>
                <?php endif; ?>
                
                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-danger"><?= $_SESSION['error'] ?></div>
                    <?php unset($_SESSION['error']); ?>
                <?php endif; ?>
                
                <?php if (isset($posts['error'])): ?>
                    <p class="text-danger"><?= $posts['error'] ?></p>
                <?php else: ?>
                    <?php foreach ($posts as $post): ?>
                        <div class="card mb-4">
                            <div class="card-body">
                                <div class="post-header d-flex align-items-center mb-3">
                                    <img src="data:image/jpeg;base64,<?= blobToBase64($post['profile_picture']) ?>" alt="Profile Picture" class="rounded-circle me-3" width="40" height="40">
                                    <div>
                                        <h5 class="mb-0"><?= htmlspecialchars($post['first_name'] . ' ' . $post['last_name']) ?></h5>
                                        <small class="text-muted"><?= timeAgo($post['created_at']) ?></small>
                                    </div>
                                </div>
                                <p class="mb-3"><?= htmlspecialchars($post['text']) ?></p>
                                
                                <?php if (!empty($post['image'])): ?>
                                    <div class="post-images mb-3">
                                        <img src="<?= $post['image'] ?>" alt="Post Image" class="img-fluid rounded">
                                    </div>
                                <?php endif; ?>
                                
                                <?php if (!empty($post['video'])): ?>
                                    <div class="post-video mb-3">
                                        <video controls class="w-100">
                                            <source src="<?= $post['video'] ?>" type="video/mp4">
                                            Your browser does not support the video tag.
                                        </video>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="post-actions d-flex justify-content-between">
                                    <form method="POST" action="">
                                        <input type="hidden" name="post_id" value="<?= $post['id'] ?>">
                                        <input type="hidden" name="delete_post" value="1">
                                        <button type="submit" class="btn btn-outline-danger btn-sm">Delete</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>