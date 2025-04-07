<?php
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../model/User.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header("Location: ../public/login.php");
    exit();
}


$user_id = $_GET['user_id'] ?? $_SESSION['user_id'];

$userModel = new User();
$user = $userModel->getUserById($user_id);

if (!$user) {
    $_SESSION['error'] = "<p>User not found.</p>";
    header("Location: ../public/index.php");
    exit();
}

function blobToBase64($blob) {
    return base64_encode($blob);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>R Connect</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.profile.css">
</head>
<body>
    <?php include('header.php'); ?>

    <!-- Profile Section -->
    <div class="container mt-4">
        <!-- Profile Header -->
        <div class="row">
            <div class="col-md-12">
                <div class="profile-cover">
                    <form id="coverPhotoForm" action="" method="POST" enctype="multipart/form-data">
                        <img src="data:image/jpeg;base64,<?php echo blobToBase64($user['cover_photo']); ?>" alt="Cover Photo" id="coverPhoto">
                        <input type="file" name="cover_photo" accept="image/*" style="display:none;" onchange="this.form.submit();">
                    </form>
                </div>
                <div class="profile-header d-flex justify-content-between align-items-end">
                    <div class="d-flex align-items-center">
                        <form id="profilePicForm" action="" method="POST" enctype="multipart/form-data" style="margin:0;">
                            <img src="data:image/jpeg;base64,<?php echo blobToBase64($user['profile_picture']); ?>" alt="Profile Picture" class="profile-picture" id="profilePicture">
                            <input type="file" name="profile_picture" id="profilePictureInput" accept="image/*" style="display:none;" onchange="this.form.submit();">
                        </form>
                        <div class="ms-3">
                            <h1 class="profile-name text-white"><?= htmlspecialchars($user['first_name']) ?> <?= htmlspecialchars($user['last_name']) ?></h1>
                            <div class="profile-stats">
                                <span>3.4K followers</span> · <span>456 Friends</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Profile Navigation -->
        <div class="row">
            <div class="col-md-12">
                <ul class="nav profile-tabs">
                    <li class="nav-item">
                        <a class="nav-link active" href="?page=posts">Posts</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="?page=account">Account</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="?page=photos">Photos</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="?page=videos">Videos</a>
                    </li>
                </ul>
            </div>
        </div>

        <div class="row">
            <?php
                $allowedPages = ['posts', 'account', 'photos', 'videos'];
                $page = $_GET['page'] ?? 'posts';
                $page = in_array($page, $allowedPages, true) ? $page : 'posts';
                include "pages/$page.php";
            ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        window.addEventListener('load', function() {
            <?php if (isset($_SESSION['message'])): ?>
                showToast("<?= htmlspecialchars($_SESSION['message']) ?>");
                <?php unset($_SESSION['message']); ?>
            <?php endif; ?>
        });

        function showToast(message) {
            const toast = document.createElement('div');
            toast.className = 'toast-container position-fixed bottom-0 end-0 p-3';
            toast.innerHTML = `
                <div id="liveToast" class="toast show" role="alert" aria-live="assertive" aria-atomic="true">
                    <div class="toast-header">
                        <strong class="me-auto">Notification</strong>
                        <small>Just now</small>
                        <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
                    </div>
                    <div class="toast-body">
                        ${message}
                    </div>
                </div>
            `;
            document.body.appendChild(toast);

            setTimeout(() => {
                const toastElement = new bootstrap.Toast(toast);
                toastElement.hide();
                setTimeout(() => {
                    toast.remove();
                }, 500);
            }, 5000);
        }
    </script>
</body>
</html>