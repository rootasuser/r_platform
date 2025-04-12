const styles = `
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
`;

const styleSheet = document.createElement("style");
styleSheet.type = "text/css";
styleSheet.innerText = styles;
document.head.appendChild(styleSheet);