const styles = `
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
`;

const styleSheet = document.createElement("style");
styleSheet.type = "text/css";
styleSheet.innerText = styles;
document.head.appendChild(styleSheet);