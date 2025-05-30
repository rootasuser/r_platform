const styles = `
  body {
    background-color: pink;
    height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
  }
  .login-container {
    background: #ffb5c0;
    padding: 30px;
    border-radius: 10px;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
    max-width: 400px;
    width: 100%;
  }
  .modal-dialog {
    max-width: 450px;
  }
  .modal-content {
    background-color: #fff;
    border-radius: 10px;
    width: 100%;
  }
  .form-group input {
    border-radius: 5px;
  }
  .login-btn {
    width: 100%;
    background-color: #ff69b4;
    color: white;
  }
`;

const styleSheet = document.createElement("style");
styleSheet.type = "text/css";
styleSheet.innerText = styles;
document.head.appendChild(styleSheet);

document.addEventListener('contextmenu', function(e) {
  e.preventDefault();
});
