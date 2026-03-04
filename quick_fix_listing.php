<?php
include_once 'includes/db_connect.php';

echo "<h1>🚀 Rentmate Listing Quick-Fix</h1>";

if (!isset($_GET['phone'])) {
    echo "<p style='color:orange;'>Please add your phone number to the URL: <b>?phone=XXXXXXXXXX</b></p>";
    exit;
}

$phone = $_GET['phone'];

try {
    // 1. Ensure User is a Lister
    $stmt = $conn->prepare("UPDATE users SET role = 'lister' WHERE phone = ?");
    $stmt->execute([$phone]);
    
    // 2. Get User ID
    $stmt = $conn->prepare("SELECT id FROM users WHERE phone = ?");
    $stmt->execute([$phone]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        echo "<p style='color:red;'>❌ ERROR: No user found with phone $phone. Please register first.</p>";
        exit;
    }

    echo "<p style='color:green;'>✅ User role updated to 'Lister'.</p>";

    // 3. Create a test listing automatically
    $title = "Test Property " . rand(1, 100);
    $price = 5000;
    $location = "Chennai";

    $sql = "INSERT INTO listings (lister_id, title, price, location, bhk_type, status, created_at) 
            VALUES (?, ?, ?, ?, '1BHK', 'Active', NOW())";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$user['id'], $title, $price, $location]);

    echo "<p style='color:green;'>✅ Success! Automatically created a test listing for you.</p>";
    echo "<p>Now, try checking your listings in Postman using the <b>GET</b> method on: <br>
    <code>http://localhost/rentmate-backend/api/lister/get_my_listings.php</code></p>";

} catch (Exception $e) {
    echo "<p style='color:red;'>❌ DATABASE ERROR: " . $e->getMessage() . "</p>";
    echo "<p>If the error says 'Table doesn't exist', please run <a href='setup_db.php'>setup_db.php</a> first.</p>";
}
?>
