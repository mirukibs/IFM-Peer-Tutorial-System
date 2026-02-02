<?php
session_start();
include("../config/db.php");

// Ensure tutor only
if ($_SESSION['role'] != 'tutor') {
    die("Unauthorized access.");
}

$tutor_id = $_SESSION['user_id'];
$session_id = $_GET['id'] ?? null;

if (!$session_id) {
    die("Session ID missing.");
}

// Verify session belongs to this tutor
$stmt = $conn->prepare("SELECT title, start_time, end_time FROM sessions WHERE id=? AND tutor_id=?");
$stmt->bind_param("ii", $session_id, $tutor_id);
$stmt->execute();
$result = $stmt->get_result();
$session = $result->fetch_assoc();

if (!$session) {
    die("Session not found or not owned by you.");
}

// Fetch registered students
$registrations = $conn->prepare("SELECT u.first_name, u.last_name, u.email, r.registered_at 
                                FROM session_registrations r
                                JOIN users u ON r.student_id = u.id
                                WHERE r.session_id=?");
$registrations->bind_param("i", $session_id);
$registrations->execute();
$students = $registrations->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Registered Students</title>
  <style>
    body { font-family: Arial; margin:20px; background:#f4f6f9; }
    h2 { color:#2c3e50; }
    table { width:100%; border-collapse: collapse; margin-top:20px; background:#fff; }
    table, th, td { border:1px solid #ccc; }
    th, td { padding:10px; text-align:center; }
    th { background:#3498db; color:#fff; }
    .meta { margin:10px 0; }
    a { color:#3498db; text-decoration:none; }
    a:hover { text-decoration:underline; }
  </style>
</head>
<body>
  <h2>👥 Registered Students</h2>
  <p class="meta"><b>Session:</b> <?php echo htmlspecialchars($session['title']); ?></p>
  <p class="meta"><b>Start:</b> <?php echo date("d M Y H:i", strtotime($session['start_time'])); ?> 
     | <b>End:</b> <?php echo date("d M Y H:i", strtotime($session['end_time'])); ?></p>

  <table>
    <tr>
      <th>Student Name</th>
      <th>Email</th>
      <th>Registered At</th>
    </tr>
    <?php if ($students->num_rows > 0): ?>
      <?php while ($s = $students->fetch_assoc()): ?>
        <tr>
          <td><?php echo htmlspecialchars($s['first_name']." ".$s['last_name']); ?></td>
          <td><?php echo htmlspecialchars($s['email']); ?></td>
          <td><?php echo date("d M Y H:i", strtotime($s['registered_at'])); ?></td>
        </tr>
      <?php endwhile; ?>
    <?php else: ?>
      <tr><td colspan="3">No students registered yet.</td></tr>
    <?php endif; ?>
  </table>

  <br>
  <a href="my_sessions.php">⬅ Back to My Sessions</a>
</body>
</html>
