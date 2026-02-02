<?php
session_start();
include("../config/db.php");

// Ensure admin only
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    die("Unauthorized access.");
}

// Fetch all users with their roles
$users = $conn->query("
    SELECT u.id, u.first_name, u.last_name, u.email, u.status,
           GROUP_CONCAT(ur.role, ', ') AS roles
    FROM users u
    LEFT JOIN user_roles ur ON u.id = ur.user_id
    GROUP BY u.id
    ORDER BY u.id
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Manage Users - Admin Panel</title>
  <style>
    body { font-family: Arial, sans-serif; background: #f4f6f9; margin: 20px; }
    h2 { color: #2c3e50; }
    table { width: 100%; border-collapse: collapse; margin-top: 20px; }
    table, th, td { border: 1px solid #ccc; }
    th, td { padding: 10px; text-align: center; }
    a { color: #3498db; text-decoration: none; }
    a:hover { text-decoration: underline; }
  </style>
</head>
<body>
  <h2>👥 Manage Users</h2>
  <table>
    <tr>
      <th>ID</th>
      <th>Name</th>
      <th>Email</th>
      <th>Role</th>
      <th>Status</th>
      <th>Action</th>
    </tr>
    <?php while ($u = $users->fetch_assoc()): ?>
    <tr>
      <td><?php echo $u['id']; ?></td>
      <td><?php echo htmlspecialchars($u['first_name']." ".$u['last_name']); ?></td>
      <td><?php echo htmlspecialchars($u['email']); ?></td>
      <td><?php echo htmlspecialchars($u['roles'] ?? 'None'); ?></td>
      <td><?php echo $u['status']; ?></td>
      <td><a href="edit_user.php?id=<?php echo $u['id']; ?>">✏ Edit</a></td>
    </tr>
    <?php endwhile; ?>
  </table>
  <br>
  <a href="../dashboard/admin.php">⬅ Back to Dashboard</a>
</body>
</html>
