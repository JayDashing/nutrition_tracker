<?php
require_once 'init.php';

// Make sure user is logged in
if (!isset($_SESSION['username'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit();
}

verify_csrf();

$username = $_SESSION['username'];

// Get all announcement IDs
$announcements_query = "SELECT id FROM announcements";
$result = $conn->query($announcements_query);

// Store all announcement IDs in the session as read
if ($result && $result->num_rows > 0) {
    $announcement_ids = [];
    while($row = $result->fetch_assoc()) {
        $announcement_id = $row['id'];
        $announcement_ids[] = $announcement_id;
        
        // Insert into announcement_reads table, ignoring if already exists
        $insert_query = "INSERT IGNORE INTO announcement_reads (username, announcement_id) VALUES (?, ?)";
        $stmt = $conn->prepare($insert_query);
        $stmt->bind_param("si", $username, $announcement_id);
        $stmt->execute();
        $stmt->close();
    }
    $_SESSION['read_announcements'] = $announcement_ids;
}

$conn->close();

// Return success response
header('Content-Type: application/json');
echo json_encode(['success' => true]);
?>