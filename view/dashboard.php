<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }

require_once __DIR__ . '/../config/Database.php';

if (!isset($_SESSION['user_id'])) 
    {
        header("Location: index.php");
        exit();
    }

    $db = new Database();
    $conn = $db->getConnection();

    require_once __DIR__ . '/../model/User.php';
    require_once __DIR__ . '/../model/DashboardModel.php';
    $userModel = new User();
    $user = $userModel->getUserById($_SESSION['user_id']);

    if (!$user || isset($user['error'])) {
        echo "<p>User not found.</p>";
        exit();
    }

function blobToBase64($blob) {
    return base64_encode($blob);
}

function getActiveUsers($conn) {
    try {
        $query = "SELECT id, first_name, last_name, profile_picture, status FROM users WHERE status IN ('Active', 'Offline')";
        $stmt = $conn->prepare($query);

        if ($stmt === false) {
            throw new Exception("Err P.S: " . $conn->error);
        }

        $stmt->execute();
        $result = $stmt->get_result();

        if ($result === false) {
            throw new Exception("Err E.Q: " . $conn->error);
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

if (isset($_GET['action'])) {
    if ($_GET['action'] === 'load_messages' && isset($_GET['recipient_id'])) {
        loadMessages($_GET['recipient_id']);
        exit();
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'send_message') {
        sendMessage($_POST['message'], $_POST['sender_id'], $_POST['recipient_id']);
        exit();
    }
}

function loadMessages($recipientId) {
    global $conn, $_SESSION;
    header('Content-Type: application/json');
    try {
        $senderId = $_SESSION['user_id'];
        $query = "SELECT messages.*, users.first_name, users.last_name 
                  FROM messages 
                  JOIN users ON messages.sender_id = users.id
                  WHERE (messages.sender_id = ? AND messages.recipient_id = ?) 
                     OR (messages.sender_id = ? AND messages.recipient_id = ?)
                  ORDER BY messages.created_at";
        $stmt = $conn->prepare($query);

        if ($stmt === false) {
            echo json_encode(['success' => false, 'error' => 'Error preparing statement: ' . $conn->error]);
            exit();
        }

        $stmt->bind_param('iiii', $senderId, $recipientId, $recipientId, $senderId);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result === false) {
            echo json_encode(['success' => false, 'error' => 'Error executing query: ' . $conn->error]);
            exit();
        }

        $messages = [];
        while ($row = $result->fetch_assoc()) {
            $messages[] = $row;
        }

        echo json_encode(['success' => true, 'messages' => $messages]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
    }
    exit();
}

function sendMessage($message, $senderId, $recipientId) {
    global $conn, $_SESSION;

    try {
        if ($senderId != $_SESSION['user_id']) {
            echo json_encode(['success' => false, 'error' => 'Unauthorized sender']);
            exit();
        }

        $query = "INSERT INTO messages (sender_id, recipient_id, message, message_type, created_at) VALUES (?, ?, ?, 'text', NOW())";
        $stmt = $conn->prepare($query);

        if ($stmt === false) {
            echo json_encode(['success' => false, 'error' => 'Error preparing statement: ' . $conn->error]);
            exit();
        }

        $stmt->bind_param('iis', $senderId, $recipientId, $message);
        $stmt->execute();

        if ($stmt->affected_rows > 0) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Failed to send message']);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
    }
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['postText'])) {
        require_once __DIR__ . '/../config/Database.php';
        $db = new Database();
        $conn = $db->getConnection();

        $userId = $_SESSION['user_id'];
        $postText = $_POST['postText'];
        $postImage = null;
        $postVideo = null;


        $imageUploadDir = 'upload_images/';
        $videoUploadDir = 'upload_videos/';

        if (!is_dir($imageUploadDir)) {
            mkdir($imageUploadDir, 0777, true);
            if (!is_dir($imageUploadDir)) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'error' => 'Failed to create image upload directory']);
                exit();
            }
        }
        if (!is_dir($videoUploadDir)) {
            mkdir($videoUploadDir, 0777, true);
            if (!is_dir($videoUploadDir)) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'error' => 'Failed to create video upload directory']);
                exit();
            }
        }

        if (isset($_FILES['postImage']) && $_FILES['postImage']['error'] === UPLOAD_ERR_OK) {
            $fileType = mime_content_type($_FILES['postImage']['tmp_name']);
            if (!in_array($fileType, ['image/jpeg', 'image/png', 'image/gif'])) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'error' => 'Invalid image type']);
                exit();
            }
            if (!is_readable($_FILES['postImage']['tmp_name'])) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'error' => 'Cannot read image file']);
                exit();
            }

            $imageFileName = uniqid() . '_' . basename($_FILES['postImage']['name']);
            $imageFilePath = $imageUploadDir . $imageFileName;

            if (!move_uploaded_file($_FILES['postImage']['tmp_name'], $imageFilePath)) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'error' => 'Failed to move uploaded image file']);
                exit();
            }

            if (!file_exists($imageFilePath)) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'error' => 'Image file not found after upload']);
                exit();
            }

            $postImage = 'upload_images/' . $imageFileName;
        }

        if (isset($_FILES['postVideo']) && $_FILES['postVideo']['error'] === UPLOAD_ERR_OK) {
            $fileType = mime_content_type($_FILES['postVideo']['tmp_name']);
            if (!in_array($fileType, ['video/mp4', 'video/quicktime', 'video/x-msvideo'])) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'error' => 'Invalid video type']);
                exit();
            }
            if (!is_readable($_FILES['postVideo']['tmp_name'])) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'error' => 'Cannot read video file']);
                exit();
            }


            $videoFileName = uniqid() . '_' . basename($_FILES['postVideo']['name']);
            $videoFilePath = $videoUploadDir . $videoFileName;

            if (!move_uploaded_file($_FILES['postVideo']['tmp_name'], $videoFilePath)) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'error' => 'Failed to move uploaded video file']);
                exit();
            }

            if (!file_exists($videoFilePath)) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'error' => 'Video file not found after upload']);
                exit();
            }

            $postVideo = 'upload_videos/' . $videoFileName;
        }

        try {
            $query = "INSERT INTO posts (user_id, text, image, video, created_at) VALUES (?, ?, ?, ?, NOW())";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("isss", $userId, $postText, $postImage, $postVideo);
            $stmt->execute();

            if ($stmt->affected_rows > 0) {
                header('Content-Type: application/json');
                echo json_encode(['success' => true, 'message' => 'Post created successfully.']);
            } else {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'error' => 'Failed to create post.']);
            }
        } catch (Exception $e) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Error creating post: ' . $e->getMessage()]);
        }

        $conn->close();
        exit(); 
    }
}
function getAllPosts($conn) {
    try {
        $query = "SELECT p.*, u.first_name, u.last_name, u.profile_picture 
                  FROM posts p
                  JOIN users u ON p.user_id = u.id
                  ORDER BY p.created_at DESC";
        $stmt = $conn->prepare($query);

        if ($stmt === false) {
            throw new Exception("Error preparing statement: " . $conn->error);
        }

        $stmt->execute();
        $result = $stmt->get_result();

        if ($result === false) {
            throw new Exception("Error executing query: " . $conn->error);
        }

        $posts = [];
        while ($row = $result->fetch_assoc()) {
            $row['image'] = !empty($row['image']) ? '/r_platform/view/upload_images/' . basename($row['image']) : null;
            $row['video'] = !empty($row['video']) ? '/r_platform/view/upload_videos/' . basename($row['video']) : null;
            $posts[] = $row;
        }

        return $posts;
    } catch (Exception $e) {
        return ['error' => 'DB Err: ' . $e->getMessage()];
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
        return " ";
    } elseif ($minutes < 60) {
        return "{$minutes} minutes ago";
    } elseif ($hours < 24) {
        return "{$hours} hours ago";
    } elseif ($days < 7) {
        return "{$days} days ago";
    } elseif ($weeks < 4) {
        return "{$weeks} weeks ago";
    } elseif ($months < 12) {
        return "{$months} months ago";
    } else {
        return "{$years} years ago";
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        require_once __DIR__ . '/../config/Database.php';
        $db = new Database();
        $conn = $db->getConnection();

        $userId = $_SESSION['user_id'];

        if ($_POST['action'] === 'like' && isset($_POST['post_id'])) {
            $postId = $_POST['post_id'];
            $result = likePost($conn, $postId, $userId);
            echo json_encode($result);
            exit();
        }

        if ($_POST['action'] === 'unlike' && isset($_POST['post_id'])) {
            $postId = $_POST['post_id'];
            $result = unlikePost($conn, $postId, $userId);
            echo json_encode($result);
            exit();
        }

        if ($_POST['action'] === 'add_comment' && isset($_POST['post_id']) && isset($_POST['comment_text'])) {
            $postId = $_POST['post_id'];
            $commentText = $_POST['comment_text'];
            $result = addComment($conn, $postId, $userId, $commentText);
            echo json_encode($result);
            exit();
        }

        if ($_POST['action'] === 'share' && isset($_POST['post_id'])) {
            $postId = $_POST['post_id'];
            $result = sharePost($conn, $postId, $userId);
            echo json_encode($result);
            exit();
        }
    }
}

