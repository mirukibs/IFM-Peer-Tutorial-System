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

    // Reject session ONLY if it is still requested
    $stmt = $conn->prepare("UPDATE sessions SET status='rejected' 
                            WHERE id=? AND tutor_id=? AND status='requested'");
    $stmt->bind_param("ii", $sid, $tid);
    $stmt->execute();

    if ($stmt->affected_rows > 0) {
        // Notify student
        $info = $conn->query("SELECT learner_id, title FROM sessions WHERE id=$sid")->fetch_assoc();
        if ($info) {
            $studentId = $info['learner_id'];
            $title     = $conn->real_escape_string($info['title']);
            $msg = "Your session request '$title' has been rejected by your tutor.";
            $conn->query("INSERT INTO notifications (user_id,message) VALUES ($studentId,'$msg')");
        }

        $response["success"] = true;
        $response["message"] = "Session rejected successfully.";
        $response["newStatus"] = "rejected";
    } else {
        $response["message"] = "Session cannot be rejected (maybe already accepted or cancelled).";
    }
}

// Return JSON
header('Content-Type: application/json');
echo json_encode($response);
exit;
