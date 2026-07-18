<?php
session_start();
if (!isset($_SESSION['userID']) || $_SESSION['userType'] !== 'learner') {
    header("Location: login.php");
    exit();
}

require_once "db_connect.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_question'])) {
    $learnerID = $_SESSION['userID'];
    $educatorID = filter_var($_POST['educator'] ?? '', FILTER_VALIDATE_INT);
    $topicID = filter_var($_POST['topic'] ?? '', FILTER_VALIDATE_INT);
    $correctAnswer = filter_var($_POST['correct-answer'] ?? '', FILTER_VALIDATE_INT);
    

    if (empty($educatorID) || empty($topicID) || empty($correctAnswer)) {
        $_SESSION['error'] = "All fields are required.";
        header("Location: recommend-question.php");
        exit();
    }
    
    if ($educatorID === false || $topicID === false || $correctAnswer === false) {
        $_SESSION['error'] = "Invalid input data detected.";
        header("Location: recommend-question.php");
        exit();
    }
    
    try {

        $quizQuery = "SELECT id FROM Quiz WHERE educatorID = ? AND topicID = ?";
        $stmt = $connection->prepare($quizQuery);
        
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $connection->error);
        }
        
        $stmt->bind_param("ii", $educatorID, $topicID);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            $_SESSION['error'] = "No quiz found for the selected educator and topic combination.";
            header("Location: recommend-question.php");
            exit();
        }
        
        $quiz = $result->fetch_assoc();
        $quizID = $quiz['id'];
        $stmt->close();
        

        if (isset($_FILES['question-figure']) && $_FILES['question-figure']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = 'images/';

            

            if (!is_dir($uploadDir)) {
                if (!mkdir($uploadDir, 0755, true)) {
                    throw new Exception("Failed to create upload directory.");
                }
            }
            

            $fileName = uniqid() . '_' . basename($_FILES['question-figure']['name']);
            $uploadFile = $uploadDir . $fileName;
            

            $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];
            $fileExtension = strtolower(pathinfo($_FILES['question-figure']['name'], PATHINFO_EXTENSION));
            
            if (!in_array($fileExtension, $allowedExtensions)) {
                $_SESSION['error'] = "Only JPG, JPEG, PNG & GIF files are allowed.";
                header("Location: recommend-question.php");
                exit();
            }
            

            $imageInfo = getimagesize($_FILES['question-figure']['tmp_name']);
            if ($imageInfo === false) {
                $_SESSION['error'] = "The uploaded file is not a valid image.";
                header("Location: recommend-question.php");
                exit();
            }
            
            $allowedMimeTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
            if (!in_array($imageInfo['mime'], $allowedMimeTypes)) {
                $_SESSION['error'] = "Only JPG, JPEG, PNG & GIF files are allowed.";
                header("Location: recommend-question.php");
                exit();
            }
            

            if (move_uploaded_file($_FILES['question-figure']['tmp_name'], $uploadFile)) {

                $insertQuery = "INSERT INTO RecommendedQuestion (quizID, learnerID, question, questionFigureFileName, correctAnswer, status) VALUES (?, ?, ?, ?, ?, 'pending')";
                $stmt = $connection->prepare($insertQuery);
                
                if (!$stmt) {
                    throw new Exception("Prepare failed: " . $connection->error);
                }
                
                $questionText = "What number do you see in this pattern?";
                $stmt->bind_param("iisss", $quizID, $learnerID, $questionText, $fileName, $correctAnswer);
                
                if ($stmt->execute()) {
                    header("Location: learner-dashboard.php");
                    exit();
                } else {
                    throw new Exception("Failed to insert question: " . $stmt->error);
                }
                
                $stmt->close();
            } else {
                $_SESSION['error'] = "Failed to upload image. Please try again.";
                header("Location: recommend-question.php");
                exit();
            }
        } else {

            $uploadError = $_FILES['question-figure']['error'] ?? 'Unknown error';
            $errorMessages = [
                UPLOAD_ERR_INI_SIZE => 'The uploaded file exceeds the upload_max_filesize directive in php.ini.',
                UPLOAD_ERR_FORM_SIZE => 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form.',
                UPLOAD_ERR_PARTIAL => 'The uploaded file was only partially uploaded.',
                UPLOAD_ERR_NO_FILE => 'No file was uploaded.',
                UPLOAD_ERR_NO_TMP_DIR => 'Missing a temporary folder.',
                UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk.',
                UPLOAD_ERR_EXTENSION => 'A PHP extension stopped the file upload.'
            ];
            
            $errorMessage = $errorMessages[$uploadError] ?? 'Unknown upload error';
            $_SESSION['error'] = "Please upload an image file. Error: " . $errorMessage;
            header("Location: recommend-question.php");
            exit();
        }
        
    } catch (Exception $e) {
        error_log("Error processing recommendation: " . $e->getMessage());
        $_SESSION['error'] = "An error occurred while processing your recommendation. Please try again.";
        header("Location: recommend-question.php");
        exit();
    }
} else {
    $_SESSION['error'] = "Invalid request method.";
    header("Location: recommend-question.php");
    exit();
}
?>