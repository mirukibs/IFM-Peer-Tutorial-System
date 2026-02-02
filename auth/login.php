<?php
session_start();
include("../config/db.php");

// Auto-login if cookie is present
if (!isset($_SESSION['user_id']) && isset($_COOKIE['remember_user'])) {
    list($uid, $role, $hash) = explode('|', $_COOKIE['remember_user']);
    if (hash_equals(hash('sha256', $uid . $role . 'SECRET_KEY'), $hash)) {
        $_SESSION['user_id'] = $uid;
        $_SESSION['role'] = $role;

        if ($role === "admin") {
            header("Location: ../dashboard/admin.php");
        } else {
            header("Location: ../dashboard/{$role}.php");
        }
        exit;
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);
    $pass = $_POST['password'];
    $remember = isset($_POST['remember']);

    $stmt = $conn->prepare("SELECT id, password_hash FROM users WHERE email=? AND status='active'");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($res->num_rows === 1) {
        $user = $res->fetch_assoc();
        if (password_verify($pass, $user['password_hash'])) {
            $_SESSION['user_id'] = $user['id'];

            $rolesRes = $conn->prepare("SELECT role FROM user_roles WHERE user_id=?");
            $rolesRes->bind_param("i", $user['id']);
            $rolesRes->execute();
            $rolesData = $rolesRes->get_result();

            $roles = [];
            while ($r = $rolesData->fetch_assoc()) {
                $roles[] = $r['role'];
            }

            if (in_array("admin", $roles)) {
                $_SESSION['role'] = "admin";
                if ($remember) {
                    $token = hash('sha256', $user['id'] . "admin" . 'SECRET_KEY');
                    setcookie("remember_user", $user['id'] . "|admin|" . $token, time() + (30 * 24 * 60 * 60), "/", "", false, true);
                }
                header("Location: ../dashboard/admin.php");
                exit;
            }

            if (count($roles) === 1) {
                $_SESSION['role'] = $roles[0];
                if ($remember) {
                    $token = hash('sha256', $user['id'] . $roles[0] . 'SECRET_KEY');
                    setcookie("remember_user", $user['id'] . "|" . $roles[0] . "|" . $token, time() + (30 * 24 * 60 * 60), "/", "", false, true);
                }
                header("Location: ../dashboard/{$roles[0]}.php");
                exit;
            } else {
                $_SESSION['roles'] = $roles;
                $_SESSION['remember'] = $remember;
                header("Location: select_role.php");
                exit;
            }
        }
    }
    $error = "Invalid email or password.";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Login - IFM Peer Tutoring System</title>
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

    .login-box {
      background: rgba(255, 255, 255, 0.12);
      backdrop-filter: blur(14px);
      border-radius: 18px;
      box-shadow: 0 10px 30px rgba(0, 0, 0, 0.25);
      width: 90%;
      max-width: 420px;
      padding: 50px 40px;
      text-align: center;
      border: 1px solid rgba(255, 255, 255, 0.2);
      animation: fadeIn 0.7s ease-in-out;
    }

    .system-title {
      font-size: 1.7rem;
      font-weight: 600;
      color: #fff;
    }

    .system-subtitle {
      color: #FDB913;
      font-size: 0.95rem;
      margin-bottom: 30px;
      font-weight: 500;
      letter-spacing: 0.5px;
    }

    h2 {
      font-size: 1.3rem;
      color: #fff;
      margin-bottom: 20px;
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
      transition: all 0.3s;
    }

    input::placeholder {
      color: #ddd;
    }

    input:focus {
      outline: none;
      background: rgba(255, 255, 255, 0.25);
      box-shadow: 0 0 0 2px #FDB913;
    }

    .form-options {
      display: flex;
      justify-content: space-between;
      align-items: center;
      font-size: 0.9rem;
      margin: 10px 0 20px 0;
    }

    .form-options label {
      display: flex;
      align-items: center;
      gap: 6px;
      cursor: pointer;
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

    a {
      color: #FDB913;
      text-decoration: none;
      font-size: 0.9rem;
    }

    a:hover {
      text-decoration: underline;
    }

    @keyframes fadeIn {
      from { opacity: 0; transform: translateY(10px); }
      to { opacity: 1; transform: translateY(0); }
    }

    @media (max-width: 480px) {
      .login-box {
        padding: 40px 25px;
      }
    }
  </style>
</head>
<body>
  <div class="login-box">
    <div class="system-title">IFM Peer Tutoring System</div>
    <div class="system-subtitle">Institute of Finance Management</div>

    <h2>Login to Your Account</h2>

    <?php 
      if (isset($_GET['registered']) && $_GET['registered'] == 1) {
          echo "<p class='success'>✅ Account created successfully! Please login.</p>";
      }
      if (!empty($error)) echo "<p class='error'>$error</p>"; 
    ?>

    <form method="POST">
      <input type="email" name="email" placeholder="Institutional Email (e.g. john@ifm.ac.tz)" required>
      <input type="password" name="password" placeholder="Password" required>

      <div class="form-options">
        <label><input type="checkbox" name="remember" id="remember"> Remember me</label>
        <a href="reset_request.php">Forgot password?</a>
      </div>

      <button type="submit">Login</button>
    </form>

    <p style="margin-top:20px;">New user? <a href="register.php">Register here</a></p>
  </div>
</body>
</html>
