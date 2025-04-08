<?php
if (session_status() === PHP_SESSION_NONE) session_start();

require_once __DIR__ . '/../config/Database.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../public/login.php");
    exit();
}


$db = new Database();
$conn = $db->getConnection();
$loggedInUserId = $_SESSION['user_id'];

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["friend_action"]) && isset($_POST["friend_id"])) {
    $friendId = $_POST["friend_id"];
    $action = $_POST["friend_action"];

    try {
        $checkQuery = "SELECT id, status, sender_id FROM friend_requests 
                       WHERE (sender_id = ? AND receiver_id = ?) 
                          OR (sender_id = ? AND receiver_id = ?)
                       ORDER BY created_at DESC 
                       LIMIT 1";
        $checkStmt = $conn->prepare($checkQuery);
        $checkStmt->bind_param("iiii", $loggedInUserId, $friendId, $friendId, $loggedInUserId);
        $checkStmt->execute();
        $checkResult = $checkStmt->get_result();

        if ($checkResult->num_rows > 0) {
            $existingRequest = $checkResult->fetch_assoc();

            if ($action === 'add') {
                if ($existingRequest['status'] === 'pending') {
                    $_SESSION['error'] = "You already have a pending friend request with this user.";
                } elseif (in_array($existingRequest['status'], ['cancelled', 'cancelled'])) {
                    $query = "INSERT INTO friend_requests (sender_id, receiver_id, status) VALUES (?, ?, 'pending')";
                    $stmt = $conn->prepare($query);
                    $stmt->bind_param("ii", $loggedInUserId, $friendId);
                    $stmt->execute();
                    $_SESSION['success'] = "Friend request sent successfully.";
                } elseif ($existingRequest['status'] === 'friends') {
                    $_SESSION['error'] = "You are already friends with this user.";
                }
            } elseif ($action === 'cancel') {
                if (!isset($existingRequest['sender_id']) || $existingRequest['sender_id'] != $loggedInUserId) {
                    $_SESSION['error'] = "You can only cancel your own friend requests.";
                } 

                elseif ($existingRequest['status'] !== 'pending') {
                    $_SESSION['error'] = "This friend request cannot be cancelled as it's no longer pending.";
                } 
                else {
                    $query = "UPDATE friend_requests SET status = 'cancelled' WHERE id = ? AND sender_id = ?";
                    $stmt = $conn->prepare($query);
                    $stmt->bind_param("ii", $existingRequest['id'], $loggedInUserId);
                    $stmt->execute();

                    if ($stmt->affected_rows > 0) {
                        $_SESSION['success'] = "Friend request cancelled.";
                    } else {
                        $_SESSION['error'] = "Failed to cancel the friend request. It may have already been processed.";
                    }
                }
            }
        } else {
            if ($action === 'add') {
                $query = "INSERT INTO friend_requests (sender_id, receiver_id, status) VALUES (?, ?, 'pending')";
                $stmt = $conn->prepare($query);
                $stmt->bind_param("ii", $loggedInUserId, $friendId);
                $stmt->execute();
                $_SESSION['success'] = "Friend request sent successfully.";
            } else {
                $_SESSION['error'] = "Invalid action.";
            }
        }
    } catch (Exception $e) {
        $_SESSION['error'] = "Failed to process friend request: " . $e->getMessage();
    }
}

$page = $_GET['page'] ?? 'suggested_friends';

function getAllUsers($conn, $loggedInUserId) {
    try {
        $query = "SELECT * FROM users WHERE id != ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $loggedInUserId);
        $stmt->execute();
        $result = $stmt->get_result();

        $users = [];
        while ($row = $result->fetch_assoc()) {
            $users[] = $row;
        }
        return $users;
    } catch (Exception $e) {
        return ['error' => 'DB Err: ' . $e->getMessage()];
    }
}

$allUsers = [];
if (in_array($page, ['suggested_friends', 'home', 'suggestions'])) {
    $allUsers = getAllUsers($conn, $loggedInUserId);
}

function getActiveUsers($conn) {
    try {
        $query = "SELECT id, first_name, last_name, profile_picture, status FROM users WHERE status IN ('Active', 'Offline')";
        $stmt = $conn->prepare($query);

        if ($stmt === false) {
            throw new Exception("Err P.Q: " . $conn->error);
        }

        $stmt->execute();
        $result = $stmt->get_result();

        if ($result === false) {
            throw new Exception("E.C Q: " . $conn->error);
        }

        $users = [];
        while ($row = $result->fetch_assoc()) {
            $users[] = $row;
        }

        return $users;
    } catch (Exception $e) {
        return ['error' => 'DB Err: ' . $e->getMessage()];
    }
}

