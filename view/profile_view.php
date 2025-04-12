<?php
if (session_status() === PHP_SESSION_NONE) session_start();

require_once __DIR__ . '/../config/Database.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../public/login.php");
    exit();
}

$db = new Database();
$conn = $db->getConnection();

$requestedUserId = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;
$loggedInUserId = $_SESSION['user_id'];
$action = isset($_POST['action']) ? $_POST['action'] : null;
$postId = isset($_POST['post_id']) ? $_POST['post_id'] : null;

if ($action === 'like' && $postId) {
    $response = likePost($postId, $loggedInUserId, $conn);
    $_SESSION['like_response'] = $response;
    header("Location: {$_SERVER['PHP_SELF']}?user_id=$requestedUserId");
    exit();
}

elseif ($action === 'share' && $postId) {
    $response = sharePost($postId, $loggedInUserId, $conn);
    $_SESSION['share_response'] = $response;
    header("Location: {$_SERVER['PHP_SELF']}?user_id=$requestedUserId");
    exit();
}

try {
    $query = "SELECT * FROM users WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $requestedUserId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result === false) {
        throw new Exception("Err Exec query: " . $conn->error);
    }

    $user = $result->fetch_assoc();

    if (!$user) {
        throw new Exception("User not found.");
    }
} catch (Exception $e) {
    die("Error: " . $e->getMessage());
}

try {
    $query = "SELECT 
                p.*, 
                u.first_name, 
                u.last_name,
                (SELECT COUNT(*) FROM likes WHERE post_id = p.id) AS likes_count,
                (SELECT COUNT(*) FROM comments WHERE post_id = p.id) AS comments_count,
                (SELECT COUNT(*) FROM shares WHERE post_id = p.id) AS shares_count
              FROM posts p
              JOIN users u ON p.user_id = u.id
              WHERE p.user_id = ?
              ORDER BY p.created_at DESC";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $requestedUserId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result === false) {
        throw new Exception("Err exec query: " . $conn->error);
    }

    $posts = $result->fetch_all(MYSQLI_ASSOC);
} catch (Exception $e) {
    die("Err: " . $e->getMessage());
}

$conn->close();

function likePost($postId, $userId, $conn) {
    try {
        $query = "SELECT * FROM likes WHERE post_id = ? AND user_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ii", $postId, $userId);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $query = "DELETE FROM likes WHERE post_id = ? AND user_id = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("ii", $postId, $userId);
            $stmt->execute();
            $liked = false;
        } else {
            $query = "INSERT INTO likes (post_id, user_id, created_at) VALUES (?, ?, NOW())";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("ii", $postId, $userId);
            $stmt->execute();
            $liked = true;
        }

        return [
            'success' => true,
            'new_likes_count' => getLikesCount($postId, $conn),
            'liked' => $liked
        ];
    } catch (Exception $e) {
        return ['success' => false, 'message' => 'Err: ' . $e->getMessage()];
    }
}

function getLikesCount($postId, $conn) {
    try {
        $query = "SELECT COUNT(*) AS count FROM likes WHERE post_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $postId);
        $stmt->execute();
        $result = $stmt->get_result();
        $count = $result->fetch_assoc();
        return $count['count'];
    } catch (Exception $e) {
        return 0;
    }
}

function sharePost($postId, $userId, $conn) {
    try {
        $query = "INSERT INTO shares (post_id, user_id, created_at) VALUES (?, ?, NOW())";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ii", $postId, $userId);
        $stmt->execute();

        if ($stmt->affected_rows > 0) {
            return [
                'success' => true,
                'new_shares_count' => getSharesCount($postId, $conn)
            ];
        } else {
            return ['success' => false, 'message' => 'Failed to share post.'];
        }
    } catch (Exception $e) {
        return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
    }
}

function getSharesCount($postId, $conn) {
    try {
        $query = "SELECT COUNT(*) AS count FROM shares WHERE post_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $postId);
        $stmt->execute();
        $result = $stmt->get_result();
        $count = $result->fetch_assoc();
        return $count['count'];
    } catch (Exception $e) {
        return 0;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>User Profile</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="../assets/js/profile.view.style.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css">
</head>
<body>
<nav class="navbar navbar-custom">
    <span class="navbar-brand mb-0 h1">R</span>
    <button class="btn btn-outline-secondary ml-auto" type="button" onclick="window.history.back();">
      <i class="fa fa-arrow-left"></i> Back
    </button>
  </nav>


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
        
        <div class="profile-posts mt-4">
            <h3>Posts</h3>
            <?php if (empty($posts)): ?>
                <p>No posts yet.</p>
            <?php else: ?>
                <?php foreach ($posts as $post): ?>
                    <div class="post-card">
                        <div class="post-header">
                            <img src="<?= !empty($user['profile_picture']) ? 'data:image/jpeg;base64,' . base64_encode($user['profile_picture']) : '../assets/default_img/def.png' ?>" alt="Profile" class="post-avatar">
                            <div>
                                <strong><?= htmlspecialchars($post['first_name'] . ' ' . $post['last_name']) ?></strong>
                                <div class="post-time"><?= htmlspecialchars($post['created_at']) ?></div>
                            </div>
                        </div>
                        <div class="post-content">
                            <p><?= htmlspecialchars($post['text']) ?></p>
                            <?php if (!empty($post['image'])): ?>
                                <img src="<?= $post['image'] ?>" alt="Post Image" class="post-image">
                            <?php endif; ?>
                            <?php if (!empty($post['video'])): ?>
                                <video controls class="post-video">
                                    <source src="<?= $post['video'] ?>" type="video/mp4">
                                    Your browser does not support the video tag.
                                </video>
                            <?php endif; ?>
                        </div>
                        <div class="post-footer">
                            <span>Likes: <?= htmlspecialchars($post['likes_count'] ?? '0') ?></span>
                            <span>Comments: <?= htmlspecialchars($post['comments_count'] ?? '0') ?></span>
                            <span>Shares: <?= htmlspecialchars($post['shares_count'] ?? '0') ?></span>
                        </div>
                        <div class="post-actions">
                            <form method="POST" action="">
                                <input type="hidden" name="action" value="like">
                                <input type="hidden" name="post_id" value="<?= $post['id'] ?>">
                                <button type="submit" class="post-action-btn like-btn">
                                    <i class="fa fa-thumbs-up"></i> Like
                                </button>
                            </form>
                            <form method="POST" action="">
                                <input type="hidden" name="action" value="share">
                                <input type="hidden" name="post_id" value="<?= $post['id'] ?>">
                                <button type="submit" class="post-action-btn share-btn">
                                    <i class="fa fa-share"></i> Share
                                </button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.10.2/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.min.js"></script>
</body>
</html>