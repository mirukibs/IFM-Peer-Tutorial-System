<?php
session_start();
if ($_SESSION['role'] != 'tutor') { die("Unauthorized"); }

include("../config/db.php");

$uid = $_SESSION['user_id'];

// Tutor info
$stmt = $conn->prepare("SELECT first_name,last_name,profile_pic FROM users WHERE id=?");
$stmt->bind_param("i", $uid);
$stmt->execute();
$res = $stmt->get_result();
$user = $res->fetch_assoc();
$tutorName = $user ? $user['first_name']." ".$user['last_name'] : "Tutor";

// Counts
$upcomingCount = $conn->query("SELECT COUNT(*) as c FROM sessions WHERE tutor_id=$uid AND status='accepted' AND start_time >= datetime('now')")->fetch_assoc()['c'];
$completedCount = $conn->query("SELECT COUNT(*) as c FROM sessions WHERE tutor_id=$uid AND status='completed'")->fetch_assoc()['c'];
$feedbackCount  = $conn->query("SELECT COUNT(*) as c FROM feedback f JOIN sessions s ON f.session_id=s.id WHERE s.tutor_id=$uid")->fetch_assoc()['c'];
$noteCount      = $conn->query("SELECT COUNT(*) as c FROM notifications WHERE user_id=$uid AND is_read=0")->fetch_assoc()['c'];

// Notifications preview
$notes = $conn->query("SELECT * FROM notifications WHERE user_id=$uid ORDER BY created_at DESC LIMIT 5");

