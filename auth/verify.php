<?php
include("../config/db.php");

$message = "";

if (isset($_GET['token'])) {
    $token = $_GET['token'];

    // Use prepared statement
    $stmt = $conn->prepare("UPDATE users SET status='active', verification_token=NULL WHERE verification_token=?");
    $stmt->bind_param("s", $token);
    $stmt->execute();

    if ($stmt->affected_rows > 0) {
        $message = "<p class='success'>Your account has been verified! <a href='login.php'>Login here</a></p>";
    } else {
        $message = "<p class='error'>Invalid or expired verification link.</p>";
    }
} else {
    $message = "<p class='error'>No verification token provided.</p>";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Email Verification - Peer Tutoring System</title>
  <style>
    body {
      font-family: Arial, sans-serif;
      background: #f4f6f9;
      display: flex;
      justify-content: center;
      align-items: center;
      height: 100vh;
      margin: 0;
    }
    .verify-box {
      background: #fff;
      padding: 30px;
      border-radius: 10px;
      box-shadow: 0 4px 10px rgba(0,0,0,0.1);
      width: 400px;
      text-align: center;
    }
    h2 {
      margin-bottom: 20px;
      color: #2c3e50;
    }
    .success { color: green; margin-bottom: 20px; font-size: 16px; }
    .error { color: red; margin-bottom: 20px; font-size: 16px; }
    a {
      color: #3498db;
      text-decoration: none;
      font-weight: bold;
    }
    a:hover {
      text-decoration: underline;
    }
  </style>
</head>
<body>
  <div class="verify-box">
    <h2>📩 Email Verification</h2>
    <?php echo $message; ?>
  </div>
</body>
</html>
