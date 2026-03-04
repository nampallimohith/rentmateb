<?php
include_once 'includes/db_connect.php';

echo "<h1>Lister Role Fixer</h1>";

if (!isset($_GET['phone'])) {
    echo "<p>Please add your phone number to the URL like this: <b>?phone=9876543210</b></p>";
} else {
    $phone = $_GET['phone'];
    try {
        $stmt = $conn->prepare("UPDATE users SET role = 'lister' WHERE phone = ?");
        $stmt->execute([$phone]);
        
        if ($stmt->rowCount() > 0) {
            echo "<p style='color:green;'>✅ Success! User with phone <b>$phone</b> is now a <b>Lister</b>.</p>";
            echo "<p>You can now try creating a listing in Postman.</p>";
        } else {
            echo "<p style='color:red;'>❌ Failed: No user found with phone <b>$phone</b>. Check your phone number in phpMyAdmin.</p>";
        }
    } catch (Exception $e) {
        echo "<p style='color:red;'>❌ Error: " . $e->getMessage() . "</p>";
    }
}
?>
