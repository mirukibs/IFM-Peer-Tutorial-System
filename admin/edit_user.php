<?php
session_start();
include("../config/db.php");

// Ensure admin
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    die("Unauthorized access.");
}

$user_id = $_GET['id'] ?? null;
if (!$user_id) die("User not found.");

$message = "";

// Fetch user
$stmt = $conn->prepare("SELECT id, first_name, last_name, email, status FROM users WHERE id=?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
if (!$user) die("User not found.");

// Fetch user roles
$roleStmt = $conn->prepare("SELECT role FROM user_roles WHERE user_id=?");
$roleStmt->bind_param("i", $user_id);
$roleStmt->execute();
$roleResult = $roleStmt->get_result();
$userRoles = [];
while ($r = $roleResult->fetch_assoc()) {
    $userRoles[] = $r['role'];
}

// Handle updates
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $fname = trim($_POST['first_name']);
    $lname = trim($_POST['last_name']);
    $roles  = $_POST['roles'] ?? [];
    $status = $_POST['status'];
    $password = $_POST['password'];

    // Update basic info
    if (!empty($password)) {
        $hashed = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE users SET first_name=?, last_name=?, status=?, password_hash=? WHERE id=?");
        $stmt->bind_param("ssssi", $fname, $lname, $status, $hashed, $user_id);
    } else {
        $stmt = $conn->prepare("UPDATE users SET first_name=?, last_name=?, status=? WHERE id=?");
        $stmt->bind_param("sssi", $fname, $lname, $status, $user_id);
    }
    $stmt->execute();

    // Update roles - delete existing and insert new
    $delStmt = $conn->prepare("DELETE FROM user_roles WHERE user_id=?");
    $delStmt->bind_param("i", $user_id);
    $delStmt->execute();
    
    if (!empty($roles)) {
        $roleStmt = $conn->prepare("INSERT INTO user_roles (user_id, role) VALUES (?, ?)");
        foreach ($roles as $role) {
            $roleStmt->bind_param("is", $user_id, $role);
            $roleStmt->execute();
        }
    }

    $message = "<p class='success'>✅ User profile updated successfully.</p>";

    // Reload updated user and roles
    $stmt = $conn->prepare("SELECT id, first_name, last_name, email, status FROM users WHERE id=?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    
    $roleStmt = $conn->prepare("SELECT role FROM user_roles WHERE user_id=?");
    $roleStmt->bind_param("i", $user_id);
    $roleStmt->execute();
    $roleResult = $roleStmt->get_result();
    $userRoles = [];
    while ($r = $roleResult->fetch_assoc()) {
        $userRoles[] = $r['role'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Edit User - Admin Panel</title>
  <style>
    body { font-family: Arial, sans-serif; background: #f4f6f9; display:flex; justify-content:center; align-items:center; height:100vh; }
    .box { background:#fff; padding:30px; border-radius:10px; box-shadow:0 4px 10px rgba(0,0,0,0.1); width:450px; }
    h2 { text-align:center; color:#2c3e50; }
    label { font-weight:bold; display:block; margin-top:10px; }
    input, select { width:95%; padding:10px; margin:5px 0; border:1px solid #ccc; border-radius:5px; }
    button { width:100%; padding:10px; background:#27ae60; border:none; color:white; border-radius:5px; margin-top:15px; cursor:pointer; }
    button:hover { background:#219150; }
    .success { color:green; margin:10px 0; text-align:center; }
    .actions { margin-top:15px; text-align:center; }
    .actions a { color:#3498db; text-decoration:none; }
    .actions a:hover { text-decoration:underline; }
  </style>
</head>
<body>
  <div class="box">
    <h2>✏ Edit User</h2>
    <?php if (!empty($message)) echo $message; ?>
    <form method="POST">
      <label>First Name</label>
      <input type="text" name="first_name" value="<?php echo htmlspecialchars($user['first_name']); ?>" required>

      <label>Last Name</label>
      <input type="text" name="last_name" value="<?php echo htmlspecialchars($user['last_name']); ?>" required>

      <label>Email (read-only)</label>
      <input type="email" value="<?php echo htmlspecialchars($user['email']); ?>" readonly>

      <label>Roles (select all that apply)</label>
      <div style="margin: 10px 0;">
        <label style="display:inline-block; margin-right:15px; font-weight:normal;">
          <input type="checkbox" name="roles[]" value="student" <?php if(in_array('student', $userRoles)) echo 'checked'; ?>> Student
        </label>
        <label style="display:inline-block; margin-right:15px; font-weight:normal;">
          <input type="checkbox" name="roles[]" value="tutor" <?php if(in_array('tutor', $userRoles)) echo 'checked'; ?>> Tutor
        </label>
        <label style="display:inline-block; font-weight:normal;">
          <input type="checkbox" name="roles[]" value="admin" <?php if(in_array('admin', $userRoles)) echo 'checked'; ?>> Admin
        </label>
      </div>

      <label>Status</label>
      <select name="status">
        <option value="active" <?php if($user['status']=="active") echo "selected"; ?>>Active</option>
        <option value="suspended" <?php if($user['status']=="suspended") echo "selected"; ?>>Suspended</option>
      </select>

      <label>New Password (leave blank if not changing)</label>
      <input type="password" name="password" placeholder="Enter new password">

      <button type="submit">Update User</button>
    </form>

    <div class="actions">
      <a href="admin_users.php">⬅ Back to Users</a>
    </div>
  </div>
</body>
</html>
