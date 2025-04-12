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

/**
 * Handle friend request actions (add, cancel)
 */
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

/**
 * * Handle friend request acceptance or rejection
 */
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["action"]) && isset($_POST["request_id"])) {
    $requestId = $_POST["request_id"];
    $action = $_POST["action"];

    try {
        $conn->begin_transaction();

        $newStatus = $action === 'accepted' ? 'friends' : 'cancelled';
        $query = "UPDATE friend_requests SET status = ? WHERE id = ? AND receiver_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("sii", $newStatus, $requestId, $loggedInUserId);
        $stmt->execute();

        if ($stmt->affected_rows > 0) {
            if ($action === 'accepted') {
                $requestQuery = "SELECT sender_id, receiver_id FROM friend_requests WHERE id = ?";
                $requestStmt = $conn->prepare($requestQuery);
                $requestStmt->bind_param("i", $requestId);
                $requestStmt->execute();
                $requestResult = $requestStmt->get_result();
                $friendRequest = $requestResult->fetch_assoc();

                if ($friendRequest) {
                    $friendsQuery = "INSERT INTO friends (user_id, friend_id) VALUES (?, ?), (?, ?)";
                    $friendsStmt = $conn->prepare($friendsQuery);
                    $friendsStmt->bind_param("iiii", 
                        $friendRequest['sender_id'], $friendRequest['receiver_id'],
                        $friendRequest['receiver_id'], $friendRequest['sender_id']
                    );
                    $friendsStmt->execute();

                    if ($friendsStmt->affected_rows > 0) {
                        $_SESSION['success'] = "Friend request accepted. You are now friends.";
                    } else {
                        throw new Exception("Failed to add friends.");
                    }
                } else {
                    throw new Exception("Friend request not found.");
                }
            } else {
                $_SESSION['success'] = "Friend request rejected.";
            }

            $conn->commit();
        } else {
            $_SESSION['error'] = "Friend request not found or you are not authorized to perform this action.";
        }
    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['error'] = "Failed to update friend request: " . $e->getMessage();
    }
}



/* * Check if the user is logged in 
 */
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

/** Get all users except the logged-in user
 */
$allUsers = [];
if (in_array($page, ['suggested_friends', 'home', 'suggestions'])) {
    $allUsers = getAllUsers($conn, $loggedInUserId);
}

/**
 * Get active users (status: Active or Offline)
 */
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

/** Get friend requests for the logged-in user
*/
function getFriendRequests($conn, $userId) {
    try {
        $query = "
            SELECT 
                fr.id AS request_id,
                fr.status,
                u.id AS sender_id,
                u.first_name,
                u.last_name,
                u.profile_picture
            FROM friend_requests fr
            JOIN users u ON fr.sender_id = u.id
            WHERE fr.receiver_id = ? AND fr.status = 'pending'
        ";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $requests = [];
        while ($row = $result->fetch_assoc()) {
            $requests[] = [
                'request_id' => $row['request_id'],
                'status' => $row['status'],
                'sender' => [
                    'id' => $row['sender_id'],
                    'first_name' => $row['first_name'],
                    'last_name' => $row['last_name'],
                    'profile_picture' => $row['profile_picture']
                ]
            ];
        }
        
        return $requests;
    } catch (Exception $e) {
        $_SESSION['error'] = "Failed to process friend request: " . $e->getMessage();
        return [];
    }
}

/** Get friend requests for the logged-in user
 */
$friendRequests = getFriendRequests($conn, $loggedInUserId);

/** Get id of logged in uer
 */
$friends = getFriends($conn, $loggedInUserId);

/** Get friends of the logged-in user
 */
function getFriends($conn, $userId) {
    try {
        /**
         * Fetch friends from the friends table
         * This is to get the friends who are already in the friends table
         */
        $friendsQuery = "
            SELECT 
                u.id AS friend_id,
                u.first_name,
                u.last_name,
                u.profile_picture
            FROM friends f
            JOIN users u ON (f.friend_id = u.id AND f.user_id = ?)
                OR (f.user_id = u.id AND f.friend_id = ?)
        ";
        $friendsStmt = $conn->prepare($friendsQuery);
        $friendsStmt->bind_param("ii", $userId, $userId);
        $friendsStmt->execute();
        $friendsResult = $friendsStmt->get_result();
        /**
         *  * Fetch friends from the friend_requests table
         *  * This is to get the friends who are not in the friends table but have sent a friend request
         */
        $requestsQuery = "
            SELECT 
                u.id AS friend_id,
                u.first_name,
                u.last_name,
                u.profile_picture
            FROM friend_requests fr
            JOIN users u ON (fr.sender_id = u.id AND fr.receiver_id = ? AND fr.status = 'friends')
                OR (fr.receiver_id = u.id AND fr.sender_id = ? AND fr.status = 'friends')
        ";
        $requestsStmt = $conn->prepare($requestsQuery);
        $requestsStmt->bind_param("ii", $userId, $userId);
        $requestsStmt->execute();
        $requestsResult = $requestsStmt->get_result();
        
        $friends = [];
        while ($row = $friendsResult->fetch_assoc()) {
            $friends[] = [
                'id' => $row['friend_id'],
                'first_name' => $row['first_name'],
                'last_name' => $row['last_name'],
                'profile_picture' => $row['profile_picture']
            ];
        }
        
        while ($row = $requestsResult->fetch_assoc()) {
            $friends[] = [
                'id' => $row['friend_id'],
                'first_name' => $row['first_name'],
                'last_name' => $row['last_name'],
                'profile_picture' => $row['profile_picture']
            ];
        }
        
        $friends = array_unique($friends, SORT_REGULAR);
        
        return $friends;
    } catch (Exception $e) {
        $_SESSION['error'] = "Failed to fetch friends: " . $e->getMessage();
        return [];
    }
}

