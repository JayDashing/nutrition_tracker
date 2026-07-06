<?php
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$rootDir = dirname(__DIR__, 2);

require_once $rootDir . '/vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable($rootDir);
$dotenv->load();

$host = $_ENV['DB_HOST'];
$user = $_ENV['DB_USER'];
$pass = $_ENV['DB_PASS'];
$dbname = 'nutrack_db'; 

$conn = new mysqli($host, $user, $pass, $dbname);

try {
    $conn = new mysqli($host, $user, $pass, $dbname);
    
    $conn->set_charset("utf8mb4");

} catch (\mysqli_sql_exception $e) {
    error_log("Database connection failed: " . $e->getMessage());

    die("A critical error occurred while connecting to the database.");
}
?>