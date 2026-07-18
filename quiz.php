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

// Get quiz ID from request
$quizID = $_GET['quizID'] ?? null;
if (!$quizID) {
    header("Location: educator-dashboard.php");
    exit();
}

// Get quiz information and questions
$quizInfo = [];
$questions = [];
$errorMessage = '';

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

    // Get all questions for this quiz
    $questionsQuery = "
        SELECT 
            id as questionID,
            question,
            questionFigureFileName,
            correctAnswer
        FROM QuizQuestion 
        WHERE quizID = ?
        ORDER BY id
    ";
    $stmt = $connection->prepare($questionsQuery);
    $stmt->bind_param("i", $quizID);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $questions[] = $row;
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
    <title>Quiz Management - Color Vision</title>
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

        .question-figure-small {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 6px;
            border: 1px solid #e6e6e6;
        }

        .no-questions {
            text-align: center;
            color: #9e9e9e;
            font-style: italic;
            padding: 40px;
            background: #f8f9fa;
            border-radius: 8px;
            margin: 20px 0;
        }

        .action-btn {
            background: #3A2165;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            font-family: 'Halant', serif;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
            font-size: 14px;
        }

        .action-btn:hover {
            background: #4A2C82;
            transform: translateY(-2px);
        }

        .link-btn {
            color: #3498db;
            text-decoration: none;
            font-weight: 500;
            cursor: pointer;
        }

        .link-btn:hover {
            text-decoration: underline;
        }

        .delete-btn {
            color: #e74c3c !important;
        }

        .delete-btn:hover {
            color: #c0392b !important;
        }

        .ajax-message {
            padding: 12px;
            border-radius: 4px;
            margin-bottom: 20px;
            font-weight: 500;
            border: 1px solid;
        }

        .success-message {
            background-color: #d4edda;
            color: #155724;
            border-color: #c3e6cb;
        }

        .error-message {
            background-color: #f8d7da;
            color: #721c24;
            border-color: #f5c6cb;
        }

        .loading {
            opacity: 0.6;
            pointer-events: none;
        }
    </style>
