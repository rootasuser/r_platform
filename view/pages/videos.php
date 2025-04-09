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

function getAllUserVideos($conn, $userId) {
    try {
        $query = "SELECT p.video 
                  FROM posts p
                  WHERE p.user_id = ? AND p.video IS NOT NULL
                  ORDER BY p.created_at DESC";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();

        $videos = [];
        while ($row = $result->fetch_assoc()) {
            $videos[] = $row['video'];
        }

        return $videos;
    } catch (Exception $e) {
        return ['error' => 'Database error: ' . $e->getMessage()];
    }
}

$db = new Database();
$conn = $db->getConnection();
$userId = $_SESSION['user_id'];
$videos = getAllUserVideos($conn, $userId);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Videos</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
    <link rel="stylesheet" href="../../assets/css/style.videos.css">
    <style>
        .video-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 15px;
            margin-top: 20px;
        }

        .video-item {
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease;
            cursor: pointer;
        }

        .video-item:hover {
            transform: translateY(-5px);
        }

        .video-item video {
            width: 100%;
            height: 200px;
            object-fit: cover;
        }

        .modal-content {
            border-radius: 10px;
        }

        .modal-body video {
            width: 100%;
            height: auto;
        }
    </style>
</head>
<body>
    <div class="container mt-4">
        <div class="row">
            <div class="col-md-12">
                <h2 class="mb-4">My Videos</h2>
                <?php if (isset($videos['error'])): ?>
                    <p class="text-danger"><?= $videos['error'] ?></p>
                <?php else: ?>
                    <div class="video-grid">
                        <?php foreach ($videos as $video): ?>
                            <div class="video-item" data-video="<?= $video ?>">
                                <video poster="<?= $video ?>" controls>
                                    <source src="<?= $video ?>" type="video/mp4">
                                    Your browser does not support the video tag.
                                </video>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Video Modal -->
    <div class="modal fade" id="videoModal" tabindex="-1" aria-labelledby="videoModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="videoModalLabel">Video</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <video id="modalVideo" controls class="img-fluid">
                        Your browser does not support the video tag.
                    </video>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const videoItems = document.querySelectorAll('.video-item');
            const modal = new bootstrap.Modal(document.getElementById('videoModal'));
            const modalVideo = document.getElementById('modalVideo');

            videoItems.forEach(item => {
                item.addEventListener('click', function() {
                    const video = this.getAttribute('data-video');
                    modalVideo.src = video;
                    modal.show();
                });
            });
        });
    </script>
</body>
</html>