<?php
session_start();
require_once 'db_connect.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo "false";
    exit;
}

$recommendedId = $_POST['questionId'] ?? null;
$quizId        = $_POST['quizId'] ?? null;
$status        = $_POST['status'] ?? null;  
$comment       = $_POST['comment'] ?? '';

if (!$recommendedId || !$status) {
    echo "false";
    exit;
}

$connection->begin_transaction();

try {

    // Update status + comments
    $updateSql = "
        UPDATE RecommendedQuestion
        SET status = ?, comments = ?
        WHERE id = ?
    ";
    $updateStmt = $connection->prepare($updateSql);
    $updateStmt->bind_param("ssi", $status, $comment, $recommendedId);
    $updateStmt->execute();

    // If approved → add to QuizQuestion
    if ($status === 'approved') {

        // Get recommended question
        $selectSql = "
            SELECT question, questionFigureFileName, correctAnswer
            FROM RecommendedQuestion
            WHERE id = ?
        ";
        $selectStmt = $connection->prepare($selectSql);
        $selectStmt->bind_param("i", $recommendedId);
        $selectStmt->execute();
        $rqRow = $selectStmt->get_result()->fetch_assoc();

        if ($rqRow && $quizId) {

            $insertSql = "
                INSERT INTO QuizQuestion
                    (quizID, question, questionFigureFileName, correctAnswer)
                VALUES (?, ?, ?, ?)
            ";
            $insertStmt = $connection->prepare($insertSql);
            $insertStmt->bind_param(
                "isss",
                $quizId,
                $rqRow['question'],
                $rqRow['questionFigureFileName'],
                $rqRow['correctAnswer']
            );
            $insertStmt->execute();
        }
    }

    $connection->commit();
    echo "true";
    exit;

} catch (Exception $e) {
    $connection->rollback();
    echo "false";
    exit;
}