function likePost($conn, $postId, $userId) {
    try {
        $query = "INSERT INTO likes (post_id, user_id, created_at) VALUES (?, ?, NOW()) 
                  ON DUPLICATE KEY UPDATE created_at = NOW()";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ii", $postId, $userId);
        $stmt->execute();

        if ($stmt->affected_rows > 0) {
            return ['success' => true, 'message' => 'Post liked successfully.'];
        } else {
            return ['success' => false, 'error' => 'Failed to like post.'];
        }
    } catch (Exception $e) {
        return ['success' => false, 'error' => 'Error liking post: ' . $e->getMessage()];
    }
}

function unlikePost($conn, $postId, $userId) {
    try {
        $query = "DELETE FROM likes WHERE post_id = ? AND user_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ii", $postId, $userId);
        $stmt->execute();

        if ($stmt->affected_rows > 0) {
            return ['success' => true, 'message' => 'Post unliked successfully.'];
        } else {
            return ['success' => false, 'error' => 'Failed to unlike post.'];
        }
    } catch (Exception $e) {
        return ['success' => false, 'error' => 'Error unliking post: ' . $e->getMessage()];
    }
}

function addComment($conn, $postId, $userId, $commentText) {
    try {
        $query = "INSERT INTO comments (post_id, user_id, comment_text, created_at) VALUES (?, ?, ?, NOW())";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("iis", $postId, $userId, $commentText);
        $stmt->execute();

        if ($stmt->affected_rows > 0) {
            return ['success' => true, 'message' => 'Comment added successfully.'];
        } else {
            return ['success' => false, 'error' => 'Failed to add comment.'];
        }
    } catch (Exception $e) {
        return ['success' => false, 'error' => 'Error adding comment: ' . $e->getMessage()];
    }
}

