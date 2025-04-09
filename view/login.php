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
  <title>R Portal</title>
  <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link rel="stylesheet" href="../assets/css/style.toast.css">
  <style>
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
  
  </style>
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

<div class="login-container text-center">
  <div class="logo mb-3">
    <img src="../assets/logo/R.png" alt="logo login" width="120" height="120" class="rounded-circle">
  </div>

  <form method="POST" action="">
  <div class="form-group input-group">
    <div class="input-group-prepend">
      <span class="input-group-text"><i class="fas fa-user"></i></span>
    </div>
    <input type="text" class="form-control" name="emailOrContactNumber" placeholder="Email or phone number" required>
  </div>
  <div class="form-group input-group">
    <div class="input-group-prepend">
      <span class="input-group-text"><i class="fas fa-lock"></i></span>
    </div>
    <input type="password" class="form-control" name="password" placeholder="Password" required>
  </div>
  <button type="submit" class="btn login-btn" name="loginBtn">Log In</button>
</form>


  <div class="create-account mb-2 mt-2">
    <a href="#" id="create-account-btn" style="font-weight: bolder; color: #333; text-decoration: underline;">Create new account</a>
  </div>
</div>

<!-- Modal -->
<div class="modal" id="createAccountModal" tabindex="-1" role="dialog" style="display: none;">
  <div class="modal-dialog modal-dialog-centered" role="document">
    <div class="modal-content p-4">
      <span class="close-btn ml-auto mb-2" id="closeModal" style="font-size: 24px; cursor: pointer;">&times;</span>
      <h4 class="text-center mb-4">Create New Account</h4>
      <form method="POST">
  <div class="form-group input-group">
    <div class="input-group-prepend">
      <span class="input-group-text"><i class="fas fa-user"></i></span>
    </div>
    <input type="text" class="form-control" name="firstName" placeholder="First name" required>
  </div>

  <div class="form-group input-group">
    <div class="input-group-prepend">
      <span class="input-group-text"><i class="fas fa-user"></i></span>
    </div>
    <input type="text" class="form-control" name="lastName" placeholder="Last name" required>
  </div>

  <div class="form-group input-group">
    <div class="input-group-prepend">
      <span class="input-group-text"><i class="fas fa-envelope"></i></span>
    </div>
    <input type="email" class="form-control" name="email" placeholder="Email" required>
  </div>

  <div class="form-group input-group">
    <div class="input-group-prepend">
      <span class="input-group-text"><i class="fas fa-phone"></i></span>
    </div>
    <input type="tel" class="form-control" name="contactNumber" placeholder="Contact" required>
  </div>

  <div class="form-group input-group">
    <div class="input-group-prepend">
      <span class="input-group-text"><i class="fas fa-lock"></i></span>
    </div>
    <input type="password" class="form-control" name="password" placeholder="Password" required>
  </div>

  <div class="form-group input-group">
    <div class="input-group-prepend">
      <span class="input-group-text"><i class="fas fa-lock"></i></span>
    </div>
    <input type="password" class="form-control" name="confirmPasword" placeholder="Confirm Password" required>
  </div>

  <button type="submit" class="btn login-btn" name="registerBtn">Sign Up</button>
</form>

    </div>
  </div>
</div>

<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/function.login.js"></script>
<script src="../assets/js/function.toast.js"></script>

</body>
</html>
