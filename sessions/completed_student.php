<?php
session_start();
if ($_SESSION['role'] != 'student') { die("Unauthorized"); }

include("../config/db.php");

$uid = $_SESSION['user_id'];

// Student info
$stmt = $conn->prepare("SELECT first_name,last_name,profile_pic FROM users WHERE id=?");
$stmt->bind_param("i", $uid);
$stmt->execute();
$res = $stmt->get_result();
$user = $res->fetch_assoc();
$studentName = $user ? $user['first_name']." ".$user['last_name'] : "Student";

// ✅ Completed sessions (union of registered + learner_id)
$completed = $conn->query("
    SELECT s.title,s.start_time,s.end_time,u.first_name,u.last_name
    FROM session_registrations r
    JOIN sessions s ON r.session_id=s.id
    JOIN users u ON s.tutor_id=u.id
    WHERE r.student_id=$uid 
      AND (s.status='completed' OR (s.status='accepted' AND s.end_time < NOW()))
    UNION
    SELECT s.title,s.start_time,s.end_time,u.first_name,u.last_name
    FROM sessions s
    JOIN users u ON s.tutor_id=u.id
    WHERE s.learner_id=$uid 
      AND (s.status='completed' OR (s.status='accepted' AND s.end_time < NOW()))
    ORDER BY end_time DESC
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Completed Sessions - Peer Tutoring System</title>
  <style>
    body { font-family: Arial, sans-serif; background:#f4f6f9; margin:0; padding:0; }
    .container { width: 90%; margin:30px auto; }
    h1 { text-align:center; color:#2c3e50; margin-bottom:20px; }
    table { width:100%; border-collapse:collapse; background:#fff; border-radius:10px; overflow:hidden; box-shadow:0 2px 6px rgba(0,0,0,0.1); }
    th, td { padding:12px 15px; text-align:left; border-bottom:1px solid #eee; }
    th { background:#2c3e50; color:white; }
    tr:hover { background:#f9f9f9; }
    .back-btn { display:inline-block; margin:20px auto; padding:12px 20px; background:#3498db; color:white; text-decoration:none; border-radius:6px; font-weight:bold; }
    .back-btn:hover { background:#2980b9; }
    footer { text-align:center; padding:15px; background:#2c3e50; color:white; margin-top:30px; border-radius:8px 8px 0 0; }
  </style>
</head>
<body>
  <div class="container">
    <h1>✅ Completed Sessions</h1>

    <?php if ($completed->num_rows > 0): ?>
      <table>
        <thead>
          <tr>
            <th>Session Title</th>
            <th>Tutor</th>
            <th>Start Time</th>
            <th>End Time</th>
          </tr>
        </thead>
        <tbody>
          <?php while($c = $completed->fetch_assoc()): ?>
            <tr>
              <td><?= htmlspecialchars($c['title']); ?></td>
              <td><?= htmlspecialchars($c['first_name']." ".$c['last_name']); ?></td>
              <td><?= date("d M Y H:i", strtotime($c['start_time'])); ?></td>
              <td><?= date("d M Y H:i", strtotime($c['end_time'])); ?></td>
            </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
    <?php else: ?>
      <p style="text-align:center;">No completed sessions found.</p>
    <?php endif; ?>

    <div style="text-align:center;">
      <a href="../dashboard/student.php" class="back-btn">⬅ Back to Dashboard</a>
    </div>
  </div>

  <footer>
    © <?= date("Y"); ?> Peer Tutoring System. All rights reserved.
  </footer>
</body>
</html>
