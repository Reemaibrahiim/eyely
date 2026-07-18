<?php
session_start();
if (!isset($_SESSION['userID'])) {
    header("Location: login.php");
    exit();
}
if ($_SESSION['userType'] !== 'educator') {
    header("Location: learner-dashboard.php");
    exit();
}

require_once 'db_connect.php';

$educatorID = $_SESSION['userID'];
$educatorInfo = [];
$educatorQuizzes = [];
$pendingQuestions = [];

try {

    $stmt = $connection->prepare("SELECT firstName, lastName, emailAddress, photoFileName FROM Users WHERE id = ?");
    $stmt->bind_param("i", $educatorID);
    $stmt->execute();
    $result = $stmt->get_result();
    $educatorInfo = $result->fetch_assoc();
    $stmt->close();

    $quizQuery = "
        SELECT 
            q.id as quizID,
            t.topicName,
            (SELECT COUNT(*) FROM QuizQuestion qq WHERE qq.quizID = q.id) as questionCount,
            (SELECT COUNT(*) FROM TakenQuiz tq WHERE tq.quizID = q.id) as takenCount,
            (SELECT 
            COALESCE(AVG(tq.score / qqCount.questionCount * 100), 0)
            FROM TakenQuiz tq
            JOIN (
                SELECT quizID, COUNT(*) AS questionCount
                FROM QuizQuestion
                GROUP BY quizID
            ) AS qqCount ON tq.quizID = qqCount.quizID
            WHERE tq.quizID = q.id
            ) AS averageScore,
            (SELECT COALESCE(AVG(rating), 0) FROM QuizFeedback qf WHERE qf.quizID = q.id) as averageRating,
            (SELECT COUNT(*) FROM QuizFeedback qf WHERE qf.quizID = q.id) as feedbackCount
        FROM Quiz q
        JOIN Topic t ON q.topicID = t.id
        WHERE q.educatorID = ?
        ORDER BY t.topicName
    ";

    $stmt = $connection->prepare($quizQuery);
    $stmt->bind_param("i", $educatorID);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $educatorQuizzes[] = $row;
    }
    $stmt->close();

    $topicsQuery = "
        SELECT DISTINCT t.topicName 
        FROM Topic t 
        JOIN Quiz q ON t.id = q.topicID 
        WHERE q.educatorID = ?
        ORDER BY t.topicName
    ";

    $topicsStmt = $connection->prepare($topicsQuery);
    $topicsStmt->bind_param("i", $educatorID);
    $topicsStmt->execute();
    $topicsResult = $topicsStmt->get_result();
    $specializedTopics = [];

    while ($topic = $topicsResult->fetch_assoc()) {
        $specializedTopics[] = $topic['topicName'];
    }
    $topicsStmt->close();

    $pendingQuery = "
        SELECT 
            rq.id,
            rq.question,
            rq.questionFigureFileName,
            rq.correctAnswer,
            rq.status,
            rq.comments, 
            u.firstName,
            u.lastName,
            u.photoFileName,
            t.topicName,
            q.id as quizID
        FROM RecommendedQuestion rq
        JOIN Quiz q ON rq.quizID = q.id
        JOIN Topic t ON q.topicID = t.id
        JOIN Users u ON rq.learnerID = u.id
        WHERE q.educatorID = ? AND (rq.status = 'pending')
        ORDER BY rq.id DESC
    ";

    $stmt = $connection->prepare($pendingQuery);
    $stmt->bind_param("i", $educatorID);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $pendingQuestions[] = $row;
    }
    $stmt->close();
} catch (Exception $e) {
    error_log("Database error: " . $e->getMessage());
    $errorMessage = "Database error: " . $e->getMessage();
}

$connection->close();
?>

