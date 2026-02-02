<?php
session_start();
include("../config/db.php");

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$message = "";

// Handle profile updates
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $fname = trim($_POST['first_name']);
    $lname = trim($_POST['last_name']);
    $password = $_POST['password'];
    $confirm  = $_POST['confirm_password'];

    if (!empty($password)) {
        if ($password !== $confirm) {
            $message = "<p class='error'>Passwords do not match.</p>";
        } elseif (strlen($password) < 8) {
            $message = "<p class='error'>Password must be at least 8 characters.</p>";
        } else {
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE users SET first_name=?, last_name=?, password_hash=? WHERE id=?");
            $stmt->bind_param("sssi", $fname, $lname, $hashed, $user_id);
            $stmt->execute();
            $message = "<p class='success'>✅ Profile and password updated successfully.</p>";
        }
    } else {
        $stmt = $conn->prepare("UPDATE users SET first_name=?, last_name=? WHERE id=?");
        $stmt->bind_param("ssi", $fname, $lname, $user_id);
        $stmt->execute();
        $message = "<p class='success'>✅ Profile updated successfully.</p>";
    }
}

// Fetch user details
$stmt = $conn->prepare("SELECT first_name,last_name,email,status FROM users WHERE id=?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

// Fetch user roles
$roleStmt = $conn->prepare("SELECT role FROM user_roles WHERE user_id=?");
$roleStmt->bind_param("i", $user_id);
$roleStmt->execute();
$roleResult = $roleStmt->get_result();
$roles = [];
while ($r = $roleResult->fetch_assoc()) {
    $roles[] = $r['role'];
}
$user['role'] = implode(', ', $roles);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>User Profile - Peer Tutoring System</title>
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
    .profile-box {
      background: #fff;
      padding: 30px;
      border-radius: 10px;
      box-shadow: 0 4px 10px rgba(0,0,0,0.1);
      width: 450px;
    }
    h2 {
      margin-bottom: 20px;
      color: #2c3e50;
      text-align: center;
    }
    label {
      font-weight: bold;
      display: block;
      margin-top: 10px;
    }
    input {
      width: 95%;
      padding: 10px;
      margin: 5px 0;
      border: 1px solid #ccc;
      border-radius: 5px;
    }
    button {
      width: 100%;
      padding: 10px;
      background: #3498db;
      border: none;
      color: white;
      font-size: 16px;
      border-radius: 5px;
      cursor: pointer;
      margin-top: 15px;
    }
    button:hover {
      background: #2980b9;
    }
    .error { color: red; margin: 10px 0; }
    .success { color: green; margin: 10px 0; }
    .readonly {
      background: #f4f4f4;
    }
    .actions {
      margin-top: 20px;
      text-align: center;
    }
    .actions a {
      margin: 0 10px;
      color: #e67e22;
      text-decoration: none;
    }
    .actions a:hover {
      text-decoration: underline;
    }
  </style>
</head>
<body>
  <div class="profile-box">
    <h2>👤 User Profile</h2>
    <?php if (!empty($message)) echo $message; ?>

    <form method="POST">
      <label>First Name</label>
      <input type="text" name="first_name" value="<?php echo htmlspecialchars($user['first_name']); ?>" required>

      <label>Last Name</label>
      <input type="text" name="last_name" value="<?php echo htmlspecialchars($user['last_name']); ?>" required>

      <label>Email (read-only)</label>
      <input type="email" value="<?php echo htmlspecialchars($user['email']); ?>" class="readonly" readonly>

      <label>Role</label>
      <input type="text" value="<?php echo htmlspecialchars($user['role']); ?>" class="readonly" readonly>

      <label>Status</label>
      <input type="text" value="<?php echo htmlspecialchars($user['status']); ?>" class="readonly" readonly>

      <label>New Password (leave blank if not changing)</label>
      <input type="password" name="password" placeholder="Enter new password">

      <label>Confirm New Password</label>
      <input type="password" name="confirm_password" placeholder="Confirm new password">

      <button type="submit">Update Profile</button>
    </form>

    <div class="actions">
      <a href="logout.php">🚪 Logout</a> |
      <a href="../dashboard/<?php echo $user['role']; ?>.php">🏠 Dashboard</a>
    </div>
  </div>
</body>
</html>
