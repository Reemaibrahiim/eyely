<?php
session_start();
require_once 'db_connect.php'; 


if ($_SERVER['REQUEST_METHOD'] !== 'POST' || $_SESSION['userType'] !== 'learner' || !isset($_SESSION['userID'])) {
    header("Location: login.php");
    exit();
}


$quizID = $_POST['quizID'] ?? null;
$learnerID = $_POST['learnerID'] ?? null;
$questionIDs_string = $_POST['questionIDs'] ?? ''; 
$user_answers = $_POST['answer'] ?? []; 


if (empty($quizID) || empty($learnerID) || empty($questionIDs_string) || empty($user_answers)) {
    die("Error: Quiz data is incomplete or invalid.");
}

$questionIDs_array = explode(',', $questionIDs_string);
$in_clause = str_repeat('?,', count($questionIDs_array) - 1) . '?'; 

$score = 0;
$total_questions = count($questionIDs_array);


if (!$connection) {
    die("Database connection failed.");
}


$sql_details = "
    SELECT 
        T.topicName, 
        U.firstName, 
        U.lastName,
        U.photoFileName 
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



$sql_correct = "SELECT id, correctAnswer FROM QuizQuestion WHERE id IN ($in_clause)";
$stmt_correct = $connection->prepare($sql_correct);

$types = str_repeat('i', count($questionIDs_array));
call_user_func_array([$stmt_correct, 'bind_param'], array_merge([$types], $questionIDs_array));
$stmt_correct->execute();
$correct_answers_result = $stmt_correct->get_result();

$correct_answers = [];
while ($row = $correct_answers_result->fetch_assoc()) {
    $correct_answers[$row['id']] = $row['correctAnswer'];
}
$stmt_correct->close();


foreach ($user_answers as $qID => $uAnswer) {
    if (isset($correct_answers[$qID]) && $uAnswer === $correct_answers[$qID]) {
        $score++;
    }
}



$sql_taken_insert = "INSERT INTO TakenQuiz (quizID, score) VALUES (?, ?)";
$stmt_taken_insert = $connection->prepare($sql_taken_insert);
$stmt_taken_insert->bind_param("ii", $quizID, $score);
$stmt_taken_insert->execute();
$stmt_taken_insert->close();



$score_percentage = ($total_questions > 0) ? ($score / $total_questions) * 100 : 0;
$reaction_video_url = 'videos/default.mp4'; 
$reaction_message = 'Please try to identify the numbers again.';

if ($score_percentage >= 80) {
    $reaction_video_url = 'videos/excellent.MP4'; 
    $reaction_message = 'Outstanding result! You have excellent color vision.';
} elseif ($score_percentage >= 50) {
    $reaction_video_url = 'videos/Almost_there.mp4'; 
    $reaction_message = 'Well done! A solid performance.';
} else {
    $reaction_video_url = 'videos/Give.mp4'; 
}

$connection->close();
?>

