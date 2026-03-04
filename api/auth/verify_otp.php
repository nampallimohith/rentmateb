<?php
include_once '../../includes/db_connect.php';
include_once '../../includes/functions.php';
include_once '../../includes/jwt_helper.php';

$data = json_decode(file_get_contents("php://input"));

// Check if identity is provided via phone or token
$phone = $data->phone ?? null;

if (isset($data->token)) {
    $tokenPhone = validateJWT($data->token);
    if ($tokenPhone) {
        $phone = $tokenPhone;
    } else {
        sendResponse("error", "Invalid or expired session token", null, 401);
    }
}

if (!$phone || !isset($data->otp)) {
    sendResponse("error", "Phone (or Token) and OTP are required", null, 400);
}

// 1. Verify OTP using PHP's current time for 100% consistency
$now = date("Y-m-d H:i:s");
$stmt = $conn->prepare("SELECT * FROM otps WHERE phone = ? AND code = ? AND expiry > ?");
$stmt->execute([$phone, $data->otp, $now]);
$otp_record = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$otp_record) {
    // Debugging: If it fails, check if the record exists at all
    $debugStmt = $conn->prepare("SELECT * FROM otps WHERE phone = ?");
    $debugStmt->execute([$phone]);
    $exists = $debugStmt->fetch(PDO::FETCH_ASSOC);
    
    $now = date("Y-m-d H:i:s");
    if (!$exists) {
        sendResponse("error", "OTP not found for this phone ($phone).", null, 404);
    } else {
        sendResponse("error", "Invalid or expired OTP. Server Time: $now, Expiry: " . $exists['expiry'], null, 401);
    }
}

// 2. Fetch User Role for the app
$stmt = $conn->prepare("SELECT role FROM users WHERE phone = ?");
$stmt->execute([$phone]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// 3. Delete OTP after successful verification
$stmt = $conn->prepare("DELETE FROM otps WHERE phone = ?");
$stmt->execute([$phone]);

// 4. Generate Session Token
$token = generateJWT($phone);

sendResponse("success", "OTP verified successfully", [
    "token" => $token,
    "role" => $user['role'],
    "phone" => $phone
]);
?>
