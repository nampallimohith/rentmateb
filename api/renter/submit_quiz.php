<?php
include_once '../../includes/db_connect.php';
include_once '../../includes/functions.php';
include_once '../../includes/jwt_helper.php';

// Auth Check
$headers = getallheaders();
$token = isset($headers['Authorization']) ? str_replace('Bearer ', '', $headers['Authorization']) : null;
$phone = validateJWT($token);

if (!$phone) {
    sendResponse("error", "Unauthorized access", null, 401);
}

$data = json_decode(file_get_contents("php://input"));

if (!isset($data->answers) || !is_array($data->answers)) {
    sendResponse("error", "Answers are required", null, 400);
}

try {
    // 1. Get User ID
    $stmt = $conn->prepare("SELECT id FROM users WHERE phone = ?");
    $stmt->execute([$phone]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // 2. Store or Update Quiz Answers (Simplified: JSON storage)
    $stmt = $conn->prepare("UPDATE users SET quiz_data = ? WHERE id = ?");
    $stmt->execute([json_encode($data->answers), $user['id']]);

    sendResponse("success", "Quiz submitted successfully");
} catch (Exception $e) {
    sendResponse("error", "Failed to submit quiz: " . $e->getMessage(), null, 500);
}
?>
