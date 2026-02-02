<?php
session_start();
include("../config/db.php");

if ($_SESSION['role'] != 'tutor') { 
    echo json_encode(["success" => false, "message" => "Unauthorized"]);
    exit;
}

$response = ["success" => false, "message" => ""];

if (isset($_GET['id'])) {
    $sid = intval($_GET['id']);
    $tid = $_SESSION['user_id'];

    // Only complete if currently accepted
    $check = $conn->prepare("SELECT status FROM sessions WHERE id=? AND tutor_id=?");
    $check->bind_param("ii", $sid, $tid);
    $check->execute();
    $result = $check->get_result()->fetch_assoc();

    if ($result && $result['status'] === 'accepted') {
        $stmt = $conn->prepare("UPDATE sessions SET status='completed', end_time=datetime('now') WHERE id=? AND tutor_id=?");
        $stmt->bind_param("ii", $sid, $tid);
        if ($stmt->execute()) {
            // Notify student
            $info = $conn->query("SELECT learner_id, title FROM sessions WHERE id=$sid")->fetch_assoc();
            if ($info) {
                $studentId = $info['learner_id'];
                $title     = $conn->real_escape_string($info['title']);
                $msg = "✅ Your session '$title' has been marked as completed by your tutor.";
                $conn->query("INSERT INTO notifications (user_id,message) VALUES ($studentId,'$msg')");
            }

            $response["success"] = true;
            $response["message"] = "Session marked as completed!";
            $response["newStatus"] = "completed";
        } else {
            $response["message"] = "Database update failed.";
        }
    } else {
        $response["message"] = "Session cannot be completed (must be accepted first).";
    }
}

// Return JSON
header('Content-Type: application/json');
echo json_encode($response);
exit;
