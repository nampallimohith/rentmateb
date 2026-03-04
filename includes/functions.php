<?php
/**
 * Set Timezone
 */
date_default_timezone_set("Asia/Kolkata");

/**
 * Generates a random 6-digit OTP, stores it in the database with an expiry,
 * and sends it via SMS (placeholder logic).
 */
function generateAndSendOTP($phone, $conn) {
    $otp = rand(100000, 999999);
    $expiry = date("Y-m-d H:i:s", strtotime("+10 minutes"));
    
    // Store in DB
    $sql = "INSERT INTO otps (phone, code, expiry) VALUES (?, ?, ?) 
            ON DUPLICATE KEY UPDATE code=?, expiry=?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$phone, $otp, $expiry, $otp, $expiry]);
    
    // INTEGRATION POINT: Call your SMS Gateway here
    // Example (fast2sms): 
    // $url = "https://www.fast2sms.com/dev/bulkV2?authorization=API_KEY&route=otp&variables_values=$otp&flash=0&numbers=$phone";
    // file_get_contents($url);
    
    return $otp; // Return for development purposes
}

/**
 * Validates input data
 */
function validateInput($data, $fields) {
    if (!$data) return false;
    foreach ($fields as $field) {
        if (!isset($data->$field) || (is_string($data->$field) && trim($data->$field) === "")) {
            return false;
        }
    }
    return true;
}

/**
 * Common response sending function
 */
function sendResponse($status, $message, $data = null, $code = 200) {
    http_response_code($code);
    echo json_encode([
        "status" => $status,
        "message" => $message,
        "data" => $data
    ]);
    exit();
}
?>
