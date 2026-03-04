<?php
include_once '../../includes/db_connect.php';
include_once '../../includes/functions.php';

$data = json_decode(file_get_contents("php://input"));

// The frontend should send 'full_name', 'email', 'password', 'phone', and 'role'
if (!validateInput($data, ['phone', 'role', 'full_name', 'email', 'password'])) {
    sendResponse("error", "Missing required registration details", null, 400);
}

// 1. Check if user already exists
$stmt = $conn->prepare("SELECT id FROM users WHERE phone = ? OR email = ?");
$stmt->execute([$data->phone, $data->email]);

if ($stmt->rowCount() > 0) {
    sendResponse("error", "User with this phone or email already registered", null, 409);
}

// 2. Hash Password and Create User
// Trim password to remove any whitespace/newline characters
$clean_password = trim($data->password);
$hashed_password = password_hash($clean_password, PASSWORD_DEFAULT);
$stmt = $conn->prepare("INSERT INTO users (phone, name, email, password, role, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
$stmt->execute([$data->phone, $data->full_name, $data->email, $hashed_password, $data->role]);

// 3. Generate and Send OTP
$otp = generateAndSendOTP($data->phone, $conn);

sendResponse("success", "Registration successful. OTP sent.", ["otp_sent" => true]);
?>