$activeUsers = getActiveUsers($conn);

function getFriendRequests($conn, $userId) {
    try {
        $query = "SELECT * FROM friend_requests WHERE receiver_id = ? AND status = 'pending'";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();

        $requests = [];
        while ($row = $result->fetch_assoc()) {
            $senderQuery = "SELECT id, first_name, last_name, profile_picture FROM users WHERE id = ?";
            $senderStmt = $conn->prepare($senderQuery);
            $senderStmt->bind_param("i", $row['sender_id']);
            $senderStmt->execute();
            $senderResult = $senderStmt->get_result();
            $sender = $senderResult->fetch_assoc();

            if ($sender) {
                $row['sender'] = $sender;
                $requests[] = $row;
            }
        }

        return $requests;
    } catch (Exception $e) {
        $_SESSION['error'] = "Failed to process friend request: " . $e->getMessage();
    }
}
    ?>
 

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>R Connect</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="../assets/css/style.people.css" />
    <style>
        .friend-card {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
            padding: 10px;
            border: 1px solid #e0e0e0;
            border-radius: 5px;
        }
        .friend-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            margin-right: 15px;
        }
        .friend-name {
            font-weight: bold;
        }
        .friend-mutual {
            font-size: 12px;
            color: #666;
        }
        .confirm-btn {
            margin-left: auto;
            padding: 5px 15px;
        }
        .online-dot {
            display: inline-block;
            width: 10px;
            height: 10px;
            border-radius: 50%;
            margin-right: 5px;
        }
        .online-list li {
            display: flex;
            align-items: center;
            margin-bottom: 10px;
        }
        .online-list a {
            text-decoration: none;
            color: #333;
        }
        .message-box {
            padding: 10px;
            margin-bottom: 15px;
            border-radius: 5px;
        }
        .success-message {
            background-color: #d4edda;
            color: #155724;
        }
        .error-message {
            background-color: #f8d7da;
            color: #721c24;
        }
    </style>
