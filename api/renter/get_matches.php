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

// Logic to fetch matches
$matches = [
    ["name" => "Rahul Sharma", "match_percentage" => 85, "occupation" => "Engineer"],
    ["name" => "Sneha Kapoor", "match_percentage" => 92, "occupation" => "Designer"]
];

sendResponse("success", "Matches fetched", $matches);
?>
