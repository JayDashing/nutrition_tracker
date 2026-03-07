<?php
require_once 'db.php';
$admin_username = getenv('ADMIN_USERNAME') ?: 'nutriadmin';
$raw_password = getenv('ADMIN_SETUP_PASSWORD');

if (!$raw_password) {
    error_log("Security Alert: Setup attempted without ADMIN_SETUP_PASSWORD set.");
    die("Configuration error: Cannot proceed with setup.");
}

$admin_password = password_hash($raw_password, PASSWORD_DEFAULT);
$admin_role = 'admin';

$check_query = "SELECT id FROM users WHERE username = ?";
$stmt = $conn->prepare($check_query);

if (!$stmt) {
    error_log("Database prepare error on check_query: " . $conn->error);
    die("An internal system error occurred.");
}

$stmt->bind_param("s", $admin_username);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows == 0) {
    $stmt->close();

    $insert_query = "INSERT INTO users (username, password, role) VALUES (?, ?, ?)";
    $insert_stmt = $conn->prepare($insert_query);
    
    if (!$insert_stmt) {
        error_log("Database prepare error on insert_query: " . $conn->error);
        die("An internal system error occurred.");
    }
    
    $insert_stmt->bind_param("sss", $admin_username, $admin_password, $admin_role);
    
    if ($insert_stmt->execute()) {
        echo "Admin account created successfully!";
    } else {
        error_log("Database execute error on insert_query: " . $insert_stmt->error);
        echo "An error occurred while creating the account.";
    }
    
    $insert_stmt->close();
} else {
    echo "Admin account already exists!";
    $stmt->close();
}

$conn->close();
?>