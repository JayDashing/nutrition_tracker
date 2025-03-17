<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['username'])) {
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit();
}

// Check if water_count was sent
if (!isset($_POST['water_count'])) {
    echo json_encode(['success' => false, 'message' => 'No water count provided']);
    exit();
}

// Get username and water count
$username = $_SESSION['username'];
$water_count = intval($_POST['water_count']);

// Database connection
$servername = "localhost";
$db_username = "root";
$password = "";
$database = "nutrack_db";

$conn = new mysqli($servername, $db_username, $password, $database);
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit();
}

// Check if there's already a water entry for today
$check_sql = "SELECT id FROM water_intake WHERE username = ? AND DATE(created_at) = CURDATE()";
$check_stmt = $conn->prepare($check_sql);
$check_stmt->bind_param("s", $username);
$check_stmt->execute();
$result = $check_stmt->get_result();

if ($result->num_rows > 0) {
    // Update existing record
    $row = $result->fetch_assoc();
    $update_sql = "UPDATE water_intake SET glasses = ?, updated_at = NOW() WHERE id = ?";
    $update_stmt = $conn->prepare($update_sql);
    $update_stmt->bind_param("ii", $water_count, $row['id']);
    $success = $update_stmt->execute();
    $update_stmt->close();
} else {
    // Insert new record
    $insert_sql = "INSERT INTO water_intake (username, glasses, created_at, updated_at) VALUES (?, ?, NOW(), NOW())";
    $insert_stmt = $conn->prepare($insert_sql);
    $insert_stmt->bind_param("si", $username, $water_count);
    $success = $insert_stmt->execute();
    $insert_stmt->close();
}

$check_stmt->close();
$conn->close();

// Also update the session variable
$_SESSION['water_intake'] = $water_count;

echo json_encode(['success' => $success, 'message' => $success ? 'Water intake saved' : 'Failed to save water intake']);
?>