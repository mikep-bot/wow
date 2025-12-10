<?php
// db_connect.php - Database Connection
$host = 'localhost';
$dbname = 'agristock';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Initialize database tables if not exist
$pdo->exec("
    CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        email VARCHAR(255) UNIQUE NOT NULL,
        password VARCHAR(255) NOT NULL,
        avatar TEXT,
        preferences JSON,
        notifications JSON,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )
");

$pdo->exec("
    CREATE TABLE IF NOT EXISTS products (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        category VARCHAR(255) NOT NULL,
        stock INT NOT NULL,
        price DECIMAL(10,2) NOT NULL,
        description TEXT,
        image TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )
");

$pdo->exec("
    CREATE TABLE IF NOT EXISTS categories (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) UNIQUE NOT NULL,
        description TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        color VARCHAR(7)
    )
");
?>