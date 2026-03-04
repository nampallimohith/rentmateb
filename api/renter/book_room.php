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

if (!isset($data->listing_id)) {
    sendResponse("error", "Listing ID is required", null, 400);
}

try {
    // 1. Get User ID
    $stmt = $conn->prepare("SELECT id FROM users WHERE phone = ?");
    $stmt->execute([$phone]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // 2. Create Booking Entry
    $stmt = $conn->prepare("INSERT INTO bookings (user_id, listing_id, status, created_at) VALUES (?, ?, 'Pending', NOW())");
    $stmt->execute([$user['id'], $data->listing_id]);

    sendResponse("success", "Booking request submitted", ["booking_id" => $conn->lastInsertId()]);
} catch (Exception $e) {
    sendResponse("error", "Failed to submit booking: " . $e->getMessage(), null, 500);
}
?>
