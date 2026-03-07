<?php
// 1. Define the root folder by stepping up TWO levels from the current file
$rootDir = dirname(__DIR__, 2);

// 2. Load Composer and Dotenv using that correct root path
require_once $rootDir . '/vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable($rootDir);
$dotenv->load();

// 3. Pull the credentials OUT of the loaded environment variables
$host = $_ENV['DB_HOST'];
$user = $_ENV['DB_USER'];
$pass = $_ENV['DB_PASS'];
$dbname = 'nutrack_db'; 

// 4. Plug those variables into your connection
$conn = new mysqli($host, $user, $pass, $dbname);

// 5. Check for errors securely
if ($conn->connect_error) {
    error_log("Database connection failed: " . $conn->connect_error);
    die("A critical error occurred while connecting to the database.");
}
?>