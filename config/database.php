<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'book_archive');
define('DB_USER', 'root');
define('DB_PASS', '');

// Create database if it doesn't exist
try {
    $pdo = new PDO("mysql:host=" . DB_HOST, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Create database
    $pdo->exec("CREATE DATABASE IF NOT EXISTS " . DB_NAME);
    $pdo->exec("USE " . DB_NAME);
    
    // Create users table
    $pdo->exec("CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) NOT NULL UNIQUE,
        email VARCHAR(100) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        api_key VARCHAR(64) NOT NULL UNIQUE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    
    // Create collections table
    $pdo->exec("CREATE TABLE IF NOT EXISTS collections (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        name VARCHAR(100) NOT NULL,
        description TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )");
    
    // Create items table
    $pdo->exec("CREATE TABLE IF NOT EXISTS items (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        collection_id INT,
        type ENUM('book', 'paper') NOT NULL,
        title VARCHAR(255) NOT NULL,
        author VARCHAR(255),
        description TEXT,
        cover_url VARCHAR(255),
        status ENUM('wishlist', 'reading', 'read') DEFAULT 'wishlist',
        rating INT,
        notes TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (collection_id) REFERENCES collections(id) ON DELETE SET NULL
    )");
    
    // Create tags table
    $pdo->exec("CREATE TABLE IF NOT EXISTS tags (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(50) NOT NULL UNIQUE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    
    // Create item_tags table
    $pdo->exec("CREATE TABLE IF NOT EXISTS item_tags (
        item_id INT NOT NULL,
        tag_id INT NOT NULL,
        PRIMARY KEY (item_id, tag_id),
        FOREIGN KEY (item_id) REFERENCES items(id) ON DELETE CASCADE,
        FOREIGN KEY (tag_id) REFERENCES tags(id) ON DELETE CASCADE
    )");
    
} catch(PDOException $e) {
    die("Database error: " . $e->getMessage());
}
?> 