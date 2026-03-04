<?php
include_once '../../includes/db_connect.php';
include_once '../../includes/functions.php';

$data = json_decode(file_get_contents("php://input"));

if (!validateInput($data, ['email', 'password', 'role'])) {
    sendResponse("error", "Email, password, and role are required", null, 400);
}

// 1. Verify User, Password and Role
$stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
$stmt->execute([$data->email]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user || $user['role'] !== $data->role) {
    sendResponse("error", "Account not found for this role", null, 404);
}

// 2. Verify Password Hashed in Register
// Trim password to remove any whitespace/newline characters
$clean_password = trim($data->password);
if (password_verify($clean_password, $user['password'])) {
    include_once '../../includes/jwt_helper.php';
    $token = generateJWT($user['phone']);
    
    sendResponse("success", "Login successful", [
        "token" => $token,
        "role" => $user['role'],
        "name" => $user['name'],
        "email" => $user['email']
    ]);
} else {
    sendResponse("error", "Invalid password", null, 401);
}
?>
