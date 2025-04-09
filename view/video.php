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

$userModel = new User();
$user = $userModel->getUserById($_SESSION['user_id']);

if (!$user) {
    $_SESSION['error'] = "User not found.";
    header("Location: ../public/login.php");
    exit();
}

function getAllVideos($conn) {
    try {
        $query = "SELECT p.*, u.first_name, u.last_name 
                  FROM posts p
                  JOIN users u ON p.user_id = u.id
                  WHERE p.video IS NOT NULL AND p.video != ''
                  ORDER BY p.created_at DESC";
        $stmt = $conn->prepare($query);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_all(MYSQLI_ASSOC);
    } catch (Exception $e) {
        return ['error' => 'Database error: ' . $e->getMessage()];
    }
}

$db = new Database();
$conn = $db->getConnection();
$videos = getAllVideos($conn);

function timeAgo($datetime) {

    if (is_numeric($datetime) && strlen($datetime) > 10) {
        $datetime = $datetime / 1000;
    } else {
        $datetime = strtotime($datetime);
    }
    
    $diff = time() - $datetime;
    
    if ($diff < 0) {  
        $absDiff = abs($diff);
        if ($absDiff < 60) {
            return '1 minute from now';
        } else {
            $minutes = round($absDiff / 60);
            return $minutes . ' minutes from now';
        }
    }
    
    if ($diff < 60) {
        return '1 minute ago';
    } elseif ($diff < 3600) {
        return round($diff / 60) . ' minutes ago';
    } elseif ($diff < 86400) {
        return round($diff / 3600) . ' hours ago';
    } else {
        return round($diff / 86400) . ' days ago';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All Videos</title>
    <!-- Bootstrap 5 CSS -->
    <link rel="stylesheet" href="../assets/node_modules/bootstrap/dist/css/bootstrap.min.css" />
    <link rel="stylesheet" href="../assets/css/style.dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
    <style>
        .carousel-item {
            height: 400px;
        }
        .carousel-caption {
            background-color: rgba(0, 0, 0, 0.5);
            border-radius: 10px;
            padding: 15px;
        }
        .navbar-custom {
            background-color: #f8f9fa;
            padding: 10px 20px;
        }   
    </style>
</head>
<body>

<nav class="navbar navbar-custom">
    <span class="navbar-brand mb-0 h1">R</span>
    <button class="btn btn-outline-secondary ml-auto" type="button" onclick="window.history.back();">
      <i class="fa fa-arrow-left"></i> Back
    </button>
  </nav>


    <div class="container mt-4">
        <div class="row">
            <div class="col-md-12">
                <h2 class="mb-4">All Videos</h2>
                <?php if (isset($videos['error'])): ?>
                    <p class="text-danger"><?= $videos['error'] ?></p>
                <?php else: ?>
                    <?php if (empty($videos)): ?>
                        <p>No videos yet.</p>
                    <?php else: ?>
                        <div id="videoCarousel" class="carousel slide" data-bs-ride="carousel">
                            <div class="carousel-indicators">
                                <?php foreach ($videos as $index => $video): ?>
                                    <button type="button" data-bs-target="#videoCarousel" data-bs-slide-to="<?= $index ?>" class="<?= $index == 0 ? 'active' : '' ?>"></button>
                                <?php endforeach; ?>
                            </div>
                            <div class="carousel-inner">
                                <?php foreach ($videos as $index => $video): ?>
                                    <div class="carousel-item <?= $index == 0 ? 'active' : '' ?>">
                                        <video class="d-block w-100" controls>
                                            <source src="<?= $video['video'] ?>" type="video/mp4">
                                            Your browser does not support the video tag.
                                        </video>
                                        <div class="carousel-caption d-none d-md-block">
                                            <h5><?= htmlspecialchars($video['first_name'] . ' ' . $video['last_name']) ?></h5>
                                            <p><?= htmlspecialchars($video['text']) ?></p>
                                            <p class="text-muted"><?= timeAgo($video['created_at']) ?></p>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <button class="carousel-control-prev" type="button" data-bs-target="#videoCarousel" data-bs-slide="prev">
                                <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                                <span class="visually-hidden">Previous</span>
                            </button>
                            <button class="carousel-control-next" type="button" data-bs-target="#videoCarousel" data-bs-slide="next">
                                <span class="carousel-control-next-icon" aria-hidden="true"></span>
                                <span class="visually-hidden">Next</span>
                            </button>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="../assets/node_modules/jquery/dist/jquery.slim.min.js"></script>
    <script src="../assets/node_modules/@popperjs/core/dist/umd/popper.min.js"></script>
    <script src="../assets/node_modules/bootstrap/dist/js/bootstrap.min.js"></script>

</body>
</html>