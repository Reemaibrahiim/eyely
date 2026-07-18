<?php
session_start();

require_once 'db_connect.php'; 


if (!isset($_SESSION['userID'])) {
    header("Location: login.php");
    exit();
}

if ($_SESSION['userType'] !== 'learner') {
    header("Location: educator-dashboard.php");
    exit();
}


if (!isset($_GET['quizID']) || !is_numeric($_GET['quizID'])) {
    die("Error: Quiz ID is missing or invalid.");
}

$quizID = $_GET['quizID'];
$learnerID = $_SESSION['userID'];
$quiz_questions = [];
$selected_question_ids = [];


$quiz_details = null;
$sql_details = "
    SELECT 
        T.topicName, 
        U.firstName, 
        U.lastName
    FROM Quiz Q
    JOIN Topic T ON Q.topicID = T.id
    JOIN Users U ON Q.educatorID = U.id
    WHERE Q.id = ?
";
$stmt_details = $connection->prepare($sql_details);
$stmt_details->bind_param("i", $quizID);
$stmt_details->execute();
$quiz_details = $stmt_details->get_result()->fetch_assoc();
$stmt_details->close(); 

if (!$quiz_details) {
    die("Error: The requested quiz does not exist.");
}


$sql_questions = "SELECT id, question, questionFigureFileName FROM QuizQuestion WHERE quizID = ?";
$stmt_questions = $connection->prepare($sql_questions);
$stmt_questions->bind_param("i", $quizID);
$stmt_questions->execute();
$result = $stmt_questions->get_result();

$all_questions = $result->fetch_all(MYSQLI_ASSOC);
$stmt_questions->close();

$total_available_questions = count($all_questions);
$num_questions_to_take = min(5, $total_available_questions);

if ($num_questions_to_take === 0) {
    $connection->close();
    die("Sorry, no questions are available for this quiz.");
}

$random_keys = array_rand($all_questions, $num_questions_to_take);
if (!is_array($random_keys)) { $random_keys = [$random_keys]; }

foreach ($random_keys as $key) {
    $quiz_questions[] = $all_questions[$key];
    $selected_question_ids[] = $all_questions[$key]['id'];
}


$ids_string = implode(',', $selected_question_ids);
$total_questions = count($quiz_questions); 


$connection->close();
?>

<!DOCTYPE html>
<html lang="en" dir="ltr"> <head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Take Quiz - <?php echo htmlspecialchars($quiz_details['topicName']); ?></title>
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

     
        .color-test-container { max-width: 900px; margin: 100px auto 50px; padding: 0 20px; }
        .welcome-section { text-align: center; background: var(--background-alt); padding: 30px; border-radius: 12px; margin-bottom: 30px; box-shadow: 0 5px 15px var(--shadow); }
        .welcome-section h1 { font-size: 2rem; color: var(--primary); margin-bottom: 10px; }
        .welcome-section p { font-size: 1.1rem; color: var(--text-light); margin: 5px 0; }
        
     
        .test-card { background: var(--background-alt); border-radius: 12px; padding: 30px; box-shadow: 0 10px 30px var(--shadow); border: 1px solid var(--border); }
        .question-block { margin-bottom: 40px; padding: 20px; border-bottom: 1px dashed var(--border); border-radius: 8px; }
        .question-block:last-child { border-bottom: none; }
        .question-block h3 { color: var(--primary); margin-bottom: 15px; font-size: 1.4rem; }
        .question-block p { font-size: 1.1rem; color: var(--text); margin-bottom: 20px; }
        
       
        .test-image { max-width: 400px; height: auto; border-radius: 8px; box-shadow: 0 5px 15px var(--shadow-light); display: block; margin: 20px auto; }
        .test-controls { margin-top: 20px; }
        .test-controls label { display: block; margin-bottom: 10px; font-weight: 600; color: var(--text); }
        .test-controls select { width: 60%; max-width: 400px; padding: 12px; border: 2px solid var(--border); border-radius: 8px; font-size: 1rem; margin-top: 10px; }
        
      
        .submit-btn { display: block; width: 50%; max-width: 300px; margin: 30px auto 0; padding: 15px; background: var(--secondary); color: white; border: none; border-radius: 12px; font-size: 1.1rem; font-weight: 600; cursor: pointer; transition: all 0.3s ease; box-shadow: 0 4px 15px var(--shadow); }
        .submit-btn:hover { background: var(--secondary-light); transform: translateY(-3px); }
    </style>
</head>
<body>
    
    <div class="color-test-page">
        <header class="header">
            <div class="logo">
                <a href="learner_dashboard.php"><img src="images/logo.png" alt="Color Vision"></a>
            </div>
            <div class="auth-buttons">
                <a href="logout.php" class="auth-btn" id="sign-out-btn">Sign Out</a>
            </div>
        </header>

        <div class="color-test-container">
            <div class="welcome-section">
                <h1 id="test-title">Quiz: <?php echo htmlspecialchars($quiz_details['topicName']); ?></h1>
                <p id="test-educator">Supervised by: <?php echo htmlspecialchars($quiz_details['firstName'] . ' ' . $quiz_details['lastName']); ?></p>
                <h3 id="test-description">Please select the number you see in the following patterns.</h3>
            </div>
            
            <div class="test-card">
              

                <form action="score_feedback.php" method="POST">
                    
                    <input type="hidden" name="quizID" value="<?php echo htmlspecialchars($quizID); ?>">
                    <input type="hidden" name="learnerID" value="<?php echo htmlspecialchars($learnerID); ?>">
                    <input type="hidden" name="questionIDs" value="<?php echo htmlspecialchars($ids_string); ?>">
                    
                    <?php foreach ($quiz_questions as $index => $q): 
                        $question_number = $index + 1;
                        
                        $input_name = "answer[" . $q['id'] . "]"; 
                    ?>
                        <div class="question-block" data-question="<?php echo $question_number; ?>">
                            
                            <h3>Question No. <?php echo $question_number; ?>:</h3>
                            <p><?php echo htmlspecialchars($q['question']); ?></p>

                        <?php if (!empty($q['questionFigureFileName'])): ?>
    <div class="test-image-container" style="text-align: center;">
      <img src="images/<?php echo htmlspecialchars($q['questionFigureFileName']); ?>" 
           alt="Question Figure" class="test-image">
    </div>
<?php endif; ?>


                            <div class="test-controls" style="margin-top: 20px; text-align: center;">
                                <label for="<?php echo $input_name; ?>">Select the number you see:</label><br>
                                <select name="<?php echo $input_name; ?>" required>
                                    <option value="">-- Select your answer --</option>
                                    <?php 
                                    
                                    for ($i = 0; $i <= 9; $i++) {
                                        echo "<option value=\"{$i}\">{$i}</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    
                    <div class="test-navigation" style="text-align: center; margin-top: 40px;">
                        <button type="submit" class="submit-btn" id="finish-test-btn">Submit Answers and Get Score</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>
</html>