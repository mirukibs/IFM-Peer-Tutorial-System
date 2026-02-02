<?php
session_start();
include("../config/db.php");

if (!isset($_SESSION['user_id'])) { 
    die("Unauthorized"); 
}

$uid = $_SESSION['user_id'];
$role = $_SESSION['role'];

// Fetch sessions depending on role
if ($role == 'student') {
    $res = $conn->query("
        SELECT s.*, u.first_name, u.last_name, u.email, u.phone 
        FROM sessions s 
        LEFT JOIN users u ON s.tutor_id=u.id 
        WHERE s.learner_id=$uid
        ORDER BY s.start_time DESC
    ");
} elseif ($role == 'tutor') {
    $res = $conn->query("
        SELECT s.*, u.first_name, u.last_name, u.email, u.phone 
        FROM sessions s 
        LEFT JOIN users u ON s.learner_id=u.id 
        WHERE s.tutor_id=$uid
        ORDER BY s.start_time DESC
    ");
} else {
    $res = $conn->query("
        SELECT s.*, u.first_name, u.last_name, u.email, u.phone 
        FROM sessions s 
        LEFT JOIN users u ON s.learner_id=u.id
        ORDER BY s.start_time DESC
    ");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>My Sessions - IFM Peer Tutoring</title>
<style>
  * { box-sizing: border-box; }
  body {
    font-family: 'Poppins', sans-serif;
    background: linear-gradient(135deg, #002B7F, #0044AA);
    margin: 0;
    padding: 0;
    min-height: 100vh;
    color: #fff;
    display: flex;
    justify-content: center;
    align-items: flex-start;
  }

  .container {
    width: 95%;
    max-width: 1100px;
    background: rgba(255, 255, 255, 0.12);
    backdrop-filter: blur(12px);
    border-radius: 18px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.25);
    padding: 30px 25px 40px;
    margin: 40px 20px;
    border: 1px solid rgba(255,255,255,0.2);
    animation: fadeIn 0.8s ease-in-out;
  }

  h1 {
    text-align: center;
    color: #FDB913;
    font-size: 1.8rem;
    margin-bottom: 20px;
  }

  /* 🔍 Search Bar */
  .search-bar {
    display: flex;
    align-items: center;
    background: rgba(255,255,255,0.15);
    border-radius: 10px;
    margin-bottom: 20px;
    overflow: hidden;
  }
  .search-bar input {
    flex: 1;
    padding: 12px 14px;
    border: none;
    outline: none;
    background: transparent;
    color: #fff;
    font-size: 15px;
  }
  .search-bar button {
    background: #e74c3c;
    border: none;
    padding: 12px 20px;
    color: #fff;
    font-weight: 600;
    cursor: pointer;
    transition: 0.3s;
  }
  .search-bar button:hover {
    background: #c0392b;
  }

  table {
    width: 100%;
    border-collapse: collapse;
    background: rgba(255,255,255,0.15);
    border-radius: 12px;
    overflow: hidden;
  }

  th, td {
    padding: 12px 15px;
    text-align: left;
    color: #fff;
  }

  th {
    background: rgba(0, 0, 0, 0.3);
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
  }

  tr:nth-child(even) {
    background: rgba(255, 255, 255, 0.05);
  }

  tr:hover {
    background: rgba(255, 255, 255, 0.2);
    transition: 0.3s;
  }

  .status {
    font-weight: bold;
    text-transform: capitalize;
    border-radius: 6px;
    padding: 4px 8px;
    display: inline-block;
  }

  .requested { background: rgba(253,185,19,0.25); color: #FDB913; }
  .accepted { background: rgba(52,152,219,0.25); color: #74b9ff; }
  .completed { background: rgba(46,204,113,0.25); color: #2ecc71; }
  .cancelled { background: rgba(231,76,60,0.25); color: #e74c3c; }
  .rejected { background: rgba(231,76,60,0.25); color: #e74c3c; }

  .btn {
    display: inline-block;
    margin: 4px 3px;
    padding: 8px 14px;
    border-radius: 8px;
    text-decoration: none;
    font-size: 0.85rem;
    font-weight: 600;
    color: #002B7F;
    background: #FDB913;
    cursor: pointer;
    transition: all 0.3s;
  }
  .btn:hover {
    background: #fff;
    color: #002B7F;
    transform: translateY(-2px);
    box-shadow: 0 6px 12px rgba(255,255,255,0.25);
  }
  .btn.cancel { background: #e74c3c; color: #fff; }
  .btn.join { background: #27ae60; color: #fff; }
  .btn.finish { background: #8e44ad; color: #fff; }
  .btn.feedback { background: #f39c12; color: #fff; }
  .btn.email { background: #8e44ad; color: #fff; }
  .btn.call { background: #16a085; color: #fff; }
  .btn.whatsapp { background: #25d366; color: #fff; }

  .back-btn {
    display: block;
    text-align: center;
    margin: 25px auto 0;
    background: #FDB913;
    color: #002B7F;
    font-weight: 600;
    padding: 12px 25px;
    border-radius: 10px;
    text-decoration: none;
    width: 240px;
    transition: 0.3s;
  }

  .back-btn:hover {
    background: #fff;
    color: #002B7F;
    transform: translateY(-2px);
  }

  @media (max-width: 800px) {
    table, thead, tbody, th, td, tr { display: block; }
    th { display: none; }
    tr { margin-bottom: 20px; background: rgba(255,255,255,0.1); padding: 15px; border-radius: 10px; }
    td::before {
      content: attr(data-label);
      font-weight: bold;
      color: #FDB913;
      display: block;
      margin-bottom: 4px;
    }
  }

  @keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
  }
</style>
<script>
async function handleAction(action, id, el) {
  if (!confirm("Are you sure you want to proceed?")) return;
  try {
    const response = await fetch(`${action}.php?id=${id}`, { method: "GET" });
    const data = await response.json().catch(() => ({}));
    if (response.ok) {
      const row = el.closest("tr");
      const statusCell = row.querySelector(".status");

      if (action === "accept") {
        statusCell.textContent = "Accepted";
        statusCell.className = "status accepted";
      } else if (action === "reject") {
        statusCell.textContent = "Rejected";
        statusCell.className = "status cancelled";
      } else if (action === "finish") {
        statusCell.textContent = "Completed";
        statusCell.className = "status completed";
      } else if (action === "cancel") {
        statusCell.textContent = "Cancelled";
        statusCell.className = "status cancelled";
      }
      alert(data.message || "✅ Action successful!");
    } else {
      alert("Something went wrong.");
    }
  } catch (err) {
    alert("⚠ Error: " + err.message);
  }
}
</script>
</head>
<body>

<div class="container">
  <h1>📅 My Sessions</h1>

  <!-- 🔍 Search bar -->
  <div class="search-bar">
    <input type="text" id="sessionSearch" placeholder="Search by title, description, or status...">
    <button onclick="document.getElementById('sessionSearch').value=''; filterSessions();">Clear</button>
  </div>

  <table id="sessionsTable">
    <tr>
      <th>ID</th>
      <th>Title</th>
      <th>Description</th>
      <th>Start</th>
      <th>End</th>
      <th>Status</th>
      <?php if ($role == 'student'): ?><th>Tutor Details</th><?php elseif ($role == 'tutor'): ?><th>Student Details</th><?php endif; ?>
      <th>Actions</th>
    </tr>

    <?php if ($res->num_rows > 0): ?>
      <?php while ($s = $res->fetch_assoc()): ?>
        <tr>
          <td data-label="ID"><?= $s['id']; ?></td>
          <td data-label="Title"><?= htmlspecialchars($s['title']); ?></td>
          <td data-label="Description"><?= htmlspecialchars($s['description']); ?></td>
          <td data-label="Start"><?= date("d M Y H:i", strtotime($s['start_time'])); ?></td>
          <td data-label="End"><?= date("d M Y H:i", strtotime($s['end_time'])); ?></td>
          <td class="status <?= $s['status']; ?>" data-label="Status"><?= ucfirst($s['status']); ?></td>
          <td data-label="Contact">
            <?php if ($s['status'] == 'accepted' || $s['status'] == 'completed'): ?>
              👤 <?= htmlspecialchars($s['first_name']." ".$s['last_name']); ?><br>
              📧 <?= htmlspecialchars($s['email']); ?><br>
              📞 <?= htmlspecialchars($s['phone']); ?><br>
              <?php
                $rawPhone = preg_replace('/\D/', '', $s['phone']);
                $waPhone = (strpos($rawPhone, "255") !== 0) ? "255".$rawPhone : $rawPhone;
              ?>
              <a href="mailto:<?= htmlspecialchars($s['email']); ?>" class="btn email">✉ Email</a>
              <a href="tel:<?= htmlspecialchars($s['phone']); ?>" class="btn call">📞 Call</a>
              <a href="https://wa.me/<?= $waPhone; ?>" target="_blank" class="btn whatsapp">💬 WhatsApp</a>
            <?php else: ?>
              <em>Details available once accepted</em>
            <?php endif; ?>
          </td>
          <td data-label="Actions">
            <?php if ($role == 'student'): ?>
              <?php if (in_array($s['status'], ['requested','accepted'])): ?>
                <button class="btn cancel" onclick="handleAction('cancel', <?= $s['id']; ?>, this)">Cancel</button>
              <?php endif; ?>
              <?php if ($s['status'] == 'completed'): ?>
                <a href="../feedback/submit.php?session_id=<?= $s['id']; ?>" class="btn feedback">Give Feedback</a>
              <?php endif; ?>
            <?php elseif ($role == 'tutor'): ?>
              <?php if ($s['status'] == 'requested'): ?>
                <button class="btn join" onclick="handleAction('accept', <?= $s['id']; ?>, this)">Accept</button>
                <button class="btn cancel" onclick="handleAction('reject', <?= $s['id']; ?>, this)">Reject</button>
              <?php elseif ($s['status'] == 'accepted'): ?>
                <button class="btn finish" onclick="handleAction('finish', <?= $s['id']; ?>, this)">Finish</button>
              <?php endif; ?>
            <?php endif; ?>
          </td>
        </tr>
      <?php endwhile; ?>
    <?php else: ?>
      <tr><td colspan="9" style="text-align:center;">No sessions found.</td></tr>
    <?php endif; ?>
  </table>

  <?php if ($role == 'student'): ?>
    <a href="../dashboard/student.php" class="back-btn">⬅ Back to Student Dashboard</a>
  <?php elseif ($role == 'tutor'): ?>
    <a href="../dashboard/tutor.php" class="back-btn">⬅ Back to Tutor Dashboard</a>
  <?php elseif ($role == 'admin'): ?>
    <a href="../dashboard/admin.php" class="back-btn">⬅ Back to Admin Dashboard</a>
  <?php endif; ?>
</div>

<script>
// 🔍 Client-side table filter
function filterSessions() {
  const input = document.getElementById("sessionSearch");
  const filter = input.value.toLowerCase();
  const rows = document.querySelectorAll("#sessionsTable tr:not(:first-child)");

  rows.forEach(row => {
    const text = row.textContent.toLowerCase();
    row.style.display = text.includes(filter) ? "" : "none";
  });
}
document.getElementById("sessionSearch").addEventListener("input", filterSessions);
</script>

</body>
</html>
