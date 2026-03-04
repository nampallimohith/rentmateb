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

try {
    // Fetch all Active listings
    $stmt = $conn->prepare("SELECT l.*, u.name as lister_name FROM listings l JOIN users u ON l.lister_id = u.id WHERE l.status = 'Active' ORDER BY l.created_at DESC");
    $stmt->execute();
    $listings = $stmt->fetchAll(PDO::FETCH_ASSOC);

    sendResponse("success", "Active listings fetched", $listings);
} catch (Exception $e) {
    sendResponse("error", "Failed to fetch listings: " . $e->getMessage(), null, 500);
}
?>
