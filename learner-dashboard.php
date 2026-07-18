<?php
session_start();
if (!isset($_SESSION['userID'])) {
  header("Location: login.php");
  exit();
}
if ($_SESSION['userType'] !== 'learner') {
  header("Location: educator-dashboard.php");
  exit();
}

require_once 'db_connect.php';

$userInfo = [];
$quizzes = [];
$recommendedQuestions = [];
$topics = [];
$selectedTopic = 'all';
$errorMessage = '';

$userID = $_SESSION['userID'];
try {
    $stmt = $connection->prepare("SELECT firstName, lastName, emailAddress, photoFileName FROM Users WHERE id = ?");
    $stmt->bind_param("i", $userID);
    $stmt->execute();
    $result = $stmt->get_result();
    $userInfo = $result->fetch_assoc();
    $stmt->close();

    $topicQuery = "SELECT id, topicName FROM Topic ORDER BY topicName";
    $topicResult = $connection->query($topicQuery);
    while ($row = $topicResult->fetch_assoc()) {
        $topics[] = $row;
    }

    $recommendedQuery = "
        SELECT 
            rq.id,
            rq.question,
            rq.questionFigureFileName,
            rq.correctAnswer,
            rq.status,
            rq.comments as reviewComments,
            t.topicName,
            u.firstName as educatorFirstName,
            u.lastName as educatorLastName,
            u.photoFileName as educatorPhoto
        FROM RecommendedQuestion rq
        JOIN Quiz q ON rq.quizID = q.id
        JOIN Topic t ON q.topicID = t.id
        JOIN Users u ON q.educatorID = u.id
        WHERE rq.learnerID = ?
        ORDER BY rq.id DESC
    ";
    
    $stmt = $connection->prepare($recommendedQuery);
    $stmt->bind_param("i", $userID);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $recommendedQuestions[] = $row;
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
    <link rel="stylesheet" type="text/css" href="style.css">
    <title>Learner Dashboard</title>
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

        .auth-btn:hover {
            background-color: #ffffff;
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

        .suggest-question-bar {
            display: flex;
            justify-content: center;
            margin: 30px 0;
            width: 100%;
        }

        .suggest-btn {
            background: linear-gradient(30deg, white, #e9e5ea);
            color: #641c88;
            border: none;
            padding: 16px 60px;
            border-radius: 50px;
            font-family: 'Halant', serif;
            font-size: 1.1rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 5px 10px rgba(0, 0, 0, 0.2);
            display: inline-block;
            text-align: center;
            min-width: 300px;
        }

        .suggest-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.25);
        }

        .suggest-btn:active {
            transform: translateY(0);
        }
         .take-quiz-btn {
            background: #3498db;
            color: white;
            padding: 8px 16px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .take-quiz-btn:hover {
            background: #2980b9;
            transform: translateY(-2px);
        }
        
        .take-quiz-btn:disabled {
            background: #95a5a6;
            cursor: not-allowed;
            transform: none;
        }
        
        .quiz-filter {
            display: flex;
            gap: 15px;
            align-items: center;
            margin-bottom: 20px;
            padding: 15px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        
        .quiz-filter label {
            font-weight: 600;
            color: #5a5a5a;
        }
        
        .quiz-filter select {
            padding: 10px 15px;
            border: 1px solid #e6e6e6;
            border-radius: 8px;
            font-family: 'Halant', serif;
        }
        
        .status-pending {
            color: #ff9800;
            font-weight: 500;
        }
        
        .status-approved {
            color: #4caf50;
            font-weight: 500;
        }
        
        .status-disapproved {
            color: #f44336;
            font-weight: 500;
        }
        
        .no-questions {
            color: #9e9e9e;
            font-style: italic;
        }
        
        .profile-photo-small {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid #e6e6e6;
        }
        
        .question-figure-small {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 6px;
            border: 1px solid #e6e6e6;
        }
        
        .no-quizzes {
            text-align: center;
            color: #9e9e9e;
            font-style: italic;
            padding: 20px;
        }
        
        .error-message {
            background-color: #f8d7da;
            color: #721c24;
            padding: 12px;
            border-radius: 4px;
            margin-bottom: 20px;
            border: 1px solid #f5c6cb;
        }
        
        .loading-spinner {
            text-align: center;
            padding: 20px;
            color: #666;
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
            <?php if ($errorMessage): ?>
                <div class="error-message">
                    <?php echo htmlspecialchars($errorMessage); ?>
                </div>
            <?php endif; ?>

            <div class="welcome-section">
                <h1>Welcome, <?php echo htmlspecialchars($userInfo['firstName'] . ' ' . $userInfo['lastName']); ?>!</h1>
                <p>Track your color vision progress and discover new tests</p>
            </div>
            
            <div class="user-profile welcome-section">
                <div class="info-photo">
                    <img src="images/<?php echo htmlspecialchars($userInfo['photoFileName'] ?? 'learner.png'); ?>" alt="Learner's Photo" class="profile-photo">
                </div>                  
                <div class="user-details">
                    <h2><?php echo htmlspecialchars($userInfo['firstName'] . ' ' . $userInfo['lastName']); ?></h2>
                    <p><?php echo htmlspecialchars($userInfo['emailAddress']); ?></p>
                </div>
            </div>
 
            <div class="quiz-filter">
                <label for="topic-filter">Filter by Topic:</label>
                <select id="topic-filter" name="topic-filter">
                    <option value="all">All Topics</option>
                    <?php foreach ($topics as $topic): ?>
                        <option value="<?php echo $topic['id']; ?>">
                            <?php echo htmlspecialchars($topic['topicName']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
           
            <div class="suggest-question-bar">
              <a href="recommend-question.php">  <button class="suggest-btn" id="suggest-question-btn">
                    <i class="fas fa-lightbulb"></i> Suggest a Question to Educators
                </button></a>
            </div>

            <div class="dashboard-section">
                <div class="section-header">
                    <h2>Available Quizzes</h2>
                    <span class="quiz-count" id="quiz-count">Loading...</span>
                </div>
                
                <div id="quizzes-container">
                    <div class="loading-spinner">
                        Loading quizzes...
                    </div>
                </div>
            </div>

            <div class="dashboard-section">
                <div class="section-header">
                    <h2>Your Recommended Questions</h2>
                    <span class="quiz-count">(<?php echo isset($recommendedQuestions) ? count($recommendedQuestions) : 0; ?> questions)</span>
                </div>
                
                <?php if (empty($recommendedQuestions)): ?>
                    <div class="no-quizzes">
                        <p>You haven't recommended any questions yet.</p>
                        <p><small>Use the "Suggest a Question" button above to recommend questions to educators.</small></p>
                    </div>
                <?php else: ?>
                    <table class="dashboard-table">
                        <thead>
                            <tr>
                                <th>Topic Name</th>
                                <th>Educator</th>
                                <th>Question Details</th>
                                <th>Status</th>
                                <th>Review Comments</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recommendedQuestions as $question): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($question['topicName']); ?></td>
                                    <td class="learner-info">
                                        <img src="images/<?php echo htmlspecialchars($question['educatorPhoto'] ?? 'educator.png'); ?>" alt="Educator's Photo" class="profile-photo-small">
                                        <span><?php echo htmlspecialchars($question['educatorFirstName'] . ' ' . $question['educatorLastName']); ?></span>
                                    </td>
                                    <td>
                                        <div class="question-details">
                                            <?php if ($question['questionFigureFileName'] && $question['questionFigureFileName'] != ''): ?>
                                                <p><strong>Figure:</strong> <img src="images/<?php echo htmlspecialchars($question['questionFigureFileName']); ?>" alt="Color Figure" class="question-figure-small"></p>
                                            <?php endif; ?>
                                            <p><strong>Question:</strong> <?php echo htmlspecialchars($question['question']); ?></p>
                                            <p><strong>Correct Answer:</strong> <?php echo $question['correctAnswer']; ?></p>
                                        </div>
                                    </td>
                                    <td class="status-<?php echo strtolower($question['status']); ?>">
                                        <?php echo ucfirst($question['status']); ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($question['reviewComments'] ?? '-'); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const topicFilter = document.getElementById('topic-filter');
            const quizzesContainer = document.getElementById('quizzes-container');
            const quizCount = document.getElementById('quiz-count');

            loadQuizzes('all');

            topicFilter.addEventListener('change', function() {
                const selectedTopic = this.value;
                loadQuizzes(selectedTopic);
            });

            function loadQuizzes(topicId) {
                quizCount.textContent = 'Loading...';
                quizzesContainer.innerHTML = '<div class="loading-spinner">Loading quizzes...</div>';

                const xhr = new XMLHttpRequest();
                xhr.open('POST', 'get_quizzes.php', true);
                xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
                
                xhr.onreadystatechange = function() {
                    if (xhr.readyState === 4 && xhr.status === 200) {
                        try {
                            const response = JSON.parse(xhr.responseText);
                            
                            if (response.success) {
                                displayQuizzes(response.quizzes);
                            } else {
                                showError(response.message || 'Error loading quizzes');
                            }
                        } catch (e) {
                            showError('Error parsing response');
                        }
                    }
                };

                xhr.send('topic_id=' + encodeURIComponent(topicId));
            }

            function displayQuizzes(quizzes) {
                if (quizzes.length === 0) {
                    quizzesContainer.innerHTML = `
                        <div class="no-quizzes">
                            <p>No quizzes found matching your criteria.</p>
                            <p><small>Try selecting a different topic or check back later for new quizzes.</small></p>
                        </div>
                    `;
                    quizCount.textContent = '0 quizzes found';
                    return;
                }

                let html = `
                    <table class="dashboard-table">
                        <thead>
                            <tr>
                                <th>Topic Name</th>
                                <th>Educator</th>
                                <th>Number of Questions</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                `;

                quizzes.forEach(quiz => {
                    html += `
                        <tr>
                            <td>${escapeHtml(quiz.topicName)}</td>
                            <td class="learner-info">
                                <img src="images/${escapeHtml(quiz.educatorPhoto || 'educator.png')}" alt="Educator Photo" class="profile-photo-small">
                                <span>${escapeHtml(quiz.firstName + ' ' + quiz.lastName)}</span>
                            </td>
                            <td>${quiz.questionCount}</td>
                            <td>
                                ${quiz.questionCount > 0 ? 
                                    `<a href="take-quiz.php?quizID=${quiz.quizID}" class="take-quiz">Take Quiz</a>` : 
                                    `<span class="no-questions">No questions available</span>`
                                }
                            </td>
                        </tr>
                    `;
                });

                html += `
                        </tbody>
                    </table>
                `;

                quizzesContainer.innerHTML = html;
                quizCount.textContent = `${quizzes.length} quizzes found`;
            }

            function showError(message) {
                quizzesContainer.innerHTML = `
                    <div class="error-message">
                        ${escapeHtml(message)}
                    </div>
                `;
                quizCount.textContent = 'Error';
            }

            function escapeHtml(unsafe) {
                return unsafe
                    .replace(/&/g, "&amp;")
                    .replace(/</g, "&lt;")
                    .replace(/>/g, "&gt;")
                    .replace(/"/g, "&quot;")
                    .replace(/'/g, "&#039;");
            }
        });
    </script>
</body>
</html>