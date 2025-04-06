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
    echo "<p>User not found or an error occurred.</p>";
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="shortcut icon" href="images/favicon.ico" type="image/x-icon">
    <title>facebook.com</title>
    <!-- Bootstrap 4 CSS -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css" integrity="sha512-KfkfwYDsLkIlwQp6LFnl8zNdLGxu9YAA1QvwINks4PhcElQSvqcyVLLD9aMhXd13uQjoXtEKNosOWaZqXgel0g==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <!-- Custom CSS -->
    <style>
        :root {
            --background: #d1deec;
            --foreground: #f1f3f5;
            --white: #fff;
            --black: #000;
            --gray: #6e6e6e;
            --shadow: #76767663;
            --border: #cfcfcf;
        }

        .dark-color {
            --background: #222230;
            --foreground: #42435c;
            --white: #2b2c44;
            --black: #eeecff;
            --gray: #d5dfd5;
            --shadow: #00000063;
            --border: #3f4172;
        }

        body {
            background: var(--background);
        }

        .header-container {
            background: var(--white);
            border-radius: 20px;
            box-shadow: 0 2px 5px 0 var(--shadow);
        }

        .searchBox input {
            background: var(--foreground);
            box-shadow: 0 2px 5px 0 var(--shadow);
        }

        .iconBox1 i,
        .iconBox2 i {
            color: #769bcb;
        }

        .iconBox1 i:hover,
        .iconBox2 i:hover {
            background: #1877f2;
            color: #d1deec;
        }

        .darkTheme.button-Active span {
            margin-left: 16px;
        }

        .single-stories {
            position: relative;
            padding-top: 25px;
        }

        .single-stories label {
            width: 45px;
            height: 45px;
            background: #daeaff;
            position: absolute;
            left: 50%;
            transform: translateX(-50%);
            top: 0;
            border-radius: 50px;
            overflow: hidden;
            border: 3px solid #1877f2;
        }

        .single-stories > div {
            width: 100%;
            overflow: hidden;
            height: 100%;
            border-radius: 18px;
            text-align: center;
            box-shadow: 1px 6px 6px 0 var(--shadow);
        }

        .single-stories > div img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .single-stories > div b {
            position: absolute;
            bottom: 5px;
            left: 50%;
            transform: translateX(-50%);
            font-size: 12px;
            color: white;
            font-weight: 400;
            text-shadow: 0 1px 8px black;
        }

        .single-stories > div i {
            position: absolute;
            bottom: 30px;
            left: 50%;
            transform: translateX(-50%);
            font-size: 25px;
            color: white;
        }

        .post-text img {
            width: 40px;
            border-radius: 50px;
            position: absolute;
            left: 10px;
            top: 30px;
        }

        .post-text input {
            padding: 20px 20px 20px 60px;
            width: 100%;
            background: var(--foreground);
            border: none;
            height: 100px;
            border-radius: 10px;
            box-shadow: 0 2px 5px 0 var(--shadow);
        }

        .post-icon a {
            margin-right: 10px;
            padding: 5px;
            border-radius: 10px;
            font-size: 14px;
            color: #262626;
            font-weight: 500;
        }

        .post-icon a i {
            padding: 5px;
            border-radius: 5px;
            color: #fff;
        }

        .fb-post1-header ul li {
            text-transform: uppercase;
            padding: 5px 10px;
            font-weight: 600;
            color: var(--gray);
            cursor: pointer;
        }

        .fb-post1-header .active {
            color: var(--black);
            border-bottom: 3px solid #1877f2;
        }

        .post-title img {
            width: 50px;
            border-radius: 50px;
            margin-right: 20px;
            margin-bottom: 20px;
        }

        .post-title ul li span {
            color: var(--gray);
            font-weight: 400;
            font-size: 14px;
        }

        .post-images1 img:nth-child(1) {
            width: 100%;
            margin-bottom: 10px;
            height: 200px;
            object-fit: cover;
            border-radius: 15px;
        }

        .post-images1 img:nth-child(2),
        .post-images1 img:nth-child(3) {
            width: 47%;
            height: 120px;
            object-fit: cover;
            border-radius: 15px;
        }

        .post-images2 img {
            width: 100%;
            height: 335px;
            border-radius: 15px;
            object-fit: cover;
        }

        .like-comment ul li {
            margin-right: 20px;
        }

        .like-comment ul li img {
            width: 20px;
            margin-right: -5px;
        }

        .like-comment ul li i {
            color: #9d9d9d;
        }

        .like-comment ul li span {
            color: var(--gray);
            margin-left: 10px;
            font-size: 14px;
        }

        .event img {
            width: 100%;
            border-radius: 10px;
            margin-bottom: 18px;
        }

        .event-date h3 {
            color: #1877f2;
            text-align: center;
            line-height: 20px;
            margin-right: 10px;
            background: var(--white);
            padding: 6px;
            box-shadow: 0 2px 5px 0 var(--shadow);
            border-radius: 6px;
        }

        .event-date h3 b {
            color: var(--black);
            display: block;
            text-transform: uppercase;
        }

        .event-date h4 {
            color: var(--black);
            font-size: 14px;
        }

        .event-date h4 span {
            display: block;
            font-size: 12px;
            font-weight: 600;
            color: var(--gray);
        }

        .event button {
            padding: 6px;
            background: #1877f2;
            color: white;
            border-radius: 6px;
            border: none;
            margin-bottom: 18px;
        }

        .event button:hover {
            background: #115cbd;
            cursor: pointer;
        }

        .event button i {
            margin-right: 6px;
        }

        .friend ul li img {
            width: 50px;
            border-radius: 50px;
            margin-right: 10px;
        }

        .friend ul li b {
            color: var(--black);
            cursor: pointer;
            text-transform: capitalize;
        }

        .friend ul li p {
            font-size: 12px;
            display: block;
            margin-bottom: 10px;
            color: var(--gray);
        }

        .friend ul li button {
            background: #1877f2;
            border: none;
            padding: 3px 10px;
            color: white;
            border-radius: 5px;
            margin-right: 5px;
            font-size: 12px;
            cursor: pointer;
        }

        .friend-remove {
            background: var(--background) !important;
            color: var(--black) !important;
        }

        .create-page ul li .fa-circle-plus {
            color: white;
            padding: 10px;
            background: #1877f2;
            border-radius: 10px;
            font-size: 20px;
            margin-right: 8px;
            cursor: pointer;
        }

        .create-page ul li h4 {
            font-size: 14px;
            color: var(--black);
            margin-right: 95px;
        }

        .create-page ul li i {
            color: var(--gray);
        }

        .create-page ul li img {
            width: 100%;
            border-radius: 10px;
        }

        .create-page ul li b {
            font-size: 12px;
        }

        .create-page ul li button {
            font-size: 12px;
            border: none;
            padding: 3px 10px;
            background: #1877f2;
            color: white;
            border-radius: 50px;
            cursor: pointer;
        }

        .create-page ul li:nth-child(3) {
            justify-content: space-between;
            margin-bottom: 5px;
        }

        .create-page ul li b span {
            display: block;
            font-weight: 500;
            color: var(--gray);
        }

        .messenger ul li {
            display: flex;
            width: 100%;
            margin-bottom: 10px;
            overflow: hidden;
            align-items: center;
            background: var(--foreground);
            padding: 10px 5px;
            border-radius: 10px;
            box-shadow: 0 2px 5px 0 var(--shadow);
            position: relative;
            transition: .4s;
        }

        .messenger ul li img {
            width: 42px;
            border-radius: 50px;
            margin-right: 10px;
        }

        .messenger ul li:hover {
            box-shadow: 0 4px 6px 0 var(--shadow);
        }

        .messenger ul li b {
            color: var(--black);
            font-size: 14px;
        }

        .messenger ul li b span {
            display: block;
            color: var(--gray);
            font-size: 10px;
            position: relative;
            margin-left: 15px;
        }

        .messenger ul li span::before {
            content: '';
            display: block;
            width: 7px;
            height: 7px;
            background: #12da01;
            position: absolute;
            border-radius: 50px;
            top: 4px;
            left: -12px;
        }

        .messenger ul li:nth-child(2) span::before {
            background: #ff9600;
        }

        .messenger ul li i {
            color: #1877f2;
            position: absolute;
            right: 12px;
            top: 35%;
            background: white;
            padding: 5px;
            border-radius: 50px;
            box-shadow: 0 2px 5px #95959561;
        }

        .messenger-search .fa-magnifying-glass {
            position: absolute;
            right: 6px;
            font-size: 12px;
            color: var(--black);
            cursor: pointer;
        }
    </style>
