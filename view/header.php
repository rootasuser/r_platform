<!-- header section start -->
<header class="bg-white p-3 shadow-sm">
        <div class="container">
            <div class="d-flex align-items-center justify-content-between">
                <div class="logoBox">
                    <a href="dashboard.php">
                    <img src="" alt="logo">
                    </a>
                </div>
                <div class="searchBox d-flex align-items-center">
                    <input type="search" class="form-control rounded-pill" placeholder="Search R Connect">
                    <i class="fas fa-search ml-2"></i>
                </div>
                <div class="iconBox1 d-flex">
                    <a href="dashboard.php">
                    <i class="fa-solid fa-house mx-3"></i>
                    </a>
                    <a href="people.php">
                    <i class="fa-solid fa-user-group mx-3"></i>
                    </a>
                    
                    <a href="videos.php">
                    <i class="fa-solid fa-video mx-3"></i>
                    </a>
                 
                </div>
                <div class="iconBox2 d-flex align-items-center">
                    <i class="fa-solid fa-circle-plus mx-3"></i>
                    <i class="fa-brands fa-facebook-messenger mx-3"></i>
                    <i class="fa-solid fa-bell mx-3"></i>
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

    <script>
        document.addEventListener('DOMContentLoaded', function() { var dropdownToggle = document.querySelector('[data-bs-toggle="dropdown"]'); if (dropdownToggle) { var dropdown = new bootstrap.Dropdown(dropdownToggle); } });
    </script>