</head>
<body>
<div class="wrapper">
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-logo">R</div>
        <ul class="sidebar-nav">
            <li><a href="dashboard.php" class="<?= $page === 'home' ? 'active' : '' ?>"><span class="icon">🏠</span>Home</a></li>
            <li><a href="?page=suggested_friends" class="<?= $page === 'suggested_friends' ? 'active' : '' ?>"><span class="icon">🤝</span>Friend Requests</a></li>
            <li><a href="?page=suggestions" class="<?= $page === 'suggestions' ? 'active' : '' ?>"><span class="icon">📢</span>Suggestions</a></li>
            <li><a href="?page=friends" class="<?= $page === 'friends' ? 'active' : '' ?>"><span class="icon">👥</span>Friends</a></li>
        </ul>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <!-- 显示消息 -->
        <?php if (isset($_SESSION['success'])): ?>
            <div class="message-box success-message">
                <?= htmlspecialchars($_SESSION['success']) ?>
            </div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="message-box error-message">
                <?= htmlspecialchars($_SESSION['error']) ?>
            </div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>

        <?php if ($page === 'suggested_friends') : ?>
            <h2 class="page-title">Friend Requests</h2>
            <div class="friend-requests-container">
                <?php if (isset($friendRequests['error'])): ?>
                    <div class="alert"><?= $friendRequests['error'] ?></div>
                <?php elseif (empty($friendRequests)): ?>
                    <div class="alert">No friend requests found.</div>
                <?php else: ?>
                    <?php foreach ($friendRequests as $request): ?>
                        <div class="friend-card">
                            <img src="<?= !empty($request['sender']['profile_picture']) ? 'data:image/jpeg;base64,' . base64_encode($request['sender']['profile_picture']) : '../assets/default_img/def.png' ?>" alt="Profile" class="friend-avatar">
                            <div>
                                <div class="friend-name"><?= htmlspecialchars($request['sender']['first_name'] . ' ' . $request['sender']['last_name']) ?></div>
                            </div>
                            <form method="post" style="display: flex; gap: 10px;">
                                <input type="hidden" name="request_id" value="<?= $request['id'] ?>">
                                <button type="submit" name="action" value="accepted" class="btn btn-success">Accept</button>
                                <button type="submit" name="action" value="rejected" class="btn btn-danger">Reject</button>
                            </form>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

        <?php elseif ($page === 'suggestions') : ?>
            <h2 class="page-title">Suggestions</h2>
            <div class="suggestions-box">
                <div class="friend-requests-container">
                    <?php if (isset($allUsers['error'])): ?>
                        <div class="alert"><?= $allUsers['error'] ?></div>
                    <?php elseif (empty($allUsers)): ?>
                        <div class="alert">No suggestions available.</div>
                    <?php else: ?>
                        <?php foreach ($allUsers as $user): ?>
                            <!-- 检查是否已经发送了请求 -->
                            <?php
                            $existingRequest = null;
                            if ($conn) {
                                $checkQuery = "SELECT id, status, sender_id FROM friend_requests 
                                             WHERE (sender_id = ? AND receiver_id = ?) 
                                             OR (sender_id = ? AND receiver_id = ?)
                                             ORDER BY created_at DESC 
                                             LIMIT 1";
                                $checkStmt = $conn->prepare($checkQuery);
                                $checkStmt->bind_param("iiii", $loggedInUserId, $user['id'], $user['id'], $loggedInUserId);
                                $checkStmt->execute();
                                $checkResult = $checkStmt->get_result();
                                
                                if ($checkResult->num_rows > 0) {
                                    $existingRequest = $checkResult->fetch_assoc();
                                }
                            }
                            ?>
                            
                            <div class="friend-card">
                                <img src="<?= !empty($user['profile_picture']) ? 'data:image/jpeg;base64,' . base64_encode($user['profile_picture']) : '../assets/default_img/def.png' ?>" alt="Profile" class="friend-avatar">
                                <div>
                                    <div class="friend-name"><?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?></div>
                                </div>
                                <form method="post">
                                    <input type="hidden" name="friend_id" value="<?= $user['id'] ?>">
                                    
                                    <?php if ($existingRequest): ?>
                                        <?php if (isset($existingRequest['status']) && isset($existingRequest['sender_id']) && $existingRequest['status'] === 'pending' && $existingRequest['sender_id'] === $loggedInUserId): ?>
                                            <button type="submit" name="friend_action" value="cancel" class="btn btn-warning">Cancel Request</button>
                                        <?php else: ?>
                                            <button type="submit" name="friend_action" value="add" class="btn btn-primary">Add as Friend</button>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <button type="submit" name="friend_action" value="add" class="btn btn-primary">Add as Friend</button>
                                    <?php endif; ?>
                                </form>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

        <?php elseif ($page === 'friends') : ?>
            <h2 class="page-title">Friends</h2>
            <p>Your friends list will appear here.</p>

        <?php else : ?>
            <div class="alert">Page not found.</div>
        <?php endif; ?>
    </div>

    <!-- Right Sidebar -->
    <div class="right-sidebar">
        <h3 class="online-title">Online Friends</h3>
        <ul class="online-list">
            <?php if (isset($activeUsers['error'])): ?>
                <li class="alert"><?= $activeUsers['error'] ?></li>
            <?php elseif (empty($activeUsers)): ?>
                <li class="alert">No active users.</li>
            <?php else: ?>
                <?php foreach ($activeUsers as $user): ?>
                    <li>
                        <span class="online-dot" style="background-color: <?= $user['status'] === 'Active' ? 'yellowgreen' : 'gray' ?>"></span>
                        <a href="profile_view.php?user_id=<?= $user['id'] ?>">
                            <span data-user-id="<?= $user['id'] ?>" data-user-name="<?= $user['first_name'] . ' ' . $user['last_name'] ?>" data-user-status="<?= $user['status'] ?>">
                                <?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?>
                            </span>
                        </a>
                    </li>
                <?php endforeach; ?>
            <?php endif; ?>
        </ul>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // 搜索功能
        document.getElementById('friendSearch')?.addEventListener('input', function () {
            const query = this.value.toLowerCase();
            document.querySelectorAll('.friend-card').forEach(card => {
                const name = card.querySelector('.friend-name').textContent.toLowerCase();
                card.style.display = name.includes(query) ? 'block' : 'none';
            });
        });
    });
</script>
</body>
</html>