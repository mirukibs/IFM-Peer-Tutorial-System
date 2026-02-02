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

// Mark all as read
if (isset($_GET['mark_read'])) {
    $conn->query("UPDATE notifications SET is_read=1 WHERE user_id=$uid");
    header("Location: student.php");
    exit;
}

// Stats
$upcomingCount = $conn->query("SELECT COUNT(*) as c FROM (
    SELECT s.id FROM session_registrations r
    JOIN sessions s ON r.session_id=s.id
    WHERE r.student_id=$uid AND s.status='accepted' AND s.end_time >= datetime('now')
    UNION
    SELECT s.id FROM sessions s
    WHERE s.learner_id=$uid AND s.status='accepted' AND s.end_time >= datetime('now')
) as combined")->fetch_assoc()['c'];

$completedCount = $conn->query("SELECT COUNT(*) as c FROM (
    SELECT s.id FROM session_registrations r
    JOIN sessions s ON r.session_id=s.id
    WHERE r.student_id=$uid AND (s.status='completed' OR (s.status='accepted' AND s.end_time < datetime('now')))
    UNION
    SELECT s.id FROM sessions s
    WHERE s.learner_id=$uid AND (s.status='completed' OR (s.status='accepted' AND s.end_time < datetime('now')))
) as combined")->fetch_assoc()['c'];

$feedbackCount = $conn->query("SELECT COUNT(*) as c FROM feedback WHERE rater_id=$uid")->fetch_assoc()['c'];
$noteCount = $conn->query("SELECT COUNT(*) as c FROM notifications WHERE user_id=$uid AND is_read=0")->fetch_assoc()['c'];

// Notifications
$notes = $conn->query("SELECT * FROM notifications WHERE user_id=$uid ORDER BY created_at DESC LIMIT 5");

