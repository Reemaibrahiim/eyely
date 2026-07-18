<?php
session_start();
if (!isset($_SESSION['userID'])) {
  header("Location: login.php");
  exit();
}


require_once 'db_connect.php';


if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("Error: Question ID is missing or invalid.");
}

$questionID = $_GET['id'];
$question_details = null;


if (!$connection) {
    die("Database connection failed. Please ensure MySQL is running.");
}


$sql = "SELECT id, question, questionFigureFileName, correctAnswer FROM QuizQuestion WHERE id = ?";
$stmt = $connection->prepare($sql);
$stmt->bind_param("i", $questionID);
$stmt->execute();
$result = $stmt->get_result();




$quizID_to_redirect = null;
$sql_get_quizid = "SELECT quizID FROM QuizQuestion WHERE id = ?";
$stmt_get_quizid = $connection->prepare($sql_get_quizid);
$stmt_get_quizid->bind_param("i", $questionID);
$stmt_get_quizid->execute();
$result_quizid = $stmt_get_quizid->get_result();

if ($result_quizid->num_rows > 0) {
    $row = $result_quizid->fetch_assoc();
    $quizID_to_redirect = $row['quizID'];
}
$stmt_get_quizid->close();

if ($result->num_rows === 0) {
    $stmt->close();

    $connection->close();
    die("Error: The requested question does not exist.");
}




$question_details = $result->fetch_assoc();
$stmt->close();
$connection->close(); 


$current_figure_path =  $current_figure_path = !empty($question_details['questionFigureFileName']) 
    ? "images/quiz_figures/" . htmlspecialchars($question_details['questionFigureFileName']) 
    : "images/default_figure.png";

$current_question_text = htmlspecialchars($question_details['question']);
$current_correct_answer = htmlspecialchars($question_details['correctAnswer']);
?>

<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Question - Color Vision</title>
    <link href="https://fonts.googleapis.com/css2?family=Halant:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --primary: #4A2C82; --primary-light: #5E3BA6; --secondary: #6C63FF; 
            --text: #2D2B55; --text-light: #6C6B8A; --background: #F8F9FC; 
            --background-alt: #FFFFFF; --border: #E2E8F0; --shadow: rgba(74, 44, 130, 0.1);
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { background-color: var(--background); font-family: 'Halant', serif; color: var(--text); line-height: 1.6; min-height: 100vh; position: relative; }
        .header { background-color: var(--background-alt); border-radius: 16px; padding: 15px 40px; box-shadow: 0 8px 25px var(--shadow); position: fixed; top: 20px; left: 50%; transform: translateX(-50%); width: 85%; max-width: 1200px; z-index: 1000; display: flex; justify-content: space-between; align-items: center; border: 1px solid var(--border); }
        .logo { display: flex; align-items: center; }
        .logo img { height: 45px; width: auto; filter: brightness(0.9); }
        .auth-buttons { display: flex; gap: 15px; }
        .auth-btn { background: var(--primary); color: white; border: none; font-size: 16px; font-weight: 500; cursor: pointer; padding: 10px 20px; border-radius: 8px; transition: all 0.3s; }
        .auth-btn:hover { background: var(--primary-light); transform: translateY(-2px); box-shadow: 0 5px 15px var(--shadow); }

        .dashboard-container { max-width: 800px; margin: 100px auto 50px; padding: 0 20px; }
        .welcome-section { text-align: center; background: var(--background-alt); padding: 30px; border-radius: 12px; margin-bottom: 30px; box-shadow: 0 5px 15px var(--shadow); }
        .welcome-section h1 { font-size: 2rem; color: var(--primary); margin-bottom: 10px; }
        .welcome-section p { font-size: 1.1rem; color: var(--text-light); margin: 5px 0; }

        .dashboard-section { background: var(--background-alt); border-radius: 12px; padding: 30px; margin-bottom: 30px; box-shadow: 0 10px 30px var(--shadow); border: 1px solid var(--border); }
        .section-header h2 { color: var(--primary); font-size: 1.6rem; margin-bottom: 20px; }
        .form-group { margin-bottom: 25px; position: relative; }
        .form-group label { display: block; margin-bottom: 8px; color: var(--text); font-weight: 600; font-size: 1rem; }
        .form-group textarea, .form-group select { width: 100%; padding: 14px 16px; border: 2px solid var(--border); border-radius: 10px; font-size: 1rem; font-family: 'Halant', serif; transition: all 0.3s ease; background: var(--background); color: var(--text); resize: vertical; }
        .profile-image-preview { width: 100px; height: 100px; border-radius: 50%; object-fit: cover; margin: 10px 0; display: block; border: 1px solid var(--border); }
        .image-note { font-size: 0.9rem; color: var(--text-light); margin-top: 5px; }

        .form-actions { display: flex; gap: 15px; justify-content: flex-end; margin-top: 30px; }
        .btn { padding: 12px 30px; background: var(--primary); border: none; border-radius: 8px; color: white; font-size: 1rem; font-weight: 600; cursor: pointer; transition: all 0.3s ease; box-shadow: 0 4px 15px var(--shadow); text-decoration: none; }
        .btn-outline { background: transparent; border: 1px solid var(--primary); color: var(--primary); }
        .btn-outline:hover { background: var(--primary-light); color: white; }
    </style>

</head>
<body>
    
    <div class="educator-dashboard">
        <header class="header">
            <div class="logo">
                <a href="educator-dashboard.php"><img src="images/logo.png" alt="Color Vision"></a> 
            </div>
            <div class="auth-buttons">
                <a href="logout.php" class="auth-btn" id="sign-out-btn">Sign Out</a> 
            </div>
        </header>

        <div class="dashboard-container">
            <div class="welcome-section">
                <h1>Edit Question</h1>
                <p>Update your color vision test question</p>
            </div>

            <div class="dashboard-section">
                <div class="section-header">
                    <h2>Question Details</h2> 
                </div>
                
                <form action="update_question.php" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="questionID" value="<?php echo $questionID; ?>">
                    

                    <div class="form-group">
                        <label for="question-text">Question Text</label>
                        <textarea id="question-text" name="questionText" required><?php echo $current_question_text; ?></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="question-figure">Current Question Image</label>
                        
                        <img id="figure-preview" class="profile-image-preview" 
                             src="<?php echo $current_figure_path; ?>" 
                             alt="Figure Preview">
                        
                        <input type="file" id="question-figure" name="questionFigure" accept="image/*">
                        <p class="image-note">This is the current image. Upload a new one to replace it.</p>
                    </div>
                    
                    <div class="form-group">
                        <label for="correct-answer">Correct Answer (Required)</label>
                        <select id="correct-answer" name="correctAnswer" required>
                            <option value="">Select the correct answer</option>
                            <?php 
                            for ($i = 0; $i <= 9; $i++) {
                                $selected = ($current_correct_answer == $i) ? 'selected' : '';
                                echo "<option value=\"{$i}\" {$selected}>{$i}</option>";
                            }
                            ?>
                        </select>
                    </div>
                    
                    <div class="form-actions">
                        <a href="quiz.php" class="btn btn-outline">Cancel</a> 
                        <button type="submit" class="btn" id="update_question.php">Update Question</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

</body>
</html>
