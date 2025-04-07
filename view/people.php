<?php
if (session_status() === PHP_SESSION_NONE) session_start();

require_once __DIR__ . '/../config/Database.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$db = new Database();
$conn = $db->getConnection();
$loggedInUserId = $_SESSION['user_id'];

$page = $_GET['page'] ?? 'suggested_friends';

/**
 * Fetch all users except the logged-in user.
 */
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
        return ['error' => 'Database error: ' . $e->getMessage()];
    }
}

$allUsers = [];
if (in_array($page, ['suggested_friends', 'home', 'suggestions'])) {
    $allUsers = getAllUsers($conn, $loggedInUserId);
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>R Connect</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.jsdelivr.net/npm/vue@3.4.21/dist/vue.global.prod.js"></script>
    <style>
        <?php include '../assets/css/style.people.css'; ?> 
    </style>
</head>
<body>
<div class="wrapper">
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-logo">R</div>
        <ul class="sidebar-nav">
            <li><a href="?page=home" class="<?= $page === 'home' ? 'active' : '' ?>"><span class="icon">🏠</span>Home</a></li>
            <li><a href="?page=suggested_friends" class="<?= $page === 'suggested_friends' ? 'active' : '' ?>"><span class="icon">🤝</span>Friend Requests</a></li>
            <li><a href="?page=suggestions" class="<?= $page === 'suggestions' ? 'active' : '' ?>"><span class="icon">📢</span>Suggestions</a></li>
            <li><a href="?page=friends" class="<?= $page === 'friends' ? 'active' : '' ?>"><span class="icon">👥</span>Friends</a></li>
        </ul>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <?php if ($page === 'home') : ?>
            <?php header("Location: dashboard.php"); exit(); ?>
        
        <?php elseif ($page === 'suggested_friends') : ?>
            <h2 class="page-title">Friend Requests</h2>

            <div class="search-box">
                <input type="text" id="friendSearch" placeholder="Search person...">
            </div>

            <div class="friend-requests-container" id="friendRequestsContainer">
                <?php if (isset($allUsers['error'])): ?>
                    <div class="alert"><?= $allUsers['error'] ?></div>
                <?php elseif (empty($allUsers)): ?>
                    <div class="alert">No users found.</div>
                <?php else: ?>
                    <?php foreach ($allUsers as $user): ?>
                        <div class="friend-card">
                            <div class="friend-avatar">
                                <img src="<?= !empty($user['profile_picture']) ? 'data:image/jpeg;base64,' . base64_encode($user['profile_picture']) : '../assets/default_img/def.png' ?>" alt="Profile">
                            </div>
                            <div class="friend-name"><?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?></div>
                            <div class="friend-mutual"><?= rand(1, 10) ?> mutual friends</div>
                            <button class="confirm-btn" data-user-id="<?= $user['id'] ?>">Confirm</button>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

        <?php elseif ($page === 'suggestions') : ?>
            <h2 class="page-title">Suggestions</h2>
            <div class="suggestions-box">
                <p>Here are some friend suggestions for you!</p>
                <div class="friend-requests-container">
                    <?php if (isset($allUsers['error'])): ?>
                        <div class="alert"><?= $allUsers['error'] ?></div>
                    <?php elseif (empty($allUsers)): ?>
                        <div class="alert">No suggestions available.</div>
                    <?php else: ?>
                        <?php foreach ($allUsers as $user): ?>
                            <div class="friend-card">
                                <div class="friend-avatar">
                                    <img src="<?= !empty($user['profile_picture']) ? 'data:image/jpeg;base64,' . base64_encode($user['profile_picture']) : '../assets/default_img/def.png' ?>" alt="Profile">
                                </div>
                                <div class="friend-name"><?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?></div>
                                <button class="confirm-btn" data-user-id="<?= $user['id'] ?>">Add as Friend</button>
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
            <li><span class="online-dot"></span> Mark</li>
            <li><span class="online-dot"></span> Jane</li>
        </ul>
    </div>
</div>

<!-- JS: Friend search filter -->
<script>
    document.getElementById('friendSearch')?.addEventListener('input', function () {
        const query = this.value.toLowerCase();
        document.querySelectorAll('.friend-card').forEach(card => {
            const name = card.querySelector('.friend-name').textContent.toLowerCase();
            card.style.display = name.includes(query) ? 'block' : 'none';
        });
    });
</script>
</body>
</html>
