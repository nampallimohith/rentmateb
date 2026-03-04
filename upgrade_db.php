<?php
include_once 'includes/db_connect.php';

echo "<h1>🛠️ Database Schema Upgrader</h1>";

function addColumnIfMissing($conn, $table, $column, $definition) {
    try {
        $conn->query("SELECT $column FROM $table LIMIT 1");
        echo "✅ Column '$column' already exists in '$table'.<br>";
    } catch (PDOException $e) {
        try {
            $conn->exec("ALTER TABLE $table ADD COLUMN $column $definition");
            echo "<p style='color:green;'>➕ Added column '$column' to '$table'.</p>";
        } catch (PDOException $ex) {
            echo "<p style='color:red;'>❌ Failed to add '$column': " . $ex->getMessage() . "</p>";
        }
    }
}

// Ensure Users table has all columns
addColumnIfMissing($conn, 'users', 'password', 'VARCHAR(255) AFTER email');
addColumnIfMissing($conn, 'users', 'role', "ENUM('renter', 'lister') NOT NULL DEFAULT 'renter' AFTER password");
addColumnIfMissing($conn, 'users', 'occupation', 'VARCHAR(50)');
addColumnIfMissing($conn, 'users', 'gender', 'VARCHAR(10)');
addColumnIfMissing($conn, 'users', 'bio', 'TEXT');

// Ensure Listings table is solid
try {
    $conn->exec("CREATE TABLE IF NOT EXISTS listings (
        id INT AUTO_INCREMENT PRIMARY KEY,
        lister_id INT NOT NULL,
        title VARCHAR(200) NOT NULL,
        price DECIMAL(10, 2) NOT NULL,
        location VARCHAR(200) NOT NULL,
        bhk_type VARCHAR(20) DEFAULT '1BHK',
        status ENUM('Active', 'Sold', 'Inactive') DEFAULT 'Active',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (lister_id) REFERENCES users(id) ON DELETE CASCADE
    )");
    echo "✅ Listings table is ready.<br>";
} catch (Exception $e) {
    echo "❌ Listings Error: " . $e->getMessage() . "<br>";
}

echo "<h2>Database is now Up-to-Date!</h2>";
?>
