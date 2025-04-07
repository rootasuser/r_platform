<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header("Location: ../public/login.php");
    exit();
}

require_once __DIR__ . '/../model/User.php';
$userModel = new User();

$user = $userModel->getUserById($_SESSION['user_id']);

if (!$user || isset($user['error'])) {
    echo "<p>User not found.</p>";
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
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="shortcut icon" href="images/favicon.ico" type="image/x-icon">
    <title>R Connect</title>
    <!-- Bootstrap 4 CSS -->
    <link rel="stylesheet" href="../assets/node_modules/bootstrap/dist/css/bootstrap.min.css" />
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../assets/css/style.dashboard.css">
</head>
<body>
    <?php include('header.php'); ?>
    <!-- home section start -->
    <div class="container mt-4">
        <div class="row">
            <!-- home left start here -->
            <div class="col-md-3">
                <div class="card mb-4">
                    <div class="card-body">
                        <div class="profile d-flex align-items-center mb-4">
                            <img src="images/user.jpg" alt="user" class="rounded-circle mr-2" width="40">
                            <h3 class="mb-0">Zahidul hossain</h3>
                        </div>
                        
                        <div class="pages mb-4">
                            <h4 class="mini-headign text-uppercase mb-3">Pages</h4>
                            <div class="d-flex align-items-center mb-3">
                                <img src="images/messenger.png" alt="messenger" class="mr-2" width="30">
                                <span>messenger</span>
                            </div>
                            <div class="d-flex align-items-center mb-3">
                                <img src="images/instagram.png" alt="instagram" class="mr-2" width="30">
                                <span>instagram</span>
                            </div>
                            <button class="btn btn-outline-primary btn-sm btn-block">See more <i class="fa-solid fa-angle-down ml-2"></i></button>
                        </div>

                        <div class="group mb-4">
                            <h4 class="mini-headign text-uppercase mb-3">Group</h4>
                            <div class="d-flex align-items-center mb-3">
                                <img src="images/gg.png" alt="group01" class="mr-2" width="30">
                                <span>Graphic design</span>
                            </div>
                            <div class="d-flex align-items-center mb-3">
                                <img src="images/gg2.png" alt="group02" class="mr-2" width="30">
                                <span>website design</span>
                            </div>
                            <div class="d-flex align-items-center mb-3">
                                <img src="images/gg3.png" alt="group03" class="mr-2" width="30">
                                <span>ZED.zahidul</span>
                            </div>
                            <button class="btn btn-outline-primary btn-sm btn-block">See more <i class="fa-solid fa-angle-down ml-2"></i></button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- home center start here -->
            <div class="col-md-6">
                <div class="card mb-4">
                    <div class="card-body">
                        <div class="stories mb-4">
                            <h3 class="mini-headign text-uppercase mb-3">Stories</h3>
                            <div class="row stories-wrapper">
                                <div class="col-3 single-stories mb-3">
                                    <label class="d-block text-center">
                                        <img src="images/user.jpg" alt="user" class="rounded-circle mb-2" width="45">
                                    </label>
                                    <div class="position-relative">
                                        <img src="images/user.jpg" alt="user" class="rounded mb-2" width="100%">
                                        <i class="fa-solid fa-circle-plus position-absolute bottom-0 left-50 translate-middle"></i>
                                        <b class="position-absolute bottom-0 left-50 translate-middle w-100 text-center">Create Stories</b>
                                    </div>
                                </div>
                                <div class="col-3 single-stories mb-3">
                                    <label class="d-block text-center">
                                        <img src="images/us.png" alt="sp" class="rounded-circle mb-2" width="45">
                                    </label>
                                    <div>
                                        <img src="images/ss.jpg" alt="ss" class="rounded mb-2" width="100%">
                                        <b class="text-center">Your Name</b>
                                    </div>
                                </div>
                                <div class="col-3 single-stories mb-3">
                                    <label class="d-block text-center">
                                        <img src="images/us2.png" alt="sp2" class="rounded-circle mb-2" width="45">
                                    </label>
                                    <div>
                                        <img src="images/ss2.jpg" alt="ss2" class="rounded mb-2" width="100%">
                                        <b class="text-center">Your Name</b>
                                    </div>
                                </div>
                                <div class="col-3 single-stories mb-3">
                                    <label class="d-block text-center">
                                        <img src="images/us3.png" alt="sp3" class="rounded-circle mb-2" width="45">
                                    </label>
                                    <div>
                                        <img src="images/ss3.jpg" alt="ss3" class="rounded mb-2" width="100%">
                                        <b class="text-center">Your Name</b>
                                    </div>
                                </div>
                                <div class="col-3 single-stories mb-3">
                                    <label class="d-block text-center">
                                        <img src="images/us4.png" alt="sp4" class="rounded-circle mb-2" width="45">
                                    </label>
                                    <div>
                                        <img src="images/ss4.jpg" alt="ss4" class="rounded mb-2" width="100%">
                                        <b class="text-center">Your Name</b>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="createPost mb-4">
                            <h3 class="mini-headign text-uppercase mb-3">Create Post</h3>
                            <div class="post-text position-relative mb-4">
                                <img src="data:image/jpeg;base64,<?php echo blobToBase64($user['profile_picture']); ?>" alt="user" class="rounded-circle position-absolute left-0 top-0 mt-3 ml-3" width="40">
                                <input type="text" class="form-control" placeholder="What's on your mind, <?= htmlspecialchars($user['first_name']) ?>">
                            </div>

                            <div class="post-icon d-flex">
                                <a href="#" class="bg-light p-2 mr-2" style="background: #ffebed;">
                                    <i class="fa-solid fa-camera" style="background: #ff4154; padding: 5px; border-radius: 5px; color: white;"></i>
                                    <span class="ml-2">Gallery</span>
                                </a>
                                <a href="#" class="bg-light p-2 mr-2" style="background: #ccdcff;">
                                    <i class="fa-solid fa-video" style="background: #0053ff; padding: 5px; border-radius: 5px; color: white;"></i>
                                    <span class="ml-2">Video</span>
                                </a>
                                <a href="#" class="bg-light p-2 mr-2" style="background: #d7ffef;">
                                    <i class="fa-solid fa-location-dot" style="background: #00d181; padding: 5px; border-radius: 5px; color: white;"></i>
                                    <span class="ml-2">Location</span>
                                </a>
                                <a href="#" class="bg-light p-2 mr-2" style="background: #cff3ff;">
                                    <i class="fa-solid fa-gift" style="background: #04c3ff; padding: 5px; border-radius: 5px; color: white;"></i>
                                    <span class="ml-2">Gift</span>
                                </a>
                                <a href="#" class="bg-light p-2" style="background: #fff4d1;">
                                    <i class="fa-solid fa-face-grin-beam" style="background: #ffca28; padding: 5px; border-radius: 5px; color: white;"></i>
                                    <span class="ml-2">Feeling / Activity</span>
                                </a>
                            </div>
                        </div>

                        <div class="fb-post1">
                            <div class="fb-post1-container">
                                <div class="fb-post1-header mb-3">
                                    <ul class="nav nav-tabs">
                                        <li class="nav-item">
                                            <a class="nav-link active" href="#">Popular</a>
                                        </li>
                                        <li class="nav-item">
                                            <a class="nav-link" href="#">Recent</a>
                                        </li>
                                        <li class="nav-item">
                                            <a class="nav-link" href="#">Most View</a>
                                        </li>
                                    </ul>
                                </div>
                                <div class="fb-p1-main">
                                    <div class="post-title d-flex flex-wrap mb-4">
                                        <img src="images/user2.jpg" alt="user picture" class="rounded-circle mr-3 mb-3" width="50">
                                        <div class="flex-grow-1">
                                            <ul class="list-unstyled">
                                                <li class="mb-1">
                                                    <h3 class="mb-0">Arham Kabir <span class="text-muted">. 2 hours ago</span></h3>
                                                </li>
                                                <li class="mb-2">
                                                    <span class="text-muted">02 march at 12:55 PM</span>
                                                </li>
                                            </ul>
                                            <p class="mb-0">Hello Everyone Thanks for Watching Please SUBSCRIBE My Channel - Like Comments and Share
                                                <a href="https://www.youtube.com/channel/UCHhGX-DD7A8jq7J_NPGN6gA">https://www.youtube.com/channel/UCHhGX-DD7A8jq7J_NPGN6gA</a>
                                            </p>
                                        </div>
                                    </div>

                                    <div class="post-images row mb-4">
                                        <div class="col-md-6 post-images1 mb-3">
                                            <img src="images/pp.jpg" alt="post images 01" class="rounded mb-3 w-100">
                                            <img src="images/pp2.jpg" alt="post images 02" class="rounded float-left mr-2 mb-3" width="47%">
                                            <img src="images/pp3.jpg" alt="post images 03" class="rounded float-left mb-3" width="47%">
                                        </div>
                                        <div class="col-md-6 post-images2">
                                            <img src="images/pp4.jpg" alt="post images 04" class="rounded w-100">
                                        </div>
                                    </div>

                                    <div class="like-comment">
                                        <ul class="list-inline mb-0">
                                            <li class="list-inline-item">
                                                <img src="images/love.png" alt="love" class="mr-2" width="20">
                                                <img src="images/like.png" alt="like" class="mr-2" width="20">
                                                <span>22k like</span>
                                            </li>
                                            <li class="list-inline-item">
                                                <i class="fa-regular fa-comment-dots mr-2"></i>
                                                <span>555 comments</span>
                                            </li>
                                            <li class="list-inline-item">
                                                <i class="fa-solid fa-share-from-square mr-2"></i>
                                                <span>254 share</span>
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- home right start here -->
            <div class="col-md-3">
                <div class="card mb-4">
                    <div class="card-body">
                      
                        <div class="friend mb-4">
                            <h3 class="heading mb-3 d-flex justify-content-between">
                                Friend Requests
                                <small class="text-muted"><a href="#" class="text-decoration-none">see all</a></small>
                            </h3>
                            <ul class="list-unstyled">
                                <li class="d-flex mb-3">
                                    <img src="images/user4.jpg" alt="user" class="rounded-circle mr-3" width="50">
                                    <div class="flex-grow-1">
                                        <h4 class="mb-0">armanul islam</h4>
                                        <p class="mb-2 text-muted">Lorem ipsum dolor sit amet.</p>
                                        <div class="d-flex">
                                            <button class="btn btn-primary btn-sm mr-2">Confirm</button>
                                            <button class="btn btn-outline-secondary btn-sm friend-remove">Remove</button>
                                        </div>
                                    </div>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-body">
                        <div class="messenger">
                            <div class="messenger-search d-flex align-items-center mb-4">
                                <i class="fa-solid fa-user-group text-primary mr-3" style="font-size: 20px;"></i>
                                <h4 class="mb-0">Messenger</h4>
                                <input type="search" class="form-control flex-grow-1 ml-3" placeholder="Search">
                                <i class="fa-solid fa-magnifying-glass text-muted ml-2"></i>
                            </div>
                            <ul class="list-unstyled">
                                <li class="d-flex align-items-center mb-3 p-2 bg-light rounded">
                                    <img src="images/us2.png" alt="user" class="rounded-circle mr-3" width="42">
                                    <div class="flex-grow-1">
                                        <h5 class="mb-0">Zunayed Hossain</h5>
                                        <small class="text-success">Online</small>
                                    </div>
                                    <i class="fa-brands fa-facebook-messenger text-primary"></i>
                                </li>
                                <li class="d-flex align-items-center mb-3 p-2 bg-light rounded">
                                    <img src="images/us3.png" alt="user" class="rounded-circle mr-3" width="42">
                                    <div class="flex-grow-1">
                                        <h5 class="mb-0">Armanul Islam</h5>
                                        <small class="text-muted">Offline</small>
                                    </div>
                                    <i class="fa-brands fa-facebook-messenger text-primary"></i>
                                </li>
                                <li class="d-flex align-items-center p-2 bg-light rounded">
                                    <img src="images/us4.png" alt="user" class="rounded-circle mr-3" width="42">
                                    <div class="flex-grow-1">
                                        <h5 class="mb-0">Mohammad Amir</h5>
                                        <small class="text-success">Online</small>
                                    </div>
                                    <i class="fa-brands fa-facebook-messenger text-primary"></i>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap 4 JS -->
    <script src="../assets/node_modules/jquery/dist/jquery.slim.min.js"></script>
    <script src="../assets/node_modules/@popperjs/core/dist/umd/popper.min.js"></script>
    <script src="../assets/node_modules/bootstrap/dist/js/bootstrap.min.js"></script>

    <script>
        // document.addEventListener('DOMContentLoaded', function() {
        //     var darkButton = document.querySelector(".darkTheme");
        //     if (darkButton) {
        //         darkButton.onclick = function() {
        //             darkButton.classList.toggle("button-Active");
        //             document.body.classList.toggle("dark-color");
        //         };
        //     } else {
        //         console.error("Element with class 'darkTheme' not found.");
        //     }
        // });

        
    </script>
</body>
</html>