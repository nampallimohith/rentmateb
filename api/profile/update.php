<?php
include_once '../../includes/db_connect.php';
include_once '../../includes/functions.php';
include_once '../../includes/jwt_helper.php';

function get_auth_header() {
    $headers = null;
    if (isset($_SERVER['Authorization'])) {
        $headers = $_SERVER['Authorization'];
    } elseif (isset($_SERVER['HTTP_AUTHORIZATION'])) {
        $headers = $_SERVER['HTTP_AUTHORIZATION'];
    } elseif (function_exists('getallheaders')) {
        $all_headers = getallheaders();
        if (isset($all_headers['Authorization'])) {
            $headers = $all_headers['Authorization'];
        } elseif (isset($all_headers['authorization'])) {
            $headers = $all_headers['authorization'];
        }
    }
    return $headers;
}

$token_header = get_auth_header();
$token = $token_header ? str_replace('Bearer ', '', $token_header) : null;
$phone = validateJWT($token);

if (!$phone) {
    sendResponse("error", "Unauthorized access", null, 401);
}

$data = json_decode(file_get_contents("php://input"));

try {
    // Map 'full_name' or 'name' to the 'name' column in DB
    $updateFields = [];
    $params = [];

    if (isset($data->full_name)) { $updateFields[] = "name = ?"; $params[] = $data->full_name; }
    elseif (isset($data->name)) { $updateFields[] = "name = ?"; $params[] = $data->name; }
    
    if (isset($data->email)) { $updateFields[] = "email = ?"; $params[] = $data->email; }
    if (isset($data->occupation)) { $updateFields[] = "occupation = ?"; $params[] = $data->occupation; }
    if (isset($data->gender)) { $updateFields[] = "gender = ?"; $params[] = $data->gender; }
    if (isset($data->bio)) { $updateFields[] = "bio = ?"; $params[] = $data->bio; }

    if (empty($updateFields)) {
        sendResponse("error", "No fields provided for update", null, 400);
    }

    $sql = "UPDATE users SET " . implode(", ", $updateFields) . " WHERE phone = ?";
    $params[] = $phone;

    $stmt = $conn->prepare($sql);
    $stmt->execute($params);

    sendResponse("success", "Profile updated successfully");
} catch (Exception $e) {
    sendResponse("error", "Update failed: " . $e->getMessage(), null, 500);
}
?>