<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Educator Dashboard - Color Vision</title>
        <link href="https://fonts.googleapis.com/css2?family=Halant:wght@400;500;600;700&display=swap" rel="stylesheet">
        <link rel="stylesheet" href="style.css">

        <style>
            .header {
                background-color: white;
                border-radius: 50px;
                padding: 15px 30px;
                box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
                position: fixed;
                top: 20px;
                left: 50%;
                transform: translateX(-50%);
                width: 90%;
                max-width: 1250px;
                z-index: 1000;
                display: flex;
                justify-content: space-between;
                align-items: center;
                transition: top 0.3s ease;
            }

            .logo {
                display: flex;
                align-items: center;
            }

            .logo img {
                height: 50px;
                width: auto;
                max-width: 200px;
                object-fit: contain;
            }

            .auth-buttons {
                display: flex;
                gap: 20px;
            }

            .auth-btn {
                background: none;
                border: none;
                color: #3498db;
                font-family: 'Halant', serif;
                font-size: 18px;
                font-weight: 500;
                cursor: pointer;
                padding: 8px 0;
                position: relative;
                transition: color 0.3s;
            }

            .auth-btn:hover {
                color: #2980b9;
            }

            .auth-btn::after {
                content: '';
                position: absolute;
                bottom: 0;
                left: 0;
                width: 0;
                height: 2px;
                background-color: #3498db;
                transition: width 0.3s;
            }

            .auth-btn:hover::after {
                width: 100%;
            }

            .question-status {
                font-weight: bold;
                padding: 5px 10px;
                border-radius: 4px;
                text-align: center;
            }

            .question-status.pending {
                background-color: #fff3cd;
                color: #856404;
            }

            .question-status.approved {
                background-color: #d4edda;
                color: #155724;
            }

            .question-status.disapproved {
                background-color: #f8d7da;
                color: #721c24;
            }

            .quiz-stats {
                font-size: 0.9rem;
                color: #666;
            }

            .no-data {
                color: #999;
                font-style: italic;
                text-align: center;
            }

            .review-form textarea {
                width: 100%;
                padding: 8px;
                border: 1px solid #ddd;
                border-radius: 4px;
                margin-bottom: 8px;
                font-family: 'Halant', serif;
                resize: vertical;
                min-height: 60px;
            }

            .review-actions {
                display: flex;
                gap: 5px;
            }

            .approve-btn, .disapprove-btn {
                padding: 8px 12px;
                border: none;
                border-radius: 4px;
                cursor: pointer;
                font-family: 'Halant', serif;
                font-weight: 500;
                flex: 1;
            }

            .approve-btn {
                background: #28a745;
                color: white;
            }

            .disapprove-btn {
                background: #dc3545;
                color: white;
            }

            .approve-btn:hover, .disapprove-btn:hover {
                opacity: 0.9;
            }

            .learner-info {
                display: flex;
                align-items: center;
                gap: 8px;
            }

            .profile-photo-small {
                width: 30px;
                height: 30px;
                border-radius: 50%;
                object-fit: cover;
            }

            .success-message {
                background-color: #d4edda;
                color: #155724;
                padding: 12px;
                border-radius: 4px;
                margin-bottom: 20px;
                border: 1px solid #c3e6cb;
            }

            .error-message {
                background-color: #f8d7da;
                color: #721c24;
                padding: 12px;
                border-radius: 4px;
                margin-bottom: 20px;
                border: 1px solid #f5c6cb;
            }

            .section-header {
                display: flex;
                justify-content: space-between;
                align-items: center;
                margin-bottom: 20px;
            }

            .topic-tag {
                display: inline-block;
                background: #e3f2fd;
                color: #1976d2;
                padding: 5px 12px;
                border-radius: 20px;
                margin: 3px;
                font-size: 0.9rem;
                border: 1px solid #bbdefb;
            }
        </style>

    </head>
    <body>

        <div class="educator-dashboard">

            <header class="header">
                <div class="logo">
                    <a href="index.php"><img src="images/logo.png" alt="Color Vision"></a>
                </div>
                <div class="auth-buttons">
                    <form action="logout.php" method="POST" style="display:inline;">
                        <button type="submit" class="auth-btn" id="sign-out-btn" style="background:none;border:none;padding:0;cursor:pointer;">Sign Out</button>
                    </form>
                </div>
            </header>

            <div class="dashboard-container">

<?php if (isset($_SESSION['successMessage'])): ?>
                    <div class="success-message">
    <?php echo htmlspecialchars($_SESSION['successMessage']); ?>
                    <?php unset($_SESSION['successMessage']); ?>
                    </div>
                    <?php endif; ?>

                    <?php if (isset($_SESSION['errorMessage'])): ?>
                    <div class="error-message">
                    <?php echo htmlspecialchars($_SESSION['errorMessage']); ?>
                    <?php unset($_SESSION['errorMessage']); ?>
                    </div>
                    <?php endif; ?>

                <div class="welcome-section">
                    <h1>Welcome, <?php echo htmlspecialchars($educatorInfo['firstName'] . ' ' . $educatorInfo['lastName']); ?>!</h1>
                    <p>This is your educator dashboard where you can manage color blindness tests</p>
                </div>

                <div class="user-profile" style="margin: 1rem;">
                    <div class="info-photo">
                        <img src="images/<?php echo htmlspecialchars($educatorInfo['photoFileName'] ?? 'default.jpg'); ?>" alt="Educator's Photo" class="profile-photo">
                    </div>  
                    <div class="user-details">
                        <h2><?php echo htmlspecialchars($educatorInfo['firstName'] . ' ' . $educatorInfo['lastName']); ?></h2>
                        <p><?php echo htmlspecialchars($educatorInfo['emailAddress']); ?></p>
                        <div class="specialties">
                            <h3>Specialized Topics:</h3>
                            <div class="topics-list">