/**
 * Get the count of friends for a user in the friends card
 */
function getFriendCount($conn, $userId) {
    try {
      
        $friendsQuery = "
            SELECT COUNT(DISTINCT friend_id) AS friend_count
            FROM friends
            WHERE user_id = ?
        ";
        $friendsStmt = $conn->prepare($friendsQuery);
        $friendsStmt->bind_param("i", $userId);
        $friendsStmt->execute();
        $friendsResult = $friendsStmt->get_result();
        $friendsRow = $friendsResult->fetch_assoc();
      
        $requestsQuery = "
            SELECT COUNT(*) AS friend_count
            FROM friend_requests
            WHERE (sender_id = ? OR receiver_id = ?) AND status = 'friends'
        ";
        $requestsStmt = $conn->prepare($requestsQuery);
        $requestsStmt->bind_param("ii", $userId, $userId);
        $requestsStmt->execute();
        $requestsResult = $requestsStmt->get_result();
        $requestsRow = $requestsResult->fetch_assoc();
        

        $totalFriends = $friendsRow['friend_count'] + $requestsRow['friend_count'];
        
        return $totalFriends;
    } catch (Exception $e) {
        $_SESSION['error'] = "Failed to fetch friend count: " . $e->getMessage();
        return 0;
    }
}

/**
 * Get the count of followers for a user in the friends card
 */
function getFollowerCount($conn, $userId) {
    try {
     
        $requestsQuery = "
            SELECT COUNT(*) AS follower_count
            FROM friend_requests
            WHERE receiver_id = ? AND status = 'friends'
        ";
        $requestsStmt = $conn->prepare($requestsQuery);
        $requestsStmt->bind_param("i", $userId);
        $requestsStmt->execute();
        $requestsResult = $requestsStmt->get_result();
        $requestsRow = $requestsResult->fetch_assoc();
        
        $friendsQuery = "
            SELECT COUNT(*) AS follower_count
            FROM friends
            WHERE friend_id = ?
        ";
        $friendsStmt = $conn->prepare($friendsQuery);
        $friendsStmt->bind_param("i", $userId);
        $friendsStmt->execute();
        $friendsResult = $friendsStmt->get_result();
        $friendsRow = $friendsResult->fetch_assoc();
        
        $totalFollowers = $requestsRow['follower_count'] + $friendsRow['follower_count'];
        
        return $totalFollowers;
    } catch (Exception $e) {
        $_SESSION['error'] = "Failed to fetch follower count: " . $e->getMessage();
        return 0;
    }
}


$friends = getFriends($conn, $loggedInUserId);
/**
 * Get the count of friends for the logged-in user
 * And display into right sidebar
 */
