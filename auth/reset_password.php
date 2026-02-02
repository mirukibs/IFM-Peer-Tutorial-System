<?php
include("../config/db.php");

$token = $_GET['token'] ?? null;
$message = "";
$formVisible = false;

if ($token) {
    $formVisible = true;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $token = $_POST['token'] ?? null;
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm_password'] ?? '';

    if (!$token) {
        $message = "<p class='error'>Invalid request. No token provided.</p>";
    } elseif ($password !== $confirm) {
        $message = "<p class='error'>Passwords do not match.</p>";
        $formVisible = true;
    } elseif (strlen($password) < 8) {
        $message = "<p class='error'>Password must be at least 8 characters long.</p>";
        $formVisible = true;
    } else {
        $newpass = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE users SET password_hash=?, verification_token=NULL WHERE verification_token=?");
        $stmt->bind_param("ss", $newpass, $token);
        $stmt->execute();

        if ($stmt->affected_rows > 0) {
            $message = "<p class='success'>Password updated successfully. 
                        <a href='login.php'>Login here</a></p>";
            $formVisible = false;
        } else {
            $message = "<p class='error'>Failed to update password. The reset link may be invalid or expired.</p>";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Reset Password - IFM Peer Tutoring System</title>
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

    button {
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
    }

    button:hover {
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

    .success a {
      color: #FDB913;
      text-decoration: none;
      font-weight: 600;
    }

    .success a:hover {
      text-decoration: underline;
    }

    #pwError {
      color: #FF6B6B;
      display: none;
      font-size: 0.9rem;
      margin-top: 5px;
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
    <h2>🔑 Reset Password</h2>

    <?php if (!empty($message)) echo $message; ?>

    <?php if ($formVisible): ?>
      <form method="POST" onsubmit="return validatePassword()">
        <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
        <input type="password" id="password" name="password" placeholder="Enter new password" required>
        <input type="password" id="confirm_password" name="confirm_password" placeholder="Confirm new password" required>
        <p id="pwError">Passwords do not match</p>
        <button type="submit">Update Password</button>
      </form>
    <?php endif; ?>
  </div>

  <script>
    function validatePassword() {
      const pw = document.getElementById("password").value;
      const cpw = document.getElementById("confirm_password").value;
      const errorMsg = document.getElementById("pwError");

      if (pw !== cpw) {
        errorMsg.style.display = "block";
        return false;
      }
      errorMsg.style.display = "none";
      return true;
    }
  </script>
</body>
</html>
