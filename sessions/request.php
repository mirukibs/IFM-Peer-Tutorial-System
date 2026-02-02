<?php
session_start();
include("../config/db.php");

// ✅ Allow both students and tutors to request
if (!in_array($_SESSION['role'], ['student', 'tutor'])) {
    die("Unauthorized");
}

$message = "";
$alertClass = "";
$uid = $_SESSION['user_id'];

// Fetch profile info (safe)
$profileRes = $conn->query("SELECT degree_programme, year_of_study FROM users WHERE id=$uid");
$profile = $profileRes && $profileRes->num_rows > 0 ? $profileRes->fetch_assoc() : ['degree_programme' => '', 'year_of_study' => ''];

$defaultProgramme = $profile['degree_programme'] ?? "";
$defaultYear = $profile['year_of_study'] ?? "";

$selectedProgramme = $_POST['programme'] ?? $defaultProgramme;
$selectedYear = $_POST['year'] ?? $defaultYear;
$selectedSubject = $_POST['subject'] ?? "";
$selectedTutor = $_POST['tutor'] ?? "";
$selectedDate = $_POST['date'] ?? "";
$selectedTime = $_POST['time'] ?? "";

// Handle request submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $programme = $conn->real_escape_string($_POST['programme']);
    $year = intval($_POST['year']);
    $subject = $conn->real_escape_string($_POST['subject']);
    $tutor_id = intval($_POST['tutor']);
    $date = $_POST['date'];
    $time = $_POST['time'];
    $start = "$date $time";
    $end = date("Y-m-d H:i:s", strtotime($start) + 3600);

    if ($tutor_id == $uid) {
        $message = "⚠ You cannot request a session with yourself.";
        $alertClass = "warning";
    } else {
        $tutorRes = $conn->query("
            SELECT id, first_name, last_name 
            FROM users 
            WHERE id=$tutor_id 
              AND status='active'
              AND id IN (SELECT user_id FROM user_roles WHERE role='tutor')
            LIMIT 1
        ");
        $tutor = $tutorRes ? $tutorRes->fetch_assoc() : null;

        if ($tutor) {
            $sql = "INSERT INTO sessions (learner_id,tutor_id,title,start_time,end_time,status)
                    VALUES ($uid,$tutor_id,'$subject','$start','$end','requested')";
            if ($conn->query($sql)) {
                $conn->query("INSERT INTO notifications (user_id,message) 
                              VALUES ($tutor_id,'New session request for $subject')");
                $message = "✅ Session requested successfully with tutor <b>{$tutor['first_name']} {$tutor['last_name']}</b>!";
                $alertClass = "success";
            } else {
                $message = "Error: " . $conn->error;
                $alertClass = "error";
            }
        } else {
            $message = "⚠ Selected tutor not available.";
            $alertClass = "warning";
        }
    }
}

// Fetch programmes
$programmes = $conn->query("SELECT DISTINCT degree_programme FROM tutor_subjects ORDER BY degree_programme");
$years = [1,2,3,4];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Request Tutoring Session - IFM Peer Tutoring</title>
<style>
  * { box-sizing: border-box; }
  body {
    font-family: 'Poppins', sans-serif;
    background: linear-gradient(135deg, #002B7F, #0044AA);
    margin: 0;
    padding: 0;
    display: flex;
    justify-content: center;
    align-items: flex-start;
    min-height: 100vh;
    color: #002B7F;
  }

  .container {
    background: rgba(255, 255, 255, 0.12);
    backdrop-filter: blur(12px);
    border-radius: 18px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.25);
    padding: 40px 35px;
    margin: 60px 20px;
    max-width: 650px;
    width: 100%;
    border: 1px solid rgba(255,255,255,0.2);
    color: #fff;
    animation: fadeIn 0.8s ease-in-out;
  }

  h1 {
    text-align: center;
    color: #FDB913;
    font-size: 1.7rem;
    margin-bottom: 25px;
  }

  form {
    display: flex;
    flex-direction: column;
    gap: 15px;
  }

  label {
    font-weight: 600;
    color: #fff;
    margin-bottom: 4px;
  }

  select, input {
    padding: 12px;
    border-radius: 8px;
    border: none;
    font-size: 15px;
    background: rgba(255,255,255,0.9);
    color: #002B7F;
    outline: none;
    transition: 0.3s;
  }

  select:focus, input:focus {
    box-shadow: 0 0 0 2px #FDB913;
  }

  button {
    padding: 14px;
    background: #FDB913;
    color: #002B7F;
    border: none;
    border-radius: 10px;
    font-size: 16px;
    font-weight: 700;
    cursor: pointer;
    transition: 0.3s;
  }

  button:hover {
    background: #fff;
    color: #002B7F;
    transform: translateY(-2px);
    box-shadow: 0 6px 15px rgba(255,255,255,0.2);
  }

  .alert {
    padding: 15px;
    border-radius: 10px;
    font-weight: 500;
    margin-bottom: 20px;
  }
  .success { background: rgba(46, 204, 113, 0.2); border: 1px solid #2ecc71; color: #d4f8e8; }
  .error { background: rgba(231, 76, 60, 0.2); border: 1px solid #e74c3c; color: #ffd6d6; }
  .warning { background: rgba(241, 196, 15, 0.2); border: 1px solid #f1c40f; color: #fff6c2; }

  .back-btn {
    display: block;
    text-align: center;
    margin-top: 25px;
    background: #FDB913;
    color: #002B7F;
    text-decoration: none;
    padding: 12px 20px;
    border-radius: 10px;
    font-weight: 600;
    transition: 0.3s;
  }

  .back-btn:hover {
    background: #fff;
    color: #002B7F;
    transform: translateY(-2px);
    box-shadow: 0 6px 18px rgba(255,255,255,0.25);
  }

  @keyframes fadeIn {
    from { opacity: 0; transform: translateY(15px); }
    to { opacity: 1; transform: translateY(0); }
  }

  @media (max-width: 480px) {
    .container { padding: 30px 25px; }
    h1 { font-size: 1.4rem; }
  }
</style>

<script>
  async function loadSubjects() {
    const programme = document.getElementById('programme').value;
    const year = document.getElementById('year').value;
    const subjectDropdown = document.getElementById('subject');
    const tutorDropdown = document.getElementById('tutor');

    subjectDropdown.innerHTML = "<option value=''>-- Select Subject --</option>";
    tutorDropdown.innerHTML = "<option value=''>-- Select Tutor --</option>";

    if (programme && year) {
      const response = await fetch(`load_subjects.php?programme=${encodeURIComponent(programme)}&year=${year}`);
      const data = await response.json();
      data.forEach(sub => {
        let option = document.createElement("option");
        option.value = sub;
        option.textContent = sub;
        if (sub === "<?php echo $selectedSubject; ?>") option.selected = true;
        subjectDropdown.appendChild(option);
      });
    }
  }

  async function loadTutors() {
    const programme = document.getElementById('programme').value;
    const year = document.getElementById('year').value;
    const subject = document.getElementById('subject').value;
    const tutorDropdown = document.getElementById('tutor');

    tutorDropdown.innerHTML = "<option value=''>-- Select Tutor --</option>";

    if (programme && year && subject) {
      const response = await fetch(`load_tutors.php?programme=${encodeURIComponent(programme)}&year=${year}&subject=${encodeURIComponent(subject)}`);
      const data = await response.json();
      data.forEach(t => {
        if (t.id != "<?php echo $uid; ?>") {
          let option = document.createElement("option");
          option.value = t.id;
          option.textContent = t.name;
          if (t.id == "<?php echo $selectedTutor; ?>") option.selected = true;
          tutorDropdown.appendChild(option);
        }
      });
    }
  }

  window.onload = function() {
    if ("<?php echo $selectedProgramme; ?>") loadSubjects();
    if ("<?php echo $selectedSubject; ?>") loadTutors();
  }
</script>
</head>
<body>

  <div class="container">
    <h1>📅 Request a Tutoring Session</h1>

    <?php if ($message): ?>
      <div class="alert <?= $alertClass; ?>"><?= $message; ?></div>
    <?php endif; ?>

    <form method="POST">
      <label for="programme">Degree Programme</label>
      <select name="programme" id="programme" required onchange="loadSubjects()">
        <option value="">-- Select Programme --</option>
        <?php if ($programmes): while($p = $programmes->fetch_assoc()): ?>
          <option value="<?= $p['degree_programme']; ?>" <?= $selectedProgramme==$p['degree_programme'] ? "selected" : ""; ?>>
            <?= $p['degree_programme']; ?>
          </option>
        <?php endwhile; endif; ?>
      </select>

      <label for="year">Year of Study</label>
      <select name="year" id="year" required onchange="loadSubjects()">
        <option value="">-- Select Year --</option>
        <?php foreach($years as $y): ?>
          <option value="<?= $y; ?>" <?= $selectedYear==$y ? "selected" : ""; ?>>Year <?= $y; ?></option>
        <?php endforeach; ?>
      </select>

      <label for="subject">Subject</label>
      <select name="subject" id="subject" required onchange="loadTutors()">
        <option value="">-- Select Subject --</option>
      </select>

      <label for="tutor">Available Tutors</label>
      <select name="tutor" id="tutor" required>
        <option value="">-- Select Tutor --</option>
      </select>

      <label for="date">Date</label>
      <input type="date" name="date" id="date" required value="<?= $selectedDate; ?>">

      <label for="time">Start Time</label>
      <input type="time" name="time" id="time" required value="<?= $selectedTime; ?>">

      <button type="submit">Request Session</button>
    </form>

    <?php if ($_SESSION['role'] == 'tutor'): ?>
      <a href="../dashboard/tutor.php" class="back-btn">⬅ Back to Tutor Dashboard</a>
    <?php else: ?>
      <a href="../dashboard/student.php" class="back-btn">⬅ Back to Student Dashboard</a>
    <?php endif; ?>
  </div>
</body>
</html>
