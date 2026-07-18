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

// Get quiz information for display
$quizInfo = [];
try {
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
} catch (Exception $e) {
    error_log("Database error: " . $e->getMessage());
}

$connection->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" type="text/css" href="style.css">
    <title>Add Question - Color Vision</title>
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

        .figure-preview {
            max-width: 200px;
            max-height: 200px;
            margin-top: 10px;
            border-radius: 8px;
            border: 1px solid #e6e6e6;
            display: none;
        }

        .btn {
            background: #3A2165;
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 8px;
            font-family: 'Halant', serif;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
        }

        .btn:hover {
            background: #4A2C82;
            transform: translateY(-2px);
        }

        .btn-outline {
            background: transparent;
            border: 1px solid #3A2165;
            color: #3A2165;
        }

        .btn-outline:hover {
            background: #3A2165;
            color: white;
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
                <h1>Add New Question</h1>
                <p>Create a new question for <?php echo htmlspecialchars($quizInfo['topicName'] ?? 'the quiz'); ?></p>
                <?php if ($quizInfo): ?>
                    <p>Educator: <?php echo htmlspecialchars($quizInfo['firstName'] . ' ' . $quizInfo['lastName']); ?></p>
                <?php endif; ?>
            </div>

            <?php if (isset($_GET['error'])): ?>
                <div class="error-message" style="background-color: #f8d7da; color: #721c24; padding: 12px; border-radius: 4px; margin-bottom: 20px;">
                    <?php
                    if ($_GET['error'] === 'missing_fields') {
                        echo 'Please fill in all required fields.';
                    } elseif ($_GET['error'] === 'upload_failed') {
                        echo 'Failed to upload image. Please try again.';
                    } elseif ($_GET['error'] === 'invalid_file_type') {
                        echo 'Invalid file type. Please upload JPG, JPEG, PNG, or GIF images only.';
                    } elseif ($_GET['error'] === 'database_error') {
                        echo 'Database error. Please try again.';
                    }
                    ?>
                </div>
            <?php endif; ?>

            <div class="dashboard-section">
                <div class="section-header">
                    <h2>Question Details</h2>
                </div>
                
                <!-- FORM NOW SUBMITS TO SEPARATE PHP PAGE -->
                <form id="add-question-form" action="process-add-question.php" method="POST" enctype="multipart/form-data">
                    <!-- Hidden input for quiz ID -->
                    <input type="hidden" name="quizID" value="<?php echo htmlspecialchars($quizID); ?>">
                    
                    <div class="form-group">
                        <label for="question-text">Question Text *</label>
                        <textarea id="question-text" name="questionText" placeholder="Enter the question text" required></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="question-figure">Question Figure</label>
                        <input type="file" id="question-figure" name="questionFigure" accept="image/*">
                        <p style="color: #666; font-size: 0.9rem; margin-top: 5px;">Supported formats: JPG, JPEG, PNG, GIF</p>
                        <img id="figure-preview" class="figure-preview" src="#" alt="Figure Preview">
                    </div>
                    
                    <div class="form-group">
                        <label for="correct-answer">Correct Answer *</label>
                        <select id="correct-answer" name="correctAnswer" required>
                            <option value="">Select correct answer</option>
                            <option value="0">0</option>
                            <option value="1">1</option>
                            <option value="2">2</option>
                            <option value="3">3</option>
                            <option value="4">4</option>
                            <option value="5">5</option>
                            <option value="6">6</option>
                            <option value="7">7</option>
                            <option value="8">8</option>
                            <option value="9">9</option>
                        </select>
                    </div>
                    
                    <div class="form-actions">
                        <a href="quiz.php?quizID=<?php echo $quizID; ?>" class="btn btn-outline">Cancel</a>
                        <button type="submit" class="btn" id="add-question-btn">Add Question</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Image preview functionality
        document.getElementById('question-figure').addEventListener('change', function(e) {
            const preview = document.getElementById('figure-preview');
            const file = e.target.files[0];
            
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.src = e.target.result;
                    preview.style.display = 'block';
                }
                reader.readAsDataURL(file);
            } else {
                preview.style.display = 'none';
            }
        });
    </script>
    
</body>
</html>