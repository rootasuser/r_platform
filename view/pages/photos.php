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

function getAllUserPhotos($conn, $userId) {
    try {
        $query = "SELECT p.image 
                  FROM posts p
                  WHERE p.user_id = ? AND p.image IS NOT NULL
                  ORDER BY p.created_at DESC";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();

        $photos = [];
        while ($row = $result->fetch_assoc()) {
            $photos[] = $row['image'];
        }

        return $photos;
    } catch (Exception $e) {
        return ['error' => 'Database error: ' . $e->getMessage()];
    }
}

$db = new Database();
$conn = $db->getConnection();
$userId = $_SESSION['user_id'];
$photos = getAllUserPhotos($conn, $userId);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Photos</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
    <link rel="stylesheet" href="../../assets/css/style.photos.css">
    <style>
        .photo-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 15px;
            margin-top: 20px;
        }

        .photo-item {
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease;
            cursor: pointer;
        }

        .photo-item:hover {
            transform: translateY(-5px);
        }

        .photo-item img {
            width: 100%;
            height: 200px;
            object-fit: cover;
        }

        .modal-content {
            border-radius: 10px;
        }

        .modal-body img {
            width: 100%;
            height: auto;
        }
    </style>
</head>
<body>
    <div class="container mt-4">
        <div class="row">
            <div class="col-md-12">
                <h2 class="mb-4">My Photos</h2>
                <?php if (isset($photos['error'])): ?>
                    <p class="text-danger"><?= $photos['error'] ?></p>
                <?php else: ?>
                    <div class="photo-grid">
                        <?php foreach ($photos as $photo): ?>
                            <div class="photo-item" data-photo="<?= $photo ?>">
                                <img src="<?= $photo ?>" alt="Photo">
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Photo Modal -->
    <div class="modal fade" id="photoModal" tabindex="-1" aria-labelledby="photoModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="photoModalLabel">Photo</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <img src="" alt="Photo" id="modalPhoto" class="img-fluid">
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const photoItems = document.querySelectorAll('.photo-item');
            const modal = new bootstrap.Modal(document.getElementById('photoModal'));
            const modalPhoto = document.getElementById('modalPhoto');

            photoItems.forEach(item => {
                item.addEventListener('click', function() {
                    const photo = this.getAttribute('data-photo');
                    modalPhoto.src = photo;
                    modal.show();
                });
            });
        });
    </script>
</body>
</html>