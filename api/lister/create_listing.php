<?php
// Set headers for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

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

if (!$token) {
    sendResponse("error", "Debug: No Token found in Authorization Header. Make sure you use 'Bearer <token>'", null, 401);
}

$phone = validateJWT($token);
if (!$phone) {
    sendResponse("error", "Debug: Token validation failed. It might be expired or the secret key is wrong.", null, 401);
}

$raw_input = file_get_contents("php://input");
$data = json_decode($raw_input);

if (!$data) {
    sendResponse("error", "Debug: Invalid JSON body. Received: " . $raw_input, null, 400);
}

if (!validateInput($data, ['title', 'price', 'location'])) {
    sendResponse("error", "Debug: Missing fields. Required: title, price, location. Received: " . $raw_input, null, 400);
}

try {
    $stmt = $conn->prepare("SELECT id, role FROM users WHERE phone = ?");
    $stmt->execute([$phone]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        sendResponse("error", "Debug: No user found in database for phone: $phone", null, 404);
    }

    if ($user['role'] !== 'lister') {
        sendResponse("error", "Debug: Access Denied. Your role is '" . $user['role'] . "', but only 'lister' can create listings.", null, 403);
    }

    $sql = "INSERT INTO listings (lister_id, title, price, location, bhk_type, status, created_at) 
            VALUES (?, ?, ?, ?, ?, 'Active', NOW())";
    $stmt = $conn->prepare($sql);
    $stmt->execute([
        $user['id'],
        $data->title,
        $data->price,
        $data->location,
        $data->bhk_type ?? '1BHK'
    ]);

    sendResponse("success", "Listing created successfully", ["listing_id" => $conn->lastInsertId()]);
} catch (PDOException $e) {
    sendResponse("error", "DATABASE ERROR: " . $e->getMessage() . " Code: " . $e->getCode(), null, 500);
} catch (Exception $e) {
    sendResponse("error", "GENERAL ERROR: " . $e->getMessage(), null, 500);
}
?>
