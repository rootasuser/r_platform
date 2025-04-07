<?php
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../model/User.php';

class Chat {
    private $conn;

    public function __construct() {
        $db = new Database();
        $this->conn = $db->getConnection();
    }

    // Function to send message (text, image, or video)
    public function sendMessage($senderId, $recipientId, $message, $messageType, $file = null) {
        try {
            if ($messageType === 'image' || $messageType === 'video') {
                // Handle file upload
                $uploadDir = __DIR__ . '/../uploads/';
                $fileName = time() . '_' . basename($file['name']);
                $filePath = $uploadDir . $fileName;

                // Move the uploaded file to the server directory
                if (move_uploaded_file($file['tmp_name'], $filePath)) {
                    // Insert message into the database
                    $query = "INSERT INTO messages (sender_id, recipient_id, message, message_type, file_path) VALUES (?, ?, ?, ?, ?)";
                    $stmt = $this->conn->prepare($query);
                    $stmt->bind_param('iisss', $senderId, $recipientId, $message, $messageType, $filePath);
                    $stmt->execute();
                } else {
                    return ['error' => 'File upload failed.'];
                }
            } else {
                // Insert text message into the database
                $query = "INSERT INTO messages (sender_id, recipient_id, message, message_type) VALUES (?, ?, ?, ?)";
                $stmt = $this->conn->prepare($query);
                $stmt->bind_param('iiss', $senderId, $recipientId, $message, $messageType);
                $stmt->execute();
            }

            return ['success' => 'Message sent.'];
        } catch (Exception $e) {
            return ['error' => 'Failed to send message: ' . $e->getMessage()];
        }
    }

    // Function to load messages between two users
    public function loadMessages($senderId, $recipientId) {
        try {
            $query = "SELECT * FROM messages WHERE (sender_id = ? AND recipient_id = ?) OR (sender_id = ? AND recipient_id = ?) ORDER BY created_at";
            $stmt = $this->conn->prepare($query);
            $stmt->bind_param('iiii', $senderId, $recipientId, $recipientId, $senderId);
            $stmt->execute();
            $result = $stmt->get_result();

            $messages = [];
            while ($row = $result->fetch_assoc()) {
                $messages[] = $row;
            }

            return $messages;
        } catch (Exception $e) {
            return ['error' => 'Failed to load messages: ' . $e->getMessage()];
        }
    }
}
?>
