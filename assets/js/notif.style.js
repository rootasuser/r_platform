const styles = `
  .activity-item {
            border-bottom: 1px solid #dee2e6;
            padding: 15px 0;
        }

        .activity-item:last-child {
            border-bottom: none;
        }

        .activity-header {
            display: flex;
            align-items: center;
            margin-bottom: 8px;
        }

        .activity-header img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            margin-right: 10px;
        }

        .activity-content {
            margin-left: 50px;
        }

        .activity-time {
            font-size: 0.8rem;
            color: #6c757d;
        }
`;

const styleSheet = document.createElement("style");
styleSheet.type = "text/css";
styleSheet.innerText = styles;
document.head.appendChild(styleSheet);