function sharePost($conn, $postId, $userId) {
    try {
        $query = "INSERT INTO shares (post_id, user_id, created_at) VALUES (?, ?, NOW())";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ii", $postId, $userId);
        $stmt->execute();

        if ($stmt->affected_rows > 0) {
            return ['success' => true, 'message' => 'Post shared successfully.'];
        } else {
            return ['success' => false, 'error' => 'Failed to share post.'];
        }
    } catch (Exception $e) {
        return ['success' => false, 'error' => 'Error sharing post: ' . $e->getMessage()];
    }
}

function getPostLikesCount($conn, $postId) {
    try {
        $query = "SELECT COUNT(*) AS likes_count FROM likes WHERE post_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $postId);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();

        return $row['likes_count'];
    } catch (Exception $e) {
        return 0;
    }
}

function getPostComments($conn, $postId) {
    try {
        $query = "SELECT c.*, u.first_name, u.last_name, u.profile_picture 
                  FROM comments c
                  JOIN users u ON c.user_id = u.id
                  WHERE c.post_id = ?
                  ORDER BY c.created_at DESC";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $postId);
        $stmt->execute();
        $result = $stmt->get_result();

        $comments = [];
        while ($row = $result->fetch_assoc()) {
            $comments[] = $row;
        }

        return $comments;
    } catch (Exception $e) {
        return ['error' => 'Database error: ' . $e->getMessage()];
    }
}

