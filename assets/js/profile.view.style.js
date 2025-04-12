const styles = `
   .profile-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        .profile-header {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
        }
        .profile-avatar {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            object-fit: cover;
        }
        .profile-info {
            margin-left: 20px;
        }
        .profile-status {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
            margin-top: 10px;
        }
        .status-active {
            background-color: #28a745;
            color: white;
        }
        .status-offline {
            background-color: #6c757d;
            color: white;
        }
        .post-card {
            border: 1px solid #dee2e6;
            border-radius: 5px;
            margin-bottom: 20px;
            padding: 15px;
        }
        .post-header {
            display: flex;
            align-items: center;
            margin-bottom: 10px;
        }
        .post-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
            margin-right: 10px;
        }
        .post-content {
            margin-bottom: 10px;
        }
        .post-image {
            max-width: 100%;
            border-radius: 5px;
            margin-bottom: 10px;
        }
        .post-video {
            width: 100%;
            border-radius: 5px;
            margin-bottom: 10px;
        }
        .post-footer {
            display: flex;
            justify-content: space-between;
            color: #6c757d;
            font-size: 12px;
            margin-bottom: 10px;
        }
        .post-actions {
            display: flex;
            gap: 15px;
            margin-bottom: 10px;
        }
        .post-action-btn {
            background: none;
            border: none;
            color: #6c757d;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        .post-action-btn:hover {
            color: #007bff;
        }
        .navbar-custom {
            background-color: #f8f9fa;
            padding: 10px 20px;
        }  
`;

const styleSheet = document.createElement("style");
styleSheet.type = "text/css";
styleSheet.innerText = styles;
document.head.appendChild(styleSheet);