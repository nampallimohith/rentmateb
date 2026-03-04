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
    // 1. Verify Lister identity and get ID
    $stmt = $conn->prepare("SELECT id FROM users WHERE phone = ?");
    $stmt->execute([$phone]);
    $lister = $stmt->fetch(PDO::FETCH_ASSOC);

    // 2. Verify Lister owns this listing
    $stmt = $conn->prepare("SELECT id FROM listings WHERE id = ? AND lister_id = ?");
    $stmt->execute([$data->listing_id, $lister['id']]);
    
    if ($stmt->rowCount() == 0) {
        sendResponse("error", "Listing not found or access denied", null, 403);
    }

    // 3. Fetch applicants (Users who booked this listing)
    $sql = "SELECT b.id as booking_id, b.status, b.created_at, u.name, u.phone, u.email, u.occupation 
            FROM bookings b 
            JOIN users u ON b.user_id = u.id 
            WHERE b.listing_id = ? 
            ORDER BY b.created_at DESC";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$data->listing_id]);
    $applicants = $stmt->fetchAll(PDO::FETCH_ASSOC);

    sendResponse("success", "Applicants fetched", $applicants);
} catch (Exception $e) {
    sendResponse("error", "Failed to fetch applicants: " . $e->getMessage(), null, 500);
}
?>
