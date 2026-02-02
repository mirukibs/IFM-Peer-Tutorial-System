<?php
include("../config/db.php");

$message = "";
$formVisible = true;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = strtolower(trim($_POST['email'])); // normalize email

    $stmt = $conn->prepare("SELECT id FROM users WHERE email=?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($res->num_rows > 0) {
        $token = bin2hex(random_bytes(16));
        $update = $conn->prepare("UPDATE users SET verification_token=? WHERE email=?");
        $update->bind_param("ss", $token, $email);
        $update->execute();

        $reset_link = "http://localhost:8080/peer/auth/reset_password.php?token=$token";

        $sent = @mail($email, "Reset Password", "Click this link to reset your password: $reset_link");

        if ($sent) {
            $message = "<p class='success'>✅ A reset link has been sent to <b>$email</b>. 
                        Please check your inbox.</p>";
        } else {
            $message = "<p class='success'>✅ Reset link generated successfully.<br>
                        (⚠ Local testing mode – email not sent)<br>
                        You can continue directly:<br>
                        <a href='$reset_link' class='btn'>➡ Reset Your Password</a></p>";
        }

        $formVisible = false;
    } else {
        $message = "<p class='error'>Email not found in system.</p>";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Forgot Password - IFM Peer Tutoring System</title>
  <style>
    * {
      box-sizing: border-box;
    }

    body {
      font-family: 'Poppins', sans-serif;
      background: linear-gradient(135deg, #002B7F, #0044AA);
      display: flex;
      justify-content: center;
      align-items: center;
      height: 100vh;
      margin: 0;
      color: #fff;
    }

    .reset-box {
      background: rgba(255, 255, 255, 0.12);
      backdrop-filter: blur(14px);
      border-radius: 18px;
      box-shadow: 0 10px 30px rgba(0, 0, 0, 0.25);
      width: 90%;
      max-width: 420px;
      padding: 45px 40px;
      text-align: center;
      border: 1px solid rgba(255, 255, 255, 0.2);
      animation: fadeIn 0.8s ease-in-out;
    }

    h2 {
      font-size: 1.5rem;
      margin-bottom: 20px;
      color: #fff;
    }

    p {
      font-size: 0.95rem;
      line-height: 1.5;
    }

    input {
      width: 100%;
      padding: 12px 14px;
      margin: 8px 0;
      border: none;
      border-radius: 8px;
      background: rgba(255, 255, 255, 0.15);
      color: #fff;
      font-size: 0.95rem;
      transition: all 0.3s ease;
    }

    input::placeholder {
      color: #ddd;
    }

    input:focus {
      outline: none;
      background: rgba(255, 255, 255, 0.25);
      box-shadow: 0 0 0 2px #FDB913;
    }

    button, .btn {
      width: 100%;
      background: #FDB913;
      color: #002B7F;
      border: none;
      padding: 12px;
      border-radius: 8px;
      font-weight: 600;
      font-size: 1rem;
      cursor: pointer;
      transition: all 0.3s;
      margin-top: 10px;
      text-decoration: none;
      display: inline-block;
    }

    button:hover, .btn:hover {
      background: #fff;
      color: #002B7F;
      transform: translateY(-2px);
      box-shadow: 0 6px 18px rgba(255, 255, 255, 0.25);
    }

    .error {
      color: #FF6B6B;
      margin-bottom: 10px;
      font-size: 0.9rem;
    }

    .success {
      color: #4CAF50;
      margin-bottom: 10px;
      font-size: 0.9rem;
    }

    a {
      color: #FDB913;
      text-decoration: none;
      font-size: 0.9rem;
      display: inline-block;
      margin-top: 15px;
    }

    a:hover {
      text-decoration: underline;
    }

    @keyframes fadeIn {
      from { opacity: 0; transform: translateY(10px); }
      to { opacity: 1; transform: translateY(0); }
    }

    @media (max-width: 480px) {
      .reset-box {
        padding: 35px 25px;
      }
    }
  </style>
</head>
<body>
  <div class="reset-box">
    <h2>🔒 Forgot Password</h2>

    <?php if (!empty($message)) echo $message; ?>

    <?php if ($formVisible): ?>
      <form method="POST">
        <input type="email" name="email" placeholder="Enter your institutional email (e.g. john@ifm.ac.tz)" required>
        <button type="submit">Send Reset Link</button>
      </form>
    <?php endif; ?>

    <a href="login.php">⬅ Back to Login</a>
  </div>
</body>
</html>
