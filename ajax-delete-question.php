<?php
session_start();
if (!isset($_SESSION['userID']) || $_SESSION['userType'] !== 'educator') {
    header('HTTP/1.1 403 Forbidden');
    echo json_encode(['success' => false, 'message' => 'Access denied']);
    exit();
}

// Include database connection (using your secure credentials file)
require_once 'db_connect.php';

// Get question ID and quiz ID from POST data
$questionID = $_POST['questionID'] ?? null;
$quizID = $_POST['quizID'] ?? null;

if (!$questionID || !$quizID) {
    header('HTTP/1.1 400 Bad Request');
    echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
    exit();
}

header('Content-Type: application/json');

try {
    // Verify that the question belongs to the educator's quiz
    $verifyQuery = "
        SELECT qq.id, qq.questionFigureFileName 
        FROM QuizQuestion qq 
        JOIN Quiz q ON qq.quizID = q.id 
        WHERE qq.id = ? AND q.id = ? AND q.educatorID = ?
    ";
    $stmt = $connection->prepare($verifyQuery);
    $stmt->bind_param("iii", $questionID, $quizID, $_SESSION['userID']);
    $stmt->execute();
    $result = $stmt->get_result();
    $question = $result->fetch_assoc();
    $stmt->close();

    if (!$question) {
        echo json_encode(['success' => false, 'message' => 'Question not found or access denied']);
        exit();
    }

    // Get the question figure filename before deleting
    $imagePath = null;
    if ($question['questionFigureFileName'] && !empty($question['questionFigureFileName'])) {
        $imagePath = $question['questionFigureFileName'];
    }

    // Delete the question from database
    $deleteQuery = "DELETE FROM QuizQuestion WHERE id = ?";
    $stmt = $connection->prepare($deleteQuery);
    $stmt->bind_param("i", $questionID);
    
    if ($stmt->execute()) {
        // Delete the question figure image if it exists
        if ($imagePath && file_exists($imagePath) && is_file($imagePath)) {
            unlink($imagePath);
        }
        
        echo json_encode(['success' => true, 'message' => 'Question deleted successfully']);
    } else {
        throw new Exception("Failed to delete question from database");
    }

} catch (Exception $e) {
    error_log("Database error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}

$connection->close();
?>