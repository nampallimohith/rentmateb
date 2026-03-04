<?php
include_once '../../includes/db_connect.php';
include_once '../../includes/functions.php';

$data = json_decode(file_get_contents("php://input"));

if (empty($data->email)) {
    sendResponse("error", "Email address is required", null, 400);
}

// 1. Verify Account Exists and Get Phone
$stmt = $conn->prepare("SELECT phone FROM users WHERE email = ?");
$stmt->execute([$data->email]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    sendResponse("error", "Account not found with this email", null, 404);
}

// 2. Send Reset OTP to the linked phone
$otp = generateAndSendOTP($user['phone'], $conn);

sendResponse("success", "Reset code sent to your registered phone number", ["email" => $data->email]);
?>