<?php
if (!empty($specializedTopics)) {
    foreach ($specializedTopics as $topic) {
        echo '<span class="topic-tag">' . htmlspecialchars($topic) . '</span>';
    }
} else {
    echo '<span class="no-data">No topics assigned yet</span>';
}
?>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="dashboard-section">
                    <div class="section-header">
                        <h2>Your Color Blindness Tests</h2>
                    </div>

                    <table class="dashboard-table">
                        <thead>
                            <tr>
                                <th>Topic Name</th>
                                <th>Number of Questions</th>
                                <th>Quiz Statistics</th>
                                <th>Quiz Feedback</th>
                            </tr>
                        </thead>
                        <tbody>
<?php if (empty($educatorQuizzes)): ?>
                                <tr>
                                    <td colspan="4" style="text-align: center;" class="no-data">No quizzes found.</td>
                                </tr>
                            <?php else: ?>
    <?php foreach ($educatorQuizzes as $quiz): ?>
                                    <tr>
                                        <td>
                                            <a href="quiz.php?quizID=<?php echo $quiz['quizID']; ?>" class="link-btn">
                                    <?php echo htmlspecialchars($quiz['topicName']); ?>
                                            </a>
                                        </td>
                                        <td><?php echo $quiz['questionCount']; ?></td>
                                        <td class="quiz-stats">
        <?php if ($quiz['takenCount'] > 0): ?>
            <?php echo $quiz['takenCount']; ?> takers, Average score: 
            <?php
            $averageScore = $quiz['averageScore'];

            if ($averageScore <= 1.0) {
                echo round($averageScore * 100, 1) . '%';
            } else {
                echo round($averageScore, 1) . '%';
            }
            ?>
                                            <?php else: ?>
                                                <span class="no-data">Quiz not taken yet</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="quiz-stats">
                                            <?php if ($quiz['feedbackCount'] > 0): ?>
                                                <?php echo round($quiz['averageRating'], 1); ?> ★ 
                                                <a href="comments.php?quizID=<?php echo $quiz['quizID']; ?>" class="link-btn">View Comments</a>
                                            <?php else: ?>
                                                <span class="no-data">No feedback yet</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <div class="dashboard-section">
                    <div class="section-header">
                        <h2>Recommended Questions</h2>
                    </div>

<?php if (empty($pendingQuestions)): ?>
                        <div class="no-data">
                            <p>No pending recommended questions.</p>
                            <p><small>When learners recommend questions for your quizzes, they will appear here.</small></p>
                        </div>
<?php else: ?>
                        <table class="dashboard-table">
                            <thead>
                                <tr>
                                    <th>Topic Name</th>
                                    <th>Learner</th>
                                    <th>Question Details</th>
                                    <th>Review</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
    <?php foreach ($pendingQuestions as $question): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($question['topicName']); ?></td>
                                        <td class="learner-info">
                                            <img src="images/<?php echo htmlspecialchars($question['photoFileName'] ?? 'default.jpg'); ?>" alt="Learner Photo" class="profile-photo-small">
                                            <span><?php echo htmlspecialchars($question['firstName'] . ' ' . $question['lastName']); ?></span>
                                        </td>
                                        <td>
                                            <div class="question-details">
        <?php if ($question['questionFigureFileName'] && $question['questionFigureFileName'] != ''): ?>
                                                    <p><strong>Figure:</strong> <img src="images/<?php echo htmlspecialchars($question['questionFigureFileName']); ?>" alt="Color Figure" class="question-figure" style="max-width: 100px;">

        <?php endif; ?>
                                                <p><strong>Question:</strong> <?php echo htmlspecialchars($question['question']); ?></p>
                                                <p><strong>Options:</strong> 0, 1, 2, 3, 4, 5, 6, 7, 8, 9, I don't see anything clearly</p>
                                                <p><strong>Correct Answer:</strong> <?php echo $question['correctAnswer']; ?></p>
                                            </div>
                                        </td>
                                        <td>
                                            <form method="POST" action="review-question.php" class="review-form">
                                                <input type="hidden" name="action" value="review">
                                                <input type="hidden" name="questionId" value="<?php echo $question['id']; ?>">
                                                <input type="hidden" name="quizId" value="<?php echo $question['quizID']; ?>">
                                                <textarea name="comment" placeholder="Write your comment here..." required>
                                                    <?php echo htmlspecialchars($question['comments'] ?? ''); ?>
                                                </textarea>
                                                <div class="review-actions">
                                                    <button type="submit" name="status" value="approved" class="approve-btn">Approve</button>
                                                    <button type="submit" name="status" value="disapproved" class="disapprove-btn">Disapprove</button>
                                                </div>
                                            </form>

                                        </td>
                                        <td class="question-status pending">Pending</td>
                                    </tr>
    <?php endforeach; ?>
                            </tbody>
                        </table>
<?php endif; ?>
                </div>
            </div>
        </div>

        <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
        <script src="educator-review.js"></script>

    </body>
</html> 