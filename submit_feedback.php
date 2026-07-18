<?php
session_start();
require_once 'db_connect.php'; 


if ($_SERVER['REQUEST_METHOD'] !== 'POST' || $_SESSION['userType'] !== 'learner') {
    header("Location: learner_dashboard.php");
    exit();
}


$quizID = $_POST['quizID'] ?? null;
$rating = $_POST['rating'] ?? null;
$comments = $_POST['comments'] ?? null;


if (empty($quizID) || empty($rating)) {
    die("Error: Rating must be provided.");
}

if (!$connection) {
    die("Database connection failed.");
}


$sql = "INSERT INTO QuizFeedback (quizID, rating, comments, date) VALUES (?, ?, ?, NOW())";
$stmt = $connection->prepare($sql);

$stmt->bind_param("iis", $quizID, $rating, $comments); 

if ($stmt->execute()) {
    
    header("Location: learner-dashboard.php?feedback=success");
} else {
 
    die("Error inserting feedback: " . $connection->error);
}

$stmt->close();
$connection->close();
exit();
?>