<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

header("Content-Type: text/html");
echo "<html><head><title>Rentmate Fixer</title></head><body style='font-family: sans-serif; padding: 20px; line-height: 1.6;'>";
echo "<h1>🔧 Rentmate Backend Health Check</h1>";

// 1. Database Connection
echo "<h3>1. Database Connection</h3>";
include_once 'includes/db_connect.php';
try {
    $conn->query("SELECT 1");
    echo "✅ Connection Successful!";
} catch (Exception $e) {
    echo "❌ Connection Failed: " . $e->getMessage();
    echo "</body></html>"; exit;
}

// 2. Table Checks
echo "<h3>2. Table Status</h3>";
$tables = ['users', 'otps', 'listings', 'bookings', 'payments'];
foreach ($tables as $t) {
    try {
        $conn->query("SELECT 1 FROM $t LIMIT 1");
        echo "✅ Table '$t' exists.<br>";
    } catch (Exception $e) {
        echo "❌ Table '$t' is missing! Run <a href='setup_db.php'>setup_db.php</a><br>";
    }
}

// 3. Header Check
echo "<h3>3. Authorization Header Detection</h3>";
function get_auth() {
    if (isset($_SERVER['Authorization'])) return $_SERVER['Authorization'];
    if (isset($_SERVER['HTTP_AUTHORIZATION'])) return $_SERVER['HTTP_AUTHORIZATION'];
    if (function_exists('getallheaders')) {
        $h = getallheaders();
        return $h['Authorization'] ?? $h['authorization'] ?? null;
    }
    return null;
}
$auth = get_auth();
if ($auth) {
    echo "✅ Header detected: <code style='background:#eee; padding:2px;'>$auth</code>";
} else {
    echo "❌ No Authorization header found. <b>If you are using Postman, make sure you added the header!</b><br>";
    echo "💡 Try adding this to your Apache config or .htaccess if not already there:<br>";
    echo "<pre style='background:#f4f4f4; padding:10px;'>RewriteEngine On\nRewriteCond %{HTTP:Authorization} ^(.*)\nRewriteRule .* - [e:HTTP_AUTHORIZATION:%1]</pre>";
}

// 4. Token Check
echo "<h3>4. Token & User Status</h3>";
include_once 'includes/jwt_helper.php';
if ($auth) {
    $token = str_replace('Bearer ', '', $auth);
    $phone = validateJWT($token);
    if ($phone) {
        echo "✅ Token valid for phone: <b>$phone</b><br>";
        $stmt = $conn->prepare("SELECT role, name FROM users WHERE phone = ?");
        $stmt->execute([$phone]);
        $u = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($u) {
            echo "✅ User found: <b>" . ($u['name'] ?? 'No Name') . "</b><br>";
            echo "✅ Role: <b>" . $u['role'] . "</b> ";
            if ($u['role'] !== 'lister') {
                echo "⚠️ (Warning: This user CANNOT create listings. Change role to 'lister' in phpMyAdmin)";
            }
        } else {
            echo "❌ No user found in database for phone: $phone";
        }
    } else {
        echo "❌ Token validation failed. It might be expired or the secret key is wrong.";
    }
} else {
    echo "⚪ Provide a token in Postman headers to check user status.";
}

echo "<br><br><hr><p><b>Next Step:</b> Try creating a listing in Postman and paste the error here if it still fails.</p>";
echo "</body></html>";
?>