$activeUsers = getActiveUsers($conn);
$onlineFriends = [];
foreach ($activeUsers as $user) {
    foreach ($friends as $friend) {
        if ($user['id'] == $friend['id']) {
            $onlineFriends[] = $user;
            break;
        }
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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.people.css" />
    <link rel="stylesheet" href="../assets/css/style.people.card.css" />
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
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert"><?= htmlspecialchars($_SESSION['error']) ?></div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert success-message"><?= htmlspecialchars($_SESSION['success']) ?></div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>
        <?php if (empty($friendRequests)): ?>
            <div class="alert">No friend requests found.</div>
        <?php else: ?>
            <?php foreach ($friendRequests as $request): ?>
                <div class="friend-card">
                    <img src="<?= !empty($request['sender']['profile_picture']) ? 'data:image/jpeg;base64,' . base64_encode($request['sender']['profile_picture']) : '../assets/default_img/def.png' ?>" alt="Profile" class="friend-avatar">
                    <div>
                        <div class="friend-name"><?= htmlspecialchars($request['sender']['first_name'] . ' ' . $request['sender']['last_name']) ?></div>
                    </div>
                    <form method="post" style="display: flex; gap: 10px;">
                        <input type="hidden" name="request_id" value="<?= $request['request_id'] ?>">
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
                    <?php
                    $existingRequest = null;
                    $isFriend = false;

                    // Check if the user is already a friend
                    if (!empty($friends)) {
                        foreach ($friends as $friend) {
                            if ($friend['id'] == $user['id']) {
                                $isFriend = true;
                                break;
                            }
                        }
                    }

                    if ($conn && !$isFriend) {
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
                        <div class="friend-card-header">
                            <img src="<?= !empty($user['profile_picture']) ? 'data:image/jpeg;base64,' . base64_encode($user['profile_picture']) : '../assets/default_img/def.png' ?>" alt="Profile" class="friend-avatar">
                            <div class="friend-name"><?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?></div>
                        </div>
                        <form method="post" class="friend-action-form">
                            <input type="hidden" name="friend_id" value="<?= $user['id'] ?>">
                            <?php if ($isFriend): ?>
                                <button type="button" class="btn btn-secondary" disabled><i class="fas fa-user-check"></i> Friends</button>
                            <?php elseif ($existingRequest): ?>
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
    <div class="d-flex align-items-center mb-3 mt-2">
        <input type="text" id="friendSearch" class="form-control me-2" placeholder="Search Friends" aria-label="Search Friends">
        </div>
        <div class="friend-count d-flex align-items-start justify-content-start mt-1 mb-1">
            <?php
            $friendCount = count($friends);
            if ($friendCount == 1) {
                echo "{$friendCount} Friend";
            } else {
                echo "{$friendCount} Friends";
            }
            ?>
    </div>
    <div class="friends-list" id="friendsList">
        <?php if (empty($friends)): ?>
            <div class="alert">No friends found.</div>
        <?php else: ?>
            <?php foreach ($friends as $friend): ?>
                <div class="friend-card">
                    <input type="hidden" id="friend-id" value="<?= $friend['id'] ?>">
                    <img src="<?= !empty($friend['profile_picture']) ? 'data:image/jpeg;base64,' . base64_encode($friend['profile_picture']) : '../assets/default_img/def.png' ?>" alt="Profile" class="friend-avatar">
                    <div>
                        <div class="friend-name">
                            <a href="profile_view.php?user_id=<?= $friend['id'] ?>" style="text-decoration: none; color: inherit;">
                                <?= htmlspecialchars($friend['first_name'] . ' ' . $friend['last_name']) ?>
                            </a>
                        </div>
                        <div class="friend-id d-none">ID: <?= $friend['id'] ?></div>
                        <div class="d-flex justify-content-center align-items-center">
                            <div class="friend-stats d-flex p-2 gap-2" style="color: #6D6D6D;">
                                <div>
                                    <?php
                                    $friendFriendCount = getFriendCount($conn, $friend['id']);
                                    if ($friendFriendCount == 1) {
                                        echo "{$friendFriendCount} Friend";
                                    } else {
                                        echo "{$friendFriendCount} Friends";
                                    }
                                    ?>
                                </div>
                                <div>
                                    <?php
                                    $followerCount = getFollowerCount($conn, $friend['id']);
                                    if ($followerCount == 1) {
                                        echo "{$followerCount} Follower";
                                    } else {
                                        echo "{$followerCount} Followers";
                                    }
                                    ?>
                                </div>
                            </div>
                                 
                        </div>
                        <div class="mt-2 mb-2">
                            <a href="profile_view.php?user_id=<?= $friend['id'] ?>" class="btn btn-primary btn-sm">View Profile</a>
                            </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>




        <?php else : ?>
            <div class="alert">Page not found.</div>
        <?php endif; ?>
    </div>

    <!-- Right Sidebar -->
<div class="right-sidebar">
    <h3 class="online-title">Online Friends</h3>
    <ul class="online-list">
        <?php if (empty($onlineFriends)): ?>
            <li class="alert">No online friends.</li>
        <?php else: ?>
            <?php foreach ($onlineFriends as $user): ?>
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
        document.getElementById('friendSearch')?.addEventListener('input', function () {
            const query = this.value.toLowerCase();
            document.querySelectorAll('.friend-card').forEach(card => {
                const name = card.querySelector('.friend-name').textContent.toLowerCase();
                card.style.display = name.includes(query) ? 'block' : 'none';
            });
        });
    });

    document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('friendSearch');
    const friendsList = document.getElementById('friendsList');
    
    if (searchInput && friendsList) {
        searchInput.addEventListener('input', function() {
            const query = this.value.toLowerCase();
            const friendCards = friendsList.querySelectorAll('.friend-card');
            
            friendCards.forEach(card => {
                const name = card.querySelector('.friend-name').textContent.toLowerCase();
                card.style.display = name.includes(query) ? 'block' : 'none';
            });
        });
    }
});

document.addEventListener('contextmenu', function(e) {
              e.preventDefault();
            });
</script>
</body>
</html>