function getPostSharesCount($conn, $postId) {
    try {
        $query = "SELECT COUNT(*) AS shares_count FROM shares WHERE post_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $postId);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();

        return $row['shares_count'];
    } catch (Exception $e) {
        return 0;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="shortcut icon" href="images/favicon.ico" type="image/x-icon">
    <title>R Connect</title>
    <link rel="stylesheet" href="../assets/node_modules/bootstrap/dist/css/bootstrap.min.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.dashboard.css">
    <link rel="stylesheet" href="../assets/css/style.modal2.css">
    <link rel="stylesheet" href="../assets/css/style.comment-box.css">
</head>
<body>
    <?php include('header.php'); ?>

    <div class="toast-container position-fixed top-0 end-0 p-3">
        <div id="successToast" class="toast" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="toast-header">
                <strong class="me-auto">Success</strong>
                <small>just now</small>
                <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
            <div class="toast-body">
                Post submitted successfully!
            </div>
        </div>
    </div>

<div class="container mt-4">
    <div class="row">
<div class="col-md-3">
    <div class="card mb-4">
        <div class="card-body">
            <div class="profile d-flex align-items-center justify-content-center text-center mb-4">
                <h1 class="text-center">ADS</h1>
            </div>
            
            <div class="pages mb-4">
                <div class="card shadow-lg">
                    <div class="card-body">
                    <video src="" controls poster="default_img/def.png" style="width: 100%; height: 150px; object-fit: cover; border-radius: 5px; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);">
                    Your browser does not support the video tag.
                </video>
                    </div>
                </div>
            </div>

            <div class="group mb-4">
                <div class="card shadow-lg">
                    <div class="card-body">
                    <video src="" controls poster="default_img/def.png" style="width: 100%; height: 150px; object-fit: cover; border-radius: 5px; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);">
                    Your browser does not support the video tag.
                </video>
                    </div>
                </div>
            </div>

            <div class="group mb-4">
                <div class="card shadow-lg">
                    <div class="card-body">
                    <video src="" controls poster="default_img/def.png" style="width: 100%; height: 150px; object-fit: cover; border-radius: 5px; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);">
                    Your browser does not support the video tag.
                </video>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

            <!-- home center start here -->
            <div class="col-md-6">
                <div class="card mb-4">
                    <div class="card-body">
                        <div class="createPost mb-4">
                            <div class="post-text position-relative mb-4">
                                <img src="data:image/jpeg;base64,<?php echo blobToBase64($user['profile_picture']); ?>" alt="user" class="rounded-circle position-absolute left-0 top-0 mt-3 ml-3" width="40" height="40">
                                <input type="text" class="form-control" placeholder="What's on your mind, <?= htmlspecialchars($user['first_name']) ?>" data-bs-toggle="modal" data-bs-target="#createPostModal">
                            </div>

                            <div class="post-icon d-flex align-items-center justify-content-center">
                                <a href="#" class="bg-light p-2 mr-2" style="background: #ffebed;">
                                    <i class="fa-solid fa-plus" style="background: #ff4154; padding: 5px; border-radius: 5px; color: white;"></i>
                                </a>
                                <a href="#" class="bg-light p-2 mr-2" style="background: #ffebed;">
                                    <i class="fa-solid fa-image" style="background: #ff4154; padding: 5px; border-radius: 5px; color: white;"></i>
                                </a>
                                <a href="#" class="bg-light p-2 mr-2" style="background: #ccdcff;">
                                    <i class="fa-solid fa-video" style="background: #0053ff; padding: 5px; border-radius: 5px; color: white;"></i>
                                </a>
                            </div>
                        </div>

                        <!-- Create Post Modal -->
                        <div class="modal fade" id="createPostModal" tabindex="-1" aria-labelledby="createPostModalLabel" aria-hidden="true">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title" id="createPostModalLabel">Create Post</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body">
                                        <form id="postForm" enctype="multipart/form-data">
                                            <div class="mb-3">
                                                <label for="postText" class="form-label" id="postInputLabel">What's on your mind?</label>
                                                <textarea class="form-control" id="postText" rows="3" required></textarea>
                                            </div>
                                            <div class="mb-3">
                                                <label for="postImage" class="form-label">Upload Image</label>
                                                <input type="file" class="form-control" id="postImage" accept="image/*">
                                                <div class="mt-2">
                                                    <img id="imagePreview" src="" alt="Image Preview" style="max-width: 100%; max-height: 200px; display: none;">
                                                </div>
                                            </div>
                                            <div class="mb-3">
                                                <label for="postVideo" class="form-label">Upload Video</label>
                                                <input type="file" class="form-control" id="postVideo" accept="video/*">
                                                <div class="mt-2">
                                                    <video id="videoPreview" controls style="max-width: 100%; max-height: 200px; display: none;"></video>
                                                </div>
                                            </div>
                                            <button type="submit" class="btn btn-primary">Post</button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <script>
                            document.addEventListener('DOMContentLoaded', function() {
                                var modal = new bootstrap.Modal(document.getElementById('createPostModal'));
                                var postInput = document.querySelector('.post-text input');
                                var modalLabel = document.getElementById('postInputLabel');
                                var userName = '<?= htmlspecialchars($user['first_name']) ?>';
                                var userId = '<?= $_SESSION['user_id'] ?>';
                                
                                modalLabel.textContent = 'What\'s on your mind, ' + userName + '?';
                                
                                postInput.addEventListener('click', function() {
                                    modal.show();
                                });
                                
                                document.getElementById('postImage').addEventListener('change', function(e) {
                                    var file = e.target.files[0];
                                    if (file) {
                                        var reader = new FileReader();
                                        reader.onload = function(e) {
                                            document.getElementById('imagePreview').src = e.target.result;
                                            document.getElementById('imagePreview').style.display = 'block';
                                        };
                                        reader.readAsDataURL(file);
                                    }
                                });
                                
                                document.getElementById('postVideo').addEventListener('change', function(e) {
                                    var file = e.target.files[0];
                                    if (file) {
                                        var reader = new FileReader();
                                        reader.onload = function(e) {
                                            document.getElementById('videoPreview').src = e.target.result;
                                            document.getElementById('videoPreview').style.display = 'block';
                                        };
                                        reader.readAsDataURL(file);
                                    }
                                });
                                
                                document.getElementById('postForm').addEventListener('submit', function(e) {
                                    e.preventDefault();
                                    
                                    var formData = new FormData();
                                    formData.append('postText', document.getElementById('postText').value);
                                    
                                    var postImage = document.getElementById('postImage');
                                    if (postImage.files.length > 0) {
                                        formData.append('postImage', postImage.files[0]);
                                    }
                                    
                                    var postVideo = document.getElementById('postVideo');
                                    if (postVideo.files.length > 0) {
                                        formData.append('postVideo', postVideo.files[0]);
                                    }
                                    
                                    fetch('<?= $_SERVER['PHP_SELF'] ?>', {
                                        method: 'POST',
                                        body: formData
                                    })
                                    .then(response => {
                                        if (!response.ok) {
                                            throw new Error('Network responsenot ok');
                                        }
                                        return response.text();
                                    })
                                    .then(text => {
                                        try {
                                            if (!text.trim()) {
                                                throw new Error('Empty response');
                                            }
                                            
                                            const data = JSON.parse(text); 
                                            
                                            if (data.success) {
                                                var toast = new bootstrap.Toast(document.getElementById('successToast'));
                                                toast.show();
                                                
                                                document.getElementById('postForm').reset();
                                                document.getElementById('imagePreview').style.display = 'none';
                                                document.getElementById('videoPreview').style.display = 'none';
                                            } else {
                                                alert('Err submitting post: ' + data.error);
                                            }
                                        } catch (e) {
                                            console.error('Err parsing JSON:', e);
                                            alert('Failed to parse server response.');
                                        }
                                    })
                                    .catch(error => {
                                        console.error('Err:', error);
                                        alert('An err ocur submitting the post.');
                                    });
                                });
                            });
                        </script>

                        <?php
                        $posts = getAllPosts($conn);
                        ?>

                        <?php foreach ($posts as $post): ?>
                            <?php if (isset($post['error'])): ?>
                                <p class="text-danger"><?= $post['error'] ?></p>
                                <?php continue; ?>
                            <?php endif; ?>
                            <div class="fb-post1">
                                <div class="fb-post1-container">
                                    <div class="fb-post1-header mb-3">
                                        <hr>
                                    </div>
                                    <div class="fb-p1-main">
                                        <div class="post-title d-flex flex-wrap mb-4">
                                            <img src="data:image/jpeg;base64,<?= blobToBase64($post['profile_picture']) ?>" alt="user picture" class="rounded-circle mr-3 mb-3" width="40" height="40">
                                            <div class="flex-grow-1">
                                                <ul class="list-unstyled">
                                                    <li class="mb-1">
                                                        <h6 class="mb-0"><?= htmlspecialchars($post['first_name'] . ' ' . $post['last_name']) ?> <span class="text-muted">. <?= timeAgo($post['created_at']) ?></span></h6>
                                                    </li>
                                                    <li class="mb-2">
                                                        <span class="text-muted"><?= date('d F Y H:i A', strtotime($post['created_at'])) ?></span>
                                                    </li>
                                                </ul>
                                                <p class="mb-0"><?= htmlspecialchars($post['text']) ?></p>
                                            </div>
                                        </div>

                                        <?php if (!empty($post['image'])): ?>
                                            <div class="post-images row mb-4">
                                                <div class="col-md-12">
                                                    <img src="<?= $post['image'] ?>" alt="post image" class="rounded w-100" style="max-height: 400px;">
                                                </div>
                                            </div>
                                        <?php endif; ?>

                                        <?php if (!empty($post['video'])): ?>
                                            <div class="post-video mb-4">
                                                <video controls class="w-100" style="max-height: 200px;">
                                                    <source src="<?= $post['video'] ?>" type="video/mp4">
                                                    Your browser does not support the video tag.
                                                </video>
                                            </div>
                                        <?php endif; ?>

                                        <div class="like-comment">
                                            <ul class="list-inline mb-0">
                                                <li class="list-inner-item">
                                                    <button class="like-btn" data-post-id="<?= $post['id'] ?>">
                                                        <i class="fa-regular fa-heart mr-2"></i>
                                                        <span class="likes-count-<?= $post['id'] ?>"><?= getPostLikesCount($conn, $post['id']) ?></span> likes
                                                    </button>
                                                </li>
                                                <li class="list-inner-item">
                                                    <button class="comment-btn" data-post-id="<?= $post['id'] ?>">
                                                        <i class="fa-regular fa-comment-dots mr-2"></i>
                                                        <span class="comments-count-<?= $post['id'] ?>"><?= count(getPostComments($conn, $post['id'])) ?></span> comments
                                                    </button>
                                                </li>
                                                <li class="list-inner-item">
                                                    <button class="share-btn" data-post-id="<?= $post['id'] ?>">
                                                        <i class="fa-solid fa-share-from-square mr-2"></i>
                                                        <span class="shares-count-<?= $post['id'] ?>"><?= getPostSharesCount($conn, $post['id']) ?></span> shares
                                                    </button>
                                                </li>
                                            </ul>
                                        </div>

                                        <div class="comments-section mt-3" id="comments-<?= $post['id'] ?>" style="display: none;">
                                            <div class="comment-input mb-3">
                                                <input type="text" class="form-control" placeholder="Write a comment..." id="comment-text-<?= $post['id'] ?>">
                                                <button class="btn btn-primary mt-2 comment-submit" data-post-id="<?= $post['id'] ?>">Post</button>
                                            </div>
                                            <div class="comments-list">
                                                <?php
                                                $comments = getPostComments($conn, $post['id']);
                                                foreach ($comments as $comment):
                                                    if (isset($comment['error'])) {
                                                        echo '<p class="text-danger">' . $comment['error'] . '</p>';
                                                        continue;
                                                    }
                                                ?>
                                                    <div class="comment-item mb-3">
                                                        <img src="data:image/jpeg;base64,<?= blobToBase64($comment['profile_picture']) ?>" alt="user picture" class="rounded-circle mr-3" width="30" height="30">
                                                        <div class="comment-content">
                                                            <strong><?= htmlspecialchars($comment['first_name'] . ' ' . $comment['last_name']) ?></strong>
                                                            <p class="mb-0"><?= htmlspecialchars($comment['comment_text']) ?></p>
                                                            <small class="text-muted"><?= timeAgo($comment['created_at']) ?></small>
                                                        </div>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <div class="col-md-3">
                <div class="card mb-4">
                    <div class="card-body">
                        <div class="messenger">
                            <div class="messenger-search d-flex align-items-center mb-4">
                            </div>
                            <ul class="list-unstyled">
                                <?php foreach ($activeUsers as $user): ?>
                                <li class="d-flex align-items-center mb-3 p-2 bg-light rounded user-card" data-user-id="<?= $user['id'] ?>" data-user-name="<?= $user['first_name'] . ' ' . $user['last_name'] ?>" data-user-status="<?= $user['status'] ?>">
                                    <img src="data:image/jpeg;base64,<?= blobToBase64($user['profile_picture']) ?>" alt="user" class="rounded-circle mr-3" width="40" height="40">
                                    <div class="flex-grow-1">
                                        <h5 class="mb-0"><?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?></h5>
                                        <small class="<?= $user['status'] == 'Active' ? 'text-success' : 'text-muted' ?>"><?= htmlspecialchars($user['status']) ?></small>
                                    </div>
                                    <i class="fa-brands fa-facebook-messenger text-primary"></i>
                                </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div id="conversationModal" class="conversation-modal" style="max-height: 300px; margin-top: 300px; margin-right: 20px;">
        <div class="custom-modal-content">
            <div class="custom-modal-header" style="background-color: pink; color: #fff;">
                <h5 id="modalUserName">Chat with User</h5>
                <span class="close" onclick="closeModal()">&times;</span>
            </div>
            <div class="custom-modal-body">
                <div id="messages" class="messages-box">
                </div>
                <div class="message-input">
                    <input type="text" id="messageInput" placeholder="Type a message...">
                    <input type="hidden" id="senderId" value="<?= $_SESSION['user_id'] ?>">
                    <input type="hidden" id="recipientId" value="">
                    <button class="custom-button" onclick="sendMessage()"><i class="fas fa-paper-plane"></i></button>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Like Button
            document.querySelectorAll('.like-btn').forEach(button => {
                button.addEventListener('click', function() {
                    const postId = this.getAttribute('data-post-id');
                    const likeBtn = this;
                    const likesCountSpan = document.querySelector(`.likes-count-${postId}`);
                    
                    fetch('<?= $_SERVER['PHP_SELF'] ?>', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: `action=like&post_id=${postId}`
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            const likesCount = parseInt(likesCountSpan.textContent);
                            likesCountSpan.textContent = likesCount + 1;
                            likeBtn.innerHTML = `<i class="fa-solid fa-heart mr-2"></i>${likesCount + 1} likes`;
                        } else {
                            alert(data.error);
                        }
                    })
                    .catch(error => {
                        // console.error('Err:', error);
                        alert('An err occour liking the post.');
                    });
                });
            });

            // Unlike Button
            document.querySelectorAll('.like-btn').forEach(button => {
                button.addEventListener('dblclick', function() {
                    const postId = this.getAttribute('data-post-id');
                    const likeBtn = this;
                    const likesCountSpan = document.querySelector(`.likes-count-${postId}`);
                    
                    fetch('<?= $_SERVER['PHP_SELF'] ?>', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: `action=unlike&post_id=${postId}`
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            const likesCount = parseInt(likesCountSpan.textContent);
                            likesCountSpan.textContent = likesCount - 1;
                            likeBtn.innerHTML = `<i class="fa-regular fa-heart mr-2"></i>${likesCount - 1} likes`;
                        } else {
                            alert(data.error);
                        }
                    })
                    .catch(error => {
                        // console.error('Err:', error);
                        alert('Err occur unliking the post.');
                    });
                });
            });

            // Comment Button
            document.querySelectorAll('.comment-btn').forEach(button => {
                button.addEventListener('click', function() {
                    const postId = this.getAttribute('data-post-id');
                    const commentsSection = document.getElementById(`comments-${postId}`);
                    
                    if (commentsSection.style.display === 'none') {
                        commentsSection.style.display = 'block';
                    } else {
                        commentsSection.style.display = 'none';
                    }
                });
            });

            // Comment Submit
            document.querySelectorAll('.comment-submit').forEach(button => {
                button.addEventListener('click', function() {
                    const postId = this.getAttribute('data-post-id');
                    const commentText = document.getElementById(`comment-text-${postId}`).value;
                    
                    if (commentText.trim() === '') {
                        alert('Please enter a comment.');
                        return;
                    }
                    
                    fetch('<?= $_SERVER['PHP_SELF'] ?>', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: `action=add_comment&post_id=${postId}&comment_text=${encodeURIComponent(commentText)}`
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            document.getElementById(`comment-text-${postId}`).value = '';
                            const commentsCountSpan = document.querySelector(`.comments-count-${postId}`);
                            const commentsCount = parseInt(commentsCountSpan.textContent);
                            commentsCountSpan.textContent = commentsCount + 1;
                        
                            location.reload();
                        } else {
                            alert(data.error);
                        }
                    })
                    .catch(error => {
                        // console.error('Err:', error);
                        alert('An err occur posting comment.');
                    });
                });
            });

            // Share Button
            document.querySelectorAll('.share-btn').forEach(button => {
                button.addEventListener('click', function() {
                    const postId = this.getAttribute('data-post-id');
                    const sharesCountSpan = document.querySelector(`.shares-count-${postId}`);
                    
                    fetch('<?= $_SERVER['PHP_SELF'] ?>', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: `action=share&post_id=${postId}`
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            const sharesCount = parseInt(sharesCountSpan.textContent);
                            sharesCountSpan.textContent = sharesCount + 1;
                            alert('Post shared successfully!');
                        } else {
                            alert(data.error);
                        }
                    })
                    .catch(error => {
                        // console.error('Err:', error);
                        alert('Err occur sharing post.');
                    });
                });
            });

           
            });

            document.addEventListener('contextmenu', function(e) {
              e.preventDefault();
            });
            
    </script>

        

    <script src="../assets/node_modules/jquery/dist/jquery.slim.min.js"></script>
    <script src="../assets/node_modules/@popperjs/core/dist/umd/popper.min.js"></script>
    <script src="../assets/node_modules/bootstrap/dist/js/bootstrap.min.js"></script>

    <script>
 document.querySelectorAll('.user-card').forEach(userCard => {
    userCard.addEventListener('click', function () {
        const userId = this.getAttribute('data-user-id');
        const userName = this.getAttribute('data-user-name');
        const userStatus = this.getAttribute('data-user-status');
        document.getElementById('modalUserName').innerText = 'Chat with ' + userName;
        document.getElementById('conversationModal').style.display = 'block';
        document.getElementById('recipientId').value = userId;
        loadMessages(userId);
    });

  
    userCard.addEventListener('dblclick', function () {
        const userId = this.getAttribute('data-user-id');
        window.location.href = 'profile.php?user_id=' + userId;
    });
});

        function closeModal() {
            document.getElementById('conversationModal').style.display = 'none';
        }

        function loadMessages(recipientId) {
            const messagesBox = document.getElementById('messages');
            messagesBox.innerHTML = '';

            const xhr = new XMLHttpRequest();
            xhr.open('GET', '?action=load_messages&recipient_id=' + recipientId, true);
            xhr.onreadystatechange = function () {
                if (xhr.readyState === 4 && xhr.status === 200) {
                    const response = JSON.parse(xhr.responseText);
                    if (response.success) {
                        const messages = response.messages;
                        messages.forEach(message => {
                            const messageDiv = document.createElement('div');
                            messageDiv.classList.add('message');
                            messageDiv.innerHTML = '<strong>' + message.first_name + ' ' + message.last_name + ':</strong> ' + message.message;
                            messagesBox.appendChild(messageDiv);
                        });
                    } else {
                        messagesBox.innerHTML = '<p>Error loading messages: ' + response.error + '</p>';
                    }
                }
            };
            xhr.send();
        }

        function sendMessage() {
            const messageInput = document.getElementById('messageInput');
            const message = messageInput.value.trim();
            const senderId = document.getElementById('senderId').value;
            const recipientId = document.getElementById('recipientId').value;

            if (message === '') {
                alert('Please enter a message');
                return;
            }

            const xhr = new XMLHttpRequest();
            xhr.open('POST', '', true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            xhr.onreadystatechange = function () {
                if (xhr.readyState === 4 && xhr.status === 200) {
                    const response = JSON.parse(xhr.responseText);
                    if (response.success) {
                        const messagesBox = document.getElementById('messages');
                        const messageDiv = document.createElement('div');
                        messageDiv.classList.add('message');
                        messageDiv.innerHTML = '<strong>You:</strong> ' + message;
                        messagesBox.appendChild(messageDiv);

                        messageInput.value = '';
                    } else {
                        alert('Error sending message: ' + response.error);
                    }
                }
            };
            xhr.send('action=send_message&message=' + encodeURIComponent(message) + '&sender_id=' + senderId + '&recipient_id=' + recipientId);
        }
    

    </script>
</body>
</html>