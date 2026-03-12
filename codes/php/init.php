<?php
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.use_strict_mode', 1);

session_start();

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$rootDir = dirname(__DIR__, 2);
require_once $rootDir . '/vendor/autoload.php';

if (file_exists($rootDir . '/.env')) {
    $dotenv = Dotenv\Dotenv::createImmutable($rootDir);
    $dotenv->load();
}

$host   = $_ENV['DB_HOST'] ?? getenv('DB_HOST');
$user   = $_ENV['DB_USER'] ?? getenv('DB_USER');
$pass   = $_ENV['DB_PASS'] ?? getenv('DB_PASS');
$dbname = 'nutrack_db'; 

try {
    $conn = new mysqli($host, $user, $pass, $dbname);
    $conn->set_charset("utf8mb4");
} catch (\mysqli_sql_exception $e) {
    error_log("Database connection failed: " . $e->getMessage());
    die("A critical error occurred while connecting to the database.");
}

function verify_csrf() {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!isset($_POST['csrf_token']) ||
            !isset($_SESSION['csrf_token']) ||
            !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
            
            $ip = $_SERVER['REMOTE_ADDR'] ?? 'Unknown IP';
            error_log("CSRF validation failed for IP: $ip");
            
            die("Invalid security token. Please go back, refresh the page, and try again.");
        }
    }
}

function csrf_field() {
    if (isset($_SESSION['csrf_token'])) {
        $token = htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8');
        return '<input type="hidden" name="csrf_token" value="' . $token . '">';
    }
    return ''; // Return nothing if the session isn't set up right
}
?>