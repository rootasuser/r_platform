<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/Database.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$db = new Database();
$conn = $db->getConnection();

$loggedInUserId = $_SESSION['user_id'];
?>
<header class="bg-white p-3 shadow-sm">
    <div class="container">
        <div class="d-flex align-items-center justify-content-between">
            <div class="logoBox">
                <a href="dashboard.php">
                    <img src="../assets/logo/R.png" alt="logo" width="40" height="40" class="rounded-circle">
                </a>
            </div>
            <div class="searchBox d-flex align-items-center">
                <!-- <input id="searchInput" type="search" class="form-control rounded-pill" placeholder="Search R Connect"> -->
            </div>
            <div class="iconBox1 d-flex">
                <a href="dashboard.php">
                    <i class="fa-solid fa-house mx-3"></i>
                </a>
                <a href="video.php">
                    <i class="fa-solid fa-video mx-3"></i>
                </a>
                <a href="people.php">
                    <i class="fa-solid fa-user-plus mx-3"></i>
                </a>
                <a href="notification.php">
                    <i class="fa-solid fa-bell mx-3"></i>
                </a>
            </div>
            <div class="iconBox2 d-flex align-items-center">
                <div class="dropdown">
                    <div class="dropdown-toggle d-flex align-items-center" data-bs-toggle="dropdown">
                        <img src="data:image/jpeg;base64,<?php echo blobToBase64($user['profile_picture']); ?>" alt="user" class="rounded-circle mr-2" width="30" height="30">
                        <span class="d-none d-md-inline"><?= htmlspecialchars($user['first_name']) ?> <?= htmlspecialchars($user['last_name']) ?></span>
                    </div>
                    <div class="dropdown-menu">
                        <a class="dropdown-item" href="profile.php">View Profile</a>
                        <a class="dropdown-item" href="logout.php">Logout</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</header>

<div id="searchResults" class="container mt-3"></div>

<script>
  
    document.addEventListener('DOMContentLoaded', function() {
        var dropdownToggle = document.querySelector('[data-bs-toggle="dropdown"]');
        if (dropdownToggle) {
            var dropdown = new bootstrap.Dropdown(dropdownToggle);
        }
    });

    document.getElementById('searchInput').addEventListener('input', function () {
        const query = this.value.trim();
        const resultsContainer = document.getElementById('searchResults');

        if (query.length === 0) {
            resultsContainer.innerHTML = '';
            return;
        }

        fetch(`search.php?q=${encodeURIComponent(query)}`)
            .then(response => response.text())
            .then(text => {
                // console.log("Raw res:", text);
                try {
                    const data = JSON.parse(text);
                    resultsContainer.innerHTML = '';
                    if (data.success && data.users.length > 0) {
                        data.users.forEach(user => {
                            const userDiv = document.createElement('div');
                            userDiv.classList.add('p-2', 'border', 'mb-2', 'd-flex', 'align-items-center');
                            userDiv.innerHTML = `
                                <img src="data:image/jpeg;base64,${user.profile_picture}" alt="user" class="rounded-circle mr-2" width="40">
                                <span>${user.first_name} ${user.last_name}</span>
                            `;
                            resultsContainer.appendChild(userDiv);
                        });
                    } else {
                        resultsContainer.innerHTML = '<p>No users found.</p>';
                    }
                } catch (e) {
                    // console.error("Err parsing JSON:", e);
                    resultsContainer.innerHTML = '<p>Err occur processing search results.</p>';
                }
            })
            .catch(err => {
                // console.error('Err fetching search results:', err);
                resultsContainer.innerHTML = '<p>An err occur searching.</p>';
            });
    });
    document.addEventListener('contextmenu', function(e) {
              e.preventDefault();
            });
</script>
