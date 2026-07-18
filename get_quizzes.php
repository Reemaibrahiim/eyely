<?php
session_start();
require_once 'db_connect.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['userID']) || $_SESSION['userType'] !== 'learner') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

$topicId = $_POST['topic_id'] ?? 'all';

try {
    if ($topicId === 'all') {
        $quizQuery = "
            SELECT 
                q.id as quizID,
                t.topicName,
                u.firstName,
                u.lastName,
                u.photoFileName as educatorPhoto,
                (SELECT COUNT(*) FROM QuizQuestion qq WHERE qq.quizID = q.id) as questionCount
            FROM Quiz q
            JOIN Topic t ON q.topicID = t.id
            JOIN Users u ON q.educatorID = u.id
            ORDER BY t.topicName, u.lastName, u.firstName
        ";
        $stmt = $connection->prepare($quizQuery);
        $stmt->execute();
    } else {
        $quizQuery = "
            SELECT 
                q.id as quizID,
                t.topicName,
                u.firstName,
                u.lastName,
                u.photoFileName as educatorPhoto,
                (SELECT COUNT(*) FROM QuizQuestion qq WHERE qq.quizID = q.id) as questionCount
            FROM Quiz q
            JOIN Topic t ON q.topicID = t.id
            JOIN Users u ON q.educatorID = u.id
            WHERE t.id = ?
            ORDER BY t.topicName, u.lastName, u.firstName
        ";
        $stmt = $connection->prepare($quizQuery);
        $stmt->bind_param("i", $topicId);
        $stmt->execute();
    }

    $result = $stmt->get_result();
    $quizzes = [];

    while ($row = $result->fetch_assoc()) {
        $quizzes[] = $row;
    }

    $stmt->close();
    $connection->close();

    echo json_encode([
        'success' => true,
        'quizzes' => $quizzes
    ]);

} catch (Exception $e) {
    error_log("Database error in get_quizzes.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Database error'
    ]);
}
?>