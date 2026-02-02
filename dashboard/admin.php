<?php
session_start();
if ($_SESSION['role'] != 'admin') { die("Unauthorized"); }
include("../config/db.php");

/* ==============================
   HANDLE ACCOUNT ACTIONS
   ============================== */

// Suspend
if (isset($_GET['suspend'])) {
    $id = intval($_GET['suspend']);
    $stmt = $conn->prepare("UPDATE users SET status='suspended' WHERE id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    header("Location: admin.php");
    exit;
}

// Unsuspend
if (isset($_GET['unsuspend'])) {
    $id = intval($_GET['unsuspend']);
    $stmt = $conn->prepare("UPDATE users SET status='active' WHERE id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    header("Location: admin.php");
    exit;
}

// Deactivate with reason
if (isset($_POST['deactivate_id']) && !empty($_POST['reason'])) {
    $id = intval($_POST['deactivate_id']);
    $reason = trim($_POST['reason']);
    $stmt = $conn->prepare("UPDATE users SET status='deactivated', deactivation_reason=? WHERE id=?");
    $stmt->bind_param("si", $reason, $id);
    $stmt->execute();
    header("Location: admin.php");
    exit;
}

// Reactivate
if (isset($_GET['reactivate'])) {
    $id = intval($_GET['reactivate']);
    $stmt = $conn->prepare("UPDATE users SET status='active', deactivation_reason=NULL WHERE id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    header("Location: admin.php");
    exit;
}

// Permanent delete
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $stmt = $conn->prepare("DELETE FROM users WHERE id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    header("Location: admin.php");
    exit;
}

/* ==============================
   FETCH DATA
   ============================== */

$search = isset($_GET['search']) ? trim($_GET['search']) : "";
$where = $search ? "WHERE u.first_name LIKE '%$search%' OR u.last_name LIKE '%$search%' OR u.email LIKE '%$search%'" : "";

$users = $conn->query("
  SELECT u.id, u.first_name, u.last_name, u.email, u.status, u.deactivation_reason,
         GROUP_CONCAT(ur.role, ', ') AS roles
  FROM users u
  LEFT JOIN user_roles ur ON u.id = ur.user_id
  $where
  GROUP BY u.id
  ORDER BY u.status DESC, u.id ASC
");

$feedback = $conn->query("
  SELECT f.id, f.stars, f.comment, f.created_at,
         t.first_name AS tutor_first, t.last_name AS tutor_last,
         s.first_name AS student_first, s.last_name AS student_last
  FROM feedback f
  JOIN sessions se ON f.session_id = se.id
  JOIN users t ON se.tutor_id = t.id
  JOIN users s ON f.rater_id = s.id
  ORDER BY f.created_at DESC
");

$avgRatings = $conn->query("
  SELECT t.id, t.first_name, t.last_name, 
         ROUND(AVG(f.stars),1) AS avg_rating,
         COUNT(f.id) AS total_reviews
  FROM feedback f
  JOIN sessions se ON f.session_id = se.id
  JOIN users t ON se.tutor_id = t.id
  GROUP BY t.id
  ORDER BY avg_rating DESC
");

$user_id = $_SESSION['user_id'];
$notes = $conn->query("SELECT * FROM notifications WHERE user_id=$user_id ORDER BY created_at DESC LIMIT 5");

/* ==============================
   HANDLE AJAX REQUESTS
   ============================== */
if (isset($_GET['ajax'])) {
    ob_clean();
    $search = isset($_GET['search']) ? trim($_GET['search']) : "";
    $where = $search ? "WHERE u.first_name LIKE '%$search%' OR u.last_name LIKE '%$search%' OR u.email LIKE '%$search%'" : "";
    $users = $conn->query("
      SELECT u.id, u.first_name, u.last_name, u.email, u.status, u.deactivation_reason,
             GROUP_CONCAT(ur.role, ', ') AS roles
      FROM users u
      LEFT JOIN user_roles ur ON u.id = ur.user_id
      $where
      GROUP BY u.id
      ORDER BY u.status DESC, u.id ASC
    ");

    echo "<table><tr><th>ID</th><th>Name</th><th>Email</th><th>Roles</th><th>Status</th><th>Reason</th><th>Action</th></tr>";
    if ($users->num_rows > 0) {
        while ($u = $users->fetch_assoc()) {
            echo "<tr>
                <td>{$u['id']}</td>
                <td>".htmlspecialchars($u['first_name']." ".$u['last_name'])."</td>
                <td>".htmlspecialchars($u['email'])."</td>
                <td>".htmlspecialchars($u['roles'] ?: '—')."</td>
                <td>".ucfirst($u['status'])."</td>
                <td>".htmlspecialchars($u['deactivation_reason'] ?? '')."</td>
                <td>";

            if ($u['status'] == "active") {
                echo "<a class='btn suspend' href='?suspend={$u['id']}'>Suspend</a>
                      <form method='POST' style='display:inline;'>
                        <input type='hidden' name='deactivate_id' value='{$u['id']}'>
                        <input type='text' name='reason' placeholder='Reason' required style='padding:3px; font-size:12px;'>
                        <button type='submit' class='btn suspend'>Deactivate</button>
                      </form>";
            } elseif ($u['status'] == "suspended") {
                echo "<a class='btn unsuspend' href='?unsuspend={$u['id']}'>Unsuspend</a>";
            } elseif ($u['status'] == "deactivated") {
                echo "<a class='btn unsuspend' href='?reactivate={$u['id']}'>Reactivate</a>";
            }

            echo "<a class='btn delete' href='?delete={$u['id']}' onclick=\"return confirm('⚠️ Delete permanently?');\">Delete</a>
                </td></tr>";
        }
    } else {
        echo "<tr><td colspan='7' style='text-align:center;'>No users found.</td></tr>";
    }
    echo "</table>";
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>🟥 Admin Dashboard - IFM Peer Tutoring</title>
<style>
  * { box-sizing: border-box; }
  body {
    font-family: 'Poppins', sans-serif;
    background: linear-gradient(135deg, #002B7F, #0044AA);
    margin: 0;
    display: flex;
    color: #fff;
  }

  /* Sidebar */
  .sidebar {
    width: 230px;
    background: rgba(0,0,0,0.3);
    backdrop-filter: blur(10px);
    height: 100vh;
    padding: 25px 15px;
    position: fixed;
    left: 0;
    top: 0;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    border-right: 1px solid rgba(255,255,255,0.1);
  }
  .sidebar h2 { color: #FDB913; text-align: center; margin-bottom: 20px; }
  .nav a {
    display: block; padding: 12px 15px; margin-bottom: 8px; text-decoration: none;
    border-radius: 8px; color: #fff; transition: 0.3s; font-weight: 500;
  }
  .nav a:hover, .nav a.active { background: #e74c3c; transform: translateX(4px); }
  .sidebar-footer { text-align: center; }
  .sidebar-footer a {
    display: inline-block; color: #e74c3c; font-weight: bold; text-decoration: none;
  }
  .sidebar-footer a:hover { color: #FDB913; }

  /* Main */
  .main { margin-left: 240px; flex: 1; padding: 30px; }
  h1 { color: #e74c3c; margin-bottom: 10px; }
  h2 { color: #FDB913; border-bottom: 2px solid rgba(255,255,255,0.2); padding-bottom: 6px; margin-bottom: 15px; }

  /* Search */
  .search-bar { display: flex; margin-bottom: 20px; background: rgba(255,255,255,0.15); border-radius: 10px; overflow: hidden; }
  .search-bar input { flex: 1; padding: 12px 14px; border: none; background: transparent; color: #fff; font-size: 15px; }
  .search-bar button { background: #e74c3c; border: none; padding: 12px 20px; color: #fff; cursor: pointer; font-weight: 600; }
  .search-bar button:hover { background: #c0392b; }

  /* Tables */
  table { width: 100%; border-collapse: collapse; background: rgba(255,255,255,0.1); border-radius: 12px; overflow: hidden; margin-bottom: 25px; }
  th, td { padding: 12px; border-bottom: 1px solid rgba(255,255,255,0.1); color: #fff; text-align: left; }
  th { background: rgba(0,0,0,0.3); font-weight: 600; }
  tr:hover { background: rgba(255,255,255,0.1); }

  .btn { padding: 6px 12px; border-radius: 6px; color: #fff; font-size: 13px; font-weight: 600; text-decoration: none; }
  .suspend { background: #e74c3c; }
  .unsuspend { background: #27ae60; }
  .delete { background: #8e44ad; }
  .btn:hover { opacity: 0.85; }
  .stars { color: #FDB913; }
  .comment { font-style: italic; color: #eee; }
  .reason { font-size: 12px; color: #ccc; }

  #userTable { transition: opacity 0.2s ease-in-out; }
  #userTable.loading { opacity: 0.5; }
</style>
</head>
<body>
  <div class="sidebar">
    <div>
      <h2>IFM Admin</h2>
      <div class="nav">
        <a href="#" class="active">📊 Dashboard</a>
        <a href="#users">👥 Manage Users</a>
        <a href="#ratings">⭐ Tutor Ratings</a>
        <a href="#feedback">💬 Feedback</a>
        <a href="admin_sessions.php">📅 Sessions</a>
        <a href="#notes">🔔 Notifications</a>
      </div>
    </div>
    <div class="sidebar-footer">
      <a href="../auth/logout.php">🚪 Logout</a>
    </div>
  </div>

  <div class="main">
    <h1>👨‍💼 Admin Dashboard</h1>

    <!-- 🔍 Live Search -->
    <div class="search-bar">
      <input type="text" id="liveSearch" placeholder="Search users, tutors, or emails...">
      <button type="button" id="refreshBtn">🔄 Refresh</button>
    </div>

    <!-- 🔔 Notifications -->
    <section id="notes">
      <h2>🔔 Notifications</h2>
      <?php if ($notes && $notes->num_rows > 0): ?>
        <ul>
          <?php while($n = $notes->fetch_assoc()): ?>
            <li <?= !$n['is_read'] ? 'style="font-weight:bold;"' : ''; ?>>
              <?= htmlspecialchars($n['message']); ?> 
              <small>(<?= date("d M H:i", strtotime($n['created_at'])); ?>)</small>
            </li>
          <?php endwhile; ?>
        </ul>
      <?php else: ?><p>No notifications.</p><?php endif; ?>
    </section>

    <!-- 👥 User Management -->
    <section id="users">
      <h2>👥 User Management</h2>
      <div id="userTable">
        <table>
          <tr><th>ID</th><th>Name</th><th>Email</th><th>Roles</th><th>Status</th><th>Reason</th><th>Action</th></tr>
          <?php while ($u = $users->fetch_assoc()): ?>
          <tr>
            <td><?= $u['id']; ?></td>
            <td><?= htmlspecialchars($u['first_name']." ".$u['last_name']); ?></td>
            <td><?= htmlspecialchars($u['email']); ?></td>
            <td><?= htmlspecialchars($u['roles'] ?: '—'); ?></td>
            <td><?= ucfirst($u['status']); ?></td>
            <td><?= htmlspecialchars($u['deactivation_reason'] ?? ''); ?></td>
            <td>
              <?php if ($u['status']=="active"): ?>
                <a class="btn suspend" href="?suspend=<?= $u['id']; ?>">Suspend</a>
                <form method="POST" style="display:inline;">
                  <input type="hidden" name="deactivate_id" value="<?= $u['id']; ?>">
                  <input type="text" name="reason" placeholder="Reason" required>
                  <button type="submit" class="btn suspend">Deactivate</button>
                </form>
              <?php elseif ($u['status']=="suspended"): ?>
                <a class="btn unsuspend" href="?unsuspend=<?= $u['id']; ?>">Unsuspend</a>
              <?php elseif ($u['status']=="deactivated"): ?>
                <a class="btn unsuspend" href="?reactivate=<?= $u['id']; ?>">Reactivate</a>
              <?php endif; ?>
              <a class="btn delete" href="?delete=<?= $u['id']; ?>" onclick="return confirm('⚠️ Delete permanently?');">Delete</a>
            </td>
          </tr>
          <?php endwhile; ?>
        </table>
      </div>
    </section>

    <!-- ⭐ Ratings -->
    <section id="ratings">
      <h2>⭐ Tutor Ratings Summary</h2>
      <table>
        <tr><th>Tutor</th><th>Average Rating</th><th>Total Reviews</th></tr>
        <?php if ($avgRatings && $avgRatings->num_rows > 0): ?>
          <?php while ($a = $avgRatings->fetch_assoc()): ?>
          <tr>
            <td><?= htmlspecialchars($a['first_name']." ".$a['last_name']); ?></td>
            <td class="stars"><?= str_repeat("⭐", floor($a['avg_rating'])); ?> (<?= $a['avg_rating']; ?>/5)</td>
            <td><?= $a['total_reviews']; ?></td>
          </tr>
          <?php endwhile; ?>
        <?php else: ?><tr><td colspan="3" style="text-align:center;">No tutor ratings yet.</td></tr><?php endif; ?>
      </table>
    </section>

    <!-- 💬 Feedback -->
    <section id="feedback">
      <h2>💬 Tutor Feedback</h2>
      <table>
        <tr><th>ID</th><th>Tutor</th><th>Student</th><th>Rating</th><th>Comment</th><th>Date</th></tr>
        <?php if ($feedback && $feedback->num_rows > 0): ?>
          <?php while ($f = $feedback->fetch_assoc()): ?>
          <tr>
            <td><?= $f['id']; ?></td>
            <td><?= htmlspecialchars($f['tutor_first']." ".$f['tutor_last']); ?></td>
            <td><?= htmlspecialchars($f['student_first']." ".$f['student_last']); ?></td>
            <td class="stars"><?= str_repeat("⭐", $f['stars']); ?></td>
            <td class="comment"><?= htmlspecialchars($f['comment']); ?></td>
            <td><?= date("d M Y H:i", strtotime($f['created_at'])); ?></td>
          </tr>
          <?php endwhile; ?>
        <?php else: ?><tr><td colspan="6" style="text-align:center;">No feedback available.</td></tr><?php endif; ?>
      </table>
    </section>
  </div>

  <script>
    const searchInput = document.getElementById("liveSearch");
    const userTable = document.getElementById("userTable");
    const refreshBtn = document.getElementById("refreshBtn");

    function fetchUsers(query = "") {
      userTable.classList.add("loading");
      fetch(`?ajax=1&search=${encodeURIComponent(query)}`)
        .then(res => res.text())
        .then(data => {
          userTable.innerHTML = data;
          userTable.classList.remove("loading");
        });
    }

    searchInput.addEventListener("input", function() {
      fetchUsers(this.value.trim());
    });
    refreshBtn.addEventListener("click", () => fetchUsers(searchInput.value.trim()));
    setInterval(() => fetchUsers(searchInput.value.trim()), 10000);
  </script>
</body>
</html>