</head>
<body>
    
    <div class="educator-dashboard">
        <!-- Header -->
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
            <div class="welcome-section">
                <h1 id="quiz-title"><?php echo htmlspecialchars($quizInfo['topicName'] ?? 'Quiz'); ?> Questions</h1>
                <p>Manage all questions for this quiz</p>
                <?php if ($quizInfo): ?>
                    <p>Educator: <?php echo htmlspecialchars($quizInfo['firstName'] . ' ' . $quizInfo['lastName']); ?></p>
                <?php endif; ?>
            </div>

            <?php if ($errorMessage): ?>
                <div class="error-message" style="background-color: #f8d7da; color: #721c24; padding: 12px; border-radius: 4px; margin-bottom: 20px;">
                    <?php echo htmlspecialchars($errorMessage); ?>
                </div>
            <?php endif; ?>

            <div class="dashboard-section">
                <div class="section-header">
                    <h2>Quiz Questions</h2>  
                    <div style="display: flex; gap: 10px;">
                        <a href="add-question.php?quizID=<?php echo $quizID; ?>" class="action-btn" id="add-question-btn">
                            + Add New Question
                        </a>
                        <a href="educator-dashboard.php" class="action-btn">
                            ← Back to Dashboard
                        </a>
                    </div>
                </div>
                
                <?php if (empty($questions)): ?>
                    <div class="no-questions">
                        <p>No questions found for this quiz.</p>
                        <p><small>Use the "Add New Question" button to add questions to this quiz.</small></p>
                    </div>
                <?php else: ?>
                    <table class="dashboard-table">
                        <thead>
                            <tr>
                                <th width="60%">Question Details</th>
                                <th width="20%">Edit</th>
                                <th width="20%">Delete</th>
                            </tr>
                        </thead>
                        <tbody id="questions-table-body">
                            <?php foreach ($questions as $index => $question): ?>
                                <tr id="question-row-<?php echo $question['questionID']; ?>">
                                    <td>
                                        <div class="question-details">
                                            <p><strong>Question <?php echo $index + 1; ?>:</strong> <?php echo htmlspecialchars($question['question']); ?></p>
                                        <?php if ($question['questionFigureFileName'] && $question['questionFigureFileName'] != ''): ?>
                                            <p style="margin-top: 10px;">
                                                <strong>Image:</strong> 
                                            <img src="images/<?php echo htmlspecialchars($question['questionFigureFileName']); ?>" 
                                                                   alt="Question Figure" 
                                                                   class="question-figure-small"
                                                                     onerror="this.style.display='none'">


                        
                        
                        


                                                   
                                            </p>
                                        <?php endif; ?>
                                            <p style="margin-top: 10px;"><strong>Correct Answer:</strong> <?php echo htmlspecialchars($question['correctAnswer']); ?></p>
                                        </div>
                                    </td>
                                    <td>
                                        <a href="edit-question.php?id=<?php echo $question['questionID']; ?>&quizID=<?php echo $quizID; ?>" class="link-btn">Edit</a>
                                    </td>
                                    <td>
                                        <a href="#" 
                                           class="link-btn delete-btn"
                                           data-question-id="<?php echo $question['questionID']; ?>"
                                           data-quiz-id="<?php echo $quizID; ?>">Delete</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- jQuery Library -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <script>
    $(document).ready(function() {
        // AJAX delete functionality
        $(document).on('click', '.delete-btn', function(e) {
            e.preventDefault();
            
            const deleteLink = $(this);
            const questionID = deleteLink.data('question-id');
            const quizID = deleteLink.data('quiz-id');
            const tableRow = $('#question-row-' + questionID);
            
            if (!confirm('Are you sure you want to delete this question? This action cannot be undone.')) {
                return false;
            }
            
            // Show loading state
            deleteLink.text('Deleting...').addClass('loading');
            tableRow.addClass('loading');
            
            $.ajax({
                url: 'ajax-delete-question.php',
                type: 'POST',
                data: {
                    questionID: questionID,
                    quizID: quizID
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        // Remove the row from the table with fade effect
                        tableRow.fadeOut(300, function() {
                            $(this).remove();
                            
                            // Show success message
                            showMessage('Question deleted successfully!', 'success');
                            
                            // If no questions left, show empty state
                            if ($('#questions-table-body tr').length === 0) {
                                showEmptyState();
                            }
                        });
                    } else {
                        showMessage('Failed to delete question: ' + response.message, 'error');
                        resetDeleteButton(deleteLink);
                        tableRow.removeClass('loading');
                    }
                },
                error: function(xhr, status, error) {
                    showMessage('Error deleting question. Please try again.', 'error');
                    resetDeleteButton(deleteLink);
                    tableRow.removeClass('loading');
                    console.error('AJAX Error:', error);
                }
            });
        });
        
        function resetDeleteButton(deleteLink) {
            deleteLink.text('Delete').removeClass('loading');
        }
        
        function showMessage(message, type) {
            // Remove existing messages
            $('.ajax-message').remove();
            
            const messageClass = type === 'success' ? 'success-message' : 'error-message';
            const messageDiv = $('<div class="ajax-message ' + messageClass + '"></div>')
                .text(message)
                .hide()
                .insertBefore('.dashboard-section');
            
            messageDiv.fadeIn(300);
            
            // Auto-hide after 5 seconds
            setTimeout(function() {
                messageDiv.fadeOut(300, function() {
                    $(this).remove();
                });
            }, 5000);
        }
        
        function showEmptyState() {
            const emptyStateHTML = `
                <div class="no-questions">
                    <p>No questions found for this quiz.</p>
                    <p><small>Use the "Add New Question" button to add questions to this quiz.</small></p>
                </div>
            `;
            $('.dashboard-table').replaceWith(emptyStateHTML);
        }
    });
    </script>
</body>
</html>