// Feedback
$feedback = $conn->query("SELECT f.stars,f.comment,f.created_at,s.title,u.first_name,u.last_name
FROM feedback f
JOIN sessions s ON f.session_id=s.id
JOIN users u ON s.tutor_id=u.id
WHERE f.rater_id=$uid
ORDER BY f.created_at DESC
LIMIT 5");

// Upcoming Sessions
$sessions = $conn->query("SELECT s.title,s.start_time,s.end_time,u.first_name,u.last_name
FROM session_registrations r
JOIN sessions s ON r.session_id=s.id
JOIN users u ON s.tutor_id=u.id
WHERE r.student_id=$uid AND s.status='accepted' AND s.end_time >= datetime('now')
UNION
SELECT s.title,s.start_time,s.end_time,u.first_name,u.last_name
FROM sessions s
JOIN users u ON s.tutor_id=u.id
WHERE s.learner_id=$uid AND s.status='accepted' AND s.end_time >= datetime('now')
ORDER BY start_time ASC LIMIT 5");

// Completed Sessions
$completed = $conn->query("SELECT s.title,s.end_time,u.first_name,u.last_name
FROM session_registrations r
JOIN sessions s ON r.session_id=s.id
JOIN users u ON s.tutor_id=u.id
WHERE r.student_id=$uid AND (s.status='completed' OR (s.status='accepted' AND s.end_time < datetime('now')))
UNION
SELECT s.title,s.end_time,u.first_name,u.last_name
FROM sessions s
JOIN users u ON s.tutor_id=u.id
WHERE s.learner_id=$uid AND (s.status='completed' OR (s.status='accepted' AND s.end_time < datetime('now')))
ORDER BY end_time DESC LIMIT 5");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>IFM Peer Tutoring - Student Dashboard</title>
<style>
  * { box-sizing: border-box; margin: 0; padding: 0; }
  body {
    font-family: 'Poppins', sans-serif;
    background: #F7F9FC;
    display: flex;
    min-height: 100vh;
  }

  /* Sidebar */
  .sidebar {
    width: 250px;
    background: #002B7F;
    color: white;
    display: flex;
    flex-direction: column;
    padding: 25px 15px;
    position: fixed;
    height: 100vh;
  }
  .sidebar h2 {
    color: #FDB913;
    margin-bottom: 20px;
    font-size: 1.2rem;
    text-align: center;
  }
  .profile {
    text-align: center;
    margin-bottom: 20px;
  }
  .profile img {
    width: 80px; height: 80px; border-radius: 50%; object-fit: cover;
    border: 2px solid #FDB913;
  }
  .profile h3 {
    margin-top: 10px;
    font-size: 1rem;
  }
  .nav-links a {
    display: block;
    color: white;
    padding: 12px 16px;
    border-radius: 8px;
    text-decoration: none;
    margin: 5px 0;
    font-weight: 500;
    transition: 0.3s;
  }
  .nav-links a:hover, .nav-links a.active {
    background: #FDB913;
    color: #002B7F;
  }

  /* Main */
  .main {
    margin-left: 250px;
    padding: 30px;
    flex: 1;
  }
  h1 {
    font-size: 1.8rem;
    color: #002B7F;
    margin-bottom: 25px;
  }

  /* Stats Cards */
  .stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
  }
  .card {
    background: white;
    border-radius: 12px;
    padding: 20px;
    box-shadow: 0 4px 12px rgba(0, 43, 127, 0.08);
    text-align: center;
    transition: 0.3s;
  }
  .card:hover {
    transform: translateY(-3px);
  }
  .card h2 {
    color: #002B7F;
    font-size: 2rem;
    margin-bottom: 5px;
  }
  .card p {
    color: #555;
  }

  /* Content boxes */
  .box {
    background: white;
    border-radius: 12px;
    padding: 20px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.05);
    margin-bottom: 25px;
  }
  .box h3 {
    color: #002B7F;
    margin-bottom: 15px;
  }
  ul { list-style: none; }
  li { margin: 10px 0; color: #333; }
  small { color: #777; }
  a { color: #002B7F; text-decoration: none; font-weight: 500; }
  a:hover { text-decoration: underline; }

  .stars { color: #FDB913; }
  .comment { color: #555; font-style: italic; }

  footer {
    text-align: center;
    padding: 20px;
    background: #002B7F;
    color: white;
    border-radius: 8px;
    margin-top: 30px;
  }

  /* Responsive */
  @media (max-width: 768px) {
    .sidebar {
      width: 200px;
    }
    .main {
      margin-left: 200px;
    }
  }
  @media (max-width: 600px) {
    .sidebar {
      display: none;
    }
    .main {
      margin: 0;
      padding: 20px;
    }
  }
</style>
</head>
<body>

  <!-- Sidebar -->
  <div class="sidebar">
    <h2>🎓 Peer Tutoring</h2>
    <div class="profile">
      <img src="../uploads/profile_pics/<?= !empty($user['profile_pic']) ? $user['profile_pic'] : 'default.png'; ?>" alt="Profile">
      <h3><?= htmlspecialchars($studentName); ?></h3>
    </div>
    <div class="nav-links">
      <a href="../sessions/request.php">📅 Request Session</a>
      <a href="../sessions/view.php">👀 My Sessions</a>
      <a href="../feedback/view_all_student.php">⭐ My Feedback</a>
      <a href="../profile/student.php">👤 My Profile</a>
      <a href="../notifications/view_all.php">🔔 Notifications</a>
      <a href="../auth/logout.php">🚪 Logout</a>
    </div>
  </div>

  <!-- Main Content -->
  <div class="main">
    <h1>Welcome, <?= htmlspecialchars($studentName); ?> 👋</h1>

    <!-- Stats -->
    <div class="stats">
      <div class="card"><h2><?= $upcomingCount; ?></h2><p>Upcoming Sessions</p></div>
      <div class="card"><h2><?= $completedCount; ?></h2><p>Completed Sessions</p></div>
      <div class="card"><h2><?= $feedbackCount; ?></h2><p>Feedback Submitted</p></div>
      <div class="card"><h2><?= $noteCount; ?></h2><p>New Notifications</p></div>
    </div>

    <!-- Notifications -->
    <div class="box">
      <h3>🔔 Recent Notifications</h3>
      <?php if ($notes->num_rows > 0): ?>
        <ul>
          <?php while($n = $notes->fetch_assoc()): ?>
            <li <?php if(!$n['is_read']) echo 'style="font-weight:bold;"'; ?>>
              <?= htmlspecialchars($n['message']); ?>
              <small>(<?= date("d M H:i", strtotime($n['created_at'])); ?>)</small>
            </li>
          <?php endwhile; ?>
        </ul>
        <a href="?mark_read=1">Mark all as read</a> | <a href="../notifications/view_all.php">View All</a>
      <?php else: ?>
        <p>No notifications.</p>
      <?php endif; ?>
    </div>

    <!-- Upcoming Sessions -->
    <div class="box">
      <h3>📅 Upcoming Sessions</h3>
      <?php if ($sessions->num_rows > 0): ?>
        <ul>
          <?php while($s = $sessions->fetch_assoc()): ?>
            <li>
              <b><?= htmlspecialchars($s['title']); ?></b><br>
              Tutor: <?= htmlspecialchars($s['first_name']." ".$s['last_name']); ?><br>
              <small><?= date("d M Y H:i", strtotime($s['start_time'])); ?> → <?= date("d M Y H:i", strtotime($s['end_time'])); ?></small>
            </li>
          <?php endwhile; ?>
        </ul>
      <?php else: ?>
        <p>No upcoming sessions.</p>
      <?php endif; ?>
    </div>

    <!-- Completed Sessions -->
    <div class="box">
      <h3>✅ Recently Completed Sessions</h3>
      <?php if ($completed->num_rows > 0): ?>
        <ul>
          <?php while($c = $completed->fetch_assoc()): ?>
            <li>
              <b><?= htmlspecialchars($c['title']); ?></b><br>
              Tutor: <?= htmlspecialchars($c['first_name']." ".$c['last_name']); ?><br>
              <small>Ended: <?= date("d M Y H:i", strtotime($c['end_time'])); ?></small>
            </li>
          <?php endwhile; ?>
        </ul>
        <a href="../sessions/completed_student.php">📖 View All Completed Sessions</a>
      <?php else: ?>
        <p>No completed sessions yet.</p>
      <?php endif; ?>
    </div>

    <!-- Feedback -->
    <div class="box">
      <h3>⭐ My Recent Feedback</h3>
      <?php if ($feedback->num_rows > 0): ?>
        <ul>
          <?php while($f = $feedback->fetch_assoc()): ?>
            <li>
              On <b><?= htmlspecialchars($f['first_name']." ".$f['last_name']); ?></b> for <i><?= htmlspecialchars($f['title']); ?></i><br>
              <span class="stars"><?= str_repeat("⭐", $f['stars']); ?></span><br>
              <span class="comment"><?= htmlspecialchars($f['comment']); ?></span><br>
              <small><?= date("d M Y H:i", strtotime($f['created_at'])); ?></small>
            </li>
          <?php endwhile; ?>
        </ul>
        <a href="../feedback/view_all_student.php">📖 View All Feedback</a>
      <?php else: ?>
        <p>You haven’t submitted any feedback yet.</p>
      <?php endif; ?>
    </div>

    <footer>© <?= date("Y"); ?> IFM Peer Tutoring System. All rights reserved.</footer>
  </div>
</body>
</html>
