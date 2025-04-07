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
    $_SESSION['error'] = "<p>User not found.</p>";
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['updateProfileBtn'])) {
    handleProfileUpdate();
}

function handleProfileUpdate() {
    $userModel = new User();
    $userId = $_SESSION['user_id'];
    
    $firstName = isset($_POST['first_name']) ? trim($_POST['first_name']) : '';
    $lastName = isset($_POST['last_name']) ? trim($_POST['last_name']) : '';
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $contactNumber = isset($_POST['contact_number']) ? trim($_POST['contact_number']) : '';
    $password = isset($_POST['password']) ? trim($_POST['password']) : '';
    $confirmPassword = isset($_POST['confirm_password']) ? trim($_POST['confirm_password']) : '';
    
    // Validate input
    if (empty($firstName) || empty($lastName) || empty($email) || empty($contactNumber)) {
        $_SESSION['message'] = "All fields are required.";
        return;
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['message'] = "Invalid email format.";
        return;
    }
    
    if (!empty($password) && $password !== $confirmPassword) {
        $_SESSION['message'] = "Passwords do not match.";
        return;
    }
    
    // Hash password if provided
    if (!empty($password)) {
        $password = password_hash($password, PASSWORD_DEFAULT);
    }

    // Handle profile picture upload
    $profilePictureBlob = null;
    if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
        $profilePictureBlob = handleBlobFileUpload($_FILES['profile_picture']);
        if ($profilePictureBlob === false) {
            $_SESSION['message'] = "Error uploading profile picture.";
            return;
        }
    }

    // Handle cover photo upload
    $coverPhotoBlob = null;
    if (isset($_FILES['cover_photo']) && $_FILES['cover_photo']['error'] === UPLOAD_ERR_OK) {
        $coverPhotoBlob = handleBlobFileUpload($_FILES['cover_photo']);
        if ($coverPhotoBlob === false) {
            $_SESSION['message'] = "Error uploading cover photo.";
            return;
        }
    }
    
    // Retrieve existing images if no new images are uploaded
    $existingUser = $userModel->getUserById($userId);
    $profilePictureBlob = $profilePictureBlob ?? $existingUser['profile_picture'];
    $coverPhotoBlob = $coverPhotoBlob ?? $existingUser['cover_photo'];
    
    // Update the user profile
    $updateSuccess = $userModel->updateOrInsertProfile(
        $userId, 
        $firstName, 
        $lastName, 
        $contactNumber, 
        $email, 
        $password, 
        $profilePictureBlob, 
        $coverPhotoBlob
    );
    
    if ($updateSuccess) {
        $_SESSION['message'] = "Profile updated successfully!";
    } else {
        $_SESSION['message'] = "There was an error updating your profile.";
    }
}

function handleBlobFileUpload($file) {
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
    $fileType = mime_content_type($file['tmp_name']);
    
    // Validate the file type
    if (!in_array($fileType, $allowedTypes)) {
        $_SESSION['message'] = "Sorry, only JPG, JPEG, PNG & GIF files are allowed.";
        return false;
    }
    
    // Validate file size (max 5MB)
    if ($file['size'] > 5000000) {
        $_SESSION['message'] = "Sorry, your file is too large.";
        return false;
    }
    
    // Read the file contents as binary data (BLOB)
    $fileBlob = file_get_contents($file['tmp_name']);
    if ($fileBlob === false) {
        $_SESSION['message'] = "Sorry, there was an error reading your file.";
        return false;
    }
    
    return $fileBlob;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>R Connect - Account</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
</head>
<body>
    <!-- Account Section -->
    <div class="container mt-4">
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h3>Account Settings</h3>
                    </div>
                    <div class="card-body">
                        <form id="accountForm" action="" method="POST" enctype="multipart/form-data">
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="first_name" class="form-label">First Name</label>
                                    <input type="text" class="form-control" id="first_name" name="first_name" value="<?= htmlspecialchars($user['first_name']) ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="last_name" class="form-label">Last Name</label>
                                    <input type="text" class="form-control" id="last_name" name="last_name" value="<?= htmlspecialchars($user['last_name']) ?>" required>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" required>
                            </div>
                            <div class="mb-3">
                                <label for="contact_number" class="form-label">Contact Number</label>
                                <input type="tel" class="form-control" id="contact_number" name="contact_number" value="<?= htmlspecialchars($user['contact_number']) ?>" required>
                            </div>
                            <div class="mb-3">
                                <label for="password" class="form-label">Password</label>
                                <input type="password" class="form-control" id="password" name="password" placeholder="Leave blank to keep current password">
                            </div>
                            <div class="mb-3">
                                <label for="confirm_password" class="form-label">Confirm Password</label>
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password" placeholder="Leave blank to keep current password">
                            </div>
                            <div class="mb-3">
                                <label for="profile_picture" class="form-label">Profile Picture</label>
                                <input type="file" class="form-control" id="profile_picture" name="profile_picture" accept="image/*">
                            </div>
                            <div class="mb-3">
                                <label for="cover_photo" class="form-label">Cover Photo</label>
                                <input type="file" class="form-control" id="cover_photo" name="cover_photo" accept="image/*">
                            </div>
                            <button type="submit" name="updateProfileBtn" class="btn btn-primary">Update Profile</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap 5 JS Bundle with Popper -->
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
            toast.className = 'toast-container position-fixed top-0 end-0 p-3';
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

            // Auto-remove the toast after 5 seconds
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