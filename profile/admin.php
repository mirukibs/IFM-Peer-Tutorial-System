<?php
session_start();
if ($_SESSION['role'] != 'admin') { die("Unauthorized"); }

include("../config/db.php");

$uid = $_SESSION['user_id'];
$message = "";

// Fetch admin data
$stmt = $conn->prepare("SELECT first_name,last_name,email,password_hash,profile_pic FROM users WHERE id=?");
$stmt->bind_param("i", $uid);
$stmt->execute();
$res = $stmt->get_result();
$user = $res->fetch_assoc();

// Handle profile update
if (isset($_POST['update_profile'])) {
    $fname = trim($_POST['first_name']);
    $lname = trim($_POST['last_name']);
    $email = trim($_POST['email']);

    // Handle profile picture
    $picPath = $user['profile_pic'];
    if (!empty($_FILES['profile_pic']['name'])) {
        $targetDir = "../uploads/profile_pics/";
        if (!is_dir($targetDir)) mkdir($targetDir, 0777, true);

        $ext = pathinfo($_FILES['profile_pic']['name'], PATHINFO_EXTENSION);
        $newName = "admin_" . $uid . "_" . time() . "." . strtolower($ext);
        $targetFile = $targetDir . $newName;

        if (in_array(strtolower($ext), ['jpg','jpeg','png'])) {
            if (move_uploaded_file($_FILES['profile_pic']['tmp_name'], $targetFile)) {
                $picPath = $newName;
            }
        }
    }

    $upd = $conn->prepare("UPDATE users SET first_name=?, last_name=?, email=?, profile_pic=? WHERE id=?");
    $upd->bind_param("ssssi", $fname, $lname, $email, $picPath, $uid);
    if ($upd->execute()) {
        $message = "<p class='success'>✅ Profile updated successfully.</p>";
        $user['first_name'] = $fname;
        $user['last_name']  = $lname;
        $user['email']      = $email;
        $user['profile_pic'] = $picPath;
    } else {
        $message = "<p class='error'>Failed to update profile.</p>";
    }
}

// Handle password change
if (isset($_POST['change_password'])) {
    $current = $_POST['current_password'];
    $new     = $_POST['new_password'];
    $confirm = $_POST['confirm_password'];

    if (!password_verify($current, $user['password_hash'])) {
        $message = "<p class='error'>Current password is incorrect.</p>";
    } elseif ($new !== $confirm) {
        $message = "<p class='error'>New passwords do not match.</p>";
    } elseif (strlen($new) < 6) {
        $message = "<p class='error'>Password must be at least 6 characters.</p>";
    } else {
        $hash = password_hash($new, PASSWORD_DEFAULT);
        $upd = $conn->prepare("UPDATE users SET password_hash=? WHERE id=?");
        $upd->bind_param("si", $hash, $uid);
        if ($upd->execute()) {
            $message = "<p class='success'>✅ Password changed successfully.</p>";
        } else {
            $message = "<p class='error'>Failed to change password.</p>";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Admin Profile</title>
  <style>
    body { font-family: Arial, sans-serif; background:#f4f6f9; display:flex; justify-content:center; align-items:center; min-height:100vh; margin:0; }
    .profile-box { background:#fff; padding:30px; border-radius:10px; box-shadow:0 4px 10px rgba(0,0,0,0.1); width:400px; text-align:center; }
    h2 { color:#2c3e50; margin-bottom:20px; }
    h3 { color:#34495e; margin-top:30px; }
    label { display:block; margin-top:10px; text-align:left; font-weight:bold; }
    input[type="text"], input[type="email"], input[type="password"], input[type="file"] {
      width:95%; padding:10px; margin:6px 0; border:1px solid #ccc; border-radius:6px;
    }
    button { width:100%; padding:12px; background:#27ae60; border:none; color:white; font-size:16px; border-radius:6px; margin-top:15px; cursor:pointer; }
    button:hover { background:#219150; }
    .success { color:green; margin-bottom:15px; }
    .error { color:red; margin-bottom:15px; }
    .back { display:inline-block; margin-top:20px; text-decoration:none; background:#3498db; color:white; padding:10px 16px; border-radius:6px; }
    .back:hover { background:#2980b9; }
    img.profile { width:100px; height:100px; border-radius:50%; margin-bottom:10px; object-fit:cover; }
  </style>
</head>
<body>
  <div class="profile-box">
    <h2>👤 Admin Profile</h2>
    <?php if ($message) echo $message; ?>

    <?php if (!empty($user['profile_pic'])): ?>
      <img src="../uploads/profile_pics/<?php echo $user['profile_pic']; ?>" class="profile" alt="Profile Picture">
    <?php else: ?>
      <img src="../uploads/profile_pics/default.png" class="profile" alt="Default Profile">
    <?php endif; ?>

    <!-- Update Profile Form -->
    <form method="POST" enctype="multipart/form-data">
      <label>First Name</label>
      <input type="text" name="first_name" value="<?php echo htmlspecialchars($user['first_name']); ?>" required>

      <label>Last Name</label>
      <input type="text" name="last_name" value="<?php echo htmlspecialchars($user['last_name']); ?>" required>

      <label>Email</label>
      <input type="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>

      <label>Profile Picture</label>
      <input type="file" name="profile_pic" accept="image/png, image/jpeg">

      <button type="submit" name="update_profile">💾 Update Profile</button>
    </form>

    <!-- Change Password -->
    <h3>🔑 Change Password</h3>
    <form method="POST">
      <label>Current Password</label>
      <input type="password" name="current_password" required>

      <label>New Password</label>
      <input type="password" name="new_password" required>

      <label>Confirm New Password</label>
      <input type="password" name="confirm_password" required>

      <button type="submit" name="change_password">🔒 Change Password</button>
    </form>

    <a href="../dashboard/admin.php" class="back">⬅ Back to Dashboard</a>
  </div>
</body>
</html>
