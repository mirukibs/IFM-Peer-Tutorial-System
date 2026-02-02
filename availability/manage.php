<?php
session_start();
include("../config/db.php");

if ($_SESSION['role'] != 'tutor') { die("Unauthorized"); }

$tutor_id = $_SESSION['user_id'];
$message = "";

// Handle add availability
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add'])) {
    $day = $_POST['day_of_week'];
    $start = $_POST['start_time'];
    $end = $_POST['end_time'];

    $stmt = $conn->prepare("INSERT INTO tutor_availability (tutor_id, day_of_week, start_time, end_time) VALUES (?,?,?,?)");
    $stmt->bind_param("isss", $tutor_id, $day, $start, $end);
    if ($stmt->execute()) {
        $message = "✅ Availability added successfully.";
    } else {
        $message = "Error: " . $conn->error;
    }
}

// Handle delete availability
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $stmt = $conn->prepare("DELETE FROM tutor_availability WHERE id=? AND tutor_id=?");
    $stmt->bind_param("ii", $id, $tutor_id);
    $stmt->execute();
    header("Location: manage.php");
    exit;
}

// Fetch availability
$res = $conn->query("SELECT * FROM tutor_availability WHERE tutor_id=$tutor_id ORDER BY 
    CASE day_of_week
        WHEN 'Monday' THEN 1
        WHEN 'Tuesday' THEN 2
        WHEN 'Wednesday' THEN 3
        WHEN 'Thursday' THEN 4
        WHEN 'Friday' THEN 5
        WHEN 'Saturday' THEN 6
        WHEN 'Sunday' THEN 7
    END");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Manage Availability</title>
<style>
    body { font-family: Arial, sans-serif; background:#f4f6f9; margin:0; padding:0; }
    .container { width:70%; margin:40px auto; background:#fff; padding:30px; border-radius:10px; box-shadow:0 4px 12px rgba(0,0,0,0.1); }
    h1 { text-align:center; color:#2c3e50; }
    form { display:flex; gap:15px; margin:20px 0; }
    select, input { padding:10px; border:1px solid #ccc; border-radius:6px; }
    button { padding:10px 18px; background:#3498db; color:#fff; border:none; border-radius:6px; cursor:pointer; }
    button:hover { background:#2980b9; }
    table { width:100%; border-collapse:collapse; margin-top:20px; }
    th,td { padding:10px; border-bottom:1px solid #ddd; text-align:left; }
    th { background:#2c3e50; color:white; }
    .delete { color:#fff; background:#e74c3c; padding:6px 12px; text-decoration:none; border-radius:5px; }
    .delete:hover { background:#c0392b; }
    .back-btn { display:block; margin-top:20px; text-align:center; padding:12px; background:#2c3e50; color:#fff; text-decoration:none; border-radius:6px; font-weight:bold; }
</style>
</head>
<body>
<div class="container">
    <h1>🗓 Manage Availability</h1>
    <?php if($message): ?><p><b><?php echo $message; ?></b></p><?php endif; ?>

    <form method="POST">
        <input type="hidden" name="add" value="1">
        <select name="day_of_week" required>
            <option value="">Select Day</option>
            <option>Monday</option><option>Tuesday</option><option>Wednesday</option>
            <option>Thursday</option><option>Friday</option><option>Saturday</option><option>Sunday</option>
        </select>
        <input type="time" name="start_time" required>
        <input type="time" name="end_time" required>
        <button type="submit">Add</button>
    </form>

    <table>
        <tr><th>Day</th><th>Start</th><th>End</th><th>Action</th></tr>
        <?php while($row=$res->fetch_assoc()): ?>
        <tr>
            <td><?php echo $row['day_of_week']; ?></td>
            <td><?php echo $row['start_time']; ?></td>
            <td><?php echo $row['end_time']; ?></td>
            <td><a class="delete" href="?delete=<?php echo $row['id']; ?>" onclick="return confirm('Delete this slot?');">Delete</a></td>
        </tr>
        <?php endwhile; ?>
    </table>

    <a href="../dashboard/tutor.php" class="back-btn">⬅ Back to Dashboard</a>
</div>
</body>
</html>
