<?php
/**
 * Simple Token Generator (For production, use firebase/php-jwt)
 */
function generateJWT($phone) {
    $header = json_encode(['typ' => 'JWT', 'alg' => 'HS256']);
    $payload = json_encode(['phone' => $phone, 'exp' => time() + 3600]);
    
    $base64UrlHeader = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($header));
    $base64UrlPayload = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($payload));
    
    // In a real app, you would sign this with a secret key
    $signature = hash_hmac('sha256', $base64UrlHeader . "." . $base64UrlPayload, 'YOUR_SECRET_KEY', true);
    $base64UrlSignature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));
    
    return $base64UrlHeader . "." . $base64UrlPayload . "." . $base64UrlSignature;
}

/**
 * Validates the JWT (Placeholder)
 */
function validateJWT($token) {
    $parts = explode('.', $token);
    if (count($parts) != 3) return false;
    
    $payload = json_decode(base64_decode(str_replace(['-', '_'], ['+', '/'], $parts[1])));
    if (!$payload || !isset($payload->exp) || time() > $payload->exp) {
        return false;
    }
    
    return $payload->phone;
}
?>