<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quiz Score and Feedback</title>
    <link href="https://fonts.googleapis.com/css2?family=Halant:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
       
        :root {
            --primary: #2C3E50; --primary-light: #34495E; --secondary: #16A085; 
            --secondary-light: #1ABC9C; --accent: #7F8C8D; --text: #2C3E50; 
            --text-light: #7F8C8D; --background: #FFFFFF; --background-alt: #F8F9FA; 
            --border: #ECF0F1; --success: #27AE60; --warning: #F39C12; --error: #E74C3C; 
            --shadow: rgba(44, 62, 80, 0.1); --shadow-light: rgba(44, 62, 80, 0.05);
        }

       
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { background-color: #3b2776; font-family: 'Halant', serif; min-height: 100vh; overflow-x: hidden; color: var(--text); line-height: 1.6; }
        
      
        .headerg { background-color: white; border-radius: 50px; padding: 15px 30px; box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1); position: fixed; top: 20px; left: 50%; transform: translateX(-50%); width: 90%; max-width: 1250px; z-index: 1000; display: flex; justify-content: space-between; align-items: center; }
        .logo img { height: 45px; width: auto; max-width: 200px; object-fit: contain; }
        .auth-buttons { display: flex; gap: 20px; }
        .auth-btn { background: var(--primary); color: white; border: none; padding: 10px 20px; border-radius: 8px; font-size: 16px; font-weight: 500; cursor: pointer; transition: all 0.3s; }
        
     
        .containerg { width: 90%; max-width: 800px; padding: 40px; background: rgba(255, 255, 255, 0.9); border-radius: 20px; box-shadow: 0 15px 35px rgba(0, 0, 0, 0.2); backdrop-filter: blur(10px); margin: 100px auto 20px auto; color: var(--text); }

     
        .educator-info { display: flex; align-items: center; justify-content: flex-start; gap: 20px; padding-bottom: 20px; margin-bottom: 20px; border-bottom: 2px solid #e6e6e6; color: #5a5a5a; }
        .educator-info .educator-label { font-weight: bold; font-size: 1.2em; }
        .profile-photo { width: 60px; height: 60px; border-radius: 50%; border: 3px solid #e6e6e6; object-fit: cover; }
        
        .score-section { color: #5a5a5a; margin-bottom: 30px; text-align: center;}
        .score-section h2 { font-size: 2em; border-bottom: 2px solid #e6e6e6; padding-bottom: 10px; margin-bottom: 20px; }
        #quizScore { font-size: 3em; font-weight: bold; color: var(--secondary); display: block; margin: 10px 0;}
        
        .video-container { text-align: center; margin-top: 20px; }
        #reactionVideo { max-width: 100%; border-radius: 10px; box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2); }
        
        .feedback-section { color: #5a5a5a; text-align: left; }
        .feedback-section h2 { font-size: 2em; border-bottom: 2px solid #e6e6e6; padding-bottom: 10px; margin-bottom: 20px; }
        
        .form-group { margin-bottom: 18px; }
        .form-group label { display: block; margin-bottom: 8px; font-weight: bold; color: #5a5a5a; }
        .form-group select, .form-group textarea { width: 100%; padding: 12px; border-radius: 8px; border: none; background-color: #f8f8f8; color: #333; resize: vertical; }
        
        .submit-btn { display: block; width: 100%; padding: 15px; background: var(--primary); color: white; border: none; border-radius: 12px; font-size: 1.2em; font-weight: 600; cursor: pointer; transition: all 0.3s ease; box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2); }
        .submit-btn:hover { transform: translateY(-3px); box-shadow: 0 8px 20px rgba(0, 0, 0, 0.3); }
        .skip-link { display: block; text-align: center; margin-top: 20px; color: var(--secondary); text-decoration: none; font-size: 1.1em; transition: color 0.3s ease; }
    </style>
</head>
<body>
    
    <header class="headerg">
        <div class="logo">
            <a href="learner_dashboard.php"><img src="images/logo.png" alt="Color Vision"></a>
        </div>
        <div class="auth-buttons">
            <a href="learner-dashboard.php"><button class="auth-btn">Dashboard</button></a>
        </div>
    </header> 
    
    <main class="containerg">
        <h1 style="text-align: center; color: var(--primary);">Quiz Result and Feedback</h1>
        
        <section class="educator-info">
            <div class="info-details">
                <p><strong>Topic:</strong> <?php echo htmlspecialchars($quiz_details['topicName'] ?? 'N/A'); ?></p>
                <span class="educator-label">Supervised by:</span>
                <span class="educator-name"><?php echo htmlspecialchars($quiz_details['firstName'] . ' ' . $quiz_details['lastName'] ?? 'N/A'); ?></span>
            </div>
            </div>
           <div class="info-photo">
                <img src="images/<?php echo htmlspecialchars($quiz_details['photoFileName'] ?? 'default.jpg'); ?>" alt="Educator Photo" class="profile-photo">

                </div>
        </section>

        <section class="score-section">
            <h2>Final Score: <span id="quizScore"><?php echo "{$score} / {$total_questions}"; ?></span></h2>
            <p style="font-size: 1.2em; margin-top: 15px; color: <?php echo ($score_percentage >= 50) ? 'var(--secondary)' : 'var(--error)'; ?>; font-weight: bold;"><?php echo $reaction_message; ?></p>
            
            <div class="video-container">
                <video id="reactionVideo" width="320" height="240" controls autoplay muted>
                    <source src="<?php echo $reaction_video_url; ?>" type="video/mp4">
                    Your browser does not support the video tag.
                </video>
            </div>
        </section>
        
        <hr style="border: 1px dashed #e6e6e6; margin: 30px 0;">

        <section class="feedback-section">
            <h2>Share Your Feedback and Rating</h2>
            
            <form action="submit_feedback.php" method="POST">
                
                <input type="hidden" name="quizID" value="<?php echo htmlspecialchars($quizID); ?>">
                
                <div class="form-group">
                    <label for="rating">Rating (1-5):</label>
                    <select name="rating" required>
                        <option value="">-- Select Rating --</option>
                        <option value="5">5 Stars (Excellent)</option>
                        <option value="4">4 Stars (Very Good)</option>
                        <option value="3">3 Stars (Good)</option>
                        <option value="2">2 Stars (Acceptable)</option>
                        <option value="1">1 Star (Poor)</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="comments">Additional Comments:</label>
                    <textarea name="comments" rows="4"></textarea>
                </div>
                
                <button type="submit" class="submit-btn">Submit Feedback</button>
            </form>
            
            <a href="learner-dashboard.php" class="skip-link">Skip Feedback and Return to Dashboard</a>
        </section>
    </main>
</body>
</html>