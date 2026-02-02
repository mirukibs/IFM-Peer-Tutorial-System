<?php
session_start();
include("../config/db.php");

// Ensure student only
if ($_SESSION['role'] != 'student') {
    die("Unauthorized access.");
}

$student_id = $_SESSION['user_id'];
$msg = "";

// Handle registration
if (isset($_GET['join'])) {
    $session_id = intval($_GET['join']);

    $check = $conn->prepare("SELECT * FROM session_registrations WHERE session_id=? AND student_id=?");
    $check->bind_param("ii", $session_id, $student_id);
    $check->execute();
    $res = $check->get_result();

    if ($res->num_rows == 0) {
        // Insert registration
        $stmt = $conn->prepare("INSERT INTO session_registrations (session_id, student_id) VALUES (?,?)");
        $stmt->bind_param("ii", $session_id, $student_id);
        $stmt->execute();

        // Fetch session details for notification
        $sinfo = $conn->prepare("SELECT title, tutor_id FROM sessions WHERE id=?");
        $sinfo->bind_param("i", $session_id);
        $sinfo->execute();
        $session = $sinfo->get_result()->fetch_assoc();

        // Notify tutor
        if ($session) {
            $notify = $conn->prepare("INSERT INTO notifications (user_id, message) VALUES (?, ?)");
            $nmsg = "📢 A student registered for your session: " . $session['title'];
            $notify->bind_param("is", $session['tutor_id'], $nmsg);
            $notify->execute();
        }

        $msg = "✅ Registered successfully!";
    } else {
        $msg = "⚠ Already registered.";
    }
}

// Fetch sessions
$sessions = $conn->query("SELECT s.id, s.title, s.description, s.start_time, s.end_time, s.capacity, 
                                 (SELECT COUNT(*) FROM session_registrations WHERE session_id=s.id) AS registered,
                                 u.first_name, u.last_name 
                          FROM sessions s 
                          JOIN users u ON s.tutor_id=u.id 
                          ORDER BY s.start_time ASC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Available Sessions</title>
  <style>
    body { font-family: Arial; background:#f4f6f9; margin:20px; }
    h2 { color:#2c3e50; }
    .card { background:#fff; padding:20px; margin:10px 0; border-radius:10px; box-shadow:0 2px 6px rgba(0,0,0,0.1); }
    .meta { font-size:14px; color:#555; }
    a.join { color:#27ae60; font-weight:bold; }
  </style>
</head>
<body>
  <h2>📅 Available Sessions</h2>
  <?php if (!empty($msg)) echo "<p>$msg</p>"; ?>

  <?php while ($s = $sessions->fetch_assoc()): ?>
    <div class="card">
      <h3><?php echo htmlspecialchars($s['title']); ?></h3>
      <p><?php echo htmlspecialchars($s['description']); ?></p>
      <p class="meta">🧑 Tutor: <?php echo htmlspecialchars($s['first_name']." ".$s['last_name']); ?></p>
      <p class="meta">📆 Start: <?php echo date("d M Y H:i", strtotime($s['start_time'])); ?></p>
      <p class="meta">⏰ End: <?php echo date("d M Y H:i", strtotime($s['end_time'])); ?></p>
      <p class="meta">👥 <?php echo $s['registered']." / ".$s['capacity']; ?> students registered</p>
      <?php if ($s['registered'] < $s['capacity']): ?>
        <a href="?join=<?php echo $s['id']; ?>" class="join">➡ Join Session</a>
      <?php else: ?>
        <p style="color:red;">Session full</p>
      <?php endif; ?>
    </div>
  <?php endwhile; ?>
  
  <br>
  <a href="../dashboard/student.php">⬅ Back to Dashboard</a>
</body>
</html>
