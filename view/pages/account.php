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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['updateProfileBtn'])) {
    handleProfileUpdate();
}


function handleProfileUpdate() {
    global $userModel;
    
    $userId = $_SESSION['user_id'];
    $firstName = trim($_POST['first_name'] ?? '');
    $lastName = trim($_POST['last_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $contactNumber = trim($_POST['contact_number'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $confirmPassword = trim($_POST['confirm_password'] ?? '');
    
    if (empty($firstName) || empty($lastName) || empty($email) || empty($contactNumber)) {
        setMessage("All fields are required.");
        return;
    }
    

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        setMessage("Invalid email format.");
        return;
    }
    
    if (!empty($password) && $password !== $confirmPassword) {
        setMessage("Passwords do not match.");
        return;
    }
    
    $hashedPassword = !empty($password) ? password_hash($password, PASSWORD_DEFAULT) : null;

    $profilePictureBlob = handleFileUpload($_FILES['profile_picture'] ?? null);
    if ($profilePictureBlob === false) {
        return;
    }
    
    $coverPhotoBlob = handleFileUpload($_FILES['cover_photo'] ?? null);
    if ($coverPhotoBlob === false) {
        return;
    }
    

    $existingUser = $userModel->getUserById($userId);
    if (!$existingUser) {
        setMessage("User not found.");
        return;
    }
    $profilePictureBlob = $profilePictureBlob ?? $existingUser['profile_picture'];
    $coverPhotoBlob = $coverPhotoBlob ?? $existingUser['cover_photo'];
    
    $updateSuccess = $userModel->updateOrInsertProfile(
        $userId,
        $firstName,
        $lastName,
        $contactNumber,
        $email,
        $hashedPassword,
        $profilePictureBlob,
        $coverPhotoBlob
    );
    
    if ($updateSuccess) {
        setMessage("Profile updated successfully!");
    } else {
        setMessage("Err updating your profile.");
    }
}

/**
 * Handles file upload and returns BLOB data
 * 
 * @param array|null $file File data from $_FILES
 * @return string|null BLOB data or null if no file uploaded
 */
function handleFileUpload($file) {
    if (isset($file) && $file['error'] === UPLOAD_ERR_OK) {
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
        $fileType = mime_content_type($file['tmp_name']);
        
        if (!in_array($fileType, $allowedTypes)) {
            setMessage("Only JPG, JPEG, PNG & GIF files are allowed.");
            return false;
        }
        
        if ($file['size'] > 5000000) {
            setMessage("File is too large (max 5MB).");
            return false;
        }
        
        $fileBlob = file_get_contents($file['tmp_name']);
        if ($fileBlob === false) {
            setMessage("Err read file.");
            return false;
        }
        
        return $fileBlob;
    }
    
    return null;
}

/**
 * Sets a message in the session
 * 
 * @param string $message Message to set
 */
function setMessage($message) {
    $_SESSION['message'] = $message;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.account.css">
    <title>Account Settings</title>
    
</head>
<body>
    <div class="container mt-4">
        <div class="row">
            <div class="col-md-12">
                <div class="card border-0">
                    <div class="card-header">
                        <h3>Account Settings</h3>
                    </div>
                    <div class="card-body">
                        <form id="accountForm" action="" method="POST" enctype="multipart/form-data">
                            <!-- Display messages -->
                            <?php if (isset($_SESSION['message'])): ?>
                                <div class="message-container <?= isset($_SESSION['error']) ? 'error-message' : 'success-message' ?>">
                                    <?= $_SESSION['message'] ?>
                                </div>
                                <?php unset($_SESSION['message']); ?>
                                <?php unset($_SESSION['error']); ?>
                            <?php endif; ?>
                            
                            <div class="row mb-3 border-0">
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
                                <label class="file-label">Profile Picture</label>
                                <img src="data:image/jpeg;base64,<?= htmlspecialchars(base64_encode($user['profile_picture'])) ?>" alt="Profile Picture" class="profile-preview">
                                <input type="file" class="file-input" id="profile_picture" name="profile_picture" accept="image/*">
                            </div>
                            
                            <div class="mb-3">
                                <label class="file-label">Cover Photo</label>
                                <img src="data:image/jpeg;base64,<?= htmlspecialchars(base64_encode($user['cover_photo'])) ?>" alt="Cover Photo" class="cover-preview">
                                <input type="file" class="file-input" id="cover_photo" name="cover_photo" accept="image/*">
                            </div>
                            <div class="mb-3 d-flex justify-content-end align-items end">
                            <button type="submit" name="updateProfileBtn" class="btn btn-primary">Update</button>     
                            </div>
                           
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>


    <script src="https://cdn.jsdelivr.net/npm/jquery@3.6.0/dist/jquery.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.min.js"></script>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const profilePictureInput = document.getElementById('profile_picture');
            const coverPhotoInput = document.getElementById('cover_photo');
            const profilePreview = document.querySelector('.profile-preview');
            const coverPreview = document.querySelector('.cover-preview');
            
            function previewImage(input, previewElement) {
                if (input.files && input.files[0]) {
                    const reader = new FileReader();
                    
                    reader.onload = function(e) {
                        previewElement.src = e.target.result;
                    }
                    
                    reader.readAsDataURL(input.files[0]);
                }
            }
            
            if (profilePictureInput) {
                profilePictureInput.addEventListener('change', function() {
                    previewImage(this, profilePreview);
                });
            }
            
            if (coverPhotoInput) {
                coverPhotoInput.addEventListener('change', function() {
                    previewImage(this, coverPreview);
                });
            }
        });
        
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
            setTimeout(() => {
                const toastElement = new bootstrap.Toast(toast);
                toastElement.hide();
                setTimeout(() => {
                    toast.remove();
                }, 500);
            }, 5000);
        }
        document.addEventListener('contextmenu', function(e) {
              e.preventDefault();
            });
    </script>
</body>
</html>        