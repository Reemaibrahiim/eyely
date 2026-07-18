<?php
session_start();
if (!isset($_SESSION['userID'])) {
  header("Location: index.php");
  exit();
}
if ($_SESSION['userType'] !== 'educator') {
  header("Location: learner-dashboard.php");
  exit();
}

// Include database connection
require_once 'db_connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $quizID = $_POST['quizID'] ?? '';
    $questionText = $_POST['questionText'] ?? '';
    $correctAnswer = $_POST['correctAnswer'] ?? '';

    // Validate required fields
    if (empty($quizID) || empty($questionText) || empty($correctAnswer)) {
        header("Location: add-question.php?quizID=" . $quizID . "&error=missing_fields");
        exit();
    }

    try {
        $questionFigureFileName = '';
        
        // Handle image upload if provided
        if (isset($_FILES['questionFigure']) && $_FILES['questionFigure']['error'] === UPLOAD_ERR_OK) {
      $uploadDir = "images/";  
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $newFileName = uniqid() . "_" . basename($_FILES["questionFigure"]["name"]);
        $uploadPath = $uploadDir . $newFileName;

        $imageFileType = strtolower(pathinfo($uploadPath, PATHINFO_EXTENSION));
        $allowedTypes = ['jpg','jpeg','png','gif'];

        if (in_array($imageFileType, $allowedTypes)) {
            if (!move_uploaded_file($_FILES["questionFigure"]["tmp_name"], $uploadPath)) {
                header("Location: add-question.php?quizID=" . $quizID . "&error=upload_failed");
                exit();
            }
        } else {
            header("Location: add-question.php?quizID=" . $quizID . "&error=invalid_file_type");
            exit();
        }

  $questionFigureFileName = $newFileName;

} else {
    $questionFigureFileName = null; // إذا لم يتم رفع صورة
}


        // Insert the new question into database
        $insertQuery = "INSERT INTO QuizQuestion (quizID, question, questionFigureFileName, correctAnswer) 
                       VALUES (?, ?, ?, ?)";
        $stmt = $connection->prepare($insertQuery);
        $stmt->bind_param("isss", $quizID, $questionText, $questionFigureFileName, $correctAnswer);
        
        if ($stmt->execute()) {
            // Redirect to quiz page on success with quiz ID in request
            header("Location: quiz.php?quizID=" . $quizID . "&success=question_added");
            exit();
        } else {
            header("Location: add-question.php?quizID=" . $quizID . "&error=database_error");
            exit();
        }
        
        $stmt->close();

    } catch (Exception $e) {
        error_log("Database error: " . $e->getMessage());
        header("Location: add-question.php?quizID=" . $quizID . "&error=database_error");
        exit();
    }
} else {
    // If not POST request, redirect to dashboard
    header("Location: educator-dashboard.php");

    exit();
}

$connection->close();
?>