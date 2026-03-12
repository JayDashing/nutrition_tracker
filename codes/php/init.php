<?php
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);

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
        if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
            
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

/**
 * Rate limiting function to prevent brute force attacks (Session-based)
 * @param string $action - Action type (login, otp_verify, otp_resend)
 * @param int $max_attempts - Maximum attempts allowed
 * @param int $time_window - Time window in seconds
 * @return array - ['allowed' => bool, 'remaining' => int, 'retry_after' => int]
 */
function check_rate_limit($action, $max_attempts = 5, $time_window = 900) {
    // Initialize rate limit storage in session if not exists
    if (!isset($_SESSION['rate_limits'])) {
        $_SESSION['rate_limits'] = [];
    }
    
    if (!isset($_SESSION['rate_limits'][$action])) {
        $_SESSION['rate_limits'][$action] = [];
    }
    
    $current_time = time();
    $cutoff_time = $current_time - $time_window;
    
    // Clean old attempts (older than time window)
    $_SESSION['rate_limits'][$action] = array_filter(
        $_SESSION['rate_limits'][$action],
        function($attempt) use ($cutoff_time) {
            return $attempt['time'] >= $cutoff_time;
        }
    );
    
    // Count recent attempts
    $attempts = count($_SESSION['rate_limits'][$action]);
    $remaining = max(0, $max_attempts - $attempts);
    
    if ($attempts >= $max_attempts) {
        // Find the oldest attempt to calculate retry_after
        $oldest_attempt = min(array_column($_SESSION['rate_limits'][$action], 'time'));
        $retry_after = ($oldest_attempt + $time_window) - $current_time;
        
        return [
            'allowed' => false,
            'remaining' => 0,
            'retry_after' => max(0, $retry_after)
        ];
    }
    
    return [
        'allowed' => true,
        'remaining' => $remaining - 1, // -1 because this attempt will be recorded
        'retry_after' => 0
    ];
}

/**
 * Record a rate limit attempt (Session-based)
 */
function record_rate_limit($action) {
    if (!isset($_SESSION['rate_limits'])) {
        $_SESSION['rate_limits'] = [];
    }
    
    if (!isset($_SESSION['rate_limits'][$action])) {
        $_SESSION['rate_limits'][$action] = [];
    }
    
    $_SESSION['rate_limits'][$action][] = [
        'time' => time()
    ];
}

/**
 * Clear rate limit attempts for an action (call on successful login/verification)
 */
function clear_rate_limit($action) {
    if (isset($_SESSION['rate_limits'][$action])) {
        unset($_SESSION['rate_limits'][$action]);
    }
}
?>