// Feedback preview
$feedback = $conn->query("
    SELECT f.stars, f.comment, f.created_at, s.title, u.first_name, u.last_name
    FROM feedback f
    JOIN sessions s ON f.session_id=s.id
    JOIN users u ON f.rater_id=u.id
    WHERE s.tutor_id=$uid
    ORDER BY f.created_at DESC
    LIMIT 5
");

// Upcoming preview (only accepted)
$sessions = $conn->query("
    SELECT title, start_time, end_time
    FROM sessions
    WHERE tutor_id=$uid AND status='accepted' AND start_time >= datetime('now')
    ORDER BY start_time ASC
    LIMIT 5
");

// Completed preview
$completed = $conn->query("
    SELECT title, start_time, end_time
    FROM sessions
    WHERE tutor_id=$uid AND status='completed'
    ORDER BY end_time DESC
    LIMIT 5
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Tutor Dashboard - IFM Peer Tutoring</title>
<style>
  * { box-sizing: border-box; }
  body {
    font-family: 'Poppins', sans-serif;
    background: linear-gradient(135deg, #002B7F, #0044AA);
    margin: 0;
    padding: 0;
    display: flex;
    min-height: 100vh;
    color: #fff;
  }

  /* Sidebar */
  .sidebar {
    width: 240px;
    background: rgba(0,0,0,0.25);
    backdrop-filter: blur(10px);
    padding: 30px 20px;
    display: flex;
    flex-direction: column;
    align-items: center;
    border-right: 1px solid rgba(255,255,255,0.2);
  }

  .sidebar img {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    object-fit: cover;
    border: 3px solid #2ecc71;
  }

  .sidebar h3 {
    margin-top: 10px;
    font-size: 1.1rem;
    color: #FDB913;
    text-align: center;
  }

  .nav-links {
    display: flex;
    flex-direction: column;
    gap: 12px;
    margin-top: 30px;
    width: 100%;
  }

  .nav-links a {
    color: #fff;
    text-decoration: none;
    padding: 12px;
    border-radius: 8px;
    transition: 0.3s;
    text-align: center;
    font-weight: 500;
    background: rgba(255,255,255,0.08);
  }

  .nav-links a:hover {
    background: #2ecc71;
    color: #002B7F;
    transform: translateY(-2px);
  }

  .logout {
    margin-top: auto;
    background: rgba(231,76,60,0.2);
  }
  .logout:hover { background: #e74c3c; color: #fff; }

  /* Main Content */
  .main {
    flex: 1;
    padding: 30px;
    overflow-y: auto;
  }

  h1 {
    color: #FDB913;
    font-size: 1.7rem;
    text-align: center;
    margin-bottom: 25px;
  }

  .stats {
    display: flex;
    flex-wrap: wrap;
    gap: 20px;
    justify-content: center;
    margin-bottom: 30px;
  }

  .stat-card {
    flex: 1;
    min-width: 200px;
    background: rgba(255,255,255,0.15);
    border-radius: 12px;
    padding: 25px;
    text-align: center;
    box-shadow: 0 6px 12px rgba(0,0,0,0.2);
    transition: 0.3s;
  }

  .stat-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 18px rgba(0,0,0,0.3);
  }

  .stat-card h2 {
    margin: 0;
    color: #2ecc71;
    font-size: 2.4rem;
  }

  .stat-card p {
    color: #fff;
    margin: 8px 0 0;
  }

  .box {
    background: rgba(255,255,255,0.12);
    backdrop-filter: blur(10px);
    border-radius: 12px;
    padding: 20px 25px;
    margin-bottom: 25px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.25);
  }

  .box h3 {
    color: #FDB913;
    margin-bottom: 10px;
    font-size: 1.2rem;
  }

  ul { list-style: none; padding: 0; margin: 0; }
  li { margin: 10px 0; color: #fff; }

  .stars { color: #FDB913; }
  .comment { font-style: italic; color: #eee; }

  a.link {
    color: #2ecc71;
    text-decoration: none;
    font-weight: 500;
  }

  a.link:hover {
    text-decoration: underline;
  }

  footer {
    text-align: center;
    color: #ddd;
    font-size: 0.9rem;
    margin-top: 30px;
    opacity: 0.9;
  }

  @media (max-width: 850px) {
    body { flex-direction: column; }
    .sidebar { width: 100%; flex-direction: row; justify-content: space-around; border-right: none; border-bottom: 1px solid rgba(255,255,255,0.2); }
    .nav-links { flex-direction: row; flex-wrap: wrap; justify-content: center; margin-top: 10px; }
    .nav-links a { flex: 1; min-width: 120px; font-size: 14px; }
  }
</style>
</head>
<body>

  <div class="sidebar">
    <img src="../uploads/profile_pics/<?= $user['profile_pic'] ?: 'default.png'; ?>" alt="Profile Picture">
    <h3><?= htmlspecialchars($tutorName); ?></h3>
    <div class="nav-links">
      <a href="../sessions/view.php">📅 My Sessions</a>
      <a href="../feedback/view_all_tutor.php">⭐ Feedback</a>
      <a href="../notifications/view_all.php">🔔 Notifications</a>
      <a href="../profile/tutor.php">👤 Profile</a>
      <a href="../auth/logout.php" class="logout">🚪 Logout</a>
    </div>
  </div>

  <div class="main">
    <h1>👋 Welcome, <?= htmlspecialchars($tutorName); ?></h1>

    <!-- Stats -->
    <div class="stats">
      <div class="stat-card"><h2><?= $upcomingCount; ?></h2><p>Upcoming Sessions</p></div>
      <div class="stat-card"><h2><?= $completedCount; ?></h2><p>Completed Sessions</p></div>
      <div class="stat-card"><h2><?= $feedbackCount; ?></h2><p>Total Feedback</p></div>
      <div class="stat-card"><h2><?= $noteCount; ?></h2><p>Unread Notifications</p></div>
    </div>

    <!-- Notifications -->
    <div class="box">
      <h3>🔔 Recent Notifications</h3>
      <?php if ($notes->num_rows > 0): ?>
        <ul>
          <?php while($n = $notes->fetch_assoc()): ?>
            <li <?= !$n['is_read'] ? 'style="font-weight:bold;"' : ''; ?>>
              <?= htmlspecialchars($n['message']); ?> 
              <small>(<?= date("d M H:i", strtotime($n['created_at'])); ?>)</small>
            </li>
          <?php endwhile; ?>
        </ul>
        <a href="../notifications/view_all.php" class="link">📢 View All Notifications</a>
      <?php else: ?>
        <p>No notifications yet.</p>
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
              <small><?= date("d M Y H:i", strtotime($s['start_time'])); ?> → <?= date("H:i", strtotime($s['end_time'])); ?></small>
            </li>
          <?php endwhile; ?>
        </ul>
      <?php else: ?>
        <p>No upcoming sessions scheduled.</p>
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
              <small>Ended: <?= date("d M Y H:i", strtotime($c['end_time'])); ?></small>
            </li>
          <?php endwhile; ?>
        </ul>
        <a href="../sessions/completed.php" class="link">📖 View All Completed Sessions</a>
      <?php else: ?>
        <p>No completed sessions yet.</p>
      <?php endif; ?>
    </div>

    <!-- Feedback -->
    <div class="box">
      <h3>⭐ Recent Feedback</h3>
      <?php if ($feedback->num_rows > 0): ?>
        <ul>
          <?php while($f = $feedback->fetch_assoc()): ?>
            <li>
              <b><?= htmlspecialchars($f['first_name']." ".$f['last_name']); ?></b> on <i><?= htmlspecialchars($f['title']); ?></i><br>
              <span class="stars"><?= str_repeat("⭐", $f['stars']); ?></span><br>
              <span class="comment"><?= htmlspecialchars($f['comment']); ?></span><br>
              <small><?= date("d M Y H:i", strtotime($f['created_at'])); ?></small>
            </li>
          <?php endwhile; ?>
        </ul>
        <a href="../feedback/view_all_tutor.php" class="link">📖 View All Feedback</a>
      <?php else: ?>
        <p>No feedback yet.</p>
      <?php endif; ?>
    </div>

    <footer>© <?= date("Y"); ?> IFM Peer Tutoring System. All rights reserved.</footer>
  </div>
</body>
</html>
