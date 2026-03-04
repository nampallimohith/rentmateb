<?php
include_once '../../includes/db_connect.php';
include_once '../../includes/functions.php';

// Razorpay or Stripe will send a POST request with a JSON payload
$data = json_decode(file_get_contents("php://input"));

// 1. Verify Webhook Signature (Crucial for production security)
// Example logic for Razorpay:
// $expectedSignature = $_SERVER['HTTP_X_RAZORPAY_SIGNATURE'];
// verifySignature($payload, $expectedSignature, $webhookSecret);

if (isset($data->event) && $data->event == 'payment.captured') {
    $order_id = $data->payload->payment->entity->order_id;
    $payment_id = $data->payload->payment->entity->id;
    
    try {
        // 2. Update Payment Status in DB
        $stmt = $conn->prepare("UPDATE payments SET status = 'Success', payment_id = ? WHERE order_id = ?");
        $stmt->execute([$payment_id, $order_id]);
        
        // 3. Update related booking status if needed
        $stmt = $conn->prepare("UPDATE bookings SET status = 'Confirmed' WHERE order_id = ?");
        $stmt->execute([$order_id]);

        http_response_code(200);
        echo json_encode(["status" => "success", "message" => "Webhook processed"]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(["status" => "error", "message" => "Webhook processing failed"]);
    }
} else {
    // Other events or invalid events
    http_response_code(200);
    echo json_encode(["status" => "ignored"]);
}
?>
