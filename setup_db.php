<?php
include_once 'includes/db_connect.php';

echo "<h1>Rentmate Database Setup</h1>";

$queries = [
    "Users Table" => "CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        phone VARCHAR(15) UNIQUE NOT NULL,
        name VARCHAR(100),
        email VARCHAR(100) UNIQUE,
        password VARCHAR(255),
        role ENUM('renter', 'lister') NOT NULL,
        occupation VARCHAR(50),
        gender VARCHAR(10),
        bio TEXT,
        quiz_data JSON,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )",
    "OTPs Table" => "CREATE TABLE IF NOT EXISTS otps (
        phone VARCHAR(15) PRIMARY KEY,
        code VARCHAR(6) NOT NULL,
        expiry DATETIME NOT NULL
    )",
    "Listings Table" => "CREATE TABLE IF NOT EXISTS listings (
        id INT AUTO_INCREMENT PRIMARY KEY,
        lister_id INT NOT NULL,
        title VARCHAR(200) NOT NULL,
        price DECIMAL(10, 2) NOT NULL,
        location VARCHAR(200) NOT NULL,
        bhk_type VARCHAR(20) DEFAULT '1BHK',
        status ENUM('Active', 'Sold', 'Inactive') DEFAULT 'Active',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (lister_id) REFERENCES users(id) ON DELETE CASCADE
    )",
    "Bookings Table" => "CREATE TABLE IF NOT EXISTS bookings (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        listing_id INT NOT NULL,
        status ENUM('Pending', 'Confirmed', 'Cancelled') DEFAULT 'Pending',
        order_id VARCHAR(100),
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (listing_id) REFERENCES listings(id) ON DELETE CASCADE
    )",
    "Payments Table" => "CREATE TABLE IF NOT EXISTS payments (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        order_id VARCHAR(100) UNIQUE NOT NULL,
        payment_id VARCHAR(100),
        amount DECIMAL(10, 2) NOT NULL,
        status ENUM('Pending', 'Success', 'Failed') DEFAULT 'Pending',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )"
];

foreach ($queries as $name => $sql) {
    try {
        $conn->exec($sql);
        echo "<p style='color:green;'>✅ $name created or already exists.</p>";
    } catch (PDOException $e) {
        echo "<p style='color:red;'>❌ Error creating $name: " . $e->getMessage() . "</p>";
    }
}

echo "<h2>Setup Complete!</h2>";
?>