</head>
<body>
    <!-- header section start -->
    <header class="bg-white p-3 shadow-sm">
        <div class="container">
            <div class="d-flex align-items-center justify-content-between">
                <div class="logoBox">
                    <img src="images/facebook-logo.png" alt="logo">
                </div>
                <div class="searchBox d-flex align-items-center">
                    <input type="search" class="form-control rounded-pill" placeholder="Search Facebook">
                    <i class="fas fa-search ml-2"></i>
                </div>
                <div class="iconBox1 d-flex">
                    <i class="fa-solid fa-house mx-3"></i>
                    <i class="fa-solid fa-user-group mx-3"></i>
                    <i class="fa-solid fa-video mx-3"></i>
                    <i class="fa-solid fa-gamepad mx-3"></i>
                </div>
                <div class="iconBox2 d-flex align-items-center">
                    <i class="fa-solid fa-circle-plus mx-3"></i>
                    <i class="fa-brands fa-facebook-messenger mx-3"></i>
                    <i class="fa-solid fa-bell mx-3"></i>
                    <div class="dropdown">
                        <div class="dropdown-toggle d-flex align-items-center" data-toggle="dropdown">
                            <img src="images/user.jpg" alt="user" class="rounded-circle mr-2" width="30">
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

                        <div class="games mb-4">
                            <h4 class="mini-headign text-uppercase mb-3">Games</h4>
                            <div class="d-flex align-items-center mb-3">
                                <img src="images/game.png" alt="game01" class="mr-2" width="30">
                                <span>Facebook games</span>
                            </div>
                            <div class="d-flex align-items-center mb-3">
                                <img src="images/game2.png" alt="game02" class="mr-2" width="30">
                                <span>Free Play Games</span>
                            </div>
                            <button class="btn btn-outline-primary btn-sm btn-block">See more <i class="fa-solid fa-angle-down ml-2"></i></button>
                        </div>

                        <div class="explore">
                            <h4 class="mini-headign text-uppercase mb-3">Explore</h4>
                            <div class="d-flex align-items-center mb-3">
                                <i class="fa-solid fa-user-group mr-2"></i>
                                <span>Group</span>
                            </div>
                            <div class="d-flex align-items-center mb-3">
                                <i class="fa-solid fa-star mr-2"></i>
                                <span>Favorites</span>
                            </div>
                            <div class="d-flex align-items-center mb-3">
                                <i class="fa-solid fa-bookmark mr-2"></i>
                                <span>Saves</span>
                            </div>
                            <div class="d-flex align-items-center mb-3">
                                <i class="fa-solid fa-clock mr-2"></i>
                                <span>Events</span>
                            </div>
                            <div class="d-flex align-items-center mb-3">
                                <i class="fa-solid fa-flag mr-2"></i>
                                <span>Pages</span>
                            </div>
                            <div class="d-flex align-items-center mb-3">
                                <label class="darkTheme mr-2"><span></span></label>
                                <span>Apply Dark Theme</span>
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
                                <img src="images/user.jpg" alt="user" class="rounded-circle position-absolute left-0 top-0 mt-3 ml-3" width="40">
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
                                    <span class="ml-2">Feeling / Acrivity</span>
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
                        <div class="event mb-4">
                            <h3 class="heading mb-3 d-flex justify-content-between">
                                Upcoming Events
                                <small class="text-muted"><a href="#" class="text-decoration-none">see all</a></small>
                            </h3>
                            <img src="images/eve.jpg" alt="event-img" class="mb-3 rounded w-100">
                            <div class="event-date d-flex mb-3">
                                <h3 class="text-center mr-3">
                                    21 <br>
                                    <small class="font-weight-bold">july</small>
                                </h3>
                                <div>
                                    <h4 class="mb-0">United state of America</h4>
                                    <small class="text-muted">New York City</small>
                                </div>
                            </div>
                            <button class="btn btn-primary btn-block">
                                <i class="fa-regular fa-star mr-2"></i> Interested
                            </button>
                        </div>
                        <hr>
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

                <div class="card mb-4">
                    <div class="card-body">
                        <div class="create-page mb-4">
                            <ul class="list-unstyled">
                                <li class="d-flex align-items-center mb-3">
                                    <i class="fa-solid fa-circle-plus text-primary mr-3" style="font-size: 20px;"></i>
                                    <div class="flex-grow-1">
                                        <h4 class="mb-0">Create Page & Groups</h4>
                                    </div>
                                    <i class="fa-solid fa-magnifying-glass text-muted"></i>
                                </li>
                                <li class="mb-3">
                                    <img src="images/group.jpg" alt="groups" class="rounded w-100">
                                </li>
                                <li class="d-flex justify-content-between">
                                    <div>
                                        <h5 class="mb-0">simple group or page name</h5>
                                        <small class="text-muted">200k Members</small>
                                    </div>
                                    <button class="btn btn-primary">Join Group</button>
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
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>

    <script>
        //   <?php
        //     $page = filter_input(INPUT_GET, 'page', FILTER_SANITIZE_SPECIAL_CHARS) ?: 'analytics';
        //     $allowedPages = ['analytics', 'research_title', 'documents', 'search_and_filter', 'reports', 'logs', 'users', 'account'];
        //     if (!in_array($page, $allowedPages, true)) { $page = '404'; }
        //     $viewFile = __DIR__ . '/templates/' . $page . '.php';
        //     if (is_readable($viewFile)) { include $viewFile; } else { http_response_code(404); echo '<h2>404 - Page Not Found</h2>'; }
        // ?>

        var darkButton = document.querySelector(".darkTheme");
        darkButton.onclick = function() {
            darkButton.classList.toggle("button-Active");
            document.body.classList.toggle("dark-color");
        };
    </script>
</body>
</html>