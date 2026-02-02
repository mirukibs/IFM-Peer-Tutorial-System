<?php
session_start();
if ($_SESSION['role'] != 'tutor') { die("Unauthorized"); }
include("../config/db.php");

$uid = $_SESSION['user_id'];
$message = "";

// Fetch tutor data
$stmt = $conn->prepare("SELECT first_name,last_name,email,phone,password_hash,profile_pic,year_of_study,degree_programme 
                        FROM users WHERE id=?");
$stmt->bind_param("i", $uid);
$stmt->execute();
$res = $stmt->get_result();
$user = $res->fetch_assoc();

// Fetch tutor subjects
$tutorSubjects = [];
$subRes = $conn->query("SELECT subject FROM tutor_subjects WHERE tutor_id=$uid");
while ($row = $subRes->fetch_assoc()) {
    $tutorSubjects[] = $row['subject'];
}

// Handle profile update
if (isset($_POST['update_profile'])) {
    $fname   = trim($_POST['first_name']);
    $lname   = trim($_POST['last_name']);
    $email   = trim($_POST['email']);
    $phone   = trim($_POST['phone']);
    $year    = intval($_POST['year_of_study']);
    $degree  = trim($_POST['degree_programme']);
    $subjects = $_POST['subjects'] ?? [];

    // Profile picture
    $picPath = $user['profile_pic'];
    if (!empty($_FILES['profile_pic']['name'])) {
        $targetDir = "../uploads/profile_pics/";
        if (!is_dir($targetDir)) mkdir($targetDir, 0777, true);

        $ext = pathinfo($_FILES['profile_pic']['name'], PATHINFO_EXTENSION);
        $newName = "tutor_" . $uid . "_" . time() . "." . strtolower($ext);
        $targetFile = $targetDir . $newName;

        if (in_array(strtolower($ext), ['jpg','jpeg','png'])) {
            if (move_uploaded_file($_FILES['profile_pic']['tmp_name'], $targetFile)) {
                $picPath = $newName;
            }
        }
    }

    // Update users table
    $upd = $conn->prepare("UPDATE users 
                           SET first_name=?, last_name=?, email=?, phone=?, profile_pic=?, year_of_study=?, degree_programme=? 
                           WHERE id=?");
    $upd->bind_param("sssssisi", $fname, $lname, $email, $phone, $picPath, $year, $degree, $uid);
    if ($upd->execute()) {
        // Update tutor_subjects table
        $conn->query("DELETE FROM tutor_subjects WHERE tutor_id=$uid");
        foreach ($subjects as $sub) {
            $sub = $conn->real_escape_string(trim($sub));
            if ($sub !== "") {
                $conn->query("INSERT INTO tutor_subjects (tutor_id,subject,year_of_study,degree_programme) 
                              VALUES ($uid,'$sub',$year,'$degree')");
            }
        }

        $message = "<div class='alert success'>✅ Profile updated successfully.</div>";
        $user['first_name'] = $fname;
        $user['last_name']  = $lname;
        $user['email']      = $email;
        $user['phone']      = $phone;
        $user['profile_pic'] = $picPath;
        $user['year_of_study'] = $year;
        $user['degree_programme'] = $degree;
        $tutorSubjects = $subjects;
    } else {
        $message = "<div class='alert error'>Failed to update profile.</div>";
    }
}

// Handle password change
if (isset($_POST['change_password'])) {
    $current = $_POST['current_password'];
    $new     = $_POST['new_password'];
    $confirm = $_POST['confirm_password'];

    if (!password_verify($current, $user['password_hash'])) {
        $message = "<div class='alert error'>Current password is incorrect.</div>";
    } elseif ($new !== $confirm) {
        $message = "<div class='alert error'>New passwords do not match.</div>";
    } elseif (strlen($new) < 6) {
        $message = "<div class='alert error'>Password must be at least 6 characters.</div>";
    } else {
        $hash = password_hash($new, PASSWORD_DEFAULT);
        $upd = $conn->prepare("UPDATE users SET password_hash=? WHERE id=?");
        $upd->bind_param("si", $hash, $uid);
        if ($upd->execute()) {
            $message = "<div class='alert success'>✅ Password changed successfully.</div>";
        } else {
            $message = "<div class='alert error'>Failed to change password.</div>";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>👨‍🏫 Tutor Profile - IFM Peer Tutoring</title>
<style>
  * { box-sizing: border-box; }
  body {
    font-family: 'Poppins', sans-serif;
    background: linear-gradient(135deg, #002B7F, #0044AA);
    margin: 0; padding: 0;
    display: flex;
    justify-content: center;
    align-items: flex-start;
    min-height: 100vh;
    color: #fff;
  }

  .profile-box {
    background: rgba(255,255,255,0.12);
    backdrop-filter: blur(12px);
    border-radius: 18px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.25);
    padding: 40px;
    margin: 60px 20px;
    max-width: 850px;
    width: 95%;
    border: 1px solid rgba(255,255,255,0.2);
    animation: fadeIn 0.8s ease-in-out;
  }

  h2 {
    text-align: center;
    color: #2ecc71;
    font-size: 1.8rem;
    margin-bottom: 20px;
  }

  h3 { color: #FDB913; margin-top: 35px; font-size: 1.2rem; }

  label { display:block; margin-top:10px; font-weight:600; color:#fff; }
  input, select {
    width:100%; padding:12px; margin:6px 0 12px;
    border-radius:8px; border:none; background:rgba(255,255,255,0.9);
    color:#002B7F; font-size:15px; transition:0.3s;
  }
  input:focus, select:focus { outline:none; box-shadow:0 0 0 2px #2ecc71; }

  button {
    width:100%; padding:14px;
    background:#2ecc71; color:#002B7F;
    border:none; border-radius:10px;
    font-size:16px; font-weight:700;
    cursor:pointer; transition:0.3s;
  }
  button:hover {
    background:#fff; color:#002B7F;
    transform:translateY(-2px);
    box-shadow:0 6px 15px rgba(255,255,255,0.25);
  }

  .add-btn { background:#FDB913; color:#002B7F; font-weight:600; }
  .add-btn:hover { background:#fff; }

  .alert { padding:15px; border-radius:10px; font-weight:500; margin-bottom:20px; text-align:center; }
  .success { background:rgba(46,204,113,0.2); border:1px solid #2ecc71; color:#d4f8e8; }
  .error { background:rgba(231,76,60,0.2); border:1px solid #e74c3c; color:#ffd6d6; }

  .info-panel { text-align:center; margin-bottom:30px; }
  .info-panel img { width:120px; height:120px; border-radius:50%; border:3px solid #2ecc71; object-fit:cover; margin-bottom:10px; }
  .info-panel h3 { margin:10px 0 5px; color:#fff; }
  .info-panel p { margin:4px 0; color:#eee; }

  .subjects-list { display:flex; align-items:center; margin-bottom:8px; gap:8px; }
  .subjects-list input { flex:1; }
  .subjects-list button {
    width:40px; background:#e74c3c; color:white; border:none;
    padding:8px; border-radius:6px; cursor:pointer;
  }

  .section { background:rgba(255,255,255,0.1); padding:25px; border-radius:12px; margin-bottom:25px; }

  .back {
    display:block; text-align:center;
    margin-top:25px; background:#2ecc71;
    color:#002B7F; font-weight:600;
    padding:12px 25px; border-radius:10px;
    text-decoration:none; width:240px;
    transition:0.3s; margin-left:auto; margin-right:auto;
  }
  .back:hover { background:#fff; color:#002B7F; transform:translateY(-2px); box-shadow:0 6px 18px rgba(255,255,255,0.25); }

  @keyframes fadeIn {
    from { opacity:0; transform:translateY(10px); }
    to { opacity:1; transform:translateY(0); }
  }
</style>
<script>
  function addSubjectField(value="") {
    const container = document.getElementById('subjects-container');
    const div = document.createElement('div');
    div.classList.add('subjects-list');
    div.innerHTML = `
      <input type="text" name="subjects[]" value="${value}" placeholder="Enter subject" required>
      <button type="button" onclick="this.parentElement.remove()">❌</button>
    `;
    container.appendChild(div);
  }
</script>
</head>
<body>
  <div class="profile-box">
    <h2>👨‍🏫 Tutor Profile</h2>
    <?php if ($message) echo $message; ?>

    <div class="info-panel">
      <img src="../uploads/profile_pics/<?php echo $user['profile_pic'] ?: 'default.png'; ?>" alt="Profile Picture">
      <h3><?= htmlspecialchars($user['first_name'] . " " . $user['last_name']); ?></h3>
      <p>📧 <?= htmlspecialchars($user['email']); ?></p>
      <p>📞 <?= htmlspecialchars($user['phone']); ?></p>
      <p>🎓 <?= htmlspecialchars($user['degree_programme']); ?> - Year <?= htmlspecialchars($user['year_of_study']); ?></p>
      <p>📚 Subjects: <?= htmlspecialchars(implode(", ", $tutorSubjects) ?: "None listed"); ?></p>
    </div>

    <div class="section">
      <h3>📝 Update Profile Information</h3>
      <form method="POST" enctype="multipart/form-data">
        <label>First Name</label>
        <input type="text" name="first_name" value="<?= htmlspecialchars($user['first_name']); ?>" required>

        <label>Last Name</label>
        <input type="text" name="last_name" value="<?= htmlspecialchars($user['last_name']); ?>" required>

        <label>Email</label>
        <input type="email" name="email" value="<?= htmlspecialchars($user['email']); ?>" required>

        <label>Phone Number</label>
        <input type="text" name="phone" value="<?= htmlspecialchars($user['phone'] ?? ''); ?>" required>

        <label>Year of Study</label>
        <select name="year_of_study" required>
          <option value="">-- Select Year --</option>
          <?php for ($i=1;$i<=4;$i++): ?>
            <option value="<?= $i; ?>" <?= $user['year_of_study']==$i ? "selected" : ""; ?>>Year <?= $i; ?></option>
          <?php endfor; ?>
        </select>

        <label>Degree Programme</label>
        <input type="text" name="degree_programme" value="<?= htmlspecialchars($user['degree_programme']); ?>" required>

        <label>Subjects You Can Teach</label>
        <div id="subjects-container"></div>
        <button type="button" class="add-btn" onclick="addSubjectField()">+ Add Subject</button>

        <label>Profile Picture</label>
        <input type="file" name="profile_pic" accept="image/png, image/jpeg">

        <button type="submit" name="update_profile">💾 Update Profile</button>
      </form>
    </div>

    <div class="section">
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
    </div>

    <a href="../dashboard/tutor.php" class="back">⬅ Back to Dashboard</a>
  </div>

  <script>
    const existingSubjects = <?php echo json_encode($tutorSubjects); ?>;
    existingSubjects.forEach(sub => addSubjectField(sub));
  </script>
</body>
</html>
