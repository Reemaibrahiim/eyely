<?php
session_start();
require_once "db_connect.php";

// ---- SIGN UP ----
if (isset($_POST['signup'])) {
    $firstName = $_POST['firstName'];
    $lastName = $_POST['lastName'];
    $email = $_POST['emailAddress'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $userType = $_POST['userType'];

    // Handle image upload
    $photoFileName = "default.jpg";
    if (!empty($_FILES['photoFile']['name'])) {
        $uploadDir = "images/";
        $photoFileName = uniqid() . "_" . basename($_FILES["photoFile"]["name"]);
        move_uploaded_file($_FILES["photoFile"]["tmp_name"], $uploadDir . $photoFileName);
    }

    // Check duplicate email
    $checkEmail = $connection->prepare("SELECT * FROM Users WHERE emailAddress = ?");
    $checkEmail->bind_param("s", $email);
    $checkEmail->execute();
    $result = $checkEmail->get_result();

    if ($result->num_rows > 0) {
        header("Location: Sign-up.php?error=email_exists");
        exit();
    }

    // Insert user
    $stmt = $connection->prepare("INSERT INTO Users (firstName, lastName, emailAddress, password, photoFileName, userType) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssss", $firstName, $lastName, $email, $password, $photoFileName, $userType);
    $stmt->execute();
    $userId = $stmt->insert_id;

    $_SESSION['userID'] = $userId;
    $_SESSION['userType'] = $userType;

    // If educator → create quizzes ONLY for selected topics
    if ($userType === "educator") {
        // Get selected topics from the form
        $selectedTopics = $_POST['topics'] ?? [];
        
        if (!empty($selectedTopics)) {
            // Create quizzes only for selected topics
            foreach ($selectedTopics as $topicId) {
                $insertQuiz = $connection->prepare("INSERT INTO Quiz (educatorID, topicID) VALUES (?, ?)");
                $insertQuiz->bind_param("ii", $userId, $topicId);
                $insertQuiz->execute();
                $insertQuiz->close();
            }
        }
    }

    if ($userType === "learner") {
        header("Location: learner-dashboard.php");
    } else {
        header("Location: educator-dashboard.php");
    }
    exit();
}

// ---- LOG IN ----
if (isset($_POST['login'])) {
    $email = $_POST['emailAddress'];
    $password = $_POST['password'];
    $userType = $_POST['userType']; 

    $stmt = $connection->prepare("SELECT * FROM Users WHERE emailAddress = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();

        if (password_verify($password, $user['password'])) {
            if ($user['userType'] === $userType) {
                // type of user is correcr
                $_SESSION['userID'] = $user['id'];
                $_SESSION['userType'] = $user['userType'];

                if ($user['userType'] === 'educator') {
                    header("Location: educator-dashboard.php");
                } else {
                    header("Location: learner-dashboard.php");
                }
                exit();
            } else {
                // type of user is incorrecr
                header("Location: login.php?error=wrong_user_type");
                exit();
            }
        } else {
            // the password is incorrecr
            header("Location: login.php?error=wrong_password");
            exit();
        }
    } else {
        // the user not found
        header("Location: login.php?error=user_not_found");
        exit();
    }
}

?>