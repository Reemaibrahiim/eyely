<?php
session_start();
if (!isset($_SESSION['userID'])) {
  header("Location: login.php");
  exit();
}

require_once 'db_connect.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || $_SESSION['userType'] !== 'educator') {
    header("Location: educator-dashboard.php");
    exit();
}

$questionID = $_POST['questionID'] ?? null;
$questionText = $_POST['questionText'] ?? null;
$correctAnswer = $_POST['correctAnswer'] ?? null;

$questionFigureFileName = null; 

if (empty($questionID) || empty($questionText) || !is_numeric($correctAnswer)) {
    die("Error: Missing or invalid question details (ID, Text, or Answer).");
}

if (!$connection) {
    die("Database connection failed. Please check db_connect.php.");
}

$quizID_to_redirect = null;
$sql_get_quizid = "SELECT quizID FROM QuizQuestion WHERE id = ?";
$stmt_get_quizid = $connection->prepare($sql_get_quizid);
$stmt_get_quizid->bind_param("i", $questionID);
$stmt_get_quizid->execute();
$result_quizid = $stmt_get_quizid->get_result();
if ($result_quizid->num_rows > 0) {
    $row = $result_quizid->fetch_assoc();
    $quizID_to_redirect = $row['quizID'];
}
$stmt_get_quizid->close();

if (isset($_FILES['questionFigure']) && $_FILES['questionFigure']['error'] === UPLOAD_ERR_OK) {
    $uploadDir = 'images/';

    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true); 
    }

    $fileExt = pathinfo($_FILES['questionFigure']['name'], PATHINFO_EXTENSION);
    $newFileName = uniqid() . '.' . $fileExt;
    $targetPath = $uploadDir . $newFileName;

    if (move_uploaded_file($_FILES['questionFigure']['tmp_name'], $targetPath)) {
        $questionFigureFileName = $newFileName; // فقط اسم الملف
    } else {
        die("Error saving the new image file to the server.");
    }
}


if ($questionFigureFileName) {
    $sql = "UPDATE QuizQuestion SET question = ?, questionFigureFileName = ?, correctAnswer = ? WHERE id = ?";
    $stmt = $connection->prepare($sql);
    $stmt->bind_param("sssi", $questionText, $questionFigureFileName, $correctAnswer, $questionID); 
} else {
    $sql = "UPDATE QuizQuestion SET question = ?, correctAnswer = ? WHERE id = ?";
    $stmt = $connection->prepare($sql);
    $stmt->bind_param("ssi", $questionText, $correctAnswer, $questionID); 
}

if ($stmt->execute()) {
    if ($quizID_to_redirect) {
        header("Location: quiz.php?quizID=" . $quizID_to_redirect . "&update=success");
    } else {
        header("Location: educator-dashboard.php?update=success");
    }
} else {
    die("Error updating question: " . $stmt->error);
}

$stmt->close();
$connection->close();
exit();
?>
