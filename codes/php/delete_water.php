<?php
require_once 'init.php';

header('Content-Type: application/json');

verify_csrf();

// Check if user is logged in
if (!isset($_SESSION['username'])) {
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method. Must be POST.']);
    exit();
}
if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    echo json_encode(['success' => false, 'message' => 'Security token invalid or expired.']);
    exit();
}

// Get username
$username = $_SESSION['username'];

// Check if there's a water entry for today
$check_sql = "SELECT id, glasses FROM water_intake WHERE username = ? AND DATE(created_at) = CURDATE()";
$check_stmt = $conn->prepare($check_sql);
$check_stmt->bind_param("s", $username);
$check_stmt->execute();
$result = $check_stmt->get_result();

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    
    // If already at 0, don't do anything
    if ($row['glasses'] <= 0) {
        echo json_encode(['success' => true, 'message' => 'Water intake already at 0', 'current' => 0]);
        $check_stmt->close();
        $conn->close();
        exit();
    }
    
    // Decrease by 1
    $new_value = $row['glasses'] - 1;
    
    // Update existing record
    $update_sql = "UPDATE water_intake SET glasses = ?, updated_at = NOW() WHERE id = ?";
    $update_stmt = $conn->prepare($update_sql);
    $update_stmt->bind_param("ii", $new_value, $row['id']);
    $success = $update_stmt->execute();
    $update_stmt->close();
    
    // Update session
    $_SESSION['water_intake'] = $new_value;
    
    echo json_encode(['success' => $success, 'message' => $success ? 'Water intake decreased' : 'Failed to decrease water intake', 'current' => $new_value]);
} else {
    // No record found for today, nothing to decrease
    echo json_encode(['success' => true, 'message' => 'No water intake recorded for today', 'current' => 0]);
}

$check_stmt->close();
$conn->close();
?>