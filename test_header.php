<?php
header("Content-Type: application/json");

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

$auth = get_auth_header();

echo json_encode([
    "method" => $_SERVER['REQUEST_METHOD'],
    "authorization_header_found" => ($auth !== null),
    "header_value" => $auth,
    "hint" => $auth === null ? "Check Postman Headers. Add Key: 'Authorization', Value: 'Bearer your_token'" : "Header detected correctly!"
]);
?>
