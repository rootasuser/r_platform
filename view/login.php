<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../controller/AuthController.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Login</title>
  <link rel="stylesheet" href="../assets/css/style.login.css">
  <link rel="stylesheet" href="../assets/css/style.modal.css">
  <link rel="stylesheet" href="../assets/css/style.toast.css">

</head>
<body>
<div id="toast-container" class="toast-container"></div>
<?php if (isset($_SESSION['message'])): ?>
  <script>
    document.addEventListener("DOMContentLoaded", function () {
      showToast("<?= addslashes($_SESSION['message']); ?>");
    });
  </script>
  <?php unset($_SESSION['message']); ?>
<?php endif; ?>



  <div class="row res">
    <div class="fb-form res">

      <div class="card">
        <h1>R Connect</h1>
        <p>Connect with friends and the world</p>
        <p>around you on R Connect Platform.</p>
      </div>
      <form method="POST">
        <input type="tex" name="emailOrContactNumber" placeholder="Email or phone number" required>
        <input type="password" name="password" placeholder="Password" required>
        <div class="fb-submit">
          <button type="submit" class="login" name="loginBtn">Login</button>
          <a href="#" class="forgot">Forgot password?</a>
        </div>
        <hr>
        <div class="button">
          <a href="#" id="create-account-btn">Create new account</a>
        </div>
      </form>
    </div>
  </div>

  <div class="modal" id="createAccountModal">
    <div class="modal-content">
      <span class="close-btn" id="closeModal">&times;</span>
      <h2>Create New Account</h2>

      <form method="POST">
      <input type="text" name="firstName" placeholder="First name" required>
      <input type="text" name="lastName" placeholder="Last name" required>
      <input type="email" name="email" placeholder="Email" required>
      <input type="tel" name="contactNumber" placeholder="Contact" required>
      <input type="password" name="password" placeholder="Password" required>
      <input type="password" name="confirmPasword" placeholder="Confirm Password" required>
      <button class="register-btn" name="registerBtn">Sign Up</button>
      </form>
    </div>
  </div>

  <script src="../assets/js/function.login.js"></script>
  <script src="../assets/js/function.toast.js"></script>
</body>
</html>
