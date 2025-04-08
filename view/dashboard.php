<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/Database.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$db = new Database();
$conn = $db->getConnection();

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

function getActiveUsers($conn) {
    try {
        $query = "SELECT id, first_name, last_name, profile_picture, status FROM users WHERE status IN ('Active', 'Offline')";
        $stmt = $conn->prepare($query);

        if ($stmt === false) {
            throw new Exception("Error preparing statement: " . $conn->error);
        }

        $stmt->execute();
        $result = $stmt->get_result();

        if ($result === false) {
            throw new Exception("Error executing query: " . $conn->error);
        }

        $users = [];
        while ($row = $result->fetch_assoc()) {
            $users[] = $row;
        }

        return $users;
    } catch (Exception $e) {
        return ['error' => 'Database error: ' . $e->getMessage()];
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
    <link rel="stylesheet" href="../assets/css/style.modal2.css">
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
                            <h3 class="mb-0">ADS</h3>
                        </div>
                        
                        <div class="pages mb-4">
                           <div class="card shadow-lg">
                            <div class="card-body">
                                ADS 1
                            </div>
                           </div>
                        </div>

                        <div class="group mb-4">
                        <div class="card shadow-lg">
                            <div class="card-body">
                                ADS 2
                            </div>
                           </div>
                        </div>

                        <div class="group mb-4">
                        <div class="card shadow-lg">
                            <div class="card-body">
                                ADS 3
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
                                <input type="text" class="form-control" placeholder="What's on your mind, <?= htmlspecialchars($user['first_name']) ?>">
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

                        <div class="fb-post1">
                            <div class="fb-post1-container">
                                <div class="fb-post1-header mb-3">
                                    <hr>
                                </div>
                                <div class="fb-p1-main">
                                    <div class="post-title d-flex flex-wrap mb-4">
                                        <img src="images/user2.jpg" alt="user picture" class="rounded-circle mr-3 mb-3" width="40" height="40">
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
                                            <li class="list-inner-item">
                                                <img src="images/love.png" alt="love" class="mr-2" width="20">
                                                <img src="images/like.png" alt="like" class="mr-2" width="20">
                                                <span>22k like</span>
                                            </li>
                                            <li class="list-inner-item">
                                                <i class="fa-regular fa-comment-dots mr-2"></i>
                                                <span>555 comments</span>
                                            </li>
                                            <li class="list-inner-item">
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
                <div class="card mb-4 d-none">
                    <div class="card-body">
                    </div>
                </div>

                <div class="card">
                    <div class="card-body overflow-y-scroll h-100" style="max-height: 700px;">
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

                <div id="conversationModal" class="conversation-modal">
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
            </div>
        </div>
    </div>

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