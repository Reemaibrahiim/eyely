<?php
session_start();
require_once "db_connect.php";

header('Content-Type: application/json');


if (!isset($_SESSION['userID'])) {
    echo json_encode(['success' => false, 'error' => 'Authentication required']);
    exit();
}


if (!isset($_GET['topic_id']) || empty($_GET['topic_id'])) {
    echo json_encode(['success' => false, 'error' => 'Topic ID is required']);
    exit();
}

$topicID = filter_var($_GET['topic_id'], FILTER_VALIDATE_INT);

if ($topicID === false || $topicID <= 0) {
    echo json_encode(['success' => false, 'error' => 'Invalid Topic ID']);
    exit();
}

$educators = [];

try {

    $educatorQuery = "
        SELECT DISTINCT u.id, u.firstName, u.lastName 
        FROM Users u
        JOIN Quiz q ON u.id = q.educatorID
        WHERE u.userType = 'educator' AND q.topicID = ?
        ORDER BY u.firstName, u.lastName
    ";
    
    $stmt = $connection->prepare($educatorQuery);
    
    if (!$stmt) {
        throw new Exception("Database prepare failed: " . $connection->error);
    }
    
    $stmt->bind_param("i", $topicID);
    
    if (!$stmt->execute()) {
        throw new Exception("Execute failed: " . $stmt->error);
    }
    
    $result = $stmt->get_result();
    
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $educators[] = [
                'id' => $row['id'],
                'firstName' => htmlspecialchars($row['firstName']),
                'lastName' => htmlspecialchars($row['lastName'])
            ];
        }
    }
    
    $stmt->close();
    

    echo json_encode([
        'success' => true,
        'educators' => $educators,
        'count' => count($educators)
    ]);
    
} catch (Exception $e) {
    error_log("Error fetching educators: " . $e->getMessage());
    

    echo json_encode([
        'success' => false,
        'error' => 'Error loading educators. Please try again.',
        'debug_message' => $e->getMessage()
    ]);
}

$connection->close();
?>