<?php
session_start();
if (!isset($_SESSION['userID'])) {
  header("Location: index.php");
  exit();
}

// Include database connection
require_once 'db_connect.php';

// Initialize variables
$quizID = $_GET['quizID'] ?? null;
$quizInfo = [];
$comments = [];

if (!$quizID) {
    header("Location: educator-dashboard.php");
    exit();
}

try {
    // Get quiz information
    $quizQuery = "
        SELECT 
            t.topicName,
            u.firstName,
            u.lastName
        FROM Quiz q
        JOIN Topic t ON q.topicID = t.id
        JOIN Users u ON q.educatorID = u.id
        WHERE q.id = ?
    ";
    $stmt = $connection->prepare($quizQuery);
    $stmt->bind_param("i", $quizID);
    $stmt->execute();
    $result = $stmt->get_result();
    $quizInfo = $result->fetch_assoc();
    $stmt->close();

    // Get all feedback comments for this quiz, ordered by newest first
    $commentsQuery = "
        SELECT 
            rating,
            comments,
            date
        FROM QuizFeedback 
        WHERE quizID = ? 
        ORDER BY date DESC
    ";
    $stmt = $connection->prepare($commentsQuery);
    $stmt->bind_param("i", $quizID);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $comments[] = $row;
    }
    $stmt->close();

} catch (Exception $e) {
    error_log("Database error: " . $e->getMessage());
    $errorMessage = "Database error: " . $e->getMessage();
}

$connection->close();
?>

<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Color Blindness Quiz Comments</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

    <header class="headerg">
        <div class="logo">
           <a href="index.php"><img src="images/logo.png" alt="Color Vision"></a>
        </div>
        <div class="auth-buttons">
            <?php if ($_SESSION['userType'] === 'educator'): ?>
                <a href="educator-dashboard.php"><button class="auth-btn">Dashboard</button></a>
            <?php else: ?>
                <a href="learner-dashboard.php"><button class="auth-btn">Dashboard</button></a>
            <?php endif; ?>
        </div>
    </header>

    <main class="containerg">
        <section class="page-title-section">
            <h1 class="page-title"><?php echo htmlspecialchars($quizInfo['topicName'] ?? 'Quiz'); ?> Test</h1>
            <p class="page-subtitle">Feedback from learners who took this quiz</p>
            <?php if ($quizInfo): ?>
                <p class="page-subtitle">Educator: <?php echo htmlspecialchars($quizInfo['firstName'] . ' ' . $quizInfo['lastName']); ?></p>
            <?php endif; ?>
        </section>

        <section class="comments-list">
            <?php if (empty($comments)): ?>
                <div class="comment">
                    <p class="comment-text">No feedback comments yet for this quiz.</p>
                </div>
            <?php else: ?>
                <?php foreach ($comments as $comment): ?>
                    <div class="comment">
                        <div class="comment-details">
                            <span class="comment-rating">Rating: <?php echo $comment['rating']; ?>/5</span>
                            <span class="comment-date">Date: <?php echo date('F j, Y', strtotime($comment['date'])); ?></span>
                        </div>
                        <p class="comment-text"><?php echo htmlspecialchars($comment['comments']); ?></p>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </section>
    </main>
</body>
</html>