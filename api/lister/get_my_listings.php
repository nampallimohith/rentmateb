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
    sendResponse("error", "Unauthorized: Please provide a valid Bearer token", null, 401);
}

try {
    // 1. Get User and verify role
    $stmt = $conn->prepare("SELECT id, role FROM users WHERE phone = ?");
    $stmt->execute([$phone]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        sendResponse("error", "User account not found for this token", null, 404);
    }

    if ($user['role'] !== 'lister') {
        sendResponse("error", "Access Denied: Only listers can view their listings", null, 403);
    }

    // 2. Fetch all listings for this lister
    $stmt = $conn->prepare("SELECT * FROM listings WHERE lister_id = ? ORDER BY created_at DESC");
    $stmt->execute([$user['id']]);
    $listings = $stmt->fetchAll(PDO::FETCH_ASSOC);

    sendResponse("success", "Listings fetched", $listings);
} catch (Exception $e) {
    sendResponse("error", "Database Error: " . $e->getMessage(), null, 500);
}
?>
