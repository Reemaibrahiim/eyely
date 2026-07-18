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

if (isset($_SESSION['error'])) {
    $error = $_SESSION['error'];
    unset($_SESSION['error']);
}


require_once "db_connect.php";

$topics = [];
$error = $error ?? '';
$success = $success ?? '';

try {
    $topicQuery = "SELECT id, topicName FROM Topic ORDER BY topicName";
    $topicResult = $connection->query($topicQuery);

    if ($topicResult && $topicResult->num_rows > 0) {
        while ($row = $topicResult->fetch_assoc()) {
            $topics[] = $row;
        }
    } else {
        $error = "No topics available.";
    }
} catch (Exception $e) {
    error_log("Error fetching data: " . $e->getMessage());
    $error = "Error loading data. Please try again.";
}

$connection->close();
?>

<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <link rel="stylesheet" type="text/css" href="style.css">
        <title>Recommend Question - Color Vision</title>
        <link href="https://fonts.googleapis.com/css2?family=Halant:wght@400;500;600;700&display=swap" rel="stylesheet">

        <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.1/jquery.min.js"></script>

        <style>
            .recommend-container {
                max-width: 800px;
                margin: 50px auto;
                padding: 30px;
                background: white;
                border-radius: 15px;
                box-shadow: 0 5px 25px rgba(0,0,0,0.1);
            }

            .logo-section {
                text-align: center;
                margin-bottom: 30px;
            }

            .logo-section img {
                height: 60px;
                margin-bottom: 15px;
            }

            .logo-section h1 {
                color: #3A2165;
                margin-bottom: 10px;
                font-family: 'Halant', serif;
            }

            .logo-section p {
                color: #666;
                font-size: 1.1rem;
            }

            .recommend-form {
                background: #f8f9fa;
                padding: 25px;
                border-radius: 10px;
                border: 1px solid #e9ecef;
            }

            .form-row {
                display: flex;
                gap: 20px;
                margin-bottom: 20px;
            }

            .form-group {
                flex: 1;
            }

            .form-group label {
                display: block;
                margin-bottom: 8px;
                font-weight: 600;
                color: #495057;
                font-family: 'Halant', serif;
            }

            .form-group select,
            .form-group input[type="file"] {
                width: 100%;
                padding: 12px 15px;
                border: 1px solid #ced4da;
                border-radius: 6px;
                font-size: 16px;
                font-family: 'Halant', serif;
                transition: border-color 0.3s;
            }

            .form-group select:focus,
            .form-group input[type="file"]:focus {
                outline: none;
                border-color: #3A2165;
                box-shadow: 0 0 0 3px rgba(58, 33, 101, 0.1);
            }

            .fixed-question {
                background-color: #e9ecef;
                padding: 15px;
                border-radius: 8px;
                margin-bottom: 20px;
                border-left: 4px solid #007bff;
                font-weight: 500;
            }

            .required {
                color: red;
            }

            .form-actions {
                display: flex;
                gap: 15px;
                justify-content: flex-end;
                margin-top: 25px;
            }

            .btn {
                padding: 12px 30px;
                border: none;
                border-radius: 6px;
                font-family: 'Halant', serif;
                font-size: 16px;
                font-weight: 500;
                cursor: pointer;
                transition: all 0.3s ease;
                text-decoration: none;
                display: inline-block;
                text-align: center;
            }

            .btn-outline {
                background: transparent;
                border: 2px solid #6c757d;
                color: #6c757d;
            }

            .btn-outline:hover {
                background: #6c757d;
                color: white;
            }

            .btn {
                background: #3A2165;
                color: white;
            }

            .btn:hover {
                background: #2a184a;
                transform: translateY(-2px);
            }

            .error-message {
                background-color: #f8d7da;
                color: #721c24;
                padding: 12px;
                border-radius: 4px;
                margin-bottom: 20px;
                border: 1px solid #f5c6cb;
            }

            .success-message {
                background-color: #d4edda;
                color: #155724;
                padding: 12px;
                border-radius: 4px;
                margin-bottom: 20px;
                border: 1px solid #c3e6cb;
            }

            .loading {
                opacity: 0.6;
                pointer-events: none;
                position: relative;
            }

            .loading::after {
                content: " ⏳";
                font-size: 14px;
            }

            .success {
                border-color: #28a745 !important;
            }

            .error {
                border-color: #dc3545 !important;
            }

            @media (max-width: 768px) {
                .form-row {
                    flex-direction: column;
                    gap: 10px;
                }

                .recommend-container {
                    margin: 20px auto;
                    padding: 20px;
                }
            }
        </style>
    </head>
    <body>

        <div class="recommend-container">
            <div class="logo-section">
                <a href="index.php"><img src="images/logo.png" alt="Color Vision"></a>
                <h1>Recommend a Question</h1>
                <p>Suggest a new question for an educator's quiz</p>
            </div>

            <?php if ($error): ?>
                <div class="error-message">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="success-message">
                    <?php echo htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>

            <form method="POST" class="recommend-form" enctype="multipart/form-data" action="process-recommendation.php">
                <div class="form-row">
                    <div class="form-group">
                        <label for="topic">Topic <span class="required">*</span></label>
                        <select id="topic" name="topic" required>
                            <option value="">Select a topic</option>
                            <?php foreach ($topics as $topic): ?>
                                <option value="<?php echo $topic['id']; ?>">
                                    <?php echo htmlspecialchars($topic['topicName']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="educator">Educator <span class="required">*</span></label>
                        <select id="educator" name="educator" required disabled>
                            <option value="">Select a topic first</option>
                        </select>
                    </div>
                </div>

                <div class="fixed-question">
                    <strong>Question:</strong> What number do you see in this pattern?
                </div>

                <div class="form-group">
                    <label for="question-figure">Question Figure <span class="required">*</span></label>
                    <input type="file" id="question-figure" name="question-figure" accept="image/*" required>
                    <p style="color: #666; font-size: 0.9rem; margin-top: 5px;">Please upload an image showing the color pattern (JPG, JPEG, PNG, GIF).</p>
                </div>

                <div class="form-group">
                    <label for="correct-answer">Correct Answer <span class="required">*</span></label>
                    <select id="correct-answer" name="correct-answer" required>
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
                    <a href="learner-dashboard.php" class="btn btn-outline">Cancel</a>
                    <button type="submit" name="submit_question" class="btn">Submit Question</button>
                </div>
            </form>
        </div>

        <script>
            $(document).ready(function () {
                const topicSelect = $('#topic');
                const educatorSelect = $('#educator');

                console.log('Document ready - jQuery AJAX system initialized');


                topicSelect.change(function () {
                    const topicID = $(this).val();
                    console.log('Topic selection changed to:', topicID);

                    educatorSelect.html('<option value="">Loading educators...</option>');
                    educatorSelect.prop('disabled', true);
                    educatorSelect.addClass('loading');

                    if (!topicID) {
                        educatorSelect.html('<option value="">Please select a topic first</option>');
                        educatorSelect.prop('disabled', true);
                        educatorSelect.removeClass('loading');
                        return;
                    }

                    console.log('Initiating AJAX request for topic ID:', topicID);

                    $.ajax({
                        type: "GET",
                        url: `get-educators-by-topic.php?topic_id=${topicID}`,
                        dataType: "json", 
                        timeout: 10000, 
                        success: function (data) {
                            console.log('AJAX Success - Received data:', data);

                            educatorSelect.empty(); 
                            educatorSelect.removeClass('loading');

                            if (data.success && data.educators && data.educators.length > 0) {
                                console.log(`Found ${data.educators.length} educators`);

                                educatorSelect.append($('<option value="">Select an educator</option>'));

                                $.each(data.educators, function (index, educator) {
                                    educatorSelect.append(
                                            $('<option></option>')
                                            .val(educator.id)
                                            .text(`${educator.firstName} ${educator.lastName}`)
                                            );
                                });

                                educatorSelect.prop('disabled', false);
                                console.log('Educator dropdown populated successfully');

                            } else {
                                console.log('No educators available for selected topic');
                                educatorSelect.append($('<option value="">No educators found for this topic</option>'));
                                educatorSelect.prop('disabled', true);

                                if (data.error) {
                                    console.error('Server error:', data.error);
                                }
                            }
                        },
                        error: function (xhr, status, error) {
                            console.error('AJAX Error:', {
                                status: status,
                                error: error,
                                responseText: xhr.responseText
                            });

                            educatorSelect.empty();
                            educatorSelect.append($('<option value="">Error loading educators</option>'));
                            educatorSelect.prop('disabled', true);
                            educatorSelect.removeClass('loading');

                            alert('Unable to load educators. Please check your connection and try again.');
                        },
                        complete: function () {
                            console.log('AJAX request completed');
                            educatorSelect.removeClass('loading');
                        }
                    });
                });

                $(window).on('beforeunload', function () {
                    console.log('Page unload - resetting form state');
                });

            });
        </script>
    </body>
</html>