<?php
session_start();
include("../config/db.php");

// Ensure student only
if ($_SESSION['role'] != 'student') {
    die("Unauthorized access.");
}

$student_id = $_SESSION['user_id'];
$msg = "";

// Handle cancellation
if (isset($_GET['cancel'])) {
    $session_id = intval($_GET['cancel']);

    $stmt = $conn->prepare("DELETE FROM session_registrations WHERE session_id=? AND student_id=?");
    $stmt->bind_param("ii", $session_id, $student_id);
    $stmt->execute();

    // Notify tutor
    $getTutor = $conn->prepare("SELECT tutor_id, title FROM sessions WHERE id=?");
    $getTutor->bind_param("i", $session_id);
    $getTutor->execute();
    $tutorData = $getTutor->get_result()->fetch_assoc();

    if ($tutorData) {
        $msgText = "⚠ A student cancelled registration for your session: " . $tutorData['title'];
        $notify = $conn->prepare("INSERT INTO notifications (user_id, message) VALUES (?, ?)");
        $notify->bind_param("is", $tutorData['tutor_id'], $msgText);
        $notify->execute();
    }

    $msg = "✅ You have cancelled your registration.";
}

// Handle feedback submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['feedback_session'])) {
    $session_id = intval($_POST['feedback_session']);
    $stars = intval($_POST['stars']);
    $comment = trim($_POST['comment']);

    $stmt = $conn->prepare("INSERT INTO feedback (session_id, rater_id, stars, comment) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("iiis", $session_id, $student_id, $stars, $comment);
    if ($stmt->execute()) {
        $msg = "✅ Feedback submitted successfully!";
    } else {
        $msg = "Failed to submit feedback.";
    }
}

// Filter: upcoming or past
$filter = $_GET['filter'] ?? 'upcoming'; // default upcoming
$now = date("Y-m-d H:i:s");

$query = "
    SELECT s.id, s.title, s.description, s.start_time, s.end_time, s.capacity, s.is_closed,
           (SELECT COUNT(*) FROM session_registrations WHERE session_id=s.id) AS registered,
           u.first_name, u.last_name 
    FROM session_registrations sr
    JOIN sessions s ON sr.session_id=s.id
    JOIN users u ON s.tutor_id=u.id
    WHERE sr.student_id=?
";
if ($filter === 'upcoming') {
    $query .= " AND s.start_time >= ? ORDER BY s.start_time ASC";
} else {
    $query .= " AND s.start_time < ? ORDER BY s.start_time DESC";
}

$stmt = $conn->prepare($query);
$stmt->bind_param("is", $student_id, $now);
$stmt->execute();
$sessions = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>My Sessions</title>
  <style>
    body { font-family: Arial; background:#f4f6f9; margin:20px; }
    h2 { color:#2c3e50; }
    .card { background:#fff; padding:20px; margin:10px 0; border-radius:10px; box-shadow:0 2px 6px rgba(0,0,0,0.1); }
    .meta { font-size:14px; color:#555; }
    .cancel { color:red; font-weight:bold; text-decoration:none; }
    .cancel:hover { text-decoration:underline; }
    .filters { margin-bottom:20px; }
    .filters a {
      padding:8px 14px;
      background:#3498db;
      color:white;
      border-radius:6px;
      text-decoration:none;
      margin-right:8px;
    }
    .filters a.active { background:#2c3e50; }
    .filters a:hover { background:#2980b9; }
    form.feedback-form { margin-top:15px; }
    form.feedback-form textarea { width:100%; padding:8px; border:1px solid #ccc; border-radius:5px; margin-top:5px; }
    form.feedback-form select, form.feedback-form button { margin-top:8px; padding:8px; }
    form.feedback-form button {
      background:#27ae60; border:none; color:white; border-radius:5px; cursor:pointer;
    }
    form.feedback-form button:hover { background:#219150; }
  </style>
</head>
<body>
  <h2>📖 My Registered Sessions</h2>
  <?php if (!empty($msg)) echo "<p>$msg</p>"; ?>

  <!-- Filters -->
  <div class="filters">
    <a href="?filter=upcoming" class="<?php echo $filter==='upcoming' ? 'active' : ''; ?>">📅 Upcoming</a>
    <a href="?filter=past" class="<?php echo $filter==='past' ? 'active' : ''; ?>">📜 Past</a>
  </div>

  <?php if ($sessions->num_rows > 0): ?>
    <?php while ($s = $sessions->fetch_assoc()): ?>
      <div class="card">
        <h3><?php echo htmlspecialchars($s['title']); ?></h3>
        <p><?php echo htmlspecialchars($s['description']); ?></p>
        <p class="meta">🧑 Tutor: <?php echo htmlspecialchars($s['first_name']." ".$s['last_name']); ?></p>
        <p class="meta">📆 Start: <?php echo date("d M Y H:i", strtotime($s['start_time'])); ?></p>
        <p class="meta">⏰ End: <?php echo date("d M Y H:i", strtotime($s['end_time'])); ?></p>
        <p class="meta">👥 <?php echo $s['registered']." / ".$s['capacity']; ?> students registered</p>
        <p class="meta">Status: <?php echo $s['is_closed'] ? "Closed" : "✅ Open"; ?></p>

        <?php if ($filter === 'upcoming'): ?>
          <a href="?cancel=<?php echo $s['id']; ?>&filter=upcoming" class="cancel">🚫 Cancel Registration</a>
        <?php elseif ($filter === 'past'): ?>
          <!-- Inline feedback form -->
          <form method="POST" class="feedback-form">
            <input type="hidden" name="feedback_session" value="<?php echo $s['id']; ?>">
            <label>⭐ Rating:</label>
            <select name="stars" required>
              <option value="">Select</option>
              <option value="1">1 - Poor</option>
              <option value="2">2 - Fair</option>
              <option value="3">3 - Good</option>
              <option value="4">4 - Very Good</option>
              <option value="5">5 - Excellent</option>
            </select>
            <label>💬 Comment:</label>
            <textarea name="comment" rows="3" placeholder="Write your feedback..."></textarea>
            <button type="submit">Submit Feedback</button>
          </form>
        <?php endif; ?>
      </div>
    <?php endwhile; ?>
  <?php else: ?>
    <p>No <?php echo $filter==='upcoming' ? 'upcoming' : 'past'; ?> sessions found.</p>
  <?php endif; ?>

  <br>
  <a href="../dashboard/student.php">⬅ Back to Dashboard</a>
</body>
</html>
