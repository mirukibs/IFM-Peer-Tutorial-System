<?php
include("../config/db.php");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $fname = trim($_POST['first_name']);
    $lname = trim($_POST['last_name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'] ?? '';
    $roles = $_POST['roles'] ?? [];

    if ($password !== $confirm_password) {
        $error = "Passwords do not match.";
    } elseif (empty($roles)) {
        $error = "Please select at least one role.";
    } else {
        $passHash = password_hash($password, PASSWORD_DEFAULT);

        if (!preg_match("/@student\.ifm\.ac\.tz$/", $email)) {
            $error = "Invalid email domain. Use your institutional email (@student.ifm.ac.tz).";
        } else {
            $stmt = $conn->prepare("INSERT INTO users (first_name, last_name, email, password_hash, status) VALUES (?,?,?,?, 'active')");
            $stmt->bind_param("ssss", $fname, $lname, $email, $passHash);

            if ($stmt->execute()) {
                $userId = $stmt->insert_id;

                $roleStmt = $conn->prepare("INSERT OR IGNORE INTO user_roles (user_id, role) VALUES (?, ?)");
                foreach ($roles as $role) {
                    $role = strtolower(trim($role));
                    $roleStmt->bind_param("is", $userId, $role);
                    $roleStmt->execute();
                }

                header("Location: login.php?registered=1");
                exit;
            } else {
                $error = "Error: " . $conn->error;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Register - IFM Peer Tutoring System</title>
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

    .register-box {
      background: rgba(255, 255, 255, 0.12);
      backdrop-filter: blur(14px);
      border-radius: 18px;
      box-shadow: 0 10px 30px rgba(0, 0, 0, 0.25);
      width: 90%;
      max-width: 440px;
      padding: 45px 40px;
      text-align: center;
      border: 1px solid rgba(255, 255, 255, 0.2);
      animation: fadeIn 0.8s ease-in-out;
    }

    .system-title {
      padding-top: 40px;
      font-size: 1.7rem;
      font-weight: 600;
      color: #fff;
    }

    .system-subtitle {
      color: #FDB913;
      font-size: 0.95rem;
      margin-bottom: 25px;
      font-weight: 500;
      letter-spacing: 0.4px;
    }

    h2 {
      font-size: 1.3rem;
      margin-bottom: 20px;
      color: #fff;
    }

    input {
      width: 100%;
      padding: 12px 14px;
      margin: 7px 0;
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

    .roles {
      text-align: left;
      margin: 15px 0 10px 0;
      font-size: 0.95rem;
    }

    .roles label {
      display: block;
      margin: 6px 0;
      cursor: pointer;
    }

    .roles input {
      width: auto;
      margin-right: 8px;
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
      .register-box {
        padding: 35px 25px;
      }
    }
  </style>
</head>
<body>
  <div class="register-box">
    <div class="system-title">IFM Peer Tutoring System</div>
    <div class="system-subtitle">Institute of Finance Management</div>

    <h2>Create an Account</h2>

    <?php if (!empty($error)) echo "<p class='error'>$error</p>"; ?>

    <form method="POST">
      <input name="first_name" placeholder="First Name" required>
      <input name="last_name" placeholder="Last Name" required>
      <input type="email" name="email" placeholder="Institutional Email (e.g. john@ifm.ac.tz)" required>
      <input type="password" name="password" placeholder="Password" required>
      <input type="password" name="confirm_password" placeholder="Confirm Password" required>

      <div class="roles">
        <p><strong>Select Role(s):</strong></p>
        <label><input type="checkbox" name="roles[]" value="student"> Student</label>
        <label><input type="checkbox" name="roles[]" value="tutor"> Tutor</label>
      </div>

      <button type="submit">Register</button>
    </form>

    <a href="login.php">Already have an account? Login here</a>
  </div>
</body>
</html>
